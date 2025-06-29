<?php

class Lib_Model {
    private $flash = array();
    private $table, $conn, $primaryColumn = 'id';

    public function setConn($conn) {
        $this->conn = $conn;
    }

    public function setTable($table) {
        $this->table = $table;
    }

    public function setPrimaryColumn($primaryColumn) {
        $this->primaryColumn = $primaryColumn;
    }

    public function addFlash($type, $message) {
        $this->flash[] = array(
            'type' => $type,
            'message' => $message
        );
    }

    public function getFlash() {
        return $this->flash;
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->prepare('INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
        return $this->execute($stmt, array_values($data), null);
    }

    public function createBatch($data) {
        $columns = implode(', ', array_keys($data[0]));
        $placeholders = implode(', ', array_fill(0, count($data[0]), '?'));
        $values = array();
        foreach ($data as $row) {
            $rowValues = array_values($row);
            foreach ($rowValues as $value) {
                $values[] = $value;
            }
        }
        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES ' . implode(', ', array_fill(0, count($data), '(' . $placeholders . ')'));
        $stmt = $this->prepare($query);
        return $this->execute($stmt, $values, null);
    }

    public function update($id, $data) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $stmt = $this->prepare('UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' = ?');
        return $this->execute($stmt, array_values($data), $id);
    }

    public function updateBatch($ids, $data) {
        $idCount = count($ids);
        $setClauses = array();
        $values = array();
        foreach ($data as $column => $valuesArray) {
            if ($idCount !== count($valuesArray)) {
                trigger_error('500|Execute failed: The number of IDs and data elements must match.');
                return false;
            }
            $caseClause = array();
            foreach ($ids as $key => $id) {
                $caseClause[] = 'WHEN ? THEN ?';
                $values[] = $id;
                $values[] = $valuesArray[$key];
            }
            $setClauses[] = $column . ' = CASE id ' . implode(' ', $caseClause) . ' ELSE ' . $column . ' END';
        }
        $setClause = implode(', ', $setClauses);
        $stmt = $this->prepare('UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->primaryColumn . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $values = array_merge($values, $ids);
        return $this->execute($stmt, $values, null);
    }

    public function delete($id) {
        $stmt = $this->prepare('DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?');
        return $this->execute($stmt, array(), $id);
    }

    public function deleteBatch($ids) {
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->prepare('DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' IN (' . $placeholders . ')');
        return $this->execute($stmt, $ids, null);
    }

    public function save($data) {
        if (isset($data['id']) && !empty($data['id'])) {
            return $this->update($data['id'], $data);
        } else {
            return $this->create($data);
        }
    }

    public function find($id, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $this->primaryColumn . ' = ?');
        $stmt = $this->execute($stmt, array(), $id);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all($columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table);
        $stmt = $this->execute($stmt, array(), null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first($conditions, $params, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1');
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function where($conditions, $params, $columns = '*') {
        $stmt = $this->prepare('SELECT ' . $columns . ' FROM ' . $this->table . ' WHERE ' . $conditions);
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get($query, $params) {
        $stmt = $this->prepare($query);
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count($conditions, $params) {
        $stmt = $this->prepare('SELECT COUNT(*) FROM ' . $this->table . ' WHERE ' . $conditions);
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchColumn();
    }

    public function exists($conditions, $params) {
        $stmt = $this->prepare('SELECT 1 FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1');
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchColumn() !== false;
    }

    protected function prepare($query) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            trigger_error('500|Prepare failed: ' . $this->conn->errorInfo()[2]);
            return false;
        }
        return $stmt;
    }

    protected function execute($stmt, $params, $id) {
        $typeMap = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'NULL' => PDO::PARAM_NULL,
            'resource' => PDO::PARAM_LOB,
        );

        $i = 1;

        foreach ($params as $value) {
            $type = isset($typeMap[gettype($value)]) ? $typeMap[gettype($value)] : PDO::PARAM_STR;
            $stmt->bindValue($i++, $value, $type);
        }

        if ($id) {
            $stmt->bindValue($i, $id, PDO::PARAM_INT);
        }

        if (!$stmt->execute()) {
            trigger_error('500|Execute failed: ' . implode(', ', $stmt->errorInfo()[2]));
            return false;
        }

        return $stmt;
    }
}
?>