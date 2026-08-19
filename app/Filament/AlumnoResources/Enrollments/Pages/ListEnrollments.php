<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Enrollments\Pages;

use App\Filament\AlumnoResources\Enrollments\EnrollmentResource;
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
