<?php

/**
 * @abstract Permite
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2016-11-24
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

//var_dump($obBD_con1->getFormula(21,NULL,NULL,$obBD_conexion));
if (isset($getPlantilla)) {
    //if(empty($Map_Cod)){ echo json_encode(array(success=>false,message=>'No se ha seleccionado la Plantilla del Rol de Pagos!'));exit(); }
    $grid = $obBD_con1->getGridRol($Map_Cod, $obBD_conexion);
    //$grid['grid']['footerrow']=true;
    $obBD_con1->echoJson($grid);
}
if (isset($getRoles)) {
    $response = array('success' => true, 'Rol_Tip' => $Rol_Tip, 'numbers' => array());
    $roles = $obBD_con1->getArrayConsulta(13, $_GET, $obBD_conexion);
    foreach ($roles as $v) {
        array_push($response['numbers'], $v['Rol_Num'] * 1);
    }

    //    if($Rol_Tip=='M'){
    //        $response['months']=array();
    //        foreach($roles as $v) {
    //            $m= explode('-',$v['Rol_Fei']);
    //            array_push($response['months'],$m[1]*1);
    //        }
    //    }
    //    if($Rol_Tip=='S'){
    //        $response['weeks']=array();
    //        foreach($roles as $v) {            
    //            array_push($response['weeks'],$v['Rol_Num']*1);
    //        }
    //    }
    $obBD_con1->echoJson($response);
}
if (isset($getDefaults)) {
    $obBD_con1->getRolDefaults($_GET, $obBD_conexion);
    //    $response=array( 'success'=>true, 'defaults'=>array(), 'anticipos_rol_p'=>array(), 'descuentos_rol_p'=>array(), 'prestamos_rol_p'=>array() );     
    //    if(empty($Rol_Fei)) $obBD_con1->echoJson($response);    
    //	$response['personal']=$obBD_con1->getArrayConsulta(9,$_GET, $obBD_conexion,true);
    //    /* valores default */
    //    $defaults=$obBD_con1->getArrayConsulta(30, $Rol_Fei, $obBD_conexion);
    //    foreach ($defaults as $d){ $response['defaults'][$d['Rde_Var']]=$d['Rde_Val']; }
    //    $response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);
    //    /* anticipos descuentos */    
    //    $anticipos=$obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'*', $obBD_conexion);
    //    $descuentos=$obBD_con1->getArrayConsulta(31, $Ses_Emp_Cod.'*'.$Rol_Fei.'*'.$Rol_Fef.'**'.'D', $obBD_conexion);
    //    if(!empty($anticipos)) $response['anticipos_rol_p']=$anticipos;   
    //    if(!empty($descuentos)) $response['descuentos_rol_p']=$descuentos;
    //    $obBD_con1->echoJson($response);      
}
if (isset($getData)) {
    $hoy = isset($Rol_Fei) ? $Rol_Fei : $hoy;
    $defaults = $obBD_con1->getArrayConsulta(30, $hoy, $obBD_conexion);
    $response = array(
        'success' => true,
        'rows' => $obBD_con1->getArrayConsulta(9, $_GET, $obBD_conexion, true),
        'defaults' => array()
    );
    foreach ($defaults as $d) {
        $response['defaults'][$d['Rde_Var']] = $d['Rde_Val'];
    }
    //$response['defaults']['sueldo_bas_medio']=''.(($response['defaults']['sueldo_bas']*1)/2);
    foreach ($response['rows'] as &$v) {
        $v = array_merge($v, $response['defaults']);
        $v['Prs_Ced'] = substr($v['Prs_Ced'], 0, 10);
        $v['tiempo_parcial'] = ($v['Ded_Hrs'] * 1 == 0) ? 'S' : 'N';
        $v['medio_tiempo'] = ($v['Ded_Hrs'] * 1 == 4) ? 'S' : 'N';
        if (!empty($v['Afi_Fei'])) {
        }
    }
    unset($v);
    $obBD_con1->echoJson($response);
}

