<?php
/**
 * Control de Alimentación de Personal Interno - Relavera
 * Permite registrar, consultar y generar reportes de alimentación del personal interno.
 * @author Sistema EXA
 * @version 2.0 (Ajuste de Interfaz)
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_alimentacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Alimentacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Alimentacion();

// 1. Obtener planta asignada al usuario
$Pla_Cod_Log = isset($_SESSION['Ses_Pla_Cod']) ? (int)$_SESSION['Ses_Pla_Cod'] : 0;

if ($Pla_Cod_Log === 0) {
    $row_mu = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = '".$_SESSION['Ses_Usu_Cod']."' LIMIT 1", $obBD_conexion);
    if (isset($row_mu[0]['Pla_Cod'])) {
        $Pla_Cod_Log = (int)$row_mu[0]['Pla_Cod'];
    }
}

if ($Pla_Cod_Log === 0) {
    $row_pla_def = $obBD_con1->getArrayConsultaSql("SELECT Pla_Cod FROM manifiesto_plantas mp LEFT JOIN cliente c ON c.Cli_Cod = mp.Cli_Cod WHERE mp.Pla_Est = 'A' AND c.Emp_Cod = '".$_SESSION['Ses_Emp_Cod']."' LIMIT 1", $obBD_conexion);
    $Pla_Cod_Log = isset($row_pla_def[0]['Pla_Cod']) ? (int)$row_pla_def[0]['Pla_Cod'] : 0;
}

/* ==================== AJAX HANDLERS ==================== */

// Listar personal interno
if (isset($_GET['listPersonalAjax'])) {
    $rows_data = $obBD_con1->getArrayConsulta(1, array('Pla_Cod' => $Pla_Cod_Log), $obBD_conexion);
    $obBD_con1->utf8_change_param($rows_data);
    $obBD_con1->echoJson($rows_data);
    exit;
}

// Obtener alimentaciones ya registradas para un personal y fecha
if (isset($_GET['getAlimentacionRegistradaAjax'])) {
    $per_cod = isset($_GET['Per_Cod']) ? intval($_GET['Per_Cod']) : 0;
    $fecha = isset($_GET['Mal_Fec']) ? trim($_GET['Mal_Fec']) : '';

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
        list($d, $m, $y) = explode('/', $fecha);
        $fecha = "$y-$m-$d";
    }

    $rows = $obBD_con1->getArrayConsultaSql("
        SELECT Mal_Tip FROM maquinaria_alimentacion 
        WHERE Per_Cod = $per_cod AND Mal_Fec = '$fecha' AND Mal_Est = 'A'
    ", $obBD_conexion);

    $tipos = array();
    foreach ($rows as $row) {
        $tipos[] = $row['Mal_Tip'];
    }

    $obBD_con1->echoJson($tipos);
    exit;
}

