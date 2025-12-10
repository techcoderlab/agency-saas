<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active'
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean'
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}