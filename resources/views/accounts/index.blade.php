@extends('layouts.index')

@section('title', 'Accounts')

@section('content')

<div class="px-6 py-6 max-w-5xl mx-auto">

    {{-- PAGE HEADER --}}
    <div class="mb-10">
        <h2 class="text-2xl font-bold text-gray-900">Connected Accounts</h2>
        <p class="text-gray-500 text-sm mt-1">
            Manage your social media integrations.
        </p>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-5">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-5">
            {{ session('error') }}
        </div>
    @endif

    @php
        $platforms = [
            'facebook' => [
                'color' => 'bg-blue-600',
                'label' => 'Facebook',
                'letter' => 'F'
            ],
            'instagram' => [
                'color' => 'bg-pink-600',
                'label' => 'Instagram',
                'letter' => 'I'
            ],
            'youtube' => [
                'color' => 'bg-red-600',
                'label' => 'YouTube',
                'letter' => 'Y'
            ],
        ];

        $facebookPages = $accounts->where('platform', 'facebook');
    @endphp

    <div class="space-y-6">

        @foreach ($platforms as $key => $info)

            {{-- PLATFORM STATUS --}}
            @php
                if ($key === 'facebook') {
                    $isConnected = $facebookPages->count() > 0;
                } else {
                    $isConnected = $accounts->where('platform', $key)->first();
                }
            @endphp

            <div class="bg-white border rounded-xl shadow-sm p-5 flex flex-col sm:flex-row 
                        justify-between sm:items-center gap-5">

                {{-- LEFT --}}
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full {{ $info['color'] }}
                                text-white text-xl font-bold flex items-center justify-center">
                        {{ $info['letter'] }}
                    </div>

                    <div>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $info['label'] }}
                        </p>

                        <p class="text-sm text-gray-500">
                            @if($key === 'facebook' && $isConnected)
                                {{ $facebookPages->count() }} page(s) connected
                            @else
                                {{ $isConnected ? 'Account connected' : 'No account connected' }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- RIGHT --}}
                <div class="flex items-center gap-4">

                    @if($isConnected)

                        {{-- CONNECTED BADGE --}}
                        <span class="bg-green-100 text-green-600 text-xs px-3 py-1 rounded-full
                                     flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Connected
                        </span>

                        {{-- DISCONNECT --}}
                        <form method="POST" action="{{ route('accounts.disconnect') }}">
                            @csrf
                            <input type="hidden" name="platform" value="{{ $key }}">
                            <button class="text-gray-400 hover:text-red-500 transition"
                                    title="Disconnect">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-width="2" stroke-linecap="round"
                                          d="M6 7h12M10 11v6m4-6v6M8 7h8l-1 12H9L8 7z"/>
                                </svg>
                            </button>
                        </form>

                    @else

                        {{-- DISCONNECTED --}}
                        <span class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full">
                            Disconnected
                        </span>

                        {{-- CONNECT BUTTON --}}
                        @if($key === 'facebook')
                            <a href="{{ route('facebook.connect') }}"
                               class="px-4 py-1.5 text-white bg-blue-600 rounded-lg text-sm
                                      hover:bg-blue-700 transition">
                                Connect
                            </a>

                        @elseif($key === 'instagram')
                            <form method="POST" action="{{ route('instagram.connect') }}">
                                @csrf
                                <button
                                    class="px-4 py-1.5 text-pink-600 border border-pink-600
                                           rounded-lg text-sm hover:bg-pink-600
                                           hover:text-white transition">
                                    Connect
                                </button>
                            </form>

                        @elseif($key === 'youtube')
                            <a href="{{ route('youtube.connect') }}"
                               class="px-4 py-1.5 text-white bg-red-600 rounded-lg text-sm
                                      hover:bg-red-700 transition">
                                Connect
                            </a>
                        @endif

                    @endif

                </div>
            </div>
        @endforeach

    </div>

</div>

@endsection
