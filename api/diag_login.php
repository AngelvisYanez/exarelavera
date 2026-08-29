<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';

    $api = new DataAPI('exa');
    $cols = $api->query("DESCRIBE api_tokens");
    $rows = $api->query("SELECT * FROM api_tokens");

    echo json_encode([
        'cols' => $cols,
        'rows' => $rows
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
