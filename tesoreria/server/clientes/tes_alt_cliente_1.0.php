<?php
/**
 * Permite registrar un nuevo Cliente ya sea Nacional(Cedula o Ruc) o Extranjero(Pasaporte)
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
require_once(__DIR__.'/../../../administrador/LOGICA/seguridad.php');
require_once(__DIR__.'/../../LOGICA/tes_log_cliente.php');
require_once(__DIR__.'/../../../Librerias/procedimientos/almacenados_standar.php');

/**
* objeto para la conexion
* @var Class_Log_Conexion_Tes
*/
if(!isset($Ses_Dat_Dis)){
    $Ses_Dat_Dis = null;
}
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
* objeto para consultas
* @var Class_Log_Datos_Tes
*/
$obBD_con1 =  new Class_Log_Datos_Cli;

/* ver si exite un cliente */
if(isset($searchCliente)){
    $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
}

if(isset($verificaAjax)){
    $varios="persona.Prs_Ape = 'VARIOS INGRESOS'";
    $busqueda=array(
        'success'=>true,
        'consumidorFinal'=>$obBD_con1->getArrayConsulta('cliente.selectWhere', array('setWhere'=>array('setEmpCod','isActive', /*'byConsF',*/ 'isConsF')), $obBD_conexion),
        'consumidorFinalPersona'=>$obBD_con1->getArrayConsulta('persona.selectWhere', array('setWhere'=>array('isActive', /*'byConsF', */'isConsF')), $obBD_conexion),
        'variosIngresos'=>$obBD_con1->getArrayConsulta('cliente.selectWhere', array('where'=>$varios,'setWhere'=>array('setEmpCod', 'isActive', /*'byVariosIngresos'*/)), $obBD_conexion),
        'variosIngresosPersona'=>$obBD_con1->getArrayConsulta('persona.selectWhere',array('where'=>$varios,'setWhere'=>array('isActive'/*,'byVariosIngresos'*/)), $obBD_conexion),
    );
    $obBD_con1->echoJson($busqueda);
}
/* guarda consumidor final*/
if(isset($guardarConsumidorFnal)){
    $condicion = 'VARIOS INGRESOS';
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['Emp_Cod']=$Ses_Emp_Cod;
        $obBD_con1->operacionobBD(19,$data['consumidorF'],$obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        $obBD_con1->operacionobBD(20,$data,$obBD_conexion);
        $data['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        if($data['consumidorF']['Prs_Ape'] == $condicion){ $obBD_con1->operacionobBD('caja_clien.insert', array('Cli_Cod'=>$data['Cli_Cod']),$obBD_conexion);}
        $obBD_con1->operacionobBD(12,$data['consumidorF']['Prs_Ced'].'*'.$data['consumidorF']['Prs_Nom'].'*'.$data['consumidorF']['Prs_Ape'].'*'.$data['consumidorF']['Prs_Sex'].'*'.$data['consumidorF']['Prs_Dir'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Prs_Tel'].'*'.$data['consumidorF']['Ciu_Cod'].'*'.$data['consumidorF']['Ide_Cod'].'*'.$data['consumidorF']['Prs_Cor'].'*'.$data['Prs_Cod'],$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}
/* Guardar persona como cliente cuando existe Consumidor final en persona */
if(isset($guardarCfPersona)){
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['persona']['Emp_Cod']=$Ses_Emp_Cod;
        $obBD_con1->operacionobBD(20,$data['persona'],$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}
/* Guardar persona como cliente cuando existe Varios Ingresos en persona   */
if(isset($guardarVIPersona)){
    //$obBD_conexion1 = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_con1->echoLog("guardarVIPersona");
    $resp=array('success'=>false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try{
        $data = $_POST;
        $data['persona']['Emp_Cod']=$Ses_Emp_Cod;
        //$obBD_con1->echoLog($data['Emp_Cod']);
        //$obBD_con1->echoLog($data);
        $obBD_con1->operacionobBD(20,$data['persona'],$obBD_conexion);
        $data['persona']['Cli_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        //$obBD_con1->echoLog($data['persona']['Cli_Cod']);
        //$obBD_con1->operacionobBD(29,'*'.$data['persona']['Cli_Cod'],$obBD_conexion, true);
        $obBD_con1->operacionobBD('caja_clien.insert', array('Cli_Cod'=>$data['persona']['Cli_Cod']),$obBD_conexion);
    }catch(Exception $e){ $obBD_con1->rollBack_nomsn($obBD_conexion); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if(!$resp['success']) $resp['error']=$obBD_con1->MsgError;
    $obBD_con1->echoJson($resp);
}

/* guarda un nuevo cliente */
// echo $guardarCliente;
if(isset($guardarCliente)){
    $data=$_POST;
    $data['Emp_Cod']=$Ses_Emp_Cod;
    $data['Cli_Cor']=$data['Prs_Cor'];
    $obBD_con1->inicio_transaccion($obBD_conexion);
        if(empty($Prs_Cod)){
            $obBD_con1->operacionobBD(19,$data,$obBD_conexion);
            $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
        }else{
            $pers=$obBD_con1->getRowConsulta('persona.selectWhere',array('clean'=>true, 'Prs_Cod'=>$Prs_Cod),$obBD_conexion);
            if(empty($Prs_Cor)&&!empty($pers['Prs_Cor'])) $data['Cli_Cor']=$pers['Prs_Cor'];
        }
        $obBD_con1->operacionobBD(12,$Prs_Ced.'*'.$Prs_Nom.'*'.$Prs_Ape.'*'.$Prs_Sex.'*'.$Prs_Dir.'*'.$Prs_Tel.'*'.$Prs_Te2.'*'.$Prs_Cel.'*'.$Ciu_Cod.'*'.$Ide_Cod.'*'.(empty($pers['Prs_Cor'])&&!empty($Prs_Cor)?$Prs_Cor:'').'*'.$data['Prs_Cod'],$obBD_conexion);
        $obBD_con1->operacionobBD(20,$data,$obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) { $responce['success'] = true; }
    else{ $responce['success'] = false; $responce['message'] = "No se ha logrado realizar la Transaccion"; }
    $obBD_con1->echoJson($responce);
    // echo $_POST;
}
// return "hola"; 0705788636

/* $responce = $obBD_con1->getRowConsulta(17, $Prs_Ced, $obBD_conexion);
    $existe = $obBD_con1->getRowConsulta(18, $responce['Prs_Cod'].'*'.$Ses_Emp_Cod, $obBD_conexion);
    (!empty($existe['Cli_Cod']))?$responce['existe']=true:$responce['existe']=false;
    $obBD_con1->echoJson($responce);
?> */


