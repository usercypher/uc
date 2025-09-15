
<div>
    <h3>Quick Speed Test</h3>
    <p><strong>Page Load Time:</strong> <span id="load-time">--</span> seconds</p>
    <p><strong>Time To First Byte (TTFB):</strong> <span id="ttfb">--</span> seconds</p>
    <p><strong>Server Processing Time:</strong> <span id="server-speed">--</span> ms</p>
    <p><strong>Server Memory Usage:</strong> <span id="server-memory">--</span> KB</p>
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

            const serverSpeed = getCookieValue('server_speed');
            const serverMemory = getCookieValue('server_memory');

            document.getElementById('server-speed').textContent = serverSpeed ? `${serverSpeed}` : '--';
            document.getElementById('server-memory').textContent = serverMemory ? `${serverMemory}` : '--';
        }

        updateMetrics();
    });
</script>
