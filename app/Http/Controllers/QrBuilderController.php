<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\QrCode;
use MongoDB\BSON\ObjectId;
use App\Models\QrClickLog;
use Illuminate\Support\Facades\Http;



class QrBuilderController extends Controller
{
    public function index()
    {
        $qrCodes = QrCode::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('qr.list', compact('qrCodes'));
    }

    public function create()
    {
        return view('qr.index');
    }

public function edit($id)
{
    \Log::info('Edit called', ['id' => $id, 'user' => Auth::id()]);

    $qr = QrCode::where('_id', new ObjectId($id))
        ->where('user_id', (string) Auth::id())
        ->firstOrFail();

    return view('qr.index', [
        'qr' => $qr,
        'qrSettings' => is_string($qr->settings)
            ? json_decode($qr->settings, true)
            : $qr->settings
    ]);
}


public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'          => 'required|string|max:255',
        'mode'          => 'required|in:static,dynamic',
        'payload_type'  => 'required|string',
        'payload_value' => 'required',
        'design'        => 'nullable|array',
        'qr_png'        => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    // Process payload
    $payload = $request->payload_value;
    if (is_string($payload)) {
        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $payload = $decoded;
        }
    }

    /* ================= DYNAMIC QR SETUP (FIRST) ================= */
    $shortUrl     = null;
    $originalUrl  = null;
    $shortCode    = null;

    if ($request->mode === 'dynamic') {
        do {
            $shortCode = Str::random(8);
        } while (QrCode::where('short_url', $shortCode)->exists());

        $shortUrl = $shortCode;
        $originalUrl = is_array($payload) ? ($payload['value'] ?? '') : $payload;
    }

    /* ================= QR DATA ================= */
    $qrData = $this->formatQrData($request->payload_type, $payload);

    // 👉 ONLY THIS enables scan count (safe)
    if ($request->mode === 'dynamic') {
        $qrData = url("/qr/{$shortCode}/scan");
    }

    /* ================= SAVE PNG ================= */
    $pngPath  = null;
    $publicUrl = null;

    if ($request->qr_png) {
        $png = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $request->qr_png)
        );

        $pngPath = 'qr-codes/' . Auth::id() . '/' . time() . '.png';
        Storage::disk('public')->makeDirectory('qr-codes/' . Auth::id());
        Storage::disk('public')->put($pngPath, $png);
       

    }

    /* ================= CREATE QR ================= */
    $qr = QrCode::create([
        'user_id'      => (string) Auth::id(),
        'title'        => $request->name,
        'qr_type'      => $request->payload_type,
        'qr_mode'      => $request->mode,
        'qr_data'      => $qrData,              // 👈 scan URL for dynamic
        'short_url'    => $shortUrl,
        'original_url' => $originalUrl,
    'qr_image_url' => $pngPath,

        'scans'        => 0,
        'visits'       => 0,
        'qr_scan_count' => 0,
        'visit_count'  => 0,
        'settings'     => $request->design ?? [],
        'is_active'    => true,
        'expiry_date'  => null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'QR created successfully',
        'data'    => [
            'id'           => (string) $qr->_id,
            'short_url'    => $qr->short_url,
            'qr_image_url' => $qr->qr_image_url,
        ]
    ]);
}

  public function update(Request $request, $id)
{
    \Log::info('UPDATE ID CHECK', ['id' => $id]);

    $validator = Validator::make($request->all(), [
        'name'          => 'required|string|max:255',
        'mode'          => 'required|in:static,dynamic',
        'payload_type'  => 'required|string',
        'payload_value' => 'required',
        'design'        => 'nullable|array',
        'qr_png'        => 'nullable|string',
    ]);

    
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    // ✅ FIX: MongoDB _id + user_id
    $qr = QrCode::where('_id', new ObjectId($id))
        ->where('user_id', (string) Auth::id())
        ->firstOrFail();

    // Process payload
    $payloadValue = $request->payload_value;
    if (is_string($payloadValue)) {
        $decodedValue = json_decode($payloadValue, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $payloadValue = $decodedValue;
        }
    }

    // Format QR data
    $qrData = $this->formatQrData($request->payload_type, $payloadValue);
    // ✅ OVERRIDE ONLY FOR DYNAMIC
if ($request->mode === 'dynamic') {
    $qrData = url("/qr/{$qr->short_url}/scan");
}
    $design = $request->design ?? [];

    // Update QR image if provided
    if (!empty($request->qr_png)) {
        $imageData = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $request->qr_png)
        );

        Storage::disk('public')->makeDirectory('qr-codes/' . Auth::id());
        $qrImagePath = 'qr-codes/' . Auth::id() . '/' . time() . '.png';
        Storage::disk('public')->put($qrImagePath, $imageData);
