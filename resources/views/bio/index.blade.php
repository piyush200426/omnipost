@extends('layouts.index')

@section('title', 'Bio Pages')

@section('content')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

{{-- SUCCESS/ERROR MESSAGES --}}
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
    <ul class="list-disc pl-5 text-sm">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($editingBio)

{{-- ===================================================== --}}
{{-- ================== EDIT MODE ======================== --}}
{{-- ===================================================== --}}

<div class="space-y-6 sm:space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Customize {{ $editingBio->title }}</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Edit your bio page content and settings</p>
        </div>

        <div class="flex flex-col xs:flex-row gap-2 sm:gap-3">
            @if($editingBio && $editingBio->slug)
            <a href="{{ route('bio.view', $editingBio->slug) }}" target="_blank"
               class="px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs sm:text-sm font-medium text-gray-700 transition duration-200 text-center">
                Preview Bio
            </a>
            @endif

            <a href="{{ route('bio.index') }}"
               class="px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-xs sm:text-sm font-medium text-gray-700 transition duration-200 text-center">
                Back to List
            </a>
        </div>
    </div>

    {{-- TABS --}}
    <div class="bg-white rounded-xl shadow-sm border p-2 overflow-x-auto">
        <div class="flex gap-2 min-w-max">
            <button class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium bg-black text-white rounded-lg transition-all duration-200 whitespace-nowrap">
                Content
            </button>
            <button class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition duration-200 whitespace-nowrap">
                Social Links
            </button>
            <button class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition duration-200 whitespace-nowrap">
                Design
            </button>
            <button class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition duration-200 whitespace-nowrap">
                Settings
            </button>
            <button class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition duration-200 whitespace-nowrap">
                Statistics
            </button>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <form method="POST" action="{{ route('bio.save') }}"
          class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-5 sm:gap-6 lg:gap-8 items-start">

        @csrf
        <input type="hidden" name="id" value="{{ $editingBio->_id }}">

        {{-- LEFT COLUMN - FORM --}}
        <div class="space-y-5 sm:space-y-6">
            
            {{-- FORM CARD --}}
            <div class="bg-white rounded-xl shadow-sm border p-4 sm:p-6 space-y-5 sm:space-y-6">
                
                {{-- Bio Page Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Bio Page Name
                    </label>
                    <input id="bioName"
                           name="title"
                           value="{{ $editingBio->title }}"
                           oninput="updatePreview()"
                           class="w-full px-3 sm:px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                           placeholder="Enter bio page name">
                </div>

                {{-- Bio Page Alias --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Bio Page Alias
                    </label>
                    <div class="flex flex-col sm:flex-row">
                        <span class="inline-flex items-center px-3 sm:px-4 py-3 bg-gray-50 border border-gray-300 rounded-t-lg sm:rounded-l-lg sm:rounded-tr-none sm:border-r-0 text-gray-600 text-xs sm:text-sm">
                            https://qrurl.co/
                        </span>
                        <input name="alias"
                               value="{{ $editingBio->slug }}"
                               class="flex-1 px-3 sm:px-4 py-3 border border-gray-300 rounded-b-lg sm:rounded-r-lg sm:rounded-bl-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                               placeholder="your-alias">
                    </div>
                    <p class="mt-2 text-xs sm:text-sm text-gray-500">
                        Leave empty to generate a random alias
                    </p>
                </div>

                {{-- Add Content Button --}}
                <button type="button"
                        onclick="openAddContent()"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 sm:py-3.5 rounded-lg font-medium text-sm sm:text-base flex items-center justify-center gap-2 transition duration-200 hover:shadow-md">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Link or Content
                </button>

                {{-- CONTENT BLOCKS CONTAINER --}}
                <div id="contentBlocks" class="space-y-4 mt-6">
                    @foreach($editingBio->links ?? [] as $i => $link)

                    {{-- TAGLINE --}}
                    @if($link['type'] === 'tagline')
                    <div class="bg-white border rounded-xl shadow-sm">
                        <div class="p-4 font-medium text-sm sm:text-base">Tagline</div>
                        <div class="border-t p-4">
                            <input type="text"
                                   name="links[{{ $i }}][text]"
                                   value="{{ $link['text'] }}"
                                   class="w-full px-3 py-2 border rounded-lg text-sm sm:text-base"
                                   oninput="updateTaglineLive(this.value)">
                            <input type="hidden" name="links[{{ $i }}][type]" value="tagline">
                        </div>
                    </div>
                    @endif

                    {{-- LINK --}}
                    @if($link['type'] === 'link')
                    <div class="bg-white border rounded-xl shadow-sm">
                        <div class="p-4 font-medium text-sm sm:text-base">Link</div>
                        <div class="border-t p-4 space-y-3">
                            <input type="text"
                                   name="links[{{ $i }}][text]"
                                   value="{{ $link['text'] }}"
                                   class="w-full px-3 py-2 border rounded-lg text-sm sm:text-base">

                            <input type="url"
                                   name="links[{{ $i }}][url]"
                                   value="{{ $link['url'] }}"
                                   class="w-full px-3 py-2 border rounded-lg text-sm sm:text-base">

                            <input type="hidden" name="links[{{ $i }}][type]" value="link">
                        </div>
                    </div>
                    @endif

                    {{-- HEADING --}}
                    @if($link['type'] === 'heading')
                    <div class="bg-white border rounded-xl shadow-sm">
                        <div class="p-4 font-medium text-sm sm:text-base">Heading</div>
                        <div class="border-t p-4 space-y-3">
                            <select name="links[{{ $i }}][style]" class="w-full px-3 py-2 border rounded-lg text-sm sm:text-base">
                                @foreach(['h1','h2','h3','h4','h5','h6'] as $h)
                                <option value="{{ $h }}" {{ ($link['style'] ?? 'h5') === $h ? 'selected' : '' }}>
                                    {{ strtoupper($h) }}
                                </option>
                                @endforeach
                            </select>

                            <input type="text"
                                   name="links[{{ $i }}][text]"
                                   value="{{ $link['text'] }}"
                                   class="w-full px-3 py-2 border rounded-lg text-sm sm:text-base">

                            <div class="flex items-center gap-3">
                                <label class="text-xs sm:text-sm text-gray-700">Color:</label>
                                <input type="color"
                                       name="links[{{ $i }}][color]"
                                       value="{{ $link['color'] ?? '#000000' }}"
                                       class="w-8 h-8 sm:w-10 sm:h-10 cursor-pointer">
                            </div>

                            <input type="hidden" name="links[{{ $i }}][type]" value="heading">
                        </div>
                    </div>
                    @endif

                    {{-- TEXT (QUILL) --}}
                    @if($link['type'] === 'text')
                    <div class="bg-white border rounded-xl shadow-sm">
                        <div class="p-4 font-medium text-sm sm:text-base">Text</div>
                        <div class="border-t p-4 space-y-3">
                            <div id="editor-{{ $i }}" class="min-h-[150px] sm:min-h-[200px]"></div>

                            <input type="hidden"
                                   name="links[{{ $i }}][text]"
                                   id="input-{{ $i }}"
                                   value="{{ e($link['text']) }}">

                            <input type="hidden" name="links[{{ $i }}][type]" value="text">
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const quill{{ $i }} = new Quill('#editor-{{ $i }}', {
                                theme: 'snow',
                                modules: {
                                    toolbar: [
                                        ['bold', 'italic', 'underline'],
                                        [{ list: 'ordered' }, { list: 'bullet' }],
                                        ['link'],
                                        ['clean']
                                    ]
                                }
                            });

                            quill{{ $i }}.root.innerHTML = `{!! addslashes($link['text']) !!}`;

                            quill{{ $i }}.on('text-change', function () {
                                document.getElementById('input-{{ $i }}').value =
                                    quill{{ $i }}.root.innerHTML;
                            });
                        });
                    </script>
                    @endif

                    @endforeach
                </div>

                {{-- Save Button --}}
                <button type="submit"
                        class="w-full bg-black hover:bg-gray-900 text-white py-3 sm:py-3.5 rounded-lg font-medium text-sm sm:text-base transition duration-200 hover:shadow-md mt-6">
                    Save Changes
                </button>
            </div>
        </div>

        {{-- RIGHT COLUMN - MOBILE PREVIEW --}}
        <div class="sticky top-6">
            
            {{-- Preview Label --}}
            <div class="mb-4 text-center">
                <p class="text-sm font-medium text-gray-700">Live Preview</p>
                <p class="text-xs text-gray-500 mt-1">Updates in real-time</p>
            </div>
            
            {{-- Phone Frame --}}
            <div class="relative mx-auto max-w-[340px] sm:mx-0">
                {{-- Phone Top Speaker --}}
                <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-24 sm:w-32 h-4 sm:h-5 bg-black rounded-b-2xl z-10"></div>
                
                {{-- Phone Body --}}
                <div class="w-full max-w-[320px] sm:max-w-[340px] h-[600px] sm:h-[680px] bg-gradient-to-b from-gray-900 to-black rounded-[40px] sm:rounded-[48px] p-2 sm:p-3 shadow-2xl relative overflow-hidden mx-auto">
                    
                    {{-- Phone Notch --}}
                    <div class="absolute top-2 sm:top-3 left-1/2 transform -translate-x-1/2 w-24 sm:w-32 h-4 sm:h-5 bg-black rounded-b-xl z-20"></div>
                    
                    {{-- Phone Screen --}}
                    <div class="w-full h-full bg-white rounded-[32px] sm:rounded-[40px] overflow-y-auto p-3 sm:p-4 md:p-5 pt-10 sm:pt-12">
                        
                        {{-- Profile Avatar --}}
                        <div class="flex flex-col items-center mb-4 sm:mb-6">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl flex items-center justify-center mb-3 sm:mb-4 shadow">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            
                            {{-- Bio Name --}}
                            <h2 id="previewName" class="text-base sm:text-lg md:text-xl font-bold text-gray-900 text-center mb-1">
                                {{ $editingBio->title }}
                            </h2>
                            
                            {{-- Bio URL --}}
                            <p class="text-xs sm:text-sm text-gray-500 text-center">
                                qrurl.co/{{ $editingBio->slug }}
                            </p>
                            
                            {{-- Tagline Preview --}}
                            <p id="previewTagline" class="text-xs sm:text-sm text-gray-600 text-center mt-2 hidden"></p>
                        </div>

                        {{-- Content Blocks Preview --}}
                        <div class="space-y-3 sm:space-y-4">
                            {{-- Default Button --}}
                            <div class="bg-purple-100 text-purple-700 py-3 rounded-xl text-center font-medium shadow-sm text-xs sm:text-sm md:text-base">
                                QRURL
                            </div>
                            
                            {{-- Content Blocks Preview --}}
                            <div class="space-y-3 sm:space-y-4">
                                @foreach($editingBio->links ?? [] as $block)

                                {{-- TAGLINE --}}
                                @if($block['type'] === 'tagline' && ($block['enabled'] ?? true))
                                <p class="text-xs sm:text-sm text-gray-600 text-center">
                                    {{ $block['text'] }}
                                </p>
                                @endif

                                {{-- HEADING --}}
                                @if($block['type'] === 'heading' && ($block['enabled'] ?? true))
                                @php
                                $tag = $block['style'] ?? 'h5';
                                @endphp

                                <{{ $tag }}
                                    class="text-center font-semibold"
                                    style="color: {{ $block['color'] ?? '#000' }}"
                                >
                                    {{ $block['text'] }}
                                </{{ $tag }}>
                                @endif

                                {{-- TEXT --}}
                                @if($block['type'] === 'text' && ($block['enabled'] ?? true))
                                <div class="text-xs sm:text-sm text-gray-700 text-center leading-relaxed">
                                    {!! $block['text'] !!}
                                </div>
                                @endif

                                {{-- LINK --}}
                                @if($block['type'] === 'link' && ($block['enabled'] ?? true))
                                <a href="{{ $block['url'] }}"
                                   target="_blank"
                                   class="block bg-purple-100 text-purple-700 py-3 rounded-xl text-center font-medium text-xs sm:text-sm md:text-base">
                                    {{ $block['text'] }}
                                </a>
                                @endif

                                @endforeach
                            </div>
                            
                            {{-- Default Content --}}
                            <div class="bg-gray-100 text-gray-700 py-3 rounded-xl text-center font-medium shadow-sm text-xs sm:text-sm md:text-base">
                                More content ↓
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-100 text-center">
                            <p class="text-xs text-gray-400">
                                Powered by <span class="font-medium text-gray-600">QRURL</span>
                            </p>
                        </div>
                    </div>
                    
                    {{-- Home Button --}}
                    <div class="absolute bottom-3 sm:bottom-4 left-1/2 transform -translate-x-1/2 w-20 sm:w-24 h-1 sm:h-1.5 bg-gray-800 rounded-full"></div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ===================================================== --}}
{{-- ================== LIST MODE ======================== --}}
{{-- ===================================================== --}}
@else

