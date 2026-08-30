<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
chdir(__DIR__ . '/..');
require_once 'vendor/autoload.php';
require_once 'DATA/MysqlConexion.php';
require_once 'DATA/MysqlDatos.php';
require_once 'classes/DataAPI.php';

echo "DataAPI loaded\n";

$api = new DataAPI('servicios');
$rows = $api->query('SHOW TABLES');
echo "Tables: " . count($rows) . "\n";

$targets = [
    'productor_banano', 'banano_marca', 'liquidacion_bana', 'exportacion_container', 'labores',
    'naviera_container', 'naviera_exporta', 'naviera_vapor', 'viaje',
    'plan_cuenta', 'perio_cont', 'comprobantes', 'comprobantes_det', 'tipo_compr', 'tipo_compro',
    'personal', 'departamen', 'departamento', 'contratos_lab', 'contrato', 'rol_pagos', 'cargo', 'tiposcargo', 'tipo_personal',
    'bodega', 'kardex_ie', 'stock', 'movimiento_bodega',
    'requisicion', 'requisicion_det',
    'activo', 'tipo_activo', 'custodio', 'tipo_mante', 'activo_deprecia', 'mantenimie',
    'caja_chica', 'reposicion', 'cab_reposicio',
    'vehiculo', 'tickets', 'transporte', 'chofer',
    'productor_camaron', 'nego_camaron', 'liquidacion', 'liqui_camaron',
    'banco', 'bancos', 'cheques', 'cheques_ext', 'cheques_otros', 'conciliacion_bancaria', 'ccpp_pagar', 'ccpp_cobrar', 'cheq_det_ccpp',
    'manifiesto',
];

$allTables = [];
foreach ($rows as $r) {
    $val = reset($r);
    if ($val) $allTables[] = $val;
}

foreach ($targets as $t) {
    if (in_array($t, $allTables)) {
        $cols = $api->query("DESCRIBE `$t`");
        $colNames = array_map(function($c) { return $c['Field']; }, $cols);
        echo "OK $t: " . implode(', ', $colNames) . "\n";
    } else {
        $matched = [];
        foreach ($allTables as $dbt) {
            if (stripos($dbt, $t) !== false || stripos($t, $dbt) !== false) {
                $matched[] = $dbt;
            }
        }
        if (!empty($matched)) {
            echo "MISS $t => possible: " . implode('|', $matched) . "\n";
        } else {
            echo "MISS $t => NOT FOUND\n";
        }
    }
}