if ($qr->qr_image_url) {
    if (Storage::disk('public')->exists($qr->qr_image_url)) {
        Storage::disk('public')->delete($qr->qr_image_url);
    }
}


$qr->qr_image_url = $qrImagePath;

    }

    // Update record
    $qr->update([
        'title'    => $request->name,
        'qr_mode' => $request->mode,
        'qr_type' => $request->payload_type,
        'qr_data' => $qrData,
        'settings'=> $design,
         'qr_image_url' => $qrImagePath ?? $qr->qr_image_url,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'QR code updated successfully',
        'data'    => [
            'id'           => (string) $qr->_id,
            'name'         => $qr->title,
            'short_url'    => $qr->short_url,
          'qr_image_url' => $qr->qr_image_url,


            'updated_at'   => $qr->updated_at,
        ]
    ]);
}

public function destroy($id)
{
    $qr = QrCode::where('_id', new ObjectId($id))
        ->where('user_id', (string) Auth::id())
        ->firstOrFail();

    // Delete image safely
    if ($qr->qr_image_url && Storage::disk('public')->exists($qr->qr_image_url)) {
        Storage::disk('public')->delete($qr->qr_image_url);
    }

    $qr->delete();

    return redirect()
        ->route('qr.builder')
        ->with('success', 'QR deleted successfully');
}

    // ... rest of your existing methods (preview, redirect, scan, formatQrData, getDomain) ...
    
    private function formatQrData($type, $data)
    {
        if (!is_array($data)) {
            $data = ['value' => $data];
        }

        switch ($type) {
            case 'text':
                return $data['value'] ?? '';

            case 'link':
                $url = $data['value'] ?? '';
                return preg_match('~^https?://~', $url) ? $url : 'https://' . $url;

            case 'email':
                return "mailto:{$data['to']}?subject=" .
                    urlencode($data['subject'] ?? '') .
                    "&body=" . urlencode($data['message'] ?? '');

            case 'phone':
                return 'tel:' . preg_replace('/\D/', '', $data['value'] ?? '');

            case 'sms':
                return "sms:{$data['phone']}?body=" .
                    urlencode($data['message'] ?? '');

            case 'whatsapp':
                return "https://wa.me/" .
                    preg_replace('/\D/', '', $data['phone'] ?? '') .
                    "?text=" . urlencode($data['message'] ?? '');

            case 'cryptocurrency':
                return ($data['type'] ?? 'bitcoin') . ':' . ($data['address'] ?? '');

            default:
                return $data['value'] ?? '';
        }
    }


public function scan($code)
{
    $qr = QrCode::where('short_url', $code)->firstOrFail();

    if (!$qr->is_active) {
        abort(403);
    }

    $ip = request()->ip();
    $cacheKey = "qr_scan_{$qr->_id}_{$ip}";

    if (!cache()->has($cacheKey)) {

        // 🔢 COUNTS
        $qr->increment('scans');
        $qr->increment('visits');
        $qr->increment('qr_scan_count');
        $qr->increment('visit_count');


        /* =========================
           DEVICE + BROWSER (NO PACKAGE)
        ========================== */
        $ua = request()->userAgent();

        $deviceType = str_contains(strtolower($ua), 'mobile')
            ? 'Mobile'
            : 'Desktop';

        if (str_contains($ua, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Other';
        }

        /* =========================
           CITY + COUNTRY (FREE API)
        ========================== */
        $city = 'Unknown';
        $country = 'Unknown';

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($response->ok()) {
                $data = $response->json();
                $city = $data['city'] ?? 'Unknown';
                $country = $data['country'] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            // fail silently
        }

        /* =========================
           ANALYTICS LOG
        ========================== */
        QrClickLog::create([
            'qr_id'       => (string) $qr->_id,
            'short_code'  => $code,
            'type'        => 'qr',
            'ip_address'  => $ip,
            'city'        => $city,
            'country'     => $country,
            'device_type' => $deviceType,
            'browser'     => $browser,
        ]);

        cache()->put($cacheKey, true, now()->addMinutes(5));
    }

    // 🔁 Redirect
    if ($qr->qr_mode === 'dynamic' && $qr->original_url) {
        return redirect()->away($qr->original_url);
    }

    return redirect($qr->qr_data);
}

public function getDomain()
{
    $domain = config('app.url'); // .env se APP_URL
    
    // Agar localhost hai to qrul.co use karo
    $hostname = request()->getHost();
    $isLocal = $hostname === 'localhost' || 
               $hostname === '127.0.0.1' ||
               str_contains($hostname, '.test') ||
               str_contains($hostname, '.local') ||
               app()->environment('local');
    
    if ($isLocal) {
        $domain = 'https://qrul.co';
    }
    
    return response()->json([
        'success' => true,
        'domain' => $domain,
        'qr_base_url' => $domain . '/qr/',
        'is_local' => $isLocal,
        'detected_host' => $hostname
    ]);
}


}