<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_exporta_plan.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_ExportaPlanif;

if(isset($searchAjax)){
    $obBD_con1->getPageGridJson('exporta_planif.selectWhere', $_GET, $obBD_conexion);
}
if(isset($provAjax)){
    $page=$obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
    
}
if(isset($loadContainers)){
    $page=$obBD_con1->getPageGridJson('naviera_container.selectWhere', $_GET, $obBD_conexion,true);
}
if(isset($pedidosDetAjax)){
    $page=$obBD_con1->getPageGrid('exporta_planif_det.selectWhere', $_GET, $obBD_conexion);
    foreach ($page['rows'] as &$v) {
        $conteo=$obBD_con1->getRowConsulta('exporta_planif_det.sql.countContenedores',$v['Pde_Cod'], $obBD_conexion);
        $v['Contenedores']=isset($conteo['total'])&&!empty($conteo['total'])?$conteo['total']*1:0;
        $v['Total']=isset($conteo['suma'])&&!empty($conteo['suma'])?$conteo['suma']*1:0;
    } unset($v);
    $obBD_con1->echoJson($page);
}
if(isset($savePedido)){
    $resp=array('success'=>false);        
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_ExportaPlanif;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        $saver=$obBD_con1->formatUpdate($dato);
        $isNew=!isset($saver['Pln_Cod'])||empty($saver['Pln_Cod']);       
        $obBD_ins1->operacionobBD('exporta_planif.'.($isNew?'insert':'update'), $saver, $obBD_conexionIns);
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($saveDetalle)){
    $resp=array('success'=>false);        
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_ExportaPlanif;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        $saver=$dato;
        $isNew=!isset($saver['Pde_Cod'])||empty($saver['Pde_Cod']);       
        $obBD_ins1->operacionobBD('exporta_planif_det.'.($isNew?'insert':'update'), $saver, $obBD_conexionIns);
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($changeEstado)){
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_ExportaPlanif;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('exporta_planif_det.update', array('where'=>array('Pde_Cod'=>$Pde_Cod),'Pln_Est'=>$Pln_Est), $obBD_conexionIns);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_ins1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);// finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_ins1->echoJson($resp);
}
$hoy = date("Y-m-d");
$marcas=$obBD_con1->getArrayConsulta('banano_marca.selectWhere',  array('setWhere'=>array('setEmpCod','isActive')), $obBD_conexion); 
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion); 
$destinos=$obBD_con1->getArrayConsulta('exporta_dest.selectWhere',  array('setWhere'=>array('isActive')), $obBD_conexion); 
$cur_periodo=current($periodos);
?>
<!DOCTYPE html>
<HTML>
<HEAD>		
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script language="javascript" src="../VALIDACIONES/ban_val_planificacion.js"></script>
    <style></style>
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Pedidos del Exterior</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="divSearch" class="row">
                <div class="col-sm-12">
                    <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                        <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Consulta de Informaci�n</legend>
                        <div class="col-sm-4">    
                            
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>  
                                <div class="col-xs-7" >
                                    <select id="Lib_Ano" name="Pln_Ano" class="form-control input-xs" >
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                    </select>
                                </div>  
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>  
                                <div class="col-xs-9" ><select id="Prt_Sem" name="Pln_Sem" class="form-control input-xs" ></select></div>                            
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Marca:</label>
                                <div class="col-xs-9" >
                                    <select id="Bam_Cod" name="Bam_Cod" class="form-control input-xs getData ins">
                                        <?php if(count($marcas)!=1){ ?><option value="">Selecione Marca...</option><?php } ?>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>   
                        <div class="col-sm-4">    
                            <div class="center">
                                <button type="button" onclick="$('#searchForm').formSubmit();" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Cargar Datos</button>
                            </div>
                        </div>    
                        </fieldset>
                    </form>
                </div>       
                <div class="col-xs-12">
                    <div >
                        <table id="searchGrid"></table>
                        <div id="searchGridPager"></div>
                    </div>
                </div>
            </div> 
            
            <div id="divPedido" class="row" style="display: none;"> 
                <div class="col-sm-3"></div>
                <div class="col-sm-6">
                    <form id="formDocumentoPedido" class="form-horizontal normal formDatos" action="javascript:validaPedido();">   
                        <input name="Pln_Cod" type="text" value="" class="hidden" />                        
                        <fieldset id="provFormTemp" class="exa-fieldset">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">C�dula/RUC:</label>  
                                <div class="col-xs-9" >                                  
                                  <input name="Cli_Cod" data-name="Cli_Cod" type="text" style="display:none;" />
                                  <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">  
                                  <div class="input-group input-group-xs">                                          
                                    <input name="search" data-name="Ruc" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Cliente..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                    <span class="input-group-btn">                                        
                                        <button type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" ><span class="glyphicon glyphicon-search"></span></button>                                        
                                    </span>
                                  </div>
                                </div>                              
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cliente:</label>  
                                <div class="col-xs-9" >
                                    <span name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de Pedido</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Fecha&nbsp;Pedido:</label>  
                                <div class="col-xs-3" >
                                    <input type="text" id="Pln_Fec" name="Pln_Fec" class="form-control input-xs isFecha" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Periodo:</label>                                
                                <div class="col-xs-4" >
                                    <select id="Pln_Ano" name="Pln_Ano" class="form-control input-xs" required="">
                                        <option value="">Periodo..</option>
                                        <?php foreach ($periodos as $p) { echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Year]'>$p[Year]</option>"; } ?>
                                    </select>
                                </div>  
                            </div> 
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Semana:</label>  
                                <div class="col-xs-5" ><select id="Prt_Sem_Ped" name="Pln_Sem" class="form-control input-xs" required=""></select></div>                            
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Marca:</label>
                                <div class="col-xs-6" >
                                    <select name="Bam_Cod" class="form-control input-xs getData ins" required="">
                                        <?php if(count($marcas)!=1){ ?><option value="">Selecione Marca...</option><?php } ?>
                                        <?php foreach ($marcas as $m) {
                                            echo "<option value='$m[Bam_Cod]' data--bam_-cod='$m[Bam_Cod]' data--bam_-tam='$m[Bam_Tam]'>$m[Bam_Nom] $m[Bam_Tam]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Cantidad:</label>  
                                <div class="col-xs-4" >
                                    <input type="text" name="Pln_Can" class="form-control input-xs " required="" onkeypress="return validar_numeric(event);" />
                                </div>
                            </div>
                        </fieldset>
                        <fieldset id="detinoTemp" class="exa-fieldset">
                            <legend class="Titulos2">Datos del Destino</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Puerto:</label>
                                <div class="col-xs-6" >
                                    <select name="Exd_Cod" class="form-control input-xs getData ins" required="" onchange="$('#detinoTemp').setData(this.value===''?{}:$(this).find('option:selected').data(),'name');">
                                        <?php if(count($destinos)!=1){ ?><option value="">Selecione Destino...</option><?php } ?>
                                        <?php foreach ($destinos as $m) {
                                            echo "<option value='$m[Exd_Cod]' data--exd_-cod='$m[Exd_Cod]' data--pas_-nom='$m[Pas_Nom]'>$m[Exd_Nom]</option>";
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs required">Pais:</label>  
                                <div class="col-xs-3" >
                                    <span name="Pas_Nom" data-name="Pas_Nom" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div>
                        </fieldset>    
                        <div class="form-group">
                            <div class="col-xs-9" >
                                <button type="button" class="btn btn-sm btn-inverse" onclick="$('#divPedido').moveComp('#divSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
                                <button type="button" class="btn btn-sm btn-success" onclick="$('#formDocumentoPedido').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>   
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="divDetalle" class="row" style="display: none;"> 
                <div class="col-sm-3">
                    <div id="planifTmp" class="form-horizontal normal">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos del Pedido</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">RUC:</label>  
                                <div class="col-xs-7" ><span name="Ruc" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Cliente:</label>  
                                <div class="col-xs-9" ><span name="Cliente" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Fecha:</label>  
                                <div class="col-xs-7" ><span name="Pln_Fec" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Periodo:</label>  
                                <div class="col-xs-7" ><span name="Pln_Ano" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Semana:</label>  
                                <div class="col-xs-7" ><span name="Pln_Sem" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Marca:</label>  
                                <div class="col-xs-8" ><span name="Marca" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Pais:</label>  
                                <div class="col-xs-9" ><span name="Pas_Nom" class="form-control input-xs"></span></div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Destino:</label>  
                                <div class="col-xs-9" ><span name="Exd_Nom" class="form-control input-xs"></span></div>
                            </div>
                        </fieldset>    
                    </div>
                </div>
                <div class="col-sm-4">
                    <form id="formDocumentoDetalle" class="form-horizontal normal formDatos" action="javascript:validaDetalle();"> 
                        <input name="Pln_Cod" type="text" value="" class="hidden" /> 
                        <input id="Pde_Cod" name="Pde_Cod" type="text" value="" class="hidden" />                        
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos de la Planificacion</legend>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">AUCP:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Auc" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">DAE:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Dae" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Orden:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Ord" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">BL:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Bld" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Booking:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Boo" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">DHL:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Dhl" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">CDO:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Cdo" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Tipo:</label>  
                                <div class="col-xs-9" >
                                    <input type="text" name="Pln_Tip" class="form-control input-xs" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-3 control-label label-xs">Obs:</label>  
                                <div class="col-xs-9" >
                                    <textarea name="Pln_Obs" class="form-control input-xs" ></textarea>
                                </div>
                            </div>
                            <div class="form-group center" >
                                <button type="button" class="btn btn-sm btn-success" onclick="$('#formDocumentoDetalle').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>   
                            </div>    
                        </fieldset>    
                        
                    </form>
                </div>
                <div class="col-sm-5">
                    <div>
                        <table id="containers"></table>
                    </div>
                </div>  <div class="help-block"></div>  
                <div class="col-sm-12">
                    <div class="form-group">
                        <div class="col-xs-9" >
                            <button type="button" class="btn btn-sm btn-inverse" onclick="$('#divDetalle').moveComp('#divSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">

    </script>  
    <!--INICIO DEL DIALOGO BUSCAR PRODUCTOR--> 
    <div id="provDialog" title="B&uacute;squeda de Productor"></div>     
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>
</HTML>



