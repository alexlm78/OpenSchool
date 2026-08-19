<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseOfferings;

use App\Filament\AdminResource;
use App\Filament\Resources\CourseOfferings\Pages\CreateCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\EditCourseOffering;
use App\Filament\Resources\CourseOfferings\Pages\ListCourseOfferings;
use App\Filament\Resources\CourseOfferings\Schemas\CourseOfferingForm;
use App\Filament\Resources\CourseOfferings\Tables\CourseOfferingsTable;
use App\Models\CourseOffering;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourseOfferingResource extends AdminResource
{
    protected static ?string $model = CourseOffering::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Course Offering');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Course Offerings');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseOfferingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseOfferingsTable::configure($table);
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
            'index' => ListCourseOfferings::route('/'),
            'create' => CreateCourseOffering::route('/create'),
            'edit' => EditCourseOffering::route('/{record}/edit'),
        ];
    }
}
