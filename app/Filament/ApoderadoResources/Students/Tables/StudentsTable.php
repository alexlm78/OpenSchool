<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Students\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->label(__('Matrícula'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('Student Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date_of_birth')
                    ->label(__('Date of Birth'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label(__('Gender'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('Address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('user.name', 'asc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
