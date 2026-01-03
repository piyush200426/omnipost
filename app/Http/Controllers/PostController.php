<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\SocialAccount;

class PostController extends Controller
{
    protected string $fbVersion = 'v24.0';

    /* =========================================================
        CREATE POST PAGE
    ========================================================= */
 public function create()
{
    $userId = (string) auth()->user()->_id;

    /*
    |--------------------------------------------------------------------------
    | ACCOUNTS (FOR PLATFORM BUTTONS)
    |--------------------------------------------------------------------------
    */
    $accounts = SocialAccount::where('user_id', $userId)
        ->where('status', 'connected')
        ->get()
        ->keyBy('platform'); // facebook / instagram / youtube

    /*
    |--------------------------------------------------------------------------
    | FACEBOOK PAGES (FROM SINGLE DOCUMENT)
    |--------------------------------------------------------------------------
    */
    $facebookAccount = SocialAccount::where('user_id', $userId)
        ->where('platform', 'facebook')
        ->where('status', 'connected')
        ->first();

    $facebookPages = [];

    if ($facebookAccount && !empty($facebookAccount->pages)) {
        $facebookPages = $facebookAccount->pages;
    }

    return view('posts.create', [
        'accounts'       => $accounts,
        'facebookPages'  => $facebookPages,
    ]);
}


    /* =========================================================
        STORE + PUBLISH POST
    ========================================================= */
    public function store(Request $request)
    {
        $request->validate([
            'content'           => 'required|string',
            'platforms'         => 'required',
            'facebook_page_id'  => 'nullable|string',
        ]);

        $platforms = json_decode($request->platforms, true);

        $post = Post::create([
            'user_id'   => (string) auth()->user()->_id,
            'content'   => $request->content,
            'platforms' => $platforms,
            'status'    => 'processing',
        ]);

        if (in_array('facebook', $platforms)) {
            $this->publishFacebook($request, $post);
        }

        $post->update(['status' => 'published']);

        return back()->with('success', 'Post published successfully');
    }

    /* =========================================================
        FACEBOOK PUBLISH (PAGE FROM ARRAY)
    ========================================================= */
    protected function publishFacebook(Request $request, Post $post): bool
    {
        if (!$request->facebook_page_id) {
            Log::error('FACEBOOK PAGE NOT SELECTED');
            return false;
        }

        $account = SocialAccount::forUser(auth()->user()->_id)
            ->where('platform', 'facebook')
            ->first();

        if (!$account) return false;

        $page = collect($account->pages)
            ->firstWhere('page_id', $request->facebook_page_id);

        if (!$page) {
            Log::error('FACEBOOK PAGE NOT FOUND');
            return false;
        }

        $res = Http::asForm()->post(
            "https://graph.facebook.com/{$this->fbVersion}/{$page['page_id']}/feed",
            [
                'message' => $post->content,
                'access_token' => $page['page_access_token'],
            ]
        );

        Log::info('FB POST RESPONSE', $res->json());

        return $res->successful();
    }
}
