<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class MongoUserProvider extends EloquentUserProvider
{
    // Nothing special needed, EloquentUserProvider works with MongoModel automatically.
}
