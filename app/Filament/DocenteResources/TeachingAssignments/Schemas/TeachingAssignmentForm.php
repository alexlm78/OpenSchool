<?php

namespace App\Filament\DocenteResources\TeachingAssignments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeachingAssignmentForm
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
                TextInput::make('teacher_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
