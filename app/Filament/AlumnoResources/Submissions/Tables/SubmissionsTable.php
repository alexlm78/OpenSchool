<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Submissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('evaluation.title')
                    ->label(__('Evaluation'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('evaluation.courseOffering.courseTemplate.name')
                    ->label(__('Course'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'graded' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->label(__('Submitted At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('attempt')
                    ->label(__('Attempt'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('late_flag')
                    ->label(__('Late Flag'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'draft' => ucfirst(__('draft')),
                        'submitted' => ucfirst(__('submitted')),
                        'graded' => ucfirst(__('graded')),
                    ]),
                TernaryFilter::make('late_flag')
                    ->label(__('Late')),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('submitted_at', 'desc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
