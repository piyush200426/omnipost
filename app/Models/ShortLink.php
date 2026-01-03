<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShortLink extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'short_links';

    protected $fillable = [
        'user_id',
        'original_url',
        'short_code',
        'short_url',
        'click_count',
        'is_active',
    ];
}
