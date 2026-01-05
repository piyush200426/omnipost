<div class="bg-white rounded-xl shadow border p-6 pb-10 w-full">
    <h2 class="font-semibold mb-5 text-gray-800 text-lg md:text-xl">
        Platform Performance
    </h2>

    <div id="platformChart" class="w-full" style="height: 320px;"></div>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {

    let chart;

    function renderChart(platforms, engagement, reach) {
        const options = {
            chart: {
                type: "bar",
                height: 320,
                toolbar: { show: false }
            },

            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: "55%",
                    borderRadius: 6
                }
            },

            series: [
                { name: "Engagement", data: engagement },
                { name: "Reach", data: reach }
            ],

            colors: ["#4C6FFF", "#CBD5E1"],

            xaxis: {
                categories: platforms
            },

            grid: {
                borderColor: "#E2E8F0",
                strokeDashArray: 5
            },

            legend: {
                position: "bottom"
            }
        };

        if (chart) {
            chart.updateOptions(options);
        } else {
            chart = new ApexCharts(
                document.querySelector("#platformChart"),
                options
            );
            chart.render();
        }
    }

    function fetchPlatformData() {
        fetch("/dashboard/platform-performance")
            .then(res => res.json())
            .then(data => {
                renderChart(
                    data.platforms,
                    data.engagement,
                    data.reach
                );
            })
            .catch(console.error);
    }

    // INITIAL LOAD
    fetchPlatformData();

    // AUTO REFRESH (REAL-TIME FEEL)
    setInterval(fetchPlatformData, 15000); // every 15 sec
});
</script>
@endpush
