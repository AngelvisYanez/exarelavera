<?php
chdir(__DIR__ . '/administrador/FRONT');

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';

header('Content-Type: application/json; charset=utf-8');

$obMaster = new MysqlConexion(); // exa
$obDatos = new MysqlDatos();

$masterUsers = $obDatos->getArrayConsultaSql("
    SELECT u.Usu_Cod, u.Usu_Ced, u.Suc_Cod, s.Emp_Cod, e.Emp_Nom, e.Dat_Dis 
    FROM usuarios u 
    LEFT JOIN sucursal s ON u.Suc_Cod = s.Suc_Cod 
    LEFT JOIN empresas e ON s.Emp_Cod = e.Emp_Cod 
    LIMIT 50
", $obMaster);

$obLocal = new MysqlConexion('ecoparkmining');
$localUsers = $obDatos->getArrayConsultaSql("
    SELECT u.Usu_Cod, u.Usu_Ced, u.Suc_Cod, p.Prs_Nom, p.Prs_Ape 
    FROM usuarios u 
    LEFT JOIN persona p ON u.Prs_Cod = p.Prs_Cod 
    LIMIT 50
", $obLocal);

// Look for 22600781 specifically anywhere
$searchMaster = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced LIKE '%22600781%' OR Usu_Cod LIKE '%22600781%'", $obMaster);
$searchLocal = $obDatos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced LIKE '%22600781%' OR Usu_Cod LIKE '%22600781%'", $obLocal);

$searchPersonaMaster = $obDatos->getArrayConsultaSql("SELECT * FROM persona WHERE Prs_Ced LIKE '%22600781%'", $obMaster);
$searchPersonaLocal = $obDatos->getArrayConsultaSql("SELECT * FROM persona WHERE Prs_Ced LIKE '%22600781%'", $obLocal);

echo json_encode([
    'searchMaster' => $searchMaster,
    'searchLocal' => $searchLocal,
    'searchPersonaMaster' => $searchPersonaMaster,
    'searchPersonaLocal' => $searchPersonaLocal,
    'masterUsers' => $masterUsers,
    'localUsers' => $localUsers,
], JSON_PRETTY_PRINT);
