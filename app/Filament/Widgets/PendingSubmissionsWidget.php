<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Evaluation;
use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class PendingSubmissionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('student');
    }

    public function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $studentId = $user?->getKey();
        if ($studentId === null) {
            return [];
        }

        $nextWeek = now()->addDays(7)->endOfDay();
        $now = now();

        $pendingCount = Evaluation::query()
            ->where('published_at', '<=', $now)
            ->where('due_at', '>=', $now)
            ->where('due_at', '<=', $nextWeek)
            ->whereExists(function (Builder $q) use ($studentId): void {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->where('enrollments.student_id', (int) $studentId)
                    ->where('enrollments.status', 'active');
            })
            ->whereDoesntHave('submissions', function (Builder $q) use ($studentId): void {
                $q->where('submissions.student_id', (int) $studentId)
                    ->where('submissions.status', 'submitted');
            })
            ->count();

        $urgentCount = Evaluation::query()
            ->where('published_at', '<=', $now)
            ->where('due_at', '>=', $now)
            ->where('due_at', '<=', now()->addDays(2)->endOfDay())
            ->whereExists(function (Builder $q) use ($studentId): void {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->where('enrollments.student_id', (int) $studentId)
                    ->where('enrollments.status', 'active');
            })
            ->whereDoesntHave('submissions', function (Builder $q) use ($studentId): void {
                $q->where('submissions.student_id', (int) $studentId)
                    ->where('submissions.status', 'submitted');
            })
            ->count();

        return [
            Stat::make(__('widgets.pending_title'), (string) $pendingCount)
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
