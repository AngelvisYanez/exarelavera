<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_roles.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


require_once('../../DATA/MysqlDatos.php');
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

//Traer funciones del archivo MysqlDatos
$funciones_msyql = new MysqlDatosContab;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($getDefaults)) {
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);
}

if (isset($printAjax)) {
    $datos = $_GET;
    $omitCols = array();
    if (!empty($datos['omitirColsRol'])) {
        if (is_string($datos['omitirColsRol'])) {
            $dec = json_decode($datos['omitirColsRol'], true);
            if (is_array($dec)) {
                foreach ($dec as $cn) {
                    if ($cn !== '' && $cn !== null) {
                        $omitCols[$cn] = true;
                    }
                }
            }
        } elseif (is_array($datos['omitirColsRol'])) {
            foreach ($datos['omitirColsRol'] as $cn) {
                if ($cn !== '' && $cn !== null) {
                    $omitCols[$cn] = true;
                }
            }
        }
    }
    $rolOmit = function ($name) use ($omitCols) {
        return !empty($omitCols[$name]);
    };
    $omitNombres = !empty($omitCols['Prs_Ape']) || !empty($omitCols['Prs_Nom']);

    $rol_pago = $obBD_con1->getRowConsulta(16, $datos, $obBD_conexion);
    $grid = $obBD_con1->getGridRolMayorCero($rol_pago['Map_Cod'], $datos['Rol_Cod'], $datos['imprimirAll'], $datos['check_col_hs'], $datos['check_col_jorn'], $obBD_conexion, false);
    //$grid=$obBD_con1->getGridRol($rol_pago['Map_Cod'],$obBD_conexion,false);
    $obBD_con1->utf8_change_param($grid);
    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);
    $t = array('{Emp_Nom}' => $Ses_Emp_Nom, '{Rol_Con}' => $rol_pago['Rol_Con'], '{Rol_Range}' => "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef] ", '{ingHeader}' => '', '{egrHeader}' => '', '{ingSpan}' => 0, '{egrSpan}' => 0, '{data}' => '', '{rol_border}' => 'border:' . (isset($print) ? '1px solid gray;' : '0.1pt solid black;'), '{Rol_Campos_Ingreso}' => '', '{Rol_Campos_Egreso}' => '', '{theadRolRows}' => '');
    $aux = array('{Rol_i}' => '', '{Prs_Ced}' => '', '{Prs_Ape}' => '', '{Prs_Nom}' => '', '{dias}' => '', '{Tic_Des}' => '');
    $estaMarcado = isset($datos['estaMarcado']) ? filter_var($datos['estaMarcado'], FILTER_VALIDATE_BOOLEAN) : false;
    $mostrarCargoCol = !$estaMarcado && !$rolOmit('Tic_Des');

    foreach ($grid['rol'] as $f) {
        if ($rolOmit($f['Cam_Var'])) {
            continue;
        }
        if (($f['Cam_Tip'] === 'I') && $f['Cam_Vis'] === 'S') {
            $t['{ingHeader}'] .= "<td  style='{rol_border};'> $f[Cam_Dec] </td>";
            $t['{ingSpan}']++;
            $t['{Rol_Campos_Ingreso}'] .= '<td  style="{rol_border} " align="right">{' . $f['Cam_Var'] . '}</td>';
        }
        if (($f['Cam_Tip'] === 'E') && $f['Cam_Vis'] === 'S') {
            $t['{egrHeader}'] .= "<td  style='{rol_border} '>$f[Cam_Dec]</td>";
            $t['{egrSpan}']++;
            $t['{Rol_Campos_Egreso}'] .= '<td style="{rol_border} " align="right">{' . $f['Cam_Var'] . '}</td>';
        }
        if ($f['Cam_Vis'] === 'S') {
            $aux['{' . $f['Cam_Var'] . '}'] = 0;
        }
    }

    $filas = '<tr>';
    if (!$rolOmit('Rol_i')) {
        $filas .= '<td style="{rol_border} " align="center">{Rol_i}</td>';
    }
    if (!$rolOmit('Prs_Ced')) {
        $filas .= '<td style="{rol_border} mso-number-format:&#39;@&#39;;">{Prs_Ced}</td>';
    }
    if (!$omitNombres) {
        $filas .= '<td style="{rol_border} ">{Prs_Ape} {Prs_Nom}</td>';
    }
    if ($mostrarCargoCol) {
        $filas .= '<td  style="{rol_border} ">{Tic_Des}</td>';
    }
    if (!$rolOmit('dias')) {
        $filas .= '<td style="{rol_border} " align="center">{dias}</td>';
    }
    $filas .= '{Rol_Campos_Ingreso}';
    if (!$rolOmit('total_ingr')) {
        $filas .= '<td style="{rol_border} " align="right">{total_ingr}</td>';
    }
    $filas .= '{Rol_Campos_Egreso}';
    if (!$rolOmit('total_egr')) {
        $filas .= '<td style="{rol_border} " align="right">{total_egr}</td>';
    }
    if (!$rolOmit('total_rol')) {
        $filas .= '<td style="{rol_border} " align="right">{total_rol}</td>';
    }
    $filas .= '<td style="{rol_border} " align="right" height="40" width="100"></td></tr>';

    $t['{mostrarColumnCargo}'] = $mostrarCargoCol ? '<td rowspan="2" style="border:0.1pt solid; font-size:13px">CARGO </td>' : '';

    $rbHead = 'border:' . (isset($print) ? '1px solid gray;' : '0.1pt solid black;');
    $tr1 = '<tr style="font-weight: bold" align="center">';
    if (!$rolOmit('Rol_i')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">No.</td>';
    }
    if (!$rolOmit('Prs_Ced')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">CÉDULA</td>';
    }
    if (!$omitNombres) {
        $tr1 .= '<td colspan="1" rowspan="2" style="' . $rbHead . ' font-size:13px;">NOMBRES</td>';
    }
    if ($mostrarCargoCol) {
        $tr1 .= '<td rowspan="2" style="border:0.1pt solid; font-size:13px">CARGO </td>';
    }
    if (!$rolOmit('dias')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">DIAS</td>';
    }
    if ($t['{ingSpan}'] > 0) {
        $tr1 .= '<td colspan="' . (int)$t['{ingSpan}'] . '" style="' . $rbHead . ' font-size:13px;">INGRESOS</td>';
    }
    if (!$rolOmit('total_ingr')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">TOT. INGR.</td>';
    }
    if ($t['{egrSpan}'] > 0) {
        $tr1 .= '<td colspan="' . (int)$t['{egrSpan}'] . '" style="' . $rbHead . ' font-size:13px;">EGRESOS</td>';
    }
    if (!$rolOmit('total_egr')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">TOT. EGRE.</td>';
    }
    if (!$rolOmit('total_rol')) {
        $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">TOTAL</td>';
    }
    $tr1 .= '<td rowspan="2" style="' . $rbHead . ' font-size:13px;">FIRMA</td></tr>';
    $tr2 = '<tr style="font-weight: bold" align="center">' . $t['{ingHeader}'] . $t['{egrHeader}'] . '</tr>';
    $t['{theadRolRows}'] = $tr1 . $tr2;

    $f = reporteArray($t, $filas);

    foreach ($roles as $r) {
        $t['{data}'] .= reporteArray($r, $f);
        foreach ($grid['rol'] as $fd) {
            if ($fd['Cam_Vis'] === 'S' && !$rolOmit($fd['Cam_Var'])) {
                $aux['{' . $fd['Cam_Var'] . '}'] += ($r['{' . $fd['Cam_Var'] . '}'] * 1);
            }
        }
    }
    foreach ($grid['rol'] as $fd) {
        if ($fd['Cam_Vis'] === 'S' && !$rolOmit($fd['Cam_Var'])) {
            $aux['{' . $fd['Cam_Var'] . '}'] = formato_numero($aux['{' . $fd['Cam_Var'] . '}'], 2, 1);
        }
    }
    $aux['{dias}'] = '';
    $t['{data}'] .= reporteArray($aux, $f);
    //var_dump($rol['roles']);
    $fixedCols = 1;
    if (!$rolOmit('Rol_i')) {
        $fixedCols++;
    }
    if (!$rolOmit('Prs_Ced')) {
        $fixedCols++;
    }
    if (!$omitNombres) {
        $fixedCols++;
    }
    if ($mostrarCargoCol) {
        $fixedCols++;
    }
    if (!$rolOmit('dias')) {
        $fixedCols++;
    }
    if (!$rolOmit('total_ingr')) {
        $fixedCols++;
    }
    if (!$rolOmit('total_egr')) {
        $fixedCols++;
    }
    if (!$rolOmit('total_rol')) {
        $fixedCols++;
    }
    $t['{maxSpan}'] = $t['{ingSpan}'] + $t['{egrSpan}'] + $fixedCols;
    $t['{header_empresa}'] = $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ROL DE PAGOS  - ' . "$rol_pago[Are_Des]", "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef] ", $obBD_conexion, false, $t['{maxSpan}'], isset($print), true);

    //Obtener los campos para realizar el encabezado pdf y excel
    $full = true;
    $withLogo = $t['{maxSpan}'];

    $row_inst = $funciones_msyql->getSucursal($Ses_Suc_Cod, $obBD_conexion);
    $appendLogo = ((!$full) && $withLogo);
    $row_ciud = $funciones_msyql->getCiudad($row_inst['Ciu_Cod'], $obBD_conexion);
    $t['{CIUDAD}'] = (empty($row_ciud) || empty($row_ciud['Pro_Nom'])) ? '' : " - " . $row_ciud['Pro_Nom'] . ' - ' . $row_ciud['Pas_Nom'];
    // $t['{LOGO}'] =$full||$appendLogo?"<div align='left'><img style='float:left;position:fixed;".(empty($row_inst['Suc_Com'])?"":"padding-top:7px;")."' src='".(empty($row_inst['Suc_Lg2'])?$row_inst['Emp_Log']:$row_inst['Suc_Lg2'])."' width='".($appendLogo?83:103)."' height='".($appendLogo?73:93)."' /></div>":'';
    $t['{LOGO}'] = $full || $appendLogo ? "<div align='left'><img style='float:left;position:fixed;" . (empty($row_inst['Suc_Com']) ? "" : "padding-top:7px;") . "' src='" . (empty($row_inst['Suc_Lg2']) ? $row_inst['Emp_Log'] : $row_inst['Suc_Lg2']) . "' width='83' height='73' /></div>" : '';
    $t['{titulo_empresa}'] = (empty($row_inst['Suc_Com']) ? $row_inst['Emp_Nom'] : $row_inst['Suc_Com']);
    $t['{empresa_nombre}'] = $row_inst['Emp_Nom'];
    $t['{empresa_ruc}'] = $row_inst['Emp_Ruc'];
    $t['{suc_te1}'] = $row_inst['Suc_Te1'];
    $t['{suc_dir}'] = $row_inst['Suc_Dir'];
    $t['{Suc_Cor}'] = $row_inst['Suc_Cor'];
    $t['{Ciu_Des}'] =  $row_inst['Ciu_Des'];
    $t['{titulo}'] = 'ROL DE PAGOS  - ' . "$rol_pago[Are_Des]";
    $startDate = new DateTime($rol_pago['Rol_Fei']);
    $endDate = new DateTime($rol_pago['Rol_Fef']);
    $weekNumber = ceil(($startDate->format('z') / 7) + 1); // Calculate the week number based on the start date
    $t['{semana}'] = ($rol_pago['Rol_Tip'] === 'S') ? 'Semana # ' . $weekNumber : '';
    $t['{subtitulo}'] = "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef] ";
    $t['{colspan}'] = $withLogo;
    //Fin para el encabezado pdf y excel
    $responce['tabla'] = reporteHtml($t, 'rhu_pri_rol_pago.html');
    //var_dump($grid['rol']);
    $responce['success'] = true;
    if (!isset($echo))
        $obBD_con1->echoJson($responce);
    else {
        echo $responce['tabla'];
        exit();
    }
}

