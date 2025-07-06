<?php

class Lib_DatabaseHelper {
    var $messages = array();
    var $conn, $table, $primaryColumn;

    function init($conn, $table, $primaryColumn = 'id') {
        $this->conn = $conn;
        $this->table = $table;
        $this->primaryColumn = $primaryColumn;
    }

    function addMessage($type, $message, $meta = array()) {
        $this->messages[] = array('type' => $type, 'message' => $message, 'meta' => array('table' => $this->table) + $meta);
    }

    function getMessages() {
        return $this->messages;
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

    function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        return ($this->query($query, array_values($data)) !== false) ? $this->lastInsertId() : false;
    }

    function insertBatch($rows) {
        $columns = implode(', ', array_keys($rows[0]));
        $placeholders = implode(', ', array_fill(0, count($rows[0]), '?'));
        $values = array();
        foreach ($rows as $row) {
            $rowValues = array_values($row);
            foreach ($rowValues as $value) {
                $values[] = $value;
            }
        }
        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES ' . implode(', ', array_fill(0, count($rows), '(' . $placeholders . ')'));
        return $this->query($query, $values) !== false;
    }

    function update($id, $data) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' = ?';
        return $this->query($query, array_merge(array_values($data), array($id))) !== false;
    }

    function updateBatch($rows) {
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row[$this->primaryColumn];
        }

        $allColumns = array_keys($rows[0]);
        $columns = array();
        foreach ($allColumns as $col) {
            if ($col !== $this->primaryColumn) {
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
                $values[] = $row[$this->primaryColumn];
                $values[] = $row[$column];
            }
            $setClauses[] = $column . ' = CASE ' . $this->primaryColumn . ' ' . implode(' ', $caseClause) . ' ELSE ' . $column . ' END';
        }

        $setClause = implode(', ', $setClauses);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')';

        $values = array_merge($values, $ids);

        return $this->query($query, $values) !== false;
    }

    function delete($id) {
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?';
        return $this->query($query, array($id)) !== false;
    }

    function deleteBatch($ids) {
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')';
        return $this->query($query, $ids) !== false;
    }

    function save($data) {
        return isset($data[$this->primaryColumn]) ? $this->update($data[$this->primaryColumn], $data) : $this->insert($data);
    }

    function find($id, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?';
        $stmt = $this->query($query, array($id));
        return $this->fetch($stmt);
    }

    function all($columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table;
        $stmt = $this->query($query, array());
        return $this->fetchAll($stmt);
    }

    function first($conditions, $params, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1';
        $stmt = $this->query($query, $params);
        return $this->fetch($stmt);
    }

    function where($conditions, $params, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions;
        $stmt = $this->query($query, $params);
        return $this->fetchAll($stmt);
    }

    function get($query, $params) {
        $stmt = $this->query($query, $params);
        return $this->fetchAll($stmt);
    }

    function count($conditions, $params) {
        $query = 'SELECT COUNT(*) AS total FROM ' . $this->table . (!empty($conditions) ? ' WHERE ' . $conditions : '');
        $stmt = $this->query($query, $params);
        $result = $this->fetch($stmt);

        return $result ? (int) $result['total'] : 0;
    }

    function exists($conditions, $params) {
        $query = 'SELECT 1 FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1';
        $stmt = $this->query($query, $params);
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