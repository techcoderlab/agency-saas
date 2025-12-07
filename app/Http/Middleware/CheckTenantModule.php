<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModule
{
    /**
     * Gate 1: Tenant Module Check
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            // Super admins might not have a tenant, allow them or handle accordingly
            if ($user && $user->isSuperAdmin()) {
                 return $next($request);
            }
            abort(403, 'Tenant context missing.');
        }

        // Check if the module is in the enabled_modules array
        if (!in_array($module, $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
            abort(403, "The '{$module}' module is not enabled for your organization.");
        }

        return $next($request);
    }
}