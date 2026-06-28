<?php

namespace App\Tenancy;

final class TenantContext
{
    private ?int $schoolId = null;

    public function getSchoolId(): ?int
    {
        return $this->schoolId;
    }

    public function setSchoolId(?int $schoolId): void
    {
        $this->schoolId = $schoolId;
    }

    public function requireSchoolId(): int
    {
        if ($this->schoolId === null) {
            throw new \RuntimeException('Tenant school_id is required but was not set.');
        }

        return $this->schoolId;
    }
}
