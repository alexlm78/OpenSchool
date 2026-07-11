<?php

namespace App\Filament\Resources\ProjectDetails\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectDetailsForm
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
                TextInput::make('evaluationable_type')
                    ->required(),
                TextInput::make('evaluationable_id')
                    ->required()
                    ->numeric(),
                Toggle::make('group_project')
                    ->required(),
                TextInput::make('max_group_size')
                    ->numeric(),
                Textarea::make('rubric')
                    ->columnSpanFull(),
            ]);
    }
}
