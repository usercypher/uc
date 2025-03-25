<?php

class Database {
    private $host, $port, $name, $user, $pass, $pdo, $time;

    public function __construct($dependency) {
        $app = $dependency['App'];
        $this->host = $app->getEnv('DB_HOST');
        $this->port = $app->getEnv('DB_PORT');
        $this->name = $app->getEnv('DB_NAME');
        $this->user = $app->getEnv('DB_USER');
        $this->pass = $app->getEnv('DB_PASS');
        $this->time = $app->getEnv('DB_TIME');
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
