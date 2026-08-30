<?php

/**
 * @abstract Reporte de tickets por cliente: nombres, cédula, vehículo, total, cantidad, anticipos, saldo
 * @author Sistema
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_ticket.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_ticket($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_ticket;

// Sección para cargar datos del grid de clientes (diálogo de búsqueda)
if (isset($_GET['clienteAjax'])) {
    $data = filter_input_array(INPUT_GET);
    $data["Emp_Cod"] = $Ses_Emp_Cod;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 50;
    $contar = $obBD_con1->getRowConsulta(1, $data, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $responce = $pagination['data'];
    $data["limits"] = $pagination['limits'];
    if ($contar['total'] > 0) {
        $responce['rows'] = $obBD_con1->getArrayConsulta(1, $data, $obBD_conexion);
    }
    if (function_exists('utf8_encode_deep')) {
        utf8_encode_deep($responce);
    }
    echo json_encode($responce);
    exit();
}

// Respuesta AJAX para el grid (reporte por cliente)
if (isset($_GET['repTicketsAjax']) || isset($_POST['repTicketsAjax'])) {
    $filas = array();
    $Emp_Cod = isset($Ses_Emp_Cod) ? (int) $Ses_Emp_Cod : 0;
    $Cli_Cod = isset($_GET['Cli_Cod']) ? $_GET['Cli_Cod'] : (isset($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : '');
    $search = isset($_GET['search']) ? trim($_GET['search']) : (isset($_POST['search']) ? trim($_POST['search']) : '');
    $params = array('Emp_Cod' => $Emp_Cod);
    if ($Cli_Cod !== '' && (int)$Cli_Cod > 0) {
        $params['Cli_Cod'] = (int) $Cli_Cod;
    }
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($Emp_Cod > 0) {
        try {
            $filas = $obBD_con1->getArrayConsulta(51, $params, $obBD_conexion);
            if (!is_array($filas)) {
                $filas = array();
            }
        } catch (Exception $e) {
            try {
                $filas = $obBD_con1->getArrayConsulta(52, $params, $obBD_conexion);
                if (!is_array($filas)) {
                    $filas = array();
                }
            } catch (Exception $e2) {
                $filas = array();
            }
        }
        if (!empty($filas) && function_exists('utf8_encode_deep')) {
            utf8_encode_deep($filas);
        }
    }
    $total = count($filas);
    $responce = array(
        'page' => 1,
        'total' => $total > 0 ? 1 : 0,
        'records' => $total,
        'rows' => $filas
    );
    echo json_encode($responce);
    exit();
}

// Respuesta AJAX para el grid de ventas (reporte por fecha / forma de pago)
if (isset($_GET['repVentasAjax']) || isset($_POST['repVentasAjax'])) {
    $filas = array();
    $Emp_Cod = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
    $Fec_Ini = isset($_GET['Fec_Ini']) ? $_GET['Fec_Ini'] : (isset($_POST['Fec_Ini']) ? $_POST['Fec_Ini'] : '');
    $Fec_Fin = isset($_GET['Fec_Fin']) ? $_GET['Fec_Fin'] : (isset($_POST['Fec_Fin']) ? $_POST['Fec_Fin'] : '');
    $Agrupar = isset($_GET['Agrupar']) ? $_GET['Agrupar'] : (isset($_POST['Agrupar']) ? $_POST['Agrupar'] : '0');
    $Cli_Cod = isset($_GET['Cli_Cod']) ? $_GET['Cli_Cod'] : (isset($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : '');

    $params = array('Emp_Cod' => $Emp_Cod);
    if ($Fec_Ini !== '') {
        $params['Fec_Ini'] = $Fec_Ini;
    }
    if ($Fec_Fin !== '') {
        $params['Fec_Fin'] = $Fec_Fin;
    }
    if ($Agrupar === '1' || $Agrupar === 1) {
        $params['Agrupar'] = 1;
    }
    if ($Cli_Cod !== '' && (int)$Cli_Cod > 0) {
        $params['Cli_Cod'] = (int)$Cli_Cod;
    }

    if ($Emp_Cod > 0) {
        try {
            // Consulta 54: debe devolver tickets con campos: Tck_Fec, mes, Tck_Num, cliente_nombre, Veh_Pla,
            // Veh_Tip_Desc, Tck_Pag, Tck_Tot
            $filas = $obBD_con1->getArrayConsulta(54, $params, $obBD_conexion);
            if (!is_array($filas)) {
                $filas = array();
            }
        } catch (Exception $e) {
            $filas = array();
        }
        if (!empty($filas) && function_exists('utf8_encode_deep')) {
            utf8_encode_deep($filas);
        }
    }

    // Calcular totales por forma de pago:
    // - Efectivo  => Tck_Pag = 'E'
    // - Crédito   => Tck_Pag = 'C'
    // - Firma     => Tck_Pag = 'F' (usado como anticipo según tu requerimiento)
    $total_efectivo = 0;
    $total_credito = 0;
    $total_firma = 0;
    foreach ($filas as $row) {
        $monto = isset($row['Tck_Tot']) ? (float)$row['Tck_Tot'] : 0;
        $forma = isset($row['Tck_Pag']) ? strtoupper(trim($row['Tck_Pag'])) : 'E';
        if ($forma === 'C') {
            $total_credito += $monto;
        } elseif ($forma === 'F') {
            $total_firma += $monto;
        } else { // 'E' o cualquier valor inesperado
            $total_efectivo += $monto;
        }
    }

    // Paginación manual para jqGrid (por defecto 500 filas)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : (isset($_POST['page']) ? (int)$_POST['page'] : 1);
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : (isset($_POST['rows']) ? (int)$_POST['rows'] : 500);
    if ($page < 1) {
        $page = 1;
    }
    if ($rows < 1) {
        $rows = 500;
    }
    $total_records = count($filas);
    $total_pages = $total_records > 0 ? ceil($total_records / $rows) : 0;
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }
    $start = ($page - 1) * $rows;
    $filas_paged = array_slice($filas, $start, $rows);

    $responce = array(
        'page' => $page,
        'total' => $total_pages,
        'records' => $total_records,
        'rows' => $filas_paged,
        'total_efectivo' => $total_efectivo,
        'total_credito' => $total_credito,
        'total_firma' => $total_firma
    );

    echo json_encode($responce);
    exit();
}

// SubGrid: detalle de vehículos del chofer (tickets y consumo por vehículo)
if (isset($_GET['repTicketsVehiculosAjax']) || isset($_POST['repTicketsVehiculosAjax'])) {
    $filas = array();
    $Emp_Cod = isset($Ses_Emp_Cod) ? (int) $Ses_Emp_Cod : 0;
    $Cli_Cod = isset($_GET['Cli_Cod']) ? $_GET['Cli_Cod'] : (isset($_POST['Cli_Cod']) ? $_POST['Cli_Cod'] : '');
    if ($Emp_Cod > 0 && $Cli_Cod !== '' && (int)$Cli_Cod > 0) {
        $params = array('Emp_Cod' => $Emp_Cod, 'Cli_Cod' => (int) $Cli_Cod);
        try {
            $filas = $obBD_con1->getArrayConsulta(53, $params, $obBD_conexion);
            if (!is_array($filas)) {
                $filas = array();
            }
        } catch (Exception $e) {
            $filas = array();
        }
        if (!empty($filas) && function_exists('utf8_encode_deep')) {
            utf8_encode_deep($filas);
        }
    }
    $total = count($filas);
    $responce = array(
        'page' => 1,
        'total' => $total > 0 ? 1 : 0,
        'records' => $total,
        'rows' => $filas
    );
    echo json_encode($responce);
    exit();
}

$Emp_Cod = isset($Ses_Emp_Cod) ? (int) $Ses_Emp_Cod : 0;
$fec_ini_mes_actual = date('Y-m-01');
$fec_fin_mes_actual = date('Y-m-d');
?>
<!DOCTYPE html>
<meta charset="UTF-8">
<html>
<head>
    <title><?php echo $Ses_Sys_Nom; ?> - Reporte Tickets por Cliente</title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <style>
        .panel-rep { margin: 15px 0; }
    </style>
</head>
<body>
    <div class="panel panel-main panel-rep">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Reportes de Tickets</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="active"><a href="#tabClientes" role="tab" data-toggle="tab">Por Cliente</a></li>
                <li><a href="#tabVentas" role="tab" data-toggle="tab">Ventas</a></li>
            </ul>
            <div class="tab-content" style="margin-top:10px;">
                <div class="tab-pane active" id="tabClientes">
            <div id="bus_rep" class="row">
                <form id="frm_rep" name="frm_rep" class="form-horizontal normal" action="javascript:$('#Lis_Rep_Ticket').Search('#frm_rep','repTicketsAjax');">
                    <input type="hidden" name="repTicketsAjax" value="1">
                    <input type="hidden" name="Emp_Cod" id="Emp_Cod" value="<?php echo $Emp_Cod; ?>">
                    <input type="hidden" name="Cli_Cod" id="Cli_Cod" value="">
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Búsqueda</legend>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Cliente:</label>
                                <div class="col-md-7 col-sm-7">
                                    <div class="input-group">
                                        <input type="text" id="Cli_Des" name="Cli_Des" class="form-control input-xs" placeholder="Seleccione cliente..." readonly="">
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                            <button class="btn btn-default btn-xs" type="button" title="Limpiar cliente" onclick="$('#Cli_Cod').val(''); $('#Cli_Des').val('');"><span class="glyphicon glyphicon-remove"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-4 label-sm">Placa:</label>
                                <div class="col-md-7 col-sm-7">
                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Placa del vehículo..." onkeydown="if (event.keyCode === 13) this.form.submit()">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-md-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-7">
                                    <button class="btn btn-success btn-xs" type="button" title="Buscar" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </form>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table id="Lis_Rep_Ticket"></table>
                    <div id="Pag_Rep"></div>
                </div>
            </div>
                </div>
                <div class="tab-pane" id="tabVentas">
                    <div id="bus_rep_ventas" class="row">
                        <form id="frm_rep_ventas" name="frm_rep_ventas" class="form-horizontal normal" action="javascript:$('#Lis_Rep_Ventas').Search('#frm_rep_ventas','repVentasAjax');">
                            <input type="hidden" name="repVentasAjax" value="1">
                            <input type="hidden" name="Emp_Cod" id="Emp_Cod_Ventas" value="<?php echo $Emp_Cod; ?>">
                            <input type="hidden" name="Cli_Cod" id="Cli_Cod_Ventas" value="">
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros de Fecha</legend>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Cliente:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <div class="input-group">
                                                <input type="text" id="Cli_Des_Ventas" name="Cli_Des_Ventas" class="form-control input-xs" placeholder="Seleccione cliente..." readonly="">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="$('#clienteDialogVentas').dialog('open');"><span class="glyphicon glyphicon-search"></span></button>
                                                    <button class="btn btn-default btn-xs" type="button" title="Limpiar cliente" onclick="$('#Cli_Cod_Ventas').val(''); $('#Cli_Des_Ventas').val('');"><span class="glyphicon glyphicon-remove"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Desde:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <input type="date" id="Fec_Ini" name="Fec_Ini" class="form-control input-xs">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-4 label-sm">Hasta:</label>
                                        <div class="col-md-7 col-sm-7">
                                            <input type="date" id="Fec_Fin" name="Fec_Fin" class="form-control input-xs">
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-6">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Totales</legend>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-5 label-sm">Tickets Efectivo:</label>
                                        <div class="col-md-6 col-sm-7">
                                            <span id="lblTotalEfectivo" class="form-control input-xs" style="border:none; background-color:#f5f5f5;">0.0000</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-5 label-sm">Tickets Crédito:</label>
                                        <div class="col-md-6 col-sm-7">
                                            <span id="lblTotalCredito" class="form-control input-xs" style="border:none; background-color:#f5f5f5;">0.0000</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="control-label col-md-4 col-sm-5 label-sm">Tickets Firma (Anticipo):</label>
                                        <div class="col-md-6 col-sm-7">
                                            <span id="lblTotalFirma" class="form-control input-xs" style="border:none; background-color:#f5f5f5;">0.0000</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-offset-3 col-md-7 col-sm-offset-4 col-sm-7">
                                            <div class="checkbox" style="margin-top:0;">
                                                <label>
                                                    <input type="checkbox" id="Agrupar" name="Agrupar" value="1">
                                                    Agrupar tickets por placa y día
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-offset-4 col-md-6">
                                            <button class="btn btn-success btn-xs" type="button" title="Buscar" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </form>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="Lis_Rep_Ventas"></table>
                            <div id="Pag_Rep_Ventas"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="imprimir" style="display: none;">
        <div style="width: 1030px;">
            <div class="report-header" style="text-align:center; margin-bottom:15px;">
                <h4><?php echo $Ses_Sys_Nom; ?> - REPORTE DE TICKETS POR CLIENTE</h4>
                <span class="subtitle">Total de registros</span>
            </div>
            <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; table-layout: auto; font-size: 12px;"></table>
        </div>
    </div>

    <!-- Diálogo para buscar cliente -->
    <div id="clienteDialog" style="display:none;"></div>
    <div id="clienteDialogVentas" style="display:none;"></div>

    <script type="text/javascript">
        function seleccionarClienteReporte(rowObject) {
            $('#Cli_Cod').val(rowObject.Cli_Cod || '');
            $('#Cli_Des').val(rowObject.cliente || (rowObject.Prs_Ced || ''));
            $('#clienteDialog').dialog('close');
            $('#Lis_Rep_Ticket').Search('#frm_rep', 'repTicketsAjax');
        }
        function limpiarClienteFiltro() {
            $('#Cli_Cod').val('');
            $('#Cli_Des').val('');
            $('#Lis_Rep_Ticket').Search('#frm_rep', 'repTicketsAjax');
        }
        function seleccionarClienteVentas(rowObject) {
            $('#Cli_Cod_Ventas').val(rowObject.Cli_Cod || '');
            $('#Cli_Des_Ventas').val(rowObject.cliente || (rowObject.Prs_Ced || ''));
            $('#clienteDialogVentas').dialog('close');
            $('#Lis_Rep_Ventas').Search('#frm_rep_ventas', 'repVentasAjax');
        }
        $(function () {
            // Valores por defecto: mes actual (Ventas)
            if (!$('#Fec_Ini').val()) {
                $('#Fec_Ini').val('<?php echo $fec_ini_mes_actual; ?>');
            }
            if (!$('#Fec_Fin').val()) {
                $('#Fec_Fin').val('<?php echo $fec_fin_mes_actual; ?>');
            }

            // Diálogo para buscar cliente (filtrar reporte)
            $.createSearchDialog('#clienteDialog', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, hidden: true },
                { label: 'C&eacute;dula', name: 'Prs_Ced', width: 30 },
                { label: 'Cliente', name: 'cliente', width: 70 },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
                    formatter: function (cellvalue, options, rowObject) {
                        return $.getGridButton(seleccionarClienteReporte, rowObject);
                    }
                }
            ], null, null, null, null, {
                title: 'Buscar Cliente',
                options: [{ label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c' }]
            });
            $('#clienteDialog').on('dialogopen', function () {
                setTimeout(function () { $.Search('cliente'); }, 200);
            });

            // Diálogo para buscar cliente (ventas)
            $.createSearchDialog('#clienteDialogVentas', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, hidden: true },
                { label: 'C&eacute;dula', name: 'Prs_Ced', width: 30 },
                { label: 'Cliente', name: 'cliente', width: 70 },
                {
                    label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act', width: 18, align: 'center', viewable: false,
                    formatter: function (cellvalue, options, rowObject) {
                        return $.getGridButton(seleccionarClienteVentas, rowObject);
                    }
                }
            ], null, null, null, null, {
                title: 'Buscar Cliente',
                options: [{ label: '&nbsp;&nbsp;Nombre&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c' }]
            });
            $('#clienteDialogVentas').on('dialogopen', function () {
                setTimeout(function () { $.Search('cliente'); }, 200);
            });

            $("#Lis_Rep_Ticket").createGrid({
                postData: $("#frm_rep").getData("repTicketsAjax"),
                height: 295,
                colModel: [
                    {
                        label: 'Cli_Cod',
                        name: 'Cli_Cod',
                        key: true,
                        hidden: true
                    },
                    {
                        label: 'Nombres',
                        name: 'cliente_nombre',
                        width: 200,
                        align: "left"
                    },
                    {
                        label: 'Cédula / RUC',
                        name: 'Prs_Ced',
                        width: 100,
                        align: "center"
                    },
                    {
                        label: 'Cant. tickets',
                        name: 'cantidad_tickets',
                        width: 80,
                        align: "center"
                    },
                    {
                        label: 'Total en tickets',
                        name: 'total_tickets',
                        width: 110,
                        align: "right",
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 4
                        }
                    },
                    {
                        label: 'Val.Anticipos',
                        name: 'saldo_anticipo',
                        width: 100,
                        align: "right",
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 4
                        }
                    },
                    
                    {
                        label: 'Saldo Anticipos',
                        name: 'saldo',
                        width: 100,
                        align: "right",
                        formatter: function (cellvalue) {
                            var n = parseFloat(cellvalue || 0);
                            var s = (n).toFixed(4);
                            return n < 0 ? '<span class="text-danger">' + s + '</span>' : s;
                        }
                    }
                ],
                footerrow: true,
                subGrid: true,
                subGridOptions: {
                    "plusicon": "ui-icon-triangle-1-e",
                    "minusicon": "ui-icon-triangle-1-s",
                    "openicon": "ui-icon-arrowreturn-1-e",
                    "reloadOnExpand": true,
                    "selectOnExpand": false
                },
                subGridRowExpanded: function (subgrid_id, row_id) {
                    var subgrid_table_id = subgrid_id + "_t";
                    $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                    var subgridUrl = "?repTicketsVehiculosAjax=1&Cli_Cod=" + encodeURIComponent(row_id);
                    $("#" + subgrid_table_id).jqGrid({
                        url: subgridUrl,
                        datatype: "json",
                        regional: 'es',
                        autowidth: true,
                        shrinkToFit: true,
                        cmTemplate: { sortable: false },
                        colModel: [
                            { label: 'Vehículo', name: 'Veh_Pla', width: 120, align: 'left' },
                            { label: 'Cant. tickets', name: 'cantidad_tickets', width: 90, align: 'center' },
                            { label: 'Consumo', name: 'total_tickets', width: 100, align: 'right',
                                formatter: 'number',
                                formatoptions: { decimalPlaces: 4 }
                            }
                        ],
                        rowNum: 10000,
                        pager: "",
                        height: '100%'
                    });
                },
                loadComplete: function (data) {
                    if ($.varValid(data.rows) && data.rows.length > 0) {
                        var sumTotal = 0, sumCant = 0, sumAnticipo = 0, sumSaldo = 0;
                        for (var i = 0, z = data.rows.length; i < z; i++) {
                            sumTotal += parseFloat(data.rows[i]['total_tickets'] || 0);
                            sumCant += parseFloat(data.rows[i]['cantidad_tickets'] || 0);
                            sumAnticipo += parseFloat(data.rows[i]['saldo_anticipo'] || 0);
                            sumSaldo += parseFloat(data.rows[i]['saldo'] || 0);
                        }
                        $('#Lis_Rep_Ticket').jqGrid('footerData', 'set', {
                            cliente_nombre: 'TOTALES:',
                            total_tickets: sumTotal.toFixed(4),
                            cantidad_tickets: sumCant,
                            saldo_anticipo: sumAnticipo.toFixed(4),
                            saldo: sumSaldo.toFixed(4)
                        });
                    }
                }
            }, false, "#Pag_Rep", { view: false, refresh: true }).gridButtonsAdd([
                {
                    caption: 'Exportar Excel',
                    buttonicon: 'glyphicon glyphicon-download',
                    onClickButton: function () {
                        $("#Lis_Rep_Ticket").jqGrid('exportGridExcel', {
                            nombre: 'Reporte-Tickets-Cliente',
                            hoja: 'HOJA 1',
                            footer: true
                        });
                    }
                },
                {
                    caption: 'Imprimir PDF',
                    buttonicon: 'glyphicon glyphicon-print',
                    onClickButton: function () {
                        imprimir();
                    }
                }
            ]);

            // Grid de Ventas
            $("#Lis_Rep_Ventas").createGrid({
                postData: $("#frm_rep_ventas").getData("repVentasAjax"),
                height: 295,
                rowNum: 500,
                colModel: [
                    { label: 'Fecha', name: 'Tck_Fec', width: 90, align: "center" },
                    { label: 'Mes', name: 'mes', width: 80, align: "center" },
                    {
                        label: 'Documento',
                        name: 'documento',
                        width: 80,
                        align: "center",
                        formatter: function () { return 'Ticket'; }
                    },
                    { label: 'Cliente', name: 'cliente_nombre', width: 200, align: "left" },
                    { label: 'Placa', name: 'Veh_Pla', width: 90, align: "center" },
                    { label: 'CANT.VOLQ.', name: 'Cant_Volq', width: 90, align: "center" },
                    { label: 'Val.Unitario', name: 'Val_Uni', width: 80, align: "right", formatter: 'number', formatoptions: { decimalPlaces: 4 } },
                    { label: 'Des.Volqueta', name: 'Veh_Tip_Desc', width: 100, align: "left" },
                    {
                        label: 'Forma de Pago',
                        name: 'Tck_Pag',
                        width: 90,
                        align: "center",
                        formatter: function (cellvalue) {
                            var v = (cellvalue || '').toString().toUpperCase();
                            if (v === 'C') return 'Crédito';
                            if (v === 'F') return 'Firma';
                            return 'Efectivo';
                        }
                    },
                    {
                        label: 'Valor Pagar',
                        name: 'Tck_Tot',
                        width: 80,
                        align: "right",
                        formatter: 'number',
                        formatoptions: { decimalPlaces: 4 }
                    }
                ],
                footerrow: true,
                loadComplete: function (data) {
                    var sumTotal = 0;
                    var sumCantVolq = 0;
                    if ($.varValid(data.rows) && data.rows.length > 0) {
                        for (var i = 0, z = data.rows.length; i < z; i++) {
                            sumTotal += parseFloat(data.rows[i]['Tck_Tot'] || 0);
                            sumCantVolq += parseFloat(data.rows[i]['Cant_Volq'] || 0);
                        }
                    }
                    $('#Lis_Rep_Ventas').jqGrid('footerData', 'set', {
                        cliente_nombre: 'TOTAL:',
                        Cant_Volq: sumCantVolq,
                        Tck_Tot: sumTotal.toFixed(4)
                    });

                    var totalEfe = parseFloat(data.total_efectivo || 0).toFixed(4);
                    var totalCre = parseFloat(data.total_credito || 0).toFixed(4);
                    var totalFir = parseFloat(data.total_firma || 0).toFixed(4);
                    $('#lblTotalEfectivo').text(totalEfe);
                    $('#lblTotalCredito').text(totalCre);
                    $('#lblTotalFirma').text(totalFir);
                }
            }, false, "#Pag_Rep_Ventas", { view: false, refresh: true }).gridButtonsAdd([
                {
                    caption: 'Exportar Excel',
                    buttonicon: 'glyphicon glyphicon-download',
                    onClickButton: function () {
                        $("#Lis_Rep_Ventas").jqGrid('exportGridExcel', {
                            nombre: 'Reporte-Ventas-Tickets',
                            hoja: 'VENTAS',
                            footer: true
                        });
                    }
                }
            ]);

            // Cargar ventas automáticamente al abrir el tab por primera vez
            $('a[href="#tabVentas"]').one('shown.bs.tab', function () {
                $('#Lis_Rep_Ventas').Search('#frm_rep_ventas', 'repVentasAjax');
            });

            function imprimir() {
                var grid = $("#Lis_Rep_Ticket");
                $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', {
                    footer: true,
                    generated: false,
                    removeHiddens: true,
                    removeCols: [0]
                }));
                $('#imprimir').printElement();
            }
        });
    </script>
</body>
</html>
