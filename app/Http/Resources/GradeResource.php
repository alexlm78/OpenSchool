<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiDates;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Grade
 */
final class GradeResource extends JsonResource
{
    use FormatsApiDates;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $score = $this->getAttributeValue('score') !== null
            ? (float) $this->getAttributeValue('score')
            : null;

        $maxScore = $this->whenLoaded(
            'evaluation',
            fn () => $this->evaluation !== null && $this->evaluation->getAttributeValue('max_score') !== null
                ? (float) $this->evaluation->getAttributeValue('max_score')
                : null,
            null,
        );

        return [
            'id' => (int) $this->getKey(),
            'score' => $score,
            'max_score' => $maxScore,
            'score_meta' => $this->buildScoreMeta($score, \is_float($maxScore) ? $maxScore : null),
            'feedback' => $this->when(
                (string) $this->getAttributeValue('feedback') !== '',
                fn (): string => (string) $this->getAttributeValue('feedback'),
            ),
            'graded_by' => new UserSummaryResource($this->whenLoaded('grader')),
            'evaluation' => new EvaluationResource($this->whenLoaded('evaluation')),
            'created_at' => $this->toIsoString($this->getAttributeValue('created_at')),
            'updated_at' => $this->toIsoString($this->getAttributeValue('updated_at')),
        ];
    }

    /**
     * @return array{label: string, hex_color: string, level: string, percentage: float|null}
     */
    private function buildScoreMeta(null|float|int $score, null|float|int $maxScore): array
    {
        if ($score === null) {
            return [
                'label' => 'Sin calificar',
                'hex_color' => '#64748b',
                'level' => 'default',
                'percentage' => null,
            ];
        }

        $percentage = null;
        if ($maxScore !== null && (float) $maxScore > 0) {
            $percentage = round(((float) $score / (float) $maxScore) * 100, 2);
        }

        if ($percentage !== null) {
            if ($percentage >= 70) {
                return [
                    'label' => 'Aprobado',
                    'hex_color' => '#22c55e',
                    'level' => 'success',
                    'percentage' => $percentage,
                ];
            }

            if ($percentage >= 50) {
                return [
                    'label' => 'Recuperación',
                    'hex_color' => '#f59e0b',
                    'level' => 'warning',
                    'percentage' => $percentage,
                ];
            }

            return [
                'label' => 'Reprobado',
                'hex_color' => '#ef4444',
                'level' => 'danger',
                'percentage' => $percentage,
            ];
        }

        return [
            'label' => (string) $score,
            'hex_color' => '#0ea5e9',
            'level' => 'info',
            'percentage' => null,
        ];
    }
}
