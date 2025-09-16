<div>
    <h3>Quick Speed Test</h3>
    <p><strong>Page Load Time:</strong> <span id="load-time">--</span> seconds</p>
    <p><strong>Time To First Byte (TTFB):</strong> <span id="ttfb">--</span> seconds</p>
    <p><strong>App Execution Time:</strong> <span id="app-exec-time-ms">--</span> ms</p>
    <p><strong>App Memory Usage:</strong> <span id="app-memory-kb">--</span> KB</p>
</div>

<script>
    function getCookieValue(name) {
        return document.cookie
            .split('; ')
            .find(row => row.startsWith(name + '='))
            ?.split('=')[1];
    }

    window.addEventListener('load', function () {
        const timing = performance.timing;

        function updateMetrics() {
            const loadEventEnd = timing.loadEventEnd;

            if (!loadEventEnd || loadEventEnd === 0) {
                return setTimeout(updateMetrics, 50);
            }

            const loadTime = (loadEventEnd - timing.navigationStart) / 1000;
            const ttfb = (timing.responseStart - timing.navigationStart) / 1000;

            document.getElementById('load-time').textContent = loadTime.toFixed(2);
            document.getElementById('ttfb').textContent = ttfb.toFixed(2);

            const appExecTimeMs = getCookieValue('app_exec_time_ms');
            const appMemoryKb = getCookieValue('app_memory_kb');

            document.getElementById('app-exec-time-ms').textContent = appExecTimeMs ? appExecTimeMs : '--';
            document.getElementById('app-memory-kb').textContent = appMemoryKb ? appMemoryKb : '--';
        }

        updateMetrics();
    });
</script>
