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
            'short_url'     => 'nullable|string', // ✅ Frontend se aaye code ke liye
            'qr_data'       => 'required|string', // ✅ QR data bhi aana chahiye
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

        /* ================= DYNAMIC QR SETUP ================= */
        $shortUrl     = null;
        $originalUrl  = null;
        $shortCode    = null;
        $qrData       = $request->qr_data; // ✅ Frontend se aaya hua QR data

        if ($request->mode === 'dynamic') {
            // ✅ IMPORTANT: Use the tracking code from frontend
            $shortCode = $request->short_url;
            
            // ✅ Agar frontend ne code nahi bheja, tabhi generate karo
            if (empty($shortCode)) {
                do {
                    $shortCode = Str::random(8);
                } while (QrCode::where('short_url', $shortCode)->exists());
            } else {
                // ✅ Agar frontend ne code bheja hai, check karo unique hai ya nahi
                // (Edit mode ke liye allow karo duplicate ho, lekin new ke liye nahi)
                if (QrCode::where('short_url', $shortCode)->exists()) {
                    // Agar edit mode nahi hai (new QR create kar rahe hain)
                    // toh naya code generate karo
                    do {
                        $shortCode = Str::random(8);
                    } while (QrCode::where('short_url', $shortCode)->exists());
                }
            }

            $shortUrl = $shortCode;
            
            // ✅ Original URL get karo
         // ✅ Original URL get karo
if (is_array($payload)) {
    if (isset($payload['value'])) {
        $originalUrl = $payload['value'];
    } elseif (isset($payload['to']) && $request->payload_type === 'email') {
        $originalUrl = "mailto:{$payload['to']}";
    } elseif (isset($payload['phone']) && $request->payload_type === 'sms') {
        $originalUrl = "smsto:{$payload['phone']}";
    } elseif (isset($payload['phone']) && $request->payload_type === 'whatsapp') {
        $originalUrl = "https://wa.me/{$payload['phone']}";
    } else {
        $originalUrl = '';
    }
} else {
    // ✅ Agar phone type hai toh 'tel:' prefix add karo
    if ($request->payload_type === 'phone') {
        $phone = $payload;
        $phone = preg_replace('/\D/', '', $phone);
        if ($phone && !str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        $originalUrl = 'tel:' . $phone;
    } else {
        $originalUrl = $payload;
    }
}
            
            // ✅ QR data set karo (frontend se aaya hua data use karo)
            // Yeh format hoga: http://192.168.31.149:8000/qr/4G5cXCTL
            $qrData = $request->qr_data;
        } else {
            // Static QR ke liye
            $qrData = $this->formatQrData($request->payload_type, $payload);
        }

        /* ================= SAVE PNG ================= */
        $pngPath = null;

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
            'qr_data'      => $qrData,              // ✅ Same as frontend QR
            'short_url'    => $shortUrl,            // ✅ Same tracking code
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
                'qr_data'      => $qr->qr_data, // ✅ Return same QR data
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
            'qr_data'       => 'required|string', // ✅ QR data bhi required
            'short_url'     => 'nullable|string', // ✅ Tracking code
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // ✅ MongoDB ID + user safety
        $qr = QrCode::where('_id', new ObjectId($id))
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        /* =========================
           PAYLOAD PROCESS
        ========================== */
        $payloadValue = $request->payload_value;
        if (is_string($payloadValue)) {
            $decoded = json_decode($payloadValue, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payloadValue = $decoded;
            }
        }

        /* =========================
           QR DATA & TRACKING CODE
        ========================== */
        $qrData = $request->qr_data; // ✅ Frontend se aaya hua data
        $shortCode = $qr->short_url; // ✅ Existing tracking code (change nahi karo)
        $originalUrl = $qr->original_url;

        if ($request->mode === 'dynamic') {
            // ✅ Tracking code change mat karo, wahi rahe
            $qrData = $request->qr_data; // Frontend se aaya hua
            
            // ✅ Original URL update karo
            if (is_array($payloadValue)) {
                if (isset($payloadValue['value'])) {
                    $originalUrl = $payloadValue['value'];
                } elseif (isset($payloadValue['to']) && $request->payload_type === 'email') {
                    $originalUrl = "mailto:{$payloadValue['to']}";
                } elseif (isset($payloadValue['phone']) && $request->payload_type === 'sms') {
                    $originalUrl = "smsto:{$payloadValue['phone']}";
                } elseif (isset($payloadValue['phone']) && $request->payload_type === 'whatsapp') {
                    $originalUrl = "https://wa.me/{$payloadValue['phone']}";
                }
            } else {
                $originalUrl = $payloadValue;
            }
        } else {
            // Static QR
            $qrData = $this->formatQrData($request->payload_type, $payloadValue);
        }

        $design = $request->design ?? [];

        /* =========================
           IMAGE UPDATE (OPTIONAL)
        ========================== */
        if (!empty($request->qr_png)) {
            $imageData = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $request->qr_png)
            );

            Storage::disk('public')->makeDirectory('qr-codes/' . Auth::id());

            $qrImagePath = 'qr-codes/' . Auth::id() . '/' . time() . '.png';
            Storage::disk('public')->put($qrImagePath, $imageData);

            // delete old image safely
            if ($qr->qr_image_url && Storage::disk('public')->exists($qr->qr_image_url)) {
                Storage::disk('public')->delete($qr->qr_image_url);
            }

            $qr->qr_image_url = $qrImagePath;
        }

        /* =========================
           FINAL UPDATE
        ========================== */
        $qr->update([
            'title'        => $request->name,
            'qr_mode'      => $request->mode,
            'qr_type'      => $request->payload_type,
            'qr_data'      => $qrData,           // ✅ Same as frontend
            'original_url' => $originalUrl,
            'settings'     => $design,
            'qr_image_url' => $qr->qr_image_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'QR code updated successfully',
            'data'    => [
                'id'           => (string) $qr->_id,
                'name'         => $qr->title,
                'short_url'    => $qr->short_url,
                'qr_image_url' => $qr->qr_image_url,
                'qr_data'      => $qr->qr_data, // ✅ Return same QR data
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

    // ✅ PEHLE COUNT INCREMENT KARO
    $qr->increment('scans');
    $qr->increment('qr_scan_count');

    // ✅ FIR CACHE CHECK KARO
    if (!cache()->has($cacheKey)) {
        /* =========================
           DEVICE + BROWSER
        ========================== */
        $ua = request()->userAgent();
        $deviceType = str_contains(strtolower($ua), 'mobile') ? 'Mobile' : 'Desktop';

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
           CITY + COUNTRY
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
            // silent
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

    // 🔥 **PERFECT REDIRECT LOGIC FOR ALL TYPES**
    return $this->redirectToTarget($qr);
}

// ✅ NEW METHOD: Handle all redirect types
private function redirectToTarget($qr)
{
    // Agar original_url empty hai
    if (empty($qr->original_url)) {
        return redirect('/')->with('error', 'QR target not configured');
    }

    // Check QR type aur uske hisaab se redirect karo
    switch($qr->qr_type) {
        case 'sms':
            return $this->redirectToSMS($qr);
        case 'phone':
            return $this->redirectToPhone($qr);
        case 'email':
            return $this->redirectToEmail($qr);
        case 'whatsapp':
            return $this->redirectToWhatsApp($qr);
        case 'application':
            return $this->redirectToApplication($qr);
        case 'file':
            return $this->redirectToFile($qr);
        case 'vcard':
            return $this->redirectToVCard($qr);
        case 'wifi':
            return $this->redirectToWifi($qr);
        case 'event':
            return $this->redirectToEvent($qr);
        case 'cryptocurrency':
            return $this->redirectToCrypto($qr);
        case 'link':
            // Ensure link has http://
            $url = $qr->original_url;
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }
            return redirect()->away($url);
        default:
            // Default: direct redirect
            return redirect()->away($qr->original_url);
    }
}

private function redirectToApplication($qr)
{
    $original = $qr->original_url;
    
    \Log::info('Application Redirect Debug', [
        'original_url' => $original,
        'is_json' => str_starts_with($original, '{'),
        'is_url' => filter_var($original, FILTER_VALIDATE_URL),
        'qr_id' => $qr->_id
    ]);
    
    // Check format - agar JSON string hai
    if (str_starts_with($original, '{')) {
        try {
            \Log::info('Trying JSON decode', ['original' => $original]);
            $data = json_decode($original, true);
            
            \Log::info('JSON Decoded', ['data' => $data]);
            
            // Priority: App Store > Google Play > Other
            if (!empty($data['appStore'])) {
                return redirect()->away($data['appStore']);
            } elseif (!empty($data['googlePlay'])) {
                return redirect()->away($data['googlePlay']);
            } elseif (!empty($data['other'])) {
                return redirect()->away($data['other']);
            } elseif (!empty($data['value'])) {
                return redirect()->away($data['value']);
            }
        } catch (\Exception $e) {
            \Log::error('JSON Decode Failed', ['error' => $e->getMessage()]);
        }
    }
    
    // Agar direct URL hai
    if (filter_var($original, FILTER_VALIDATE_URL)) {
        \Log::info('Direct URL redirect', ['url' => $original]);
        return redirect()->away($original);
    }
    
    // Agar app name hai, search karo
    \Log::info('Google search redirect', ['query' => $original]);
    return redirect()->away("https://play.google.com/store/search?q=" . urlencode($original));
}
// ✅ FILE REDIRECT (Uploaded files)
private function redirectToFile($qr)
{
    $original = $qr->original_url;
    
    // Check if it's a URL
    if (filter_var($original, FILTER_VALIDATE_URL)) {
        return redirect()->away($original);
    }
    
    // Check if it's a file path in storage
    if (Storage::disk('public')->exists($original)) {
        return Storage::disk('public')->response($original);
    }
    
    // Check if it's base64 encoded data
    if (str_starts_with($original, 'data:')) {
        // Display file inline
        return response(base64_decode(preg_replace('#^data:[\w/]+;base64,#i', '', $original)))
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'inline');
    }
    
    // Default error
    abort(404, 'File not found');
}

// ✅ VCARD REDIRECT
private function redirectToVCard($qr)
{
    $original = $qr->original_url;
    
    // Check if it's a vCard data string
    if (str_contains($original, 'BEGIN:VCARD')) {
        // Download as .vcf file
        return response($original)
            ->header('Content-Type', 'text/vcard')
            ->header('Content-Disposition', 'attachment; filename="contact.vcf"');
    }
    
    // Check if it's JSON
    if (str_starts_with($original, '{')) {
        try {
            $data = json_decode($original, true);
            $vcard = $this->generateVCard($data);
            
            return response($vcard)
                ->header('Content-Type', 'text/vcard')
                ->header('Content-Disposition', 'attachment; filename="contact.vcf"');
        } catch (\Exception $e) {
            // JSON decode fail
        }
    }
    
    // Direct redirect
    return redirect()->away($original);
}

// ✅ WIFI REDIRECT
private function redirectToWifi($qr)
{
    $original = $qr->original_url;
    
    // Check if it's WiFi config string
    if (str_starts_with($original, 'WIFI:')) {
        // Show WiFi configuration page
        return view('qr.wifi', [
            'qr' => $qr,
            'wifiConfig' => $original
        ]);
    }
    
    // Check if it's JSON
    if (str_starts_with($original, '{')) {
        try {
            $data = json_decode($original, true);
            
            // Use isset() instead of ?? operator
            $encryption = isset($data['encryption']) ? $data['encryption'] : 'WPA';
            $ssid = isset($data['ssid']) ? $data['ssid'] : '';
            $password = isset($data['password']) ? $data['password'] : '';
            
            $wifiString = "WIFI:T:{$encryption};S:{$ssid};P:{$password};;";
            
            return view('qr.wifi', [
                'qr' => $qr,
                'wifiConfig' => $wifiString
            ]);
        } catch (\Exception $e) {
            // JSON decode fail
        }
    }
    
    // Default
    return redirect()->away($original);
}

// ✅ EVENT REDIRECT (Calendar event)
private function redirectToEvent($qr)
{
    $original = $qr->original_url;
    
    // Check if it's iCalendar format
    if (str_contains($original, 'BEGIN:VEVENT')) {
        return response($original)
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'attachment; filename="event.ics"');
    }
    
    // Check if it's JSON
    if (str_starts_with($original, '{')) {
        try {
            $data = json_decode($original, true);
            $ical = $this->generateICal($data);
            
            return response($ical)
                ->header('Content-Type', 'text/calendar')
                ->header('Content-Disposition', 'attachment; filename="event.ics"');
        } catch (\Exception $e) {
            // JSON decode fail
        }
    }
    
    // Direct redirect
    return redirect()->away($original);
}

