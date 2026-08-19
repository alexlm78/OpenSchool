<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiDates;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademicPeriod
 */
final class AcademicPeriodResource extends JsonResource
{
    use FormatsApiDates;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'name' => (string) $this->getAttributeValue('name'),
            'code' => $this->when($this->offsetExists('code'), fn (): string => (string) $this->getAttributeValue('code')),
            'type' => (string) $this->getAttributeValue('type'),
            'starts_at' => $this->when(
                $this->getAttributeValue('starts_at') !== null,
                fn () => $this->toIsoString($this->getAttributeValue('starts_at')),
            ),
            'ends_at' => $this->when(
                $this->getAttributeValue('ends_at') !== null,
                fn () => $this->toIsoString($this->getAttributeValue('ends_at')),
            ),
        ];
    }
}
