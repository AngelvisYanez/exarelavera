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


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Rol;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($getDefaults)) {
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);
}
if (isset($rolesAjax)) {
    $data = $_GET;
    $responce['rows'] = $obBD_con1->getArrayConsulta(16, $data, $obBD_conexion);
    foreach ($responce['rows'] as &$v) {
        $com = $obBD_con1->getRowConsulta(29, $v['Rol_Cod'] . '*' . 'RL', $obBD_conexion);
        $v['Com_Cod'] = $com['Com_Cod'];
        $com_provi = $obBD_con1->getRowConsulta(29, $v['Rol_Cod'] . '*' . 'AS', $obBD_conexion);
        $v['Com_Cod_Provi'] = $com_provi['Com_Cod'];
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
    $responce = $obBD_con1->getGridRol($Map_Cod, $obBD_conexion);
    $rol_pago = $obBD_con1->getRowConsulta(16, array('Rol_Cod' => $Rol_Cod), $obBD_conexion);
    $responce['Rol_Cod'] = $Rol_Cod;
    $responce['personal'] = $obBD_con1->getListRoles(array('Rol_Cod' => $Rol_Cod), $obBD_conexion, false);

    //for each por cada numero de cedula para obtener labores_total, labores_ingreso, labores_egreso
    $labores = $obBD_con1->getRowConsulta(54, array('Plantilla' => $Map_Cod), $obBD_conexion);
    if ($labores['labores'] == '1') {
        foreach ($responce['personal'] as &$persona) {
            $dataSemanal = array('Semana' => $Rol_Num, 'Periodo' => $Pec_Cod, 'Personal' => $persona['Per_Cod']);
            $totalSemanal = $obBD_con1->getRowConsulta(53, $dataSemanal, $obBD_conexion);
            $persona['labores_total'] = $totalSemanal['totalSemanal'];

            if (!empty($persona['labores_total'])) {
                if ($persona['medio_tiempo'] == '1') {
                    $diferencia = number_format($persona['labores_total'] - (($persona['sueldo'] * 12) / $semanas / 2), 2);
                } else {
                    $diferencia = number_format($persona['labores_total'] - ($persona['sueldo'] * 12) / $semanas, 2);
                }

                if ($diferencia > 0) {
                    $persona['labores_ingreso'] = $diferencia;
                    $persona['labores_egreso'] = "0.00";
                }
                if ($diferencia < 0) {
                    $persona['labores_egreso'] = abs($diferencia);
                    $persona['labores_ingreso'] = "0.00";
                }
            }
        }
    }


    $responce['grid']['caption'] = $rol_pago['Rol_Con'];
    array_push($responce['grid']['colModel'], array('label' => '&nbsp;', 'name' => 'edit', 'width' => 60, 'align' => 'center', 'viewable' => false, 'title' => false, 'hidden' => true));
    $responce['success'] = true;
    //unset($responce['rol']);
    $obBD_con1->echoJson($responce);
}
if (isset($saveRol)) {
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod, $obBD_conexion);
    $Rol_Cod = $rol['Rol_Cod'];
    $Com_Cod = $rol['Com_Cod'];
    $Com_Cod_Provi = $rol['Com_Cod_Provi'];
    $edit = false;;
    if (is_string($data)) $data = json_decode(stripslashes($data), true);

    try {
        $obBD_ins1 =  new Class_Log_Datos_Rol;
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns->conexion);
        $obBD_ins1->operacionobBD(14, $rol, $obBD_conexionIns); //crea el rol de pagos  
        foreach ($data as $d) {
            if ($d['edit'] == 'S') {
                $edit = true;
                foreach ($fields as $f) {
                    //echo $f['Cam_Var']. '<br/>';
                    $obBD_ins1->operacionobBD(15, array('Rol_Cod' => $Rol_Cod, 'Cam_Cod' => $f['Cam_Cod'], 'Con_Cod' => $d['Con_Cod'], 'Rol_Val' => $d[$f['Cam_Var']], 'edit' => 'S'), $obBD_conexionIns); // inserta los valores de cada campo del rol
                }
            }
        }
        if ($configs['Cof_Con'] == 'S') {
            /* PARA EL COMPROBANTE CONTABLE */
            $t_rubros = $totales['total_ingr'];
            $Com_Con = $rol['Rol_Con'];
            $Com_Fec = $rol['Rol_Fef'];
            $meseCom = explode('-', $Com_Fec);
            $campo = 'Prv_Cod'; // campos para el asiento
            $Tia_Asi = $obBD_con1->getRowConsulta(26, "D*RL", $obBD_conexion);
            if (!isset($Tia_Asi['Tia_Cod']) || empty($Tia_Asi['Tia_Cod'])) throw new Exception('Revisar el tipo de asiento: <u>Roles de Pago</u>!');
            $Com_Num = $obBD_con1->getComNumAuto($Ses_Emp_Cod, $Tia_Asi['Tia_Cod'], $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo                
            $Prv_Cod = $obBD_con1->getProveeClie($Ses_Emp_Cod, $campo, $obBD_conexion);
            /* Cabecera del Comprobante */
            $obBD_ins1->operacionobBD(24, $rol['Pec_Cod'] . '*' . $Prv_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . "Rol Pago $rol[Rol_Fei] hasta $rol[Rol_Fef]" . '*' . $campo . '*' . $Com_Cod, $obBD_conexionIns);
            if (empty($Com_Cod)) {
                $Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns->conexion);
                $obBD_ins1->operacionobBD(25, $Com_Cod . '*' . $Rol_Cod, $obBD_conexionIns); // relacion rol comprobante
            } else $obBD_ins1->operacionobBD(28, $Com_Cod, $obBD_conexionIns); // elimino los asientos

            $total = $obBD_con1->getArrayConsulta(7, array('Map_Cod' => $rol['Map_Cod'], 'type' => 'T', 'var' => 'total_rol'), $obBD_conexion);
            $campos = $obBD_con1->getArrayConsulta(7, array('Map_Cod' => $rol['Map_Cod'], 'type' => array('I', 'E'), 'sum' => 'S'), $obBD_conexion);
            $cols =  array_merge($campos, $total);
            foreach ($cols as $v) { //busco las cuentas
                //var_dump($v['Cam_Var'],empty($totales[$v['Cam_Var']]));
                if (!isset($totales[$v['Cam_Var']])) throw new Exception('Revisar los valores del campo: <u>' . $v['Cam_Des'] . '</u>!');
                if (is_numeric($totales[$v['Cam_Var']]) && $totales[$v['Cam_Var']] * 1 > 0) {
                    $cuenta = $obBD_con1->getRowConsulta(22, $v['Cam_Cod'] . '*' . $rol['Are_Cod'] . '*' . $rol['Pec_Cod'], $obBD_conexion);
                    if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del campo: <u>' . $v['Cam_Des'] . '</u>!');
                    $obBD_ins1->operacionobBD(27, $Com_Cod . '*' . ($v['Cam_Tip'] == 'I' ? 'D' : 'H') . '*' . $totales[$v['Cam_Var']] . '*' . $cuenta['Pld_Des'] . '*' . $v['Cam_Des'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento          
                }
            }
            /* PARA PROVISIONES */
            if (('0' . $t_provi) * 1 > 0) {
                $Tia_Asi_Provi = $obBD_con1->getRowConsulta(26, "D*AS", $obBD_conexion);
                if (!isset($Tia_Asi['Tia_Cod']) || empty($Tia_Asi_Provi['Tia_Cod'])) throw new Exception('Revisar el tipo de asiento: <u>Asientos de Provision</u>!');
                $Com_Num_Provi = $obBD_con1->getComNumAuto($Ses_Emp_Cod, $Tia_Asi_Provi['Tia_Cod'], $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo 
                /* Cabecera del Comprobante Provision */
                $obBD_ins1->operacionobBD(24, $rol['Pec_Cod'] . '*' . $Prv_Cod . '*' . $Com_Num_Provi . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi_Provi['Tia_Cod'] . '*' . $t_provi . '*' . "Provision Rol $rol[Rol_Fei] hasta $rol[Rol_Fef]" . '*' . $campo . '*' . $Com_Cod_Provi, $obBD_conexionIns);
                if (empty($Com_Cod_Provi)) {
                    $Com_Cod_Provi = $obBD_ins1->insercionid($obBD_conexionIns->conexion);
                    $obBD_ins1->operacionobBD(25, $Com_Cod_Provi . '*' . $Rol_Cod, $obBD_conexionIns); // relacion rol comprobante Provision
                } else $obBD_ins1->operacionobBD(28, $Com_Cod_Provi, $obBD_conexionIns); // elimino los asientos    

                $provi = $obBD_con1->getArrayConsulta(7, array('Map_Cod' => $rol['Map_Cod'], 'type' => 'P'), $obBD_conexion);
                foreach ($provi as $v) { //busco las cuentas                    
                    if (!isset($totales[$v['Cam_Var']])) throw new Exception('Revisar los valores del campo provision: <u>' . $v['Cam_Des'] . '</u>!');
                    if (is_numeric($totales[$v['Cam_Var']]) && $totales[$v['Cam_Var']] * 1 > 0) {
                        // DEBE
                        $cuentaD = $obBD_con1->getRowConsulta(22, $v['Cam_Cod'] . '*' . $rol['Are_Cod'] . '*' . $rol['Pec_Cod'] . '*' . 'D', $obBD_conexion);
                        if (!isset($cuentaD['Pld_Cod']) || empty($cuentaD['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable acreedora del campo provision: <u>' . $v['Cam_Des'] . '</u>!');
                        $obBD_ins1->operacionobBD(27, $Com_Cod_Provi . '*' . 'D' . '*' . $totales[$v['Cam_Var']] . '*' . $cuentaD['Pld_Des'] . '*' . $v['Cam_Des'] . '*' . $cuentaD['Pld_Cod'], $obBD_conexionIns);  // inserta asiento          
                        // HABER
                        $cuentaH = $obBD_con1->getRowConsulta(22, $v['Cam_Cod'] . '*' . $rol['Are_Cod'] . '*' . $rol['Pec_Cod'] . '*' . 'H', $obBD_conexion);
                        if (!isset($cuentaH['Pld_Cod']) || empty($cuentaH['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable deudora del campo provision: <u>' . $v['Cam_Des'] . '</u>!');
                        $obBD_ins1->operacionobBD(27, $Com_Cod_Provi . '*' . 'H' . '*' . $totales[$v['Cam_Var']] . '*' . $cuentaH['Pld_Des'] . '*' . $v['Cam_Des'] . '*' . $cuentaH['Pld_Cod'], $obBD_conexionIns);  // inserta asiento          
                    }
                }
            } else {
                if (!empty($Com_Cod_Provi)) {
                    $obBD_ins1->operacionobBD(50, $Com_Cod_Provi, $obBD_conexionIns);
                    $obBD_ins1->operacionobBD(51, $Com_Cod_Provi, $obBD_conexionIns);
                }
            }
        }
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $responce = array('success' => false, 'message' => $e->getMessage());
        $obBD_con1->echoJson($responce);
    }
    if ($obBD_ins1->Error == 0) {
        $responce = array('success' => true, 'Rol_Cod' => $Rol_Cod, 'Com_Cod' => $Com_Cod, 'Com_Cod_Provi' => $Com_Cod_Provi, 'edit' => true);
    } else {
        $responce = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    $reporte = $obBD_con1->reportesExa("/con_alt_compr__._.php", $Ses_Emp_Cod, $obBD_conexion);
    $responce['Com_Link'] = "" . (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=";
    $responce['Rol_Link'] = baseUrl("../../rrhh/FRONT/rhu_alt_rol_gestion.php") . "?printAjax=1&echo=1&Rol_Cod=$Rol_Cod";
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Rol Pago Modificar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=509"></script>
    <style></style>
</HEAD>

<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar de Roles</h3>
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
                                        <label class="col-sm-3 control-label label-xs">Area:</label>
                                        <div class="col-sm-9">
                                            <select id="Are_Cod" name="Are_Cod" class="form-control input-xs">
                                                <option value="">TODAS</option>
                                                <?php $rs_area = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_area as $row) {
                                                ?><option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                                                                                                            }
                                                                                                                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label label-xs">Plantilla:</label>
                                        <div class="col-sm-9">
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
                                        <label class="col-sm-2 control-label label-xs">Periodo:</label>
                                        <div class="col-sm-3">
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
                                        <label class="col-sm-2 control-label label-xs ">Desde:</label>
                                        <div class="col-sm-3">
                                            <input name="ini" type="text" id="ini" class="form-control input-xs" disabled="" />
                                        </div>
                                        <label class="col-sm-2 control-label label-sm ">Hasta:</label>
                                        <div class="col-sm-3">
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
                    <form id="formRol" action="javascript:" class="detalle">
                        <div class="col-xs-3">
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
                        <div class="col-xs-3">
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
                                                <option value="BS" data-dias="14">Bi Semanal</option>
                                                <option value="S" data-dias="7">Semanal</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-xs-3">
                            <input name="Pec_Cod" type="text" style="display: none">
                            <input name="Are_Cod" type="text" style="display: none">
                            <input name="Rol_Cod" type="text" style="display: none">
                            <input name="Com_Cod" type="text" style="display: none">
                            <input name="Com_Cod_Provi" type="text" style="display: none">
                            <input name="Map_Cod" type="text" style="display: none">
                        </div>
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs">Numero:</label>
                                        <div class="col-xs-3">
                                            <span name="Rol_Num" class="form-control input-xs databind" style="text-align: right;"></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold alert-info">Desde:</span>
                                                <input name="Rol_Fei" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
                                                <span class="input-group-addon bold alert-info">Hasta:</span>
                                                <input name="Rol_Fef" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
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
                                justify-content: center;
                                align-items: center;
                            }
                        </style>


                        <div class="col-xs-12">
                            <div class="form-horizontal normal">
                                <div class="form-group">
                                    <label class="col-xs-2 control-label label-xs">Concepto:</label>
                                    <div class="col-xs-8">
                                        <input type="text" name="Rol_Con" class="form-control input-xs" />
                                    </div>
                                    <?php if ($Ses_Emp_Cod == 429 || $Ses_Emp_Cod == 1) { ?>
                                        <div class="col-xs-2">
                                            <button type="button" onclick="actualizarLabores();" class="btn btn-sm btn-success btn-save"><span class="glyphicon glyphicon-refresh"></span>Actualizar valores</button>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="col-xs-12" id="gridContainer" style="padding-bottom: 8px; min-height: 300px;">
                        <table id="rol"></table>
                        <div id="rolPager"></div>
                    </div>
                    <div class="col-xs-12">
                        <button class="btn btn-inverse" onclick="$('#rol-sdetail').moveComp('#main-search').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                        <button type="button" onclick="validaRol()" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--INICIO DEL DIALOGO IMPRIMIR -->
    <div id="successDialog" title="Mensaje del Sistema">
        <center>
            <h4>El Comprobante se ha registrado con Exito!</h4>
        </center>
        <center>
            <button type="button" id="impRoles" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Rol de Pagos" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Pagos</button>
            <button type="button" id="impCompr" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Rol" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Rol </button>
            <button type="button" id="impComprProv" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Provisiones" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Comprob. Provision </button>
        </center>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            createSearchGrid([{
                label: '&nbsp;',
                name: 'act1',
                width: 30,
                align: 'center',
                viewable: false,
                title: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: detallarRoles,
                    conditional: function(o) {
                        return o.Pagos !== 'S' && o.Rol_Est === 'A';
                    },
                    caseFalse: function(o) {
                        if (o.Rol_Est !== "A") return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"');
                        return $.createIcon('lock orange', false, 'title="Contiene Pagos!"');
                    }
                }
            }]);
        });
    </script>
    <div id="proviDetaDialog" title="Provisiones"></div>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>