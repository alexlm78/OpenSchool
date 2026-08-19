<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssignmentDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_id')
                    ->label(__('School ID'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('evaluationable_type')
                    ->label(__('Evaluationable Type'))
                    ->searchable(),
                TextColumn::make('evaluationable_id')
                    ->label(__('Evaluationable ID'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('file_requirements')
                    ->label(__('File Requirements'))
                    ->searchable(),
                IconColumn::make('allow_late_submission')
                    ->label(__('Allow Late Submission'))
                    ->boolean(),
                TextColumn::make('late_penalty_percent')
                    ->label(__('Late Penalty Percent'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_until')
                    ->label(__('Late Until'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
