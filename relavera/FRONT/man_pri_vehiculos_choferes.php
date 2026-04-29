<?php
/**
 * Consulta de Vehículos y Choferes por Planta - Manifiestos
 * Permite visualizar todos los registros de vehículos y choferes de una planta y exportar a Excel.
 * @author Sistema EXA
 * @version 1.0
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
if (empty($cliente_manifiesto) || !is_array($cliente_manifiesto)) {
    $cliente_manifiesto = array();
}

/* ==================== AJAX HANDLERS ==================== */

// Listar choferes por Pla_Cod de cliente_manifiesto (consulta 5)
if (isset($listChoferesPlantaGridAjax)) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
    if ($rows === -1) {
        $rows = 1000000; // "Ver todos"
    }
    $op_opciones = isset($_GET['op_opciones']) ? $_GET['op_opciones'] : 'd';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $params = array();
    $params[0] = $Ses_Emp_Cod;
    if (!empty($cliente_manifiesto['Pla_Cod'])) {
        $params['Pla_Cod'] = (int) $cliente_manifiesto['Pla_Cod'];
    } elseif (!empty($cliente_manifiesto['Cli_Cod'])) {
        $params['Cli_Cod'] = $cliente_manifiesto['Cli_Cod'];
    }
    if (!empty($op_opciones) && !empty($search)) {
        $params['op_opciones'] = $op_opciones;
        $params['search'] = $search;
    }

    $paramsCount = $params;
    $contar = $obBD_con1->getRowConsulta(5, $paramsCount, $obBD_conexion);
    $pagination = pages($contar['total'], $page, $rows);
    $response = $pagination['data'];

    if ($contar['total'] > 0) {
        $params['limits'] = $pagination['limits'];
        $response['rows'] = $obBD_con1->getArrayConsulta(5, $params, $obBD_conexion);
        $obBD_con1->utf8_change_param($response['rows']);
    } else {
        $response['rows'] = array();
    }
    $obBD_con1->echoJson($response);
    exit;
}

// Listar vehículos filtrado por Pla_Cod del manifiesto_usuario (consulta 11)
if (isset($listVehiculosPlantaGridAjax)) {
    $request = $_GET;
    if (isset($request['rows']) && (int)$request['rows'] === -1) {
        $request['rows'] = 1000000; // "Ver todos"
        $request['page'] = 1;
    }
    $data = array_merge($request, array('where' => array()));
    if (!empty($cliente_manifiesto['Pla_Cod'])) {
        $data['where']['manifiesto_vehiculo.Pla_Cod'] = (int) $cliente_manifiesto['Pla_Cod'];
    }
    $searchVal = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (isset($_GET['op_opciones']) && !empty($searchVal)) {
        $data['op_opciones'] = $_GET['op_opciones'];
        $data['search'] = $searchVal;
    }
    $obBD_con1->getPageGridJson(11, $data, $obBD_conexion);
    exit;
}

