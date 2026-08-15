<?php

namespace App\Filament\Resources\ProjectDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectDetailsTable
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
                IconColumn::make('group_project')
                    ->label(__('Group Project'))
                    ->boolean(),
                TextColumn::make('max_group_size')
                    ->label(__('Max Group Size'))
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
