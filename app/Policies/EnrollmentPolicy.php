<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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
            $guardianProfile = $user->guardianProfile;
            if (! $guardianProfile instanceof Guardian) {
                return false;
            }

            /** @var Collection<int, Student> $linkedStudents */
            $linkedStudents = $guardianProfile->students()->get(['students.id']);
            $linkedStudentIds = $linkedStudents
                ->map(static fn (Student $s): int => (int) $s->getAttributeValue('user_id'))
                ->all();

            return \in_array((int) $enrollment->getAttributeValue('student_id'), $linkedStudentIds, true);
        }

        return false;
    }
}
