<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\QrLink;
use App\Models\QrClickLog;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrLinkController extends Controller
{
    public function index()
    {
        try {
            $userId = (string) Auth::id();

            $qrs = QrLink::where('user_id', $userId)->latest()->get();

            return view('qrcode.index', [
                'qrs'          => $qrs,
                'totalQrs'     => $qrs->count(),
                'totalLinks'   => $qrs->sum('visit_count'),
                'totalVisits'  => $qrs->sum('visit_count'),
                'totalQrScans' => $qrs->sum('qr_scan_count'),
                'activeQrs'    => $qrs->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('QR index error', ['error' => $e->getMessage()]);
            abort(500);
        }
    }

    public function show($id)
    {
        try {
            $qr = QrLink::where('_id', $id)
                ->where('user_id', (string) Auth::id())
                ->firstOrFail();

            return view('qrcode.show', compact('qr'));
        } catch (\Throwable $e) {
            Log::error('QR show error', ['error' => $e->getMessage()]);
            abort(404);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'original_url'  => 'nullable|url',
                'short_link_id' => 'nullable|string',
                'label'         => 'nullable|string|max:255',
            ]);

            if (!$request->original_url && !$request->short_link_id) {
                return back()->withErrors([
                    'original_url' => 'URL ya Short Link required hai'
                ]);
            }

            do {
                $shortCode = Str::random(6);
            } while (QrLink::where('short_code', $shortCode)->exists());

            $qrUrlForScan = url('/q/' . $shortCode . '?qr=1');

            $renderer = new ImageRenderer(
                new RendererStyle(300),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);
            $qrImage = $writer->writeString($qrUrlForScan);

            $fileName = 'qr_' . $shortCode . '.svg';
            $filePath = 'qrcodes/' . $fileName;

            Storage::disk('public')->put($filePath, $qrImage);

            QrLink::create([
                'user_id'       => (string) Auth::id(),
                'label'         => $request->label,
                'original_url'  => $request->original_url,
                'short_code'    => $shortCode,
                'qr_image_path' => $filePath,
                'visit_count'   => 0,
                'qr_scan_count' => 0,
            ]);

            return redirect()
                ->route('qr-links.index')
                ->with('success', 'QR code generated successfully');
        } catch (\Throwable $e) {
            Log::error('QR store error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Something went wrong');
        }
    }

    public function redirect(Request $request, $code)
    {
        try {
            $qr = QrLink::where('short_code', $code)->firstOrFail();

            if ($request->has('qr')) {
                $qr->increment('qr_scan_count');
                $type = 'qr';
            } else {
                $qr->increment('visit_count');
                $type = 'link';
            }

            $ip = $request->ip();
            $location = $this->getLocationFromIp($ip);

            $ua = $request->header('User-Agent') ?? '';
            $info = $this->detectDevice($ua);

            QrClickLog::create([
                'qr_id'       => (string) $qr->_id,
                'short_code'  => $qr->short_code,
                'type'        => $type,
                'ip_address'  => $ip,
                'city'        => $location['city'],
                'country'     => $location['country'],
                'device_type' => $info['device'],
                'browser'     => $info['browser'],
            ]);

            return redirect()->away($qr->original_url);
        } catch (\Throwable $e) {
            Log::error('QR redirect error', [
                'code'  => $code,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'original_url' => 'required|url',
                'label'        => 'nullable|string|max:255',
                'foreground_type'  => 'nullable|in:single,gradient',
                'foreground_color' => 'nullable|string',
                'background_color' => 'nullable|string',
                'gradient_start'   => 'nullable|string',
                'gradient_end'     => 'nullable|string',
                'gradient_dir'     => 'nullable|in:horizontal,vertical,diagonal,radial',
                'qr_rotation'      => 'nullable|integer|in:0,90,180,270',
            ]);

            $qr = QrLink::where('_id', $id)
                ->where('user_id', (string) auth()->id())
                ->firstOrFail();

            $updateData = [
                'original_url' => $request->original_url,
                'label'        => $request->label,
            ];

            if ($request->has('foreground_type')) {
                $updateData = array_merge($updateData, $request->only([
                    'foreground_type',
                    'foreground_color',
                    'background_color',
                    'gradient_start',
                    'gradient_end',
                    'gradient_dir',
                ]));

                $updateData['qr_rotation'] = $request->qr_rotation ?? 0;

                $renderer = new ImageRenderer(
                    new RendererStyle(300),
                    new SvgImageBackEnd()
                );

                $writer = new Writer($renderer);
                $svg = $writer->writeString(url('/q/' . $qr->short_code . '?qr=1'));

               $svg = $this->applySvgDesign($svg, $request);

if (str_contains($svg, '<svg')) {
    Storage::disk('public')->put($qr->qr_image_path, $svg);
}

            }

            $qr->update($updateData);

            return back()->with('success', 'QR updated successfully');
        } catch (\Throwable $e) {
            Log::error('QR update error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Update failed');
        }
    }

    public function destroy($id)
    {
        try {
            $qr = QrLink::where('_id', $id)
                ->where('user_id', (string) Auth::id())
                ->firstOrFail();

            if (!empty($qr->qr_image_path)) {
                $filePath = str_replace('storage/', '', $qr->qr_image_path);
                Storage::disk('public')->delete($filePath);
            }

            $qr->delete();

            return redirect()->route('qr-links.index')->with('success', 'QR code deleted successfully');
        } catch (\Throwable $e) {
            Log::error('QR delete error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Delete failed');
        }
    }

    private function detectDevice(string $agent): array
    {
        $agent = strtolower($agent);

        $device = preg_match('/mobile|android|iphone|ipad|ipod/', $agent) ? 'Mobile' : 'Desktop';

        if (str_contains($agent, 'chrome')) $browser = 'Chrome';
        elseif (str_contains($agent, 'firefox')) $browser = 'Firefox';
        elseif (str_contains($agent, 'safari')) $browser = 'Safari';
        elseif (str_contains($agent, 'edge')) $browser = 'Edge';
        else $browser = 'Other';

        return compact('device', 'browser');
    }

    private function getLocationFromIp(string $ip): array
    {
        try {
            $response = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city");
            $data = json_decode($response, true);

            if (($data['status'] ?? '') !== 'success') {
                return ['city' => 'Unknown', 'country' => 'Unknown'];
            }

            return [
                'city'    => $data['city'] ?? 'Unknown',
                'country' => $data['country'] ?? 'Unknown',
            ];
        } catch (\Throwable $e) {
            Log::warning('IP location failed', ['ip' => $ip]);
            return ['city' => 'Unknown', 'country' => 'Unknown'];
        }
    }
private function applySvgDesign(string $svg, Request $request): string
{
    $fg = $request->foreground_color ?? '#000000';
    $bg = $request->background_color ?? '#ffffff';

    // remove xml header
    $svg = preg_replace('/<\?xml.*?\?>/i', '', $svg);

    // add ONE background rect (safe)
    $svg = preg_replace(
        '/<svg([^>]*)>/i',
        '<svg$1><rect width="100%" height="100%" fill="'.$bg.'" class="qr-bg"/>',
        $svg,
        1
    );

    // 🔥 IMPORTANT: color ONLY QR paths, NOT rect
    $svg = preg_replace(
        '/<path([^>]*)fill="[^"]*"([^>]*)>/i',
        '<path$1fill="'.$fg.'"$2>',
        $svg
    );

    return trim($svg);
}

    public function downloadSvg($id)
    {
        $qr = QrLink::where('_id', $id)->firstOrFail();

        $path = storage_path('app/public/' . $qr->qr_image_path);

        if (!file_exists($path)) {
            abort(404);
        }

        return response(
            file_get_contents($path),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }
}
