<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Lead;
use App\Observers\LeadObserver;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);


        /* Gates to define permissioning for API Keys Module */
        Gate::define('api_keys.view', function ($user) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && in_array('api_keys', $user->tenant->enabled_modules ?? []) && $user->can('view api_keys');
        });
    
        Gate::define('api_keys.write', function ($user) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff()  && in_array('api_keys', $user->tenant->enabled_modules ?? [])&& $user->can('write api_keys');
        });
    
        Gate::define('api_keys.update', function ($user) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && in_array('api_keys', $user->tenant->enabled_modules ?? []) && $user->can('update api_keys');
        });
    
        Gate::define('api_keys.delete', function ($user) {
            return $user->isNotSuperAdmin() && !$user->isTenantStaff() && in_array('api_keys', $user->tenant->enabled_modules ?? []) && $user->can('delete api_keys');
        });
    }
}
