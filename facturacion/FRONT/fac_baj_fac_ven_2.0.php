<?php

/**
 * @abstract Permite realizar la baja de ventas
 * @author Cesar Bermeo
 * @version 2.0
 * Fecha de creaci�n 23-11-2018
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Vent;
//$obBD_con1->debugLogs(false);
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/**
 * Para saber si lleva contabilidad
 */
$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere', array('setWhere' => array('setEmpCod')), $obBD_conexion);
$obBD_con1->echoLog($configs);

/**
 * Busqueda de toas las ventas
 */
if (isset($searchAllVentas)) {
    //$obBD_con1->echoLog('** PHP VENTAS AJAX ***');
    if ($configs['Cof_Con'] === 'S') {
        //$obBD_con1->echoLog('** PHP SI AJAX ***');
        $datos = array_merge($_GET, array(
            'setWhere' => array(/*'byCcpp', 'byVentasCompr',*/'setSucCod'),
            'join' => array(
                'ccpp_cobrar' => array('type' => 'joinLeft', 'cols' => '*', 'pk' => 'Vet_Cod'),
                'ventas_compr' => array('type' => 'joinLeft', 'cols' => array('Com_Cod2' => "Com_Cod"), 'pk' => 'Vet_Cod')
            )
        ));
        $resultado = $obBD_con1->getPageGrid('ventas.selectWhere', $datos, $obBD_conexion, true);

        foreach ($resultado['rows'] as &$vet) {
            $det = $obBD_con1->getRowConsulta('det_ccpp_c.selectWhere', array('det_ccpp_c.Cpc_Cod' => $vet['Cpc_Cod'], 'cmpr.Com_Est' => 'A', 'setWhere' => array('isActive', 'byComprobante', 'setCount')), $obBD_conexion);
            $vet['Detalle'] = $det['totalD'] * 1 > 0 ? 'S' : 'N';
        }
        unset($vet);
        //$obBD_con1->echoLog($resultado);
        $obBD_con1->echoJson($resultado);
    } else {
        //$obBD_con1->echoLog('** PHP NO AJAX ***');
        $datos = array_merge($_GET, array('setWhere' => array('setSucCod', 'setTotales')));
        $resultado = $obBD_con1->getPageGrid('ventas.selectWhere', $_GET, $obBD_conexion);
        foreach ($resultado['rows'] as &$vet) {
            $vet['Detalle'] = 'N';
        }
        //$obBD_con1->echoLog($resultado);
        $obBD_con1->echoJson($resultado);
    }
}
/**
 * Actualizar el estado de una venta y comprobante
 */
