<?php

namespace App\Filament\DocenteResources\CourseOfferings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseOfferingForm
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
                TextInput::make('academic_period_id')
                    ->required()
                    ->numeric(),
                TextInput::make('course_template_id')
                    ->required()
                    ->numeric(),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('section_name'),
            ]);
    }
}
