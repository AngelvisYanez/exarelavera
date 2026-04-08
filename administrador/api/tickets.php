<?php
    
    // require_once('../../administrador/LOGICA/seguridad.php');
    require_once(__DIR__.'/../../Librerias/config.php/register_globals.php'); 
    // require_once($APP_REAL_PATH.'/DATA/GestorErrores.php');
    require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');   
    require_once('../LOGICA/adm_log_tickets.php');
    require_once('../../Librerias/procedimientos/almacenados_standar.php');
    require_once('../../Librerias/postclass.php');
    

    $obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
    /** 
    * Cracion del objeto mysql para las consultas 
    */
    $obBD_con1 =  new Class_Log_Datos_Con($obBD_conexion);
    $hoy = date("Y-m-d H:i:s");
    $mes = date("m");

    if(isset($isCreateAction)){  
        $obBD_con1->crearTicket( $_POST, $Ses_Emp_Cod, $Ses_Usu_Cod);
        exit(); 
    }

    if(isset($isEditAction)){  
        $obBD_con1->modificarTicket( $_POST, $Ses_Emp_Cod, $Ses_Usu_Cod);
        exit(); 
    }

    if(isset($verTicket)){
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

    if(isset($isRateAction)){
        $data = $obBD_con1->operacionobBD(21, $_POST, $obBD_conexion);
        $response['success'] = true;
        $response['message'] = "Transacci&oacute;n realizada con &eacute;xito";
        $obBD_con1->echoJson($response);
        exit(); 
    }

    /* if(isset($takeTicket)){
        $obBD_con1->tomarTicket($_POST, $Ses_Usu_Cod);
        exit(); 
    } */

    if(isset($getModules)){
        $obBD_con1->consultarModulos($Ses_Usu_Cod);
        exit(); 
    }

    if(isset($getProcesses)){
        $obBD_con1->consultarProcesos($Ses_Usu_Cod, $Org_Niv);
        exit(); 
    }

    if(isset($getActions)){
        $obBD_con1->consultarAcciones($Ses_Usu_Cod, $Org_Cod);
        exit(); 
    }

    if(isset($searchFiltro))
    {
        $_GET['Emp_Cod'] = $Ses_Emp_Cod;
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
?>
