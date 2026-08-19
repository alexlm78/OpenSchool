<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssignmentDetails\Pages;

use App\Filament\Resources\AssignmentDetails\AssignmentDetailsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignmentDetails extends CreateRecord
{
    protected static string $resource = AssignmentDetailsResource::class;
}
