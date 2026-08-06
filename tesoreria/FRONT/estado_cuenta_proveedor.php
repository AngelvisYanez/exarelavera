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

    if ($Prv_Cod <= 0) {
        $response['message'] = 'Seleccione un proveedor.';
        $obBD_con1->echoJson($response);
        exit;
    }

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
        'Estado_Color' => 'gris'
    );
    array_unshift($rows, $fila_inicial);

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

    if ($Prv_Cod <= 0) {
        $response['message'] = 'Seleccione un proveedor.';
        $obBD_con1->echoJson($response);
        exit;
    }

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

    $body = '<tr><td></td><td>' . $txt_fec_ini . '</td><td></td><td>Saldo Inicial</td><td>SALDO ACUMULADO AL ' . $txt_fec_ini . '</td><td></td><td></td><td style="text-align:right">0.00</td><td style="text-align:right">0.00</td><td style="text-align:right">' . number_format(round($saldo_inicial, 2), 2, '.', ',') . '</td></tr>';
    
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
        $body .= '<tr><td>' . htmlspecialchars(isset($r['Com_Codigo']) ? $r['Com_Codigo'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Fecha_Emision']) ? $r['Fecha_Emision'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Fecha_Venc']) ? $r['Fecha_Venc'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars($tipo) . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Documento']) ? $r['Documento'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars(isset($r['Cuenta_Bancaria']) ? $r['Cuenta_Bancaria'] : '') . '</td>';
        $body .= '<td>' . htmlspecialchars($fec_cheque) . '</td>';
        $body .= '<td style="text-align:right">' . number_format($total, 2, '.', ',') . '</td>';
        $body .= '<td style="text-align:right">' . number_format($abono, 2, '.', ',') . '</td>';
        $body .= '<td style="text-align:right">' . number_format(round($saldo, 2), 2, '.', ',') . '</td></tr>';
    }
    $body .= '<tr style="font-weight:bold;border-top:2px solid #000"><td colspan="7" style="text-align:right">TOTALES:</td>';
    $body .= '<td style="text-align:right">' . number_format(round($sum_total, 2), 2, '.', ',') . '</td>';
    $body .= '<td style="text-align:right">' . number_format(round($sum_abono, 2), 2, '.', ',') . '</td>';
    $body .= '<td style="text-align:right">' . number_format(round($saldo, 2), 2, '.', ',') . '</td></tr>';

    $response['success'] = true;
    $response['html']    = $body;
    $response['titulo']  = 'Estado de Cuenta - ' . $nombre_prov . ' (' . $txt_fec_ini . ' / ' . $txt_fec_fin . ')';
    $obBD_con1->echoJson($response);
    exit;
}

// ----- Vista -----
$Ses_Suc_Cod = isset($_SESSION['Ses_Suc_Cod']) ? $_SESSION['Ses_Suc_Cod'] : '';
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
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
    <script src="../VALIDACIONES/tes_val_estado_cuenta.js?e=1"></script>
    <style>
        .exa-fieldset { border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; }
        .form-group-compact { margin-bottom: 8px !important; }
        .Titulos2 { font-weight: bold; margin-bottom: 8px; }
        /* Colores estado de cuenta: verde=pagada, amarillo=pago parcial, rojo=vencida, celeste=pago, gris=por vencer */
        #searchGrid .cell-verde   { background: #c8e6c9 !important; }
        #searchGrid .cell-amarillo { background: #fff9c4 !important; }
        #searchGrid .cell-rojo    { background: #ffcdd2 !important; color: #b71c1c; font-weight: bold; }
        #searchGrid .cell-celeste { background: #b3e5fc !important; }
        #searchGrid .cell-gris    { background: #e0e0e0 !important; }
        .panel-resumen { margin-bottom: 5px; }
        .panel-resumen .panel-heading { font-size: 11px; padding: 5px 10px; }
        .panel-resumen .panel-body { padding: 8px 15px; }
        .resumen-monto { font-size: 16px; font-weight: bold; }
        .resumen-vencido { color: #c62828; }
        .input-group-addon-blue { background-color: #d9edf7 !important; color: #31708f !important; font-weight: bold; }
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
                        </fieldset>
                    </div>
                </form>
            </div>
            <!-- Tarjeta resumen: saldo general y saldo vencido (siempre visible; se actualiza al buscar) -->
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
                    <table id="searchGrid" name="searchGrid"></table>
                    <div id="pag_sg"></div>
                    <div class="Titulos2">
                        <span><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop" style="color:#2e7d32;"></span> Factura pagada &nbsp; <span class="glyphicon glyphicon-stop" style="color:#f9a825;"></span> Pago parcial &nbsp; <span class="glyphicon glyphicon-stop" style="color:#c62828;"></span> Factura vencida &nbsp; <span class="glyphicon glyphicon-stop" style="color:#0288d1;"></span> Pago &nbsp; <span class="glyphicon glyphicon-stop" style="color:#9e9e9e;"></span> Facturas por vencer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="proveedoresDialog" title="Búsqueda de Proveedores"><form class="form-horizontal normal"></form></div>

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
