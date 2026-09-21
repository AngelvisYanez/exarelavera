<?php
/**
 * @abstract Permite registrar las labores
 * @author Cesar Bermeo.
 * @version 1.0
 * Fecha de creación: 07-02-2019
 *
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../bananero/LOGICA/ban_log_labores.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Lab;

$hoy = date("Y-m-d");

/* Eliminar Labor */
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

/* Anular Sector */
if(isset($elimFinca)){
	$obBD_ins1 = new Class_Log_Datos_Lab;
    $obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try{
		$obBD_con1->operacionobBD('finca_actividad.setInactive', array('Fnc_Cod' => $Fnc_Cod), $obBD_conexion);
	}catch(Exception $e){
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
    $resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
    $obBD_con1->echoJson($resp);
}

/* Guardar segun tipo Formulario * */
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
            $Tpg_Cod = 0;
            if(isset($data['unidades']) && is_array($data['unidades'])){
                $guardados = array();
                foreach($data['unidades'] as $uniDes){
                    $uniDes = trim(strtoupper($uniDes));
                    if($uniDes !== ''){
                        $existente = $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array(
                            'tipo_pago_labor.Tpg_Des' => $uniDes,
                            'setWhere' => array('isActive')
                        ), $obBD_conexionIns);
                        if(empty($existente)){
                            $obBD_ins1->operacionobBD('tipo_pago_labor.insert', array(
                                'Tpg_Cod' => 0,
                                'Tpg_Des' => $uniDes,
                                'Suc_Cod' => $Ses_Suc_Cod
                            ), $obBD_conexionIns);
                            $nuevoId = $obBD_ins1->insercionid($obBD_conexionIns);
                            $guardados[] = array('Tpg_Cod' => $nuevoId, 'Tpg_Des' => $uniDes);
                            $Tpg_Cod = $nuevoId;
                        }
                    }
                }
                $resp['total_guardados'] = count($guardados);
                if(!empty($guardados)){
                    $ultimo = end($guardados);
                    $resp['tipoPago'] = $ultimo;
                }
            } else {
                $obBD_ins1->operacionobBD('tipo_pago_labor.insert', array('Tpg_Cod'=>$data['Tpg_Cod'], 'Tpg_Des'=>$data['Tpg_Des'], 'Suc_Cod'=>$Ses_Suc_Cod), $obBD_conexionIns );
                $Tpg_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                $resp['tipoPago'] = array('Tpg_Cod' => $Tpg_Cod, 'Tpg_Des' => $data['Tpg_Des']);
            }
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
            if(!isset($data['Fnc_Hec']) || $data['Fnc_Hec'] === '' || $data['Fnc_Hec'] === null){
                $data['Fnc_Hec'] = 0;
            }
            $fncLat = (isset($data['Fnc_Lat']) && $data['Fnc_Lat'] !== '') ? $data['Fnc_Lat'] : null;
            $fncLng = (isset($data['Fnc_Lng']) && $data['Fnc_Lng'] !== '') ? $data['Fnc_Lng'] : null;
            $fncGeo = (isset($data['Fnc_Geo_JSON']) && $data['Fnc_Geo_JSON'] !== '') ? $data['Fnc_Geo_JSON'] : null;

            $obBD_ins1->operacionobBD('finca_actividad.insert', array(
                'Fnc_Cod' => $data['Fnc_Cod'],
                'Fnc_Des' => $data['Fnc_Des'],
                'Fnc_Hec' => $data['Fnc_Hec'],
                'Fnc_Dir' => $data['Fnc_Dir'],
                'Fnc_Lat' => $fncLat,
                'Fnc_Lng' => $fncLng,
                'Fnc_Geo_JSON' => $fncGeo,
                'Suc_Cod' => $Ses_Suc_Cod
            ), $obBD_conexionIns );
            $Fnc_Cod = $obBD_ins1->insercionid($obBD_conexionIns);

            // Sincronizar cache JSON de sectores para modo offline
            $fileSecJson = dirname(__FILE__) . '/../../mapeo/DATA/sectores_relavera.json';
            if (file_exists(dirname($fileSecJson))) {
                $secActuales = array();
                if (file_exists($fileSecJson) && filesize($fileSecJson) > 0) {
                    $secActuales = json_decode(file_get_contents($fileSecJson), true);
                    if (!is_array($secActuales)) $secActuales = array();
                }
                $secActuales[] = array(
                    'id' => (int)$Fnc_Cod,
                    'nombre' => $data['Fnc_Des'],
                    'direccion' => $data['Fnc_Dir'],
                    'hectareas' => (float)$data['Fnc_Hec'],
                    'lat' => $fncLat !== null ? (float)$fncLat : null,
                    'lng' => $fncLng !== null ? (float)$fncLng : null,
                    'geometria' => (!empty($fncGeo)) ? json_decode($fncGeo, true) : null,
                    'suc_cod' => $Ses_Suc_Cod
                );
                @file_put_contents($fileSecJson, json_encode($secActuales));
            }
		}

        if(isset($updateFinca)){
            if(!isset($data['Fnc_Hec_Upd']) || $data['Fnc_Hec_Upd'] === '' || $data['Fnc_Hec_Upd'] === null){
                $data['Fnc_Hec_Upd'] = 0;
            }
            $fncLatUpd = (isset($data['Fnc_Lat_Upd']) && $data['Fnc_Lat_Upd'] !== '') ? $data['Fnc_Lat_Upd'] : null;
            $fncLngUpd = (isset($data['Fnc_Lng_Upd']) && $data['Fnc_Lng_Upd'] !== '') ? $data['Fnc_Lng_Upd'] : null;
            $fncGeoUpd = (isset($data['Fnc_Geo_JSON_Upd']) && $data['Fnc_Geo_JSON_Upd'] !== '') ? $data['Fnc_Geo_JSON_Upd'] : null;

            $obBD_ins1->operacionobBD('finca_actividad.update', array(
                'Fnc_Cod' => $data['Fnc_Cod_Upd'],
                'Fnc_Des' => $data['Fnc_Des_Upd'],
                'Fnc_Dir' => $data['Fnc_Dir_Upd'],
                'Fnc_Hec' => $data['Fnc_Hec_Upd'],
                'Fnc_Lat' => $fncLatUpd,
                'Fnc_Lng' => $fncLngUpd,
                'Fnc_Geo_JSON' => $fncGeoUpd
            ), $obBD_conexionIns);
            $Fnc_Cod = $data['Fnc_Cod_Upd'];

            // Sincronizar cache JSON de sectores para modo offline
            $fileSecJson = dirname(__FILE__) . '/../../mapeo/DATA/sectores_relavera.json';
            if (file_exists(dirname($fileSecJson))) {
                $secActuales = array();
                if (file_exists($fileSecJson) && filesize($fileSecJson) > 0) {
                    $secActuales = json_decode(file_get_contents($fileSecJson), true);
                    if (!is_array($secActuales)) $secActuales = array();
                }
                $encontrado = false;
                foreach ($secActuales as &$s) {
                    if (isset($s['id']) && (int)$s['id'] === (int)$Fnc_Cod) {
                        $s['nombre'] = $data['Fnc_Des_Upd'];
                        $s['direccion'] = $data['Fnc_Dir_Upd'];
                        $s['hectareas'] = (float)$data['Fnc_Hec_Upd'];
                        $s['lat'] = $fncLatUpd !== null ? (float)$fncLatUpd : null;
                        $s['lng'] = $fncLngUpd !== null ? (float)$fncLngUpd : null;
                        if (!empty($fncGeoUpd)) {
                            $s['geometria'] = json_decode($fncGeoUpd, true);
                        }
                        $encontrado = true;
                        break;
                    }
                }
                unset($s);
                if (!$encontrado) {
                    $secActuales[] = array(
                        'id' => (int)$Fnc_Cod,
                        'nombre' => $data['Fnc_Des_Upd'],
                        'direccion' => $data['Fnc_Dir_Upd'],
                        'hectareas' => (float)$data['Fnc_Hec_Upd'],
                        'lat' => $fncLatUpd !== null ? (float)$fncLatUpd : null,
                        'lng' => $fncLngUpd !== null ? (float)$fncLngUpd : null,
                        'geometria' => (!empty($fncGeoUpd)) ? json_decode($fncGeoUpd, true) : null,
                        'suc_cod' => $Ses_Suc_Cod
                    );
                }
                @file_put_contents($fileSecJson, json_encode($secActuales));
            }
        }


    }catch(Exception $e){ $obBD_ins1->rollBack_nomsn($obBD_conexionIns); $resp['message']=$e->getMessage(); $obBD_con1->echoJson($resp); }
    $resp['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
    if(!$resp['success']) $resp['error']=$obBD_ins1->MsgError;$resp['tipoPago']=$obBD_con1->getRowConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Cod'=>$Tpg_Cod, 'Suc_Cod'=>$Ses_Suc_Cod, 'setWhere'=>array('isActive')), $obBD_conexion);$resp['finca']=$obBD_con1->getRowConsulta('finca_actividad.selectWhere', array('finca_actividad.Fnc_Cod'=>$Fnc_Cod, 'setWhere'=>array('isActive')), $obBD_conexion );
    $obBD_con1->echoJson($resp);

}

