<?php

namespace App\Filament\DocenteResources\CourseOfferings\Pages;

use App\Filament\DocenteResources\CourseOfferings\CourseOfferingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseOffering extends CreateRecord
{
    protected static string $resource = CourseOfferingResource::class;
}
