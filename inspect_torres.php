<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';

$obMaster = new MysqlConexion();
$obDatos = new MysqlDatos();

$allEmp = $obDatos->getArrayConsultaSql("SELECT Emp_Cod, Emp_Nom, Emp_Ruc, Dat_Dis FROM empresas ORDER BY Emp_Cod ASC", $obMaster);

echo json_encode($allEmp, JSON_PRETTY_PRINT) . "\n";
