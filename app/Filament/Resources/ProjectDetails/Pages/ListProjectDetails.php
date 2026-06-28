<?php

namespace App\Filament\Resources\ProjectDetails\Pages;

use App\Filament\Resources\ProjectDetails\ProjectDetailsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectDetails extends ListRecords
{
    protected static string $resource = ProjectDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
