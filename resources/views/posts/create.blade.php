@extends('layouts.index')

@section('title', 'Create Post')

@section('content')

<form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
@csrf

{{-- 🔴 ERROR MESSAGE --}}
@if ($errors->has('publish_error'))
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
        <strong>❌ Post Failed</strong>
        <p class="text-sm mt-1">{{ $errors->first('publish_error') }}</p>
    </div>
@endif

{{-- 🟢 SUCCESS MESSAGE --}}
@if (session('success'))
    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-700">
        <strong>✅ Success</strong>
        <p class="text-sm mt-1">{{ session('success') }}</p>
    </div>
@endif

<div class="flex flex-col lg:flex-row gap-6 min-h-[calc(100vh-80px)]">

{{-- ================= LEFT ================= --}}
<div class="w-full lg:w-[70%]">

    <h2 class="text-2xl font-bold mb-2">Create New Post</h2>
    <p class="text-gray-500 mb-8">Craft your content and publish to multiple platforms</p>

    {{-- ACTION BAR --}}
    <div class="flex justify-between items-center mb-8 p-4 bg-blue-50/50 rounded-xl border">
        <select name="status" class="border rounded-lg px-4 py-2 text-sm">
            <option value="Draft">Draft</option>
            <option value="Published" selected>Publish Now</option>
        </select>

        <button type="submit"
            class="bg-[#4C6FFF] hover:bg-[#3E58D8] text-white px-6 py-2.5 rounded-lg shadow">
            🚀 Publish Post
        </button>
    </div>

    {{-- PLATFORMS --}}
    <label class="font-semibold text-sm uppercase mb-3 block">Select Platforms</label>
    <div class="grid grid-cols-3 gap-4 mb-6">
        @foreach (['Facebook','Instagram','YouTube'] as $platform)
        <button type="button"
            class="platform-btn border-2 rounded-xl p-4 text-center"
            data-platform="{{ $platform }}">
            {{ $platform }}
        </button>
        @endforeach
    </div>

    <input type="hidden" name="platforms" id="selectedPlatformsInput">
    <input type="hidden" name="content" id="contentInput">

    {{-- FACEBOOK PAGE --}}
    @if(!empty($facebookPages))
    <div id="facebookPageBox" class="mb-6 hidden">
        <label class="font-semibold text-sm">Facebook Page</label>
        <select name="facebook_page_id" id="facebookPageSelect"
            class="w-full p-3 border rounded-xl">
            <option value="">-- Select Page --</option>
            @foreach($facebookPages as $page)
                <option value="{{ $page['page_id'] }}">
                    {{ $page['page_name'] ?? 'Facebook Page' }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- INSTAGRAM URL --}}
    <div id="instagramUrlBox" class="mb-6 hidden">
        <label class="font-semibold text-sm">Instagram Media URL</label>
        <input type="url" name="media_url" id="instagramMediaUrl"
            class="w-full p-3 border rounded-xl"
            placeholder="https://example.com/image-or-video.jpg">
        <p class="text-xs text-gray-500 mt-1">
            Instagram requires a <b>public media URL</b>
        </p>
    </div>

    {{-- CONTENT --}}
    <label class="font-semibold text-sm uppercase mb-2 block">Post Content</label>
    <textarea id="postText"
        class="w-full p-5 border rounded-xl min-h-[160px]"
        placeholder="Write something..."></textarea>
    <div class="text-right text-xs text-gray-500 mb-6" id="charCount">0/280</div>

    {{-- MEDIA FILE --}}
    <label class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer block mb-4">
        <p>Upload Image / Video</p>
        <input type="file" name="media" id="imageInput"
            accept="image/*,video/*" hidden>
    </label>

    <div id="uploadedPreview" class="hidden mb-6">
        <p id="fileName" class="text-sm font-medium"></p>
        <button id="removeMedia" type="button"
            class="text-xs text-red-500">Remove</button>
    </div>

