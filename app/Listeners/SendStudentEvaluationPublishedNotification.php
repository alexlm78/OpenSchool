<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EvaluationPublished as EvaluationPublishedEvent;
use App\Models\User;
use App\Notifications\Student\NewEvaluationPublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class SendStudentEvaluationPublishedNotification
{
    public function handle(EvaluationPublishedEvent $event): void
    {
        $evaluation = $event->evaluation;
        $offeringId = $evaluation->getAttributeValue('course_offering_id');
        if ($offeringId === null) {
            return;
        }

        /** @var Collection<int, User> $studentUsers */
        $studentUsers = User::query()
            ->whereHas('enrollments', static function (Builder $q) use ($offeringId): void {
                $q->where('enrollments.course_offering_id', (int) $offeringId)
                    ->where('enrollments.status', 'active');
            })
            ->where('school_id', $evaluation->getAttributeValue('school_id'))
            ->get();

        if ($studentUsers->isEmpty()) {
            return;
        }

        Notification::send($studentUsers, new NewEvaluationPublished($evaluation));
    }
}
