<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CourseOffering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseOffering
 */
final class CourseOfferingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'section_name' => (string) $this->getAttributeValue('section_name'),
            'capacity' => $this->getAttributeValue('capacity') !== null
                ? (int) $this->getAttributeValue('capacity')
                : null,
            'course_template' => new CourseTemplateResource($this->whenLoaded('courseTemplate')),
            'academic_period' => new AcademicPeriodResource($this->whenLoaded('academicPeriod')),
        ];
    }
}
