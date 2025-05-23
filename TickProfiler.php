<?php

class TickProfiler {
    var $eol = "\n";
    var $file, $memoryStart, $memoryTotal, $timeStart, $timeTotal, $ticks;

    function init($file) {
        $this->file = dirname(__FILE__) . '/' . $file;

        $this->ticks = array();

        $this->memoryTotal = 0;
        $this->timeTotal = 0;

        $this->memoryStart = 0;
        $this->timeStart = $this->microtime(true);

        register_tick_function(array($this, 'handler'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    function handler($comment = '') {
        $this->tick(memory_get_usage(), $this->microtime(true), $comment);
        $this->memoryStart = memory_get_usage();
        $this->timeStart = $this->microtime(true);
    } 

    function tick($memoryCurrent, $timeCurrent, $comment = '') {
        $memoryUsage = $memoryCurrent - $this->memoryStart;
        $timeElapse = $timeCurrent - $this->timeStart;

        $this->memoryTotal += $memoryUsage;
        $this->timeTotal += $timeElapse;

        $formattedMemory = sprintf("%9.2f KB", $memoryUsage / 1024);
        $formattedTime = sprintf("%10.6f s", $timeElapse);

        $db = debug_backtrace();
        $pad = str_repeat('-', count($db) - 2);
        $line = isset($db[1]['line']) ? $db[1]['line'] : 0;
        $file = isset($db[1]['file']) ? $db[1]['file'] : 'No file';
        $funtion = isset($db[2]['function']) ? $db[2]['function'] : 'No function';
        $class = isset($db[2]['class']) ? $db[2]['class'] . (isset($db[2]['type']) ? $db[2]['type'] : '') : '';

        $this->ticks[] = "  [$formattedTime] [$formattedMemory] [line: " . sprintf("%5.0f", $line) . "] [$pad] $file [$class$funtion] $comment";
    }

    function shutdown() {
        $this->ticks[0] .= '[tick init] [php init state]';
        $this->tick(memory_get_usage(), $this->microtime(true), '[shutdown]');

        list($micro, $time) = $this->microtime();

        $formattedMemory = sprintf("Total: %9.2f KB", $this->memoryTotal / 1024);
        $formattedTime = sprintf("Total: %10.6f s", $this->timeTotal);
    
        $stamp = '[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'No uri';
        if ($fp = fopen($this->file, 'a')) {
            fwrite($fp, (string) ($stamp . 'uri: ' . $uri . $this->eol . implode($this->eol, $this->ticks) . $this->eol . $this->eol . "[$formattedTime] [$formattedMemory]" . $this->eol . $this->eol));
            fclose($fp);
        }
    }

    function microtime($combined = false) {
        $mt = explode(' ', microtime());
        return ($combined) ? ((float) $mt[1] + (float) $mt[0]) : array((float) $mt[0], (float) $mt[1]);
    }

}