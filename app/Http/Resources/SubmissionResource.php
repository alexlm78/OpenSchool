<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiDates;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Submission
 */
final class SubmissionResource extends JsonResource
{
    use FormatsApiDates;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'evaluation' => new EvaluationResource($this->whenLoaded('evaluation')),
            'evaluation_id' => (int) $this->getAttributeValue('evaluation_id'),
            'status' => (string) $this->getAttributeValue('status'),
            'attempt' => (int) $this->getAttributeValue('attempt'),
            'late_flag' => (bool) $this->getAttributeValue('late_flag'),
            'submitted_at' => $this->toIsoString($this->getAttributeValue('submitted_at')),
            'files_count' => $this->whenCounted('submissionFiles'),
        ];
    }
}
