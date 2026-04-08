<?php
/**
 * @abstract Permite modificar el estado contable de los cheques recibidos
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creacion  2017-09-21
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas
 */
$obBD_con1 = new Class_Log_Datos_Che;
//$obBD_con1->debug(true);


if (isset($cargarBancos)) {
    try {
        $resp['bancos'] = $obBD_con1->getArrayConsulta(17, '', $obBD_conexion);
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

if (isset($cuentasMovimiento)) {
    try {
        $data = $_GET;
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        $Pla_Cod = $obBD_con1->getRowConsulta(21, $data, $obBD_conexion);
        $contado1 = $obBD_con1->getArrayConsulta(19, $Pla_Cod['Pla_Cod'] . '*' . $Ses_Emp_Cod, $obBD_conexion);
        $contado2 = $obBD_con1->getArrayConsulta(20, $Pla_Cod['Pla_Cod'], $obBD_conexion);
        $resp['cuentas'] = $contado = array_merge($contado2, $contado1);
        //$obBD_con1->echoLog($resp['cuentas']);
        $resp['success'] = true;
    } catch (Exception $ex) {
        $resp = array('messsage' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($resp);
}

if (isset($getTipoCompr)) {
    try {
        $data = $_GET;
        $resp['tipos_compr'] = $obBD_con1->getArrayConsulta(22, $data, $obBD_conexion);
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

if (isset($cargarBancoProtestar)) {
    try {
        $data = $_GET;
        $resp['banco'] = $obBD_con1->getRowConsulta(30, $data, $obBD_conexion);
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

if (isset($movimientosAjax)) {
    try {
        //$obBD_con1->debug(true);
        $resp = $obBD_con1->getPageGrid(33, $_GET, $obBD_conexion);
        $reporte = $obBD_con1->getRowConsulta(34, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        foreach ($resp['rows'] as &$mov) {
            $mov['Com_Link'] = "" . (!empty($reporte) ? "$reporte[Rut_Des]$reporte[Pcs_Nom]?codigo=" : "") . $mov['Com_Cod'];
        }
        unset($mov);
    } catch (Exception $exc) {
        $obBD_con1->echoLog($exc->getTraceAsString());
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


if (isset($searchDocument)) {
    //$obBD_con1->debug(true);
    try {
        $data = $_GET;
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        $resp = $obBD_con1->getPageGrid(18, $data, $obBD_conexion);
    } catch (Exception $exc) {
        $obBD_con1->echoLog($exc->getTraceAsString());
    }
    $obBD_con1->echoJson($resp);
}

if (isset($aplazarCheques)) {
    try {
        $obBD_conexionIns = new Class_Log_Conexion_Che($Ses_Dat_Dis);
        $obBD_conIns = new Class_Log_Datos_Che;
        $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
        //$obBD_conIns->debug(true);
        $data = $_POST;
        foreach ($data['cheques'] as $indice => $cheque) {
            $obBD_conIns->operacionobBD(28, $cheque, $obBD_conexionIns);
        }
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $ex->getMessage();
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);

    if ($obBD_conIns->Error === 0) {
        $resp = array('success' => true);
    } else {
        $resp = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }

    $obBD_con1->echoJson($resp);
}





if (isset($saveChequesExt)) {
    $obBD_conexionIns = new Class_Log_Conexion_Che($Ses_Dat_Dis);
    $obBD_conIns = new Class_Log_Datos_Che;
    /* Habilita Debuger de SQLs en Proceso de Guardado de Movimiento de Cheques */
    //$obBD_conIns->debug(true);
     $obBD_con1->debug(true);
    /* Inicio de Transaccion */
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    //$obBD_conIns->debug(true);
    //$obBD_con1->debug(true);
    try {
        $data = $_POST;
        $protestado = false;
        if (isset($data['cheque_selec'])) {
            $protestado = true;
        }
        $data['Emp_Cod'] = $Ses_Emp_Cod;
        $array_date = explode('-', $data['Mov_Fec']);
        $data['fecha'] = $array_date[0];
        $data['Usu_Cod'] = $Ses_Usu_Cod;
        
		/***********************************************************/
		if ($data['Tia_Ini'] === 'D' OR $data['Tia_Ini'] === 'E'){
			$campo_cli_prv ='Prv_Cod';
		}else{
			$campo_cli_prv ='Cli_Cod';
		}
		//$campo_cli_prv = $data['Tia_Ini'] === 'D'? 'Prv_Cod' : 'Cli_Cod';  /*esta linea esta anteriormente*/
        /************************************************************/
		
		$data['prv_or_cli'] = $campo_cli_prv;
        $data['Che_Cod'] = $Che_Cod;
        $data['Com_Con'] = $data['Mov_Tip_Data']['Mov_Tip_Txt']." Num.:".$data['cheque_selec']['Che_Num'];
        $data['prv_cli'] = $obBD_con1->getProveeCliente($Ses_Emp_Cod, $Cli_Cod, $Che_Cod, $obBD_conexion);
        //ChromePhp::log('Cliente: '.$data['Cli_Cod']);
        //ChromePhp::log('Cheque: '.$data['Che_Cod']);
        $Pla_Cod = $obBD_con1->getRowConsulta(35, array('fecha' => $array_date[0], 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        $data['Pec_Cod'] = $Pla_Cod['Pec_Cod'];
        $data['Pla_Cod'] = $Pla_Cod['Pla_Cod'];
        //nuevo comprobante
        $data['Com_Num'] = $obBD_con1->getComNumPecAuto($data['Tia_Cod'], $Pla_Cod['Pec_Cod'], $data['Mov_Fec'], $obBD_conexion);
        if ($protestado){
            $data['Com_Val'] = ($data['Mov_Multa']*1)+($data['cheque_selec']['Che_Val'] * 1);
            $obBD_conIns->echoLog('multa ===>'.($data['Mov_Multa']*1).' cheque'.$data['cheque_selec']['Che_Val'] * 1);
        }
        $obBD_conIns->operacionobBD(23, $data, $obBD_conexionIns);
		
        $data['Com_Cod'] = $obBD_conIns->insercionid($obBD_conexionIns);
        $data['Com_Cod'] = $obBD_conIns->insercionid($obBD_conexionIns);
        $Com_Cod = $data['Com_Cod'];
        //crear asiento Debe
        if ($protestado) {
            $Cli_Var = $obBD_con1->getRowConsulta(29, $data['Pla_Cod'], $obBD_conexion);
            if (isset($Cli_Var['Pld_Cod'])) {
                $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $data['Com_Val']-($data['Mov_Multa']*1), 'Asi_Con' => $data['Tia_Ini_Data']['Tia_Ini_Txt'] . ' ' . $Cli_Var['Pld_Des'], 'Asi_Glo' => $data['Mov_Tip_Data']['Mov_Tip_Txt'], 'Pld_Cod' => $Cli_Var['Pld_Cod']), $obBD_conexionIns);
                //$obBD_con1->echoLog('id de Asiento insertado ' . $obBD_conIns->insercionid($obBD_conexionIns));
                $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $data['Mov_Multa'], 'Asi_Con' => $data['Tia_Ini_Data']['Tia_Ini_Txt'] . ' ' . $Cli_Var['Pld_Des'], 'Asi_Glo' => $data['Mov_Tip_Data']['Mov_Tip_Txt'] . '- Multa', 'Pld_Cod' => $Cli_Var['Pld_Cod']), $obBD_conexionIns);
                //$obBD_con1->echoLog('id de Asiento insertado ' . $obBD_conIns->insercionid($obBD_conexionIns));
            } else {
                throw new Exception('Revisar la parametrizacion contable de Clientes Varios!');
            }
        } else {
            $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $data['Com_Val'], 'Asi_Con' => $data['Tia_Ini_Data']['Tia_Ini_Txt'] . ' ' . $data['Pld_Cod_Data']['Pld_Cod_Txt'], 'Asi_Glo' => $data['Mov_Tip_Data']['Mov_Tip_Txt'], 'Pld_Cod' => $data['Pld_Cod']), $obBD_conexionIns);
            //$obBD_con1->echoLog('id de Asiento insertado ' . $obBD_conIns->insercionid($obBD_conexionIns));
        }
        //crear asiento Haber

        if ($protestado) {
            $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $data['Com_Val']-($data['Mov_Multa']*1), 'Asi_Con' => $data['Tia_Ini_Data']['Tia_Ini_Txt'] . ' ' . $data['Pld_Cod_Data']['Pld_Cod_Txt'], 'Asi_Glo' => $data['Mov_Tip_Data']['Mov_Tip_Txt'], 'Pld_Cod' => $data['Pld_Cod']), $obBD_conexionIns);
            $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $data['Mov_Multa'], 'Asi_Con' => $data['Tia_Ini_Data']['Tia_Ini_Txt'] . ' ' . $data['Pld_Cod_Data']['Pld_Cod_Txt'], 'Asi_Glo' => $data['Mov_Tip_Data']['Mov_Tip_Txt'] . '- Multa', 'Pld_Cod' => $data['Pld_Cod']), $obBD_conexionIns);
            //crear movimiento de cada cheque
            $obBD_conIns->operacionobBD(26, array('Che_Cod' => $data['cheque_selec']['Che_Cod'], 'Com_Cod' => $data['Com_Cod'], 'Mov_Fec' => $data['Mov_Fec'], 'Mov_Usu' => $Ses_Usu_Cod, 'Mov_Obs' => $data['Mov_Obs'] . "-" . $data['cheque_selec']['Che_Num'], 'Mov_Tip' => $data['Mov_Tip'], 'Mov_Doc' => ''), $obBD_conexionIns);
            //cambiar estados a cheque_ext
            $obBD_conIns->operacionobBD(27, array('Che_Cod' => $data['cheque_selec']['Che_Cod'], 'Che_Est' => $data['Mov_Tip']), $obBD_conexionIns);

            //detalles de cuentas por cobrar
            //detalles asociados al cheque
            $resp['detalles'] = $obBD_con1->getArrayConsulta(31, array('Che_Cod' => $data['cheque_selec']['Che_Cod']), $obBD_conexion);
            $items = sizeof($resp['detalles']);
            foreach ($resp['detalles'] as $indice => $detalle) {
                $detalle['Com_Cod']=$Com_Cod;
                if ($detalle['Cpc_Val'] * 1 > $data['cheque_selec']['Che_Val'] * 1) {
                    $detalle['Cpc_Val'] = $data['cheque_selec']['Che_Val'] * 1;
                }
                $detalle['Cpc_Val'] = ($detalle['Cpc_Val'] + ($data['Mov_Multa'] * 1 / $items)) * -1;
                $detalle['Cpc_fec'] = $data['Mov_Fec'];
                $detalle['Cpc_Obs'] = "Protestado Cheque N. " . $data['cheque_selec']['Che_Num'] ." + multa/$items";
                $obBD_conIns->operacionobBD(32, $detalle, $obBD_conexionIns);
            }
        } else {
            $param = $obBD_con1->getRowConsulta(25, array('Tpa_Abr' => 'CCH', 'Pla_Cod' => $data['Pla_Cod']), $obBD_conexion);
            if (!isset($param['Pld_Cod']) || empty($param['Pld_Cod']))
                throw new Exception('Revisar la parametrizacion contable de Cheques recibidos: <u>CCH</u>!');
            $obBD_conIns->operacionobBD(24, array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $data['Com_Val'], 'Asi_Con' => $param['Tpa_Des'], 'Asi_Glo' => $data['Com_Doc'], 'Pld_Cod' => $param['Pld_Cod']), $obBD_conexionIns);
            foreach ($data['cheques'] as $indice => $cheque) {
                //crear movimiento de cada cheque
                $obBD_conIns->operacionobBD(26, array('Che_Cod' => $cheque['Che_Cod'], 'Com_Cod' => $data['Com_Cod'], 'Mov_Fec' => $data['Mov_Fec'], 'Mov_Usu' => $Ses_Usu_Cod, 'Mov_Obs' => $data['Mov_Obs'] . "-" . $cheque['Che_Num'], 'Mov_Tip' => $data['Mov_Tip'], 'Mov_Doc' => $data['Com_Doc']), $obBD_conexionIns);
                //cambiar estados a cheque_ext
                $obBD_conIns->operacionobBD(27, array('Che_Cod' => $cheque['Che_Cod'], 'Che_Est' => $data['Mov_Tip'], 'Che_Cob' => $data['Mov_Fec']), $obBD_conexionIns);
            }
        }
        //throw new Exception("todo bien");
    } catch (Exception $ex) {
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        $resp['message'] = $ex->getMessage();
        $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
        $obBD_con1->echoJson($resp);
    }

    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns);
    
    if ($obBD_conIns->Error === 0) {
        $reporte = $obBD_con1->getRowConsulta(34, array('Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
        //$obBD_con1->echoLog($reporte);
        $resp['Com_Link'] = "" . (!empty($reporte) ? "$reporte[Rut_Des]$reporte[Pcs_Nom]?codigo=" : "") . "$Com_Cod";
        $resp['success'] = true;
    } else {
        $resp = array('success' => false, 'message' => "No se ha logrado realizar la Transaccion", 'error' => $obBD_conIns->MsgError);
    }

    $obBD_con1->echoJson($resp);
}
?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
        <TITLE><?Php echo "Ccxcc Control Cheque [EXA]"; ?></TITLE>
	    <meta charset="UTF-8">
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <style></style>
        <script type="text/ecmascript" src="../VALIDACIONES/tes_val_cheque_ext.js?a=27">
        </script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Modificar Cheques Recibidos</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="documentoSearch">
                    <div class="row">
                        <form name="searchCheque" id="searchCheque" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchCheque','searchDocument');">
                            <div class="col-xs-5">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Búsqueda</legend>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                                        <div class="col-xs-10 radioset opt_search">
                                            <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Cliente&nbsp;&nbsp;&nbsp;</label>
                                            <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                            <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                                        <div class="col-xs-7" >
                                            <div class="input-group">                        
                                                <input name="search" onkeydown="if (event.keyCode === 13)
                                                            this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                                <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                            </div><!-- /input-group --> 
                                        </div><input type="text" tabindex="-1" style="display:none;" />                    
                                    </div>

                                </fieldset>
                            </div>
                            <div class="col-sm-7 ">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtros</legend>
                                    <div class="form-group">
                                        <label class="col-sm-1 control-label label-xs">Banco:</label>
                                        <div class="col-sm-3">
                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs" ></select>
                                        </div>
                                        <label class="col-sm-1 control-label label-xs">Periodo:</label>
                                        <div class="col-sm-3">
                                            <select class="form-control input-xs" id="periodos" name="periodos"  required="">
                                                <option value=0><< TODOS >></option>
                                            </select>
                                        </div>
                                        <label class="col-sm-1 control-label label-xs">Por:</label>
                                        <div class="col-sm-3">
                                            <select class="form-control input-xs" id="TipFecha" name="TipFecha"  required="">
                                                <option value='CHE'> Fecha de Cheque</option>
                                                <option value='COM'> Fecha de Cobro</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1 control-label label-xs">Tipo:</label>
                                        <div class="col-sm-3">
                                            <select multiple class="chzn-select form-control input-xs" id="TipBus" data-placeholder="Filtros..."  name="TipBus" required="">
                                                <option value=0><< TODOS >></option>
                                                <option value=1>Aplazados</option>
                                                <option value=2>Cobrados</option>
                                                <option value=5>Depositados</option>
                                                <option value=3>No Cobrados</option>
                                                <option value=4>Protestados</option>
                                            </select>
                                        </div>
                                        <div id="rango_fechas">
                                            <label class="col-sm-1 control-label label-xs">Desde:</label>
                                            <div class="col-sm-2">
                                                <input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker" style="text-align: center;"/>
                                            </div>
                                            <label class="col-sm-1 control-label label-xs">Hasta:</label>
                                            <div class="col-sm-2">
                                                <input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker" style="text-align: center;"/>
                                            </div>
                                        </div>
                                    </div>                                                                       
                                </fieldset>
                            </div>   
                        </form>
                        <div class="col-xs-12" style="min-height: 360px;">
                            <table id="searchGrid" name="searchGrid"></table>
                            <table id="searchGridPager"></table>
                            <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-lock orange"></span>Imposible protestar| <span class="fa fa-exclamation-circle  white" style="background-color: #cc0000!important;height: 12px;width: 14px;text-align: center;"></span>Protestar Cheque</span></div>
                        </div>
                    </div>
                    <div class="center">
                        <button type="submit" onclick="javascript:guardarCambios()" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>
                </div>

                <!-- Inicio del di�logo para gestionar cheques seleccionados -->
                <div id="gestionarDialog" title="Gestionar Movimiento" class="dialog-exa" style="display:none;">
                    <form class="form-horizontal normal" id="movimientoForm" action="javascript:validarDocument()" >
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Movimiento</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Movimiento:</label>
                                <div class="col-xs-5" >
                                    <select name="Mov_Tip" id="Mov_Tip" class="getData text-center form-control input-xs center " required="">
                                        <option  class="text-center"  value="C" banco="no">Cobrado</option>
                                        <option class="text-center" value="D" banco="si">Depositado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Fecha:</label>
                                <div class="col-xs-5" >
                                    <input name="Mov_Fec" type="text" id="Mov_Fec" size="10" class="form-control input-xs datepicker" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Ingresa a:</label>
                                <div class="col-xs-5" >
                                    <select name="Pld_Cod" id="Pld_Cod" class="getData form-control input-xs " required=""></select>
                                </div>
                            </div>

                            <div class="form-group deposito">
                                <label class="col-xs-4 control-label label-xs required">Com. Deposito:</label>
                                <div class="col-xs-5" >
                                    <input name="Com_Doc" type="text" id="Com_Doc" size="10" class="form-control input-xs" required="" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Observación:</label>
                                <div class="col-xs-5" >
                                    <div class="input-group input-group-xs">
                                        <textarea class="form-control" id="Mov_Obs" val="" name="Mov_Obs" rows="5"></textarea>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Asiento</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo de Asiento:</label>
                                <div class="col-xs-5" >
                                    <select name="Tia_Ini" id="Tia_Ini" class="getData text-center form-control input-xs center" required="">
                                        <option  class="text-center"  value="D" >Diario</option>										
                                        <option class="text-center" value="I" selected="true">Ingreso</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo de Comprobante:</label>
                                <div class="col-xs-5" >
                                    <select name="Tia_Cod" id="Tia_Cod" class="getData text-center form-control input-xs center" required=""></select>
                                </div>
                            </div>
                        </fieldset>
                        <div class="center">
                            <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                        <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                    </form>
                </div>



                <!-- Inicio del di�logo para protestar cheques -->
                <div id="protestarDialog" title="Protestar Cheque" class="dialog-exa" style="display:none;">
                    <form class="form-horizontal normal" id="movimientoForm" action="javascript:validarProtest()">
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Movimiento</legend>
                            <input class="hidden" id="Che_Cod">
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Movimiento:</label>
                                <div class="col-xs-5" >
                                    <select name="Mov_Tip" id="Mov_Tip" class="getData text-center form-control input-xs center protestar" required="">
                                        <option  class="text-center"  value="P" banco="si">Protestado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Fecha:</label>
                                <div class="col-xs-5" >
                                    <input name="Mov_Fec" type="text" size="10" class="form-control input-xs datepicker protestar" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Devuelto por:</label>
                                <div class="col-xs-5" >
                                    <select name="Pld_Cod" id="Pld_Cod" class="getData form-control input-xs protestar " required=""></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Multa:</label>
                                <div class="col-xs-5" >
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input name="Mov_Multa" type="text" id="Mov_Multa" size="10" onKeyPress = "return validar_decimal(event)" class="form-control input-xs protestar " required="" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Observación:</label>
                                <div class="col-xs-5" >
                                    <div class="input-group input-group-xs">
                                        <textarea class="form-control protestar" id="Mov_Obs" val="" name="Mov_Obs" rows="5">
                                        </textarea>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos del Asiento</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo de Asiento:</label>
                                <div class="col-xs-5" >
                                    <select name="Tia_Ini" id="Tia_Ini" class="getData text-center form-control input-xs center protestar" required="">
                                        <option  class="text-center"  value="D" >Diario</option>										
										<option  class="text-center"  value="E" >Egreso</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs required">Tipo de Comprobante:</label>
                                <div class="col-xs-5" >
                                    <select name="Tia_Cod" id="Tia_Cod" class="getData text-center form-control input-xs center protestar" required=""></select>
                                </div>
                            </div>
                        </fieldset>
                        <div class="center">
                            <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                        </div>
                        <div class="Titulos2"><hr><b>NOTA:</b> Los campos marcados con un asterisco (  <span class="required"></span>) son campos obligatorios.</div>
                    </form>
                </div>




                <!-- Inicio del di�logo detalles de movimiento de cheques -->
                <div id="movimientosDialog" title="Movimientos Cheque" class="dialog-exa" style="display:none;">
                    <form class="form-horizontal normal" id="movimientosForm" action="javascript:validarProtest()">
                        <fieldset class="exa-fieldset" >
                            <legend class="Titulos2">Datos de Cheque</legend>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Cod Int:</label>
                                <div class="col-xs-5" >
                                    <input name="Che_Cod" type="text" id="Che_Cod" size="10" class="form-control input-xs"  />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Titular:</label>
                                <div class="col-xs-5" >
                                    <input name="Cli_Ven" type="text" id="Cli_Ven" size="10" class="form-control input-xs"  />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Che. N:</label>
                                <div class="col-xs-5" >
                                    <input name="Che_Num" type="text" id="Che_Num" size="10" class="form-control input-xs"  />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Fecha:</label>
                                <div class="col-xs-5" >
                                    <input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-4 control-label label-xs ">Valor:</label>
                                <div class="col-xs-5" >
                                    <div class="input-group">
                                        <span class="input-group-addon input-xs"><span class="glyphicon glyphicon-usd"></span></span>
                                        <input name="Che_Val" type="text" id="Che_Val" size="10" class="form-control input-xs"  />
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>

                <div id="successDialog"  title="Mensaje del Sistema"  class="dialog-exa" style="display:none;">  
                    <center><h2>El Comprobante se ha registrado con Exito!</h2></center>  
                    <center> 
                        <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                            <i class="icon-ban-circle icon-white"></i>
                            <span>Cerrar</span>
                        </button>            
                        <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
                    </center>        
                </div>

                <div id="formatoReporte" style="display: none;">
                    <div style="width: 1030px;">
                        <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span id="titleReporte"></span>', $obBD_conexion); ?>
                        <table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>            
                        <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
                    </div>
                </div>  
                <div id="formatoExportar" style="width: 700px;display: none;">
                    <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE REGISTROS', '<span class="title_grid"></span>', $obBD_conexion, false, 5); ?>
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
