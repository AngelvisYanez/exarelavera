<?php
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';

$con = new MysqlConexion(); // base 'exa'
$datos = new MysqlDatos();

$response = [];

// 1. Coincidencias de usuarios
$response['usuarios_22600781'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, u.Usu_Tip, u.Suc_Cod,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced,
            s.Emp_Cod, e.Emp_Nom, e.Emp_Cor, s.Suc_Des
       FROM usuarios u
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
       LEFT JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       LEFT JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE u.Usu_Ced = '22600781' OR p.Prs_Ced = '22600781'",
    $con
);

$response['usuarios_1676514'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, u.Usu_Tip, u.Suc_Cod,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced,
            s.Emp_Cod, e.Emp_Nom, e.Emp_Cor, s.Suc_Des
       FROM usuarios u
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
       LEFT JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       LEFT JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE u.Usu_Ced = '1676514' OR p.Prs_Ced = '1676514'",
    $con
);

// 2. Coincidencias de empresas Torres Carrion
$response['empresas_torres_carrion'] = $datos->getArrayConsultaSql(
    "SELECT s.Suc_Cod, s.Suc_Des, s.Emp_Cod, e.Emp_Nom, e.Emp_Cor, e.Emp_Ruc, e.Emp_Est
       FROM sucursal s
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE e.Emp_Nom LIKE '%TORRES%' AND e.Emp_Nom LIKE '%CARRION%'",
    $con
);

// 3. Usuarios de esas empresas
$response['usuarios_de_empresas_tc'] = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est,
            p.Prs_Nom, p.Prs_Ape, p.Prs_Ced,
            s.Suc_Cod, s.Suc_Des, e.Emp_Cod, e.Emp_Nom, e.Emp_Cor
       FROM usuarios u
       JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
       LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
      WHERE e.Emp_Nom LIKE '%TORRES%' AND e.Emp_Nom LIKE '%CARRION%'",
    $con
);

// 4. Probar la query exacta de empresas para 22600781 y 1676514
$response['query_empresas_22600781'] = $datos->getArrayConsultaSql(
    "SELECT sucursal.Suc_Cod, sucursal.Suc_Des, sucursal.Emp_Cod,
            usuarios.Usu_Cod, empresas.Emp_Nom, empresas.Emp_Cor
       FROM usuarios
      INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
      INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
      WHERE usuarios.Usu_Ced = '22600781' AND empresas.Emp_Est = 'A' AND usuarios.Usu_Est = 'A'
      ORDER BY empresas.Emp_Cor ASC",
    $con
);

$response['query_empresas_1676514'] = $datos->getArrayConsultaSql(
    "SELECT sucursal.Suc_Cod, sucursal.Suc_Des, sucursal.Emp_Cod,
            usuarios.Usu_Cod, empresas.Emp_Nom, empresas.Emp_Cor
       FROM usuarios
      INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
      INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
      WHERE usuarios.Usu_Ced = '1676514' AND empresas.Emp_Est = 'A' AND usuarios.Usu_Est = 'A'
      ORDER BY empresas.Emp_Cor ASC",
    $con
);

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
