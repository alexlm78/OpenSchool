<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\LinkedGuardianStudents;

final class EvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAdministrativeRole()
            || $user->hasAnyRole(['teacher', 'student', 'guardian']);
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        if ((int) $user->getAttributeValue('school_id') !== (int) $evaluation->getAttributeValue('school_id')) {
            return false;
        }

        if ($user->hasAdministrativeRole()) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return TeachingAssignment::query()
                ->where('school_id', (int) $user->getAttributeValue('school_id'))
                ->where('teacher_id', (int) $user->getKey())
                ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
                ->exists();
        }

        if ($user->hasRole('student')) {
            return Enrollment::query()
                ->where('school_id', (int) $user->getAttributeValue('school_id'))
                ->where('student_id', (int) $user->getKey())
                ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        if ($user->hasRole('guardian')) {
            $linked = LinkedGuardianStudents::resolveForUser($user);

            return Enrollment::query()
                ->where('school_id', (int) $user->getAttributeValue('school_id'))
                ->whereIn('student_id', $linked['userIds'])
                ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return false;
    }
}
