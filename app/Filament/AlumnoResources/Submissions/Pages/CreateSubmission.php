<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Submissions\Pages;

use App\Filament\AlumnoResources\Submissions\SubmissionResource;
use App\Tenancy\TenantContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSubmission extends CreateRecord
{
    protected static string $resource = SubmissionResource::class;

    public function mount(): void
    {
        parent::mount();

        $evaluationId = request()->query('evaluation_id');
        if ($evaluationId !== null) {
            $this->form->fill([
                'evaluation_id' => (int) $evaluationId,
            ] + $this->form->getRawState());
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $studentId = Auth::id();
        if ($studentId !== null) {
            $data['student_id'] = (int) $studentId;
        }

        $schoolId = app(TenantContext::class)->getSchoolId();
        if ($schoolId !== null) {
            $data['school_id'] = $schoolId;
        }

        if (! isset($data['submitted_at']) || $data['submitted_at'] === null) {
            $data['submitted_at'] = now();
        }

        if (! isset($data['attempt']) || $data['attempt'] === null) {
            $data['attempt'] = 1;
        }

        if (! isset($data['status']) || $data['status'] === null) {
            $data['status'] = 'submitted';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $schoolId = app(TenantContext::class)->getSchoolId();
        if ($schoolId !== null) {
            $this->record->submissionFiles()->each(function ($file) use ($schoolId) {
                if ($file->school_id === null) {
                    $file->school_id = $schoolId;
                    $file->save();
                }
            });
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record->getKey()]);
    }
}
