<?php

/**
 * @abstract Permite realizar el ingreso de retenciones bancarias
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creación: 18-09-2018
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_ret_cre_vta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Ret;
$hoy = date("Y-m-d");
if (isset($clientAjax)) {
    $data = array_merge($_GET, array('setWhere' => array('byPerCod')));
    $respuesta = $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($respuesta);
}

/* Consulta del codigo retencion */
if(isset($codiAjax)){
    //$obBD_con1->echoLog($Caj_Fec);
    $resultado=array(
        'success' => true,
		  'periodo'=> $obBD_con1->getRowConsulta('perio_cont.selectWhere',array('perio_cont.Pec_Est'=>'A', 'setWhere'=>array('setEmpCod')), $obBD_conexion),
		  'rows'=>$obBD_con1->getArrayConsulta('renta_iva.selectWhere', array( 'setWhere'=>array('isActive','byOrder')), $obBD_conexion),
	 );
	 //$obBD_con1->echoLog('pla_cod');
	 $Pla_Cod=$resultado['periodo']['Pla_Cod'];
	 //$obBD_con1->echoLog($resultado['periodo']['Pla_Cod']);
			foreach ($resultado['rows'] as &$r) {
			//$obBD_con1->echoLog($r['Ren_Cod']);
			$Ren_Cod=$r['Ren_Cod'];
			$cuenta = $obBD_con1->getRowConsulta('reniva_pla.selectWhere',array('reniva_pla.Ren_Cod'=>$Ren_Cod, 'detP.Pla_Cod'=>$Pla_Cod,'setWhere'=>array('byDetPlan','isVenta')),$obBD_conexion);
			//$obBD_con1->echoLog($cuenta);
			if(!empty($cuenta['Pld_Cod'])) $r=array_merge($r,$cuenta);
			//$obBD_con1->echoLog($r);
		}unset($r);


	$obBD_con1->echoJson($resultado);

}

/********
 * Guardar
 */
if(isset($saveDocumento)){
	$resp=array('success'=>false);
	$obBD_ins1 = new Class_Log_Datos_Ret;
	$obBD_conexionIns = new  Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try{
		//$obBD_con1->echoLog('**-- PHP GUARDAR --**');
		$data = $_POST;
		//$obBD_con1->echoLog($data);
		$numRetenciones = $obBD_con1->getRowConsulta('retcre_vta.sql.getNext', array('where'=>array('')),$obBD_conexion);
		$valorRetTot = $numRetenciones['total'] + 1 ;
		//$obBD_con1->echoLog($valorRetTot);
		//$obBD_con1->echoLog($Ses_Prs_Cod);
		//$obBD_con1->echoLog($Ses_Usu_Cod);
		//$obBD_con1->echoLog($Ses_Sys_Nom);
		$obBD_ins1->operacionobBD('retcre_vta.insert',array('Cli_Cod'=>$data['Cli_Cod'], 'Tic_Cod'=>$data['Tic_Cod'], 'Rvt_Num'=>$data['Rvt_Num'],
																			 'Rvt_Aut'=>$data['Rvt_Aut'], 'Rvt_Fec'=>$data['Caj_Fec'], 'Rvt_Doc'=>$data['Rvt_Doc'],
																			 'Usu_Cod'=>$Ses_Usu_Cod, 'Rvt_Tem'=>$data['Rvt_Tem'], 'Pec_Cod'=>$data['Pec_Cod'], 'Rvt_Obs'=>$data['Rvt_Obs'] ), $obBD_conexionIns);
		$Rvt_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
		//$obBD_con1->echoLog($Rvt_Cod);
		$cont = 0;
		foreach ($retenciones as $retencion) {
			if($retencion['Ren_Cod'] != 0 ) {
				$obBD_ins1->operacionobBD('retcrevta_det.insert',array('Rvt_Cod'=>$Rvt_Cod, 'Rvt_Int'=>$cont + 1, 'Ren_Cod'=>$retencion['Ren_Cod'], 'Rvt_Bas'=>$retencion['Rvt_Bas'] ),$obBD_conexionIns);
				$cont++;
			}
		}

	}catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
	$resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);

}




