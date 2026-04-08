<?php

/**
 * Logica de búsqueda de documentos
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
*/

require_once("sql_man_busq_doc_2.0.php");

class Class_Log_Conexion_BusqDoc extends MysqlConexion{ }

class Class_Log_Datos_BusqDoc extends MysqlDatos{
	function __construct(){
        $this->setSentencias('sentencias_busq_doc');
    }
}

?>

