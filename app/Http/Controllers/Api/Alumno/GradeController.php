<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class GradeController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Grade::class);

        /** @var User $user */
        $user = $request->user();
        $schoolId = (int) $user->getAttributeValue('school_id');

        $query = Grade::query()
            ->with([
                'grader',
                'evaluation.courseOffering.courseTemplate',
                'evaluation.courseOffering.academicPeriod',
            ])
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

        if ($request->filled('course_offering_id')) {
            $offeringId = (int) $request->query('course_offering_id');
            if ($offeringId > 0) {
                $query->whereHas(
                    'evaluation',
                    static fn (Builder $sq): Builder => $sq
                        ->where('school_id', $schoolId)
                        ->where('course_offering_id', $offeringId),
                );
            }
        }

        if ($request->filled('student_id')) {
            $studentId = (int) $request->query('student_id');
            if ($studentId > 0) {
                if ($user->hasRole('guardian')) {
                    $linkedUserIds = LinkedGuardianStudents::resolveForUser($user);
                    if (\in_array($studentId, $linkedUserIds, true)) {
                        $query->where('student_id', $studentId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->where('student_id', $studentId);
                }
            }
        }

        $sort = (string) $request->query('sort', '-created_at');
        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');

        if (\in_array($sortColumn, ['created_at', 'updated_at', 'score'], true)) {
            $query->orderBy($sortColumn, $sortDir);
        } else {
            $query->latest('created_at');
        }

        return GradeResource::collection(
            $query->paginate(
                perPage: (int) $request->query('per_page', 15),
                page: (int) $request->query('page', 1),
            ),
        );
    }

    public function show(Request $request, Grade $grade): JsonResponse
    {
        $this->authorize('view', $grade);

        $grade->loadMissing([
            'grader',
            'evaluation.courseOffering.courseTemplate',
            'evaluation.courseOffering.academicPeriod',
        ]);

        return new JsonResponse([
            'data' => new GradeResource($grade),
        ]);
    }
}
