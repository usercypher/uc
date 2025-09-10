<?php

class Lib_Database {
    var $app;
    var $host, $port, $name, $user, $pass, $conn, $time = '+00:00';

    public function args($args) {
        list(
            $this->app
        ) = $args;

        $this->host = $this->app->getEnv('DB_HOST');
        $this->port = $this->app->getEnv('DB_PORT');
        $this->name = $this->app->getEnv('DB_NAME');
        $this->user = $this->app->getEnv('DB_USER');
        $this->pass = $this->app->getEnv('DB_PASS');
        $this->time = $this->app->getEnv('DB_TIME', '+00:00');
    }

    function connect() {
        if (!$this->conn) {
            $this->conn = new PDO('mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->name, $this->user, $this->pass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
            ));
            $this->conn->exec('SET time_zone = "' . $this->time . '"');
        }
        return $this->conn;
    }

    function disconnect() {
        $this->conn = null;
    }
}
?>
