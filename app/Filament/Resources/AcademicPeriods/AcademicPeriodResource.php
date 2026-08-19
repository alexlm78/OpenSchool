<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicPeriods;

use App\Filament\AdminResource;
use App\Filament\Resources\AcademicPeriods\Pages\CreateAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\EditAcademicPeriod;
use App\Filament\Resources\AcademicPeriods\Pages\ListAcademicPeriods;
use App\Filament\Resources\AcademicPeriods\Schemas\AcademicPeriodForm;
use App\Filament\Resources\AcademicPeriods\Tables\AcademicPeriodsTable;
use App\Models\AcademicPeriod;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AcademicPeriodResource extends AdminResource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Academic Period');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Academic Periods');
    }

    public static function form(Schema $schema): Schema
    {
        return AcademicPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicPeriodsTable::configure($table);
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
            'index' => ListAcademicPeriods::route('/'),
            'create' => CreateAcademicPeriod::route('/create'),
            'edit' => EditAcademicPeriod::route('/{record}/edit'),
        ];
    }
}