// Obtener sanciones vigentes de un chofer (fecha/hora actual < Msa_Fef o Msa_Fef IS NULL)
if (isset($getSancionesChoferAjax)) {
    $Cho_Cod = isset($_GET['Cho_Cod']) ? (int) $_GET['Cho_Cod'] : 0;
    $resp = array('success' => true, 'sanciones' => array(), 'identificador' => '');
    if ($Cho_Cod > 0) {
        $sanciones = $obBD_con1->getArrayConsultaSql(
            "SELECT * FROM manifiesto_sanciones WHERE Cho_Cod = $Cho_Cod AND Msa_Tip = 'CH' AND Msa_Est = 'A' AND (Msa_Fef IS NULL OR NOW() < Msa_Fef) ORDER BY Msa_Fei DESC",
            $obBD_conexion
        );
        $obBD_con1->utf8_change_param($sanciones);
        $resp['sanciones'] = $sanciones;
        $chofer = $obBD_con1->getRowConsulta('chofer.selectWhere', array('where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
        if (!empty($chofer)) {
            $persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Cod' => $chofer['Prs_Cod'])), $obBD_conexion);
            if (!empty($persona)) {
                $resp['identificador'] = trim((isset($persona['Prs_Nom']) ? $persona['Prs_Nom'] : '') . ' ' . (isset($persona['Prs_Ape']) ? $persona['Prs_Ape'] : ''));
            }
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Obtener sanciones vigentes de un vehículo (fecha/hora actual < Msa_Fef o Msa_Fef IS NULL)
if (isset($getSancionesVehiculoAjax)) {
    $Veh_Cod = isset($_GET['Veh_Cod']) ? (int) $_GET['Veh_Cod'] : 0;
    $resp = array('success' => true, 'sanciones' => array(), 'identificador' => '');
    if ($Veh_Cod > 0) {
        $sanciones = $obBD_con1->getArrayConsultaSql(
            "SELECT * FROM manifiesto_sanciones WHERE Veh_Cod = $Veh_Cod AND Msa_Tip = 'VE' AND Msa_Est = 'A' AND (Msa_Fef IS NULL OR NOW() < Msa_Fef) ORDER BY Msa_Fei DESC",
            $obBD_conexion
        );
        $obBD_con1->utf8_change_param($sanciones);
        $resp['sanciones'] = $sanciones;
        $veh = $obBD_con1->getRowConsulta('vehiculo.selectWhere', array('where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
        if (!empty($veh)) {
            $resp['identificador'] = (isset($veh['Veh_Pla']) ? $veh['Veh_Pla'] : '') . ' - ' . (isset($veh['Veh_Mar']) ? $veh['Veh_Mar'] : '');
        }
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// Nombre de la planta para el banner informativo
$planta_nombre = '';
if (!empty($cliente_manifiesto['Pla_Cod'])) {
    $pla = $obBD_con1->getRowConsulta('manifiesto_plantas.selectWhere', array('where' => array('Pla_Cod' => $cliente_manifiesto['Pla_Cod'], 'Pla_Est' => 'A')), $obBD_conexion);
    if (!empty($pla)) {
        $obBD_con1->utf8_change_param($pla);
        $planta_nombre = isset($pla['Pla_Nom']) ? $pla['Pla_Nom'] : '';
    }
}

// Logo para cabecera de impresión (ruta directa, sin getReportHeader/getReportFooter)
$logo_empresa = utf8_encode($Ses_Emp_Log);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo "Vehículos y Choferes por Planta - Manifiestos [EXA]"; ?></title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
    <script>
        var listChoferesPlantaGridAjax = 1;
        var listVehiculosPlantaGridAjax = 1;
        var getSancionesChoferAjax = 1;
        var getSancionesVehiculoAjax = 1;
        var Pla_Cod = '<?php echo isset($cliente_manifiesto["Pla_Cod"]) ? (int)$cliente_manifiesto["Pla_Cod"] : 0; ?>';
        var planta_nombre = '<?php echo addslashes($planta_nombre); ?>';
    </script>
    <style>
        .nav-tabs-custom {
            margin-bottom: 20px;
        }
        .nav-tabs-custom>.nav-tabs {
            border-bottom: 3px solid #3c8dbc;
        }
        .nav-tabs-custom>.nav-tabs>li {
            margin-right: 5px;
        }
        .nav-tabs-custom>.nav-tabs>li>a {
            border-radius: 5px 5px 0 0;
            color: #444;
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 10px 20px;
            font-weight: bold;
        }
        .nav-tabs-custom>.nav-tabs>li.active>a {
            background: #3c8dbc;
            color: white;
            border-color: #3c8dbc;
        }
        .nav-tabs-custom>.nav-tabs>li>a:hover {
            background: #e9ecef;
        }
        .nav-tabs-custom>.nav-tabs>li.active>a:hover {
            background: #367fa9;
            color: white;
        }
        .tab-content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .tab-pane {
            min-height: 200px;
        }
        .icon-tab {
            margin-right: 8px;
            font-size: 16px;
        }
        .info-planta {
            background: #e7f3ff;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        /* Modal Sanciones - mejor aspecto */
        #modalSanciones .modal-dialog { width: 90%; max-width: 650px; }
        #modalSanciones .modal-header {
            background: linear-gradient(135deg, #d9534f 0%, #c9302c 100%);
            color: #fff;
            border-radius: 4px 4px 0 0;
            padding: 12px 15px;
            border-bottom: none;
        }
        #modalSanciones .modal-header .close {
            color: #fff;
            opacity: 0.9;
            text-shadow: none;
        }
        #modalSanciones .modal-header .close:hover { opacity: 1; }
        #modalSanciones .modal-title {
            font-weight: 600;
            font-size: 15px;
        }
        #modalSanciones .modal-title .glyphicon-ban-circle {
            margin-right: 8px;
        }
        #modalSanciones .modal-body {
            padding: 20px;
            background: #fafafa;
        }
        #modalSanciones #tablaSanciones {
            margin: 0;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        #modalSanciones #tablaSanciones thead {
            background: #5a6268;
            color: #fff;
        }
        #modalSanciones #tablaSanciones thead th {
            border: none;
            padding: 10px 12px;
            font-weight: 600;
            font-size: 12px;
        }
        #modalSanciones #tablaSanciones tbody tr {
            border-bottom: 1px solid #eee;
        }
        #modalSanciones #tablaSanciones tbody tr:hover {
            background-color: #fff9f9;
        }
        #modalSanciones #tablaSanciones tbody td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        #modalSanciones .modal-footer {
            border-top: 1px solid #e5e5e5;
            padding: 12px 15px;
            background: #f9f9f9;
        }
        #modalSanciones .sin-sanciones {
            text-align: center;
            padding: 30px 20px;
            color: #28a745;
        }
        #modalSanciones .sin-sanciones .glyphicon-ok-circle {
            font-size: 36px;
            margin-bottom: 10px;
        }
        #modalSanciones .obs-cell {
            max-width: 280px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="info-planta">
        <strong>Planta:</strong> <?php echo htmlspecialchars($planta_nombre ? $planta_nombre : 'Sin planta asignada'); ?>
    </div>

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#tabChoferes" aria-controls="tabChoferes" role="tab" data-toggle="tab">
                    <i class="glyphicon glyphicon-user icon-tab"></i>Choferes
                </a>
            </li>
            <li role="presentation">
                <a href="#tabVehiculos" aria-controls="tabVehiculos" role="tab" data-toggle="tab">
                    <i class="glyphicon glyphicon-road icon-tab"></i>Vehículos
                </a>
            </li>
        </ul>

        <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="tabChoferes">
            <div class="form-inline" style="margin-bottom:10px;">
                <label class="control-label">Filtrar por:</label>
                <select name="op_opciones" id="opChofer" class="form-control input-sm">
                    <option value="d">Nombre</option>
                    <option value="c">Cédula</option>
                </select>
                <input type="text" name="searchChofer" id="searchChofer" class="form-control input-sm" placeholder="Buscar..." style="margin-left:5px;">
                <button type="button" class="btn btn-sm btn-primary" id="btnBuscarChofer"><i class="fa fa-search"></i> Buscar</button>
                <button type="button" class="btn btn-sm btn-success" id="btnExcelChoferes"><i class="fa fa-file-excel-o"></i> Excel</button>
                <button type="button" class="btn btn-sm btn-info" id="btnImprimirChoferes"><i class="fa fa-print"></i> Imprimir</button>
            </div>
            <table id="gridChoferes"></table>
            <div id="pagerChoferes"></div>
        </div>

        <div role="tabpanel" class="tab-pane" id="tabVehiculos">
            <div class="form-inline" style="margin-bottom:10px;">
                <label class="control-label">Filtrar por:</label>
                <select name="op_opciones" id="opVehiculo" class="form-control input-sm">
                    <option value="p">Placa</option>
                </select>
                <input type="text" name="searchVehiculo" id="searchVehiculo" class="form-control input-sm" placeholder="Buscar por placa..." style="margin-left:5px;">
                <button type="button" class="btn btn-sm btn-primary" id="btnBuscarVehiculo"><i class="fa fa-search"></i> Buscar</button>
                <button type="button" class="btn btn-sm btn-success" id="btnExcelVehiculos"><i class="fa fa-file-excel-o"></i> Excel</button>
                <button type="button" class="btn btn-sm btn-info" id="btnImprimirVehiculos"><i class="fa fa-print"></i> Imprimir</button>
            </div>
            <table id="gridVehiculos"></table>
            <div id="pagerVehiculos"></div>
        </div>
        </div>
    </div>
