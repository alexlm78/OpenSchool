<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionDetailResource;
use App\Http\Resources\SubmissionResource;
use App\Models\AssignmentDetails;
use App\Models\Evaluation;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SubmissionController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Submission::class);

        /** @var User $user */
        $user = $request->user();

        $schoolId = (int) $user->getAttributeValue('school_id');

        $query = Submission::query()
            ->with([
                'evaluation.courseOffering.courseTemplate',
            ])
            ->withCount('submissionFiles')
            ->where('school_id', $schoolId);

        if ($user->hasRole('student')) {
            $query->where('student_id', (int) $user->getKey());
        }

        if ($user->hasRole('guardian')) {
            $linkedUserIds = LinkedGuardianStudents::resolveForUser($user);
            $query->whereIn('student_id', $linkedUserIds);
        }

        if ($request->filled('evaluation_id')) {
            $evaluationId = (int) $request->query('evaluation_id');
            if ($evaluationId > 0) {
                $query->where('evaluation_id', $evaluationId);
            }
        }

        if ($request->filled('status')) {
            $status = trim((string) $request->query('status'));
            if (\in_array($status, ['draft', 'submitted'], true)) {
                $query->where('status', $status);
            }
        }

        $sort = (string) $request->query('sort', '-submitted_at');
        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');

        if (\in_array($sortColumn, ['submitted_at', 'attempt', 'status'], true)) {
            $query->orderBy($sortColumn, $sortDir);
        } else {
            $query->latest('submitted_at');
        }

        return SubmissionResource::collection(
            $query->paginate(
                perPage: (int) $request->query('per_page', 15),
                page: (int) $request->query('page', 1),
            ),
        );
    }

    public function show(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        $submission->loadMissing([
            'evaluation.courseOffering.courseTemplate',
            'submissionFiles',
            'grades.grader',
        ]);

        return new JsonResponse([
            'data' => new SubmissionDetailResource($submission),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Submission::class);

        /** @var User $user */
        $user = $request->user();

        $schoolId = app(TenantContext::class)->requireSchoolId();
        $studentId = (int) $user->getKey();

        $validated = $request->validate([
            'evaluation_id' => ['required', 'integer', 'min:1', 'exists:evaluations,id'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', 'in:draft,submitted'],
            'files' => ['present', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:10240'],
        ]);

        /** @var Evaluation $evaluation */
        $evaluation = Evaluation::query()
            ->with('evaluationable')
            ->where('school_id', $schoolId)
            ->findOrFail((int) $validated['evaluation_id']);

        $enrolled = DB::table('enrollments')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (! $enrolled) {
            throw new AuthorizationException(
                __('No estás matriculado en el curso de esta evaluación.'),
            );
        }

        if ($evaluation->getAttributeValue('published_at') === null) {
            throw new AuthorizationException(
                __('Esta evaluación no ha sido publicada.'),
            );
        }

        $dueAt = $evaluation->getAttributeValue('due_at');
        $assignmentDetails = $evaluation->evaluationable;
        $allowLate = $assignmentDetails instanceof AssignmentDetails
            ? (bool) $assignmentDetails->getAttributeValue('allow_late_submission')
            : false;
        $lateUntil = $assignmentDetails instanceof AssignmentDetails
            ? $assignmentDetails->getAttributeValue('late_until')
            : null;

        $now = Carbon::now();
        $isLate = false;

        if ($dueAt instanceof Carbon && $now->greaterThan($dueAt)) {
            if (! $allowLate) {
                throw ValidationException::withMessages([
                    'evaluation_id' => [__('La evaluación ya cerró y no se aceptan entregas tarde.')],
                ]);
            }

            if ($lateUntil instanceof Carbon && $now->greaterThan($lateUntil)) {
                throw ValidationException::withMessages([
                    'evaluation_id' => [__('Tarde tolerante cerrada. No se aceptan más entregas.')],
                ]);
            }

            $isLate = true;
        }

        $existingAttempts = Submission::query()
            ->where('school_id', $schoolId)
            ->where('evaluation_id', (int) $evaluation->getKey())
            ->where('student_id', $studentId)
            ->count();

        $attempt = $existingAttempts + 1;
        $status = \in_array(($validated['status'] ?? ''), ['draft', 'submitted'], true)
            ? (string) $validated['status']
            : 'submitted';

        $submission = DB::transaction(function () use (
            $schoolId,
            $studentId,
            $evaluation,
            $attempt,
            $status,
            $isLate,
            $validated,
            $user,
        ): Submission {
            /** @var Submission $submission */
            $submission = Submission::query()->create([
                'school_id' => $schoolId,
                'evaluation_id' => (int) $evaluation->getKey(),
                'student_id' => $studentId,
                'submitted_at' => now(),
                'status' => $status,
                'attempt' => $attempt,
                'late_flag' => $isLate,
                'comment' => $validated['comment'] ?? null,
            ]);

            $fileRecords = [];

            /** @var UploadedFile $uploadedFile */
            foreach (($validated['files'] ?? []) as $idx => $uploadedFile) {
                if (! $uploadedFile instanceof UploadedFile) {
                    continue;
                }

                $dir = \sprintf(
                    'submissions/school-%d/student-%d/evaluation-%d/submission-%d',
                    $schoolId,
                    $studentId,
                    $evaluation->getKey(),
                    $submission->getKey(),
                );

                $originalName = $uploadedFile->getClientOriginalName();
                $safeFilename = \sprintf(
                    '%d-%s-%s',
                    $idx + 1,
                    Str::limit(Str::slug(pathinfo($originalName, \PATHINFO_FILENAME), '-'), 60, ''),
                    sha1(uniqid((string) mt_rand(), true)),
                ).'.'.Str::lower($uploadedFile->getClientOriginalExtension() ?: 'bin');

                $storedPath = $uploadedFile->storeAs($dir, $safeFilename, [
                    'disk' => 'local',
                ]);

                if ($storedPath === false) {
                    Log::error('Submission file upload failed', [
                        'user_id' => $user->getKey(),
                        'submission_id' => $submission->getKey(),
                        'original_name' => $originalName,
                    ]);

                    throw new \RuntimeException('No se pudo almacenar el archivo de entrega. Inténtalo de nuevo.');
                }

                $fileRecords[] = [
                    'school_id' => $schoolId,
                    'submission_id' => (int) $submission->getKey(),
                    'file_name' => $originalName,
                    'file_path' => $storedPath,
                    'file_type' => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
                    'file_size' => (int) $uploadedFile->getSize(),
                ];
            }

            if ($fileRecords !== []) {
                SubmissionFile::query()->insert($fileRecords);
            }

            return $submission;
        });

        $submission->loadMissing([
            'evaluation.courseOffering.courseTemplate',
            'submissionFiles',
        ]);

        return new JsonResponse([
            'data' => new SubmissionDetailResource($submission),
            'meta' => [
                'attempt' => $attempt,
                'late' => $isLate,
                'files_count' => $submission->submissionFiles()->count(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}
