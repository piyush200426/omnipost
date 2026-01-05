<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SocialAccount;
use App\Models\SocialHourlyStat;

class DashboardController extends Controller
{
    /* =========================
        DASHBOARD PAGE
    ========================== */
    public function index()
    {
        $userId = (string) auth()->user()->_id;

        $facebookAccount = SocialAccount::forUser($userId)
            ->where('platform', 'facebook')
            ->where('status', 'connected')
            ->first();

        return view('dashboard.index', [
            'facebookPages' => $facebookAccount->pages ?? []
        ]);
    }

    /* =========================
        LIVE DASHBOARD API
    ========================== */
    public function live(Request $request)
    {
        $userId = (string) auth()->user()->_id;

        /* =====================
           PLATFORM FILTER
        ===================== */
        $platform = $request->get('platform', 'all');
        if (!in_array($platform, ['all', 'facebook', 'instagram', 'youtube'])) {
            $platform = 'all';
        }

        /* =====================
           PAGE FILTER
        ===================== */
        $pageId = $request->get('page', 'all');

        /* =====================
           DATE RANGE
        ===================== */
        if ($request->get('filter') === 'today') {
            $since = Carbon::today();
            $until = Carbon::today();
        } else {
            $range = (int) $request->get('range', 7);
            if (!in_array($range, [7, 30, 90])) {
                $range = 7;
            }

            $since = Carbon::now()->subDays($range)->startOfDay();
            $until = Carbon::now()->endOfDay();
        }

        /* =====================
           PLATFORM DATA
        ===================== */
        $facebook  = $this->platformFromDb('facebook', $userId, $since, $until, $pageId);
        $instagram = $this->platformFromDb('instagram', $userId, $since, $until, $pageId);
        $youtube   = $this->platformFromDb('youtube', $userId, $since, $until, $pageId);

        /* =====================
           FINAL TOTALS
        ===================== */
        return response()->json([
            'totalReach' =>
                $facebook['reach'] +
                $instagram['reach'] +
                $youtube['reach'],

            'totalEngagement' =>
                $facebook['engagement'] +
                $instagram['engagement'] +
                $youtube['engagement'],

            'linkClicks' => 0,

            'followerGrowth' =>
                $facebook['followers'] +
                $instagram['followers'] +
                $youtube['followers'],

            /* ================= GRAPH ================= */
            'labels' => $facebook['labels'], // date labels same
            'engagementData' => $facebook['engagementGraph'],

            /* ================= PLATFORM COMPARISON ================= */
            'platformReach' => [
                $facebook['reach'],
                $instagram['reach'],
                $youtube['reach'],
            ],

            'platformEngagement' => [
                $facebook['engagement'],
                $instagram['engagement'],
                $youtube['engagement'],
            ],

            /* ================= DROPDOWNS ================= */
            'pages' => $facebook['pages'],
        ]);
    }

    /* ======================================================
        GENERIC PLATFORM STATS (SECURE)
    ====================================================== */
    private function platformFromDb(
        string $platform,
        string $userId,
        $since,
        $until,
        string $pageFilter = 'all'
    ) {
        /* =====================
           ACCOUNT CHECK
        ===================== */
        $account = SocialAccount::forUser($userId)
            ->where('platform', $platform)
            ->where('status', 'connected')
            ->first();

        if (!$account) {
            return $this->blank();
        }

        /* =====================
           STATS QUERY (SECURE)
        ===================== */
        $query = SocialHourlyStat::where('platform', $platform)
            ->where('user_id', $userId)
            ->whereBetween('stat_date', [
                $since->toDateString(),
                $until->toDateString()
            ]);

        if ($pageFilter !== 'all') {
            $query->where('page_id', $pageFilter);
        }

        $stats = $query->get();

        if ($stats->isEmpty()) {
            return $this->blank($account->pages);
        }

        /* =====================
           TOTALS
        ===================== */
        $reach      = $stats->sum('reach');
        $engagement = $stats->sum('engagement');

        $followers = optional(
            $stats->sortByDesc('stat_date')
                  ->sortByDesc('stat_hour')
                  ->first()
        )->followers ?? 0;

        /* =====================
           GRAPH (DAY WISE)
        ===================== */
        $grouped = $stats->groupBy('stat_date');

        $labels = [];
        $graph  = [];

        foreach ($grouped as $date => $rows) {
            $labels[] = Carbon::parse($date)->format('d M');
            $graph[]  = $rows->sum('engagement');
        }

        return [
            'reach'           => $reach,
            'engagement'      => $engagement,
            'followers'       => $followers,
            'labels'          => $labels,
            'engagementGraph' => $graph,
            'pages'           => $account->pages ?? [],
        ];
    }

    /* =========================
        EMPTY STRUCTURE
    ========================== */
    private function blank($pages = [])
    {
        return [
            'reach' => 0,
            'engagement' => 0,
            'followers' => 0,
            'labels' => [],
            'engagementGraph' => [],
            'pages' => $pages,
        ];
    }
}
