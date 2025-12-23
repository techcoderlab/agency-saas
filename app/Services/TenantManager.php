<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class TenantManager
{
    private ?Tenant $tenant = null;

    public function __construct()
    {
        if ($user = Auth::user()) {
            if ($user->currentTenant) {
                $this->setTenant($user->currentTenant);
            }
        }
    }

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Retrieve the active tenant, loading it if necessary.
     */
    public function getTenant()
    {
        if (!$this->tenant) {
            $user = Auth::user();

            // Validate user and their context
            if ($user && $user->current_tenant_id) {
                // Eager load plans and modules to optimize performance
                $this->tenant = Tenant::with('plans.modules')->find($user->current_tenant_id);
            }
        }
        return $this->tenant;
    }

    /**
     * Check if a specific module is enabled for the active tenant.
     */
    public function isModuleEnabled(string $moduleSlug): bool
    {
        // FIX: Call getTenant() to ensure it is loaded
        $tenant = $this->getTenant();

        if (!$tenant) {
            return false;
        }

        $enabledModules = $this->getEnabledModules();

        return in_array($moduleSlug, $enabledModules);
    }

    /**
     * Get a list of all enabled module slugs.
     */
    public function getEnabledModules()
    {
        // FIX: Call getTenant() here too for safety
        $tenant = $this->getTenant();

        if (!$tenant || !$tenant->plans->first()) {
            return [];
        }
        // Cache the enabled modules for the tenant to reduce DB queries
        // return Cache::remember("tenant:{$tenant->id}:modules", 500, function () use ($tenant) {
        // A tenant can have multiple plans, so we get modules from all plans.
        // return $this->tenant->plans()->with('modules')->get()
        //     ->pluck('modules')->flatten()->pluck('slug')->unique()->toArray();
        return $tenant->plans->first()->modules->pluck('slug')->toArray();
        // });
    }

    // public function getEnabledModules(): array
    // {
    //     if (!$this->tenant) {
    //         return [];
    //     }

    //     // Cache the enabled modules for the tenant to reduce DB queries
    //     return Cache::rememberForever("tenant:{$this->tenant->id}:modules", function () {
    //         // A tenant can have multiple plans, so we get modules from all plans.
    //         return $this->tenant->plans()->with('modules')->get()
    //             ->pluck('modules')->flatten()->pluck('slug')->unique()->toArray();
    //     });
    // }

    public function forgetTenantCache(): void
    {
        if ($this->tenant) {
            Cache::forget("tenant:{$this->tenant->id}:modules");
        }
    }
}
