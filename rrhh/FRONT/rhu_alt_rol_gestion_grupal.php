<?php

/**
 * @abstract Permite realizar la consulta de roles de pagos grupales
 * @author Alejadro Camacho
 * @version 1.0
 * Fecha de creacion  2024/05/26
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/rhu_log_roles_grupal.php');

$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
$obBD_con1 =  new Class_Log_Datos_Rol;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($getPlantilla)) {
    $grid = $obBD_con1->getGridRol($Map_Cod, $obBD_conexion);
    $obBD_con1->echoJson($grid);
}

if (isset($getRolDetail)) {

    $responce = $obBD_con1->getGridRol($Map_Cod, $obBD_conexion, false);

    //Obtener roles de pago
    $roles = $obBD_con1->getArrayConsulta(10, array('Are_Cod' => $Are_Cod, 'Map_Cod' => $Map_Cod, 'Pec_Cod' => $Pec_Cod, 'Rol_Tip' => $Rol_Tip, 'Rol_I' => $Rol_I, 'Rol_F' => $Rol_F), $obBD_conexion);
    $rolesCodigos = array();
    foreach ($roles as $rol) {
        $rolesCodigos[] = $rol['Rol_Cod'];
    }
    $rolesCadena = "(" . implode(", ", $rolesCodigos) . ")";

    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod' => $rolesCadena), $obBD_conexion, false);
    $responce['grid']['caption'] = "PAGO ROLES DE CONSULTA GRUPAL";
    array_push($responce['grid']['colModel'], array('label' => '&nbsp;', 'name' => 'act1', 'width' => 40, 'align' => 'center', 'viewable' => false, 'title' => false, 'formatter' => 'printRolIndFormater'));
    array_push($responce['grid']['colModel'], array('label' => '&nbsp;', 'name' => 'act2', 'width' => 40, 'align' => 'center', 'viewable' => false, 'title' => false, 'formatter' => 'descargarRolIndFormater'));
    $responce['success'] = true;
    $responce['edit'] = false;
    $obBD_con1->echoJson($responce);
}

if (isset($printAjax)) {
    $datos = $_GET;
    $roles = $obBD_con1->getArrayConsulta(10, array('Are_Cod' => $Are_Cod, 'Map_Cod' => $Map_Cod, 'Pec_Cod' => $Pec_Cod, 'Rol_Tip' => $Rol_Tip, 'Rol_I' => $Rol_I, 'Rol_F' => $Rol_F), $obBD_conexion);
    $rolesCodigos = array();
    foreach ($roles as $rol) {
        $rolesCodigos[] = $rol['Rol_Cod'];
    }
    $rolesCadena = "(" . implode(", ", $rolesCodigos) . ")";
    $datos['Rol_Cod'] = $rolesCodigos[0];
    $rol_pago = $obBD_con1->getRowConsulta(16, $datos, $obBD_conexion);
    $grid = $obBD_con1->getGridRol($rol_pago['Map_Cod'], $obBD_conexion, false);
    $obBD_con1->utf8_change_param($grid);
    $datos['Rol_Cod'] = $rolesCadena;
    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);

    $t = array('{Emp_Nom}' => $Ses_Emp_Nom, '{Rol_Con}' => $rol_pago['Rol_Con'], '{Rol_Range}' => "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]", '{ingHeader}' => '', '{egrHeader}' => '', '{ingSpan}' => 0, '{egrSpan}' => 0, '{data}' => '', '{rol_border}' => 'border:' . (isset($print) ? '1px solid gray;' : '0.1pt solid black;'), '{Rol_Campos_Ingreso}' => '', '{Rol_Campos_Egreso}' => '');
    $aux = array('{Rol_i}' => '', '{Prs_Ced}' => '', '{Prs_Ape}' => '', '{Prs_Nom}' => '', '{dias}' => '', '{Tic_Des}' => '');
    $filas = '<tr>    
            <td style="{rol_border} " align="center">{Rol_i}</td>
            <td style="{rol_border} mso-number-format:&#39;@&#39;;">{Prs_Ced}</td>
            <td style="{rol_border} ">{Prs_Ape}</td>
            <td style="{rol_border} ">{Prs_Nom}</td>            
            <td style="{rol_border} ">{Tic_Des}</td>
            <td style="{rol_border} " align="center">{dias}</td>
            {Rol_Campos_Ingreso}
            <td style="{rol_border} " align="right">{total_ingr}</td>
            {Rol_Campos_Egreso}
            <td style="{rol_border} " align="right">{total_egr}</td>
            <td style="{rol_border} " align="right">{total_rol}</td>
            <td style="{rol_border} " align="right" height="40" width="100"></td>
        </tr>';
    foreach ($grid['rol'] as $f) {
        if (($f['Cam_Tip'] === 'I') && $f['Cam_Vis'] === 'S') {
            $t['{ingHeader}'] .= "<td style='{rol_border} '>$f[Cam_Dec]</td>";
            $t['{ingSpan}']++;
            $t['{Rol_Campos_Ingreso}'] .= '<td style="{rol_border} " align="right">{' . $f['Cam_Var'] . '}</td>';
        }
        if (($f['Cam_Tip'] === 'E') && $f['Cam_Vis'] === 'S') {
            $t['{egrHeader}'] .= "<td style='{rol_border} '>$f[Cam_Dec]</td>";
            $t['{egrSpan}']++;
            $t['{Rol_Campos_Egreso}'] .= '<td style="{rol_border} " align="right">{' . $f['Cam_Var'] . '}</td>';
        }
        if ($f['Cam_Vis'] === 'S') $aux['{' . $f['Cam_Var'] . '}'] = 0;
    }
    $f = reporteArray($t, $filas);

    foreach ($roles as $r) {
        $t['{data}'] .= reporteArray($r, $f);
        foreach ($grid['rol'] as $fd) if ($fd['Cam_Vis'] === 'S') $aux['{' . $fd['Cam_Var'] . '}'] += ($r['{' . $fd['Cam_Var'] . '}'] * 1);
    }
    foreach ($grid['rol'] as $fd) if ($fd['Cam_Vis'] === 'S') $aux['{' . $fd['Cam_Var'] . '}'] = formato_numero($aux['{' . $fd['Cam_Var'] . '}'], 2, 1);
    $aux['{dias}'] = '';
    $t['{data}'] .= reporteArray($aux, $f);
    //var_dump($rol['roles']);
    $t['{maxSpan}'] = $t['{ingSpan}'] + $t['{egrSpan}'] + 10;
    $t['{header_empresa}'] = $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ROL DE PAGO GRUPAL', "Desde $datos[Rol_I] Hasta $datos[Rol_F]", $obBD_conexion, false, $t['{maxSpan}'], isset($print), true);
    $responce['tabla'] = reporteHtml($t, 'rhu_pri_rol_pago_grupal.html');
    //var_dump($grid['rol']);
    $responce['success'] = true;
    if (!isset($echo))
        $obBD_con1->echoJson($responce);
    else {
        echo $responce['tabla'];
        exit();
    }
}



// IMPRIMIR ROL INDIVIDUAL GRUPAL
if (isset($printRolIndGrupAjax)) {
    $datos = $_GET;
    $rol_pago = $obBD_con1->getRowConsulta(16, $datos, $obBD_conexion);
    $rol_fei_year = date('Y', strtotime($rol_pago['Rol_Fei'])); //obtencion del año en el que inicia el rol
    $rol_fef_year = date('Y', strtotime($rol_pago['Rol_Fef'])); //obtencion del año en el que finaliza el rol
    //ChromePhp::log($rol_pago);
    $grid = $obBD_con1->getGridRol($rol_pago['Map_Cod'], $obBD_conexion, false);
    $empresa = $obBD_con1->getRowConsulta('empresas.selectWhere', array('where' => "empresas.Emp_Cod=$Ses_Emp_Cod"), $obBD_conexion);
    $obBD_con1->utf8_change_param($grid);
    $t = array('{representante}' => $empresa['Emp_Rep'], '{contador}' => $empresa['Emp_Con'], '{Emp_Nom}' => $Ses_Emp_Nom, '{Rol_Con}' => $rol_pago['Rol_Con'], '{Rol_Range}' => "Desde $rol_pago[Rol_Fei] Hasta $rol_pago[Rol_Fef]", '{Rol_Type}' => 'Rol ' . ($rol_pago['Rol_Tip'] == 'M' ? 'Mensual' : ($rol_pago['Rol_Tip'] == 'Q' ? 'Quincenal' : ($rol_pago['Rol_Tip'] == 'BS' ? 'BiSemanal' : 'Semanal'))), '{data}' => '', '{efectivo}' => '', '{cheque}' => '', '{otros}' => '');
    $filas = array('ingreso' => array(), 'egreso' => array());
    $obBD_con1->utf8_change_param($filas);
    foreach ($grid['rol'] as $f) {
        if (($f['Cam_Tip'] === 'I') && $f['Cam_Vis'] === 'S') {
            array_push($filas['ingreso'], $f);
        }
        if (($f['Cam_Tip'] === 'E') && $f['Cam_Vis'] === 'S') {
            array_push($filas['egreso'], $f);
        }
    }
    $max = (count($filas['ingreso']) > count($filas['egreso']) ? count($filas['ingreso']) : count($filas['egreso']));
    $html = '';
    for ($i = 0; $i < $max; $i++) {

        if (isset($filas['ingreso'][$i])) {
            // //ChromePhp::log($filas['ingreso'][$i]['Cam_Des']);
            // bloque de codigo comentado segun lo solicitado para que se pueda visualizar dos parametros anteriormente ocultos
            // if ($filas['ingreso'][$i]['Cam_Des'] != "CANTIDAD HORAS EXTRAORDINARIA"  &&  $filas['ingreso'][$i]['Cam_Des'] != "CANTIDAD SUPLEMENTARIA") {
                $html .= '<tr><td colspan="3">&nbsp;' . $filas['ingreso'][$i]['Cam_Des'] . '</td><td align="right" data-formatcode="0.00">{' . $filas['ingreso'][$i]['Cam_Var'] . '}</td>';
            // } else {
            //     $html .= '<tr><td colspan="3">&nbsp;</td><td align="right" data-formatcode="0.00"></td>';
            // }
        } else {
            $html .= '<tr><td colspan="4"></td>';
        }
        if (isset($filas['egreso'][$i])) {

            $html .= '<td colspan="3">&nbsp;' . $filas['egreso'][$i]['Cam_Des'] . '</td><td align="right" data-formatcode="0.00">{' . $filas['egreso'][$i]['Cam_Var'] . '}</td></tr>';
        } else {
            $html .= '<td colspan="4"></td><td colspan="2"></td></tr>';
        }
    }
    $fil_plan = array('{filas}' => $html, '{header_empresa}' => '', '{header_excel}' => '');
    if (isset($print)) {
        foreach ($rol_pago as $fila) {
            // Bloque comentado para sustitucion de funciones revertir si no funciona la actualizacion
            // if ($datos['Rol_I'] == $fila["Rol_Num"]) {
            //     $fecha_inicio = obtenerInicioSemana($fila["Rol_Fei"], date("Y"), "start");
            // }
            // if ($datos['Rol_F'] == $fila["Rol_Num"]) {
            //     $fecha_fin = obtenerFinSemana($fila["Rol_Fef"],  date("Y"), "end");
            // }

            if ($datos['Rol_I'] == $fila["Rol_Num"]) {
                $fecha_inicio = date('d-m-Y', strtotime($_GET['Rol_Fecha_Inicio']));
            } else {
                $fecha_inicio = date('d-m-Y', strtotime($_GET['Rol_Fecha_Inicio']));
            }

            if ($datos['Rol_F'] == $fila["Rol_Num"]) {
                $fecha_fin = date('d-m-Y', strtotime($_GET['Rol_Fecha_Fin']));
            } else {
                $fecha_fin = date('d-m-Y', strtotime($_GET['Rol_Fecha_Fin']));
            }
        }
        $fil_plan['{header_empresa}'] = $obBD_con1->getReportHeader($Ses_Suc_Cod, 'ROL DE PAGOS', "Desde $fecha_inicio Hasta $fecha_fin ", $obBD_conexion, false, 10, isset($print), true);
    } else {
        $fil_plan['{header_excel}'] = '<tr><td colspan="10" align="center" style=" font-weight: bold;font-size:16px;">{Emp_Nom}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:14px;">{Rol_Type}</td></tr>
        <tr><td colspan="10" align="center" style="font-weight: bold;font-size:12px;">{Rol_Range}</td></tr>
        <tr><td colspan="10"></td></tr>';
    }
    $plantilla = reporteHtml($fil_plan, 'rhu_pri_rol_grupal.html');
    $roles_data = $obBD_con1->getArrayConsulta(10, array('Are_Cod' => $datos["Are_Cod"], 'Map_Cod' => $datos["Map_Cod"], 'Pec_Cod' => $datos["Pec_Cod"], 'Rol_Tip' => $datos["Rol_Tip"], 'Rol_I' => $datos["Rol_I"], 'Rol_F' => $datos["Rol_F"]), $obBD_conexion);
    foreach ($roles_data as $rol) {
        $rolesCodigos[] = $rol['Rol_Cod'];
    }
    $datos['Rol_Cod'] = "(" . implode(", ", $rolesCodigos) . ")";
    $roles = $obBD_con1->getListRoles($datos, $obBD_conexion);
    $obBD_con1->utf8_change_param($roles);
    $responce['tabla'] = '<style> @media all { div.saltopagina{ display: none; } } @media print{ div.saltopagina{ display:block; page-break-before:always; } } </style>';
    $long = count($roles);
    //ChromePhp::log("CONTADOR: " . count($roles));
    foreach ($roles as $i => $r) {
        $abonos = $obBD_con1->getArrayConsulta('det_an_rol.selectWhere', array('clean' => true, 'where' => array('Con_Cod' => $r['Con_Cod'], 'Rol_Cod' => $Rol_Cod, 'Ant_Tip' => 'B'), 'join' => array('antici_rol' => array('on' => 'det_an_rol.Ant_Cod=antici_rol.Ant_Cod', 'cols' => array()))), $obBD_conexion);
        if (count($abonos) > 0) foreach ($abonos as $ab) {
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
        $r['{total_letras}'] = num2letras($r['{total_rol}']) . ' DOLARES AMERICANOS';
        $responce['tabla'] .= '<table style="width:700px;font-size:11px;table-layout:fixed;border-collapse:collapse" cellpadding="2">' . reporteArray(array_merge($t, $r), $plantilla) . '</table>' . (($i + 1) != $long ? '<div class="saltopagina"></div>' : '');
    }
    $responce['success'] = true;
    if (!isset($echo))
        $obBD_con1->echoJson($responce);
    else {
        echo $responce['tabla'];
        exit();
    }
}

// Bloque comentado para sustitucion de funciones revertir si no funciona la actualizacion
// function obtenerFinSemana($semana, $year, $aux) {
//     $fechaInicio = new DateTime("$year-01-01");
//     if ($fechaInicio->format('N') != 1) {
//         $fechaInicio->modify('next Monday');
//     }
//     $fechaInicio->modify('+' . ($semana - 1) . ' weeks');
//     return $fechaInicio->format('d-m-Y');
// }

// function obtenerInicioSemana($semana, $year) {
//     $fechaInicio = new DateTime("$year-01-01");
//     if ($semana > 1) {
//         $diasSumar = ($semana - 1) * 7;
//         $fechaInicio->modify("+$diasSumar days");
//     }
//     return $fechaInicio->format('d-m-Y');
// }

?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo $Ses_Sys_Nom; ?></title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles_grupal.js"></script>
</head>

<body>

    <div class="panel panel-main">

        <div class="panel-heading exa-header">
            <h3 class="panel-title">Consultar roles grupales</h3>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <form id="formRol" action="javascript:detallarRoles();">
                    <div class="col-xs-3">
                        <fieldset class="exa-fieldset ">
                            <legend class="Titulos2">Filtros</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Area:</label>
                                    <div class="col-xs-9">
                                        <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" required="">
                                            <option value="">Seleccione...</option>
                                             <option value="0"><?php echo "TODOS"; ?></option>
                                            <?php $rs_area = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_area as $row) { ?>
                                                <option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Plantilla:</label>
                                    <div class="col-xs-9">
                                        <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" required="" onchange="if(this.value!=='') recreateGrid(this.value)">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_maps = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_maps as $row) {
                                            ?><option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-xs-3">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Periodo</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Periodo:</label>
                                    <div class="col-xs-9">
                                        <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="$('#Rol_Tip').removeAttr('disabled');" required="">
                                            <option value="">Seleccione...</option>
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_perio as $row) {
                                            ?>
                                                <option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option>

                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                                    <div class="col-xs-9">
                                        <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs" onchange="setSemanasAnio(this);" required="" disabled>
                                            <option value="">Seleccione...</option>
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

                    <div class="col-xs-3">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Rango</legend>
                            <div class="form-horizontal normal">
                                <div class="form-group ranges S BS">
                                    <label class="col-xs-3 control-label label-xs required">Inicio:</label>
                                    <div class="col-xs-9">
                                        <select id="Rol_I" name="Rol_I" class="form-control input-xs" required="" onchange="validarRango(this);"></select>
                                        <input type="text" id="Rol_Fecha_Inicio" name="Rol_Fecha_Inicio" class="form-control input-xs" readonly style="display: none;">
                                        <script>
                                            $('#Rol_I, #Rol_F, #Pec_Cod').change(function() {
                                                var year = $('#Pec_Cod option:selected').data('year');
                                                var weekNumber = $('#Rol_I').val();
                                                if (year && weekNumber) {
                                                    var date = new Date(year, 0, 1 + (weekNumber - 1) * 7);
                                                    var dayOfWeek = date.getDay();
                                                    var ISOweekStart = date;
                                                    if (dayOfWeek <= 4)
                                                        ISOweekStart.setDate(date.getDate() - date.getDay() + 1);
                                                    else
                                                        ISOweekStart.setDate(date.getDate() + 8 - date.getDay());
                                                    $('#Rol_Fecha_Inicio').val(ISOweekStart.toISOString().split('T')[0]);
                                                }
                                            });
                                        </script>
                                    </div>
                                </div>
                                <div class="form-group ranges S BS">
                                    <label class="col-xs-3 control-label label-xs required">Fin:</label>
                                    <div class="col-xs-9">
                                        <select id="Rol_F" name="Rol_F" class="form-control input-xs" required="" onchange="validarRango(this);"></select>
                                        <input type="text" id="Rol_Fecha_Fin" name="Rol_Fecha_Fin" class="form-control input-xs" readonly style="display: none;">
                                        <script>
                                            $('#Rol_F, #Pec_Cod').change(function() {
                                                var year = $('#Pec_Cod option:selected').data('year');
                                                var weekNumber = $('#Rol_F').val();
                                                if (year && weekNumber) {
                                                    var date = new Date(year, 0, 1 + (weekNumber - 1) * 7);
                                                    var dayOfWeek = date.getDay();
                                                    var ISOweekEnd = date;
                                                    if (dayOfWeek <= 4)
                                                        ISOweekEnd.setDate(date.getDate() - date.getDay() + 7);
                                                    else
                                                        ISOweekEnd.setDate(date.getDate() + 7 - date.getDay());
                                                    $('#Rol_Fecha_Fin').val(ISOweekEnd.toISOString().split('T')[0]);
                                                }
                                            });
                                        </script>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-xs-2 center vcenter" style="height: 70px;">
                        <button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i> Buscar</button>
                    </div>
                </form>
                <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                    <table id="rol"></table>
                    <div id="rolPager"></div>
                </div>
                <div class="col-xs-12">
                    <button class="btn btn-sm btn-success exportRoles" onclick="printRolesIndividualGrupal($(this).data('originaldata'));"><i class="glyphicon glyphicon-print"></i> Rol Grupal Individual</button>
                    <button class="btn btn-sm btn-success exportRoles" onclick="exportRolesIndividualGrupal($(this).data('originaldata'));"><i class="glyphicon glyphicon-download"></i>Excel Grupal Individual</button>


                    <button class="btn btn-sm btn-success exportRoles" onclick="printRoles();"><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>
                    <!--<button class="btn btn-sm btn-success exportRoles" onclick="printRolDetailIndiv($(this).data('originaldata'))" ><i class="glyphicon glyphicon-print"></i> Rol Individual</button>-->
                    <button class="btn btn-sm btn-success exportRoles" onclick="exportRoles()"><i class="glyphicon glyphicon-download"></i> Excel Rol Grupal</button>
                    <!--<button class="btn btn-sm btn-success exportRoles" onclick="exportRolesIndiv($(this).data('originaldata'));" ><i class="glyphicon glyphicon-download"></i> Excel Rol Individual</button>-->
                </div>
            </div>
        </div>
    </div>
    <div id="imprimirRoles" style="display: none;width: 1200px;"></div>
    <div id="proviDetaDialog" title="Provisiones"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/xmljs.js"></script>

</body>

</html>