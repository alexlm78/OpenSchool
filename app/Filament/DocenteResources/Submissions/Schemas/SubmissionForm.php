<?php

namespace App\Filament\DocenteResources\Submissions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubmissionForm
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
                TextInput::make('evaluation_id')
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('submitted_at'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('attempt')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('late_flag')
                    ->required(),
            ]);
    }
}
