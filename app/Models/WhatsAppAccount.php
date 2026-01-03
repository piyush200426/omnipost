<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WhatsAppAccount extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'whatsapp_accounts';

    protected $fillable = [
        'user_id',
        'provider',              // meta / twilio / gupshup
        'phone_number_id',
        'business_account_id',
        'access_token',
        'token_expires_at',
        'status',                // active / expired
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];
}
