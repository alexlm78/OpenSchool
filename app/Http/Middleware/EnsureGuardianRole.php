<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureGuardianRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof \App\Models\User) {
            abort(403);
        }

        $schoolId = filter_var($user->school_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (is_int($schoolId) && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        if (! $user->hasRole('guardian')) {
            abort(403);
        }

        return $next($request);
    }
}

