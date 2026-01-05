<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SocialAccount;
use Carbon\Carbon;

class YouTubeTokenService
{
    public static function getAccessToken(string $userId): string
    {
        // 🔥 MongoDB safety
        $userId = (string) $userId;

        $acc = SocialAccount::where('platform', 'youtube')
            ->where('user_id', $userId)
            ->first();

        if (!$acc) {
            throw new \Exception('YouTube account not connected');
        }

        $creds = $acc->credentials;

        // ✅ token valid
        if (!empty($creds['expires_at']) && Carbon::parse($creds['expires_at'])->isFuture()) {
            return $creds['access_token'];
        }

        // 🔁 refresh
        $newCreds = self::refreshToken($acc);
        return $newCreds['access_token'];
    }

    protected static function refreshToken(SocialAccount $acc): array
    {
        $refreshToken = $acc->credentials['refresh_token'] ?? null;

        if (!$refreshToken) {
            throw new \Exception('Refresh token missing. Reconnect YouTube.');
        }

        $response = Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
                'client_id'     => config('services.youtube.client_id'),
                'client_secret' => config('services.youtube.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]
        )->json();

        if (!isset($response['access_token'])) {
            throw new \Exception('YouTube token refresh failed');
        }

        $acc->credentials = array_merge(
            $acc->credentials,
            [
                'access_token' => $response['access_token'],
                'expires_in'   => $response['expires_in'],
                'expires_at'   => Carbon::now()
                    ->addSeconds($response['expires_in'])
                    ->toISOString(),
            ]
        );

        $acc->save();

        return $acc->credentials;
    }
}
