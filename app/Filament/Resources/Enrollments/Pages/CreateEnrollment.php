<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Domain\Enrollment\EnrollStudent;
use App\Domain\Enrollment\Exceptions\EnrollmentAlreadyExists;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceeded;
use App\Domain\Enrollment\Exceptions\EnrollmentScheduleConflict;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $enrolledAt = isset($data['enrolled_at']) && is_string($data['enrolled_at'])
                ? Carbon::parse($data['enrolled_at'])
                : null;

            return app(EnrollStudent::class)->enroll(
                studentId: (int) $data['student_id'],
                courseOfferingId: (int) $data['course_offering_id'],
                status: isset($data['status']) && is_string($data['status']) ? $data['status'] : 'active',
                enrolledAt: $enrolledAt,
            );
        } catch (EnrollmentAlreadyExists $e) {
            throw ValidationException::withMessages([
                'course_offering_id' => $e->getMessage(),
            ]);
        } catch (EnrollmentCapacityExceeded $e) {
            throw ValidationException::withMessages([
                'course_offering_id' => $e->getMessage(),
            ]);
        } catch (EnrollmentScheduleConflict $e) {
            throw ValidationException::withMessages([
                'course_offering_id' => $e->getMessage(),
            ]);
        }
    }
}
