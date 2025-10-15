<?php

include_once './api/database.php';

if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    ini_set("zlib.output_compression", 4096);
}

header('Content-Type: application/json');

$table = $_GET['table'] ?? '';

if (empty($table)) {
    http_response_code(400);
    echo json_encode(['error' => 'El parámetro "table" es requerido']);
    exit;
}

try {
    $database = new Database();
    $query = "SELECT id, data FROM data WHERE tableName = :table ORDER BY sortOrder";
    $stmt = $database->connection->prepare($query);
    $stmt->bindParam(':table', $table, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar id con el contenido de data en el mismo objeto
    $output = [];
    foreach ($result as $row) {
        $dataContent = json_decode($row['data'], true);
        if (is_array($dataContent)) {
            $output[] = array_merge(['id' => $row['id']], $dataContent);
        } else {
            $output[] = ['id' => $row['id'], 'data' => $dataContent];
        }
    }
    
    echo json_encode($output);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
}
