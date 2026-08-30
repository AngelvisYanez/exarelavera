<?php

/**
 * @abstract Permite realizar el registro de productores de productos de camaron
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
if (isset($provAjax)) {
    $obBD_con1->getPageGridJson('proveedore.selectWhere', array_merge($_GET, array('setWhere' => 'isNotProductor')), $obBD_conexion);
}
/* cuscar cuentas contables */
if (isset($cuenAjax)) {
    $responce = $obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET, array('setWhere' => array('byPecCod', 'isActive', 'isDetalle'))), $obBD_conexion, true);
}

if (isset($saveDocumento)) { //Registrar datos del productor
    $resp = array('success' => false);
    $productore = $obBD_con1->getRowConsulta('productor_camaron.selectWhere', array('Prv_Cod' => $Prv_Cod), $obBD_conexion);
    if (isset($productore['Prv_Cod']) && !empty($productore['Prv_Cod'])) $resp['message'] = "El Proveedor ya se encuentra registrado como productor!";
    if (isset($resp['message'])) $obBD_con1->echoJson($resp);
    $obBD_ins1 =  new Class_Log_Conexion_Productor;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $datos = array('Prv_Cod' => $_POST['Prv_Cod']);
        // guardo al proveedor como productor
        $obBD_ins1->operacionobBD('productor_camaron.insert', $datos, $obBD_conexionIns,true);
        $Prod_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
        // guardo las haciendas
        foreach ($haciendas as $haci) {
            $haci['Prod_Cod'] = $Prod_Cod;
            $obBD_ins1->operacionobBD('sector_camaronera.insert', $haci, $obBD_conexionIns, true);
        }
        // $obBD_ins1->operacionobBD('acopio.insert', array('Suc_Cod' => $Ses_Suc_Cod, 'Act_Tip' => 'PF', 'Prd_Cod' => $Prd_Cod, 'Aco_Des' => $Aco_Des, 'Aco_Def' => 'N', 'Aco_Tip' => 'B'), $obBD_conexionIns);
        // guardo las cuentas contables para configuracion
        /* if (!empty($Pld_Cod_Cxc))
            $obBD_ins1->operacionobBD('productor_det_plan.insert', array('Pld_Cod' => $Pld_Cod_Cxc, 'Prd_Cod' => $Prd_Cod, 'Prp_Tip' => 'CC'), $obBD_conexionIns);
        if (!empty($Pld_Cod_Inv))
            $obBD_ins1->operacionobBD('productor_det_plan.insert', array('Pld_Cod' => $Pld_Cod_Inv, 'Prd_Cod' => $Prd_Cod, 'Prp_Tip' => 'IN'), $obBD_conexionIns);
        $resp['Prd_Cod'] = $Prd_Cod;*/
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }
    // finalizo la transaccion y compruebo errores
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}




/*
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>'setEmpCod','order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$cur_periodo=current($periodos);*/

