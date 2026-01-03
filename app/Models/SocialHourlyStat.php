<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SocialHourlyStat extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'social_hourly_stats';

    protected $fillable = [
        'user_id',
        'platform',      // facebook
        'page_id',

        'reach',
        'engagement',
        'followers',

        'stat_date',     // 2026-01-02
        'stat_hour',     // 0-23
    ];
}
