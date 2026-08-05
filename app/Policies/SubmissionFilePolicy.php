<?php

namespace App\Policies;

use App\Models\SubmissionFile;
use App\Models\TeachingAssignment;
use App\Models\User;

final class SubmissionFilePolicy
{
    public function download(User $user, SubmissionFile $submissionFile): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->school_id === null) {
            return false;
        }

        if ((int) $user->school_id !== (int) $submissionFile->school_id) {
            return false;
        }

        $submission = $submissionFile->submission;
        if ($submission === null) {
            return false;
        }

        if ((int) $submission->student_id === (int) $user->id) {
            return true;
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
