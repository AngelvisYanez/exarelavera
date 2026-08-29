<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $api = new DataAPI('exa');
    
    // Create api_tokens table if not exists
    $api->query("
        CREATE TABLE IF NOT EXISTS `api_tokens` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `Emp_Cod` int(11) NOT NULL,
          `nombre` varchar(100) NOT NULL,
          `token_hash` char(64) NOT NULL,
          `token_prefix` varchar(12) NOT NULL,
          `limite_tipo` enum('D','M','NONE') NOT NULL DEFAULT 'NONE',
          `limite_cantidad` int(11) NOT NULL DEFAULT 0,
          `usos_hoy` int(11) NOT NULL DEFAULT 0,
          `usos_mes` int(11) NOT NULL DEFAULT 0,
          `ultimo_uso` datetime DEFAULT NULL,
          `modulos` text DEFAULT NULL,
          `activo` tinyint(1) NOT NULL DEFAULT 1,
          `creado_el` datetime NOT NULL,
          `expira_en` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_token_hash` (`token_hash`),
          KEY `idx_empresa` (`Emp_Cod`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $tokenHash = hash('sha256', $tokenRaw);

    // Delete existing with same prefix or hash to be clean
    $api->query("DELETE FROM api_tokens WHERE token_hash = '$tokenHash' OR token_prefix = '8e316143'");

    $inserted = $api->insert('api_tokens', [
        'Emp_Cod' => 620,
        'nombre' => 'ERP Locator Token',
        'token_hash' => $tokenHash,
        'token_prefix' => substr($tokenRaw, 0, 8),
        'limite_tipo' => 'NONE',
        'limite_cantidad' => 0,
        'modulos' => json_encode(['*']),
        'activo' => 1,
        'creado_el' => date('Y-m-d H:i:s')
    ]);

    $mgr = new APITokenManager('exa');
    $val = $mgr->validate($tokenRaw, false);

    echo json_encode([
        'success' => true,
        'inserted' => $inserted,
        'validation' => $val,
        'rows' => $api->query("SELECT id, Emp_Cod, nombre, token_prefix, activo, creado_el FROM api_tokens")
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
