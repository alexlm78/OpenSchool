<?php

namespace App\Domain\Enrollment\Exceptions;

final class EnrollmentScheduleConflict extends EnrollmentException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: __('Schedule conflict detected for this enrollment.');
        parent::__construct($message, $code, $previous);
    }
}
