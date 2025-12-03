<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Form;

class Lead extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'form_id',
        'source',
        'temperature',
        'status',
        'payload',
        'meta_data',
        'notes'
    ];

    protected $casts = [
        'payload' => 'array',
        'meta_data' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
    
    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }
}


