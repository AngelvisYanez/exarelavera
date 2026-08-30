<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_naviera.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Naviera;

if(isset($searchAjax)){
    $obBD_con1->getPageGridJson('naviera_exporta.selectWhere', $_GET, $obBD_conexion);
}
if(isset($saveDocumento)){
    $resp=array('success'=>false);    
    if(isset($resp['message'])) $obBD_con1->echoJson($resp);
    
    $obBD_ins1 =  new Class_Log_Datos_Naviera;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);  
    try{
        $isNew=!isset($dato['Nav_Cod'])||empty($dato['Nav_Cod']);
        $dato['Emp_Cod']=$Ses_Emp_Cod;
        $obBD_ins1->operacionobBD('naviera_exporta.'.($isNew?'insert':'update'), $dato, $obBD_conexionIns);
        
    } catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns); // finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}
if(isset($changeEstado)){
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Naviera;
    //$obBD_ins1->debug(true);
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $obBD_ins1->operacionobBD('naviera_exporta.update', array('where'=>array('Nav_Cod'=>$Nav_Cod),'Nav_Est'=>$Nav_Est), $obBD_conexionIns);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_ins1->echoJson($resp); }    
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);// finalizo la transaccion y compruebo errores
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_ins1->echoJson($resp);
}
$hoy = date("Y-m-d");
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="../VALIDACIONES/ban_val_naviera.js"></script>
        <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestion Navieras</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda de Navieras</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                    <div class="col-sm-5 radioset">
                                        <input id="rad_ba3" name="Nav_Tip" type="radio" value="" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba3">&nbsp;&nbsp;TODOS&nbsp;&nbsp;</label>
                                        <input id="rad_ba2" name="Nav_Tip" type="radio" value="N" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;NAVIERA&nbsp;&nbsp;</label>
                                        <input id="rad_ba1" name="Nav_Tip" type="radio" value="A" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;AGENTE NAVIERO&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-sm">B&uacute;squeda:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" class="form-control input-sm clearable submit" placeholder="Ingrese b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-sm" type="button" title="Buscar Naviera" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            </span>
                                        </div>
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
            </div>
        </div>
        
        
        <script type="text/javascript">
            
        </script>
        <div id="createDialog" title="Gestion Naviera" style="display:none;">  
            <form id="createForm" class="form-horizontal normal" action="javascript:validaDocument();">                
                <input type="text" name="Nav_Cod" class="form-control input-xs hidden" value=""  />
                <fieldset class="exa-fieldset" >
                    <legend class="Titulos2">Datos de la Naviera</legend>
                    <div class="row">                    
                        <div class="col-xs-12">
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Nombre:</label>  
                            <div class="col-xs-9">
                                <input type="text" name="Nav_Nom" class="form-control input-xs" value="" required="" />
                            </div> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Tipo:</label>  
                            <div class="col-xs-9">
                                <select name="Nav_Tip" class="form-control input-xs"  required="">
                                    <option value="">Seleccione Tipo...</option>
                                    <option value="N">NAVIERA</option>
                                    <option value="A">AGENTE NAVIERO</option>
                                </select>
                            </div> 
                        </div>    
                        </div>
                    </div> 
                </fieldset>   
                <div class="center">                
                    <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-plus"></i> Guardar</button>
                </div>
            </form>  
        </div>  
    </BODY>
</HTML>