// ✅ CRYPTOCURRENCY REDIRECT
private function redirectToCrypto($qr)
{
    $original = $qr->original_url;
    
    // Check format: bitcoin:address
    if (str_contains($original, ':')) {
        list($type, $address) = explode(':', $original, 2);
        
        // Show cryptocurrency payment page
        return view('qr.crypto', [
            'qr' => $qr,
            'cryptoType' => $type,
            'address' => $address
        ]);
    }
    
    // Direct redirect
    return redirect()->away($original);
}
// ✅ GENERATE VCARD FROM DATA
private function generateVCard($data)
{
    return "BEGIN:VCARD
VERSION:3.0
FN:" . ($data['firstName'] ?? '') . " " . ($data['lastName'] ?? '') . "
ORG:" . ($data['organization'] ?? '') . "
TEL;TYPE=WORK:" . ($data['phone'] ?? '') . "
TEL;TYPE=CELL:" . ($data['cell'] ?? '') . "
TEL;TYPE=FAX:" . ($data['fax'] ?? '') . "
EMAIL:" . ($data['email'] ?? '') . "
URL:" . ($data['website'] ?? '') . "
END:VCARD";
}

// ✅ GENERATE ICAL FROM DATA
private function generateICal($data)
{
    $start = date('Ymd\THis\Z', strtotime($data['start'] ?? now()));
    $end = date('Ymd\THis\Z', strtotime($data['end'] ?? now()->addHour()));
    
    return "BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:" . ($data['title'] ?? 'Event') . "
DESCRIPTION:" . ($data['description'] ?? '') . "
LOCATION:" . ($data['location'] ?? '') . "
URL:" . ($data['url'] ?? '') . "
DTSTART:" . $start . "
DTEND:" . $end . "
END:VEVENT
END:VCALENDAR";
}

