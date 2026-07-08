<?php
/**
 * @abstract Permite realizar el registro de requisiciones
 * @author Cear Bermeo
 * @version 1.0
 * Fecha de creaci�n  2018-06-26
 */
require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/requisiciones/index.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Requisiciones($obBD_conexion);
$tusItems ;
$busquedaProf;
//
if (isset($requisitorSearchAjax)) {
	$_GET["Usu_Cod"] = $Ses_Usu_Cod;
	$_GET["Emp_Cod"] = $Ses_Emp_Cod; 
	$obBD_con1->getRequisitores($_GET);
}

if (isset($proAjax)) {
    $productos = $obBD_con1->getPageGridJson('producto.selectWhere', $_GET, $obBD_conexion);
    //$obBD_con1->echoLog($productos);
}

if(isset($initialize)){
	$_GET['Emp_Cod'] = $Ses_Emp_Cod;
		$Req_Num = $obBD_con1->getRowConsulta(
			"requisiciones.3",
			$_GET, 
			$obBD_conexion
		);
	$resp['Req_Num'] = $Req_Num;
	
	$tipos = $obBD_con1->getArrayConsulta('requisiciones_tipo.0', $_GET, $obBD_conexion);
	$resp['tipos'] = $tipos; 
	
	$obBD_con1->echoJson($resp);
}

if(isset($saveDocumento)){
	$_POST['Emp_Cod'] = $Ses_Emp_Cod;
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Requisiciones($obBD_conexion);
    //$obBD_ins2 =  new Class_Log_Datos_Requisiciones_Det;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->debugLogs(false);
    $obBD_con1->debugLogs(false);

    //$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    $prd_indice = 0;
    $es_creacion = 0;
    try{
        $data = $_POST;
		ChromePhp::log('DATA SAVEDOCUMENT',$data);
		// es nueva requisicion
		$tituloImp= 'Requisicion No. '.($data['Req_Num']);
		$obBD_con1->echoLog('opcion creacion');
		// Busco el numero actual de las requisiciones
		$numRequisicion = $obBD_con1->getRowConsulta(
			"requisiciones.3",
			$_POST, 
			$obBD_conexion
		);
		// insertamos caabecera de requisicion
		$obBD_ins1->operacionobBD('requisiciones.insert',
			array(
			'Req_Fec_Cre' => $data['Req_Fec'],
			'Req_Fec_Ent' => $data['Req_Fec_Ent_Bod'],
			'Req_Num' => $numRequisicion['total'] + 1,
			'Emp_Cod' => $Ses_Emp_Cod,
			'Usu_Cod' => $Ses_Usu_Cod, 
			'Per_Cod' => $data['Per_Cod'],
			'Req_Fec_Ent_Bod' => $data['Req_Fec_Ent_Bod'],
			'Req_Tip' => $data['Req_Tip'],
			'Req_Per_Sol' => $data['Req_Per_Sol'],
			'Req_Ent_Com' => $data['Req_Ent_Com'],
			'Req_Ent_Par' => $data['Req_Ent_Par'],
			'Req_Obs' => $data['Req_Obs'],
		),$obBD_conexionIns);
		ChromePhp::log('ERROR', $obBD_ins1->MsgError);
		$Req_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
		$busquedaProf = $data['Req_Num'];
		$conta = 0;
		foreach ($items as $itemsito) {
			if ($itemsito['Pro_Cod'] != 0) {
				$obBD_ins1->operacionobBD(
					'requisiciones_det.insert', 
					array(
						'Req_Cod' => $Req_Cod, 
						'Rqd_Int' => $conta + 1, 
						'Pro_Cod' => $itemsito['Pro_Cod'], 
						'Rqd_Cant' => $itemsito['Vet_Can'], 
						'Rqd_Uni' => $itemsito['Uni_Des'], 
						'Iva_Cod' => $itemsito['Iva_Cod']
					), $obBD_conexionIns);
				$conta++;
			}
		}
    }catch(Exception $e){
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message']=$e->getMessage(); 
		$obBD_con1->echoJson($resp); 
	}
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	$obBD_con1->echoJson($resp);
	
	exit();
}

