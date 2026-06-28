<?php

namespace App\Filament\Resources\OfferingTimeSlots\Pages;

use App\Filament\Resources\OfferingTimeSlots\OfferingTimeSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfferingTimeSlot extends EditRecord
{
    protected static string $resource = OfferingTimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
