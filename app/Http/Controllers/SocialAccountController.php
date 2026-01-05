<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SocialAccount;

class SocialAccountController extends Controller
{
    /* ===========================
        ACCOUNTS PAGE
    ============================ */
    public function index()
    {
        $accounts = SocialAccount::where('user_id', auth()->id())->get();
        return view('accounts.index', compact('accounts'));
    }


    /* ===========================
        CONNECT FACEBOOK / INSTAGRAM
    ============================ */
    public function connect(Request $request)
    {
        $request->validate([
            'platform' => 'required|string'
        ]);

        $platform = strtolower($request->platform);
        $credentials = [];

        // FACEBOOK
        if ($platform === 'facebook') {
            $credentials = [
                'page_id'    => env('FACEBOOK_PAGE_ID'),
                'page_token' => env('FACEBOOK_PAGE_TOKEN'),
            ];
        }

        // INSTAGRAM
        if ($platform === 'instagram') {
            $credentials = [
                'business_id' => env('INSTAGRAM_BUSINESS_ID'),
                'access_token'=> env('INSTAGRAM_ACCESS_TOKEN'),
            ];
        }

        if (empty(array_filter($credentials))) {
            return back()->with('error', ucfirst($platform) . ' credentials missing in .env');
        }

        SocialAccount::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'platform' => $platform,
            ],
            [
                'status'      => 'connected',
                'credentials' => $credentials,
            ]
        );

        return back()->with('success', ucfirst($platform) . ' connected successfully');
    }


    /* ===========================
        DISCONNECT ACCOUNT
    ============================ */
    public function disconnect(Request $request)
    {
        $request->validate([
            'platform' => 'required|string'
        ]);

        SocialAccount::where('user_id', auth()->id())
            ->where('platform', strtolower($request->platform))
            ->delete();

        return back()->with('success', ucfirst($request->platform) . ' disconnected successfully');
    }


    /* ===========================
        YOUTUBE CONNECT (OAUTH)
    ============================ */
public function connectYouTube()
{
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => env('YOUTUBE_CLIENT_ID'),
        'redirect_uri'  => env('YOUTUBE_REDIRECT_URI'),
        'response_type' => 'code',
        'scope' => implode(' ', [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube'
        ]),
        'access_type'   => 'offline', // 🔥 REQUIRED
        'prompt'        => 'consent', // 🔥 REQUIRED
    ]);

    return redirect($authUrl);
}



    /* ===========================
        YOUTUBE CALLBACK
    ============================ */
public function youtubeCallback(Request $request)
{
    if (!$request->code) {
        return redirect()->route('accounts')->with('error', 'YouTube authorization failed');
    }

    $token = Http::asForm()->post(
        'https://oauth2.googleapis.com/token',
        [
            'code'          => $request->code,
            'client_id'     => env('YOUTUBE_CLIENT_ID'),
            'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
            'redirect_uri'  => env('YOUTUBE_REDIRECT_URI'),
            'grant_type'    => 'authorization_code',
        ]
    )->json();

    if (empty($token['refresh_token'])) {
        return redirect()->route('accounts')
            ->with('error', 'YouTube refresh token missing. Please reconnect.');
    }

    SocialAccount::updateOrCreate(
        [
            'user_id'  => auth()->id(),
            'platform' => 'youtube',
        ],
        [
            'status' => 'connected',
            'credentials' => json_encode([
                'access_token'  => $token['access_token'],
                'refresh_token' => $token['refresh_token'], // 🔥 MUST
                'expires_at'    => now()->addSeconds($token['expires_in'])->toISOString(),
                'scope'         => $token['scope'] ?? '',
            ]),
        ]
    );

    return redirect()->route('accounts')
        ->with('success', 'YouTube connected successfully');
}

}
