<?php

class Game_PlayerRepo extends Shared_Lib_DatabaseHelper {
    private $app;
    private $database;
    private $castStandard;
    private $castDb;

    public function args($args) {
        list(
            $this->app,
            $this->database,
            $this->castStandard,
            $this->castDb
        ) = $args;

        $name = 'game';

        parent::setTable('player');
        parent::setDb(
            $this->database,
            $this->database->connect(array(
                'dsn' => $this->app->env['db'][$name]['dsn'],
                'user' => $this->app->env['db'][$name]['user'],
                'pass' => $this->app->env['db'][$name]['pass']
            ), $name)
        );
    }
}
?>
