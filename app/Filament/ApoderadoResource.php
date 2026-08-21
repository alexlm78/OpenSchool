<?php

declare(strict_types=1);

namespace App\Filament;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

abstract class ApoderadoResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        return $user->hasRole('guardian');
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
            ->where('user_id', $guardianUserId)
            ->first();

        if (! $profile instanceof Guardian) {
            return [];
        }

        return $profile->students()
            ->pluck('students.user_id')
            ->map(static fn (mixed $v): ?int => is_numeric($v) ? (int) $v : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int> student profile (students table) ids linked to the current guardian
     */
    protected static function linkedStudentProfileIds(): array
    {
        $guardianUserId = self::currentGuardianUserId();
        if ($guardianUserId === null) {
            return [];
        }

        $profile = Guardian::query()
            ->where('user_id', $guardianUserId)
            ->first();

        if (! $profile instanceof Guardian) {
            return [];
        }

        return $profile->students()
            ->pluck('students.id')
            ->map(static fn (mixed $v): ?int => is_numeric($v) ? (int) $v : null)
            ->filter()
            ->values()
            ->all();
    }
}
