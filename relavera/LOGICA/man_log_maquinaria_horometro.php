<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_maquinaria_horometro.php");

/**
 * Conexión y acceso a base de datos para Horómetro y Mantenimiento de Maquinaria
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Maquinaria_Horometro extends MysqlConexion{

}

class Class_Log_Datos_Maquinaria_Horometro extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_maquinaria_horometro');
    } 

    public function getCombustibleReporte($tipo, $params, $conexion) {
        if ($tipo == 'individual') {
            return $this->getArrayConsulta(23, $params, $conexion);
        } else {
            return $this->getArrayConsulta(24, $params, $conexion);
        }
    }
}
