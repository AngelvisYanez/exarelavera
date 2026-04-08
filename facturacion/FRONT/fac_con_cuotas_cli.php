<?php
/**
 * @abstract Permite registrar alicuotas clientes
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creaci�n: 25/01/2019
 */

  require_once('../../administrador/LOGICA/seguridad.php');
  require_once('../LOGICA/fac_log_cuotas_cli.php');
  require_once('../../Librerias/procedimientos/almacenados_standar.php');

  /* Creacion del Objeto de conexion */
    $obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
 /* Creacion del objeto mysql para las consultas */
    $obBD_con1 = new Class_Log_Datos_Cuo;
    /**
     *Busqueda de clientes
     */
    if (isset($clienteAjax)) {
        $respuesta = $obBD_con1->getPageGridJson('cliente.selectWhere', $_GET, $obBD_conexion, true);
    }
    /**
     * Busqueda de Ventas ventasAjax
     */
    if (isset($ventasAjax)) {
    $datos = array_merge($_GET, array('setWhere' => array('isActive','setTotales','setAliCuotas'/*,'byAliPagos'*//*,'addNumeroVenta'*/,'isTicCod0'/*'byTicCod'*/,'groupByAliCod'/*'setAliPagos'*//*,'addVerificaSaldo'*/)));
        $resultado = $obBD_con1->getPageGrid('ventas.selectWhere',$datos, $obBD_conexion, true);
        $obBD_con1->echoJson($resultado);
    }

    /**
     * Busqueda de ventas searchAllFacVentas
     */
    if(isset($searchAllFacVentas)){
        $data = $_GET;
        $cero =0;
        $obBD_con1->echoLog('** PHP MOVIMIENTOS* SEARCH ***');
        $obBD_con1->echoLog($valor);
        $obBD_con1->echoLog(trim($data['letra']));
        //'where' => array('Fnc_Cod' => $Fnc_Cod)
        if(trim($data['letra']) == 'TODOS'){$datos = array_merge($_GET, array('setWhere' => array('isActive','setTotales','setAliCuotas'/*'aliPagosGeneral'*//*,'byAliPagos'*//*,'addNumeroVenta'*/,'isTicCod0'/*'byTicCod'*/,'groupByAliCod'/*'setAliPagos'*//*,'addVerificaSaldo'*/))); }
        if(trim($data['letra']) == 'Pagadas'){$datos = array_merge($_GET, array('setWhere' => array('isActive','setTotales','setAliCuotas'/*'byAliPago'*//* 'aliPagosGeneral' *//*,'byAliPagos'*//*,'addNumeroVenta'*/,'isTicCod0'/*'byTicCod'*//*,'addVerificaSaldo'*/,'hasAliPag'/*'sinSaldo'*/),'having'=>'Resto=0')); }
        if(trim($data['letra']) == 'Por Pagar'){$datos = array_merge($_GET, array('setWhere' => array('isActive','setTotales','setAliCuotas'/*'byAliPorPagar'*//*,'byAliPagos'*//*,'addNumeroVenta'*/,'isTicCod0'/*'byTicCod'*/,'groupByAliCod'/*'setAliPagos'*//*,'addVerificaSaldo'*/,/*'hasSaldo'*/),'having'=>'Resto>0')); }


        $resultado = $obBD_con1->getPageGrid('ventas.selectWhere',$datos, $obBD_conexion, true);
        $obBD_con1->echoJson($resultado);
    }

    /**
     * Busqueda de F.Venta ND del subgrid
     */
    if(isset($movAlicuota)){

        $data=array_merge($_GET, array('setWhere' => array('isMovVetCod', 'isActive')));
        $respuesta =  $obBD_con1->getPageGrid('ali_pagos.selectWhere', $data, $obBD_conexion, true);
        $obBD_con1->echoJson($respuesta);

	}

	/**
	 * Busqueda del detalle con Vet_Cod
	 */
	if(isset($detallePorVenta)){
		$resultado = array(
			'success'=>true,
			'detSubGridDetail'=>$obBD_con1->getArrayConsulta('ali_pagos.selectWhere', array('where'=>array('Vet_Cod'=>$Vet_Cod), 'setWhere'=>array('isActive')), $obBD_conexion, true),
		);
		$obBD_con1->echoJson($resultado);

	}

    /**
     * Guardar
     */
    if(isset($saveDocumento)){
        $resp=array('success'=>false);
        $obBD_ins1 = new Class_Log_Datos_Cuo;
        $obBD_conexionIns = new  Class_Log_Conexion_Global($Ses_Dat_Dis);
        $obBD_ins1->debug(true);
        $obBD_ins1->inicio_transaccion($obBD_conexionIns);
        try{
            $obBD_con1->echoLog('**-- PHP GUARDAR --**');
            $data = $_POST;
            $obBD_con1->echoLog($data);
            if (isset($saveCrear)) {
                $obBD_con1->echoLog('**-- PHP GUARDAR  crear--**');
                foreach($alicuotas as $alicouta){

                    if ($alicouta['Cli_Cod_Ali'] != 0) {

                        $obBD_ins1->operacionobBD('ali_pagos.insert',array('Ali_Fec'=>$alicouta['Ali_Fec_Venta'], 'Ali_Pag'=>$alicouta['Ali_Pag'], 'Cli_Cod'=>$alicouta['Cli_Cod_Ali'],'Vet_Cod'=>$alicouta['Vet_Cod_Ali']), $obBD_conexionIns, true);
                    }
                }
            }

        }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
	    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
        if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;
        $obBD_con1->echoJson($resp);

    }

