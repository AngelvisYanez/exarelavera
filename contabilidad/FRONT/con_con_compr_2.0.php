<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

require_once('../LOGICA/con_log_balances.php'); //Consulta de detalle del balance
require_once('../LOGICA/con_log_docs.php');    //guarda el comprobante y el asiento 

$obBD_conexion = new Class_Log_Conexion_Doc($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Doc;
$obBD_con2 = new Class_Log_Datos_Con;

//Guarda un diario de apertura esde el balance general
$dataDiario = $_POST;
$guardadoExitoso = 0;
if (isset($dataDiario['Diario_Generado']) && $dataDiario['Diario_Generado'] == 'True') {

    $PecCod1_Anio = explode('*', $dataDiario['Pec_Cod1']);
    $PecCod1 = $PecCod1_Anio[0];
    $Anio =  $PecCod1_Anio[1] - 1; //Anio escogido menos uno para buscar el periodo anterior

    //Obtengo las variables necesarias con el plan de cuentas desde el que se obtiene los valores
    $rs_periodos = $obBD_con2->getArrayConsulta(519, $Ses_Emp_Cod . '*' . $Anio, $obBD_conexion);
    $periodo = current($rs_periodos);

    if (count($rs_periodos) > 0) {

        $fechaInicio = $periodo['Pec_Fei'];
        $fechaFin = $periodo['Pec_Fef'];
        $plaCod = $periodo['Pla_Cod'];
        $PecCod2 =  $periodo['Pec_Cod'];

        //Obtengo la utilidad con el Codigo del periodo donde se obtiene las cuentas
        $row_utilidades = $obBD_con2->getRowConsulta(220, $PecCod2, $obBD_conexion);
        $utilidad = $row_utilidades['Pld_Cod'];

        /*
      Obtengo un array con valor, codigo de cuenta y tipo de cada detalle del balance general
      */
        $cuentasDetalle = $obBD_con2->cargarDiarioDesdeBalance($plaCod, 0, $fechaInicio, $fechaFin, $obBD_conexion, 1, $PecCod2, 0, $utilidad, 0, $dataDiario['Max_Niv2'], 1);
        $tamano = count($cuentasDetalle);

        if ($tamano > 0) {

            $proveedorDiario = $obBD_con2->buscarProvedorDiario($obBD_conexion);
            /*
          Calcular el total del comprobante con respecto a los detalles de 'Debe'
          cuentaDetalle[0] = valor del detalle
          cuentaDetalle[1] = Codigo cuenta
          cuentaDetalle[2] = tipo 'D' o 'H'
          */
            $totalComprobante = 0;
            $valorComprobante = 0;
            for ($i = 0; $i < $tamano; $i = $i + 3) {
                if ($cuentasDetalle[$i + 2] == 'D') {
                    if ($cuentasDetalle[$i] < 0) {
                        $valorComprobante = $cuentasDetalle[$i] * -1;
                    } else {
                        $valorComprobante = $cuentasDetalle[$i];
                    }
                    $totalComprobante = $totalComprobante + $valorComprobante;
                }
            }

            /*
          Completo los campos para enviar a guardar el comprobante
          */
            $dataDiario['Pec_Cod'] = $PecCod1;
            $dataDiario['Cli_Cod'] = null;
            $dataDiario['Prv_Cod'] = $proveedorDiario;
            $dataDiario['Com_Num'] = $obBD_con1->getComNumPecAuto(1, $PecCod1, $Anio + 1 . "-01-01", $obBD_conexion);
            $dataDiario['Com_Fec'] = $Anio + 1 . "-01-01";
            $dataDiario['Com_Con'] = 'DIARIO DE APERTURA';
            $dataDiario['Com_Tip'] = 'D';
            $dataDiario['Com_Val'] =  $totalComprobante;
            $dataDiario['Com_Obs'] = 'Para registrar el estado de situacion inicial';
            $dataDiario['Com_Tipo'] = null;
            $dataDiario['Com_Est'] = 'A';
            $dataDiario['Tia_Cod'] = 1;
            $dataDiario['Com_Sys'] = date("Y-m-d H:i:s");
            $dataDiario['Com_Gen'] = 'M';

            $obBD_con1->inicio_transaccion($obBD_conexion);

            //Ingresa el comprobante de Diario de apertura     
            $obBD_con1->operacionobBD(23, $dataDiario, $obBD_conexion);
            $Com_Cod = $obBD_con1->insercionid($obBD_conexion);

            //Ingresa un asiento por cada detalle del balance general
            for ($i = 0; $i < $tamano; $i = $i + 3) {
                if ($cuentasDetalle[$i] < 0) {
                    $cuentasDetalle[$i] = $cuentasDetalle[$i] * -1;
                }
                $asiento = array('Com_Cod' => $Com_Cod, 'Asi_Deh' => $cuentasDetalle[$i + 2], 'Asi_Con' => '', 'Asi_Glo' => 'REGISTRAR ESTADO DE SITUACION INICIAL', 'Pld_Cod' => $cuentasDetalle[$i + 1], 'Asi_Val' => $cuentasDetalle[$i]);
                $obBD_con1->operacionobBD(24, $asiento, $obBD_conexion);
            }

            $obBD_con1->fin_transaccion_nomsn($obBD_conexion);

            //Si guarda correctamente se pone la variable en 1 para realizar la carga automatica de los comprobantes
            if ($obBD_con1->Error == 0) {
                $guardadoExitoso = 1;
            }
        } //FIN DEL IF PARA VERIFICAR SI HAY CUENTAS
        else {
            $guardadoExitoso = 2;
        }
    } //FIN IF DEL PARA VERIFICAR SI HAY PERIODO ANTERIOR
    else {
        $guardadoExitoso = 3;
    }
}

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($ajaxComp)) {
    $data = $_GET;
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(1, $data, $obBD_conexion);
    //ChromePhp::log($responce);
    echo json_encode($responce);
    exit();
}
if (isset($saveData)) {

    $data = $_POST;
    $obBD_con1->validaCierrePeriodo('comprobantes', 'Com_Fec', 'Com_Cod', $form['Com_Fec'], $Com_Cod, $obBD_conexion);
    $mes = explode('-', $form['Com_Fec']);
    $old_mes = explode('-', $form['Old_Com_Fec']);
    if ($form['Tia_Cod'] != $form['Old_Tia_Cod'] || $mes[1] != $old_mes[1])
        $data['form']['Com_Num'] = $obBD_con1->getComNumAuto($Ses_Emp_Cod, $form['Tia_Cod'], $form['Com_Fec'], $obBD_conexion);
    else
        $data['form']['Com_Num'] = $form['Old_Com_Num'];
    $codigo = $form['Tia_Abr'] . '-' . $mes[1] . '-' . $data['form']['Com_Num'];
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(8, $data['form'], $obBD_conexion);
    if (!empty($asien))
        foreach ($asien as $row) {
            $obBD_con1->operacionobBD(9, $row, $obBD_conexion);
        }
    if (!empty($Cop_Cod)) {
        $obBD_con1->operacionobBD(10, $Cop_Cod . '*' . $data['form']['Doc_Obs'], $obBD_conexion);
    }
    if (!empty($Vet_Cod)) {
        $obBD_con1->operacionobBD(13, $Vet_Cod . '*' . $data['form']['Doc_Obs'], $obBD_conexion);
    }
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'codigo' => $codigo, 'link' => baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php") . "?codigo=$Com_Cod&" . (!empty($form['Prv_Cod']) ? "tabla=proveedore&campo=Prv_Cod" : "tabla=cliente&campo=Cli_Cod") . "&tipo=$form[Tia_Cod]&Pec_Cod=$form[Pec_Cod]");
    } else {
        $responce = array('success' => false, 'message' => 'Error al actualizar la informaci&oacute;n!', 'error' => $obBD_con1->MsgError);
    }
    //ChromePhp::log($obBD_con1->MsgError);
    //ChromePhp::log('error');
    echo json_encode($responce);
    exit();
}
if (isset($saveForm)) {
    $obBD_con1->validaCierrePeriodo('comprobantes', 'Com_Fec', 'Com_Cod', $Com_Fec, $Com_Cod, $obBD_conexion);
    /* Mes del comprobante */
    $data = $_POST;
    $data['Com_Tip'] = $Tia_Ini;
    if (substr($Com_Fec, 0, 7) !== substr($Old_Com_Fec, 0, 7)) {
        $data['Com_Num'] = $obBD_con1->getComNumPecAuto($Tia_Cod, $Pec_Cod, $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo
    } else $data['Com_Num'] = $Old_Com_Num;
    $codigo = $Tia_Abr . '-' . substr($Com_Fec, 5, 2) . '-' . $data['Com_Num'];
    /* Inicio de la transaccion */
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
    $obBD_con1->operacionobBD(23, $data, $obBD_conexion); // Inserción del Comprobante       
    $obBD_con1->operacionobBD(25, $Com_Cod, $obBD_conexion); // elimino asiento
    /* Recorre el arreglo de los datos de las cuentas seleccionadas */
    foreach ($cuentas as $row) {
        $asiento = array('Com_Cod' => $Com_Cod, 'Asi_Deh' => $row['Det_Tip'], 'Asi_Con' => $row['Pld_Des'], 'Asi_Glo' => $row['Glosa'], 'Pld_Cod' => $row['Pld_Cod'], 'Asi_Val' => $row['Det_Tip'] == 'D' ? $row['Debe'] : $row['Haber']);
        $obBD_con1->operacionobBD(24, $asiento, $obBD_conexion); //guardado de los asientos
    }
    /* Finaliza la transacción */
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'codigo' => $codigo, 'link' => "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod");
    } else {
        $responce['success'] = false;
        $responce['error'] = $obBD_con1->MsgError;
    }
    //ChromePhp::log($obBD_con1->MsgError);
    echo json_encode($responce);
    exit();
}
if (isset($loadData)) {
    $data = filter_input_array(INPUT_GET);
    $responce['success'] = true;
    $responce['compro'] = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
    $responce['compro']['detalle'] = $obBD_con1->getArrayConsulta(4, $data, $obBD_conexion);
    $responce['cheques']['detalle'] = $obBD_con1->getArrayConsulta(21, $data['Com_Cod'], $obBD_conexion);
    $responce['cheques']['conteo'] = count($responce['cheques']['detalle']);
    if (!empty($Cop_Cod)) {
        $responce['compra'] = $obBD_con1->getRowConsulta(5, $data, $obBD_conexion);
        $responce['compra']['detalle'] = $obBD_con1->getArrayConsulta(6, $data, $obBD_conexion);
    }
    if (!empty($Vet_Cod)) {
        $responce['venta'] = $obBD_con1->getRowConsulta(11, $data, $obBD_conexion);
        $responce['venta']['detalle'] = $obBD_con1->getArrayConsulta(12, $data, $obBD_conexion);
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);
    exit();
}
if (isset($cuen2Ajax) || isset($cuenAjax)) {
    $data = filter_input_array(INPUT_GET);
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(7, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/* Sección para cargar datos en el Jqgrid referente a los proveedores*/
if (isset($persAjax)) {
    $data = $_GET;
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(19, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/*Sección para cargar datos en el Jqgrid referente a los proveedores*/
if (isset($provAjax)) {
    $data = $_GET;
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(17, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
/*Sección para cargar datos en el Jqgrid referente a los clientes*/
if (isset($cliAjax)) {
    $data = $_GET;
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(18, $data, $obBD_conexion);
    echo json_encode($responce);
    exit();
}
if (isset($cargarReportes)) {
    try {
        $response['reportes'] = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        $response['success'] = true;
    } catch (Exception $ex) {
        $response['message'] = $ex->getMessage();
    }
    $obBD_con1->echoJson($response);
}

if (isset($cuenAjaxv1)) {
    if ($des_plan != '') {
        //$data = " AND (det_plan.Pld_Des , det_plan.Pld_Cdc)  LIKE '%$des_plan%'";
        $data = " AND (det_plan.Pld_Des LIKE '%$des_plan%' OR det_plan.Pld_Cdc LIKE '%$des_plan%')";
    }
    $resultado['rows'] = $obBD_con1->getArrayConsulta(30, $Ses_Emp_Cod . "*" . $data, $obBD_conexion);
    $obBD_con1->echoJson($resultado);
    exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Comprobantes Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php
    $mask_model = 'model1';
    require_once("../../mascaras/unified-loader.php");
    ?>
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js"></script>
    <?php $perio =  $obBD_con1->getRowConsulta(20, $Ses_Emp_Cod, $obBD_conexion); ?>
    <script type="text/javascript">
        var anula = false,
            duplica = false,
            noEdit = true,
            editing = false,
            listsearch, compNoEdit, docuView, dataSend = new Array(),
            pec_min = '<?php echo (!empty($perio['menor']) ? substr($perio['menor'], 0, 4) : '2015'); ?>',
            pec_max = '<?php echo (!empty($perio['mayor']) ? substr($perio['mayor'], 0, 4) : substr($hoy, 0, 4)); ?>',
            tipo;
    </script>
    <script type="text/javascript" src="../VALIDACIONES/con_con_compr.js?x=a"></script>
    <style>
        #tabsSearch.ui-widget-content {background: none !important;}
        .ui-tabs-panel {padding-bottom: 0 !important;}
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Consultar Comprobantes</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-panel">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros:</legend>
                    <div id="tabsSearch" class="ui-tab-fix ui-tabs">
                        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix">
                            <li><a href="#tabs-1">Por Comprobante</a></li>
                            <li><a href="#tabs-2">Por Compras</a></li>
                            <li><a href="#tabs-3">Por Ventas</a></li>
                        </ul>
                        <div id="tabs-1">
                            <form id="formComp" class="form-horizontal normal" action="javascript:listSearch.Search('#formComp','ajaxComp');">
                                <input name="order" type="hidden" value="" />
                                <input name="vets" type="hidden" value="" />
                                <input name="cops" type="hidden" value="" />
                                <input name="comp" type="hidden" value="true" />
                                <input name="opcion_cnta" id="opcion_cnta" type="hidden" value="" />

                                <div class="row">
                                    <div class="col-xs-6">
                                        <!-- static input-->
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs ">Proveedor/Cliente:</label>
                                            <div class="col-xs-8" id="persona">
                                                <input type="text" name="Prs_Cod" value="" style="display: none" />
                                                <input type="text" name="Prv_Cod" value="" style="display: none" />
                                                <input type="text" name="Cli_Cod" value="" style="display: none" />
                                                <div class="input-group input-group-xs">
                                                    <input id="Cli_Ced" name="Persona" type="text" class="form-control" placeholder="Seleccione un Proveedor/Cliente ..." required readonly />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success" onclick="$('#persDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor/Cliente"></span></button>
                                                        <button class="btn btn-success" onclick="$('#persona').setData();$('#formComp').formSubmit();" type="button"><span class="glyphicon glyphicon-eject" title="Buscar Proveedor/Cliente"></span></button>
                                                    </span>
                                                </div><!-- /input-group -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs ">Tipo Comp.:</label>
                                            <div class="col-xs-4">
                                                <select class="form-control input-xs" name="Tia_Ini" id="Tia_Ini" onchange="updateTiaCod(this.value,'Tia_Cod');updateNumCom()">
                                                    <option value="">TODOS</option>
                                                    <option value="I">INGRESO</option>
                                                    <option value="E">EGRESO</option>
                                                    <option value="D">DIARIO</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs ">Tipo Asiento:</label>
                                            <div class="col-xs-8">
                                                <?php $tiasien =  $obBD_con1->getArrayConsulta(2, '', $obBD_conexion); ?>
                                                <select class="form-control input-xs" name="Tia_Cod" id="Tia_Cod" onchange="updateNumCom()">
                                                    <option value="" class="todos">TODOS</option>
                                                    <?php foreach ($tiasien as $row) {  ?>
                                                        <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data-abre="<?php echo $row['Tia_Abr']; ?>"><?php echo mb_convert_encoding($row['Tia_Abr'] . ' - ' . $row['Tia_Des'], 'UTF-8', 'ISO-8859-1'); ?></option>

                                                        <!--option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data-abre="<?php echo $row['Tia_Abr']; ?>"><?php echo $row['Tia_Abr'] . ' - ' . $row['Tia_Des']; ?></option-->
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <!-- static input-->
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs ">Filtrar por:</label>
                                            <div class="col-xs-8">
                                                <div class="radioset">
                                                    <input id="radfil1" name="op_comp" type="radio" value="t" onchange="selectFiltro('t')" alt=""/><label for="radfil1">&nbsp;&nbsp;Todos&nbsp;&nbsp;</label>
                                                    <input id="radfil2" name="op_comp" type="radio" value="a" onchange="selectFiltro('a')" alt="" checked=""/><label for="radfil2">&nbsp;&nbsp;Asiento&nbsp;&nbsp;</label>
                                                    <input id="radfil3" name="op_comp" type="radio" value="r" onchange="selectFiltro('r')" alt=""/><label for="radfil3">&nbsp;&nbsp;Rango&nbsp;&nbsp;</label>
                                                    <input id="radfil4" name="op_comp" type="radio" value="n" onchange="selectFiltro('n')" alt=""/><label for="radfil4">&nbsp;Anulados&nbsp;</label>
                                                    <input id="radfil5" name="op_comp" type="radio" value="c" onchange="selectFiltro('c')" alt=""/><label for="radfil5">&nbsp;Cta.Cont.&nbsp;</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div id='todos' class='filtros' style='display:none'>
                                            <div class="form-group">
                                                <div class="col-xs-3" style="height: 22px"></div>
                                            </div>
                                            <div class="form-group">
                                                <div class="col-xs-9"></div>
                                                <div class="col-xs-3"><button type="submit" id="buscarDiario" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>

                                            </div>
                                        </div>
                                        <div id='asien' class='filtros'>
                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Periodo/Mes:</label>
                                                <div class="col-xs-3">
                                                    <input type="text" class="form-control input-xs" name="Month" id="Month"></span>
                                                </div>
                                            </div>
                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Num. Comprobante:</label>
                                                <div class="col-xs-3">
                                                    <div class="input-group input-group-xs">
                                                        <span class="input-group-addon" id="Com_Num"> # </span>
                                                        <input class="form-control input-xs" name="Com_Num" id="numeroDiarioGenerado" type="text" style="text-align:right" onkeypress="return  validar_decimal(event)" />
                                                    </div>
                                                </div>
                                                <div class="col-xs-2"></div>
                                                <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                            </div>
                                        </div>
                                        <div id='rangos' class='filtros' style='display:none'>
                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Desde:</label>
                                                <div class="col-xs-3">
                                                    <input type="text" class="form-control input-xs" name="Asi_Ini" id="Asi_Ini"></span>
                                                </div>
                                            </div>

                                            <!-- static input-->
                                            <div class="form-group">
                                                <label class="col-xs-4 control-label label-xs ">Hasta:</label>
                                                <div class="col-xs-3">
                                                    <input type="text" class="form-control input-xs" name="Asi_Fin" id="Asi_Fin"></span>
                                                </div>
                                                <div class="col-xs-2"></div>
                                                <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                            </div>
                                        </div>
                                        <!-- Cuenta Contable -->
                                        <div class="form-group" id="Cuent_Cont" style="display:none; margin-left: 80px; margin-top: 15px;">
                                            <label class="col-xs-3 control-label label-xs ">Buscar Cuenta Contable:</label>
                                            <div class="col-xs-8" id="comprobantes" style="padding-right:0;">
                                                    <input type="hidden" class="" name="Pld_Cdc_Compr" id="Pld_Cdc_Compr" value="">
                                                    <input type="hidden" class="" name="Pld_Cod_Compr" id="Pld_Cod_Compr" value="">
                                                    <div class="input-group input-group-xs" id="comprobante">
                                                        <input name="Pld_Des_Compr" id="Pld_Des_Compr" type="text" class="form-control" placeholder="Seleccione una cuenta ..." required readonly />
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-success" onclick="open_plan_cuentas('T');" type="button"><span class="glyphicon glyphicon-search" title="Buscar cuentas"></span></button>
                                                            <button class="btn btn-success" onclick="limpiar_input_ctas('T');" type="button"><span class="glyphicon glyphicon-trash" title="Limpiar Campo"></span></button>
                                                        </span>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </form>
                        </div>
                        <div id="tabs-2" style="display: none;">
                            <form id="formCops" class="form-horizontal normal" action="javascript:listSearch.Search('#formCops','ajaxComp');">
                                <input name="order" type="hidden" value="" />
                                <input name="vets" type="hidden" value="" />
                                <input name="cops" type="hidden" value="true" />
                                <input name="comp" type="hidden" value="" />
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs ">Proveedor:</label>
                                            <div class="col-xs-8" id="proveedor">
                                                <input type="hidden" name="Prv_Cod" id="Prv_Cod" value="" />
                                                <div class="input-group input-group-xs">
                                                    <input name="proveedor" type="text" class="form-control" placeholder="Seleccione un Proveedor ..." required readonly />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                        <button class="btn btn-success" onclick="selectProv({});" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                                    </span>

                                                </div><!-- /input-group -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Tipo Doc.:</label>
                                            <div class="col-xs-8">
                                                <select name="Tic_Cod" class="form-control input-xs">
                                                    <option value="" class="todos">TODOS</option>
                                                    <?php $rs_tipo_comprobante = $obBD_con1->getArrayConsulta(14, '', $obBD_conexion);
                                                    foreach ($rs_tipo_comprobante as $row) { ?>
                                                        <!--option value="<?php echo $row["Tic_Cod"]; ?>"><?php echo $row["Tic_Des"]; ?></option-->
                                                        <option value="<?php echo $row["Tic_Cod"]; ?>"><?php echo mb_convert_encoding($row["Tic_Des"], 'UTF-8', 'ISO-8859-1'); ?></option>

                                                    <?php }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Nro. Documento:</label>
                                            <div class="col-xs-8">
                                                <input type="text" name="Cop_Num" id="Cop_Num" class="form-control input-xs">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Establecer Rango</legend>
                                            <div class="form-group">
                                                <div class="col-xs-12">
                                                    <div class="input-group input-group-sm"><span class="input-group-addon "><span class=""><input type="checkbox" name="chk_sr" id="chk_sr" class="check-big"></span></span><span class="input-group-addon alert-info">Desde</span><input type="text" name="Cop_Ini" id="Cop_Ini" class="form-control" /><span class="input-group-addon alert-info">Hasta</span><input type="text" name="Cop_Fin" id="Cop_Fin" class="form-control" /></div>
                                                </div>
                                            </div>
                                            <!-- Cuenta Contable -->
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Buscar por Cuenta:</label>
                                                <div class="col-xs-9" id="compras">
                                                    <input type="hidden" class="" name="Pld_Cdc_Com" id="Pld_Cdc_Com" value="">
                                                    <input type="hidden" class="" name="Pld_Cod_Com" id="Pld_Cod_Com" value="">
                                                    <div class="input-group input-group-xs" id="compras">
                                                        <input name="Pld_Des_Com" id="Pld_Des_Com" type="text" class="form-control" placeholder="Seleccione una cuenta ..." required readonly />
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-success" onclick="open_plan_cuentas('C');" type="button"><span class="glyphicon glyphicon-check" title="Abrir cuentas"></span></button>
                                                            <button class="btn btn-success" onclick="limpiar_input_ctas('C');" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-xs-2">
                                        <div class="form-group">
                                            <div class="col-xs-3" style="padding-top: 20px;"><button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="tabs-3" style="display: none;">
                            <form id="formVets" class="form-horizontal normal" action="javascript:listSearch.Search('#formVets','ajaxComp');">
                                <input name="order" type="hidden" value="" />
                                <input name="vets" type="hidden" value="true" />
                                <input name="cops" type="hidden" value="" />
                                <input name="comp" type="hidden" value="" />
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs ">Cliente:</label>
                                            <div class="col-xs-8" id="cliente">
                                                <input type="hidden" name="Cli_Cod" value="" />
                                                <div class="input-group input-group-xs" id="cliente">
                                                    <input name="cliente" type="text" class="form-control" placeholder="Seleccione un Cliente ..." required readonly />
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-success" onclick="$('#cliDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Cliente"></span></button>
                                                        <button class="btn btn-success" onclick="selectClie({});" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                                    </span>

                                                </div><!-- /input-group -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Tipo Doc.:</label>
                                            <div class="col-xs-8">
                                                <select name="Tic_Cod" class="form-control input-xs">
                                                    <option value="" class="todos">TODOS</option>
                                                    <?php $rs_tipo_comprobante = $obBD_con1->getArrayConsulta(14, '', $obBD_conexion);
                                                    foreach ($rs_tipo_comprobante as $row) { ?>
                                                        <option value="<?php echo $row["Tic_Cod"]; ?>"><?php echo mb_convert_encoding($row["Tic_Des"], 'UTF-8', 'ISO-8859-1'); ?></option>

                                                        <!--option value="<?php echo $row["Tic_Cod"]; ?>"><?php echo $row["Tic_Des"]; ?></option-->
                                                    <?php }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-3 control-label label-xs">Nro. Documento:</label>
                                            <div class="col-xs-8">
                                                <input type="text" name="Vet_Num" id="Vet_Num" class="form-control input-xs">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <fieldset class="exa-fieldset">
                                            <legend class="Titulos2">Establecer Rango</legend>
                                            <div class="form-group">
                                                <div class="col-xs-12">
                                                    <div class="input-group input-group-sm"><span class="input-group-addon "><span class=""><input type="checkbox" name="chk_sr1" id="chk_sr1" class="check-big"></span></span><span class="input-group-addon alert-info">Desde</span><input type="text" name="Ven_Ini" id="Ven_Ini" class="form-control" /><span class="input-group-addon alert-info">Hasta</span><input type="text" name="Ven_Fin" id="Ven_Fin" class="form-control" /></div>
                                                </div>
                                            </div>
                                            <!-- Cuenta Contable -->
                                            <div class="form-group">
                                                <label class="col-xs-3 control-label label-xs ">Buscar por Cuenta:</label>
                                                <div class="col-xs-9" id="cliente">
                                                    <input type="hidden" class="" name="Pld_Cdc" id="Pld_Cdc" value="">
                                                    <input type="hidden" class="" name="Pld_Cod" id="Pld_Cod" value="">
                                                    <div class="input-group input-group-xs" id="cliente">
                                                        <input name="Pld_Des" id="Pld_Des" type="text" class="form-control" placeholder="Seleccione una cuenta ..." required readonly />
                                                        <span class="input-group-btn">
                                                            <button class="btn btn-success" onclick="open_plan_cuentas('V');" type="button"><span class="glyphicon glyphicon-check" title="Abrir cuentas"></span></button>
                                                            <button class="btn btn-success" onclick="limpiar_input_ctas('V');" type="button"><span class="glyphicon glyphicon-eject" title="Limpiar Campo"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="col-xs-2">
                                        <div class="form-group" style="padding-top: 20px;">
                                            <div class="col-xs-3"><button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </fieldset>
                <div style="min-height: 350px;">
                    <table id="listsearch"></table>
                    <div id="listsearchPager"></div>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="glyphicon glyphicon-arrow-right white" style="background-color: #ff892a!important;height: 12px;width: 14px;text-align: center;"></span> Generacion Automatica(Valores no editables)</span></div>
                </div>
            </div>
            <div id="edit-panel" style="display: none;">
                <form id="formAsien" class="form-horizontal normal" action="javascript:save();">
                    <input name="comp" type="hidden" value="" />
                    <div class="row">
                        <div class="col-xs-3 no_doc_panel" style="display: none"></div>
                        <div id="asientoAutomatico" class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Asiento Contable:</legend>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Tipo Comp.:</label>
                                    <div class="col-sm-3">
                                        <select class="form-control input-xs readOnly" data-compr="Tia_Ini" data-trigger="true" onchange="updateTiaCod(this.value,'Com_Tia_Cod');" disabled="">
                                            <option value="I">INGRESO</option>
                                            <option value="E">EGRESO</option>
                                            <option value="D">DIARIO</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Tipo Asien.:</label>
                                    <div class="col-sm-8">
                                        <select class="form-control input-xs" name="Tia_Cod" data-compr="Tia_Cod" id="Com_Tia_Cod" required="">
                                            <option value="" class="todos">Seleccione..</option>
                                            <?php foreach ($tiasien as $row) {  ?>
                                                <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data-abre="<?php echo $row['Tia_Abr']; ?>"><?php echo $row['Tia_Des']; ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Com_Gen" data-compr="Com_Gen" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Pec_Cod" id="Old_Pec_Cod" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Old_Tia_Cod" data-compr="Tia_Cod" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Old_Com_Fec" data-compr="Com_Fec" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Old_Com_Num" data-compr="Com_Num" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Prv_Cod" data-compr="Prv_Cod" value="" />
                                        <input type="text" class="form-control input-xs hidden" readonly="" name="Cli_Cod" data-compr="Cli_Cod" value="" />
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs ">Fecha:</label>
                                    <div class="col-xs-3">
                                        <input type="text" class="form-control input-xs" name="Com_Fec" data-compr="Com_Fec" id="Com_Com_Fec" value="" />
                                    </div>
                                    <label class="col-xs-2 control-label label-xs ">No. Com.:</label>
                                    <div class="col-xs-3">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon"> # </span>
                                            <input class="form-control input-xs" name="Com_Num" data-compr="Com_Num" type="text" style="text-align:right" onkeypress="return  validar_decimal(event)" readonly="" />
                                        </div>
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Concepto:</label>
                                    <div class="col-sm-8">
                                        <textarea name="Com_Con" data-compr="Com_Con" id="Com_Com_Con" class="form-control input-xs" style="textarea { resize:vertical ; }"></textarea>
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Observación:</label>
                                    <div class="col-sm-8">
                                        <textarea name="Com_Obs" data-compr="Com_Obs" id="Com_Com_Obs" class="form-control input-xs" style="textarea { resize:vertical ; }"></textarea>
                                    </div>
                                </div>
                                <div class="form-group" align="right">
                                    <div class="col-sm-12">
                                        <label>Creado por:</label>
                                        <label name="usu_reg" data-compr="usu_reg" id="usu_reg"></label>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div id="doc_panel" class="col-xs-6 doc_panel">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Documento de <span id="doc_type">Compra</span>:</legend>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Tipo Doc.:</label>
                                    <div class="col-sm-8">
                                        <input data-compra="Tic_Des" data-venta="Tic_Des" type="text" class="form-control input-xs" readonly=""></span>
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Sustento:</label>
                                    <div class="col-sm-8">
                                        <input data-compra="Tri_Des" data-venta="Tri_Des" type="text" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">C.I./R.U.C.:</label>
                                    <div class="col-sm-4">
                                        <input data-compra="Prs_Ced" data-venta="Prs_Ced" type="text" class="form-control input-xs" readonly="">
                                    </div>
                                    <label class="col-xs-1 control-label label-xs ">Pago:</label>
                                    <div class="col-xs-3">
                                        <input data-compra="Tpc_Des" data-venta="Tpc_Des" type="text" class="form-control input-xs" name="Tcp_Des" readonly="">
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Proveedor/Cliente:</label>
                                    <div class="col-sm-8">
                                        <input data-compra="Persona" data-venta="Persona" type="text" class="form-control input-xs" readonly="">
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">

                                    <label class="col-xs-3 control-label label-xs ">No. Doc.:</label>
                                    <div class="col-xs-4">
                                        <input class="form-control input-xs" name="Doc_Num" id="Doc_Doc_Num" type="text" readonly="" />
                                    </div>
                                    <label class="col-xs-1 control-label label-xs ">Fecha:</label>
                                    <div class="col-xs-3">
                                        <input type="text" class="form-control input-xs" name="Doc_Fec" data-compra="Cop_Fec" data-venta="Caj_Fec" readonly="" />
                                    </div>
                                </div>
                                <!-- static input-->
                                <div class="form-group">
                                    <label class="col-sm-3 control-label label-xs ">Observación:</label>
                                    <div class="col-sm-8">
                                        <textarea data-compra="Cop_Obs" data-venta="Vet_Obs" name="Doc_Obs" class="form-control input-xs" style="textarea { resize:vertical ; }"></textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-xs-3 no_doc_panel" style="display: none"></div>
                    <div class="col-xs-6">
                        <div>
                            <table id="compNoEdit"></table>
                            <div id="compNoEditPager"></div>
                        </div>
                    </div>
                    <div class="col-xs-6 doc_panel">
                        <table id="docuView"></table>
                        <div id="docuViewPager"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-3 no_doc_panel" style="display: none"></div>
                    <div class="col-xs-6" style="padding-top:10px;">
                        <button onclick="$('#edit-panel').moveComp('#main-panel').updateGridsSizes();" class="btn btn-sm btn-inverse" title="Volver Atrás"><i class="glyphicon glyphicon-arrow-left"></i><span>&nbsp;&nbsp;Atrás&nbsp;&nbsp;</span></button><span>&nbsp;</span>

                    </div>
                </div>


            </div>
            <div id="modificar-panel" style="display: none;">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Comprobante de <span id="title_comp">Ingreso</span></legend>
                            <form name="formCompConta" id="formCompConta" method="post" action="javascript:validaComp()" class="form-horizontal normal">
                                <input type="hidden" value="" name="Pec_Cod" />
                                <input type="hidden" value="" name="Com_Gen" />
                                <input type="hidden" value="" name="Com_Cod" />
                                <input type="hidden" value="" name="Old_Com_Fec" />
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label label-xs ">Tipo&nbsp;Comprobante:</label>
                                        <div class="col-sm-3">
                                            <select class="form-control input-xs readOnly" name="Tia_Ini" disabled="">
                                                <option value="I">INGRESO</option>
                                                <option value="E">EGRESO</option>
                                                <option value="D">DIARIO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Asiento:</label>
                                        <div class="col-xs-8">
                                            <select class="form-control input-xs" id="Tia_Cod_Comp" name="Tia_Cod" class="isSelectMenu" required>
                                                <option value="">Seleccione...</option>
                                                <?php foreach ($tiasien as $row) {  ?>
                                                    <option value="<?php echo $row['Tia_Cod']; ?>" style="display:none" data-type="<?php echo $row['Tia_Ini']; ?>" data--tia_-cod="<?php echo $row['Tia_Cod']; ?>" data--tia_-abr="<?php echo $row['Tia_Abr']; ?>" data--tia_-des="<?php echo $row['Tia_Des']; ?>"><?php echo $row['Tia_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group persona cliente">
                                        <label class="col-xs-4 control-label label-xs required">Cliente:</label>
                                        <div class="col-xs-8">
                                            <input type="hidden" id="cod_cli" name="Cli_Cod" value="" data-name='Cli_Cod' />
                                            <div class="input-group input-group-xs">
                                                <input id="lblClie" name="cliente" data-name="cliente" onkeydown='if (event.keyCode === 13) buscaCliente();' onchange="if($('#lblClie').val()==='')$('#cod_cli').val('');" class="form-control varios clearable" placeholder="Ingrese Cliente" />
                                                <span class="input-group-btn"><a onclick="$('#cliDialog').dialog('open')" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                            </div><!-- /input-group -->
                                        </div>
                                    </div>
                                    <div class="form-group persona proveedor" style="display: none;">
                                        <label class="col-xs-4 control-label label-xs required">Proveedor:</label>
                                        <div class="col-xs-8">
                                            <input type="hidden" id="cod_pvr" name="Prv_Cod" value="" data-name="Prv_Cod" />
                                            <div class="input-group input-group-xs">
                                                <input id="lblProvee" name="proveedor" data-name="proveedor" onkeydown='if (event.keyCode === 13) buscaProvee();' onchange="if($('#lblProvee').val()==='')$('#cod_pvr').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor" />
                                                <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                            </div><!-- /input-group -->
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs">Concepto:</label>
                                        <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="Con_Con" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-4 control-label label-xs">Observación:</label>
                                        <div class="col-xs-8">
                                            <textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea>
                                            <label class="col-xs-4 control-label label-xs">Elaborado por:</label>
                                            <div class="col-xs-5"><input id="persona" name="persona" type="text" class="form-control input-xs" required /></div>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                        <div class="col-xs-5"><input id="Com_Fec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs ">No. Doc.:</label>
                                        <div class="col-xs-2">
                                            <input class="form-control input-xs" name="Old_Com_Num" type="text" readonly="" style="text-align: right;" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs required">Valor:</label>
                                        <div class="col-xs-5">
                                            <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input class="form-control input-xs" name="Com_Val" id="Com_Val" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <div class="col-sm-12">
                        <div id="compGrilla" style="padding-top: 6px; padding-bottom: 6px; ">
                            <table id="compAsien"></table>
                            <div id="compAsienPager"></div>
                        </div>

                        <button onclick="editing=false; $('#modificar-panel').moveComp('#main-panel').updateGridsSizes();" class="btn btn-sm btn-inverse" title="Volver Atras"><i class="glyphicon glyphicon-arrow-left"></i><span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span></button><span>&nbsp;</span>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        //Nuevos metodos
        var Lista_plan_cuentas;
        $(function() {
            Lista_plan_cuentas = $("#Lista_plan");
            load_plan_cuentas();
        });

        function open_plan_cuentas(opcion) {
            $("#search").val("");
            $("#cod_plan_cntas").val("");
            load_plan_cuentas(opcion);
            $('#Lista_plan').Search('#formCuentas', 'cuenAjaxv1');
            $('#agregar_plan_cuentas').dialog({
                autoOpen: false,
                modal: true,
                width: '50%',
                height: 360,
                title: 'Plan de cuentas',
                open: function(event, ui) {
                    $(this).parent().find('.btn-siguiente').addClass('btn-primary'); // Ejemplo: btn-primary de Bootstrap
                }
            });
            $('#agregar_plan_cuentas').dialog('open');
        }

        function load_plan_cuentas(opcion) {
            $("#opcion_cnta").val(opcion);
            //Dialog buscar clientes
            Lista_plan_cuentas.createGrid({
                mtype: "GET",
                width: 650,
                height: 200,
                datatype: 'json',
                responsive: true,
                regional: 'es',
                autowidth: false,
                shrinkToFit: true,
                cmTemplate: { sortable: false },
                colModel: [
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 10, align: "center", hidden: false },
                    { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 20 },
                    { label: 'Cuenta', name: 'Pld_Des', width: 50,
                        cellattr: function(rowId, tv, rawObject, cm, rdata) {
                            return 'style="white-space: normal;"';
                        }
                    },
                    { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 10, align: 'center', viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: 'SelectCta_filtrar',
                            title: 'Seleccione Cuentas',
                            data: ['Pld_Cod', 'Pld_Cdc', 'Pld_Des']
                        }
                    }
                ]
            })
        }

        function SelectCta_filtrar(cta) {
            var opcion = $("#opcion_cnta").val();
            console.log("Eleccion: ", opcion);

            if (opcion == "V") {
                $("#Pld_Cdc").val(cta.Pld_Cdc);
                $("#Pld_Des").val(cta.Pld_Des);
                $("#Pld_Cod").val(cta.Pld_Cod);
            }

            if (opcion == "C") {
                $("#Pld_Cdc_Com").val(cta.Pld_Cdc);
                $("#Pld_Des_Com").val(cta.Pld_Des);
                $("#Pld_Cod_Com").val(cta.Pld_Cod);
            }

            // añadido 29-30/05/25
            if (opcion == "T") {
                $("#Pld_Cdc_Compr").val(cta.Pld_Cdc);
                $("#Pld_Des_Compr").val(cta.Pld_Des);
                $("#Pld_Cod_Compr").val(cta.Pld_Cod);
            }
            $('#agregar_plan_cuentas').dialog('close');
        }

        function limpiar_input_ctas(opcion) {
            if (opcion == "V") {
                $("#Pld_Cdc").val("");
                $("#Pld_Des").val("");
                $("#Pld_Cod").val("");
            }
            if (opcion == "C") {
                $("#Pld_Cdc_Com").val("");
                $("#Pld_Des_Com").val("");
                $("#Pld_Cod_Com").val("");
            }
            // añadido 29-30/05/25
            if (opcion == "T") {
                $("#Pld_Cdc_Compr").val("");
                $("#Pld_Des_Compr").val("");
                $("#Pld_Cod_Compr").val("");
            }
        }
        //fin de nuevo metodo

        function SelectCta(cta) {
            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), cta['tipo']);
        }

        function SelectCta2(data) {
            compNoEdit.changeRow($('input[name=Asi_Cod]').val(), $.extend(data, {
                act1: 'Yes'
            }));
            $('#cuen2Dialog').dialog('close');
        }

        function selectPerss(data) {
            $('#persona').setData(data);
            $('#persDialog').dialog('close');
            $('#formComp').formSubmit();
        }

        function selectProv(prov) {
            if (editing) $(".persona.proveedor").setData(prov);
            else {
                $("#proveedor").setData(prov);
                $('#formCops').formSubmit();
            }
            $("#provDialog").dialog("close");
        }

        function selectClie(clie) {
            if (editing) $(".persona.cliente").setData(clie);
            else {
                $("#cliente").setData(clie);
                $('#formVets').formSubmit();
            }
            $("#cliDialog").dialog("close");
        }

        function buscaCliente() {
            $.SearchOrDialogArray("#cliDialog", selectClie, {
                'search': $('#lblClie').val(),
                'op_opciones': 'c'
            });
            selectClie({});
        }

        function buscaProvee() {
            $.SearchOrDialogArray("#provDialog", selectProv, {
                'search': $('#lblProvee').val(),
                'op_opciones': 'c'
            });
            selectProv({});
        }
    </script>

    <?php
    if ($guardadoExitoso == 1) {
    ?>
        <script type="text/javascript">
            $(function() {
                $('#numeroDiarioGenerado').val(<?php echo '"' . $dataDiario['Com_Num'] . '"' ?>);
            });

            jQuery(function() {
                jQuery('#Month').val(<?php echo '"' . ($Anio + 1) . '-01' . '"' ?>);
                jQuery('span#Com_Num').html("01-");
                jQuery('#buscarDiario').click();
            });
        </script>

    <?php
    } elseif ($guardadoExitoso == 2) {
    ?>

        <script type="text/javascript">
            alert("No existen cuentas para el periodo anterior al elegido!");
        </script>
    <?php
    } elseif ($guardadoExitoso == 3) {
    ?>
        <script type="text/javascript">
            alert("No existe periodo en el año anterior al elegido!");
        </script>

    <?php
    }
    ?>




    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <div id="cuen2Dialog" title="B&uacute;squeda de Cuentas"></div>
    <div id="chequesDialog" title="Cheques"></div>
    <!--INICIO DEL DIALOGO BUSCAR CLIENTES-->
    <div id="cliDialog" title="B&uacute;squeda de Clientes"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDORES-->
    <div id="provDialog" title="B&uacute;squeda de Proveedores"></div>
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDORES-->
    <div id="persDialog" title="B&uacute;squeda de Proveedores/Clientes"></div>
    <!--INICIO DEL DIALOGO SUCCESS-->
    <div id="successDialog" title="Mensaje del Sistema" style="display: none;">
        <center>
            <b style="font-size:14px;">Se ha actualizado con Exito!</b>
            <h4><b class="blue">Asiento: </b><span class="orange" id="successCodigo">dd-55-55</span></h4>
            <button id="btnImpCompr" type="button" class="btn btn-info" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Comprobante</button>
        </center>
    </div>

    <div id="agregar_plan_cuentas" style="display: none;">
        <div class="col-sm-12">
            <form class="form-horizontal normal" name="formCuentas" id="formCuentas" action="javascript:$('#Lista_plan').Search('#formCuentas','cuenAjaxv1');">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Plan de Cuentas</legend>
                    <div class="col-sm-4">
                    </div>
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" style="margin-bottom: 7px;">Descripción/Cod.Plan:</label>
                            <div class=" col-sm-6">
                                <input class=" form-control input-xs clearable submit" name="des_plan" id="des_plan" type="text" />
                                <input class=" form-control input-xs clearable submit" name="cod_plan" id="cod_plan" type="text" style="display: none;" />
                            </div>
                            <button class=" col-sm-2  btn btn-success btn-xs">Buscar</button>
                        </div>
                    </div>
                </fieldset>
                <div class="row">
                    <div class="col-sm-12">
                        <table id="Lista_plan" style="width: 100%!important;"></table>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
</BODY>

</HTML>