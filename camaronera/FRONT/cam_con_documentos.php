<?php

/**
 * @abstract Consulta todos los procesos que tienen que ver con la negociacion de camaron
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de cración: 16-03-2025
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/cam_log_negociacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cam($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_datos_Cam();
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");
//Vendedor
$vendedor = $obBD_con1->getRowConsulta(19, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);
// Datos de la empacadora
$data_empacadora = $obBD_con1->getArrayConsulta(7, $Ses_Emp_Cod, $obBD_conexion);
// datos del periodo
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

// VENTAS
if (isset($negociaciones_ventas_Ajax)) {
    // Obtener los datos enviados por POST o GET
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $Fec_Ini = isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '';
    $Fec_Fin = isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '';
    $op_opciones = isset($_REQUEST['op_opciones']) ? $_REQUEST['op_opciones'] : 'codnegV';
    $estado = isset($_REQUEST['op_est']) ? $_REQUEST['op_est'] : 'T';

    // Construir el parámetro $Par_Sql[1] según el filtro seleccionado
    $Par_Sql1 = '';
    if ($search !== '') {
        switch ($op_opciones) {
            case 'codnegV':
                $Par_Sql1 = "AND nego_camaron.Num_Neg='$search'";
                break;
            case 'nom_cliV':
                $Par_Sql1 = "AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$search%'";
                break;
            case 'ced_cliV':
                $Par_Sql1 = "AND persona.Prs_Ced='$search'";
                break;
        }
    }

    $Par_Sql5 = '';
    if($estado == 'A'){
        $Par_Sql5 = " AND ventas.Vet_Est = 'A' ";
    } elseif ($estado == 'I'){
        $Par_Sql5 = " AND ventas.Vet_Est = 'I' ";

    }

    // Pasar los parámetros en el orden esperado por la consulta
    $param = array($Ses_Emp_Cod, $Par_Sql1, $Fec_Ini, $Fec_Fin, $filtroV, $Par_Sql5);
    $responce = $obBD_con1->getArrayConsulta(49, $param, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($ventasAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(16, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($pagoVentasAjax)) {
    $responce1 = $obBD_con1->getArrayConsulta(18, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce2 = $obBD_con1->getArrayConsulta(46, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $responce['response'] = array_merge($responce1, $responce2);
    $obBD_con1->echoJson($responce);
    exit();
}

// COMPRAS
if (isset($negociaciones_compras_Ajax)) {
    // Obtener los datos enviados por POST o GET
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $Fec_IniC = isset($_REQUEST['Fec_IniC']) ? $_REQUEST['Fec_IniC'] : '';
    $Fec_FinC = isset($_REQUEST['Fec_FinC']) ? $_REQUEST['Fec_FinC'] : '';
    $op_opciones = isset($_REQUEST['op_opciones']) ? $_REQUEST['op_opciones'] : 'codnegC';
    $estado = isset($_REQUEST['op_estC']) ? $_REQUEST['op_estC'] : 'T';
    
    // Construir el parámetro $Par_Sql[1] según el filtro seleccionado
    $Par_Sql1 = '';
    if ($search !== '') {
        switch ($op_opciones) {
            case 'codnegC':
                $Par_Sql1 = "AND nego_camaron.Num_Neg='$search'";
                break;
            case 'nom_prvC':
                $Par_Sql1 = "AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$search%'";
                break;
            case 'ced_rucC':
                $Par_Sql1 = "AND persona.Prs_Ced='$search'";
                break;
        }
    }

    $Par_Sql5 = '';
    if($estado == 'A'){
        $Par_Sql5 = " AND compras.Cop_Est = 'A' ";
    } elseif ($estado == 'I'){
        $Par_Sql5 = " AND compras_Cop_Est = 'I' ";
    }

    $param = array($Ses_Emp_Cod, $Par_Sql1, $Fec_IniC, $Fec_FinC, $filtroC, $Par_Sql5);
    $responce = $obBD_con1->getArrayConsulta(50, $param, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($comprasAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}

if (isset($pagoComprasAjax)) {
    $responce['response'] = $obBD_con1->getArrayConsulta(45, $Ses_Emp_Cod . '*' . $Cod_Neg, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
// ANTICIPOS PROVEEDORES
if (isset($negociaciones_AntiProv_Ajax)) {
    // Obtener los datos enviados por POST o GET
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $Fec_IniPr = isset($_REQUEST['Fec_IniPr']) ? $_REQUEST['Fec_IniPr'] : '';
    $Fec_FinPr = isset($_REQUEST['Fec_FinPr']) ? $_REQUEST['Fec_FinPr'] : '';
    $op_opciones = isset($_REQUEST['op_opciones']) ? $_REQUEST['op_opciones'] : 'nom_prv';
    // Construir el parámetro $Par_Sql[1] según el filtro seleccionado
    $Par_Sql1 = '';
    if ($search !== '') {
        switch ($op_opciones) {
            case 'nom_prv':
                $Par_Sql1 = "AND CONCAT(prs.Prs_Nom,' ',prs.Prs_Ape) LIKE '%$search%'";
                break;
            case 'prv_ced':
                $Par_Sql1 = "AND prs.Prs_Ced='$search'";
                break;
        }
    }
    $param = array($Ses_Emp_Cod, $Par_Sql1, $Fec_IniPr, $Fec_FinPr);
    $responce = $obBD_con1->getArrayConsulta(51, $param,  $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
// ANTICIPOS CLIENTES
if (isset($negociaciones_AntiCli_Ajax)) {
    // Obtener los datos enviados por POST o GET
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $Fec_IniCli = isset($_REQUEST['Fec_IniCli']) ? $_REQUEST['Fec_IniCli'] : '';
    $Fec_FinCli = isset($_REQUEST['Fec_FinCli']) ? $_REQUEST['Fec_FinCli'] : '';
    $op_opciones = isset($_REQUEST['op_opciones']) ? $_REQUEST['op_opciones'] : 'nom_cli';
    $Par_Sql1 = '';
    if ($search !== '') {
        switch ($op_opciones) {
            case 'nom_cli':
                $Par_Sql1 = "AND CONCAT(prs.Prs_Nom,' ',prs.Prs_Ape) LIKE '%$search%'";
                break;
            case 'cli_ced':
                $Par_Sql1 = "AND prs.Prs_Ced='$search'";
                break;
        }
    }
    $param = array($Ses_Emp_Cod, $Par_Sql1, $Fec_IniCli, $Fec_FinCli);
    $responce = $obBD_con1->getArrayConsulta(53, $param,  $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit();
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE><?Php echo "Negociación camaron [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <style>
        .ui-jqgrid .jqgrow td { white-space: normal !important; word-wrap: break-word; }
        @media print {
            #tablaReporte { width: 100%; font-size: 10pt; }
            #tablaReporte td, #tablaReporte th { word-wrap: break-word; white-space: normal; }
        }
        /* Mejorar visual de los tabs */
        .ui-tabs-nav li { margin-right: 5px !important; }
        .ui-tabs .ui-tabs-nav { padding: 0.2em 0.2em 0 0.2em; }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main" id="formFinal">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Datos Documentos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch" class="ui-tabs ui-tab-fix noPaddingH">
                <ul>
                    <li><a href="#tabs-1">Ventas</a></li>
                    <li><a href="#tabs-2">Compras</a></li>
                    <li><a href="#tabs-3">Anticipos Proveedores</a></li>
                    <li><a href="#tabs-4">Anticipos Clientes</a></li>
                </ul>
                <!-- Pestaña de Ventas -->
                <div id="tabs-1">
                    <div id="documentoSearch">
                        <div class="row">
                            <div class="col-xs-12">
                                <div id="tabsDatos" class="ui-tab-fix">
                                    <div class="panels-area form-horizontal normal ">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <fieldset class="exa-fieldset" id="prodFormTemp">
                                                        <legend class="Titulos2">B&uacute;squeda</legend>
                                                        <form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#containerVentas').Search('#frm_prod_ven','negociaciones_ventas_Ajax'); ">
                                                            <input name="order" type="hidden" value="" />
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                                                    <div class="col-sm-10 radioset opt_search" style="margin-bottom: 5px;display: inline-flex; align-items: center;">
                                                                        <input id="radsV1" name="op_opciones" type="radio" value="codnegV" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsV1">Cod.Neg</label>
                                                                        <input id="radsV2" name="op_opciones" type="radio" value="nom_cliV" onclick="setfocus(this.form.search)" alt="" /><label for="radsV2">Productor</label>
                                                                        <input id="radsV3" name="op_opciones" type="radio" value="ced_cliV" onclick="setfocus(this.form.search)" alt="" /><label for="radsV3">Ced/Ruc</label>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <label style="margin-left: 80px;">Estado:</label>&nbsp;
                                                                        <span class="radioset">
                                                                            <input id="op_est3" name="op_est" type="radio" value="T" style="cursor:pointer"><label for="op_est3"> Todas </label>
                                                                            <input id="op_est1" name="op_est" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_est1"> Activas </label>
                                                                            <input id="op_est2" name="op_est" type="radio" value="I" style="cursor:pointer"><label for="op_est2">Anuladas</label>
                                                                        </span>
                                                                    </div>
                                                                    <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                                                    <div class="col-sm-10">
                                                                        <div class="input-group">
                                                                            <input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                                            <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                                                                            <span class="input-group-btn">
                                                                                <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                                                    <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <input type="text" tabindex="-1" style="display:none;" />
                                                                </div>
                                                                <div class="col-md-6" style="display: flex; align-items: center;">
                                                                    <fieldset class="exa-fieldset" id="fechaFormTemp" style="width:100%; height: 55px;">
                                                                        <legend class="Titulos2" style="text-align:left;">Rango de Fechas</legend>
                                                                        <div class="col-sm-12" style="display: flex; justify-content: center;">
                                                                            <div class="input-group input-group-xs por_fecha" style="width: 80%; justify-content: center;">
                                                                                <span class="input-group-addon alert-info">Desde</span>
                                                                                <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" />
                                                                                <span class="input-group-addon alert-info">Hasta</span>
                                                                                <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" />
                                                                            </div>
                                                                        </div>
                                                                    </fieldset>
                                                                </div>
                                                                <input type="hidden" id="filtroV" name="filtroV" class="form-control"/>
                                                            </div>
                                                        </form>
                                                    </fieldset>
                                                </div>
                                                <!-- Grid de Ventas -->
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <table id="containerVentas"></table>
                                                        <div id="containerVentasPager"></div>
                                                    </div>
                                                </div>
                                                <!-- fin grid ventas -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pestaña de Compras -->
                <div id="tabs-2" class="ui-tabs-panel" style="display: none">
                    <div class="col-md-12">
                        <fieldset class="exa-fieldset" id="prodFormTemp">
                            <legend class="Titulos2">B&uacute;squeda</legend>
                            <form id="frm_prod_cop" name="frm_prod_cop" class="form-horizontal normal" action="javascript:$('#containerCompras').Search('#frm_prod_cop','negociaciones_compras_Ajax'); ">
                                <input name="order" type="hidden" value="" />
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-sm-10 radioset opt_search" style="margin-bottom: 5px; display: inline-flex; align-items: center;">
                                            <input id="radsC1" name="op_opciones" type="radio" value="codnegC" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsC1">Cod.Neg</label>
                                            <input id="radsC2" name="op_opciones" type="radio" value="nom_prvC" onclick="setfocus(this.form.search)" alt="" /><label for="radsC2">Productor</label>
                                            <input id="radsC3" name="op_opciones" type="radio" value="ced_rucC" onclick="setfocus(this.form.search)" alt="" /><label for="radsC3">Ced/Ruc</label>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <label style="margin-left: 80px;">Estado:</label>&nbsp;
                                            <span class="radioset">
                                                <input id="op_estC3" name="op_estC" type="radio" value="T" style="cursor:pointer"><label for="op_estC3"> Todas </label>
                                                <input id="op_estC1" name="op_estC" type="radio" value="A" checked='checked' style="cursor:pointer"><label for="op_estC1"> Activas </label>
                                                <input id="op_estC2" name="op_estC" type="radio" value="I" style="cursor:pointer"><label for="op_estC2">Anuladas</label>
                                            </span>
                                        </div>
                                        <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group">
                                                <input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                                                <span class="input-group-btn">
                                                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                        <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                    <div class="col-md-6" style="display: flex; align-items: center;">
                                        <fieldset class="exa-fieldset" id="fechaFormTemp" style="width:100%; height: 55px;">
                                            <legend class="Titulos2" style="text-align:left;">Rango de Fechas</legend>
                                            <div class="col-sm-12" style="display: flex; justify-content: center;">
                                                <div class="input-group input-group-xs por_fecha" style="width: 80%; justify-content: center;">
                                                    <span class="input-group-addon alert-info">Desde</span>
                                                    <input type="text" id="Fec_IniC" name="Fec_IniC" class="form-control" />
                                                    <span class="input-group-addon alert-info">Hasta</span>
                                                    <input type="text" id="Fec_FinC" name="Fec_FinC" class="form-control" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <input type="hidden" id="filtroC" name="filtroC" class="form-control"/>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <!-- Grid de Compras -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="containerCompras"></table>
                                <div id="containerComprasPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pestaña de Anticipos Proveedores -->
                <div id="tabs-3" class="ui-tabs-panel" style="display: none">
                    <div class="col-md-12">
                        <fieldset class="exa-fieldset" id="prodFormTemp">
                            <legend class="Titulos2">B&uacute;squeda</legend>
                            <form id="frm_ant_prov" name="frm_ant_prov" class="form-horizontal normal" action="javascript:$('#containerAntiPrv').Search('#frm_ant_prov','negociaciones_AntiProv_Ajax'); ">
                                <input name="order" type="hidden" value="" />
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-sm-10 radioset opt_search" style="margin-bottom: 5px;">
                                            <input id="radsAp1" name="op_opciones" type="radio" value="nom_prv" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsAp1">Productor</label>
                                            <input id="radsAp2" name="op_opciones" type="radio" value="prv_ced" onclick="setfocus(this.form.search)" alt="" /><label for="radsAp2">Ced/Ruc</label>
                                        </div>
                                        <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group">
                                                <input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                                                <span class="input-group-btn">
                                                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                        <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                    <div class="col-md-6" style="display: flex; align-items: center;">
                                        <fieldset class="exa-fieldset" id="fechaFormTemp" style="width:100%; height: 55px;">
                                            <legend class="Titulos2" style="text-align:left;">Rango de Fechas</legend>
                                            <div class="form-group">
                                                <label class="col-sm-1 control-label label-xs">Periodo:</label>
                                                <div class="col-sm-3">
                                                    <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                                                        <option data-year='2018' data-inicio='2018-01-01' data-fin='2030-12-31' value="T">
                                                            << Todos>>
                                                        </option>
                                                        <?php
                                                        foreach ($periodos as $p) {
                                                            echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-7" style="margin-left: 10px;">
                                                    <div class="input-group input-group-xs por_fecha" style="width: 100%; justify-content: center;">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_IniPr" name="Fec_IniPr" class="form-control" />
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_FinPr" name="Fec_FinPr" class="form-control" />
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <!-- Grid de Anticipos de Proveedores -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="containerAntiPrv"></table>
                                <div class="Titulos2">
                                    <span id="plan-footer">
                                        <strong>Leyenda:</strong>
                                        <span class="glyphicon glyphicon-stop green"></span> Anticipos Usados </span>
                                    <span class="glyphicon glyphicon-stop gray"></span> Anticipos Consumidos </span>
                                    <span class="glyphicon glyphicon-stop red"></span> Anticipos Anulados </span>
                                </div>
                                <div id="containerAntiPrvPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pestaña de Anticipos Clientes -->
                <div id="tabs-4" class="ui-tabs-panel" style="display: none">
                    <div class="col-md-12">
                        <fieldset class="exa-fieldset" id="prodFormTemp">
                            <legend class="Titulos2">B&uacute;squeda</legend>
                            <form id="frm_ant_cli" name="frm_ant_cli" class="form-horizontal normal" action="javascript:$('#containerAntiCli').Search('#frm_ant_cli','negociaciones_AntiCli_Ajax'); ">
                                <input name="order" type="hidden" value="" />
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                                        <div class="col-sm-10 radioset opt_search" style="margin-bottom: 5px;">
                                            <input id="radsAc1" name="op_opciones" type="radio" value="nom_cli" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsAc1">Productor</label>
                                            <input id="radsAc2" name="op_opciones" type="radio" value="cli_ced" onclick="setfocus(this.form.search)" alt="" /><label for="radsAc2">Ced/Ruc</label>
                                        </div>
                                        <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                                        <div class="col-sm-10">
                                            <div class="input-group">
                                                <input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                                                <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                                                <span class="input-group-btn">
                                                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
                                                        <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <input type="text" tabindex="-1" style="display:none;" />
                                    </div>
                                    <div class="col-md-6" style="display: flex; align-items: center;">
                                        <fieldset class="exa-fieldset" id="fechaFormTemp" style="width:100%; height: 55px;">
                                            <legend class="Titulos2" style="text-align:left;">Rango de Fechas</legend>
                                            <div class="form-group">
                                                <label class="col-sm-1 control-label label-xs">Periodo:</label>
                                                <div class="col-sm-3">
                                                    <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                                                        <option data-year='2018' data-inicio='2018-01-01' data-fin='2030-12-31' value="T">
                                                            << Todos>>
                                                        </option>
                                                        <?php
                                                        foreach ($periodos as $p) {
                                                            echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-7" style="margin-left: 10px;">
                                                    <div class="input-group input-group-xs por_fecha" style="width: 100%; justify-content: center;">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_IniCli" name="Fec_IniCli" class="form-control" />
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_FinCli" name="Fec_FinCli" class="form-control" />
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </form>
                        </fieldset>
                    </div>
                    <!-- Grid de Anticipos de Cliente -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div>
                                <table id="containerAntiCli"></table>
                                <div class="Titulos2">
                                    <span id="plan-footer">
                                        <strong>Leyenda:</strong>
                                        <span class="glyphicon glyphicon-stop green"></span> Anticipos Usados </span>
                                    <span class="glyphicon glyphicon-stop gray"></span> Anticipos Consumidos </span>
                                    <span class="glyphicon glyphicon-stop red"></span> Anticipos Anulados </span>
                                </div>
                                <div id="containerAntiCliPager"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--kk-->
                <div id="imprimir" style="display: none;">
                    <div style="width: 1030px;">
                        <div id="reportHeaderContainer">
                            <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE NEGOCIACIONES-VENTAS', '<span class="subtitle"></span>', $obBD_conexion) ?>
                        </div>
                        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
                        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
                    </div>
                </div>
                <div id="exportar" style="display: none;">
                    <div id="reportHeaderContainerExport">
                        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE NEGOCIACIONES-VENTAS', '<span class="subtitle"></span>', $obBD_conexion, false, 5) ?>
                    </div>
                    <table id="tablaExporta" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
                </div>
                <script>
                    // Cambia el título del reporte según el tab seleccionado
                    $(function() {
                        $("#documentoSearch").tabs();
                        // Definir los títulos por tab
                        var titulos = [
                            "REPORTE DE NEGOCIACIONES-VENTAS",
                            "REPORTE DE NEGOCIACIONES-COMPRAS",
                            "REPORTE DE NEGOCIACIONES-ANTICIPOS PROVEEDORES",
                            "REPORTE DE NEGOCIACIONES-ANTICIPOS CLIENTES"
                        ];
                        // Obtener el header base desde PHP y usar como plantilla
                        var headerHtmlBase = <?php
                                                $header = $obBD_con1->getReportHeader($Ses_Suc_Cod, 'TITULO_REPORTE', '<span class="subtitle"></span>', $obBD_conexion);
                                                echo json_encode($header);
                                                ?>;

                        $("#documentoSearch").on("tabsactivate", function(event, ui) {
                            var idx = ui.newTab.index();
                            var titulo = titulos[idx] || titulos[0];
                            // Reemplazar correctamente el marcador de título
                            var headerHtml = headerHtmlBase.replace(/TITULO_REPORTE/g, titulo);
                            $("#reportHeaderContainer").html(headerHtml);
                            $("#reportHeaderContainerExport").html(headerHtml);
                        });
                    });
                </script>
                <!--kk-->
            </div>
        </div>
    </div>
    <script>
        $(function() {
            // Inicializar los tabs con jQuery UI
            $("#documentoSearch").tabs();
        });
    </script>
    <script src="../VALIDACIONES/cam_val_documentos.js?x=7"></script>
    <!-- <script src="../../tesoreria/VALIDACIONES/tes_val_anticipo_mod_prv_2.0.js"></script> -->
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>