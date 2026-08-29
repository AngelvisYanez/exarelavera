<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $tokenHash = hash('sha256', $tokenRaw);

    $mgr = new APITokenManager('exa');
    
    $sql = "
        SELECT t.*, e.Emp_Nom, COALESCE(t.Tok_Bdd, d.Dat_Dis, 'ecoparkmining') as Bdd
          FROM api_tokens t
     LEFT JOIN empresas e ON e.Emp_Cod = t.Emp_Cod
     LEFT JOIN data d ON d.Emp_Cod = t.Emp_Cod
         WHERE t.Tok_Hash = '$tokenHash'
         LIMIT 1
    ";
    
    $resRow = $mgr->findByRaw($tokenRaw);
    $manualRow = (new DataAPI('exa'))->queryRow($sql);

    echo json_encode([
        'tokenRaw' => $tokenRaw,
        'tokenHash' => $tokenHash,
        'sql' => $sql,
        'manualRow' => $manualRow,
        'findByRaw' => $resRow,
        'validate' => $mgr->validate($tokenRaw, false)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
