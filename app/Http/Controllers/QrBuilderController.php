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
use Illuminate\Support\Facades\Log;

class QrBuilderController extends Controller
{
    public function index()
    {
        try {
            $qrCodes = QrCode::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return view('qr.list', compact('qrCodes'));
        } catch (\Exception $e) {
            Log::error('QR index error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('qr.list', ['qrCodes' => []]);
        }
    }

    public function create()
    {
        try {
            return view('qr.index');
        } catch (\Exception $e) {
            Log::error('QR create error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('qr.builder')->with('error', 'Error loading QR builder');
        }
    }

    public function edit($id)
    {
        try {
            Log::info('Edit called', ['id' => $id, 'user' => Auth::id()]);

            $qr = QrCode::where('_id', new ObjectId($id))
                ->where('user_id', (string) Auth::id())
                ->firstOrFail();

            return view('qr.index', [
                'qr' => $qr,
                'qrSettings' => is_string($qr->settings)
                    ? json_decode($qr->settings, true)
                    : $qr->settings
            ]);
        } catch (\Exception $e) {
            Log::error('QR edit error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('qr.builder')->with('error', 'QR not found');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'          => 'required|string|max:255',
                'mode'          => 'required|in:static,dynamic',
                'payload_type'  => 'required|string',
                'payload_value' => 'required',
                'design'        => 'nullable|array',
                'qr_png'        => 'required|string',
                'short_url'     => 'nullable|string',
                'qr_data'       => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $payload = $request->payload_value;
            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                }
            }

            $shortUrl     = null;
            $originalUrl  = null;
            $shortCode    = null;
            $qrData       = $request->qr_data;

            if ($request->mode === 'dynamic') {
                $shortCode = $request->short_url;
                
                if (empty($shortCode)) {
                    do {
                        $shortCode = Str::random(8);
                    } while (QrCode::where('short_url', $shortCode)->exists());
                } else {
                    if (QrCode::where('short_url', $shortCode)->exists()) {
                        do {
                            $shortCode = Str::random(8);
                        } while (QrCode::where('short_url', $shortCode)->exists());
                    }
                }

                $shortUrl = $shortCode;
                
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
                
                $qrData = $request->qr_data;
            } else {
                $qrData = $this->formatQrData($request->payload_type, $payload);
            }

            $pngPath = null;

            if ($request->qr_png) {
                $png = base64_decode(
                    preg_replace('#^data:image/\w+;base64,#i', '', $request->qr_png)
                );

                $pngPath = 'qr-codes/' . Auth::id() . '/' . time() . '.png';
                Storage::disk('public')->makeDirectory('qr-codes/' . Auth::id());
                Storage::disk('public')->put($pngPath, $png);
            }

            $qr = QrCode::create([
                'user_id'      => (string) Auth::id(),
                'title'        => $request->name,
                'qr_type'      => $request->payload_type,
                'qr_mode'      => $request->mode,
                'qr_data'      => $qrData,
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
                    'qr_data'      => $qr->qr_data,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('QR store error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating QR code'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('UPDATE ID CHECK', ['id' => $id]);

            $validator = Validator::make($request->all(), [
                'name'          => 'required|string|max:255',
                'mode'          => 'required|in:static,dynamic',
                'payload_type'  => 'required|string',
                'payload_value' => 'required',
                'design'        => 'nullable|array',
                'qr_png'        => 'nullable|string',
                'qr_data'       => 'required|string',
                'short_url'     => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $qr = QrCode::where('_id', new ObjectId($id))
                ->where('user_id', (string) Auth::id())
                ->firstOrFail();

            $payloadValue = $request->payload_value;
            if (is_string($payloadValue)) {
                $decoded = json_decode($payloadValue, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payloadValue = $decoded;
                }
            }

            $qrData = $request->qr_data;
            $shortCode = $qr->short_url;
            $originalUrl = $qr->original_url;

            if ($request->mode === 'dynamic') {
                $qrData = $request->qr_data;
                
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
                $qrData = $this->formatQrData($request->payload_type, $payloadValue);
            }

            $design = $request->design ?? [];

            if (!empty($request->qr_png)) {
                $imageData = base64_decode(
                    preg_replace('#^data:image/\w+;base64,#i', '', $request->qr_png)
                );

                Storage::disk('public')->makeDirectory('qr-codes/' . Auth::id());

                $qrImagePath = 'qr-codes/' . Auth::id() . '/' . time() . '.png';
                Storage::disk('public')->put($qrImagePath, $imageData);

                if ($qr->qr_image_url && Storage::disk('public')->exists($qr->qr_image_url)) {
                    Storage::disk('public')->delete($qr->qr_image_url);
                }

                $qr->qr_image_url = $qrImagePath;
            }

            $qr->update([
                'title'        => $request->name,
                'qr_mode'      => $request->mode,
                'qr_type'      => $request->payload_type,
                'qr_data'      => $qrData,
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
                    'qr_data'      => $qr->qr_data,
                    'updated_at'   => $qr->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('QR update error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating QR code'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $qr = QrCode::where('_id', new ObjectId($id))
                ->where('user_id', (string) Auth::id())
                ->firstOrFail();

            if ($qr->qr_image_url && Storage::disk('public')->exists($qr->qr_image_url)) {
                Storage::disk('public')->delete($qr->qr_image_url);
            }

            $qr->delete();

            return redirect()
                ->route('qr.builder')
                ->with('success', 'QR deleted successfully');
        } catch (\Exception $e) {
            Log::error('QR destroy error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()
                ->route('qr.builder')
                ->with('error', 'Error deleting QR');
        }
    }

    private function formatQrData($type, $data)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Format QR data error', [
                'type' => $type,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    public function scan($code)
    {
        try {
            $qr = QrCode::where('short_url', $code)->firstOrFail();

            if (!$qr->is_active) {
                abort(403);
            }

            $ip = request()->ip();
            $cacheKey = "qr_scan_{$qr->_id}_{$ip}";

            $qr->increment('scans');
            $qr->increment('qr_scan_count');

            if (!cache()->has($cacheKey)) {
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
                }

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

            return $this->redirectToTarget($qr);
        } catch (\Exception $e) {
            Log::error('QR scan error', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'QR not found');
        }
    }

    private function redirectToTarget($qr)
    {
        try {
            if (empty($qr->original_url)) {
                return redirect('/')->with('error', 'QR target not configured');
            }

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
                    $url = $qr->original_url;
                    if (!preg_match('/^https?:\/\//', $url)) {
                        $url = 'https://' . $url;
                    }
                    return redirect()->away($url);
                default:
                    return redirect()->away($qr->original_url);
            }
        } catch (\Exception $e) {
            Log::error('Redirect to target error', [
                'qr_id' => $qr->_id,
                'qr_type' => $qr->qr_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToApplication($qr)
    {
        try {
            $original = $qr->original_url;
            
            Log::info('Application Redirect Debug', [
                'original_url' => $original,
                'is_json' => str_starts_with($original, '{'),
                'is_url' => filter_var($original, FILTER_VALIDATE_URL),
                'qr_id' => $qr->_id
            ]);
            
            if (str_starts_with($original, '{')) {
                try {
                    Log::info('Trying JSON decode', ['original' => $original]);
                    $data = json_decode($original, true);
                    
                    Log::info('JSON Decoded', ['data' => $data]);
                    
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
                    Log::error('JSON Decode Failed', ['error' => $e->getMessage()]);
                }
            }
            
            if (filter_var($original, FILTER_VALIDATE_URL)) {
                Log::info('Direct URL redirect', ['url' => $original]);
                return redirect()->away($original);
            }
            
            Log::info('Google search redirect', ['query' => $original]);
            return redirect()->away("https://play.google.com/store/search?q=" . urlencode($original));
        } catch (\Exception $e) {
            Log::error('Redirect to application error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToFile($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (filter_var($original, FILTER_VALIDATE_URL)) {
                return redirect()->away($original);
            }
            
            if (Storage::disk('public')->exists($original)) {
                return Storage::disk('public')->response($original);
            }
            
            if (str_starts_with($original, 'data:')) {
                return response(base64_decode(preg_replace('#^data:[\w/]+;base64,#i', '', $original)))
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'inline');
            }
            
            abort(404, 'File not found');
        } catch (\Exception $e) {
            Log::error('Redirect to file error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'File not found');
        }
    }

    private function redirectToVCard($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_contains($original, 'BEGIN:VCARD')) {
                return response($original)
                    ->header('Content-Type', 'text/vcard')
                    ->header('Content-Disposition', 'attachment; filename="contact.vcf"');
            }
            
            if (str_starts_with($original, '{')) {
                try {
                    $data = json_decode($original, true);
                    $vcard = $this->generateVCard($data);
                    
                    return response($vcard)
                        ->header('Content-Type', 'text/vcard')
                        ->header('Content-Disposition', 'attachment; filename="contact.vcf"');
                } catch (\Exception $e) {
                }
            }
            
            return redirect()->away($original);
        } catch (\Exception $e) {
            Log::error('Redirect to vcard error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToWifi($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_starts_with($original, 'WIFI:')) {
                return view('qr.wifi', [
                    'qr' => $qr,
                    'wifiConfig' => $original
                ]);
            }
            
            if (str_starts_with($original, '{')) {
                try {
                    $data = json_decode($original, true);
                    
                    $encryption = isset($data['encryption']) ? $data['encryption'] : 'WPA';
                    $ssid = isset($data['ssid']) ? $data['ssid'] : '';
                    $password = isset($data['password']) ? $data['password'] : '';
                    
                    $wifiString = "WIFI:T:{$encryption};S:{$ssid};P:{$password};;";
                    
                    return view('qr.wifi', [
                        'qr' => $qr,
                        'wifiConfig' => $wifiString
                    ]);
                } catch (\Exception $e) {
                }
            }
            
            return redirect()->away($original);
        } catch (\Exception $e) {
            Log::error('Redirect to wifi error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToEvent($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_contains($original, 'BEGIN:VEVENT')) {
                return response($original)
                    ->header('Content-Type', 'text/calendar')
                    ->header('Content-Disposition', 'attachment; filename="event.ics"');
            }
            
            if (str_starts_with($original, '{')) {
                try {
                    $data = json_decode($original, true);
                    $ical = $this->generateICal($data);
                    
                    return response($ical)
                        ->header('Content-Type', 'text/calendar')
                        ->header('Content-Disposition', 'attachment; filename="event.ics"');
                } catch (\Exception $e) {
                }
            }
            
            return redirect()->away($original);
        } catch (\Exception $e) {
            Log::error('Redirect to event error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToCrypto($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_contains($original, ':')) {
                list($type, $address) = explode(':', $original, 2);
                
                return view('qr.crypto', [
                    'qr' => $qr,
                    'cryptoType' => $type,
                    'address' => $address
                ]);
            }
            
            return redirect()->away($original);
        } catch (\Exception $e) {
            Log::error('Redirect to crypto error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function generateVCard($data)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Generate vcard error', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "BEGIN:VCARD\nVERSION:3.0\nFN:Contact\nEND:VCARD";
        }
    }

    private function generateICal($data)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Generate ical error', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "BEGIN:VCALENDAR\nVERSION:2.0\nEND:VCALENDAR";
        }
    }

    private function redirectToSMS($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (strpos($original, ':') !== false) {
                list($phone, $message) = explode(':', $original, 2);
                
                $phone = trim($phone);
                $message = trim($message);
                
                $smsUrl = "sms:{$phone}?body=" . urlencode($message);
            } else {
                $smsUrl = "sms:" . trim($original);
            }
            
            return redirect()->away($smsUrl);
        } catch (\Exception $e) {
            Log::error('Redirect to SMS error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToPhone($qr)
    {
        try {
            $phone = $qr->original_url;
            $phone = preg_replace('/\D/', '', $phone);
            
            if ($phone && !str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }
            
            return redirect()->away("tel:{$phone}");
        } catch (\Exception $e) {
            Log::error('Redirect to phone error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToEmail($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_starts_with($original, 'mailto:')) {
                return redirect()->away($original);
            }
            
            if (filter_var($original, FILTER_VALIDATE_EMAIL)) {
                return redirect()->away("mailto:{$original}");
            }
            
            return redirect()->away($original);
        } catch (\Exception $e) {
            Log::error('Redirect to email error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    private function redirectToWhatsApp($qr)
    {
        try {
            $original = $qr->original_url;
            
            if (str_starts_with($original, 'https://wa.me/')) {
                return redirect()->away($original);
            }
            
            $phone = preg_replace('/\D/', '', $original);
            
            if (strlen($phone) > 10 && $phone[0] == '0') {
                $phone = substr($phone, 1);
            }
            
            return redirect()->away("https://wa.me/{$phone}");
        } catch (\Exception $e) {
            Log::error('Redirect to whatsapp error', [
                'qr_id' => $qr->_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->away($qr->original_url);
        }
    }

    public function visit($code)
    {
        try {
            $qr = QrCode::where('short_url', $code)->firstOrFail();

            if ($qr->is_active) {
                $qr->increment('visits');
                $qr->increment('visit_count');
            }

            return redirect("/qr/{$code}/scan");
        } catch (\Exception $e) {
            Log::error('QR visit error', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'QR not found');
        }
    }

    public function getDomain()
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Get domain error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'domain' => config('app.url'),
                'qr_base_url' => config('app.url') . '/qr/',
                'is_local' => false,
                'detected_host' => request()->getHost()
            ]);
        }
    }
}