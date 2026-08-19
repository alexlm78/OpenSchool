<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class ActiveEnrollmentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

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

        $activeCount = Enrollment::query()
            ->where('student_id', (int) $studentId)
            ->where('status', 'active')
            ->count();

        $totalCount = Enrollment::query()
            ->where('student_id', (int) $studentId)
            ->count();

        return [
            Stat::make(__('widgets.active_courses_title'), (string) $activeCount)
                ->description(__('widgets.active_courses_description', ['total' => $totalCount]))
                ->descriptionIcon('heroicon-o-book-open', IconPosition::Before)
                ->color('info')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
