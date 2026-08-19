<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AssignmentDetails;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Evaluation
 */
final class EvaluationDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = (new EvaluationResource($this))->toArray($request);
        $studentId = Auth::id();

        $requirements = null;
        if ($this->relationLoaded('evaluationable') || $this->getAttributeValue('evaluationable_type') !== null) {
            $evaluationable = $this->evaluationable;

            if ($evaluationable instanceof AssignmentDetails) {
                $requirements = new AssignmentDetailsResource($evaluationable);
            }
        }

        $studentSubmission = null;
        $studentGrade = null;

        if ($studentId !== null) {
            $submission = $this->submissions()
                ->where('student_id', (int) $studentId)
                ->with(['submissionFiles'])
                ->first();

            if ($submission instanceof Submission) {
                $studentSubmission = new SubmissionDetailResource($submission);
            }

            $grade = $this->grades()
                ->where('student_id', (int) $studentId)
                ->with(['grader'])
                ->first();

            if ($grade instanceof Grade) {
                $studentGrade = new GradeResource($grade);
            }
        }

        return array_merge(
            $base,
            [
                'requirements' => $requirements,
                'my_submission' => $studentSubmission,
                'my_grade' => $studentGrade,
            ],
        );
    }
}
