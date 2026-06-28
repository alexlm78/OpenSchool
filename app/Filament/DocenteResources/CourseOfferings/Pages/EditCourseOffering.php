<?php

namespace App\Filament\DocenteResources\CourseOfferings\Pages;

use App\Filament\DocenteResources\CourseOfferings\CourseOfferingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseOffering extends EditRecord
{
    protected static string $resource = CourseOfferingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
