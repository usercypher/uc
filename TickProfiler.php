<?php

class TickProfiler {
    var $eol = "\n";
    var $file;
    var $timeStart;
    var $timeTotal;
    var $memoryStart;
    var $memoryTotal;
    var $messages;

    function init($file) {
        $this->file = dirname(__FILE__) . '/' . $file;
        $this->timeStart = $this->microtime(true);
        $this->timeTotal = 0;
        $this->memoryStart = 0;
        $this->memoryTotal = 0;
        $this->handler('PHP');
        register_tick_function(array($this, 'handler'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    function handler($comment = '') {
        $this->messages[] = $this->get($comment);
        $this->timeStart = $this->microtime(true);
        $this->memoryStart = memory_get_usage();
    } 

    function get($comment = '') {
        $timeCurrent = $this->microtime(true);
        $memoryCurrent = memory_get_usage();

        $timeElapse = $timeCurrent - $this->timeStart;
        $memoryUsage = $memoryCurrent - $this->memoryStart;
    
        $formattedTime = sprintf("%10.6f s", $timeElapse);
        $formattedMemory = sprintf("%9.2f KB", $memoryUsage / 1024);

        $f = debug_backtrace();
        $line = isset($f[1]['line']) ? $f[1]['line'] : 0;
        $file = isset($f[1]['file']) ? $f[1]['file'] : 'No file';

        $message = "  [$formattedTime] [$formattedMemory] [line: " . sprintf("%5.0f", $line) . "] $file [$comment]";

        $this->timeTotal += ($timeCurrent - $this->timeStart);
        $this->memoryTotal += $memoryUsage;

        return $message;
    }

    function shutdown() {
        $this->handler('shutdown');

        list($micro, $time) = $this->microtime();

        $formattedTime = sprintf("Total: %10.6f s", $this->timeTotal);
        $formattedMemory = sprintf("Total: %9.2f KB", $this->memoryTotal / 1024);
    
        $stamp = '[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'No uri';
        if ($fp = fopen($this->file, 'a')) {
            fwrite($fp, (string) ($stamp . 'uri: ' . $uri . $this->eol . implode($this->eol, $this->messages) . $this->eol . $this->eol . "[$formattedTime] [$formattedMemory]" . $this->eol . $this->eol));
            fclose($fp);
        }
    }

    function microtime($combined = false) {
        $mt = explode(' ', microtime());
        return ($combined) ? ((float) $mt[1] + (float) $mt[0]) : array((float) $mt[0], (float) $mt[1]);
    }

}