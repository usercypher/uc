<?php

class Shared_Lib_Session {
    function set($key, $value) {
        $this->start();

        $_SESSION[$key] = $value;
    }

    function get($key) {
        $this->start();

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    function unset($key) {
        $this->start();

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
        if (session_id() != '') {
            session_unset();
            session_destroy();
        }
    }

    function regenerate() {
        if (session_id() != '') {
            session_regenerate_id(true);
        }
    }

    function close() {
        if (session_id() != '') {
            session_write_close();
        }
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
