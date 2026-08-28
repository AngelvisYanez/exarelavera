<?php
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';

header('Content-Type: application/json; charset=utf-8');

$con = new MysqlConexion();
$datos = new MysqlDatos();

// Resetear password para 22600781 a md5('123456')
$md5 = md5('123456');
mysqli_query($con->conexion, "UPDATE usuarios SET Usu_Pal = '$md5' WHERE Usu_Ced = '22600781'");

$updated = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, s.Suc_Cod, s.Suc_Des, e.Emp_Cod, e.Emp_Nom, e.Emp_Cor
       FROM usuarios u
       JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE u.Usu_Ced = '22600781' AND (e.Emp_Nom LIKE '%TORRES%' OR e.Emp_Nom LIKE '%CARRION%')
      ORDER BY e.Emp_Cod ASC",
    $con
);

$response = ['status' => 'ready', 'records' => $updated];
utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
