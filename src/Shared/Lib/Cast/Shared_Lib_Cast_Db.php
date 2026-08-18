<?php

class Shared_Lib_Cast_Db {
    var $db, $t;

    function args($args) {
        list(
            $app,
            $database,
            $translator
        ) = $args;

        $this->t = $translator->get('shared');

        $db = $app->getEnv('DB', array());
        $name = 'DEFAULT';

        $this->db = $database->conn[$database->connect([
            'dsn' => isset($db[$name]['DSN']) ? $db[$name]['DSN'] : null,
            'user' => isset($db[$name]['USER']) ? $db[$name]['USER'] : null,
            'pass' => isset($db[$name]['PASS']) ? $db[$name]['PASS'] : null,
            'query' => isset($db[$name]['QUERY']) ? $db[$name]['QUERY'] : null,
        ], $name)];
    }

    function unique($table, $column, $current = null) {
        $o = new Shared_Lib_Cast_Db_Unique;
        $o->t = $this->t;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        $o->current = $current;
        return $o;
    }

    function exists($table, $column) {
        $o = new Shared_Lib_Cast_Db_Exists;
        $o->t = $this->t;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        return $o;
    }

    function unchanged($table, $column, $id) {
        $o = new Shared_Lib_Cast_Db_Unchanged;
        $o->t = $this->t;
        $o->db = $this->db;
        $o->table = $table;
        $o->column = $column;
        $o->id = $id;
        return $o;
    }

}

class Shared_Lib_Cast_Db_Unique {
    var $t, $db, $table, $column, $current;

    function call($value) {
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
            return array($value, $this->t->t('lib_cast_db_already_exists', array(
                ':value' => $value,
                ':table' => $table,
            )));
        }

        return array($value, null);
    }
}

class Shared_Lib_Cast_Db_Exists {
    var $t, $db, $table, $column;

    function call($value) {
        $table = $this->table;
        $column = $this->column;

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();

        if (!$result || $result['count'] == 0) {
            return array($value, $this->t->t('lib_cast_db_not_found', array(
                ':value' => $value,
                ':table' => $table,
            )));
        }

        return array($value, null);
    }
}

class Shared_Lib_Cast_Db_Unchanged {
    var $t, $db, $table, $column, $id;

    function call($value) {
        $table = $this->table;
        $column = $this->column;
        $id = $this->id;

        $sql = "SELECT {$column} FROM {$table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result && $result[$column] === $value) {
            return array($value, $this->t->t('lib_cast_db_unchanged', array(
                ':value' => $value,
                ':column' => $column,
                ':current' => $result[$column],
                
            )));
        }

        return array($value, null);
    }
}
