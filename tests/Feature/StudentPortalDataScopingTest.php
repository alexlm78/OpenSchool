<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AssignmentDetails;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentPortalDataScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_student_sees_only_their_enrollments_in_alumno_portal(): void
    {
        $school = School::create(['name' => 'Escuela A', 'email' => 'a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

        $period = AcademicPeriod::create([
            'school_id' => $school->id, 'name' => 'S1', 'type' => 'semester',
            'starts_at' => now()->toDateString(), 'ends_at' => now()->addMonths(4)->toDateString(),
        ]);
        $template = CourseTemplate::create(['school_id' => $school->id, 'name' => 'Math', 'code' => 'M101']);
        $offering = CourseOffering::create([
            'school_id' => $school->id, 'academic_period_id' => $period->id,
            'course_template_id' => $template->id, 'capacity' => 30, 'section_name' => 'A',
        ]);
        $offering2 = CourseOffering::create([
            'school_id' => $school->id, 'academic_period_id' => $period->id,
            'course_template_id' => $template->id, 'capacity' => 30, 'section_name' => 'B',
        ]);

        $studentUser1 = User::factory()->createOne(['school_id' => $school->id]);
        $studentUser2 = User::factory()->createOne(['school_id' => $school->id]);

        Enrollment::create([
            'school_id' => $school->id, 'student_id' => $studentUser1->id,
            'course_offering_id' => $offering->id, 'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $school->id, 'student_id' => $studentUser2->id,
            'course_offering_id' => $offering2->id, 'status' => 'active',
        ]);

        $seenByStudent1 = Enrollment::query()
            ->where('school_id', $school->id)
            ->where('student_id', $studentUser1->id)
            ->pluck('course_offering_id')
            ->all();

        $seenByStudent2 = Enrollment::query()
            ->where('school_id', $school->id)
            ->where('student_id', $studentUser2->id)
            ->pluck('course_offering_id')
            ->all();

        $this->assertContains($offering->id, $seenByStudent1);
        $this->assertNotContains($offering2->id, $seenByStudent1);

        $this->assertContains($offering2->id, $seenByStudent2);
        $this->assertNotContains($offering->id, $seenByStudent2);
    }

    public function test_guardian_sees_only_linked_students_grades(): void
    {
        $school = School::create(['name' => 'Escuela G', 'email' => 'g@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

        $period = AcademicPeriod::create([
            'school_id' => $school->id, 'name' => 'S1', 'type' => 'semester',
            'starts_at' => now()->toDateString(), 'ends_at' => now()->addMonths(4)->toDateString(),
        ]);
        $template = CourseTemplate::create(['school_id' => $school->id, 'name' => 'Math', 'code' => 'M101']);
        $offering = CourseOffering::create([
            'school_id' => $school->id, 'academic_period_id' => $period->id,
            'course_template_id' => $template->id, 'capacity' => 30, 'section_name' => 'A',
        ]);
        $assignment = AssignmentDetails::create([
            'school_id' => $school->id,
            'evaluationable_type' => Evaluation::class,
            'evaluationable_id' => 0,
            'description' => 'Tarea',
            'file_requirements' => 'PDF',
            'allow_late_submission' => false,
            'late_penalty_percent' => 0,
        ]);

        $evaluation = Evaluation::create([
            'school_id' => $school->id, 'course_offering_id' => $offering->id,
            'title' => 'T1', 'max_score' => 100, 'weight' => 1,
            'evaluationable_type' => 'App\\Models\\AssignmentDetails',
            'evaluationable_id' => $assignment->id,
        ]);
        $assignment->update(['evaluationable_id' => $evaluation->id]);

        $studentUserA = User::factory()->createOne(['school_id' => $school->id]);
        $studentUserB = User::factory()->createOne(['school_id' => $school->id]);

        $profileA = Student::create(['school_id' => $school->id, 'user_id' => $studentUserA->id, 'student_id' => 'S-A']);
        $profileB = Student::create(['school_id' => $school->id, 'user_id' => $studentUserB->id, 'student_id' => 'S-B']);

        $guardianUser = User::factory()->createOne(['school_id' => $school->id]);
        $guardianProfile = Guardian::create(['school_id' => $school->id, 'user_id' => $guardianUser->id, 'relationship' => 'parent']);
        $guardianProfile->students()->syncWithoutDetaching([$profileA->id => ['school_id' => $school->id]]);

        Grade::create([
            'school_id' => $school->id, 'evaluation_id' => $evaluation->id,
            'student_id' => $studentUserA->id, 'score' => 90,
        ]);
        Grade::create([
            'school_id' => $school->id, 'evaluation_id' => $evaluation->id,
            'student_id' => $studentUserB->id, 'score' => 50,
        ]);

        $linkedIds = $guardianProfile->students()
            ->pluck('students.user_id')
            ->filter(static fn ($id): bool => \is_int($id))
            ->values()
            ->all();

        $gradesForGuardian = Grade::query()
            ->where('school_id', $school->id)
            ->whereIn('student_id', $linkedIds)
            ->pluck('student_id')
            ->unique()
            ->all();

        $this->assertContains($studentUserA->id, $gradesForGuardian);
        $this->assertNotContains($studentUserB->id, $gradesForGuardian);
    }

    public function test_guardian_can_only_see_linked_students(): void
    {
        $school = School::create(['name' => 'Escuela V', 'email' => 'v@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

        $studentUserVisible = User::factory()->createOne(['school_id' => $school->id]);
        $studentUserHidden = User::factory()->createOne(['school_id' => $school->id]);

        $visible = Student::create(['school_id' => $school->id, 'user_id' => $studentUserVisible->id, 'student_id' => 'VISIBLE']);
        $hidden = Student::create(['school_id' => $school->id, 'user_id' => $studentUserHidden->id, 'student_id' => 'HIDDEN']);

        $guardianUser = User::factory()->createOne(['school_id' => $school->id]);
        $guardian = Guardian::create(['school_id' => $school->id, 'user_id' => $guardianUser->id, 'relationship' => 'mother']);
        $guardian->students()->syncWithoutDetaching([$visible->id => ['school_id' => $school->id]]);

        $visibleStudentIds = $guardian->students()->pluck('students.id')->all();

        $this->assertContains($visible->id, $visibleStudentIds);
        $this->assertNotContains($hidden->id, $visibleStudentIds);
    }
}
