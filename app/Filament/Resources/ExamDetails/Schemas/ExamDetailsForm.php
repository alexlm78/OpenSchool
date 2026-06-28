<?php

namespace App\Filament\Resources\ExamDetails\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamDetailsForm
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
                DateTimePicker::make('exam_date'),
                TextInput::make('duration_minutes')
                    ->numeric(),
                TextInput::make('location'),
                TextInput::make('modality')
                    ->required()
                    ->default('in-person'),
            ]);
    }
}
