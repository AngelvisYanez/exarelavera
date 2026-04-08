<?php
/**
 * @abstract Permite realizar el registro de requisiciones
 * @author Angeloni Cuesta
 * @version 1.0
 * Fecha de creaci�n  2021-06-15
 */

require_once('../../../administrador/LOGICA/seguridad.php');
require_once('../../LOGICA/requisiciones/index.php');
require_once('../../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Requisiciones($obBD_conexion);
if (isset($profAjax)) {
    //ChromePhp::log("PROFAJAX");
	$_GET["Emp_Cod"] = $Ses_Emp_Cod;
    $responce = $obBD_con1->getArrayConsulta("requisiciones.2",$_GET, $obBD_conexion);
	$obBD_con1->echoJson(array(
		'rows'=>$responce,
		'total'=>1,
		'records'=>count($responce),
		'success'=>true
	));
}

if (isset($getRequisicionPorId)) {
    //ChromePhp::log("GETREQUISICIONPORID");
	$_POST["Emp_Cod"] = $Ses_Emp_Cod;
    $respuesta["requisicion"] = $obBD_con1->getRowConsulta("requisiciones.4",$_POST, $obBD_conexion);
	$respuesta["requisicion_det"] = $obBD_con1->getArrayConsulta("requisiciones_det.0",$_POST, $obBD_conexion);
	$obBD_con1->echoJson($respuesta);
}
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
        </style>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo; Requisiciones</h3></div>
                <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                    <div id="allProformas" class = "row">
                        <div class="col-sm-12">
                            <div id="tabsProformas" class="ui-tab-fix ui-tabs">
                                <ul class="ui-tabs-nav">
                                    <li><a href="#tabs-1">Individual</a></li>
                                    <!-- <li><a href="#tabs-2">Totales</a></li> -->
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
                                        <input id="radsc1" name="op_opciones" type="radio" value="requisitor" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;Requisitor&nbsp;&nbsp;</label>
                                        <input id="radsc2" name="op_opciones" type="radio" value="numero" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;# Requisicion&nbsp;&nbsp;</label>
                                        <input id="radsc3" name="op_opciones" type="radio" value="fecha" onclick="setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;Fecha&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-1 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-5">
                                        <div class="input-group input-group-sm">
                                            <input  id="search" name="search" onkeydown="if (event.keyCode === 13)
                                        this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus  class="form-control input-xs clearable submit"/>
                                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Requisicion"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
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
        </div>
    </div>
</div>
</div>
	<!--FORMULARIO VISTA -->
	<div class="container">
		<div class="row">
			<div class="col align-self-center" id="documentoVistaD" style="display:none;">
				<form name="datosProf2" id="datosProf2" class="form-horizontal normal">
					<div class="row">
						<div class="col-sm-1"></div>
						<div class="col-sm-10">
							<div class="col align-self-center">
								<span align="right" id="numProfor" name="numProfor" style="font-size: 11px;" class="form-control input-xs databind datatitle"></span>
								<fieldset class="exa-fieldset">
								<legend class="Titulos2">Datos del Requisitor</legend>
									<table id="cabeceraTabla" style="width: 100%;border-collapse: collapse;" class="rep">
										<tr style="height: 0;">
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
										</tr>
										<tr>
											<td class='bold'>Identificaci&oacute;n:</td>
											<td colspan="1">
												<input type="text" id="Prs_Ced" name="Prs_Ced" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>Nombre:</td>
											<td colspan="1">
												<span id="Requisitor" name="Requisitor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>DIRECCION:</td>
											<td colspan="4">
												<span id="Prs_Dir" name="Prs_Dir" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>CORREO:</td>
											<td>
												<span id="Prs_Cor" name="Prs_Cor" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
									</table>
								</fieldset>
								<fieldset class="exa-fieldset">
								<legend class="Titulos2">Datos del Documento</legend>
									<table id="cabeceraTabla" style="width: 100%;border-collapse: collapse;" class="rep">
										<tr style="height: 0;">
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
											<td style="width: 10%;"></td>
											<td style="width: 40%;">&nbsp;</td>
										</tr>
										<tr>
											<td class='bold'>FECHA CREACI&Oacute;N:</td>
											<td colspan="1">
												<span id="Req_Fec_Cre" name="Req_Fec_Cre" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>FECHA ENT M&Aacute;X:</td>
											<td colspan="1">
												<span id="Req_Fec_Ent" name="Req_Fec_Ent" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>TIPO:</td>
											<td colspan="1">
												<span id="Req_Tip" name="Req_Tip" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>USUARIO:</td>
											<td>
												<span id="Usuario" name="Usuario" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>SOLICITADO A:</td>
											<td>
												<span id="Req_Per_Sol" name="Req_Per_Sol" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
										<tr>
											<td class='bold'>ACEPTA ENT PARCIALES:</td>
											<td>
												<span id="Req_Ent_Par" name="Req_Ent_Par" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
											<td class='bold'>COMO?:</td>
											<td>
												<span id="Req_Ent_Com" name="Req_Ent_Com" style="font-size: 12px;" class="form-control input-xs databind datatitle">
											</td>
										</tr>
									</table>
								</fieldset>
							</div>
						</div>
					</div>
					<br />
				</form>
				<div class="col-sm-12 table-responsive" style=" padding-bottom: 5px;">
					<table id="TablaRequisicionDetalle" class="table table-fixed"></table>
					<div id="itemsPager"></div>
				</div>
				<div class="center">
					<button type="button" class="btn btn-sm btn-inverse" onclick="$('#documentoVistaD').moveComp('#allProformas').updateGridsSizes();">
						<i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
				</div>
			</div>
		</div>
	</div>
	</div>
	<script src="../../VALIDACIONES/requisiciones/consultar.js?x=109"></script>
	<script type="text/javascript" src="/framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="/framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="/framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>