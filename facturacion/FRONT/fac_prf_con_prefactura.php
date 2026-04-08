<?php
/**
 * @abstract Permite realizar el registro de proformas
 * @author Cear Bermeo
 * @version 1.0
 * Fecha de creación  2018-07-12
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/prf_log_proforma.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Proforma;
//$obBD_con1->getArrayConsulta('vendedor.selectWhere', array_merge($_GET,array('setWhere'=>array('setEmpCod','setUsuCod'))), $obBD_conexion, true);
if (isset($profAjax)) {
   //$responce = $obBD_con1->getPageGridJson('proformas.selectWhere',array_merge($_GET,array('setWhere'=>array('isActive'))), $obBD_conexion, true);
   $responce = $obBD_con1->getPageGridJson('proformas.selectWhere', $_GET, $obBD_conexion,true);
   //$responce = $obBD_con1->getPageGridJson('proformas.selectWhere',array_merge($_GET,array('setWhere'=>array('byVendedor'))), $obBD_conexion, true);
   //$resp = $obBD_con1->getArrayConsultaSql("select * from proformas_det inner join proformas on proformas.Prf_Cod=proformas_det.Prf_Cod;", $obBD_conexion, true);
   $obBD_con1->echoLog($responce);
}
/**
 * Busqueda de proformas con sus respectivos totales
 */
if(isset($profAjaxTotales)){
    $data=array_merge($_GET, array('setWhere'=>array('detProf')));
    $respuesta = $obBD_con1->getPageGrid('proformas.selectWhere',$data,$obBD_conexion,true);
    $obBD_con1->echoJson($respuesta);
}
//$sqlClase=$obBD_con1->getArrayConsulta('proformas_det.selectWhere', $_GET, $obBD_conexion, true);
//$sqlNativa=$obBD_con1->getArrayConsultaSql("select * from proformas_det inner join proformas on proformas.prf_cod = proformas_det.prf_cod;", $obBD_conexion, true);

//select * from proformas_det inner join proformas on proformas.prf_cod = proformas_det.prf_cod where Prf_Num='2018-000000000016';
//select * from proformas_det inner join proformas on proformas.prf_cod = proformas_det.prf_cod inner join producto on proformas_det.Pro_Cod=producto.pro_cod inner join item on producto.ite_cod=item.ite_cod;
//select * from proformas_det prfd, proformas prf, producto prd, item itm WHERE prf.prf_cod=prfd.prf_cod AND prfd.prf_cod=prd.pro_cod AND prd.ite_cod=itm.ite_cod;
//select prfd.Prf_Cod, prfd.Pfd_Int, prfd.Pro_Cod, prfd.Prf_Imp,  prfd.Prf_Cant, prfd.Prf_Pru, prf.Prf_Cod, prf.Prf_Des,prf.Prf_Num, prf.Prf_Obs, prd.Pro_Cod, prd.Ite_Cod, itm.Ite_Cod, itm.Ite_Lar  from proformas_det prfd, proformas prf, producto prd, item itm WHERE prf.prf_cod=prfd.prf_cod AND prfd.Pro_Cod=prd.pro_cod AND prd.ite_cod=itm.ite_cod;
if(isset($profDetalleAjax)){
    $resProformas=array(
        'success' => true,
        'todasPrf'=>$obBD_con1->getArrayConsultaSql("select prfd.Prf_Cod, prfd.Pfd_Int, prfd.Pro_Cod, prfd.Prf_Imp,  prfd.Prf_Cant, prfd.Prf_Pru, prfd.Iva_Cod, prf.Prf_Cod, prf.Prf_Des,prf.Prf_Num, prf.Prf_Obs, prd.Pro_Cod, prd.Ite_Cod, iv.Iva_Cod, iv.Iva_Por, iv.Iva_Ini, iv.Iva_Fin, itm.Ite_Cod, itm.Ite_Lar  from proformas_det prfd, proformas prf, producto prd, item itm, iva iv WHERE prf.prf_cod=prfd.prf_cod AND prfd.Pro_Cod=prd.pro_cod AND prd.ite_cod=itm.ite_cod and iv.Iva_Cod=prfd.Iva_Cod and prfd.Prf_Cod='$Prf_Cod';", $obBD_conexion),
        'vendedorAct'=>$obBD_con1->getArrayConsultaSql("SELECT vendedor.*, CONCAT(Prs_Nom,' ',Prs_Ape) AS Vendedor, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced, persona.Prs_Dir, persona.Prs_Cor, puntos_imp.Pun_Des AS Punto, puntos_imp.Suc_Cod FROM vendedor INNER JOIN persona ON persona.Prs_Cod=vendedor.Prs_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod WHERE (puntos_imp.Suc_Cod='$Ses_Suc_Cod') AND Vnd_Cod='$Vnd_Cod';",$obBD_conexion),
        'ventasAct'=>$obBD_con1->getArrayConsultaSql("SELECT ventas.* , caja_aper.Caj_Fec, CONCAT(Prs_Nom,' ',Prs_Ape) AS Cliente from ventas inner join cliente on cliente.Cli_Cod=ventas.Cli_Cod inner join persona on persona.Prs_Cod=cliente.Prs_Cod inner join caja_aper on ventas.Caj_Cod=caja_aper.Caj_Cod  where Prf_Cod= '$Prf_Cod';",$obBD_conexion),
        'numVentasAct'=>$obBD_con1->getArrayConsultaSql("SELECT  COUNT(*) AS total from ventas inner join cliente on cliente.Cli_Cod=ventas.Cli_Cod inner join persona on persona.Prs_Cod=cliente.Prs_Cod inner join caja_aper on ventas.Caj_Cod=caja_aper.Caj_Cod  where Prf_Cod= '$Prf_Cod';",$obBD_conexion),
        'ivas'=>!isset($Prf_Fec)?array():$obBD_con1->getArrayConsultaSql("SELECT * FROM iva WHERE  '$Prf_Fec'  BETWEEN Iva_ini AND Iva_fin AND Iva_Est = 'A' AND Iva_Por>0;", $obBD_conexion, true),
    );
    $obBD_con1->echoJson($resProformas);
}