$hoy = date("Y-m-d");
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../VALIDACIONES/cam_val_productor.js"></script>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Registrar Productor</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-xs-6">
                    <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
                        <fieldset class="exa-fieldset" id="provFormTemp">
                            <legend class="Titulos2">Datos del Proveedor</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                                <div class="col-xs-6">
                                    <!--input name="Prs_Cod" data-name="Prs_Cod" type="text" style="display:none;" /-->
                                    <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                                    <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                    <div class="input-group input-group-xs">
                                        <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Proveedor..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                        <span class="input-group-btn">
                                            <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                        </span>
                                    </div>
                                </div>
                              </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs required">Proveedor:</label>
                                <div class="col-xs-6">
                                    <span name="Proveedor" data-name="Proveedor" class="form-control input-xs databind datatitle"></span>
                                </div>
                              </div>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Dirección:</label>
                                <div class="col-xs-10">
                                    <div class="input-group input-group-xs">
                                        <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                        <span class="input-group-addon bold">e-mail:</span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <!--fieldset class="exa-fieldset" id="provFormTemp">
                        <legend class="Titulos2">Datos del Productor</legend>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs">Código Aux.:</label>
                            <div class="col-xs-6" >
                                <input type="text" name="Prd_Cau" class="form-control input-xs" value=""  />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Cupo Referencial:</label>
                            <div class="col-xs-6" >
                                <input type="number" name="Prd_Cup" class="form-control input-xs nospin" value="" step="1" min="1" required="" value="0" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-4 control-label label-xs required">Nombre Acopio:</label>
                            <div class="col-xs-6" >
                                <input type="text" id="Aco_Des" name="Aco_Des" class="form-control input-xs nospin" value="" required="" value="" />
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Cuentas Contables</legend>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Periodo Contable</label>
                            <div class="col-xs-3" >
                                <select id="Pec_Cod" onchange="clearCuentas(); $('#cuenForm').setData($('#Pec_Cod').find('option:selected').data(),'name');" class="form-control input-xs">
                                    <?php foreach ($periodos as $p) {
                                        echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="ctaCC1FormTemp">
                            <label class="col-xs-3 control-label label-xs">Cta. x Cobrar:</label>
                            <div class="col-xs-9" >
                              <input name="Pld_Cod_Cxc" data-name="Pld_Cod" type="text" style="display:none;" />
                              <div class="input-group input-group-xs">
                                <span data-name="Pld_Cdc" class="input-group-addon bold"> </span>
                                <span data-name="Pld_Des" placeholder="Ingrese Proveedor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" ></span>
                                <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#Index').val('ctaCC1FormTemp'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                </span>
                              </div>
                            </div>
                        </div>
                        <div class="form-group" id="ctaCC2FormTemp">
                            <label class="col-xs-3 control-label label-xs">Cta. Inventario:</label>
                            <div class="col-xs-9" >
                              <input name="Pld_Cod_Inv" data-name="Pld_Cod" type="text" style="display:none;" />
                              <div class="input-group input-group-xs">
                                <span data-name="Pld_Cdc" class="input-group-addon bold"> </span>
                                <span data-name="Pld_Des" class="form-control input-xs clearable dialogSearch" tabindex="1" ></span>
                                <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#Index').val('ctaCC2FormTemp'); $('#cuenDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                </span>
                              </div>
                            </div>
                        </div>
                    </fieldset-->
                        <div class="center">
                            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                        <div class="help-block"></div>
                    </form>
                </div>

                <div class="col-xs-6">
                    <!--                    <div class="jqHeaderFirst jqFirst">
                        <table id="marcaBan"></table>
                        <div id="marcaBanPager"></div>
                    </div>
                    <div class="help-block"></div>-->
                    <div class="jqHeaderFirst jqFirst">
                        <table id="haciendas"></table>
                        <div id="haciendasPager"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <script type="text/javascript">
        $(function() {
            $('#Pec_Cod').trigger('change');
        });

        function saveHacienda() {
            var data = haciendasCreateForm.getData();
            if ($.isEmpty(data['Index'])) {
                data['Index'] = haciendas.nextIndex('Index');
                data['Sec_Est'] = 'A';
                haciendas.setRow(data);
            } else {
                haciendas.changeRow(data.Index, $.extend(haciendas.getCell(data.Index, 'OriginalData'), data));
            }
            haciendasCreateDlg.dialog('close');
        }

        function updateHacienda(data) {
            var data = haciendas.getCell(data.Index, 'OriginalData');
            $('#MagapCod').val($.isEmpty(data['Prh_Mag']) ? 'N' : 'S').trigger('change');
            haciendasCreateForm.setData(data);
            haciendasCreateDlg.dialog('open');
        }

        function deleteHacienda(data) {
            haciendas.delRowData(data.Index);
        }

        function clearCuentas() {
            $('#ctaCC1FormTemp').setData({}, 'name');
            $('#ctaCC2FormTemp').setData({}, 'name');
        }

        function validaDocument() {
            var data = $('#formDocumento').getData('saveDocumento');
            if ($.isEmpty(data.Prv_Cod)) return $.alert("Debe escoger un proveedor!", null, 'alert');
            data['haciendas'] = haciendas.getGridColumn('OriginalData');
            $.arraySpliceFields(data['haciendas'], ['Index']);
            if (data['haciendas'].length === 0) return $.alert("Debe ingresar al menos un sector!", null, 'alert');
            $.createDialogConfirm('¿Esta seguro de guardar el Productor?', data, saveDocument);
        }

        function saveDocument(data) { //console.log(data); console.log(data['rets']);
            $.saveDataJson('', data,
                function(resp) {
                    haciendas.clearGrid();
                    $('#formDocumento').setData({});
                    $('#formDocumento').setData({}, 'name');
                }
            );
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor"></div>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <script>
        function selectProvee(provee) {
            var reset = ($('#reset').val() !== '0');
            $('#provFormTemp').setData($.extend(provee, {
                op_opciones: 'c'
            }), 'name').find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Aco_Des').val(provee.Proveedor);
            $('#provDialog').dialog('close');
        }

        function SelectCta(cta) {
            $('#' + ($('#Index').val())).setData($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), 'name');
            $('#cuenDialog').dialog('close');
        }
    </script>
    <?php include('../COMPONENTES/gestionHaciendaModal.php'); ?>
</BODY>

</HTML>