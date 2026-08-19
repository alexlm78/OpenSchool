<?php

namespace App\Filament;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class AlumnoResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->hasRole('student');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    protected static function currentStudentUserId(): ?int
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User ? (int) $user->getAuthIdentifier() : null;
    }
}
