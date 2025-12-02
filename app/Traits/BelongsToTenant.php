<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            $user = Auth::user();

            if ($user && $user->isNotSuperAdmin()) {
                $model->tenant_id = $user->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->isNotSuperAdmin()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
            }
        });
    }
}


