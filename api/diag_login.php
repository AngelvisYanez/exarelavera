<?php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../DATA/MysqlConexion.php';
    require_once __DIR__ . '/../DATA/MysqlDatos.php';
    require_once __DIR__ . '/../tesoreria/LOGICA/tes_log_cliente.php';
    require_once __DIR__ . '/../adquisiciones/LOGICA/adq_log_provee.php';
    require_once __DIR__ . '/../administrador/LOGICA/adm_log_menu_tree.php';

    $exists1 = class_exists('MysqlDatosContab');
    $exists2 = class_exists('Class_Log_Datos_Cli');

    echo json_encode([
        'success' => true,
        'MysqlDatosContab' => $exists1,
        'Class_Log_Datos_Cli' => $exists2,
        'php' => PHP_VERSION
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
