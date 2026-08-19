<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\TeachingAssignment;
use App\Models\User;

final class SubmissionPolicy
{
    public function grade(User $user, Submission $submission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->school_id === null) {
            return false;
        }

        if ((int) $user->school_id !== (int) $submission->school_id) {
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
            ->where('school_id', $user->school_id)
            ->where('teacher_id', $user->id)
            ->where('course_offering_id', $evaluation->course_offering_id)
            ->exists();
    }
}
