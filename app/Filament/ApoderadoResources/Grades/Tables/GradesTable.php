<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Grades\Tables;

use App\Models\Grade;
use App\Models\Student;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GradesTable
{
    /**
     * @param  array<string, string>  $studentFilterOptions
     * @param  array<string, string>  $evaluationOptions
     * @param  array<string, string>  $courseOfferingOptions
     * @param  array<int, int>  $studentUserIds
     */
    public static function configure(Table $table, array $studentFilterOptions = [], array $evaluationOptions = [], array $courseOfferingOptions = [], array $studentUserIds = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('Student'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('evaluation.title')
                    ->label(__('Evaluation'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('evaluation.max_score')
                    ->label(__('Max Score'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score')
                    ->label(__('Score'))
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
                    ->sortable(),
                TextColumn::make('feedback')
                    ->label(__('Feedback'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
                TextColumn::make('grader.name')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('student')
                    ->label(__('Estudiante'))
                    ->options($studentFilterOptions)
                    ->modifyQueryUsing(function (Builder $query, $state) use ($studentUserIds): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        $profileId = (int) $value;
                        $userId = collect($studentUserIds)->search($profileId, true);
                        $target = $userId === false ? null : (int) $userId;
                        if ($target === null && $profileId > 0) {
                            $found = Student::query()
                                ->where('id', $profileId)
                                ->value('user_id');
                            $target = $found !== null ? (int) $found : null;
                        }

                        return $target !== null ? $query->where('student_id', $target) : $query;
                    }),
                SelectFilter::make('evaluation')
                    ->label(__('Evaluation'))
                    ->options($evaluationOptions)
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->where('evaluation_id', (int) $value);
                    }),
                SelectFilter::make('courseOffering')
                    ->label(__('Course Offering'))
                    ->options($courseOfferingOptions)
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->whereHas('evaluation', function (Builder $q) use ($value) {
                            $q->where('course_offering_id', (int) $value);
                        });
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
