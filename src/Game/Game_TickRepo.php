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
            $this->castDb
        ) = $args;

        $db = $this->app->getEnv('DB', array());
        $name = 'GAME';

        parent::setTable('tick');
        parent::setDb(
            $this->database,
            $this->database->connect(array(
                'dsn' => isset($db[$name]['DSN']) ? $db[$name]['DSN'] : null,
                'user' => isset($db[$name]['USER']) ? $db[$name]['USER'] : null,
                'pass' => isset($db[$name]['PASS']) ? $db[$name]['PASS'] : null,
                'query' => isset($db[$name]['QUERY']) ? $db[$name]['QUERY'] : null,
            ), $name)
        );
    }
}
?>
