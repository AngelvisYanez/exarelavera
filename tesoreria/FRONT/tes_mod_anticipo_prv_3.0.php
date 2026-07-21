<?php

/**
 * @abstract Permite Modificar/Consultas anticipos
 * @author EXA Contable
 * @version 3.0
 * @Fecha de creacion: 17/01/2026
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_prv_3.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new  Class_Log_Datos_Ant_Prv;


 ini_set("memory_limit", "1024M");
// ini_set('max_execution_time', 9300);

ini_set('max_execution_time', 600);

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);


$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
if ($configs["Cof_NegCam"] == 'S') { //Cargar si esta activada la negociacion de camarón
	if (isset($negociacionesAjax)) {
		$data_negociaciones = $obBD_con1->getArrayConsulta('nego_camaron.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Est_Neg' => array('A', 'P'))), $obBD_conexion, true);
		$obBD_con1->echoJson($data_negociaciones);
	}
}



$hoy = date("Y-m-d");
/* Tipo Asiento */
$rows_tipo_asiento = $obBD_con1->getArrayConsulta('tipo_asien.selectWhere', array('where' => array('Tia_Abr' => 'EG'), 'setWhere' => array('isActive'), 'order' => 'tipo_asien.Tia_Abr'), $obBD_conexion);

/**************** CODIGO JOSE ********************/
/* Tipos de pago */
$tPagos = $obBD_con1->getArrayConsulta('tipos_pago.selectWhere', array('where' => array("Pag_Abr='EFE' OR Pag_Abr='CHE' OR Pag_Abr='TRF' OR Pag_Abr='DEP'")), $obBD_conexion);
$consumoPago= $obBD_con1->getArrayConsulta('tipos_pago.selectWhere', array('where' => array("Pag_Abr='EFE' OR Pag_Abr='CHE' OR Pag_Abr='TRF' OR Pag_Abr='DEP' OR Pag_Abr='OTR'")), $obBD_conexion);
$consumoBanco= $obBD_con1->getArrayConsulta('banco.selectWhere', array('setWhere'=>array('setEmpCod','byDetPlan','byPlan'),'where' => array("Ban_Tip in('B','C')")), $obBD_conexion,true);
//para obtener planes de cuenta para agregar aportaciones
if (isset($cuentasAjax)) {
	$obBD_con1->getPageGridJson('det_plan.12', $_GET, $obBD_conexion);
}
if (isset($anticiposCruceAjax)) {	
	$data = $_GET;	
	$resultado['rows'] = $obBD_con1->getArrayConsulta('anticipos_proveedores.2', $_GET, $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}
/* Verificar el numero de cheque externo */
if (isset($verificaChequeExt)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'numCheque' => $obBD_con1->getArrayConsulta('cheques_ext.selectWhere', array('where' => array('Bak_Cod' => $Bak_Cod,'Che_Cta'=>$PapCtd , 'Che_Num' => $Che_Num)), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}
if(isset($saveConsumoAjax)){
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$resp = array('success' => false);
	/*Cuentas para generar la contabilidad*/    	
    $ctas=$obBD_con1->getArrayConsulta('plan_param.selectWhere',array("Tpa_Abr in('CD','ANP','ANC','CCH','CA','CEF')",'Emp_Cod'=>$Ses_Emp_Cod), $obBD_conexion,true); 
	

	if($tipo=='EFE'){
		$Com_Con='CONSUMO EEFECTIVO';
		$ctaPago=$Pld_Cod_banco;
	}
	if($tipo=='CHE'){
		$Com_Con='CONSUMO CHEQUE No: '.$CheNum;
		$cta_Pago=reset(array_filter($ctas,function($e){if($e['Tpa_Abr']=='CCH')return $e;}));
		$ctaPago=$cta_Pago['Pld_Cod'];		
	}
	if($tipo=='TRF'){
		$Com_Con='CONSUMO TRANSF.';
		$ctaPago=$Pld_Cod_banco;
	}
	if($tipo=='OTR'){
		$Com_Con='CONSUMO OTROS';
		$ctaPago=$Pld_Cod_Otr;
	}		
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {	
		$Pec_Cod = $obBD_ins1->getRowConsulta('perio_cont.selectWhere', array('Date' =>  $Com_Fec, 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		if(empty($Cli_Cod_Pagos)){
			$cliente = $obBD_con1->getRowConsultaSql("SELECT Prs_Cod,Cli_Tic,Cli_Con,Cli_Cor,Cli_Dir,Cli_Tip FROM cliente WHERE Prs_Cod=".$Prs_Cod_Pagos." limit 1",$obBD_conexion,true);			
			if(!empty($cliente)){
				$clie=array_merge($cliente,array('Emp_Cod'=>$Ses_Emp_Cod));
				$obBD_ins1->operacionobBD('cliente.insert',$clie, $obBD_conexion);
				$Cli_Cod_Pagos = $obBD_ins1->insercionid($obBD_conexion);		
			}else{
				$clie=array('Emp_Cod'=>$Ses_Emp_Cod,'Prs_Cod'=>$Prs_Cod_Pagos);
				$obBD_ins1->operacionobBD('cliente.insert',$clie, $obBD_conexion);
				$Cli_Cod_Pagos = $obBD_ins1->insercionid($obBD_conexion);
			}
		}
		/* Consultamos el parametros de ANTICIPO PROVEEDORES */
		$Pld_Cod_Prv= $obBD_ins1->getRowConsulta('plan_param.selectWhere',array('setWhere'=>array('setEmpCod'),'where'=>array('Tpa_Abr'=>'ANP','Pld_Est'=>'A')),$obBD_conexion);
		
		if(empty($Com_Cod)){
			//insertamos un comprobante		
			$Com_Num = $obBD_ins1->getComNumPecAuto(17, $Pec_Cod['Pec_Cod'], $Com_Fec, $obBD_conexion);	
			$obBD_ins1->operacionobBD('comprobantes.insert', array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Cli_Cod' => $Cli_Cod_Pagos, 'Com_num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Usu_Cod'=>$Ses_Usu_Cod, 'Com_Con' => $Com_Con, 'Com_Val' => $PapVal, 'Tia_Cod' => 17), $obBD_conexionIns, true);
			$Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
		}else{
			$arr_com=array('Cli_Cod' => $Cli_Cod_Pagos, 'Com_num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $PapVal);
			if(date('m',strtotime($Com_Fec))!==date('m',strtotime($Com_Fec)))	
				$arr_com['Com_Num'] = $obBD_ins1->getComNumPecAuto(17, $Pec_Cod['Pec_Cod'], $Com_Fec, $obBD_conexion);		
			$arr_com['where']=array('Com_Cod'=>$Com_Cod); 
			//var_dump($arr_com);
			$obBD_ins1->operacionobBD('comprobantes.update',$arr_com , $obBD_conexionIns, true);
			$obBD_ins1->operacionobBD('asientos.deleteWhere', array('Com_Cod'=> $Com_Cod), $obBD_conexionIns, true);
			$obBD_ins1->operacionobBD('det_ant_ccpp.deleteWhere', array('Com_Cod'=> $Com_Cod), $obBD_conexionIns, true);
		}

		// insertamos un asitento
		$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'H', 'Asi_Glo' => "Anticipo Proveedores",'Asi_Con' => "Anticipo Proveedores", 'Asi_Val' => $PapVal, 'Pld_Cod' => $Pld_Cod_Prv['Pld_Cod']), $obBD_conexionIns, true);		
		$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Glo' => $Com_Con, 'Asi_Val' => $PapVal, 'Pld_Cod' => $ctaPago), $obBD_conexionIns, true);
		$Asi_Cod_Debe = $obBD_ins1->insercionid($obBD_conexionIns);

		if($tipo=='CHE'){
			//Ingresamos cheque			
			$obBD_ins1->operacionobBD('cheques_ext.insert', array('Cli_Cod' => $Cli_Cod_Pagos,'Che_Fec'=>$CheFec, 'Bak_Cod' => $BakCod, 'Asi_Cod' => $Asi_Cod_Debe,'Che_Num'=>$CheNum,'Che_Val'=> $PapVal, 'Che_Est' => 'A'), $obBD_conexionIns, true);
		}
		if($tipo=='EFE'){
			//$arr=array('Pap_Cto'=>'');
		}
		if($tipo=='TRF'){
			$arr=array('Pap_Cto'=>$Pap_Cto,'Pap_Ctd'=>$Pap_Ctd);
		}
		if($tipo=='OTR'){
			//$arr=array('Pap_Cto'=>'');
		}
		$arr=array();
		foreach($anticipo as $ant){			
			//Creamos el consumo		
			$arr=array_merge($arr,array('Atp_Cod' => $ant['Atp_Cod'], 'Asi_Cod' => $Asi_Cod_Debe, 'Pag_Cod' => $PagCod, 'Pap_Cto'=>$Pap_Cto, 'Pap_Ctd'=>$PapCtd, 'Pap_Est' => 'A','Pap_Obs'=>$PapObs,'Pap_Val'=>$ant['Acl_Cru'],'Pap_Es2'=>'M'));
			$obBD_ins1->operacionobBD('pago_anticipo_proveedores.insert', $arr, $obBD_conexionIns, true);
			$Pap_Cod= $obBD_ins1->insercionid($obBD_conexionIns);

			//creamos un registro en det_ant_ccpp
			$obBD_ins1->operacionobBD('det_ant_ccpp.insert', array('Atp_Cod' => $ant['Atp_Cod'], 'Pap_Cod'=>$Pap_Cod, 'Dac_Val' => $ant['Acl_Cru'], 'Com_Cod' => $Com_Cod), $obBD_conexionIns, true);
		}
		$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=".$Com_Cod;

	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/* Select cheques por consumo*/
if (isset($getDetalleConsumo)) {	
	$resp=array();
	$resp['det']= $obBD_con1->getArrayConsulta('anticipos_proveedores.3', array('Prv_Cod'=>$Prv_Cod,'Com_Cod'=>$Com_Cod), $obBD_conexion);	
	$resp['che']= $obBD_con1->getRowConsulta('cheques_ext.selectWhere', array('where' => array('cheques_ext.Asi_Cod' => $Asi_Cod)), $obBD_conexion);		
	$obBD_con1->echoJson($resp);
}
if(isset($bajaConsumoAjax)){
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$response['success'] = false; 
    try {
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);	
		//$obBD_con1->getRowConsulta('det_ant_ccpp.2',array('Com_Cod'=>$Com_Cod), $obBD_conexion);		
		$obBD_con1->getRowConsultaSql('delete pago_anticipo_proveedores, det_ant_ccpp from pago_anticipo_proveedores inner join det_ant_ccpp ON pago_anticipo_proveedores.Pap_Cod = det_ant_ccpp.Pap_Cod where det_ant_ccpp.Com_Cod ='.$Com_Cod, $obBD_conexion);		
		$obBD_ins1->operacionobBD('comprobantes.update',array('Com_Est'=>'I','where'=>array('Com_Cod'=>$Com_Cod)), $obBD_conexionIns);
	$response['success']=$obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	echo $obBD_ins1->MsgError;
    } catch (Exception $e) {
        $obBD_ins1->rollBack_nomsn($obBD_conexionIns);
        $response['success']=false; $response['error']=$obBD_ins1->MsgError;
    }
    $obBD_ins1->echoJson($response);    
}
/** FIN CODIGO JOSE CUMBICOS **/

/* Tipos de pago */
$tPagos = $obBD_con1->getArrayConsulta('tipos_pago.selectWhere', array('where' => array("Pag_Abr='EFE' OR Pag_Abr='CHE' OR Pag_Abr='TRF' OR Pag_Abr='DEP'")), $obBD_conexion);
/* Perfiles */
$perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod), 'setWhere' => array('getPerfil')), $obBD_conexion);
utf8_encode_deep($perfil);
/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);





/* Busuqeda de Grid */
if (isset($anticiposAjax)) {
	$obBD_con1->debugLogs(false);
	$data = $_GET;
	// Determinar el filtro de estado según el valor de 'letra'
	$estadoFiltro = isset($data['letra']) ? trim($data['letra']) : 'AU'; // Por defecto: Por Consumir (A y U)
	// Determinar si se usa filtro de fecha corte
	$usarFechaCorte = isset($data['Pec_Cod']) && $data['Pec_Cod'] === 'Corte';
	// CORREGIDO: Cambiar el orden de ejecución para evitar duplicación de filas
	// El problema es que 'pagos' hace GROUP BY antes de que 'getDetAntCCPP' haga su JOIN
	// Esto causa que cuando 'getDetAntCCPP' hace JOIN, duplica las filas ya agrupadas
	// SOLUCIÓN: Ejecutar 'getDetAntCCPP' ANTES de 'pagos' para que el GROUP BY maneje correctamente la duplicación
	// Esta línea define un array llamado $setWhere con los nombres de filtros/procesos que se aplicarán a la consulta sobre anticipos_proveedores,
	// dependiendo de si se usa o no el filtro de corte por fecha.
	// El primer elemento del arreglo será 'getDetAntCCPP1' si $usarFechaCorte es true, de lo contrario será 'getDetAntCCPP'.
	// Los siguientes elementos son siempre: 'pagoAnticipo', 'getUsuario', 'pagos' y 'tipoPago'.
	$setWhere = array($usarFechaCorte ? 'getDetAntCCPP1' : 'getDetAntCCPP', 'pagoAnticipo', 'getUsuario', 'pagos', 'tipoPago');
	// Agregar el filtro de estado según el valor
	switch($estadoFiltro) {
		case 'AUC': // Todos - A, U y C
			$setWhere[] = 'filterByEstado';
			$data['Atp_Est_Filter'] = 'AUC';
			break;
		case 'AU': // Por Consumir - A y U
		default:
			$setWhere[] = 'filterByEstado';
			$data['Atp_Est_Filter'] = 'AU';
			break;
		case 'C': // Consumidos - Solo C
			$setWhere[] = 'filterByEstado';
			$data['Atp_Est_Filter'] = 'C';
			break;
		case 'I': // Anulados - Solo I
			$setWhere[] = 'filterByEstado';
			$data['Atp_Est_Filter'] = 'I';
			break;
	}
	
	// Esta línea toma todos los parámetros recibidos por GET ($_GET) y les agrega el array 'setWhere',
	// combinando ambos en un solo array asociativo llamado $datos, que será utilizado como datos de entrada
	// para la función que consulta la base de datos (en este caso, define filtros específicos para la consulta).
	$datos = array_merge($_GET, array('setWhere' => $setWhere));
	$datos['Atp_Est_Filter'] = $data['Atp_Est_Filter'];
	// Pasar Pec_Cod y fechas para el filtro de corte si aplica
	if ($usarFechaCorte) {
		$datos['Pec_Cod'] = 'Corte';
		if (isset($data['txt_fec_ini'])) {
			$datos['txt_fec_ini'] = $data['txt_fec_ini'];
		}
		if (isset($data['txt_fec_fin'])) {
			$datos['txt_fec_fin'] = $data['txt_fec_fin'];
		}
	}
	$resultado = $obBD_con1->getPageGrid('anticipos_proveedores.selectWhere', $datos, $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}







//Seccion para obtener los proveedores registrados en la empresa
if (isset($proveedoresAjax)) {
  	$obBD_con1->getPageGridJson('anticipos_proveedores.1',$_GET, $obBD_conexion);
}

/* SubGrid */
if (isset($movAnticipo)) {
	$obBD_con1->debugLogs(false);
	$pecCodSeleccionado = isset($_GET['Pec_Cod']) ? $_GET['Pec_Cod'] : null;
	$data = array_merge($_GET, array('setWhere' => array(/*'pagos','pagoAnticipo', */'subGrid')));
	$respuesta =  $obBD_con1->getPageGrid('det_ant_ccpp.selectWhere', $data, $obBD_conexion, true);
	$respuesta['Pec_Cod_seleccionado'] = $pecCodSeleccionado;
	$obBD_con1->echoJson($respuesta);
}
/* Get asientos del subgrid */
if (isset($getSubGridAsient)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'dataSubAsiento' => $obBD_con1->getArrayConsulta('asientos.selectWhere', array('where' => array('asientos.Com_Cod' => $Com_Cod), 'setWhere' => array('ByDetPlan')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}






/* Get Asientos */
/*if (isset($getAsientos)) {
	//$obBD_con1->debugLogs(false);
	// CORREGIDO: Usar asientos.selectWhere directamente para evitar duplicación
	// El problema es que anticipos_proveedores.selectWhere con pagoAnticipo hace JOIN
	// que duplica las filas cuando hay múltiples registros en pago_anticipo_proveedores
	// SOLUCIÓN: Obtener los asientos directamente sin pasar por anticipos_proveedores
	$resultado = array(
		'success' => true,
		'dataASiento' => $obBD_con1->getArrayConsulta('asientos.selectWhere', array('where' => array('asientos.Com_Cod' => $Com_Cod), 'setWhere' => array('ByDetPlan')), $obBD_conexion, true),
	);
	
	// Obtener información del anticipo para combinar con los asientos
	$anticipoInfo = $obBD_con1->getRowConsulta('anticipos_proveedores.selectWhere', 
		array('where' => array('anticipos_proveedores.Com_Cod' => $Com_Cod), 
		'setWhere' => array('pagoAnticipo2')), $obBD_conexion, true);
	
	// OPTIMIZACIÓN: Cargar todos los tipos de pago de una vez para todos los asientos
	$asiCodList = array();
	if (!empty($anticipoInfo['Atp_Cod'])) {
		foreach ($resultado['dataASiento'] as $resp) {
			if (!empty($resp['Asi_Cod'])) {
				$asiCodList[] = intval($resp['Asi_Cod']);
			}
		}
		
		if (!empty($asiCodList)) {
			$asiCodList = array_unique($asiCodList);
			$asiCodStr = implode(',', $asiCodList);
			$atpCod = intval($anticipoInfo['Atp_Cod']);
			
			// Consulta SQL directa optimizada para cargar todos los datos de una vez
			$sqlTipoPagos = "SELECT
				pap.Pap_Est, pap.Pap_Cto, pap.Pap_img, pap.Pag_Cod, pap.Asi_Cod, pap.Atp_Cod,
				tpsPg.Pag_Abr, tpsPg.Pag_Des,tpsPg.*,
				chq.Che_Cod, chq.Che_Fec, chq.Che_Num, chq.Ban_Cod, chq.Che_Est
			FROM pago_anticipo_proveedores AS pap
			LEFT JOIN tipos_pago AS tpsPg ON tpsPg.Pag_Cod = pap.Pag_Cod
			LEFT JOIN cheques AS chq ON chq.Asi_Cod = pap.Asi_Cod
			WHERE pap.Asi_Cod IN ($asiCodStr) AND pap.Atp_Cod = $atpCod";
			
			ChromePhp::log('sqlTipoPagos', $sqlTipoPagos);

			$tipoPagos = $obBD_con1->getArrayConsultaSql($sqlTipoPagos, $obBD_conexion, true);
			
			// Crear índice por Asi_Cod para acceso rápido
			$tipoPagosIndex = array();
			foreach ($tipoPagos as $tp) {
				$asiCod = $tp['Asi_Cod'];
				if (!isset($tipoPagosIndex[$asiCod])) {
					$tipoPagosIndex[$asiCod] = array();
				}
				$tipoPagosIndex[$asiCod][] = $tp;
			}
			
			// Combinar datos de tipos de pago con los asientos
			foreach ($resultado['dataASiento'] as &$resp) {
				if (!empty($resp['Asi_Cod']) && isset($tipoPagosIndex[$resp['Asi_Cod']])) {
					// Tomar el primer tipo de pago encontrado para este asiento
					$tp = $tipoPagosIndex[$resp['Asi_Cod']][0];
					$resp = array_merge($resp, $tp);
				}
			}
			unset($resp);
		}
	}
	
	$obBD_con1->echoJson($resultado);
}*/


/* Get Asientos */
if (isset($getAsientos)) {
	//$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		// 'dataASiento' => $obBD_con1->getArrayConsulta('anticipos_proveedores.selectWhere', array('where' => array('anticipos_proveedores.Com_Cod' => $Com_Cod), 'setWhere' => array('byAsiento')), $obBD_conexion, true),
		'dataASiento' => $obBD_con1->getArrayConsulta('anticipos_proveedores.selectWhere', array('where' => array('anticipos_proveedores.Com_Cod' => $Com_Cod), 'setWhere' => array('byAsiento', 'pagoAnticipo', 'tipoPago')), $obBD_conexion),
	);
	foreach ($resultado['dataASiento'] as &$resp) {
		$asi_cod = intval(isset($resp['Asi_Cod']) ? $resp['Asi_Cod'] : 0);
		$atp_cod = intval(isset($resp['Atp_Cod']) ? $resp['Atp_Cod'] : 0);
		if ($asi_cod <= 0) {
			continue;
		}
		$filtro_atp = $atp_cod > 0 ? " AND pap.Atp_Cod = $atp_cod" : '';
		$tipoPago = $obBD_con1->getRowConsultaSql(
			"SELECT pap.Pap_Cod, pap.Atp_Cod, pap.Pap_Cto, pap.Pap_Ctd,
			        pap.Pap_Val, pap.Pap_Est, pap.Pap_img, pap.Pag_Cod,
			        tpg.Pag_Abr, tpg.Pag_Des,
			        che.Che_Cod, che.Che_Fec, che.Che_Num, che.Ban_Cod, che.Che_Est
			 FROM pago_anticipo_proveedores pap
			 LEFT JOIN tipos_pago tpg ON tpg.Pag_Cod = pap.Pag_Cod
			 LEFT JOIN cheques che ON che.Asi_Cod = pap.Asi_Cod
			 WHERE pap.Asi_Cod = $asi_cod
			   $filtro_atp
			 ORDER BY pap.Pap_Cod DESC
			 LIMIT 1",
			$obBD_conexion
		);
		if (is_array($tipoPago) && !empty($tipoPago['Pap_Cod'])) {
			$resp = array_merge($resp, $tipoPago);
		}
	}
	unset($resp);
	$obBD_con1->echoJson($resultado);
}


/* Get cheques */
if (isset($getCheques)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'dataCheque' => $obBD_con1->getArrayConsulta('cheques.selectWhere', array('where' => array('antp.Atp_Cod' => $Atp_Cod, 'antp.Prv_Cod' => $Prv_Cod), 'setWhere' => array('byCheques', 'byDetAntCCPP')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}
/* Obtener plan de cuentas y No. Cuenta del banco para anticipos con cheques */
if (isset($getPlanCuentasCheq)) {
	$obBD_con1->debugLogs(false);
	$campo;
	if ($Ban_Tip === 'EFE' || $Ban_Tip === 'DEP') {
		$campo = 'C';
	}
	if ($Ban_Tip === 'CHE' || $Ban_Tip === 'TRF') {
		$campo = 'B';
	}
	$resultado = array(
		'success' => true,
		'getData' => $obBD_con1->getArrayConsulta('det_plan.selectWhere', array('Ban_Tip' => $campo, 'where' => array('Pec_Cod' => $Pec_Cod), 'setWhere' => array('byPerioCont', 'byBanco')), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}

/* Verificar el numero de cheque */
if (isset($verificaCheque)) {
	$obBD_con1->debugLogs(false);
	$resultado = array(
		'success' => true,
		'numCheque' => $obBD_con1->getArrayConsulta('cheques.selectWhere', array('where' => array('Ban_Cod' => $Ban_Cod, 'Che_Num' => $Che_Num)), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}
/* Protestar cheque */
if (isset($protestarCheq)) {
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$obBD_ins1->echoLog('lola');
		$Pec_Cod = $obBD_ins1->getRowConsulta('perio_cont.selectWhere', array('Date' => date("Y-m-d"), 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		if (count($Pec_Cod) > 0) {

			$var_mes = explode('-', $hoy);
			$tipo_asien_prt = $obBD_ins1->getRowConsulta('tipo_asien.selectWhere', array('where' => array('Tia_Abr' => 'DG'), 'setWhere' => array('isActive')), $obBD_conexion, true);
			//$Tia_Cod=$tipo_asien_prt['Tia_Cod'];

			$Com_Num = $obBD_ins1->getComNumPecAuto($tipo_asien_prt['Tia_Cod'], $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion, true);

			//Actualizamos el estado del cheque
			$obBD_ins1->operacionobBD('cheques.update', array('Che_Cod' => $row['Che_Cod'], 'Prv_Cod' => $row['Prv_Cod'], 'Ban_Cod' => $row['Ban_Cod'], 'Asi_Cod' => $row['Asi_Cod'], 'Che_Est' => 'P'), $obBD_conexionIns, true);
			//Buscamos ese pago
			$pagoAntProv = $obBD_ins1->getRowConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('Pap_Cod' => $row['Pap_Cod'])), $obBD_conexion, true);
			//actualizar estado del pago de anticipo a proveedores
			$obBD_ins1->operacionobBD('pago_anticipo_proveedores.update', array('Pap_Cod' => $pagoAntProv['Pap_Cod'], 'Atp_Cod' => $pagoAntProv['Atp_Cod'], 'Asi_Cod' => $pagoAntProv['Asi_Cod'], 'Pag_Cod' => $pagoAntProv['Pag_Cod'], 'Pap_Est' => 'P', 'Pap_Obs' => 'CHEQUE No.' . $row['Che_Num'] . ' PROTESTADO'), $obBD_conexionIns, true);

			//modificar asiento de cheque protestado
			$obBD_ins1->operacionobBD('asientos.update', array('Asi_Cod' => $row['Asi_Cod'], 'Asi_Glo' => "CHEQUE No. " . $row['Che_Num'] . " protestado"), $obBD_conexionIns, true);
			//insertamos un comprobante
			$obBD_ins1->operacionobBD('comprobantes.insert', array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Prv_Cod' => $row['Prv_Cod'], 'Com_num' => $Com_Num, 'Com_Fec' => $hoy, 'Com_Con' => "CHEQUE No. " . $row['Che_Num'] . " protestado", 'Com_Val' => $row['Che_Val'], 'Tia_Cod' => $tipo_asien_prt['Tia_Cod']), $obBD_conexionIns, true);
			$ultimo_comprobate = $obBD_ins1->insercionid($obBD_conexionIns);
			//Busco la actual cuenta que esta al DEBE
			$PldCod_Actual = $obBD_ins1->getRowConsulta('asientos.selectWhere', array('where' => array('Com_Cod' => $row['Com_Cod'], 'Asi_Deh' => 'D')), $obBD_conexion, true);
			// insertamos un asitento inicial para el cheque protestado
			$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Glo' => "CHEQUES PROTESTADOS", 'Asi_Val' => $row['Che_Val'], 'Pld_Cod' => $PldCod_Actual['Pld_Cod']), $obBD_conexionIns, true);
			$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Glo' => "CHEQUE No. " . $row['Che_Num'] . " protestado", 'Asi_Val' => $row['Che_Val'], 'Pld_Cod' => $row['Pld_Cod']), $obBD_conexionIns, true);
			//creamos un registro en det_ant_ccpp
			$obBD_ins1->operacionobBD('det_ant_ccpp.insert', array('Atp_Cod' => $row['Atp_Cod'], 'Dac_Val' => $row['Che_Val'], 'Com_Cod' => $ultimo_comprobate), $obBD_conexionIns, true);
			//Busco el anticipo proveedores, cambios estado de anticpos proveedor a usado Atp_Cod
			$anticipoCambio = $obBD_ins1->getRowConsulta('anticipos_proveedores.selectWhere', array('where' => array('anticipos_proveedores.Atp_Cod' => $row['Atp_Cod']), array('Atp_Cod', 'Com_Cod', 'Prv_Cod')), $obBD_conexion, true);
			$obBD_ins1->echoLog($anticipoCambio);
			$obBD_ins1->operacionobBD('anticipos_proveedores.update', array('Atp_Cod' => $anticipoCambio['Atp_Cod'], 'Com_Cod' => $anticipoCambio['Com_Cod'], 'Prv_Cod' => $anticipoCambio['Prv_Cod'], 'Suc_Cod' => $Ses_Suc_Cod, 'Atp_Est' => 'U'), $obBD_conexionIns, true);

			$Pec_Cod_val = $Pec_Cod['Pec_Cod'];
			$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=proveedore&campo=Prv_Cod&tipo=" . $tipo_asien_prt['Tia_Cod'] . "&Pec_Cod=$Pec_Cod_val";
		}
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/* Guardar anticipo  */
if (isset($save)) {
	//$obBD_con1->debugLogs(false);
	//$Pec_Cod=$obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date'=>date("Y-m-d"),'where'=>array(),'setWhere'=>array('getByDate','getPerioByFec')),  $obBD_conexion,true);

	$resp = array('success' => false);
	$resp['arrayCheques'] = array();
	$resp['isCheque'] = false;
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$data = $_POST;
		$obBD_ins1->echoLog($data);
		$Pec_Cod = $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date' => $data['Atp_Fec'], 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		if ($data['Atp_Val'] * 1 != $data['totalFinal'] * 1) {
			$valorAct = $data['totalFinal'];
		} else {
			$valorAct = $data['Atp_Val'];
		}
		if (isset($saveDataModAnt)) {
			$obBD_ins1->echoLog($data['Com_Cod']);
			//Update comprobantes Com_Cod
			$obBD_ins1->operacionobBD('comprobantes.update', array('Com_Cod' => $data['Com_Cod'], 'Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Prv_Cod' => $data['Prv_Cod'], 'Com_Num' => $data['Com_Num'], 'Com_Fec' => $data['Atp_Fec'], 'Com_Con' => $data['Atp_Obs'], 'Com_Val' => $valorAct, 'Tia_Cod' => $data['Tia_Cod']), $obBD_conexionIns, true);
			//$Com_Cod_Data = $obBD_ins1->insercionid($obBD_conexionIns);
			//Insert anticipo
			$obBD_ins1->operacionobBD('anticipos_proveedores.update', array('Atp_Cod' => $data['Atp_Cod'], 'Atp_Fec' => $data['Atp_Fec'], 'Atp_Val' => $valorAct, 'Atp_Obs' => $data['Atp_Obs'], 'Com_Cod' => $data['Com_Cod'], 'Prv_Cod' => $data['Prv_Cod'], 'Suc_Cod' => $Ses_Suc_Cod), $obBD_conexionIns, true);
			//$Atp_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
			/* $datosHaBorrar = $obBD_con1->getArrayConsulta('asientos.selectWhere', array('where'=>array('Com_Cod'=>$data['Com_Cod']), 'setWhere'=>array('byCheques')),$obBD_conexion,true);
			$obBD_con1->echoLog($datosHaBorrar); */
			//Borrar asientos que tenga referencia con anticipos y que no sean algun cheque protestado
			$obBD_ins1->operacionobBD('asientos.1', array('Com_Cod' => $data['Com_Cod']), $obBD_conexionIns, true);

			//REGISTRAR LA NEGOCIACION DE CAMARON
			if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
				if (!empty($Cod_Nd)) {
					$obBD_ins1->operacionobBD(36, $data['Cod_Neg'] . '*' . $data['Atp_Cod'] . '*' . 'ANTP' . '*' . $data['Cod_Nd'], $obBD_conexionIns, true);
				} else {
					$obBD_ins1->operacionobBD('nego_documentos.insert', array('Cod_Neg' => $data['Cod_Neg'], 'Cod_Doc' => $data['Atp_Cod'], 'Abr_Doc' => 'ANTP'), $obBD_conexionIns, true);
				}
			}
			//Anular negociacion de la venta
			if (!empty($Cod_Nd) && empty($Cod_Neg) && empty($Num_Neg)) {
				$obBD_ins1->operacionobBD(38, $Cod_Nd . '*' . 'ANTP',  $obBD_conexionIns, true);
			}


			$contChk = 0;
			$obBD_ins1->echoLog($anticipoGrid);
			foreach ($anticipoGrid as $pago) {
				$obBD_ins1->echoLog($pago);
				if ($pago['grid_tipp'] != 'pago') {
					$obBD_ins1->echoLog($_SESSION['Ses_Emp_Cod']);
					//Select del la cuenta contable para Pago anticipos proveedores
					$Pld_Cod_ini = $obBD_con1->getArrayConsulta('det_plan.selectWhere', array('setWhere' => array('setEmpCod', 'setPlanParam', 'isActive', 'isParamANP')), $obBD_conexion, true);
					//Inserto asiento de tipo Debe
					$obBD_ins1->echoLog($Pld_Cod_ini[0]['Pld_Cod']);
					//$obBD_ins1->echoLog($Com_Cod_Data);
					$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'D', 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $Pld_Cod_ini[0]['Pld_Cod'], 'Asi_Glo' => $pago['Asi_Glo'], 'Asi_Con' => 'ANTICIPO A PROVEEDORES'), $obBD_conexionIns, true);
				} else {
					$obBD_ins1->echoLog('else');
					//Inserto asiento de tipo Haber
					$obBD_ins1->operacionobBD('asientos.insert', array('Com_Cod' => $data['Com_Cod'], 'Asi_Deh' => 'H', 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod'], 'Asi_Glo' => $pago['Asi_Glo']), $obBD_conexionIns, true);
					$lastAsiento = $obBD_ins1->insercionid($obBD_conexionIns);
					if ($pago['Pag_Abr'] == 'EFE' || $pago['Pag_Abr'] == 'DEP') {
						$valorCto = '';
					} else {
						$valorCto = $pago['Pap_Cto'];
					}
					// insertamos un pago_anticipo_proveedores
					$obBD_ins1->operacionobBD('pago_anticipo_proveedores.insert', array('Pap_Cto' => $valorCto, 'Pap_Ctd' => $pago['Pap_Ctd'], 'Pap_Val' => $pago['Haber'], 'Atp_Cod' => $data['Atp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $lastAsiento), $obBD_conexionIns, true);
					// Verificamos si el pago es en cheque
					if ($pago['Pag_Abr'] == 'CHE') {
						$contChk += 1;
						$resp['isCheque'] = true;
						array_push($resp['arrayCheques'], array('link' => "?codigo2=$contChk&asi=" . $lastAsiento . "&ban=" . $pago['Ban_Cod'] . "&pro=" . $data['Prv_Cod'], 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Haber']));
						//insertar un registro en la tabla cheques
						if (!empty($pago['Che_Num'])) { //Validar que se pueda editar si tiene un cheque.
							$obBD_ins1->echoLog($pago['Che_Est']);
							$obBD_ins1->operacionobBD('cheques.insert', array('Che_Cod' => $contChk, 'Prv_Cod' => $data['Prv_Cod'], 'Ban_Cod' => $pago['Ban_Cod'], 'Asi_Cod' => $lastAsiento, 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Haber'], 'Che_Obs' => $data['Atp_Obs'], 'Che_Ben' => $data['nombre']), $obBD_conexionIns, true);
						}
					}
				}
			}
			$pecCod = $Pec_Cod['Pec_Cod'];
			$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=" . $data['Com_Cod'] . "&tabla=proveedore&campo=Prv_Cod&tipo=" . $data['Tia_Cod'] . "&Pec_Cod=$pecCod";
			$obBD_con1->echoLog($resp);
			//throw new Exception("The field is undefined.");


		}
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/* Anular Anticipo  */
if (isset($anularAnticipo)) {
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	//$obBD_ins1->echoLog('PHP ANULAR ANTICIPO');
	try {

		$obBD_ins1->echoLog($data);
		//Inactivar un anticipo
		$obBD_ins1->operacionobBD('anticipos_proveedores.setInactive', array('Atp_Cod' => $data[0]['Atp_Cod']), $obBD_conexionIns, true);
		//Inactivar comprobantes
		$obBD_ins1->operacionobBD('comprobantes.1', array('Com_Cod' => $data[0]['Com_Cod']), $obBD_conexionIns, true);
		//Buscamos el desglose del anticipo
		$pagosAntPrv = $obBD_ins1->getArrayConsulta('pago_anticipo_proveedores.selectWhere', array('where' => array('pago_anticipo_proveedores.Atp_Cod' => $data[0]['Atp_Cod'])),  $obBD_conexion, true);
		foreach ($pagosAntPrv as &$pgp) {
			//Inactivar un pago anticipo proveedores
			$obBD_ins1->operacionobBD('pago_anticipo_proveedores.setInactive', array('Pap_Cod' => $pgp['Pap_Cod']), $obBD_conexionIns, true);
		}
		unset($pgp);
		//throw new Exception("The field is undefined.");

	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}

/* Imprimir asiento cheque portestado  */
if (isset($impAsiento)) {
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	try {
		$Pec_Cod = $obBD_con1->getRowConsulta('perio_cont.selectWhere', array('Date' => date("Y-m-d"), 'where' => array(), 'setWhere' => array('getByDate', 'getPerioByFec')),  $obBD_conexion, true);
		$Pec_Cod_val = $Pec_Cod['Pec_Cod'];
		$resp['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=" . $params[0]['Com_Cod'] . "&tabla=proveedore&campo=Prv_Cod&tipo=" . $params[0]['Tia_Cod'] . "&Pec_Cod=$Pec_Cod_val";
	} catch (Exception $e) {
		$obBD_ins1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_ins1->echoJson($resp);
	}
	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$obBD_con1->echoJson($resp);
}
/* GET Proveedores */
if (isset($provAjax)) {
	$dataProv = $obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion, false);
	$obBD_con1->echoJson($dataProv);
}

//* Obtener el documento de la negociación
/*
if (isset($searchNegAntAjax)) {
	$responce = array('success' => false);
	try {
		$responce['data']  = $obBD_con1->getRowConsulta('nego_documentos.selectWhere', array('where' => array('Cod_Doc' => $Atp_Cod)), $obBD_conexion, true);
		$responce['data1'] = $obBD_con1->getRowConsulta('nego_camaron.selectWhere', array('where' => array('Cod_Neg' =>  $responce['data']['Cod_Neg'])), $obBD_conexion, true);
		$responce['data'] = array_merge($responce['data'], $responce['data1']);
		if (!empty($responce['data'])) {
			$responce['success'] = true;
		}
	} catch (Exception $e) {
		$responce['message'] = $e->getMessage();
	}
	$obBD_con1->echoJson($responce);
}*/

if (isset($searchNegAntAjax)) {

	$response = array('success' => false);
	$data = $obBD_con1->getRowConsulta(
		'nego_documentos.selectWhere',
		array('where' => array('Cod_Doc' => $Atp_Cod)),
		$obBD_conexion,
		true
	);

	if (empty($data) || empty($data['Cod_Neg'])) {
		$obBD_con1->echoJson($response);
		return;
	}

	$dataCamaron = $obBD_con1->getRowConsulta(
		'nego_camaron.selectWhere',
		array('where' => array('Cod_Neg' => $data['Cod_Neg'])),
		$obBD_conexion,
		true
	);
	$response['data'] = !empty($dataCamaron)
		? array_merge($data, $dataCamaron)
		: $data;

	$response['success'] = true;

	$obBD_con1->echoJson($response);
}






//Actualizacion de Negociacion de camaron
if (isset($saveNego)) {
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
		//REGISTRAR LA NEGOCIACION DE CAMARON
		if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
			if (!empty($Cod_Nd)) {
				$obBD_ins1->operacionobBD(36, $Cod_Neg. '*' . $Atp_Cod . '*' . 'ANTP' . '*' . $Cod_Nd, $obBD_conexionIns, true);
			} else {
				$obBD_ins1->operacionobBD('nego_documentos.insert', array('Cod_Neg' => $Cod_Neg, 'Cod_Doc' => $Atp_Cod, 'Abr_Doc' => 'ANTP'), $obBD_conexionIns, true);
			}
		}
		//Anular negociacion de la venta
		if (!empty($Cod_Nd) && empty($Cod_Neg) && empty($Num_Neg)) {
			$obBD_ins1->operacionobBD(38, $Cod_Nd . '*' . 'ANTP',  $obBD_conexionIns, true);
		}

	$resp['success'] = $obBD_ins1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) $resp['error'] = $obBD_ins1->MsgError;
	$resp['success'] = true;
	$obBD_con1->echoJson($resp);
}


?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?php echo "Ant.Proveedor Modif/Anular [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script>
		var peridodo = <?php echo json_encode($periodos) ?>,
			prf = <?php echo json_encode($perfil) ?>;
	</script>
	<style>
		.pagination>li>a, .pagination>li>span { padding: 4px 2px; }
		.pagination { /*display: block;*/ margin: 0; padding: 0; }
		.chosen-default span, .chosen-single span { color: #555; }
		.chosen-single span { padding-left: 5px; }
	</style>
</HEAD>

<BODY>
	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo;Modificar Anticipos a Proveedores</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="documentoSearch">
				<div class="row">
					<form name="searchAnticipos" id="searchAnticipos" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchAnticipos','anticiposAjax');">
						<div class="col-xs-5">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">B&uacute;squeda</legend>
								<div class="form-group">
								<label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
								<div class="col-xs-10 radioset opt_search">
									<input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc1">Proveedor</label>
									<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc2">C&eacute;dula/RUC</label>
									<input id="radsc3" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" alt="" />
									<label for="radsc3">No. Comprobante</label>
								</div>
								</div>
								<div class="form-group">
									<label class="col-xs-2 control-label">B&uacute;squeda:</label>
									<div class="col-xs-7">
										<div class="input-group">
											<input name="search" onkeydown="if (event.keyCode === 13)
                                            this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
											<span class="input-group-btn">
												<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
													<span class="glyphicon glyphicon-search"></span>
													<span>Buscar</span>
												</button>
											</span>
										</div>
									</div>
									<input type="text" tabindex="-1" style="display:none;" />
								</div>
							</fieldset>
						</div>
						<div class="col-sm-7">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtros</legend>
								<div class="form-group">
									<label class="col-sm-1 control-label label-xs">Periodo:</label>
									<div class="col-sm-3">
										<select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" style="text-align: center;">
											<option data-year='Todos' data-inicio='' data-fin='' value="T"><< Todos >></option>
											<option data-year='PorFechas' data-inicio='Corte' data-fin='' value="PorFechas" selected> << Por Fechas >></option>
											<!-- <option data-year='Corte' data-inicio='' data-fin='' value="Corte">Fecha Corte</option> -->
											<?php
											foreach ($periodos as $p) {
												echo "<option data-year='$p[Year]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' data-pec-cod='$p[Pec_Cod]' value='$p[Pec_Cod]'>Periodo $p[Year]</option>";
											}
											?>
										</select>
									</div>
									<div class="col-sm-3" id="btnFiltroCorte" style="display: none; margin-left: -20px; width: 1px;">
										<button type="button" class="btn btn-info" size="4" style="height: 22px; width: 22px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Filtro Fecha Corte Activo: se esta visualizando todos los anticipos de clientes hasta la fecha elegida">
											<i class="glyphicon glyphicon-info-sign" style="font-size: 14px;"></i>
										</button>
									</div>
									<script>
										document.getElementById('Pec_Cod').addEventListener('change', function() {
											var btnFiltroCorte = document.getElementById('btnFiltroCorte');
											if (this.value === 'Corte') {
												btnFiltroCorte.style.display = 'block';
											} else {
												btnFiltroCorte.style.display = 'none';
											}
										});
									</script>
									<div class="col-sm-7" style="margin-left: 10px;">
										<div class="input-group input-group-xs">
											<span class="input-group-addon bold alert-info">Desde:</span>
											<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="6" class="form-control input-xs datepicker databind" style="text-align: center;" />
											<span class="input-group-addon bold alert-info">Hasta:</span>
											<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="6" class="form-control input-xs datepicker databind" style="text-align: center;" />
										</div>
									</div>
								</div>
							</fieldset>
							<div class="form-group">
								<div class="col-sm-12 center">
									<?php 
									// Determinar el valor actual del filtro (por defecto: Por Consumir = A y U)
									$valorActual = isset($_GET['letra']) ? $_GET['letra'] : 'AU';
									$valores = array(
										array("label" => "Todos", "value" => "AUC"),
										array("label" => "Por Consumir", "value" => "AU"),
										array("label" => "Consumidos", "value" => "C"),
										array("label" => "Anulados", "value" => "I")
									);
									?>
									<input type="hidden" id="letra" name="letra" value="<?php echo $valorActual; ?>" />
									<nav>
										<ul class="pagination pagination-centered">
											<?php foreach ($valores as $val) { ?>
												<li <?php if ($val['value'] == $valorActual) echo 'class="active"'; ?>>
													<a data-value="<?php echo $val['value']; ?>">
														<?php echo $val['label'] ?>
													</a>
												</li>
											<?php } ?>
										</ul>
									</nav>
								</div>
							</div>
						</div>
					</form>
					<div class="col-xs-12" style="min-height: 360px;">
						<table id="searchGrid" name="searchGrid"></table>
						<div class="form-group" style="margin-top: 6px; margin-bottom: 4px;">
							<label class="checkbox-inline">
								<input type="checkbox" id="chkImprimirTodos" name="chkImprimirTodos" title="Al marcar, Excel y PDF incluirán todos los registros filtrados (sin paginación)" />
								<strong>Imprimir/Exportar todos</strong> <span class="text-muted">(todos los registros filtrados, sin paginación)</span>
							</label>
						</div>
						<div class="Titulos2">
							<span id="plan-footer">
								<strong>Leyenda:</strong>
								<span class="glyphicon glyphicon-stop green"></span> Anticipos Usados </span>
							<span class="glyphicon glyphicon-stop gray"></span> Anticipos Consumidos </span>
							<span class="glyphicon glyphicon-stop red"></span> Anticipos Anulados </span>

						</div>
						<div id="searchGridPager"></div>
					</div>
				</div>
				<br>	
				<div>						
					<button class="btn btn-sm btn-success" type="button" onclick="vaciarGridCruce(); $('#cruceDialog').dialog('open');"><i class="glyphicon glyphicon-random"></i> Cruce Manual</button>
				</div>
			</div>

			<div id="documentoUpdate" hidden="true">
				<div class="row">
					<div class="col-sm-12">
						<form class="form-horizontal normal" id="anticipoPrvForm" name="anticipoPrvForm" action="javascript:updateData('anticipoPrvForm','saveDataModAnt','modificar')">
							<div class="col-sm-12">
								<div class="row">
									<div class="col-sm-6">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Proveedor</legend>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
												<div class="col-sm-6">
													<input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
													<input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
													<input name="Prv_Cod" id="Prv_Cod" type="text" style="display:none;" />
													<input name="Com_Cod" id="Com_Cod" type="text" style="display:none;" />
													<input name="Atp_Cod" id="Atp_Cod" type="text" style="display:none;" />
													<input name="op_opciones" type="text" value="c" style="display: none;" />
													<input type="text" name="totalFinal" id="totalFinal" hidden>
													<input name="Atp_Val" id="Atp_Val" type="text" value="0.00" style="display: none;" />
													<!-- <input name="Prs_Ced" id="Prs_Ced" type="text" class="form-control input-sm" tabindex="1" required="" readonly /> -->
													<div class="input-group input-group-sm">
														<input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un proveedor..." class="form-control input-sm" tabindex="1" required="" readonly />
														<span class="input-group-btn">
															<button type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Proveedor" tabindex="2">
																<span class="glyphicon glyphicon-search"></span>
															</button>
														</span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm">Proveedor:</label>
												<div class="col-sm-6">
													<input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly />
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
												<div class="col-sm-6">
													<input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly />
												</div>
											</div>
											<!-- Negociacion -->
											<?php
											if ($configs["Cof_NegCam"] == 'S') { ?>
												<div class="form-group">
													<label class="col-sm-4 control-label label-sm">Neg. camarón:</label>
													<div class="col-sm-6">
														<div class="input-group input-group-sm">
															<input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-sm clearable dialogSearch" tabindex="1" readonly />
															<input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
															<input type="text" name="Cod_Nd" id="Cod_Nd" style="display:none;" />
															<span class="input-group-btn">
																<button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>																
																<button type="button" onclick="clearNego()" class="btn btn-danger btn-xs" title="Eliminar Negociación"><span class="glyphicon glyphicon-remove"></span></button>
																<button type="button" onclick="saveNego()" class="btn btn-success btn-xs" title="Ejecutara acción"><span class="glyphicon glyphicon-floppy-disk"></span></button>
														
															</span>
														</div>
													</div>
												</div>
											<?php } ?>

										</fieldset>
									</div>
									<div class="col-sm-6">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Anticipo</legend>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">Fecha:</label>
												<div class="col-sm-6">
													<div class="input-group">
														<input name="Atp_Fec" type="text" id="Atp_Fec" size="10" class="form-control input-sm datepicker" required="" />
														<span class="input-group-addon">
															<span class="glyphicon glyphicon-calendar"></span>
														</span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-4 control-label label-sm required">Tipo de Asiento:</label>
												<div class="col-sm-6">
													<input type="text" name="Tia_Cod_temp" id="Tia_Cod_temp" hidden>
													<input type="text" name="Com_Num" id="Com_Num" hidden>
													<select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
														<?php
														if (count($rows_tipo_asiento) > 0) {
															foreach ($rows_tipo_asiento as $row) {
																echo "<option value='$row[Tia_Cod]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
															}
														}
														?>
													</select>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-4 control-label label-sm ">Observaci&oacute;n:</label>
												<div class="col-sm-6">
													<!-- <div class="input-group input-group-sm"> -->
													<textarea class="form-control" id="Atp_Obs" val="" name="Atp_Obs" rows="2"></textarea>
													<!-- </div> -->
												</div>
											</div>
										</fieldset>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-12">
								<div id="contenedor_pagos" style="width: 100%;padding-top: 10px;">
									<table id="pagos"></table>
									<div id="pagosPager"></div>
								</div>
							</div>
						</div>
						<div class="separator"></div>
						<div class="row">
							<div class="col-sm-12">
								<button class="btn btn-sm btn-inverse no" onclick="moveToMain();limpiarFormAnticipos();">
									<i class="glyphicon glyphicon-arrow-left"></i> Atras</button>
								<button class="btn btn-sm btn-success no" onclick="$('#anticipoPrvForm').formSubmit();">
									<i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="verAsientoDialogMod" title="Datos">
		<div class="row">
			<div class="col-sm-12">
				<div id="tabs_sub_ant_det" class="ui-tab-fix">
					<ul style="font-size: 12px;" role="tablist">
						<li id="sub_ant_detasi">
							<a href="#sub_ant_det_asi">Asiento</a>
						</li>
					</ul>
					<div id="sub_ant_det_asi">
						<div class="row">
							<div class="col-sm-12" style="padding-top: 10px;">
								<table id="showSubGridAsi" name="showSubGridAsi"></table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="verPagosDialogMod" title="Pago">
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Datos del Anticipo</legend>
					<form id="verPagosForm" class="form-horizontal normal">
						<div class="row">
							<div class="col-sm-7">
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">Proveedor:</label>
									<div class="col-xs-8">
										<input type="text" id="prov_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">No. Compr.:</label>
									<div class="col-xs-8">
										<input type="text" id="compr_show" class="form-control input-xs" readonly>
									</div>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">C&eacute;dula/R.U.C.:</label>
									<div class="col-xs-8">
										<input type="text" id="ruc_show" class="form-control input-xs" readonly>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-4 control-label label-xs">Fecha:</label>
									<div class="col-xs-8">
										<input type="text" id="fec_show" class="form-control input-xs" readonly>
									</div>
								</div>
							</div>
						</div>
						<div class="form-group condensed">
							<div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;">
								<b>USUARIO:</b>
								<span id="usuario" name="usuario" class="databind"></span>
								<b>CREACIÓN:</b>
								<span id="Com_Sys" name="Com_Sys" class="databind"></span>
							</div>
						</div>
					</form>
				</fieldset>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Observaci&oacute;n</legend>
					<div class="form-group">
						<div class="col-xs-12">
							<textarea id="obs_show" class="form-control input-xs" readonly></textarea>
						</div>
					</div>
				</fieldset>
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<div id="tabs_ant_det" class="ui-tab-fix">
					<ul style="font-size: 12px;" role="tablist">
						<li id="ant_detasi">
							<a href="#ant_det_asi">Asientos</a>
						</li>
						<li id="ant_detche">
							<a href="#ant_det_che">Cheques</a>
						</li>
					</ul>
					<div id="ant_det_asi">
						<div class="row">
							<div class="col-sm-12" style="padding-top: 10px;">
								<table id="showPagosAsi" name="showPagosAsi"></table>
							</div>
						</div>
					</div>
					<div id="ant_det_che">
						<div class="row">
							<div class="col-sm-12" style="padding-top: 10px;">
								<table id="showPagosChe" name="showPagosChe"></table>
								<div class="Titulos2">
									<span id="plan-footer">
										<strong>Leyenda:</strong>
										<span class="glyphicon glyphicon-stop red"></span> Cheques protestados </span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="verPagosDialog" title="Pago">
		<form id="verPagosForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs">Tipo de pago:</label>
				<div class="col-xs-6">
					<input type="text" id="pago_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">No. cheque:</label>
				<div class="col-xs-6">
					<input type="text" id="numero_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Cheque Transferencia">
				<label class="col-xs-4 control-label label-xs">Cuenta:</label>
				<div class="col-xs-6">
					<input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="destino_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs">Fecha:</label>
				<div class="col-xs-6">
					<input type="text" id="fecha_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group Transferencia Deposito Efectivo Cheque">
				<label class="col-xs-4 control-label label-sm">Valor:</label>
				<div class="col-xs-6 ">
					<input type="text" id="valor_ver" class="form-control input-xs" readonly>
				</div>
			</div>

			<div class="form-group center">
				</br>
				<a class="btn btn-sm btn-primary" onclick="$('#verPagosDialog').dialog('close');">
					<i class="glyphicon glyphicon-remove"></i> Cerrar</a>
			</div>
		</form>
	</div>

	<div id="comprobantePagoDialog" title="Comprobante de transferencia">
		<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:300px;padding:12px;background:#f3f5f7;border-radius:6px;">
			<img id="comprobantePagoImagen" alt="Comprobante de transferencia" style="display:block;max-width:100%;max-height:72vh;border-radius:5px;box-shadow:0 5px 20px rgba(0,0,0,.2);">
			<a id="comprobantePagoDescargar" class="btn btn-success btn-sm" href="#" download style="margin-top:12px;">
				<i class="glyphicon glyphicon-download-alt"></i> Descargar imagen
			</a>
		</div>
	</div>

	<div id="successDialog" title="Mensaje del Sistema">
		<center>
			<h2>El Comprobante se ha registrado con Exito!</h2>
		</center>
		<center>
			<button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
				<i class="icon-ban-circle icon-white"></i>
				<span>Cerrar</span>
			</button>
			<a id="impCompr" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante">
				<span class="btn btn-success start">
					<i class="icon-print icon-white"></i>
					<span>Imprimir</span>
				</span>
			</a>
			<br>
			<br>
			<fieldset class="exa-fieldset" id="siche" hidden>
				<legend class="Titulos2">Impresi&oacute;n de Cheques</legend>
				<div>
					<center>
						<h5>Eliga el cheque que desea imprimir!</h5>
					</center>
					<div class="row">
						<div class="form-group">
							<div class="col-sm-3"></div>
							<div class="col-sm-6">
								<div class="input-group">
									<select id="Che_imp" name="Che_imp" class="form-control input-xs" onchange="cambiarChek()">
									</select>
								</div>
							</div>
						</div>
					</div>
					<br>
					<div class="row">
						<?php $ruta = './' . (file_exists('cheques/' . $Ses_Emp_Cod) ? "cheques/$Ses_Emp_Cod/" : ''); ?>
						<div id="conten_bancos_imp">
							<table style="margin-bottom:10px;" cellpadding="1" border="1">
								<tr>
									<td align="center" class="ui-widget-content" colspan="6">
										<b>&nbsp; plantillas &nbsp;</b>
									</td>
								</tr>
								<tr id="impchetd">
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php" href="" target="_blank"
											title="Banco de Machala">
											<img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php" href="" target="_blank"
											title="Banco del Pacifico">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php" href="" target="_blank"
											title="Banco del Rumiñahui">
											<img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php" href="" target="_blank"
											title="Banco del Guayaquil">
											<img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php" href="" target="_blank"
											title="Banco del Pichincha">
											<img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30" />
										</a>
									</td>
									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php" href="" target="_blank"
											title="Banco Internacional">
											<img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32" />
										</a>
									</td>

									<td align="center">
										<a data-ruta="<?php echo $ruta; ?>cheques/1/tes_pri_cheque_loj_1.0.php" href="" target="_blank"
											title="Banco de Loja">
											<img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="32" height="32" />
										</a>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</fieldset>
		</center>
	</div>

	<div id="imprimir" style="display: none;">
		<div style="width: 1030px;">
			<?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE ANTICIPOS PROVEEDORES', '<span class="subtitle">Total de registros</span>', $obBD_conexion) ?>
			<table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse;table-layout:auto  ;font-size:12px;"></table>
			<?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
		</div>
	</div>

	<!-- Inicio del diálogo para buscar Clientes -->
	<div id="clientesDialog" title="B&uacute;squeda de Clientes">
		<form class="form-horizontal normal"> </form>
	</div>

	<!-- dialogo de registro de pagos de anticipo -->
	<div id="pagosDialog" title="Agregar Pagos">
		<form id="pagosForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tipo:</label>
				<div class="col-xs-6">
					<select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))"
						required="">
						<?php
						if (count($tPagos) > 0) {
							foreach ($tPagos as $row) {
								echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
							}
						}
						?>
					</select>
				</div>
			</div>

			<!-- Bancos de DataBase -->
			<div class="form-group Cheque Transferencia Efectivo Deposito">
				<label class="col-xs-4 control-label label-xs required">Cuenta:</label>
				<div class="col-xs-6">
					<select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
					</select>
				</div>
			</div>

			<div class="form-group  Deposito Transferencia">
				<label class="col-xs-4 control-label label-xs required">Cta. Destino:</label>
				<div class="col-xs-6">
					<input type="text" id="Pap_Ctd" name="Pap_Ctd" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">Fecha:</label>
				<div class="col-xs-6">
					<input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
				</div>
			</div>

			<div class="form-group Cheque">
				<label class="col-xs-4 control-label label-xs required">No. cheque:</label>
				<div class="col-xs-6">
					<div class="input-group input-group-xs">
						<span class="input-group-addon validate">
							<i id="indicadorChe" class=""></i>
						</span>
						<input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)"
							onkeypress="return soloNumeros(event)">
					</div>
				</div>
			</div>

			<div class="form-group Transferencia Deposito Efectivo Cheque">
				<label class="col-xs-4 control-label label-sm required">Valor:</label>
				<div class="col-xs-6 ">
					<div class="input-group input-group-xs">
						<span class="input-group-addon">
							<i id="indicadorChe" class="glyphicon glyphicon-usd"></i>
						</span>
						<input name="Pap_Val" type="text" id="Pap_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onkeypress="return  validar_decimal(event)" />
					</div>
				</div>
			</div>

			<div class="form-group center">
				</br>
				<a id="btnGuardar" class="btn btn-sm btn-primary" onclick="agregarFila(0)"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
			</div>
		</form>
	</div>

	<!-- Inicio del diálogo para buscar Cuentas  -->
	<div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>

	<!-- Inicio del diálogo para buscar Proveedores -->
    <div id="proveedoresDialog" title="B&uacute;squeda de Proveedores">
		<form class="form-horizontal normal"> 
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">C&eacute;dula/RUC:</label>
				<div class="col-xs-6">
					<input type="text" id="Prs_Ced" name="Prs_Ced" class="form-control input-xs" required="">
				</div>
			</div>
		</form>
    </div>
	
	<!-- Modal Consumo manual de anticipos  -->
	<div id="cruceDialog" title="Consumo de Anticipos">   
		<form id="cruceForm" class="form-horizontal normal">      			
			<div id="infoCruce" name="infoCruce">
				<fieldset class="exa-fieldset">
				<legend  class="Titulos2">Datos del Cruce</legend>	
					<div class="form-group">
						<label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>
						<div class="col-xs-9">
							<div class="input-group input-group-xs">						
								<span id='PrsCed' class="input-group-addon bold alert-info"></span>
								<input type='hidden' id='Prv_Cod_Pagos' name='Prv_Cod_Pagos'>
								<input type='hidden' id='Prs_Cod_Pagos' name='Prs_Cod_Pagos'>
								<input type='hidden' id='Cli_Cod_Pagos' name='Cli_Cod_Pagos'>
								<input type='hidden' id='Com_Cod' name='Com_Cod'>
								<input type='hidden' id='Com_Fec_Old' name='Com_Fec_Old'>
								<input name="Prs_Nom_Pagos" id="Prs_Nom_Pagos" type="text"  placeholder="Seleccione un proveedor..."  class="form-control input-xs" tabindex="1" readonly/>
								<span class="input-group-btn">
								<a onclick="$('#proveedoresDialog').dialog('open');" class="btn btn-success btn-xs" title="Seleccionar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></a>                    
								</span>
							</div>               
						</div>              
					</div>		
				
					<div class="form-group">
						<label class="col-xs-2 control-label label-xs required">Tipo:</label>
						<div class="col-xs-3">
							<select id="PagCod" name="PagCod" class="form-control input-xs" onchange="habilitaCacilleros($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))"
								required="">							
								<?php							
									foreach ($consumoPago as $row) {
										echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
									}							
								?>
							</select>
						</div>	
						<label class="col-xs-3 control-label label-xs required">Fecha Pago:</label>
						<div class="col-xs-3">																											
							<input name="Com_Fec" type="text" id="Com_Fec"  class="form-control input-xs datepicker" required="" />						
						</div>	
					</div>

					<!-- Bancos de DataBase -->
					<div class="form-group">
						<label class="col-xs-2 control-label label-xs required">Bco. Origen:</label>
						<div class="col-xs-4">
							<?php $bancos= $obBD_con1->getArrayConsultaSql('select bancos.* From bancos where Bak_Est="A" AND Bak_Cod!=1',  $obBD_conexion); ?>
							<select id="BakCod" name="BakCod" class="form-control input-xs Transferencia Cheque Bloqueo" disabled required="">														
								<?php							
									foreach ($bancos as $row) {
										echo "<option value='$row[Bak_Cod]' data-des='$row[Bak_Des]' data-tip='$row[Ban_Tip]'>$row[Bak_Des]</option>";
									}							
								?>
							</select>
						</div>					
						<label class="col-xs-2 control-label label-xs required">Cta. Destino:</label>
						<div class="col-xs-4">
							<select id="BanCod" name="BanCod" class="form-control input-xs Transferencia Efectivo Deposito Bloqueo" required="">														
								<?php							
									foreach ($consumoBanco as $row) {
										echo "<option value='$row[Ban_Cod]' ".(($row['Ban_Tip']!=='C')?"style='display: none'":"")." data-des='$row[Pld_Des]' data-tip='$row[Ban_Tip]' data-Pld='$row[Pld_Cod]' data-Cta='$row[Ban_Cue]'>$row[Pld_Des]</option>";
									}							
								?>
							</select>
						</div>					
					</div>
					<div class="form-group">
						<label class="col-xs-2 control-label label-xs required">Cta. Origen:</label>
						<div class="col-xs-4">
							<input type="text" id="PapCtd" name="PapCtd" onchange="" disabled onkeypress="return soloNumeros(event)" class="form-control input-xs Cheque Deposito Transferencia Bloqueo">
						</div>
						<label class="col-xs-2 control-label label-xs required">No. cheque:</label>
						<div class="col-xs-4">
							<div class="input-group input-group-xs">
								<span class="input-group-addon validate"><i id="estadoNumChe" class="" ></i></span>
								<input type="text" id="CheNum" name="CheNum" size="20" onchange="validaNumChequeExt(this.value)" disabled class="form-control input-xs Cheque Bloqueo" onkeyup="" onkeypress="return soloNumeros(event)">
								<span class="input-group-addon bold alert-info validate" title="Fecha de Cheque"><i id="indicadorChe" class=""></i>Fecha</span>
								<input name="CheFec" type="text" id="CheFec"  class="form-control input-xs datepicker Cheque Bloqueo" disabled required="" />
							</div>
						</div>	
					</div>
					<div class="form-group">
						<label class="col-xs-2 control-label label-sm required">Valor:</label>	
						<div class="col-xs-3">										
							<input name="PapVal" type="text" id="PapVal" size="10" class="form-control input-xs readOnly" disabled required="" autocomplete="off" onkeypress="return  validar_decimal(event)" />					
						</div>					
						<label class="col-xs-3 control-label label-xs required">Cta Otros:</label>
						<div class="col-xs-4">
							<div class="input-group input-group-xs">
								<span class="input-group-addon validate"><i id="infPldCdc" class=""></i></span>
								<input type="hidden" id="Pld_Cod_Otr" name="Pld_Cod_Otr">
								<input type="text" id="Pld_Des_Otr" name="Pld_Des_Otr" size="20" onchange="" disabled class="form-control input-xs Otros Bloqueo" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)">
								<span class="input-group-btn">
									<a id="btnCuenta" name="btnCuenta" onclick="$('#cuentasDialog').dialog('open');"  class="btn btn-success btn-xs Otros Bloqueo disabled" title="Seleccionar Cuenta"  tabindex="2"><span class="glyphicon glyphicon-search"></span></a>
								</span>
							</div>
						</div>
					</div>
					<div class="form-group">									
						<label class="col-xs-2 control-label label-xs">Observ.:</label>
						<div class="col-sm-10"> 
							<input name="PapObs" type="text" id="PapObs" size="30" class="form-control input-xs" required="" autocomplete="off"/>
						</div>					                   
					</div>																			
				</fieldset>
			</div>
		</form>
        <div class="row">
            <div class="col-sm-12">
				<table id="crucesGrid" name="crucesGrid"></table>
            </div>
        </div>
		<br>
		<div class="form-group center">					
			<a id="btnGuardar" class="btn btn-sm btn-primary" onclick="preSaveConsumo()"> <i class="glyphicon glyphicon-floppy-disk"></i> Guardar</a>
		</div>
    </div> 

	<!-- Negociaciones-->
	<div id="negDialog" title="B&uacute;squeda de Negociación">
		<form id="frm_nego" name="frm_nego" class="form-horizontal normal" action="javascript:$('#containerNegoci').Search('#frm_nego','negociacionesAjax'); ">
			<fieldset class="exa-fieldset" id="prodFormTemp">
				<div class="col-xs-12 col-sm-12">
					<legend class="Titulos2">B&uacute;squeda</legend>
					<div class="form-group">
						<div class="col-sm-12">
							<div class="input-group">
								<input id="search" name="search" onkeydown=" this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
								<span class="input-group-btn">
									<button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="-1">
										<span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
									</button>
								</span>
							</div>
						</div>
					</div>
					<input type="text" tabindex="-1" style="display:none;">
				</div>
			</fieldset>
		</form>
		<table id="containerNegoci"></table>
	</div>
	<script>
		//Ver negociaciones
		$('#negDialog').dialog({ autoOpen: false });
		var containerNegoci = $("#containerNegoci");
		$(function() { armargrid(); });

		function armargrid() {
			containerNegoci.createGrid({
				width: 260,
				height: 140,
				colModel: [
					{ label: 'Cod.Cop', name: 'Cod_Neg', width: 30 },
					{ label: 'Num.Agu', name: 'Num_Neg', width: 80 },
					{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton',
						formatoptions: {
							action: selectNego
						}
					},
				],
				jsonReader: { root: "response", repeatitems: false },
				datatype: "local",
				footerrow: false,
			});
		}

		function selectNego(data) {
			$('#Num_Neg').val(data['Num_Neg']);
			$('#Cod_Neg').val(data['Cod_Neg']);
			$('#negDialog').dialog('close');
		}
	</script>
	<script src="../VALIDACIONES/tes_val_anticipo_mod_prv_3.0.js?a=5"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
	<?php
	// Cerrado y liberacion de las conexiones
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
	?>
</BODY>

</HTML>