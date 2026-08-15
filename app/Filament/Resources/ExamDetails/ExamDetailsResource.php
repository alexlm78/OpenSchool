<?php

namespace App\Filament\Resources\ExamDetails;

use App\Filament\AdminResource;
use App\Filament\Resources\ExamDetails\Pages\CreateExamDetails;
use App\Filament\Resources\ExamDetails\Pages\EditExamDetails;
use App\Filament\Resources\ExamDetails\Pages\ListExamDetails;
use App\Filament\Resources\ExamDetails\Schemas\ExamDetailsForm;
use App\Filament\Resources\ExamDetails\Tables\ExamDetailsTable;
use App\Models\ExamDetails;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExamDetailsResource extends AdminResource
{
    protected static ?string $model = ExamDetails::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Exam Details');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Exam Details');
    }

    public static function form(Schema $schema): Schema
    {
        return ExamDetailsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExamDetailsTable::configure($table);
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
            'index' => ListExamDetails::route('/'),
            'create' => CreateExamDetails::route('/create'),
            'edit' => EditExamDetails::route('/{record}/edit'),
        ];
    }
}
