<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Widgets;

use App\Models\Enrollment;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class ApoderadoLinkedStudentsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

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
        $profileIds = $linked['profileIds'];
        $userIds = $linked['userIds'];

        $activeCount = 0;
        if ($userIds !== []) {
            $activeCount = Enrollment::query()
                ->whereIn('student_id', $userIds)
                ->where('status', 'active')
                ->distinct('student_id')
                ->count('student_id');
        }

        return [
            Stat::make(__('widgets.apoderado_linked_title'), (string) \count($profileIds))
                ->description(
                    \count($profileIds) > 0
                        ? __('widgets.apoderado_linked_description_active', ['count' => $activeCount])
                        : __('widgets.apoderado_linked_description_empty'),
                )
                ->descriptionIcon('heroicon-o-user-group', IconPosition::Before)
                ->color(\count($profileIds) > 0 ? 'info' : 'gray')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
