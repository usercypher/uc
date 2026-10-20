<?php

class Shared_Lib_Database {
    var $objects = array();

    function set($key, $config = array()) {
        $object = new Shared_Lib_Database_Obj;
        $object->config = $config;
        $this->objects[$key] = $object;
    }

    function get($key) {
        return $this->objects[$key];
    }
}

class Shared_Lib_Database_Obj {
    var $conn, $config = array();

    function connect() {
        if (!isset($this->conn)) {
            $dsn = isset($this->config['dsn']) ? $this->config['dsn'] : '';
            $user = isset($this->config['user']) ? $this->config['user'] : '';
            $pass = isset($this->config['pass']) ? $this->config['pass'] : '';
            $timeout = isset($this->config['timeout']) ? (int) $this->config['timeout'] : 5;

            $this->conn = new PDO($dsn, $user, $pass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                PDO::ATTR_TIMEOUT => $timeout,
            ));
        }
        return $this->conn;
    }

    function disconnect() {
        if (isset($this->conn)) {
            unset($this->conn);
        }
    }

    function hasConnection() {
        return isset($this->conn);
    }

    // Database operations

    function begin() {
        $this->connect();
        return $this->conn->beginTransaction();
    }

    function commit() {
        $this->connect();
        return $this->conn->commit();
    }

    function rollback() {
        $this->connect();
        return $this->conn->rollBack();
    }

    function lastInsertId($seq = null) {
        $this->connect();
        return $this->conn->lastInsertId($seq);
    }

    function execute($query) {
        $this->connect();
        return $this->conn->exec($query);
    }

    function stmt($query, $param) {
        $this->connect();
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            $error = $this->conn->errorInfo();
            trigger_error('Prepare failed: ' . $error[2], E_USER_WARNING);
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
            trigger_error('Execute failed: ' . $error[2], E_USER_WARNING);
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
