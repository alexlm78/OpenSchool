<?php

namespace App\Filament\DocenteResources\TeachingAssignments\Pages;

use App\Filament\DocenteResources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeachingAssignment extends EditRecord
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
