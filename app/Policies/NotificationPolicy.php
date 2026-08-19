<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAdministrativeRole()
            || $user->hasAnyRole(['teacher', 'student', 'guardian']);
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        if ($user->isSuperAdmin() || $user->hasAdministrativeRole() || $user->hasRole('teacher')) {
            return true;
        }

        $notifiableType = (string) $notification->getAttributeValue('notifiable_type');
        $notifiableId = (int) $notification->getAttributeValue('notifiable_id');

        if ($user->hasRole('student')) {
            return $notifiableType === User::class && $notifiableId === (int) $user->getKey();
        }

        if ($user->hasRole('guardian')) {
            return $notifiableType === User::class && \in_array(
                $notifiableId,
                LinkedGuardianStudents::resolveForUser($user),
                true,
            );
        }

        return false;
    }

    public function markAsRead(User $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function markAllAsRead(User $user): bool
    {
        return $this->viewAny($user);
    }
}
