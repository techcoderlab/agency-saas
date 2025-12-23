<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Lead;
use App\Observers\LeadObserver;
use Illuminate\Support\Facades\Gate;
use App\Services\TenantManager;


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

        // The "Landlord" Gate to prevent cross-tenant data access
        Gate::define('access-tenant-resource', function ($user, $model) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            $tenantManager = app(TenantManager::class);
            if (!$tenantManager->getTenant()) {
                return false;
            }

            // Check if the model uses the BelongsToTenant trait
            if (in_array(\App\Traits\BelongsToTenant::class, class_uses_recursive($model))) {
                return $model->tenant_id === $tenantManager->getTenant()->id;
            }

            return false; // Or true, depending on default behavior for non-tenant models
        });

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
