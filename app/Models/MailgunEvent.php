<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailgunEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event',
        'provider_message_id',
        'recipient',
        'occurred_at',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
