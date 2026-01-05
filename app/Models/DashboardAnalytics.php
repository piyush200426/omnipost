<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DashboardAnalytics extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'dashboard_analytics';

    protected $fillable = [
        'user_id',
        'stats',
        'weekly',
        'performance',
        'updated_at'
    ];

    protected $casts = [
        'stats' => 'array',
        'weekly' => 'array',
        'performance' => 'array'
    ];
}
