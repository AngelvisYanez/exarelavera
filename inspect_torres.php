<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';

$obMaster = new MysqlConexion();
$obDatos = new MysqlDatos();

$empresasTorres = $obDatos->getArrayConsultaSql("
    SELECT e.Emp_Cod, e.Emp_Nom, e.Dat_Dis, s.Suc_Cod, s.Suc_Des 
    FROM empresas e 
    LEFT JOIN sucursal s ON e.Emp_Cod = s.Emp_Cod 
    WHERE e.Emp_Nom LIKE '%Torres%' OR e.Emp_Nom LIKE '%Carrion%' OR e.Emp_Nom LIKE '%torres%'
", $obMaster);

echo json_encode($empresasTorres, JSON_PRETTY_PRINT) . "\n";
