<?php
class Database
{
    private $host = "";
    private $db_name = "";
    private $username = "";
    private $password = "";
    public $connection;

    public function __construct()
    {
        $this->setUpDB();
        $this->connection = null;
        try {
            $this->connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->connection->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
    }

    private function setUpDB()
    {
        switch ($_SERVER['HTTP_HOST']) {
            case 'localhost':
            case 'localhost:8080':
                $this->host = "localhost";
                $this->db_name = "casalinda";
                $this->username = "root";
                $this->password = "";
                break;
            default:
                $this->host = "localhost";
                $this->db_name = "aguaviva_api";
                $this->username = "aguaviva_Webadmin_Api";
                $this->password = "{v3d&}r2~;2@";
        }
    }

    function getDBName()
    {
        return $this->db_name;
    }
}