</div>

<div id="imprimirVehiculos" style="display: none;">
    <div style="width: 1030px;">
        <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
            <tr>
                <td style="width:140px; text-align:left; vertical-align:middle;">
                    <img src="<?php echo htmlspecialchars($logo_empresa); ?>" alt="Logo" style="max-height:65px; max-width:130px;" onerror="this.style.display='none'">
                </td>
                <td style="text-align:center; vertical-align:middle;">
                    <div style="font-size:18px; font-weight:bold;">REPORTE DE VEHICULOS</div>
                    <div style="font-size:12px; margin-top:2px;">PLANTA: <?php echo htmlspecialchars($planta_nombre ? $planta_nombre : 'Sin planta asignada'); ?></div>
                </td>
                <td style="width:180px; text-align:right; vertical-align:middle; font-size:11px;">
                    Generado: <?php echo date('d-m-Y H:i'); ?>
                </td>
            </tr>
        </table>
        <div style="border-top:1px solid #222; margin:4px 0 10px 0;"></div>
        <div style="font-size: 12px; font-weight: bold; margin: 0 0 8px 0;">
            Total de registros
        </div>
        <table id="tablaReporteVehiculos" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; table-layout: auto; font-size: 12px;"></table>
    </div>
</div>

<div id="imprimirChoferes" style="display: none;">
    <div style="width: 1030px;">
        <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
            <tr>
                <td style="width:140px; text-align:left; vertical-align:middle;">
                    <img src="<?php echo htmlspecialchars($logo_empresa); ?>" alt="Logo" style="max-height:65px; max-width:130px;" onerror="this.style.display='none'">
                </td>
                <td style="text-align:center; vertical-align:middle;">
                    <div style="font-size:18px; font-weight:bold;">REPORTE DE CHOFERES</div>
                    <div style="font-size:12px; margin-top:2px;">PLANTA: <?php echo htmlspecialchars($planta_nombre ? $planta_nombre : 'Sin planta asignada'); ?></div>
                </td>
                <td style="width:180px; text-align:right; vertical-align:middle; font-size:11px;">
                    Generado: <?php echo date('d-m-Y H:i'); ?>
                </td>
            </tr>
        </table>
        <div style="border-top:1px solid #222; margin:4px 0 10px 0;"></div>
        <div style="font-size: 12px; font-weight: bold; margin: 0 0 8px 0;">
            Total de registros
        </div>
        <table id="tablaReporteChoferes" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; table-layout: auto; font-size: 12px;"></table>
    </div>
</div>

<!-- Modal Sanciones -->
<div class="modal fade" id="modalSanciones" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-ban-circle"></i> Sanciones vigentes - <span id="modalSancionesTitulo"></span></h4>
            </div>
            <div class="modal-body">
                <div id="contenidoSanciones">
                    <table class="table table-condensed" id="tablaSanciones">
                        <thead><tr><th>Fecha inicio</th><th>Fecha fin</th><th>Observación</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="sinSancionesMsg" class="sin-sanciones" style="display:none;">
                    <i class="glyphicon glyphicon-ok-circle"></i>
                    <p style="margin:0;">Sin sanciones vigentes registradas</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="../VALIDACIONES/man_val_vehiculos_choferes.js?a=7"></script>
</body>
</html>
