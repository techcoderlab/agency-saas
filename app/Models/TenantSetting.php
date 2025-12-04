<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_theme',
        'crm_config'
    ];

    protected $casts = [
        'client_theme' => 'array',
        'crm_config' => 'array'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
