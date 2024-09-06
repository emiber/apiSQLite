<?php
include_once 'database.php';

class Security
{
    private $method;
    private $table;
    private $user;

    function __construct($method, $table, $user)
    {
        $this->method = $method;
        $this->table = $table;
        $this->user = $user;
    }

    function getPermission()
    {
        $method = $this->method;
        if ($method === 'OPTIONS') {
            return true;
        }
        if ($this->user === '') {
            return false;
        }

        $table = $this->table;
        $enabled = $this->user->enabled;
        $sysAdmin = $this->user->sysAdmin;
        if ($sysAdmin || ($method === 'OPTIONS')) {
            return true;
        }
        if (!$enabled) {
            return false;
        }
        if (!$sysAdmin && ($table !== 'users')) {
            return true;
        }
        return false;
    }
}
