<?php

namespace App\Filament\Resources\SubmissionFiles\Pages;

use App\Filament\Resources\SubmissionFiles\SubmissionFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubmissionFiles extends ListRecords
{
    protected static string $resource = SubmissionFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
