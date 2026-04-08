<?php
/**
 * @abstract Permite realizar el registro vehiculos
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creaci�n  22-09-2025
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del objeto mysql para las consultas */

$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Cli;

$hoy = date("Y-m-d");
if (isset($choferAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(35, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(35, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if (isset($searchAjax)) {
    $responce = $obBD_con1->getArrayConsulta(37,  $search  . '*' . $op_opciones . '*' . $Ses_Emp_Cod, $obBD_conexion);
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}

if (isset($saveData)) {
    $obBD_con1 = new MysqlDatos(true);
    $resp = array();
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    $oBdSet->beginTrans();
    try {
        $data = array('Veh_Cod' => $Veh_Cod, 'Ext_Cod' => $Ext_Cod, 'Veh_Pla' => $Veh_Pla, 'Veh_Mar' => $Veh_Mar, 'Veh_Col' => $Veh_Col);
        if (empty($Veh_Cod)) {
            unset($data['Veh_Cod']);
            $oBdSet->operation('vehiculo.insert', $data);
        } else {
            $oBdSet->operation('vehiculo.update', $data);
        }
        $oBdSet->endTrans($resp);
    } catch (Exception $e) {
        $oBdSet->revertTrans($e->getMessage(), $resp);
    }
    $oBdSet->echoJson($resp);
}

if (isset($deleteData)) {
    $obBD_con1 = new MysqlDatos(true);
    $resp = array('success' => false);
    $oBdSet = new MysqlDatos($obBD_con1->getMyCon());
    $oBdSet->beginTrans();
    try {
        $oBdSet->operation('vehiculo.setInactive', array('Veh_Cod' => $Veh_Cod));
        $oBdSet->endTrans($resp);
    } catch (Exception $e) {
        $oBdSet->revertTrans($e->getMessage(), $resp);
    }
    $oBdSet->echoJson($resp);
}

?>
<!DOCTYPE html>
<HTML>
<head>
    <title><?Php echo $Ses_Sys_Nom; ?></title>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/ecmascript" src="../VALIDACIONES/tes_val_socio_vehi.js"></script>
    <style></style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Gestion Vehiculos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-6">
                    <form id="searchForm" name="searchForm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchForm','searchAjax');">
                        <fieldset class="exa-fieldset form-horizontal normal">
                            <legend class="Titulos2">Busqueda de Vehiculos</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                <div class="col-sm-10 radioset">
                                    <input id="rad_ba3" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" /><label for="rad_ba3">&nbsp;&nbsp;Placa&nbsp;&nbsp;</label>
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Proveedor&nbsp;&nbsp;</label>
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label label-sm">B&uacute;squeda:</label>
                                <div class="col-sm-7">
                                    <div class="input-group">
                                        <input type="text" id="search" name="search" onkeydown="if (event.keyCode===13) this.form.submit()" class="form-control input-sm clearable" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-sm" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <div class="">
                        <table id="searchGrid"></table>
                        <div id="searchGridPager"></div>
                    </div>
                </div>
                <div id="edit" class="col-sm-6">
                    <div class="panel exa-panel">
                        <div class="panel-heading ui-widget-header"><span class="panel-title">Datos Vehiculo</span></div>
                        <div class="panel-body">
                            <form id="frm_aut" name="frm_aut" class="form-horizontal normal" action="javascript:saveItem();">
                                <fieldset class="exa-fieldset" id="provFormTemp">
                                    <legend class="Titulos2">Datos del Socio</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Cedula/RUC:</label>
                                        <div class="col-xs-6">
                                            <input name="Ext_Cod" data-name="Ext_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="search" data-name="Ext_Ruc" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectChofer); }" type="text" placeholder="Ingrese Proveedor..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                <span class="input-group-btn">
                                                    <button id="Prv_Btn" type="button" onclick="$('#choferDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Socio:</label>
                                        <div class="col-xs-6"><span name="Ext_Nom" data-name="Ext_Nom" class="form-control input-xs databind datatitle"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Direccion:</label>
                                        <div class="col-xs-10">
                                            <div class="input-group input-group-xs">
                                                <input name="Ext_Dir" data-name="Ext_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                                <span class="input-group-addon bold">e-mail:</span>
                                                <input name="Ext_Email" data-name="Ext_Email" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Datos del Vehiculo</legend>
                                    <input type="text" id="Veh_Cod" name="Veh_Cod" class="hidden">
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm required">Placa:</label>
                                        <div class="col-md-7 col-sm-4">
                                            <input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required="">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Marca:</label>
                                        <div class="col-md-7 col-sm-4">
                                            <input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Color:</label>
                                        <div class="col-md-7 col-sm-4">
                                            <input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs">
                                        </div>
                                    </div>
                                </fieldset>
                                <div style="text-align: center;">
                                    <button type="button" onclick="$('#edit').fadeOut();" class="btn btn-inverse btn-sm"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="choferDialog" title="B&uacute;squeda de Proveedor"></div>
    <script type="text/javascript">
    </script>
</body>
</HTML>