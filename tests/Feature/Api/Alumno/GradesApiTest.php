<?php

declare(strict_types=1);

use App\Models\AcademicPeriod;
use App\Models\AssignmentDetails;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
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
        'name' => 'Escuela Notas',
        'email' => 'grades@example.com',
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
        'school_id' => $this->school->id, 'name' => 'Año', 'type' => 'annual',
        'starts_at' => now()->toDateString(), 'ends_at' => now()->addYear()->toDateString(),
    ]);
    $tpl = CourseTemplate::query()->create(['school_id' => $this->school->id, 'name' => 'Historia', 'code' => 'HIS101']);
    $this->offering = CourseOffering::query()->create([
        'school_id' => $this->school->id, 'academic_period_id' => $period->id,
        'course_template_id' => $tpl->id, 'capacity' => 40, 'section_name' => 'Única',
    ]);

    $this->approved = User::factory()->createOne(['school_id' => $this->school->id]);
    $this->failed = User::factory()->createOne(['school_id' => $this->school->id]);

    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->approved->id, 'student_id' => 'NOTAS-1']);
    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->failed->id, 'student_id' => 'NOTAS-2']);
    $this->approved->assignRole($studentRole);
    $this->failed->assignRole($studentRole);

    Enrollment::query()->create([
        'school_id' => $this->school->id, 'student_id' => $this->approved->id,
        'course_offering_id' => $this->offering->id, 'status' => 'active',
    ]);
    Enrollment::query()->create([
        'school_id' => $this->school->id, 'student_id' => $this->failed->id,
        'course_offering_id' => $this->offering->id, 'status' => 'active',
    ]);

    $assignment = AssignmentDetails::query()->create([
        'school_id' => $this->school->id,
        'evaluationable_type' => Evaluation::class,
        'evaluationable_id' => 0,
        'description' => 'Prueba final',
        'file_requirements' => 'N/A',
        'allow_late_submission' => false,
        'late_penalty_percent' => 0,
    ]);
    $this->evaluation = Evaluation::query()->create([
        'school_id' => $this->school->id, 'course_offering_id' => $this->offering->id,
        'title' => 'Examen', 'max_score' => 100, 'weight' => 1,
        'published_at' => now()->subWeeks(2),
        'due_at' => now()->subWeek(),
        'evaluationable_type' => 'App\\Models\\AssignmentDetails',
        'evaluationable_id' => $assignment->id,
    ]);
    $assignment->update(['evaluationable_id' => $this->evaluation->id]);

    Grade::query()->create([
        'school_id' => $this->school->id, 'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->approved->id, 'score' => 92, 'feedback' => '¡Excelente trabajo!',
    ]);
    Grade::query()->create([
        'school_id' => $this->school->id, 'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->failed->id, 'score' => 45, 'feedback' => 'Estudiar más para la próxima.',
    ]);
});

it('approved student sees only their own 92 grade in API', function (): void {
    Sanctum::actingAs($this->approved);

    $response = $this->getJson(route('grades.index'));
    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.score', 92)
        ->assertJsonPath('data.0.score_meta.level', 'success')
        ->assertJsonPath('data.0.score_meta.label', 'Aprobado')
        ->assertJsonPath('data.0.feedback', '¡Excelente trabajo!');
});

it('failed student sees only their 45 grade (fail)', function (): void {
    Sanctum::actingAs($this->failed);

    $response = $this->getJson(route('grades.index'));

    $response->assertOk()
        ->assertJsonPath('data.0.score', 45)
        ->assertJsonPath('data.0.score_meta.level', 'danger')
        ->assertJsonPath('data.0.score_meta.label', 'Reprobado');
});

it('approved student cannot see failed grade (no scope leak)', function (): void {
    Sanctum::actingAs($this->approved);

    $scores = collect($this->getJson(route('grades.index'))->json('data'))
        ->pluck('score')
        ->all();

    $this->assertContains(92, $scores);
    $this->assertNotContains(45, $scores);
});

it('unauthenticated user cannot see grades (401)', function (): void {
    $this->getJson(route('grades.index'))->assertUnauthorized();
});

it('student cannot view grade of another student via show route (403)', function (): void {
    $failedGrade = Grade::query()->where('student_id', $this->failed->id)->firstOrFail();

    Sanctum::actingAs($this->approved);
    $this->getJson(route('grades.show', $failedGrade))->assertForbidden();
});

it('student can view their own grade detail via show route', function (): void {
    $approvedGrade = Grade::query()->where('student_id', $this->approved->id)->firstOrFail();
    Sanctum::actingAs($this->approved);

    $this->getJson(route('grades.show', $approvedGrade))
        ->assertOk()
        ->assertJsonPath('data.id', $approvedGrade->id);
});
