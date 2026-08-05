<?php

namespace App\Filament;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class AdminResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->hasAdministrativeRole();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }
}
