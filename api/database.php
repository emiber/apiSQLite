<?php
class Database
{
    private $database_path = './db/database.sqlite';
    public $connection;

    public function __construct()
    {
        $this->connection = null;
        try {
            // Cambiamos la conexión a SQLite
            $this->connection = new PDO("sqlite:" . $this->database_path);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            // Mantenemos la captura de excepciones, pero es recomendable registrar el error en lugar de usar echo
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection error.");
        }
    }
}
