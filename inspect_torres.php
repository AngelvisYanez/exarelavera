<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';
require_once __DIR__ . '/administrador/LOGICA/adm_sql_login.php';

$obMaster = new MysqlConexion();
$obDatos = new MysqlDatos();

$sql = sentencias_log(1, ['22600781']);
$res = $obDatos->getArrayConsultaSql($sql, $obMaster);

echo json_encode([
    'sql' => $sql,
    'total' => count($res),
    'empresas' => $res
], JSON_PRETTY_PRINT) . "\n";
