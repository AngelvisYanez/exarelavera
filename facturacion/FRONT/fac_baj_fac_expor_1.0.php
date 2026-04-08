<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_exportacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Exportacion;

$hoy = date("Y-m-d");

/* busqueda de documentos */
if(isset($searchDocument)){    
    $obBD_con1->getPageGridJson('ventas.selectWhere', array_merge($_GET, array('where'=>"(Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52') AND exporta_vent.Eve_Cod IS NOT NULL",'setWhere'=>array('isActive','setExportacion'))), $obBD_conexion) ;
}
if(isset($docDetalle)){
    $resp=$obBD_con1->getRowConsulta('ventas.selectWhere', array_merge($_GET, array('where'=>"ventas.Vet_Cod=".$Vet_Cod,'setWhere'=>array('setExportacion','setTotales'))), $obBD_conexion) ;
    $resp['Vet_Items']=$obBD_con1->getArrayConsulta('ventas.1',$Vet_Cod, $obBD_conexion); 
    $resp['success']=true;
    $obBD_con1->echoJson($resp);
}
if(isset($deleteDocumento)){
    $resp=array('success'=>false);
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_Exportacion;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        // guardo la modificacion
        $obBD_ins1->operacionobBD('exporta_vent.deleteWhere',array('Eve_Cod'=>$Eve_Cod), $obBD_conexionIns); 
        
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

$rs_perio=$obBD_con1->getArrayConsulta('perio_cont.selectWhere',array('setWhere'=>array('setEmpCod','order')), $obBD_conexion);
$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('where'=>"Tic_Est='A' AND Tic_Sri!='0'"), $obBD_conexion); 
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Eliminar Datos Exportacion</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <script>
             var busquedaButton=[{ label: '&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteDoc', data:'Eve_Cod', title:'Eliminar Exportacion', icon:'trash', type:'danger' }, title:false }];
            </script>
            <?php include('../COMPONENTES/busquedaVentaOpciones.php'); ?>  
        </div>
    </div>


    <script type="text/javascript">
    function deleteDoc(Eve_Cod){        
        $.createDialogConfirm('¿Esta seguro que desea eliminar el Registro de Exportación?<br/><span style="color:red;"><u><b>NOTA: </b></u>Esta accion no se podra deshacer.<span>', {deleteDocumento:true, Eve_Cod:Eve_Cod}, saveDocument);
    }
    function saveDocument(data){ //console.log(data); console.log(data['rets']);
        $.saveDataJson('',data,
            function (resp){               
                $('#searchGrid').trigger('reloadGrid', []);
            }
        ); 
    }
    </script>
    
</BODY>
</HTML>



