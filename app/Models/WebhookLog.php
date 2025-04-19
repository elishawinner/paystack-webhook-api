<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'event_type',
        'reference',
        'payload',
        'is_verified',
        'headers',
        'ip_address',
        'metadata'
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'metadata' => 'array'
    ];
}