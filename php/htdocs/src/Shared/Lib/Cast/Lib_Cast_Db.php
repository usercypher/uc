<?php

class Lib_Cast_Db {
    var $db;

    function args($args) {
        list(
            $app,
            $database
        ) = $args;

        $this->db = $database->conn[$database->connect([
            'host' => $app->getEnv('DB_HOST'), 
            'port' => $app->getEnv('DB_PORT'),
            'name' => $app->getEnv('DB_NAME'),
            'user' => $app->getEnv('DB_USER'),
            'pass' => $app->getEnv('DB_PASS'),
            'time' => $app->getEnv('DB_TIME', '+00:00')
        ])];
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
            return array($value, null);
        }

        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();

        if ($result && $result['count'] > 0) {
            return array($value, $value . ' already exists in ' . $table);
        }

        return array($value, null);
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
            return array($value, $value . ' not found in ' . $table);
        }

        return array($value, null);
    }
}