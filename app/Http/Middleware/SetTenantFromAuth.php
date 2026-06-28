<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantFromAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);

        $schoolIdValue = Auth::user()?->school_id;
        $schoolId = filter_var($schoolIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $tenantContext->setSchoolId(is_int($schoolId) ? $schoolId : null);

        if ($tenantContext->getSchoolId() !== null && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantContext->getSchoolId());
        }

        return $next($request);
    }
}
