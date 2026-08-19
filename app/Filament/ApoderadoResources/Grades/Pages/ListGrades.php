<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Grades\Pages;

use App\Filament\ApoderadoResources\Grades\GradeResource;
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
