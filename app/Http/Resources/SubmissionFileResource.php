<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SubmissionFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin SubmissionFile
 */
final class SubmissionFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canDownload = Gate::allows('download', $this->resource);

        return [
            'id' => (int) $this->getKey(),
            'file_name' => (string) $this->getAttributeValue('file_name'),
            'file_type' => (string) $this->getAttributeValue('file_type'),
            'file_size_bytes' => (int) $this->getAttributeValue('file_size'),
            'can_download' => $canDownload,
            'download_url' => $canDownload
                ? (string) route('submission-files.download', $this->resource, absolute: true)
                : null,
        ];
    }
}
