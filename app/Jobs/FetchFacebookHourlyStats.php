<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SocialHourlyStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FetchFacebookHourlyStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $fbVersion = 'v24.0';

    public function handle()
    {
        $time = now()->subHour(); // FB delay safe
        $date = $time->toDateString();
        $hour = $time->hour;

        $accounts = SocialAccount::where('platform','facebook')
            ->where('status','connected')
            ->get();

        foreach ($accounts as $account) {
            foreach ($account->pages as $page) {

                $pageId = $page['page_id'];
                $token  = $page['page_access_token'];

                /* ===== FOLLOWERS ===== */
                $info = Http::get(
                    "https://graph.facebook.com/{$this->fbVersion}/{$pageId}",
                    [
                        'fields' => 'followers_count',
                        'access_token' => $token
                    ]
                )->json();

                /* ===== ENGAGEMENT ===== */
                $eng = Http::get(
                    "https://graph.facebook.com/{$this->fbVersion}/{$pageId}/insights",
                    [
                        'metric' => 'page_post_engagements',
                        'period' => 'day',
                        'since'  => $date,
                        'until'  => $date,
                        'access_token' => $token
                    ]
                )->json();

                $engagement = collect($eng['data'][0]['values'] ?? [])
                    ->sum('value');

                /* ===== REACH ===== */
                $reachRes = Http::get(
                    "https://graph.facebook.com/{$this->fbVersion}/{$pageId}/insights",
                    [
                        'metric' => 'page_impressions_unique',
                        'period' => 'day',
                        'since'  => $date,
                        'until'  => $date,
                        'access_token' => $token
                    ]
                )->json();

                $reach = collect($reachRes['data'][0]['values'] ?? [])
                    ->sum('value');

                /* ===== STORE IN MONGO ===== */
                SocialHourlyStat::updateOrCreate(
                    [
                        'platform'  => 'facebook',
                        'page_id'   => $pageId,
                        'stat_date' => $date,
                        'stat_hour' => $hour,
                    ],
                    [
                        'user_id'    => (string) $account->user_id,
                        'reach'      => (int) $reach,
                        'engagement' => (int) $engagement,
                        'followers'  => (int) ($info['followers_count'] ?? 0),
                    ]
                );
            }
        }
    }
}
