<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamDetails\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamDetailsForm
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
                TextInput::make('evaluationable_type')
                    ->label(__('Evaluationable Type'))
                    ->required(),
                TextInput::make('evaluationable_id')
                    ->label(__('Evaluationable ID'))
                    ->required()
                    ->numeric(),
                DateTimePicker::make('exam_date')
                    ->label(__('Exam Date')),
                TextInput::make('duration_minutes')
                    ->label(__('Duration Minutes'))
                    ->numeric(),
                TextInput::make('location')
                    ->label(__('Location')),
                TextInput::make('modality')
                    ->label(__('Modality'))
                    ->required()
                    ->default('in-person'),
            ]);
    }
}
