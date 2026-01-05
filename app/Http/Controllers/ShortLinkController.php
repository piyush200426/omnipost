<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\ShortLink;

class ShortLinkController extends Controller
{
    /**
     * Show Short Links page
     */
    public function index()
    {
        $links = ShortLink::where('user_id', (string) Auth::id())
            ->latest()
            ->get();

        return view('short-links.index', compact('links'));
    }

    /**
     * Create short link
     */
    public function store(Request $request)
    {
        $request->validate([
            'original_url' => 'required|url',
        ]);

        do {
            $code = Str::random(6);
        } while (ShortLink::where('short_code', $code)->exists());

        ShortLink::create([
            'user_id'      => (string) Auth::id(),
            'original_url' => $request->original_url,
            'short_code'   => $code,
            'click_count'  => 0,
            'is_active'    => true,
        ]);

        return back()->with('success', 'Short link created successfully');
    }

    /**
     * Redirect short link
     */
    public function redirect($code)
    {
        
        $link = ShortLink::where('short_code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $link->increment('click_count');

        return redirect()->away($link->original_url);
    }

    /**
     * Delete short link
     */
    public function destroy($id)
    {
        ShortLink::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->delete();

        return back()->with('success', 'Short link deleted');
    }
    public function update(Request $request, $id)
{
    $request->validate([
        'original_url' => 'required|url',
    ]);

    $link = ShortLink::where('_id', $id)
        ->where('user_id', (string) Auth::id())
        ->firstOrFail();

    $link->update([
        'original_url' => $request->original_url,
    ]);

    return back()->with('success', 'Short link updated successfully');
}

}
