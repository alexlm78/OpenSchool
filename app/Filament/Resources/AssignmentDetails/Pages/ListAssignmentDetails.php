<?php

namespace App\Filament\Resources\AssignmentDetails\Pages;

use App\Filament\Resources\AssignmentDetails\AssignmentDetailsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssignmentDetails extends ListRecords
{
    protected static string $resource = AssignmentDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