if (isset($saveRol)) {
    $configs = $obBD_con1->getRowConsulta(23, $Ses_Emp_Cod, $obBD_conexion);

    if (is_string($data)) $data = json_decode(stripslashes($data), true);
   
   
    try {
        if (empty($data) || count($data) == 0) throw new Exception('El Rol no puede estar vacio!');

        $obBD_ins1 =  new Class_Log_Datos_Rol;
        //$obBD_ins1->debug(true);
        $obBD_conexionIns = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
        $obBD_ins1->operacionobBD(14, $rol, $obBD_conexionIns); //crea el rol de pagos
        $Rol_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
        foreach ($data as $d) {
            //if(!empty($d['dias'])||$d['dias']*1>0)
            foreach ($fields as $f) { //echo $f['Cam_Var']. '<br/>';
                $obBD_ins1->operacionobBD(15, array('Rol_Cod' => $Rol_Cod, 'Cam_Cod' => $f['Cam_Cod'], 'Con_Cod' => $d['Con_Cod'], 'Rol_Val' => $d[$f['Cam_Var']]), $obBD_conexionIns); // inserta los valores de cada campo del rol
            }
            //$obBD_ins1->echoLog($d);
            //$obBD_ins1->echoLog($extra_fields);
            foreach ($extra_fields as $k => $ef) {
                //if(isset($d[$k.'_data']))$obBD_ins1->echoLog($d[$k.'_data']);
                if (isset($d[$k . '_data']) && !empty($d[$k . '_data']) && count($d[$k . '_data']) > 0) {
                    foreach ($d[$k . '_data'] as $e) {
                        $obBD_ins1->operacionobBD(44, array_merge(array('Rol_Cod' => $Rol_Cod, 'id' => $e[$ef['id_field']]), $ef), $obBD_conexionIns);
                    }
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

            //ChromePhp::log("Proveedore:".$Prv_Cod);

            /* Cabecera del Comprobante */

           // //ChromePhp::log("perido:::: ".$rol['Pec_Cod']." - ".  $Prv_Cod. " - ".$Com_Num." - ".  $Com_Fec." - ".$campo );
            $obBD_ins1->operacionobBD(24, $rol['Pec_Cod'] . '*' . $Prv_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . "Rol Pago $rol[Rol_Fei] hasta $rol[Rol_Fef]" . '*' . $campo, $obBD_conexionIns, true);
            $Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            
            
            $obBD_ins1->operacionobBD(25, $Com_Cod . '*' . $Rol_Cod, $obBD_conexionIns); // relacion rol comprobante

            $total = $obBD_con1->getArrayConsulta(7, array('Map_Cod' => $rol['Map_Cod'], 'type' => 'T', 'var' => 'total_rol'), $obBD_conexion);
            $campos = $obBD_con1->getArrayConsulta(7, array('Map_Cod' => $rol['Map_Cod'], 'type' => array('I', 'E'), 'sum' => 'S'), $obBD_conexion);
            $cols =  array_merge($campos, $total);
            foreach ($cols as $v) { //busco las cuentas                    
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
                $obBD_ins1->operacionobBD(24, $rol['Pec_Cod'] . '*' . $Prv_Cod . '*' . $Com_Num_Provi . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi_Provi['Tia_Cod'] . '*' . $t_provi . '*' . "Provision Rol $rol[Rol_Fei] hasta $rol[Rol_Fef]" . '*' . $campo, $obBD_conexionIns);
                $Com_Cod_Provi = $obBD_ins1->insercionid($obBD_conexionIns);
                $obBD_ins1->operacionobBD(25, $Com_Cod_Provi . '*' . $Rol_Cod, $obBD_conexionIns); // relacion rol comprobante Provision

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
            }
        }
        $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $responce = array('success' => false, 'message' => 'No se logro guardar el Rol de Pagos <br/><span style="color:red;">' . $e->getMessage() . '</span>', 'error' => $e->getMessage());
        $obBD_con1->echoJson($responce);
    }
    if ($obBD_ins1->Error == 0) {
        $responce = array('success' => true, 'Rol_Cod' => $Rol_Cod, 'Com_Cod' => isset($Com_Cod) ? $Com_Cod : NULL, 'Com_Cod_Provi' => isset($Com_Cod_Provi) ? $Com_Cod_Provi : NULL);
    } else {
        $responce = array('success' => false, 'message' => $obBD_ins1->MsgError."No se ha logrado realizar la Transaccion", 'error' => $obBD_ins1->MsgError);
    }
    $reporte = $obBD_con1->reportesExa("/con_alt_compr__._.php", $Ses_Emp_Cod, $obBD_conexion);
    $responce['Com_Link'] = "" . (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=";
    $responce['Rol_Link'] = baseUrl("../../rrhh/FRONT/rhu_alt_rol_gestion.php") . "?printAjax=1&echo=1&Rol_Cod=$Rol_Cod";
    $responce['Rol_Ind_Link'] = baseUrl("../../rrhh/FRONT/rhu_alt_rol_gestion.php") . "?printRolIndAjax=1&echo=1&Rol_Cod=$Rol_Cod";
    //ChromePhp::log($obBD_ins1->MsgError);
    $obBD_con1->echoJson($responce);
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Rol Pago Registar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script type="text/ecmascript" src="../VALIDACIONES/rhu_val_roles.js?x=660"></script>
    <style>
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Generar Rol de Pagos</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

            <div>
                <div class="row">
                    <form id="formRol">
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset ">
                                <legend class="Titulos2">Plantilla Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Area:</label>
                                        <div class="col-xs-9">
                                            <select id="Are_Cod" name="Are_Cod" class="form-control input-xs" onchange="if(this.value!=='') getDataGrid(this.value); else $grid.clearGridData();" required="">
                                                <option value="">Seleccione...</option>
                                                <?php $rs_area = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_area as $row) {?>
                                                    <option value="<?php echo $row['Are_Cod']; ?>"><?php echo $row['Are_Des']; ?></option><?php
                                                }?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Plantilla:</label>
                                        <div class="col-xs-9">
                                            <select id="Map_Cod" name="Map_Cod" class="form-control input-xs" onchange="if(this.value!=='') recreateGrid(this.value)" required="">
                                                <option value="">Seleccione...</option>
                                                <?php $rs_maps = $obBD_con1->getArrayConsulta(10, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_maps as $row) {?>
                                                    <option value="<?php echo $row['Map_Cod']; ?>"><?php echo $row['Map_Des']; ?></option><?php
                                                }?>
                                            </select>
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
                                        <label class="col-xs-3 control-label label-xs required">Periodo:</label>
                                        <div class="col-xs-9">
                                            <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" onchange="setRoles();" required="">
                                                <?php $rs_perio = $obBD_con1->getArrayConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
                                                foreach ($rs_perio as $row) {
                                                ?><option value="<?php echo $row['Pec_Cod']; ?>" data-year="<?php echo $row['Periodo']; ?>">Periodo <?php echo $row['Periodo']; ?></option><?php
                                                                                                                                                                                        }
                                                                                                                                                                                            ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Tipo:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_Tip" name="Rol_Tip" class="form-control input-xs readOnly datatrigger" onchange="updateDias()" disabledt="">
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
                            <fieldset class="exa-fieldset ranges M Q S BS" style="display: none;">
                                <legend class="Titulos2">Rango</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group ranges M Q">
                                        <label class="col-xs-3 control-label label-xs required">Mes:</label>
                                        <div class="col-xs-9">
                                            <div class="input-group input-group-xs">
                                                <input id="Month" name="Month" type="hidden">
                                                <span id="Mes" class="form-control"></span>
                                                <span class="input-group-btn">
                                                    <button id="MonthButton" onclick="$('#Month').monthpicker('show','#Mes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group ranges Q">
                                        <label class="col-xs-3 control-label label-xs required">Quincena:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_Q" class="form-control input-xs datatrigger" onchange="setRange();">
                                                <option value="0">Seleccione..</option>
                                                <option value="1">Primera Quincena</option>
                                                <option value="2">Segunda Quincena</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group ranges S BS">
                                        <label class="col-xs-3 control-label label-xs required">Semana:</label>
                                        <div class="col-xs-9">
                                            <select id="Rol_S" class="form-control input-xs datatrigger" onchange="setSemana();"></select>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                        </div>
                        <div class="col-xs-3">
                            <fieldset class="exa-fieldset Rol_Range">
                                <legend class="Titulos2">Rol</legend>
                                <div class="form-horizontal normal">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                        <div class="col-xs-9">
                                            <input type="number" id="Rol_Num" name="Rol_Num" class="form-control input-xs" readonly="" style="text-align: right;" value="1" min="1" step="1" required="" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon bold alert-info">Desde:</span>
                                                <input id="Rol_Fei" name="Rol_Fei" type="text" class="form-control span" style="text-align: right;" readonly="" tabindex="-1">
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
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button id="btnGuardar" type="button" onclick="validaRol()" class="btn btn-primary btn-save"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                        <button id="btnReset" type="button" onclick="resetAll();" class="btn btn-primary btn-new hidden" disabled=""><span class="glyphicon glyphicon-floppy-disk"></span> Nuevo</button>
                    </div>
                    <div class="col-xs-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div id="proviDetaDialog" title="Provisiones"></div>
    <div id="detallesRol">
        <style>
            #detalleRolTabs .panels-area div[id^="tabs"] {
                padding: 10px 0 0 0;
            }
        </style>
        <div class="form-horizontal normal">
            <div class="form-group">
                <label class="col-xs-3 control-label label-xs">Personal:</label>
                <div class="col-xs-9"><input type="text" id="detaPersona" name="detaPersona" class="form-control input-xs" readonly="" tabindex="-1" /></div>
            </div>
        </div>
        <div id="detalleRolTabs" class="ui-tab-fix noBg" style="margin-top: 0px;">
            <ul>
                <li><a href="#tabs-1">Anticipos</a></li>
                <li><a href="#tabs-2">Descuentos</a></li>
                <li><a href="#tabs-3">Prestamos</a></li>
            </ul>
            <div class="panels-area form-horizontal normal ">
                <div id="tabs-1">
                    <table id="anticipos_rol_p"></table>
                </div>
                <div id="tabs-2">
                    <table id="descuentos_rol_p"></table>
                </div>
                <div id="tabs-3">
                    <table id="prestamos_rol_p"></table>
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
            <button type="button" id="impRoles" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Rol de Pagos" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>
            <button type="button" id="impRolesInd" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Roles Individual" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Rol Individual</button>
            <button type="button" id="impCompr" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Rol" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Compr. Rol </button>
            <button type="button" id="impComprProv" onclick="$.imprimirUrl($(this).data('url'))" style="display: inline;" title="Imprimir Comprobante de Provisiones" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Compr. Provision </button>
        </center>
    </div>

    <script type="text/javascript">
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
        $(function() {
            $('#Month').attr('data-monthplacer', '#Mes').createMonthPicker({
                showYear: false,
                prepend: 'Seleccione Mes',
                openOnFocus: false
            }, setRange).monthpicker('setMonthActive', 0);;
            recreateGrid($('#Map_Cod').val());
            setRoles();
        });



        //Desaparece los tipos que no han sido seleccionados
        //Oculta los campos del select Tipo, que no fueron seleccionados (WB)
        $(document).ready(function() {
            $('#Rol_Tip').change(function() {
                var selectedValue = $(this).val();
                $('#Rol_Tip option:hidden').show();
                $('#Rol_Tip option').not(':selected').hide();
            });
        });
    </script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>