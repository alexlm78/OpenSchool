<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferingTimeSlots\Pages;

use App\Filament\Resources\OfferingTimeSlots\OfferingTimeSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOfferingTimeSlots extends ListRecords
{
    protected static string $resource = OfferingTimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
