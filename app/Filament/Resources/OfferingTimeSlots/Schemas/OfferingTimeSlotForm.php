<?php

namespace App\Filament\Resources\OfferingTimeSlots\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OfferingTimeSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('school_id')
                    ->required()
                    ->numeric(),
                TextInput::make('course_offering_id')
                    ->required()
                    ->numeric(),
                TextInput::make('time_slot_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
