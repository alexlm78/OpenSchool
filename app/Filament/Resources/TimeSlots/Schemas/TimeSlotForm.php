<?php

declare(strict_types=1);

namespace App\Filament\Resources\TimeSlots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class TimeSlotForm
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
                TextInput::make('day_of_week')
                    ->label(__('Day Of Week'))
                    ->required(),
                TimePicker::make('start_time')
                    ->label(__('Start Time'))
                    ->required(),
                TimePicker::make('end_time')
                    ->label(__('End Time'))
                    ->required(),
            ]);
    }
}
