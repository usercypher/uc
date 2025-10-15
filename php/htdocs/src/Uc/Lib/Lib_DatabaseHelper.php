<?php

class Lib_DatabaseHelper {
    var $messages = array();
    var $conn, $table, $key;

    function setConn($conn) {
        $this->conn = $conn;
    }

    function setTable($table, $key = 'id') {
        $this->table = $table;
        $this->key = $key;
    }

    function addMessage($type, $message, $meta = array()) {
        $this->messages[] = array('type' => $type, 'message' => $message, 'meta' => array('table' => $this->table) + $meta);
    }

    function getMessages() {
        return $this->messages;
    }

    function begin() {
        return $this->conn->beginTransaction();
    }

    function commit() {
        return $this->conn->commit();
    }

    function rollback() {
        return $this->conn->rollBack();
    }

    function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    function execute($query) {
        return $this->conn->exec($query);
    }

    function stmt($query, $params) {
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
            $type = strtolower(gettype($value));
            $type = isset($typeMap[$type]) ? $typeMap[$type] : PDO::PARAM_STR;
            $stmt->bindValue($i++, $value, $type);
        }

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
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

    function create($definition, $return = false) {
        $query = 'CREATE TABLE IF NOT EXISTS ' . $this->table . ' (' . $definition . ')';
        if ($return) return $query . ';';
        return $this->execute($query) !== false;
    }

    function drop() {
        $query = 'DROP TABLE IF EXISTS ' . $this->table;
        return $this->execute($query) !== false;
    }

    function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';

        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        return ($this->stmt($query, array_values($data)) !== false) ? $this->lastInsertId() : false;
    }

    function insertBatch($rows) {
        $columns = implode(', ', array_keys($rows[0]));
        $placeholders = str_repeat('?,', count($rows[0]) - 1) . '?';
        $values = array();
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $values[] = $value;
            }
        }
        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES ' . str_repeat('(' . $placeholders . '), ', count($rows) - 1) . '(' . $placeholders . ')';
        return $this->stmt($query, $values) !== false;
    }

    function update($data) {
        $id = $data[$this->key];
        unset($data[$this->key]);
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->key . ' = ?';
        return $this->stmt($query, array_merge(array_values($data), array($id))) !== false;
    }

    function updateBatch($rows) {
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row[$this->key];
        }

        $allColumns = array_keys($rows[0]);
        $columns = array();
        foreach ($allColumns as $col) {
            if ($col !== $this->key) {
                $columns[] = $col;
            }
        }

        $setClauses = array();
        $values = array();
        foreach ($columns as $column) {
            $caseClause = array();
            foreach ($rows as $row) {
                if (!isset($row[$column])) {
                    trigger_error('500|Execute failed: Missing column "' . $column . '" in some rows.');
                    return false;
                }
                $caseClause[] = 'WHEN ? THEN ?';
                $values[] = $row[$this->key];
                $values[] = $row[$column];
            }
            $setClauses[] = $column . ' = CASE ' . $this->key . ' ' . implode(' ', $caseClause) . ' ELSE ' . $column . ' END';
        }

        $setClause = implode(', ', $setClauses);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->key . ' IN (' . $placeholders . ')';

        $values = array_merge($values, $ids);

        return $this->stmt($query, $values) !== false;
    }

    function delete($id) {
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->key . ' = ?';
        return $this->stmt($query, array($id)) !== false;
    }

    function deleteBatch($ids) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->key . ' IN (' . $placeholders . ')';
        return $this->stmt($query, $ids) !== false;
    }

    function save($data) {
        return isset($data[$this->key]) ? $this->update($data) : $this->insert($data);
    }

    function query($query, $params = array()) {
        $stmt = $this->stmt($query, $params);
        return $this->fetchAll($stmt);
    }

    function one($conditions = '', $params = array(), $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' ' . $conditions . ' LIMIT 1';
        $stmt = $this->stmt($query, $params);
        return $this->fetch($stmt);
    }

    function all($conditions = '', $params = array(), $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' ' . $conditions;
        $stmt = $this->stmt($query, $params);
        return $this->fetchAll($stmt);
    }

    function count($conditions = '', $params = array()) {
        $query = 'SELECT COUNT(*) AS total FROM ' . $this->table . ' ' . $conditions;
        $stmt = $this->stmt($query, $params);
        $result = $this->fetch($stmt);

        return $result ? (int) $result['total'] : 0;
    }

    function exists($conditions, $params = array()) {
        $query = 'SELECT 1 FROM ' . $this->table . ' ' . $conditions . ' LIMIT 1';
        $stmt = $this->stmt($query, $params);
        return $this->fetch($stmt) !== false;
    }

    function chunk(&$array, $chunkSize) {
        if (!$array || 0 >= $chunkSize) {
            return false;
        }
        $chunk = array_slice($array, 0, $chunkSize);
        $array = array_slice($array, $chunkSize);
        return $chunk;
    }
}
?>