@extends('layouts.index')

@section('title', 'Create Post')

@section('content')

<form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
@csrf

<div class="flex flex-col lg:flex-row gap-8 min-h-[calc(100vh-80px)] p-4">

{{-- ================= LEFT ================= --}}
<div class="w-full lg:w-[68%]">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8">

{{-- HEADER --}}
<h2 class="text-2xl font-bold text-gray-900 mb-2">Create New Post</h2>
<p class="text-gray-500 text-sm mb-8">Publish content across platforms</p>

{{-- ================= PLATFORM SELECT ================= --}}
<div class="mb-6">
    <p class="text-sm font-semibold text-gray-700 mb-3">Select Platforms</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        @foreach($accounts as $platform => $acc)
            @if($acc->status === 'connected')
                <button type="button"
                    data-platform="{{ ucfirst($platform) }}"
                    class="platform-btn flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                           border-2 text-sm font-medium transition hover:shadow
                           bg-gray-50 border-gray-200">
                    <span>
                        @if($platform === 'facebook') 📘
                        @elseif($platform === 'instagram') 📷
                        @elseif($platform === 'youtube') ▶️
                        @endif
                    </span>
                    {{ ucfirst($platform) }}
                </button>
            @endif
        @endforeach
    </div>

    <input type="hidden" name="platforms" id="selectedPlatformsInput">
</div>

{{-- ================= FACEBOOK PAGES ================= --}}
@if(!empty($facebookPages))
<div id="facebookPageWrapper" class="mb-6 hidden">

    <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-semibold text-gray-700">
            Select Facebook Page
        </label>

        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
            {{ count($facebookPages) }} Pages
        </span>
    </div>

    <select name="facebook_page_id" id="facebookPageSelect"
        class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-sm
               focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <option value="">-- Choose a page --</option>

        @foreach($facebookPages as $page)
            <option value="{{ $page['page_id'] }}">
                {{ $page['page_name'] }}
            </option>
        @endforeach
    </select>

    <p class="text-xs text-gray-500 mt-2">
        Post will be published on the selected Facebook Page
    </p>
</div>
@endif

{{-- ================= POST CONTENT ================= --}}
<div class="mb-6">
    <label class="text-sm font-semibold text-gray-700 mb-2 block">
        Post Content
    </label>

    <textarea name="content" id="postText"
        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 min-h-[140px]
               focus:ring-2 focus:ring-blue-500"
        placeholder="What would you like to share?"></textarea>
</div>

{{-- ================= MEDIA ================= --}}
<div class="mb-6">
    <label class="text-sm font-semibold text-gray-700 mb-2 block">Media</label>

    <input type="file" name="media" id="mediaFile"
        class="block w-full text-sm border border-gray-300 rounded-xl p-3">

    <input type="url" name="media_url"
        class="mt-3 block w-full text-sm border border-gray-300 rounded-xl p-3"
        placeholder="Or paste media URL">
</div>

{{-- ================= SUBMIT ================= --}}
<div class="flex justify-end">
    <button type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5
               rounded-xl text-sm font-medium">
        Publish Post
    </button>
</div>

</div>
</div>

{{-- ================= RIGHT PREVIEW ================= --}}
<div class="w-full lg:w-[32%]">
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Preview</h3>

    <div id="previewContainer" class="space-y-4 text-sm text-gray-700">
        <p class="text-gray-400">Select platforms to see preview</p>
    </div>
</div>
</div>

</div>
</form>
@endsection

{{-- ================= SCRIPT ================= --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const buttons = document.querySelectorAll('.platform-btn');
    const hidden  = document.getElementById('selectedPlatformsInput');
    const fbWrap  = document.getElementById('facebookPageWrapper');
    const fbSelect= document.getElementById('facebookPageSelect');

    let platforms = [];

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {

            btn.classList.toggle('active');

            platforms = [...document.querySelectorAll('.platform-btn.active')]
                .map(b => b.dataset.platform);

            hidden.value = JSON.stringify(platforms);

            // FACEBOOK PAGE DROPDOWN TOGGLE
            if (fbWrap) {
                if (platforms.includes('Facebook')) {
                    fbWrap.classList.remove('hidden');
                } else {
                    fbWrap.classList.add('hidden');
                    if (fbSelect) fbSelect.value = '';
                }
            }
        });
    });

    // FORM VALIDATION
    document.getElementById('postForm').addEventListener('submit', (e) => {
        if (platforms.includes('Facebook')) {
            if (!fbSelect || !fbSelect.value) {
                alert('Please select a Facebook Page');
                e.preventDefault();
            }
        }
    });
});
</script>
@endpush
