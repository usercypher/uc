<?php

class Shared_Lib_Session {
    var $name, $id;

    function args($args) {
        $this->init(isset($args[0]) ? $args[0] : array());
    }

    function init($config = array()) {
        if (session_id() == '') {
            $this->name = isset($config['name']) ? $config['name'] : 'PHP_SESSION_DEFAULT';
            session_name($this->name);
            $this->id = isset($config['id']) ? session_id($config['id']) : session_id();
        }
    }

    function connect() {
        if (session_id() == '') {
            session_start();
        }
    }

    function disconnect() {
        if (session_id() != '') {
            session_write_close();
        }
    }

    function hasConnection() {
        return session_id() != '';
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

    function del($key) {
        unset($_SESSION[$key]);
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
