<?php

namespace App\Filament\Resources\SubmissionFiles\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubmissionFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_id')
                    ->label(__('School ID'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submission_id')
                    ->label(__('Submission'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('file_name')
                    ->label(__('File Name'))
                    ->searchable(),
                TextColumn::make('file_path')
                    ->label(__('File Path'))
                    ->searchable(),
                TextColumn::make('file_type')
                    ->label(__('File Type'))
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label(__('File Size'))
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
                Action::make('download')
                    ->label('Download')
                    ->authorize('download')
                    ->url(fn (\App\Models\SubmissionFile $record): string => route('submission-files.download', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
