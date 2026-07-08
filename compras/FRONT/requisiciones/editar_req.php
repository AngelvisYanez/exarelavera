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
            #Req_Tip { width: 66.666666% !important; }
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
                <div class="row">
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
                                            <span id="Requisitor" name="Requisitor" class="form-control input-xs databind datatitle"></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label label-xs">Direcci&oacute;n:</label>
                                        <div class="col-md-4">
                                            <span id="Prs_Dir" name="Prs_Dir" type="text" class="form-control input-xs databind datatitle"></span>
                                        </div>
                                        <label class="col-md-1 control-label label-xs">Correo:</label>
                                        <div class="col-md-5">
                                            <span id="Prs_Cor" name="Prs_Cor" type="text" class="form-control input-xs databind datatitle"></span>
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
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-2 control-label label-xs required" style="text-align:center;">Usuario:</label>
                                        <div class="col-md-4">
                                            <input type="text" id="Usuario" name="Usuario" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
                                            value="" readonly></input>
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
                                    <i class="glyphicon glyphicon-floppy-disk"></i> Editar</button>
                                <!-- <button class="btn btn-sm btn-success" onclick="verVentanaRequisiciones();"><i class="glyphicon glyphicon-arrow-right"></i> Ver Requisiciones</button>-->
                                <button class="black btn btn-sm btn-danger" onclick="location.reload(); return false;"><i class="glyphicon glyphicon-remove"></i>Cancelar</button>
                            </div>
                        </form>
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
	<script src="../../VALIDACIONES/requisiciones/editar.js?x=109"></script>
	<script type="text/javascript" src="/framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="/Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
	<script type="text/javascript" src="/framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="/framework/plugins/moment.min.js"></script>
	</BODY>

</HTML>