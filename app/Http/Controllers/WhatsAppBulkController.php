<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;

class WhatsAppBulkController extends Controller
{
    /**
     * Prepare bulk send (create pending logs)
     */
    public function prepare(Request $request)
    {
        $request->validate([
            'whatsapp_account_id' => 'required|string',
            'contact_ids'         => 'required|array',
            'template_name'       => 'required|string',
            'payload'             => 'required|array',
        ]);

        $userId = (string) Auth::id();

        // 1️⃣ Load WhatsApp account (ownership check)
        $account = WhatsAppAccount::where('_id', $request->whatsapp_account_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // 2️⃣ Fetch contacts (opt-in only)
        $contacts = Contact::whereIn('_id', $request->contact_ids)
            ->where('user_id', $userId)
            ->where('opt_in', true)
            ->get();

        if ($contacts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid opt-in contacts found',
            ], 422);
        }

        $logs = [];

        // 3️⃣ Create pending logs
        foreach ($contacts as $contact) {
            $log = WhatsAppMessageLog::createPending([
                'user_id'             => $userId,
                'whatsapp_account_id' => (string) $account->_id,
                'contact_id'          => (string) $contact->_id,
                'phone_number'        => $contact->phone_number,
                'template_name'       => $request->template_name,
                'payload'             => $request->payload,
            ]);

            $logs[] = $log->_id;
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Bulk messages prepared',
            'total_contacts'=> count($logs),
            'log_ids'       => $logs,
        ]);
    }

public function execute(Request $request)
{
    $request->validate([
        'log_ids' => 'required|array',
    ]);

    $userId = (string) auth()->id();

    // Fetch logs (only pending, user-owned)
    $logs = \App\Models\WhatsAppMessageLog::whereIn('_id', $request->log_ids)
        ->where('user_id', $userId)
        ->where('status', 'pending')
        ->get();

    if ($logs->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No pending messages to send',
        ], 422);
    }

    // Load WhatsApp account (assume all logs share same account)
    $accountId = (string) $logs->first()->whatsapp_account_id;

    $account = \App\Models\WhatsAppAccount::where('_id', $accountId)
        ->where('user_id', $userId)
        ->firstOrFail();

    $service = new WhatsAppService(
        decrypt($account->access_token),
        $account->phone_number_id
    );

    $sent = 0;
    $failed = 0;

    foreach ($logs as $log) {
        try {
            $service->sendTemplate(
                $log->phone_number,
                $log->template_name,
                $log->payload['body'] ?? []
            );

            $log->markAsSent();
            $sent++;
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
            $failed++;
        }

        // Optional: small delay to respect rate limits
        usleep(200000); // 0.2 sec
    }

    return response()->json([
        'success' => true,
        'message' => 'Bulk send completed',
        'sent'    => $sent,
        'failed'  => $failed,
    ]);
}

}
