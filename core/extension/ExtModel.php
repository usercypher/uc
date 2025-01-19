<?php

class ExtModel {
    protected $conn, $table;

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function find($id) {
        $stmt = $this->prepare('SELECT * FROM ' . $this->table . ' WHERE id = ?');
        $stmt = $this->execute($stmt, array(), $id);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all() {
        $stmt = $this->prepare('SELECT * FROM ' . $this->table);
        $stmt = $this->execute($stmt, array(), null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->prepare('INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
        return $this->execute($stmt, array_values($data), null);
    }

    public function update($id, $data) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $stmt = $this->prepare('UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE id = ?');
        return $this->execute($stmt, array_values($data), $id);
    }

    public function delete($id) {
        $stmt = $this->prepare('DELETE FROM ' . $this->table . ' WHERE id = ?');
        return $this->execute($stmt, array(), $id);
    }

    public function save($data) {
        if (isset($data['id']) && !empty($data['id'])) {
            return $this->update($data['id'], $data);
        } else {
            return $this->create($data);
        }
    }

    public function where($conditions, $params) {
        $stmt = $this->prepare('SELECT * FROM ' . $this->table . ' WHERE ' . $conditions);
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get($query, $params) {
        $stmt = $this->prepare($query);
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first($conditions, $params) {
        $stmt = $this->prepare('SELECT * FROM ' . $this->table . ' WHERE ' . $conditions . ' LIMIT 1');
        $stmt = $this->execute($stmt, $params, null);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
            throw new Exception('Prepare failed: ' . $this->conn->errorInfo()[2], 500);
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
            throw new Exception('Execute failed: ' . implode(', ', $stmt->errorInfo()), 500);
        }

        return $stmt;
    }
}
?>