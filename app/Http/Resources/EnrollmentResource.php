<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiDates;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollment
 */
final class EnrollmentResource extends JsonResource
{
    use FormatsApiDates;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'course_offering' => new CourseOfferingSummaryResource($this->whenLoaded('courseOffering')),
            'status' => (string) $this->getAttributeValue('status'),
            'status_meta' => $this->buildStatusMeta((string) $this->getAttributeValue('status')),
            'enrolled_at' => $this->toIsoString($this->getAttributeValue('enrolled_at')),
            'completed_at' => $this->toIsoString($this->getAttributeValue('completed_at')),
        ];
    }

    /**
     * @return array{label: string, hex_color: string, level: string}
     */
    private function buildStatusMeta(string $status): array
    {
        return match ($status) {
            'active' => [
                'label' => 'En curso',
                'hex_color' => '#22c55e',
                'level' => 'success',
            ],
            'completed' => [
                'label' => 'Completado',
                'hex_color' => '#3b82f6',
                'level' => 'info',
            ],
            'dropped' => [
                'label' => 'Retirado',
                'hex_color' => '#ef4444',
                'level' => 'danger',
            ],
            default => [
                'label' => mb_convert_case($status, \MB_CASE_TITLE),
                'hex_color' => '#64748b',
                'level' => 'default',
            ],
        };
    }
}
