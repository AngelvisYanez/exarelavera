<?php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Angeloni Cuesta
 * @version 1.0
 * Fecha de actualizaci�n:	2021/04/29
 *
 * @package administrador.Logica
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_soporte.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Angeloni Cuesta 
 *
 * @package administracion.LOGICA
*/

class Class_Log_Conexion_Spt extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Angeloni Cuesta
 *
 */

class Class_Log_Datos_Spt extends MysqlDatos{
    protected $_conexion = null;
    function __construct($conexion){
        $this->setSentencias('sentencias_con');
        $this->conexion = $conexion;
    }

    function tomarTicket($_POST, $Ses_Usu_Cod){
        //ChromePhp::log('Tomando ticket', $_POST);
        $_POST['Ase_Cod'] = $Ses_Usu_Cod;
        $this->inicio_transaccion($this->conexion->conexion);
        $db = $this->getRowConsulta(20,$_POST,$this->conexion);
        //ChromePhp::log('DAT_DIS',$db['Dat_Dis']);
        $_POST['db'] = $db['Dat_Dis'];
        // //ChromePhp::log('DAT_DIS',$db['Dat_Dis']);
        $this->operacionobBD(18,$_POST,$this->conexion);
        //ChromePhp::log('Transaccion ticket');
        if ($this->fin_transaccion_nomsn($this->conexion->conexion)) { 
          $responce['success'] = true;
          $responce['message'] = "Se te ha asignado el ticket!";        
        }else{
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transaccion";
        }
        $this->echoJson($responce);
      } 
}