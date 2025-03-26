<?php
class Seeder {
    private $conn;

    public function __construct($dependencies) {
        $this->conn = $dependencies['Database']->getConnection();
    }

    public function run() {
        $this->insertBooks(true);
    }

    private function execute($sql, $message) {
        if (stripos(trim($sql), "SELECT") === 0) {
            $stmt = $this->conn->query($sql);
            if ($stmt === false) {
                trigger_error('500|' . $message . $this->conn->errorInfo()[2]);
                return false;
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            if ($this->conn->exec($sql) === false) {
                trigger_error('500|' . $message . $this->conn->errorInfo()[2]);
                return false;
            }
            return true;
        }
    }

    private function insertTable($shouldCheckForData, $data) {
        if ($shouldCheckForData) {
            $sqlCheck = "SELECT COUNT(*) FROM " . $data['table'];
            $result = $this->execute($sqlCheck, '');

            if ($result && $result[0]['COUNT(*)'] == 0) {
                $sqlInsert = "INSERT INTO " . $data['table'] . " " . $data['columns'] . " VALUES " . $data['rows'];
                $this->execute($sqlInsert, 'Error during database seeding: ');
            }
        } else {
            $sqlInsert = "INSERT INTO " . $data['table'] . " " . $data['columns'] . " VALUES " . $data['rows'];
            $this->execute($sqlInsert, 'Error during database seeding: ');
        }
    }


    private function insertBooks($shouldCheckForData) {
        $this->insertTable($shouldCheckForData, array(
            'table' => 'books',
            'columns' => '(title, author, publisher, year)',
            'rows' => ("
                ('The Great Adventure', 'Jane Austen', 'Literary Press', '1815-05-01'),
                ('Exploring the Universe', 'Carl Sagan', 'Cosmos Publishing', '1980-11-15'),
                ('The Odyssey', 'Homer', 'Ancient Texts Ltd.', '800-01-01'),
                ('War and Peace', 'Leo Tolstoy', 'Russian Classics', '1869-01-01'),
                ('Pride and Prejudice', 'Jane Austen', 'Classic Books', '1813-01-28'),
                ('Moby-Dick', 'Herman Melville', 'Whale Press', '1851-10-18'),
                ('The Catcher in the Rye', 'J.D. Salinger', 'American Books', '1951-07-16'),
                ('1984', 'George Orwell', 'Dystopian Publishing', '1949-06-08'),
                ('To Kill a Mockingbird', 'Harper Lee', 'Southern Press', '1960-07-11'),
                ('The Great Gatsby', 'F. Scott Fitzgerald', 'Classic Novels', '1925-04-10'),
                ('Frankenstein', 'Mary Shelley', 'Gothic Press', '1818-01-01'),
                ('The Hobbit', 'J.R.R. Tolkien', 'Middle-Earth Publishing', '1937-09-21'),
                ('The Diary of a Young Girl', 'Anne Frank', 'World History Press', '1947-06-25')
            "),

        ));
    }
}
?>