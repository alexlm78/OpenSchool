<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AcademicPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label(__('School'))
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                TextInput::make('type')
                    ->label(__('Type'))
                    ->required(),
                DatePicker::make('starts_at')
                    ->label(__('Starts At'))
                    ->required(),
                DatePicker::make('ends_at')
                    ->label(__('Ends At'))
                    ->required(),
            ]);
    }
}
