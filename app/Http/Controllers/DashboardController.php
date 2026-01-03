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
        $facebookAccount = SocialAccount::forUser(auth()->user()->_id)
            ->where('platform', 'facebook')
            ->where('status', 'connected')
            ->first();

        $facebookPages = $facebookAccount->pages ?? [];

        return view('dashboard.index', compact('facebookPages'));
    }

    /* =========================
        LIVE DASHBOARD (DB ONLY)
    ========================== */
    public function live(Request $request)
    {
        /* =====================
           PLATFORM
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
           FACEBOOK DATA (DB)
        ===================== */
        $facebook = $this->facebookFromDb($since, $until, $pageId);

        /* =====================
           FINAL TOTALS
        ===================== */
        $totalReach      = $facebook['reach'];
        $totalEngagement = $facebook['engagement'];
        $followers       = $facebook['followers'];

        return response()->json([
            'totalReach'      => $totalReach,
            'totalEngagement' => $totalEngagement,
            'linkClicks'      => 0,
            'followerGrowth'  => $followers,

            'labels'          => $facebook['labels'],
            'engagementData'  => $facebook['engagementGraph'],

            'platformReach' => [
                $facebook['reach'], 0, 0
            ],
            'platformEngagement' => [
                $facebook['engagement'], 0, 0
            ],

            // dropdown refresh
            'pages' => $facebook['pages'],
        ]);
    }

    /* ======================================================
        FACEBOOK STATS FROM MONGODB
    ====================================================== */
    private function facebookFromDb($since, $until, $pageFilter = 'all')
    {
        $query = SocialHourlyStat::where('platform', 'facebook')
            ->whereBetween('stat_date', [
                $since->toDateString(),
                $until->toDateString()
            ]);

        if ($pageFilter !== 'all') {
            $query->where('page_id', $pageFilter);
        }

        $stats = $query->get();

        if ($stats->isEmpty()) {
            return $this->blank();
        }

        /* =====================
           TOTALS
        ===================== */
        $reach      = $stats->sum('reach');
        $engagement = $stats->sum('engagement');

        // latest hour followers (correct logic)
        $followers = $stats
            ->sortByDesc('stat_date')
            ->sortByDesc('stat_hour')
            ->first()
            ->followers ?? 0;

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

        /* =====================
           PAGES (FROM ACCOUNT)
        ===================== */
        $account = SocialAccount::forUser(auth()->user()->_id)
            ->where('platform', 'facebook')
            ->where('status', 'connected')
            ->first();

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
    private function blank()
    {
        return [
            'reach' => 0,
            'engagement' => 0,
            'followers' => 0,
            'labels' => [],
            'engagementGraph' => [],
            'pages' => [],
        ];
    }
}
