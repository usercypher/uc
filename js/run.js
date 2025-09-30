(function() {
    window.run = function(condition, callback, options) {
        options = options || {};
        var startTime = new Date().getTime();
        var interval = options.interval || 100;
        var timeout = options.timeout || 30000;
        var intervalId = setInterval(function () {
            try {
                if (condition()) {
                    clearInterval(intervalId);
                    callback();
                } else if (new Date().getTime() - startTime >= timeout) {
                    clearInterval(intervalId);
                    console.log('run: timeout reached without condition being true.');
                }
            } catch (e) {
                clearInterval(intervalId);
                console.log('run: error in condition or callback: ' + e);
            }
        }, interval);
    };
})();
