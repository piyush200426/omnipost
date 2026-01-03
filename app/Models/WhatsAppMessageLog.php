<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WhatsAppMessageLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'whatsapp_message_logs';

    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'contact_id',
        'phone_number',
        'template_name',
        'payload',
        'status',          // pending / sent / failed
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
