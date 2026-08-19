<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CourseTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseTemplate
 */
final class CourseTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'name' => (string) $this->getAttributeValue('name'),
            'code' => (string) $this->getAttributeValue('code'),
            'description' => $this->when(
                (string) $this->getAttributeValue('description') !== '',
                fn (): string => (string) $this->getAttributeValue('description'),
            ),
            'default_credits' => $this->when(
                $this->offsetExists('default_credits'),
                fn () => $this->getAttributeValue('default_credits') !== null
                    ? (int) $this->getAttributeValue('default_credits')
                    : null,
            ),
        ];
    }
}
