<?php

/**
 * Permite modificar un Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-16
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2014-05-21
 * 
 * @package tesoreria.FRONT
 */	  
require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/tes_log_cliente.php');	  
require_once('../../../Librerias/procedimientos/almacenados_standar.php');

/**
* objeto para la conexion
* @var Class_Log_Conexion_Tes
*/
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Cli;

/*Secci�n para listar los clientes registrados dentro de la empresa*/
if (isset($clientesAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(27, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(27, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

/* ver si exite un cliente */
if(isset($searchCliente)){  
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['exisCli']=true:$responce['exisCli']=false;
    (!empty($responce['Prs_Cod']))?$responce['exisPer']=true:$responce['exisPer']=false;
    utf8_encode_deep($responce); echo json_encode($responce); exit();
}

/* guarda un nuevo cliente */
if(isset($guardarCliente)){    
    $pers=$obBD_con1->getRowConsulta('persona.selectWhere',array('clean'=>true, 'Prs_Cod'=>$Prs_Cod),$obBD_conexion);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);                  
    $obBD_con1->operacionobBD(12,mb_convert_encoding($Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Te2.'*'.$Prs_Cel.'*'.$Ciu_Cod.'*'.$Ide_Cod.'*'.(!empty($Prs_Cor)?$Prs_Cor:'').'*'.$Prs_Cod, 'ISO-8859-1', 'UTF-8'),$obBD_conexion); 
    $obBD_con1->operacionobBD(26,$Prs_Cod.'*'.$Cli_Tic.'*'.$Cli_Con.'*'.$Cli_Cod.'*'.$Prs_Cor,$obBD_conexion); 
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) { $responce['success'] = true; }
    else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion"; }
    echo json_encode($responce);exit();
}
?>