if (isset($printRolIndAjax)) {
    $datos = $_GET;
    $omitCols = array();
    if (!empty($datos['omitirColsRol'])) {
        if (is_string($datos['omitirColsRol'])) {
            $dec = json_decode($datos['omitirColsRol'], true);
            if (is_array($dec)) {
                foreach ($dec as $cn) {
                    if ($cn !== '' && $cn !== null) {
                        $omitCols[$cn] = true;
                    }
                }
            }
        } elseif (is_array($datos['omitirColsRol'])) {
            foreach ($datos['omitirColsRol'] as $cn) {
                if ($cn !== '' && $cn !== null) {
                    $omitCols[$cn] = true;
                }
            }
        }
    }
    $rolOmit = function ($name) use ($omitCols) {
        return !empty($omitCols[$name]);
    };
    $omitCeroFila = isset($datos['imprimirAll']) ? filter_var($datos['imprimirAll'], FILTER_VALIDATE_BOOLEAN) : false;
    $rol_cod_abonos = !empty($datos['Rol_Cod']) ? $datos['Rol_Cod'] : (isset($Rol_Cod) ? $Rol_Cod : 0);

    $rol_pago = $obBD_con1->getRowConsulta(16, $datos, $obBD_conexion);
    $grid = $obBD_con1->getGridRol($rol_pago['Map_Cod'], $obBD_conexion, false);
    $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => "empresas.Emp_Cod=$Ses_Emp_Cod"), $obBD_conexion);
    $obBD_con1->utf8_change_param($grid);
    $t = array('{representante}' => $empresa['Emp_Rep'], '{contador}' => $empresa['Emp_Con'], '{Emp_Nom}' => $Ses_Emp_Nom, '{Rol_Con}' => $rol_pago['Rol_Con'], '{Rol_Range}' => "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]", '{Rol_Type}' => 'Rol ' . ($rol_pago['Rol_Tip'] == 'M' ? 'Mensual' : ($rol_pago['Rol_Tip'] == 'Q' ? 'Quincenal' : ($rol_pago['Rol_Tip'] == 'BS' ? 'BiSemanal' : 'Semanal'))), '{data}' => '', '{efectivo}' => '', '{cheque}' => '', '{otros}' => '');

    $fil_plan_headers = array('{header_empresa}' => '', '{header_excel}' => '');
    if (isset($print)) {
        $startDate = new DateTime($rol_pago['Rol_Fei']);
        $endDate = new DateTime($rol_pago['Rol_Fef']);
        $weekNumber = ceil(($startDate->format('z') / 7) + 1);
        $weekText = ($rol_pago['Rol_Tip'] === 'S') ? "Semana #" . $weekNumber . "<br>" : "";
        $fil_plan_headers['{header_empresa}'] = $obBD_con1->getReportHeader(
            $Ses_Suc_Cod,
            'ROL DE PAGOS',
            $weekText . "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]",
            $obBD_conexion,
            false,
            10,
            isset($print),
            true
        );
    } else {
        $fil_plan_headers['{header_excel}'] = '<tr><td colspan="10" align="center" style=" font-weight: bold;font-size:16px;">{Emp_Nom}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:14px;">{Rol_Type}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:12px;">{Rol_Range}</td></tr>
        <tr><td colspan="10"></td></tr>';
    }

    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);

    $responce['tabla'] = '<style> @media all { div.saltopagina{ display: none; } } @media print{ div.saltopagina{ display:block; page-break-before:always; } } </style>';
    $long = count($roles);
    foreach ($roles as $i => $r) {
        $filas = array('ingreso' => array(), 'egreso' => array());
        foreach ($grid['rol'] as $f) {
            if ($rolOmit($f['Cam_Var'])) {
                continue;
            }
            if ($omitCeroFila && ($f['Cam_Tip'] === 'I' || $f['Cam_Tip'] === 'E') && $f['Cam_Vis'] === 'S') {
                $vk = '{' . $f['Cam_Var'] . '}';
                if (!isset($r[$vk])) {
                    continue;
                }
                $rawV = $r[$vk];
                if (is_numeric($rawV)) {
                    if (abs((float) $rawV) < 1e-9) {
                        continue;
                    }
                } else {
                    $n = floatval(preg_replace('/[^\d\.\-]/', '', (string) $rawV));
                    if (abs($n) < 1e-9) {
                        continue;
                    }
                }
            }
            if (($f['Cam_Tip'] === 'I') && $f['Cam_Vis'] === 'S') {
                array_push($filas['ingreso'], $f);
            }
            if (($f['Cam_Tip'] === 'E') && $f['Cam_Vis'] === 'S') {
                array_push($filas['egreso'], $f);
            }
        }
        $max = (count($filas['ingreso']) > count($filas['egreso']) ? count($filas['ingreso']) : count($filas['egreso']));
        $html = '';
        for ($j = 0; $j < $max; $j++) {
            if (isset($filas['ingreso'][$j])) {
                $html .= '<tr><td colspan="3">&nbsp;' . $filas['ingreso'][$j]['Cam_Des'] . '</td><td align="right" data-formatcode="0.00">{' . $filas['ingreso'][$j]['Cam_Var'] . '}</td>';
            } else {
                $html .= '<tr><td colspan="4"></td>';
            }
            if (isset($filas['egreso'][$j])) {
                $html .= '<td colspan="3">&nbsp;' . $filas['egreso'][$j]['Cam_Des'] . '</td><td align="right" data-formatcode="0.00">{' . $filas['egreso'][$j]['Cam_Var'] . '}</td></tr>';
            } else {
                $html .= '<td colspan="4"></td><td colspan="2"></td></tr>';
            }
        }
        $fil_plan = array_merge($fil_plan_headers, array('{filas}' => $html));
        $plantilla = reporteHtml($fil_plan, 'rhu_pri_rol_ind.html');

        $abonos = $obBD_con1->getArrayConsulta('det_an_rol.selectWhere', array('clean' => true, 'where' => array('Con_Cod' => $r['Con_Cod'], 'Rol_Cod' => $rol_cod_abonos, 'Ant_Tip' => 'B'), 'join' => array('antici_rol' => array('on' => 'det_an_rol.Ant_Cod=antici_rol.Ant_Cod', 'cols' => array()))), $obBD_conexion);
        if (count($abonos) > 0) {
            foreach ($abonos as $ab) {
                switch ($ab['Pag_Cod']) {
                    case 1:
                        $r['{efectivo}'] = 'X';
                        break;
                    case 3:
                        $r['{cheque}'] = 'X';
                        break;
                    default:
                        $r['{otros}'] = 'X';
                        break;
                }
            }
        }
        $r['{total_letras}'] = num2letras($r['{total_rol}']) . ' DOLARES AMERICANOS';
        $responce['tabla'] .= '<table style="width:700px;font-size:11px;table-layout:fixed;border-collapse:collapse" cellpadding="2">' . reporteArray(array_merge($t, $r), $plantilla) . '</table>' . (($i + 1) != $long ? '<div class="saltopagina"></div>' : '');
    }

    $responce['success'] = true;
    if (!isset($echo)) {
        $obBD_con1->echoJson($responce);
    } else {
        echo $responce['tabla'];
        exit();
    }
}


