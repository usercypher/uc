<?php

class Session {
    public function __construct() {
        $this->start();
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function unset($key) {
        $value = null;

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        return $value;
    }

    public function start() {
        if (version_compare(phpversion(),'5.4.0','>')){
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } else{
            if(session_id() == '') {
                session_start();
            }
        }
    }

    public function destroy() {
        session_unset();
        session_destroy();
    }

    public function regenerate() {
        session_regenerate_id(true);
    }

    public function close() {
        session_write_close();
    }

    public function id() {
        return session_id();
    }
}