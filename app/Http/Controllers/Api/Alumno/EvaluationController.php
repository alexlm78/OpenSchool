<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationDetailResource;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class EvaluationController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Evaluation::class);

        /** @var User $user */
        $user = $request->user();

        $schoolId = (int) $user->getAttributeValue('school_id');

        $query = Evaluation::query()
            ->with([
                'courseOffering.courseTemplate',
                'courseOffering.academicPeriod',
                'submissions' => static function (HasMany $q) use ($user): void {
                    if ($user->hasRole('student')) {
                        $q->where('student_id', (int) $user->getKey());
                    }
                },
                'grades' => static function (HasMany $q) use ($user): void {
                    if ($user->hasRole('student')) {
                        $q->where('student_id', (int) $user->getKey());
                    }
                },
            ])
            ->where('evaluations.school_id', $schoolId)
            ->whereNotNull('evaluations.published_at');

        if ($user->hasRole('student')) {
            $query->whereExists(
                static fn (Builder $q): Builder => $q
                    ->selectRaw('1')
                    ->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->where('enrollments.school_id', $schoolId)
                    ->where('enrollments.student_id', (int) $user->getKey())
                    ->whereIn('enrollments.status', ['active', 'completed']),
            );
        }

        if ($user->hasRole('guardian')) {
            $linked = LinkedGuardianStudents::resolveForUser($user);
            $linkedProfileIds = $linked['profileIds'];
            $linkedUserIds = $linked['userIds'];

            $query
                ->with([
                    'submissions' => function ($q) use ($linkedProfileIds): void {
                        $q->whereIn('student_id', $linkedProfileIds);
                    },
                    'grades' => function ($q) use ($linkedProfileIds): void {
                        $q->whereIn('student_id', $linkedProfileIds);
                    },
                ])
                ->whereExists(
                    static function (Builder $q) use ($schoolId, $linkedProfileIds): Builder {
                        $q->selectRaw('1')
                            ->from('enrollments')
                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                            ->where('enrollments.school_id', $schoolId)
                            ->whereIn('enrollments.student_id', $linkedProfileIds)
                            ->whereIn('enrollments.status', ['active', 'completed']);

                        return $q;
                    },
                );
        }

        if ($request->filled('course_offering_id')) {
            $offeringId = (int) $request->query('course_offering_id');
            if ($offeringId > 0) {
                $query->where('course_offering_id', $offeringId);
            }
        }

        $sort = (string) $request->query('sort', '-due_at');
        $sortDir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');

        if (\in_array($sortColumn, ['due_at', 'published_at', 'max_score', 'weight'], true)) {
            $query->orderBy($sortColumn, $sortDir);
        } else {
            $query->latest('due_at');
        }

        if ($request->filled('status')) {
            $status = mb_strtolower(trim((string) $request->query('status')));
            if (\in_array($status, ['pending', 'submitted', 'graded'], true) && $user->hasRole('student')) {
                $studentId = (int) $user->getKey();
                if ($status === 'pending') {
                    $query->whereDoesntHave(
                        'submissions',
                        static fn (\Illuminate\Database\Eloquent\Builder $sq): \Illuminate\Database\Eloquent\Builder => $sq
                            ->where('school_id', $schoolId)
                            ->where('student_id', $studentId),
                    );
                } elseif ($status === 'submitted') {
                    $query->whereHas(
                        'submissions',
                        static fn (\Illuminate\Database\Eloquent\Builder $sq): \Illuminate\Database\Eloquent\Builder => $sq
                            ->where('school_id', $schoolId)
                            ->where('student_id', $studentId),
                    )->whereDoesntHave(
                        'grades',
                        static fn (\Illuminate\Database\Eloquent\Builder $sq): \Illuminate\Database\Eloquent\Builder => $sq
                            ->where('school_id', $schoolId)
                            ->where('student_id', $studentId),
                    );
                } else {
                    $query->whereHas(
                        'grades',
                        static fn (\Illuminate\Database\Eloquent\Builder $sq): \Illuminate\Database\Eloquent\Builder => $sq
                            ->where('school_id', $schoolId)
                            ->where('student_id', $studentId),
                    );
                }
            }
        }

        return EvaluationResource::collection(
            $query->paginate(
                perPage: (int) $request->query('per_page', 15),
                page: (int) $request->query('page', 1),
            ),
        );
    }

    public function show(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('view', $evaluation);

        $evaluation->loadMissing([
            'courseOffering.courseTemplate',
            'courseOffering.academicPeriod',
            'evaluationable',
        ]);

        return new JsonResponse([
            'data' => new EvaluationDetailResource($evaluation),
        ]);
    }
}