// Guardar registro de alimentación (Múltiple)
if (isset($_POST['saveAlimentacionAjax'])) {
    $resp = array('success' => false);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $fecha = isset($_POST['txtFecha']) ? trim($_POST['txtFecha']) : '';
        $per_cod = isset($_POST['cboPersonal']) ? intval($_POST['cboPersonal']) : 0;
        $tipos = isset($_POST['cboTipos']) && is_array($_POST['cboTipos']) ? $_POST['cboTipos'] : array();

        if (empty($per_cod)) {
            throw new Exception('Debe seleccionar al personal.');
        }
        if (empty($tipos)) {
            throw new Exception('Debe seleccionar al menos un tipo de alimentación.');
        }

        // Convertir fecha a Y-m-d
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
            list($d, $m, $y) = explode('/', $fecha);
            $fecha = "$y-$m-$d";
        }

        $guardados = array();
        $duplicados = array();
        
        $nombres_tipos = array(
            'D' => 'Desayuno',
            'A' => 'Almuerzo',
            'M' => 'Merienda',
            'C' => 'Cena'
        );

        foreach ($tipos as $tipo) {
            if (!in_array($tipo, array('D', 'A', 'M', 'C'))) {
                continue;
            }

            // Verificar duplicados
            $check = $obBD_con1->getArrayConsulta(4, array(
                'Per_Cod' => $per_cod,
                'Mal_Fec' => $fecha,
                'Mal_Tip' => $tipo
            ), $obBD_conexion);

            if (!empty($check)) {
                $duplicados[] = isset($nombres_tipos[$tipo]) ? $nombres_tipos[$tipo] : $tipo;
                continue;
            }

            // Insertar registro
            $obBD_con1->operacionobBD(5, array(
                'Per_Cod' => $per_cod,
                'Usu_Cod' => $_SESSION['Ses_Usu_Cod'],
                'Mal_Tip' => $tipo,
                'Mal_Fec' => $fecha
            ), $obBD_conexion);

            $guardados[] = isset($nombres_tipos[$tipo]) ? $nombres_tipos[$tipo] : $tipo;
        }

        if (empty($guardados) && !empty($duplicados)) {
            throw new Exception('No se guardaron nuevos registros. Ya existían: ' . implode(', ', $duplicados));
        }

        $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
        $resp['success'] = true;

        // Mensaje simple: solo confirmar que se procesó
        $resp['message'] = "Alimentación procesada correctamente.";

    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $resp['message'] = $e->getMessage();
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Consultar registros para el grid
if (isset($_GET['listAlimentacionGridAjax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    $f_tipo = isset($_GET['f_tipo']) ? $_GET['f_tipo'] : '';
    $f_val_dia = isset($_GET['f_val_dia']) ? $_GET['f_val_dia'] : '';
    $f_val_semana = isset($_GET['f_val_semana']) ? $_GET['f_val_semana'] : '';
    $f_val_mes = isset($_GET['f_val_mes']) ? $_GET['f_val_mes'] : '';
    $f_quincena = isset($_GET['f_quincena']) ? $_GET['f_quincena'] : '';
    $f_buscar = isset($_GET['f_buscar']) ? trim($_GET['f_buscar']) : '';
    $f_tipo_busqueda = isset($_GET['f_tipo_busqueda']) ? trim($_GET['f_tipo_busqueda']) : 'personal';
    $f_estado = isset($_GET['f_estado']) ? $_GET['f_estado'] : '';

    $params = array();
    $desde = '';
    $hasta = '';

    if ($f_tipo === 'D') {
        $desde = $f_val_dia;
        $hasta = $f_val_dia;
    } elseif ($f_tipo === 'S' && !empty($f_val_semana)) {
        list($anio, $semana) = explode('-W', $f_val_semana);
        $primer_dia_semana = new DateTime();
        $primer_dia_semana->setISODate($anio, $semana);
        $desde = $primer_dia_semana->format('Y-m-d');
        $ultimo_dia_semana = new DateTime($desde);
        $ultimo_dia_semana->modify('+6 days');
        $hasta = $ultimo_dia_semana->format('Y-m-d');
    } elseif ($f_tipo === 'Q' && !empty($f_val_mes)) {
        list($anio, $mes) = explode('-', $f_val_mes);
        if ($f_quincena == 1) {
            $desde = "$anio-$mes-01";
            $hasta = "$anio-$mes-15";
        } else {
            $desde = "$anio-$mes-16";
            $ultimo_dia = date('t', mktime(0,0,0,$mes,1,$anio));
            $hasta = "$anio-$mes-$ultimo_dia";
        }
    } elseif ($f_tipo === 'M' && !empty($f_val_mes)) {
        list($anio, $mes) = explode('-', $f_val_mes);
        $desde = "$anio-$mes-01";
        $ultimo_dia = date('t', mktime(0,0,0,$mes,1,$anio));
        $hasta = "$anio-$mes-$ultimo_dia";
    }

    if ($desde) {
        $params['Mal_Fec_Desde'] = $desde;
    }
    if ($hasta) {
        $params['Mal_Fec_Hasta'] = $hasta;
    }
    if ($f_estado) {
        $params['Mal_Est'] = $f_estado;
    }
    $params['f_buscar'] = $f_buscar;
    if ($f_tipo_busqueda == 'personal') {
        $params['f_tipo_busqueda'] = 'personal';
    } else {
        $params['f_tipo_busqueda'] = 'personal';
    }

    // Obtener conteo total
    $row_count = $obBD_con1->getRowConsulta(6, $params, $obBD_conexion);
    $total_records = isset($row_count['total']) ? (int)$row_count['total'] : 0;

    // Calcular paginación
    $total_pages = ceil($total_records / $rows);
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }
    $start = ($page - 1) * $rows;
    $params['limits'] = "LIMIT $start, $rows";

    // Obtener datos
    $data = $obBD_con1->getArrayConsulta(7, $params, $obBD_conexion);
    $obBD_con1->utf8_change_param($data);

    $response = array(
        'total' => $total_pages,
        'page' => $page,
        'records' => $total_records,
        'rows' => $data
    );

    $obBD_con1->echoJson($response);
    exit;
}

// Obtener preview detallado de alimentación para el modal
if (isset($_GET['getPreviewAlimentacionAjax'])) {
    $active_ids = isset($_GET['Active_Ids']) ? trim($_GET['Active_Ids']) : '';
    $active_ids = preg_replace('/[^0-9,]/', '', $active_ids);
    $active_ids = trim($active_ids, ',');
    if (empty($active_ids)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Identificador inválido.'));
        exit;
    }

    $sql = "SELECT a.Mal_Fec, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Per_Nom, a.Mal_Tip
            FROM maquinaria_alimentacion a
            INNER JOIN personal pe ON pe.Per_Cod = a.Per_Cod
            INNER JOIN persona p ON p.Prs_Cod = pe.Prs_Cod
            WHERE a.Mal_Cod IN ($active_ids) AND a.Mal_Est = 'A'
            ORDER BY FIELD(a.Mal_Tip, 'D', 'A', 'M', 'C')";

    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    if (empty($rows)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontraron datos de alimentación.'));
        exit;
    }

    $comidas = array();
    $fecha = '';
    $personal = '';

    foreach ($rows as $row) {
        if (empty($fecha) && isset($row['Mal_Fec'])) {
            $fecha = $row['Mal_Fec'];
        }
        if (empty($personal) && isset($row['Per_Nom'])) {
            $personal = $row['Per_Nom'];
        }
        if (isset($row['Mal_Tip']) && !in_array($row['Mal_Tip'], $comidas)) {
            $comidas[] = $row['Mal_Tip'];
        }
    }

    $obBD_con1->echoJson(array('success' => true, 'data' => array(
        'fecha' => $fecha,
        'personal' => $personal,
        'comidas' => $comidas
    )));
    exit;
}

// Anular registro de alimentación (Múltiple)
if (isset($_POST['anularAlimentacionAjax'])) {
    $resp = array('success' => false);
    $mal_cod = isset($_POST['Mal_Cod']) ? trim($_POST['Mal_Cod']) : '';
    // Sanitizar para que solo contenga números y comas
    $mal_cod = preg_replace('/[^0-9,]/', '', $mal_cod);

    if (empty($mal_cod)) {
        $resp['message'] = 'Código de registro inválido.';
        $obBD_con1->echoJson($resp);
        exit;
    }

    $obBD_con1->operacionobBD(8, array('Mal_Cod' => $mal_cod), $obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['message'] = 'Registro(s) anulado(s) exitosamente.';
    } else {
        $resp['message'] = $obBD_con1->getMsgError();
    }

    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener datos para el reporte quincenal/mensual
if (isset($_GET['getReporteAlimentacionAjax'])) {
    $mes = isset($_GET['cboMes']) ? intval($_GET['cboMes']) : date('n');
    $anio = isset($_GET['cboAnio']) ? intval($_GET['cboAnio']) : date('Y');
    $per_cod = isset($_GET['cboPersonalReporte']) ? intval($_GET['cboPersonalReporte']) : 0;

    $params = array('Mes' => $mes, 'Anio' => $anio);
    if ($per_cod) {
        $params['Per_Cod'] = $per_cod;
    }

    $data = $obBD_con1->getArrayConsulta(9, $params, $obBD_conexion);
    $obBD_con1->utf8_change_param($data);

    // Organizar datos por personal y fecha
    $reporte = array();
    foreach ($data as $row) {
        $per_key = $row['Per_Cod'];
        if (!isset($reporte[$per_key])) {
            $reporte[$per_key] = array(
                'Per_Nom' => $row['Per_Nom'],
                'datos' => array()
            );
        }

        $fecha = $row['Mal_Fec'];
        if (!isset($reporte[$per_key]['datos'][$fecha])) {
            $reporte[$per_key]['datos'][$fecha] = array(
                'Desayuno' => 0,
                'Almuerzo' => 0,
                'Merienda' => 0
            );
        }

        $tipo = $row['Mal_Tip'] == 'D' ? 'Desayuno' : ($row['Mal_Tip'] == 'A' ? 'Almuerzo' : 'Merienda');
        $reporte[$per_key]['datos'][$fecha][$tipo] = 1;
    }

    $obBD_con1->echoJson($reporte);
    exit;
}

?>
<!DOCTYPE HTML>
<HTML>
<HEAD>
    <TITLE>Control de Alimentación de Personal Interno</TITLE>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <?php require_once("../../mascaras/model3/estilos/estilos.php") ?>
    <link rel="stylesheet" type="text/css" href="../RECURSOS/alimentacion.css" />
    <style>
        .preview-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            padding: 0 10px;
        }
        .preview-step {
            text-align: center;
            width: 22%;
            position: relative;
            color: #b0b9c4;
        }
        .preview-step.active {
            color: #1e88e5;
        }
        .preview-step::after {
            content: '';
            position: absolute;
            top: 24px;
            left: 50%;
            width: calc(100% + 32px);
            height: 2px;
            background: #d8dde6;
            z-index: -1;
        }
        .preview-step.active::after {
            background: #1e88e5;
        }
        .preview-step:last-child::after {
            display: none;
        }
        .preview-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
        }
        .preview-step.active .preview-icon {
            background: #1e88e5;
            color: #fff;
        }
        .preview-icon i {
            font-size: 20px;
        }
        .preview-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
        }
        .preview-step.active .preview-icon {
            background: #1e88e5;
            color: #fff;
        }
        .preview-label {
            font-size: 13px;
            font-weight: 700;
        }
    </style>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