// ✅ SMS REDIRECT
private function redirectToSMS($qr)
{
    $original = $qr->original_url;
    
    // Format check karo: +918511936683:Hello message
    if (strpos($original, ':') !== false) {
        list($phone, $message) = explode(':', $original, 2);
        
        // Phone number cleanup
        $phone = trim($phone);
        $message = trim($message);
        
        // SMS URL banayein
        $smsUrl = "sms:{$phone}?body=" . urlencode($message);
    } else {
        // Sirf phone number hai
        $smsUrl = "sms:" . trim($original);
    }
    
    return redirect()->away($smsUrl);
}

// ✅ PHONE REDIRECT
private function redirectToPhone($qr)
{
    $phone = $qr->original_url;
    $phone = preg_replace('/\D/', '', $phone);
    
    if ($phone && !str_starts_with($phone, '+')) {
        $phone = '+' . $phone;
    }
    
    return redirect()->away("tel:{$phone}");
}

// ✅ EMAIL REDIRECT
private function redirectToEmail($qr)
{
    $original = $qr->original_url;
    
    // Check if it's already a mailto link
    if (str_starts_with($original, 'mailto:')) {
        return redirect()->away($original);
    }
    
    // If it's just email address
    if (filter_var($original, FILTER_VALIDATE_EMAIL)) {
        return redirect()->away("mailto:{$original}");
    }
    
    // Default
    return redirect()->away($original);
}

// ✅ WHATSAPP REDIRECT
private function redirectToWhatsApp($qr)
{
    $original = $qr->original_url;
    
    // Check if it's already a WhatsApp link
    if (str_starts_with($original, 'https://wa.me/')) {
        return redirect()->away($original);
    }
    
    // If it's phone number
    $phone = preg_replace('/\D/', '', $original);
    
    // Remove leading 0 if present
    if (strlen($phone) > 10 && $phone[0] == '0') {
        $phone = substr($phone, 1);
    }
    
    return redirect()->away("https://wa.me/{$phone}");
}
    public function visit($code)
    {
        $qr = QrCode::where('short_url', $code)->firstOrFail();

        if ($qr->is_active) {
            $qr->increment('visits');
            $qr->increment('visit_count');
        }

        return redirect("/qr/{$code}/scan");
    }

    public function getDomain()
    {
        $domain = config('app.url');
        
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