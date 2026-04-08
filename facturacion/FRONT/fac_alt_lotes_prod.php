<?php
/**
 * @abstract Permite el alta de Lotes de productos
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creación: 02-04-2019
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

/**
 * Busqueda de proveedores
 */
if (isset($provAjax)) {
    $datos = array_merge($_GET, array('setWhere' => array('setEmpCod')));
    $proveedores = $obBD_con1->getPageGrid('proveedore.selectWhere', $datos, $obBD_conexion);
    $obBD_con1->echoJson($proveedores);
}
/**
 * Busqueda de Productos
 */
if (isset($prodAjax)) {
    //setSucCod
    $datos = array_merge($_GET, array('setWhere' => array('setSucCod')));
    $productos = $obBD_con1->getPageGrid('producto.selectWhere', $datos, $obBD_conexion);
    $obBD_con1->echoJson($productos);
}

/**
 * Busqueda de Lotes por Pro_Cod
 */
if (isset($searchLotesProd)) {
    $lotes = array(
        'success' => true,
        'getLabor' => $obBD_con1->getArrayConsulta('loteprod.selectWhere', array('where' => array('prd.Pro_Cod' => $Pro_Cod), 'setWhere' => array('isActive', 'getProveedor')), $obBD_conexion),
    );
    $obBD_con1->echoJson($lotes);
}
/**
  *Validaciones
 */
if(isset($searchActProdLot)){
	$resultadoExistente = array(
		'success' => true,
		'getAtLotesProd'=>$obBD_con1->getArrayConsulta('loteprod.selectWhere',array('where' => array('Pro_Cod'=>$Pro_Cod, 'Lte_Ser'=>$Serie),'setWhere' => array('isActive', 'getProveedor') ), $obBD_conexion),
	);
	$obBD_con1->echoJson($resultadoExistente);
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
/**
 * Guardar
 */
if (isset($save)) {
    $resp = array('success' => false);
    $obBD_ins1 = new Class_Log_Datos_Lte;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        $data = $_POST;
        if (isset($saveLotes)) {
            foreach ($lotes as $lot) {
                $obBD_ins1->operacionobBD('loteprod.insert', array('Pro_Cod' => $lot['Pro_Cod'], 'Lte_Alt' => $lot['Lte_Alt'], 'Lte_Cad' => $lot['Lte_Cad'], 'Lte_Ser' => $lot['Lte_Ser'], 'Prv_Cod' => $lot['Prv_Cod'], 'Lte_Obs'=>$lot['Lte_Obs'], 'Lte_Nti'=>$lot['Lte_Nti']), $obBD_conexionIns, true);
            }
        }
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
        <script></script>
        <style></style>
    </HEAD>
    <BODY>
        <div class="panel panel-main" id="formFinal">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Lotes con Productos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panels-area form-horizontal normal ">
                            <div class="col-md-4 col-sm-4 col-md-offset-0">
                                <form id="frm_actividades" name="frm_actividades" class="form-horizontal normal" action="javascript:saveData('frm_actividades','saveLotes','noEsDialog')">
                                    <input id="Pro_Cod" name="Pro_Cod" type="text" class="hidden" />
                                    <input id="Ubi_Cod" name="Ubi_Cod" type="text" class="hidden" />
                                    <fieldset class="exa-fieldset" id="actFormTemp">
                                        <legend class="Titulos2">Datos</legend>
                                        <div class="col-xs-12" style="padding-top: 10px;">
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-xs required">Producto:</label>
                                                <div class="col-sm-9">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="Producto" name="Producto" class="form-control input-sm text" style="text-align: left" onchange="" onkeypress="" >
                                                        <span class="input-group-btn"><button onclick="$('#prodDialog').dialog('open');" class="btn btn-success" type="button"><i class="glyphicon glyphicon-search"></i></button></span>
                                                    </div>

                                                </div>
                                            </div>

                                            <!-- <div class="form-group">
                                            <label class="col-sm-3 control-label label-xs">Des. Larga:</label>
                                            <div class="col-sm-9">
                                            <input id="Ite_Lar" name="Ite_Lar" class="form-control input-sm text" placeholder="" readonly="readonly">
                                            </div>
                                            </div> -->
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-xs">Detalle:</label>
                                                <div class="col-sm-9">
                                                    <input id="Pro_Obs" name="Pro_Obs" class="form-control input-sm text" placeholder="" readonly="readonly">
                                                </div>
                                            </div>
                                            <!--  <div class="form-group">
                                                                                                                        <label class="col-sm-3 control-label label-xs">Categoria:</label>
                                                                                                                        <div class="col-sm-8">
                                                                                                                        <input id="Cat_Des" name="Cat_Des" class="form-control input-sm text" placeholder="" readonly="readonly">
                                                                                                                        </div>
                                                                                                                        </div> -->
                                            <!--  <div class="form-group">
                                                                                                                                                                        <label class="col-sm-3 control-label label-xs">Ubicaci&oacute;n:</label>
                                                                                                                                                                        <div class="col-sm-8">
                                                                                                                                                                        <input id="Ubi_Des" name="Ubi_Des" class="form-control input-sm text" placeholder="" readonly="readonly">
                                                                                                                                                                        </div>
                                                                                                                                                                        </div> -->

                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-xs">Marca:</label>
                                                <div class="col-sm-5">
                                                    <input id="Mar_Des" name="Mar_Des" class="form-control input-sm text" placeholder="" readonly="readonly">
                                                </div>
                                            </div>


                                            <div class="form-group">
                                                <label class="col-sm-3 control-label label-xs">Stock:</label>
                                                <div class="col-sm-3">
                                                    <input type="text" id="Stk_Can" name="Stk_Can" class="form-control input-xs text" onkeypress="" value="" size="8" maxlength="8"
                                                           style="text-align: right" required readonly="readonly">
                                                </div>
                                            </div>


                                        </div>
                                    </fieldset>
                                </form>
                                <legend class="Titulos2">Listado de Lotes Activos</legend>
                                <div class="form-horizontal normal" style="padding-left:1px;">
                                    <div class="form-group condensed">
                                        <div class="col-sm-10 col-md-offset-1">
                                            <div class="pull-left">
                                                <table id="detaLotesAct"></table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="col-xs-1"></div>
                            <div class="col-xs-7" style="padding-bottom: 8px; min-height: 300px;" id="gridLotes">
                                <table id="lotesGrid"></table>
                                <div id="lotesGridPager"></div>
                            </div>
                            
                            <div style="text-align: center;padding-top: 5px;">
                                <button type="button" id="btn_gua_act" name="btn_gua_act" class="btn btn-primary btn-sm" onclick="$('#frm_actividades').formSubmit();"
                                        disabled="disabled">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="prodDialog" title="B&uacute;squeda de Productos">
            <form id="frmProductos">
                <input type="hidden" id="CodFormProd" name="CodFormProd">
            </form>
        </div>
        <div id="provDialog" title="B&uacute;squeda de Proveedor">
            <form id="frmProveedores">
                <input type="hidden" id="CodFormProv" name="CodFormProv">
            </form>
        </div>
        <script src="../VALIDACIONES/fac_val_alt_lotes_prod.js?k=112"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    </BODY>

</HTML>