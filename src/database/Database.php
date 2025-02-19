<?php

class Database {
    private $host, $port, $name, $user, $pass, $pdo;

    public function __construct() {
        $this->host = App::getEnv('DB_HOST');
        $this->port = App::getEnv('DB_PORT');
        $this->name = App::getEnv('DB_NAME');
        $this->user = App::getEnv('DB_USER');
        $this->pass = App::getEnv('DB_PASS');
    }

    public function getConnection() {
        if (!$this->pdo) {
            $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->name;
            $this->pdo = new PDO($dsn, $this->user, $this->pass);
        }
        return $this->pdo;
    }

    public function closeConnection() {
        $this->pdo = null;
    }
}
?>
