<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeForm
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
                TextInput::make('evaluation_id')
                    ->label(__('Evaluation'))
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->label(__('Student'))
                    ->required()
                    ->numeric(),
                TextInput::make('score')
                    ->label(__('Score'))
                    ->numeric(),
                Textarea::make('feedback')
                    ->label(__('Feedback'))
                    ->columnSpanFull(),
                TextInput::make('graded_by')
                    ->label(__('Graded By'))
                    ->numeric(),
            ]);
    }
}
