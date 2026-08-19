<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssignmentDetails\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssignmentDetailsForm
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
                Textarea::make('description')
                    ->label(__('Description'))
                    ->columnSpanFull(),
                TextInput::make('file_requirements')
                    ->label(__('File Requirements')),
                Toggle::make('allow_late_submission')
                    ->label(__('Allow Late Submission'))
                    ->required(),
                TextInput::make('late_penalty_percent')
                    ->label(__('Late Penalty Percent'))
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('late_until')
                    ->label(__('Late Until')),
            ]);
    }
}
