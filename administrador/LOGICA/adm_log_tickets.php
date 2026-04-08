<?php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualizaci�n:	2021/03/22
 *
 * @package administrador.Logica
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_tickets.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Alejandro Camacho 
 *
 * @package contabilidad.LOGICA
*/

class Class_Log_Conexion_Con extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Alejandro Camacho
 *
 */

class Class_Log_Datos_Con extends MysqlDatos{
    protected $_conexion = null;
    function __construct($conexion){
        $this->setSentencias('sentencias_con');
        $this->conexion = $conexion;
    }
    function crearTicket($post, $Ses_Emp_Cod, $Ses_Usu_Cod){
    //function crearTicket($post, $Ses_Emp_Cod, $Ses_Usu_Cod){
        $post['Tic_Evi_Pro'] = $this->guardarArchivo( $post, $Ses_Emp_Cod );
        $post['Usu_Cod'] = $Ses_Usu_Cod;
        $post['Emp_Cod'] = $Ses_Emp_Cod;
        $this->inicio_transaccion($this->conexion->conexion);
        $id = $this->getROWConsulta(11,  $Ses_Emp_Cod, $this->conexion);
        $this->operacionobBD(3,$post,$this->conexion);
        if ($this->fin_transaccion_nomsn($this->conexion->conexion)) { 
          $responce['success'] = true;
          $responce['id'] = $id["Cod_Fut"] + 1;
          $responce['titulos'] = $post["Tic_Tem"];
          $responce['descripcion'] = $post["Tic_Des"];
        }
        else {
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transacci�n";
        }
        //ChromePhp::log('ERROR',$this->MsgError);
        $this->echoJson($responce);
    }

    function modificarTicket($post, $Ses_Emp_Cod, $Ses_Usu_Cod){
      $post['Mod_Tic_Evi_Pro'] = $this->editarArchivo( $post, $Ses_Emp_Cod );
      $post['Usu_Cod'] = $Ses_Usu_Cod;
      $post['Emp_Cod'] = $Ses_Emp_Cod;
      $this->inicio_transaccion($this->conexion->conexion);
      $this->operacionobBD(20,$post,$this->conexion);
      if ($this->fin_transaccion_nomsn($this->conexion->conexion)) { 
        $responce['success'] = true;            
      }else {
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transacci�n";
      }
      $this->echoJson($responce);
    }

    function editarArchivo($formulario, $Ses_Emp_Cod){
      $formulario['Tic_Fec'] = date("Y-m-d H:i:s"); 
      if (isset($_FILES["Mod_Tic_Evi_Pro"]) && $_FILES["Mod_Tic_Evi_Pro"]['size'] > 0){
        $carpeta = "../tickets/$Ses_Emp_Cod/";
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        //asignar un nombre de archivo unico para que no se confundan
        $extension = pathinfo($_FILES["Mod_Tic_Evi_Pro"]["name"], PATHINFO_EXTENSION);
        $nombreArchivo = "ticket" . $formulario["Tic_Cod"] . "." . $extension;
        $target_file = $carpeta . basename($nombreArchivo);
        // Verifica si existe el archivo
        if (file_exists($target_file)) unlink($target_file);
        // Comprueba el tamano del archivo
        if ($_FILES["Mod_Tic_Evi_Pro"]["size"] > 5242880) $this->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
        // if everything is ok, try to upload file
        if (move_uploaded_file($_FILES["Mod_Tic_Evi_Pro"]["tmp_name"], $target_file)) {
            $formulario['Mod_Tic_Evi_Pro']= $target_file;
        }else $this->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));
      }else{
        $formulario['Mod_Tic_Evi_Pro'] = '';
      }
      return $formulario['Mod_Tic_Evi_Pro'];
  }

    function guardarArchivo( $formulario, $Ses_Emp_Cod){
        $formulario['Tic_Fec'] = date("Y-m-d H:i:s"); 
        if (isset($_FILES["Tic_Evi_Pro"]) && $_FILES["Tic_Evi_Pro"]['size'] > 0){
          $carpeta = "../tickets/$Ses_Emp_Cod/";
          if (!file_exists($carpeta)) {
              mkdir($carpeta, 0777, true);
          }
          //asignar un nombre de archivo unico para que no se confundan
          $extension = pathinfo($_FILES["Tic_Evi_Pro"]["name"], PATHINFO_EXTENSION);
          $Tic_Cod_Fut = $this->getROWConsulta(11,  $Ses_Emp_Cod, $this->conexion);
          $nombreArchivo = "ticket" . ($Tic_Cod_Fut["Cod_Fut"] + 1) . "." . $extension;
          $target_file = $carpeta . basename($nombreArchivo);
          // Verifica si existe el archivo
          if (file_exists($target_file)) unlink($target_file);
          // Comprueba el tamano del archivo
          if ($_FILES["Tic_Evi_Pro"]["size"] > 5242880) $this->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
          // if everything is ok, try to upload file
          if (move_uploaded_file($_FILES["Tic_Evi_Pro"]["tmp_name"], $target_file)) {
              $formulario['Tic_Evi_Pro']= $target_file;
          }else $this->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));
        }else{
          $formulario['Tic_Evi_Pro'] = '';
        }
        return $formulario['Tic_Evi_Pro'];
    }

    /* function tomarTicket($post, $Ses_Usu_Cod){
      $post['Ase_Cod'] = $Ses_Usu_Cod;
      $this->inicio_transaccion($this->conexion->conexion);
      $this->operacionobBD(18,$post,$this->conexion);
      if ($this->fin_transaccion_nomsn($this->conexion->conexion)) { 
        $responce['success'] = true;
        $responce['message'] = "Se te ha asignado el ticket!";        
      }else{
        $responce['success'] = false;
        $responce['message'] = "No se ha logrado realizar la Transacci�n";
      }
      $this->echoJson($responce);
    } */

    function consultarModulos($Ses_Usu_Cod){
      $post['Usu_Cod'] = $Ses_Usu_Cod;
      $modulos = $this->getArrayConsulta(16, $post, $this->conexion);
      $this->echoJson($modulos);
    }

    function consultarProcesos($Ses_Usu_Cod, $Org_Niv){
      $post['Usu_Cod'] = $Ses_Usu_Cod;
      $post['Org_Niv'] = $Org_Niv;
      $procesos = $this->getArrayConsulta(15, $post, $this->conexion);
      $this->echoJson($procesos);
    }

    function consultarAcciones($Ses_Usu_Cod, $Org_Cod){
      $post['Usu_Cod'] = $Ses_Usu_Cod;
      $post['Org_Cod'] = $Org_Cod;
      $acciones = $this->getArrayConsulta(17, $post, $this->conexion);
      $this->echoJson($acciones);
    }
}