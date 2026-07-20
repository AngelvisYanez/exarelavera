<?php
/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Edison Moya
 * @version 1.0
 * Fecha de creaci�n  2018-05-24
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_exportacion_container.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion_get = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con_get = new Class_Log_Datos_Exportacion_Container;

if(isset($containerAjax)){
    $data=array_merge($_GET,array('where'=>array("Exc_Est"=>'A')));
    $obBD_con_get->getPageGridJson('exportacion_container.selectWhere', $_GET, $obBD_conexion_get);
}

if(isset($saveContainer)||isset($modContainer)||isset($delContainer)){
    $resp=array('success'=>false);

    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new Class_Log_Datos_Exportacion_Container;
    //$obBD_con_set->debug(true);
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try{
        if(isset($saveContainer))
            $obBD_con_set->operacionobBD('exportacion_container.insert', $form, $obBD_conexion_set);
        if(isset($modContainer))
            $obBD_con_set->operacionobBD('exportacion_container.update', $form, $obBD_conexion_set);
        if(isset($delContainer))
            $obBD_con_set->operacionobBD('exportacion_container.setInactive', array('Exc_Cod'=>$Exc_Cod), $obBD_conexion_set);
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
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestionar Container </h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Containers registrados</legend>
                        <div>
                            <form name="searchContainer" id="searchContainer" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchContainer','containerAjax');">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <div class="form-group">
                                        <label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-xs-5 radioset opt_search">
                                            <input id="radsc1" name="op_opciones" type="radio" value="b" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Buque</label>
                                            <input id="radsc2" name="op_opciones" type="radio" value="s" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">Semana</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-1 control-label">B&uacute;squeda:</label>
                                        <div class="col-xs-5">
                                            <div class="input-group">
                                                <input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Container"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                            </div><!-- /input-group -->
                                        </div><input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                        <div class="" style="min-height: 300px;">
                            <table id="container"></table>
                            <div id="containerPager"></div>
                        </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <div id="gestionarContainer" title="Gestionar Container">
            <form id="containerForm" class="form-horizontal normal" action="javascript:validarFormContainer(containerForm.data('tipo'));">
                <input type="hidden" name="Emp_Cod" id="Emp_Cod" value="<?php echo $Ses_Emp_Cod?>" />
                <input type="hidden" name="Exc_Cod" id="Exc_Cod" value="" />
                <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs required" for="Exc_Ano">A&ntilde;o:</label>
                            <div class="col-sm-7">
                                <select id="Exc_Ano" name="Exc_Ano" class="form-control input-xs" onchange="setFecPeriodoCom()" required=''>
                                    <?php
                                    $periodos=$obBD_con_get->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion_get);
                                    foreach ($periodos as $p) {
                                        echo "<option data-pec-fei='$p[Pec_Fei]' data-pec-fef='$p[Pec_Fef]' value='$p[Year]'>$p[Year]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs required" for="Exc_Sem">Semana:</label>
                            <div class="col-sm-7">
                                <select id="Exc_Sem" name="Exc_Sem" class="form-control input-xs" required=''>
                                    <?php
                                    for ($i=0; $i < 53; $i++) {
                                        echo "<option value='".($i+1)."'>".($i+1)." Semana</option>";
                                    }?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs required" for="Exc_Fec">Fecha:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Fec" name="Exc_Fec" class="form-control input-xs datepicker" placeholder="yy-mm-dd" type="text" autocomplete="off" required=''/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs required" for="Exc_Vap">Nave/Buque:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Vap" name="Exc_Vap" class="form-control input-xs" placeholder="ej: Buque Sta. Mar&iacute;a..." type="text" required=''/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs required" for="Exc_Con" >Container:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Con" name="Exc_Con" class="form-control input-xs" placeholder="ej: Container abc-21..." type="text" required=''/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Pto">Puert. marit.:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Pto" name="Exc_Pto" class="form-control input-xs" placeholder="ej: Pto. Bolivar..." type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Zon">Ciudad/Zona:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Zon" name="Exc_Zon" class="form-control input-xs" placeholder="ej: Machala..." type="text"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Ter">Term.:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Ter" name="Exc_Ter" class="form-control input-xs" placeholder="Term..." type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Can">Can.:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Can" name="Exc_Can" class="form-control input-xs" placeholder="Can..." type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Bod">Bodega:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Bod" name="Exc_Bod" class="form-control input-xs" placeholder="ej: bodega 1..." type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Aco">Acopio:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Aco" name="Exc_Aco" class="form-control input-xs" placeholder="" type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Cho">Chofer:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Cho" name="Exc_Cho" class="form-control input-xs" placeholder="" type="text"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label label-xs" for="Exc_Pla">Placa:</label>
                            <div class="col-sm-7">
                            <input id="Exc_Pla" name="Exc_Pla" class="form-control input-xs" placeholder="" type="text"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:5px;"></div>
                <div class="row">
                    <div class="form-group">
                        <label class="col-sm-3 control-label label-xs" for="Exc_Obs">Observaci&oacute;n:</label>
                        <div class="col-sm-9">
                        <textarea id="Exc_Obs" name="Exc_Obs" class="form-control input-xs obs-mayus" style="resize: none;" placeholder="Breve observaci&oacute;n del container..."></textarea>
                        </div>
                    </div>
                </div>
                <div style="margin-top:5px;"></div>
                <div class="row">
                    <center>
                        <a onclick="containerForm.formSubmit();" class="btn btn-success btn-xs">Guardar</a>
                        <a onclick="$('#gestionarContainer').dialog('close');" class="btn btn-danger btn-xs">Cancelar</a>
                    </center>
                </div>
                </div>
            </form>
        </div>

        <script src="../VALIDACIONES/ban_val_exportacion_container.js"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/javascript">
        </script>
    </BODY>
</HTML>
