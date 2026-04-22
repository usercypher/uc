<?php

class Shared_Lib_Session {
    function init($config) {
        if (session_id() == '') {
            session_name(isset($config['name']) ? $config['name'] : 'PHP_SESSION_DEFAULT');
        }
    }

    function open() {
        if (session_id() == '') {
            session_start();
        }
    }

    function close() {
        if (session_id() != '') {
            session_write_close();
        }
    }

    function destroy() {
        if (session_id() != '') {
            $this->clear();
            session_destroy();
        }
    }

    function set($key, $value) {
        if (session_id() == '') {
            session_start();
        }

        $_SESSION[$key] = $value;
    }

    function get($key) {
        if (session_id() == '') {
            session_start();
        }

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    function remove($key) {
        if (session_id() == '') {
            session_start();
        }

        $value = null;

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        return $value;
    }

    function clear() {
        if (session_id() != '') {
            session_unset();
        }
    }
}
