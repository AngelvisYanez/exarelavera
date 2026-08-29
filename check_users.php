<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';

$obMaster = new MysqlConexion();
$obDatos = new MysqlDatos();

echo "=== MASTER USERS ===\n";
$users = $obDatos->getArrayConsultaSql("SELECT Usu_Cod, Usu_Ced, Usu_Est, Suc_Cod FROM usuarios LIMIT 20", $obMaster);
echo json_encode($users, JSON_PRETTY_PRINT) . "\n";

echo "=== USER 22600781 in master ===\n";
$u = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced = '22600781'", $obMaster);
echo json_encode($u, JSON_PRETTY_PRINT) . "\n";

echo "=== USER 1676514 in master ===\n";
$u2 = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced = '1676514'", $obMaster);
echo json_encode($u2, JSON_PRETTY_PRINT) . "\n";

$obLocal = new MysqlConexion('ecoparkmining');
echo "=== USER 22600781 in ecoparkmining ===\n";
$ul = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced = '22600781'", $obLocal);
echo json_encode($ul, JSON_PRETTY_PRINT) . "\n";

echo "=== USER 1676514 in ecoparkmining ===\n";
$ul2 = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced = '1676514'", $obLocal);
echo json_encode($ul2, JSON_PRETTY_PRINT) . "\n";
