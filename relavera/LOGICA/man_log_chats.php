<?php
/**
 * Logica de chats
 * @author Wilson Belduma
 * @version  1.0
 * Fecha de creacion: 12/05/2026
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH."/relavera/LOGICA/sql_man_chats.php");

class Class_Log_Conexion_Chats extends MysqlConexion{}
class Class_Log_Datos_Chats extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_consulta_chats');
    }
}