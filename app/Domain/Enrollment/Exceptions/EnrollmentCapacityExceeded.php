<?php

namespace App\Domain\Enrollment\Exceptions;

final class EnrollmentCapacityExceeded extends EnrollmentException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: __('Course offering capacity reached.');
        parent::__construct($message, $code, $previous);
    }
}
