<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\TeachingAssignments\Pages;

use App\Filament\DocenteResources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachingAssignments extends ListRecords
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
