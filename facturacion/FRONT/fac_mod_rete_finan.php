<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Ret;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($searchDocument)){
    $rows=$obBD_con1->getPageGridJson('retencion.selectWhere', array_merge($_GET,array('group'=>'retencion.Ret_Cod','where'=>array('Cop_Est'=>'E'),'setWhere'=>array('setTotales'))), $obBD_conexion);        
}
if(isset($docDetalle)){
    $reten=$obBD_con1->getRowConsulta('retencion.selectWhere',array('clean'=>true,'unsetCols'=>true,'addCols'=>array(''=>array('Cop_Cod')), 'where'=>array('Ret_Cod'=>$Ret_Cod)), $obBD_conexion);
    $detalle=$obBD_con1->getArrayConsulta('retencion.selectWhere', array('addCols'=>array('renta_iva'=>'*'),'order'=>'Ren_Ret DESC,Ren_Sri','group'=>'renta_iva.Ren_Cod','where'=>array('retencion.Ret_Cod'=>$Ret_Cod),'setWhere'=>array('setTotales')), $obBD_conexion);
    $items=$obBD_con1->getArrayConsulta('det_compra.selectWhere', array('clean'=>true,'where'=>array('Cop_Cod'=>$reten['Cop_Cod'])), $obBD_conexion);
    $obBD_con1->echoJson(array('success'=>true, 'detalle'=>$detalle, 'items'=>$items));
}
if(isset($editRetencion)){
    $reten=$obBD_con1->getRowConsulta('retencion.selectWhere',array('clean'=>true, 'where'=>array('Ret_Cod'=>$Ret_Cod)), $obBD_conexion);
    $detalle=$obBD_con1->getArrayConsulta('det_retenc.selectWhere', array('clean'=>true, 'addCols'=>array(''=>array('Total'=>$obBD_con1->expr("IF(Ren_Por>0,CAST(Ret_Bas*Ren_Por/100 AS DECIMAL(10,2)),0.00)"))),'order'=>'Ren_Ret DESC,Ren_Sri','join'=>array('renta_iva'=>array('on'=>'renta_iva.Ren_Cod=det_retenc.Ren_Cod','cols'=>array('*') )),'where'=>array('Ret_Cod'=>$Ret_Cod)), $obBD_conexion);
    $compra=$obBD_con1->getRowConsulta('compras.selectWhere', array('clean'=>true,'where'=>array('Cop_Cod'=>$reten['Cop_Cod'])), $obBD_conexion);
    $provee=$obBD_con1->getRowConsulta('proveedore.selectWhere', array('where'=>array('Prv_Cod'=>$compra['Prv_Cod'])), $obBD_conexion);
    $autorizaci = $obBD_con1->getRowConsulta('autorizacion.selectWhere', array('where'=>array('Tic_Sri'=>7,'Aut_Cod'=>$reten['Aut_Cod']),'setWhere'=>array('setEmpCod','setPrsCod')), $obBD_conexion);
    if($compra['Cop_Fec']=='0000-00-00')$compra['Cop_Fec']='';
    $obBD_con1->echoJson(array('success'=>true, 'autoriz' =>$autorizaci,'proveedor'=>$provee, 'retencion'=>$reten,'detalle'=>$detalle, 'compra'=>$compra));
}
if(isset($provAjax)){    
    $obBD_con1->getPageGridJson('proveedore.selectWhere', array_merge($_GET,array()), $obBD_conexion);
}
if(isset($codiAjax)){    
    $resp=$obBD_con1->getPageGrid('renta_iva.selectWhere', array_merge($_GET,array('where'=>array("Ren_Sri LIKE '323%' OR Ren_Sri LIKE '504%'"),'setWhere'=>'isActive')), $obBD_conexion);
    $obBD_con1->echoJson($resp);
}
$vendedor = $obBD_con1->getRowConsulta('vendedor.selectWhere',array('unsetCols'=>true, 'addCols'=>array('vendedor'=>array('Vnd_Cod','Pun_Cod')),'setWhere'=>array('setSucCod','setPrsCod','isActive')),$obBD_conexion);    
$cof=$obBD_con1->getRowConsulta('confi_fact.selectWhere',array('clean'=>true,'unsetCols'=>true,'addCols'=>array(''=>array('Cof_Con')), 'where'=>array('Emp_Cod'=>$Ses_Emp_Cod)), $obBD_conexion);

