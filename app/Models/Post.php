<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Post extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'posts';

    protected $fillable = [
        'user_id',
        'content',
        'media_url',
        'platforms',     // array: ['facebook','instagram']
        'status',        // draft | processing | published | failed
        'facebook_page_id', // optional (for multi-page support)
        'scheduled_at',     // optional
    ];

    protected $dates = [
        'scheduled_at',
        'created_at',
        'updated_at',
    ];

    /* ===============================
       SCOPES
    =============================== */

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', (string) $userId);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /* ===============================
       HELPERS
    =============================== */

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
