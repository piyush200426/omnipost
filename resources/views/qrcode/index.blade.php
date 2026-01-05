@extends('layouts.index')

@section('title', 'QR & Links')

@section('content')

<div
    x-data="{
        openModal: false,
        editMode: false,
        editId: null,
        originalUrl: '',
        label: ''
    }"
    class="space-y-6 md:space-y-8 p-4 md:p-0"
>

    {{-- ================= ENHANCED HEADER WITH GRADIENT ================= --}}
    <div class="bg-gradient-to-br from-gray-900 via-slate-900 to-gray-950 rounded-xl md:rounded-2xl p-4 sm:p-6 md:p-8 shadow-lg md:shadow-2xl overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-cyan-500/10 animate-gradient-x"></div>
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 sm:gap-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="qr-code" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-white mb-1 sm:mb-2">QR & Links Manager</h1>
                        <p class="text-slate-300 text-xs sm:text-sm md:text-base">Create, manage, and track QR codes with real-time analytics</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="openModal = true; editMode = false; editId = null; originalUrl=''; label='';"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 py-2.5 sm:px-5 sm:py-3 md:px-6 md:py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 group flex items-center justify-center gap-2 sm:gap-3 font-medium text-xs sm:text-sm md:text-base mt-4 lg:mt-0 w-full sm:w-auto">
                    <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 group-hover:rotate-90 transition-transform duration-300"></i>
                    <span>Create QR Code</span>
                </button>
            </div>

            {{-- Stats bar --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 md:gap-4 mt-4 sm:mt-6 md:mt-8 pt-3 sm:pt-4 md:pt-6 border-t border-white/10">
                <div class="flex items-center gap-1 sm:gap-2">
                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-xs sm:text-sm text-slate-300">Real-time tracking</span>
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <svg class="w-3 h-3 md:w-4 md:h-4 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-xs md:text-sm text-slate-300">Updated instantly</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= ENHANCED STATS GRID ================= --}}
    <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4 md:gap-6">
        @php
            $stats = [
                ['icon' => 'qr-code', 'color' => 'indigo', 'label' => 'Total QR Codes', 'value' => $totalQrs ?? 0],
                ['icon' => 'link-2', 'color' => 'purple', 'label' => 'Total Links', 'value' => $totalLinks ?? 0],
                ['icon' => 'scan-line', 'color' => 'blue', 'label' => 'QR Scans', 'value' => $totalQrScans ?? 0],
                ['icon' => 'mouse-pointer-click', 'color' => 'emerald', 'label' => 'Total Visits', 'value' => $totalVisits ?? 0],
                ['icon' => 'activity', 'color' => 'cyan', 'label' => 'Active QRs', 'value' => $activeQrs ?? 0],
            ];
        @endphp

        @foreach($stats as $stat)
        <div class="group bg-gradient-to-br from-white to-{{ $stat['color'] }}-50/30 rounded-xl md:rounded-2xl border border-{{ $stat['color'] }}-100 shadow sm:shadow-lg hover:shadow-lg md:hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 p-3 sm:p-4 md:p-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-500"></div>
            <div class="flex items-start justify-between mb-2 sm:mb-3 md:mb-4">
                <div class="min-w-0">
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1 truncate">{{ $stat['label'] }}</p>
                    <p class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900 truncate">{{ number_format($stat['value']) }}</p>
                </div>
                <div class="p-1.5 sm:p-2 md:p-3 bg-gradient-to-br from-{{ $stat['color'] }}-100 to-{{ $stat['color'] }}-50 rounded-xl group-hover:scale-110 transition-transform duration-300 shadow-sm flex-shrink-0 ml-2">
                    <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-{{ $stat['color'] }}-600"></i>
                </div>
            </div>
            <div class="pt-2 sm:pt-3 md:pt-4 border-t border-{{ $stat['color'] }}-100/50">
                <div class="w-full bg-{{ $stat['color'] }}-100 rounded-full h-1.5 md:h-2 mt-1 sm:mt-2">
                    <div class="bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-500 h-1.5 md:h-2 rounded-full transition-all duration-1000" 
                         style="width: {{ $stat['value'] > 0 ? min(($stat['value'] / 1000) * 100, 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ================= ENHANCED MAIN CONTENT ================= --}}
    <div class="bg-white rounded-xl md:rounded-2xl shadow-lg md:shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 to-white">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg md:text-xl font-bold text-slate-900 flex items-center gap-2 sm:gap-3">
                        <i data-lucide="grid" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-indigo-600"></i>
                        <span class="truncate">QR Codes List</span>
                    </h3>
                    <p class="text-slate-600 text-xs sm:text-sm md:text-base mt-1 truncate">{{ count($qrs) }} QR codes created • Last updated just now</p>
                </div>
                
                <div class="flex items-center gap-2 sm:gap-3 w-full md:w-auto">
                    <div class="relative flex-1 md:flex-none md:w-48 lg:w-64">
                        <input type="text" 
                               placeholder="Search..." 
                               class="pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 md:py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm w-full transition-all duration-300 hover:border-slate-400">
                        <i data-lucide="search" class="w-3 h-3 sm:w-4 sm:h-4 text-slate-400 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                    </div>
                    <button class="hidden md:flex items-center gap-2 px-3 sm:px-4 py-2 md:py-2.5 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors text-xs sm:text-sm font-medium hover:border-slate-400">
                        <i data-lucide="filter" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                        <span class="hidden sm:inline">Filter</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ================= ENHANCED TABLE (Responsive) ================= --}}
        <div class="overflow-x-auto -mx-0 sm:mx-0">
            <div class="min-w-[768px] sm:min-w-0">
                <table class="w-full text-xs sm:text-sm">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <i data-lucide="qr-code" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden xs:inline">QR Code</span>
                                </div>
                            </th>

                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <i data-lucide="link" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden sm:inline">Original URL</span>
                                </div>
                            </th>

                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <i data-lucide="external-link" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden sm:inline">Short Link</span>
                                </div>
                            </th>

                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center justify-center gap-1 sm:gap-2">
                                    <i data-lucide="mouse-pointer-click" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden sm:inline">Visits</span>
                                </div>
                            </th>

                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center justify-center gap-1 sm:gap-2">
                                    <i data-lucide="scan-line" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden sm:inline">QR Scans</span>
                                </div>
                            </th>

                            <th class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                <div class="flex items-center justify-end gap-1 sm:gap-2">
                                    <i data-lucide="settings" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    <span class="hidden sm:inline">Actions</span>
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($qrs as $qr)
                        <tr class="group hover:bg-gradient-to-r hover:from-indigo-50/30 hover:to-purple-50/30 transition-all duration-200">

                            {{-- QR CODE IMAGE WITH AVATAR --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4">
                                <div class="flex items-center gap-2 sm:gap-3 md:gap-4">
                                    <div class="relative group/qr">
                                        <img src="{{ asset('storage/' . $qr->qr_image_path) }}?v={{ $qr->updated_at?->timestamp ?? time() }}"
                                             class="w-10 h-10 sm:w-14 sm:h-14 md:w-20 md:h-20 rounded-xl md:rounded-2xl border-2 border-slate-200 group-hover/qr:border-indigo-300 transition-all duration-300 shadow-lg group-hover/qr:shadow-xl group-hover/qr:scale-105">
                                        <div class="absolute -top-1 -right-1 sm:-top-1 sm:-right-1 md:-top-2 md:-right-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-[10px] sm:text-xs px-1 py-0.5 sm:px-1.5 sm:py-0.5 md:px-2.5 md:py-1 rounded-full font-medium shadow">
                                            QR
                                        </div>
                                    </div>
                                    <div class="text-[10px] sm:text-xs text-slate-500 hidden sm:block">
                                        {{ Carbon\Carbon::parse($qr->created_at)->format('M d, Y') }}
                                    </div>
                                </div>
                            </td>

                            {{-- ORIGINAL URL --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4">
                                <div class="max-w-[120px] sm:max-w-xs md:max-w-md min-w-0">
                                    <p class="text-slate-900 font-medium truncate group-hover:text-indigo-700 transition-colors text-xs sm:text-sm md:text-base" title="{{ $qr->original_url }}">
                                        {{ Str::limit($qr->original_url, 20) }}
                                    </p>
                                    <p class="text-[10px] sm:text-xs text-slate-500 mt-1 flex items-center gap-1 truncate">
                                        <i data-lucide="globe" class="w-2.5 h-2.5 sm:w-3 sm:h-3 flex-shrink-0"></i>
                                        @if($qr->original_url)
                                            {{ parse_url($qr->original_url, PHP_URL_HOST) }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                    @if($qr->label)
                                    <span class="inline-block mt-1 sm:mt-2 px-1.5 py-0.5 sm:px-2 sm:py-1 md:px-3 md:py-1.5 bg-gradient-to-r from-indigo-100 to-indigo-50 text-indigo-700 text-[10px] sm:text-xs font-medium rounded-full border border-indigo-200 truncate max-w-full">
                                        <i data-lucide="tag" class="w-2.5 h-2.5 sm:w-3 sm:h-3 inline mr-1"></i>
                                        {{ Str::limit($qr->label, 12) }}
                                    </span>
                                    @endif
                                </div>
                            </td>

                            {{-- SHORT LINK WITH COPY --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4">
                                <div class="flex items-center gap-1 sm:gap-2 md:gap-3 min-w-0">
                                   <a href="{{ url('/q/'.$qr->short_code) }}" 
   target="_blank"
   class="text-xs sm:text-sm md:text-base truncate max-w-[80px] sm:max-w-[120px] md:max-w-[180px]">
    {{ url('/q/'.$qr->short_code) }}
</a>

<button
    type="button"
    onclick="copyToClipboard('{{ url('/q/'.$qr->short_code) }}')"
    class="text-slate-400 hover:text-indigo-600 p-1 sm:p-1.5 md:p-2 hover:bg-indigo-50 rounded-xl transition-all duration-200 hover:scale-110 flex-shrink-0"
    title="Copy link">
</button>                                </div>
                            </td>

                            {{-- VISITS WITH PROGRESS --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-base sm:text-lg md:text-xl font-bold text-slate-900">{{ $qr->visit_count }}</span>
                                    <div class="w-8 sm:w-12 md:w-16 h-1 sm:h-1.5 md:h-2 bg-slate-200 rounded-full overflow-hidden mt-0.5 sm:mt-1 md:mt-2">
                                        <div class="h-full bg-gradient-to-r from-emerald-500 to-green-500 rounded-full transition-all duration-1000" 
                                             style="width: {{ $qr->visit_count > 0 ? min(($qr->visit_count / max(1, $qrs->max('visit_count'))) * 100, 100) : 0 }}%"></div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5 sm:mt-1">Visits</p>
                                </div>
                            </td>

                            {{-- QR SCANS WITH PROGRESS --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-base sm:text-lg md:text-xl font-bold text-indigo-600">{{ $qr->qr_scan_count ?? 0 }}</span>
                                    <div class="w-8 sm:w-12 md:w-16 h-1 sm:h-1.5 md:h-2 bg-slate-200 rounded-full overflow-hidden mt-0.5 sm:mt-1 md:mt-2">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-1000" 
                                             style="width: {{ ($qr->qr_scan_count ?? 0) > 0 ? min((($qr->qr_scan_count ?? 0) / max(1, $qrs->max('qr_scan_count'))) * 100, 100) : 0 }}%"></div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5 sm:mt-1">Scans</p>
                                </div>
                            </td>

                            {{-- ENHANCED ACTION BUTTONS --}}
                            <td class="px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 md:py-4 text-right">
                                <div class="inline-flex items-center gap-0.5 sm:gap-1 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl p-0.5 md:p-1">

                                    {{-- VIEW --}}
                                    <a href="{{ route('qr-links.show', $qr->_id) }}"
                                       class="p-1.5 sm:p-2 md:p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200 hover:scale-110 group/action"
                                       title="View details">
                                        <i data-lucide="eye" class="w-3 h-3 sm:w-4 sm:h-4 group-hover/action:rotate-12 transition-transform"></i>
                                    </a>

                                    {{-- EDIT --}}
                                    <button
                                        type="button"
                                        @click="
                                            openModal = true;
                                            editMode = true;
                                            editId = '{{ $qr->_id }}';
                                            originalUrl = '{{ $qr->original_url }}';
                                            label = '{{ $qr->label }}';
                                        "
                                        class="p-1.5 sm:p-2 md:p-2.5 text-slate-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition-all duration-200 hover:scale-110 group/action"
                                        title="Edit">
                                        <i data-lucide="edit-3" class="w-3 h-3 sm:w-4 sm:h-4 group-hover/action:rotate-12 transition-transform"></i>
                                    </button>

                                    {{-- DOWNLOAD --}}
                                    <a
                                        href="{{ asset('storage/' . $qr->qr_image_path) }}"
                                        download="qr-{{ $qr->short_code }}.svg"
                                        class="p-1.5 sm:p-2 md:p-2.5 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-lg"
                                        title="Download SVG">
                                        <i data-lucide="download" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                    </a>

                                    {{-- DELETE --}}
                                    <form
                                        action="{{ route('qr-links.destroy', $qr->_id) }}"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this QR code? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 sm:p-2 md:p-2.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200 hover:scale-110 group/action"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4 group-hover/action:rotate-12 transition-transform"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 md:px-6 py-8 sm:py-12 md:py-20 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto px-2">
                                    <div class="w-12 h-12 sm:w-16 sm:h-16 md:w-24 md:h-24 bg-gradient-to-br from-slate-100 to-slate-50 rounded-xl md:rounded-2xl flex items-center justify-center mb-3 sm:mb-4 md:mb-6 shadow-lg">
                                        <i data-lucide="qr-code" class="w-6 h-6 sm:w-8 sm:h-8 md:w-12 md:h-12 text-slate-400"></i>
                                    </div>
                                    <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 mb-1 sm:mb-2 md:mb-3">No QR Codes Yet</h3>
                                    <p class="text-slate-600 text-center mb-4 sm:mb-6 md:mb-8 text-xs sm:text-sm md:text-base">
                                        Start by creating your first QR code. Track scans, visits, and analytics all in one place.
                                    </p>
                                    <button
                                        type="button"
                                        @click="openModal = true; editMode = false; editId = null; originalUrl=''; label='';"
                                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 py-2.5 sm:px-6 sm:py-3 md:px-8 md:py-4 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 group text-xs sm:text-sm md:text-base w-full sm:w-auto">
                                        <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 mr-1 sm:mr-2 inline group-hover:rotate-90 transition-transform"></i>
                                        Create Your First QR Code
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ENHANCED PAGINATION --}}
        @if(count($qrs) > 0)
        <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 border-t border-slate-100 bg-gradient-to-r from-slate-50/50 to-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs sm:text-sm text-slate-600">
                <div class="text-xs sm:text-sm">
                    Showing <span class="font-semibold text-slate-900">1-{{ count($qrs) }}</span> of <span class="font-semibold text-slate-900">{{ count($qrs) }}</span> QR codes
                </div>
                <div class="flex items-center gap-1 sm:gap-2">
                    <button class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 flex items-center justify-center border border-slate-300 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-colors">
                        <i data-lucide="chevron-left" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </button>
                    <button class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 flex items-center justify-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow text-xs sm:text-sm md:text-base">
                        1
                    </button>
                    <button class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 flex items-center justify-center border border-slate-300 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-colors">
                        <i data-lucide="chevron-right" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ================= ENHANCED MODAL ================= --}}
    <div
        x-show="openModal"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm">

        <div
            x-show="openModal"
            @click.away="openModal = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white w-full max-w-md rounded-xl md:rounded-2xl shadow-2xl transform transition-all overflow-hidden max-h-[90vh] overflow-y-auto mx-2">

            {{-- MODAL HEADER --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 sm:gap-3 md:gap-4 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
                            <i data-lucide="qr-code" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm sm:text-base md:text-lg font-bold text-white truncate"
                                x-text="editMode ? 'Edit QR Code' : 'Create QR Code'"></h2>
                            <p class="text-indigo-100 text-xs sm:text-sm mt-0.5 truncate"
                               x-text="editMode ? 'Update your QR code details' : 'Generate a new QR code'"></p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="openModal = false"
                        class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 flex items-center justify-center text-white/80 hover:text-white hover:bg-white/20 rounded-xl transition-colors flex-shrink-0 ml-2">
                        <i data-lucide="x" class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5"></i>
                    </button>
                </div>
            </div>

            <form
                :action="editMode
                    ? `/qr-links/${editId}/update`
                    : '{{ route('qr-links.store') }}'"
                method="POST"
                class="p-4 sm:p-6 md:p-8 space-y-4 sm:space-y-6">

                @csrf

                <div class="space-y-2 sm:space-y-3">
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700">
                        <div class="flex items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
                            <i data-lucide="link" class="w-3 h-3 sm:w-4 sm:h-4 text-indigo-500"></i>
                            <span>Destination URL</span>
                        </div>
                    </label>
                    <input
                        type="url"
                        name="original_url"
                        x-model="originalUrl"
                        required
                        placeholder="https://example.com/your-long-url"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all duration-300 text-xs sm:text-sm md:text-base hover:border-slate-300">
                    <p class="text-xs text-slate-500">The URL this QR code will redirect to</p>
                </div>

                <div class="space-y-2 sm:space-y-3">
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700">
                        <div class="flex items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
                            <i data-lucide="tag" class="w-3 h-3 sm:w-4 sm:h-4 text-indigo-500"></i>
                            <span>Label (Optional)</span>
                        </div>
                    </label>
                    <input
                        type="text"
                        name="label"
                        x-model="label"
                        placeholder="e.g., Marketing Campaign, Event QR"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all duration-300 text-xs sm:text-sm md:text-base hover:border-slate-300">
                    <p class="text-xs text-slate-500">Add a label to easily identify this QR code</p>
                </div>

                {{-- PREVIEW SECTION --}}
                <div x-show="originalUrl" class="pt-3 sm:pt-4 border-t border-slate-100 space-y-2 sm:space-y-3">
                    <p class="text-xs sm:text-sm font-medium text-slate-700">Preview</p>
                    <div class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl p-3 sm:p-4 border border-slate-200">
                        <p class="text-xs sm:text-sm text-slate-600 truncate">
                            <span class="font-medium">URL:</span> 
                            <span x-text="originalUrl" class="truncate block"></span>
                        </p>
                        <p x-show="label" class="text-xs sm:text-sm text-slate-600 mt-1 sm:mt-2">
                            <span class="font-medium">Label:</span> 
                            <span x-text="label" class="truncate block"></span>
                        </p>
                    </div>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex justify-end gap-2 sm:gap-3 md:gap-4 pt-4 sm:pt-6 border-t border-slate-100">
                    <button
                        type="button"
                        @click="openModal = false"
                        class="px-3 py-2 sm:px-4 sm:py-2.5 md:px-6 md:py-3 border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 hover:border-slate-400 transition-all duration-300 font-medium hover:scale-105 text-xs sm:text-sm md:text-base">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-3 py-2 sm:px-4 sm:py-2.5 md:px-6 md:py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 group text-xs sm:text-sm md:text-base">
                        <span x-text="editMode ? 'Update QR Code' : 'Generate QR Code'"></span>
                        <i data-lucide="arrow-right" class="w-3 h-3 sm:w-4 sm:h-4 ml-1 sm:ml-2 inline group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

{{-- COPY TO CLIPBOARD FUNCTION --}}
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-3 sm:top-4 md:top-6 right-3 sm:right-4 md:right-6 z-50 px-3 py-2 sm:px-4 sm:py-2.5 md:px-5 md:py-3.5 rounded-xl shadow-xl text-white font-medium transform transition-all duration-300 translate-x-full bg-gradient-to-r from-emerald-500 to-green-500';
        toast.innerHTML = `
            <div class="flex items-center gap-1 sm:gap-2 md:gap-3">
                <div class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check" class="w-2.5 h-2.5 sm:w-3 sm:h-3 md:w-4 md:h-4 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-xs sm:text-sm md:text-base truncate">Link copied!</p>
                    <p class="text-[10px] sm:text-xs md:text-sm text-emerald-100 truncate">Ready to share</p>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.remove('translate-x-full'), 10);
        
        // Remove after 2 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
        
        // Initialize Lucide icon
        if (window.lucide) {
            lucide.createIcons();
        }
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>

<style>
@keyframes gradient-x {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient-x {
    background-size: 200% 200%;
    animation: gradient-x 15s ease infinite;
}

/* Responsive table container */
@media (max-width: 640px) {
    .overflow-x-auto {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .min-w-\[768px\] {
        min-width: 768px;
    }
}

/* Responsive breakpoints */
@media (min-width: 425px) {
    .xs\:grid-cols-2 {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Touch-friendly buttons on mobile */
@media (max-width: 768px) {
    button:not(.action-button),
    a[role="button"],
    .action-button {
        min-height: 36px;
        min-width: 36px;
    }
    
    .modal-button {
        min-height: 40px;
    }
}

/* Better table readability on mobile */
@media (max-width: 640px) {
    table td, table th {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    .action-buttons-container {
        display: flex;
        flex-wrap: nowrap;
    }
}

/* Prevent text overflow */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Modal responsive adjustments */
@media (max-width: 640px) {
    .modal-content {
        max-width: 95%;
        margin: 0 auto;
    }
}

/* Smooth scrolling for mobile */
html {
    -webkit-overflow-scrolling: touch;
}

/* Better tap highlight for mobile */
a, button {
    -webkit-tap-highlight-color: rgba(79, 70, 229, 0.2);
}

/* Hide/show elements based on screen size */
@media (max-width: 480px) {
    .hidden-xs {
        display: none !important;
    }
}

@media (max-width: 640px) {
    .hidden-sm {
        display: none !important;
    }
}

/* Progress bar responsive */
.progress-bar {
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

/* QR code image responsive */
.qr-image {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.qr-image:hover {
    transform: scale(1.05);
}

/* Action buttons hover effect */
.group\/action:hover i {
    animation: bounce 0.3s ease;
}

@keyframes bounce {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Smooth scrollbar for desktop */
@media (min-width: 768px) {
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #4f46e5, #7c3aed);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #4338ca, #6d28d9);
    }
}

/* Mobile-safe hover effects */
@media (hover: hover) and (pointer: fine) {
    .hover-effect:hover {
        transform: translateY(-1px);
    }
}

/* Loading states */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Safe area for notched phones */
@media (max-width: 768px) {
    body {
        padding-left: env(safe-area-inset-left, 0);
        padding-right: env(safe-area-inset-right, 0);
    }
}
</style>

@push('scripts')
<script>
window.downloadPngFromSvg = function (svgUrl, filename) {
    fetch(svgUrl)
        .then(r => r.text())
        .then(svg => {
            if (!svg.includes('<svg')) {
                alert('SVG load nahi hui');
                return;
            }

            const img = new Image();
            const blob = new Blob([svg], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);

            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = 800;
                canvas.height = 800;

                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, 800, 800);
                ctx.drawImage(img, 0, 0, 800, 800);

                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = `qr-${filename}.png`;
                a.click();

                URL.revokeObjectURL(url);
            };

            img.src = url;
        })
        .catch(() => alert('Fetch fail ho gaya'));
};
</script>
@endpush

@endsection