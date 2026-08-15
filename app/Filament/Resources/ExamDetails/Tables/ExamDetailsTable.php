<?php

namespace App\Filament\Resources\ExamDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamDetailsTable
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
                TextColumn::make('exam_date')
                    ->label(__('Exam Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label(__('Duration Minutes'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('Location'))
                    ->searchable(),
                TextColumn::make('modality')
                    ->label(__('Modality'))
                    ->searchable(),
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
