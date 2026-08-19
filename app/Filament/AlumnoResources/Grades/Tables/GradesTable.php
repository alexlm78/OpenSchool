<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Grades\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GradesTable
{
    /**
     * @param  array<string, string>  $evaluationOptions
     * @param  array<string, string>  $courseOfferingOptions
     */
    public static function configure(Table $table, array $evaluationOptions = [], array $courseOfferingOptions = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('evaluation.courseOffering.courseTemplate.name')
                    ->label(__('Course'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('evaluation.title')
                    ->label(__('Evaluation'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('evaluation.max_score')
                    ->label(__('Max Score'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('score')
                    ->label(__('Score'))
                    ->numeric()
                    ->badge()
                    ->color(static function (mixed $state): string {
                        $num = (float) $state;
                        if ($num >= 70) {
                            return 'success';
                        }
                        if ($num >= 50) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->formatStateUsing(static function (mixed $state, $record): string {
                        $max = $record->evaluation?->getAttributeValue('max_score');
                        if ($max === null) {
                            return (string) $state;
                        }

                        return "{$state} / {$max}";
                    })
                    ->sortable(),
                TextColumn::make('feedback')
                    ->label(__('Feedback'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->tooltip(fn ($record): ?string => $record->getAttributeValue('feedback') === null
                        ? null
                        : (string) $record->getAttributeValue('feedback')),
                TextColumn::make('grader.name')
                    ->label(__('Teacher'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Graded At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('evaluation')
                    ->label(__('Evaluation'))
                    ->options($evaluationOptions)
                    ->modifyQueryUsing(static function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->where('evaluation_id', (int) $value);
                    }),
                SelectFilter::make('courseOffering')
                    ->label(__('Course Offering'))
                    ->options($courseOfferingOptions)
                    ->modifyQueryUsing(static function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->whereHas('evaluation', static function (Builder $q) use ($value): void {
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
