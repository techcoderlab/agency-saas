<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModule
{

    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Gate 1: Tenant Module Check
     */
    public function handle(Request $request, Closure $next, string $moduleSlug, string $modelClass = null): Response
    {
        // $user = $request->user();

        // if ($user && $user->isSuperAdmin()) {
        //     return $next($request);
        // }

        // $tenantManager = app(TenantManager::class);

        // if (!$tenantManager->isModuleEnabled($module)) {
        //     abort(403, "The '{$module}' module is not enabled for your organization.");
        // }


        // 1. Check if module is enabled in the plan
        $modules = $this->tenantManager->getEnabledModules();
        if (!in_array($moduleSlug, $modules)) {
            return response()->json(['message' => "The {$moduleSlug} module is not included in your current plan."], 403);
        }

        // 2. Check limits (only if a Model is provided to count against)
        if ($modelClass && class_exists($modelClass)) {
            // USAGE OF checkLimit
            if (!$this->tenantManager->checkLimit($moduleSlug, $modelClass)) {
                return response()->json(['message' => "You have reached the limit for {$moduleSlug}."], 402); // 402 Payment Required
            }
        }

        return $next($request);
    }
}
