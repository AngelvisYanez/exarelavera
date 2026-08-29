<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(__DIR__ . '/administrador/FRONT');

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';
require_once __DIR__ . '/administrador/LOGICA/adm_log_login.php';
require_once __DIR__ . '/administrador/LOGICA/adm_log_control.php';
require_once __DIR__ . '/administrador/LOGICA/adm_log_menu_tree.php';
require_once __DIR__ . '/administrador/LOGICA/adm_sql_menu.php';

$ced = '22600781';
$pass = '123456';

// Step 1: Query empresas in login page
$obLoginConn = new Class_Log_Conexion_Log();
$obLoginDatos = new Class_Log_Datos_Log();
$rs_empresas = $obLoginDatos->getArrayConsulta(1, $ced, $obLoginConn);

// Step 2: Simulate login with the first empresa found (or all empresas)
$empResults = [];
foreach ($rs_empresas as $emp) {
    $empCod = (int)$emp['Emp_Cod'];
    $sucCod = (int)$emp['Suc_Cod'];
    
    // In adm_con_control_1.2.php:
    $obCtrlConn = new Class_Log_Conexion_Cnt();
    $obCtrlDatos = new Class_Log_Datos_Cnt();
    $row_data = $obCtrlDatos->getRowConsulta(2, $empCod . '*' . $ced, $obCtrlConn);
    
    $bddName = (!empty($row_data) && !empty($row_data['Dat_Dis'])) ? $row_data['Dat_Dis'] : 'exa';
    $obDistConn = new Class_Log_Conexion_Cnt($bddName);
    
    $user_sql = "SELECT 
        usuarios.Usu_Ced, usuarios.Usu_Est, usuarios.Suc_Cod, sucursal.Emp_Cod,
        usuarios.Prs_Cod, usuarios.Usu_Cod, usuarios.Usu_Tip,
        persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Sex,
        usuarios.Usu_Cad, usuarios.Usu_Pal, usuarios.Usu_Men,
        empresas.Emp_Nom, empresas.Emp_Log, sucursal.Suc_Des,
        empresas.Emp_Cor, sucursal.Suc_Web
    FROM usuarios
    INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
    INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
    INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
    WHERE Usu_Ced = '$ced' AND empresas.Emp_Cod = $empCod AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A' AND sucursal.Suc_Cod = $sucCod";
    
    $userRow = $obCtrlDatos->getRowConsultaSql($user_sql, $obDistConn);
    
    // Profiles
    $profiles = [];
    $menuHtml = '';
    if ($userRow) {
        $rs_perfiles = $obCtrlDatos->getArrayConsulta(21, $userRow["Usu_Cod"], $obDistConn);
        $lperf = [];
        if (is_array($rs_perfiles)) {
            foreach ($rs_perfiles as $v0) {
                $lperf[] = $v0["Per_Cod"];
            }
        }
        $profiles = $lperf;
        
        // Menu generation
        $menuTree = new Class_Sys_Menu();
        $menuObj = $menuTree->getMenuContainer2($lperf, $obDistConn);
        $menuHtml = $menuTree->menuToHtml(1, $menuObj, 'nav nav-list', 'hover');
    }
    
    $empResults[] = [
        'emp' => $emp,
        'row_data' => $row_data,
        'bddName' => $bddName,
        'userFound' => !empty($userRow),
        'userRow' => $userRow,
        'profiles' => $profiles,
        'menuLength' => strlen($menuHtml),
        'menuSnippet' => substr($menuHtml, 0, 300)
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ced' => $ced,
    'totalEmpresas' => count($rs_empresas),
    'empresas' => $rs_empresas,
    'results' => $empResults
], JSON_PRETTY_PRINT);
