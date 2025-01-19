<?php
class Migration {
    private $conn;

    public function __construct($dependencies) {
        $this->conn = $dependencies['Database']->getConnection();
    }

    public function up() {
        $this->createTable($this->books());
    }

    public function down() {
        $this->dropTable('books');
    }

    private function execute($sql, $message) {
        if ($this->conn->exec($sql) === false) {
            throw new Exception($message . $pdo->errorInfo()[2], 500);
        }
    }

    private function createTable($sql) {
        $this->execute($sql, 'Error during database migration: ');
    }

    private function dropTable($tableName) {
        $sql = 'DROP TABLE IF EXISTS ' . $tableName;
        $this->execute($sql, 'Error during database migration: ');
    }

    private function books() {
        return (
            "CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255),
                publisher VARCHAR(255),
                author VARCHAR(255),
                year DATE
            )"
        );
    }
}
?>