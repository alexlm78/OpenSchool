<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Grade;
use App\Models\School;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class GradePublished
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Grade $grade,
        public readonly ?School $school = null,
        public readonly bool $isUpdate = false,
    ) {}

    public function getSchoolId(): ?int
    {
        $school = $this->school;
        if (! $school instanceof School) {
            $gradeSchool = $this->grade->school;
            if (! $gradeSchool instanceof School) {
                return null;
            }
            $school = $gradeSchool;
        }

        $id = $school->getKey();

        return \is_int($id) ? $id : null;
    }

    public function getStudentUserId(): ?int
    {
        $id = $this->grade->student_id;
        if (\is_int($id)) {
            return $id;
        }
        $id = filter_var($id, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return \is_int($id) ? $id : null;
    }
}