/* Busca pagos de Labores apartir de una descripción */
if(isset($verificaDesc)){

    $resultPagosDesc = array(
        'success' => true,
        'tipPagoDesc' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('tipo_pago_labor.Tpg_Des'=>$Tpg_Des,'setWhere'=>array('isActive')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($resultPagosDesc);
}

/* Buscar pagos de Labores */
if(isset($buscarLaborPago)){

    $resultPagos = array(
        'success' => true,
        'tipPago' => $obBD_con1->getArrayConsulta('tipo_pago_labor.selectWhere', array('setWhere'=>array('isActive')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($resultPagos);
}

/* Buscar Labores existentes */
if(isset($laborAjax)){

    $laboresList = array(
        'success' => true,
        'listLab' => $obBD_con1->getArrayConsulta('labores.selectWhere', array('setWhere'=>array('isActive','orderByDes','byFormaPago', 'setEmpCod')), $obBD_conexion ),
    );
    $obBD_con1->echoJson($laboresList);
}

/* Busqueda de Fincas */
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
	<!-- <TITLE> <?php echo $Ses_Sys_Nom; ?> </TITLE> -->
    <title>Gestionar Labores y Sectores</title>
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<link rel="stylesheet" href="../RECURSOS/relavera_ui.css?v=9" />
</HEAD>
<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo;  Gestionar Actividades Relavera</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div class="row">
                <div class="col-sm-12">
                    <div id="tabsLabores" class="ui-tab-fix">
                        <ul>
                            <li>
                                <a href="#tabs-1">Labores - Sectores </a>
                            </li>
                        </ul>
                        <div class="panels-area form-horizontal normal ">
                            <div id="tabs-1">
                                <div class="row">
                                    <div class="col-xs-12">
                                        <div id="formDatosLabor" class="col-md-6 col-sm-12">
                                            <form class="form-horizontal normal" id="frmLabor" name="frmLabor" autocomplete="off" action="javascript:saveData('frmLabor','saveLabor','noEsDialog')">
                                                <fieldset class="exa-fieldset">
                                                    <legend class="Titulos2">Datos de la Labor</legend>
                                                    <input type="hidden" id="Lab_Cod" name="Lab_Cod">
                                                     <div class="form-group">
                                                         <label class="col-xs-3 control-label label-xs required">Descripci&oacute;n:</label>
                                                         <div class="col-xs-8">
                                                             <input id="Lab_Des" name="Lab_Des" class="form-control input-xs readOnly" onkeyup="javascript:this.value=this.value.toUpperCase();"></input>
                                                         </div>
                                                     </div>
                                                     <div class="form-group">
                                                         <label class="col-xs-3 control-label label-xs required">Modo Trabajo:</label>
                                                         <div class="col-xs-7  input-group input-group-xs ret" style="left:15px;text-align: center;">
                                                             <select id="Tpg_Cod" name="Tpg_Cod" class="form-control input-xs select_unidad" data-placeholder="Unidad Labor">
                                                             </select>
                                                             <span class="input-group-btn">
                                                                 <button id="agregarLabor" type="button" onclick="abrirModalUnidades();" class="btn btn-success btn-xs" title="Agregar Modos de Trabajo" tabindex="3">
                                                                     <span class="glyphicon glyphicon-plus-sign"></span>
                                                                 </button>
                                                             </span>
                                                         </div>
                                                     </div>
                                                     <div class="form-group">
                                                         <label class="col-xs-3 control-label label-xs required">Valor Labor:</label>
                                                         <div class="col-md-8 col-sm-4">
                                                             <input type="number" id="Lab_Val" name="Lab_Val" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
                                                             placeHolder="Precio Labor" min="0" step="0.0001" pattern="^\d+(?:\.\d{1,9})?$" />
                                                         </div>
                                                     </div>
                                                     <div class="col-md-12 col-sm-6" style="text-align: center;padding-top: 5px;">
                                                         <button type="button" id="btn_guardar_labor" name="btn_guardar_labor" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
                                                             <span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                                                     </div>
                                                 </fieldset>
									<legend class="Titulos2">Listado de Labores:</legend>
									<div class="exa-grid-container-card">
										<div class="exa-ui-grid-host">
											<table id="detaLabores"></table>
										</div>
									</div>
								</form>
							</div>
							<div id="formDatosFinca" class="col-md-6 col-sm-12">
								<form class="form-horizontal normal" id="frmFinca" name="frmFinca" autocomplete="off" action="javascript:saveData('frmFinca','saveFinca','noEsDialog')">
									<fieldset class="exa-fieldset">
										<legend class="Titulos2">Datos del Sector</legend>
										<input type="hidden" id="Fnc_Cod" name="Fnc_Cod">
										<div class="form-group">
											<label class="col-xs-3 control-label label-xs required">Ubicaci&oacute;n Mapa:</label>
											<div class="col-xs-8">
												<div class="input-group input-group-xs">
													<select id="sel_ubicacion_generada" class="form-control input-xs select_finca" onchange="seleccionarUbicacionGenerada(this, 'nuevo')" required>
														<option value="0">-- Seleccionar Ubicaci&oacute;n / Punto del Mapa --</option>
													</select>
													<span class="input-group-btn">
														<button type="button" class="btn btn-default btn-xs" onclick="recargarSelectUbicaciones()" title="Actualizar lista de ubicaciones">
															<span class="glyphicon glyphicon-refresh text-primary"></span>
														</button>
														<button type="button" class="btn btn-success btn-xs" onclick="abrirMapeoParaNuevaLocacion('nuevo')" title="Agregar nueva locaci&oacute;n en Mapeo Interactivo">
															<span class="glyphicon glyphicon-plus"></span>
														</button>
													</span>
												</div>
												<input type="hidden" id="Fnc_Lat" name="Fnc_Lat" value="" />
												<input type="hidden" id="Fnc_Lng" name="Fnc_Lng" value="" />
												<input type="hidden" id="Fnc_Geo_JSON" name="Fnc_Geo_JSON" value="" />
												<small id="Fnc_Coords_Badge" style="display:none; color:#16a34a; font-weight:600; font-size:11px; margin-top:2px;"></small>
											</div>
										</div>
										<div class="form-group">
											<label class="col-xs-3 control-label label-xs required">Nombre Sector:</label>
											<div class="col-xs-8">
												<input id="Fnc_Des" name="Fnc_Des" class="form-control input-xs readOnly" readonly="readonly" placeholder="Seleccione ubicacion o agregue en mapa (+)" onkeyup="javascript:this.value=this.value.toUpperCase();" required></input>
											</div>
										</div>
										<div class="form-group">
											<label class="col-xs-3 control-label label-xs required">Hect&aacute;reas:</label>
											<div class="col-md-8 col-sm-4">
												<input type="number" id="Fnc_Hec" name="Fnc_Hec" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;"
                                                placeholder="Numero de Hectareas" min="0" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" required />
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-3 control-label">Direcci&oacute;n</label>
											<div class="col-sm-8">
												<textarea class="form-control" id="Fnc_Dir" name="Fnc_Dir"></textarea>
											</div>
										</div>
										<div class="col-md-12 col-sm-6" style="text-align: center;padding-top: 5px;">
											<button type="button" id="btn_guardar_fink" name="btn_guardar_fink" class="btn btn-primary btn-sm" onclick="$(this.form).formSubmit();">
												<span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
										</div>
									</fieldset>
									<legend class="Titulos2">Listado de Sectores:</legend>
									<div class="exa-grid-container-card">
										<div class="exa-ui-grid-host">
											<table id="detaFincas"></table>
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

	<div id="unidadDialog" title="Registrar Modos de Trabajo">
		<div class="exa-modal-container">
			<form id="frm_add_unidad" name="frm_add_unidad" autocomplete="off" onsubmit="return false;">
				<div class="exa-modal-toolbar">
					<div class="toolbar-title">
						<i class="glyphicon glyphicon-th-list text-primary"></i> Ingreso M&uacute;ltiple de Modos de Trabajo
					</div>
					<div class="toolbar-actions">
						<span id="badge_total_unidades" class="exa-badge-count">1 modo</span>
						<button type="button" class="btn btn-success btn-xs" onclick="agregarFilaUnidad()" title="A&ntilde;adir otra fila para ingresar m&aacute;s modos a la vez">
							<span class="glyphicon glyphicon-plus"></span> Agregar Fila
						</button>
					</div>
				</div>

				<div class="exa-info-tip">
					<i class="glyphicon glyphicon-info-sign"></i>
					<span>Puede ingresar varios modos de trabajo a la vez (ej: HORA, D&Iacute;A, METRO, VIAJE, CAJA). El sistema verifica que no est&eacute;n duplicados antes de guardar.</span>
				</div>

				<div class="exa-multi-table-wrap">
					<table class="exa-multi-table" id="tabla_unidades_multiples">
						<thead>
							<tr>
								<th style="width: 36px; text-align: center;">#</th>
								<th>Descripci&oacute;n del Modo de Trabajo</th>
								<th style="width: 150px; text-align: center;">Estado</th>
								<th style="width: 44px; text-align: center;">Acci&oacute;n</th>
							</tr>
						</thead>
						<tbody id="tbody_unidades_multiples">
							<!-- Filas dinámicas administradas por JS -->
						</tbody>
					</table>
				</div>

				<div class="exa-modal-footer">
					<button type="button" id="btn_gua_unidades_multi" class="btn btn-primary btn-sm" onclick="guardarUnidadesMultiples()">
						<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Modos
					</button>
					<button type="button" class="btn btn-default btn-sm" onclick="$('#unidadDialog').dialog('close');">
						<span class="glyphicon glyphicon-remove"></span> Cancelar
					</button>
				</div>
			</form>
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

	<div id="fincaDialog" title="Actualizar Sector">
		<div class="row">
			<div class="col-md-12">
				<form id="frm_upd_finca" name="frm_upd_finca" class="form-horizontal normal" action="javascript:saveData('frm_upd_finca','updateFinca','finca')">
					<fieldset class="exa-fieldset">
						<legend class="Titulos2">Formulario de Actualizaci&oacute;n</legend>
						<input type="hidden" id="Fnc_Cod_Upd" name="Fnc_Cod_Upd" value="0" />
						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm">Ubicaci&oacute;n Mapa:</label>
							<div class="col-md-8 col-sm-7">
								<div class="input-group input-group-xs">
									<select id="sel_ubicacion_generada_upd" class="form-control input-xs select_finca" onchange="seleccionarUbicacionGenerada(this, 'editar')">
										<option value="0">-- Seleccionar Ubicaci&oacute;n / Punto del Mapa --</option>
									</select>
									<span class="input-group-btn">
										<button type="button" class="btn btn-default btn-xs" onclick="recargarSelectUbicaciones()" title="Actualizar lista de ubicaciones">
											<span class="glyphicon glyphicon-refresh text-primary"></span>
										</button>
										<button type="button" class="btn btn-success btn-xs" onclick="abrirMapeoParaNuevaLocacion('editar')" title="Agregar nueva locaci&oacute;n en Mapeo Interactivo">
											<span class="glyphicon glyphicon-plus"></span>
										</button>
									</span>
								</div>
								<input type="hidden" id="Fnc_Lat_Upd" name="Fnc_Lat_Upd" value="" />
								<input type="hidden" id="Fnc_Lng_Upd" name="Fnc_Lng_Upd" value="" />
								<input type="hidden" id="Fnc_Geo_JSON_Upd" name="Fnc_Geo_JSON_Upd" value="" />
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Nombre Sector:</label>
							<div class="col-md-8 col-sm-7">
								<input type="text" id="Fnc_Des_Upd" name="Fnc_Des_Upd" readonly="readonly" onkeyup="javascript:this.value=this.value.toUpperCase();" class="form-control input-xs trigger readOnly" required="" />
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Hect&aacute;reas:</label>
							<div class="col-md-8 col-sm-7">
								<input type="number" id="Fnc_Hec_Upd" name="Fnc_Hec_Upd" class="form-control input-xs trigger" tabindex="2" style="text-align:center; background-color:powderblue;" placeholder="Numero de Hectareas" min="0" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" required />
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-4 col-sm-5 label-sm required">Direcci&oacute;n:</label>
							<div class="col-md-8 col-sm-7">
								<textarea class="form-control input-xs" id="Fnc_Dir_Upd" name="Fnc_Dir_Upd" required></textarea>
							</div>
						</div>
					</fieldset>
					<div style="text-align: center;">
						<button type="submit" id="btn_gua_finca" name="btn_gua_finca" class="btn btn-primary btn-sm">
							<span class="glyphicon glyphicon-floppy-disk"></span> Editar</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Modal Popup para Visualizar / Vectorizar en Mapa Satelital HD -->
	<div id="dialogMapeoSector" title="Mapeo Interactivo Georreferenciado - Relavera El Tabl&oacute;n" style="display:none; padding:0; overflow:hidden;">
		<div style="background:#0f172a; color:#fff; padding:6px 12px; font-size:12px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #334155;">
			<span id="dialogMapeoIndicacion">
				<i class="glyphicon glyphicon-info-sign text-info"></i> Haga clic en cualquier punto del mapa para capturar coordenadas o en un sector para seleccionarlo.
			</span>
			<div class="btn-group btn-group-xs">
				<button type="button" class="btn btn-default btn-xs" onclick="recargarIframeMapeo()" title="Recargar mapa">
					<span class="glyphicon glyphicon-refresh"></span>
				</button>
				<button type="button" class="btn btn-warning btn-xs" onclick="cerrarModalMapeoSector()" title="Cerrar modal">
					<span class="glyphicon glyphicon-remove"></span> Cerrar
				</button>
			</div>
		</div>
		<iframe id="iframeMapeoSector" src="about:blank" style="width:100%; height:490px; border:none; display:block;"></iframe>
	</div>

        <script type="text/javascript" charset="utf-8" src="../VALIDACIONES/ban_val_labores_relavera.js?k=4"></script>
        <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
		
		<!-- Cierra y libera las conexiones a la base de datos -->
		<?php
			$obBD_con1->liberar();
			$obBD_conexion->cerrar();
		?>
	</BODY>

</HTML>