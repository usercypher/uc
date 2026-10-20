<?php

class Game_TickRepo extends Shared_Lib_DatabaseHelper {
    private $app;
    private $database;
    private $castStandard;
    private $castDb;

    public function args($args) {
        list(
            $this->app,
            $this->database,
            $this->castStandard,
            $this->castDb,
            $name,
            $table
        ) = $args;

        $this->database->set($name, array(
            'dsn' => $this->app->env['db'][$name]['dsn'],
            'user' => $this->app->env['db'][$name]['user'],
            'pass' => $this->app->env['db'][$name]['pass']
        ));

        parent::setTable($table);
        parent::setDb($this->database->get($name));
    }
}
?>
