<?php

namespace App\Filament\Resources\OfferingTimeSlots;

use App\Filament\AdminResource;
use App\Filament\Resources\OfferingTimeSlots\Pages\CreateOfferingTimeSlot;
use App\Filament\Resources\OfferingTimeSlots\Pages\EditOfferingTimeSlot;
use App\Filament\Resources\OfferingTimeSlots\Pages\ListOfferingTimeSlots;
use App\Filament\Resources\OfferingTimeSlots\Schemas\OfferingTimeSlotForm;
use App\Filament\Resources\OfferingTimeSlots\Tables\OfferingTimeSlotsTable;
use App\Models\OfferingTimeSlot;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OfferingTimeSlotResource extends AdminResource
{
    protected static ?string $model = OfferingTimeSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Offering Time Slot');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Offering Time Slots');
    }

    public static function form(Schema $schema): Schema
    {
        return OfferingTimeSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfferingTimeSlotsTable::configure($table);
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
            'index' => ListOfferingTimeSlots::route('/'),
            'create' => CreateOfferingTimeSlot::route('/create'),
            'edit' => EditOfferingTimeSlot::route('/{record}/edit'),
        ];
    }
}
