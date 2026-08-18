<?php

namespace App\Domain\Enrollment\Exceptions;

final class EnrollmentCapacityPolicyViolation extends EnrollmentException
{
    public static function unlimitedNotAllowed(): self
    {
        return new self(__('This school does not allow unlimited capacity course offerings. Contact administration.'));
    }
}
