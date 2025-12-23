<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user')->withPivot('role', 'is_primary');
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_tenant');
    }
    
    public function settings()
    {
        return $this->hasOne(TenantSetting::class);
    }
}


