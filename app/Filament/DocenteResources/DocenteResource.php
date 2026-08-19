<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources;

use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class DocenteResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('teacher');
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }
}
