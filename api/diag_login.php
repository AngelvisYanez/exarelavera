<?php
chdir(__DIR__ . '/../administrador/FRONT');

require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../DATA/MysqlConexion.php';
require_once __DIR__ . '/../DATA/MysqlDatos.php';
require_once __DIR__ . '/../administrador/LOGICA/adm_log_control.php';

header('Content-Type: application/json; charset=utf-8');

$user_name = '22600781';
$password = '123456';
$encryptor = md5('123456');
$Emp_Cod = 96;
$Suc_Cod = 99;

$obBD_conexion = new Class_Log_Conexion_Cnt;
$obBD_con1 = new Class_Log_Datos_Cnt;

$row_data = $obBD_con1->getRowConsulta(2, $Emp_Cod.'*'.$user_name, $obBD_conexion);
$bddName = (!empty($row_data) && !empty($row_data['Dat_Dis'])) ? $row_data['Dat_Dis'] : 'exa';

if (!empty($row_data) && !empty($row_data['Dat_Dis'])) {
    $obBD_conexion = new Class_Log_Conexion_Cnt($bddName);
}

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
WHERE Usu_Ced = '$user_name' AND empresas.Emp_Cod = $Emp_Cod AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";

if ($Suc_Cod > 0) {
    $user_sql .= " AND sucursal.Suc_Cod = $Suc_Cod";
}

$row_user = $obBD_con1->getRowConsultaSql($user_sql, $obBD_conexion);

// Test individual tables
$sucursal_row = $obBD_con1->getRowConsultaSql("SELECT * FROM sucursal WHERE Suc_Cod = $Suc_Cod", $obBD_conexion);
$empresa_row = $obBD_con1->getRowConsultaSql("SELECT * FROM empresas WHERE Emp_Cod = $Emp_Cod", $obBD_conexion);
$usuario_row = $obBD_con1->getRowConsultaSql("SELECT * FROM usuarios WHERE Usu_Ced = '$user_name' AND Suc_Cod = $Suc_Cod", $obBD_conexion);

$response = [
    'row_data' => $row_data,
    'bddName' => $bddName,
    'user_sql' => $user_sql,
    'row_user' => $row_user,
    'sucursal_row' => $sucursal_row,
    'empresa_row' => $empresa_row,
    'usuario_row' => $usuario_row
];

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
