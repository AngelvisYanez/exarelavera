<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';

    $api = new DataAPI('exa');
    
    $tables = $api->query("SHOW TABLES LIKE '%data%'");
    $emp620 = $api->query("SELECT * FROM empresas WHERE Emp_Cod = 620");
    $joinTest = $api->query("SELECT t.Tok_Id, t.Tok_Hash FROM api_tokens t WHERE t.Tok_Hash = 'b9033e7d59064208c76838f3200653fdb0e8c454ed2e0e94df68586aaa98f6b9'");

    echo json_encode([
        'tables' => $tables,
        'emp620' => $emp620,
        'joinTest' => $joinTest
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
