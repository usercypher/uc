<?php

class Shared_Lib_Session {
    var $config;

    function connect() {
        if (session_id() == '') {
            session_name($this->config['name']);
            session_id($this->config['id']);
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
        $this->connect();
        $this->clear();
        session_destroy();
    }

    function set($key, $value) {
        $this->connect();
       
        $_SESSION[$key] = $value;
    }

    function get($key) {
        $this->connect();

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    function del($key) {
        $this->connect();
        unset($_SESSION[$key]);
    }

    function remove($key) {
        $this->connect();

        $value = null;

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        return $value;
    }

    function clear() {
        $this->connect();
        session_unset();
    }
}
