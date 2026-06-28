<?php

namespace App\Filament\DocenteResources\CourseOfferings\Pages;

use App\Filament\DocenteResources\CourseOfferings\CourseOfferingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourseOfferings extends ListRecords
{
    protected static string $resource = CourseOfferingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
