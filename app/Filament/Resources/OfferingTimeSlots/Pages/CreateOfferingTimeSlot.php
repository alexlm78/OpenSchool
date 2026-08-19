<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferingTimeSlots\Pages;

use App\Filament\Resources\OfferingTimeSlots\OfferingTimeSlotResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingTimeSlot extends CreateRecord
{
    protected static string $resource = OfferingTimeSlotResource::class;
}
