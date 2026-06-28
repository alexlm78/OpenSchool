<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('school_id')
                    ->required()
                    ->numeric(),
                TextInput::make('evaluation_id')
                    ->required()
                    ->numeric(),
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                TextInput::make('score')
                    ->numeric(),
                Textarea::make('feedback')
                    ->columnSpanFull(),
                TextInput::make('graded_by')
                    ->numeric(),
            ]);
    }
}
