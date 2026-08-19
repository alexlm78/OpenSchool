<?php

namespace App\Events;

use App\Models\Evaluation;
use App\Models\School;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EvaluationPublished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Evaluation $evaluation,
        public readonly ?School $school = null,
        public readonly bool $isRepublish = false,
    ) {
    }

    public function getSchoolId(): ?int
    {
        $school = $this->school;
        if (! $school instanceof School) {
            $evaluationSchool = $this->evaluation->school;
            if (! $evaluationSchool instanceof School) {
                return null;
            }
            $school = $evaluationSchool;
        }

        $id = $school->getKey();

        return is_int($id) ? $id : null;
    }
}
