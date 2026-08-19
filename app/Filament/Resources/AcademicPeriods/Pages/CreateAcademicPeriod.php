<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicPeriods\Pages;

use App\Filament\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicPeriod extends CreateRecord
{
    protected static string $resource = AcademicPeriodResource::class;
}
