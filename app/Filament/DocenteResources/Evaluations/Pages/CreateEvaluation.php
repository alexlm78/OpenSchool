<?php

namespace App\Filament\DocenteResources\Evaluations\Pages;

use App\Filament\DocenteResources\Evaluations\EvaluationResource;
use App\Models\AssignmentDetails;
use App\Models\Evaluation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Evaluation {
            $assignmentDetails = AssignmentDetails::create([
                'evaluationable_type' => Evaluation::class,
                'evaluationable_id' => 0,
                'description' => isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
                'file_requirements' => isset($data['file_requirements']) && is_string($data['file_requirements']) ? $data['file_requirements'] : null,
                'allow_late_submission' => (bool) ($data['allow_late_submission'] ?? false),
                'late_penalty_percent' => (int) ($data['late_penalty_percent'] ?? 0),
                'late_until' => $data['late_until'] ?? null,
            ]);

            $evaluation = Evaluation::create([
                'course_offering_id' => (int) $data['course_offering_id'],
                'title' => (string) $data['title'],
                'description' => isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
                'max_score' => $data['max_score'] ?? 100,
                'weight' => $data['weight'] ?? 1,
                'due_at' => $data['due_at'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'evaluationable_type' => AssignmentDetails::class,
                'evaluationable_id' => $assignmentDetails->getKey(),
            ]);

            $assignmentDetails->update([
                'evaluationable_id' => $evaluation->getKey(),
            ]);

            return $evaluation;
        });
    }
}
