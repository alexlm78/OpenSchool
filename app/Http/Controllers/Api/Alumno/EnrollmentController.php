<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class EnrollmentController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Enrollment::class);

        /** @var User $user */
        $user = $request->user();

        $query = Enrollment::query()
            ->with([
                'courseOffering.courseTemplate',
                'courseOffering.academicPeriod',
            ])
            ->where('school_id', (int) $user->getAttributeValue('school_id'));

        if ($user->hasRole('student')) {
            $query->where('student_id', (int) $user->getKey());
        }

        if ($user->hasRole('guardian')) {
            $linkedUserIds = LinkedGuardianStudents::resolveForUser($user);
            $query->whereIn('student_id', $linkedUserIds);
        }

        $sort = (string) $request->query('sort', '-enrolled_at');
        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');

        if (\in_array($sortColumn, ['enrolled_at', 'completed_at', 'status'], true)) {
            $query->orderBy($sortColumn, $sortDir);
        } else {
            $query->latest('enrolled_at');
        }

        if ($request->filled('status')) {
            $statuses = array_filter(
                explode(',', (string) $request->query('status', '')),
                static fn (string $s): bool => \in_array(trim($s), ['active', 'completed', 'dropped'], true),
            );
            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }
        }

        return EnrollmentResource::collection(
            $query->paginate(
                perPage: (int) $request->query('per_page', 15),
                page: (int) $request->query('page', 1),
            ),
        );
    }

    public function show(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        $enrollment->loadMissing([
            'courseOffering.courseTemplate',
            'courseOffering.academicPeriod',
        ]);

        return new JsonResponse([
            'data' => new EnrollmentResource($enrollment),
        ]);
    }
}
