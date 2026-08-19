<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseTemplates;

use App\Filament\AdminResource;
use App\Filament\Resources\CourseTemplates\Pages\CreateCourseTemplate;
use App\Filament\Resources\CourseTemplates\Pages\EditCourseTemplate;
use App\Filament\Resources\CourseTemplates\Pages\ListCourseTemplates;
use App\Filament\Resources\CourseTemplates\Schemas\CourseTemplateForm;
use App\Filament\Resources\CourseTemplates\Tables\CourseTemplatesTable;
use App\Models\CourseTemplate;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourseTemplateResource extends AdminResource
{
    protected static ?string $model = CourseTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Course Template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Course Templates');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseTemplatesTable::configure($table);
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
            'index' => ListCourseTemplates::route('/'),
            'create' => CreateCourseTemplate::route('/create'),
            'edit' => EditCourseTemplate::route('/{record}/edit'),
        ];
    }
}
