<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title') | tracklio</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  {{-- 🔥 ADD THIS: AlpineJS --}}
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

  @stack('styles')

  <style>
    .no-scroll { overflow: hidden; }

    /* 🔥 ADD THIS: x-cloak fix */
    [x-cloak] { display: none !important; }
  </style>

  <script>
    function openSidebar() {
      document.getElementById('sidebar').classList.remove('-translate-x-full');
      document.getElementById('sidebarOverlay').classList.remove('hidden');
      document.documentElement.classList.add('no-scroll');
    }
    function closeSidebar() {
      document.getElementById('sidebar').classList.add('-translate-x-full');
      document.getElementById('sidebarOverlay').classList.add('hidden');
      document.documentElement.classList.remove('no-scroll');
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeSidebar();
    });
  </script>
</head>

<body class="bg-[#F5F7FB]">

<div class="flex h-screen w-full overflow-hidden">

  <div id="sidebarOverlay"
       class="hidden md:hidden fixed inset-0 bg-black/40 z-40"
       onclick="closeSidebar()"></div>

 <div id="sidebar"
     class="fixed top-0 left-0 h-screen w-64 bg-[#0D1321] text-white shadow-xl z-50
            overflow-y-auto
            transform -translate-x-full md:translate-x-0 transition-transform duration-300">

    @include('layouts.sidebar')
  </div>

  <div class="flex-1 flex flex-col md:ml-64">

    <div class="h-[72px] bg-white border-b flex items-center px-4 sm:px-6 md:px-10 sticky top-0 z-30">
      @include('layouts.navbar')
    </div>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-10">
      <div class="w-full">

        {{-- Flash messages --}}
        @if(session('success'))
          <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded">
            {{ session('success') }}
          </div>
        @endif

        @if(session('error'))
          <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded">
            {{ session('error') }}
          </div>
        @endif

        {{-- Main Content --}}
        @yield('content')

      </div>
    </div>

  </div>

</div>

@stack('scripts')
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
@stack('scripts')
</body>
</html>
