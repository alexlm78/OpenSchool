<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'school_id', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends TenantAuthenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    public function preferredLocale(): ?string
    {
        if ($this->locale && $this->isAvailableLocale($this->locale)) {
            return $this->locale;
        }

        return null;
    }

    public function setLocale(string $locale): bool
    {
        if (! $this->isAvailableLocale($locale)) {
            return false;
        }

        $this->locale = $locale;

        return $this->save();
    }

    protected function isAvailableLocale(string $locale): bool
    {
        return array_key_exists($locale, (array) config('app.available_locales', []));
    }

    public function isSuperAdmin(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        return DB::table($modelHasRolesTable)
            ->join($rolesTable, $rolesTable . '.id', '=', $modelHasRolesTable . '.' . $pivotRole)
            ->where($modelHasRolesTable . '.model_type', self::class)
            ->where($modelHasRolesTable . '.' . $modelMorphKey, $this->getKey())
            ->where($rolesTable . '.name', 'super_admin')
            ->where($rolesTable . '.guard_name', 'web')
            ->exists();
    }

    public function hasAdministrativeRole(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole('admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $schoolId = filter_var($this->school_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (is_int($schoolId) && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        return match ($panel->getId()) {
            'admin' => $this->hasAdministrativeRole(),
            'docente' => $this->hasRole('teacher'),
            'alumno' => $this->hasRole('student'),
            'apoderado' => $this->hasRole('guardian'),
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the school that the user belongs to.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
