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

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function isModuleEnabled(string $moduleSlug): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $enabledModules = $this->getEnabledModules();

        return in_array($moduleSlug, $enabledModules);
    }

    public function getEnabledModules(): array
    {
        if (!$this->tenant) {
            return [];
        }

        // Cache the enabled modules for the tenant to reduce DB queries
        return Cache::rememberForever("tenant:{$this->tenant->id}:modules", function () {
            // A tenant can have multiple plans, so we get modules from all plans.
            return $this->tenant->plans()->with('modules')->get()
                ->pluck('modules')->flatten()->pluck('slug')->unique()->toArray();
        });
    }

    public function forgetTenantCache(): void
    {
        if ($this->tenant) {
            Cache::forget("tenant:{$this->tenant->id}:modules");
        }
    }
}
