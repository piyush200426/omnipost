<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /* ================= FILE SIZE LIMIT ================= */
        ini_set('upload_max_filesize', '200M');
        ini_set('post_max_size', '200M');

        /* ================= SVG MIME TYPE FIX ================= */
        Response::macro('svg', function ($content) {
            return response($content, 200)->header('Content-Type', 'image/svg+xml');
        });
    }
}
