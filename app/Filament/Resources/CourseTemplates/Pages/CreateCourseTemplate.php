<?php

declare(strict_types=1);

namespace App\Filament\Resources\CourseTemplates\Pages;

use App\Filament\Resources\CourseTemplates\CourseTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseTemplate extends CreateRecord
{
    protected static string $resource = CourseTemplateResource::class;
}
