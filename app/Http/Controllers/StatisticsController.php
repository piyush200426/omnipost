<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Models\QrClickLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        /* =========================
           DATE RANGE
        ========================== */
        $days = (int) $request->get('range', 30);
        $from = Carbon::now()->subDays($days);

        $userId = (string) auth()->id();

        /* =========================
           SOURCE OF TRUTH (QrLink)
        ========================== */
        $qrs = QrLink::where('user_id', $userId)->get();

        $totalQrCodes = $qrs->count();
        $totalVisits  = $qrs->sum('visit_count');     // ✅ ONLY from QrLink
        $totalQrScans = $qrs->sum('qr_scan_count');   // ✅ ONLY from QrLink
        $totalLinks   = $totalVisits;                 // same meaning
        $activeQrs    = $qrs->count();                // future: is_active

        /* =========================
           ANALYTICS LOGS
        ========================== */
        $qrIds = $qrs->pluck('_id')->map(fn ($id) => (string) $id);

        $logs = QrClickLog::whereIn('qr_id', $qrIds)
            ->where('created_at', '>=', $from)
            ->get();

        /* =========================
           GRAPHS
        ========================== */

        // 📊 Clicks per day
        $clicksByDate = $logs
            ->groupBy(fn ($log) => $log->created_at->format('Y-m-d'))
            ->map->count();

        // 🌍 Country
        $countries = $logs
            ->groupBy(fn ($l) => $l->country ?: 'Unknown')
            ->map->count()
            ->sortDesc();

        // 📱 Device
        $devices = $logs
            ->groupBy(fn ($l) => $l->device_type ?: 'Unknown')
            ->map->count();

        // 🌐 Browser
        $browsers = $logs
            ->groupBy(fn ($l) => $l->browser ?: 'Other')
            ->map->count();

        // 🔀 QR vs Link split
        $typeSplit = [
            'qr'   => $logs->where('type', 'qr')->count(),
            'link' => $logs->where('type', 'link')->count(),
        ];

        return view('statistics.index', [
            /* ===== CARDS ===== */
            'totalQrCodes' => $totalQrCodes,
            'totalLinks'   => $totalLinks,
            'totalVisits'  => $totalVisits,
            'totalQrScans' => $totalQrScans,
            'activeQrs'    => $activeQrs,

            /* ===== FILTER ===== */
            'days' => $days,

            /* ===== CHART DATA ===== */
            'clicksByDate' => $clicksByDate,
            'countries'    => $countries,
            'devices'      => $devices,
            'browsers'     => $browsers,
            'typeSplit'    => $typeSplit,
        ]);
    }
}
