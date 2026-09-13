<?php

class Shared_Lib_Assert {
    function isEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: $message\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
        }
    }

    function isTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }

    function isFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception("Assertion failed: $message");
        }
    }

    function isThrows(callable $fn, $exceptionClass, $message = '') {
        try {
            $fn();
            throw new Exception("Expected $exceptionClass but no exception was thrown. $message");
        } catch (Exception $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Exception("Expected $exceptionClass but got " . get_class($e) . ". $message");
            }
        }
    }
}