<div class="space-y-6 sm:space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Bio Pages</h1>
            <div class="flex items-center gap-3 sm:gap-4 mt-2 flex-wrap">
                <span class="text-xs sm:text-sm text-gray-600">
                    {{ $bios->count() }} Bio Pages / 75
                </span>
                <span class="text-xs sm:text-sm text-gray-400 hidden sm:inline">•</span>
                <span class="text-xs sm:text-sm text-gray-600">
                    {{ $bios->sum('views') ?? 0 }} Total Views
                </span>
            </div>
        </div>

        <button onclick="openCreateModal()"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 sm:px-5 py-2.5 rounded-lg font-medium text-sm sm:text-base flex items-center justify-center gap-2 transition duration-200 shadow-sm hover:shadow w-full sm:w-auto">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Bio
        </button>
    </div>

    {{-- SEARCH --}}
    <div class="relative max-w-md">
        <input type="text" 
               placeholder="Search for Bio Pages" 
               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm sm:text-base">
        <svg class="absolute left-3 top-3.5 w-4 h-4 sm:w-5 sm:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>

    {{-- BIO PAGES GRID --}}
    @if($bios->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @foreach($bios as $bio)
        <div class="bg-white rounded-xl shadow-sm border p-4 sm:p-5 hover:shadow-md transition duration-200">
            <div class="flex items-start justify-between mb-3 sm:mb-4 flex-col sm:flex-row gap-2">
                <div class="w-full">
                    <h3 class="font-semibold text-gray-900 text-base sm:text-lg mb-2">{{ $bio->title }}</h3>
                    @if(!empty($bio->slug))
                    <a href="{{ route('bio.view', $bio->slug) }}" target="_blank" class="text-xs sm:text-sm text-purple-600 hover:text-purple-800 break-all">
                        qrurl.co/{{ $bio->slug }}
                    </a>
                    @endif
                </div>
                @if($bio->is_active)
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full w-fit mt-2 sm:mt-0">Active</span>
                @else
                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full w-fit mt-2 sm:mt-0">Inactive</span>
                @endif
            </div>

            <div class="flex items-center gap-2 sm:gap-3 text-xs sm:text-sm text-gray-500 mb-4 sm:mb-5 flex-wrap">
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ $bio->views ?? 0 }} views
                </span>
                <span class="hidden sm:inline">•</span>
                <span>{{ $bio->created_at->diffForHumans() }}</span>
            </div>

            <div class="flex gap-2 flex-col sm:flex-row">
                <a href="{{ route('bio.edit', $bio->_id) }}"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 text-center transition duration-200">
                    Edit
                </a>
                
                @if(!empty($bio->slug))
                <a href="{{ route('bio.view', $bio->slug) }}"
                   target="_blank"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-green-700 hover:bg-green-50 text-center transition duration-200">
                    View
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    {{-- EMPTY STATE --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 sm:p-8 md:p-10 text-center">
        <div class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4 sm:mb-6">
            <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">No bio pages yet</h3>
        <p class="text-xs sm:text-sm text-gray-500 mb-4 sm:mb-6 max-w-md mx-auto">
            Create your first bio page to share all your important links in one place.
        </p>
        <button onclick="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 sm:px-5 py-2.5 rounded-lg shadow-sm hover:shadow transition duration-200 w-full sm:w-auto text-sm sm:text-base">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Your First Bio
        </button>
    </div>
    @endif

</div>

{{-- CREATE BIO MODAL --}}
<div id="createBioModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        
        {{-- OVERLAY --}}
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeCreateModal()"></div>
        
        {{-- MODAL --}}
        <div class="inline-block w-full max-w-md p-4 sm:p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl">
            
            {{-- HEADER --}}
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">Create New Bio</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            {{-- FORM --}}
            <form method="POST" action="{{ route('bio.save') }}" id="createBioForm">
                @csrf
                
                <div class="space-y-4">
                    {{-- Bio Page Name --}}
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                            Bio Page Name
                        </label>
                        <input type="text" 
                               name="title" 
                               id="bioTitle"
                               required
                               placeholder="e.g. My Personal Bio"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm sm:text-base">
                    </div>
                    
                    {{-- Bio Page Alias --}}
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                            Bio Page Alias
                        </label>
                        <div class="flex flex-col sm:flex-row">
                            <span class="px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-t-lg sm:rounded-l-lg sm:rounded-tr-none sm:border-r-0 text-xs sm:text-sm text-gray-600">
                                https://qrurl.co/
                            </span>
                            <input type="text" 
                                   name="alias" 
                                   id="bioAlias"
                                   placeholder="your-alias"
                                   class="flex-1 px-3 py-2.5 border border-gray-300 rounded-b-lg sm:rounded-r-lg sm:rounded-bl-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm sm:text-base">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Leave empty to generate a random alias.
                        </p>
                    </div>
                </div>
                
                {{-- FOOTER --}}
                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                    <button type="button"
                            onclick="closeCreateModal()"
                            class="px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 order-2 sm:order-1">
                        Cancel
                    </button>
                    
                    <button type="submit"
                            class="px-3 sm:px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg text-xs sm:text-sm order-1 sm:order-2">
                        Create Bio
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</div>

@endif

{{-- ===================================================== --}}
{{-- =============== ADD CONTENT MODAL =================== --}}
{{-- ===================================================== --}}

<div id="addContentModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-3 sm:p-4">
        <div class="bg-white w-full max-w-5xl rounded-xl sm:rounded-2xl shadow-2xl overflow-hidden">
            
            {{-- MODAL HEADER --}}
            <div class="sticky top-0 bg-white border-b p-3 sm:p-4 md:p-6 z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg md:text-xl font-bold text-gray-900">Add Link or Content</h2>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Choose content type to add to your bio page</p>
                    </div>
                    <button onclick="closeAddContent()" 
                            class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            {{-- MODAL CONTENT --}}
            <div class="p-3 sm:p-4 md:p-6 max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3 md:gap-4">
                    
                    @foreach([
                        'Link','Tagline','Heading','Text','Divider','HTML',
                        'Image','Phone Call','WhatsApp Call','WhatsApp Message',
                        'Video','Audio','PDF','YouTube','Spotify','Instagram',
                        'TikTok','Google Maps','Contact Form','Newsletter','FAQs'
                    ] as $item)
                    <div
                        onclick="
                            @if($item === 'Link')
                                addLinkBlock()
                            @elseif($item === 'Tagline')
                                addTaglineBlock()
                            @elseif($item === 'Heading')
                                addHeadingBlock()
                            @elseif($item === 'Text') 
                                addTextBlock()
                            @endif
                        "
                        class="border border-gray-200 rounded-lg md:rounded-xl p-3 sm:p-4 hover:bg-purple-50 hover:border-purple-200 cursor-pointer transition duration-200 group"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-purple-100 transition duration-200">
                                @switch($item)
                                    @case('Link')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        @break
                                    @case('Tagline')
                                        <span class="text-gray-600 group-hover:text-purple-600 text-xs sm:text-sm md:text-base">🏷️</span>
                                        @break
                                    @case('Heading')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        @break
                                    @case('Text')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        @break
                                    @case('Divider')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        @break
                                    @case('Image')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        @break
                                    @case('Phone Call')
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        @break
                                    @case('WhatsApp')
                                    @case('WhatsApp Call')
                                    @case('WhatsApp Message')
                                        <span class="text-gray-600 group-hover:text-purple-600 text-xs sm:text-sm md:text-base">💬</span>
                                        @break
                                    @default
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                @endswitch
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 group-hover:text-purple-700 text-xs sm:text-sm md:text-base truncate">{{ $item }}</h4>
                                <p class="text-xs text-gray-500 mt-1 truncate">Add {{ strtolower($item) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                </div>
            </div>
            
            {{-- MODAL FOOTER --}}
            <div class="border-t p-3 sm:p-4 bg-gray-50">
                <div class="flex justify-end">
                    <button onclick="closeAddContent()"
                            class="px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-100 w-full sm:w-auto">
                        Cancel
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>

{{-- ===================================================== --}}
{{-- ================== JAVASCRIPT ======================= --}}
{{-- ===================================================== --}}
<script>
function addTextBlock() {
    const container = document.getElementById('contentBlocks');
    const index = container.children.length;

    const block = document.createElement('div');
    block.className = 'bg-white border rounded-xl shadow-sm';

    block.innerHTML = `
        <div class="p-4 font-medium text-sm sm:text-base">Text</div>

        <div class="border-t p-4 space-y-3">
            <div id="editor-${index}" class="bg-white min-h-[150px] sm:min-h-[200px]"></div>

            <input type="hidden" name="links[${index}][text]" id="input-${index}">
            <input type="hidden" name="links[${index}][type]" value="text">
        </div>
    `;

    container.appendChild(block);

    const quill = new Quill('#editor-' + index, {
        theme: 'snow',
        placeholder: 'Write something...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    quill.on('text-change', function () {
        document.getElementById('input-' + index).value = quill.root.innerHTML;
    });

    closeAddContent();
}
</script>

<script>
function addHeadingBlock() {
    const container = document.getElementById('contentBlocks');
    const index = container.children.length;

    const block = document.createElement('div');
    block.className = 'bg-white border rounded-xl shadow-sm';

    block.innerHTML = `
        <div class="flex items-center justify-between p-4 cursor-pointer" onclick="toggleBlock(this)">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold">H</span>
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate text-sm sm:text-base">Heading</div>
                    <div class="text-xs text-gray-500 truncate">New heading</div>
                </div>
            </div>
        </div>

        <div class="border-t p-4 space-y-4">

            <!-- STYLE -->
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-700">Style</label>
                <select name="links[${index}][style]"
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm sm:text-base">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                    <option value="h5" selected>H5</option>
                    <option value="h6">H6</option>
                </select>
            </div>

            <!-- TEXT -->
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-700">Text</label>
                <input type="text"
                       name="links[${index}][text]"
                       class="w-full mt-1 px-3 py-2 border rounded-lg text-sm sm:text-base"
                       placeholder="Heading text"
                       oninput="updateHeadingPreview(this.value)">
            </div>

            <!-- COLOR -->
            <div class="flex items-center gap-3">
                <label class="text-xs sm:text-sm font-medium text-gray-700">Color:</label>
                <input type="color"
                       name="links[${index}][color]"
                       value="#000000"
                       class="w-8 h-8 sm:w-10 sm:h-10 cursor-pointer">
            </div>

            <input type="hidden" name="links[${index}][type]" value="heading">
        </div>
    `;

    container.appendChild(block);
    closeAddContent();
}
</script>

<script>
// Update Preview Functions
function updatePreview() {
    const nameInput = document.getElementById('bioName');
    const previewName = document.getElementById('previewName');
    
    if (nameInput && previewName) {
        previewName.textContent = nameInput.value || 'Your Bio';
    }
}

function updateLinkPreviewText(value) {
    const el = document.getElementById('previewLinkText');
    if (el) {
        el.textContent = value || 'Your link text';
    }
}

function updateLinkPreviewUrl(value) {
    const el = document.getElementById('previewLink');
    if (el) {
        el.href = value || '#';
    }
}

function updateTaglineLive(value) {
    const preview = document.getElementById('previewTagline');
    if (!preview) return;

    if (value.trim() === '') {
        preview.classList.add('hidden');
        preview.textContent = '';
    } else {
        preview.textContent = value;
        preview.classList.remove('hidden');
    }
}

// Modal Functions
function openCreateModal() {
    const modal = document.getElementById('createBioModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('block');
        document.body.classList.add('overflow-hidden');
        
        setTimeout(() => {
            const titleInput = document.getElementById('bioTitle');
            if (titleInput) titleInput.focus();
        }, 100);
    }
}

function closeCreateModal() {
    const modal = document.getElementById('createBioModal');
    if (modal) {
        modal.classList.remove('block');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

function openAddContent() {
    const modal = document.getElementById('addContentModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}

function closeAddContent() {
    const modal = document.getElementById('addContentModal');
    if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// Content Block Functions
function toggleBlock(header) {
    const body = header.nextElementSibling;
    body.classList.toggle('hidden');
}

function addLinkBlock() {
    const container = document.getElementById('contentBlocks');
    const index = container.children.length;

    const block = document.createElement('div');
    block.className = 'bg-white border rounded-xl shadow-sm';
    block.innerHTML = `
        <div class="flex items-center justify-between p-4 cursor-pointer" onclick="toggleBlock(this)">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-lg">🔗</span>
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate text-sm sm:text-base">Link</div>
                    <div class="text-xs text-gray-500 truncate">New link</div>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="text-xs text-gray-400">0 Clicks</span>
                <input type="checkbox" checked>
            </div>
        </div>
        <div class="border-t p-4 space-y-4">
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-700">Text</label>
                <input type="text" name="links[${index}][text]" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm sm:text-base"
                       placeholder="e.g. Follow me on Instagram" oninput="updateLinkPreviewText(this.value)">
            </div>
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-700">Link</label>
                <input type="url" name="links[${index}][url]" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm sm:text-base"
                       placeholder="https://instagram.com" oninput="updateLinkPreviewUrl(this.value)">
            </div>
            <input type="hidden" name="links[${index}][type]" value="link">
        </div>
    `;
    container.appendChild(block);
    closeAddContent();
}

function addTaglineBlock() {
    const container = document.getElementById('contentBlocks');
    const index = container.children.length;

    const block = document.createElement('div');
    block.className = 'bg-white border rounded-xl shadow-sm';

    block.innerHTML = `
        <div class="flex items-center justify-between p-4 cursor-pointer" onclick="toggleBlock(this)">
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-lg">📝</span>
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate text-sm sm:text-base">Tagline</div>
                    <div class="text-xs text-gray-500 truncate">—</div>
                </div>
            </div>
        </div>

        <div class="border-t p-4 space-y-4">
            <div>
                <label class="text-xs sm:text-sm font-medium text-gray-700">Tagline</label>

                <input type="text"
                       name="links[${index}][text]"
                       class="w-full mt-1 px-3 py-2 border rounded-lg text-sm sm:text-base"
                       placeholder="e.g. Digital Creator"
                       oninput="updateTaglineLive(this.value)">

                <input type="hidden" name="links[${index}][type]" value="tagline">
            </div>
        </div>
    `;

    container.appendChild(block);
    closeAddContent();
}

// Event Listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
        closeAddContent();
    }
});

const createModal = document.getElementById('createBioModal');
if (createModal) {
    createModal.addEventListener('click', function(e) {
        if (e.target.id === 'createBioModal') {
            closeCreateModal();
        }
    });
}

const addContentModal = document.getElementById('addContentModal');
if (addContentModal) {
    addContentModal.addEventListener('click', function(e) {
        if (e.target.id === 'addContentModal') {
            closeAddContent();
        }
    });
}
</script>

<style>
#addContentModal .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #c7d2fe transparent;
}

#addContentModal .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

#addContentModal .overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

#addContentModal .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c7d2fe;
    border-radius: 3px;
}

#addContentModal .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a5b4fc;
}

/* Responsive breakpoints */
@media (max-width: 640px) {
    .xs\:flex-row {
        flex-direction: row;
    }
}

@media (max-width: 768px) {
    .lg\:grid-cols-\[1fr_380px\] {
        grid-template-columns: 1fr;
    }
    
    /* Center phone preview on mobile */
    .sticky {
        position: static !important;
    }
    
    .relative.mx-auto {
        margin-left: auto;
        margin-right: auto;
    }
}

/* Better mobile touch targets */
@media (max-width: 640px) {
    button, a, input[type="checkbox"] {
        min-height: 44px;
        min-width: 44px;
    }
    
    select, input[type="text"], input[type="url"] {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}
</style>

@endsection