<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Exceptions;

final class EnrollmentAlreadyExists extends EnrollmentException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: __('Student is already enrolled in this course offering.');
        parent::__construct($message, $code, $previous);
    }
}
