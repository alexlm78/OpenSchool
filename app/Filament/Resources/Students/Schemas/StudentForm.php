<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
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
                TextInput::make('user_id')
                    ->label(__('User'))
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->label(__('Student ID'))
                    ->required(),
                DatePicker::make('date_of_birth')
                    ->label(__('Date Of Birth')),
                TextInput::make('gender')
                    ->label(__('Gender')),
                TextInput::make('address')
                    ->label(__('Address')),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->tel(),
            ]);
    }
}
