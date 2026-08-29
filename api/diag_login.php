<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $api = new DataAPI('exa');
    
    // Check if table api_tokens exists in exa
    $tableExists = $api->tableExists('api_tokens');
    
    // Check tables in exa
    $tables = $api->query("SHOW TABLES LIKE '%token%'");

    // Check ecoparkmining
    $apiEco = new DataAPI('ecoparkmining');
    $tablesEco = $apiEco->query("SHOW TABLES LIKE '%token%'");

    // Create api_tokens if not exists in exa
    if (!$tableExists) {
        $api->query("
            CREATE TABLE IF NOT EXISTS `api_tokens` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `Emp_Cod` int(11) NOT NULL,
              `nombre` varchar(100) NOT NULL,
              `token_hash` char(64) NOT NULL,
              `token_prefix` varchar(12) NOT NULL,
              `limite_tipo` enum('D','M','NONE') NOT NULL DEFAULT 'D',
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
    }

    // Insert ERP Locator token
    $fixedToken = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $fixedHash = hash('sha256', $fixedToken);
    
    $existing = $api->queryRow("SELECT * FROM api_tokens WHERE token_hash = '$fixedHash'");
    if (!$existing) {
        $api->insert('api_tokens', [
            'Emp_Cod' => 620,
            'nombre' => 'ERP Locator',
            'token_hash' => $fixedHash,
            'token_prefix' => substr($fixedToken, 0, 8),
            'limite_tipo' => 'NONE',
            'limite_cantidad' => 0,
            'modulos' => json_encode(['*']),
            'activo' => 1,
            'creado_el' => date('Y-m-d H:i:s')
        ]);
    }

    $mgr = new APITokenManager();
    $val = $mgr->validate($fixedToken, false);

    echo json_encode([
        'table_exists_before' => $tableExists,
        'tables_exa' => $tables,
        'tables_eco' => $tablesEco,
        'token_validation' => $val,
        'tokens_list' => $mgr->listAll()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
