<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Grades\Tables;

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
     */
    public static function configure(Table $table, array $studentFilterOptions = [], array $evaluationOptions = [], array $courseOfferingOptions = []): Table
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
                    ->numeric()
                    ->badge()
                    ->color(function (string $state): string {
                        $num = (float) $state;
                        if ($num >= 70) {
                            return 'success';
                        }
                        if ($num >= 50) {
                            return 'warning';
                        }

                        return 'danger';
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
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->where('student_id', (int) $value);
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
            ]);
    }
}
