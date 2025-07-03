<?php

class Lib_DatabaseHelper {
    var $messages = array();
    var $conn, $table, $primaryColumn = 'id';

    function setConn($conn) {
        $this->conn = $conn;
    }

    function setTable($table) {
        $this->table = $table;
    }

    function setPrimaryColumn($primaryColumn) {
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

    function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->prepare('INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
        return ($this->execute($stmt, array_values($data)) !== false) ? $this->lastInsertId() : false;
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
        $stmt = $this->prepare($query);
        return $this->execute($stmt, $values) !== false;
    }

    function update($id, $data) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $stmt = $this->prepare('UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' = ?');
        return $this->execute($stmt, array_merge(array_values($data), array($id))) !== false;
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
        $sql = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')';

        $values = array_merge($values, $ids);
        $stmt = $this->prepare($sql);

        return $this->execute($stmt, $values) !== false;
    }

    function delete($id) {
        $stmt = $this->prepare('DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?');
        return $this->execute($stmt, array($id)) !== false;
    }

    function deleteBatch($ids) {
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->prepare('DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')');
        return $this->execute($stmt, $ids) !== false;
    }

    function save($data) {
        return isset($data[$this->primaryColumn]) ? $this->update($data[$this->primaryColumn], $data) : $this->insert($data);
    }

    function find($id, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?');
        $stmt = $this->execute($stmt, array($id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function all($columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table);
        $stmt = $this->execute($stmt, array());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function first($conditions, $params, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1');
        $stmt = $this->execute($stmt, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function where($conditions, $params, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions);
        $stmt = $this->execute($stmt, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function query($query, $params) {
        $stmt = $this->prepare($query);
        $stmt = $this->execute($stmt, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function count($conditions, $params) {
        $stmt = $this->prepare('SELECT COUNT(*) FROM ' . $this->table . (!empty($conditions) ? ' WHERE ' . $conditions : ''));
        $stmt = $this->execute($stmt, $params);
        return $stmt->fetchColumn();
    }

    function exists($conditions, $params) {
        $stmt = $this->prepare('SELECT 1 FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1');
        $stmt = $this->execute($stmt, $params);
        return $stmt->fetchColumn() !== false;
    }

    function chunk(&$array, $chunkSize) {
        if (!$array || 0 >= $chunkSize) {
            return false;
        }
        $chunk = array_slice($array, 0, $chunkSize);
        $array = array_slice($array, $chunkSize);
        return $chunk;
    }

    function prepare($query) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            $error = $stmt->errorInfo();
            trigger_error('500|Prepare failed: ' . $error[2]);
            return false;
        }
        return $stmt;
    }

    function execute($stmt, $params) {
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
            $error = $stmt->errorInfo();
            trigger_error('500|Execute failed: ' . $error[2]);
            return false;
        }

        return $stmt;
    }
}
?>