<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BioPage extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bio_pages';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'links',        // array of buttons/links
        'is_active',
    ];

    protected $casts = [
        'links'     => 'array',
        'is_active'=> 'boolean',
    ];

    public $timestamps = true;
}
