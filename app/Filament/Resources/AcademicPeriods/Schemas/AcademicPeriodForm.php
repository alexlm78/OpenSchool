<?php

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
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                DatePicker::make('starts_at')
                    ->required(),
                DatePicker::make('ends_at')
                    ->required(),
            ]);
    }
}
