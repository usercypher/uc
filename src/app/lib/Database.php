<?php

class Database {
    private $app;
    private $host, $port, $name, $user, $pass, $pdo, $time;

    public function __construct($args = array()) {
        list(
            $this->app
        ) = $args;

        $this->host = $this->app->getEnv('DB_HOST');
        $this->port = $this->app->getEnv('DB_PORT');
        $this->name = $this->app->getEnv('DB_NAME');
        $this->user = $this->app->getEnv('DB_USER');
        $this->pass = $this->app->getEnv('DB_PASS');
        $this->time = $this->app->getEnv('DB_TIME');
    }

    public function getConnection() {
        if (!$this->pdo) {
            try {
                $this->pdo = new PDO('mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->name, $this->user, $this->pass, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
                ));
                $this->pdo->exec('SET time_zone = "' . $this->time . '"');
            } catch (PDOException $e) {
                trigger_error('500|' . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    public function closeConnection() {
        $this->pdo = null;
    }
}
?>
