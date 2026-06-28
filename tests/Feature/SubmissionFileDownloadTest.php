<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubmissionFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_owner_student_can_download_submission_file(): void
    {
        Storage::fake('local');

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

        $student = $this->createUserForSchool($school->id);

        $submission = Submission::create([
            'school_id' => $school->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $path = 'submissions/'.$submission->id.'/archivo.txt';
        Storage::disk('local')->put($path, 'contenido');

        $file = SubmissionFile::create([
            'school_id' => $school->id,
            'submission_id' => $submission->id,
            'file_name' => 'archivo.txt',
            'file_path' => $path,
            'file_type' => 'text/plain',
            'file_size' => 9,
        ]);

        $response = $this->actingAs($student)->get(route('submission-files.download', $file));
        $response->assertOk();
        $response->assertHeaderContains('content-disposition', 'attachment');
    }

    public function test_other_student_cannot_download_submission_file(): void
    {
        Storage::fake('local');

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

        $ownerStudent = $this->createUserForSchool($school->id);
        $otherStudent = $this->createUserForSchool($school->id);

        $submission = Submission::create([
            'school_id' => $school->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $ownerStudent->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $path = 'submissions/'.$submission->id.'/archivo.txt';
        Storage::disk('local')->put($path, 'contenido');

        $file = SubmissionFile::create([
            'school_id' => $school->id,
            'submission_id' => $submission->id,
            'file_name' => 'archivo.txt',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($otherStudent)->get(route('submission-files.download', $file));
        $response->assertForbidden();
    }

    public function test_assigned_teacher_can_download_submission_file(): void
    {
        Storage::fake('local');

        $school = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        app(TenantContext::class)->setSchoolId($school->id);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($school->id);

        Role::create([
            'name' => 'teacher',
            'guard_name' => 'web',
            'school_id' => $school->id,
        ]);

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

        $teacher = $this->createUserForSchool($school->id);
        $teacher->assignRole('teacher');

        TeachingAssignment::create([
            'school_id' => $school->id,
            'course_offering_id' => $offering->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = $this->createUserForSchool($school->id);

        $submission = Submission::create([
            'school_id' => $school->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $path = 'submissions/'.$submission->id.'/archivo.txt';
        Storage::disk('local')->put($path, 'contenido');

        $file = SubmissionFile::create([
            'school_id' => $school->id,
            'submission_id' => $submission->id,
            'file_name' => 'archivo.txt',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($teacher)->get(route('submission-files.download', $file));
        $response->assertOk();
    }

    public function test_user_from_other_school_gets_404_due_to_tenancy_scope(): void
    {
        Storage::fake('local');

        $schoolA = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        $schoolB = School::create(['name' => 'Escuela B', 'email' => 'escuela-b@example.com']);

        app(TenantContext::class)->setSchoolId($schoolA->id);

        $period = AcademicPeriod::create([
            'school_id' => $schoolA->id,
            'name' => '2026 Semestre 1',
            'type' => 'semester',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonths(4)->toDateString(),
        ]);

        $template = CourseTemplate::create([
            'school_id' => $schoolA->id,
            'name' => 'Matemáticas',
            'code' => 'MAT-101',
        ]);

        $offering = CourseOffering::create([
            'school_id' => $schoolA->id,
            'academic_period_id' => $period->id,
            'course_template_id' => $template->id,
            'capacity' => 30,
            'section_name' => 'A',
        ]);

        $evaluation = Evaluation::create([
            'school_id' => $schoolA->id,
            'course_offering_id' => $offering->id,
            'title' => 'Tarea 1',
            'evaluationable_type' => 'App\\Models\\AssignmentDetails',
            'evaluationable_id' => 1,
        ]);

        $studentA = $this->createUserForSchool($schoolA->id);
        $submission = Submission::create([
            'school_id' => $schoolA->id,
            'evaluation_id' => $evaluation->id,
            'student_id' => $studentA->id,
            'status' => 'submitted',
            'attempt' => 1,
            'late_flag' => false,
        ]);

        $path = 'submissions/'.$submission->id.'/archivo.txt';
        Storage::disk('local')->put($path, 'contenido');

        $file = SubmissionFile::create([
            'school_id' => $schoolA->id,
            'submission_id' => $submission->id,
            'file_name' => 'archivo.txt',
            'file_path' => $path,
        ]);

        $userB = $this->createUserForSchool($schoolB->id);

        $response = $this->actingAs($userB)->get(route('submission-files.download', $file));
        $response->assertNotFound();
    }

    private function createUserForSchool(int $schoolId): User
    {
        $user = User::factory()->createOne([
            'school_id' => $schoolId,
        ]);

        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            throw new \RuntimeException('Expected a User that implements Authenticatable.');
        }

        return $user;
    }
}
