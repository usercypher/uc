<?php

class Lib_Database {
    var $conn = array();

    function connect($config = array(), $key = '_') {
        if (!isset($this->conn[$key])) {
            $host = isset($config['host']) ? $config['host'] : 'localhost';
            $port = isset($config['port']) ? $config['port'] : 3306;
            $name = isset($config['name']) ? $config['name'] : '';
            $user = isset($config['user']) ? $config['user'] : '';
            $pass = isset($config['pass']) ? $config['pass'] : '';
            $time = isset($config['time']) ? $config['time'] : '+00:00';

            $this->conn[$key] = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name, $user, $pass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
            ));
            $this->conn[$key]->exec('SET time_zone = "' . $time . '"');
        }
        return $this->conn[$key];
    }

    function disconnect($key = '_') {
        if (isset($this->conn[$key])) unset($this->conn[$key]);
    }

    function disconnectAll() {
        $this->conn = array();
    }

    function hasConnection($key = '_') {
        return isset($this->conn[$key]);
    }
}
?>