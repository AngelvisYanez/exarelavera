<?php
require_once 'DATA/libs/Env.php';
require_once 'DATA/MysqlConexion.php';
require_once 'DATA/MysqlDatos.php';

echo "Probando conexion a la base de datos...\n";
echo "=========================================\n";

try {
    // Probar conexión a exa_master (configurada en .env)
    $conexionClase = new MysqlConexion();
    $db = $conexionClase->getConexion();
    
    if ($db) {
        $result = $db->query("SELECT DATABASE()")->fetch_row();
        echo "[OK] Conexion exitosa a la base de datos configurada: " . $result[0] . "\n";
    }

    // Probar conexión a servicios
    $dbServicios = new mysqli('127.0.0.1', 'root', '', 'servicios', 3306);
    if ($dbServicios->connect_error) {
        echo "[ERROR] No se pudo conectar a 'servicios': " . $dbServicios->connect_error . "\n";
    } else {
        $resultServicios = $dbServicios->query("SELECT DATABASE()")->fetch_row();
        echo "[OK] Conexion exitosa a la base de datos: " . $resultServicios[0] . "\n";
    }

} catch (Exception $e) {
    echo "[ERROR] Excepción capturada: " . $e->getMessage() . "\n";
}
