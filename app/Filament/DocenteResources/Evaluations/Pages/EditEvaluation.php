<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\Evaluations\Pages;

use App\Filament\DocenteResources\Evaluations\EvaluationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