if (isset($rolesAjax)) {
    $data = $_GET;
    $responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
    foreach ($responce['rows'] as &$v) {
        if ($v['Usu_Cod'] != null) {
            $usua = $obBD_con1->getRowConsulta(48, $v['Usu_Cod'], $obBD_conexion);
            $v['Usuario'] = $usua['Usuario'];
        }
    }
    unset($v);
    $responce['records'] = count($responce['rows']);
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}
if (isset($getRolDetail)) {
    $responce = $obBD_con1->getGridRol($Map_Cod, $obBD_conexion, false);
    $rol_pago = $obBD_con1->getRowConsulta(16, array('Rol_Cod' => $Rol_Cod), $obBD_conexion);
    $responce['Rol_Cod'] = $Rol_Cod;
    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod' => $Rol_Cod), $obBD_conexion, false);
    $responce['grid']['caption'] = $rol_pago['Rol_Con'];
    array_push($responce['grid']['colModel'], array('label' => '&nbsp;', 'name' => 'act1', 'width' => 40, 'align' => 'center', 'viewable' => false, 'title' => false, 'formatter' => 'printRolIndFormater'));
    array_push($responce['grid']['colModel'], array('label' => '&nbsp;', 'name' => 'act2', 'width' => 40, 'align' => 'center', 'viewable' => false, 'title' => false, 'formatter' => 'descargarRolIndFormater'));
    $responce['success'] = true;
    $responce['edit'] = false; //unset($responce['rol']);
    $obBD_con1->echoJson($responce);
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
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Rol Pago Consultar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=510"></script>
    <style></style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Gestión de Roles</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="main-search">
                <div class="row">
                    <form id="formSearchRol" action="javascript:searchRoles();">
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset ">
                                <legend class="Titulos2">Plantilla Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Area:</label>
                                        <div class="col-xs-9">
                                            <select id="Are_Cod" name="Are_Cod" class="form-control input-xs">
                                                <option value="">TODAS</option>
                                                <?php $rs_area = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_area as $row) { ?>
                                                    <option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Plantilla:</label>
                                        <div class="col-xs-9">
                                            <select id="Map_Cod" name="Map_Cod" class="form-control input-xs">
                                                <option value="">TODAS</option>
                                                <?php $rs_maps = $obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_maps as $row) {
                                                ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                                                                                                    }
                                                                                                                                        ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-7">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos Generales</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Periodo:</label>
                                        <div class="col-xs-3">
                                            <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="" required="">
                                                <?php $rs_perio = $obBD_con1->getArrayConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_perio as $row) {
                                                ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                                                                                                                                                        }
                                                                                                                                                                                            ?>
                                                <option value="ALL" selected>TODOS</option>
                                                <option value="RANGE">POR RANGO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group date-ranges">
                                        <label class="col-xs-2 control-label label-xs ">Desde:</label>
                                        <div class="col-xs-3">
                                            <input name="ini" type="text" id="ini" class="form-control input-xs" disabled="" />
                                        </div>
                                        <label class="col-xs-2 control-label label-sm ">Hasta:</label>
                                        <div class="col-xs-3">
                                            <input name="fin" type="text" id="fin" class="form-control input-xs" disabled="" />
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="col-xs-2 center vcenter" style="height: 70px;"><button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button></div>

                    </form>
                    <div class="col-xs-12" style="min-height: 250px;">
                        <table id="comp"></table>
                        <div id="listPager"></div>
                    </div>
                </div>
            </div>

            <div id="rol-sdetail" style="display: none;">
                <div class="row">
                    <div class="col-xs-3 detalle">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Plantilla Rol</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Area:</label>
                                    <div class="col-xs-9">
                                        <span name="Are_Des" class="form-control input-xs"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Plantilla:</label>
                                    <div class="col-xs-9">
                                        <span name="Map_Des" class="form-control input-xs"></span>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-xs-3 detalle">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Datos Generales</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Periodo:</label>
                                    <div class="col-xs-9">
                                        <span name="Anio" class="form-control input-xs"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tipo:</label>
                                    <div class="col-xs-9">
                                        <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger" onchange="updateDias()" disabled="">
                                            <option value="M" data-dias="30" data-period="12">Mensual</option>
                                            <option value="Q" data-dias="15" data-period="24">Quincenal</option>
                                            <option value="BS" data-dias="14">BiSemanal</option>
                                            <option value="S" data-dias="7">Semanal</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                   <!-- <div class="col-xs-3 detalle">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Seleccionar columnas para generar el reporte en Excel</legend>
                            <span class="control-label label-xs" style="display: flex;align-items:center">
                                <input style="margin:0" type="checkbox" id="check_box_cargo" name="check_box_cargo"><span style="margin-left: 4px;font-size:12px"> Excluir columna Cargo</span>
                            </span>
                            <span class="control-label label-xs" style="display: flex;align-items:center">
                                <input style="margin:0" type="checkbox" id="imprimi_col_cero" name="imprimi_col_cero"> <span style="margin-left: 4px;font-size:12px"> Excluir columnas con total igual a cero</span>
                            </span>

                            <span class="control-label label-xs" style="display: flex;align-items:center">
                                <input style="margin:0" type="checkbox" id="check_col_hs" name="check_col_hs"> <span style="margin-left: 4px;font-size:12px"> Excluir columna Can.Hrs.Suple y Can.Hrs.Extrao.</span>
                            </span>
                             <span class="control-label label-xs" style="display: flex;align-items:center">
                                <input style="margin:0" type="checkbox" id="check_col_jorn" name="check_col_jorn"> <span style="margin-left: 4px;font-size:12px"> Ocultar columnas de Jornada.</span>
                            </span>
                        </fieldset>
                    </div>-->



                    <div class="col-xs-3 detalle">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Rol</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Numero:</label>
                                    <div class="col-xs-3">
                                        <span name="Rol_Num" class="form-control input-xs" style="text-align: right;"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold alert-info">Desde:</span>
                                            <span name="Rol_Fei" class="form-control"></span>
                                            <span class="input-group-addon bold alert-info">Hasta:</span>
                                            <span name="Rol_Fef" class="form-control"> </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>



                    <style>
                        .ui-jqgrid .ui-jqgrid-htable .ui-th-div {
                            display: flex !important;
                            height: 35px;
                            font-size: 10px;
                            align-items: center;
                            justify-content: center;
                        }
                    </style>

                    <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                        <table id="rol"></table>
                        <div id="rolPager"></div>
                    </div>


                    <div class="col-xs-12">
                        <button type="button" class="btn btn-sm btn-info" onclick="abrirModalVariablesRol();" title="Listado de variables (Cam_Var) de la plantilla del rol"><i class="glyphicon glyphicon-list-alt"></i> Omitir columnas</button>
                        <button class="btn btn-sm btn-inverse" onclick="$('#rol-sdetail').moveComp('#main-search').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                        <button class="btn btn-sm btn-success exportRoles" onclick="printRoles($(this).data('originaldata'))"><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>
                        <button class="btn btn-sm btn-success exportRoles" onclick="printRolDetailIndiv($(this).data('originaldata'))"><i class="glyphicon glyphicon-print"></i> Rol Individual</button>
                        <button class="btn btn-sm btn-success exportRoles" onclick="exportRoles($(this).data('originaldata'))"><i class="glyphicon glyphicon-download"></i> Excel Rol Grupal</button>
                        <button class="btn btn-sm btn-success exportRoles" onclick="exportRolesIndiv($(this).data('originaldata'));"><i class="glyphicon glyphicon-download"></i> Excel Rol Individual</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        //        var gridComp=$("#comp"),
        //            groupAnioArea={
        //                    groupField: ["Anio","Are_Des"], groupColumnShow: [false,false],
        //                    groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>","<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
        //                    groupOrder: ["asc","asc"], groupSummary: [false], groupCollapse: false
        //                },
        //            groupAnio={
        //                    groupField: ["Anio"], groupColumnShow: [false],
        //                    groupText: ["<div><span style='float:left;'><b> &nbsp;-&nbsp; Periodo {0} &nbsp;-&nbsp; </b></span><span style='float:right;'> {1} Area(s)</span></div>"],
        //                    groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
        //                },
        //            groupArea={
        //                    groupField: ["Are_Des"], groupColumnShow: [false],
        //                    groupText: ["<div><span style='float:left;'> <b> &nbsp;&nbsp;Area: {0} &nbsp;&nbsp; </b> </span><span style='float:right;'> {1} Rol(es)</span></div>"],
        //                    groupOrder: ["asc"], groupSummary: [false], groupCollapse: false
        //                };  
        //        
        //        function searchRoles(){
        //            $.getDataJson(gridComp,$('#formRol').getData('rolesAjax'),function (r){               
        //                var area=$('#Are_Cod').val(),periodo=$('#Pec_Cod').val();
        //                if(area!==''&&periodo!=='ALL'&&periodo!=='RANGE')
        //                    gridComp.jqGrid('groupingRemove', true);
        //                else if(area===''&&periodo!=='ALL'&&periodo!=='RANGE')
        //                    gridComp.jqGrid('groupingGroupBy','Are_Des',groupArea);
        //                else if(area!==''&&(periodo==='ALL'||periodo==='RANGE'))
        //                    gridComp.jqGrid('groupingGroupBy','Anio',groupAnio);
        //                else
        //                    gridComp.jqGrid('groupingGroupBy',['Anio','Are_Des'],groupAnioArea);
        //                gridComp.setRows(r['rows']).jqGrid('setCaption','ROLES '+(periodo==='RANGE'?' - DESDE '+$('#ini').val()+' HASTA '+$('#fin').val():(periodo!=='ALL'?' - PERIODO: '+$('#Pec_Cod option:selected').data('year'):''))+(area!==''?' - AREA: '+$('#Are_Cod option:selected').text():''));
        //            });
        //        };
        //        function detallarRoles(data){ 
        //            $('.exportRoles').attr('data-originaldata',$.jsonParser(data)); 
        //            data['Anio']='Periodo '+data['Anio'];
        //            $('.detalle').setData(data);            
        //            $.getDataJson( "",$.extend(data,{getRolDetail:true}), function( response ) {
        //                if($('#rol')[0].grid) {$.jgrid.gridUnload('#rol'); } $grid=$("#rol").createGrid($.extend({height:300},response['grid']),true,'#rolPager',{view:false}).setGroupHeaders(response['header']);
        //                $('#rol').setRows(response['personal']);
        //                $('#main-search').moveComp('#rol-sdetail').updateGridsSizes();
        //            }); 
        //        }
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            createSearchGrid([{
                    label: '&nbsp;',
                    name: 'act2',
                    width: 15,
                    align: 'center',
                    formatter: 'gridButton',
                    formatoptions: {
                        action: ImpCom,
                        title: 'Imprimir Comprobante',
                        icon: 'print',
                        type: 'info'
                    },
                    title: false
                },
                {
                    label: '&nbsp;',
                    name: 'act1',
                    width: 60,
                    align: 'center',
                    viewable: false,
                    title: false,
                    formatter: function(cv, opt, rObj) {
                        if (rObj.Rol_Est === 'I')
                            return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"');
                        return $.getGridButton(printRoles, {
                                Rol_Cod: rObj.Rol_Cod,
                                Map_Cod: rObj.Map_Cod
                            }, 'Imprimir Roles', 'print', null, 'info') + '&nbsp;' +
                            $.getGridButton(exportRoles, {
                                Rol_Cod: rObj.Rol_Cod,
                                Map_Cod: rObj.Map_Cod
                            }, 'Descargar Excel', 'download', null, 'info') + '&nbsp;' +
                            $.getGridButton(detallarRoles, rObj) + '&nbsp;';
                    }
                }
            ]);
            //            $.createDateRange('#ini','#fin');
            //            $('#Pec_Cod').on('change',function(){ if($(this).val()==='RANGE'){ $('.date-ranges :input').removeAttr('disabled','disabled'); }else{ $('.date-ranges :input').attr('disabled','disabled'); }  });
        });

        function ImpCom(rObj) {
            $.getDataJson('', {
                'cargarReportes': true
            }, function(res) {
                var reportes = res['reportes'];
                console.log(rObj);
                $.varValid(reportes[2]) ? $.imprimirUrl(reportes[2] + '?codigo=' + rObj.Com_Cod) : $.alert('Sin Reportes Asociados');
            }, function(err) {
                console.log(err['message']);
            });
        }
    </script>





    <div id="imprimirRoles" style="display: none;width: 1200px;"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/xmljs.js"></script>
    <div id="proviDetaDialog" title="Provisiones"></div>
    <div id="modalVariablesRol" style="display:none;">
        <p class="text-muted small" style="margin-bottom:8px;">Columnas del grid (<code>name</code>) en orden. Verde: ingresos; rojo: egresos. <strong>Marque el check en las columnas que no deben incluirse</strong> al imprimir o exportar Excel &laquo;Rol Grupal&raquo; o al usar Rol individual / Excel individual. Las omisiones y la opci&oacute;n &laquo;columnas en cero&raquo; se conservan hasta que las cambie.</p>
        <p class="small" style="margin-bottom:8px;">
            <a href="javascript:void(0)" id="modalVariablesRolTodos">Marcar todos</a>
            &nbsp;|&nbsp;
            <a href="javascript:void(0)" id="modalVariablesRolNinguno">Desmarcar todos</a>
        </p>
        <div class="checkbox" style="margin-bottom:10px;padding:6px 8px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:3px;">
            <label style="margin:0;font-weight:normal;cursor:pointer;display:flex;align-items:flex-start;">
                <input type="checkbox" id="modalOmitirColCero" name="modalOmitirColCero" value="1" style="margin:2px 8px 0 0;flex-shrink:0;">
                <span>Omitir columnas en cero <span class="text-muted small">(no incluir en Rol Grupal los rubros cuyo total del rol sea 0)</span></span>
            </label>
        </div>
        <div id="listaVariablesRol"></div>
    </div>
</BODY>

</HTML>