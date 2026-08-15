<?php

namespace App\Filament\Resources\SubmissionFiles;

use App\Filament\AdminResource;
use App\Filament\Resources\SubmissionFiles\Pages\CreateSubmissionFile;
use App\Filament\Resources\SubmissionFiles\Pages\EditSubmissionFile;
use App\Filament\Resources\SubmissionFiles\Pages\ListSubmissionFiles;
use App\Filament\Resources\SubmissionFiles\Schemas\SubmissionFileForm;
use App\Filament\Resources\SubmissionFiles\Tables\SubmissionFilesTable;
use App\Models\SubmissionFile;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubmissionFileResource extends AdminResource
{
    protected static ?string $model = SubmissionFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Submission File');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Submission Files');
    }

    public static function form(Schema $schema): Schema
    {
        return SubmissionFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubmissionFilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissionFiles::route('/'),
            'create' => CreateSubmissionFile::route('/create'),
            'edit' => EditSubmissionFile::route('/{record}/edit'),
        ];
    }
}
