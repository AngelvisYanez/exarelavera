<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
if (file_exists("vendor/autoload.php")) require_once "vendor/autoload.php";
require_once "Librerias/config.php/debugbar.php";

require_once __DIR__ . "/administrador/LOGICA/adm_log_login.php";
require_once __DIR__ . "/DATA/MysqlConexion.php";
require_once __DIR__ . "/DATA/MysqlDatos.php";

$username = "0703703413";
$password = md5("lj2002");

$obBD_conexion = new Class_Log_Conexion_Log();
$obBD_con1 = new Class_Log_Datos_Log();

$rs_empresas = $obBD_con1->getArrayConsulta(1, $username, $obBD_conexion);
foreach($rs_empresas as $emp) {
    if (stripos($emp['Emp_Nom'], 'capacitacion') !== false || stripos($emp['Emp_Cor'], 'capacitacion') !== false || stripos($emp['Emp_Cor'], 'video') !== false || stripos($emp['Emp_Nom'], 'video') !== false) {
        $empresa = $emp['Emp_Cod'];
        echo "Found: {$emp['Emp_Nom']} (Cod: $empresa)\n";
        
        require_once __DIR__ . "/administrador/LOGICA/adm_sql_control_1.0.php";
        $sql_db = sentencias_cnt(2, [$empresa, $username]);
        $result_db = $obBD_con1->consulta($sql_db, $obBD_conexion->conexion);
        $row_data = $obBD_con1->fetch_assoc($result_db);
        $bdd_distribuida = $row_data['Dat_Dis'];
        echo "DB: $bdd_distribuida\n";
        
        $obBD_conexion_dist = new Class_Log_Conexion_Log($bdd_distribuida);
        $sql_login = "SELECT usuarios.Usu_Ced, persona.Prs_Nom, persona.Prs_Ape, usuarios.Usu_Pal
                        FROM usuarios
                        INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
                        INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
                        INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
                        WHERE Usu_Ced = '$username' AND empresas.Emp_Cod = $empresa
                        AND usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
        $result_login = $obBD_con1->consulta($sql_login, $obBD_conexion_dist->conexion);
        $user_data = $obBD_con1->fetch_assoc($result_login);
        echo "Hashes: DB=" . $user_data['Usu_Pal'] . " / Input=" . $password . "\n";
        if ($user_data['Usu_Pal'] === $password) {
            echo "MATCHES!\n";
        } else {
            echo "MISMATCH!\n";
            echo "MD5(lj2002) = $password\n";
            echo "MD5(MD5(lj2002)) = " . md5($password) . "\n";
        }
    }
}
