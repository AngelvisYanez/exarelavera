<?php
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../administrador/LOGICA/adm_log_menu_tree.php';

header('Content-Type: application/json; charset=utf-8');

$con = new MysqlConexion();
$datos = new MysqlDatos();

$user = $datos->getRowConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Men, u.Usu_Est, s.Suc_Cod, s.Emp_Cod
       FROM usuarios u
       JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
      WHERE u.Usu_Ced = '22600781' AND s.Emp_Cod = 96 LIMIT 1",
    $con
);

// Perfiles del usuario
$perfiles = $datos->getArrayConsultaSql(
    "SELECT p.Per_Cod, p.Per_Des
       FROM usuarioperfil up
       JOIN perfil p ON p.Per_Cod = up.Per_Cod
      WHERE up.Usu_Cod = " . (int)$user['Usu_Cod'],
    $con
);

$lperf = array_column($perfiles, 'Per_Cod');

$menu = new Class_Sys_Menu();
$filter = $menu->buildProfileFilter($lperf);
$org = $menu->getArrayConsulta(2, '*' . $filter, $con);
$procs = $menu->getArrayConsulta(3, '*' . $filter . '*P', $con);

$res = [
    'user' => $user,
    'perfiles' => $perfiles,
    'lperf' => $lperf,
    'filter' => $filter,
    'total_org' => count($org),
    'org_samples' => array_slice($org, 0, 10),
    'total_procs' => count($procs),
    'procs_samples' => array_slice($procs, 0, 10)
];

utf8_encode_deep($res);
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
