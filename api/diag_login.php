<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $mgr = new APITokenManager();
    $tokens = $mgr->listAll();

    // Check or create token for ecoparkmining (Empresa 620)
    $fixedToken = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $fixedHash = hash('sha256', $fixedToken);
    
    $api = new DataAPI('exa');
    $existing = $api->queryRow("SELECT * FROM api_tokens WHERE token_hash = '$fixedHash'");
    if (!$existing) {
        $api->insert('api_tokens', [
            'Emp_Cod' => 620,
            'nombre' => 'ERP Locator Token',
            'token_hash' => $fixedHash,
            'token_prefix' => substr($fixedToken, 0, 8),
            'limite_tipo' => 'NONE',
            'limite_cantidad' => 0,
            'modulos' => json_encode(['*']),
            'activo' => 1,
            'creado_el' => date('Y-m-d H:i:s')
        ]);
    }

    $val = $mgr->validate($fixedToken, false);

    echo json_encode([
        'success' => true,
        'tokens_count' => count($tokens),
        'fixed_token_validation' => $val,
        'tokens' => $tokens
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
