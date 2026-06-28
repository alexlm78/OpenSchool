<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->setSchoolId(null);
    }

    public function test_admin_login_page_is_accessible_for_guests(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_docente_login_page_is_accessible_for_guests(): void
    {
        $response = $this->get('/docente/login');

        $response->assertOk();
    }

    public function test_alumno_login_page_is_accessible_for_guests(): void
    {
        $response = $this->get('/alumno/login');

        $response->assertOk();
    }

    public function test_apoderado_login_page_is_accessible_for_guests(): void
    {
        $response = $this->get('/apoderado/login');

        $response->assertOk();
    }

    public function test_teacher_cannot_access_admin_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $teacher = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_docente_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $admin = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/docente')
            ->assertForbidden();
    }

    public function test_student_cannot_access_other_panels(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $student = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $student->assignRole('student');

        $this->actingAs($student)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/docente')
            ->assertForbidden();

        $this->actingAs($student)
            ->get('/apoderado')
            ->assertForbidden();
    }

    public function test_guardian_cannot_access_other_panels(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $guardian = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $guardian->assignRole('guardian');

        $this->actingAs($guardian)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($guardian)
            ->get('/docente')
            ->assertForbidden();

        $this->actingAs($guardian)
            ->get('/alumno')
            ->assertForbidden();
    }

    public function test_user_without_role_cannot_access_panels(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $user = $this->createUserForSchool($school->id);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/docente')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/alumno')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/apoderado')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $admin = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $admin->assignRole('admin');
        $this->assertTrue($admin->hasRole('admin'));

        $response = $this->actingAs($admin)->get('/admin');

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_teacher_can_access_docente_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $teacher = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $teacher->assignRole('teacher');
        $this->assertTrue($teacher->hasRole('teacher'));

        $response = $this->actingAs($teacher)->get('/docente');

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_student_can_access_alumno_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $student = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $student->assignRole('student');
        $this->assertTrue($student->hasRole('student'));

        $response = $this->actingAs($student)->get('/alumno');

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_guardian_can_access_apoderado_panel(): void
    {
        $school = $this->createSchool();
        $this->seedRolesForSchool($school->id);

        $guardian = $this->createUserForSchool($school->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);
        $guardian->assignRole('guardian');
        $this->assertTrue($guardian->hasRole('guardian'));

        $response = $this->actingAs($guardian)->get('/apoderado');

        $this->assertNotSame(403, $response->getStatusCode());
    }

    private function createSchool(): School
    {
        return School::create([
            'name' => 'Escuela A',
            'email' => 'escuela-a@example.com',
        ]);
    }

    private function seedRolesForSchool(int $schoolId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);

        Role::create(['name' => 'admin', 'guard_name' => 'web', 'school_id' => $schoolId]);
        Role::create(['name' => 'teacher', 'guard_name' => 'web', 'school_id' => $schoolId]);
        Role::create(['name' => 'student', 'guard_name' => 'web', 'school_id' => $schoolId]);
        Role::create(['name' => 'guardian', 'guard_name' => 'web', 'school_id' => $schoolId]);
    }

    private function createUserForSchool(int $schoolId): User&Authenticatable
    {
        $user = User::factory()->createOne(['school_id' => $schoolId]);

        if (! $user instanceof User) {
            throw new \RuntimeException('Expected a User instance.');
        }

        if (! $user instanceof Authenticatable) {
            throw new \RuntimeException('Expected a User that implements Authenticatable.');
        }

        return $user;
    }
}