?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Ventas Reporte Alicuotas [EXA]"; ?></TITLE>
    <meta charset= "UTF-8">
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script> </script>
        <style>
            .ret2{
                background-color: white;
            }
            #ventasDialog ui-corner-left{
               visibility: hidden;
            }
            .pagination>li>a, .pagination>li>span {padding: 4px 2px;}
            .pagination {/*display: block;*/margin:0;padding: 0;}
            .chosen-default span,.chosen-single span{color:#555;}
            .chosen-single span{padding-left: 5px;}
			.visibilityHide{
				visibility:hidden;
			}

        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main" id="formFinal">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Control de Alicuotas</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div id="tabsAlicuotas" class="ui-tab-fix">
                            <ul>
                                <!-- <li><a href="#tabs-1">Registrar</a></li> -->
	<li>
		<a href="#tabs-2">Consultar</a>
	</li>
	</ul>
	<div class="panels-area form-horizontal normal ">
		<!--  <div id="tabs-1">
                                                    <div class="row">
                                                        <form id="frm_ali_cuotas" name="frm_ali_cuotas" class="form-horizontal normal" action="javascript:saveAliCuota('frm_ali_cuotas','saveCrear');">
                                                            <div class="col-md-12">
                                                                <table id="Cuo_Grid"></table>
                                                                <div id="Cuo_Page"></div>
                                                            </div>
                                                            <div style="text-align: center;padding-top: 5px;">
                                                                <button type="button" id="btn_guardar_cuota" name="btn_guardar_cuota" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div> -->
		<div id="tabs-1">
			<div id="tab2" class="row">
				<form id="frm_mod_ali_cuotas" name="frm_mod_ali_cuotas" class="form-horizontal normal" action="javascript:$('#tableAlicuotas').Search('#frm_mod_ali_cuotas','searchAllFacVentas');">
					<div class="col-sm-6 col-md-6 col-lg-6">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">B&uacute;squeda de Facturas</legend>
							<input name="order" type="hidden" value="" />
							<div class="form-group">
								<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
								<div class="col-sm-5 radioset">
									<input id="rad_ba1" name="op_opciones" type="radio" value="k" checked="" onclick="setfocus(this.form.search)" />
									<label for="rad_ba1">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
									<input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" />
									<label for="rad_ba2">&nbsp;&nbsp;Factura&nbsp;&nbsp;</label>
									<input id="rad_ba3" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" />
									<label for="rad_ba3">&nbsp;&nbsp;Por Fecha&nbsp;&nbsp;</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-2 control-label">B&uacute;squeda:</label>
								<div class="col-xs-8">
									<div class="input-group">
										<input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda"
										 autofocus="">
										<span class="input-group-btn">
											<button id="btnSearch" type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Factura" tabindex="-1">
												<span class="glyphicon glyphicon-search"></span>
												<span>Buscar</span>
											</button>
										</span>
									</div>
								</div>
							</div>
						</fieldset>
						<div class="form-group">
							<div class="col-sm-12 center">
								<input type="hidden" id="letra" name="letra" value="TODOS" />
								<nav>
									<?php $valores = array("Pagadas", "Por Pagar", "TODOS"); ?>
									<ul class="pagination pagination-centered">
										<?php foreach ($valores as $valor) { ?>
										<li <?php if ($valor=='TODOS' ) echo 'class="active"'; ?>>
											<a>
												<?php echo $valor; ?>
											</a>
										</li>
										<?php } ?>
									</ul>
								</nav>

							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<fieldset class="exa-fieldset">
							<legend class="Titulos2">Filtros</legend>
							<div class="form-group">
								<div class="col-xs-12">
									<div class="input-group input-group-xs">
										<span class="input-group-addon bold alert-info">Desde:</span>
										<input disabled onchange="" name="Fec_Ini" type="text" id="Fec_Ini" size="10" class="form-control input-xs datepicker databind"
										 style="text-align: center;" />
										<span class="input-group-addon bold alert-info">Hasta:</span>
										<input disabled name="Fec_Fin" type="text" id="Fec_Fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;"
										/>
									</div>
								</div>
							</div>
						</fieldset>
					</div>
				</form>
				<div class="col-sm-12" style="min-height: 200px; padding-bottom: 5px;">
					<table id="tableAlicuotas"></table>
					<div id="tableAlicuotasPager"></div>
					<div class="Titulos2">
						<span id="plan-footer">
							<strong>Leyenda:</strong>
							<span class="glyphicon glyphicon-stop" style="color:#8bff9f;"></span> Facturas Pagadas
						</span>
					</div>
				</div>
				<div id='MessageHolder'></div>
				<a href="#" id="testAnchor"></a>
			</div>
		</div>
	</div>
	</div>

	</div>
	</div>
	</div>
	</div>

	<!--Datos Reporte -->
	<div id="datosReporte" class="grid" style="display:none;">
		<h3 style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; text-align: center;">PAGO A FACTURA</h3>
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
				<td colspan="3">&nbsp;
					<span name="Prs_Ced" id="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td class='bold'>
					<strong>FECHA:</strong>
				</td>
				<td>&nbsp;
					<span name="Caj_Fec" id="Caj_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>CLIENTE:</strong>
				</td>
				<td colspan="5">&nbsp;
					<span name="Cliente" id="Cliente" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>DIRECCION:</strong>
				</td>
				<td colspan="5">&nbsp;
					<span name="Prs_Dir" id="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
			</tr>
			<tr>
				<td class='bold'>
					<strong>CORREO:</strong>
				</td>
				<td>
					<span name="Prs_Cor" id="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
				</td>
				<td>
					<span class="bold">&nbsp;</span>
				</td>
				<td colspan="3">&nbsp;</span>
				</td>
			</tr>
		</table>
		<table id="datosTabla" style="width: 100%;border-collapse: collapse;font-family:Verdana, Geneva, sans-serif; font-size:12px"
		 cellpadding="3" border="1" class="noBorder">
			<thead>
				<tr>
					<th style="width:15%;">Num. Factura</th>
					<th style="width:60%;" align="center">Descripción</th>
					<th style="width:25%;" align="right">Abono</th>
				</tr>
			</thead>
			<tbody id="tablita" class='noBorder' align='center' style="border-bottom: none;">
			</tbody>
			<tbody class='noBorder' style="border-collapse: collapse;">
				<tr>
					<td colspan="1"></td>
					<td colspan="1" align="right" class="bold" style=" border-top: 1px solid;">
						<strong>TOTAL:</strong>
					</td>
					<td align='right' style=" border-top: 1px solid;">
						<span name="t_abono" id="t_abono" class="form-control input-xs databind datatitle"></span>
					</td>

				</tr>
			</tbody>
		</table>
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
					<span style="width: 39%; font-size: 10px; text-align: justify;" name="Ali_Obs" class="form-control input-xs databind datatitle"></span>
				</td>
				<td></td>
			</tr>
		</table>
		<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
	</div>
	<div id="clienteDialog" title="B&uacute;squeda de Clientes">
		<form id="frmClientes">
			<input type="hidden" id="CodFormBus" name="CodFormBus">
		</form>
	</div>
	<div id="ventasDialog" title="B&uacute;squeda de Ventas">
		<form id="frmVentas">
			<input type="hidden" id="ParamCodAux" name="Cli_Cod">
			<input type="hidden" id="CodFormVen" name="CodFormVen">
		</form>
	</div>

	<script src="../VALIDACIONES/fac_val_con_alicuotas.js?k=197"></script>



	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>