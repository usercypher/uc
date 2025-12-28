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
            $timeout = isset($config['timeout']) ? (int)$config['timeout'] : 5;

            $this->conn[$key] = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name, $user, $pass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                PDO::ATTR_TIMEOUT => $timeout
            ));
            $this->conn[$key]->exec('SET time_zone = "' . $time . '"');
        }
        return $key;
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

    // db operations

    function begin($key = '_') {
        return $this->conn[$key]->beginTransaction();
    }

    function commit($key = '_') {
        return $this->conn[$key]->commit();
    }

    function rollback($key = '_') {
        return $this->conn[$key]->rollBack();
    }

    function lastInsertId($key = '_') {
        return $this->conn[$key]->lastInsertId();
    }

    function execute($query, $key = '_') {
        return $this->conn[$key]->exec($query);
    }

    function stmt($query, $param, $key = '_') {
        $stmt = $this->conn[$key]->prepare($query);
        if (!$stmt) {
            $error = $this->conn[$key]->errorInfo();
            trigger_error('500|Prepare failed: ' . $error[2], E_USER_WARNING);
            return false;
        }

        $typeMap = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'null' => PDO::PARAM_NULL,
            'resource' => PDO::PARAM_LOB,
        );

        $i = 1;

        foreach ($param as $value) {
            $type = strtolower(gettype($value));
            $type = isset($typeMap[$type]) ? $typeMap[$type] : PDO::PARAM_STR;
            $stmt->bindValue($i++, $value, $type);
        }

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            trigger_error('500|Execute failed: ' . $error[2], E_USER_WARNING);
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