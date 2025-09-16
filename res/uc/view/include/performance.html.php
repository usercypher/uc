<div>
    <h3>Quick Speed Test</h3>

    <p><strong>Page Load Time (Client):</strong> <span id="client-load-time">--</span> seconds</p>
    <p><strong>Time To First Byte (Client):</strong> <span id="client-ttfb">--</span> seconds</p>

    <p><strong>Execution Time (Server):</strong> <span id="server-exec-time">--</span> ms</p>
    <p><strong>Memory Usage (Server):</strong> <span id="server-memory-usage">--</span> KB</p>
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
    
            const serverExecTime = getCookieValue('server_exec_time_ms');
            const serverMemory = getCookieValue('server_memory_usage_kb');
    
            document.getElementById('server-exec-time').textContent = serverExecTime ?? '--';
            document.getElementById('server-memory-usage').textContent = serverMemory ?? '--';
        }
    
        updateMetrics();
    });
</script>
