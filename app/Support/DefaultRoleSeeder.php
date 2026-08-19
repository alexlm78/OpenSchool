<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class DefaultRoleSeeder
{
    public const DEFAULT_ROLES = ['admin', 'teacher', 'student', 'guardian'];

    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function seedForSchool(School|int $school, string $guard = 'web', bool $force = false): array
    {
        $schoolId = $school instanceof School ? (int) $school->getKey() : (int) $school;
        if ($schoolId < 1) {
            throw new \InvalidArgumentException('Invalid school id provided for default role seeding.');
        }

        $this->permissionRegistrar->forgetCachedPermissions();
        $this->permissionRegistrar->setPermissionsTeamId($schoolId);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'school_id');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (self::DEFAULT_ROLES as $roleName) {
            $existing = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->where($teamForeignKey, $schoolId)
                ->first();

            if ($existing instanceof Role && ! $force) {
                $skipped++;

                continue;
            }

            $attributes = [
                'name' => $roleName,
                'guard_name' => $guard,
            ];

            if ($existing instanceof Role && $force) {
                $existing->fill($attributes);
                $existing->saveOrFail();
                $updated++;

                continue;
            }

            Role::create($attributes);
            $created++;
        }

        return compact('created', 'updated', 'skipped');
    }
}
