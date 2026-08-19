<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\Evaluations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EvaluationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_id')
                    ->label(__('School ID'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('course_offering_id')
                    ->label(__('Course Offering'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(),
                TextColumn::make('max_score')
                    ->label(__('Max Score'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight')
                    ->label(__('Weight'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label(__('Due Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('Published At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('evaluationable_type')
                    ->label(__('Evaluationable Type'))
                    ->searchable(),
                TextColumn::make('evaluationable_id')
                    ->label(__('Evaluationable ID'))
                    ->numeric()
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
