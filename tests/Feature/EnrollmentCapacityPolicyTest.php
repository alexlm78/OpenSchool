<?php

namespace Tests\Feature;

use App\Domain\Enrollment\EnrollStudent;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceeded;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityPolicyViolation;
use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentCapacityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_school_rejects_zero_capacity_when_unlimited_not_allowed(): void
    {
        $school = School::create([
            'name' => 'Escuela Estricta',
            'email' => 'estricta@example.com',
            'allow_unlimited_capacity' => false,
            'max_students_per_course' => 30,
            'min_students_per_course' => 1,
        ]);
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
            'capacity' => 0,
            'section_name' => 'A',
        ]);

        $student = User::factory()->createOne(['school_id' => $school->id]);

        $this->expectException(EnrollmentCapacityPolicyViolation::class);
        app(EnrollStudent::class)->enroll(studentId: (int) $student->id, courseOfferingId: (int) $offering->id);
    }

    public function test_effective_max_uses_min_of_offering_and_school_max(): void
    {
        $school = School::create([
            'name' => 'Escuela Max 2',
            'email' => 'max2@example.com',
            'allow_unlimited_capacity' => true,
            'max_students_per_course' => 2,
            'min_students_per_course' => 1,
        ]);
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
            'capacity' => 30,
            'section_name' => 'A',
        ]);

        $student1 = User::factory()->createOne(['school_id' => $school->id]);
        $student2 = User::factory()->createOne(['school_id' => $school->id]);
        $student3 = User::factory()->createOne(['school_id' => $school->id]);

        $service = app(EnrollStudent::class);
        $service->enroll(studentId: (int) $student1->id, courseOfferingId: (int) $offering->id);
        $service->enroll(studentId: (int) $student2->id, courseOfferingId: (int) $offering->id);

        $this->expectException(EnrollmentCapacityExceeded::class);
        $service->enroll(studentId: (int) $student3->id, courseOfferingId: (int) $offering->id);
    }

    public function test_school_with_only_global_max_enforces_it(): void
    {
        $school = School::create([
            'name' => 'Escuela Solo Max Global',
            'email' => 'globalmax@example.com',
            'allow_unlimited_capacity' => false,
            'max_students_per_course' => 1,
            'min_students_per_course' => 0,
        ]);
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
            'capacity' => 0,
            'section_name' => 'A',
        ]);

        $student = User::factory()->createOne(['school_id' => $school->id]);

        $this->expectException(EnrollmentCapacityPolicyViolation::class);
        app(EnrollStudent::class)->enroll(studentId: (int) $student->id, courseOfferingId: (int) $offering->id);
    }
}
