<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $bio->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#7c3aed">

    <!-- Tailwind CSS with responsive configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                screens: {
                    'xs': '375px',
                    'sm': '640px',
                    'md': '768px',
                    'lg': '1024px',
                    'xl': '1280px',
                }
            }
        }
    </script>
    
    <style>
        /* Additional responsive styles */
        @media (max-width: 375px) {
            .container-extra-small {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }
        
        /* Prevent text overflow */
        .break-anywhere {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        
        /* Better touch targets on mobile */
        @media (max-width: 640px) {
            .touch-target {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        /* Improve font rendering on mobile */
        html {
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4 sm:p-6">
    
    <div class="w-full max-w-md sm:max-w-lg md:max-w-xl bg-white rounded-2xl shadow-lg p-4 sm:p-6 md:p-8 text-center mx-auto">

        <!-- QR / Logo -->
        <div class="mb-4 sm:mb-6">
            <div class="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 mx-auto bg-purple-100 rounded-xl flex items-center justify-center">
                <span class="text-purple-600 font-bold text-xl sm:text-2xl md:text-3xl">
                    QR
                </span>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-2 break-anywhere px-2">
            {{ $bio->title }}
        </h1>

        <!-- Slug/URL -->
        <p class="text-xs sm:text-sm text-gray-500 mb-6 sm:mb-8 break-anywhere px-2">
            {{ url('/b/'.$bio->slug) }}
        </p>

        <!-- Content Blocks -->
        <div class="space-y-3 sm:space-y-4 md:space-y-5">
            @forelse($links as $link)
                {{-- TAGLINE --}}
                @if(($link['type'] ?? '') === 'tagline' && ($link['enabled'] ?? true))
                    <p class="text-sm sm:text-base text-gray-600 text-center break-anywhere px-2">
                        {{ $link['text'] }}
                    </p>
                @endif

                {{-- HEADING --}}
                @if(($link['type'] ?? '') === 'heading' && ($link['enabled'] ?? true))
                    @php
                        $tag   = $link['style'] ?? 'h5';
                        $color = $link['color'] ?? '#000000';
                    @endphp

                    <{{ $tag }} 
                        class="font-semibold text-center break-anywhere px-2"
                        style="color: {{ $color }}"
                    >
                        {{ $link['text'] }}
                    </{{ $tag }}>
                @endif

                {{-- LINK --}}
                @if(($link['type'] ?? '') === 'link' && ($link['enabled'] ?? true))
                    <a
                        href="{{ $link['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block w-full py-3 sm:py-4 px-4 rounded-xl bg-purple-600 text-white font-medium transition hover:opacity-90 active:opacity-80 touch-target text-sm sm:text-base break-anywhere"
                    >
                        {{ $link['text'] }}
                    </a>
                @endif

                {{-- TEXT (Rich Text) --}}
                @if(($link['type'] ?? '') === 'text' && ($link['enabled'] ?? true))
                    <div class="text-gray-700 text-sm sm:text-base leading-relaxed text-left break-anywhere px-2">
                        {!! $link['text'] !!}
                    </div>
                @endif

            @empty
                <p class="text-sm sm:text-base text-gray-400 text-center py-4 sm:py-6">
                    No content added yet
                </p>
            @endforelse
        </div>

        <!-- Footer -->
        <p class="mt-6 sm:mt-8 text-xs sm:text-sm text-gray-400">
            Powered by Tracklio
        </p>
    </div>

    <!-- Safe Area for notched phones -->
    <div class="safe-area-bottom"></div>

    <script>
        // iOS Safari 100vh fix
        function setVH() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);
        
        // Prevent zoom on input focus for iOS
        document.addEventListener('touchstart', function() {}, {passive: true});
        
        // Add CSS for safe areas
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                :root {
                    --safe-area-inset-top: env(safe-area-inset-top);
                    --safe-area-inset-bottom: env(safe-area-inset-bottom);
                    --safe-area-inset-left: env(safe-area-inset-left);
                    --safe-area-inset-right: env(safe-area-inset-right);
                }
                
                body {
                    padding-left: calc(1rem + var(--safe-area-inset-left, 0px));
                    padding-right: calc(1rem + var(--safe-area-inset-right, 0px));
                    padding-top: calc(1rem + var(--safe-area-inset-top, 0px));
                    padding-bottom: calc(1rem + var(--safe-area-inset-bottom, 0px));
                }
                
                .safe-area-bottom {
                    height: var(--safe-area-inset-bottom, 0px);
                    width: 100%;
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    z-index: -1;
                }
                
              
                
                /* Improve link tap highlight */
                a {
                    -webkit-tap-highlight-color: rgba(147, 51, 234, 0.2);
                    tap-highlight-color: rgba(147, 51, 234, 0.2);
                }
            </style>
        `);
    </script>

</body>
</html>