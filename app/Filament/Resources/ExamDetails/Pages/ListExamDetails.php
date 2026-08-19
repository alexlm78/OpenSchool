<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamDetails\Pages;

use App\Filament\Resources\ExamDetails\ExamDetailsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamDetails extends ListRecords
{
    protected static string $resource = ExamDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
