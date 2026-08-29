<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../classes/DataAPI.php';
    require_once __DIR__ . '/../classes/APITokenManager.php';

    $api = new DataAPI('exa');
    $tokenRaw = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $tokenHash = hash('sha256', $tokenRaw);

    $rawRows = $api->query("SELECT * FROM api_tokens");
    
    // If empty, insert token 1
    if (empty($rawRows)) {
        $api->query("INSERT INTO api_tokens (Tok_Id, Tok_Nombre, Tok_Hash, Tok_Resumen, Emp_Cod, Tok_Bdd, Tok_Cuota, Tok_Periodo, Tok_Usadas, Tok_Periodo_Inicio, Tok_Est, Tok_Creado_Por, Tok_Fec_Crea) VALUES (1, 'ERP Locator Token', '$tokenHash', '8e316143', 620, 'ecoparkmining', 0, 'D', 0, NOW(), 'A', 1, NOW())");
    } else {
        // Update Tok_Hash of token 1 to make sure it matches
        $api->query("UPDATE api_tokens SET Tok_Hash = '$tokenHash', Tok_Est = 'A', Tok_Cuota = 0 WHERE Tok_Id = 1");
    }

    $rawRowsAfter = $api->query("SELECT * FROM api_tokens");
    $mgr = new APITokenManager();
    $val = $mgr->validate($tokenRaw, false);

    echo json_encode([
        'tokenRaw' => $tokenRaw,
        'tokenHash' => $tokenHash,
        'rawRows' => $rawRowsAfter,
        'val' => $val
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