$vendedores = $obBD_con1->getRowConsulta('vendedor.selectWhere', array_merge($_GET,array('setWhere'=>array('setSucCod','setPrsCod'))), $obBD_conexion);
$numActualProf=$obBD_con1->getRowConsultaSql("SELECT IFNULL(MAX(Req_Num),0) AS total FROM requisiciones INNER JOIN personal ON personal.Per_Cod=requisiciones.Per_Cod INNER JOIN persona ON persona.Prs_Cod=personal.Prs_Cod INNER JOIN sucursal ON personal.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion);

$ivas=$obBD_con1->getArrayConsultaSql("SELECT * FROM iva WHERE Iva_Por>0 ORDER BY Iva_Ini DESC;", $obBD_conexion);

utf8_encode_deep($vendedores);
$tituloImp= 'Requisicion No- '.($numActualProf['total'] + 1);
?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="/framework/jquery/bootstrap/popover/jquery.flyout.css">
	<?Php require_once("../../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="/framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <style>
        .ui-jqgrid td input, .ui-jqgrid td select, .ui-jqgrid td textarea {padding-top: 2px;}
            .footrow td[aria-describedby="documento_Vet_Imp"],.footrow td[aria-describedby="documento_Vet_Pru"]{padding: 0 !important;}
            .footerFact{ text-align:right;width: 100%; }
            .footerFact input[type=text],.footerFact label,.footerFact textarea,.footerFact select{height:19px;width:100% !important;display: block;margin-bottom:0px !important;margin-top:0px !important;text-align:right;}
            .footerFact input[type=text]{ padding: 0; }
            .footerFact textarea{text-align: left; height: 75px !important;}
            .footerFact select{ padding-top: 2px !important; padding-bottom: 2px !important; display: inline; }
            .footerFact label{height:19px;line-height:18px; padding-right: 5px;}
            .footerFact label.total, .footerFact input.total{background-color: #254463; color:white; font-size: 14px; border: none;}
            #jqGridButtonDiv{float:right; padding-right:10px; position:relative; top:-1px;}
            #Ret_Asu{
                vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4);
            }
            #resultContent .resp{
                font-weight: 700; font-size: 30px; color: #; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px;
            }
            #3f3fc1resultContent .resp span:first-child{
                color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px;
            }
            .msg_fly
            {
                font-size: 12px !important;
            }


            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .bold{font-weight: bold;}
            .rep{font-size: 12px;}
            .noBorder{ border: 0; }
            .grid{ width: 31%; float:left; padding: 5px;  border: 1px solid; border-radius: 5px; }
            .grid:not(:last-child){  width: 31.5%; margin-right: 1%; }
            .rep td{ padding: 1px 6px;}
			#Req_Tip { width: 66.666666% !important; }
        </style>
        <script>
        var vendedor=<?php echo json_encode($vendedores); ?>, ivas_venta=<?php echo json_encode($ivas)?>, numReqs=<?php echo json_encode($numActualProf)?>;
        </script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Requisiciones</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="requisicion" class = "row">
                    <div class="col-md-12">
                        <form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validarFormRequisicion();">
                            <div class="col-md-5 col-md-12">
                                <fieldset class="exa-fieldset" id="requisitorFormTemp">
                                    <legend class="Titulos2">Datos del Requisitor</legend>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label label-xs required">C&eacute;dula:</label>
                                        <div class="col-md-7" >
                                            <input name="Prs_Cod" type="text" style="display:none;" />
                                            <input name="Prs_Cor" type="text" style="display:none;" />
                                            <input name="Per_Cod" type="text" style="display:none;" />
                                            <input id="Req_Mod_Cod" name="Req_Mod_Cod" type="text" style="display:none;" />
                                            <input id="Num_Vtas" name="Num_Vtas" style="display:none;" type="number"  readonly/>
                                            <input id="Es_Mod" name="Es_Mod"  style="display:none;" type="number"  readonly/>
                                            <input id="Req_Nva" name="Req_Nva" style="display:none;" type="number"  readonly/>
                                            <input name="op_opciones" type="text" value="c" style="display: none;">
                                            <div class="input-group input-group-xs">
                                                <input id="Prs_Ced" name="Prs_Ced" onkeydown="if (event.keyCode === 13)
                                                            $.Search('requisitorDialog', selectRequisitor);" type="text" placeholder="Ingrese el Requisitor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" required="" />
                                                <span class="input-group-btn">
													<button id="Agr_Cli_Btn" type="button" onclick="$('#requisitorSearch').dialog('open');" class="btn btn-success btn-xs" title="Buscar Requisitor"
													tabindex="2">
														<span class="glyphicon glyphicon-search"></span>
													</button>
												</span>
											</div>
										</div>
										<!-- <div class="col-md-2">
											<button id="Prof_btn" type="button" onclick="$('#reqDialog').dialog('open');" class="btn btn-warning btn-xs" title="Modificar Requisici&oacute;n"
											tabindex="2">
												<span class="glyphicon glyphicon-edit"></span>
											</button>
										</div> -->
									</div>
									<div class="form-group">
										<label class="col-md-2 control-label label-xs">Requisitor:</label>
										<div class="col-md-10">
											<span name="Req_Nom" class="form-control input-xs databind datatitle"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-2 control-label label-xs">Direcci&oacute;n:</label>
										<div class="col-md-4">
											<span id="Prs_Dir" name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span>
										</div>
										<label class="col-md-1 control-label label-xs">Correo:</label>
										<div class="col-md-5">
											<span name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span>
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-md-7 col-md-12" id="numReqGenerado">
								<fieldset class="exa-fieldset" id="docuFormTemp">
									<legend class="Titulos2">Datos del Documento</legend>
									<input type="text" name="Vet_Cod" style="display: none;" />
									<input type="text" name="Com_Cod" style="display: none;" />
									<div class="form-group">
										<label class="col-md-2 control-label label-xs required" style="text-align:center;">Fecha Creaci&oacute;n:</label>
										<div class="col-md-4">
											<input type="text" id="Req_Fec" name="Req_Fec" class="form-control input-xs datepickers" style="text-align:center;" required>
										</div>
										<label class="col-md-2 control-label label-xs required" style="text-align:center;">Fecha Ent Max:</label>
										<div class="col-md-4">
											<input type="text" id="Req_Fec_Ent_Bod" name="Req_Fec_Ent_Bod" class="form-control input-xs datepickers" style="text-align:center;" required>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-2 control-label label-xs required" style="text-align:center;">N&uacute;mero:</label>
										<div class="col-md-2 ">
											<input type="text" id="Req_Num" name="Req_Num" class="form-control input-xs trigger" tabindex="5" style="text-align:center; background-color:powderblue;"
											required readonly/>

										</div>
										<div class="col-md-2">
											<input type="number" id="Req_Num_Ext" name="Req_Num_Ext" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
											placeHolder="Ord.Compra" min="0" />
										</div>
										<div class="col-md-6">
											<label class="col-md-4 label-xs" for="Req_Tip">Tipo:</label>
											<select id="Req_Tip" name="Req_Tip" class="col-md-8  form-control input-xs">
											</select>
										</div>
										<!-- <label class="col-md-1 control-label label-xs required">Solicitado a:</label> 
										<div class="col-md-8" >
											<input type="checkbox" id="Eve_Rel"  name="Eve_Rel" value="S" offval="N" /> 
										</div> -->
									</div>
									<div class="form-group">
										<label class="col-md-2 control-label label-xs required" style="text-align:center;">Usuario:</label>
										<div class="col-md-4">
											<input type="text" id="Vendedor" name="Vendedor" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
											value="<?php  echo mb_convert_encoding($vendedores['Vendedor'], 'ISO-8859-1', 'UTF-8')?>" readonly></input>
											<input type="text" id="Vnd_Cod" name="Vnd_Cod" style="display: none;" value="<?php  echo mb_convert_encoding($vendedores['Vnd_Cod'], 'ISO-8859-1', 'UTF-8')?>"
											/>
										</div>
										<label class="col-md-2 control-label label-xs" style="text-align:center;">Solicitado a:</label>
										<div class="col-md-4">
											<input type="text" id="Req_Per_Sol" name="Req_Per_Sol" class="form-control input-xs" tabindex="2" value="" />
										</div>
									</div>
									<div class="form-group">
										<div class="col-md-3">
											<label>
												Acepta Ent Parciales: <input type="checkbox" id="Req_Ent_Par" name="Req_Ent_Par"  value="1" offval="0" onchange="" /> 
											</label>
										</div>
										<label id="Req_Ent_Com_Lab" class="col-md-1 control-label label-xs required" style="text-align:center;">Como?:</label>
										<div class="col-md-8">
											<input type="text" id="Req_Ent_Com" name="Req_Ent_Com" class="form-control input-xs" tabindex="2" value="" />
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-sm-12 table-responsive" style=" padding-bottom: 5px;">
								<table id="items" class="table table-fixed"></table>
								<div id="itemsPager"></div>
							</div>
							<select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" style="display: none;">
								<?php
									$temp = array();
									foreach ($ivas AS $row) {
										if (!in_array($row['Iva_Por'], $temp)) {
											echo '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" data-ivaini=' . $row['Iva_Ini'] . ' data-ivafin=' . $row['Iva_Fin'] . ' >' . $row['Iva_Por'] . ' %</option>';
										}
										array_push($temp, $row['Iva_Por']);
									}
								?>
							</select>
							<div class="col-md-12 text-center">
								<button id="guardar" name="guardar" type="button" class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();">
									<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
								<!-- <button class="btn btn-sm btn-success" onclick="verVentanaRequisiciones();"><i class="glyphicon glyphicon-arrow-right"></i> Ver Requisiciones</button>-->
								<button class="black btn btn-sm btn-danger" onclick="location.reload(); return false;"><i class="glyphicon glyphicon-remove"></i>Cancelar</button>
							</div>
						</form>

	<div class="col-sm-12 Titulos2">
		<hr>
		<b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (
		<span class="required"></span>) son campos obligatorios.</div>
	</div>
	</div>
	<div id="documentoMain" style="display:none;">
		<div class="col-sm-12">
			<fieldset class="exa-fieldset">
				<legend class="Titulos2">Requisiciones registrados</legend>
				<div>
					<form name="searchProf" id="searchProf" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchProf','profAjax');">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">B&uacute;squeda</legend>
							<div class="form-group">
								<label class="col-md-1 control-label label-xs">Filtrar Por:</label>
								<div class="col-md-5 radioset opt_search">
									<input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc1">Nombre Requisitor</label>
									<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc2"># Requisici&oacute;n</label>

								</div>
							</div>
							<div class="form-group">
								<label class="col-md-1 control-label">B&uacute;squeda:</label>
								<div class="col-md-5">
									<div class="input-group">
										<input name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..."
										 autofocus class="form-control input-xs clearable submit" />
										<span class="input-group-btn">
											<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Requisicion" tabindex="-1">
												<span class="glyphicon glyphicon-search"></span>
												<span>Buscar</span>
											</button>
										</span>
									</div>
									<!-- /input-group -->
								</div>
								<input type="text" tabindex="-1" style="display:none;" />
							</div>
						</fieldset>
					</form>
				</div>
				<div class="" style="min-height: 50px;">
					<table id="container"></table>
					<div id="containerPager"></div>
				</div>
				<div class="center">
					<button type="button" class="btn btn-sm btn-inverse" onclick="$('#documentoMain').moveComp('#requisicion').updateGridsSizes();">
						<i class="glyphicon glyphicon-arrow-left"></i> Atr�s</button>
				</div>
			</fieldset>
		</div>
	</div>

	</div>



	<!-- Datos Reporte style="width: 900px;"  -->
	<div id="datosTabla5" class="grid" style="display:none;">
		<?php 
            if($Ses_Emp_Cod == 300){
                echo '<h3 style="text-align: center; font: 14pt Verdana, Geneva, sans-serif; font-weight: bold;"> REQUISICION NO <span id="titleReporte"></span></h3>';
            }else{
            echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REQUISICION NO <span id="titleReporte"></span>', ' ', $obBD_conexion); 
            }
            ?>

		<table border="0" cellpadding="0" cellspacing="0" id="cabeceraTabla" style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px"
		 class="rep">
			<tr style="height: 0;">
				<td width="10%">&nbsp;</td>
				<td width="20%">&nbsp;</td>
				<td width="6%">&nbsp;</td>
				<td width="19%">&nbsp;</td>
				<td width="15%">&nbsp;</td>
				<td width="30%">&nbsp;</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>RUC:</strong>
				</td>
				<td>&nbsp;
					<span name="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td class='bold'>
					<strong>FECHA:</strong>
				</td>
				<td>&nbsp;
					<span name="Req_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td class='bold'>
					<strong>Ord.Comp:</strong>
				</td>
				<td>&nbsp;
					<span name="Req_Num_Ext" style="font-size: 12px;" align='center' class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>REQUISITOR:</strong>
				</td>
				<td colspan="5">&nbsp;
					<span name="Requisitor" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>DIRECCION:</strong>
				</td>
				<td>
					<span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>CORREO:</strong>
				</td>
				<td>
					<span name="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td>
					<span class="bold">&nbsp;</span>
				</td>
				<td colspan="3">&nbsp;</span>
				</td>
			</tr>
		</table>
		<table id="datosTabla" style="table-layout: fixed; width: 100%; word-wrap: break-word; border-collapse: collapse;font-family:Verdana, Geneva, sans-serif; font-size:12px"
		 cellpadding="3" border="1" class="noBorder">
			<thead>
				<tr>
					<th style="width:8%;">Cantidad</th>
					<th style="width:56%;" align="center">Descripci�n</th>
					<th style="width:12%;" align="center">P.Unitario</th>
					<th style="width:12%;" align="center">Importe</th>
					<th style="width:12%;" align="right">Total</th>
				</tr>
			</thead>
			<tbody id="tablita" class='noBorder' align='center' style="border-bottom: none;">

			</tbody>
			<tbody class='noBorder' style="border-collapse: collapse;">

				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>SUBTOTAL:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_subtotal" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TARIFA 0%:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva0" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TARIFA
							<span class="iva_por"></span>%:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva12" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>
							<span class="iva_por"></span>% IVA:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_iva" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>DESCUENTO:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_descuento" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
				<tr>
					<td colspan="1"></td>
					<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TOTAL REQUISICION:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_rubros" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
			</tbody>
		</table>
		<br/>

		<br/>
		<table style="width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
			<tr>
				<td align='center' style="width: 25%;">
					<strong>OBSERVACION:</strong>
				</td>
				<td align='center' style="width: 25%;">
					<strong>SELLO/FIRMA</strong>
				</td>
			</tr>
			<tr style="height: 40px;">
				<td align='center'>
					<span style="width: 39%; font-size: 10px; text-align: justify;" name="Req_Obs" class="form-control input-xs databind datatitle"></span>
				</td>
				<td></td>
			</tr>
		</table>
		<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>

	</div>

	<!-- Formato Reporte -->
	<div id="formatoReporteRequisicion" style="display: none;">
		<div style="width: 900px;">
			<?php 
            if($Ses_Emp_Cod == 300){
                echo '<h3 style="text-align: center; font: 14pt Verdana, Geneva, sans-serif; font-weight: bold;"> REQUISICION NO <span id="titleReporte"></span></h3>';
            }else{
            echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REQUISICION NO <span id="titleReporte"></span>', ' ', $obBD_conexion); 
            }
            ?>
			<table id="tablaReporte" cellspacing="0" cellpadding="0" style="border-collapse: collapse;table-layout: fixed;"></table>
			<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
		</div>
	</div>


	<!-- Inicio del di�logo para buscar requisiciones de requisitores -->
	<!--<div id="requisitorDialog" title="B&uacute;squeda de Requisitores con Requisiciones"><form class="form-horizontal normal"> </form></div>-->
	<!-- Inicio del di�logo para buscar de requisitores -->
	<div id="requisitorSearch" title="B&uacute;squeda de Requisitores" style="display: none">
		<form name="searchRequisitor" id="searchRequisitor" method="get" class="form-horizontal normal" action="javascript:$('#requisitorSearch').Search('#searchRequisitor','requisitorSearchAjax');"></form>
	</div>
	<!-- Inicio del di�logo para buscar de Produuctos -->
	<div id="reqDialog" title="B&uacute;squeda de Requisiciones"></div>

	<script src="../../VALIDACIONES/requisiciones/registrar.js?a=100"></script>
	<script type="text/javascript" src="/framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="/framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="/framework/plugins/moment.min.js"></script>
	</BODY>



</HTML>