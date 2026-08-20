<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Guardian;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

final class LinkedGuardianStudents
{
    /**
     * @return array{profileIds: array<int, int>, userIds: array<int, int>}
     */
    public static function resolveForUser(User $user): array
    {
        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        if (! $user->hasRole('guardian')) {
            return ['profileIds' => [], 'userIds' => []];
        }

        $profile = $user->guardianProfile;
        if (! $profile instanceof Guardian) {
            return ['profileIds' => [], 'userIds' => []];
        }

        $rows = $profile->students()
            ->get(['students.id', 'students.user_id']);

        $profileIds = [];
        $userIds = [];
        foreach ($rows as $row) {
            $pid = $row->getAttributeValue('id');
            $uid = $row->getAttributeValue('user_id');
            if (is_numeric($pid)) {
                $profileIds[] = (int) $pid;
            }
            if (is_numeric($uid)) {
                $userIds[] = (int) $uid;
            }
        }

        return [
            'profileIds' => array_values(array_unique($profileIds)),
            'userIds' => array_values(array_unique($userIds)),
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function profileIds(User $user): array
    {
        return self::resolveForUser($user)['profileIds'];
    }

    /**
     * @return array<int, int>
     */
    public static function userIds(User $user): array
    {
        return self::resolveForUser($user)['userIds'];
    }
}
