<?php
/**
 * @abstract Permite consultar los lotes de productos
 * @author Cesar Bermeo.
 * @version 1.0
 * Fecha de creación: 08/04/2019
 *
 */
 require_once('../../administrador/LOGICA/seguridad.php');
 require_once('../LOGICA/fac_log_alt_lotes_prod.php');
 require_once('../../Librerias/procedimientos/almacenados_standar.php');

 /* Creacion del Objeto de conexion */
    $obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
    $obBD_con1 = new Class_Log_Datos_Lte;
    $hoy = date("Y-m-d");
    $hora = date("H:i:s");
    $mes = date("m");
// Busqueda de lotes
if(isset($ajaxLotes)){
    $data = $_GET;
    //$obBD_con1->echoLog('** PHP MOVIMIENTOS* SEARCH ***');
    //$obBD_con1->echoLog($data);
    $datos = array_merge($_GET, array('setWhere' =>array('isActive','getProducto','getProveedor','numDias')));
    $resultado = $obBD_con1->getPageGrid('loteprod.selectWhere',$datos, $obBD_conexion);
    $obBD_con1->echoJson($resultado);

}
/**
 * Inactivar Lotes
 */
if (isset($deleteLote)) {
    $obBD_ins1 = new Class_Log_Datos_Lte;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $obBD_con1->operacionobBD('loteprod.setInactive', array('Lte_Cod' => $Lte_Cod, 'Pro_Cod' => $Pro_Cod), $obBD_conexion);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success'])
        $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}


?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script> </script>
    <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main" id="formFinal">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Consulta de Lotes con Productos por Expirar</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panels-area form-horizontal normal ">
                            <form id="frm_con_activ" name="frm_con_activ" class="form-horizontal normal" action="javascript:$('#lotesConGrid').Search('#frm_con_activ','ajaxLotes');">
                                <div class="col-md-6 col-sm-6 col-md-offset-0">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">B&uacute;squeda de Lotes</legend>
                                        <input name="order" type="hidden" value="" />
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                            <div class="col-sm-8 radioset">
                                                <input id="rad_ba1" name="op_opciones" type="radio" value="s" checked="" onclick="setfocus(this.form.search)" />
									            <label for="rad_ba1">&nbsp;&nbsp;Producto&nbsp;&nbsp;</label>
                                                <input id="rad_ba2" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)" />
									            <label for="rad_ba2">&nbsp;&nbsp;Serie Lote&nbsp;&nbsp;</label>
                                                <input id="rad_ba3" name="op_opciones" type="radio" value="mes" onclick="setfocus(this.form.search)" />
									            <label for="rad_ba3">&nbsp;&nbsp;Meses&nbsp;&nbsp;</label>
                                                <input id="rad_ba4" name="op_opciones" type="radio" value="dia" onclick="setfocus(this.form.search)" />
									            <label for="rad_ba4">&nbsp;&nbsp;D&iacute;as&nbsp;&nbsp;</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group">
                                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                                    <span class="input-group-btn">
                                                        <button id="btnSearch" type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Factura" tabindex="-1">
                                                            <span class="glyphicon glyphicon-search"></span>
                                                            <span>Buscar</span>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-md-6 col-sm-6 col-md-offset-0">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Filtros</legend>
                                            <div class="form-group">
                                                <input type="text" name="stdM" id="stdM" value="n" style="display:none">
                                                <input type="text" name="stdD" id="stdD" value="n" style="display:none">
                                                <div class="col-xs-12">
                                                    <div class="col-xs-4">
                                                        <div class="input-group input-group-xs">
                                                            <span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
                                                                <input type="checkbox" id="chkM" name="chkM" onchange="verificaCheckM();" disabled>
                                                            </span>
                                                            <span class="input-group-addon bold alert-warning ">Por Caducar</span>

                                                            <span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
                                                                <input type="checkbox" id="chkD" name="chkD" onchange="verificaCheckD();" disabled>
                                                            </span>
                                                            <span class="input-group-addon bold alert-warning ">Caducados</span>
                                                        </div>
                                                        <div class="separator"></div>
                                                        <div class="input-group input-group-xs">
                                                            <span class="input-group-addon bold alert-info ">Notificaciones:</span>
                                                            <span class="input-group-btn">
                                                                <button type="button" onclick="$('#notificacionesDialog').dialog('open')" class="btn btn-info" title="Ver Lotes" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-8">
                                                        <div class="input-group input-group-xs">
                                                            <span class="input-group-addon bold alert-warning ">Meses:</span>
                                                            <select id="Sel_Mes" name="Sel_Mes" onchange="" class="form-control input-xs select_meses" disabled>
                                                                <option value="0" required="true">Seleccione...</option>
                                                            </select>
                                                            <span class="input-group-addon bold alert-warning ">D&iacute;as:</span>
                                                            <select id="Sel_Dia" name="Sel_Dia" onchange="" class="form-control input-xs select_dias" disabled>
                                                                <option value="0" required="true">Seleccione...</option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                    </fieldset>
                                </div>
                            </form>
                            <div class="col-xs-12" style="padding-bottom: 8px; min-height: 300px;" id="gridLotesCon">
                                <table id="lotesConGrid"></table>
                                <div id="lotesConGridPager"></div>
                                <div class="Titulos2">
                                    <span id="plan-footer">
                                        <strong>Leyenda:</strong>
                                        <span class="glyphicon glyphicon-stop" style="color:#d9968c"></span> Caducadas
                                        <span class="glyphicon glyphicon-stop" style="color:#8bff9f;"></span> Vigentes
                                        <span class="glyphicon glyphicon-stop" style="color:#ffaa55;"></span> En el Limite
                                        <span class="glyphicon glyphicon-stop" style="color:#ce00ce;"></span> Notificación Activa
                                    </span>
                                </div>
                            </div>
                            <div class="separator"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="notificacionesDialog" title="Notificaciones">
            <div class="condensed-header">
                <table id="notiGrid" ></table>
            </div>
        </div>




        <script src="../VALIDACIONES/fac_val_con_lotes_prod.js?k=199"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>