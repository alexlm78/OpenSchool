<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\Submissions\Pages;

use App\Filament\DocenteResources\Submissions\SubmissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubmission extends CreateRecord
{
    protected static string $resource = SubmissionResource::class;
}
