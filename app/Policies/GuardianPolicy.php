<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

final class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        $this->ensureTeamContext($user);

        return $user->hasRole('guardian');
    }

    public function view(User $user, Guardian $guardian): bool
    {
        $this->ensureTeamContext($user);
        if (! $user->hasRole('guardian')) {
            return false;
        }

        return (int) $guardian->getAttributeValue('user_id') === (int) $user->getKey()
            && (int) $guardian->getAttributeValue('school_id') === (int) $user->getAttributeValue('school_id');
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $this->view($user, $guardian);
    }

    private function ensureTeamContext(User $user): void
    {
        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }
    }
}
