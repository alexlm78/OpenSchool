<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Submission;
use App\Models\User;
use App\Notifications\Student\SubmissionGraded;
use App\Notifications\Student\SubmissionLateReceived;
use Illuminate\Support\Facades\Notification;

final class SubmissionObserver
{
    public function created(Submission $submission): void
    {
        if ((bool) $submission->getAttributeValue('late_flag') === true) {
            $this->notifyStudentUser($submission, new SubmissionLateReceived($submission));
        }
    }

    public function updated(Submission $submission): void
    {
        if ($submission->relationLoaded('grades') && $submission->grades->isNotEmpty()) {
            $this->notifyStudentUser($submission, new SubmissionGraded($submission));
        }
    }

    private function notifyStudentUser(Submission $submission, object $notification): void
    {
        $studentUserId = $submission->getAttributeValue('student_id');
        if ($studentUserId === null) {
            return;
        }

        /** @var User|null $studentUser */
        $studentUser = User::query()->find($studentUserId);
        if (! $studentUser instanceof User) {
            return;
        }

        Notification::send($studentUser, $notification);
    }
}
