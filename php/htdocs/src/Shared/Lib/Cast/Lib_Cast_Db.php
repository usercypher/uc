<?php

class Lib_Cast_Db {
    var $db;

    function setPdo($db) {
        $this->db = $db;
    }

    function unique($table, $column, $current = null) {
        $o = new Lib_Cast_Db_Unique;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        $o->current = $current;
        return $o;
    }

    function exists($table, $column) {
        $o = new Lib_Cast_Db_Exists;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        return $o;
    }
}

class Lib_Cast_Db_Unique {
    var $db, $table, $column, $current;

    function process($value) {
        $table = $this->table;
        $column = $this->column;
        $current = $this->current;

        if ($current !== null && $value === $current) {
            return $value;
        }

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();

        if ($result && $result['count'] > 0) {
            return [$value . ' already exists in ' . $table, 1];
        }

        return $value;
    }
}

class Lib_Cast_Db_Exists {
    var $db, $table, $column;

    function process($value) {
        $table = $this->table;
        $column = $this->column;

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();

        if (!$result || $result['count'] == 0) {
            return [$value . ' not found in ' . $table, 1];
        }

        return $value;
    }
}