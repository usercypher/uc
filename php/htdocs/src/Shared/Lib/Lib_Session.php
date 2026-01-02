<?php

class Lib_Session {
    function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    function unset($key) {
        $value = null;

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        return $value;
    }

    function start() {
        if (session_id() == '') {
            session_start();
        }
    }

    function destroy() {
        session_unset();
        session_destroy();
    }

    function regenerate() {
        session_regenerate_id(true);
    }

    function close() {
        session_write_close();
    }

    function id() {
        return session_id();
    }

    function name($name) {
        if (session_id() == '') {
            session_name($name);
        }
    }
}