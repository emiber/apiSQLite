<?php
// Configuración de la conexión a MySQL
$mysqlHost = "localhost";
$mysqlDb = "casalinda";
$mysqlUser = "root";
$mysqlPass = "";

// Configuración de la conexión a SQLite
$sqlitePath = "database.sqlite";

try {
    // Conexión a MySQL
    $mysqlConnection = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8", $mysqlUser, $mysqlPass);
    $mysqlConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Conexión a SQLite
    $sqliteConnection = new PDO("sqlite:" . $sqlitePath);
    $sqliteConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los datos de la tabla 'data' en MySQL
    $mysqlQuery = "SELECT * FROM data";
    $mysqlStmt = $mysqlConnection->query($mysqlQuery);
    $rows = $mysqlStmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear la tabla 'data' en SQLite si no existe
    $columns = array_keys($rows[0]);
    $createTableQuery = "CREATE TABLE IF NOT EXISTS data (" . implode(" TEXT, ", $columns) . " TEXT)";
    $sqliteConnection->exec($createTableQuery);

    // Preparar la inserción de datos en SQLite
    $insertQuery = "INSERT INTO data (" . implode(", ", $columns) . ") VALUES (:" . implode(", :", $columns) . ")";
    $sqliteStmt = $sqliteConnection->prepare($insertQuery);

    // Insertar los datos en la tabla 'data' de SQLite
    foreach ($rows as $row) {
        $sqliteStmt->execute($row);
    }

    echo "Datos copiados exitosamente de MySQL a SQLite.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Cerrar las conexiones
$mysqlConnection = null;
$sqliteConnection = null;
