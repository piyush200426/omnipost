<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ShortLink;

class ShortLinkController extends Controller
{
    public function index()
    {
        try {
            $links = ShortLink::where('user_id', (string) Auth::id())
                ->latest()
                ->get();

            return view('short-links.index', compact('links'));
        } catch (\Throwable $e) {
            Log::error('Short link index error', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function store(Request $request)
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('Short link create error', [
                'user_id' => Auth::id(),
                'payload' => $request->all(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create short link']);
        }
    }

    public function redirect($code)
    {
        try {
            $link = ShortLink::where('short_code', $code)
                ->where('is_active', true)
                ->firstOrFail();

            $link->increment('click_count');

            return redirect()->away($link->original_url);
        } catch (\Throwable $e) {
            Log::error('Short link redirect error', [
                'code'  => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(404);
        }
    }

    public function destroy($id)
    {
        try {
            ShortLink::where('_id', $id)
                ->where('user_id', (string) Auth::id())
                ->delete();

            return back()->with('success', 'Short link deleted');
        } catch (\Throwable $e) {
            Log::error('Short link delete error', [
                'link_id' => $id,
                'user_id'=> Auth::id(),
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('Short link update error', [
                'link_id' => $id,
                'user_id'=> Auth::id(),
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to update short link']);
        }
    }
}
