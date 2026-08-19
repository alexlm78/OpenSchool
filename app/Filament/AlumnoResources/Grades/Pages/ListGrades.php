<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Grades\Pages;

use App\Filament\AlumnoResources\Grades\GradeResource;
use Filament\Resources\Pages\ListRecords;

class ListGrades extends ListRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
