<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Pages;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

final class BoletinGuardian extends Page
{
    /**
     * @var array<int, array{student: Student, enrollments: array<int, array{enrollment: Enrollment, grades: Collection<int, Grade>, gpaPercent: float|null, gpaLabel: string, gpaColor: string}>}>
     */
    public array $studentReports = [];

    public float $overallGpaPercent = 0.0;

    public string $overallGpaLabel = '';

    public string $overallGpaColor = 'gray';

    public string $guardianName = '';

    public string $reportGeneratedAt = '';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.apoderado.pages.boletin-guardian';

    public static function getNavigationLabel(): string
    {
        return __('navigation.apoderado_boletin');
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }
        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }
        if (! $user->hasRole('guardian')) {
            return false;
        }
        $linked = LinkedGuardianStudents::resolveForUser($user);

        return $linked['profileIds'] !== [] || $linked['userIds'] !== [];
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }
        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }
        if (! $user->hasRole('guardian')) {
            abort(403);
        }
        $linked = LinkedGuardianStudents::resolveForUser($user);
        $profileIds = $linked['profileIds'];
        $userIds = $linked['userIds'];
        if ($profileIds === [] && $userIds === []) {
            abort(404);
        }
        $this->guardianName = (string) $user->getAttributeValue('name');
        $this->reportGeneratedAt = now()->toDateTimeString();

        $students = Student::query()
            ->with(['user'])
            ->where('school_id', (int) $user->getAttributeValue('school_id'))
            ->whereIn('id', $profileIds !== [] ? $profileIds : [-1])
            ->orderBy('id')
            ->get();

        $sumAllRatios = 0.0;
        $countAllRatios = 0;
        foreach ($students as $student) {
            $enrollments = Enrollment::query()
                ->with(['courseOffering.courseTemplate'])
                ->where('student_id', (int) $student->getAttributeValue('user_id'))
                ->where('school_id', (int) $user->getAttributeValue('school_id'))
                ->whereIn('status', ['active', 'completed'])
                ->orderBy('enrolled_at', 'desc')
                ->get();

            $studentReportRows = [];
            $sumStudent = 0.0;
            $countStudent = 0;
            foreach ($enrollments as $enrollment) {
                $evaluationIds = \DB::table('evaluations')
                    ->where('course_offering_id', (int) $enrollment->getAttributeValue('course_offering_id'))
                    ->pluck('id')
                    ->map(static fn (mixed $v): int => (int) $v)
                    ->all();
                $grades = Grade::query()
                    ->with(['evaluation'])
                    ->where('student_id', (int) $student->getAttributeValue('user_id'))
                    ->whereIn('evaluation_id', $evaluationIds !== [] ? $evaluationIds : [-1])
                    ->orderByDesc('created_at')
                    ->get();
                $enrollmentRatio = null;
                if ($grades->count() > 0) {
                    $ratioSum = 0.0;
                    $ratioCount = 0;
                    foreach ($grades as $g) {
                        $max = $g->evaluation?->getAttributeValue('max_score');
                        if ($max !== null && (float) $max > 0) {
                            $ratioSum += ((float) $g->getAttributeValue('score')) / (float) $max;
                            $ratioCount++;
                        }
                    }
                    if ($ratioCount > 0) {
                        $enrollmentRatio = $ratioSum / $ratioCount;
                        $sumStudent += $enrollmentRatio;
                        $countStudent++;
                        $sumAllRatios += $enrollmentRatio;
                        $countAllRatios++;
                    }
                }
                $gpaPercent = $enrollmentRatio === null ? null : round((float) $enrollmentRatio * 100, 1);
                $gpaColor = 'gray';
                $gpaLabel = __('widgets.gpa_no_data');
                if ($gpaPercent !== null) {
                    if ($gpaPercent >= 70) {
                        $gpaColor = 'success';
                        $gpaLabel = __('widgets.gpa_approved');
                    } elseif ($gpaPercent >= 50) {
                        $gpaColor = 'warning';
                        $gpaLabel = __('widgets.gpa_recovery');
                    } else {
                        $gpaColor = 'danger';
                        $gpaLabel = __('widgets.gpa_failing');
                    }
                }
                $studentReportRows[] = [
                    'enrollment' => $enrollment,
                    'grades' => $grades,
                    'gpaPercent' => $gpaPercent,
                    'gpaLabel' => $gpaLabel,
                    'gpaColor' => $gpaColor,
                ];
            }

            $this->studentReports[] = [
                'student' => $student,
                'enrollments' => $studentReportRows,
                'studentGpaPercent' => $countStudent > 0 ? round(($sumStudent / $countStudent) * 100, 1) : null,
            ];
        }

        if ($countAllRatios > 0) {
            $this->overallGpaPercent = round(($sumAllRatios / $countAllRatios) * 100, 1);
            if ($this->overallGpaPercent >= 70) {
                $this->overallGpaColor = 'success';
                $this->overallGpaLabel = __('widgets.gpa_approved');
            } elseif ($this->overallGpaPercent >= 50) {
                $this->overallGpaColor = 'warning';
                $this->overallGpaLabel = __('widgets.gpa_recovery');
            } else {
                $this->overallGpaColor = 'danger';
                $this->overallGpaLabel = __('widgets.gpa_failing');
            }
        } else {
            $this->overallGpaLabel = __('widgets.gpa_no_data');
        }
    }

    public function getHeading(): string
    {
        return __('navigation.apoderado_boletin_heading');
    }

    public function getSubheading(): ?string
    {
        return __('navigation.apoderado_boletin_subheading');
    }
}
