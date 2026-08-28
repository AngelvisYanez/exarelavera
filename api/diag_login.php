<?php
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';

$con = new MysqlConexion('exa_master');
$datos = new MysqlDatos();

$response = [];

// 1. Usuarios vinculados a empresas 68, 96, 387
$response['usuarios_empresas_tc'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, u.Usu_Tip, u.Suc_Cod,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced,
            s.Emp_Cod, e.Emp_Nom, e.Emp_Cor, d.Dat_Dis AS Bdd
       FROM usuarios u
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
       LEFT JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       LEFT JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
       LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
      WHERE s.Emp_Cod IN (68, 96, 387)
         OR u.Usu_Ced LIKE '%22600781%'
         OR u.Usu_Ced LIKE '%1676514%'
         OR p.Prs_Nom LIKE '%TORRES%'
         OR p.Prs_Ape LIKE '%TORRES%'",
    $con
);

// 2. Buscar en la tabla persona
$response['personas_match'] = $datos->getArrayConsultaSql(
    "SELECT p.* FROM persona p WHERE p.Prs_Ced LIKE '%22600781%' OR p.Prs_Ced LIKE '%1676514%' OR p.Prs_Nom LIKE '%TORRES%' OR p.Prs_Ape LIKE '%TORRES%'",
    $con
);

// 3. Buscar en la base de datos de datos distribuida de la empresa 96 (si existe)
$rowEmp96 = $datos->getRowConsultaSql("SELECT Dat_Dis FROM data WHERE Emp_Cod = 96", $con);
$bdd96 = !empty($rowEmp96['Dat_Dis']) ? $rowEmp96['Dat_Dis'] : '';
$response['bdd_emp_96'] = $bdd96;

if ($bdd96) {
    $con96 = new MysqlConexion($bdd96);
    $response['usuarios_en_bdd96'] = $datos->getArrayConsultaSql(
        "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced
           FROM usuarios u
           LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod",
        $con96
    );
}

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
