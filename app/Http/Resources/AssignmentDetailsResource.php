<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AssignmentDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssignmentDetails
 */
final class AssignmentDetailsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'assignment_details',
            'description' => (string) $this->getAttributeValue('description'),
            'file_requirements' => (string) $this->getAttributeValue('file_requirements'),
            'allow_late_submission' => (bool) $this->getAttributeValue('allow_late_submission'),
            'late_penalty_percent' => (int) $this->getAttributeValue('late_penalty_percent'),
            'late_until' => $this->when(
                $this->getAttributeValue('late_until') !== null,
                fn () => $this->getAttributeValue('late_until')?->toIso8601String(),
            ),
        ];
    }
}
