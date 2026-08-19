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
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(
    RefreshDatabase::class,
);

beforeEach(function (): void {
    $this->school = School::query()->create([
        'name' => 'Escuela Evaluaciones',
        'email' => 'evals@example.com',
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
        'school_id' => $this->school->id,
        'name' => '2026-II',
        'type' => 'semester',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addMonths(4)->toDateString(),
    ]);
    $tpl = CourseTemplate::query()->create([
        'school_id' => $this->school->id, 'name' => 'Física', 'code' => 'FIS101',
    ]);
    $this->offering = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $tpl->id,
        'capacity' => 25,
        'section_name' => 'B',
    ]);

    $this->studentEnrolled = User::factory()->createOne(['school_id' => $this->school->id]);
    $this->studentUnrelated = User::factory()->createOne(['school_id' => $this->school->id]);

    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->studentEnrolled->id, 'student_id' => 'SEV-1']);
    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->studentUnrelated->id, 'student_id' => 'SEV-2']);

    $this->studentEnrolled->assignRole($studentRole);
    $this->studentUnrelated->assignRole($studentRole);

    Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->studentEnrolled->id,
        'course_offering_id' => $this->offering->id,
        'status' => 'active',
    ]);

    $assignment = AssignmentDetails::query()->create([
        'school_id' => $this->school->id,
        'evaluationable_type' => Evaluation::class,
        'evaluationable_id' => 0,
        'description' => 'Tarea de física',
        'file_requirements' => 'PDF, PNG',
        'allow_late_submission' => true,
        'late_penalty_percent' => 5,
    ]);

    $this->evaluation = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offering->id,
        'title' => 'Tarea 1 - Cinemática',
        'description' => 'Resolver ejercicios 1-5',
        'max_score' => 100,
        'weight' => 1,
        'due_at' => now()->addWeeks(2),
        'published_at' => now()->subMinute(),
        'evaluationable_type' => 'App\\Models\\AssignmentDetails',
        'evaluationable_id' => $assignment->id,
    ]);
    $assignment->update(['evaluationable_id' => $this->evaluation->id]);
});

it('enrolled student sees evaluation in list with pending status', function (): void {
    Sanctum::actingAs($this->studentEnrolled);

    $response = $this->getJson(route('evaluations.index'));
    $response->assertOk()
        ->assertJsonPath('data.0.title', 'Tarea 1 - Cinemática')
        ->assertJsonPath('data.0.student_status.level', 'warning')
        ->assertJsonPath('data.0.student_status.label', 'No enviado')
        ->assertJsonPath('data.0.student_status.has_submission', false)
        ->assertJsonPath('meta.total', 1);
});

it('unrelated student sees 0 evaluations (strict scope)', function (): void {
    Sanctum::actingAs($this->studentUnrelated);

    $this->getJson(route('evaluations.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 0)
        ->assertJsonCount(0, 'data');
});

it('show endpoint returns requirements plus no submission yet', function (): void {
    Sanctum::actingAs($this->studentEnrolled);

    $response = $this->getJson(route('evaluations.show', $this->evaluation));

    $response->assertOk()
        ->assertJsonPath('data.requirements.type', 'assignment_details')
        ->assertJsonPath('data.requirements.allow_late_submission', true)
        ->assertJsonPath('data.requirements.late_penalty_percent', 5)
        ->assertJsonPath('data.my_submission', null)
        ->assertJsonPath('data.my_grade', null);
});
