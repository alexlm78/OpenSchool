<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Enrollments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsTable
{
    /**
     * @param  array<string, string>  $studentFilterOptions
     */
    public static function configure(Table $table, array $studentFilterOptions = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')
                    ->label(__('Student'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.courseTemplate.name')
                    ->label(__('Course'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.section_name')
                    ->label(__('Section'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.academicPeriod.name')
                    ->label(__('Period'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'dropped' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('enrolled_at')
                    ->label(__('Enrolled At'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->label(__('Completed At'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student')
                    ->label(__('Filtrar por Estudiante'))
                    ->options($studentFilterOptions)
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->where('student_id', (int) $value);
                    }),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'active' => __('Active'),
                        'completed' => __('Completed'),
                        'dropped' => __('Dropped'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
