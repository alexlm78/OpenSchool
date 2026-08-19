<?php

namespace App\Listeners;

use App\Events\GradePublished;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class LogGradePublishedNotification
{
    public function handle(GradePublished $event): void
    {
        $grade = $event->grade;
        $schoolId = $event->getSchoolId();
        $studentUserId = $event->getStudentUserId();
        $evaluationId = $grade->evaluation_id;
        $score = $grade->score;
        $maxScore = $grade->evaluation?->max_score ?? null;

        $guardianIds = [];
        try {
            if ($studentUserId !== null) {
                $student = \App\Models\Student::query()
                    ->where('user_id', $studentUserId)
                    ->first();
                if ($student instanceof \App\Models\Student) {
                    $guardianIds = $student->guardians()
                        ->pluck('guardians.user_id')
                        ->filter(static fn ($id): bool => is_int($id))
                        ->values()
                        ->all();
                }
            }
        } catch (\Throwable) {
            $guardianIds = [];
        }

        $studentName = null;
        try {
            if ($studentUserId !== null) {
                $user = User::query()->find($studentUserId);
                if ($user instanceof User) {
                    $studentName = (string) ($user->name ?? null);
                }
            }
        } catch (\Throwable) {
        }

        Log::info('GradePublished event handled (notification ready for channels).', [
            'event' => 'GradePublished',
            'school_id' => $schoolId,
            'evaluation_id' => $evaluationId,
            'student_user_id' => $studentUserId,
            'student_name' => $studentName,
            'score' => $score,
            'max_score' => $maxScore,
            'feedback' => $grade->feedback,
            'graded_by_user_id' => $grade->graded_by,
            'guardian_user_ids_to_notify' => $guardianIds,
            'guardian_count' => count($guardianIds),
            'is_update' => $event->isUpdate,
        ]);
    }
}
