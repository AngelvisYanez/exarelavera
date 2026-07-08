<?php

/**
 * Consulta de facturas con sus manifiestos y cantidad de manifiestos por factura
 * @author Wilson Belduma
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_manifiesto;

/* Obtener planta asignada al usuario si existe */
$pla_asignada = $obBD_con1->getArrayConsulta(75, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);
$Pla_Cod_Asignada = (is_array($pla_asignada) && count($pla_asignada) > 0) ? intval($pla_asignada[0]['Pla_Cod']) : 0;

/* Listado de plantas con cliente para modal (búsqueda por cédula y nombre) - formato simple para carga local */
if (isset($_GET['listadoPlantasModal'])) {
    $data = array(
        'Emp_Cod' => $Ses_Emp_Cod,
        'search_planta' => isset($_GET['search_planta']) ? $_GET['search_planta'] : '',
        'search_cedula' => isset($_GET['search_cedula']) ? $_GET['search_cedula'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(76, $data, $obBD_conexion);
    if (!is_array($rows)) {
        $rows = array();
    }
    $obBD_con1->echoJson(array('rows' => $rows));
    exit;
}

/* Grid paginado de plantas para diálogo de búsqueda (mismo patrón que clieAjax en fac_alt_fac_ven) */
if (isset($_REQUEST['plaAjax'])) {
    $data = array_merge($_GET, $_POST);
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getPageGrid(76, $data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit;
}

/* Grid de facturas con cantidad de manifiestos */
if (isset($_GET['gridFacturasManifiestos'])) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    
    // Si el usuario tiene una planta asignada, forzamos ese filtro
    if ($Pla_Cod_Asignada > 0) {
        $data['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
    } else if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $data['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
    }
    
    $responce = $obBD_con1->getPageGrid(73, $data, $obBD_conexion);
    $obBD_con1->echoJson($responce);
    exit;
}

/* Reporte imprimible: facturas con planta, cliente, cédula, fecha, total, cant. manifiestos */
if (isset($_GET['reporte'])) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    
    // Si el usuario tiene una planta asignada, forzamos ese filtro
    if ($Pla_Cod_Asignada > 0) {
        $data['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
    } else if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $data['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
    }
    
    $data['rows'] = 10000;
    $data['page'] = 1;
    $data['limits'] = 'LIMIT 10000';
    $responce = $obBD_con1->getPageGrid(73, $data, $obBD_conexion);
    $rows = isset($responce['rows']) ? $responce['rows'] : array();
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte Facturas y Manifiestos</title>';
    echo '<style type="text/css">
        body { font-family: Arial, sans-serif; margin: 15px; }
        h2 { margin-bottom: 10px; }
        table.reporte { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table.reporte th, table.reporte td { border: 1px solid #333; padding: 6px 8px; text-align: left; font-size: 12px; }
        table.reporte th { background: #eee; }
        table.reporte td.numeric { text-align: right; }
        .no-print { margin-bottom: 10px; }
        @media print { .no-print { display: none; } }
    </style></head><body>';
    echo '<div class="no-print"><button type="button" onclick="window.print()">Imprimir</button> <button type="button" onclick="window.close()">Cerrar</button></div>';
    echo '<h2>Reporte: Facturas con Manifiestos</h2>';
    echo '<table class="reporte"><thead><tr>';
    echo '<th>Nº Factura</th><th>Planta</th><th>Cliente</th><th>Cédula/RUC</th><th>Fecha</th><th>Total factura</th><th>Cant. Manifiestos</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $num = htmlspecialchars(isset($r['Vet_Num_Completo']) ? $r['Vet_Num_Completo'] : '');
        $pla = htmlspecialchars(isset($r['Pla_Nom']) && $r['Pla_Nom'] !== '' ? $r['Pla_Nom'] : (isset($r['pla_nom']) ? $r['pla_nom'] : ''));
        $cli = htmlspecialchars(isset($r['cliente']) ? $r['cliente'] : '');
        $ced = htmlspecialchars(isset($r['Prs_Ced']) ? $r['Prs_Ced'] : '');
        $fec = htmlspecialchars(isset($r['Vet_Fec']) ? $r['Vet_Fec'] : '');
        $tot = isset($r['total_factura']) ? number_format((float)$r['total_factura'], 2) : '0.00';
        $cant = isset($r['cant_manifiestos']) ? (int)$r['cant_manifiestos'] : 0;
        echo "<tr><td>{$num}</td><td>{$pla}</td><td>{$cli}</td><td>{$ced}</td><td>{$fec}</td><td class=\"numeric\">{$tot}</td><td class=\"numeric\">{$cant}</td></tr>";
    }
    echo '</tbody></table></body></html>';
    exit;
}

/* Exportación a Excel (formato HTML para mejor compatibilidad con cuadrícula) */
if (isset($_GET['excel'])) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    
    if ($Pla_Cod_Asignada > 0) {
        $data['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
    } else if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $data['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
    }
    
    $data['rows'] = 10000;
    $data['page'] = 1;
    $data['limits'] = 'LIMIT 10000';
    $responce = $obBD_con1->getPageGrid(73, $data, $obBD_conexion);
    $rows = isset($responce['rows']) ? $responce['rows'] : array();
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=Facturas_Manifiestos_' . date('Ymd_His') . '.xls');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /></head><body>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th style="background-color: #eee;">Nº Factura</th>';
    echo '<th style="background-color: #eee;">Planta</th>';
    echo '<th style="background-color: #eee;">Cliente</th>';
    echo '<th style="background-color: #eee;">Cédula/RUC</th>';
    echo '<th style="background-color: #eee;">Fecha</th>';
    echo '<th style="background-color: #eee;">Subtotal</th>';
    echo '<th style="background-color: #eee;">IVA</th>';
    echo '<th style="background-color: #eee;">Total Factura</th>';
    echo '<th style="background-color: #eee;">Cant. Manifiestos</th>';
    echo '</tr>';
    
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . (isset($r['Vet_Num_Completo']) ? $r['Vet_Num_Completo'] : '') . '</td>';
        echo '<td>' . (isset($r['Pla_Nom']) && $r['Pla_Nom'] !== '' ? $r['Pla_Nom'] : (isset($r['pla_nom']) ? $r['pla_nom'] : '')) . '</td>';
        echo '<td>' . (isset($r['cliente']) ? $r['cliente'] : '') . '</td>';
        echo '<td>' . (isset($r['Prs_Ced']) ? $r['Prs_Ced'] : '') . '</td>';
        echo '<td>' . (isset($r['Vet_Fec']) ? $r['Vet_Fec'] : '') . '</td>';
        echo '<td>' . (isset($r['subtotal_factura']) ? number_format((float)$r['subtotal_factura'], 2, '.', '') : '0.00') . '</td>';
        echo '<td>' . (isset($r['iva_factura']) ? number_format((float)$r['iva_factura'], 2, '.', '') : '0.00') . '</td>';
        echo '<td>' . (isset($r['total_factura']) ? number_format((float)$r['total_factura'], 2, '.', '') : '0.00') . '</td>';
        echo '<td>' . (isset($r['cant_manifiestos']) ? (int)$r['cant_manifiestos'] : 0) . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

/* Manifiestos de una factura (por Vet_Cod) */
if (isset($_GET['manifiestosFactura'])) {
    $Vet_Cod = isset($_GET['Vet_Cod']) ? intval($_GET['Vet_Cod']) : 0;
    if ($Vet_Cod <= 0) {
        $obBD_con1->echoJson(array('rows' => array()));
        exit;
    }
    $params = array('Vet_Cod' => $Vet_Cod);
    
    // Si el usuario tiene una planta asignada, forzamos ese filtro
    if ($Pla_Cod_Asignada > 0) {
        $params['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
    } else if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $params['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
    }
    
    $rows = $obBD_con1->getArrayConsulta(74, $params, $obBD_conexion);
    if (!is_array($rows)) {
        $rows = array();
    }
    $obBD_con1->echoJson(array('rows' => $rows));
    exit;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Facturas y Manifiestos</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <style>
        /*  #gridFacturas { width: 100% !important; height: 600px !important; }*/
    </style>
</head>

<body>
    <input type="hidden" id="Emp_Cod" name="Emp_Cod" value="<?php echo $Ses_Emp_Cod; ?>">
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Facturas con sus Manifiestos</h3>
        </div>
        <div class="panel-body">
            <form id="formBusqueda" class="form-horizontal">
                <input type="hidden" id="Pla_Cod_Usuario" name="Pla_Cod_Usuario" value="">
                <div class="row">
                    <div class="col-xs-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Búsqueda</legend>
                            <div class="form-group">
                                <label class="col-xs-1 control-label">Planta:</label>
                                <div class="col-xs-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="inputNombrePlanta" class="form-control" placeholder="Nombre de planta" />
                                        <span class="input-group-btn">
                                            <button type="button" id="btnAbrirModalPlanta" class="btn btn-default" title="Buscar planta"><span class="glyphicon glyphicon-search"></span></button>
                                        </span>
                                    </div>
                                    <a href="#" id="btnQuitarPlanta" class="small" style="display:none; margin-left: 5px;">Quitar filtro</a>
                                    <input type="text" id="inputCedulaPlanta" class="form-control input-sm" placeholder="Cédula/RUC (al seleccionar planta)" readonly style="margin-top: 6px; background-color: #f9f9f9;" />
                                </div>
                                <label class="col-xs-1 control-label">Nº Factura:</label>
                                <div class="col-xs-2">
                                    <input type="text" name="Num_Factura" id="Num_Factura" class="form-control input-sm" placeholder="Ej: 001-001-000000123" maxlength="20" />
                                    <div style="margin-top: 6px;">
                                        <button type="button" id="btnBuscar" class="btn btn-success btn-sm btn-block"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </div>
                                </div>
                                <label class="col-xs-1 control-label">Fec. desde:</label>
                                <div class="col-xs-2">
                                    <input type="date" name="Fec_Ini" id="Fec_Ini" class="form-control input-sm" />
                                </div>
                                <label class="col-xs-1 control-label">Fec. hasta:</label>
                                <div class="col-xs-2">
                                    <input type="date" name="Fec_Fin" id="Fec_Fin" class="form-control input-sm" />
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 5px;">
                                <div class="col-xs-12">
                                    <button type="button" id="btnImprimirReporte" class="btn btn-primary btn-sm pull-right" title="Abre el reporte en nueva ventana para imprimir"><span class="glyphicon glyphicon-print"></span> Imprimir reporte</button>
                                    <button type="button" id="btnExportarExcel" class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" title="Exportar todas las facturas filtradas a Excel"><span class="glyphicon glyphicon-export"></span> Exportar Excel</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </form>
            <div class="grid-facturas-wrapper">
                <table id="gridFacturas"></table>
                <div id="gridFacturasPager"></div>
            </div>
        </div>
    </div>

    <!-- Diálogo de búsqueda de planta (mismo patrón que clieDialog en fac_alt_fac_ven_3.2.php) -->
    <div id="plaDialog" title="B&uacute;squeda de Planta">
        <form class="form-horizontal normal"></form>
    </div>

    <!-- Modal manifiestos de la factura -->
    <div class="modal fade" id="modalManifiestos" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Manifiestos de la factura: <span id="modalFacturaNum" style="font-weight: bold;"></span></h4>

                </div>
                <div class="modal-body">
                    <div style="margin-top: 10px; margin-bottom: 10px;" class="row">
                        <div style="font-size: 12px;" class="col-md-8">
                            <span> <strong>CLIENTE:</strong> <span id="modalClienteNombre" style=" margin-right: 20px;"></span></span>
                            <br> <span> <strong>PLANTA:</strong> <span id="modalPlantaNombre" style="margin-right: 20px;"></span></span>
                            <br> <span> <strong>FECHA:</strong> <span id="modalFacturaFecha" style=" margin-right: 20px;"></span></span>
                            <span> <strong>CANT. MANIFIESTOS:</strong> <span id="modalCantManifiestos" style="font-weight: bold;"></span></span>
                        </div>
                        <div class="col-md-4 text-right">
                            <button type="button" id="btnExportarExcelMan" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-export"></span> Excel</button>
                            <button type="button" id="btnExportarPDFMan" class="btn btn-danger btn-sm"><span class="glyphicon glyphicon-print"></span> PDF</button>
                        </div>
                    </div>
                    <table id="gridManifiestosDetalle"></table>
                    <div id="gridManifiestosDetallePager"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Rango por defecto: un mes (desde día 1 al último del mes actual)
            var hoy = new Date();
            var y = hoy.getFullYear(),
                m = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var fecIni = y + '-' + m + '-01';
            var ultimoDia = new Date(y, hoy.getMonth() + 1, 0).getDate();
            var fecFin = y + '-' + m + '-' + ('0' + ultimoDia).slice(-2);
            $('#Fec_Ini').val(fecIni);
            $('#Fec_Fin').val(fecFin);

            public $grid = $('#gridFacturas');
            
            // Si el usuario tiene una planta asignada (desde PHP), ocultamos el filtro manual
            var plaAsignada = <?php echo $Pla_Cod_Asignada; ?>;
            if (plaAsignada > 0) {
                $('#inputNombrePlanta').val('FILTRO ACTIVADO POR USUARIO').prop('readonly', true);
                $('#btnAbrirModalPlanta').hide();
                $('#btnQuitarPlanta').hide();
            }

            // Build initial postData from the form, which now has the default dates
            var initialPostData = {};
            $.each($('#formBusqueda').serializeArray(), function(i, o) {
                initialPostData[o.name] = o.value;
            });
            initialPostData.gridFacturasManifiestos = 1;

            $grid.jqGrid({
                url: 'man_fac_man.php',
                mtype: 'GET',
                datatype: 'json',
                postData: initialPostData, // Use the pre-built object for initial load
                colModel: [{
                        name: 'Vet_Cod',
                        index: 'Vet_Cod',
                        key: true,
                        hidden: true
                    },
                    {
                        name: 'Vet_Num',
                        index: 'Vet_Num',
                        hidden: true
                    },
                    {
                        name: 'Vet_Num_Completo',
                        label: 'Nº Factura',
                        index: 'Vet_Num_Completo',
                        width: 120,
                        align: 'center'
                    },
                    {
                        name: 'Pla_Nom',
                        label: 'Planta',
                        index: 'Pla_Nom',
                        width: 160,
                        formatter: function(cellvalue, options, rowObject) {
                            return (rowObject.Pla_Nom != null && rowObject.Pla_Nom !== '') ? rowObject.Pla_Nom : (rowObject.pla_nom != null ? rowObject.pla_nom : '');
                        }
                    },
                    {
                        name: 'cliente',
                        label: 'Cliente',
                        index: 'cliente',
                        width: 200
                    },
                    {
                        name: 'Prs_Ced',
                        label: 'Cédula/RUC',
                        index: 'Prs_Ced',
                        width: 110,
                        align: 'center'
                    },
                    {
                        name: 'Vet_Fec',
                        label: 'Fecha venta',
                        index: 'Vet_Fec',
                        width: 100,
                        align: 'center'
                    },
                    {
                        name: 'cant_manifiestos',
                        label: 'Cant.Manif',
                        index: 'cant_manifiestos',
                        width: 70,
                        align: 'center'
                    },
                    {
                        name: 'subtotal_factura',
                        label: 'Subtotal',
                        index: 'subtotal_factura',
                        width: 80,
                        align: 'right',
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 2
                        }
                    },
                    {
                        name: 'iva_factura',
                        label: 'IVA',
                        index: 'iva_factura',
                        width: 60,
                        align: 'right',
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 2
                        }
                    },
                    {
                        name: 'total_factura',
                        label: 'Total factura',
                        index: 'total_factura',
                        width: 80,
                        align: 'right',
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 2
                        }
                    },
                    {
                        name: 'Vet_Aut_Des',
                        label: 'Estado',
                        index: 'Vet_Aut_Des',
                        width: 100,
                        align: 'center'
                    },
                    {
                        name: 'ver',
                        label: 'Manifiestos',
                        width: 75,
                        align: 'center',
                        sortable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var n = rowObject.cant_manifiestos * 1;
                            if (n === 0) return '';
                            var numShow = rowObject.Vet_Num_Completo || rowObject.Vet_Num || '';
                            var cliente = (rowObject.cliente || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var planta = (rowObject.Pla_Nom || rowObject.pla_nom || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var fecha = rowObject.Vet_Fec || '';
                            return '<button type="button" class="btn btn-success btn-ver-man btn-xs ver-manifiestos" data-vet-cod="' + rowObject.Vet_Cod + '" data-vet-num="' + (numShow + '').replace(/"/g, '&quot;') + '" data-cliente="' + cliente + '" data-planta="' + planta + '" data-fecha="' + fecha + '"><span class="glyphicon glyphicon-list"></span> Manifiestos</button>';
                        }
                    },
                    {
                        name: 'chart',
                        label: 'Graf.',
                        width: 70,
                        align: 'center',
                        sortable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var n = rowObject.cant_manifiestos * 1;
                            if (n === 0) return '';
                            var numShow = (rowObject.Vet_Num_Completo || rowObject.Vet_Num || '').replace(/"/g, '&quot;');
                            var cliente = (rowObject.cliente || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var planta = (rowObject.Pla_Nom || rowObject.pla_nom || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var direccion = (rowObject.Pla_Dir || rowObject.pla_dir || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var fecha = rowObject.Vet_Fec || '';
                            var subtotal = rowObject.subtotal_factura || 0;
                            var iva = rowObject.iva_factura || 0;
                            var total = rowObject.total_factura || 0;
                            return '<span class="glyphicon glyphicon-stats btn-chart-factura" style="color: #337ab7; font-size: 18px; cursor: pointer;" title="Imprimir factura y gráfico de manifiestos" data-vet-cod="' + rowObject.Vet_Cod + '" data-vet-num="' + numShow + '" data-cliente="' + cliente + '" data-planta="' + planta + '" data-direccion="' + direccion + '" data-fecha="' + fecha + '" data-subtotal="' + subtotal + '" data-iva="' + iva + '" data-total="' + total + '" data-cant="' + n + '"></span>';
                        }
                    }
                ],
                pager: '#gridFacturasPager',
                rowNum: 100,
                rowList: [100, 500, 1000, 2000],
                sortname: 'Vet_Fec',
                sortorder: 'desc',
                viewrecords: true,
                caption: 'Facturas con cantidad de manifiestos',
                height: 450,
                width: null,

                autowidth: true,
                loadComplete: function() {
                    public $g = $(this);
                    $g.find('.ver-manifiestos').off('click').on('click', function(e) {
                        e.preventDefault();
                        var vetCod = $(this).data('vet-cod');
                        var vetNum = $(this).data('vet-num');
                        var cliente = $(this).data('cliente');
                        var planta = $(this).data('planta');
                        var fecha = $(this).data('fecha');
                        $('#modalFacturaNum').text(vetNum || vetCod);
                        $('#modalClienteNombre').text(cliente || '-');
                        $('#modalPlantaNombre').text(planta || '-');
                        $('#modalFacturaFecha').text(fecha || '-');
                        $('#modalManifiestos').modal('show');
                        var plaCod = $('#Pla_Cod_Usuario').val() || '';
                        $.get('man_fac_man.php', {
                            manifiestosFactura: 1,
                            Vet_Cod: vetCod,
                            Pla_Cod_Usuario: plaCod
                        }, function(r) {
                            var rows = (r && r.rows) ? r.rows : [];
                            $('#modalCantManifiestos').text(rows.length);
                            var sumPes = 0,
                                sumPun = 0,
                                sumTotal = 0;
                            rows.forEach(function(row) {
                                // Peso en BD viene en kg; convertir a toneladas para cálculos y visualización
                                row.Man_Pes = parseFloat(row.Man_Pes) / 1000;
                                var totalCalculado = row.Man_Pes * parseFloat(row.Man_Pun);
                                row.total = totalCalculado.toFixed(2);

                                sumPes += row.Man_Pes || 0;
                                sumPun += parseFloat(row.Man_Pun) || 0;
                                sumTotal += parseFloat(row.total) || 0;
                            });
                            window.currentManifestRows = rows; // Guardar para exportación
                            window.currentVetNum = vetNum;
                            window.currentCliente = cliente;
                            window.currentPlanta = planta;
                            window.currentFecha = fecha;
                            public $det = $('#gridManifiestosDetalle');
                            $det.jqGrid('clearGridData');
                            $det.jqGrid('setGridParam', {
                                data: rows,
                                datatype: 'local'
                            });
                            $det.trigger('reloadGrid');
                            $det.jqGrid('footerData', 'set', {
                                Man_Num: 'TOTALES',
                                Man_Fes: '',
                                Pla_Nom: '',
                                cliente: '',
                                Man_Pes: sumPes.toFixed(2),
                                Man_Pun: sumPun.toFixed(2),
                                total: sumTotal.toFixed(2)
                            });
                        }, 'json').fail(function() {
                            $('#gridManifiestosDetalle').jqGrid('clearGridData').trigger('reloadGrid');
                        });
                    });
                    $g.find('.btn-chart-factura').off('click').on('click', function(e) {
                        e.preventDefault();
                        var vetCod = $(this).data('vet-cod');
                        var vetNum = $(this).data('vet-num') || '';
                        var cliente = $(this).data('cliente') || '';
                        var planta = $(this).data('planta') || '';
                        var direccion = $(this).data('direccion') || '';
                        var fecha = $(this).data('fecha') || '';
                        var subtotal = $(this).data('subtotal') || 0;
                        var iva = $(this).data('iva') || 0;
                        var total = $(this).data('total') || 0;
                        var cant = $(this).data('cant') || 0;
                        var plaCod = $('#Pla_Cod_Usuario').val() || '';
                        $.get('man_fac_man.php', {
                            manifiestosFactura: 1,
                            Vet_Cod: vetCod,
                            Pla_Cod_Usuario: plaCod
                        }, function(r) {
                            var rows = (r && r.rows) ? r.rows : [];
                            var byDate = {};
                            rows.forEach(function(row) {
                                var f = row.Man_Fes || row.man_fes || '';
                                var d = f.toString().substring(0, 10);
                                byDate[d] = (byDate[d] || 0) + 1;
                            });
                            var labels = Object.keys(byDate).sort();
                            var data = labels.map(function(k) {
                                return byDate[k];
                            });
                            var maxVal = Math.max.apply(null, data) || 1;
                            var chartH = 220;
                            var barsHtml = '';

                            function escHtml(s) {
                                var t = String(s || '');
                                return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                            }

                            function fmt(n) {
                                return parseFloat(n).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                            labels.forEach(function(label, i) {
                                var h = (data[i] / maxVal) * chartH;
                                barsHtml += '<div class="fm-bar-cell"><div class="fm-bar-val">' + data[i] + '</div><div class="fm-bar" style="height:' + h + 'px"></div><div class="fm-bar-label">' + escHtml(label) + '</div></div>';
                            });
                            var doc = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Factura ' + escHtml(vetNum) + ' - Gráfico manifiestos</title><style>' +
                                'body{font-family:Arial,sans-serif;margin:20px;}' +
                                '.no-print{margin-bottom:15px;}' +
                                '@media print{' +
                                '.no-print{display:none!important;}' +
                                '@page { size: landscape; margin: 0.5cm; }' +
                                '.fm-chart-wrap{overflow:visible!important; display: flex!important;}' +
                                '.fm-chart-area{border:none!important; padding:0!important; background: transparent!important;}' +
                                '.fm-header{background:#fff!important; border: 1px solid #ddd!important;}' +
                                '}' +
                                '.fm-header{margin-bottom:20px;padding:12px;background:#f5f5f5;border-left:4px solid #337ab7; display: flex; justify-content: space-between; align-items: flex-start;}' +
                                '.fm-header .info-main div{margin:4px 0; font-size: 13px;}' +
                                '.fm-header .info-totals{text-align: right; border-left: 1px solid #ccc; padding-left: 15px;}' +
                                '.fm-header .info-totals div{margin: 2px 0; font-size: 13px;}' +
                                '.fm-header .info-totals .total-val{font-size: 16px; color: #337ab7; margin-top: 5px; border-top: 1px solid #ddd; padding-top: 5px;}' +
                                '.fm-chart-title{font-size:14px;margin:15px 0 10px;color:#333;}' +
                                '.fm-chart-area{display:flex;align-items:stretch;gap:8px; border: 1px solid #eee; padding: 10px; border-radius: 4px; background: #fff;}' +
                                '.fm-y-label{font-size:11px;color:#666;writing-mode:vertical-rl;transform:rotate(180deg);align-self:center;margin-right:5px; font-weight: bold;}' +
                                '.fm-chart-wrap{display:flex;align-items:flex-end;justify-content:flex-start;gap:2px;min-height:260px;padding:10px 0 85px; border-bottom:1px solid #ccc; flex:1; overflow-x: auto; scroll-behavior: smooth;}' +
                                '.fm-bar-cell{display:flex;flex-direction:column;align-items:center;flex:0 0 28px; position: relative;}' +
                                '.fm-bar-val{font-size:11px;font-weight:bold;color:#337ab7;margin-bottom:4px;}' +
                                '.fm-bar{width:24px;background:#337ab7;border-radius:3px 3px 0 0;min-height:2px;-webkit-print-color-adjust:exact;print-color-adjust:exact; transition: height 0.3s ease;}' +
                                '.fm-bar-label{font-size:10px; position: absolute; top: 100%; margin-top: 8px; left: 50%; transform: rotate(90deg); transform-origin: left center; white-space: nowrap; color: #666; font-weight: bold;}' +
                                '</style></head><body>' +
                                '<div class="no-print"><button type="button" onclick="window.print()">Imprimir</button> <button type="button" onclick="window.close()">Cerrar</button></div>' +
                                '<div class="fm-header">' +
                                '<div class="info-main">' +
                                '<div>Nº Factura: ' + escHtml(vetNum || vetCod) + ' &nbsp;&nbsp;&nbsp; Fecha: ' + escHtml(fecha) + '</div>' +
                                '<div>Planta: ' + escHtml(planta || '-') + '</div>' +
                                '<div>Dirección: ' + escHtml(direccion || '-') + '</div>' +
                                '<div>Cliente: ' + escHtml(cliente || '-') + '</div>' +
                                '<div>Total Manifiestos: ' + cant + '</div>' +
                                '</div>' +
                                '<div class="info-totals">' +
                                '<div>Subtotal: ' + fmt(subtotal) + '</div>' +
                                '<div>IVA: ' + fmt(iva) + '</div>' +
                                '<div class="total-val">Total Facturado: ' + fmt(total) + '</div>' +
                                '</div>' +
                                '</div>' +
                                '<div class="fm-chart-title">Gráfico estadístico: cantidad de manifiestos por fecha (eje Y: cantidad, eje X: fecha)</div>' +
                                '<div class="fm-chart-area"><span class="fm-y-label">Cant. manifiestos</span><div class="fm-chart-wrap">' + (barsHtml || '<p>No hay datos de manifiestos.</p>') + '</div></div>' +
                                '</body></html>';
                           // var w = window.open('', 'ChartFactura' + vetCod, 'width=800,height=500,scrollbars=yes,resizable=yes');
                            var w = window.open('', 'ChartFactura' + vetCod, 'left=0,top=0,width=' + (screen.availWidth || screen.width) + ',height=' + (screen.availHeight || screen.height) + ',scrollbars=yes,resizable=yes');
                            
                            if (w) {
                                w.document.write(doc);
                                w.document.close();
                            }
                        }, 'json');
                    });
                }
            });

            $('#btnBuscar').on('click', function() {
                var fd = {};
                $.each($('#formBusqueda').serializeArray(), function(i, o) {
                    fd[o.name] = o.value;
                });
                fd.gridFacturasManifiestos = 1;
                $grid.jqGrid('setGridParam', {
                    postData: fd
                }).trigger('reloadGrid');
            });

            $('#btnImprimirReporte').on('click', function() {
                var q = $.param({
                    reporte: 1,
                    Pla_Cod_Usuario: $('#Pla_Cod_Usuario').val() || '',
                    Num_Factura: $('#Num_Factura').val() || '',
                    Fec_Ini: $('#Fec_Ini').val() || '',
                    Fec_Fin: $('#Fec_Fin').val() || ''
                });
                window.open('man_fac_man.php?' + q, 'ReporteFacturasManifiestos', 'width=900,height=600,scrollbars=yes,resizable=yes');
            });

            $('#btnExportarExcel').on('click', function() {
                var q = $.param({
                    excel: 1,
                    Pla_Cod_Usuario: $('#Pla_Cod_Usuario').val() || '',
                    Num_Factura: $('#Num_Factura').val() || '',
                    Fec_Ini: $('#Fec_Ini').val() || '',
                    Fec_Fin: $('#Fec_Fin').val() || ''
                });
                window.location.href = 'man_fac_man.php?' + q;
            });

            function aplicarPlantaSeleccionada(row) {
                if (row && row.Pla_Cod) {
                    $('#Pla_Cod_Usuario').val(row.Pla_Cod);
                    var plaNom = (row.Pla_Nom != null && row.Pla_Nom !== '') ? row.Pla_Nom : (row.pla_nom != null ? row.pla_nom : row.Pla_Cod);
                    $('#inputNombrePlanta').val(plaNom);
                    $('#inputCedulaPlanta').val(row.Prs_Ced != null ? row.Prs_Ced : (row.prs_ced != null ? row.prs_ced : ''));
                    $('#btnQuitarPlanta').show();
                    $('#plaDialog').dialog('close');
                    var fd = {};
                    $.each($('#formBusqueda').serializeArray(), function(i, o) {
                        fd[o.name] = o.value;
                    });
                    fd.gridFacturasManifiestos = 1;
                    $grid.jqGrid('setGridParam', {
                        postData: fd
                    }).trigger('reloadGrid');
                }
            }

            // Diálogo de búsqueda de planta (mismo patrón que clieDialog en fac_alt_fac_ven_3.2.php)
            // selectPlanta en ámbito global para que el botón del grid (onclick) la encuentre
            window.selectPlanta = function(planta) {
                aplicarPlantaSeleccionada(planta);
                $('#plaDialog').dialog('close');
            };

            $.createSearchDialog('plaDialog', [{
                    label: 'C&oacute;d. Planta',
                    name: 'Pla_Cod',
                    key: true,
                    width: 90,
                    align: 'center',
                    hidden: true
                },
                {
                    label: 'Cédula/RUC',
                    name: 'Prs_Ced',
                    width: 50
                },
                {
                    label: 'Planta',
                    name: 'Pla_Nom',
                    width: 100
                },
                {
                    label: 'Cliente',
                    name: 'cliente',
                    width: 100
                },
                {
                    label: '&nbsp;',
                    name: 'act1',
                    width: 20,
                    align: 'center',
                    viewable: false,
                    formatter: 'gridButton',
                    formatoptions: {
                        action: 'selectPlanta',
                        data: function(row) {
                            return {
                                Pla_Cod: row.Pla_Cod,
                                Pla_Nom: row.Pla_Nom,
                                Cli_Cod: row.Cli_Cod,
                                cliente: row.cliente,
                                Prs_Ced: row.Prs_Ced
                            };
                        }
                    }
                }
            ], null, null, null, {
                headertitles: true
            }, {
                title: 'Planta',
                text: 'search',
                options: [
                    { label: '&nbsp;&nbsp;Nombre planta / Cliente&nbsp;&nbsp;', value: 'd' },
                    { label: '&nbsp;&nbsp;Cédula/R.U.C&nbsp;&nbsp;', value: 'c' }
                ]
            });

            $('#plaDialog').on('dialogopen', function() {
                $.Search('pla');
            });

            $('#btnAbrirModalPlanta').on('click', function() {
                $('#plaDialog').dialog('open');
            });
            $('#inputNombrePlanta').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#plaDialog').dialog('open');
                }
            });

            $('#btnQuitarPlanta').on('click', function(e) {
                e.preventDefault();
                $('#Pla_Cod_Usuario').val('');
                $('#inputNombrePlanta').val('');
                $('#inputCedulaPlanta').val('');
                $(this).hide();
                var fd = {};
                $.each($('#formBusqueda').serializeArray(), function(i, o) {
                    fd[o.name] = o.value;
                });
                fd.gridFacturasManifiestos = 1;
                $grid.jqGrid('setGridParam', {
                    postData: fd
                }).trigger('reloadGrid');
            });

            $('#gridManifiestosDetalle').jqGrid({
                colModel: [{
                        name: 'Man_Cod',
                        hidden: true
                    },
                    {
                        name: 'Man_Num',
                        label: 'Nº Man',
                        width: 50,
                        align: 'center',
                        formatter: function(cellvalue, options, rowObject) {
                            if (rowObject.Pla_Cod && cellvalue !== undefined && cellvalue !== '') {
                                var n = String(cellvalue);
                                while (n.length < 4) n = '0' + n;
                                return 'M' + rowObject.Pla_Cod + '-' + n;
                            }
                            return cellvalue || '';
                        }
                    },
                    {
                        name: 'Man_Fes',
                        label: 'Fecha',
                        width: 130,
                        align: 'center'
                    },
                    {
                        name: 'Pla_Nom',
                        label: 'Planta',
                        width: 160
                    },
                    {
                        name: 'cliente',
                        label: 'Cliente',
                        width: 180
                    },
                    {
                        name: 'Man_Pes',
                        label: 'Peso(Tn)',
                        width: 70,
                        align: 'right',
                        formatter: 'number',
                        formatoptions: { decimalPlaces: 2 }
                    },
                    {
                        name: 'Man_Pun',
                        label: 'P.Unit.',
                        width: 70,
                        align: 'right'
                    },
                    {
                        name: 'total',
                        label: 'Total',
                        width: 80,
                        align: 'right',
                        formatter: 'number',
                        formatoptions: {
                            decimalPlaces: 2
                        }
                    }
                ],
                width: 865,
                height: 280,
                datatype: 'local',
                pager: '#gridManifiestosDetallePager',
                rowNum: 100,
                rowList: [100, 200, 500, 1000, 2000],
                viewrecords: true,
                footerrow: true,
                userDataOnFooter: true

            });

            $('#modalManifiestos').on('shown.bs.modal', function() {
                if ($('#gridManifiestosDetalle').length && $.fn.jqGrid) {
                    $('#gridManifiestosDetalle').trigger('reloadGrid');
                }
            });

            $('#btnExportarExcelMan').on('click', function() {
                var rows = window.currentManifestRows;
                if (!rows || rows.length === 0) return alert('No hay datos para exportar');
                
                var vetNum = window.currentVetNum;
                var cliente = window.currentCliente;
                var planta = window.currentPlanta;
                var fecha = window.currentFecha;
                
                var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                html += '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /></head><body>';
                
                // Tabla de resumen/encabezado
                html += '<table>';
                html += '<tr><td colspan="2"><strong>Reporte de Manifiestos</strong></td></tr>';
                html += '<tr><td><strong>Factura:</strong></td><td>' + vetNum + '</td></tr>';
                html += '<tr><td><strong>Fecha Factura:</strong></td><td>' + fecha + '</td></tr>';
                html += '<tr><td><strong>Cliente:</strong></td><td>' + cliente + '</td></tr>';
                html += '<tr><td><strong>Planta:</strong></td><td>' + planta + '</td></tr>';
                html += '<tr><td><strong>Cant. Manifiestos:</strong></td><td>' + rows.length + '</td></tr>';
                html += '<tr><td></td></tr>';
                html += '</table>';
                
                // Tabla de detalles con bordes
                html += '<table border="1">';
                html += '<tr>';
                html += '<th style="background-color: #eee;">Nº Manifiesto</th>';
                html += '<th style="background-color: #eee;">Fecha</th>';
                html += '<th style="background-color: #eee;">Planta</th>';
                html += '<th style="background-color: #eee;">Cliente</th>';
                html += '<th style="background-color: #eee;">Peso(Tn)</th>';
                html += '<th style="background-color: #eee;">P.Unit.</th>';
                html += '<th style="background-color: #eee;">Total</th>';
                html += '</tr>';
                
                var sPes = 0, sTot = 0;
                rows.forEach(function(r) {
                    html += '<tr>';
                    var manNumDisplay = (r.Pla_Cod && r.Man_Num !== undefined && r.Man_Num !== '') ? (function() { var n = String(r.Man_Num); while (n.length < 4) n = '0' + n; return 'M' + r.Pla_Cod + '-' + n; })() : (r.Man_Num || '');
                    html += '<td>' + manNumDisplay + '</td>';
                    html += '<td>' + r.Man_Fes + '</td>';
                    html += '<td>' + (r.Pla_Nom || '') + '</td>';
                    html += '<td>' + (r.cliente || '') + '</td>';
                    html += '<td align="right">' + (parseFloat(r.Man_Pes).toFixed(2)) + '</td>';
                    html += '<td align="right">' + r.Man_Pun + '</td>';
                    html += '<td align="right">' + r.total + '</td>';
                    html += '</tr>';
                    sPes += parseFloat(r.Man_Pes) || 0;
                    sTot += parseFloat(r.total) || 0;
                });
                
                // Fila de totales (sPes ya en toneladas)
                html += '<tr>';
                html += '<td colspan="4" style="background-color: #f9f9f9;"><strong>TOTALES</strong></td>';
                html += '<td align="right" style="background-color: #f9f9f9;"><strong>' + sPes.toFixed(2) + '</strong></td>';
                html += '<td style="background-color: #f9f9f9;"></td>';
                html += '<td align="right" style="background-color: #f9f9f9;"><strong>' + sTot.toFixed(2) + '</strong></td>';
                html += '</tr>';
                html += '</table></body></html>';
                
                var blob = new Blob([html], {
                    type: 'application/vnd.ms-excel;charset=utf-8;'
                });
                var link = document.createElement("a");
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "Manifiestos_Factura_" + vetNum + ".xls");
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            $('#btnExportarPDFMan').on('click', function() {
                var rows = window.currentManifestRows;
                if (!rows || rows.length === 0) return alert('No hay datos para imprimir');
                var vetNum = window.currentVetNum;
                var cliente = window.currentCliente;
                var planta = window.currentPlanta;
                var fecha = window.currentFecha;
                var win = window.open('', '_blank');
                var html = '<html><head><title>Manifiestos - ' + vetNum + '</title>';
                html += '<style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:12px;} th{background:#eee;} .header{margin-bottom:20px;} .total{font-weight:bold;}</style></head><body>';
                html += '<div class="header"><h3>Reporte de Manifiestos</h3>';
                html += '<div><strong>Factura:</strong> ' + vetNum + '</div>';
                html += '<div><strong>Fecha Factura:</strong> ' + fecha + '</div>';
                html += '<div><strong>Cliente:</strong> ' + cliente + '</div>';
                html += '<div><strong>Planta:</strong> ' + planta + '</div>';
                html += '<div><strong>Cant. Manifiestos:</strong> ' + rows.length + '</div></div>';
                html += '<table><thead><tr><th>Nº Man</th><th>Fecha</th><th>Planta</th><th>Peso(Tn)</th><th>P.Unit</th><th>Total</th></tr></thead><tbody>';
                var sPes = 0,
                    sTot = 0;
                rows.forEach(function(r) {
                    var manNumD = (r.Pla_Cod && r.Man_Num !== undefined && r.Man_Num !== '') ? (function() { var n = String(r.Man_Num); while (n.length < 4) n = '0' + n; return 'M' + r.Pla_Cod + '-' + n; })() : (r.Man_Num || '');
                    html += '<tr><td>' + manNumD + '</td><td>' + r.Man_Fes + '</td><td>' + (r.Pla_Nom || '') + '</td><td align="right">' + (parseFloat(r.Man_Pes).toFixed(2)) + '</td><td align="right">' + r.Man_Pun + '</td><td align="right">' + r.total + '</td></tr>';
                    sPes += parseFloat(r.Man_Pes) || 0;
                    sTot += parseFloat(r.total) || 0;
                });
                html += '<tr class="total"><td colspan="3">TOTALES</td><td align="right">' + sPes.toFixed(2) + '</td><td></td><td align="right">' + sTot.toFixed(2) + '</td></tr>';
                html += '</tbody></table>';
                html += '<' + 'script>window.onload = function() { window.print(); window.close(); };<' + '/script></body></html>';
                win.document.write(html);
                win.document.close();
            });
        });
    </script>
</body>

</html>