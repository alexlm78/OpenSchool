<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamDetails\Pages;

use App\Filament\Resources\ExamDetails\ExamDetailsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamDetails extends CreateRecord
{
    protected static string $resource = ExamDetailsResource::class;
}
