<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Support\LinkedGuardianStudents;

final class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAdministrativeRole()
            || $user->hasAnyRole(['teacher', 'student', 'guardian']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        if ((int) $user->getAttributeValue('school_id') !== (int) $submission->getAttributeValue('school_id')) {
            return false;
        }

        if ($user->hasAdministrativeRole()) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            $evaluation = $submission->evaluation;
            if ($evaluation === null) {
                return false;
            }

            return TeachingAssignment::query()
                ->where('school_id', (int) $user->getAttributeValue('school_id'))
                ->where('teacher_id', (int) $user->getKey())
                ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
                ->exists();
        }

        if ($user->hasRole('student')) {
            return (int) $submission->getAttributeValue('student_id') === (int) $user->getKey();
        }

        if ($user->hasRole('guardian')) {
            $linked = LinkedGuardianStudents::resolveForUser($user);

            return \in_array((int) $submission->getAttributeValue('student_id'), $linked['userIds'], true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        return $user->hasRole('student');
    }

    public function grade(User $user, Submission $submission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->getAttributeValue('school_id') === null) {
            return false;
        }

        if ((int) $user->getAttributeValue('school_id') !== (int) $submission->getAttributeValue('school_id')) {
            return false;
        }

        if ($user->hasAdministrativeRole()) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        $evaluation = $submission->evaluation;
        if ($evaluation === null) {
            return false;
        }

        return TeachingAssignment::query()
            ->where('school_id', (int) $user->getAttributeValue('school_id'))
            ->where('teacher_id', (int) $user->getKey())
            ->where('course_offering_id', (int) $evaluation->getAttributeValue('course_offering_id'))
            ->exists();
    }
}
