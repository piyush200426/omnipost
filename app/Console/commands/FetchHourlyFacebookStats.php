<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
class FetchHourlyFacebookStats extends Command
{
    protected $signature = 'facebook:hourly-stats';
    protected $description = 'Fetch Facebook hourly analytics';

    public function handle()
    {
        dispatch(new \App\Jobs\FetchFacebookHourlyStats());
        $this->info('Facebook hourly stats job dispatched');
    }
}

