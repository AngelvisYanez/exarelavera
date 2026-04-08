<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2018-05-18
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_cli_vehi.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_cli_vehi($Ses_Dat_Dis);
/** 
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_cli_vehi;

/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;

if (isset($cliAjax)) {
    $obBD_cliente = new MysqlDatos(true);
    $obBD_cliente->getPageGridJson('cliente.selectWhere', array_merge($_GET, array('setWhere' => array())));
}

// Seccion para cargar datos en el Jqgrid referente a los vehiculos con clientes
if (isset($searchAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $contar = $obBD_con1->getRowConsulta(1, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
if (isset($saveData)) {
    $resp = array('success' => false);
    $data_post = filter_input_array(INPUT_POST);
    $Veh_Cod = isset($data_post['Veh_Cod']) ? $data_post['Veh_Cod'] : '';
    $Cli_Cod = isset($data_post['Cli_Cod']) ? $data_post['Cli_Cod'] : '';
    $Veh_Pla = isset($data_post['Veh_Pla']) ? addslashes($data_post['Veh_Pla']) : '';
    $Veh_Mar = isset($data_post['Veh_Mar']) ? addslashes($data_post['Veh_Mar']) : '';
    $Veh_Col = isset($data_post['Veh_Col']) ? addslashes($data_post['Veh_Col']) : '';
    $Veh_Tit = isset($data_post['Veh_Tit']) ? addslashes($data_post['Veh_Tit']) : '';
    $Veh_Cap = isset($data_post['Veh_Cap']) ? addslashes($data_post['Veh_Cap']) : '';
    
    $obBD_conexionIns = new Class_Log_Conexion_cli_vehi($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_cli_vehi;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    
    try {
        $data = array(
            'Veh_Cod' => $Veh_Cod,
            'Cli_Cod' => $Cli_Cod,
            'Veh_Pla' => $Veh_Pla,
            'Veh_Mar' => $Veh_Mar,
            'Veh_Col' => $Veh_Col,
            'Veh_Tit' => $Veh_Tit,
            'Veh_Cap' => $Veh_Cap,
            'Emp_Cod' => $Ses_Emp_Cod
        );
        
        if (empty($Veh_Cod)) {
            // INSERT
            $obBD_conIns->operacionobBD(10, $data, $obBD_conexionIns);
            $Veh_Cod_New = $obBD_conIns->insercionid($obBD_conexionIns);
        } else {
            // UPDATE
            $obBD_conIns->operacionobBD(11, $data, $obBD_conexionIns);
        }
        
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
        
        if ($obBD_conIns->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = "Vehículo guardado correctamente!";
            if (isset($Veh_Cod_New)) {
                $resp['Veh_Cod'] = $Veh_Cod_New;
            }
        } else {
            $resp['message'] = $obBD_conIns->MsgError;
        }
    } catch (Exception $e) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns->conexion);
        $resp['message'] = $e->getMessage();
    }
    
    echo json_encode($resp);
    exit();
}
if (isset($deleteData)) {
    $resp = array('success' => false);
    $data_post = filter_input_array(INPUT_POST);
    $Veh_Cod = isset($data_post['Veh_Cod']) ? $data_post['Veh_Cod'] : '';
    
    $obBD_conexionIns = new Class_Log_Conexion_cli_vehi($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_cli_vehi;
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    
    try {
        $obBD_conIns->operacionobBD(20, array('Veh_Cod' => $Veh_Cod), $obBD_conexionIns);
        
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
        
        if ($obBD_conIns->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = "Vehículo anulado correctamente!";
        } else {
            $resp['message'] = $obBD_conIns->MsgError;
        }
    } catch (Exception $e) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns->conexion);
        $resp['message'] = $e->getMessage();
    }
    
    echo json_encode($resp);
    exit();
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/ecmascript" src="../VALIDACIONES/tca_val_cli_vehi.js"></script>
    <style></style>
</HEAD>

<BODY>
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
                                    <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" /><label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                    <input id="rad_ba1" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" /><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                    <input id="rad_ba4" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" /><label for="rad_ba4">&nbsp;&nbsp;No Rel.&nbsp;&nbsp;</label>
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
                                <label class="col-sm-3 control-label label-sm "><input type="checkbox" name="isActive" value="S" class="check-big" checked="" />&nbsp;&nbsp;&nbsp;Activos</label>
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
                                <fieldset class="exa-fieldset" id="cliFormTemp">
                                    <legend class="Titulos2">Datos del Cliente</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">C�dula/RUC:</label>
                                        <div class="col-xs-6">
                                            <input name="Prs_Cod" data-name="Prs_Cod" type="text" style="display:none;" />
                                            <input name="Cli_Cod" data-name="Cli_Cod" type="text" style="display:none;" />
                                            <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#cliDialog',selectCliente); }" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                                <span class="input-group-btn">
                                                    <button id="Cli_Btn" type="button" onclick="$('#cliDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                                    <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                        <div class="col-xs-10"><span name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Direcci�n:</label>
                                        <div class="col-xs-10">
                                            <div class="input-group input-group-xs">
                                                <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                                <span class="input-group-addon bold">e-mail:</span>
                                                <input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
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

                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Tipo Vehiculo:</label>
                                        <div class="col-md-7 col-sm-4">
                                            <select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs">
                                                <option value="V">Volqueta Sencilla</option>
                                                <option value="VM">Volqueta Mula</option>
                                                <option value="VB">Volqueta Bañera</option>
                                                <option value="D">TIPO DUMPER</option>
                                                <option value="B">Bus</option>
                                                <option value="C">CAMION</option>
                                                <option value="T">Tractor</option>
                                                <option value="M">Moto</option>
                                                <option value="O">Otro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Capacidad:</label>
                                        <div class="col-md-7 col-sm-4">
                                            <input type="text" id="Veh_Cap" name="Veh_Cap" class="form-control input-xs">
                                        </div>
                                    </div>




                                </fieldset>
                                <fieldset class="exa-fieldset" id="cliFormTemp">
                                    <legend class="Titulos2">Ultimo Chofer</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Ruc:</label>
                                        <div class="col-xs-6"><span name="Ruc_Chofer" class="form-control input-xs datatitle"></span></div>
                                        <label class="col-xs-2 control-label label-xs">Licencia:</label>
                                        <div class="col-xs-2"><span name="Cho_Tli" class="form-control input-xs datatitle"></span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Chofer:</label>
                                        <div class="col-xs-6"><span name="Chofer" class="form-control input-xs datatitle"></span></div>
                                        <label class="col-xs-1 control-label label-xs">Telf.:</label>
                                        <div class="col-xs-3"><span name="Cho_Tel" class="form-control input-xs datatitle"></span></div>
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
    <!--INICIO DEL DIALOGO BUSCAR CLIENTE-->
    <div id="cliDialog" title="B&uacute;squeda de Cliente"></div>

    <script type="text/javascript">

    </script>
</BODY>

</HTML>