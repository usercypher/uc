<?php

class Shared_Lib_Datastore {
    var $conn;

    function connect($config = array(), $id = '_') {
        if (!isset($this->conn[$id])) {
            $this->conn[$id] = @fsockopen(isset($config['host']) ? $config['host'] : '127.0.0.1', isset($config['port']) ? $config['port'] : 6379, $errno, $errstr, 3);
            if (!$this->conn[$id]) {
                trigger_error('Datastore Error: Connection failed: ' . $errstr, E_USER_ERROR);
            }
        }
        return $id;
    }

    function disconnect($id = '_') {
        if (isset($this->conn[$id])) {
            fclose($this->conn[$id]);
            unset($this->conn[$id]);
        }
    }

    function hasConnection($id = '_') {
        return isset($this->conn[$id]);
    }

    // Datastore operations

    function execute($args, $id = '_') {
        fwrite($this->conn[$id], '*' . count($args) . "\r\n");

        foreach ($args as $arg) {
            $len = strlen($arg);

            fwrite($this->conn[$id], '$' . $len . "\r\n");
            fwrite($this->conn[$id], $arg . "\r\n");
        }

        return $this->_parse($id);
    }

    function _parse($id = '_') {
        $line = fgets($this->conn[$id], 512);
        if (!$line) return false;

        $type = $line[0];
        $payload = trim(substr($line, 1));

        switch ($type) {
            case '+':
                return $payload;
            case '-':
                trigger_error('Datastore Error: ' . $payload, E_USER_WARNING);
                return false;
            case ':':
                return (int)$payload;
            case '$':
                $len = (int)$payload;
                if ($len === -1) return null;

                $data = '';
                $read = 0;
                while ($len > $read) {
                    $diff = $len - $read;
                    $chunk = fread($this->conn[$id], ($diff > 8192) ? 8192 : $diff);
                    if ($chunk === false || $chunk === '') break;
                    $read += strlen($chunk);
                    $data .= $chunk;
                }
                fread($this->conn[$id], 2);
                return $data;
            case '*':
                $count = (int)$payload;
                if ($count === -1) {
                    return null;
                }
                $results = array();
                for ($i = 0; $count > $i; $i++) {
                    $results[] = $this->_parse($id);
                }
                return $results;
            default:
                trigger_error('Datastore Error: Unknown RESP type: ' . $type, E_USER_WARNING);
                return false;
        }
    }
}