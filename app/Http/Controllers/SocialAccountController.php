<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;
use Carbon\Carbon;

class SocialAccountController extends Controller
{
    protected string $fbVersion = 'v24.0';

    /* ===========================
       ACCOUNTS PAGE
    ============================ */
    public function index()
    {
        $accounts = SocialAccount::forUser(auth()->user()->_id)->get();
        return view('accounts.index', compact('accounts'));
    }

    /* ===========================
       FACEBOOK CONNECT
    ============================ */
    public function connectFacebook()
    {
        $scope = implode(',', [
            'pages_show_list',
            'pages_manage_posts',
            'pages_read_engagement',
            'read_insights'
        ]);

        $url = "https://www.facebook.com/{$this->fbVersion}/dialog/oauth?" .
            http_build_query([
                'client_id'     => env('FACEBOOK_CLIENT_ID'),
                'redirect_uri'  => env('FACEBOOK_REDIRECT_URI'),
                'response_type' => 'code',
                'scope'         => $scope,
            ]);

        return redirect($url);
    }

    /* ===========================
       FACEBOOK CALLBACK
    ============================ */
    public function facebookCallback(Request $request)
    {
        if (!$request->code) {
            return redirect()->route('accounts')
                ->with('error', 'Facebook authorization failed');
        }

        /* ===== CODE → SHORT TOKEN ===== */
        $short = Http::get(
            "https://graph.facebook.com/{$this->fbVersion}/oauth/access_token",
            [
                'client_id'     => env('FACEBOOK_CLIENT_ID'),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'redirect_uri'  => env('FACEBOOK_REDIRECT_URI'),
                'code'          => $request->code,
            ]
        )->json();

        $shortToken = $short['access_token'] ?? null;
        if (!$shortToken) {
            return back()->with('error', 'Short token failed');
        }

        /* ===== SHORT → LONG TOKEN ===== */
        $long = Http::get(
            "https://graph.facebook.com/{$this->fbVersion}/oauth/access_token",
            [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => env('FACEBOOK_CLIENT_ID'),
                'client_secret'     => env('FACEBOOK_CLIENT_SECRET'),
                'fb_exchange_token' => $shortToken,
            ]
        )->json();

        $longToken = $long['access_token'] ?? null;
        if (!$longToken) {
            return back()->with('error', 'Long token failed');
        }

        /* ===== FETCH PAGES ===== */
        $pagesRes = Http::get(
            "https://graph.facebook.com/{$this->fbVersion}/me/accounts",
            ['access_token' => $longToken]
        )->json();

        $pages = $pagesRes['data'] ?? [];
        if (empty($pages)) {
            return back()->with('error', 'No Facebook pages found');
        }

        $pageList = collect($pages)->map(fn ($p) => [
            'page_id'            => $p['id'],
            'page_name'          => $p['name'],
            'page_access_token'  => $p['access_token'],
            'category'           => $p['category'] ?? null,
        ])->values()->toArray();

        SocialAccount::updateOrCreate(
            [
                'user_id'  => (string) auth()->user()->_id,
                'platform' => 'facebook',
            ],
            [
                'status'      => 'connected',
                'credentials' => [
                    'user_access_token' => $longToken,
                    'token_type'        => 'long_lived',
                    'expires_at'        => Carbon::now()->addDays(60),
                ],
                'pages' => $pageList,
            ]
        );

        Log::info('FACEBOOK CONNECTED', ['pages' => count($pageList)]);

        return redirect()->route('accounts')
            ->with('success', 'Facebook connected with '.count($pageList).' pages');
    }

    /* ===========================
       INSTAGRAM
    ============================ */
    public function connectInstagram()
    {
        SocialAccount::updateOrCreate(
            [
                'user_id'  => (string) auth()->user()->_id,
                'platform' => 'instagram',
            ],
            [
                'status'      => 'connected',
                'credentials' => [
                    'business_id'  => env('INSTAGRAM_BUSINESS_ID'),
                    'access_token'=> env('INSTAGRAM_ACCESS_TOKEN'),
                ],
                'pages' => [],
            ]
        );

        return back()->with('success', 'Instagram connected');
    }

    /* ===========================
       DISCONNECT
    ============================ */
    public function disconnect(Request $request)
    {
        SocialAccount::forUser(auth()->user()->_id)
            ->where('platform', $request->platform)
            ->delete();

        return back()->with('success', ucfirst($request->platform).' disconnected');
    }
}
