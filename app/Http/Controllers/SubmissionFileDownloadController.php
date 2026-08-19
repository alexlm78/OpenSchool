<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SubmissionFileDownloadController extends Controller
{
    public function __invoke(SubmissionFile $submissionFile): StreamedResponse
    {
        $this->authorize('download', $submissionFile);

        $path = (string) $submissionFile->file_path;
        $name = (string) $submissionFile->file_name;

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        $disk = (string) config('filesystems.default', 'local');

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $name);
    }
}
