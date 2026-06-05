<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
if (file_exists("vendor/autoload.php")) {
    require_once "vendor/autoload.php";
}
require_once "Librerias/config.php/debugbar.php";

require_once __DIR__ . "/administrador/LOGICA/adm_log_login.php";
require_once __DIR__ . "/DATA/MysqlConexion.php";
require_once __DIR__ . "/DATA/MysqlDatos.php";

$username = "0703703413";
$password = "lj2002";

$obBD_conexion = new Class_Log_Conexion_Log();
$obBD_con1 = new Class_Log_Datos_Log();

// 1. Obtener empresas
$rs_empresas = $obBD_con1->getArrayConsulta(1, $username, $obBD_conexion);
print_r($rs_empresas);

if (!empty($rs_empresas)) {
    $empresa = $rs_empresas[0]['Emp_Cod'];
    echo "Usando empresa: " . $empresa . "\n";
    
    // 2. Obtener BDD
    require_once __DIR__ . "/administrador/LOGICA/adm_sql_control_1.0.php";
    $sql_db = sentencias_cnt(2, [$empresa, $username]);
    echo "SQL DB: $sql_db\n";
    
    $result_db = $obBD_con1->consulta($sql_db, $obBD_conexion->conexion);
    $row_data = $obBD_con1->fetch_assoc($result_db);
    print_r($row_data);
    
    if (!empty($row_data)) {
        $bdd_distribuida = $row_data['Dat_Dis'];
        echo "BDD Distribuida: $bdd_distribuida\n";
        
        $obBD_conexion_dist = new Class_Log_Conexion_Log($bdd_distribuida); // Reusing class
        
        // 3. Validar Login
        $encryptor = md5($password);
        $sql_login = "SELECT usuarios.Usu_Ced, persona.Prs_Nom, persona.Prs_Ape
                        FROM usuarios
                        INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
                        INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
                        INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
                        WHERE Usu_Ced = '$username' AND Usu_Pal = '$encryptor' AND empresas.Emp_Cod = $empresa
                        AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
        echo "SQL LOGIN: $sql_login\n";
        $result_login = $obBD_con1->consulta($sql_login, $obBD_conexion_dist->conexion);
        $user_data = $obBD_con1->fetch_assoc($result_login);
        print_r($user_data);
    }
}
