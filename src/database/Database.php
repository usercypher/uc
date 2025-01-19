<?php

class Database {
    private $host, $name, $user, $pass, $pdo;

    public function __construct() {
        $this->host = getenv('DB_HOST');
        $this->name = getenv('DB_NAME');
        $this->user = getenv('DB_USER');
        $this->pass = getenv('DB_PASS');
    }

    public function getConnection() {
        if (!$this->pdo) {
            $this->pdo = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->pdo;
    }

    public function closeConnection() {
        $this->pdo = null;
    }
}
?>