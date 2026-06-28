<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['email' => 'demo-school@school.test'],
            [
                'name' => 'OpenSchool Demo',
                'email_verified_at' => now(),
            ],
        );

        /** @var PermissionRegistrar $permissionRegistrar */
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();
        $permissionRegistrar->setPermissionsTeamId($school->id);

        $adminRole = Role::firstOrCreate(
            [
                'name' => 'admin',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ],
        );

        $teacherRole = Role::firstOrCreate(
            [
                'name' => 'teacher',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ],
        );

        $studentRole = Role::firstOrCreate(
            [
                'name' => 'student',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ],
        );

        $guardianRole = Role::firstOrCreate(
            [
                'name' => 'guardian',
                'guard_name' => 'web',
                'school_id' => $school->id,
            ],
        );

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@school.test'],
            [
                'name' => 'Admin Demo',
                'password' => 'password',
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ],
        );
        $adminUser->syncRoles([$adminRole]);

        $teacherUser = User::updateOrCreate(
            ['email' => 'teacher@school.test'],
            [
                'name' => 'Docente Demo',
                'password' => 'password',
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ],
        );
        $teacherUser->syncRoles([$teacherRole]);

        Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'school_id' => $school->id,
                'employee_id' => 'TEACHER-DEMO-001',
                'department' => 'Academico',
                'specialization' => 'Educacion General',
                'phone' => '+56910000001',
            ],
        );

        $studentUser = User::updateOrCreate(
            ['email' => 'student@school.test'],
            [
                'name' => 'Alumno Demo',
                'password' => 'password',
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ],
        );
        $studentUser->syncRoles([$studentRole]);

        $student = Student::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'school_id' => $school->id,
                'student_id' => 'STUDENT-DEMO-001',
                'date_of_birth' => '2010-03-15',
                'gender' => 'no especificado',
                'address' => 'Direccion Demo 123',
                'phone' => '+56910000002',
            ],
        );

        $guardianUser = User::updateOrCreate(
            ['email' => 'guardian@school.test'],
            [
                'name' => 'Apoderado Demo',
                'password' => 'password',
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ],
        );
        $guardianUser->syncRoles([$guardianRole]);

        $guardian = Guardian::updateOrCreate(
            ['user_id' => $guardianUser->id],
            [
                'school_id' => $school->id,
                'relationship' => 'apoderado',
                'phone' => '+56910000003',
            ],
        );

        $guardian->students()->syncWithoutDetaching([
            $student->id => ['school_id' => $school->id],
        ]);

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'school_id' => $school->id,
                'email_verified_at' => now(),
            ],
        );
    }
}
