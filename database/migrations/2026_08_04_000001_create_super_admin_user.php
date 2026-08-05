<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $rolesTable = $tableNames['roles'];
        $modelHasRolesTable = $tableNames['model_has_roles'];
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'school_id';
        $now = now();

        DB::transaction(function () use (
            $rolesTable,
            $modelHasRolesTable,
            $pivotRole,
            $modelMorphKey,
            $teamForeignKey,
            $now
        ): void {
            $systemSchoolId = DB::table('schools')
                ->where('email', 'system@openschool.local')
                ->value('id');

            $systemSchoolPayload = [
                'name' => 'OpenSchool System',
                'email' => 'system@openschool.local',
                'email_verified_at' => $now,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($systemSchoolId === null) {
                $systemSchoolId = DB::table('schools')->insertGetId([
                    ...$systemSchoolPayload,
                    'created_at' => $now,
                ]);
            } else {
                DB::table('schools')
                    ->where('id', $systemSchoolId)
                    ->update($systemSchoolPayload);
            }

            $roleId = DB::table($rolesTable)
                ->where('name', 'super_admin')
                ->where('guard_name', 'web')
                ->where('school_id', $systemSchoolId)
                ->value('id');

            if ($roleId === null) {
                $roleId = DB::table($rolesTable)->insertGetId([
                    'name' => 'super_admin',
                    'guard_name' => 'web',
                    'school_id' => $systemSchoolId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $userId = DB::table('users')
                ->where('email', 'kreaker@kreaker.net')
                ->value('id');

            $userPayload = [
                'name' => 'Super Administrador',
                'email' => 'kreaker@kreaker.net',
                'password' => Hash::make('L0cur1t45'),
                'school_id' => null,
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

            $alreadyAssigned = DB::table($modelHasRolesTable)
                ->where($pivotRole, $roleId)
                ->where('model_type', User::class)
                ->where($modelMorphKey, $userId)
                ->where($teamForeignKey, $systemSchoolId)
                ->exists();

            if (! $alreadyAssigned) {
                DB::table($modelHasRolesTable)->insert([
                    $teamForeignKey => $systemSchoolId,
                    $pivotRole => $roleId,
                    'model_type' => User::class,
                    $modelMorphKey => $userId,
                ]);
            }
        });

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $rolesTable = $tableNames['roles'];
        $modelHasRolesTable = $tableNames['model_has_roles'];
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'school_id';

        DB::transaction(function () use (
            $rolesTable,
            $modelHasRolesTable,
            $pivotRole,
            $modelMorphKey,
            $teamForeignKey
        ): void {
            $systemSchoolId = DB::table('schools')
                ->where('email', 'system@openschool.local')
                ->value('id');

            $userId = DB::table('users')
                ->where('email', 'kreaker@kreaker.net')
                ->value('id');

            $roleId = DB::table($rolesTable)
                ->where('name', 'super_admin')
                ->where('guard_name', 'web')
                ->when($systemSchoolId !== null, fn ($query) => $query->where('school_id', $systemSchoolId))
                ->value('id');

            if ($userId !== null && $roleId !== null && $systemSchoolId !== null) {
                DB::table($modelHasRolesTable)
                    ->where($pivotRole, $roleId)
                    ->where('model_type', User::class)
                    ->where($modelMorphKey, $userId)
                    ->where($teamForeignKey, $systemSchoolId)
                    ->delete();
            }

            if ($userId !== null) {
                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }

            if ($roleId !== null) {
                DB::table($rolesTable)
                    ->where('id', $roleId)
                    ->delete();
            }

            if ($systemSchoolId !== null) {
                DB::table('schools')
                    ->where('id', $systemSchoolId)
                    ->delete();
            }
        });

        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
