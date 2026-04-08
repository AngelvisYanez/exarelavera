<?php
/**
 * @abstract Permite registrar las labores
 * @author Cesar Bermeo.
 * @version 1.0
 * Fecha de creación: 07-02-2019
 *
 */
 require_once('../../administrador/LOGICA/seguridad.php');
 require_once('../LOGICA/ban_log_labores.php');
 require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Lab;

$hoy = date("Y-m-d");

/**
 * Eliminar Labor
 */
if(isset($elimLabor)){
	$obBD_ins1 = new Class_Log_Datos_Lab;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try{
		$obBD_con1->operacionobBD('labores.setInactive',array('Lab_Cod'=>$Lab_Cod),$obBD_conexion);

	}catch(Exception $e){$obBD_ins1->rollBack_nomsn($obBD_conexionIns);$resp['message'] = $e->getMessage();$obBD_con1->echoJson($resp); }
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);

}

/**
 * Guardar segun tipo Formulario
 *
 */
if(isset($save)){
    $obBD_con1->echoLog('** PHP SAVE **');
    $resp=array('success'=>false);
    $obBD_ins1 =  new Class_Log_Datos_Lab;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
    try{
        $data = $_POST;
        $obBD_con1->echoLog($data);
        if(isset($saveUnidad)){

            $obBD_ins1->operacionobBD('tipo_pago_labor.insert', array('Tpg_Cod'=>$data['Tpg_Cod'], 'Tpg_Des'=>$data['Tpg_Des'], 'Suc_Cod'=>$Ses_Suc_Cod), $obBD_conexionIns );
            $Tpg_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
             //$obBD_con1->echoLog($Tpg_Cod);
        }
        if(isset($saveLabor)){

            $obBD_ins1->operacionobBD('labores.insert', array('Lab_Cod'=>$data['Lab_Cod'], 'Lab_Des'=>$data['Lab_Des'], 'Lab_Val'=>$data['Lab_Val'], 'Tpg_Cod'=>$data['Tpg_Cod'], 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexionIns );
            $Lab_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            $obBD_con1->echoLog($Lab_Cod);
        }

        if(isset($updateLabor)){
           	$obBD_ins1->operacionobBD('labores.update', array('Lab_Cod'=>$data['Lab_Cod_Upd'], 'Lab_Des'=>$data['Lab_Des_Upd'], 'Lab_Val'=>$data['Lab_Val_Upd'], 'Tpg_Cod'=>$data['Tpg_Cod']), $obBD_conexionIns );
        }

        if(isset($saveFinca)){

            $obBD_ins1->operacionobBD('finca_actividad.insert', array('Fnc_Cod'=>$data['Fnc_Cod'], 'Fnc_Des'=>$data['Fnc_Des'], 'Fnc_Hec'=>$data['Fnc_Hec'], 'Fnc_Dir'=>$data['Fnc_Dir'], 'Suc_Cod'=>$Ses_Suc_Cod), $obBD_conexionIns );
            $Fnc_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
             //$obBD_con1->echoLog($Fnc_Cod);
		}


    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;$resp['tipoPago']=$obBD_con1->getRowConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Cod'=>$Tpg_Cod, 'Suc_Cod'=>$Ses_Suc_Cod, 'setWhere'=>array('isActive')), $obBD_conexion);$resp['finca']=$obBD_con1->getRowConsulta('finca_actividad.selectWhere', array('finca_actividad.Fnc_Cod'=>$Fnc_Cod, 'setWhere'=>array('isActive')), $obBD_conexion );
    $obBD_con1->echoJson($resp);

}

/**
 * Busca pagos de Labores apartir de una descripción
 */
if(isset($verificaDesc)){

    $resultPagosDesc = array(
        'success' => true,
        'tipPagoDesc' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Des'=>$Tpg_Des,'setWhere'=>array('isActive')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($resultPagosDesc);
}

/**
 * Buscar pagos de Labores
 */
if(isset($buscarLaborPago)){

    $resultPagos = array(
        'success' => true,
        'tipPago' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('setWhere'=>array('isActive')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($resultPagos);
}
/**
 * Buscar Labores existentes
 */
if(isset($laborAjax)){

    $laboresList = array(
        'success' => true,
        'listLab' => $obBD_con1->getArrayConsulta('labores.selectWhere', array('setWhere'=>array('isActive','orderByDes','byFormaPago', 'setEmpCod')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($laboresList);
}
/**
 * Busqueda de Fincas
 */
if(isset($fincasAjax)){

    $fincasList = array(
        'success'=>true,
        'listaFincas'=>$obBD_con1->getArrayConsulta('finca_actividad.selectWhere', array('setWhere'=>array('setSucCod','orderByDes','isActive')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($fincasList);
}

?>

<!DOCTYPE html>
<HTML>

<HEAD>
	<TITLE>
		<?Php echo $Ses_Sys_Nom; ?>
	</TITLE>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
		<script> </script>
        <style>
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                /* display: none; <- Crashes Chrome on hover */
                -webkit-appearance: none;
                margin: 0; /* <-- Apparently some margin are still there even though it's hidden */
            }
        </style>
   </HEAD>
   <BODY>
       <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gestionar Actividades Bananeras</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">

	<div class="col-sm-12">
		<div id="tabsLabores" class="ui-tab-fix">
			<ul>
				<li>
					<a href="#tabs-1">Labores - Fincas </a>
				</li>
			</ul>

			<div class="panels-area form-horizontal normal ">
				<div id="tabs-1">
					<div class="row">
						<div class="col-xs-12">
							<div id="formDatosLabor" class="col-md-4 col-sm-4 col-md-offset-1">
								<form class="form-horizontal normal" id="frmLabor" name="frmLabor" autocomplete="off" action="javascript:saveData('frmLabor','saveLabor','noEsDialog')">
									<fieldset class="exa-fieldset">
										<legend class="Titulos2">Datos de la Labor</legend>
										<input type="hidden" id="Lab_Cod" name="Lab_Cod">
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs required">Descripci&oacute;n:</label>
											<div class="col-xs-8">
												<input id="Lab_Des" name="Lab_Des" class="form-control input-xs readOnly" onkeyup="javascript:this.value=this.value.toUpperCase();"></input>
											</div>
										</div>
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs required">Modo Trabajo:</label>
											<div class="col-xs-7  input-group input-group-xs ret" style="left:15px;text-align: center;">
												<select id="Tpg_Cod" name="Tpg_Cod" class="form-control input-xs select_unidad" data-placeholder="Unidad Labor">
												</select>
												<span class="input-group-btn">
													<button id="agregarLabor" type="button" onclick="$('#unidadDialog').dialog('open');" class="btn btn-success btn-xs" title="Agregar Unidad"
													 tabindex="3">
														<span class="glyphicon glyphicon-plus-sign"></span>
													</button>
												</span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs required">Valor Labor:</label>
											<div class="col-md-8 col-sm-4">
												<input type="number" id="Lab_Val" name="Lab_Val" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
												 placeHolder="Precio Labor" min="0" step="0.0001" pattern="^\d+(?:\.\d{1,9})?$" />
											</div>
										</div>
										<div class="col-md-12 col-sm-6" style="text-align: center;padding-top: 5px;">
											<button type="button" id="btn_guardar_labor" name="btn_guardar_labor" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
										</div>
									</fieldset>
									<legend class="Titulos2">Listado de Labores:</legend>
									<div class="form-horizontal normal" style="padding: 0 4px;">
										<div class="form-group condensed">
											<div class="col-xs-12 col-md-offset-1">
												<div class="pull-right">
													<table id="detaLabores"></table>
												</div>
											</div>
										</div>
									</div>
								</form>
							</div>
							<div id="formDatosFinca" class="col-md-4 col-md-offset-2">
								<form class="form-horizontal normal" id="frmFinca" name="frmFinca" autocomplete="off" action="javascript:saveData('frmFinca','saveFinca','noEsDialog')">
									<fieldset class="exa-fieldset">
										<legend class="Titulos2">Datos de la Finca</legend>
										<input type="hidden" id="Fnc_Cod" name="Fnc_Cod">
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs required">Nombre Finca:</label>
											<div class="col-xs-8">
												<input id="Fnc_Des" name="Fnc_Des" class="form-control input-xs readOnly" onkeyup="javascript:this.value=this.value.toUpperCase();"></input>
											</div>
										</div>
										<div class="form-group">
											<label class="col-xs-4 control-label label-xs required">Hect&aacute;reas:</label>
											<div class="col-md-8 col-sm-4">
												<input type="number" id="Fnc_Hec" name="Fnc_Hec" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
												 placeHolder="N&uacute;mero de Hect&aacute;reas" min="0" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" />
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label required">Direcci&oacute;n</label>
											<div class="col-sm-8">
												<textarea class="form-control" id="Fnc_Dir" name="Fnc_Dir" required></textarea>
											</div>
										</div>
										<div class="col-md-12 col-sm-6" style="text-align: center;padding-top: 5px;">
											<button type="button" id="btn_guardar_fink" name="btn_guardar_fink" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</butto>
										</div>
									</fieldset>
									<legend class="Titulos2">Listado de Fincas:</legend>
									<div class="form-horizontal normal" style="padding: 0 4px;">
										<div class="form-group condensed">
											<div class="col-xs-12 col-md-offset-1">
												<div class="pull-right">
													<table id="detaFincas"></table>
												</div>
											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
	</div>
	</div>

	<div id="unidadDialog" title="Registrar Unidad">
		<div class="row">
			<div class="col-md-12">
				<form id="frm_add_unidad" name="frm_add_unidad" class="form-horizontal normal" action="javascript:saveData('frm_add_unidad','saveUnidad','unidad')">
					<input type="hidden" id="Tpg_Cod" name="Tpg_Cod">
					<fieldset class="exa-fieldset">
						<legend class="Titulos2">Formulario de Registro</legend>
						<div class="form-group">
							<label class="control-label col-md-3 col-sm-4 label-sm required">Descripci&oacute;n:</label>
							<div class="col-md-8 col-sm-2">
								<div class="input-group input-group-xs">
									<input type="text" id="Tpg_Des" name="Tpg_Des" onchange="validarUnidad()" onkeyup="javascript:this.value=this.value.toUpperCase();"
									 class="form-control input-xs trigger" required="" data-container="body" data-toggle="popover" />
									<span class="input-group-addon validate">
										<i></i>
									</span>
									<div>
									</div>
								</div>
					</fieldset>
					<div style="text-align: center;">
						<button type="submit" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm">
							<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div id="laborDialog" title="Actualizar Labor">
		<div class="row">
			<div class="col-md-12">
				<form id="frm_upd_labor" name="frm_upd_labor" class="form-horizontal normal" action="javascript:saveData('frm_upd_labor','updateLabor','labor')">
					<fieldset class="exa-fieldset">
						<legend class="Titulos2">Formulario de Actualizaci&oacute;n</legend>

						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Descripci&oacute;n:</label>
							<div class="col-md-8 col-sm-7">
								<div class="input-group input-group-xs">
									<input type="hidden" id="Lab_Cod_Upd" name="Lab_Cod_Upd" vlaue='0' />
									<input type="text" id="Lab_Des_Upd" name="Lab_Des_Upd" onkeyup="javascript:this.value=this.value.toUpperCase();" class="form-control input-xs trigger" required="" data-container="body" data-toggle="popover" />
								</div>
							</div>
						</div>

						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Modo Trabajo:</label>
							<div class="col-md-8 col-sm-7">
								<div class="input-group input-group-xs">
									<select id="Tpg_Cod_Id" name="Tpg_Cod" class="form-control input-xs select_unidad" data-placeholder="Unidad Labor"></select>
								</div>
							</div>
						</div>

						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Valor Labor:</label>
							<div class="col-md-8 col-sm-7">
								<div class="input-group input-group-xs">
									<input type="number" id="Lab_Val_Upd" name="Lab_Val_Upd" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;" placeHolder="Precio Labor" min="0" step="0.0001" pattern="^\d+(?:\.\d{1,9})?$" />
								</div>
							</div>
						</div>

					</fieldset>
					<div style="text-align: center;">
						<button type="submit" id="btn_gua" name="btn_gua" class="btn btn-primary btn-sm">
							<span class="glyphicon glyphicon-floppy-disk"></span> Editar</button>
					</div>
				</form>
			</div>
		</div>
	</div>

			<script src="../VALIDACIONES/ban_val_labores.js?k=5621"></script>
			<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
			<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
			<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
			<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
			</BODY>

</HTML>