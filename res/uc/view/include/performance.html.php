
<div>
    <h3>Quick Speed Test</h3>
    <p><strong>Page Load Time:</strong> <span id="load-time">--</span> seconds</p>
    <p><strong>Time To First Byte (TTFB):</strong> <span id="ttfb">--</span> seconds</p>
</div>
<script>
    window.addEventListener('load', function() {
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
        }

        updateMetrics();
    });
</script>

