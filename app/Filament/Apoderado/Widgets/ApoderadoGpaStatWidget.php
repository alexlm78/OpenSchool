<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Widgets;

use App\Models\Grade;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class ApoderadoGpaStatWidget extends BaseWidget
{
    protected static ?int $sort = 2;

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
        if ($profileIds === []) {
            return [];
        }

        /** @var int $count */
        $count = Grade::query()
            ->whereIn('student_id', $profileIds)
            ->count();

        /** @var float|null $sumByMax */
        $sumByMax = Grade::query()
            ->whereIn('student_id', $profileIds)
            ->whereExists(function ($q): void {
                $q->from('evaluations')
                    ->whereColumn('evaluations.id', 'grades.evaluation_id')
                    ->whereNotNull('evaluations.max_score')
                    ->where('evaluations.max_score', '>', 0);
            })
            ->get()
            ->map(static function (Grade $g): ?float {
                $max = $g->evaluation?->getAttributeValue('max_score');
                if ($max === null || (float) $max <= 0) {
                    return null;
                }

                return ((float) $g->getAttributeValue('score')) / (float) $max;
            })
            ->filter(static fn ($v): bool => $v !== null)
            ->avg();

        $gpaPercent = $sumByMax === null
            ? 0.0
            : round((float) $sumByMax * 100, 1);

        $levelColor = 'gray';
        $label = __('widgets.gpa_no_data');
        if ($count > 0) {
            if ($gpaPercent >= 70) {
                $levelColor = 'success';
                $label = __('widgets.gpa_approved');
            } elseif ($gpaPercent >= 50) {
                $levelColor = 'warning';
                $label = __('widgets.gpa_recovery');
            } else {
                $levelColor = 'danger';
                $label = __('widgets.gpa_failing');
            }
        }

        return [
            Stat::make(__('widgets.apoderado_gpa_title'), $count > 0 ? "{$gpaPercent}%" : '—')
                ->description($count > 0 ? __('widgets.apoderado_gpa_description_grades_count', ['count' => $count]) : $label)
                ->descriptionIcon('heroicon-o-academic-cap', IconPosition::Before)
                ->color($levelColor)
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
