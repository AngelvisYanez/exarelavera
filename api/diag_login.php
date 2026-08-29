<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $api = new DataAPI('exa');
    
    // Check tables
    $tables = $api->query("SHOW TABLES LIKE '%api_token%'");

    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $tokenHash = hash('sha256', $tokenRaw);

    $sql = "INSERT INTO `api_tokens` (`Emp_Cod`, `nombre`, `token_hash`, `token_prefix`, `limite_tipo`, `limite_cantidad`, `modulos`, `activo`, `creado_el`) VALUES (620, 'ERP Locator Token', '$tokenHash', '8e316143', 'NONE', 0, '[\"*\"]', 1, NOW())";
    
    $res = $api->datos->consulta($sql, $api->conexion);
    $err = $api->conexion->Error;
    $errno = $api->conexion->Errno;

    $mgr = new APITokenManager();
    $val = $mgr->validate($tokenRaw, false);

    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'sql' => $sql,
        'res' => $res !== false,
        'err' => $err,
        'errno' => $errno,
        'validation' => $val,
        'tokens' => $api->query("SELECT * FROM api_tokens")
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
