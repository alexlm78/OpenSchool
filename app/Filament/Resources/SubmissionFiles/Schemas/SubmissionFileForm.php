<?php

declare(strict_types=1);

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
                    ->label(__('School'))
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('submission_id')
                    ->label(__('Submission'))
                    ->required()
                    ->numeric(),
                TextInput::make('file_name')
                    ->label(__('File Name'))
                    ->required(),
                TextInput::make('file_path')
                    ->label(__('File Path'))
                    ->required(),
                TextInput::make('file_type')
                    ->label(__('File Type')),
                TextInput::make('file_size')
                    ->label(__('File Size'))
                    ->numeric(),
            ]);
    }
}
