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
                    ->label(__('School ID'))
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('evaluation_id')
                    ->label(__('Evaluation'))
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->label(__('Student'))
                    ->required()
                    ->numeric(),
                DateTimePicker::make('submitted_at')
                    ->label(__('Submitted At')),
                TextInput::make('status')
                    ->label(__('Status'))
                    ->required()
                    ->default('draft'),
                TextInput::make('attempt')
                    ->label(__('Attempt'))
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('late_flag')
                    ->label(__('Late Flag'))
                    ->required(),
            ]);
    }
}
