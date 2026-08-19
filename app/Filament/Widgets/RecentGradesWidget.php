<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Grade;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class RecentGradesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'widgets.recent_grades_title';

    protected ?string $pollingInterval = null;

    protected array|string|int $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('student');
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $studentId = $user?->getKey();

        return $table
            ->query(
                Grade::query()
                    ->with(['evaluation.courseOffering.courseTemplate'])
                    ->where('student_id', $studentId !== null ? (int) $studentId : -1)
                    ->orderByDesc('created_at')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('evaluation.courseOffering.courseTemplate.name')
                    ->label(__('widgets.recent_grades_course'))
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('evaluation.title')
                    ->label(__('widgets.recent_grades_evaluation'))
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('score')
                    ->label(__('widgets.recent_grades_score'))
                    ->formatStateUsing(static function (Grade $record): string {
                        $score = $record->getAttributeValue('score');
                        $max = $record->evaluation?->getAttributeValue('max_score');
                        $maxStr = $max === null ? '—' : (string) $max;

                        return "{$score} / {$maxStr}";
                    })
                    ->badge()
                    ->color(static function (Grade $record): string {
                        $score = (float) $record->getAttributeValue('score');
                        $max = $record->evaluation?->getAttributeValue('max_score');
                        if ($max === null || (float) $max <= 0) {
                            return 'gray';
                        }
                        $pct = ($score / (float) $max) * 100;

                        return match (true) {
                            $pct >= 70 => 'success',
                            $pct >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->sortable(false),
                TextColumn::make('created_at')
                    ->label(__('widgets.recent_grades_date'))
                    ->dateTime('M d, Y H:i')
                    ->sortable(false),
            ])
            ->paginated(false);
    }
}
