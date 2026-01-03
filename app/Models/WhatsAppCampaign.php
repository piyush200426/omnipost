<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class WhatsAppCampaign extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'whatsapp_campaigns';

    protected $fillable = [
        'user_id',
        'name',
        'status',
    ];
}