$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere',array('setWhere'=>array('setEmpCod')),$obBD_conexion);
//$obBD_con1->echoLog($configs);
$periodos=$obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est'=>'A','setWhere'=>array('setEmpCod'),'order'=>'perio_cont.Pec_Fei DESC'), $obBD_conexion);
$comprobantes=$obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('tipo_compr.Tic_Est'=>'A', 'setWhere'=>'isFactura'), $obBD_conexion);
//$obBD_con1->echoLog($comprobantes);
// INICIO DE LA VISTA
?>
<!DOCTYPE html>
<HTML>
<HEAD>
	<TITLE>
    <meta charset="UTF-8">
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
		  <script> var Cof_Con='<?php echo $configs['Cof_Con']; ?>'; </script>
        <style>
		  		.footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
        </style>
   </HEAD>
   <BODY>
       <div class="panel panel-main" id="formFinal">
           <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Retenciones Bancarias</h3></div>
           <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
               <form id="frm_ret_ban" name="frm_ret_ban" class="form-horizontal normal" action="javascript:confirmaGuardado();">
                   <div class="col-xs-6">
                       <fieldset class="exa-fieldset" id="retFormTemp">
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs">Periodo:</label>

                               <div class="col-xs-5" >
                                   <select id="Pec_Cod" name="Pec_Cod" onchange="$('#formFinal').setData($('#Pec_Cod').find('option:selected').data(),'name');" class="form-control input-xs">
                                       <?php
                                       foreach ($periodos as $p) {
                                           echo "<option data--year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data--pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
                                       }
                                       ?>
                                   </select>
                               </div>
                               <label class="col-xs-2 control-label label-xs">Fecha emisión:</label>
                               <div class="col-xs-3">
                                   <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers">
                               </div>
                           </div>
                       </fieldset>
                       <fieldset class="exa-fieldset" id="retFormTemp2">
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required" >Cédula/RUC:</label>
                               <div class="col-xs-6">
                                   <input name="Prs_Cod" type="text" style="display:none;" />
                                   <input name="Prs_Cor" type="text" style="display:none;" />
                                   <input name="Cli_Cod" type="text" style="display:none;" />
                                   <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">
                                   <div class="input-group input-group-xs">
                                       <input id="Prs_Ced" name="Prs_Ced" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#clientDialog',selectCliente2); }"
                                              type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                       <span class="input-group-btn">
                                           <button id="Cli_Btn" type="button" onclick="$('#clientDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente"
                                                   tabindex="2">
                                               <span class="glyphicon glyphicon-search"></span>
                                           </button>
                                       </span>
                                   </div>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required">Cliente:</label>
                               <div class="col-xs-6">
                                   <span id="Cliente" name="Cliente" data-name="Cliente" class="form-control input-xs databind datatitle"></span>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs">Dirección:</label>
                               <div class="col-xs-10">
                                   <div class="input-group input-group-xs">
                                       <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                       <span class="input-group-addon bold">e-mail:</span>
                                       <input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                                   </div>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs">Observaciones:</label>
                               <div class="col-xs-10">
                                   <textarea name="Rvt_Obs" type="text" class="form-control span datatitle"></textarea>
                               </div>
                           </div>
                       </fieldset>
                       <fieldset class="exa-fieldset" id="retFormTemp3">
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs">Documento:</label>
                               <div class="col-xs-6">
                                   <select id="Tic_Cod" name="Tic_Cod" onchange="$('#formFinal').setData($('#Tic_Cod').find('option:selected').data(),'name');"
                                           class="form-control input-xs" required="">
                                               <?php foreach ($comprobantes as $c) { ?>
                                           <!-- echo "<option data--descripcion='$c[Tic_Des]' data--compra_-cod='$c[Tic_Cod]' value='$c[Tic_Cod]'>* $c[Tic_Des]</option>";-->
                                           <option value="<?php echo $c['Tic_Cod']; ?>">
                                               <?php echo $c['Tic_Des']; ?>
                                           </option>
                                       <?php } ?>
                                   </select>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required">Num. Factura:</label>
                               <div class="col-xs-6">
                                   <input id="Rvt_Doc" name="Rvt_Doc" data-name="Rvt_Doc" type="text" class="form-control input-xs secuencia" tabindex="-1" required>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required">Num. Reten:</label>
                               <div class="col-xs-6">
                                   <input name="Rvt_Num" data-name="Rvt_Num" type="number" class="form-control input-xs nospin" tabindex="-1" required>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required">Autorización:</label>
                               <div class="col-xs-6">
                                   <input name="Rvt_Aut" data-name="Rvt_Aut" type="number" class="form-control input-xs nospin" tabindex="-1" required>
                               </div>
                           </div>
                           <div class="form-group">
                               <label class="col-xs-2 control-label label-xs required">Tipo Emisión:</label>
                               <div class="col-xs-6">
                                   <div class="form-check">
                                       <input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio1" value="E" >
                                       <label class="form-check-label" for="inlineRadio1">Electronica</label>
                                   </div>
                                   <div class="form-check">
                                       <input class="form-check-input" type="radio" name="Rvt_Tem" id="inlineRadio2" value="F" >
                                       <label class="form-check-label" for="inlineRadio2">Fisica</label>
                                   </div>
                               </div>
                           </div>
                       </fieldset>
                   </div>
                   <div class="col-sm-6">
                       <div class="jqHeaderFirst jqFirst">
                           <table id="detalle"></table>
                           <div id="detallePager"></div>
                       </div>
                   </div>
                   <div class="form-group Titulos2">
                       <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
                   </div>
                   <div style="text-align: center;padding-top: 5px;">
                       <button type="button" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                   </div>
               </form>
           </div>
       </div>
       <div id="clientDialog" title="B&uacute;squeda de Clientes "></div>
       <div id="codiDialog" title="B&uacute;squeda de Retenciones "><form><input type="hidden" id="RetCodAux" name="RetCodAux"v></form></div>

       <script src="../VALIDACIONES/fact_val_ret_cre_vta.js?L=586"></script>
       <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
       <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
       <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
       <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
   </BODY>

</HTML>