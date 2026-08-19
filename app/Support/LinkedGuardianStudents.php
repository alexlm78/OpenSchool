<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class LinkedGuardianStudents
{
    /**
     * Resuelve los USER IDs de los estudiantes vinculados a un usuario guardián.
     *
     * @return array<int, int>
     */
    public static function resolveForUser(User $user): array
    {
        if (! $user->hasRole('guardian')) {
            return [];
        }

        $profile = $user->guardianProfile;
        if (! $profile instanceof Guardian) {
            return [];
        }

        /** @var Collection<int, Student> $linked */
        $linked = $profile->students()->get(['students.id']);

        return $linked
            ->map(static function (Student $student): ?int {
                $userId = $student->getAttributeValue('user_id');

                return $userId !== null ? (int) $userId : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
