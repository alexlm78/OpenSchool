<?php

declare(strict_types=1);

namespace App\Filament;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class ApoderadoResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('guardian');
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

    protected static function currentGuardianUserId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? (int) $user->getAuthIdentifier() : null;
    }

    /**
     * @return array<int, int> student user ids linked to the current guardian
     */
    protected static function linkedStudentUserIds(): array
    {
        $guardianUserId = self::currentGuardianUserId();
        if ($guardianUserId === null) {
            return [];
        }

        $profile = Guardian::query()
            ->with('students:id,user_id')
            ->where('user_id', $guardianUserId)
            ->first();

        if (! $profile instanceof Guardian) {
            return [];
        }

        return $profile->students
            ->map(static fn (Student $s): ?int => filter_var($s->user_id, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null)
            ->filter(static fn (?int $id): bool => $id !== null)
            ->values()
            ->all();
    }
}
