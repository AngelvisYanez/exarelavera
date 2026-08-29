<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $mgr = new APITokenManager();
    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    
    // Set permissions for token 1 to include contactos, choferes, plantas, vehiculos
    $mgr->setPermisos(1, [
        '/v1/contactos',
        '/v1/plantas',
        '/v1/choferes',
        '/v1/vehiculos',
        '/api/v1/contactos',
        '/api/v1/plantas',
        '/api/v1/choferes',
        '/api/v1/vehiculos'
    ]);

    $val = $mgr->validate($tokenRaw, false);

    echo json_encode([
        'success' => true,
        'validation' => $val,
        'permisos' => $mgr->getPermisos(1),
        'tokens' => $mgr->listAll()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
