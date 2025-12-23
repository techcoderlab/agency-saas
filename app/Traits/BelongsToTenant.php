<?php

namespace App\Traits;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            $tenantManager = app(TenantManager::class);
            $user = Auth::user();

            if ($user && $user->isNotSuperAdmin() && $tenantManager->getTenant()) {
                $model->tenant_id = $tenantManager->getTenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantManager = app(TenantManager::class);
            $user = Auth::user();

            if ($user && $user->isNotSuperAdmin() && $tenantManager->getTenant()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantManager->getTenant()->id);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}


