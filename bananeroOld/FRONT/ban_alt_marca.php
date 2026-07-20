<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_marca.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion_get = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con_get = new Class_Log_Datos_Marca;

$obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con_set = new Class_Log_Datos_Marca;

if(isset($marcasAjax)){
    if($_GET['op_opciones']=="n") {$search="(Bam_Nom LIKE '%".mysqli_real_escape_string($obBD_conexion_get->conexion, $_GET['search'])."%')";} else {$search="Bam_Tam LIKE '%".mysqli_real_escape_string($obBD_conexion_get->conexion, $_GET['search'])."%'";}
    $_GET['where']="$search and Bam_Est='A' and Emp_Cod=$Ses_Emp_Cod";
    $obBD_con_get->getPageGridJson('banano_marca.selectWhere', $_GET, $obBD_conexion_get);
}

if(isset($saveMarca)){
    $resp=array('success'=>false);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        $obBD_con_set->operacionobBD('banano_marca.insert', array('Bam_Nom'=>$Bam_Nom,'Bam_Des'=>$Bam_Des,'Bam_Tam'=>$Bam_Tam,'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion_set);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con_set->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}

if(isset($modMarca)){
    $resp=array('success'=>false);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        $obBD_con_set->operacionobBD('banano_marca.update', array('Bam_Cod'=>$Bam_Cod,'Bam_Nom'=>$mod_Bam_Nom,'Bam_Des'=>$mod_Bam_Des,'Bam_Tam'=>$mod_Bam_Tam), $obBD_conexion_set);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con_set->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}

if(isset($delMarca)){
    $resp=array('success'=>false);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        $obBD_con_set->operacionobBD('banano_marca.update', array('Bam_Cod'=>$Bam_Cod,'Bam_Est'=>"I"), $obBD_conexion_set);
    } catch(Exception $e){ $obBD_con_set->rollBack_nomsn($obBD_conexion_set); $resp['message']=$e->getMessage(); $obBD_con_set->echoJson($resp); }
    // finalizo la transaccion y compruebo errores
    $resp['success']=$obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if(!$resp['success']) $resp['error']=$obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}

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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Marcas </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Marcas registradas</legend>
                        <div>
                            <form name="searchMarca" id="searchMarca" method="get" class="form-horizontal normal" action="javascript:$('#marcas').Search('#searchMarca','marcasAjax');">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
                                            <div class="col-xs-5 radioset opt_search">
                                                <input id="radsc1" name="op_opciones" type="radio" value="n" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Nombre</label>
                                                <input id="radsc2" name="op_opciones" type="radio" value="t" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">Tama&ntilde;o</label>
                                            </div>
                                        </div>
                                        <label class="col-xs-1 control-label">B&uacute;squeda:</label>
                                        <div class="col-xs-5">
                                            <div class="input-group">
                                                <input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                            </div><!-- /input-group -->
                                        </div><input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                        <div class="jqHeaderFirst jqFirst">
                            <table id="marcas"></table>
                            <div id="marcasPager"></div> 
                        </div>
                        </fieldset>
                    </div>
                </div> 
            </div>
        </div>
        <div id="gestionarMarca" title="Nueva Marca">
            <form id="marcaForm" class="form-horizontal normal">
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="Bam_Nom">Nombre:</label>
                    <div class="col-sm-9">
                    <input id="Bam_Nom" name="Bam_Nom" class="form-control input-xs datepicker" placeholder="ej: Banmarc..." type="text"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="Bam_Des">Descripci&oacute;n:</label>
                    <div class="col-sm-9">
                    <textarea id="Bam_Des" name="Bam_Des" class="form-control input-xs obs-mayus" style="resize: none;" placeholder="Breve descripci&oacute;n de la marca..." required=""></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="Bam_Tam">Tama&ntilde;o:</label>
                    <div class="col-sm-9">
                    <input id="Bam_Tam" name="Bam_Tam" class="form-control input-xs datepicker" placeholder="ej: 5 x 7..." type="text"/>
                    </div>
                </div>
                <center>
                    <br>
                    <a onclick="saveMarca()" class="btn btn-success btn-xs">Guardar</a>
                    <a onclick="$('#gestionarMarca').dialog('close');limpiarFormMarca('add');" class="btn btn-danger btn-xs">Cancelar</a>
                </center>
            </form>
        </div>
        <div id="modMarca" title="Modificar Marca">
            <form id="modMarcaForm" class="form-horizontal normal">
                <input type="text" name="Bam_Cod" id="Bam_Cod" hidden>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="mod_Bam_Nom">Nombre:</label>
                    <div class="col-sm-9">
                    <input id="mod_Bam_Nom" name="mod_Bam_Nom" class="form-control input-xs datepicker" placeholder="ej: Banmarc..." type="text"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="mod_Bam_Des">Descripci&oacute;n:</label>
                    <div class="col-sm-9">
                    <textarea id="mod_Bam_Des" name="mod_Bam_Des" class="form-control input-xs obs-mayus" style="resize: none;" placeholder="Breve descripci&oacute;n de la marca..." required=""></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label label-xs required" for="mod_Bam_Tam">Tama&ntilde;o:</label>
                    <div class="col-sm-9">
                    <input id="mod_Bam_Tam" name="mod_Bam_Tam" class="form-control input-xs datepicker" placeholder="ej: 5 x 7..." type="text"/>
                    </div>
                </div>
                <center>
                    <br>
                    <a onclick="modifyMarca()" class="btn btn-success btn-xs">Guardar</a>
                    <a onclick="$('#modMarca').dialog('close');limpiarFormMarca('mod');" class="btn btn-danger btn-xs">Cancelar</a>
                </center>
            </form>
        </div>
        <script src="../VALIDACIONES/ban_val_marca.js"></script>
        <script type="text/javascript">
        </script>
    </BODY>
</HTML>
