<?php

namespace App\Filament\Resources\ProjectDetails\Pages;

use App\Filament\Resources\ProjectDetails\ProjectDetailsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectDetails extends EditRecord
{
    protected static string $resource = ProjectDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
