<?php
    
    // require_once('../../administrador/LOGICA/seguridad.php');
    require_once(__DIR__.'/../../Librerias/config.php/register_globals.php'); 
    // require_once($APP_REAL_PATH.'/DATA/GestorErrores.php');
    require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');   
    require_once('../LOGICA/adm_log_soporte.php');
    require_once('../../Librerias/procedimientos/almacenados_standar.php');
    require_once('../../Librerias/postclass.php');


    $obBD_conexion = new Class_Log_Conexion_Spt($Ses_Dat_Dis);
    /** 
    * Cracion del objeto mysql para las consultas 
    */
    
    $obBD_con1 =  new Class_Log_Datos_Spt($obBD_conexion);
    $hoy = date("Y-m-d H:i:s");
    $mes = date("m");

    /*
   * Guardar y Modificar tarea
   */
  
   if(isset($save))
   {  
      $_POST['Tic_Fec_Ter'] = $hoy;     
      $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
      $db = $obBD_con1->getRowConsulta(20,$_POST,$obBD_conexion->conexion);
      //ChromePhp::log('DAT_DIS',$db['Dat_Dis']);
      $_POST['db'] = $db['Dat_Dis'];
      $obBD_con1->operacionobBD(5,$_POST,$obBD_conexion);
      if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
          $responce['success'] = true;            
      }else {
          $responce['success'] = false;
          $responce['message'] = "No se ha logrado realizar la Transacci�n";
      }
      $obBD_con1->echoJson($responce);
      exit(); 
   }
   
    /*
     * Realizar y Modificar DESARROLLO de la Tarea
     */
     if(isset($Tic_Cod_Mod))
     {     

       $_POST['Tic_Fec_Env'] = $hoy;
          if (isset($_FILES["Tic_Evi_Sol_Arc"]) && $_FILES["Tic_Evi_Sol_Arc"]['size'] > 0){
              $carpeta = "../ticketsSolucionados/$Ses_Emp_Cod/";

              if (!file_exists($carpeta)) {
                  mkdir($carpeta, 0777, true);
              }
              $extension = pathinfo($_FILES["Tic_Evi_Sol_Arc"]["name"], PATHINFO_EXTENSION);
              $nombreArchivo = "tareaRealizada" . $_POST["Tic_Cod_Re"] . "." . $extension;

              $target_file = $carpeta . basename($nombreArchivo);


              // Verifica si existe el archivo
              if (file_exists($target_file)) unlink($target_file);
              // Comprueba el tamano del archivo
              if ($_FILES["Tic_Evi_Sol_Arc"]["size"] > 5242880) $obBD_con1->echoJson(array('success'=>false, 'message'=>'El archivo es demasiado grande!' ));
              // if everything is ok, try to upload file
              if (move_uploaded_file($_FILES["Tic_Evi_Sol_Arc"]["tmp_name"], $target_file)) {
                  $_POST['Tic_Evi_Sol_Arc']= $target_file;

              }else $obBD_con1->echoJson(array('success'=>false, 'message'=>'No se pudo subir el archivo!' ));
          }

        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        if($_POST['Tic_Cod_Mod'] == 1){
          $obBD_con1->operacionobBD(9,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        }
        else{
          $obBD_con1->operacionobBD(8,$_POST,$obBD_conexion);
          $obBD_con1->operacionobBD(10,$_POST,$obBD_conexion);
        }
        
        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion)) { 
            $responce['success'] = true;            
        }
        else {
            $responce['success'] = false;
            $responce['message'] = "No se ha logrado realizar la Transacci�n";
        }
        $obBD_con1->echoJson($responce);
        exit(); 
     }


     
    /*
     * Obtener Tareas
     */
    
    if(isset($searchFiltro))
    {
        $_GET['Ase_Cod']=$Ses_Usu_Cod;
        //ChromePhp::log('SEARCHFILTRO');
        /* $dbs = $obBD_con1->getArrayConsulta(19, $_GET, $obBD_conexion);
        $_GET['dbs'] = $dbs[6]; */
        // //ChromePhp::log('DBS',$dbs[2]);
        $data = $obBD_con1->getArrayConsulta(4, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    /*
     * Cambiar el estado de las tareas (anular y validar)
     */
    if(isset($setEstado))
    {
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->operacionobBD(6,$_GET,$obBD_conexion);
        
        if($Tic_Validar){
          $obBD_con1->operacionobBD(13,array('Tic_Cod' => $Tic_Cod, 'Ses_Usu_Cod' => $Ses_Usu_Cod),$obBD_conexion);
        }

        if ($obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion))
        {
            $response['success'] = true;
        }else{ 
            $response['success'] = false; 
            $response['message'] = "No se ha logrado realizar la Transaccion";
        }
        $obBD_con1->echoJson($response);
        exit(); 
    }


    if(isset($cargarDoc))
    {
        $db = $obBD_con1->getRowConsulta(20,$_GET,$obBD_conexion);
        //ChromePhp::log('DAT_DIS',$db['Dat_Dis']);
        $_GET['db'] = $db['Dat_Dis'];
        $data = $obBD_con1->getRowConsulta(7, $_GET, $obBD_conexion);
        
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    if(isset($cargarEditar))
    {
        $data = $obBD_con1->getRowConsulta(14, $_GET, $obBD_conexion);
        // Grid necesita este array
        $obBD_con1->echoJson(array(
            'rows'=>$data,
            'total'=>1,
            'records'=>count($data),
            'success'=>true
        ));
        exit(); 
    }

    if(isset($takeTicket)){
        //ChromePhp::log('Tomando ticket..');
        $obBD_con1->tomarTicket($_POST, $Ses_Usu_Cod);
        exit(); 
    }
?>
