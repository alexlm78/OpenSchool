<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GradePublished as GradePublishedEvent;
use App\Models\User;
use App\Notifications\Student\GradePublished;
use Illuminate\Support\Facades\Notification;

final class SendStudentGradePublishedNotification
{
    public function handle(GradePublishedEvent $event): void
    {
        $studentUserId = $event->getStudentUserId();
        if ($studentUserId === null) {
            return;
        }

        /** @var User|null $studentUser */
        $studentUser = User::query()->find($studentUserId);
        if (! $studentUser instanceof User) {
            return;
        }

        Notification::send($studentUser, new GradePublished($event->grade));
    }
}
