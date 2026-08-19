<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiDates;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Evaluation
 */
final class EvaluationResource extends JsonResource
{
    use FormatsApiDates;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'title' => (string) $this->getAttributeValue('title'),
            'description' => $this->when(
                (string) $this->getAttributeValue('description') !== '',
                fn (): string => (string) $this->getAttributeValue('description'),
            ),
            'max_score' => $this->getAttributeValue('max_score') !== null
                ? (float) $this->getAttributeValue('max_score')
                : null,
            'weight' => $this->getAttributeValue('weight') !== null
                ? (float) $this->getAttributeValue('weight')
                : null,
            'due_at' => $this->toIsoString($this->getAttributeValue('due_at')),
            'published_at' => $this->toIsoString($this->getAttributeValue('published_at')),
            'course_offering' => new CourseOfferingSummaryResource($this->whenLoaded('courseOffering')),
            'student_status' => $this->when(
                Auth::check(),
                fn (): array => $this->computeStudentStatus(),
            ),
        ];
    }

    /**
     * @return array{label: string, hex_color: string, level: string, has_submission: bool, has_grade: bool}
     */
    private function computeStudentStatus(): array
    {
        $studentId = Auth::id();
        if ($studentId === null) {
            return [
                'label' => 'Sin estado',
                'hex_color' => '#64748b',
                'level' => 'default',
                'has_submission' => false,
                'has_grade' => false,
            ];
        }

        /** @var Collection<int, Submission> $submissions */
        $submissions = $this->relationLoaded('submissions')
            ? $this->submissions
            : $this->submissions()->where('student_id', (int) $studentId)->get();

        $studentSubmission = $submissions->firstWhere('student_id', (int) $studentId);

        if ($studentSubmission === null) {
            return [
                'label' => 'No enviado',
                'hex_color' => '#f59e0b',
                'level' => 'warning',
                'has_submission' => false,
                'has_grade' => false,
            ];
        }

        /** @var Collection<int, Grade> $grades */
        $grades = $this->relationLoaded('grades')
            ? $this->grades
            : $this->grades()->where('student_id', (int) $studentId)->get();

        $hasGrade = $grades->contains(
            static fn (Grade $g): bool => (int) $g->getAttributeValue('student_id') === (int) $studentId,
        );

        return $hasGrade
            ? [
                'label' => 'Calificado',
                'hex_color' => '#22c55e',
                'level' => 'success',
                'has_submission' => true,
                'has_grade' => true,
            ]
            : [
                'label' => 'Entregado',
                'hex_color' => '#3b82f6',
                'level' => 'info',
                'has_submission' => true,
                'has_grade' => false,
            ];
    }
}
