<?php

class Stat {
    private static $memory = array();
    private static $time = array();
    private static $totalTime = 0;
    private static $startTime = 0;

    public static function memoryLog($title) {
        array_push(self::$memory, 'Memory usage during (' . $title . '): ' . memory_get_usage() . ' bytes' . PHP_EOL);
    }

    public static function memoryShow() {
        foreach (self::$memory as $m) {
            echo nl2br($m);
        }
    }

    public static function timeStart() {
        self::$startTime = microtime(true);
    }

    public static function timeEnd($title) {
        $endTime = microtime(true);
        $elapsedTime = $endTime - self::$startTime;
        array_push(self::$time, 'Elapsed time (' . $title . '): ' . $elapsedTime . ' seconds' . PHP_EOL);

        //self::memoryLog($title);
        self::$totalTime += $elapsedTime;
    }

    public static function timeShow() {
        foreach (self::$time as $t) {
            echo nl2br($t);
        }
        if (self::$totalTime != 0) {
            echo nl2br('Elapsed time (overall): ' . self::$totalTime . ' seconds' . PHP_EOL);
        }
    }
}