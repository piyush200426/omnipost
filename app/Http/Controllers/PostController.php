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

    public function create()
    {
        try {
            $userId = (string) auth()->user()->_id;

            $accounts = SocialAccount::where('user_id', $userId)
                ->where('status', 'connected')
                ->get()
                ->keyBy('platform');

            $facebookAccount = SocialAccount::where('user_id', $userId)
                ->where('platform', 'facebook')
                ->where('status', 'connected')
                ->first();

            $facebookPages = $facebookAccount->pages ?? [];

            Log::info('CREATE POST PAGE', [
                'user_id'  => $userId,
                'accounts' => $accounts->keys(),
                'fb_pages' => count($facebookPages),
            ]);

            return view('posts.create', compact('accounts', 'facebookPages'));
        } catch (\Throwable $e) {
            Log::critical('CREATE PAGE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'publish_error' => 'Failed to load create post page'
            ]);
        }
    }

    public function store(Request $request)
    {
        Log::info('POST STORE HIT', $request->all());

        try {
            $request->validate([
                'content'          => 'nullable|string',
                'platforms'        => 'required|string',
                'facebook_page_id' => 'nullable|string',
                'media'            => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov',
                'media_url'        => 'nullable|url',
                'is_short'         => 'nullable|boolean',
            ]);

            if (
                !$request->filled('content') &&
                !$request->hasFile('media') &&
                !$request->filled('media_url')
            ) {
                throw new \Exception('Post content, media file, or media URL is required');
            }

            $platforms = json_decode($request->platforms, true);

            if (!is_array($platforms) || empty($platforms)) {
                throw new \Exception('Please select at least one platform');
            }

            $platforms = array_map('strtolower', $platforms);

            $post = Post::create([
                'user_id'   => (string) auth()->user()->_id,
                'content'   => $request->input('content'),
                'platforms' => $platforms,
                'media_url' => $request->media_url,
                'is_short'  => (bool) $request->is_short,
                'status'    => 'processing',
            ]);

            $success = [];
            $errors  = [];

            if (in_array('facebook', $platforms)) {
                try {
                    $this->publishFacebook($request, $post);
                    $success[] = 'Facebook';
                } catch (\Throwable $e) {
                    Log::error('FACEBOOK FAILED', ['error' => $e->getMessage()]);
                    $errors[] = 'Facebook: ' . $e->getMessage();
                }
            }

            if (in_array('instagram', $platforms)) {
                try {
                    $this->publishInstagram($request, $post);
                    $success[] = 'Instagram';
                } catch (\Throwable $e) {
                    Log::error('INSTAGRAM FAILED', ['error' => $e->getMessage()]);
                    $errors[] = 'Instagram: ' . $e->getMessage();
                }
            }

            if (in_array('youtube', $platforms)) {
                try {
                    $this->publishYouTube($request, $post);
                    $success[] = 'YouTube';
                } catch (\Throwable $e) {
                    Log::error('YOUTUBE FAILED', ['error' => $e->getMessage()]);
                    $errors[] = 'YouTube: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                $post->update(['status' => 'failed']);

                return back()->withErrors([
                    'publish_error' => implode(' | ', $errors)
                ]);
            }

            $post->update(['status' => 'published']);

            return back()->with(
                'success',
                'Post published successfully on: ' . implode(', ', $success)
            );
        } catch (\Throwable $e) {
            Log::critical('POST STORE CRASH', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'publish_error' => $e->getMessage()
            ]);
        }
    }

    protected function publishFacebook(Request $request, Post $post): void
    {
        try {
            if (!$request->facebook_page_id) {
                throw new \Exception('Facebook page not selected');
            }

            $account = SocialAccount::forUser(auth()->user()->_id)
                ->where('platform', 'facebook')
                ->first();

            if (!$account) {
                throw new \Exception('Facebook account not connected');
            }

            $page = collect($account->pages)
                ->firstWhere('page_id', $request->facebook_page_id);

            if (!$page || empty($page['page_access_token'])) {
                throw new \Exception('Facebook page access token missing');
            }

            if (!$request->hasFile('media')) {
                $res = Http::asForm()->post(
                    "https://graph.facebook.com/{$this->fbVersion}/{$page['page_id']}/feed",
                    [
                        'message' => $post->content,
                        'access_token' => $page['page_access_token'],
                    ]
                );

                if (!$res->successful()) {
                    throw new \Exception(
                        $res->json('error.message') ?? 'Facebook text post failed'
                    );
                }

                return;
            }

            $file = $request->file('media');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image')) {
                $res = Http::attach(
                    'source',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )->post(
                    "https://graph.facebook.com/{$this->fbVersion}/{$page['page_id']}/photos",
                    [
                        'caption' => $post->content ?? '',
                        'access_token' => $page['page_access_token'],
                    ]
                );
            } elseif (str_starts_with($mime, 'video')) {
                $res = Http::attach(
                    'source',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )->post(
                    "https://graph.facebook.com/{$this->fbVersion}/{$page['page_id']}/videos",
                    [
                        'description' => $post->content ?? '',
                        'access_token' => $page['page_access_token'],
                    ]
                );
            } else {
                throw new \Exception('Unsupported media type for Facebook');
            }

            if (!$res->successful()) {
                throw new \Exception(
                    $res->json('error.message') ?? 'Facebook media upload failed'
                );
            }
        } catch (\Throwable $e) {
            Log::error('PUBLISH FACEBOOK ERROR', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function publishInstagram(Request $request, Post $post): void
    {
        try {
            if (!$post->media_url) {
                throw new \Exception('Instagram requires media URL');
            }

            $fbAccount = SocialAccount::forUser(auth()->user()->_id)
                ->where('platform', 'facebook')
                ->first();

            if (!$fbAccount) {
                throw new \Exception('Facebook account not connected');
            }

            $page = collect($fbAccount->pages)->first();

            $pageInfo = Http::get(
                "https://graph.facebook.com/{$this->fbVersion}/{$page['page_id']}",
                [
                    'fields' => 'instagram_business_account',
                    'access_token' => $page['page_access_token'],
                ]
            )->json();

            $igId = data_get($pageInfo, 'instagram_business_account.id');

            if (!$igId) {
                throw new \Exception('Instagram business account not linked');
            }

            $create = Http::asForm()->post(
                "https://graph.facebook.com/{$this->fbVersion}/{$igId}/media",
                [
                    'image_url' => $post->media_url,
                    'caption' => $post->content ?? '',
                    'access_token' => $page['page_access_token'],
                ]
            );

            if (!$create->successful()) {
                throw new \Exception(
                    $create->json('error.message') ?? $create->body()
                );
            }

            sleep(5);

            $publish = Http::asForm()->post(
                "https://graph.facebook.com/{$this->fbVersion}/{$igId}/media_publish",
                [
                    'creation_id' => $create['id'],
                    'access_token' => $page['page_access_token'],
                ]
            );

            if (!$publish->successful()) {
                throw new \Exception('Instagram publish failed');
            }
        } catch (\Throwable $e) {
            Log::error('PUBLISH INSTAGRAM ERROR', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function publishYouTube(Request $request, Post $post): void
    {
        try {
            if (!$request->hasFile('media')) {
                throw new \Exception('YouTube requires a video file');
            }

            $account = SocialAccount::forUser(auth()->user()->_id)
                ->where('platform', 'youtube')
                ->first();

            if (!$account) {
                throw new \Exception('YouTube account not connected');
            }

            $accessToken = $this->refreshYouTubeToken($account);
            $video = $request->file('media');

            $init = Http::withToken($accessToken)
                ->asJson()
                ->post(
                    'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
                    [
                        'snippet' => [
                            'title' => $post->content ?: 'New Video',
                            'description' => $post->content ?? '',
                            'categoryId' => '22',
                        ],
                        'status' => ['privacyStatus' => 'public'],
                    ]
                );

            if (!$init->successful()) {
                throw new \Exception('YouTube init failed');
            }

            $uploadUrl = $init->header('Location');

            Http::withToken($accessToken)
                ->withBody(file_get_contents($video->getRealPath()), 'application/octet-stream')
                ->put($uploadUrl);
        } catch (\Throwable $e) {
            Log::error('PUBLISH YOUTUBE ERROR', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function refreshYouTubeToken(SocialAccount $account): string
    {
        try {
            $creds = $account->credentials;

            $res = Http::asForm()->post(
                'https://oauth2.googleapis.com/token',
                [
                    'client_id' => env('YOUTUBE_CLIENT_ID'),
                    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
                    'refresh_token' => $creds['refresh_token'],
                    'grant_type' => 'refresh_token',
                ]
            );

            if (!$res->successful()) {
                throw new \Exception('Failed to refresh YouTube token');
            }

            return $res->json('access_token');
        } catch (\Throwable $e) {
            Log::error('YOUTUBE TOKEN REFRESH ERROR', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
