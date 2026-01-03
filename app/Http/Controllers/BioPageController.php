<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BioPage;
use Illuminate\Support\Str;

class BioPageController extends Controller
{
    public function index(Request $request)
    {
        $bios = BioPage::where('user_id', auth()->id())->latest()->get();

        $editingBio = null;
        if ($request->filled('edit')) {
            $editingBio = BioPage::where('_id', $request->edit)
                ->where('user_id', auth()->id())
                ->first();
        }

        $createMode = $request->boolean('create');

        return view('bio.index', compact('bios', 'editingBio', 'createMode'));
    }

public function save(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'alias' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9-]+$/',
        'links' => 'nullable|array',
    ]);

    // FIND OR CREATE
    $bio = $request->filled('id')
        ? BioPage::where('_id', $request->id)
            ->where('user_id', auth()->id())
            ->firstOrFail()
        : new BioPage(['user_id' => auth()->id()]);

    // SLUG
    $slug = $request->filled('alias')
        ? Str::slug($request->alias)
        : ($bio->slug ?? Str::random(6));

    // 🔥 FINAL LINKS (MERGE ALL TYPES)
    $finalLinks = [];

    foreach ($request->input('links', []) as $item) {

        // ❌ DELETE
        if (($item['deleted'] ?? '0') === '1') {
            continue;
        }

        /* ================= TAGLINE ================= */
        if (
            ($item['type'] ?? '') === 'tagline' &&
            !empty($item['text'])
        ) {
            $finalLinks[] = [
                'id'      => $item['id'] ?? (string) Str::uuid(),
                'type'    => 'tagline',
                'text'    => $item['text'],
                'enabled' => ($item['enabled'] ?? '1') === '1',
            ];
        }

        /* ================= LINK ================= */
        if (
            ($item['type'] ?? '') === 'link' &&
            !empty($item['text']) &&
            !empty($item['url'])
        ) {
            $finalLinks[] = [
                'id'      => $item['id'] ?? (string) Str::uuid(),
                'type'    => 'link',
                'text'    => $item['text'],
                'url'     => $item['url'],
                'enabled' => ($item['enabled'] ?? '1') === '1',
            ];
        }

        /* ================= HEADING (NEW) ================= */
        if (
            ($item['type'] ?? '') === 'heading' &&
            !empty($item['text'])
        ) {
            $finalLinks[] = [
                'id'      => $item['id'] ?? (string) Str::uuid(),
                'type'    => 'heading',
                'text'    => $item['text'],
                'style'   => $item['style'] ?? 'h5',       // h1–h6
                'color'   => $item['color'] ?? '#000000',  // text color
                'enabled' => ($item['enabled'] ?? '1') === '1',
            ];
        }
        // TEXT
if (
    ($item['type'] ?? '') === 'text' &&
    !empty($item['text'])
) {
    $finalLinks[] = [
        'id'      => $item['id'] ?? Str::uuid(),
        'type'    => 'text',
        'text'    => $item['text'], // HTML allowed
        'enabled' => ($item['enabled'] ?? '1') === '1',
    ];
}

    }

    // SAVE BIO
    $bio->title     = $request->title;
    $bio->slug      = $slug;
    $bio->links     = $finalLinks;
    $bio->is_active = true;
    $bio->save();

    return redirect()
        ->route('bio.edit', $bio->_id)
        ->with('success', 'Saved successfully');
}



    public function edit($id)
    {
        return redirect()->route('bio.index', ['edit' => $id]);
    }

    public function delete(Request $request)
    {
        BioPage::where('_id', $request->id)
            ->where('user_id', auth()->id())
            ->delete();

        return redirect()->route('bio.index')
            ->with('success', 'Bio page deleted!');
    }



public function view($slug)
{
    $bio = BioPage::where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    // 🔥 IMPORTANT
    $links = $bio->links ?? [];

    return view('bio.public', compact('bio', 'links'));
}


}
