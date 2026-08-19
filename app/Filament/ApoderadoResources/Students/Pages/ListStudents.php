<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Students\Pages;

use App\Filament\ApoderadoResources\Students\StudentResource;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
