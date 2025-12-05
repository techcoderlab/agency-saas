<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // Using your existing Trait

class AiChat extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'webhook_url',
        'webhook_secret',
        'avatar_url',
        'welcome_message'
    ];
}