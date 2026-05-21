<?php
/**
 * Estado de Cuenta de Proveedores (CCxPP)
 * Kardex financiero: facturas, pagos aplicados y saldo acumulado cronológico.
 * Filtro por proveedor y rango de fechas. Exportación Excel. Totales al final.
 *
 * @package ccxpp
 */
require_once(__DIR__ . '/../../administrador/LOGICA/seguridad.php');
require_once(__DIR__ . '/../LOGICA/tes_log_estado_cuenta.php');
require_once(__DIR__ . '/../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Estado_Cuenta_Proveedor($Ses_Dat_Dis);
$obBD_con1    = new Class_Log_Datos_Estado_Cuenta_Proveedor();

// ----- AJAX: Listado de proveedores (para selector / búsqueda) -----
if (isset($_REQUEST['proveedoresAjax'])) {
    $params = array('searchPrv' => isset($_GET['searchPrv']) ? $_GET['searchPrv'] : '');
    $rows   = $obBD_con1->getArrayConsulta(1, $params, $obBD_conexion);
    $obBD_con1->utf8_change_param($rows);
    $resp = array('rows' => $rows, 'records' => count($rows));
    $obBD_con1->echoJson($resp);
    exit;
}

// ----- AJAX: Estado de cuenta (movimientos + saldo acumulado) -----
if (isset($_REQUEST['getEstadoCuentaProveedor'])) {
    $Prv_Cod     = isset($_POST['Prv_Cod']) ? intval($_POST['Prv_Cod']) : 0;
    $txt_fec_ini = isset($_POST['txt_fec_ini']) ? $_POST['txt_fec_ini'] : date('Y-01-01');
    $txt_fec_fin = isset($_POST['txt_fec_fin']) ? $_POST['txt_fec_fin'] : date('Y-m-d');

    $response = array('success' => false, 'rows' => array(), 'records' => 0, 'totals' => array('TOTAL' => 0, 'ABONO' => 0, 'SALDO' => 0), 'resumen' => array('Saldo_Total' => 0, 'Saldo_Vencido' => 0, 'Saldo_Por_Vencer' => 0));

    $params = array(
        'Prv_Cod'      => $Prv_Cod,
        'txt_fec_ini'  => $txt_fec_ini,
        'txt_fec_fin'  => $txt_fec_fin
    );
    $rows = $obBD_con1->getArrayConsulta(2, $params, $obBD_conexion);

    if ($obBD_con1->Error != 0) {
        $response['message'] = $obBD_con1->MsgError;
        $obBD_con1->echoJson($response);
        exit;
    }

    $alerta_ccpp_rows = $obBD_con1->getArrayConsulta(10, $params, $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $alerta_ccpp_rows = array();
    } else {
        $obBD_con1->utf8_change_param($alerta_ccpp_rows);
    }
    $obBD_con1->Error = 0;

    // Saldo vencido (facturas con fecha vencida que aún tienen saldo pendiente)
    $resumen_row = $obBD_con1->getRowConsulta(3, $params, $obBD_conexion);
    $saldo_vencido = isset($resumen_row['Saldo_Vencido']) ? floatval($resumen_row['Saldo_Vencido']) : 0;

    // Saldo Inicial (antes de fec_ini)
    $ini_row = $obBD_con1->getRowConsulta(4, $params, $obBD_conexion);
    $saldo_inicial = isset($ini_row['Saldo_Inicial']) ? floatval($ini_row['Saldo_Inicial']) : 0;

    // Calcular saldo acumulado: factura suma, pago/abono resta. Nota Crédito resta.
    $saldo = $saldo_inicial;
    $sum_total = 0.0;
    $sum_abono = 0.0;
    foreach ($rows as &$r) {
        $total = isset($r['TOTAL']) ? floatval($r['TOTAL']) : 0;
        $abono = isset($r['ABONO']) ? floatval($r['ABONO']) : 0;
        $tipo  = isset($r['Tipo']) ? trim($r['Tipo']) : '';
        if ($tipo === 'Nota Crédito') {
            $saldo -= $total;
            $sum_total -= $total;
        } else {
            $saldo += $total - $abono;
            $sum_total += $total;
            $sum_abono += $abono;
        }
        $r['SALDO'] = round($saldo, 2);
        $r['TOTAL'] = round($total, 2);
        $r['ABONO'] = round($abono, 2);
        // Construir Num_Ref desde los campos raw del SQL
        $che_raw  = isset($r['Num_Che_raw']) && $r['Num_Che_raw'] !== '' ? 'Che: ' . $r['Num_Che_raw'] : '';
        $doc_raw  = isset($r['Num_Doc_raw']) && $r['Num_Doc_raw'] !== '' && $r['Num_Doc_raw'] !== '0' ? 'Transf: ' . $r['Num_Doc_raw'] : '';
        $partes   = array_filter(array($che_raw, $doc_raw));
        $r['Num_Ref'] = implode(' / ', $partes);
        // Dias_Vencimiento: ya viene del SQL (positivo = vencido hace X días, negativo = vence en X días)
        if (array_key_exists('Dias_Vencimiento', $r) && $r['Dias_Vencimiento'] !== null) {
            $r['Dias_Vencimiento'] = intval($r['Dias_Vencimiento']);
        }
        // Estado_Color: verde=pagada, amarillo=pago parcial, rojo=vencida, celeste=pago, gris=por vencer
        if ($tipo === 'Pago') {
            $r['Estado_Color'] = 'celeste';
        } elseif ($tipo === 'Factura' || $tipo === 'Nota Crédito') {
            $abono_fact = isset($r['Abono_Factura']) ? floatval($r['Abono_Factura']) : 0;
            $dias = isset($r['Dias_Vencimiento']) ? intval($r['Dias_Vencimiento']) : 0;
            if ($abono_fact >= $total) {
                $r['Estado_Color'] = 'verde';
                $r['Dias_Vencimiento'] = ''; // Factura pagada: días venc. vacío
            } elseif ($abono_fact > 0) {
                $r['Estado_Color'] = 'amarillo';
            } elseif ($dias > 0) {
                $r['Estado_Color'] = 'rojo';
            } else {
                $r['Estado_Color'] = 'gris'; // Por vencer: gris claro
            }
        } else {
            $r['Estado_Color'] = 'gris';
        }
    }
    unset($r);

    // Filtro por Tipo de fila (Factura / Pago)
    $filter_tipo_row = isset($_POST['filter_tipo_row']) ? trim($_POST['filter_tipo_row']) : '';
    if ($filter_tipo_row !== '') {
        foreach ($rows as $key => $r) {
            $tipo = isset($r['Tipo']) ? $r['Tipo'] : '';
            if ($filter_tipo_row === 'Factura' && $tipo !== 'Factura' && $tipo !== 'Nota Crédito' && $tipo !== 'Saldo Inicial') {
                unset($rows[$key]);
            } elseif ($filter_tipo_row === 'Pago' && $tipo !== 'Pago' && $tipo !== 'Saldo Inicial') {
                unset($rows[$key]);
            }
        }
        $rows = array_values($rows);
    }

    // Filtro por tipo de pago (se aplica DESPUÉS de calcular saldo para no alterar totales)
    $filter_tipo_pago = isset($_POST['filter_tipo_pago']) ? trim($_POST['filter_tipo_pago']) : '';
    if ($filter_tipo_pago !== '' && $filter_tipo_pago !== 'todos') {
        $tipos_permitidos = array_map('trim', explode(',', $filter_tipo_pago));
        foreach ($rows as $key => $r) {
            $tipo = isset($r['Tipo']) ? $r['Tipo'] : '';
            if ($tipo === 'Pago') {
                $tipo_row = isset($r['Tipo_Pago']) ? trim($r['Tipo_Pago']) : '';
                if ($tipo_row === '') { unset($rows[$key]); continue; }
                $match = false;
                foreach ($tipos_permitidos as $tp) {
                    if (strpos($tipo_row, trim($tp)) !== false) { $match = true; break; }
                }
                if (!$match) unset($rows[$key]);
            } elseif ($tipo === 'Factura' || $tipo === 'Nota Crédito' || $tipo === 'Saldo Inicial') {
                unset($rows[$key]);
            }
        }
        $rows = array_values($rows);
    }

    // Agregar fila de Saldo Inicial al principio del array
    $fila_inicial = array(
        'Com_Codigo' => '',
        'Fecha_Emision' => $txt_fec_ini,
        'Fecha_Venc' => '',
        'Dias_Vencimiento' => '',
        'Tipo' => 'Saldo Inicial',
        'Documento' => 'SALDO ACUMULADO AL ' . $txt_fec_ini,
        'Cuenta_Bancaria' => '',
        'Fecha_Cheque' => '',
        'TOTAL' => 0.00,
        'ABONO' => 0.00,
        'SALDO' => round($saldo_inicial, 2),
        'Estado_Color' => 'gris',
        'Proveedor' => ($Prv_Cod <= 0 ? 'TODOS LOS PROVEEDORES' : '')
    );
    if ($filter_tipo_pago === '' || $filter_tipo_pago === 'todos') {
        array_unshift($rows, $fila_inicial);
    }

    $obBD_con1->utf8_change_param($rows);

    $response['success']  = true;
    $response['rows']    = $rows;
    $response['records'] = count($rows);
    $response['totals']  = array(
        'TOTAL' => round($sum_total, 2),
        'ABONO'  => round($sum_abono, 2),
        'SALDO'  => round($saldo, 2)
    );
    $response['resumen'] = array(
        'Saldo_Total'     => round($saldo, 2),
        'Saldo_Vencido'   => round($saldo_vencido, 2),
        'Saldo_Por_Vencer' => round($saldo - $saldo_vencido, 2)
    );
    $response['alerta_ccpp'] = array(
        'count' => count($alerta_ccpp_rows),
        'rows'  => $alerta_ccpp_rows
    );
    $obBD_con1->echoJson($response);
    exit;
}

// ----- AJAX: Exportar a Excel (HTML para blob) -----
if (isset($_REQUEST['exportExcelEstadoCuenta'])) {
    $Prv_Cod     = isset($_POST['Prv_Cod']) ? intval($_POST['Prv_Cod']) : 0;
    $txt_fec_ini = isset($_POST['txt_fec_ini']) ? $_POST['txt_fec_ini'] : date('Y-01-01');
    $txt_fec_fin = isset($_POST['txt_fec_fin']) ? $_POST['txt_fec_fin'] : date('Y-m-d');
    $nombre_prov = isset($_POST['nombre_proveedor']) ? $_POST['nombre_proveedor'] : '';

    $response = array('success' => false, 'html' => '');

    $params = array('Prv_Cod' => $Prv_Cod, 'txt_fec_ini' => $txt_fec_ini, 'txt_fec_fin' => $txt_fec_fin);
    $rows = $obBD_con1->getArrayConsulta(2, $params, $obBD_conexion);
    if ($obBD_con1->Error != 0) {
        $response['message'] = $obBD_con1->MsgError;
        $obBD_con1->echoJson($response);
        exit;
    }

    // Saldo Inicial (antes de fec_ini)
    $ini_row = $obBD_con1->getRowConsulta(4, $params, $obBD_conexion);
    $saldo_inicial = isset($ini_row['Saldo_Inicial']) ? floatval($ini_row['Saldo_Inicial']) : 0;

    $saldo = $saldo_inicial;
    $sum_total = 0.0;
    $sum_abono = 0.0;

    $body = '<tr><td></td><td>' . $txt_fec_ini . '</td><td></td><td>Saldo Inicial</td><td>SALDO ACUMULADO AL ' . $txt_fec_ini . '</td><td></td><td></td><td></td><td></td><td style="text-align:right">0.00</td><td style="text-align:right">0.00</td><td style="text-align:right">' . number_format(round($saldo_inicial, 2), 2, '.', ',') . '</td></tr>';
    
    foreach ($rows as $r) {
        $total = isset($r['TOTAL']) ? floatval($r['TOTAL']) : 0;
        $abono = isset($r['ABONO']) ? floatval($r['ABONO']) : 0;
        $tipo  = isset($r['Tipo']) ? trim($r['Tipo']) : '';
        if ($tipo === 'Nota Crédito') {
            $saldo -= $total;
            $sum_total -= $total;
        } else {
            $saldo += $total - $abono;
            $sum_total += $total;
            $sum_abono += $abono;
        }
        $fec_cheque = isset($r['Fecha_Cheque']) && $r['Fecha_Cheque'] !== null && $r['Fecha_Cheque'] !== '' ? $r['Fecha_Cheque'] : '';
        $che_raw_ex = isset($r['Num_Che_raw']) && $r['Num_Che_raw'] !== '' ? 'Che: ' . $r['Num_Che_raw'] : '';
        $doc_raw_ex = isset($r['Num_Doc_raw']) && $r['Num_Doc_raw'] !== '' && $r['Num_Doc_raw'] !== '0' ? 'Transf: ' . $r['Num_Doc_raw'] : '';
        $partes_ex  = array_filter(array($che_raw_ex, $doc_raw_ex));
        $num_ref    = implode(' / ', $partes_ex);
        $body .= '<tr><td>' . htmlspecialchars(isset($r['Com_Codigo']) ? $r['Com_Codigo'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Fecha_Emision']) ? $r['Fecha_Emision'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Fecha_Venc']) ? $r['Fecha_Venc'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars($tipo) . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Documento']) ? $r['Documento'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Cuenta_Bancaria']) ? $r['Cuenta_Bancaria'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars($fec_cheque) . '</td>';
        $body .= '<td style="text-align:center">' . htmlspecialchars($num_ref) . '</td>';
        $body .= '<td style="text-align:center">' . htmlspecialchars(isset($r['Tipo_Pago']) ? $r['Tipo_Pago'] : '') . '</td>';
        $body .= '<td style="text-align:right">' . number_format($total, 2, '.', ',') . '</td>';
        $body .= '<td style="text-align:right">' . number_format($abono, 2, '.', ',') . '</td>';
        $body .= '<td style="text-align:right">' . number_format(round($saldo, 2), 2, '.', ',') . '</td></tr>';
    }
    $body .= '<tr style="font-weight:bold;border-top:2px solid #000"><td colspan="9" style="text-align:right">TOTALES:</td>';
    $body .= '<td style="text-align:right">' . number_format(round($sum_total, 2), 2, '.', ',') . '</td>';
    $body .= '<td style="text-align:right">' . number_format(round($sum_abono, 2), 2, '.', ',') . '</td>';
    $body .= '<td style="text-align:right">' . number_format(round($saldo, 2), 2, '.', ',') . '</td></tr>';

    $response['success'] = true;
    $response['html']    = $body;
    $response['titulo']  = 'Estado de Cuenta - ' . $nombre_prov . ' (' . $txt_fec_ini . ' / ' . $txt_fec_fin . ')';
    $obBD_con1->echoJson($response);
    exit;
}

// ----- AJAX: Detalle de un pago o factura (asientos, cheques/pagos, facturas) -----
if (isset($_REQUEST['getAsientosAbono'])) {
    $Com_Cod = isset($_POST['Com_Cod']) ? intval($_POST['Com_Cod']) : 0;
    $Cpp_Cod = isset($_POST['Cpp_Cod']) ? intval($_POST['Cpp_Cod']) : 0;
    $response = array('success' => false, 'message' => 'Error al obtener detalle');
    $response['data']       = $obBD_con1->getArrayConsulta(5, array('Com_Cod' => $Com_Cod), $obBD_conexion);
    $response['data_che']   = $obBD_con1->getArrayConsulta(6, array('Com_Cod' => $Com_Cod), $obBD_conexion);
    $response['data_facts'] = $obBD_con1->getArrayConsulta(7, array('Com_Cod' => $Com_Cod), $obBD_conexion);
    // Si es una factura, también traer sus pagos aplicados
    if ($Cpp_Cod > 0) {
        $response['data_pagos'] = $obBD_con1->getArrayConsulta(9, array('Cpp_Cod' => $Cpp_Cod), $obBD_conexion);
    } else {
        $response['data_pagos'] = array();
    }
    if ($obBD_con1->Error == 0) { $response['success'] = true; }
    $obBD_con1->echoJson($response);
    exit;
}

// ----- Vista -----
$Ses_Suc_Cod   = isset($_SESSION['Ses_Suc_Cod'])  ? $_SESSION['Ses_Suc_Cod']  : '';
$Ses_Emp_Nom   = isset($_SESSION['Ses_Emp_Nom'])   ? $_SESSION['Ses_Emp_Nom']   : '';
$tipos_pago_rows = $obBD_con1->getArrayConsulta(8, array(), $obBD_conexion);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta de Proveedores - CCxPP</title>
    <?php require_once(__DIR__ . '/../../mascaras/model1/estilos/jqgrid5.php'); ?>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script src="../VALIDACIONES/tes_val_estado_cuenta.js?e=11"></script>
    <style>
        .exa-fieldset { border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; }
        .form-group-compact { margin-bottom: 8px !important; }
        .Titulos2 { font-weight: bold; margin-bottom: 8px; }
        /* Colores estado de cuenta: solo celdas de datos; columna # = mismo th del encabezado (JS) */
        #searchGrid tr.jqgrow.cell-verde td:not(.jqgrid-rownum)   { background: #c8e6c9 !important; }
        #searchGrid tr.jqgrow.cell-amarillo td:not(.jqgrid-rownum) { background: #fff9c4 !important; }
        #searchGrid tr.jqgrow.cell-rojo td:not(.jqgrid-rownum) { background: #ffcdd2 !important; color: #000 !important; font-weight: normal; }
        #searchGrid tr.jqgrow.cell-celeste td:not(.jqgrid-rownum) { background: #b3e5fc !important; }
        #searchGrid tr.jqgrow.cell-gris td:not(.jqgrid-rownum) { background: transparent !important; color: #000 !important; }
        /* Columna #: color copiado del th del encabezado vía syncJqGridRownumWithHeader() en loadComplete */
        .panel-resumen { margin-bottom: 5px; }
        .panel-resumen .panel-heading { font-size: 11px; padding: 5px 10px; }
        .panel-resumen .panel-body { padding: 8px 15px; }
        .resumen-monto { font-size: 16px; font-weight: bold; }
        .resumen-vencido { color: #c62828; }
        .input-group-addon-blue { background-color: #d9edf7 !important; color: #31708f !important; font-weight: bold; }
        /* Modal Detalle del Pago: acciones alineadas */
        #verPagoDialog .vp-detalle-acciones-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 22px;
        }
        @media (max-width: 991px) {
            #verPagoDialog .vp-detalle-acciones-inner {
                justify-content: flex-start;
                padding-top: 0;
                margin-top: 8px;
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Estado de Cuenta de Proveedores</h3></div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <form name="formEstadoCuenta" id="formEstadoCuenta" class="form-horizontal normal" action="javascript:void(0);">
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seleccionar Proveedor</legend>
                            <div class="form-group form-group-compact">
                                <label class="col-sm-3 control-label label-sm">C&eacute;dula/RUC:</label>
                                <div class="col-sm-9">
                                    <input name="Prv_Cod" id="Prv_Cod" type="hidden" />
                                    <div class="input-group input-group-xs">
                                        <input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione proveedor..." class="form-control input-xs" readonly />
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-success btn-xs" onclick="$('#proveedoresDialog').dialog('open');" title="Seleccionar"><span class="glyphicon glyphicon-search"></span></button>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="limpiarProveedor();" title="Quitar"><span class="glyphicon glyphicon-remove"></span></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group form-group-compact">
                                <label class="col-sm-3 control-label label-xs">Proveedor:</label>
                                <div class="col-sm-9"><input name="nombre" id="nombre" class="form-control input-xs" readonly /></div>
                            </div>
                            <div class="form-group form-group-compact">
                                <label class="col-sm-3 control-label label-xs">Direcci&oacute;n:</label>
                                <div class="col-sm-9"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs" readonly /></div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Filtro por rango de fechas</legend>
                            <div class="form-group form-group-compact">
                                <label class="col-xs-2 control-label">Rango:</label>
                                <div class="col-xs-8">
                                    <div class="input-group input-group-xs">
                                        <span class="input-group-addon input-group-addon-blue" style="height: 30px; vertical-align: middle;">Desde</span>
                                        <input name="txt_fec_ini" id="txt_fec_ini" type="text" class="form-control input-xs datepicker" style="text-align: center; height: 30px;" value="<?php echo date('Y-01-01'); ?>" />
                                        <span class="input-group-addon input-group-addon-blue" style="height: 30px; vertical-align: middle;">Hasta</span>
                                        <input name="txt_fec_fin" id="txt_fec_fin" type="text" class="form-control input-xs datepicker" style="text-align: center; height: 30px;" value="<?php echo date('Y-m-d'); ?>" />
                                    </div>
                                </div>
                                <div class="col-xs-2">
                                    <button type="button" class="btn btn-success btn-sm" id="btnBuscar" title="Buscar"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                </div>
                            </div>
                            <!-- Filtros adicionales -->
                            <div class="form-group form-group-compact" style="margin-top:6px;">
                                <label class="col-xs-2 control-label label-xs">Tipo:</label>
                                <div class="col-xs-10" style="padding-top:4px;">
                                    <label style="font-size:11px; margin-right:14px; font-weight:normal; cursor:pointer;">
                                        <input type="radio" name="filter_tipo_row" id="ftr_todos" value="" checked onchange="aplicarFiltroTipo()">&nbsp;Todos
                                    </label>
                                    <label style="font-size:11px; margin-right:14px; font-weight:normal; cursor:pointer;">
                                        <input type="radio" name="filter_tipo_row" id="ftr_fact" value="Factura" onchange="aplicarFiltroTipo()">&nbsp;Facturas
                                    </label>
                                    <label style="font-size:11px; font-weight:normal; cursor:pointer;">
                                        <input type="radio" name="filter_tipo_row" id="ftr_pago" value="Pago" onchange="aplicarFiltroTipo()">&nbsp;Pagos
                                    </label>
                                </div>
                            </div>
                            <div class="form-group form-group-compact">
                                <label class="col-xs-2 control-label label-xs">T. Pago:</label>
                                <div class="col-xs-10">
                                    <select id="sel_tipo_pago_multi" multiple style="width:100%;">
                                        <?php foreach ($tipos_pago_rows as $tp): ?>
                                        <option value="<?php echo htmlspecialchars($tp['Pag_Des']); ?>"><?php echo htmlspecialchars($tp['Pag_Des']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="filter_tipo_pago" name="filter_tipo_pago" value="" />
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </form>
            </div>
            <!-- Tarjetas resumen (centradas) -->
            <div class="row" id="resumenEstadoCuenta" style="display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-bottom: 5px;">
                <div style="width: 250px;">
                    <div class="panel panel-default panel-resumen">
                        <div class="panel-heading"><strong>Saldo general</strong></div>
                        <div class="panel-body text-right">
                            <span id="resumenSaldoTotal" class="resumen-monto">$ 0.00</span>
                        </div>
                    </div>
                </div>
                <div style="width: 250px;">
                    <div class="panel panel-danger panel-resumen">
                        <div class="panel-heading"><strong>Saldo vencido</strong></div>
                        <div class="panel-body text-right">
                            <span id="resumenSaldoVencido" class="resumen-monto resumen-vencido">$ 0.00</span>
                        </div>
                    </div>
                </div>
                <div style="width: 250px;">
                    <div class="panel panel-info panel-resumen">
                        <div class="panel-heading"><strong>Saldo por vencer</strong></div>
                        <div class="panel-body text-right">
                            <span id="resumenSaldoPorVencer" class="resumen-monto" style="color: #31708f;">$ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div id="ec_alerta_prove_varios" class="alert alert-warning" style="display:none;margin-bottom:10px;padding:10px 14px;font-size:12px;">
                        <span class="glyphicon glyphicon-warning-sign"></span>
                        Facturas a cr&eacute;dito con cuenta diferente a <strong>Proveedores varios</strong> (cuenta del Haber no registrada en CxP proveedores / <code>ccpp_prove</code>); no se incluyen en este estado de cuenta:
                        <a href="#" id="ec_alerta_prove_varios_num" class="badge alert-danger" style="font-size:13px;cursor:pointer;vertical-align:middle;margin-left:4px;" title="">0</a>
                        <span class="text-muted" style="margin-left:6px;">Pase el cursor o haga clic en el n&uacute;mero para ver el detalle.</span>
                    </div>
                    <table id="searchGrid" name="searchGrid"></table>
                    <div id="pag_sg"></div>
                    <div class="Titulos2">
                        <span><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop" style="color:#2e7d32;"></span> Factura pagada &nbsp; <span class="glyphicon glyphicon-stop" style="color:#f9a825;"></span> Pago parcial &nbsp; <span class="glyphicon glyphicon-stop" style="color:#c62828;"></span> Factura vencida &nbsp; <span class="glyphicon glyphicon-stop" style="color:#0288d1;"></span> Pago &nbsp; <span title="Sin color de fondo" style="display:inline-block;width:11px;height:11px;border:2px solid #757575;border-radius:1px;vertical-align:text-bottom;background:transparent;"></span> Facturas por vencer</span>
                    </div>
                    <br>
                    <div style="text-align: center; margin-top: 15px; padding: 10px; background-color: #f5f5f5; border-radius: 4px; border: 1px solid #ddd;">
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="imprimirEstadoCuenta();" title="Imprimir estado de cuenta">
                                <span class="glyphicon glyphicon-print"></span> Imprimir
                            </button>
                            <button type="button" class="btn btn-sm btn-success" style="margin-left: 8px;" onclick="exportarExcel();" title="Exportar a Excel">
                                <span class="glyphicon glyphicon-export"></span> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="proveedoresDialog" title="Búsqueda de Proveedores"><form class="form-horizontal normal"></form></div>

    <div id="verPagoDialog" title="Detalle del Pago">
        <div class="row">
            <div class="col-sm-12">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Datos del Abono</legend>
                    <form id="verPagoForm" class="form-horizontal normal">
                        <div class="row">
                            <div class="col-xs-12 col-md-9">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Proveedor:</label>
                                            <div class="col-xs-8"><input type="text" id="vp_prov" class="form-control input-xs" readonly></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">No. Compr.:</label>
                                            <div class="col-xs-8"><input type="text" id="vp_compr" class="form-control input-xs" readonly></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-8"><input type="text" id="vp_fec" class="form-control input-xs" readonly></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-4 control-label label-xs">Observaci&oacute;n:</label>
                                            <div class="col-xs-8"><input type="text" id="vp_obs" class="form-control input-xs" readonly></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-3 vp-detalle-acciones">
                                <div class="vp-detalle-acciones-inner">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="imprimirDetallePago()" title="Imprimir detalle">
                                        <span class="glyphicon glyphicon-print"></span>
                                    </button>
                                    <button type="button" id="btnDescargarPdf" class="btn btn-sm btn-danger" onclick="descargarPdfFactura()" title="Descargar PDF de la factura" style="display:none;">
                                        <span class="glyphicon glyphicon-download-alt"></span>&nbsp;PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </fieldset>
            </div>
        </div>
        <div class="row" style="margin-top:5px;">
            <div class="col-sm-12">
                <div id="vp_tabs" class="ui-tab-fix">
                    <ul style="font-size:12px;" role="tablist">
                        <li><a href="#vp_tab_asi">Asientos</a></li>
                        <li id="vp_li_che"><a href="#vp_tab_che">Cheques</a></li>
                        <li id="vp_li_pag"><a href="#vp_tab_pag">Pagos aplicados</a></li>
                        <li id="vp_li_fact"><a href="#vp_tab_fact">Facturas</a></li>
                    </ul>
                    <div id="vp_tab_asi"><div class="row"><div class="col-sm-12" style="padding-top:8px;"><table id="vpGridAsi" name="vpGridAsi"></table></div></div></div>
                    <div id="vp_tab_che"><div class="row"><div class="col-sm-12" style="padding-top:8px;"><table id="vpGridChe" name="vpGridChe"></table></div></div></div>
                    <div id="vp_tab_pag"><div class="row"><div class="col-sm-12" style="padding-top:8px;"><table id="vpGridPag" name="vpGridPag"></table></div></div></div>
                    <div id="vp_tab_fact"><div class="row"><div class="col-sm-12" style="padding-top:8px;"><table id="vpGridFact" name="vpGridFact"></table></div></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor oculto para imprimir detalle pago -->
    <div id="print_detalle_pago" style="display:none;">
        <div style="text-align:center; margin-bottom:8px;">
            <h4 style="margin:0;"><b>DETALLE DEL PAGO</b></h4>
            <span style="font-size:13px;"><?php echo isset($Ses_Emp_Nom) ? $Ses_Emp_Nom : ''; ?></span>
        </div>
        <table style="width:100%; font-size:12px; margin-bottom:8px;">
            <tr>
                <td><b>Proveedor:</b> <span id="vp_prov_print"></span></td>
                <td><b>Fecha:</b> <span id="vp_fec_print"></span></td>
            </tr>
            <tr>
                <td><b>No. Compr.:</b> <span id="vp_compr_print"></span></td>
                <td><b>Observaci&oacute;n:</b> <span id="vp_obs_print"></span></td>
            </tr>
        </table>
        <div id="vp_print_asi_wrap">
            <b>Asientos:</b>
            <table id="vp_print_asi" style="width:100%; border-collapse:collapse; font-size:11px;" border="1" cellpadding="3">
                <thead><tr><th>C&oacute;digo</th><th>Cuenta</th><th>Glosa</th><th style="text-align:right;">Debe</th><th style="text-align:right;">Haber</th></tr></thead>
                <tbody id="vp_print_asi_body"></tbody>
                <tfoot><tr><td colspan="3" style="text-align:right;"><b>TOTALES:</b></td><td style="text-align:right;" id="vp_print_debe_tot"></td><td style="text-align:right;" id="vp_print_haber_tot"></td></tr></tfoot>
            </table>
        </div>
        <div id="vp_print_che_wrap" style="margin-top:8px;">
            <b>Cheques:</b>
            <table id="vp_print_che" style="width:100%; border-collapse:collapse; font-size:11px;" border="1" cellpadding="3">
                <thead><tr><th>No. Cheque</th><th>Fecha</th><th style="text-align:right;">Valor</th><th>Observaci&oacute;n</th></tr></thead>
                <tbody id="vp_print_che_body"></tbody>
            </table>
        </div>
        <div id="vp_print_fact_wrap" style="margin-top:8px;">
            <b>Facturas incluidas:</b>
            <table style="width:100%; border-collapse:collapse; font-size:11px;" border="1" cellpadding="3">
                <thead><tr><th>No. Factura</th><th>Fecha Emis.</th><th>Fecha Venc.</th><th style="text-align:right;">Total</th><th style="text-align:right;">Pagado</th></tr></thead>
                <tbody id="vp_print_fact_body"></tbody>
                <tfoot><tr><td colspan="3" style="text-align:right;"><b>TOTALES:</b></td><td style="text-align:right;" id="vp_print_fact_tot"></td><td style="text-align:right;" id="vp_print_fact_pag_tot"></td></tr></tfoot>
            </table>
        </div>
    </div>

    <!-- Contenedor oculto: impresi&oacute;n completa (reporte + filtros + resumen + tabla) -->
    <div id="print_estado_cuenta" style="display:none;">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'Estado de Cuenta de Proveedores', 'Kardex por proveedor', $obBD_conexion, false, 10); ?>
        <div id="ec_print_filtros" style="font-size:11px;margin:8px 0;line-height:1.4;"></div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:10px;" border="1" cellpadding="4">
            <tr>
                <td style="background:#f5f5f5;width:33.33%;"><strong>Saldo general</strong><br /><span id="ec_print_res_total"></span></td>
                <td style="background:#f2dede;width:33.33%;"><strong>Saldo vencido</strong><br /><span id="ec_print_res_venc" style="color:#c62828;"></span></td>
                <td style="background:#d9edf7;width:33.33%;"><strong>Saldo por vencer</strong><br /><span id="ec_print_res_pvenc" style="color:#31708f;"></span></td>
            </tr>
        </table>
        <table id="ec_print_tabla" class="tablaReporte" cellspacing="0" cellpadding="3" border="1" style="width:100%;border-collapse:collapse;font-size:10px;">
            <thead id="ec_print_thead"></thead>
            <tbody id="ec_print_body"></tbody>
            <tfoot id="ec_print_tfoot"></tfoot>
        </table>
        <p style="font-size:9px;margin-top:8px;color:#333;">
            <strong>Leyenda:</strong> verde = Factura pagada; amarillo = Pago parcial; rojo = Factura vencida; celeste = Pago; sin fondo = Facturas por vencer.
        </p>
    </div>

    <!-- Contenedor oculto para exportar Excel -->
    <div id="exportar" style="display:none">
        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'Estado de Cuenta de Proveedores', 'Kardex por proveedor', $obBD_conexion, false, 10); ?>
        <table class="tablaReporte" cellspacing="0" cellpadding="2" border="1" style="width:100%; border-collapse:collapse; font-size:11px;">
            <thead>
                <tr>
                    <th># Compr.</th>
                    <th>Fecha Emisión</th>
                    <th>Fecha Venc.</th>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Cuenta Bancaria</th>
                    <th>Fecha Cheque</th>
                    <th>Che / Transf</th>
                    <th>T. Pago</th>
                    <th>TOTAL</th>
                    <th>ABONO</th>
                    <th>SALDO</th>
                </tr>
            </thead>
            <tbody id="tabla_export_ex"></tbody>
        </table>
    </div>
</body>
</html>
