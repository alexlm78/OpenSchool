<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\Student\EnrollmentStatusChanged;
use Illuminate\Support\Facades\Notification;

final class EnrollmentObserver
{
    public function updated(Enrollment $enrollment): void
    {
        $previousStatus = (string) ($enrollment->getOriginal('status') ?? '');
        $newStatus = (string) $enrollment->getAttributeValue('status');

        if ($previousStatus === '' || $previousStatus === $newStatus) {
            return;
        }

        $studentUserId = $enrollment->getAttributeValue('student_id');
        if ($studentUserId === null) {
            return;
        }

        /** @var User|null $studentUser */
        $studentUser = User::query()->find($studentUserId);
        if (! $studentUser instanceof User) {
            return;
        }

        Notification::send($studentUser, new EnrollmentStatusChanged($enrollment, $previousStatus));
    }
}
