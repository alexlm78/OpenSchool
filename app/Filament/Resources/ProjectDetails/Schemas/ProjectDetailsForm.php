<?php

declare(strict_types=1);

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
                Toggle::make('group_project')
                    ->label(__('Group Project'))
                    ->required(),
                TextInput::make('max_group_size')
                    ->label(__('Max Group Size'))
                    ->numeric(),
                Textarea::make('rubric')
                    ->label(__('Rubric'))
                    ->columnSpanFull(),
            ]);
    }
}
