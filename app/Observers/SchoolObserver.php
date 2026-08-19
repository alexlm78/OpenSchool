<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\School;
use App\Support\DefaultRoleSeeder;
use Illuminate\Support\Facades\Log;

final class SchoolObserver
{
    public function __construct(
        private readonly DefaultRoleSeeder $roleSeeder,
    ) {}

    public function created(School $school): void
    {
        $schoolId = $school->getKey();
        if (! \is_int($schoolId) || $schoolId < 1) {
            return;
        }

        try {
            $this->roleSeeder->seedForSchool($school);
        } catch (\Throwable $e) {
            Log::error('Failed to seed default roles for newly created school.', [
                'school_id' => $schoolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
