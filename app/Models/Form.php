<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;
    use HasUuids;
    use BelongsToTenant;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'schema',
        'webhook_url', 
        'webhook_secret',
        'is_active'
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
    ];
}


