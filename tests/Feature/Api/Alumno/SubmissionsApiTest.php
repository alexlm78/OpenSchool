<?php

declare(strict_types=1);

use App\Models\AcademicPeriod;
use App\Models\AssignmentDetails;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(
    RefreshDatabase::class,
);

beforeEach(function (): void {
    Storage::fake('local');

    $this->school = School::query()->create([
        'name' => 'Escuela Submissions',
        'email' => 'subs@example.com',
    ]);
    $this->app->make(TenantContext::class)->setSchoolId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $studentRole = Role::firstOrCreate([
        'name' => 'student',
        'school_id' => $this->school->id,
        'guard_name' => 'web',
    ]);

    $period = AcademicPeriod::query()->create([
        'school_id' => $this->school->id, 'name' => 'T1', 'type' => 'trimester',
        'starts_at' => now()->toDateString(), 'ends_at' => now()->addMonths(3)->toDateString(),
    ]);
    $tpl = CourseTemplate::query()->create(['school_id' => $this->school->id, 'name' => 'Química', 'code' => 'QUI101']);
    $this->offering = CourseOffering::query()->create([
        'school_id' => $this->school->id, 'academic_period_id' => $period->id,
        'course_template_id' => $tpl->id, 'capacity' => 20, 'section_name' => 'Q1',
    ]);

    $this->student = User::factory()->createOne(['school_id' => $this->school->id]);
    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->student->id, 'student_id' => 'SUB-1']);
    $this->student->assignRole($studentRole);

    $this->studentOther = User::factory()->createOne(['school_id' => $this->school->id]);
    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->studentOther->id, 'student_id' => 'SUB-2']);
    $this->studentOther->assignRole($studentRole);

    Enrollment::query()->create([
        'school_id' => $this->school->id, 'student_id' => $this->student->id,
        'course_offering_id' => $this->offering->id, 'status' => 'active',
    ]);

    $assignment = AssignmentDetails::query()->create([
        'school_id' => $this->school->id, 'evaluationable_type' => Evaluation::class, 'evaluationable_id' => 0,
        'description' => 'Informe', 'file_requirements' => 'PDF o Word',
        'allow_late_submission' => false, 'late_penalty_percent' => 0,
    ]);
    $this->evaluation = Evaluation::query()->create([
        'school_id' => $this->school->id, 'course_offering_id' => $this->offering->id,
        'title' => 'Informe Prácticas', 'max_score' => 10, 'weight' => 0.5,
        'due_at' => now()->addDays(5), 'published_at' => now(),
        'evaluationable_type' => 'App\\Models\\AssignmentDetails', 'evaluationable_id' => $assignment->id,
    ]);
    $assignment->update(['evaluationable_id' => $this->evaluation->id]);
});

it('creates submission with uploaded files for enrolled student (201 Created)', function (): void {
    Sanctum::actingAs($this->student);

    $filePdf = UploadedFile::fake()->create('informe-quimica.pdf', 1200, 'application/pdf');
    $filePng = UploadedFile::fake()->image('diagrama.png', 800, 600);

    $response = $this->postJson(route('submissions.store'), [
        'evaluation_id' => $this->evaluation->id,
        'comment' => 'Adjunto mi informe completo',
        'status' => 'submitted',
        'files' => [$filePdf, $filePng],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('meta.attempt', 1)
        ->assertJsonPath('meta.late', false)
        ->assertJsonPath('meta.files_count', 2)
        ->assertJsonPath('data.evaluation_id', $this->evaluation->id)
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.late_flag', false)
        ->assertJsonCount(2, 'data.files');

    $this->assertCount(1, Submission::query()->where('student_id', $this->student->id)->get());

    $submission = Submission::query()->firstOrFail();
    $this->assertSame((int) $this->school->id, (int) $submission->school_id);
    $this->assertSame((int) $this->student->id, (int) $submission->student_id);

    Storage::disk('local')->assertExists($submission->submissionFiles()->first()?->file_path);
});

it('forbids creating submission for evaluations where student is not enrolled (403)', function (): void {
    Sanctum::actingAs($this->studentOther);

    $this->postJson(route('submissions.store'), [
        'evaluation_id' => $this->evaluation->id,
        'files' => [UploadedFile::fake()->create('no-enroll.pdf', 100)],
    ])->assertForbidden();
});

it('requires evaluation_id and non-empty files array (422)', function (): void {
    Sanctum::actingAs($this->student);

    $this->postJson(route('submissions.store'), [
        'evaluation_id' => null,
        'files' => [],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['evaluation_id', 'files']);
});
