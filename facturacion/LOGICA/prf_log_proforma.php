<?Php

/**
 * L�gica de las paginas para roles
 * 
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de actualizaci�n: 2018-06-26
 * 
 * 
 */
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("ord_sql_compra.php");
class Class_Log_Conexion_Proforma extends MysqlConexion {}

/* Clase para acceder a los datos */
class Class_Log_Datos_Proforma extends MysqlDatosContab
{
   function __construct()
   {
      $this->setSentencias('sentencias_proforma');
   }
}
