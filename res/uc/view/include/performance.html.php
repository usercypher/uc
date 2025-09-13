
<!-- 

ids: 
- load-time
- ttfb

-->

<div id="perf-stats" style="
    position: fixed;
    bottom: 10px;
    right: 10px;
    background: #000;
    opacity: 0.75;
    color: #fff;
    font-family: monospace;
    font-size: 12px;
    padding: 8px 12px;
    z-index: 10000;
">
    <div><strong>Load:</strong> <span id="load-time">--</span> s</div>
    <div><strong>TTFB:</strong> <span id="ttfb">--</span> s</div>
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

