<?php
include_once 'database.php';
ini_set('memory_limit', '1024M'); // or you could use 1G

class Data
{
    private $id;
    private $database;

    function __construct($id)
    {
        $this->database = new Database();
        $this->id = $id;
    }

    function get()
    {
        $connection = $this->database->connection;
        $query = "SELECT * FROM `data`";
        if ($this->id != "") {
            $query = $query . " WHERE `id` = $this->id";
        }
        $query = $query . " ORDER BY `tableName`, `sortOrder`;";

        $stmt = $connection->prepare($query);
        $stmt->execute();
        $rows = $this->rowsToArray($stmt);

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

            $query = "UPDATE `data` SET `data` = :data,  `sortOrder` = :sortOrder,  `enabled` = :enabled, `UpdateDateTime` = NOW() WHERE `data`.`id` = :id;";
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
