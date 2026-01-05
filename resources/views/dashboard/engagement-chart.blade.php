@php
    /*
    |--------------------------------------------------------------------------
    | SAFE FALLBACK (Controller se data na aaye to bhi chart dikhe)
    |--------------------------------------------------------------------------
    */
    $engData   = $engagementData ?? [0,0,0,0,0,0,0];
    $weekDays = $weekLabels ?? ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
@endphp

<div class="bg-white rounded-xl shadow border p-6 pb-10 w-full">
    <h2 class="font-semibold mb-5 text-gray-800 text-lg md:text-xl">
        Engagement Trends
    </h2>

    <div id="engagementChart" class="w-full" style="height:320px"></div>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Prevent duplicate render (IMPORTANT)
    if (window.engagementChartRendered) return;
    window.engagementChartRendered = true;

    const engagementData = @json($engData);
    const weekLabels     = @json($weekDays);

    const options = {
        chart: {
            type: "area",
            height: 320,
            toolbar: { show: false },
            animations: {
                enabled: true,
                easing: "easeinout",
                speed: 600
            }
        },

        series: [{
            name: "Engagement",
            data: engagementData
        }],

        xaxis: {
            categories: weekLabels,
            labels: {
                style: {
                    colors: "#64748b",
                    fontSize: "12px"
                }
            }
        },

        stroke: {
            curve: "smooth",
            width: 3
        },

        colors: ["#4C6FFF"],

        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },

        markers: {
            size: 4,
            strokeColors: "#ffffff",
            strokeWidth: 2,
            hover: { size: 6 }
        },

        grid: {
            borderColor: "#E2E8F0",
            strokeDashArray: 5
        },

        dataLabels: { enabled: false },

        tooltip: {
            theme: "light",
            y: {
                formatter: val => val.toLocaleString()
            }
        }
    };

    const chart = new ApexCharts(
        document.querySelector("#engagementChart"),
        options
    );

    chart.render();
});
</script>
@endpush
