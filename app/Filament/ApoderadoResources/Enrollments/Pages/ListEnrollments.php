<?php

namespace App\Filament\ApoderadoResources\Enrollments\Pages;

use App\Filament\ApoderadoResources\Enrollments\EnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
