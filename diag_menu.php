<?php
chdir(__DIR__ . '/administrador/FRONT');

require_once __DIR__ . '/DATA/MysqlConexion.php';
require_once __DIR__ . '/DATA/MysqlDatos.php';
require_once __DIR__ . '/administrador/LOGICA/logica.php';
require_once __DIR__ . '/administrador/LOGICA/adm_log_menu_tree.php';
require_once __DIR__ . '/administrador/LOGICA/adm_sql_menu.php';

header('Content-Type: application/json; charset=utf-8');

$ced = '22600781';
$obMaster = new MysqlConexion(); // master exa
$obDatos = new MysqlDatos();

// 1. Get user in master / empresas
$empresas = $obDatos->getArrayConsultaSql("
    SELECT u.Usu_Cod, u.Usu_Ced, u.Suc_Cod, s.Emp_Cod, e.Emp_Nom, e.Dat_Dis 
    FROM usuarios u 
    INNER JOIN sucursal s ON u.Suc_Cod = s.Suc_Cod 
    INNER JOIN empresas e ON s.Emp_Cod = e.Emp_Cod 
    WHERE u.Usu_Ced = '$ced'
", $obMaster);

$dbLocal = 'ecoparkmining';
if (!empty($empresas[0]['Dat_Dis'])) {
    $dbLocal = $empresas[0]['Dat_Dis'];
}

$obLocal = new MysqlConexion($dbLocal);

// 2. Get user in local DB
$userLocal = $obDatos->getRowConsultaSql("
    SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Men, u.Usu_Tip, u.Suc_Cod, u.Prs_Cod 
    FROM usuarios u 
    WHERE u.Usu_Ced = '$ced'
", $obLocal);

// 3. Get profiles in local DB
$profiles = [];
if ($userLocal) {
    $profiles = $obDatos->getArrayConsultaSql("
        SELECT up.Per_Cod, p.Per_Des 
        FROM usuarperfi up 
        LEFT JOIN perfiles p ON up.Per_Cod = p.Per_Cod 
        WHERE up.Usu_Cod = " . (int)$userLocal['Usu_Cod'],
        $obLocal
    );
}

// 4. Test menu generation
$lisPer = array_map(function($p) { return $p['Per_Cod']; }, $profiles);
$menuTree = new Class_Sys_Menu();
$htmlMenu = '';
if (!empty($lisPer)) {
    $menuObj = $menuTree->getMenuContainer2($lisPer, $obLocal);
    $htmlMenu = $menuTree->menuToHtml(1, $menuObj, 'nav nav-list', '');
}

echo json_encode([
    'cedula' => $ced,
    'dbLocal' => $dbLocal,
    'empresas' => $empresas,
    'userLocal' => $userLocal,
    'profiles' => $profiles,
    'lisPer' => $lisPer,
    'htmlMenuLength' => strlen($htmlMenu),
    'htmlMenuSnippet' => substr($htmlMenu, 0, 500),
], JSON_PRETTY_PRINT);
