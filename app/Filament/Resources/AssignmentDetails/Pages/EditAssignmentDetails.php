<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssignmentDetails\Pages;

use App\Filament\Resources\AssignmentDetails\AssignmentDetailsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentDetails extends EditRecord
{
    protected static string $resource = AssignmentDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