</div>

{{-- ================= RIGHT PREVIEW ================= --}}
<div class="w-full lg:w-[30%] border-l pl-6">
    <h3 class="font-bold mb-4">Preview</h3>

    <div id="previewContainer" class="space-y-4"></div>

    <div id="emptyPreview" class="text-gray-400 text-center py-10">
        No platforms selected
    </div>
</div>

</div>
</form>
@endsection

@push('styles')
<style>
.platform-btn.active { border-color:#4C6FFF; background:#eef2ff; }
.preview-card { border:1px solid #e5e7eb; border-radius:12px; padding:12px; background:white; }
.preview-img { width:100%; border-radius:10px; margin-top:8px; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

const btns = document.querySelectorAll('.platform-btn');
const platformsInput = document.getElementById('selectedPlatformsInput');
const contentInput = document.getElementById('contentInput');
const postText = document.getElementById('postText');
const charCount = document.getElementById('charCount');

const facebookBox = document.getElementById('facebookPageBox');
const instagramBox = document.getElementById('instagramUrlBox');
const instagramUrl = document.getElementById('instagramMediaUrl');

const preview = document.getElementById('previewContainer');
const emptyPreview = document.getElementById('emptyPreview');

const fileInput = document.getElementById('imageInput');
const uploadedPreview = document.getElementById('uploadedPreview');
const fileNameEl = document.getElementById('fileName');
const removeMedia = document.getElementById('removeMedia');

let selected = [];
let fileUrl = null;

btns.forEach(btn => {
    btn.onclick = () => {
        const p = btn.dataset.platform;

        btn.classList.toggle('active');

        selected.includes(p)
            ? selected = selected.filter(x => x !== p)
            : selected.push(p);

        platformsInput.value = JSON.stringify(selected);

        facebookBox && (facebookBox.style.display = selected.includes('Facebook') ? 'block' : 'none');
        instagramBox && (instagramBox.style.display = selected.includes('Instagram') ? 'block' : 'none');

        render();
    };
});

postText.oninput = () => {
    charCount.innerText = `${postText.value.length}/280`;
    contentInput.value = postText.value;
    render();
};

fileInput.onchange = () => {
    if (!fileInput.files[0]) return;

    const file = fileInput.files[0];

    if (selected.includes('YouTube') && !file.type.startsWith('video')) {
        alert('YouTube requires video');
        fileInput.value = '';
        return;
    }

    fileUrl = URL.createObjectURL(file);
    fileNameEl.innerText = file.name;
    uploadedPreview.classList.remove('hidden');
    render();
};

removeMedia.onclick = () => {
    URL.revokeObjectURL(fileUrl);
    fileUrl = null;
    fileInput.value = '';
    uploadedPreview.classList.add('hidden');
    render();
};

document.getElementById('postForm').onsubmit = e => {
    if (!selected.length) {
        alert('Select at least one platform');
        e.preventDefault();
    }

    if (selected.includes('Instagram') && !instagramUrl.value) {
        alert('Instagram requires media URL');
        e.preventDefault();
    }

    contentInput.value = postText.value;
};

function render() {
    preview.innerHTML = '';

    if (!selected.length) {
        emptyPreview.style.display = 'block';
        return;
    }

    emptyPreview.style.display = 'none';

    selected.forEach(p => {
        let html = `<div class="preview-card"><b>${p}</b><p>${postText.value}</p>`;

        if (p === 'Instagram' && instagramUrl.value) {
            html += `<img src="${instagramUrl.value}" class="preview-img">`;
        }

        if ((p === 'Facebook' || p === 'YouTube') && fileUrl) {
            html += fileUrl.endsWith('.mp4')
                ? `<video src="${fileUrl}" controls class="preview-img"></video>`
                : `<img src="${fileUrl}" class="preview-img">`;
        }

        html += `</div>`;
        preview.innerHTML += html;
    });
}

});
</script>
@endpush
