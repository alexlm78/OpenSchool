<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Evaluations\Pages;

use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use Filament\Resources\Pages\ListRecords;

class ListEvaluations extends ListRecords
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
