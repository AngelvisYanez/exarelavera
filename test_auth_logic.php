<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/administrador/LOGICA/adm_log_login.php";
require_once __DIR__ . "/DATA/MysqlConexion.php";
require_once __DIR__ . "/DATA/MysqlDatos.php";

try {
    $obBD_conexion = new Class_Log_Conexion_Log();
    $obBD_con1 = new Class_Log_Datos_Log();
    $rs_empresas = $obBD_con1->getArrayConsulta(1, "admin", $obBD_conexion);
    print_r($rs_empresas);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
