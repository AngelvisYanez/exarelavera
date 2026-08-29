<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';

    $api = new DataAPI('exa');
    $rows = $api->query("SELECT Tok_Id, Tok_Hash, LENGTH(Tok_Hash) as len, Tok_Nombre FROM api_tokens");
    $simpleRow = $api->query("SELECT * FROM api_tokens WHERE Tok_Id = 1");

    echo json_encode([
        'rows' => $rows,
        'simpleRow' => $simpleRow
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
