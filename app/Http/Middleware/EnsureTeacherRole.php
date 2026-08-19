<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTeacherRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $schoolId = filter_var($user->school_id, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId) && class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        if (! $user->hasRole('teacher')) {
            abort(403);
        }

        return $next($request);
    }
}
