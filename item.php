<?php
include_once 'database.php';

class Item
{
    private $httpResponseCode;
    private $rows;
    private $columns;

    function __construct($table, $id, $body, $requesterUser)
    {
        $this->database = new Database();
        $this->table = $table;
        $this->id = $id;
        $this->body = $body;

        $this->requesterUser = $requesterUser;

        $this->httpResponseCode = 0;
    }

    function get()
    {
        if (
            !(($this->table == 'z_users') && !is_numeric($this->id))
        ) {
            $this->getColumns();
            $data['data']['columns'] = base64_encode(json_encode($this->columns));
        }
        $this->getRows();

        $data['data']['rows'] = $this->rows;
        $data['httpResponseCode'] = $this->httpResponseCode;
        return $data;
    }

    private function getAllRows()
    {
        return (is_null($this->id) && empty($this->id));
    }

    private function getColumns()
    {
        if ($this->table == 'TABLES') {
            return [];
        } else {
            $connection = $this->database->connection;
            $query = "SELECT `COLUMN_NAME`, `IS_NULLABLE`, `DATA_TYPE`, `CHARACTER_MAXIMUM_LENGTH`, `ORDINAL_POSITION`, `COLUMN_TYPE`,
                         IF(`COLUMN_COMMENT` IS NULL OR `COLUMN_COMMENT` = '', `COLUMN_NAME`, `COLUMN_COMMENT`) as COLUMN_COMMENT
                    FROM `information_schema`.`COLUMNS`
                   WHERE `TABLE_SCHEMA` = '" . $this->database->getDBName() . "'
                     AND `TABLE_NAME` = '" . $this->table . "'
                     ";
            if ($this->getAllRows()) {
                $query .= " AND `DATA_TYPE` <> 'text'";
            }

            $query .= " ORDER BY `ORDINAL_POSITION`;";

            $stmt = $connection->prepare($query);
            $stmt->execute();
            $this->httpResponseCode = 200;
            $this->columns = $this->rowsToArray($stmt);
        }
    }

    private function getRows()
    {
        $connection = $this->database->connection;
        $executeDefaultStatement = true;
        if ($this->table == 'TABLES') {
            $query = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '" . $this->database->getDBName() . "' AND `TABLE_TYPE` = 'BASE TABLE' ORDER BY `TABLE_NAME` ASC;";
            $stmt = $connection->prepare($query);
        } else if (($this->table == 'z_users') && !is_numeric($this->id)) {
            $this->id = base64_decode($this->id);
            $query = "SELECT `given_name`, `family_name`, `email`, `user_image` FROM `z_users` WHERE `email` = '$this->id';";
            $stmt = $connection->prepare($query);
        } else if ($this->table == 'userMenu') {
            $executeDefaultStatement = false;
            $this->rows = $this->getMenuForUser();
        } else {
            if ($this->getAllRows()) { // ver que pasa con este valor
                $query = "SELECT ";
                foreach ($this->columns as $column) {
                    if ($column->DATA_TYPE != 'text') {
                        $query .= "`" . $column->COLUMN_NAME . "`, ";
                    }
                }
                $query = substr($query, 0, -2);
                $query .= " FROM `$this->table`";
                $stmt = $connection->prepare($query);
            } else {
                $query = "SELECT * FROM `$this->table` WHERE `id` = :id;";
                $stmt = $connection->prepare($query);
                $stmt->bindParam(":id", $this->id);
            }
        }

        if ($executeDefaultStatement) {
            if ($stmt->execute()) {
                if ($stmt->rowCount()) {
                    $this->httpResponseCode = 200;
                } else {
                    $this->httpResponseCode = 404;
                }
                $this->rows = $this->rowsToArray($stmt);
            } else {
                $this->httpResponseCode = 400;
            }
        }
    }

    function delete()
    {
        if (is_null($this->id)) {
            http_response_code(400);
        } else {
            $connection = $this->database->connection;
            $query = "DELETE FROM `$this->table` WHERE `id` = :id;";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(":id", $this->id);
            if ($stmt->execute()) {
                if ($stmt->rowCount()) {
                    return 200;
                } else {
                    return 204;
                }
            } else {
                return 400;
            }
        }
    }

