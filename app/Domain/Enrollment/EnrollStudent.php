<?php

declare(strict_types=1);

namespace App\Domain\Enrollment;

use App\Domain\Enrollment\Exceptions\EnrollmentAlreadyExists;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceeded;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityPolicyViolation;
use App\Domain\Enrollment\Exceptions\EnrollmentScheduleConflict;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\TimeSlot;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EnrollStudent
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function enroll(int $studentId, int $courseOfferingId, ?string $status = 'active', ?Carbon $enrolledAt = null): Enrollment
    {
        $schoolId = $this->tenantContext->requireSchoolId();

        return DB::transaction(function () use ($schoolId, $studentId, $courseOfferingId, $status, $enrolledAt): Enrollment {
            $courseOffering = CourseOffering::query()
                ->whereKey($courseOfferingId)
                ->firstOrFail();

            $school = School::query()->findOrFail($schoolId);

            $alreadyEnrolled = Enrollment::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('course_offering_id', $courseOfferingId)
                ->exists();

            if ($alreadyEnrolled) {
                throw new EnrollmentAlreadyExists(__('Student is already enrolled in this course offering.'));
            }

            $this->validateCapacityPolicy($school, $courseOffering, $schoolId);

            $newOfferingSlots = $this->getOfferingTimeSlots($schoolId, $courseOfferingId);
            if ($newOfferingSlots !== []) {
                $studentSlots = $this->getStudentActiveTimeSlots($schoolId, $studentId, $courseOfferingId);

                foreach ($newOfferingSlots as $newSlot) {
                    foreach ($studentSlots as $existingSlot) {
                        if ($this->overlaps($newSlot, $existingSlot)) {
                            throw new EnrollmentScheduleConflict(__('Schedule conflict detected for this enrollment.'));
                        }
                    }
                }
            }

            return Enrollment::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'course_offering_id' => $courseOfferingId,
                'status' => $status ?? 'active',
                'enrolled_at' => ($enrolledAt ?? now())->toDateString(),
            ]);
        });
    }

    private function validateCapacityPolicy(School $school, CourseOffering $courseOffering, int $schoolId): void
    {
        $offeringCapacity = (int) ($courseOffering->capacity ?? 0);
        $schoolMax = $school->getMaxStudentsPerCourse();
        $allowUnlimited = $school->isUnlimitedCapacityAllowed();

        if ($offeringCapacity === 0 && ! $allowUnlimited) {
            throw EnrollmentCapacityPolicyViolation::unlimitedNotAllowed();
        }

        $effectiveMax = $this->resolveEffectiveMaxCapacity($offeringCapacity, $schoolMax);

        if ($effectiveMax === null) {
            return;
        }

        $activeCount = Enrollment::query()
            ->where('school_id', $schoolId)
            ->where('course_offering_id', $courseOffering->getKey())
            ->where('status', 'active')
            ->count();

        if ($activeCount >= $effectiveMax) {
            throw new EnrollmentCapacityExceeded(__('Course offering capacity reached (:count / :max).', [
                'count' => $activeCount,
                'max' => $effectiveMax,
            ]));
        }
    }

    private function resolveEffectiveMaxCapacity(int $offeringCapacity, int $schoolMax): ?int
    {
        $offer = $offeringCapacity > 0 ? $offeringCapacity : null;
        $max = $schoolMax > 0 ? $schoolMax : null;

        if ($offer === null && $max === null) {
            return null;
        }

        if ($offer === null) {
            return $max;
        }

        if ($max === null) {
            return $offer;
        }

        return min($offer, $max);
    }

    /**
     * @return array<int, array{day_of_week: string, start_time: string, end_time: string}>
     */
    private function getOfferingTimeSlots(int $schoolId, int $courseOfferingId): array
    {
        return TimeSlot::query()
            ->join('offering_time_slots', 'offering_time_slots.time_slot_id', '=', 'time_slots.id')
            ->where('time_slots.school_id', $schoolId)
            ->where('offering_time_slots.school_id', $schoolId)
            ->where('offering_time_slots.course_offering_id', $courseOfferingId)
            ->get(['time_slots.day_of_week', 'time_slots.start_time', 'time_slots.end_time'])
            ->map(fn (object $row) => [
                'day_of_week' => (string) $row->day_of_week,
                'start_time' => (string) $row->start_time,
                'end_time' => (string) $row->end_time,
            ])
            ->all();
    }

    /**
     * @return array<int, array{day_of_week: string, start_time: string, end_time: string}>
     */
    private function getStudentActiveTimeSlots(int $schoolId, int $studentId, int $excludeCourseOfferingId): array
    {
        return TimeSlot::query()
            ->join('offering_time_slots', 'offering_time_slots.time_slot_id', '=', 'time_slots.id')
            ->join('enrollments', 'enrollments.course_offering_id', '=', 'offering_time_slots.course_offering_id')
            ->where('time_slots.school_id', $schoolId)
            ->where('offering_time_slots.school_id', $schoolId)
            ->where('enrollments.school_id', $schoolId)
            ->where('enrollments.student_id', $studentId)
            ->where('enrollments.status', 'active')
            ->where('enrollments.course_offering_id', '!=', $excludeCourseOfferingId)
            ->get(['time_slots.day_of_week', 'time_slots.start_time', 'time_slots.end_time'])
            ->map(fn (object $row) => [
                'day_of_week' => (string) $row->day_of_week,
                'start_time' => (string) $row->start_time,
                'end_time' => (string) $row->end_time,
            ])
            ->all();
    }

    /**
     * @param  array{day_of_week: string, start_time: string, end_time: string}  $a
     * @param  array{day_of_week: string, start_time: string, end_time: string}  $b
     */
    private function overlaps(array $a, array $b): bool
    {
        if ($a['day_of_week'] !== $b['day_of_week']) {
            return false;
        }

        $aStart = $this->parseTime($a['start_time']);
        $aEnd = $this->parseTime($a['end_time']);
        $bStart = $this->parseTime($b['start_time']);
        $bEnd = $this->parseTime($b['end_time']);

        return $aStart->lt($bEnd) && $aEnd->gt($bStart);
    }

    private function parseTime(string $time): Carbon
    {
        $normalized = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $normalized) === 1) {
            return Carbon::createFromFormat('H:i', $normalized);
        }

        return Carbon::createFromFormat('H:i:s', $normalized);
    }
}
