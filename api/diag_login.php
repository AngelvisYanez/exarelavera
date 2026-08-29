<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';

    $api = new DataAPI('exa');
    
    $colsTokens = $api->query("DESCRIBE api_tokens");
    $colsPermisos = $api->query("DESCRIBE api_token_permisos");
    $permisos = $api->query("SELECT * FROM api_token_permisos");

    echo json_encode([
        'api_tokens_columns' => $colsTokens,
        'api_token_permisos_columns' => $colsPermisos,
        'api_token_permisos_rows' => $permisos
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
