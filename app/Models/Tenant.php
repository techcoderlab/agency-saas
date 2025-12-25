<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'status',
        'slug',
        'is_active'
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, 'tenant_user')->withPivot('role', 'is_primary');
    // }

    // public function plans()
    // {
    //     return $this->belongsToMany(Plan::class, 'plan_tenant');
    // }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_tenant')
            ->withPivot('expires_at', 'grace_period_days')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role', 'is_primary');
    }

    public function settings()
    {
        return $this->hasOne(TenantSetting::class);
    }
}
