<?php

/**
 * @abstract Permite realizar el registro de empacadoras de camaron
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creación  2025-03-01
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cam_log_productor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Conexion_Productor;
if (isset($cliAjax)) {
    $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
}
$hoy = date("Y-m-d");
if (isset($saveDocumento)) { //Registrar datos de empacadora
    $resp = array('success' => false);
    $empacadora = $obBD_con1->getRowConsulta('empac_camaron.selectWhere', array('Cli_Cod' => $Cli_Cod, 'Emp_Est' => 'A'), $obBD_conexion);
    if (isset($empacadora['Cli_Cod']) && !empty($empacadora['Cli_Cod'])) $resp['message'] = "El Cliente ya se encuentra registrado como empacadora!";

    if (isset($resp['message'])) $obBD_con1->echoJson($resp);
    $obBD_ins1 =  new Class_Log_Conexion_Productor;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $datos = array('Cli_Cod' => $_POST['Cli_Cod'], 'Emc_Est' => 'A');
        // guardo al Cliente como empacadora
        $obBD_ins1->operacionobBD('empac_camaron.insert', $datos, $obBD_conexionIns, true);
        $Prod_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

?>
<!DOCTYPE html>
<html>

<head>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Empacadora</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-3"></div>
                <div class="col-xs-6">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                        <fieldset class="exa-fieldset" id="cliFormTemp">
                            <legend class="Titulos2">Datos del Cliente</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-6">
                                    <input name="Cli_Cod" data-name="Cli_Cod" type="text" style="display:none;" />
                                    <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                    <div class="input-group input-group-xs">
                                        <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Empacadora..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                        <span class="input-group-btn">
                                          <button id="Prv_Btn" type="button" onclick="$('#cliDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Cliente:</label>
                                <div class="col-xs-6">
                                    <span name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Dirección:</label>
                                <div class="col-xs-10">
                                    <div class="input-group input-group-xs">
                                        <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                        <span class="input-group-addon bold">e-mail:</span>
                                        <input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />                                 
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="center">
                            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                        <div class="help-block"></div>
                    </form>
                </div>
                <div class="col-xs-3"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function validaDocument() {
            var data = $('#formDocumento').getData('saveDocumento');
            if ($.isEmpty(data.Cli_Cod)) return $.alert("Debe escoger un Cliente!", null, 'alert');
            $.createDialogConfirm('¿Desea guardar empacadora?', data, saveDocument);
        }

        function saveDocument(data) {
            $.saveDataJson('', data,
                function(resp) {
                    //haciendas.clearGrid();
                    $('#formDocumento').setData({});
                    $('#formDocumento').setData({}, 'name');
                }
            );
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
     <div id="cliDialog" title="B&uacute;squeda de Cliente"></div>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <script>
        function selectCliente(cliente) {
            var reset = ($('#reset').val() !== '0');
            $('#cliFormTemp').setData($.extend(cliente, {
                op_opciones: 'c'
            }), 'name').find('.dialogSearch').addClass('x');
            $('#Aco_Des').val(cliente.Cliente);
            $('#cliDialog').dialog('close');
        }
         //DIALOG BUSCAR CLIENTE
        $('#cliDialog').createSearchDialog({colModel:[
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15,align:"center",hidden:true },
                { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'Cliente', width: 90},
                { label: 'Dir.', name: 'Prs_Dir', width: 50,align:"center"  },
                { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectCliente} }
            ]},{ title:'cliente' });
    </script>
</body>

</html>