<?php
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';

header('Content-Type: application/json; charset=utf-8');

$con = new MysqlConexion();
$datos = new MysqlDatos();

$user6170 = $datos->getRowConsultaSql("SELECT Usu_Cod, Usu_Ced, Usu_Pal, Suc_Cod FROM usuarios WHERE Usu_Cod = 6170", $con);
$hash6170 = $user6170['Usu_Pal'];

$candidates = ['123456', '1676514', '22600781', 'admin', 'admin123', 'Angelvis', 'angelvis', 'exacontable', 'Torres', 'torres', 'torrescarrion'];
$results = [];

foreach ($candidates as $c) {
    $results[$c] = [
        'raw_verify' => password_verify($c, $hash6170),
        'md5_verify' => password_verify(md5($c), $hash6170),
        'double_md5_verify' => password_verify(md5(md5($c)), $hash6170)
    ];
}

// También listar todos los usuarios con Usu_Ced = 22600781 para ver todas las empresas y sucursales
$all_22600781 = $datos->getArrayConsultaSql(
    "SELECT u.Usu_Cod, u.Usu_Ced, u.Usu_Pal, u.Usu_Est, s.Suc_Cod, s.Suc_Des, e.Emp_Cod, e.Emp_Nom, e.Emp_Cor
       FROM usuarios u
       JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
       JOIN empresas e ON e.Emp_Cod = s.Emp_Cod
      WHERE u.Usu_Ced = '22600781'
      ORDER BY e.Emp_Cod ASC",
    $con
);

$response = [
    'user6170_hash' => $hash6170,
    'password_tests' => $results,
    'total_empresas_22600781' => count($all_22600781),
    'empresas_22600781' => $all_22600781
];

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
