<?php

class Lib_DatabaseHelper {
    var $messages = array();
    var $database, $table, $primaryColumn;

    function init($database, $table, $primaryColumn = 'id') {
        $this->database = $database;
        $database->connect();

        $this->table = $table;
        $this->primaryColumn = $primaryColumn;
    }

    function addMessage($type, $message, $meta = array()) {
        $this->messages[] = array('type' => $type, 'message' => $message, 'meta' => array('table' => $this->table) + $meta);
    }

    function getMessages() {
        return $this->messages;
    }

    function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        return ($this->database->query($query, array_values($data)) !== false) ? $this->database->lastInsertId() : false;
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
        return $this->database->query($query, $values) !== false;
    }

    function update($id, $data) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' = ?';
        return $this->database->query($query, array_merge(array_values($data), array($id))) !== false;
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

        return $this->database->query($query, $values) !== false;
    }

    function delete($id) {
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?';
        return $this->database->query($query, array($id)) !== false;
    }

    function deleteBatch($ids) {
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')';
        return $this->database->query($query, $ids) !== false;
    }

    function save($data) {
        return isset($data[$this->primaryColumn]) ? $this->update($data[$this->primaryColumn], $data) : $this->insert($data);
    }

    function find($id, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?';
        $stmt = $this->database->query($query, array($id));
        return $this->database->fetch($stmt);
    }

    function all($columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table;
        $stmt = $this->database->query($query, array());
        return $this->database->fetchAll($stmt);
    }

    function first($conditions, $params, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1';
        $stmt = $this->database->query($query, $params);
        return $this->database->fetch($stmt);
    }

    function where($conditions, $params, $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions;
        $stmt = $this->database->query($query, $params);
        return $this->database->fetchAll($stmt);
    }

    function query($query, $params) {
        $stmt = $this->database->query($query, $params);
        return $this->database->fetchAll($stmt);
    }

    function count($conditions, $params) {
        $query = 'SELECT COUNT(*) AS total FROM ' . $this->table . (!empty($conditions) ? ' WHERE ' . $conditions : '');
        $stmt = $this->database->query($query, $params);
        $result = $this->database->fetch($stmt);

        return $result ? (int) $result['total'] : 0;
    }

    function exists($conditions, $params) {
        $query = 'SELECT 1 FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1';
        $stmt = $this->database->query($query, $params);
        return $this->database->fetch($stmt) !== false;
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