if(isset($saveDocument)){
    $resp=array('success'=>false);
    //$obBD_con1->debug(true);    
    $obBD_con1->validaCierrePeriodo('retencion','Ret_Fec','Ret_Cod',$Cop_Fec,$Ret_Cod,$obBD_conexion);
	if($Aut_Tem=='E'){ //es electronica
        require_once('../LOGICA/fac_log_electronica.php');
        $obBD_elect =  new Class_Log_Datos_Retencion_Elect();        
        $claveAcceso = $obBD_elect->getClaveAcceso($Aut_Cod, $Ret_Fec, $Ret_Num, $obBD_conexion);
        if(empty($claveAcceso)) $responce['message']="Error al generar <u>Clave de Acceso</u> del <i>Comprobante Electr�nico</i>!";
    }
    
    if(empty($vendedor['Vnd_Cod'])){ $resp['message']="No tiene permisos de Vendedor!";  }
    $num_existe=$obBD_con1->getRowConsulta('retencion.selectCountWhere',array('where'=>array('Aut_Sri'=>$autoriz['Aut_Sri'], 'Ret_Num'=>$Ret_Num, "retencion.Ret_Cod!=$Ret_Cod"),'setWhere'=>array('setSucCod')), $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
    if(isset($num_existe['total']) && $num_existe['total']*1>0) $resp['message']="La <b class='green'>Retencion</b> Numero <u class='red'>$Ret_Num</u> ya existe en el sistema!";
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_Ret;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        $obBD_ins1->operacionobBD('compras.update', array('Tic_Cod'=>$Tic_Cod_Sus, 'Cop_Num'=>$Cop_Num, 'Cop_Fec'=>$Cop_Fec, 'Prv_Cod'=>$Prv_Cod, 'where'=>array('Cop_Cod'=>$Cop_Cod)), $obBD_conexionIns);
        $rete=array(           
            'Vnd_Cod'=>$vendedor['Vnd_Cod'],
            'Aut_Cod'=>$Aut_Cod,
            'Tic_Cod'=>$Tic_Cod,
            'Ret_Num'=>$Ret_Num,
            'Ret_Fec'=>$Ret_Fec,
            'Ret_Con'=>$Ret_Con,            
            'Ret_Xml'=>isset($claveAcceso)?$claveAcceso:null,
            'where'=>array('Ret_Cod'=>$Ret_Cod)
        );
        $obBD_ins1->operacionobBD('retencion.update', $rete, $obBD_conexionIns);  
        $obBD_ins1->operacionobBD('det_retenc.deleteWhere', array('Ret_Cod'=>$Ret_Cod), $obBD_conexionIns);  
        foreach ($detalle as $i=>$det) {
            $item=array(
                'Ret_Int'=>($i+1),
                'Ret_Cod'=> $Ret_Cod,
                'Ren_Cod'=> $det['Ren_Cod'],
                'Ret_Bas'=> $det['Ret_Bas'],
                'Ret_Imp'=> $det['Ren_Ret'],
                'Ret_Dep'=> $det['Ret_Dep']                 
            );
            $obBD_ins1->operacionobBD('det_retenc.insert', $item, $obBD_conexionIns);
        }
        if($cof['Cof_Con']=='S'){
            
        }
        //throw new Exception("Todo Ok");
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns,$e->getMessage()); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if($resp['success']){
        $resp['Ret_Cod']=$Ret_Cod;       
        $reportes = $obBD_con1->reportes('fac_alt_fac_com__._.php', $Ses_Emp_Cod, $obBD_conexion); 
        $resp['Ret_Link']="".(isset($reportes[2])?$reportes[2]:'')."?Ret_Cod=$Ret_Cod";
        if($Aut_Tem=='E'){
            $resp['xml'] = $obBD_elect->createXmlRetencion($Ret_Cod, $Aut_Cod, $claveAcceso, $obBD_conexion);
            $resp['Ret_Xmls']=baseUrl("../FRONT/$Ses_Emp_Cod/$claveAcceso.xml");
            //$resp['mail'] = $obBD_elect->sendMailDoc($Ret_Cod,$Prs_Cor,NULL,$obBD_conexion);
        }
    }else $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

/* Valida numero de retenci�n */	
if(isset($validaRetNum)){   
    $autoriz = $Aut_Data;
    $electronica=(isset($autoriz['Aut_Tem']) && $autoriz['Aut_Tem']=='E');
    
    $row_max_codig=$obBD_con1->getRowConsulta('retencion.nextNum',$autoriz, $obBD_conexion); 
    $Ret_Id_Man = ($row_max_codig['next']);
    if(empty($vendedor['Pun_Cod'])||empty($autoriz['Aut_Cod'])) $resp=array('success'=>false, 'message'=>"No tiene autorizacion para generar Retenciones!",'Ret_Num_Old'=>0,'Ret_Num'=>'');
    else{    
        $resp=array_merge(array('success'=>true,'Ret_Num'=>$Ret_Id_Man,'Ret_Num_Old'=>$Ret_Num),$autoriz);
        if(!empty($Ret_Num)){
            $num_existe_gencod=$obBD_con1->getRowConsulta('retencion.selectCountWhere',array('where'=>array('Aut_Sri'=>$autoriz['Aut_Sri'], 'Ret_Num'=>$Ret_Num),'setWhere'=>array('setSucCod')), $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
            if($num_existe_gencod['total']*1>0){                
                $resp['success']=false; $resp['message']="La Retenci�n N�mero $Ret_Num ya Existe en el Sistema!";
            }
        }else{            
            $resp['success']=true; 
        }
        $resp['Aut_Sri']=($electronica?'Electronica':$autoriz['Aut_Sri']);
    }
    $obBD_con1->echoJson($resp);
}
$row_rs_autorizaci = $obBD_con1->getArrayConsulta('autorizacion.selectWhere', array('where'=>array('Tic_Sri'=>7),'setWhere'=>array('setEmpCod','setPrsCod','isActive',"isActiveToday")), $obBD_conexion);
$rs_periodo = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('setWhere'=>array('order',"setEmpCod")), $obBD_conexion); 
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script>var Cof_Con=<?php echo json_encode($cof['Cof_Con']); ?>, hoy=<?php echo json_encode($hoy); ?>;</script> 
    <script>var extraSearch=[{ label: $.createIcon('pencil'), name: 'act01', width: 15, viewable: false, formatter: 'gridButton', formatoptions:{action:'editRetencion', data:'Ret_Cod', title:'Editar Retenci�n', conditional: function(o){ return o.Ret_Est !== 'I' && o.Ret_Aut !== 'S'; }, caseFalse:/*[{col:'Ret_Est',eval:"==='I'",icon:'remove red',title:'Anulado/Inactivo'},{col:'Ret_Aut',eval:"==='S'"}]*/function(o){ if(o.Ret_Est==='I') return '<i class="glyphicon glyphicon-remove red" title="Anulado/Inactivo"></i>'; if(o.Ret_Aut==='S') return '<i class="fa fa-globe green" title="Retenci�n Electronica Validada"></i>'; return ''; } } }];</script>   
    <script language="javascript" src="../VALIDACIONES/fac_val_rete_finan.js"></script>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Retenci�n Financiera</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <?php if(count($row_rs_autorizaci)==0){?>
            <link rel="stylesheet" href="../../framework/jquery/bootstrap/info.tabs.css" />
            <div class="vcenter center" style="height:300px;margin-bottom: 25px;">
                <?php echo error_alerta("Usted no tiene Autorizaciones activas para los documentos de retenci�n", 2, true);?>
            </div>    
            <?php }else{ ?>  
            <div class="row" id="divSearch">
                <?php include '../COMPONENTES/factSearchRetencion.php'; ?>
            </div>
            <div class="row" id="divDocumento" style="visibility: hidden;">
                <?php $atras=true; ?> 
                <?php include '../COMPONENTES/factFormReteFinan.php'; ?>                
            </div>            
            <script type="text/javascript">    
                $(function(){
                    
                });
            </script>
            <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>   
            <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
            <?php } ?> 
        </div>
    </div>
        
</BODY>
</HTML>