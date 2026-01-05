@php
    $user = Auth::user();

    // Default fallback
    $userName = $user->name ?? 'Guest User';

    // Initials: "Piyush Rajput" → "PR"
    $initials = collect(explode(' ', $userName))
                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                    ->join('');
@endphp


<div class="w-full flex items-center justify-between gap-4">

    <!-- LEFT SIDE -->
    <div class="flex items-center gap-3">
        <button onclick="openSidebar()"
            class="md:hidden bg-[#4C6FFF] text-white p-2 rounded-lg shadow-sm">
            ☰
        </button>

        <h2 class="text-xl font-bold whitespace-nowrap hidden md:block">
            Dashboard
        </h2>
    </div>


    <!-- RIGHT SIDE -->
    <div class="flex items-center gap-5 ml-auto">

        <!-- Desktop Search -->
        <div class="hidden md:flex items-center bg-[#F1F3F9] px-4 py-2 rounded-full border
                    w-[260px] lg:w-[320px] xl:w-[360px]">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 mr-2" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>

            <input type="text" placeholder="Search posts..."
                class="bg-transparent outline-none text-sm w-full" />
        </div>

        <!-- Bell Icon -->
        <div class="relative cursor-pointer">
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>

            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405C18.79 14.79 18 
                       13.42 18 12V8a6 6 0 10-12 0v4c0 1.42-.79 
                       2.79-1.595 3.595L3 17h5m7 0v1a3 3 0 
                       11-6 0v-1"/>
            </svg>
        </div>

        <!-- USER (Desktop) -->
        <div class="hidden sm:flex items-center gap-3 cursor-pointer">

            <div class="text-right leading-tight">
                <p class="font-semibold text-sm text-gray-800">
                    {{ $userName }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $user->role ?? 'User' }}
                </p>
            </div>

            <div class="w-10 h-10 bg-[#E6E8FF] rounded-full flex items-center justify-center 
                        text-[#4C6FFF] font-semibold">
                {{ $initials }}
            </div>
        </div>

        <!-- USER (Mobile) -->
        <div class="sm:hidden">
            <div class="w-8 h-8 bg-[#E6E8FF] rounded-full flex items-center justify-center 
                        text-[#4C6FFF] font-semibold">
                {{ $initials }}
            </div>
        </div>

    </div>

</div>
