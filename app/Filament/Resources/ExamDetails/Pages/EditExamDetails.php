<?php

namespace App\Filament\Resources\ExamDetails\Pages;

use App\Filament\Resources\ExamDetails\ExamDetailsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExamDetails extends EditRecord
{
    protected static string $resource = ExamDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