if (isset($upVenta)) {
    $obBD_ins1 = new Class_Log_Datos_Vent;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    //$obBD_ins1->debug(true);
    $stado = true;
    $obBD_ins1->validaCierrePeriodo('ventas', 'Caj_Fec', 'ventas.Vet_Cod', null, $Vet_Cod, $obBD_conexionIns, 'S');
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try {
        if ($configs['Cof_Con'] === 'S') {
            $ventaDetalle = $obBD_ins1->getArrayConsulta('ventas.selectWhere', array('ventas.Vet_Cod' => $Vet_Cod, 'setWhere' => array('setEmpCod', 'setVentasDet')), $obBD_conexionIns, true);

            $numKardex = $obBD_ins1->getRowConsulta('kardex_ie.sql.getNext', array('where' => array('Vet_Cod' => $Vet_Cod)), $obBD_conexionIns, true);
            $Kar_Int = $numKardex['total'] + 1;
            foreach ($ventaDetalle as &$vetD) {

                $anulacion = $obBD_ins1->getRowConsulta(2, array('Pro_Cod' => $vetD['Pro_Cod'], 'Vet_Cod' => $Vet_Cod), $obBD_conexionIns);

                $obBD_ins1->operacionobBD(3, array(
                    'Kar_Int' => $anulacion['Kar_Int'],
                    'Vet_Cod' => $Vet_Cod,
                    'Iva_Cod' => $anulacion['Iva_Cod'],
                    'Pro_Cod' => $anulacion['Pro_Cod']
                ), $obBD_conexionIns, true);
				
               
                $stockAct = $obBD_ins1->getRowConsulta('stock.selectWhere', array('stock.Pro_Cod' => $vetD['Pro_Cod'], 'stock.Suc_Cod' => $Ses_Suc_Cod), $obBD_conexionIns, true);

                if (($stockAct['Stk_Can']) * 1 > 0) {
                    if ($stado) {
                        $cant_ACtual = (($stockAct['Stk_Can']) * 1 + ($vetD['Vet_Can']) * 1);
                        $stado = false;
                    } else {
                        $cant_ACtual = (($cant_ACtual) * 1 + ($vetD['Vet_Can']) * 1);
                    }
                } else {
                    if ($stado) {
                        $cant_ACtual = ($vetD['Vet_Can'] * 1) - ($stockAct['Stk_Can']) * (-1);
                        $stado = false;
                    } else {
                        $cant_ACtual = ($vetD['Vet_Can'] * 1) - ($cant_ACtual) * (-1);
                    }
                }

                $obBD_ins1->operacionobBD('stock.update', array('Pro_Cod' => $stockAct['Pro_Cod'], 'Suc_Cod' => $Ses_Suc_Cod, 'Stk_Can' => $cant_ACtual), $obBD_conexionIns, true);

                $obBD_ins1->operacionobBD(1, array('Pro_Cod' => $stockAct['Pro_Cod'], 'Pro_Stk' => $cant_ACtual), $obBD_conexionIns);

                $Kar_Int = $Kar_Int + 1;
                unset($stockAct);
            }

            unset($vetD);
            $obBD_ins1->operacionobBD('ventas.setInactive', array('Vet_Cod' => $Vet_Cod), $obBD_conexion);
            $obBD_ins1->operacionobBD('venta_reembolsos.deleteWhere', array('Vet_Cod' => $Vet_Cod), $obBD_conexion);
            $obBD_ins1->operacionobBD('comprobantes.setInactive', array('Com_Cod' => $Com_Cod), $obBD_conexion);
        } else {
            
            $obBD_ins1->operacionobBD('ventas.setInactive', array('Vet_Cod' => $Vet_Cod), $obBD_conexion);
            $ventaDetalle = $obBD_ins1->getArrayConsulta('ventas.selectWhere', array('ventas.Vet_Cod' => $Vet_Cod, 'setWhere' => array('setEmpCod', 'setVentasDet')), $obBD_conexionIns, true);

            $numKardex = $obBD_ins1->getRowConsulta('kardex_ie.sql.getNext', array('where' => array('Vet_Cod' => $Vet_Cod)), $obBD_conexionIns);
            $Kar_Int = $numKardex['total'] + 1;
            foreach ($ventaDetalle as &$vetD) {

                $anulacion = $obBD_ins1->getRowConsulta(2, array('Pro_Cod' => $vetD['Pro_Cod'], 'Vet_Cod' => $Vet_Cod), $obBD_conexionIns);
                $obBD_ins1->operacionobBD(3, array(
                    'Kar_Int' => $anulacion['Kar_Int'],
                    'Vet_Cod' => $Vet_Cod,
                    'Iva_Cod' => $anulacion['Iva_Cod'],
                    'Pro_Cod' => $anulacion['Pro_Cod']
                ), $obBD_conexionIns, true);

                if ($stado) {
                    $stockAct = $obBD_ins1->getRowConsulta('stock.selectWhere', array('stock.Pro_Cod' => $vetD['Pro_Cod'], 'stock.Suc_Cod' => $Ses_Suc_Cod), $obBD_conexionIns, true);
                }

                if (($stockAct['Stk_Can']) * 1 > 0) {
                    $cant_ACtual = (($stockAct['Stk_Can']) * 1 + ($vetD['Vet_Can']) * 1);
                } else {
                    $obBD_ins1->echoLog('Negativo');
                    $cant_ACtual = ($vetD['Vet_Can'] * 1) - ($stockAct['Stk_Can']) * (-1);
                }
                $obBD_ins1->operacionobBD('stock.update', array('Pro_Cod' => $stockAct['Pro_Cod'], 'Suc_Cod' => $Ses_Suc_Cod, 'Stk_Can' => $cant_ACtual), $obBD_conexionIns, true);
                $Kar_Int = $Kar_Int + 1;
            }
            unset($vetD);
        }

		$obBD_ins1->operacionobBD('viaje.update', array('Vet_Cod' => null, 'Vet_Ite' => null, 'where' => array('Vet_Cod' => $Vet_Cod)), $obBD_conexionIns);
        //Actualizar los estados de los manifiestos que tengan relacion con la venta anulada
        $existe_manifiesto = $obBD_ins1->getRowConsulta(5, array('Vet_Cod' => $Vet_Cod), $obBD_conexionIns);
        if (!empty($existe_manifiesto) && isset($existe_manifiesto['total']) && intval($existe_manifiesto['total']) > 0) {
            $obBD_ins1->operacionobBD(4, array('Vet_Cod' => $Vet_Cod), $obBD_conexionIns);
        }
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $e->getMessage();
        $obBD_ins1->echoJson($resp);
    }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $resp['success'] = true; //VERIFICAR SI LA TRANSACCION ESTA REALIZANDO CORRECTAMENTE
    $obBD_ins1->echoJson($resp);
}
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE-->
    <TITLE>
        <?Php echo "Ventas Anular [EXA]"; ?>
    </TITLE>
    <meta charset="UTF-8">
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
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Anular de Ventas</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panels-area form-horizontal normal ">

                        <form id="frm_venta" name="frm_venta" class="form-horizontal normal" action="javascript:$('#tableVentas').Search('#frm_venta','searchAllVentas');">
                            <div class="row">
                                <input name="fecha_inicio" type="hidden" value=" <?php echo $periodos[0]['Pec_Fei'] ?>" />
                                <input name="fecha_fin" type="hidden" value=" <?php echo $periodos[0]['Pec_Fef'] ?> " />
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">B&uacute;squeda de Ventas</legend>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                            <div class="col-sm-10 radioset">
                                                <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)" />
                                                <label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;</label>
                                                <input id="rad_ba2" name="op_opciones" type="radio" value="b" onclick="setfocus(this.form.search)" />
                                                <label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                                <input id="rad_ba3" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" />
                                                <label for="rad_ba3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                            <div class="col-sm-7">
                                                <div class="input-group">
                                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
                                                        autofocus="">
                                                    <span class="input-group-btn">
                                                        <button id="btnSearch" onclick="this.form.submit()" class="btn btn-success btn-xs" type="button" title="Buscar Venta">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>

                                </div>
                                <div class="col-xs-6">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Filtros</legend>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                            <div class="col-xs-3">
                                                <select id="Pec_Cod" name="Pec_Cod" onchange="" class="form-control input-xs search_pec getData">

                                                    <?php
                                                    foreach ($periodos as $p) {
                                                        echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <label class="col-xs-2 control-label label-xs">Mes:</label>
                                            <div class="col-xs-3">
                                                <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
                                                    <option value="">
                                                        << TODOS>>
                                                    </option>
                                                    <?Php for ($i = 1; $i <= 12; $i++) { ?>
                                                        <option <?php if ($i == $mes) {
                                                                    echo "selected=''";
                                                                } ?> value="
							<?Php echo $i; ?>">
                                                            <?php echo mes($i, 1); ?>
                                                        </option>
                                                    <?Php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                            </div>

                        </form>
                        <div class="col-sm-12" style="min-height: 200px; padding-bottom: 5px;">
                            <table id="tableVentas"></table>
                            <div id="tableVentasPager"></div>
                            <!--<div class="Titulos2">
                                                                                                                    <span id="plan-footer"><strong>Leyenda:</strong>
                                                                                                                    <span class="glyphicon glyphicon-stop green"></span>
                                                                                                                    Contiene Pagos |
                                                                                                                    <span class="glyphicon glyphicon-stop red"></span>
                                                                                                                    Anulados/Inactivos |
                                                                                                                    <span class="glyphicon glyphicon-lock orange"></span>
                                                                                                                    Contiene Pagos
                                                                                                                </div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../VALIDACIONES/fac_val_fac_ven_2.0.js?k=114"></script>
    <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>