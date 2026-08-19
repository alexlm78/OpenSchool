<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $data = $this->loadData();
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $rolesTable = $tableNames['roles'];
        $modelHasRolesTable = $tableNames['model_has_roles'];
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'school_id';
        $now = now();

        DB::transaction(function () use (
            $data,
            $rolesTable,
            $modelHasRolesTable,
            $pivotRole,
            $modelMorphKey,
            $teamForeignKey,
            $now
        ): void {
            $school = $data['school'];
            $schoolId = DB::table('schools')
                ->where('email', $school['email'])
                ->value('id');

            $schoolPayload = [
                'name' => $school['name'],
                'email' => $school['email'],
                'email_verified_at' => ($school['email_verified_at'] ?? false) ? $now : null,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($schoolId === null) {
                $schoolId = DB::table('schools')->insertGetId([
                    ...$schoolPayload,
                    'created_at' => $now,
                ]);
            } else {
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->update($schoolPayload);
            }

            $roleIds = [];
            foreach ($this->rolesFromData($data) as $roleName) {
                $roleId = DB::table($rolesTable)
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->where('school_id', $schoolId)
                    ->value('id');

                if ($roleId === null) {
                    $roleId = DB::table($rolesTable)->insertGetId([
                        'name' => $roleName,
                        'guard_name' => 'web',
                        'school_id' => $schoolId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $roleIds[$roleName] = $roleId;
            }

            $userIdsByEmail = [];
            $studentIdsByEmail = [];
            $guardianIdsByEmail = [];

            foreach ($data['users'] as $user) {
                $userId = DB::table('users')
                    ->where('email', $user['email'])
                    ->value('id');

                $userPayload = [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'school_id' => $schoolId,
                    'email_verified_at' => $now,
                    'updated_at' => $now,
                ];

                if ($userId === null) {
                    $userId = DB::table('users')->insertGetId([
                        ...$userPayload,
                        'created_at' => $now,
                    ]);
                } else {
                    DB::table('users')
                        ->where('id', $userId)
                        ->update($userPayload);
                }

                $userIdsByEmail[$user['email']] = $userId;

                $roleName = $user['role'] ?? null;
                if (is_string($roleName) && isset($roleIds[$roleName])) {
                    $alreadyAssigned = DB::table($modelHasRolesTable)
                        ->where($pivotRole, $roleIds[$roleName])
                        ->where('model_type', User::class)
                        ->where($modelMorphKey, $userId)
                        ->where($teamForeignKey, $schoolId)
                        ->exists();

                    if (! $alreadyAssigned) {
                        DB::table($modelHasRolesTable)->insert([
                            $teamForeignKey => $schoolId,
                            $pivotRole => $roleIds[$roleName],
                            'model_type' => User::class,
                            $modelMorphKey => $userId,
                        ]);
                    }
                }

                if (isset($user['teacher']) && is_array($user['teacher'])) {
                    $teacherId = DB::table('teachers')
                        ->where('user_id', $userId)
                        ->where('school_id', $schoolId)
                        ->value('id');

                    $teacherPayload = [
                        'school_id' => $schoolId,
                        'user_id' => $userId,
                        'employee_id' => $user['teacher']['employee_id'] ?? null,
                        'department' => $user['teacher']['department'] ?? null,
                        'specialization' => $user['teacher']['specialization'] ?? null,
                        'phone' => $user['teacher']['phone'] ?? null,
                        'updated_at' => $now,
                    ];

                    if ($teacherId === null) {
                        DB::table('teachers')->insert([
                            ...$teacherPayload,
                            'created_at' => $now,
                        ]);
                    } else {
                        DB::table('teachers')
                            ->where('id', $teacherId)
                            ->update($teacherPayload);
                    }
                }

                if (isset($user['student']) && is_array($user['student'])) {
                    $studentId = DB::table('students')
                        ->where('user_id', $userId)
                        ->where('school_id', $schoolId)
                        ->value('id');

                    $studentPayload = [
                        'school_id' => $schoolId,
                        'user_id' => $userId,
                        'student_id' => $user['student']['student_id'],
                        'date_of_birth' => $user['student']['date_of_birth'] ?? null,
                        'gender' => $user['student']['gender'] ?? null,
                        'address' => $user['student']['address'] ?? null,
                        'phone' => $user['student']['phone'] ?? null,
                        'updated_at' => $now,
                    ];

                    if ($studentId === null) {
                        DB::table('students')->insert([
                            ...$studentPayload,
                            'created_at' => $now,
                        ]);

                        $studentId = DB::table('students')
                            ->where('user_id', $userId)
                            ->where('school_id', $schoolId)
                            ->value('id');
                    } else {
                        DB::table('students')
                            ->where('id', $studentId)
                            ->update($studentPayload);
                    }

                    if ($studentId !== null) {
                        $studentIdsByEmail[$user['email']] = $studentId;
                    }
                }

                if (isset($user['guardian']) && is_array($user['guardian'])) {
                    $guardianId = DB::table('guardians')
                        ->where('user_id', $userId)
                        ->where('school_id', $schoolId)
                        ->value('id');

                    $guardianPayload = [
                        'school_id' => $schoolId,
                        'user_id' => $userId,
                        'relationship' => $user['guardian']['relationship'] ?? null,
                        'phone' => $user['guardian']['phone'] ?? null,
                        'updated_at' => $now,
                    ];

                    if ($guardianId === null) {
                        DB::table('guardians')->insert([
                            ...$guardianPayload,
                            'created_at' => $now,
                        ]);

                        $guardianId = DB::table('guardians')
                            ->where('user_id', $userId)
                            ->where('school_id', $schoolId)
                            ->value('id');
                    } else {
                        DB::table('guardians')
                            ->where('id', $guardianId)
                            ->update($guardianPayload);
                    }

                    if ($guardianId !== null) {
                        $guardianIdsByEmail[$user['email']] = $guardianId;
                    }
                }
            }

            foreach ($data['users'] as $user) {
                $linkedStudents = $user['links']['students'] ?? null;
                if (! is_array($linkedStudents) || ! isset($guardianIdsByEmail[$user['email']])) {
                    continue;
                }

                foreach ($linkedStudents as $studentEmail) {
                    $studentId = $studentIdsByEmail[$studentEmail] ?? null;
                    if ($studentId === null) {
                        continue;
                    }

                    $alreadyLinked = DB::table('guardian_student')
                        ->where('school_id', $schoolId)
                        ->where('guardian_id', $guardianIdsByEmail[$user['email']])
                        ->where('student_id', $studentId)
                        ->exists();

                    if (! $alreadyLinked) {
                        DB::table('guardian_student')->insert([
                            'school_id' => $schoolId,
                            'guardian_id' => $guardianIdsByEmail[$user['email']],
                            'student_id' => $studentId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $data = $this->loadData();
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $rolesTable = $tableNames['roles'];
        $modelHasRolesTable = $tableNames['model_has_roles'];
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'school_id';

        DB::transaction(function () use (
            $data,
            $rolesTable,
            $modelHasRolesTable,
            $pivotRole,
            $modelMorphKey,
            $teamForeignKey
        ): void {
            $schoolId = DB::table('schools')
                ->where('email', $data['school']['email'])
                ->value('id');

            if ($schoolId === null) {
                return;
            }

            $userIdsByEmail = [];
            foreach ($data['users'] as $user) {
                $userId = DB::table('users')
                    ->where('email', $user['email'])
                    ->value('id');

                if ($userId !== null) {
                    $userIdsByEmail[$user['email']] = $userId;
                }
            }

            $studentIdsByEmail = [];
            foreach ($userIdsByEmail as $email => $userId) {
                $studentId = DB::table('students')
                    ->where('user_id', $userId)
                    ->where('school_id', $schoolId)
                    ->value('id');

                if ($studentId !== null) {
                    $studentIdsByEmail[$email] = $studentId;
                }
            }

            foreach ($data['users'] as $user) {
                $linkedStudents = $user['links']['students'] ?? null;
                if (! is_array($linkedStudents)) {
                    continue;
                }

                $guardianId = DB::table('guardians')
                    ->where('user_id', $userIdsByEmail[$user['email']] ?? 0)
                    ->where('school_id', $schoolId)
                    ->value('id');

                if ($guardianId === null) {
                    continue;
                }

                foreach ($linkedStudents as $studentEmail) {
                    $studentId = $studentIdsByEmail[$studentEmail] ?? null;
                    if ($studentId === null) {
                        continue;
                    }

                    DB::table('guardian_student')
                        ->where('school_id', $schoolId)
                        ->where('guardian_id', $guardianId)
                        ->where('student_id', $studentId)
                        ->delete();
                }
            }

            foreach ($userIdsByEmail as $userId) {
                DB::table('guardians')
                    ->where('user_id', $userId)
                    ->where('school_id', $schoolId)
                    ->delete();

                DB::table('students')
                    ->where('user_id', $userId)
                    ->where('school_id', $schoolId)
                    ->delete();

                DB::table('teachers')
                    ->where('user_id', $userId)
                    ->where('school_id', $schoolId)
                    ->delete();
            }

            $roleIds = DB::table($rolesTable)
                ->where('school_id', $schoolId)
                ->whereIn('name', $this->rolesFromData($data))
                ->pluck('id')
                ->all();

            if ($roleIds !== []) {
                DB::table($modelHasRolesTable)
                    ->where('model_type', User::class)
                    ->where($teamForeignKey, $schoolId)
                    ->whereIn($pivotRole, $roleIds)
                    ->whereIn($modelMorphKey, array_values($userIdsByEmail))
                    ->delete();
            }

            if ($userIdsByEmail !== []) {
                DB::table('users')
                    ->whereIn('id', array_values($userIdsByEmail))
                    ->delete();
            }

            DB::table($rolesTable)
                ->where('school_id', $schoolId)
                ->whereIn('name', $this->rolesFromData($data))
                ->delete();

            DB::table('schools')
                ->where('id', $schoolId)
                ->delete();
        });

        $this->forgetPermissionCache();
    }

    private function loadData(): array
    {
        $path = base_path('database/data/test_users.php');
        if (! file_exists($path)) {
            throw new RuntimeException('The test users data file was not found.');
        }

        $data = require $path;
        if (! is_array($data) || ! isset($data['school'], $data['users']) || ! is_array($data['users'])) {
            throw new RuntimeException('The test users data file has an invalid structure.');
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function rolesFromData(array $data): array
    {
        $roles = [];
        foreach ($data['users'] as $user) {
            $roleName = $user['role'] ?? null;
            if (! is_string($roleName) || in_array($roleName, $roles, true)) {
                continue;
            }

            $roles[] = $roleName;
        }

        return $roles;
    }

    private function forgetPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
