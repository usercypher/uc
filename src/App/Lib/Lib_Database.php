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
    }

    function disconnect() {
        $this->conn = null;
    }

    function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    function commit() {
        return $this->conn->commit();
    }

    function rollBack() {
        return $this->conn->rollBack();
    }

    function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    function query($query, $params) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            $error = $this->conn->errorInfo();
            trigger_error('500|Prepare failed: ' . $error[2]);
            return false;
        }

        $typeMap = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'null' => PDO::PARAM_NULL,
            'resource' => PDO::PARAM_LOB,
        );

        $i = 1;

        foreach ($params as $value) {
            $type = isset($typeMap[gettype($value)]) ? $typeMap[gettype($value)] : PDO::PARAM_STR;
            $stmt->bindValue($i++, $value, $type);
        }

        if (!$stmt->execute()) {
            $error = $this->conn->errorInfo();
            trigger_error('500|Execute failed: ' . $error[2]);
            return false;
        }

        return $stmt;
    }

    function fetch($stmt) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function fetchAll($stmt) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
