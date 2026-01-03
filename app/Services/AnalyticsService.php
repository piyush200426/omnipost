<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;
use App\Services\YouTubeTokenService;

class AnalyticsService
{
    protected string $graphVersion = 'v18.0';

    public function fetch(string $userId): array
    {
        $stats = [
            'reach' => 0,
            'engagement' => 0,
            'followers' => 0,
        ];

        $weekly = [
            'facebook' => array_fill(0, 7, 0),
            'instagram'=> array_fill(0, 7, 0),
            'youtube'  => array_fill(0, 7, 0),
            'all'      => array_fill(0, 7, 0),
        ];

        $performance = [];

        $accounts = SocialAccount::where('user_id', $userId)
            ->where('status', 'connected')
            ->get()
            ->keyBy('platform');

        /* ================= FACEBOOK ================= */
        if (isset($accounts['facebook'])) {
            try {
                $fb = $accounts['facebook'];

                $res = Http::get(
                    "https://graph.facebook.com/{$this->graphVersion}/{$fb->credentials['page_id']}/insights",
                    [
                        'metric' => 'page_impressions,page_engaged_users,page_fans',
                        'period' => 'day',
                        'access_token' => $fb->credentials['page_token']
                    ]
                )->json();

                $reach = $res['data'][0]['values'][0]['value'] ?? 0;
                $eng   = $res['data'][1]['values'][0]['value'] ?? 0;
                $fans  = $res['data'][2]['values'][0]['value'] ?? 0;

                $stats['reach'] += $reach;
                $stats['engagement'] += $eng;
                $stats['followers'] += $fans;

                $performance[] = [
                    'platform' => 'Facebook',
                    'reach' => $reach,
                    'engagement' => $eng
                ];

                if (!empty($res['data'][1]['values'])) {
                    $weekly['facebook'] = array_slice(
                        array_reverse(array_column($res['data'][1]['values'], 'value')),
                        0, 7
                    );
                }
            } catch (\Throwable $e) {
                Log::error('FB analytics error', ['e'=>$e->getMessage()]);
            }
        }

        /* ================= INSTAGRAM ================= */
        if (isset($accounts['instagram'])) {
            try {
                $ig = $accounts['instagram'];

                $res = Http::get(
                    "https://graph.facebook.com/{$this->graphVersion}/{$ig->credentials['business_id']}/insights",
                    [
                        'metric' => 'impressions,reach,follower_count',
                        'period' => 'day',
                        'access_token' => $ig->credentials['access_token']
                    ]
                )->json();

                $reach = $res['data'][1]['values'][0]['value'] ?? 0;
                $eng   = $res['data'][0]['values'][0]['value'] ?? 0;
                $fol   = $res['data'][2]['values'][0]['value'] ?? 0;

                $stats['reach'] += $reach;
                $stats['engagement'] += $eng;
                $stats['followers'] += $fol;

                $performance[] = [
                    'platform' => 'Instagram',
                    'reach' => $reach,
                    'engagement' => $eng
                ];

                if (!empty($res['data'][0]['values'])) {
                    $weekly['instagram'] = array_slice(
                        array_reverse(array_column($res['data'][0]['values'], 'value')),
                        0, 7
                    );
                }
            } catch (\Throwable $e) {
                Log::error('IG analytics error', ['e'=>$e->getMessage()]);
            }
        }

        /* ================= YOUTUBE ================= */
        if (isset($accounts['youtube'])) {
            try {
                $token = YouTubeTokenService::getAccessToken($userId);

                if ($token) {
                    $ch = Http::withToken($token)->get(
                        'https://www.googleapis.com/youtube/v3/channels',
                        [
                            'part' => 'statistics',
                            'mine' => 'true'
                        ]
                    )->json();

                    $s = $ch['items'][0]['statistics'] ?? [];

                    $stats['reach'] += (int) ($s['viewCount'] ?? 0);
                    $stats['engagement'] += (int) ($s['commentCount'] ?? 0);
                    $stats['followers'] += (int) ($s['subscriberCount'] ?? 0);

                    $performance[] = [
                        'platform' => 'YouTube',
                        'reach' => (int) ($s['viewCount'] ?? 0),
                        'engagement' => (int) ($s['commentCount'] ?? 0)
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('YT analytics error', ['e'=>$e->getMessage()]);
            }
        }

        /* ================= MERGE ALL ================= */
        for ($i = 0; $i < 7; $i++) {
            $weekly['all'][$i] =
                ($weekly['facebook'][$i] ?? 0) +
                ($weekly['instagram'][$i] ?? 0) +
                ($weekly['youtube'][$i] ?? 0);
        }

        return compact('stats', 'weekly', 'performance');
    }
}
