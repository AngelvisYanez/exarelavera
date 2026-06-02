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

/** Nombre de empresa en cabecera del reporte PDF/imprimir manifiestos */
$man_fac_empresa_encabezado = 'Ecoparkmining';

/* Perfiles: opciones de firma en certificado (igual que man_alt_manifiesto.php) */
$perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array(
    'where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod),
    'setWhere' => array('getPerfil')
), $obBD_conexion);
$firmar_solo_si = false;
$firmar_solo_no = false;
if (is_array($perfil)) {
    foreach ($perfil as $p) {
        $per_desc = trim($p['Per_Des']);
        if ($per_desc == 'Gerente' || $per_desc == 'Contador') {
            $firmar_solo_si = true;
        }
        if ($per_desc == 'Admin_Oper') {
            $firmar_solo_no = true;
        }
    }
}

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

/* Totales para footer del grid de facturas (sin paginación) */
if (isset($_GET['totalesGridFacturasManifiestos'])) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;

    if ($Pla_Cod_Asignada > 0) {
        $data['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
    } else if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $data['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
    }

    $rows = $obBD_con1->getArrayConsulta(82, $data, $obBD_conexion);
    $row = (is_array($rows) && count($rows) > 0) ? $rows[0] : array();
    $obBD_con1->echoJson(array(
        'sum_cant_manifiestos' => isset($row['sum_cant_manifiestos']) ? (int)$row['sum_cant_manifiestos'] : 0,
        'sum_subtotal_factura' => isset($row['sum_subtotal_factura']) ? (float)$row['sum_subtotal_factura'] : 0,
        'sum_iva_factura' => isset($row['sum_iva_factura']) ? (float)$row['sum_iva_factura'] : 0,
        'sum_total_factura' => isset($row['sum_total_factura']) ? (float)$row['sum_total_factura'] : 0
    ));
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
    $empresa = htmlspecialchars($man_fac_empresa_encabezado);
    $logo = isset($Ses_Emp_Log) ? trim($Ses_Emp_Log) : '';
    $logoHtml = ($logo !== '') ? ('<div class="logo"><img src="' . htmlspecialchars($logo) . '" alt="' . $empresa . '"></div>') : '';

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte Facturas y Manifiestos</title>';
    echo '<style type="text/css">
        *{box-sizing:border-box;}
        body { font-family: Arial, sans-serif; margin: 15px; color:#111; font-size: 11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        /* En pantalla no mostrar el HTML (solo impresión) */
        #printArea{display:none;}
        @media print { #printArea{display:block;} }
        .rep-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #ddd;}
        .rep-head .tit{font-size:14px;font-weight:700;line-height:1.2;}
        .rep-head .sub{font-size:10px;color:#444;margin-top:4px;}
        .rep-head .logo img{max-height:58px;max-width:240px;object-fit:contain;display:block;}
        table.reporte { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table.reporte th, table.reporte td { border: 1px solid #333; padding: 3px 5px; text-align: left; font-size: 10px; }
        table.reporte th { background: #eee; }
        table.reporte td.numeric { text-align: right; }
        @page { margin: 10mm; }
        @media print { }
    </style>
    <script>
      window.onload = function () {
        try { window.focus(); } catch(e) {}
        // Pequeño defer para que el navegador termine de renderizar antes de imprimir
        setTimeout(function(){ window.print(); }, 50);
      };
    </script>
    </head><body>';

    echo '<div id="printArea">';
    echo '<div class="rep-head">';
    echo '<div class="txt"><div class="tit">' . $empresa . '</div><div class="sub">Reporte: Facturas con Manifiestos</div></div>';
    echo $logoHtml;
    echo '</div>';
    echo '<table class="reporte"><thead><tr>';
    echo '<th>#</th><th>Nº.Fac</th><th>Ced/RUC</th><th>Planta</th><th>Cliente</th><th>Fecha</th><th>Tot.Fac</th><th>Cant. Man</th></tr></thead><tbody>';
    $i = 0;
    $sumSubtotal = 0.0;
    $sumIva = 0.0;
    $sumTotal = 0.0;
    $sumCant = 0;
    foreach ($rows as $r) {
        $i++;
        $num = htmlspecialchars(isset($r['Vet_Num_Completo']) ? $r['Vet_Num_Completo'] : '');
        $pla = htmlspecialchars(isset($r['Pla_Nom']) && $r['Pla_Nom'] !== '' ? $r['Pla_Nom'] : (isset($r['pla_nom']) ? $r['pla_nom'] : ''));
        $cli = htmlspecialchars(isset($r['cliente']) ? $r['cliente'] : '');
        $ced = htmlspecialchars(isset($r['Prs_Ced']) ? $r['Prs_Ced'] : '');
        $fec = htmlspecialchars(isset($r['Vet_Fec']) ? $r['Vet_Fec'] : '');
        $subtotalVal = isset($r['subtotal_factura']) ? (float)$r['subtotal_factura'] : 0.0;
        $ivaVal = isset($r['iva_factura']) ? (float)$r['iva_factura'] : 0.0;
        $totalVal = isset($r['total_factura']) ? (float)$r['total_factura'] : 0.0;
        $tot = number_format($totalVal, 2);
        $cant = isset($r['cant_manifiestos']) ? (int)$r['cant_manifiestos'] : 0;

        $sumSubtotal += $subtotalVal;
        $sumIva += $ivaVal;
        $sumTotal += $totalVal;
        $sumCant += $cant;

        echo "<tr><td style=\"text-align:center;\">{$i}</td><td>{$num}</td><td>{$ced}</td><td>{$pla}</td><td>{$cli}</td><td>{$fec}</td><td class=\"numeric\">{$tot}</td><td class=\"numeric\">{$cant}</td></tr>";
    }
    // Fila de totales (Subtotal, IVA, Total, Cant.Manif)
    $totTotal = number_format($sumTotal, 2);
    echo "<tr>
            <td colspan=\"6\" style=\"text-align:right;font-weight:bold;background:#f3f4f6;\">TOTALES</td>
            <td class=\"numeric\" style=\"font-weight:bold;background:#f3f4f6;\">{$totTotal}</td>
            <td class=\"numeric\" style=\"font-weight:bold;background:#f3f4f6;\">{$sumCant}</td>
          </tr>";
    echo '</tbody></table></div></body></html>';
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

    $empresa = htmlspecialchars($man_fac_empresa_encabezado);
    $logo = isset($Ses_Emp_Log) ? trim($Ses_Emp_Log) : '';

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />
        <style>
          table td,table th{padding:3px 6px;font-size:11px;line-height:1.35;}
          .xl-head td{border:1px solid #ddd;}
          .xl-title{font-size:16px;font-weight:bold;}
        </style>
      </head><body>';

    // Encabezado (empresa + logo)
    echo '<table class="xl-head" border="1" style="border-collapse:collapse; width:100%;">';
    echo '<tr>';
    echo '<td colspan="7" class="xl-title">' . $empresa . '</td>';
    if ($logo !== '') {
        echo '<td colspan="3" align="right"><img src="' . htmlspecialchars($logo) . '" style="max-height:60px; max-width:220px;" /></td>';
    } else {
        echo '<td colspan="3"></td>';
    }
    echo '</tr>';
    echo '<tr><td colspan="10">Reporte: Facturas con Manifiestos</td></tr>';
    echo '</table><br />';

    echo '<table border="1">';
    echo '<tr>';
    echo '<th style="background-color: #eee;">#</th>';
    echo '<th style="background-color: #eee;">Nº Fact.</th>';
    echo '<th style="background-color: #eee;">Ced/RUC</th>';
    echo '<th style="background-color: #eee;">Planta</th>';
    echo '<th style="background-color: #eee;">Cliente</th>';
    echo '<th style="background-color: #eee;">Fecha</th>';
    echo '<th style="background-color: #eee;">Subtotal</th>';
    echo '<th style="background-color: #eee;">IVA</th>';
    echo '<th style="background-color: #eee;">Total</th>';
    echo '<th style="background-color: #eee;">Cant.Man.</th>';
    echo '</tr>';

    $i = 0;
    $sumSubtotal = 0.0;
    $sumIva = 0.0;
    $sumTotal = 0.0;
    $sumCant = 0;
    foreach ($rows as $r) {
        $i++;
        echo '<tr>';
        echo '<td align="center">' . $i . '</td>';
        echo '<td>' . (isset($r['Vet_Num_Completo']) ? $r['Vet_Num_Completo'] : '') . '</td>';
        // Forzar texto para que Excel no quite ceros ni use notación científica
        $ced = (isset($r['Prs_Ced']) ? $r['Prs_Ced'] : '');
        echo '<td style="mso-number-format:\'\\@\';">' . htmlspecialchars($ced) . '</td>';
        echo '<td>' . (isset($r['Pla_Nom']) && $r['Pla_Nom'] !== '' ? $r['Pla_Nom'] : (isset($r['pla_nom']) ? $r['pla_nom'] : '')) . '</td>';
        echo '<td>' . (isset($r['cliente']) ? $r['cliente'] : '') . '</td>';
        echo '<td>' . (isset($r['Vet_Fec']) ? $r['Vet_Fec'] : '') . '</td>';
        $subtotalVal = isset($r['subtotal_factura']) ? (float)$r['subtotal_factura'] : 0.0;
        $ivaVal = isset($r['iva_factura']) ? (float)$r['iva_factura'] : 0.0;
        $totalVal = isset($r['total_factura']) ? (float)$r['total_factura'] : 0.0;
        $cantVal = isset($r['cant_manifiestos']) ? (int)$r['cant_manifiestos'] : 0;
        $sumSubtotal += $subtotalVal;
        $sumIva += $ivaVal;
        $sumTotal += $totalVal;
        $sumCant += $cantVal;

        echo '<td>' . number_format($subtotalVal, 2, '.', '') . '</td>';
        echo '<td>' . number_format($ivaVal, 2, '.', '') . '</td>';
        echo '<td>' . number_format($totalVal, 2, '.', '') . '</td>';
        echo '<td>' . $cantVal . '</td>';
        echo '</tr>';
    }
    // Fila de totales
    echo '<tr>';
    echo '<td colspan="6" style="background-color:#f3f4f6;font-weight:bold;text-align:right;">TOTALES</td>';
    echo '<td style="background-color:#f3f4f6;font-weight:bold;">' . number_format($sumSubtotal, 2, '.', '') . '</td>';
    echo '<td style="background-color:#f3f4f6;font-weight:bold;">' . number_format($sumIva, 2, '.', '') . '</td>';
    echo '<td style="background-color:#f3f4f6;font-weight:bold;">' . number_format($sumTotal, 2, '.', '') . '</td>';
    echo '<td style="background-color:#f3f4f6;font-weight:bold;">' . $sumCant . '</td>';
    echo '</tr>';
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


//echo $Ses_Emp_Log;


?>
<!DOCTYPE html>
<html>

<head>
    <title>Facturas y Manifiestos</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <style>
        /*  #gridFacturas { width: 100% !important; height: 600px !important; }*/
        #certFacLoader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.45);
            z-index: 10050;
            cursor: wait;
        }
        #certFacLoader .cert-fac-loader-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 22px 28px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            text-align: center;
            min-width: 220px;
        }
        #certFacLoader .glyphicon-spin {
            display: inline-block;
            animation: certFacSpin 0.9s infinite linear;
        }
        @keyframes certFacSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        body.cert-fac-loading { overflow: hidden !important; }
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

    <!-- Modal: ¿Firmar certificado? (como man_alt_manifiesto / impCertificadoRango) -->
    <div class="modal fade" id="modalCertificadoFirma" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Certificado de manifiestos</h4>
                </div>
                <div class="modal-body">
                    <p style="font-size: 12px; margin-bottom: 12px;">
                        Factura: <strong id="certFacNum"></strong>
                    </p>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="control-label" style="font-size: 12px;">&iquest;Desea firmarlo?</label>
                        <div class="btn-group" style="margin-left: 8px;">
                            <?php if (!$firmar_solo_no) { ?>
                            <button id="btnCertFacSi" type="button" class="btn btn-xs <?php echo (!$firmar_solo_no ? 'btn-primary active' : 'btn-default'); ?>" style="width: 44px;">Si</button>
                            <?php } ?>
                            <?php if (!$firmar_solo_si) { ?>
                            <button id="btnCertFacNo" type="button" class="btn btn-xs <?php echo ($firmar_solo_no ? 'btn-primary active' : 'btn-default'); ?>" style="width: 44px;">No</button>
                            <?php } ?>
                        </div>
                        <input type="checkbox" id="Cert_Fac_Firmar" style="display: none;" <?php echo ($firmar_solo_no ? '' : 'checked'); ?> />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnCertFacGenerar"><span class="glyphicon glyphicon-print"></span> Generar certificado</button>
                </div>
            </div>
        </div>
    </div>

    <div id="certFacLoader" aria-hidden="true" role="alertdialog" aria-busy="true" aria-label="Generando certificado">
        <div class="cert-fac-loader-box">
            <span class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 28px; color: #337ab7;"></span>
            <div id="certFacLoaderMsg" style="margin-top: 12px; font-weight: bold; color: #334155; font-size: 13px;">Generando certificado...</div>
        </div>
    </div>

    <script>
        var MAN_FAC_EMPRESA = '<?php echo ($man_fac_empresa_encabezado); ?>';
        var MAN_FAC_LOGO = '<?php echo (isset($Ses_Emp_Log) ? $Ses_Emp_Log : ''); ?>';
        var MAN_FAC_FIRMAR_SOLO_SI = <?php echo $firmar_solo_si ? 'true' : 'false'; ?>;
        var MAN_FAC_FIRMAR_SOLO_NO = <?php echo $firmar_solo_no ? 'true' : 'false'; ?>;
        $(function() {
            // Rango por defecto: desde enero (año actual) hasta hoy
            var hoy = new Date();
            var y = hoy.getFullYear(),
                m = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var fecIni = y + '-01-01';
            var d = ('0' + hoy.getDate()).slice(-2);
            var fecFin = y + '-' + m + '-' + d;
            $('#Fec_Ini').val(fecIni);
            $('#Fec_Fin').val(fecFin);

            var $grid = $('#gridFacturas');

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

             // Mismo endpoint que viewPdfVenta en fac_val_factura2.js (PDF desde XML / electrónico)
            var FACT_PDF_URL = '../../facturacion/COMPONENTES/tesPdfElectronicos.php';

            function escAttr(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            $grid.jqGrid({
                url: 'man_fac_man.php',
                mtype: 'GET',
                datatype: 'json',
                postData: initialPostData, // Use the pre-built object for initial load
                rownumbers: true,
                rownumWidth: 40,
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
                        align: 'center',
                        formatter: function(cellvalue, options, rowObject) {
                            var txt = (rowObject.Vet_Num_Completo != null && rowObject.Vet_Num_Completo !== '') ? String(rowObject.Vet_Num_Completo) : String(rowObject.Vet_Num || '');
                            var cod = rowObject.Vet_Cod;
                            if (!cod) return escAttr(txt);
                            return '<span class="fact-pdf-link" style="color:#337ab7;cursor:pointer;text-decoration:underline;" title="Ver / imprimir PDF de la factura" data-vet-cod="' + escAttr(cod) + '">' + escAttr(txt) + '</span>';
                        }
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
                    },
                    {
                        name: 'certificado',
                        label: 'Certificado',
                        width: 85,
                        align: 'center',
                        sortable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var n = rowObject.cant_manifiestos * 1;
                            if (n === 0) return '';
                            var numShow = rowObject.Vet_Num_Completo || rowObject.Vet_Num || '';
                            var clienteC = (rowObject.cliente || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var plantaC = (rowObject.Pla_Nom || rowObject.pla_nom || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            var fechaC = rowObject.Vet_Fec || '';
                            return '<button type="button" class="btn btn-primary btn-xs btn-certificado-factura" title="Imprimir certificado (PDF)" data-vet-cod="' + rowObject.Vet_Cod + '" data-vet-num="' + (numShow + '').replace(/"/g, '&quot;') + '" data-cliente="' + clienteC + '" data-planta="' + plantaC + '" data-fecha="' + fechaC + '"><span class="glyphicon glyphicon-print"></span></button>';
                        }
                    }
                ],
                pager: '#gridFacturasPager',
                rowNum: 1000,
                rowList: [100, 500, 1000, 2000],
                sortname: 'Vet_Fec',
                sortorder: 'desc',
                viewrecords: true,
                caption: 'Facturas con cantidad de manifiestos',
                height: 450,
                width: null,

                autowidth: true,
                footerrow: true,
                userDataOnFooter: true,
                loadComplete: function() {
                    var $g = $(this);
                    // Click en Nº Factura: abrir PDF (mismo flujo que fac_mod_fac_ven_3.2.php / viewPdfVenta)
                    $g.find('.fact-pdf-link').off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var vetCod = $(this).data('vet-cod');
                        if (!vetCod) return;
                        var url = FACT_PDF_URL + '?type=VENTAS&Doc_Cod=' + encodeURIComponent(vetCod);
                        // Abrir en nueva pestaña (el visor PDF del navegador permite imprimir/guardar como PDF)
                        window.open(url, '_blank');
                    });


                    // Totales GLOBAL (sin paginación) con los filtros actuales
                    (function setFooterTotalsAll() {
                        var pd = $g.jqGrid('getGridParam', 'postData') || {};
                        var req = $.extend({}, pd, {
                            totalesGridFacturasManifiestos: 1
                        });
                        // IMPORTANT: no enviar el flag del grid, caso contrario el PHP responde el JSON paginado del grid
                        // y no los totales.
                        if (req.gridFacturasManifiestos) delete req.gridFacturasManifiestos;
                        $.getJSON('man_fac_man.php', req, function(resp) {
                            resp = resp || {};
                            $g.jqGrid('footerData', 'set', {
                                Vet_Num_Completo: 'TOTALES',
                                cant_manifiestos: resp.sum_cant_manifiestos || 0,
                                subtotal_factura: (resp.sum_subtotal_factura != null ? Number(resp.sum_subtotal_factura).toFixed(2) : '0.00'),
                                iva_factura: (resp.sum_iva_factura != null ? Number(resp.sum_iva_factura).toFixed(2) : '0.00'),
                                total_factura: (resp.sum_total_factura != null ? Number(resp.sum_total_factura).toFixed(2) : '0.00')
                            });
                        });
                    })();

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
                            var $det = $('#gridManifiestosDetalle');
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
                    $g.find('.btn-certificado-factura').off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var $btn = $(this);
                        var vetCod = $btn.data('vet-cod');
                        if (!vetCod) return;
                        window._certFacBtn = $btn;
                        window._certFacData = {
                            vetCod: vetCod,
                            vetNum: $btn.data('vet-num') || '',
                            cliente: $btn.data('cliente') || '',
                            planta: $btn.data('planta') || '',
                            fecha: $btn.data('fecha') || ''
                        };
                        $('#certFacNum').text(window._certFacData.vetNum || vetCod);
                        if (MAN_FAC_FIRMAR_SOLO_SI) {
                            $('#Cert_Fac_Firmar').prop('checked', true);
                            $('#btnCertFacSi').addClass('btn-primary active').removeClass('btn-default');
                            $('#btnCertFacNo').addClass('btn-default').removeClass('btn-primary active');
                        } else if (MAN_FAC_FIRMAR_SOLO_NO) {
                            $('#Cert_Fac_Firmar').prop('checked', false);
                            $('#btnCertFacNo').addClass('btn-primary active').removeClass('btn-default');
                            $('#btnCertFacSi').addClass('btn-default').removeClass('btn-primary active');
                        }
                        $('#modalCertificadoFirma').modal('show');
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
                            var byDateTur = {};
                            rows.forEach(function(row) {
                                var f = row.Man_Fes || row.man_fes || '';
                                var d = f.toString().substring(0, 10);
                                byDate[d] = (byDate[d] || 0) + 1;
                                var tur = row.Tur_Cod != null ? row.Tur_Cod : (row.tur_cod != null ? row.tur_cod : '');
                                tur = (tur === undefined || tur === null) ? '' : String(tur).trim();
                                if (tur !== '') {
                                    if (!byDateTur.hasOwnProperty(d)) {
                                        byDateTur[d] = tur;
                                    } else if (byDateTur[d] !== tur) {
                                        byDateTur[d] = 'MIX';
                                    }
                                }
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

                            function colorForTurCod(turCod) {
                                var t = String(turCod == null ? '' : turCod).trim();
                                // Colores más oscuros y menos intensos
                                if (t === '' || t === '0') return '#475569'; // slate-600
                                if (t === 'MIX') return '#52525b'; // zinc-600
                                var palette = ['#3b82f6', '#22c55e', '#f43f5e', '#a855f7', '#f97316', '#06b6d4', '#ec4899', '#84cc16', '#14b8a6'];
                                var n = 0;
                                for (var i = 0; i < t.length; i++) n = (n * 31 + t.charCodeAt(i)) >>> 0;
                                return palette[n % palette.length];
                            }

                            labels.forEach(function(label, i) {
                                var h = (data[i] / maxVal) * chartH;
                                var tur = byDateTur.hasOwnProperty(label) ? byDateTur[label] : '';
                                var col = colorForTurCod(tur);
                                var turTxt = (tur && tur !== 'MIX') ? ('T' + tur) : (tur === 'MIX' ? 'MIX' : '');
                                barsHtml += '<div class="fm-bar-cell" title="' + (turTxt ? ('Turno: ' + escHtml(turTxt)) : '') + '"><div class="fm-bar-val" style="color:#0f172a;">' + data[i] + '</div><div class="fm-bar" style="height:' + h + 'px;background:' + col + ';"></div><div class="fm-bar-label">' + escHtml(label) + '</div></div>';
                            });

                            // Leyenda por color: TURN 1, TURN 2, ...
                            var turSet = {};
                            labels.forEach(function(label) {
                                var t = byDateTur.hasOwnProperty(label) ? byDateTur[label] : '';
                                t = String(t == null ? '' : t).trim();
                                if (t === '' || t === '0') return;
                                turSet[t] = true;
                            });
                            var turList = Object.keys(turSet).sort(function(a, b) {
                                var na = parseInt(a, 10), nb = parseInt(b, 10);
                                if (!isNaN(na) && !isNaN(nb)) return na - nb;
                                return String(a).localeCompare(String(b));
                            });
                            var legendHtml = '';
                            turList.forEach(function(t) {
                                var col = colorForTurCod(t);
                                var txt = (t === 'MIX') ? 'Turn. mix' : ('Turn. ' + t);
                                legendHtml += '<span class="fm-turn-key-item"><span class="fm-turn-key-swatch" style="background:' + col + ';"></span><span class="fm-turn-key-text">' + escHtml(txt) + '</span></span>';
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
                                '.fm-turn-key{display:flex;flex-wrap:wrap;gap:14px;align-items:center;margin:0 0 10px 0;}' +
                                '.fm-turn-key-item{display:inline-flex;align-items:center;gap:6px;}' +
                                '.fm-turn-key-swatch{width:12px;height:12px;border-radius:3px;border:1px solid rgba(15,23,42,.15);opacity:.55;filter:saturate(.65) brightness(.85);-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                                '.fm-turn-key-text{font-size:10px;font-weight:800;letter-spacing:.02em;color:#0f172a;}' +
                                '.fm-chart-area{display:flex;align-items:stretch;gap:8px; border: 1px solid #eee; padding: 10px; border-radius: 4px; background: #fff;}' +
                                '.fm-y-label{font-size:11px;color:#666;writing-mode:vertical-rl;transform:rotate(180deg);align-self:center;margin-right:5px; font-weight: bold;}' +
                                '.fm-chart-wrap{display:flex;align-items:flex-end;justify-content:flex-start;gap:2px;min-height:260px;padding:10px 0 85px; border-bottom:1px solid #ccc; flex:1; overflow-x: auto; scroll-behavior: smooth;}' +
                                '.fm-bar-cell{display:flex;flex-direction:column;align-items:center;flex:0 0 28px; position: relative;}' +
                                '.fm-bar-val{font-size:11px;font-weight:bold;margin-bottom:4px;}' +
                                '.fm-bar{width:24px;border-radius:3px 3px 0 0;min-height:2px;opacity:.55;filter:saturate(.65) brightness(.85);-webkit-print-color-adjust:exact;print-color-adjust:exact; transition: height 0.3s ease;}' +
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
                                (legendHtml ? ('<div class="fm-turn-key">' + legendHtml + '</div>') : '') +
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
                var $btn = $(this);
                if ($btn.data('printing')) return;
                $btn.data('printing', true).prop('disabled', true).addClass('disabled');

                var q = $.param({
                    reporte: 1,
                    Pla_Cod_Usuario: $('#Pla_Cod_Usuario').val() || '',
                    Num_Factura: $('#Num_Factura').val() || '',
                    Fec_Ini: $('#Fec_Ini').val() || '',
                    Fec_Fin: $('#Fec_Fin').val() || ''
                });
                // Sin abrir otra ventana: iframe oculto + impresión (Guardar como PDF / imprimir)
                var iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';
                document.body.appendChild(iframe);

                var unlock = function() {
                    $btn.data('printing', false).prop('disabled', false).removeClass('disabled');
                };

                var cleanup = function() {
                    try { document.body.removeChild(iframe); } catch (e) {}
                    unlock();
                };

                iframe.onload = function() {
                    try { iframe.contentWindow.onafterprint = cleanup; } catch (e) {}
                    setTimeout(function() {
                        try { iframe.contentWindow.focus(); } catch (e) {}
                        try { iframe.contentWindow.print(); } catch (e) {}
                        setTimeout(cleanup, 1500);
                    }, 150);
                };

                iframe.onerror = function() {
                    cleanup();
                };

                iframe.src = 'man_fac_man.php?' + q;
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
                options: [{
                        label: '&nbsp;&nbsp;Nombre planta / Cliente&nbsp;&nbsp;',
                        value: 'd'
                    },
                    {
                        label: '&nbsp;&nbsp;Cédula/R.U.C&nbsp;&nbsp;',
                        value: 'c'
                    }
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
                        name: 'num',
                        label: 'Nro',
                        hidden: false,
                        width: 40,
                        align: 'center',
                        formatter: function(cellvalue, options, rowObject) {
                            // options.rowId starts from 1 with local data, so display as is.
                            // If you want to show 1-based index regardless of sort/filter:
                            return options.rowId;
                        }
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
                        formatoptions: {
                            decimalPlaces: 2
                        }
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

            $('#btnCertFacSi').on('click', function() {
                $('#Cert_Fac_Firmar').prop('checked', true);
                $(this).addClass('btn-primary active').removeClass('btn-default');
                $('#btnCertFacNo').addClass('btn-default').removeClass('btn-primary active');
            });
            $('#btnCertFacNo').on('click', function() {
                $('#Cert_Fac_Firmar').prop('checked', false);
                $(this).addClass('btn-primary active').removeClass('btn-default');
                $('#btnCertFacSi').addClass('btn-default').removeClass('btn-primary active');
            });

            function showCertFacLoader(msg) {
                if (msg) {
                    $('#certFacLoaderMsg').text(msg);
                } else {
                    $('#certFacLoaderMsg').text('Generando certificado...');
                }
                $('body').addClass('cert-fac-loading');
                $('#certFacLoader').show();
            }

            function hideCertFacLoader() {
                $('body').removeClass('cert-fac-loading');
                $('#certFacLoader').hide();
            }

            function impCertificadoFactura() {
                var data = window._certFacData;
                var $btn = window._certFacBtn;
                if (!data || !data.vetCod || !$btn || !$btn.length) return;
                if ($btn.data('printing')) return;

                var firmar = $('#Cert_Fac_Firmar').is(':checked') ? 1 : 0;
                /* Siempre el mismo certificado HTML; firmar=1 añade bloque de firma (sin BORRADOR) */
                var url = 'man_rep_certificado_factura.php?embed=1&Vet_Cod=' + encodeURIComponent(data.vetCod) + '&firmar=' + firmar;
                var plaCod = $('#Pla_Cod_Usuario').val();
                if (plaCod) {
                    url += '&Pla_Cod_Usuario=' + encodeURIComponent(plaCod);
                }

                $('#modalCertificadoFirma').modal('hide');
                showCertFacLoader('Generando certificado...');

                var unlock = function() {
                    $btn.data('printing', false).prop('disabled', false).removeClass('disabled');
                };

                $btn.data('printing', true).prop('disabled', true).addClass('disabled');

                var iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';

                var finished = false;
                var finish = function() {
                    if (finished) return;
                    finished = true;
                    if (safetyTimer) clearTimeout(safetyTimer);
                    hideCertFacLoader();
                    try { document.body.removeChild(iframe); } catch (err) {}
                    unlock();
                };

                var safetyTimer = setTimeout(function() {
                    finish();
                }, 45000);

                iframe.onload = function() {
                    setTimeout(function() {
                        hideCertFacLoader();
                        var win = null;
                        try { win = iframe.contentWindow; } catch (err) {}
                        if (win) {
                            try { win.focus(); } catch (err) {}
                            try {
                                win.onafterprint = function() { finish(); };
                            } catch (err) {}
                            try { win.print(); } catch (err) { finish(); return; }
                        } else {
                            finish();
                            return;
                        }
                        setTimeout(finish, 2000);
                    }, 100);
                };
                iframe.onerror = finish;
                document.body.appendChild(iframe);
                iframe.src = url;
            }

            $('#btnCertFacGenerar').on('click', function() {
                impCertificadoFactura();
            });

            $('#btnExportarExcelMan').on('click', function() {
                var rows = window.currentManifestRows;
                if (!rows || rows.length === 0) return alert('No hay datos para exportar');

                var vetNum = window.currentVetNum;
                var cliente = window.currentCliente;
                var planta = window.currentPlanta;
                var fecha = window.currentFecha;

                var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                html += '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /><style>table td,table th{padding:3px 6px;font-size:11px;line-height:1.35;}</style></head><body>';

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
                html += '<th style="background-color: #eee;">#</th>';
                html += '<th style="background-color: #eee;">Nº Manifiesto</th>';
                html += '<th style="background-color: #eee;">Fecha</th>';
                html += '<th style="background-color: #eee;">Planta</th>';
                html += '<th style="background-color: #eee;">Cliente</th>';
                html += '<th style="background-color: #eee;">Peso(Tn)</th>';
                html += '<th style="background-color: #eee;">P.Unit.</th>';
                html += '<th style="background-color: #eee;">Total</th>';
                html += '</tr>';

                var sPes = 0,
                    sTot = 0;
                rows.forEach(function(r, i) {
                    html += '<tr>';
                    var manNumDisplay = (r.Pla_Cod && r.Man_Num !== undefined && r.Man_Num !== '') ? (function() {
                        var n = String(r.Man_Num);
                        while (n.length < 4) n = '0' + n;
                        return 'M' + r.Pla_Cod + '-' + n;
                    })() : (r.Man_Num || '');
                    html += '<td align="center">' + (i + 1) + '</td>';
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
                html += '<td colspan="5" style="background-color: #f9f9f9;"><strong>TOTALES</strong></td>';
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
                var $btn = $(this);
                if ($btn.data('printing')) return;
                var rows = window.currentManifestRows;
                if (!rows || rows.length === 0) return alert('No hay datos para imprimir');
                $btn.data('printing', true).prop('disabled', true).addClass('disabled');
                var vetNum = window.currentVetNum;
                var cliente = window.currentCliente;
                var planta = window.currentPlanta;
                var fecha = window.currentFecha;

                function escPdf(s) {
                    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                var empresa = (typeof MAN_FAC_EMPRESA !== 'undefined' && MAN_FAC_EMPRESA) ? String(MAN_FAC_EMPRESA) : 'Ecoparmining';
                var logoSrc = (typeof MAN_FAC_LOGO !== 'undefined' && MAN_FAC_LOGO) ? String(MAN_FAC_LOGO) : '';

                var html = '<html><head><meta charset="UTF-8"><title>' + escPdf(empresa) + ' — Manifiestos ' + escPdf(vetNum) + '</title>';
                html += '<style>';
                html += '*{box-sizing:border-box;}';
                html += 'body{margin:0;padding:14px 18px;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:13px;color:#1e293b;background:#f1f5f9;line-height:1.45;-webkit-print-color-adjust:exact;print-color-adjust:exact;}';
                html += '.report-wrap{max-width:100%;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(15,23,42,.06);overflow:hidden;}';
                // Encabezado más compacto (menos alto)
                html += '.report-header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;background:#fff;border-bottom:1px solid #e2e8f0;}';
                html += '.report-header .brand{flex:1;min-width:200px;padding-left:10px;border-left:3px solid #0f766e;}';
                html += '.report-header .brand h1{margin:0;font-size:1.1rem;font-weight:750;letter-spacing:-0.01em;color:#0f172a;line-height:1.1;}';
                html += '.report-header .brand .doc-type{margin:4px 0 0;font-size:0.62rem;font-weight:650;text-transform:uppercase;letter-spacing:0.12em;color:#64748b;}';
                html += '.report-header .logo-wrap{flex-shrink:0;display:flex;align-items:center;justify-content:flex-end;min-width:140px;}';
                // Logo sin marco y un poco más grande
                html += '.report-header .logo-frame{display:inline-flex;align-items:center;justify-content:center;padding:0;background:transparent;border:none;border-radius:0;box-shadow:none;}';
                html += '.report-header .logo-frame img{display:block;max-height:72px;max-width:200px;width:auto;height:auto;object-fit:contain;}';
                html += '.doc-meta{padding:16px 22px 8px;background:#fff;}';
                html += '.section-title{margin:0 0 10px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#64748b;}';
                html += 'table.meta-table{width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;font-size:12.5px;}';
                html += 'table.meta-table th,table.meta-table td{padding:3px 5px;border:1px solid #e2e8f0;vertical-align:top;}';
                html += 'table.meta-table th{width:22%;background:#f8fafc;color:#475569;font-weight:600;text-align:left;white-space:nowrap;}';
                html += 'table.meta-table td{background:#fff;color:#0f172a;font-weight:500;}';
                html += 'table.meta-table tr:nth-child(even) th{background:#f1f5f9;}';
                html += 'table.meta-table tr:nth-child(even) td{background:#fafbfc;}';
                html += '.table-block{padding:8px 22px 20px;background:#fff;}';
                // Forzar que los anchos definidos por columna se respeten
                html += 'table.data-table{width:100%;border-collapse:collapse;margin:0;font-size:11.5px;table-layout:fixed;}';
                html += 'table.data-table caption{caption-side:top;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;padding:14px 0 8px;}';
                html += 'table.data-table th,table.data-table td{border:1px solid #e2e8f0;padding:2px 4px;text-align:left;vertical-align:middle;overflow:hidden;text-overflow:ellipsis;}';
                html += 'table.data-table thead th{background:#fff;color:#0f172a;font-weight:700;font-size:11px;border-color:#e2e8f0;text-align:center;}';
                // Evitar que el encabezado se vea como "Pes..." / "P.u..." / "T..."
                html += 'table.data-table thead th{overflow:visible;text-overflow:clip;white-space:nowrap;font-size:10.5px;padding:2px 2px;}';
                html += 'table.data-table tbody tr:nth-child(even){background:#f8fafc;}';
                html += 'table.data-table tbody td.numeric{text-align:right;font-variant-numeric:tabular-nums;}';
                // Centrar valores en columnas: Peso (Tn), P. unit., Total (sobrescribe .numeric)
                html += 'table.data-table tbody td:nth-child(5),table.data-table tbody td:nth-child(6),table.data-table tbody td:nth-child(7){text-align:center !important;}';
                // Columnas de identificación más angostas: Nº Man, Fecha
                html += 'table.data-table th:nth-child(1),table.data-table td:nth-child(1){width:45px;white-space:nowrap;}';  // #
                html += 'table.data-table th:nth-child(2),table.data-table td:nth-child(2){width:70px;white-space:nowrap;}';  // Nº Man
                html += 'table.data-table th:nth-child(3),table.data-table td:nth-child(3){width:120px;white-space:nowrap;}';  // Fecha
                // Columnas numéricas más angostas: Peso(Tn), P.Unit, Total
                // (Los anchos muy pequeños hacían que el encabezado se corte)
                html += 'table.data-table th:nth-child(5),table.data-table td:nth-child(5){width:70px;white-space:nowrap;}';  // Peso (Tn)
                html += 'table.data-table th:nth-child(6),table.data-table td:nth-child(6){width:70px;white-space:nowrap;}';  // P. unit.
                // Hacer más ancha la columna Total para que no se corte la suma
                html += 'table.data-table th:nth-child(7),table.data-table td:nth-child(7){width:70px;white-space:nowrap;}';  // Total
                html += 'table.data-table tr.total-row td{background:#f1f5f9;font-weight:700;border-color:#cbd5e1;color:#0f172a;overflow:visible;text-overflow:clip;}';
                html += 'table.data-table tr.total-row td.numeric{text-align:right !important;}';
                // Márgenes de impresión más reducidos (PDF)
                // @page ayuda a reducir el margen real al “Guardar como PDF”.
                // Dejar margen suficiente para que el navegador dibuje su pie (numeración).
                // Evitamos márgenes “ultra mínimos” que pueden recortar el footer del navegador.
                // En pantalla ocultar HTML (solo impresión)
                html += '#printArea{display:none;}@media print{#printArea{display:block;}body{background:#fff;padding:0;}.report-wrap{border:none;box-shadow:none;border-radius:0;}.report-header{box-shadow:none;-webkit-print-color-adjust:exact;print-color-adjust:exact;}table.data-table thead th{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}@page{margin:10mm;}';
                html += '</style></head><body>';

                html += '<div id="printArea"><div class="report-wrap">';
                html += '<header class="report-header">';
                html += '<div class="brand"><h1>' + escPdf(empresa) + '</h1><p class="doc-type">Reporte de manifiestos</p></div>';
                if (logoSrc) {
                    html += '<div class="logo-wrap"><div class="logo-frame"><img src="' + escPdf(logoSrc) + '" alt="' + escPdf(empresa) + '"></div></div>';
                }
                html += '</header>';

                html += '<section class="doc-meta">';
                html += '<h2 class="section-title">Datos del documento</h2>';
                html += '<table class="meta-table" role="presentation">';
                html += '<tr><th>No. factura</th><td>' + escPdf(vetNum) + '</td></tr>';
                html += '<tr><th>Fecha factura</th><td>' + escPdf(fecha) + '</td></tr>';
                html += '<tr><th>Cliente</th><td>' + escPdf(cliente) + '</td></tr>';
                html += '<tr><th>Planta</th><td>' + escPdf(planta) + '</td></tr>';
                html += '<tr><th>Cant. manifiestos</th><td>' + escPdf(String(rows.length)) + '</td></tr>';
                html += '</table></section>';

                html += '<div class="table-block">';
                html += '<table class="data-table"><caption>Detalle de manifiestos</caption><thead><tr><th>#</th><th>Nº Man</th><th>Fecha</th><th>Planta</th><th>Peso (Tn)</th><th>P. unit.</th><th>Total</th></tr></thead><tbody>';
                var sPes = 0,
                    sTot = 0;
                rows.forEach(function(r, i) {
                    var manNumD = (r.Pla_Cod && r.Man_Num !== undefined && r.Man_Num !== '') ? (function() {
                        var n = String(r.Man_Num);
                        while (n.length < 4) n = '0' + n;
                        return 'M' + r.Pla_Cod + '-' + n;
                    })() : (r.Man_Num || '');
                    html += '<tr><td>' + escPdf(String(i + 1)) + '</td><td>' + escPdf(manNumD) + '</td><td>' + escPdf(r.Man_Fes) + '</td><td>' + escPdf(r.Pla_Nom || '') + '</td><td class="numeric">' + (parseFloat(r.Man_Pes).toFixed(2)) + '</td><td class="numeric">' + escPdf(String(r.Man_Pun != null ? r.Man_Pun : '')) + '</td><td class="numeric">' + escPdf(String(r.total != null ? r.total : '')) + '</td></tr>';
                    sPes += parseFloat(r.Man_Pes) || 0;
                    sTot += parseFloat(r.total) || 0;
                });
                html += '<tr class="total-row"><td colspan="4">TOTALES</td><td class="numeric">' + sPes.toFixed(2) + '</td><td></td><td class="numeric">' + sTot.toFixed(2) + '</td></tr>';
                html += '</tbody></table></div></div>';
                html += '</div>'; // #printArea
                html += '</body></html>';

                // Imprimir sin abrir otra ventana/pestaña: usar iframe oculto
                var iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';
                document.body.appendChild(iframe);

                var unlock = function() {
                    $btn.data('printing', false).prop('disabled', false).removeClass('disabled');
                };

                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();

                var cleanup = function() {
                    try { document.body.removeChild(iframe); } catch (e) {}
                    unlock();
                };

                // afterprint no siempre dispara en iframe según navegador; dejar fallback
                try { iframe.contentWindow.onafterprint = cleanup; } catch (e) {}
                setTimeout(function() {
                    try { iframe.contentWindow.focus(); } catch (e) {}
                    try { iframe.contentWindow.print(); } catch (e) {}
                    setTimeout(cleanup, 1500);
                }, 150);
            });

        });
    </script>
</body>

</html>