<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
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
        
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantManager = app(TenantManager::class);

        if (!$tenantManager->isModuleEnabled($module)) {
            abort(403, "The '{$module}' module is not enabled for your organization.");
        }

        return $next($request);
    }
}