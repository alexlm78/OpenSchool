<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class GradeAverageWidget extends BaseWidget
{
    protected static ?int $sort = 1;

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

        /** @var array{total_score: float|null, total_max: float|null, count: int} $agg */
        $agg = Grade::query()
            ->where('student_id', (int) $studentId)
            ->selectRaw('COALESCE(SUM(score), 0) as total_score, COUNT(*) as count')
            ->first()
            ?->toArray() ?? ['total_score' => 0, 'count' => 0];

        $weightedCount = (int) $agg['count'];

        /** @var float|null $sumByMax */
        $sumByMax = Grade::query()
            ->where('student_id', (int) $studentId)
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
        if ($weightedCount > 0) {
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
            Stat::make(__('widgets.gpa_title'), $weightedCount > 0 ? "{$gpaPercent}%" : '—')
                ->description($label)
                ->descriptionIcon('heroicon-o-academic-cap', IconPosition::Before)
                ->color($levelColor)
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
