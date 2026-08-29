<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $mgr = new APITokenManager('exa');
    
    $val = $mgr->validate($tokenRaw, false);
    $permisos = $mgr->getPermisos(1);
    $tokens = $mgr->listAll();

    echo json_encode([
        'success' => true,
        'val' => $val,
        'permisos' => $permisos,
        'tokens' => $tokens
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
