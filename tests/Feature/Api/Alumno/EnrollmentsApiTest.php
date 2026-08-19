<?php

declare(strict_types=1);

use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
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
        'name' => 'Escuela Enrollments',
        'email' => 'enrollments@example.com',
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
        'name' => '2026-I',
        'type' => 'semester',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addMonths(5)->toDateString(),
    ]);
    $mathTemplate = CourseTemplate::query()->create([
        'school_id' => $this->school->id,
        'name' => 'Matemáticas',
        'code' => 'MAT101',
    ]);
    $biologyTemplate = CourseTemplate::query()->create([
        'school_id' => $this->school->id,
        'name' => 'Biología',
        'code' => 'BIO101',
    ]);

    $this->offeringMath = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $mathTemplate->id,
        'capacity' => 30,
        'section_name' => 'A',
    ]);
    $this->offeringBiology = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $biologyTemplate->id,
        'capacity' => 25,
        'section_name' => 'C',
    ]);

    $this->student1 = User::factory()->createOne(['school_id' => $this->school->id]);
    $this->student2 = User::factory()->createOne(['school_id' => $this->school->id]);

    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->student1->id, 'student_id' => 'S-001']);
    Student::query()->create(['school_id' => $this->school->id, 'user_id' => $this->student2->id, 'student_id' => 'S-002']);

    $this->student1->assignRole($studentRole);
    $this->student2->assignRole($studentRole);

    Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->student1->id,
        'course_offering_id' => $this->offeringMath->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);
    Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->student2->id,
        'course_offering_id' => $this->offeringBiology->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);
});

it('student 1 sees only math enrollment in API', function (): void {
    Sanctum::actingAs($this->student1);

    $response = $this->getJson(route('enrollments.index'));

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.course_offering.section_name', 'A')
        ->assertJsonPath('data.0.course_offering.course_template.code', 'MAT101');

    $ids = collect($response->json('data'))->pluck('course_offering.id')->all();

    $this->assertContains($this->offeringMath->id, $ids);
    $this->assertNotContains($this->offeringBiology->id, $ids);
});

it('student 2 sees only biology enrollment in API', function (): void {
    Sanctum::actingAs($this->student2);

    $response = $this->getJson(route('enrollments.index'));

    $ids = collect($response->json('data'))->pluck('course_offering.id')->all();

    $this->assertContains($this->offeringBiology->id, $ids);
    $this->assertNotContains($this->offeringMath->id, $ids);
});

it('returns 401 to unauthenticated request for enrollments', function (): void {
    $this->getJson(route('enrollments.index'))->assertUnauthorized();
});

it('student 1 can view detail of their own enrollment via policy', function (): void {
    $enrollment1 = Enrollment::query()->where('student_id', $this->student1->id)->firstOrFail();
    Sanctum::actingAs($this->student1);

    $response = $this->getJson(route('enrollments.show', $enrollment1));

    $response->assertOk()
        ->assertJsonPath('data.id', $enrollment1->id)
        ->assertJsonPath('data.status_meta.level', 'success');
});

it('student 2 cannot view enrollment of student 1 via policy', function (): void {
    $enrollment1 = Enrollment::query()->where('student_id', $this->student1->id)->firstOrFail();
    Sanctum::actingAs($this->student2);

    $this->getJson(route('enrollments.show', $enrollment1))->assertForbidden();
});
