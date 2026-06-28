<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('school_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->required(),
                DatePicker::make('date_of_birth'),
                TextInput::make('gender'),
                TextInput::make('address'),
                TextInput::make('phone')
                    ->tel(),
            ]);
    }
}
