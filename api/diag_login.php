<?php
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';

$con = new MysqlConexion('exa_master');
$datos = new MysqlDatos();

$response = [];

// 1. Conteo total de usuarios y personas
$response['total_usuarios'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM usuarios", $con);
$response['total_personas'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM persona", $con);
$response['total_sucursales'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM sucursal", $con);
$response['total_empresas'] = $datos->getArrayConsultaSql("SELECT COUNT(*) AS total FROM empresas", $con);

// 2. Primeros 50 usuarios
$response['sample_usuarios'] = $datos->getArrayConsultaSql("SELECT Usu_Cod, Usu_Ced, Prs_Cod, Suc_Cod, Usu_Est, Usu_Pal FROM usuarios LIMIT 50", $con);

// 3. Buscar cualquier coincidencia con 2260, 1676, torres, carrion en TODO
$response['find_2260'] = $datos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced LIKE '%2260%'", $con);
$response['find_1676'] = $datos->getArrayConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced LIKE '%1676%'", $con);
$response['find_prs_2260'] = $datos->getArrayConsultaSql("SELECT * FROM persona WHERE Prs_Ced LIKE '%2260%' OR Prs_Nom LIKE '%2260%'", $con);
$response['find_prs_1676'] = $datos->getArrayConsultaSql("SELECT * FROM persona WHERE Prs_Ced LIKE '%1676%' OR Prs_Nom LIKE '%1676%'", $con);

// 4. Buscar sucursales de empresas que contengan "TORRES" o "CARRION"
$response['sucursales_tc'] = $datos->getArrayConsultaSql(
    "SELECT s.Suc_Cod, s.Suc_Des, s.Emp_Cod, e.Emp_Nom, e.Emp_Cor, e.Emp_Ruc 
       FROM sucursal s
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE e.Emp_Nom LIKE '%TORRES%' OR e.Emp_Nom LIKE '%CARRION%'",
    $con
);

// 5. Ver si hay usuarios en esas sucursales
$response['usuarios_en_sucursales_tc'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Est, s.Suc_Cod, s.Suc_Des, e.Emp_Cod, e.Emp_Nom
       FROM usuarios u
       JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE e.Emp_Nom LIKE '%TORRES%' OR e.Emp_Nom LIKE '%CARRION%'",
    $con
);

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
