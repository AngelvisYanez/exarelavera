<?php
require_once __DIR__ . '/../Librerias/procedimientos/almacenados_standar.php';
require_once __DIR__ . '/../administrador/LOGICA/adm_log_control.php';

header('Content-Type: application/json; charset=utf-8');

$response = [];

$user_name = '22600781';
$password = '123456';
$encryptor = md5($password);

// Probar para empresas donde aparece 22600781 (ej. 96, 1, etc.)
$test_companies = [96, 1, 213, 27];

foreach ($test_companies as $Emp_Cod) {
    $item = ['Emp_Cod' => $Emp_Cod];
    
    $obBD_conexion = new Class_Log_Conexion_Cnt;
    $obBD_con1 = new Class_Log_Datos_Cnt;
    
    // Paso 1: getRowConsulta(2)
    $row_data = $obBD_con1->getRowConsulta(2, $Emp_Cod.'*'.$user_name, $obBD_conexion);
    $item['row_data'] = $row_data;
    
    $bddName = (!empty($row_data) && !empty($row_data['Dat_Dis'])) ? $row_data['Dat_Dis'] : 'exa';
    $item['bddName'] = $bddName;
    
    // Paso 2: Conexión
    $obBD_conexion_target = new Class_Log_Conexion_Cnt($bddName);
    
    // Paso 3: Buscar usuario en esa BDD
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
    
    $row_user_target = $obBD_con1->getRowConsultaSql($user_sql, $obBD_conexion_target);
    $item['row_user_target_db'] = $row_user_target;
    
    // Buscar también en base 'exa' master directamente
    $obBD_master = new Class_Log_Conexion_Cnt('exa');
    $row_user_master = $obBD_con1->getRowConsultaSql($user_sql, $obBD_master);
    $item['row_user_master_db'] = $row_user_master;
    
    // Paso 4: Comparación de contraseña
    if (!empty($row_user_target)) {
        $stored = $row_user_target['Usu_Pal'];
        $item['pass_verify_target'] = [
            'stored' => $stored,
            'encryptor' => $encryptor,
            'is_md5_match' => ($stored === $encryptor),
            'is_bcrypt_match' => (strpos($stored, '$2y$') === 0 && password_verify($encryptor, $stored))
        ];
    }
    
    if (!empty($row_user_master)) {
        $stored = $row_user_master['Usu_Pal'];
        $item['pass_verify_master'] = [
            'stored' => $stored,
            'encryptor' => $encryptor,
            'is_md5_match' => ($stored === $encryptor),
            'is_bcrypt_match' => (strpos($stored, '$2y$') === 0 && password_verify($encryptor, $stored))
        ];
    }
    
    $response["empresa_$Emp_Cod"] = $item;
}

utf8_encode_deep($response);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