    function post()
    {
        $connection = $this->database->connection;

        if ($this->table == 'validateUser') {
            $email = htmlspecialchars(strip_tags(trim($this->body->email)));
            $query = "SELECT * FROM  `z_users` WHERE `email` = '$email';";
            $stmt = $connection->prepare($query);
            $stmt->execute();

            $userExists = false;
            $userProfile = null;
            $userEnabled = 0;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userExists = true;
                $userProfile = $row['profile'];
                $userEnabled = $row['enable'];
                $id = $row['id'];
            }

            $this->table = 'z_users';
            if (!$userExists) {
                $this->insertRow();
                return 401;
            }
            if (is_null($userProfile) || ($userEnabled != 1)) {
                return 401;
            }
            $this->body->id = $id;
            $this->patch();
            return 200;
        } else {
            $this->insertRow();
        }
    }

    private function insertRow()
    {
        $connection = $this->database->connection;
        $query = "INSERT INTO `$this->table` SET ";
        foreach ($this->body as $key => $value) {
            if ($key != 'id') {
                $query .= "`$key`=:$key, ";
            }
        }
        $query = substr($query, 0, -2);
        $stmt = $connection->prepare($query);
        $arrParams = [];
        $paramId = 0;
        foreach ($this->body as $key => $value) {
            if ($key != 'id') {
                $arrParams[] = htmlspecialchars(strip_tags($value));
                $stmt->bindParam(":$key", $arrParams[$paramId]);
                $paramId++;
            }
        }

        if ($stmt->execute()) {
            return 200;
        } else {
            return 400;
        }
    }

    function patch()
    {
        $connection = $this->database->connection;

        $query = "UPDATE `$this->table` SET ";
        foreach ($this->body as $key => $value) {
            if ($key != 'id') {
                $query .= "`$key`=:$key, ";
            }
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE `id`=:id;";

        $stmt = $connection->prepare($query);
        $arrParams = [];
        $paramId = 0;
        foreach ($this->body as $key => $value) {
            $arrParams[] = htmlspecialchars(strip_tags($value));
            $stmt->bindParam(":$key", $arrParams[$paramId]);
            $paramId++;
        }

        if ($stmt->execute()) {
            if ($stmt->rowCount()) {
                return 200;
            } else {
                return 204;
            }
        } else {
            return 400;
        }
    }

    private function rowsToArray($stmt)
    {
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    private function getMenuForUser()
    {
        $connection = $this->database->connection;
        $query = "SELECT `z_users`.`profile`, `z_profiles`.`admin` FROM `z_users` LEFT JOIN `z_profiles` ON `z_users`.`profile` = `z_profiles`.`id` WHERE `enable` = 1 AND `email` = '$this->requesterUser';";

        $stmt = $connection->prepare($query);
        if ($stmt->execute()) {
            if ($stmt->rowCount()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $userProfile = $row['profile'];
                $isAdmin = $row['admin'];

                if ($isAdmin == 1) {
                    $query = "SELECT `z_menu`.`menu`, `z_submenu`.`submenu`, `z_submenu`.`table`, `z_submenu`.`link`, `z_submenu`.`icon`,
                                      1 as `read`, 1 as `create`, 1 as `update`, 1 as `delete`
                                FROM `z_menu`
                                JOIN `z_submenu` ON `z_submenu`.`menu` = `z_menu`.`id`
                            ORDER BY `z_menu`.`order`, `z_submenu`.`order`
                             ";
                } else {
                    $query = "SELECT `z_menu`.`menu`, `z_submenu`.`submenu`, `z_submenu`.`table`, `z_submenu`.`link`, `z_submenu`.`icon`,
                                     `z_profile_submenu`.`read`, `z_profile_submenu`.`create`, `z_profile_submenu`.`update`, `z_profile_submenu`.`delete`
                                FROM `z_menu`
                                JOIN `z_submenu` ON `z_submenu`.`menu` = `z_menu`.`id`
                                JOIN `z_profile_submenu` ON `z_profile_submenu`.`submenu` = `z_submenu`.`id`
                               WHERE `z_profile_submenu`.`profile` = $userProfile
                            ORDER BY `z_menu`.`order`, `z_submenu`.`order`
                             ";
                }

                $stmt = $connection->prepare($query);
                $stmt->execute();

                $menu = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!array_key_exists($row['menu'], $menu)) {
                        $menu[$row['menu']] = [];
                        $menu[$row['menu']]['menu'] = $row['menu'];
                        $menu[$row['menu']]['items'] = [];
                    }
                    $menu[$row['menu']]['items'][] = $row;
                }
                $this->httpResponseCode = 200;
                return $menu;
            } else {
                $this->httpResponseCode = 401;
            }
        } else {
            $this->httpResponseCode = 401;
        }
        return null;
    }
}
