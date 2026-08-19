<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EvaluationPublished;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Log;

final class LogEvaluationPublishedNotification
{
    public function handle(EvaluationPublished $event): void
    {
        $evaluation = $event->evaluation;
        $schoolId = $event->getSchoolId();
        $courseOfferingId = $evaluation->course_offering_id;
        $evaluationId = $evaluation->getKey();
        $title = (string) ($evaluation->title ?? 'Unnamed evaluation');

        $studentCount = 0;
        try {
            $query = Enrollment::query()
                ->select('student_id')
                ->where('status', 'active');

            if (\is_int($schoolId)) {
                $query->where('school_id', $schoolId);
            }
            if (\is_int($courseOfferingId)) {
                $query->where('course_offering_id', $courseOfferingId);
            }

            $studentCount = $query->distinct()->count('student_id');
        } catch (\Throwable) {
            $studentCount = 0;
        }

        Log::info('EvaluationPublished event handled (notification ready for channels).', [
            'event' => 'EvaluationPublished',
            'school_id' => $schoolId,
            'course_offering_id' => $courseOfferingId,
            'evaluation_id' => $evaluationId,
            'evaluation_title' => $title,
            'due_at' => $evaluation->due_at,
            'published_at' => $evaluation->published_at,
            'max_score' => $evaluation->max_score,
            'weight' => $evaluation->weight,
            'allow_late_submission' => (bool) ($evaluation->allow_late_submission ?? false),
            'affected_student_count' => $studentCount,
            'is_republish' => $event->isRepublish,
        ]);
    }
}
