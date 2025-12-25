<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Lead;
use App\Observers\LeadObserver;
use Illuminate\Support\Facades\Gate;
use App\Services\TenantManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);

        // Define a rate limiter specifically for Tenant APIs
        RateLimiter::for('tenant_api', function (Request $request) {
            // Use X-Tenant-Id header, falling back to IP if missing (though Tenant ID should be mandatory)
            $tenantId = $request->header('X-Tenant-Id') ?: $request->user()?->tenant_id;
            $key = $tenantId ?? $request->ip();

            $responseString = [
                'message' => 'Too many requests. Please slow down your API calls.',
                'status' => 429
            ];
            // Limit: 60 requests per minute per tenant
            return [Limit::perSecond(10)->by($key)->response(function () use ($responseString) {
                return response()->json($responseString, 429);
            }), Limit::perMinute(60)->by($key)->response(function () use ($responseString) {
                return response()->json($responseString, 429);
            })];
        });

        // The "Landlord" Gate to prevent cross-tenant data access
        Gate::define('access-tenant', function ($user, $tenantId) {
            // Prevent access if the user is not associated with this tenant
            return DB::table('tenant_user')
                ->where('user_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->exists();
        });

        // Global check for any model that uses BelongsToTenant
        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false) return false;

            foreach ($arguments as $argument) {
                if (is_object($argument) && isset($argument->tenant_id)) {
                    if ($argument->tenant_id !== $user->current_tenant_id) {
                        return false;
                    }
                }
            }
        });

        // Gate::define('access-tenant-resource', function ($user, $model) {
        //     if ($user->isSuperAdmin()) {
        //         return true;
        //     }

        //     $tenantManager = app(TenantManager::class);
        //     if (!$tenantManager->getTenant()) {
        //         return false;
        //     }

        //     // Check if the model uses the BelongsToTenant trait
        //     if (in_array(\App\Traits\BelongsToTenant::class, class_uses_recursive($model))) {
        //         return $model->tenant_id === $tenantManager->getTenant()->id;
        //     }

        //     return false; // Or true, depending on default behavior for non-tenant models
        // });

        /* Gates to define permissioning for API Keys Module */
        $tenantManager = app(TenantManager::class);

        Gate::define('api_keys.view', function ($user) use ($tenantManager) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && $tenantManager->isModuleEnabled('api_keys') && $user->can('view api_keys');
        });

        Gate::define('api_keys.write', function ($user) use ($tenantManager) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff()  && $tenantManager->isModuleEnabled('api_keys') && $user->can('write api_keys');
        });

        Gate::define('api_keys.update', function ($user) use ($tenantManager) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && $tenantManager->isModuleEnabled('api_keys') && $user->can('update api_keys');
        });

        Gate::define('api_keys.delete', function ($user) use ($tenantManager) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && $tenantManager->isModuleEnabled('api_keys') && $user->can('delete api_keys');
        });
    }
}
