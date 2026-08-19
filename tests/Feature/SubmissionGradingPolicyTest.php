<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Submission;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubmissionGradingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_assigned_teacher_can_grade_submission(): void
    {
        $school = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

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

        $evaluation = Evaluation::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering->id,
            'title' => 'Tarea 1',
            'evaluationable_type' => 'App\\Models\\AssignmentDetails',
            'evaluationable_id' => 1,
        ]);

        $teacher = User::factory()->createOne(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        TeachingAssignment::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = User::factory()->createOne(['school_id' => $school->id]);

        $submission = Submission::create([
            'school_id' => $school->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $this->assertTrue(Gate::forUser($teacher)->check('grade', $submission));
    }

    public function test_unassigned_teacher_cannot_grade_submission(): void
    {
        $school = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

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

        $evaluation = Evaluation::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering->id,
            'title' => 'Tarea 1',
            'evaluationable_type' => 'App\\Models\\AssignmentDetails',
            'evaluationable_id' => 1,
        ]);

        $teacher = User::factory()->createOne(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $student = User::factory()->createOne(['school_id' => $school->id]);

        $submission = Submission::create([
            'school_id' => $school->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $this->assertFalse(Gate::forUser($teacher)->check('grade', $submission));
    }
}
