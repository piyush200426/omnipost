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
        $days = (int) $request->get('range', 30);
        $from = Carbon::now()->subDays($days);

        // ✅ SOURCE OF TRUTH (QrLink)
        $qrs = QrLink::where('user_id', (string) auth()->id())->get();

        $totalQrCodes  = $qrs->count();
        $totalVisits   = $qrs->sum('visit_count');     // 👈 21 (correct)
        $totalQrScans  = $qrs->sum('qr_scan_count');   // 👈 4 (correct)
        $totalLinks    = $totalVisits;                 // 👈 same as visits
        $activeQrs     = $qrs->count();

        // 🔍 Logs ONLY for analytics
        $logs = QrClickLog::whereIn(
                    'qr_id',
                    $qrs->pluck('_id')->map(fn($id) => (string)$id)
                )
                ->where('created_at', '>=', $from)
                ->get();

        return view('statistics.index', [
            // CARDS
            'totalQrCodes' => $totalQrCodes,
            'totalLinks'   => $totalLinks,
            'totalVisits'  => $totalVisits,
            'totalQrScans' => $totalQrScans,
            'activeQrs'    => $activeQrs,

            // FILTER
            'days' => $days,

            // GRAPHS
            'clicksByDate' => $logs->groupBy(
                fn($l) => $l->created_at->format('d M')
            )->map->count(),

            'countries' => $logs->groupBy('country')->map->count(),
            'devices'   => $logs->groupBy('device_type')->map->count(),
            'browsers'  => $logs->groupBy('browser')->map->count(),
        ]);
    }
}