$numActualProf=$obBD_con1->getRowConsultaSql("SELECT MAX(Prf_Num) AS total FROM proformas INNER JOIN cliente ON cliente.Cli_Cod=proformas.Cli_Cod INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod INNER JOIN sucursal ON cliente.Emp_Cod=sucursal.Emp_Cod WHERE (sucursal.Emp_Cod='$Ses_Emp_Cod');", $obBD_conexion);
$tituloImp= 'Proforma Nº- '.($numActualProf['total'] + 1);

 ?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
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
            #Ret_Asu{ vertical-align: middle; margin-top: -2px; padding: 5px;  -ms-transform: scale(1.4); -moz-transform: scale(1.4); -webkit-transform: scale(1.4); -o-transform: scale(1.4); }
            #resultContent .resp{ font-weight: 700; font-size: 30px; color: #3f3fc1; padding: 0; margin: 0; overflow: hidden; text-overflow: ellipsis; height: 32px; }
            #resultContent .resp span:first-child{ color:darkgoldenrod;width: 100px;display: inline-block; margin-left: 42px; }
            .msg_fly{ font-size: 12px !important; }
            .ret .input-group-btn button{padding: 1px 2px !important;}
            .ret{ padding: 0 !important;}
            .nowrap{ white-space:nowrap !important; }
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Pre Facturas</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="allProformas" class = "row">
                    <div class="col-sm-12">
                        <div id="tabsProformas" class="ui-tab-fix ui-tabs">
                            <ul class="ui-tabs-nav">
                                <li><a href="#tabs-1">Individual</a></li>
                                <li><a href="#tabs-2">Totales</a></li>
                            </ul>
                            <div class="panels-area form-horizontal normal ">
                                <!-- 1 TAB !-->
            <div id="tabs-1" class="ui-tabs-panel ui-widget-content">
		<div class="row">


                    <div>
                        <form name="searchProf" id="searchProf" method="get" class="form-horizontal normal" action="javascript:$('#container').Search('#searchProf','profAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-5 radioset opt_search">
                                        <input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                        <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;# Pre Factura&nbsp;&nbsp;</label>
                                        <input id="radsc3" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;Fecha&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-5">
                                        <div class="input-group input-group-sm">
                                            <input  id="search" name="search" onkeydown="if (event.keyCode === 13)
                                        this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Proforma"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                        </div><!-- /input-group -->
                                    </div><input type="text" tabindex="-1" style="display:none;" />
                                    <div id="d" class="col-xs-6" style="display:none;">
                                        <div class="" >
                                            <label id="dl" class="col-xs-3 control-label label-sm" disabled>Desde:</label>
                                            <div  class="col-xs-3">
                                                <input type="text" id="desde" name="desde" class="form-control datepickers input-sm"  style="text-align:center; background-color:powderblue;"  disabled />
                                            </div>
                                            <label id="dlh" class="col-xs-2 control-label label-sm" disabled>Hasta:</label>
                                            <div  class="col-xs-3">
                                                <input type="text" id="hasta" name="hasta" class="form-control datepickers input-sm"  style="text-align:center; background-color:powderblue;"  disabled />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                    <div id="tablasContainer" class="" style="min-height: 550px;">
                            <table id="container"></table>
                            <div id="containerPager"></div>
                    </div>
		</div>
	</div>
	<!-- 2 TAB !-->
	<div id="tabs-2" class="ui-tabs-panel ui-widget-content">
		<div class="row">

			<!-- <legend class="Titulos2">Pre Facturas registrados</legend> -->
			<div>
				<form name="searchProfT" id="searchProfT" method="get" class="form-horizontal normal" action="javascript:$('#containerT').Search('#searchProfT','profAjaxTotales');">
					<fieldset class="exa-fieldset">
						<legend class="Titulos2">B&uacute;squeda</legend>
						<div class="form-group">
							<label class="col-xs-1 control-label label-xs">Filtrar Por:</label>
							<div class="col-xs-5 radioset opt_search">
								<input id="radsct1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsct1">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
								<input id="radsct2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsct2">&nbsp;&nbsp;# Pre Factura&nbsp;&nbsp;</label>
								<input id="radsct3" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" alt="" /><label for="radsct3">&nbsp;&nbsp;Fecha&nbsp;&nbsp;</label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-xs-1 control-label">B&uacute;squeda:</label>
							<div class="col-xs-5">
								<div class="input-group input-group-sm">
									<input id="search" name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..."
									 autofocus class="form-control input-xs clearable submit" />
									<span class="input-group-btn">
										<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Pre Factura" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button>
									</span>
								</div>
								<!-- /input-group -->
							</div>
                                                        <div id="divFecha" class="col-xs-6" style="display:none;">
								<div class="col-xs-1"></div>
								<div class="col-xs-10">
									<div class="input-group input-group-sm por_fecha">
										<span class="input-group-addon"><span class="">Rango:</span></span>
										<span class="input-group-addon alert-info">Desde</span>
										<input type="text" id="desdeT" name="desdeT" class="form-control" disabled="" />
										<span class="input-group-addon alert-info">Hasta</span>
										<input type="text" id="hastaT" name="hastaT" class="form-control" disabled="" />
									</div>
								</div>

							</div>
							<input type="text" tabindex="-1" style="display:none;" />
						</div>
					</fieldset>
				</form>
			</div>
			<div id="tablasContainerT" class="" style="min-height: 550px;">
				<table id="containerT"></table>
				<div id="containerPagerT"></div>
			</div>


		</div>
	</div>
	</div>
	</div>

	</div>
	</div>
	<!-- FORMULARIO iMPRESION -->
	<div class="container" id="documentoVista" style="display:none;">
		<div id="datosPrf" class="box-container">
			<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'Proforma Nº <span id="titleReporte"></span>', ' ', $obBD_conexion); ?>
			<form name="datosProf" id="datosProf" class="form-horizontal normal">
				<div class="row">
					<div class="col-sm-12">
						<fieldset class="exa-fieldset">
							<table border="0" cellpadding="0" cellspacing="0" id="cabeceraTabla" style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px "
							 class="rep">
								<tr style="height: 0;">
									<td style="width: 10%;"></td>
									<td style="width: 40%;">&nbsp;</td>
									<td style="width: 10%;"></td>
									<td style="width: 40%;">&nbsp;</td>
								</tr>
								<tr>
									<td class='bold' style="font-size: 12px;">
										<strong>RUC:</strong>
									</td>
									<td colspan="1">
										<span name="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
									<td class='bold' style="font-size: 12px;">
										<strong>FECHA:</strong>
									</td>
									<td colspan="1">
										<span name="Prf_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
									<td class='bold' style="font-size: 12px;">
										<strong>Ord.Comp:</strong>
									</td>
									<td>
										<span name="Prf_Ord" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
								</tr>
								<tr>
									<td class='bold' style="font-size: 12px;">
										<strong>Cliente:</strong>
									</td>
									<td colspan="4">
										<span name="Cliente" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
									</td>
								</tr>
								<tr>
									<td class='bold' style="font-size: 12px;">
										<strong>Direccion:</strong>
									</td>
									<td colspan="4">
										<span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
								</tr>
								<tr>
									<td class='bold' style="font-size: 12px;">
										<strong>Correo:</strong>
									</td>
									<td>
										<span name="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
								</tr>
								<tr style="display:none;">
									<td class='bold' style="font-size: 12px;">
										<strong>Vendedor:</strong>
									</td>
									<td>
										<span id="Vnd_Cod" name="Vnd_Cod" style="font-size: 12px;" class="form-control input-xs databind datatitle">
									</td>
								</tr>
							</table>
						</fieldset>
					</div>
				</div>
				<br />
			</form>

			<div class="jqHeaderFirst jqFirst" style="min-height: 50px;">
				<table id="datosTabla" style="width: 100%;border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px"
				 align='center' cellpadding="5" border="1" class="noBorder">
					<thead>
						<tr>
							<th style="width:5%;font-size: 12px;">Cantidad</th>
							<th style="width:60%;font-size: 12px;">Descripción</th>
							<th style="width:10%;font-size: 12px;">P.Unitario</th>
							<th style="width:10%;font-size: 12px;">Importe</th>
							<th style="width:15%;font-size: 12px;" align='right'>Total</th>
						</tr>
					</thead>
					<tbody id="tablita" class='noBorder' align='center' style="border-bottom: none;">

					</tbody>
					<tbody class='noBorder' style="border-collapse: collapse;">

						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>SUBTOTAL:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_subtotal" align='right' name="t_subtotal" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>TARIFA 0%:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_tarifaCI" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>TARIFA
									<span class="iva_por"></span>%:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_tarifaDCI" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>
									<span class="iva_por"></span>% IVA:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_tarifaDI" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;font-size: 12px;">
								<strong>DESCUENTO:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_descuento" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
						<tr>
							<td colspan="1"></td>
							<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;font-size: 12px;">
								<strong>TOTAL PRE FACTURA:</strong>
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_rubros" align='right' name="t_rubros" class="form-control input-xs databind datatitle bold"></span>
							</td>

						</tr>
					</tbody>
				</table>
			</div>
			<br/>

			<table style="width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
				<tr>
					<td class='bold' align='center' style="width: 25%;font-size: 12px;">
						<strong>OBSERVACION:</strong>
					</td>
					<td class='bold' align='center' style="width: 25%;font-size: 12px;">
						<strong>SELLO/FIRMA</strong>
					</td>
				</tr>
				<tr style="height: 40px;">
					<td>
						<span id="Prf_Obs" align='right' name="Prf_Obs" class="form-control input-xs databind datatitle bold"></span>
					</td>
					<td></td>
				</tr>
			</table>
			<div class="grid" style="width: 39%; font-size: 10px; text-align: justify;">
				<span class="bold">
					<strong>Nota:</strong>
				</span>Precios incluyen IVA.</div>
			<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
		</div>
	</div>
	<!--FORMULARIO VISTA -->
	<div class="container">
		<div class="row">
			<div class="col align-self-center" id="documentoVistaD" style="display:none;">
				<form name="datosProf2" id="datosProf2" class="form-horizontal normal">
					<div class="row">
						<div class="col-sm-2"></div>
						<div class="col-sm-8">
							<div class="col align-self-center">
								<fieldset class="exa-fieldset">
									<span align="right" id="numProfor" name="numProfor" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
									<table id="cabeceraTabla" style="width: 100%;border-collapse: collapse;" class="rep">
										<tr style="height: 0;">
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
										</tr>
										<tr>
											<td class='bold'>RUC:</td>
											<td colspan="1">
												<span name="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>FECHA:</td>
											<td colspan="1">
												<span name="Prf_Fec" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>CLIENTE:</td>
											<td colspan="4">
												<span name="Cliente" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
											</td>
										</tr>
										<tr>
											<td class='bold'>DIRECCION:</td>
											<td colspan="4">
												<span name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>Ord.Comp:</td>
											<td colspan="1">
												<span name="Prf_Ord" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>Vendedor:</td>
											<td>
												<span id="Vnd_CodD" name="Vnd_Cod" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>CORREO:</td>
											<td>
												<span name="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
									</table>
								</fieldset>
							</div>
						</div>
					</div>
					<br />
				</form>
				<div class="col-sm-12" align='center'>
					<div class="jqHeaderFirst jqFirst">
						<table id="prubaTabla"></table>
					</div>
				</div>
				<div class="container">
					<div class="row">
						<div class="col align-self-center">
							<div class="jqHeaderFirst jqFirst" style="min-height: 50px;">
								<!---->
								<table id="datosTablaDos" style="width: 100%;border-collapse: collapse; display:none;" align='center' cellpadding="5" border="1"
								 class="pull-right">
									<thead>
										<tr>
											<th style="width:5%;">Cantidad</th>
											<th style="width:60%;">Descripción</th>
											<th style="width:10%;">P.Unitario</th>
											<th style="width:10%;">Importe</th>
											<th style="width:15%;">Total</th>
										</tr>
									</thead>
									<tbody id="tablitaDos" class='noBorder' align='center' style="border-bottom: none;">

									</tbody>
									<tbody class='noBorder' style="border-collapse: collapse;">

										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">SUBTOTAL:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_subtotalD" name="t_subtotal" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">TARIFA 0%:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_tarifaC" name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">TARIFA
												<span class="iva_por"></span>%:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_tarifaDC" name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">
												<span class="iva_por"></span>% IVA:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_tarifaD" name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">DESCUENTO:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_descuentoD" name="t_descuento" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
										<tr>
											<td colspan="1"></td>
											<td colspan="3" align="right" class="bold" style=" border-top: 1px solid;">TOTAL PRE FACTURA:</td>
											<td align='center' style=" border-top: 1px solid;">
												<span id="t_rubrosD" name="t_rubros" class="form-control input-xs databind datatitle bold"></span>
											</td>

										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<br/>

				<div class="row" style="display:none;">
					<div class="col-sm-2"></div>
					<div class="col-sm-8">
						<div class="col align-self-center">
							<table style="width: 100%;font-size: 12px;border-collapse: collapse;" border="1">
								<tr>
									<td class='bold' align='center' style="width: 25%;">OBSERVACION:</td>
									<td class='bold' align='center' style="width: 25%;">SELLO/FIRMA</td>
								</tr>
								<tr style="height: 40px;">
									<td></td>
									<td></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<br/>
				<div class="center">
					<button type="button" class="btn btn-sm btn-inverse" onclick="clearDocument();$('#documentoVistaD').moveComp('#allProformas').updateGridsSizes();">
						<i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
					<button type="button" class="btn btn-sm btn-primary" onclick="impProforma();">
						<i class="glyphicon glyphicon-print"></i> Imprimir</button>
				</div>

			</div>
		</div>
	</div>
	<!-- DIALOGO DETALLE PROFORMA -->
	<div id="docDetaDialog" title="Pre Factura - Venta">
		<fieldset class="exa-fieldset">
			<legend class="Titulos2">Pre Factura:</legend>
			<div class="form-horizontal normal" style="padding: 0 4px;">
				<div class="form-group">
					<label class="col-xs-2 control-label label-xs">Cod.Int:</label>
					<div class="col-xs-2">
						<span name="Prf_Cod" class="form-control input-xs"></span>
					</div>
					<label class="col-xs-1 control-label label-xs">Num:</label>
					<div class="col-xs-2">
						<span name="Prf_Num" class="form-control input-xs"></span>
					</div>
					<label class="col-xs-1 control-label label-xs">Ord:</label>
					<div class="col-xs-3" style="text-align: center;">
						<span name="Prf_Ord" class="form-control input-xs"></span>
					</div>

				</div>
				<div class="form-group">
					<label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
					<div class="col-xs-4">
						<span name="Prs_Ced" class="form-control input-xs"></span>
					</div>
					<label class="col-xs-1 control-label label-xs">Fecha:</label>
					<div class="col-xs-4" style="text-align: center;">
						<span name="Prf_Fec" class="form-control input-xs"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-2 control-label label-xs">Cliente:</label>
					<div class="col-xs-9">
						<span name="Cliente" class="form-control input-xs"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-2 control-label label-xs">Dirección:</label>
					<div class="col-xs-9">
						<span name="Prs_Dir" class="form-control input-xs"></span>
					</div>
				</div>
				<div class="form-group condensed">
					<div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;">
						<b>Vendedor:</b>
						<span id="vendedor" name="vendedor" class="databind"></span>
					</div>
				</div>
		</fieldset>
		<fieldset class="exa-fieldset" id="venViewGrid">
			<legend class="Titulos2">Ventas Asociadas:</legend>
			<div class="form-horizontal normal" style="padding: 0 4px;">
				<div class="form-group condensed">
					<div class="col-xs-12">
						<div class="pull-right">
							<table id="detaDocu"></table>
						</div>
					</div>
				</div>
		</fieldset>
		</div>

		</div>
	</div>
	</div>
	<script src="../VALIDACIONES/prf_val_con_proformas.js?x=109"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>