<?php

namespace App\Filament\Resources\OfferingTimeSlots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OfferingTimeSlotForm
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
                TextInput::make('course_offering_id')
                    ->required()
                    ->numeric(),
                TextInput::make('time_slot_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
