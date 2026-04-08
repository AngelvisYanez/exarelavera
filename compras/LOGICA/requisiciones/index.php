<?Php 
/**
 * Logica para requisiciones
 * 
 * @author Angeloni Cuesta
 * @version 1.0
 * Fecha de actualizaci�n: 2021-06-08
 * 
 * 
 */
require_once ('../../../auditoria/LOGICA/aud_log_auditoria.php');
require_once ('../../sql/requisiciones.php');
class Class_Log_Conexion_Requisiciones extends MysqlConexion{
}

/* Clase para acceder a los datos */
class Class_Log_Datos_Requisiciones extends MysqlDatosContab{
   protected $_conexion = null;
   function __construct($conexion){
      //$this->setSentencias('sentencias_requisiciones');
      $this->conexion = $conexion;
   }

   function getRequisitores($_POST){
      //ChromePhp::log("GETREQUISITORES");
      $requisitores = $this->getArrayConsulta('requisitores.0', $_POST, $this->conexion);
      //ChromePhp::log("REQUISITORES",$requisitores);
      $this->MsgError;
      $this->echoJson($requisitores);
   }

}