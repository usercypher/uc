<?php

class Shared_Lib_Assert {
    function isEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Shared_Lib_Assert_Exception("Assertion failed: $message\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
        }
    }

    function isIn($expected, $actual, $message = '') {
        if (!in_array($actual, $expected)) {
            throw new Shared_Lib_Assert_Exception("Assertion failed: $message\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
        }
    }

    function isTrue($condition, $message = '') {
        if (!$condition) {
            throw new Shared_Lib_Assert_Exception("Assertion failed: $message");
        }
    }

    function isFalse($condition, $message = '') {
        if ($condition) {
            throw new Shared_Lib_Assert_Exception("Assertion failed: $message");
        }
    }

    function isThrows(callable $fn, $exceptionClass, $message = '') {
        try {
            $fn();
        } catch (Shared_Lib_Assert_Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            if (!$e instanceof $exceptionClass) {
                throw new Shared_Lib_Assert_Exception(
                    "Expected $exceptionClass but got " . get_class($e) . ". $message"
                );
            }
            return;
        }
        throw new Shared_Lib_Assert_Exception("Expected $exceptionClass but no exception was thrown. $message");
    }
}

class Shared_Lib_Assert_Exception extends Exception {}
