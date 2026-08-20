<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use App\Support\LinkedGuardianStudents;

final class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAdministrativeRole()
            || $user->hasAnyRole(['teacher', 'student', 'guardian']);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        if ((int) $user->getAttributeValue('school_id') !== (int) $enrollment->getAttributeValue('school_id')) {
            return false;
        }

        if ($user->hasAdministrativeRole()) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            return (int) $enrollment->getAttributeValue('student_id') === (int) $user->getKey();
        }

        if ($user->hasRole('guardian')) {
            $linked = LinkedGuardianStudents::resolveForUser($user);

            return \in_array((int) $enrollment->getAttributeValue('student_id'), $linked['profileIds'], true);
        }

        return false;
    }
}
