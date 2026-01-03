<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FacebookInsightService
{
    public static function fetchPostInsights($postId, $token)
    {
        $res = Http::get("https://graph.facebook.com/v19.0/{$postId}/insights", [
            'metric' => 'post_impressions,post_engaged_users',
            'access_token' => $token
        ])->json();

        return [
            'reach' => $res['data'][0]['values'][0]['value'] ?? 0,
            'engagement' => $res['data'][1]['values'][0]['value'] ?? 0
        ];
    }
}
