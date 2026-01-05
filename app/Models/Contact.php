<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Contact extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'contacts';

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'opt_in',
        'source',
    ];

    protected $casts = [
        'opt_in' => 'boolean',
    ];
}
