<div>
    <h3>Quick Speed Test</h3>

    <p><strong>Page Load Time (browser):</strong> <span id="client-load-time">--</span> seconds</p>
    <p><strong>Time To First Byte (browser):</strong> <span id="client-ttfb">--</span> seconds</p>

    <p><strong>Execution Time (php):</strong> <span id="php-exec-time">--</span> ms</p>
    <p><strong>Memory Usage (php):</strong> <span id="php-memory-usage">--</span> KB</p>
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
    
            document.getElementById('client-load-time').textContent = loadTime.toFixed(2);
            document.getElementById('client-ttfb').textContent = ttfb.toFixed(2);
    
            const serverExecTime = getCookieValue('php_exec_time_ms');
            const serverMemory = getCookieValue('php_memory_usage_kb');
    
            document.getElementById('php-exec-time').textContent = serverExecTime ?? '--';
            document.getElementById('php-memory-usage').textContent = serverMemory ?? '--';
        }
    
        updateMetrics();
    });
</script>
