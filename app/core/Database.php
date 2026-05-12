<?php
declare(strict_types=1);

class Database
{
    private string $host     = 'localhost';
    private string $dbname   = 'quickbite';
    private string $username = 'root';
    private string $password = '';

    private PDO $connection;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";

        $this->connection = new PDO($dsn, $this->username, $this->password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE,          PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
