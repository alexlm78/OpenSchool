<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Submission
 */
final class SubmissionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(
            (new SubmissionResource($this))->toArray($request),
            [
                'comment' => (string) $this->getAttributeValue('comment'),
                'files' => SubmissionFileResource::collection($this->whenLoaded('submissionFiles')),
                'grades' => GradeResource::collection($this->whenLoaded('grades')),
            ],
        );
    }
}
