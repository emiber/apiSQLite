<?php
// class Database
// {
//     private $host = "";
//     private $db_name = "";
//     private $username = "";
//     private $password = "";
//     public $connection;

//     public function __construct()
//     {
//         $this->setUpDB();
//         $this->connection = null;
//         try {
//             $this->connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
//             $this->connection->exec("set names utf8");
//         } catch (PDOException $exception) {
//             echo "Connection error: " . $exception->getMessage();
//         }
//     }

//     private function setUpDB()
//     {
//         switch ($_SERVER['HTTP_HOST']) {
//             case 'localhost':
//             case 'localhost:8080':
//                 $this->host = "localhost";
//                 $this->db_name = "casalinda";
//                 $this->username = "root";
//                 $this->password = "";
//                 break;
//             default:
//                 $this->host = "localhost";
//                 $this->db_name = "aguaviva_api";
//                 $this->username = "aguaviva_Webadmin_Api";
//                 $this->password = "{v3d&}r2~;2@";
//         }
//     }

//     function getDBName()
//     {
//         return $this->db_name;
//     }
// }

class Database
{
    private $database_path = 'db.sqlite';
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

    function getDBName()
    {
        // Puedes eliminar esta función si no es necesaria para SQLite
        return $this->database_path;
    }
}
