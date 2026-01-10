<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;

class SocialAccountController extends Controller
{
    public function index()
    {
        try {
            $accounts = SocialAccount::where('user_id', auth()->id())->get();
            return view('accounts.index', compact('accounts'));
        } catch (\Throwable $e) {
            Log::error('Social account index error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function connectFacebook()
    {
        try {
            $authUrl = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
                'client_id'     => env('FACEBOOK_CLIENT_ID'),
                'redirect_uri'  => env('FACEBOOK_REDIRECT_URI'),
                'response_type' => 'code',
                'scope'         => implode(',', [
                    'pages_show_list',
                    'pages_read_engagement',
                    'pages_manage_metadata',
                    'pages_manage_posts',
                    'instagram_basic',
                    'instagram_content_publish'
                ]),
            ]);

            return redirect($authUrl);
        } catch (\Throwable $e) {
            Log::critical('Facebook connect init failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to connect Facebook');
        }
    }

    public function facebookCallback(Request $request)
    {
        try {
            if (!$request->code) {
                return redirect()->route('accounts')
                    ->with('error', 'Facebook authorization failed');
            }

            $token = Http::asForm()->post(
                'https://graph.facebook.com/v19.0/oauth/access_token',
                [
                    'client_id'     => env('FACEBOOK_CLIENT_ID'),
                    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                    'redirect_uri'  => env('FACEBOOK_REDIRECT_URI'),
                    'code'          => $request->code,
                ]
            )->json();

            if (empty($token['access_token'])) {
                return redirect()->route('accounts')
                    ->with('error', 'Failed to get Facebook access token');
            }

            $userToken = $token['access_token'];

            $pages = Http::get(
                'https://graph.facebook.com/v19.0/me/accounts',
                [
                    'fields' => 'id,name,access_token',
                    'access_token' => $userToken,
                ]
            )->json();

            if (
                !isset($pages['data']) ||
                !is_array($pages['data']) ||
                count($pages['data']) === 0
            ) {
                Log::error('FB pages empty', [
                    'user_id'  => auth()->id(),
                    'response' => $pages,
                ]);

                return redirect()->route('accounts')
                    ->with('error', 'Facebook connected but no pages available.');
            }

            $page = $pages['data'][0];

            $ig = Http::get(
                "https://graph.facebook.com/v19.0/{$page['id']}",
                [
                    'fields' => 'instagram_business_account',
                    'access_token' => $page['access_token'],
                ]
            )->json();

            SocialAccount::updateOrCreate(
                [
                    'user_id'  => auth()->id(),
                    'platform' => 'facebook',
                ],
                [
                    'status' => 'connected',
                    'credentials' => [
                        'user_access_token' => $userToken,
                    ],
                    'pages' => [
                        [
                            'page_id'           => $page['id'],
                            'page_name'         => $page['name'],
                            'page_access_token' => $page['access_token'],
                        ]
                    ],
                ]
            );

            if (!empty($ig['instagram_business_account']['id'])) {
                SocialAccount::updateOrCreate(
                    [
                        'user_id'  => auth()->id(),
                        'platform' => 'instagram',
                    ],
                    [
                        'status' => 'connected',
                        'credentials' => [
                            'instagram_business_id' => $ig['instagram_business_account']['id'],
                            'page_id'               => $page['id'],
                            'page_access_token'     => $page['access_token'],
                        ],
                    ]
                );
            }

            return redirect()->route('accounts')
                ->with('success', 'Facebook & Instagram connected successfully');
        } catch (\Throwable $e) {
            Log::critical('Facebook callback failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('accounts')
                ->with('error', 'Facebook connection failed');
        }
    }

    public function disconnect(Request $request)
    {
        try {
            $request->validate([
                'platform' => 'required|string'
            ]);

            SocialAccount::where('user_id', auth()->id())
                ->where('platform', strtolower($request->platform))
                ->delete();

            return back()->with('success', ucfirst($request->platform) . ' disconnected');
        } catch (\Throwable $e) {
            Log::error('Social account disconnect error', [
                'user_id'  => auth()->id(),
                'platform' => $request->platform ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to disconnect account');
        }
    }

    public function connectYouTube()
    {
        try {
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id'     => env('YOUTUBE_CLIENT_ID'),
                'redirect_uri'  => env('YOUTUBE_REDIRECT_URI'),
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'scope' => implode(' ', [
                    'https://www.googleapis.com/auth/youtube.upload',
                    'https://www.googleapis.com/auth/youtube'
                ]),
            ]);

            return redirect($authUrl);
        } catch (\Throwable $e) {
            Log::critical('YouTube connect init failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to connect YouTube');
        }
    }

    public function youtubeCallback(Request $request)
    {
        try {
            if (!$request->code) {
                return redirect()->route('accounts')
                    ->with('error', 'YouTube authorization failed');
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
                    ->with('error', 'YouTube refresh token missing');
            }

            SocialAccount::updateOrCreate(
                [
                    'user_id'  => auth()->id(),
                    'platform' => 'youtube',
                ],
                [
                    'status' => 'connected',
                    'credentials' => [
                        'access_token'  => $token['access_token'],
                        'refresh_token' => $token['refresh_token'],
                        'expires_at'    => now()->addSeconds($token['expires_in'])->toISOString(),
                        'scope'         => $token['scope'] ?? '',
                    ],
                ]
            );

            return redirect()->route('accounts')
                ->with('success', 'YouTube connected successfully');
        } catch (\Throwable $e) {
            Log::critical('YouTube callback failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('accounts')
                ->with('error', 'YouTube connection failed');
        }
    }

    public function connectInstagram()
    {
        try {
            $businessId  = env('INSTAGRAM_BUSINESS_ID');
            $accessToken = env('INSTAGRAM_ACCESS_TOKEN');

            if (!$businessId || !$accessToken) {
                return back()->with('error', 'Instagram Business ID or Access Token missing in .env');
            }

            $check = Http::get(
                "https://graph.facebook.com/v19.0/{$businessId}",
                [
                    'fields' => 'id,username',
                    'access_token' => $accessToken,
                ]
            );

            if (!$check->successful()) {
                return back()->with('error', 'Invalid Instagram access token');
            }

            SocialAccount::updateOrCreate(
                [
                    'user_id'  => auth()->id(),
                    'platform' => 'instagram',
                ],
                [
                    'status' => 'connected',
                    'credentials' => [
                        'instagram_business_id' => $businessId,
                        'access_token'          => $accessToken,
                        'username'              => $check->json('username'),
                    ],
                ]
            );

            return back()->with('success', 'Instagram connected successfully ');
        } catch (\Throwable $e) {
            Log::error('Instagram connect error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to connect Instagram');
        }
    }
}
