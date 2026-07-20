<?php
/**
 * @abstract Permite actualizar comprobantes automaticos con cuentas de banco. 
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creaci�n  18/10/2017
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Che;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($searchCheque)){
    $obBD_con1->debug(true);
	try {
        $data=$_GET;
        $obBD_con1->getPageGridJson(407,$data,$obBD_conexion);
    } catch (Exception $e) {
        $resp = array('messsage' => $ex->getMessage());
    }
    $obBD_con1->echoJson($resp);
    
}


if (isset($cargarPeriodos)) {
    try {
        $resp['periodos'] = $obBD_con1->getArrayConsulta(6, $Ses_Emp_Cod, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
        } else {
            $resp = array('messsage' => $obBD_con1->MsgError);
        }
    } catch (Exception $ex) {
        $resp = array('messsage' => $ex->getMessage());
    }
    $obBD_con1->echoJson($resp);
}

if (isset($valChe)) {
    //$obBD_con1->debug(true);
    $conteo = $obBD_con1->getRowConsulta(368, $Pld_Cod . '*' . $numero, $obBD_conexion);
    $contar = $obBD_con1->getRowConsulta(404, $Pld_Cod, $obBD_conexion);
    $contar['success'] = true;
    if ($conteo['conteo'] == 0)
        $contar['valid'] = true;
    else
        $contar['valid'] = false;
    $obBD_con1->echoJson($contar);
}
if (isset($cheNum)) {
    $contar = $obBD_con1->getRowConsulta(404, $Ban_Cod, $obBD_conexion);
    $contar['success'] = true;
    echo json_encode($contar);
    exit();
}
if (isset($term)) {
    $contar = $obBD_con1->getArrayConsulta(365, $Ses_Emp_Cod . '*' . $term, $obBD_conexion);
    echo json_encode($contar);
    exit();
}
if (isset($cuenAjax)) {
    $obBD_con1->getPageGridJson(352, $search . '*' . $Ses_Emp_Cod . '*' . (isset($Pec_Cod) ? $Pec_Cod : '') . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
if (isset($clieAjax)) {
    $obBD_con1->getPageGridJson(359, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
if (isset($provAjax)) {
    $obBD_con1->getPageGridJson(351, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}

if (isset($getBancos)) {

    $resp = array('success' => true, 'options' => "<option value=''>Seleccione...</option>");
    $bancos = $obBD_con1->getArrayConsulta(398, $Pec_Cod, $obBD_conexion);
    //var_dump($bancos);
    foreach ($bancos as $v) {
        $resp['options'] = $resp['options'] . "<option value='$v[Pld_Cod]' data--pld_-cod='$v[Pld_Cod]' data--ban_-cod='$v[Ban_Cod]' data--ban_-cue='$v[Ban_Cue]' data--pld_-cdc='$v[Pld_Cdc]' data--pld_-des='" . str_replace("'", '', $v['Pld_Des']) . "'>$v[Pld_Des] (Cta.#: $v[Ban_Cue])</option>";
    }
    $obBD_con1->echoJson($resp);
}

if (isset($searchDocument)) {
    //$obBD_con1->debug(true);
    try {
        $data = $_GET;
        $bancos_array = array();
        if (!isset($data['Pld_Cod']) || empty($data['Pld_Cod'])) {
            $bancos = $obBD_con1->getArrayConsulta(398, $data['Pec_Cod'], $obBD_conexion);
            foreach ($bancos as $v) {
                array_push($bancos_array, $v['Pld_Cod']);
            }
            $data['Pld_Cod'] = implode(",", $bancos_array);
        }
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        $resp = $obBD_con1->getPageGrid(399, $data, $obBD_conexion);
        foreach ($resp['rows'] as &$comprobante) {
            $alert=false;
            if ($comprobante['Has_Cheque']=='si') {
                $CheInactivo=$obBD_con1->getRowConsulta(408,array('Com_Cod' =>$comprobante['Com_Cod']), $obBD_conexion);
                if(isset($CheInactivo['Che_Cod']))
                    $alert=true;
            }
            $comprobante['alert']=$alert;    
        }
        unset($comprobante);

    } catch (Exception $exc) {
        $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}

if (isset($getAsientos)) {
    //$obBD_con1->debug(true);
    $data = $_GET;
    try {
        $asientos = $obBD_con1->getArrayConsulta(400, $data, $obBD_conexion, true);
        $resp = array('success' => true, 'asientos' => $asientos);
    } catch (Exception $exc) {
        $resp = array('success' => false, 'message' => $exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}

if (isset($save)) {
    $obBD_conexionIns = new Class_Log_Conexion_Che($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_Che;
    /* Habilita Debuger de SQLs en Proceso de Guardado de Movimiento de Cheques */
    //$obBD_conIns->debug(true);
    // $obBD_con1->debug(true);
    /* Inicio de Transaccion */
    $obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',null,$Com_Cod,$obBD_conexion);
	$obBD_conIns->inicio_transaccion($obBD_conexionIns);
    try {
        $comprobante['Com_Gen']='M';
        $rs_buscar=array();
        if ($op == "I"){
            $var = "D";
            $tabla = "cliente";
            $campo = "Cli_Cod";
        }else{
            $var = 'H';
            $tabla = "proveedore";
            $campo = "Prv_Cod";
        }
        if (isset($comprobante['Num_Doc']))
            $rs_buscar = $obBD_con1->getArrayConsulta(361, $comprobante['Num_Doc'] . "*" . $Ses_Emp_Cod . '*' . $Pld_Cod . '*' . $var. '*' . $Com_Cod, $obBD_conexion);
        if ($op == 'E' && isset($comprobante['es_cheque']) && $comprobante['es_cheque']==='true') {
            $comprobante['Com_Gen']='A';
//            foreach ($cheques_validar as $row) {
//                $rs_buscar = $obBD_con1->getArrayConsulta(361, $row . "*" . $Ses_Emp_Cod . '*' . $Pld_Cod . '*' . $var, $obBD_conexion);
//                if (count($rs_buscar) == 0) {
//                    throw new Exception('El cheque N.' . row . ' ya ha sido registrado');
//                }
//            }
            
        } else {
            $rs_buscar = $obBD_con1->getArrayConsulta(361, $comprobante['Num_Doc'] . "*" . $Ses_Emp_Cod . '*' . $Pld_Cod . '*' . $var. '*' . $Com_Cod, $obBD_conexion);
        }

        if ((count($rs_buscar) == 0 || $op == 'E') || $comprobante['Num_Doc'] == '') {
            $responce['cheque'] = "";
            /* Inicio de la transaccion */
            /* Mes del comprobante */
            $var_mes = explode('-', $comprobante['Com_Fec']);
            $Com_Num = $obBD_con1->codigoComprAuto($comprobante['Tia_Cod'], $Pec_Cod, $var_mes[1], $obBD_conexion);
            /* Insercion del Comprobante */
            //$Num_Doc2 = $Num_Doc;
            if ($op == "E"|| $op == "D") {
                $comprobante['Prv_Cod']=$comprobante['Codigo'];
                $comprobante['Cli_Cod']='null';
            } else {
                $comprobante['Prv_Cod']='null';
                $comprobante['Cli_Cod']=$comprobante['Codigo'];
            }
            $comprobante['Com_Tip']=$op;
            $comprobante['Usu_Cod']=$Ses_Usu_Cod;
            $comprobante['Com_Cod']=$Com_Cod;
            $comprobante['Com_Num']=$Com_Num;
            /*UPDATE COMPROBANTE*/
            $obBD_conIns->operacionobBD(401,$comprobante,$obBD_conexionIns);
            /*DELETE ASIENTOS*/
            $obBD_conIns->operacionobBD(402,$comprobante,$obBD_conexionIns);

            foreach ($save as $asiento){
                /*INSERT ASIENTOS*/
                $asiento['Asi_Val']=$asiento['Det_Tip']==='H'?$asiento['Haber']:$asiento['Debe'];
                $asiento['Com_Cod']=$Com_Cod;
                $asiento['Asi_Con']=$comprobante['Com_Con'];
                $obBD_conIns->operacionobBD(24,$asiento,$obBD_conexionIns);
                if(isset($cheques)){
                    foreach ($cheques as $cheque){
                        if($cheque['Index']===$asiento['Che_Ind']){
                            $cheque['Prv_Cod']=$comprobante['Prv_Cod'];
                            $cheque['Che_Cod']=1;
                            /*INSERT CHEQUE*/
                            $cheque['Asi_Cod'] = $obBD_conIns->insercionid($obBD_conexionIns);
                            $obBD_conIns->operacionobBD(403,$cheque,$obBD_conexionIns);
                        }
                    }
                }
                
            }
            /* Finaliza la transaccion */
            $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
            if ($obBD_conIns->Error == 0) {
                $responce['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod&tabla=$tabla&campo=$campo&tipo=$comprobante[Tia_Cod]&Pec_Cod=$Pec_Cod";
                $responce['success'] = true;
            } else {
                $responce['success'] = false;
                $responce['error'] = $obBD_conIns->MsgError;
            }
            //$responce['message']=$obBD_con1->MsgError;
        } else {
            $responce['success'] = false;
            $responce['message'] = "El Documento Bancario ya esta Registrado!";
            throw new Exception('El documento bancario ya ha sido registrado');
        }
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $responce['message'] = $ex->getMessage();
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
        $obBD_con1->echoJson($responce);
    }
    $obBD_con1->echoJson($responce);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>		
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Conciliacion Modificar [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>  
        <style>#tabs.ui-widget-content{background:none !important;} .ui-tabs-panel{padding-bottom: 0 !important;}.ui-tabs-nav{padding-top: 0 !important;}
        </style>
        <script type="text/ecmascript" src="../VALIDACIONES/tes_val_lib_ban.js?a=22">
        </script>
        
        <style>
            .swlFlyout{
                height: 60px !important;
                min-width: 150px !important;
                z-index: 9999 !important;
            }
        </style>
    </HEAD>
    <BODY>

        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Libro Bancos</h3></div>        
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="documentoSearch">
                    <div class="row">
                        <form name="searchComprobantes" id="searchComprobantes" class="form-horizontal normal" action="javascript:$('#searchGrid').Search($.extend($('#searchComprobantes').getData(),{'searchDocument':true}));">
                            <div class="col-sm-12 ">
                                <input type="text" id='Order_By' name ='Order_By' class="hidden"/>
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros</legend>
                                    <div class="col-sm-10">   
                                        <div class="form-group">
                                            <label class="col-sm-1 control-label label-sm">Periodo:</label>
                                            <div class="col-sm-4">
                                                <select class="form-control input-sm" id="Pec_Cod" name="Pec_Cod"  required="">
                                                    <option value=0>----</option>
                                                </select>
                                            </div>
                                            <label class="col-sm-1 control-label label-sm">Banco:</label>
                                            <div class="col-sm-5">
                                                <select id="Pld_Cod" name="Pld_Cod" class="form-control input-sm" ></select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-sm-1 control-label label-sm">Comprobante:</label>
                                            <div class="col-sm-4">
                                                <select  class="chzn-select form-control input-xs" id="TipBus" data-placeholder="Comprobante de..."  name="Com_Tip" required="">
                                                    <option value=0><< TODOS >></option>
                                                    <option value=I>Ingreso</option>
                                                    <option value=E>Egreso</option>
                                                    <option value=D>Diario</option>
                                                </select>
                                            </div>
                                            <div id="rango_fechas">
                                                <label class="col-sm-1 control-label label-sm">Desde:</label>
                                                <div class="col-sm-2">
                                                    <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-sm datepicker" style="text-align: center;"/>
                                                </div>
                                                <label class="col-sm-1 control-label label-sm">Hasta:</label>
                                                <div class="col-sm-2">
                                                    <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-sm datepicker" style="text-align: center;"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="align-middle col-sm-2">
                                        <button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm align-middle" title="Buscar Documento"  tabindex="-1" style="vertical-align: middle"><span class="glyphicon glyphicon-search align-middle"></span> <span>Buscar</span></button>
                                    </div>
                                </fieldset>
                            </div>   
                        </form>
                        <div class="col-xs-12" style="min-height: 360px;">
                            <table id="searchGrid" name="searchGrid"></table>
                            <table id="searchGridPager"></table>
                            <div class="Titulos2"></div>
                        </div>
                    </div>

                <div id="chequeDialog" title="Cheques"></div>
                </div>
                <div id="documentoMain" style="visibility: hidden;">
                    <div class="row">  
                        <div class="col-sm-12 form-horizontal normal">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Periodo/Banco</legend>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs required">Seleccione Periodo:</label> 
                                    <div class="col-xs-2">
                                        <select name="perio_cont" id="perio_cont" onchange="setPeriodo()" class="form-control input-sm">
                                            <option value="">Seleccione...</option>
                                        </select>   
                                    </div>
                                    <label class="col-xs-1 control-label label-xs required">Seleccione Banco:</label> 
                                    <div class="col-xs-4">
                                        <select name="bancos" id="bancos" onchange="setBanco()"  class="form-control input-sm readOnly perio_cont" disabled="">
                                            <option value="">Seleccione...</option>
                                        </select>  
                                    </div> 
                                </div>    
                            </fieldset>
                        </div>
                    </div>   
                    <script>
                        var gridComp, valor = 0, numChe = 0, glosa = "", tipo = "Ingresos", dataFromRow = [];
                        function setPeriodo(banco = 0) {
                            $('#cuenDialog').getDialogGrid().clearGrid();
                            var perio_cont = getPeriodo();
                            //console.log(perio_cont);
                            $('#bancos').val('');
                            if (perio_cont['Pec_Cod'] === null) {
                                $('.perio_cont').attr('disabled', 'disabled');
                            } else {
                                cargarBancos(perio_cont['Pec_Cod'], '#bancos', banco);
                                $("input[name='Com_Fec']").dateLimits(perio_cont["Pec_Fei"], perio_cont["Pec_Fef"]);
                                $('.perio_cont').removeAttr('disabled');
                            }
                            resetForm();
                        }
                        function setBanco() {
                            var banco = getBanco();
                            $("input[name='Pld_Cod']").val(banco["Pld_Cod"]);
                            $("input[name='Ban_Cue']").val(banco["Ban_Cue"]);
                            $("input[name='Ban_Cod']").val(banco["Ban_Cod"]);
                            gridComp.clearGrid().updateGridDiario();
                            if (banco["Pld_Cod"] !== null) {
                                if (tipo === "Ingresos")
                                    addFilaCuenta(banco, "D");
                                else
                                    addFilaCuenta(banco, "H");
                            } //resetForm();
                        }
                        function getBanco() {
                            return $('#bancos').val() === '' ? {Pld_Cod: null} : $('#bancos option:selected').data();
                        }
                        function getPeriodo() {
                            return $('#perio_cont').val() === '' ? {Pec_Cod: null} : $('#perio_cont option:selected').data();
                        }
                        var chequeHtml = '';
                    </script> 
                    <form id="periodoForm" class="hidden">
                        <input name="periodo" type="text">
                        <input type="text" name="Com_Cod" />
                        <input type="text" name="Pec_Cod" />
                        <input type="text" name="Pld_Cod" />
                        <input type="text" name="Ban_Cod" />
                        <input type="text" name="Ban_Cue" />
                    </form>                
                    <div class="row">  
                        <div class="col-sm-12">
                            <div id="tabs" class="ui-tab-fix">
                                <ul>
                                    <li><a href="#tabs-1">Ingresos</a></li>
                                    <li><a href="#tabs-2">Egresos</a></li>
                                    <li><a href="#tabs-3">Diario</a></li>
                                    <!--<li><div><select><option value="">Ber</option><</select></div></li>-->
                                </ul>
                                <div class="panels-area form-horizontal normal ">
                                    <!-- FORMULARIO COMPROBANTE DE INGRESO -->
                                    <div id="tabs-1">
                                        <div class="row">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Comprobante de Ingreso</legend>	
                                                <form name="formComp" id="formIngreso" method="post" action="javascript:validaIngreso()" class="">                
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                                                            <div class="col-xs-8">
                                                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                                                    <option value="">Seleccione...</option>
                                                                    <?Php
                                                                    $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(373, "*I", $obBD_conexion);
                                                                    foreach ($row_rs_tipo_asien2 as $row)
                                                                        echo "<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>";
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Cliente:</label> 
                                                            <div class="col-xs-8"> 
                                                                <input type="hidden" id="cod_cli" name="Codigo" value="" data-name='Cli_Cod' /> 
                                                                <div class="input-group input-group-xs">
                                                                    <input id="lblClie" name="clientes" data-name="clientes" onkeydown='if (event.keyCode === 13)
                                                                                buscaCliente();' onchange="if ($('#lblClie').val() === '')
                                                                                            $('#cod_cli').val('');" class="form-control varios clearable" placeholder="Ingrese Cliente"  />
                                                                    <span class="input-group-btn"><a onclick="$('#clieDialog').dialog('open')" title="B&uacute;squeda de Clientes" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                                                </div><!-- /input-group -->  
                                                            </div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConIngreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Observación:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                                                        </div> 
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label> 
                                                            <div class="col-xs-5"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                                                        </div>    
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">No.&nbsp;Externo:</label> 
                                                            <div class="col-xs-5"><input class="form-control input-xs" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">Valor:</label> 
                                                            <div class="col-xs-5">
                                                                <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input class="form-control input-xs" name="Com_Val" id="Com_Val_Ingre"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                                                        </div>                        
                                                    </div>
                                                </form>
                                            </fieldset>
                                        </div>    
                                    </div>
                                    <!-- FIN FORMULARIO COMPROBANTE DE INGRESO -->
                                    <!-- FORMULARIO COMPROBANTE DE EGRESO -->
                                    <div id="tabs-2">
                                        <div class="row">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Comprobante de Egreso</legend>	
                                                <form name="formComp" id="formComp" method="post" action="javascript:validaEgreso()">                
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                                                            <div class="col-xs-8">
                                                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                                                    <option value="">Seleccione...</option>
                                                                    <?Php
                                                                    $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(373, "*E", $obBD_conexion);
                                                                    foreach ($row_rs_tipo_asien2 as $row)
                                                                        echo "<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>";
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Proveedor:</label> 
                                                            <div class="col-xs-8">
                                                                <input type="hidden" id="cod_pvr" name="Codigo" value="" data-name="Prv_Cod" /> 
                                                                <div class="input-group input-group-xs">
                                                                    <input id="lblProvee" name="proveedor" data-name="proveedor" onkeydown='if (event.keyCode === 13)
                                                                                buscaProveeIngreso();' onchange="if ($('#lblProvee').val() === '')
                                                                                            $('#cod_pvr').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor"  />
                                                                    <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                                                </div><!-- /input-group -->  
                                                            </div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConEgreso" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Observación:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Valor:</label> 
                                                            <div class="col-xs-4"><div class="input-group input-group-xs"><span class="input-group-addon">$</span><input  class="form-control input-xs" name="Com_Val" id="Com_Val_Egre" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                                                            <div class="col-xs-4"><div class="checkbox check-big input-xs"><label><input onchange="setCheque($(this));" id="es_cheque" name="es_cheque" checked type="checkbox" value="true" offval="false" />Cheque</label></div></div> 
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-3 control-label label-xs required">Fecha&nbsp;Comp. :</label> 
                                                            <div class="col-xs-5"><input id="confec" name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" onchange="$('#chefec').val($('#confec').val())" class="form-control input-xs" required /></div>   
                                                        </div>  

                                                        <div class="col-sm-12">
                                                            <div id="cheques_grilla">   
                                                                <table id="chequesGrid"></table>
                                                                <div id="chequesPager"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </fieldset>
                                        </div>    
                                    </div>
                                    <!-- FIN FORMULARIO COMPROBANTE DE EGRESO -->
                                    <!-- FORMULARIO COMPROBANTE DE DIARIO -->
                                    <div id="tabs-3">
                                        <div class="row">
                                            <fieldset class="exa-fieldset">
                                                <legend class="Titulos2">Comprobante de Diario</legend>	
                                                <form name="formComp" id="formDiario" method="post" action="javascript:validaDiario()">                
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Tipo&nbsp;Comprobante:</label> 
                                                            <div class="col-xs-8">
                                                                <select class="form-control input-xs"  name="Tia_Cod"  class="isSelectMenu" required>
                                                                    <option value="">Seleccione...</option>
                                                                    <?Php
                                                                    $row_rs_tipo_asien3 = $obBD_con1->getArrayConsulta(373, "*D", $obBD_conexion);
                                                                    foreach ($row_rs_tipo_asien3 as $row)
                                                                        echo "<option value='$row[Tia_Cod]'>$row[Tia_Des]</option>";
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs required">Proveedor:</label> 
                                                            <div class="col-xs-8">
                                                                <input type="hidden" id="cod_pvr2" name="Codigo" value="" data-name="Prv_Cod" /> 
                                                                <div class="input-group input-group-xs">
                                                                    <input id="lblProvee2" name="proveedor"  data-name="proveedor" onkeydown='if (event.keyCode === 13)
                                                                                buscaProveeDiario();' onchange="if ($('#lblProvee2').val() === '')
                                                                                            $('#cod_pvr2').val('');" class="form-control varios clearable" placeholder="Ingrese Proveedor"  />
                                                                    <span class="input-group-btn"><a onclick="$('#provDialog').dialog('open')" title="B&uacute;squeda de Proveedores" class="btn btn-success btn-mini"><i class="glyphicon glyphicon-check"></i></a></span>
                                                                </div><!-- /input-group -->  
                                                            </div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Concepto:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Con" id="ConDiario" onchange=" updateGlosa()" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)" required></textarea></div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-4 control-label label-xs">Observación:</label> 
                                                            <div class="col-xs-8"><textarea class="form-control input-xs" name="Com_Obs" cols="73" style="text-transform:uppercase" onkeypress="return  validar_injections(event)"></textarea></div>
                                                        </div> 
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label> 
                                                            <div class="col-xs-5"><input name="Com_Fec" type="text" style="text-align: center" size="10" maxlength="10" class="form-control input-xs" required /></div>
                                                        </div>    
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">No.&nbsp;Externo:</label> 
                                                            <div class="col-xs-5"><input  class="form-control input-xs" style="text-align: center" name="Num_Doc" type="text" size="10" onkeypress="return  validar_numeric(event)" /></div>
                                                        </div> 
                                                        <div class="form-group">
                                                            <label class="col-xs-2 control-label label-xs required">Valor:</label> 
                                                            <div class="col-xs-5">
                                                                <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input  class="form-control input-xs" name="Com_Val" id="Com_Val_Diario" onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                                                        </div> 
                                                    </div>
                                                </form>
                                            </fieldset>
                                        </div>    
                                    </div>
                                    <!-- FIN FORMULARIO COMPROBANTE DE DIARIO -->
                                </div>   
                            </div>    
                        </div>     
                    </div> 

                    <div class="row">   
                        <div class="col-sm-12">
                            <div id="compGrilla" style="padding-top: 6px;">   
                                <table id="comp"></table>
                                <div id="compPager"></div>
                            </div>
                        </div>
                        <div class="col-sm-12" style="padding-top: 6px;">                    
                            <button  title="Guardar Comprobante" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();" type="button" class="btn btn-inverse start" ><i class="glyphicon glyphicon-arrow-left"></i><span> Atr&aacute;s</span></button><span style="width: 15px;"></span>

                            <button onclick="if ($('bancos').val() === '') {
                                        $.alert('Seleccione el Banco');
                                    } else {
                                        if (tipo === 'Ingresos')
                                            $('#formIngreso').formSubmit();
                                        else if (tipo === 'Egresos')
                                            $('#formComp').formSubmit();
                                        else
                                            $('#formDiario').formSubmit();
                                    }" title="Guardar Comprobante" type="button" class="btn btn-primary start" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span style="width: 15px;"></span>                        
                        </div>
                        <div class="col-sm-12 Titulos2"><hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( &nbsp;<span class="required"></span> ) son campos obligatorios.</div>

                        <script type="text/javascript">
                            $(document).ready(function () {
                                $('#Che_Fec').toggleClass('disabled').find('input').toggleAttr('disabled');
                                gridComp = $("#comp");
                                gridComp.createGrid({
                                    colModel: [
                                        {label: '&nbsp;', name: 'act0', width: 30, align: 'center',
                                            formatter: function (cellvalue, options, rowObject) {
                                                return (rowObject.Pld_Cod * 1 === $('#bancos').val() * 1 ? '' : '<span class="btn btn-success btn-xs" title="Cambiar" onclick="$(\'#cuenDialog\').dialog(\'open\');$(\'#Index\').val(\'' + rowObject.Index + '\');"><i class="glyphicon glyphicon-check"></i></span>');
                                            }
                                        },
                                        {label: 'Cód.Int.', name: 'Index', key: true, width: 15, align: "center", hidden: true},
                                        {label: 'Cód.Int.', name: 'Pld_Cod', width: 20, align: "center", hidden: true},
                                        {label: 'Che. index', name: 'Che_Ind', width: 20, align: "center",hidden: true},
                                        {label: 'Tipo', name: 'Det_Tip', hidden: true},
                                        {label: 'Codigo', name: 'Pld_Cdc', width: 45},
                                        {label: 'Cuenta', name: 'Pld_Des', width: 150},
                                        {label: 'Glosa', name: 'Glosa', width: 150, editable: true},
                                        {label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter: 'currency', editable: true,
                                            formatoptions: {prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.'}, summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum",
                                            editoptions: {dataInit: function (element) {
                                                    gridComp.createInputDiario(element, "D", "Det_Tip");
                                                }}
                                        },
                                        {label: 'Haber', name: 'Haber', width: 50, align: 'right', formatter: 'currency', editable: true,
                                            formatoptions: {prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.'}, summaryTpl: "Total: <b>{0}</b>", summaryType: "sum",
                                            editoptions: {dataInit: function (element) {
                                                    gridComp.createInputDiario(element, "H", "Det_Tip");
                                                }}
                                        },
                                        {label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: function (cv, opts, rObj) {
                                                return ((rObj.Pld_Cod * 1 === $('#bancos').val() * 1 && rObj.Che_Ind) ? '' : $.getGridButton(deleteFilaCuenta, rObj.Index, 'Quitar', 'remove', null, 'danger'));
                                            }}
                                    ],loadComplete:function(data){
                                        if ($.varValid(data.rows))
                                            for (var i = 0, z = data.rows.length; i < z; i++) {
                                                if (data.rows[i]['Che_Ind']*1>0)
                                                    
                                                    gridComp.find("#" + data.rows[i].Index + ' input[name=Haber]').attr('disabled', 'disabled').addClass('readOnly');
                                            }
                                    
                                    }, height: 'auto', caption: "Datos del Asiento Contable", footerrow: true, userDataOnFooter: false // set a footer row
                                }, true, "#compPager", {view: false}).gridButtonAdd({
                                    caption: "Agregar Cuenta", buttonicon: "glyphicon glyphicon-plus", title: 'Agregar Cuenta', id: "add_cuenta", onClickButton: function () {
                                        $('#Index').val('');
                                        $('#cuenDialog').dialog('open');
                                    }
                                });
                                $("#add_cuenta").attr('disabled', 'disabled').addClass('perio_cont');
                                $.clearFooterDiario("#comp", true);
                            });
                            function deleteFilaCuenta(Index) {
                                gridComp.jqGrid('delRowData', Index);
                                resizeGridComp();
                                gridComp.updateGridDiario();
                            }

                            function resizeGridComp() {
                                var w = $('#compGrilla').width();
                                if (gridComp.width() > (w + 2) || gridComp.width() < (w - 2))
                                    gridComp.jqGrid('resizeGrid');
                            }
                            function updateValores() {
                                if (tipo === "Ingresos")
                                    valor = $("#Com_Val_Ingre").val();
                                else if (tipo === "Egresos")
                                    valor = $("#Com_Val_Egre").val();
                                else
                                    valor = $("#Com_Val_Diario").val();
                                $("input[name='Com_Val']").val($.toFixed(valor));
                                $("input[name='Haber']").val($.toFixed(valor));
                                $("input[name='Debe']").val($.toFixed(valor));
                                gridComp.updateGridDiario();
                            }
                            function updateGlosa() {
                                if (tipo === "Ingresos")
                                    glosa = $("#ConIngreso").val();
                                else if (tipo === "Egresos")
                                    glosa = $("#ConEgreso").val();
                                else
                                    glosa = $("#ConDiario").val();
                                $("input[name='Glosa']").val(glosa);
                            }
                            
                            function resetForm() {
                                gridComp.clearGrid().updateGridDiario();
                                var dat_reset = {};
                                $('#formIngreso').setData(dat_reset);
                                $('#formComp').setData(dat_reset);
                                $('#formDiario').setData(dat_reset);
                                $('#es_cheque').prop("checked", true).trigger('change');
                                setChequeNum();
                                valor = 0;
                                glosa = "";
                                if ($("#bancos").val() !== '') {
                                    if (tipo === "Ingresos")
                                        addFilaCuenta(getBanco(), "D");
                                    else
                                        addFilaCuenta(getBanco(), "H");
                                }
                                return false;
                            }
                            function validaGrid(Com_val) {
                                var batch = gridComp.getGridBatch(), ban = true, msg = '';
                                deb = $.round(gridComp.jqGrid("getCol", "Debe", false, "sum")),
                                        hab = $.round(gridComp.jqGrid("getCol", "Haber", false, "sum"));
                                gridComp.startGridEdit().loadUpdate();
                                if ((deb === hab && deb === 0) || batch.length === 0) {
                                    msg = ("El comprobante no puede tener valor <i>cero</i>!");
                                    ban = false;
                                }

                                if (!(deb === hab)) {
                                    msg = ("Los Totales no Coinciden!");
                                    ban = false;
                                }else{
                                    $('input[name=Com_Val]').val(deb);
                                }
                                $.each(batch, function (i, v) {
                                    if (('0' + v[v['Det_Tip'] === 'D' ? 'Debe' : 'Haber']) * 1 === 0) {
                                        msg = ("El valor de la cuenta <u>No. " + (i + 1) + ": " + v['Pld_Des'] + "</u> no puede ser cero!");
                                        ban = false;
                                        return ban;
                                    }
                                });
                                if (ban === false) {
                                    $.alert(msg);
                                    return ban;
                                }
                                return batch;
                            }
                            function saveComprobante(data) {
                                $.saveDataJson("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", data, function (r) {
                                    $('#impCompr').attr('href', r['link']);
                                    if (typeof r['cheque'] === 'undefined' || r['cheque'] === '') {
                                        $('#successDialog').dialog("option", "height", 150);
                                        $('#printCheque').hide();
                                    } else {
                                        $('#printCheque').show();
                                        $('#successDialog').dialog("option", "height", 250);
                                        var html = $('#modelo').html();
                                        html = html.replace(/{banco}/g, $('#bancos').find('option:selected').text());
                                        html = html.replace(/{link}/g, r['cheque']);
                                        $('#printCheque').html(html);
                                    }
                                    if(r['success'] === true)
                                        $('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
                                        $('#searchGrid').trigger('reloadGrid');
                                    $('#successDialog').dialog('open');
                                    return resetForm();
                                });
                            }
                            function validaIngreso() {
                                if ($('#cod_cli').val() === '') {
                                    $.alert("Seleccione El Cliente");
                                    return;
                                }
                                var batch = validaGrid("#Com_Val_Ingre");
                                if (batch === false)
                                    return;
                                var data = $.extend({comprobante:$('#formIngreso').serializeObject()}, {op: 'I', save: batch}, $('#periodoForm').serializeObject());
                                $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?', data, saveComprobante);
                            }
                            function validaEgreso() {
                                if ($('#cod_pvr').val() === '') {
                                    $.alert("Seleccione El Proveedor");
                                    return;
                                }
                                var batch = validaGrid("#Com_Val_Egre");
                                if (batch === false)
                                    return;
                                var data = $.extend({comprobante:$('#formComp').getData()}, {op: 'E', save: batch, cheques: $("#chequesGrid").getGridBatch()}, $('#periodoForm').serializeObject());
                                $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?', data, saveComprobante);
                            }
                            function validaDiario() {
                                if ($('#cod_pvr2').val() === '') {
                                    $.alert("Seleccione El Proveedor");
                                    return;
                                }
                                var batch = validaGrid("#Com_Val_Diario");
                                if (batch === false)
                                    return;
                                var data = $.extend({comprobante:$('#formDiario').serializeObject()}, {op: 'D', save: batch}, $('#periodoForm').serializeObject());
                                $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?', data, saveComprobante);
                            }
                        </script>
                    </div>




                    <div id="cheCreateDialog" title="Nuevo Cheque" style="display:none;">
                        <form class="form-horizontal normal" id="cheForm" action="javascript:validaCheque(this)">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">Datos del Cheque</legend>
                                <input type="text" name="Index" class="hidden"/>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Beneficiario:</label>
                                    <div class="col-xs-9" ><input name="Che_Ben" type="text" class="form-control input-xs" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Numero:</label>
                                    <div class="col-xs-9" ><input name="Che_Num" id="NumChe" type="text" class="form-control input-xs required" required /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Fecha:</label>
                                    <div class="col-xs-9" ><input name="Che_Fec" type="text" class="form-control input-xs required"  required/></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Valor:</label> 
                                    <div class="col-xs-9"><div class="input-group input-group-xs"><span class="input-group-addon">$</span><input  class="form-control input-xs required" name="Che_Val" id="Che_Val" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" /></div></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Observacion:</label>
                                    <div class="col-xs-9" ><textarea name="Che_Obs" type="text" class="form-control input-xs"></textarea></div>
                                </div>
                            </fieldset>
                            <div class="center">
                                <button onclick="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</button>
                            </div>
                            <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                        </form>
                    </div>











                </div>
            </div>
        </div>
        <script type="text/javascript">
            function setPosfecha() {
                $('#chefec')[$('#postfecha').is(':checked') ? 'removeAttr' : 'attr']('disabled', 'disabled').val($('#confec').val());
            }

            function setChequeNum() {
                if (tipo === "Egresos" && $("#bancos").val() !== '' && $('#es_cheque').is(':checked')) {
                    $.get('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {'Ban_Cod': getBanco()["Ban_Cod"], 'cheNum': true}, function (response) {
                        if (response['success'] === true) {
                            numChe = (response['Che_Num'] * 1) + 1;
                            $("#NumChe").val(numChe).alertMsg();
                        } else {
                            numChe = 0;
                            $("#NumChe").val(numChe);
                            $.alert("No se logro obtener n&uacutemero del cheque");
                        }
                    }, 'json').fail(function (error) {
                        $.alert("El Servidor ha fallado en responder!");
                    });
                    ;
                } else {
                    $("#NumChe").val(0).parent().find('.lblMsg').html('').end().find('.imgMsg').removeAttr('src');
                }
            }
            function saveBene() {
                $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", {saveBene: true, apel: $('#beneApe').val(), nomb: $('#beneNom').val()}, function (response) {
                    if (response['success'] === true) {
                        $('#apellido').val($('#beneApe').val());
                        $('#nombre').val($('#beneNom').val());
                        $('#Bene_Id').val(response['id']);
                        $('#addBenef').dialog('close');
                        $('#beneDialog').dialog('close');
                    } else {
                        $.alert(response['message']);
                    }
                }, 'json').fail(function (error) {
                    $.alert("El Servidor ha fallado en responder!");
                });
            }
            $("#tabs").tabs({activate: function (event, ui) {
                    tipo = ui.newTab[0].getElementsByTagName("a")[0].innerHTML;
                    $(ui.newTab[0].getElementsByTagName("a")[0].hash).find('div.row:first-child').effect("highlight", {}, 500);
                    resetForm();
                }});
            $(document).ready(function () {
                //            $.createDialog('#addBenef',150,550,null,null,'plus');
                $.createDialog('#successDialog', 150, 550, null, null, 'ok');
                // DIALOG BUSCAR CUENTAS
                $.createSearchDialog('cuenDialog', [
                    {label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true},
                    {label: 'Codigo', name: 'Pld_Cdc', width: 45},
                    {label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) {
                            return 'style="white-space: normal;"';
                        }},
                    {label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) {
                            return 'style="white-space: normal;"';
                        }},
                    {label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center"},
                    {label: 'Estado', name: 'Pld_Est', width: 30, align: "center"},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false,
                        formatter: function (cv, opts, rObj) {
                            return  $.getGridButton(SelectCta, {Pld_Cod: rObj.Pld_Cod, tipo: 'D'}, 'Seleccione Cuentas','','D') + '&nbsp;' + $.getGridButton(SelectCta,{Pld_Cod:rObj.Pld_Cod, tipo:'H'},'Seleccione Cuentas','','H');
                        }   
                    }
                ], null, null, null, null, {title: 'Cuenta', options: [{label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd'}, {label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]})
                        .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
                // DIALOG BUSCAR PROVEEDOR    
                $.createSearchDialog('provDialog', [
                    {label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20, align: "center", hidden: true, viewable: true},
                    {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50},
                    {label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) {
                            return 'style="white-space: normal;"';
                        }},
                    {label: 'Apellidos', name: 'Prs_Ape', hidden: true},
                    {label: 'Nombres', name: 'Prs_Nom', hidden: true},
                    {label: 'Direcci&oacute;n', name: 'Prs_Dir', hidden: true, viewable: true},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false, formatter: function (cv, opts, rObj) {
                            return $.getGridButton(selectProv, rObj, 'Seleccione Proveedor');
                        }}
                ], null, null, null, null, {title: 'Proveedor'});
                // DIALOG BUSCAR BENEFICIARIO    
                //            $.createSearchDialog('beneDialog',[
                //                    { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                //                    { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                //                    { label: 'Beneficiario', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                //                    { label: 'Apellidos', name: 'Prs_Ape',hidden:true},
                //                    { label: 'Nombres', name: 'Prs_Nom',hidden:true},                                        
                //                    { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false, formatter:function (cv, opts, rObj) { return $.getGridButton(selectBene,{Prv_Cod:rObj.Prv_Cod,Prs_Nom:rObj.Prs_Nom,Prs_Ape:rObj.Prs_Ape},'Seleccione Beneficiario'); } }
                //                ],null,null,null,null,{title:'Beneficiario'}); 
                //            $('#beneForm .form-group-search-btn').append('<a onclick="$(\'#addBenefForm\').setData({}); $(\'#addBenef\').dialog(\'open\');" title="Registrar Beneficiario" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-plus"></i></a>');
                // DIALOG BUSCAR CLIENTE    
                $.createSearchDialog('clieDialog', [
                    {label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 20, align: "center", hidden: true, viewable: true},
                    {label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50},
                    {label: 'Cliente', name: 'clientes', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) {
                            return 'style="white-space: normal;"';
                        }},
                    {label: 'Direcci&oacute;n', name: 'Prs_Dir', hidden: true, viewable: true},
                    {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false, formatter: function (cv, opts, rObj) {
                            return $.getGridButton(selectClie, {Cli_Cod: rObj.Cli_Cod, clientes: rObj.clientes}, 'Seleccione Cliente');
                        }}
                ], null, null, null, null, {title: 'Cliente'});
                $("input[name='Com_Fec']").createDatePickers({checkAvailability: true});
                $.createDatePickers("input[name='Che_Fec']");
            });
            function SelectCta(cta) {
                if (!gridComp.existsId(cta['Pld_Cod']))
                    addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', cta['Pld_Cod']), cta['tipo'] );
            }
            function selectProv(prov) {
                if (tipo === "Egresos") {
                    $('#formComp').setData(prov, null, 'name');
                } else if (tipo === "Diario")
                    $('#formDiario').setData(prov, null, 'name');
                $("#provDialog").dialog("close");
            }
            //        function selectBene(bene){ $('#formComp').setData(bene,null,'namebene'); $( "#beneDialog" ).dialog("close"); }
            function selectClie(clie) {
                $('#formIngreso').setData(clie, null, 'name');
                $("#clieDialog").dialog("close");
            }
            function buscaCliente() {
                $.SearchOrDialogArray("#clieDialog", selectClie, {'search': $('#lblClie').val(), 'op_opciones': 'c'});
                selectClie({});
            }
            function buscaProveeIngreso() {
                $.SearchOrDialogArray("#provDialog", selectProv, {'search': $('#lblProvee').val(), 'op_opciones': 'c'});
                selectProv({});
            }
            function buscaProveeDiario() {
                $.SearchOrDialogArray("#provDialog", selectProv, {'search': $('#lblProvee2').val(), 'op_opciones': 'c'});
                selectProv({});
            }
            function resetForm2(option) {
                gridComp.clearGrid().updateGridDiario();
            }
        </script>
        
        
        
        <!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
        <div id="cuenDialog" title="B&uacute;squeda de Cuentas"></div>
        <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
        <div id="provDialog"  title="B&uacute;squeda de Proveedores"></div>
        <!--INICIO DEL DIALOGO BUSCAR BEnfICIARIO--> 
        <!--<div id="beneDialog"  title="B&uacute;squeda de Beneficiarios"></div>-->
        <!--INICIO DEL DIALOGO BUSCAR CLIENTE--> 
        <div id="clieDialog"  title="B&uacute;squeda de Clientes"></div>
        <!-- CREA BEdefICIARIO DIALOG -->
        <!--    <div id="addBenef"  title="Crear Beneficiario">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Datos Beneficiario</legend>
                    <form id="addBenefForm" class="form-horizontal normal" action="javascript:saveBene();"> 
                        <div class="input-group input-group-sm" >
                            <input id="beneApe" name="apellido" class="form-control" type="text" size="32" placeholder="Apellidos" style="text-transform:uppercase" required autofocus/><span class="input-group-addon input-group-addon-sep"></span>
                            <input id="beneNom" name="nombre" class="form-control" type="text" size="32" placeholder="Nombres" style="text-transform:uppercase" />                    
                        </div>            
                        <div class="row" style="text-align: center; padding-top: 10px; padding-bottom: 5px;">
                            <button type="submit" class="btn btn-success" title="Guardar Proveedor" ><i class="glyphicon glyphicon-floppy-disk"></i><span> Guardar</span></button><span>&nbsp;</span>
                            <button type="button" onclick="$('#addBenef').dialog('close');" class="btn btn-inverse" title="Cancelar" ><i class="glyphicon glyphicon-remove"></i><span> Cancelar</span></button>            
                        </div>
                    </form> 
                </fieldset>
            </div> -->
        <!--INICIO DEL DIALOGO IMPRIMIR --> 
        <div id="successDialog"  title="Mensaje del Sistema">  
            <center><h4>El Comprobante se ha registrado con Exito!</h4></center>  
            <center id="printCheque"></center>
            <center> 
                <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" ><i class="glyphicon glyphicon-remove"></i><span> Cerrar</span></button>            
                <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="glyphicon glyphicon-print"></i> <span> Imprimir</span></span> </a>               
            </center>        
        </div>    
        <?php $ruta = './' . (file_exists('cheques/' . $Ses_Emp_Cod) ? "cheques/$Ses_Emp_Cod/" : ''); ?>
        <div id="modelo" style="display:none;">
            <table style="margin-bottom:10px;" cellpadding="1" border="1">
                <tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
                <tr><td align="center" class="ui-widget-content" colspan="6"><b>&nbsp; {banco} &nbsp;</b></td></tr>
                <tr>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
                    <td align="center"><a href="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
                </tr>
            </table>
        </div>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
        <script>$.clearValidate();</script>
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script> 
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script> 
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
        <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />    
    </BODY>
</HTML>