<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BioPage;
use App\Models\BioPageView;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\UTCDateTime;

class BioPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $bios = BioPage::where('user_id', auth()->id())
                ->latest()
                ->get()
                ->map(function ($bio) {
                    $bio->views = BioPageView::where(
                        'bio_page_id',
                        (string) $bio->_id
                    )->count();
                    return $bio;
                });

            $editingBio = null;
            $stats = [];

            if ($request->filled('edit')) {
                $editingBio = BioPage::where('_id', $request->edit)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($editingBio) {
                    $stats = $this->buildBioStats((string) $editingBio->_id);
                }
            }

            $createMode = $request->boolean('create');

            return view('bio.index', compact(
                'bios',
                'editingBio',
                'createMode',
                'stats'
            ));

        } catch (\Throwable $e) {
            Log::error('Bio index error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function save(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'alias' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9-]+$/',
            ]);
            
            // NEW BIO
            if (!$request->filled('id')) {
    $bio = new BioPage();
    $bio->user_id = auth()->id();
    $bio->is_active = true;

    $bio->slug = $request->filled('alias') 
        ? Str::slug($request->alias) 
        : Str::random(8);

    // ✅ ADD THESE DEFAULTS (MOST IMPORTANT)
    $bio->links = [];
    $bio->settings = [];
    $bio->design = [];
    $bio->social_links = [
        'position' => 'top',
        'items' => []
    ];
    $bio->social_display_style = 'icon_bg';
}

            // EXISTING BIO
            else {
                $bio = BioPage::where('_id', $request->id)
                    ->where('user_id', auth()->id())
                    ->firstOrFail();
            }
            
            $bio->title = $request->title;
            
            // Slug update
            if ($request->filled('alias') && $request->alias !== $bio->slug) {
                $bio->slug = Str::slug($request->alias);
            }
            
            // LINKS PROCESSING
            $linksInput = $request->input('links', []);
            
            // If links come as string, decode them
            if (is_string($linksInput)) {
                $linksInput = json_decode($linksInput, true) ?? [];
            }
            
            $finalLinks = [];
            foreach ($linksInput as $key => $item) {
                // Skip deleted items
                if (($item['deleted'] ?? '0') === '1') {
                    continue;
                }
                
                $type = $item['type'] ?? '';
                
                // TAGLINE
                if ($type === 'tagline' && !empty($item['text'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'tagline',
                        'text'    => $item['text'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // LINK
                if ($type === 'link' && !empty($item['text']) && !empty($item['url'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'link',
                        'text'    => $item['text'],
                        'url'     => $item['url'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // HEADING
                if ($type === 'heading' && !empty($item['text'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'heading',
                        'text'    => $item['text'],
                        'style'   => $item['style'] ?? 'h5',
                        'color'   => $item['color'] ?? '#000000',
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // TEXT
                if ($type === 'text' && !empty($item['text'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'text',
                        'text'    => $item['text'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // DIVIDER
                if ($type === 'divider') {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'divider',
                        'style'   => $item['style'] ?? 'solid',
                        'height'  => (int) ($item['height'] ?? 1),
                        'color'   => $item['color'] ?? '#000000',
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // HTML
                if ($type === 'html' && !empty($item['text'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'html',
                        'text'    => $item['text'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // IMAGE
                if ($type === 'image') {
                    $filePath = $item['file'] ?? null;
                    
                    if ($request->hasFile("links.$key.file")) {
                        $filePath = $request->file("links.$key.file")
                            ->store('bio/images', 'public');
                    }
                    
                    if ($filePath) {
                        $finalLinks[] = [
                            'id'      => $item['id'] ?? (string) Str::uuid(),
                            'type'    => 'image',
                            'file'    => $filePath,
                            'url'     => $item['url'] ?? null,
                            'enabled' => ($item['enabled'] ?? '1') === '1',
                        ];
                    }
                }
                
                // PHONE CALL
                if ($type === 'phone_call' && !empty($item['phone'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'phone_call',
                        'phone'   => $item['phone'],
                        'label'   => $item['label'] ?? 'Call us',
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // WHATSAPP CALL
                if ($type === 'whatsapp_call' && !empty($item['phone'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'whatsapp_call',
                        'phone'   => $item['phone'],
                        'label'   => $item['label'] ?? 'Call on WhatsApp',
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // WHATSAPP MESSAGE
                if ($type === 'whatsapp_message' && !empty($item['phone'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'whatsapp_message',
                        'phone'   => $item['phone'],
                        'message' => $item['message'] ?? '',
                        'label'   => $item['label'] ?? 'Message on WhatsApp',
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // VIDEO
                if ($type === 'video') {
                    $filePath = $item['file'] ?? null;
                    
                    if ($request->hasFile("links.$key.file")) {
                        $filePath = $request->file("links.$key.file")
                            ->store('bio/videos', 'public');
                    }
                    
                    if ($filePath) {
                        $finalLinks[] = [
                            'id'      => $item['id'] ?? (string) Str::uuid(),
                            'type'    => 'video',
                            'file'    => $filePath,
                            'url'     => $item['url'] ?? null,
                            'enabled' => ($item['enabled'] ?? '1') === '1',
                        ];
                    }
                }
                
                // AUDIO
                if ($type === 'audio') {
                    $filePath = $item['file'] ?? null;
                    
                    if ($request->hasFile("links.$key.file")) {
                        $filePath = $request->file("links.$key.file")
                            ->store('bio/audio', 'public');
                    }
                    
                    if ($filePath) {
                        $finalLinks[] = [
                            'id'      => $item['id'] ?? (string) Str::uuid(),
                            'type'    => 'audio',
                            'file'    => $filePath,
                            'enabled' => ($item['enabled'] ?? '1') === '1',
                        ];
                    }
                }
                
                // PDF
                if ($type === 'pdf') {
                    $filePath = $item['file'] ?? null;
                    
                    if ($request->hasFile("links.$key.file")) {
                        $filePath = $request->file("links.$key.file")
                            ->store('bio/pdfs', 'public');
                    }
                    
                    if ($filePath) {
                        $finalLinks[] = [
                            'id'      => $item['id'] ?? (string) Str::uuid(),
                            'type'    => 'pdf',
                            'file'    => $filePath,
                            'title'   => $item['title'] ?? 'View PDF',
                            'enabled' => ($item['enabled'] ?? '1') === '1',
                        ];
                    }
                }
                
                // YOUTUBE
                if ($type === 'youtube' && !empty($item['url'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'youtube',
                        'url'     => $item['url'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // SPOTIFY
                if ($type === 'spotify' && !empty($item['url'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'spotify',
                        'url'     => $item['url'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // INSTAGRAM
                if ($type === 'instagram' && !empty($item['url'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'instagram',
                        'url'     => $item['url'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // MAPS
                if ($type === 'maps' && !empty($item['address'])) {
                    $finalLinks[] = [
                        'id'      => $item['id'] ?? (string) Str::uuid(),
                        'type'    => 'maps',
                        'address' => $item['address'],
                        'enabled' => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // FAQ
                if ($type === 'faq' && !empty($item['question']) && !empty($item['answer'])) {
                    $finalLinks[] = [
                        'id'       => $item['id'] ?? (string) Str::uuid(),
                        'type'     => 'faq',
                        'question' => $item['question'],
                        'answer'   => $item['answer'],
                        'enabled'  => ($item['enabled'] ?? '1') === '1',
                    ];
                }
                
                // CONTACT FORM
                if ($type === 'contact_form') {
                    $finalLinks[] = [
                        'id'         => $item['id'] ?? (string) Str::uuid(),
                        'type'       => 'contact_form',
                        'text'       => $item['text'] ?? 'Contact',
                        'disclaimer' => $item['disclaimer'] ?? null,
                        'enabled'    => true,
                    ];
                }
                
                // NEWSLETTER
                if ($type === 'newsletter') {
                    $finalLinks[] = [
                        'id'          => $item['id'] ?? (string) Str::uuid(),
                        'type'        => 'newsletter',
                        'text'        => $item['text'] ?? 'Subscribe',
                        'description' => $item['description'] ?? null,
                        'disclaimer'  => $item['disclaimer'] ?? null,
                        'enabled'     => true,
                    ];
                }
            }
            
            // Save as array (not JSON string)
            $bio->links = $finalLinks;
            
            // SETTINGS & DESIGN
            $settingsInput = $request->input('settings');
            $designInput = $request->input('design');
            $socialLinksInput = $request->input('social_links');
            
            $bio->settings = is_string($settingsInput) 
                ? json_decode($settingsInput, true) ?? [] 
                : ($settingsInput ?? []);
                
            $bio->design = is_string($designInput) 
                ? json_decode($designInput, true) ?? [] 
                : ($designInput ?? []);
                
            $bio->social_links = is_string($socialLinksInput) 
                ? json_decode($socialLinksInput, true) ?? ['items' => []] 
                : ($socialLinksInput ?? ['items' => []]);
            
            $bio->is_active = true;
            $bio->save();
            
            return redirect()
                ->route('bio.edit', $bio->_id)
                ->with('success', 'Saved successfully');
                
        } catch (\Throwable $e) {
            Log::error('Bio save error', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        try {
            return redirect()->route('bio.index', ['edit' => $id]);
        } catch (\Throwable $e) {
            Log::error('Bio edit redirect error', [
                'bio_id' => $id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function delete(Request $request)
    {
        try {
            BioPage::where('_id', $request->id)
                ->where('user_id', auth()->id())
                ->delete();

            return redirect()->route('bio.index')
                ->with('success', 'Bio page deleted!');
        } catch (\Throwable $e) {
            Log::error('Bio delete error', [
                'bio_id' => $request->id ?? null,
                'user_id'=> auth()->id(),
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function view($slug, Request $request)
    {
        try {
            $bio = BioPage::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();
            
            // FIX: Decode links if they are stored as JSON string
            $rawLinks = $bio->links ?? [];
            $links = is_string($rawLinks) ? json_decode($rawLinks, true) ?? [] : $rawLinks;
            
            // VIEW TRACKING
            try {
                $ip = $request->ip();
                $userAgent = strtolower($request->userAgent() ?? '');
                
                $device = str_contains($userAgent, 'mobile') ? 'Mobile' : 'Desktop';
                
                if (str_contains($userAgent, 'chrome')) {
                    $browser = 'Chrome';
                } elseif (str_contains($userAgent, 'firefox')) {
                    $browser = 'Firefox';
                } elseif (str_contains($userAgent, 'safari')) {
                    $browser = 'Safari';
                } else {
                    $browser = 'Other';
                }
                
                if (str_contains($userAgent, 'windows')) {
                    $platform = 'Windows';
                } elseif (str_contains($userAgent, 'android')) {
                    $platform = 'Android';
                } elseif (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ios')) {
                    $platform = 'iOS';
                } elseif (str_contains($userAgent, 'mac')) {
                    $platform = 'Mac';
                } else {
                    $platform = 'Other';
                }
                
                $location = [];
                try {
                    $location = Http::timeout(2)
                        ->get("http://ip-api.com/json/{$ip}")
                        ->json();
                } catch (\Throwable $e) {
                    $location = [];
                }
                
                BioPageView::create([
                    'bio_page_id' => (string) $bio->_id,
                    'ip'          => $ip,
                    'country'     => $location['country'] ?? 'Unknown',
                    'city'        => $location['city'] ?? 'Unknown',
                    'device'      => $device,
                    'platform'    => $platform,
                    'browser'     => $browser,
                    'referrer'    => $request->headers->get('referer'),
                ]);
                
            } catch (\Throwable $e) {
                Log::warning('Bio view tracking failed', [
                    'slug'  => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return view('bio.public', compact('bio', 'links'));
            
        } catch (\Throwable $e) {
            Log::error('Bio public view error', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(404);
        }
    }
    
    private function buildBioStats(string $bioId): array
    {
        $base = BioPageView::where('bio_page_id', $bioId);
        
        $totalClicks  = $base->count();
        $uniqueClicks = $base->distinct('ip')->count('ip');
        
        $topCountry = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                ['$group' => ['_id' => '$country', 'total' => ['$sum' => 1]]],
                ['$sort' => ['total' => -1]],
                ['$limit' => 1],
            ]);
        })->first();
        
        $topCity = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                ['$group' => ['_id' => '$city', 'total' => ['$sum' => 1]]],
                ['$sort' => ['total' => -1]],
                ['$limit' => 1],
            ]);
        })->first();
        
        $locations = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                [
                    '$group' => [
                        '_id' => [
                            'country' => '$country',
                            'city'    => '$city',
                        ],
                        'total' => ['$sum' => 1],
                    ],
                ],
                ['$sort' => ['total' => -1]],
            ]);
        });
        
        $devices = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                ['$group' => ['_id' => '$device', 'total' => ['$sum' => 1]]],
                ['$sort' => ['total' => -1]],
            ]);
        });
        
        $browsers = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                ['$group' => ['_id' => '$browser', 'total' => ['$sum' => 1]]],
                ['$sort' => ['total' => -1]],
            ]);
        });
        
        $referrers = BioPageView::raw(function ($collection) use ($bioId) {
            return $collection->aggregate([
                ['$match' => ['bio_page_id' => $bioId]],
                ['$group' => ['_id' => '$referrer', 'total' => ['$sum' => 1]]],
                ['$sort' => ['total' => -1]],
            ]);
        });
        
        return [
            'total_clicks'  => $totalClicks,
            'unique_clicks' => $uniqueClicks,
            'top_country'   => $topCountry->_id ?? 'Unknown',
            'top_city'      => $topCity->_id ?? 'Unknown',
            
            'locations' => collect($locations)->map(function ($row) {
                return (object) [
                    'country' => $row->_id->country ?? 'Unknown',
                    'city'    => $row->_id->city ?? '—',
                    'total'   => $row->total,
                ];
            }),
            
            'devices' => collect($devices)->map(fn ($r) => (object)[
                'device' => $r->_id ?? 'Unknown',
                'total'  => $r->total,
            ]),
            
            'browsers' => collect($browsers)->map(fn ($r) => (object)[
                'browser' => $r->_id ?? 'Unknown',
                'total'   => $r->total,
            ]),
            
            'referrers' => collect($referrers)->map(fn ($r) => (object)[
                'referrer' => $r->_id,
                'total'    => $r->total,
            ]),
        ];
    }
}