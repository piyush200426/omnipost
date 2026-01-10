{{-- STATISTICS TAB --}}
<div class="space-y-10" id="statisticsTab">

    {{-- ================= OVERVIEW ================= --}}
    <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-6">
            Overview
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- TOTAL --}}
            <div class="flex items-center gap-4 p-5 rounded-xl border bg-gray-50">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    👁️
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Clicks</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $stats['total_clicks'] ?? 0 }}
                    </p>
                </div>
            </div>

            {{-- UNIQUE --}}
            <div class="flex items-center gap-4 p-5 rounded-xl border bg-gray-50">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    👤
                </div>
                <div>
                    <p class="text-xs text-gray-500">Unique Clicks</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $stats['unique_clicks'] ?? 0 }}
                    </p>
                </div>
            </div>

            {{-- COUNTRY --}}
            <div class="flex items-center gap-4 p-5 rounded-xl border bg-gray-50">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    🌍
                </div>
                <div>
                    <p class="text-xs text-gray-500">Top Country</p>
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        {{ $stats['top_country'] ?? 'Unknown' }}
                    </p>
                </div>
            </div>

            {{-- CITY --}}
            <div class="flex items-center gap-4 p-5 rounded-xl border bg-gray-50">
                <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                    🏙️
                </div>
                <div>
                    <p class="text-xs text-gray-500">Top City</p>
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        {{ $stats['top_city'] ?? 'Unknown' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= COUNTRIES & CITIES ================= --}}
    <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-6">
            Countries & Cities
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b text-left">
                        <th class="py-3 font-medium">Country</th>
                        <th class="py-3 font-medium">City</th>
                        <th class="py-3 font-medium text-right">Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['locations'] ?? [] as $row)
                        <tr class="border-b last:border-0 hover:bg-gray-50">
                            <td class="py-3">{{ $row->country ?? 'Unknown' }}</td>
                            <td class="py-3 text-gray-600">{{ $row->city ?? '—' }}</td>
                            <td class="py-3 text-right font-semibold">
                                {{ $row->total }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-400">
                                No location data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= DEVICES + BROWSERS ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- DEVICES --}}
        <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-5">
                Devices
            </h2>

            <div class="space-y-2">
                @forelse($stats['devices'] ?? [] as $row)
                    <div class="flex items-center justify-between px-4 py-3 rounded-lg border bg-gray-50">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $row->device ?? 'Unknown' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900">
                            {{ $row->total }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No device data</p>
                @endforelse
            </div>
        </div>

        {{-- BROWSERS --}}
        <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-5">
                Browsers
            </h2>

            <div class="space-y-2">
                @forelse($stats['browsers'] ?? [] as $row)
                    <div class="flex items-center justify-between px-4 py-3 rounded-lg border bg-gray-50">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $row->browser ?? 'Unknown' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900">
                            {{ $row->total }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No browser data</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ================= REFERRERS ================= --}}
    <div class="bg-white rounded-2xl border shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-5">
            Referrers
        </h2>

        <div class="space-y-2">
            @forelse($stats['referrers'] ?? [] as $row)
                <div class="flex items-center justify-between px-4 py-3 rounded-lg border bg-gray-50">
                    <span class="text-sm text-gray-700 truncate max-w-[70%]">
                        {{ $row->referrer ?: 'Direct / Unknown' }}
                    </span>
                    <span class="text-sm font-bold text-gray-900">
                        {{ $row->total }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No referrer data</p>
            @endforelse
        </div>
    </div>

</div>
