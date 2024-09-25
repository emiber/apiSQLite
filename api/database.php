<?php
class Database
{
    private $database_path = './db/database.sqlite';
    public $connection;

    public function __construct()
    {

        if (!file_exists($this->database_path)) {
            $this->createDB();
        }

        $this->connection = null;
        try {
            $this->connection = new PDO("sqlite:" . $this->database_path);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection error.");
        }
    }

    private function createDB()
    {
        try {
            $conn = new PDO("sqlite:$this->database_path");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "CREATE TABLE data (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tableName TEXT NOT NULL,
                        data TEXT NOT NULL,
                        sortOrder INTEGER NOT NULL DEFAULT 0,
                        enabled INTEGER NOT NULL DEFAULT 1,
                        createDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        updateDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    )";
            $conn->exec($sql);

            $sql = "CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        sub TEXT NOT NULL UNIQUE,
                        enabled INTEGER NOT NULL DEFAULT 0,
                        sysAdmin INTEGER NOT NULL DEFAULT 0,
                        user TEXT NOT NULL,
                        createDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    )";
            $conn->exec($sql);
        } catch (PDOException $e) {
            echo "Error en la creación de las tablas: " . $e->getMessage();
        }

        $conn = null;
    }
}
