<?php

class Lib_DatabaseHelper {
    var $messages = array();
    var $db, $conn, $table, $key;

    function setDb($db, $conn = '_') {
        $this->db = $db;
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
        return $this->db->begin($this->conn);
    }

    function commit() {
        return $this->db->commit($this->conn);
    }

    function rollback() {
        return $this->db->rollback($this->conn);
    }

    function lastInsertId() {
        return $this->db->lastInsertId($this->conn);
    }

    function execute($query) {
        return $this->db->execute($query, $this->conn);
    }

    function stmt($query, $param) {
        return $this->db->stmt($query, $param, $this->conn);
    }

    function fetch($stmt) {
        return $this->db->fetch($stmt);
    }

    function fetchAll($stmt) {
        return $this->db->fetchAll($stmt);
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
                    trigger_error('500|Execute failed: Missing column "' . $column . '" in some rows.', E_USER_WARNING);
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

    function query($query, $param = array()) {
        $stmt = $this->stmt($query, $param);
        return $this->fetchAll($stmt);
    }

    function one($conditions = '', $param = array(), $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' ' . $conditions . ' LIMIT 1';
        $stmt = $this->stmt($query, $param);
        return $this->fetch($stmt);
    }

    function all($conditions = '', $param = array(), $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' ' . $conditions;
        $stmt = $this->stmt($query, $param);
        return $this->fetchAll($stmt);
    }

    function count($conditions = '', $param = array()) {
        $query = 'SELECT COUNT(*) AS total FROM ' . $this->table . ' ' . $conditions;
        $stmt = $this->stmt($query, $param);
        $result = $this->fetch($stmt);

        return $result ? (int) $result['total'] : 0;
    }

    function exists($conditions, $param = array()) {
        $query = 'SELECT 1 FROM ' . $this->table . ' ' . $conditions . ' LIMIT 1';
        $stmt = $this->stmt($query, $param);
        return $this->fetch($stmt) !== false;
    }

    function chunk(&$array, $chunkSize) {
        if (0 >= $chunkSize || empty($array)) return false;

        return array_splice($array, 0, $chunkSize);
    }
}
?>