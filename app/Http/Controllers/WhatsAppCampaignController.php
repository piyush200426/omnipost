<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppCampaign;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Exception;

class WhatsAppCampaignController extends Controller
{
   public function index()
{
    $userId = (string) Auth::id();

    $campaigns = WhatsAppCampaign::where('user_id', $userId)
        ->latest()
        ->get()
        ->map(fn ($c) => [
            '_id'    => (string) $c->_id,
            'name'   => $c->name,
            'status' => $c->status,
        ])
        ->values();

    // 🔥 FIXED: phone_number used
    $contacts = Contact::where('user_id', $userId)
        ->get()
        ->map(fn ($c) => [
            '_id'    => (string) $c->_id,
            'name'   => $c->name ?? 'Unknown',
            'mobile' => $c->phone_number ?? null,
        ])
        ->values();

    return view('whatsapp_accounts.campaigns', [
        'campaigns' => $campaigns,
        'contacts'  => $contacts,
        'account'   => WhatsAppAccount::where('user_id', $userId)->first(),
    ]);
}


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $campaign = WhatsAppCampaign::create([
            'user_id' => (string) Auth::id(),
            'name'    => $request->name,
            'status'  => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                '_id'    => (string) $campaign->_id,
                'name'   => $campaign->name,
                'status' => $campaign->status,
            ],
        ]);
    }

    public function send(Request $request)
    {
        try {

            $request->validate([
                'campaign_id' => 'required',
                'numbers'     => 'required|array|min:1',
            ]);

            $userId = (string) Auth::id();

            $campaign = WhatsAppCampaign::where('_id', $request->campaign_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $account = WhatsAppAccount::where('user_id', $userId)->firstOrFail();

            $success = 0;
            $failed  = 0;

            foreach ($request->numbers as $mobile) {

                try {
                    // ✅ CLEAN NUMBER
                    $mobile = preg_replace('/\D/', '', $mobile);

                    // 🔒 EMPTY NUMBER GUARD
                    if (!$mobile) {
                        Log::warning('Empty mobile skipped');
                        continue;
                    }

                    // 🇮🇳 INDIA FORMAT
                    if (strlen($mobile) === 10) {
                        $mobile = '91' . $mobile;
                    }

                    if (strlen($mobile) !== 12) {
                        throw new Exception('Invalid number');
                    }

                    WhatsAppService::sendTemplate(
                        $mobile,
                        'hello_world',
                        [],
                        null,
                        $account
                    );

                    $success++;

                } catch (Exception $e) {
                    $failed++;

                    Log::error('WhatsApp Send Failed', [
                        'to'    => $mobile,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ✅ SAVE STATUS ONLY ON ATTEMPT
            $campaign->status  = 'sent';
            $campaign->sent_at = now();
            $campaign->save();

            return response()->json([
                'success'  => true,
                'sent'     => $success,
                'failed'   => $failed,
                'campaign' => [
                    '_id'    => (string) $campaign->_id,
                    'name'   => $campaign->name,
                    'status' => $campaign->status,
                ],
            ]);
            } catch (Exception $e) {

            Log::error('Campaign Send Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Campaign send failed',
            ], 500);
        }
    }
}