<?php

namespace App\Permission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class SchoolTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        return Auth::user()?->school_id;
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $this->teamId = $id->getKey();

            return;
        }

        $this->teamId = $id;
    }
}
