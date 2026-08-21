<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Widgets;

use App\Models\Evaluation;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class ApoderadoPendingSubmissionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('guardian');
    }

    public function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        $linked = LinkedGuardianStudents::resolveForUser($user);
        $profileIds = $linked['userIds'];
        if ($profileIds === []) {
            return [];
        }

        $nextWeek = now()->addDays(7)->endOfDay();
        $now = now();

        $pendingCount = Evaluation::query()
            ->where('published_at', '<=', $now)
            ->where('due_at', '>=', $now)
            ->where('due_at', '<=', $nextWeek)
            ->whereExists(function (Builder $q) use ($profileIds): void {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->whereIn('enrollments.student_id', $profileIds)
                    ->where('enrollments.status', 'active');
            })
            ->whereDoesntHave('submissions', function (Builder $q) use ($profileIds): void {
                $q->whereIn('submissions.student_id', $profileIds)
                    ->where('submissions.status', 'submitted');
            })
            ->count();

        $urgentCount = Evaluation::query()
            ->where('published_at', '<=', $now)
            ->where('due_at', '>=', $now)
            ->where('due_at', '<=', now()->addDays(2)->endOfDay())
            ->whereExists(function (Builder $q) use ($profileIds): void {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->whereIn('enrollments.student_id', $profileIds)
                    ->where('enrollments.status', 'active');
            })
            ->whereDoesntHave('submissions', function (Builder $q) use ($profileIds): void {
                $q->whereIn('submissions.student_id', $profileIds)
                    ->where('submissions.status', 'submitted');
            })
            ->count();

        return [
            Stat::make(__('widgets.apoderado_pending_title'), (string) $pendingCount)
                ->description(
                    $urgentCount > 0
                        ? __('widgets.pending_description_urgent', ['count' => $urgentCount])
                        : __('widgets.pending_description_ok'),
                )
                ->descriptionIcon('heroicon-o-exclamation-triangle', IconPosition::Before)
                ->color($urgentCount > 0 ? 'warning' : 'success')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