</HEAD>
<BODY>
    <div class="panel panel-default panel-main exa-ui-panel">
        <div class="panel-heading exa-header">
            <h3 class="panel-title"><span class="glyphicon glyphicon-cutlery"></span> » Control de Alimentación de Personal Interno</h3>
        </div>
        <div class="panel-body exa-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="active"><a href="#tabConsulta" aria-controls="tabConsulta" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-list" style="margin-right:8px;"></i>Consulta</a></li>
                    <li><a href="#tabReporte" aria-controls="tabReporte" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-bar-chart" style="margin-right:8px;"></i>Reportes</a></li>
                </ul>

                <div class="tab-content tab-content-custom">
                    <!-- Tab CONSULTA (Vista Principal) -->
                    <div role="tabpanel" class="tab-pane active" id="tabConsulta">
                        <div class="form-inline" style="margin-bottom:25px;">
                            <div style="float:left;">
                                <label class="control-label" style="font-weight:bold; margin-right:5px;">Periodo:</label>
                                <select id="tipoFiltroFecha" class="form-control input-sm" style="margin-right:5px;" onchange="ajustarFiltroFecha(this.value);">
                                    <option value="T">Todo el tiempo</option>
                                    <option value="D">Por Día</option>
                                    <option value="S">Semanal</option>
                                    <option value="Q">Quincenal</option>
                                    <option value="M">Mensual</option>
                                </select>
                                <input type="date" id="filtroFechaDia" class="form-control input-sm" style="margin-right:5px; display:none;">
                                <input type="week" id="filtroFechaSemana" class="form-control input-sm" style="margin-right:5px; display:none;">
                                <input type="month" id="filtroFechaMes" class="form-control input-sm" style="margin-right:5px; display:none;">
                                <select id="filtroQuincena" class="form-control input-sm" style="margin-right:15px; display:none;">
                                    <option value="1">1ra Quincena (1-15)</option>
                                    <option value="2">2da Quincena (16-fin)</option>
                                </select>
                                <label class="control-label" style="font-weight:bold; margin-right:5px; margin-left:10px;">Buscar:</label>
                                <select id="tipoBusqueda" class="form-control input-sm" style="margin-right:5px; display:none;">
                                    <option value="personal">Personal (Nombre/Cédula)</option>
                                </select>
                                <input type="text" id="txtBuscar" class="form-control input-sm" placeholder="Buscar..." style="margin-right:5px; width: 200px;">
                                <label class="control-label" style="font-weight:bold; margin-right:5px; margin-left:10px;">Estado:</label>
                                <select id="cboEstado" class="form-control input-sm" style="margin-right:5px;">
                                    <option value="">Todos</option>
                                    <option value="A">Activo</option>
                                    <option value="I">Inactivo</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-primary" id="btnBuscar"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                            </div>
                            <div style="float:right;">
                                <button type="button" class="btn btn-sm btn-success" id="btnNuevoAlimentacion"><i class="glyphicon glyphicon-plus"></i> Registrar Alimentación</button>
                            </div>
                        </div>
                        <div class="clearfix"></div>

                        <div class="exa-ui-grid-host" style="margin-top: 30px;">
                            <table id="gridAlimentacion"></table>
                            <div id="pagerAlimentacion"></div>
                        </div>
                    </div>

                    <!-- Tab REPORTES -->
                    <div role="tabpanel" class="tab-pane" id="tabReporte">
                        <div class="form-inline" style="margin-bottom:25px;">
                            <div style="float:left;">
                                <label class="control-label" style="font-weight: bold;">Mes:</label>
                                <select id="cboMes" class="form-control input-sm" style="margin-left:5px;">
                                    <?php
                                    $meses = array(
                                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                    );
                                    for ($i = 1; $i <= 12; $i++) {
                                        $selected = ($i == date('n')) ? 'selected' : '';
                                        echo "<option value='$i' $selected>{$meses[$i]}</option>";
                                    }
                                    ?>
                                </select>
                                <label class="control-label" style="font-weight:bold; margin-left:15px;">Año:</label>
                                <select id="cboAnio" class="form-control input-sm" style="margin-left:5px;">
                                    <?php
                                    $anio_actual = date('Y');
                                    for ($i = $anio_actual - 5; $i <= $anio_actual + 1; $i++) {
                                        $selected = ($i == $anio_actual) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                                <label class="control-label" style="font-weight:bold; margin-left:15px;">Personal Interno:</label>
                                <select id="cboPersonalReporte" class="form-control input-sm chosen-select" style="margin-left:5px; width:220px;">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div style="float:right;">
                                <button type="button" class="btn btn-sm btn-primary" id="btnGenerarReporte"><i class="glyphicon glyphicon-list-alt"></i> Generar Reporte</button>
                                <button type="button" class="btn btn-sm btn-exa-success" id="btnExportarReporteExcel"><i class="glyphicon glyphicon-file"></i> Excel</button>
                                <button type="button" class="btn btn-sm btn-danger" id="btnExportarReportePDF"><i class="glyphicon glyphicon-file"></i> PDF</button>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div id="divReporte"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registro de Alimentación -->
    <div class="modal fade" id="modalRegistroAlimentacion" tabindex="-1" role="dialog" aria-labelledby="modalRegistroAlimentacionLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header exa-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalRegistroAlimentacionLabel"><i class="glyphicon glyphicon-pencil"></i> Registrar Alimentación</h4>
                </div>
                <div class="modal-body">
                    <form id="formRegistroAlimentacion">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha:</label>
                                    <input type="text" id="txtFechaModal" class="form-control input-sm datepicker" value="<?php echo date('d/m/Y'); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="control-label">Alimentación:</label>
                                    <div style="margin-top: 5px;">
                                        <div class="chk-container-alimentacion">
                                            <label class="checkbox-inline" style="font-weight: normal; margin-left: 0;">
                                                <input type="checkbox" name="chkAlimentacionModal" value="D"> Desayuno
                                            </label>
                                        </div>
                                        <div class="chk-container-alimentacion">
                                            <label class="checkbox-inline" style="font-weight: normal; margin-left: 0;">
                                                <input type="checkbox" name="chkAlimentacionModal" value="A"> Almuerzo
                                            </label>
                                        </div>
                                        <div style="margin-top: 5px;"></div>
                                        <div class="chk-container-alimentacion">
                                            <label class="checkbox-inline" style="font-weight: normal; margin-left: 0;">
                                                <input type="checkbox" name="chkAlimentacionModal" value="M"> Merienda
                                            </label>
                                        </div>
                                        <div class="chk-container-alimentacion">
                                            <label class="checkbox-inline" style="font-weight: normal; margin-left: 0;">
                                                <input type="checkbox" name="chkAlimentacionModal" value="C"> Cena
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="control-label">Personal Interno:</label>
                                    <select id="cboPersonalModal" class="form-control input-sm chosen-select" style="width: 100%;">
                                        <option value="">Seleccione Personal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="btnGuardarModal"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview de Alimentación -->
    <div class="modal fade" id="modalPreviewAlimentacion" tabindex="-1" role="dialog" aria-labelledby="modalPreviewAlimentacionLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header exa-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modalPreviewAlimentacionLabel"><i class="glyphicon glyphicon-eye-open"></i> Detalle de Alimentación</h4>
                </div>
                        <div class="modal-body" id="bodyPreviewAlimentacion">
                    <!-- Contenido dinámico -->
                    <div style="font-size:14px;">
                        <p><strong>Fecha:</strong> <span id="pv_fecha"></span></p>
                        <p><strong>Personal:</strong> <span id="pv_personal"></span></p>
                        <p><strong>Comidas registradas:</strong></p>
                        <div id="pv_comidas_list"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cargador Visual / Loader -->
    <div id="loaderAlimentacion" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; text-align: center; padding-top: 20%;">
        <div style="display: inline-block; padding: 25px 35px; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: #334a5f;"></i>
            <div style="margin-top: 15px; font-weight: bold; color: #334a5f; font-size: 14px;">Procesando solicitud...</div>
        </div>
    </div>

    <script type="text/javascript" src="../VALIDACIONES/man_val_alimentacion.js?v=14"></script>
</BODY>
</HTML>
