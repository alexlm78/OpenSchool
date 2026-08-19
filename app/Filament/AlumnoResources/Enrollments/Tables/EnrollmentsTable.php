<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Enrollments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('courseOffering.courseTemplate.name')
                    ->label(__('Course Name'))
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
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('Completed At'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
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
