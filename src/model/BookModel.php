<?php

class BookModel extends ExtModel {
    protected $table, $conn;

    public function __construct($dependencies) {
        $this->conn = $dependencies['Database']->getConnection();
        $this->table = 'books';
    }
}
?>
