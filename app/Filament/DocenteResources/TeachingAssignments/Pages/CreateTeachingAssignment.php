<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\TeachingAssignments\Pages;

use App\Filament\DocenteResources\TeachingAssignments\TeachingAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeachingAssignment extends CreateRecord
{
    protected static string $resource = TeachingAssignmentResource::class;
}
