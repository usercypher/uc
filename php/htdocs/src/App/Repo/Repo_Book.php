<?php

class Repo_Book extends Lib_DatabaseHelper {
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
        ) = $args;

        parent::setTable('books');
        parent::setDb($this->database, $this->database->connect([
            'host' => $this->app->getEnv('DB_HOST'), 
            'port' => $this->app->getEnv('DB_PORT'),
            'name' => $this->app->getEnv('DB_NAME'),
            'user' => $this->app->getEnv('DB_USER'),
            'pass' => $this->app->getEnv('DB_PASS'),
            'time' => $this->app->getEnv('DB_TIME', '+00:00')
        ]));
    }

    public function getSchema($action, $context = array()) {
        $std = $this->castStandard;
        $db = $this->castDb;
        $s = [];

        if (in_array($action, ['update', 'delete'])) {
            $s += [
                'id' => [
                    $std->toInt(),
                    $std->required(),
                    $db->exists($this->table, 'id'),
                ]
            ];
        }

        if (in_array($action, ['insert', 'update'])) {
            $s += [
                'title' => [
                    $std->toString(),
                    $std->required(),
                    $std->lengthMin(5),
                    $std->lengthMax(30),
                ],
                'publisher' => [
                    $std->toString(),
                ],
                'author' => [
                    $std->toString(),
                ],
                'year' => [
                    $std->toString(),
                    $std->defaultValue(date('Y-m-d H:i:s')),
                    $std->toDateTime(),
                ]
            ];
        }

        if ($action === 'insert') {
            $s['title'][] = $db->unique($this->table, 'title');
        }

        if ($action === 'update') {
            $bookOld = $context['book_old'];
            $s['title'][] = $db->unique($this->table, 'title', $bookOld['title']);
        }

        return $s;
    }
}
?>