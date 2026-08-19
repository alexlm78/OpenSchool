<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubmissionFiles\Pages;

use App\Filament\Resources\SubmissionFiles\SubmissionFileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubmissionFile extends EditRecord
{
    protected static string $resource = SubmissionFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
