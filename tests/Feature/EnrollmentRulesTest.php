<?php

namespace Tests\Feature;

use App\Domain\Enrollment\EnrollStudent;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceeded;
use App\Domain\Enrollment\Exceptions\EnrollmentScheduleConflict;
use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\OfferingTimeSlot;
use App\Models\School;
use App\Models\TimeSlot;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_enrollment_respects_capacity_when_capacity_is_positive(): void
    {
        $school = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);

        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'name' => '2026 Semestre 1',
            'type' => 'semester',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonths(4)->toDateString(),
        ]);

        $template = CourseTemplate::create([
            'school_id' => $school->id,
            'name' => 'Matemáticas',
            'code' => 'MAT-101',
        ]);

        $offering = CourseOffering::create([
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'course_template_id' => $template->id,
            'capacity' => 1,
            'section_name' => 'A',
        ]);

        $student1 = User::factory()->createOne(['school_id' => $school->id]);
        $student2 = User::factory()->createOne(['school_id' => $school->id]);

        $service = app(EnrollStudent::class);
        $service->enroll(studentId: (int) $student1->id, courseOfferingId: (int) $offering->id);

        $this->expectException(EnrollmentCapacityExceeded::class);
        $service->enroll(studentId: (int) $student2->id, courseOfferingId: (int) $offering->id);
    }

    public function test_enrollment_rejects_schedule_overlap_on_same_day(): void
    {
        $school = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);

        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'name' => '2026 Semestre 1',
            'type' => 'semester',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonths(4)->toDateString(),
        ]);

        $template1 = CourseTemplate::create([
            'school_id' => $school->id,
            'name' => 'Matemáticas',
            'code' => 'MAT-101',
        ]);

        $template2 = CourseTemplate::create([
            'school_id' => $school->id,
            'name' => 'Lenguaje',
            'code' => 'LEN-101',
        ]);

        $offering1 = CourseOffering::create([
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'course_template_id' => $template1->id,
            'capacity' => 30,
            'section_name' => 'A',
        ]);

        $offering2 = CourseOffering::create([
            'school_id' => $school->id,
            'academic_period_id' => $period->id,
            'course_template_id' => $template2->id,
            'capacity' => 30,
            'section_name' => 'A',
        ]);

        $slot1 = TimeSlot::create([
            'school_id' => $school->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $slot2 = TimeSlot::create([
            'school_id' => $school->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        OfferingTimeSlot::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering1->id,
            'time_slot_id' => $slot1->id,
        ]);

        OfferingTimeSlot::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering2->id,
            'time_slot_id' => $slot2->id,
        ]);

        $student = User::factory()->createOne(['school_id' => $school->id]);

        $service = app(EnrollStudent::class);
        $service->enroll(studentId: (int) $student->id, courseOfferingId: (int) $offering1->id);

        $this->expectException(EnrollmentScheduleConflict::class);
        $service->enroll(studentId: (int) $student->id, courseOfferingId: (int) $offering2->id);
    }
}
