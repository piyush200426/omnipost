<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppAccountController extends Controller
{
    public function page()
{
    return view('whatsapp_accounts.index');
}

    /**
     * List WhatsApp accounts of logged-in user
     */
    public function index()
    {
        $userId = (string) Auth::id();

        $accounts = WhatsAppAccount::where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $accounts,
        ]);
    }

    /**
     * Store / Connect WhatsApp account
     */
    public function store(Request $request)
    {
        $request->validate([
            'provider'             => 'required|string',
            'phone_number_id'      => 'required|string',
            'business_account_id'  => 'required|string',
            'access_token'         => 'required|string',
            'token_expires_at'     => 'nullable|date',
        ]);

        $account = WhatsAppAccount::create([
            'user_id'             => (string) Auth::id(),
            'provider'            => $request->provider,
            'phone_number_id'     => $request->phone_number_id,
            'business_account_id' => $request->business_account_id,
            'access_token'        => encrypt($request->access_token),
            'token_expires_at'    => $request->token_expires_at,
            'status'              => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp account connected successfully',
            'data'    => $account,
        ]);
    }

    /**
     * Disconnect WhatsApp account
     */
    public function destroy(string $id)
    {
        $account = WhatsAppAccount::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp account disconnected',
        ]);
    }
}
