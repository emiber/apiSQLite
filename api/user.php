<?php
include_once 'database.php';

class User
{
    private $database;
    private $sub;
    private $id;

    function __construct()
    {
        $this->database = new Database();
    }

    function get($sub)
    {
        $connection = $this->database->connection;
        $query = "SELECT * FROM `users`";
        if ($sub !== '') {
            $query = $query . " WHERE `sub` = '$sub';";
        }
        echo $sub;
        die;
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $rows = $this->rowsToArray($stmt);

        foreach ($rows as $row) {
            $row = $this->castDataObject($row);
        }

        if ($sub !== '') {
            if (count($rows) === 1) {
                return $rows[0];
            } else {
                return null;
            }
        } else {
            return $rows;
        }
    }

    function getPermissions($sub)
    {
        $this->setFirstSysAdmin();
        $permission = new \stdClass;

        if ($sub === '') {
            $permission->enabled = 0;
            $permission->sysAdmin = 0;
            return $permission;
        }

        $user_ = $this->get($sub);
        if ($user_) {
            $permission->enabled = $user_->enabled;
            $permission->sysAdmin = $user_->sysAdmin;
        } else {
            $permission->enabled = 0;
            $permission->sysAdmin = 0;
        }
        return $permission;
    }

    function patch($body)
    {
        try {
            $id = $body->id;
            $field = $body->field;
            $value = $body->value;
            $connection = $this->database->connection;
            $query = "UPDATE `users` SET `$field` = $value WHERE `id` = $id;";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    function delete()
    {
        $response = false;
        if (!is_null($this->id)) {
            $connection = $this->database->connection;
            $query = "DELETE FROM `users` WHERE `id` = $this->id";

            $stmt = $connection->prepare($query);
            $response = $stmt->execute();
        }
        return $response;
    }

    private function userExists($sub)
    {
        $connection = $this->database->connection;
        $query = "SELECT COUNT(1) FROM `users` WHERE `sub` = '$sub';";
        $stmt = $connection->prepare($query);
        $rows = $this->rowsToArray($stmt);
        return count($rows) === 1 ? true : false;
    }

    function save($data)
    {
        $connection = $this->database->connection;

        $sub = $data->sub;
        $user = json_encode($data);
        $exists = $this->userExists($sub);

        if (!$exists) {
            $query = "SELECT count(1) AS countUsers FROM `users`;";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $rows = $this->rowsToArray($stmt);
            $isEnabledAndAdmin = ($rows[0]->countUsers === 0) ? 1 : 0;
            $query = "INSERT INTO `users` (`sub`, `enabled`, `sysAdmin`, `user`) VALUES ('$sub', $isEnabledAndAdmin, $isEnabledAndAdmin, '$user');";
        } else {
            $enabled = isset($data->enabled) ? (int)$data->enabled : 0;
            $sysAdmin = isset($data->sysAdmin) ? (int)$data->sysAdmin : 0;
            $query = "UPDATE `users` SET `enabled` = '$enabled', `sysAdmin` = '$sysAdmin', `user` = '$user' WHERE `sub` = '$sub';";
        }

        $stmt = $connection->prepare($query);
        if ($stmt->execute()) {
            $this->sub = $sub;
            return $this->get($sub);
        } else {
            return false;
        }
    }

    private function setFirstSysAdmin()
    {
        $connection = $this->database->connection;
        $query = "SELECT count(1) AS countUsers FROM `users`;";
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $rows = $this->rowsToArray($stmt);
        $isFirstUser = ($rows[0]->countUsers == 1) ? true : false;
        if ($isFirstUser) {
            $query = "UPDATE `users` SET `enabled` = 1, `sysAdmin` = 1;";
            $stmt = $connection->prepare($query);
            $stmt->execute();
        }
    }

    private function castDataObject($dataObject)
    {
        $dataObject->id = (int)$dataObject->id;
        $dataObject->enabled = (int)$dataObject->enabled;
        $dataObject->sysAdmin = (int)$dataObject->sysAdmin;
        $dataObject->user = json_decode($dataObject->user);

        return $dataObject;
    }

    private function rowsToArray($stmt)
    {
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }
}
