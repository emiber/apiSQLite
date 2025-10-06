<?php
include_once 'database.php';
ini_set('memory_limit', '1024M'); // or you could use 1G

class Data
{
    private $id;
    private $database;
    private $sysAdmin;

    function __construct($id, $sysAdmin = false)
    {
        $this->database = new Database();
        $this->id = $id;
        $this->sysAdmin = $sysAdmin;

        // $this->cleanDatabse();
    }

    private function cleanDatabse()
    {
        $connection = $this->database->connection;

        // Eliminar tabla 'data' si existe
        $query = "DROP TABLE IF EXISTS `data`;";
        $stmt = $connection->prepare($query);
        $stmt->execute();

        // Eliminar tabla 'users' si existe (también verifica 'usuarios')
        $query = "DROP TABLE IF EXISTS `users`;";
        $stmt = $connection->prepare($query);
        $stmt->execute();

        $query = "DROP TABLE IF EXISTS `usuarios`;";
        $stmt = $connection->prepare($query);
        $stmt->execute();

        // Recrear tabla 'data'
        $sql = "CREATE TABLE data (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tableName TEXT NOT NULL,
                    data TEXT NOT NULL,
                    sortOrder INTEGER NOT NULL DEFAULT 0,
                    enabled INTEGER NOT NULL DEFAULT 1,
                    createDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updateDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )";
        $stmt = $connection->prepare($sql);
        $stmt->execute();

        // Recrear tabla 'users' (usuarios)
        $sql = "CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    sub TEXT NOT NULL UNIQUE,
                    enabled INTEGER NOT NULL DEFAULT 0,
                    sysAdmin INTEGER NOT NULL DEFAULT 0,
                    user TEXT NOT NULL,
                    createDateTime TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    }

    function get()
    {
        $connection = $this->database->connection;
        $query = "SELECT * FROM `data`";
        if ($this->id != "") {
            $query = $query . " WHERE `id` = $this->id";
        } else if ($this->sysAdmin) {
            $query = $query . " UNION ALL SELECT `id`, 'usuarios' as `tableName`, `user` as `data`, 0 as sortOrder, enabled, `createDateTime` as `createDateTime`, `createDateTime` as `updateDateTime` FROM `users`";
        }
        $query = $query . " ORDER BY `tableName`, `sortOrder`;";

        $stmt = $connection->prepare($query);
        $stmt->execute();
        $rows = $this->rowsToArray($stmt);

        if (count($rows) == 0) {
            return [];
        }

        foreach ($rows as $row) {
            $row = $this->castDataObject($row);
        }

        if ($this->id != "") {
            return $rows[0];
        } else {
            return $rows;
        }
    }

    function delete()
    {
        $response = false;
        if (!is_null($this->id)) {
            $connection = $this->database->connection;
            $query = "DELETE FROM `data` WHERE `id` = $this->id";

            $stmt = $connection->prepare($query);
            $response = $stmt->execute();
        }
        return $response;
    }

    function copy()
    {
        $response = false;
        if (!is_null($this->id)) {
            $connection = $this->database->connection;
            $query = "INSERT INTO `data` (`tableName`, `data`, `sortOrder`, `enabled`) SELECT `tableName`, `data`, `sortOrder`, 0 FROM `data` WHERE  `id` = $this->id;";
            $stmt = $connection->prepare($query);
            if ($stmt->execute()) {
                return $this->getLast();
            }
        }
        return $response;
    }

    function post($body)
    {
        $connection = $this->database->connection;
        if (isset($body) && isset($body->tableName) && isset($body->data)) {
            $tableName  = $body->tableName;
            $sortOrder  = (int)$body->sortOrder;
            $enabled    = (int)$body->enabled;
            $data       = $body->data;

            if ($data) {
                $query = "INSERT INTO `data` (`tableName`, `data`, `sortOrder`, `enabled`) VALUES (:tableName, :data, :sortOrder, :enabled);";
                $stmt = $connection->prepare($query);
                $stmt->bindValue(":tableName", $tableName);
                $stmt->bindValue(":data", json_encode($data));
                $stmt->bindValue(":sortOrder", $sortOrder);
                $stmt->bindValue(":enabled", $enabled);

                if ($stmt->execute()) {
                    return $this->getLast();
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function patch($body)
    {
        $connection = $this->database->connection;
        if (isset($body) && isset($body->tableName) && isset($body->id) && isset($body->data)) {
            $id         = (int)$body->id;
            $sortOrder  = (int)$body->sortOrder;
            $enabled    = (int)$body->enabled;
            $data       = $body->data;

            $query = "UPDATE `data` SET `data` = :data,  `sortOrder` = :sortOrder,  `enabled` = :enabled, `UpdateDateTime` = CURRENT_TIMESTAMP WHERE `data`.`id` = :id;";
            $stmt = $connection->prepare($query);
            $stmt->bindValue(":data", json_encode($data));
            $stmt->bindValue(":sortOrder", $sortOrder);
            $stmt->bindValue(":enabled", $enabled);
            $stmt->bindValue(":id", $id);

            if ($stmt->execute()) {
                return $this->getById($id);
            }
        } else {
            return false;
        }
    }

    private function getById($id)
    {
        return $this->getDataRow($id);
    }

    private function getLast()
    {
        return $this->getDataRow(-1);
    }

    private function getDataRow($id = -1)
    {
        $connection = $this->database->connection;
        if ($id === -1) {
            $query = "SELECT * FROM `data` ORDER BY `id` DESC LIMIT 1;";
        } else {
            $query = "SELECT * FROM `data` WHERE `id` = $id;";
        }
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $rows = $this->rowsToArray($stmt);
        if (isset($rows[0])) {
            return $this->castDataObject($rows[0]);
        }
        return null;
    }

    private function castDataObject($dataObject)
    {
        $dataObject->id        = (int)$dataObject->id;
        $dataObject->sortOrder = (int)$dataObject->sortOrder;
        $dataObject->enabled   = (int)$dataObject->enabled;

        if (gettype($dataObject->data) === 'string' && json_decode($dataObject->data)) {
            $dataObject->data  = json_decode($dataObject->data);
        }

        return $dataObject;
    }

    private function rowsToArray($stmt)
    {
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }
}
