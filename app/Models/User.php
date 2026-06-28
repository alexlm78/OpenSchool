<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'school_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends TenantAuthenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        $schoolId = filter_var($this->school_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (is_int($schoolId) && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        return match ($panel->getId()) {
            'admin' => $this->hasRole('admin'),
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
