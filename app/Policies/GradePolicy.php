<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;
use App\Support\LinkedGuardianStudents;

final class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAdministrativeRole()
            || $user->hasAnyRole(['teacher', 'student', 'guardian']);
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        if ((int) $user->getAttributeValue('school_id') !== (int) $grade->getAttributeValue('school_id')) {
            return false;
        }

        if ($user->hasAdministrativeRole()) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return (int) $grade->getAttributeValue('graded_by') === (int) $user->getKey();
        }

        if ($user->hasRole('student')) {
            return (int) $grade->getAttributeValue('student_id') === (int) $user->getKey();
        }

        if ($user->hasRole('guardian')) {
            $linked = LinkedGuardianStudents::resolveForUser($user);

            return \in_array((int) $grade->getAttributeValue('student_id'), $linked['userIds'], true);
        }

        return false;
    }
}
