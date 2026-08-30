<?php
/******************************************************/
/*  Logica de componentes (shim)                      */
/*  Provee las clases y funciones que las paginas de  */
/*  componentes invocan directamente.                 */
/******************************************************/
if (!function_exists('sentencias_com')) {
    require_once(__DIR__ . '/sql.php');
}
if (!function_exists('sentencias')) {
    require_once(__DIR__ . '/../../auditoria/LOGICA/aud_sql_monitoreo.php');
}
if (!class_exists('MysqlConexion', false)) {
    require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
}
if (!class_exists('MysqlDatos', false)) {
    require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
}
if (!class_exists('Class_Log_Conexion_Com', false)) {
    class Class_Log_Conexion_Com extends MysqlConexion {}
}
if (!class_exists('Class_Log_Datos_Com', false)) {
    class Class_Log_Datos_Com extends MysqlDatos {
        function consulta($sql, $conexion = null)
        {
            $rs = parent::consulta($sql, $conexion);
            if ($rs === false) {
                $con = $this->getMyCon($conexion);
                $rs = @mysqli_query($con, "SELECT * FROM (SELECT 1 AS __dummy) AS __t WHERE 1=0");
                $this->rs_cargar = $rs;
            }
            return $rs;
        }
    }
}
