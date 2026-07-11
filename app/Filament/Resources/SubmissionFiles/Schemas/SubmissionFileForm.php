<?php

namespace App\Filament\Resources\SubmissionFiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubmissionFileForm
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
                TextInput::make('submission_id')
                    ->required()
                    ->numeric(),
                TextInput::make('file_name')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('file_type'),
                TextInput::make('file_size')
                    ->numeric(),
            ]);
    }
}
