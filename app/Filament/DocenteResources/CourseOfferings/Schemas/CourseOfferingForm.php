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
                    ->label(__('School ID'))
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('academic_period_id')
                    ->label(__('Academic Period'))
                    ->required()
                    ->numeric(),
                TextInput::make('course_template_id')
                    ->label(__('Course Template'))
                    ->required()
                    ->numeric(),
                TextInput::make('capacity')
                    ->label(__('Capacity'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('section_name')
                    ->label(__('Section Name')),
            ]);
    }
}
