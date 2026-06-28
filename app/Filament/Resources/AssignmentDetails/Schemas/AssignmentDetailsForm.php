<?php

namespace App\Filament\Resources\AssignmentDetails\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssignmentDetailsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('school_id')
                    ->required()
                    ->numeric(),
                TextInput::make('evaluationable_type')
                    ->required(),
                TextInput::make('evaluationable_id')
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('file_requirements'),
                Toggle::make('allow_late_submission')
                    ->required(),
                TextInput::make('late_penalty_percent')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('late_until'),
            ]);
    }
}
