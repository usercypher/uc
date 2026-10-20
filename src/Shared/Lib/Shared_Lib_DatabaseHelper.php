<?php

class Shared_Lib_DatabaseHelper {
    var $messages = array();
    var $table;
    var $key;
    var $db;
    var $default;

    function setTable($table, $key = 'id') {
        $this->table = $table;
        $this->key = $key;
    }

    function setDefault($default) {
        $this->default = $default;
    }

    function setDb($db) {
        $this->db = $db;
    }

    function addMessage($type, $message, $meta = array()) {
        $this->messages[] = array('type' => 'system:message:' . $type, 'data' => array('content' => $message, 'table' => $this->table) + $meta);
    }

    function getMessages() {
        return $this->messages;
    }

    function begin() {
        return $this->db->begin();
    }

    function commit() {
        return $this->db->commit();
    }

    function rollback() {
        return $this->db->rollback();
    }

    function lastInsertId($seq = null) {
        return $this->db->lastInsertId($seq);
    }

    function execute($query) {
        return $this->db->execute($query);
    }

    function stmt($query, $param) {
        return $this->db->stmt($query, $param);
    }

    function fetch($stmt) {
        return $this->db->fetch($stmt);
    }

    function fetchAll($stmt) {
        return $this->db->fetchAll($stmt);
    }

    function insert($data) {
        $prepend = array();

        if (isset($this->default['insert'])) {
            foreach ($this->default['insert'] as $k => $v) {
                if (!isset($data[$k])) {
                    $prepend[$k] = $v;
                }
            }
        }

        $columns = implode(', ', array_keys(array_merge($prepend, $data)));
        $placeholders = str_repeat('?,', count(array_merge($prepend, $data)) - 1) . '?';

        $prependResolve = array();
        foreach($prepend as $k => $v) {
            $prependResolve[$k] = is_object($v) ? $v->call($k) : $v;
        }

        $data = array_merge($prependResolve, $data);

        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        return $this->stmt($query, array_values($data)) !== false ? $data[$this->key] : null;
    }

    function insertBatch($rows) {
        $prepend = array();
        $ids = array();

        if (isset($this->default['insert'])) {
            foreach ($this->default['insert'] as $k => $v) {
                if (!isset($rows[0][$k])) {
                    $prepend[$k] = $v;
                }
            }
        }

        $columns = implode(', ', array_keys(array_merge($prepend, $rows[0])));
        $placeholders = str_repeat('?,', count(array_merge($prepend, $rows[0])) - 1) . '?';
        $values = array();
        foreach ($rows as $row) {
            $prependResolve = array();
            foreach($prepend as $k => $v) {
                $prependResolve[$k] = is_object($v) ? $v->call($k) : $v;
            }
            foreach (array_merge($prependResolve, $row) as $k => $value) {
                if ($this->key === $k) {
                    $ids[] = $value;
                }
                $values[] = $value;
            }
        }
        $query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES ' . str_repeat('(' . $placeholders . '), ', count($rows) - 1) . '(' . $placeholders . ')';
        return $this->stmt($query, $values) !== false ? $ids : array();
    }

    function update($data) {
        if (isset($this->default['update'])) {
            foreach ($this->default['update'] as $k => $v) {
                if (!isset($data[$k])) {
                    $data[$k] = is_object($v) ? $v->call($k) : $v;
                }
            }
        }
        $id = $data[$this->key];
        unset($data[$this->key]);
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $this->key . ' = ?';
        return $this->stmt($query, array_merge(array_values($data), array($id))) !== false;
    }

    function updateBatch($rows) {
        $ids = array();

        foreach ($rows as $i => $row) {
            $ids[] = $row[$this->key];
            if (isset($this->default['update'])) {
                foreach ($this->default['update'] as $k => $v) {
                    if (!isset($row[$k])) {
                        $rows[$i][$k] = is_object($v) ? $v->call($k) : $v;
                    }
                }
            }
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
                    $this->addMessage('error', 'Execute failed: Missing column "' . $column . '" in some rows.');
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

    function query($query, $param = array()) {
        $stmt = $this->stmt($query, $param);
        return $this->fetchAll($stmt);
    }

    function one($conditions = '', $param = array(), $columns = '*') {
        $query = 'SELECT ' . $columns . ' FROM ' . $this->table . ' ' . $conditions;
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
        $query = 'SELECT 1 FROM ' . $this->table . ' ' . $conditions;
        $stmt = $this->stmt($query, $param);
        return $this->fetch($stmt) !== false;
    }

    function chunk(&$array, $chunkSize) {
        if (0 >= $chunkSize || empty($array)) {
            return false;
        }
        return array_splice($array, 0, $chunkSize);
    }

    function defaultId() {
        return new Shared_Lib_DatabaseHelperDefaultId;
    }

    function defaultTime() {
        return new Shared_Lib_DatabaseHelperDefaultTime;
    }
}

class Shared_Lib_DatabaseHelperDefaultId {
    function call($k) {
        static $base32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        static $widths = array(4, 4, 2, 4, 4, 2);
    
        $mt = str_pad(time(), 12, '0', STR_PAD_LEFT) . substr(microtime(), 2, 3);
    
        $id = '';
        foreach (array((int)substr($mt, 0, 6), (int)substr($mt, 6, 6), (int)substr($mt, 12, 3), mt_rand(0, 1048575), mt_rand(0, 1048575), mt_rand(0, 1023)) as $i => $num) {
            $encoded = '';
            while ($num > 0) {
                $encoded = $base32[$num % 32] . $encoded;
                $num = floor($num / 32);
            }
            $id .= str_pad($encoded, $widths[$i], '0', STR_PAD_LEFT);
        }
    
        return $id;
    }
}

class Shared_Lib_DatabaseHelperDefaultTime {
    function call($k) {
        return time();
    }
}
?>
