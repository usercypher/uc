<?php

class Shared_Lib_Cast_Db {
    var $db;

    function args($args) {
        list(
            $app,
            $database
        ) = $args;

        $this->db = $database->conn[$database->connect([
            'dsn' => $app->getEnv('DB_DSN'),
            'user' => $app->getEnv('DB_USER'),
            'pass' => $app->getEnv('DB_PASS'),
            'time' => $app->getEnv('DB_TIME', '+00:00')
        ])];
    }

    function unique($table, $column, $current = null) {
        $o = new Shared_Lib_Cast_Db_Unique;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        $o->current = $current;
        return $o;
    }

    function exists($table, $column) {
        $o = new Shared_Lib_Cast_Db_Exists;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        return $o;
    }

    function unchanged($table, $column, $id) {
        $o = new Shared_Lib_Cast_Db_Unchanged;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        $o->id = $id;
        return $o;
    }

}

class Shared_Lib_Cast_Db_Unique {
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

class Shared_Lib_Cast_Db_Exists {
    var $db, $table, $column;

    function process($value) {
        $table = $this->table;
        $column = $this->column;

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();

        if (!$result || $result['count'] == 0) {
            return array($value, $value . ' not found in ' . $table);
        }

        return array($value, null);
    }
}

class Shared_Lib_Cast_Db_Unchanged {
    var $db, $table, $column, $id;

    function process($value) {
        $table = $this->table;
        $column = $this->column;
        $id = $this->id;

        $sql = "SELECT {$column} FROM {$table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result && $result[$column] === $value) {
            return array($value, 'cannot set to ' . $value . ': ' . $column . ' is already set to ' . $result[$column]);
        }

        return array($value, null);
    }
}
