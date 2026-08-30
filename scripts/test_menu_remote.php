<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(__DIR__ . '/../administrador/FRONT');

require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../administrador/LOGICA/adm_log_menu_tree.php';
require_once __DIR__ . '/../administrador/LOGICA/adm_sql_menu.php';

$obLocal = new MysqlConexion('ecoparkmining');
$obDatos = new MysqlDatos();

// Query all profiles in ecoparkmining
$profiles = $obDatos->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles", $obLocal);
$perIds = array_map(function($p){ return (int)$p['Per_Cod']; }, $profiles);

$menuTree = new Class_Sys_Menu();
$menuObj = $menuTree->getMenuContainer2($perIds, $obLocal);
$menuHtml = $menuTree->menuToHtml(1, $menuObj, 'nav nav-list', 'hover');

$result = [
    'profilesCount' => count($profiles),
    'perIds' => $perIds,
    'menuHtmlLength' => strlen($menuHtml),
    'menuHtmlSample' => substr($menuHtml, 0, 800)
];

file_put_contents('/var/www/vhosts/api.exacontable.com/httpdocs/remote_menu_status.json', json_encode($result, JSON_PRETTY_PRINT));
echo "DONE! Length: " . strlen($menuHtml) . "\n";
