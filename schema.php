<?php
class Schema
{
    private $tables;
    private $schemaFileName;
    public function __construct($file)
    {
        $this->tables = [];
        $this->schemaFileName = $file;
    }

    function get()
    {
        $fields = null;
        if (($file = fopen($this->schemaFileName, "r")) !== FALSE) {
            while (($line = fgetcsv($file, 1000, ",")) !== FALSE) {
                if (!$fields) {
                    $fields = [];
                    $fields[] = ["table", "string"];
                    $fields[] = ["column", "string"];
                    $fields[] = ["displayName", "string"];
                    $fields[] = ["isNullable", "boolean"];
                    $fields[] = ["showInList", "boolean"];
                    $fields[] = ["dataType", "string"];
                    $fields[] = ["length", "number"];
                    $fields[] = ["position", "number"];
                    $fields[] = ["dafaultValue", "string"];
                    $fields[] = ["relationTable", "string"];
                    $fields[] = ["relationType", "string"];
                    $fields[] = ["link", "string"];
                    $fields[] = ["columnInForm", "number"];
                } else {
                    $tableIndex = $this->getTableIndex($line[0]);

                    if ($tableIndex == -1) {
                        $table = [];
                        $table['name'] = $line[0];
                        $table['fields'] = [];
                        $this->tables[] = $table;
                        $tableIndex = count($this->tables) - 1;
                    }
                    $row = [];
                    for ($i = 1; $i < count($fields); $i++) {
                        $row[$fields[$i][0]] = $this->parseValue($line[$i], $fields[$i][1]);
                    }
                    $this->tables[$tableIndex]['fields'][] = ($row);
                }
            }
            fclose($file);
            return $this->tables;
        }
    }

    private function getTableIndex($tableName)
    {
        for ($i = 0; $i < count($this->tables); $i++) {
            if ($this->tables[$i]['name'] == $tableName) {
                return $i;
            }
        }
        return -1;
    }

    private function parseValue($value, $type)
    {
        if ($value === "") {
            return null;
        }
        switch ($type) {
            case 'string':
                return $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return filter_var($value, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT);
            default:
                return $value;
        }
    }
}
