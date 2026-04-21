<?php

/**
 * @abstract Permite modificar anticipos
 * @author Cesar Bermeo
 * @version 2.0
 * Fecha de creaci�n: 16/04/2019
 *
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

// Incluir librería de QR
if (!class_exists('QRcode')) {
    $ruta_qr_lib = $APP_REAL_PATH . "/Librerias/phpqrcode/phpqrcode.php";
    if (file_exists($ruta_qr_lib)) {
        require_once($ruta_qr_lib);
    }
}

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new  Class_Log_Datos_Mani;
$hoy = date("Y-m-d");
$fecha_hora_actual = date("Y-m-d H:i:s");

$configs = $obBD_con1->getRowConsulta('confi_fact.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod)), $obBD_conexion);
$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array(/*'setWhere'=>array('getSaldoAnticipos'),*/'where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
// $anticipo = !empty($cliente_manifiesto['Pla_Cod']) ? $obBD_con1->getRowConsulta('manifiesto_anticipo.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion) : array('saldo' => 0);
$anticipo = !empty($cliente_manifiesto['Pla_Cod']) ? $obBD_con1->getRowConsulta('manifiesto_anticipo.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod'], 'Cli_Cod' => $cliente_manifiesto['Cli_Cod']), $obBD_conexion) : array('saldo' => 0);

/* Perfiles y Permisos */
$perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod), 'setWhere' => array('getPerfil')), $obBD_conexion);
if (function_exists('utf8_encode_deep')) {
    utf8_encode_deep($perfil);
}

// Validar si el usuario tiene permiso para ver el botón de certificado
$perfiles_permitidos = array('Administrador de Sistemas', 'Gerente', 'Admin_Oper', 'Contador', 'Auditor', 'Plantas');
$mostrarBotonCertificado = false;
$mostrarBotonSelectorPlantaSaldos = false;
$firmar_solo_si = false;
$firmar_solo_no = false;

if (is_array($perfil)) {
    foreach ($perfil as $p) {
        $per_desc = trim($p['Per_Des']);
        if (in_array($per_desc, $perfiles_permitidos)) {
            $mostrarBotonCertificado = true;
        }
        if ($per_desc == 'Administrador de Sistemas') {
            $mostrarBotonSelectorPlantaSaldos = true;
        }
        if ($per_desc == 'Gerente' || $per_desc == 'Contador') {
            $firmar_solo_si = true;
        }
		if ($per_desc == 'Plantas' || $per_desc == 'Admin_Oper') {
            $firmar_solo_no = true;
        }
    }
}

/* Identificar perfil de Plantas y obtener datos para el certificado */
$esPerfilPlanta = false;
$infoPlantaCertificado = null;
if (is_array($perfil)) {
    foreach ($perfil as $p) {
        if (trim($p['Per_Des']) == 'Plantas') {
            $esPerfilPlanta = true;
            break;
        }
    }
}

if ($esPerfilPlanta && !empty($cliente_manifiesto['Cli_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) {
    $infoPlantaCertificado = $obBD_con1->getRowConsulta(8, array('Cli_Cod' => $cliente_manifiesto['Cli_Cod'], 'Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion);
    if ($infoPlantaCertificado) {
        $infoPlantaCertificado['Cli_Cod'] = $cliente_manifiesto['Cli_Cod'];
        $infoPlantaCertificado['Pla_Cod'] = $cliente_manifiesto['Pla_Cod'];
        if (function_exists('utf8_encode_deep')) {
            utf8_encode_deep($infoPlantaCertificado);
        }
    }
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
if (function_exists('utf8_encode_deep')) {
    utf8_encode_deep($periodos);
}

/* Control para sanción de planta */
$sanciones = $obBD_con1->getArrayConsulta('manifiesto_sanciones.1', array('fecha' => $fecha_hora_actual), $obBD_conexion);
$prsCedSancionPlanta = $cliente_manifiesto['Prs_Ced'];

$plaSanciones = array_filter($sanciones, function($sancion)use($prsCedSancionPlanta) {
	return $sancion['identi'] == $prsCedSancionPlanta && $sancion['Msa_Tip'] == 'PL';
});

$listado_choferes_sancionados_modal = array(); // Se rellena donde se construye el select Cho_Cod
$listado_vehiculos_sancionados_modal = array(); // Se rellena donde se construye el select Veh_Cod

// Obtener saldo de manifiestos sin factura (Vet_Cod es NULL o 0)
$saldo_sin_factura = array('saldo' => 0);
if (!empty($cliente_manifiesto['Cli_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) {
	try {
		$Cli_Cod = intval($cliente_manifiesto['Cli_Cod']);
		$Emp_Cod = intval($Ses_Emp_Cod);
		$Pla_Cod = intval($cliente_manifiesto['Pla_Cod']);
		$sql_saldo_sin_factura = "SELECT COALESCE(SUM(cast(manifiesto.Man_Pes*(manifiesto.Man_Pun/1000) as decimal(10,2))), 0) as saldo
                                  FROM manifiesto
                                  INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                                  WHERE manifiesto.Cli_Cod = $Cli_Cod
                                  AND manifiesto.Man_Est = 'A'
                                  AND cliente.Emp_Cod = $Emp_Cod
                                  AND manifiesto.Pla_Cod = $Pla_Cod
                                  AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)";
		$result = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_saldo_sin_factura, $obBD_conexion->conexion));
		if ($result && isset($result['saldo'])) {
			$saldo_sin_factura['saldo'] = floatval($result['saldo']);
		}
	} catch (Exception $e) {
		$saldo_sin_factura['saldo'] = 0;
	}
}

// Calcular saldo total (Anticipos - Sin Facturar)
$saldo_anticipo_val = isset($anticipo['saldo']) ? floatval($anticipo['saldo']) : 0;
$saldo_sin_factura_val = isset($saldo_sin_factura['saldo']) ? floatval($saldo_sin_factura['saldo']) : 0;
$saldo_total = $saldo_anticipo_val - $saldo_sin_factura_val;

$plantas_saldos_modal = array();
$sql_plantas_saldos_modal = "SELECT mp.Pla_Cod, mp.Pla_Nom, mp.Pla_Dis, mp.Pla_Dir, mp.Cli_Cod,
									CONCAT(pr.Prs_Nom, ' ', pr.Prs_Ape) AS Cli_Nom, pr.Prs_Ced
							  FROM manifiesto_plantas mp
							  INNER JOIN cliente cl ON cl.Cli_Cod = mp.Cli_Cod
							  INNER JOIN persona pr ON pr.Prs_Cod = cl.Prs_Cod
							  WHERE mp.Pla_Est = 'A'
							  ORDER BY mp.Pla_Nom ASC";
$res_plantas_saldos = $obBD_con1->consulta($sql_plantas_saldos_modal, $obBD_conexion->conexion);
if ($res_plantas_saldos) {
	while ($row_planta = $obBD_con1->fetch_assoc($res_plantas_saldos)) {
		$plantas_saldos_modal[] = $row_planta;
	}
}
if (function_exists('utf8_encode_deep')) {
	utf8_encode_deep($plantas_saldos_modal);
}

/* Tipo Asiento */
//$rows_tipo_asiento = $obBD_con1->getArrayConsulta('tipo_asien.selectWhere', array('where' => array('Tia_Abr' => 'EG'), 'setWhere' => array('isActive'), 'order' => 'tipo_asien.Tia_Abr'), $obBD_conexion);

/* Busuqeda de Grid */
if (isset($manifiestoAjax)) {
	//$obBD_con1->debugLogs(false);
	$data = $_GET;
	//$obBD_con1->echoLog(trim($data['letra']));
	if (trim($data['letra']) == 'Activos') {
		$datos = array_merge($_GET, array('setWhere' => array('isActive')));
	}
	if (trim($data['letra']) == 'Anulados') {
		$datos = array_merge($_GET, array('setWhere' => array('isInactive')));
	}
	if(!empty($cliente_manifiesto)){
		$datos['where'][] = 'manifiesto.Pla_Cod = ' . $cliente_manifiesto['Pla_Cod'];
	}
	// Filtro por factura
	$filtroFactura = isset($data['filtro_factura']) ? trim($data['filtro_factura']) : '';
	if (!empty($filtroFactura)) {
		if (!isset($datos['setWhere'])) {
			$datos['setWhere'] = array();
		}
		// Si no hay filtro de estado, aplicar isActive por defecto
		if (trim($data['letra']) == 'Activos' || empty($data['letra'])) {
			if (!in_array('isActive', $datos['setWhere'])) {
				$datos['setWhere'][] = 'isActive';
			}
		}

		if ($filtroFactura == 'FACTURADOS') {
			$datos['setWhere'][] = 'getFacturados';
		} else if ($filtroFactura == 'SIN FACTURAR') {
			$datos['setWhere'][] = 'getSinFactura';
		} else if ($filtroFactura == 'P') {
			$datos['setWhere'][] = 'getPendiente';
		} else if ($filtroFactura == 'GE') {
			$datos['setWhere'][] = 'getGaritaIn';
		} else if ($filtroFactura == 'A') {
			$datos['setWhere'][] = 'getAprobado';
		} else if ($filtroFactura == 'GS') {
			$datos['setWhere'][] = 'getGaritaOut';
		} else if ($filtroFactura == 'F') {
			$datos['setWhere'][] = 'getFacturadoManTes';
		} else if ($filtroFactura == 'R') {
			$datos['setWhere'][] = 'getRechazado';
		}
	}
	
	// Filtro de ordenamiento
	$ordenarPor = isset($data['ordenar_por']) ? trim($data['ordenar_por']) : '';
	if (!empty($ordenarPor)) {
		$datos['ordenar_por'] = $ordenarPor;
	}

	$resultado = $obBD_con1->getPageGrid('manifiesto.selectWhere', $datos, $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}

if (isset($dataModificarAjax)) {
	$responde = array('success' => true);
	$responde['antic'] = $obBD_con1->getRowConsulta(2, $_GET, $obBD_conexion);
	$responde['trans'] = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Cli_Cod' => $Cli_Cod)), $obBD_conexion);
	$responde['chof'] = $obBD_con1->getArrayConsulta('manifiesto_chofer.selectWhere', array('where' => array('manifiesto_chofer.Cli_Cod' => $Cli_Cod)), $obBD_conexion);
	$responde['vehi'] = $obBD_con1->getArrayConsulta('manifiesto_vehiculo.selectWhere', array('where' => array('manifiesto_vehiculo.Cli_Cod' => $Cli_Cod, 'Veh_Est' => 'A')), $obBD_conexion, true);
	$obBD_con1->echoJson($responde);
}
if (isset($ajaxVehiculoManifiesto)) {
	$resul = array('success' => true);
	$resul['rows'] = $obBD_con1->getArrayConsulta('manifiesto_vehiculo.selectWhere', array('where' => array('Mat_Cod' => $Mat_Cod, 'Veh_Est' => 'A')), $obBD_conexion, true);
	$obBD_con1->echoJson($resul);
}

if (isset($saveTransporteAjax)) {
	$obBD_con1->inicio_transaccion($obBD_conexion);
	try {
		$datos = array('Mat_Des' => $Mat_Des, 'Mat_Mae' => $Mat_Mae, 'Mat_Tel' => $Mat_Tel, 'Mat_Pco' => $Mat_Pco, 'Mat_Dir' => $Mat_Dir, 'Cli_Cod' => $Cli_Cod);
		if (!empty($Mat_Cod)) { //modificar
			$datos['where'] = array('Mat_Cod' => $Mat_Cod);
			$obBD_con1->operacionobBD('manifiesto_transporte.update', $datos, $obBD_conexion);
			$resp['Mat_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
		} else // Nuevo registro
			$obBD_con1->operacionobBD('manifiesto_transporte.insert', $datos, $obBD_conexion);
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}

if (isset($saveVehiculoAjax)) {
	$obBD_con1->inicio_transaccion($obBD_conexion);
	try {
		$datos = array('Veh_Mar' => $Veh_Mar, 'Veh_Pla' => $Veh_Pla, 'Veh_Col' => $Veh_Col, 'Veh_Cap' => $Veh_Cap, 'Veh_Tit' => $Veh_Tit, 'Emp_Cod' => $Ses_Emp_Cod, 'Veh_Tip' => 'VM', 'Mat_Cod' => $Mat_Cod_New);
		if (!empty($Veh_Cod)) { //modificar
			$datos['where'] = array('Veh_Cod' => $Veh_Cod);
			$obBD_con1->operacionobBD('vehiculo.update', $datos, $obBD_conexion);
			$resp['Veh_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
		} else { // Nuevo registro
			$obBD_con1->operacionobBD('vehiculo.insert', $datos, $obBD_conexion);
			$resp['Veh_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
			$obBD_con1->operacionobBD('manifiesto_vehiculo.insert', array('Veh_Cod' => $resp['Veh_Cod_New'], 'Cli_Cod' => $Cli_Cod), $obBD_conexion);
		}
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}
// Obtener datos del manifiesto para modificar tiempo de llegada
if (isset($obtenerDatosManifiestoTiempoLlegadaAjax)) {
	$resp = array('success' => true);
	$manifiesto = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $Man_Cod, 'Man_Tip' => 'P')), $obBD_conexion, true);
	if (!empty($manifiesto)) {
		// Extraer fecha y hora de llegada (Man_Fea contiene fecha y hora)
		$man_fea = isset($manifiesto['Man_Fea']) ? $manifiesto['Man_Fea'] : '';
		$fecha_llegada = '';
		$hora_llegada = '';
		
		if (!empty($man_fea)) {
			// Separar fecha y hora
			$parts = explode(' ', $man_fea);
			$fecha_llegada = isset($parts[0]) ? $parts[0] : '';
			$hora_llegada = isset($parts[1]) ? substr($parts[1], 0, 5) : ''; // Solo HH:MM
		}
		
		$resp['manifiesto'] = array(
			'Man_Cod' => $manifiesto['Man_Cod'],
			'Man_Num' => isset($manifiesto['Man_Num']) ? $manifiesto['Man_Num'] : '',
			'Man_Fea_Fecha' => $fecha_llegada,
			'Man_Fea_Hora' => $hora_llegada
		);
	} else {
		$resp['success'] = false;
		$resp['message'] = 'No se encontró el manifiesto o no tiene estado Pendiente (P)';
	}
	$obBD_con1->echoJson($resp);
}

// Modificar tiempo de llegada del manifiesto
if (isset($modificarTiempoLlegadaAjax)) {
	$resp = array('success' => false);
	$obBD_con1->inicio_transaccion($obBD_conexion);
	try {
		// Validar que Man_Cod esté presente
		if (empty($Man_Cod)) {
			throw new Exception('Código de manifiesto no válido');
		}
		
		// Validar que el manifiesto tenga Man_Tip = 'P'
		$manifiesto = $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('manifiesto.Man_Cod' => $Man_Cod, 'Man_Tip' => 'P')), $obBD_conexion, true);
		if (empty($manifiesto)) {
			throw new Exception('Solo se puede modificar la hora de llegada de manifiestos con estado Pendiente (P)');
		}
		
		// Validar que Man_Fea y Man_Fea_Hor estén presentes
		if (/*empty($Man_Fea) ||*/ empty($Man_Fea_Hor)) {
			throw new Exception('Fecha y hora de llegada son requeridas');
		}
		
		// Actualizar fecha y hora de llegada (Man_Fea)
		$datos = array('Man_Fea' => $Man_Fea . ' ' . $Man_Fea_Hor);
		$datos['where'] = array('Man_Cod' => $Man_Cod);
		
		$obBD_con1->operacionobBD('manifiesto.update', $datos, $obBD_conexion);
		
		$resp['success'] = true;
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
		return;
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) {
		$resp['error'] = $obBD_con1->MsgError;
	}
	$obBD_con1->echoJson($resp);
}

if (isset($listaTransporteAjax)) {
	$resul = array('success' => true);
	$resul['trans'] = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('where' => array('manifiesto_transporte.Cli_Cod' => $Cli_Cod)), $obBD_conexion);
	$resul['chof'] = $obBD_con1->getArrayConsulta('manifiesto_chofer.selectWhere', array('where' => array('manifiesto_chofer.Cli_Cod' => $Cli_Cod)), $obBD_conexion);
	$obBD_con1->echoJson($resul);
}
if (isset($recargarVehiculosChoferesAjax)) {
	$resul = array('success' => true);
	
	// Obtener cliente manifiesto para Pla_Cod
	$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
	
	// Obtener manifiestos pendientes (en ruta)
	$man_pendiente = array();
	if (!empty($cliente_manifiesto['Pla_Cod'])) {
		$man_pendiente = $obBD_con1->getArrayConsulta("manifiesto.2", array(/*'Pla_Cod' => $cliente_manifiesto['Pla_Cod'],*/ 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
		$obBD_con1->utf8_change_param($man_pendiente);
	}
	
	// Crear arrays de bloqueados: vehículos por Veh_Pla|Emp_Cod, choferes por Cho_Cod
	$vehiculos_bloqueados = array();
	$choferes_bloqueados = array();
	if (!empty($man_pendiente) && is_array($man_pendiente)) {
		foreach ($man_pendiente as $man) {
			if (isset($man['Veh_Pla']) && isset($man['Emp_Cod']) && $man['Veh_Pla'] !== '' && $man['Emp_Cod'] !== '') {
				$vehiculos_bloqueados[] = $man['Veh_Pla'] . '|' . $man['Emp_Cod'];
			}
			if (isset($man['Cho_Cod']) && !empty($man['Cho_Cod'])) {
				$choferes_bloqueados[] = $man['Cho_Cod'];
			}
		}
	}
	
	// Obtener vehículos
	/*if (empty($cliente_manifiesto)) {
		$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('setWhere' => array('getVehiculo', 'setEmpCod'), 'where' => array('Mat_Est' => 'A')), $obBD_conexion);
	} else {
		$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('setWhere' => array('getVehiculoByPla'), 'where' => array('Mat_Est' => 'A', 'manifiesto_vehiculo.Pla_Cod' => $cliente_manifiesto['Pla_Cod'])), $obBD_conexion);
	}*/
	$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion);													
	$obBD_con1->utf8_change_param($vehiculos);
	
	// Sanciones vigentes de vehículos (todas) para bloquear en la lista
	$sanciones_veh_ajax = $obBD_con1->getArrayConsulta('manifiesto_sanciones.1', array('fecha' => $fecha_hora_actual, 'tipo' => 'VE'), $obBD_conexion);
	$vehiculos_sancionados = array();
	foreach ($sanciones_veh_ajax as $s) {
		if (!empty($s['identi'])) {
			$vehiculos_sancionados[] = $s['identi'];
		}
	}
	
	$resul['vehiculos'] = array();
	$emp_cod_ctx = isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : '';
	if (count($vehiculos) > 0) {
		foreach ($vehiculos as $row) {
			$clave_veh = (isset($row['Veh_Pla']) ? $row['Veh_Pla'] : '') . '|' . $emp_cod_ctx;
			$esta_bloqueado = in_array($clave_veh, $vehiculos_bloqueados);
			$esta_sancionado = in_array($row['Veh_Pla'], $vehiculos_sancionados);
			if ($esta_sancionado) {
				$esta_bloqueado = true;
			}
			$texto_vehiculo = $row['Veh_Pla'] . ' - ' . $row['Veh_Mar'];
			if (/*$esta_bloqueado*/ $row['total']*1 > 0) {
				$texto_vehiculo .= ' << En Ruta >>';
				$esta_bloqueado = true;
			}
			if ($esta_sancionado) {
				$texto_vehiculo .= ' << Sancionado >>';
			}
			$resul['vehiculos'][] = array(
				'Veh_Cod' => $row['Veh_Cod'],
				'Veh_Pla' => $row['Veh_Pla'],
				'Veh_Mar' => $row['Veh_Mar'],
				'Veh_Cap' => $row['Veh_Cap'],
				'Mat_Cod' => $row['Mat_Cod'],
				'Mat_Des' => $row['Mat_Des'],
				'texto' => $texto_vehiculo,
				'bloqueado' => $esta_bloqueado
			);
		}
	}
	
	// Obtener choferes
	$datos = array();
	if (!empty($cliente_manifiesto['Cli_Cod'])) {
		$datos = $obBD_con1->getArrayConsulta('manifiesto_chofer.selectWhere', array('where' => array('Cho_Est' => 'A', 'Cli_Cod' => $cliente_manifiesto['Cli_Cod'])), $obBD_conexion, true);
		$obBD_con1->utf8_change_param($datos);
	}
	
	// Sanciones vigentes de choferes (todas) para bloquear en la lista
	$sanciones_chofer_ajax = $obBD_con1->getArrayConsulta('manifiesto_sanciones.1', array('tipo' => 'CH', 'fecha' => $fecha_hora_actual), $obBD_conexion);
	$choferes_sancionados = array();
	foreach ($sanciones_chofer_ajax as $s) {
		if (!empty($s['Prs_Ced'])) {
			$choferes_sancionados[] = $s['Prs_Ced'];
		}
	}
	// Preparar choferes con estado de bloqueo (en ruta + sancionados)
	$resul['choferes'] = array();
	if (count($datos) > 0) {
		foreach ($datos as $row) {
			$cho_cod = isset($row['Cho_Cod']) ? $row['Cho_Cod'] : 0;
			$esta_bloqueado = in_array($cho_cod, $choferes_bloqueados);
			$esta_sancionado = in_array( $row['Prs_Ced'], $choferes_sancionados);
			if ($esta_sancionado) {
				$esta_bloqueado = true;
			}
			$texto_chofer = $row['nombre'];
			if ($esta_bloqueado) {
				$texto_chofer .= ' << En ruta >>';
			}
			if ($esta_sancionado) {
				$texto_chofer .= ' << Sancionado >>';
			}
			$resul['choferes'][] = array(
				'Cho_Cod' => $row['Cho_Cod'],
				'nombre' => $row['nombre'],
				'Cho_Tli' => $row['Cho_Tli'],
				'Prs_Ced' => $row['Prs_Ced'],
				'texto' => $texto_chofer,
				'bloqueado' => $esta_bloqueado
			);
		}
	}
	
	$obBD_con1->echoJson($resul);
}
if (isset($editTransporteAjax)) {
	$resul = array('success' => true);
	$resul['rows'] = $obBD_con1->getRowConsulta('manifiesto_transporte.selectWhere', array('where' => array('Mat_Cod' => $Mat_Cod)), $obBD_conexion);
	$obBD_con1->echoJson($resul);
}
if (isset($editVehiculoAjax)) {
	$resul = array('success' => true);
	$resul['rows'] = $obBD_con1->getRowConsulta('vehiculo.selectWhere', array('where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
	$obBD_con1->echoJson($resul);
}

if (isset($saveChoferAjax)) {
	$obBD_con1->inicio_transaccion($obBD_conexion);
	$resp = array('success' => false);
	try {
		// Verificar si ya se encontró la persona (viene Prs_Cod del form)
		if (!empty($Prs_Cod)) {
			$Prs_Cod_New = $Prs_Cod;
		} else {
			// Buscar o crear persona por cédula
			$persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $Cho_Ced)), $obBD_conexion);

			if (empty($persona)) {
				// Crear nueva persona
				$datosPersona = array('Prs_Ced' => $Cho_Ced, 'Prs_Nom' => $Prs_Nom, 'Prs_Ape' => $Prs_Ape, 'Prs_Tel' => $Cho_Tel);
				$obBD_con1->operacionobBD('persona.insert', $datosPersona, $obBD_conexion);
				$Prs_Cod_New = $obBD_con1->insercionid($obBD_conexion);
			} else {
				$Prs_Cod_New = $persona['Prs_Cod'];
			}
		}

		// Datos del chofer
		$datosChofer = array(
			'Prs_Cod' => $Prs_Cod_New,
			'Emp_Cod' => $Ses_Emp_Cod,
			'Cho_Tli' => $Cho_Tli,
			'Cho_Cli' => $Cho_Cli,
			'Cho_Tel' => $Cho_Tel,
			'Cho_Tsa' => $Cho_Tsa,
			'Cho_Mae' => isset($Cho_Mae) ? $Cho_Mae : ''
		);

		if (!empty($Cho_Cod)) { //modificar
			$datosChofer['where'] = array('Cho_Cod' => $Cho_Cod);
			$obBD_con1->operacionobBD('chofer.update', $datosChofer, $obBD_conexion);
		} else { // Nuevo registro
			$obBD_con1->operacionobBD('chofer.insert', $datosChofer, $obBD_conexion);
			$resp['Cho_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
		}
		$obBD_con1->operacionobBD('manifiesto_chofer.insert', array('Cho_Cod' => $resp['Cho_Cod_New'], 'Cli_Cod' => $Cli_Cod), $obBD_conexion);
		$resp['nombre'] = $Prs_Nom . ' ' . $Prs_Ape;
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}

if (isset($anularChoferAjax)) {
	$resp = array('success' => false);
	$obBD_con1->inicio_transaccion($obBD_conexion);
	try {
		$obBD_con1->operacionobBD('chofer.update', array('Cho_Est' => 'I', 'where' => array('Cho_Cod' => $Cho_Cod)), $obBD_conexion);
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}

// Buscar persona por cédula
if (isset($buscarPersonaCedulaAjax)) {
	$resp = array('success' => true, 'existe' => false);
	$prsAux = $Prs_Ced;
	$longitud = strlen($prsAux);
	if ($longitud * 1 === 13) {
		$prsAux = substr($prsAux, 0, -3);
	}
	$persona = $obBD_con1->getRowConsulta('persona.selectWhere', array('where' => array('Prs_Ced' => $prsAux)), $obBD_conexion);
	if (!empty($persona)) {
		$resp['existe'] = true;
		$resp['persona'] = $persona;
	}
	$obBD_con1->echoJson($resp);
}

// Cargar plantas por cliente
if (isset($listPlantasAjax)) {
	$resp = array('success' => true);
	$resp['plantas'] = $obBD_con1->getArrayConsulta('manifiesto_plantas.selectWhere', array('where' => array('Cli_Cod' => $Cli_Cod, 'Pla_Est' => 'A')), $obBD_conexion, true);
	$obBD_con1->echoJson($resp);
}

// Guardar planta
if (isset($savePlantaAjax)) {
	$obBD_con1->inicio_transaccion($obBD_conexion);
	$resp = array('success' => false);
	try {
		$datosPlanta = array(
			'Cli_Cod' => $Cli_Cod,
			'Ciu_Cod' => $Ciu_Cod,
			'Pla_Nom' => $Pla_Nom,
			'Pla_Lic' => $Pla_Lic,
			'Pla_Dir' => $Pla_Dir,
			'Pla_Est' => 'A'
		);

		if (!empty($Pla_Cod)) { //modificar
			$datosPlanta['where'] = array('Pla_Cod' => $Pla_Cod);
			$obBD_con1->operacionobBD('manifiesto_plantas.update', $datosPlanta, $obBD_conexion);
		} else { // Nuevo registro
			$obBD_con1->operacionobBD('manifiesto_plantas.insert', $datosPlanta, $obBD_conexion);
			$resp['Pla_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
		}
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}

if (isset($clientesAjax)) {
	$data = $_GET;
	
	// Preparamos los parámetros para el Case 7
	$parametros = array(
		'op_opciones' => $data['op_opciones'],
		'searchCli'   => isset($data['search']) ? $data['search'] : ''
	);

	// Usamos PageGrid con el Query ID 7
	$resultado = $obBD_con1->getPageGrid(7, array_merge($data, $parametros), $obBD_conexion, true);
	$obBD_con1->echoJson($resultado);
}

// Obtener turnos disponibles para una fecha
if (isset($turnosDisponiblesAjax)) {
	$resp = array('success' => true, 'turnos' => array());

	/*
	|--------------------------------------------------------------------------
	| CONFIGURACIÓN DE TURNOS DESDE BASE DE DATOS
	|--------------------------------------------------------------------------
	| Los turnos se obtienen de las tablas:
	| - manifiesto_turnos_cab: Configuración general
	| - manifiesto_turnos_det: Detalle de cada turno (hora_inicio, hora_fin, cupos, activo)
	|--------------------------------------------------------------------------
	*/

	// Obtener fecha - siempre mínimo fecha actual
	$fecha_actual = date('Y-m-d');
	$fecha_buscar = isset($fecha) ? $fecha : $fecha_actual;

	// Validar que la fecha no sea anterior a hoy
	if ($fecha_buscar < $fecha_actual) {
		$fecha_buscar = $fecha_actual;
	}

	// Intentar obtener turnos desde la base de datos
	$turnos_bd = $obBD_con1->getArrayConsulta(
		'manifiesto_turnos_cab.selectWhere',
		array('where' => array('manifiesto_turnos_cab.Emp_Cod' => $Ses_Emp_Cod, 'Tur_Est' => 'A', 'Tud_Fec' => $fecha_buscar), 'setWhere' => array('getTurnosDetalle')),
		$obBD_conexion,
		true
	);

	$horarios_base = array();

	// Verificar si hay turnos configurados
	if (empty($turnos_bd)) {
		// No hay turnos configurados - retornar respuesta indicando esto
		$resp['success'] = false;
		$resp['sin_turnos'] = true;
		$resp['mensaje'] = 'No hay turnos planificados para la fecha seleccionada. Por favor, configure los turnos primero.';
		$resp['fecha'] = $fecha_buscar;
		$resp['fecha_minima'] = $fecha_actual;
		$resp['hora_actual'] = date('H:i');
		$obBD_con1->echoJson($resp);
		return;
	}

	// Usar turnos desde la base de datos
	foreach ($turnos_bd as $turno) {
		$horarios_base[] = array(
			'Tud_Cod' => isset($turno['Tud_Cod']) ? intval($turno['Tud_Cod']) : null,
			'hora_inicio' => substr($turno['hora_inicio'], 0, 5),
			'hora_fin' => substr($turno['hora_fin'], 0, 5),
			'cupos' => intval($turno['cupos']),
			'Cel_Cod' => isset($turno['Cel_Cod']) ? intval($turno['Cel_Cod']) : null,
			'activo' => ($turno['activo'] == '1' || $turno['activo'] === true)
		);
	}

	// Obtener manifiestos ya registrados para esa fecha
	$manifiestos_ocupados = $obBD_con1->getArrayConsulta(
		'manifiesto.selectWhere',
		array('where' => array("DATE(Man_Fes) = '" . $fecha_buscar . "'", 'Man_Est' => 'A')),
		$obBD_conexion,
		true
	);

	// Crear array de conteo por Tud_Cod (clave del turno en manifiesto_turnos_det)
	// El control de cupos ocupados se realiza basándose en la tabla manifiesto y manifiesto_turnos_det por medio del campo Tud_Cod
	$conteo_turnos = array(); // Clave: Tud_Cod, Valor: cantidad de manifiestos
	$detalle_turnos = array(); // Clave: Tud_Cod, Valor: array de detalles

	// Cupos reservados por Tud_Cod y Pla_Cod desde manifiesto_turno_reserva (Pla_Cod, Tud_Cod, Tre_Can, Tre_Est)
	$reservados_por_turno = array();
	$reservados_por_turno_por_planta = array(); // Tud_Cod => Pla_Cod => suma Tre_Can
	$tud_cods_fecha = array();
	foreach ($horarios_base as $h) {
		if (!empty($h['Tud_Cod'])) {
			$tud_cods_fecha[] = intval($h['Tud_Cod']);
		}
	}
	if (!empty($tud_cods_fecha)) {
		$tud_list = implode(',', array_unique($tud_cods_fecha));
		$reservas_turno = $obBD_con1->getArrayConsulta(
			'manifiesto_turno_reserva.selectWhere', array('where' => array("manifiesto_turno_reserva.Tud_Cod IN ($tud_list)", 'Tre_Est' => 'A')), $obBD_conexion, true);
		if (!empty($reservas_turno)) {
			foreach ($reservas_turno as $row) {
				$tid = intval($row['Tud_Cod']);
				$pla = isset($row['Pla_Cod']) ? intval($row['Pla_Cod']) : 0;
				if (!isset($reservados_por_turno[$tid])) $reservados_por_turno[$tid] = 0;
				$reservados_por_turno[$tid] += intval($row['Tre_Can']);
				if ($pla > 0) {
					if (!isset($reservados_por_turno_por_planta[$tid])) $reservados_por_turno_por_planta[$tid] = array();
					if (!isset($reservados_por_turno_por_planta[$tid][$pla])) $reservados_por_turno_por_planta[$tid][$pla] = 0;
					$reservados_por_turno_por_planta[$tid][$pla] += intval($row['Tre_Can']);
				}
			}
		}
	}

	// Planta del usuario (manifiesto_usuario.Pla_Cod = planta dueña) - solo usuarios de esta planta ven reservas
	$pla_usuario = null;
	if (!empty($cliente_manifiesto['Pla_Cod']) && $cliente_manifiesto['Pla_Cod'] !== null && $cliente_manifiesto['Pla_Cod'] !== '') {
		$pla_usuario = intval($cliente_manifiesto['Pla_Cod']);
	}

	// Ocupados por planta (manifiestos ya creados por Tud_Cod y Pla_Cod)
	$ocupados_por_turno_por_planta = array();

	if (!empty($manifiestos_ocupados)) {
		foreach ($manifiestos_ocupados as $man) {
			// Obtener Tud_Cod y Pla_Cod del manifiesto (soportar clave con o sin prefijo de tabla)
			$tud_cod = isset($man['Tud_Cod']) && $man['Tud_Cod'] !== null && $man['Tud_Cod'] !== '' ? intval($man['Tud_Cod']) : null;
			$pla_man = null;
			if (isset($man['Pla_Cod']) && $man['Pla_Cod'] !== null && $man['Pla_Cod'] !== '') {
				$pla_man = intval($man['Pla_Cod']);
			} elseif (isset($man['manifiesto.Pla_Cod']) && $man['manifiesto.Pla_Cod'] !== null && $man['manifiesto.Pla_Cod'] !== '') {
				$pla_man = intval($man['manifiesto.Pla_Cod']);
			}

			// Solo contar manifiestos que tengan Tud_Cod válido (asociados a un turno)
			if ($tud_cod !== null && $tud_cod > 0) {
				$hora_man = isset($man['Man_Fes']) ? date('H:i', strtotime($man['Man_Fes'])) : '';

				// Incrementar contador por Tud_Cod
				if (!isset($conteo_turnos[$tud_cod])) {
					$conteo_turnos[$tud_cod] = 0;
					$detalle_turnos[$tud_cod] = array();
				}
				$conteo_turnos[$tud_cod]++;
				$detalle_turnos[$tud_cod][] = array(
					'cliente' => isset($man['cliente']) ? $man['cliente'] : 'Ocupado',
					'hora' => $hora_man
				);
				// Ocupados por planta (manifiestos de esta planta en este turno)
				if ($pla_man !== null && $pla_man > 0) {
					if (!isset($ocupados_por_turno_por_planta[$tud_cod])) $ocupados_por_turno_por_planta[$tud_cod] = array();
					if (!isset($ocupados_por_turno_por_planta[$tud_cod][$pla_man])) $ocupados_por_turno_por_planta[$tud_cod][$pla_man] = 0;
					$ocupados_por_turno_por_planta[$tud_cod][$pla_man]++;
				}
			}
		}
	}

	// Construir lista de turnos con estado y disponibilidad
	// Los turnos se habilitan cuando:
	// 1. La hora actual está dentro del rango del turno (hora_inicio <= hora_actual < hora_fin)
	// Los turnos con 'activo' => false están bloqueados manualmente
	// Los turnos cuya hora ya pasó (hora_actual >= hora_fin) quedan cerrados

	$hora_actual = date('H:i');

	foreach ($horarios_base as $idx => $horario) {
		$limite_turno = isset($horario['cupos']) ? $horario['cupos'] : 25;
		$turno_activo_config = isset($horario['activo']) ? $horario['activo'] : true;
		$hora_inicio_turno = $horario['hora_inicio'];
		$hora_fin_turno = $horario['hora_fin'];
		$tud_cod = isset($horario['Tud_Cod']) ? intval($horario['Tud_Cod']) : null;

		// Contar cupos ocupados por Tud_Cod (manifiestos ya registrados)
		$ocupados = 0;
		if ($tud_cod !== null && $tud_cod > 0 && isset($conteo_turnos[$tud_cod])) {
			$ocupados = $conteo_turnos[$tud_cod];
		}
		// Cupos reservados totales en manifiesto_turno_reserva (Pla_Cod, Tud_Cod, Tre_Can)
		$reservados_total = 0;
		if ($tud_cod !== null && $tud_cod > 0 && isset($reservados_por_turno[$tud_cod])) {
			$reservados_total = $reservados_por_turno[$tud_cod];
		}
		$reservados_pendientes = $reservados_total - $ocupados;
		if ($reservados_pendientes < 0) $reservados_pendientes = 0;
		// disponibles = cupos libres para TODAS las plantas (incluye restar reservas de otras plantas)
		// Plantas sin reservas ven aquí el valor ya con cupos de reserva restados
		$disponibles = $limite_turno - $ocupados - $reservados_pendientes;
		if ($disponibles < 0) $disponibles = 0;

		// Mostrar info de reservas SOLO a usuarios de la planta dueña (Pla_Cod en manifiesto_turno_reserva)
		$reservados_planta_pendientes = 0;
		$mostrar_reservados = false;
		if ($pla_usuario !== null && $pla_usuario > 0 && $tud_cod !== null && $tud_cod > 0) {
			$reservados_planta = isset($reservados_por_turno_por_planta[$tud_cod][$pla_usuario]) ? (int)$reservados_por_turno_por_planta[$tud_cod][$pla_usuario] : 0;
			$ocupados_planta = isset($ocupados_por_turno_por_planta[$tud_cod][$pla_usuario]) ? (int)$ocupados_por_turno_por_planta[$tud_cod][$pla_usuario] : 0;
			if ($reservados_planta > 0) {
				$mostrar_reservados = true;
				$reservados_planta_pendientes = $reservados_planta - $ocupados_planta;
				if ($reservados_planta_pendientes < 0) $reservados_planta_pendientes = 0;
				// Si no se pudo contar por planta (ocupados_planta=0) pero hay ocupados totales que cubren las reservas, considerar reservas consumidas
				if ($ocupados_planta === 0 && $ocupados > 0 && $ocupados >= $reservados_planta) {
					$reservados_planta_pendientes = 0;
				}
			}
		}

		// Denominador para cupos-ocupados (X/Y ocupados): Tud_Cup para plantas con reservas; Tud_Cup - reservas_total para plantas sin reservas
		$limite_ocupados_display = $limite_turno;
		if (!$mostrar_reservados && $reservados_total > 0) {
			$limite_ocupados_display = $limite_turno - $reservados_total;
			if ($limite_ocupados_display < 0) $limite_ocupados_display = 0;
		}

		// cupos-disponibles: siempre muestran cupos libres (Tud_Cup - ocupados - reservas pendientes)
		$disponibles_display = (int)$disponibles;

		// Determinar el estado del turno
		$estado = 'bloqueado';
		$habilitado = false;
		$hora_actual_alcanzada = false;

		// Si el turno está desactivado en la configuración
		if (!$turno_activo_config) {
			$estado = 'deshabilitado';
			$habilitado = false;
		}
		// Verificar si la hora del turno ya pasó (cerrado)
		else if ($hora_actual >= $hora_fin_turno) {
			$estado = 'cerrado';
			$habilitado = false;
		} else {
			// Verificar si la hora actual está dentro del rango del turno
			// El turno se habilita si la hora actual está dentro de su rango
			$hora_actual_alcanzada = ($hora_actual >= $hora_inicio_turno && $hora_actual < $hora_fin_turno);

			if ($hora_actual_alcanzada) {
				// Si la hora actual está dentro del rango del turno, se habilita
				if ($disponibles > 0) {
					$estado = 'activo';
					$habilitado = true;
				} else {
					$estado = 'lleno';
					$habilitado = false;
				}
			} else {
				// Si la hora actual no está dentro del rango, el turno está bloqueado
				$estado = 'bloqueado';
				$habilitado = false;
			}
		}

		// Obtener detalle de manifiestos para este turno usando Tud_Cod
		$detalle_turno_actual = array();
		if ($tud_cod !== null && $tud_cod > 0 && isset($detalle_turnos[$tud_cod])) {
			$detalle_turno_actual = $detalle_turnos[$tud_cod];
		}

		$turno = array(
			'id' => $idx,
			'Tud_Cod' => isset($horario['Tud_Cod']) ? $horario['Tud_Cod'] : null,
			'Cel_Cod' => isset($horario['Cel_Cod']) ? $horario['Cel_Cod'] : null,
			'hora_inicio' => $horario['hora_inicio'],
			'hora_fin' => $horario['hora_fin'],
			'limite' => $limite_turno,
			'limite_ocupados_display' => $limite_ocupados_display,
			'denominador_barra' => $denominador_barra,
			'ocupados' => $ocupados,
			'ocupados_display' => $ocupados_display,
			'reservados' => $reservados_planta_pendientes,
			'mostrar_reservados' => $mostrar_reservados,
			'disponibles' => $disponibles,
			'disponibles_display' => $disponibles_display,
			'estado' => $estado,
			'habilitado' => $habilitado,
			'turno_activo_config' => $turno_activo_config,
			'habilitado_por_hora' => isset($hora_actual_alcanzada) ? $hora_actual_alcanzada : false,
			'detalle' => $detalle_turno_actual
		);

		$resp['turnos'][] = $turno;
	}

	$resp['fecha'] = $fecha_buscar;
	$resp['fecha_minima'] = $fecha_actual;
	$resp['hora_actual'] = $hora_actual;
	$obBD_con1->echoJson($resp);
}

/* GET Proveedores */
if (isset($cliAjax)) {
	$dataProv = $obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion);
	$obBD_con1->echoJson($dataProv);
}

/* Verificar el numero de Manifiesto ya existe */
if (isset($numeroManiAjax)) {
	$resultado = array(
		'success' => true,
		'numero' => $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Cli_Cod' => $Cli_Cod, 'Man_Num' => $Man_Num)), $obBD_conexion, true),
	);
	$obBD_con1->echoJson($resultado);
}

if (isset($saveManifiestoAjax)) {
	$datos = $_POST;
	$resp = array('success' => false);
	
	$obBD_con1->inicio_transaccion($obBD_conexion);
	try {
		// Validar que la planta no tenga sanción activa (según fecha y hora actual)
		// $Pla_Cod_int = isset($Pla_Cod) ? (int)$Pla_Cod : 0;
        $Pla_Cod_int = (int)$Pla_Cod;
		if ($Pla_Cod_int > 0) {
			$sancion_planta = $obBD_con1->getArrayConsultaSql(
				"SELECT COUNT(*) as total FROM manifiesto_sanciones WHERE Msa_Tip = 'PL' AND Pla_Cod = $Pla_Cod_int AND Msa_Est = 'A' AND NOW() >= Msa_Fei AND NOW() <= Msa_Fef",
				$obBD_conexion
			);
			$cant_sancion = isset($sancion_planta[0]['total']) ? (int)$sancion_planta[0]['total'] : 0;
			if ($cant_sancion > 0) {
				throw new Exception('La planta tiene una sanción activa vigente según fecha y hora actual. No puede realizar manifiestos hasta que finalice el período de sanción.');
			}
		}

		$veh_cap = $obBD_con1->getRowConsulta('manifiesto_vehiculo.selectWhere', array('where' => array('manifiesto_vehiculo.Veh_Cod' => $Veh_Cod)), $obBD_conexion);
		if ($veh_cap !== false && is_array($veh_cap) && !empty($veh_cap)) {
			$Veh_Cap = $veh_cap['Veh_Cap'];
		} else {
			$Veh_Cap = 0;
		}

		// Validar que las horas no estén vacías
		if (empty($Man_Fea_Hor) || trim($Man_Fea_Hor) === '') {
			throw new Exception('La hora de llegada es requerida y no puede estar vacía');
		}
		if (empty($Man_Fes_Hor) || trim($Man_Fes_Hor) === '') {
			throw new Exception('La hora de salida es requerida y no puede estar vacía');
		}

        if (empty($Man_Gui)) throw new Exception('El número de Guía de Remisión es requerido');
		
		$datos = array(
			'Veh_Cod' => $Veh_Cod,
			'Cho_Cod' => $Cho_Cod,
			'Tde_Cod' => $Tde_Cod,
			'Tud_Cod' => $Tud_Cod,
			'Cel_Cod' => $Cel_Cod,
			'Mat_Cod' => $Mat_Cod,
			'Cli_Cod' => $Cli_Cod,
			'Man_Fec' => $Man_Fec . ' ' . $Man_Hor,
			'Man_Dsa' => $Man_Dsa,
			'Man_Dde' => $Man_Dde,		
			'Man_Pes' => $Veh_Cap,//peso del vehiculo
			'Pla_Cod' => $Pla_Cod,
			'Man_Tip' => 'P',
			// 'Man_Gui' => $Man_Gui,
            'Man_Gui' => isset($_POST['Man_Gui']) ? $_POST['Man_Gui'] : $Man_Gui,
			'Man_Fea' => $Man_Fea . ' ' . $Man_Fea_Hor,
			'Man_Fes' => $Man_Fes . ' ' . $Man_Fes_Hor,
			'Man_Lac' => $Man_Lac,
			'Man_Rgd' => $Man_Rgd,
			'Man_Obe' => $Man_Obe,
			'Man_Pun' => '3.00'
		);
		if(empty($Man_Cod) && $Man_Num==''){ // Nuevo registro y no se ha ingresado el numero de manifiesto
			$Man_MaxNum = $obBD_con1->getRowConsulta('manifiesto.1', array('Emp_Cod' => $Ses_Emp_Cod, 'Cli_Cod' => $Cli_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion, true);			
			$datos['Man_Num'] = ($Man_MaxNum['Man_MaxNum'] * 1 + 1);
		} 
		if (!empty($Man_Cod)) { //modificar			
			$datos['where'] = array('Man_Cod' => $Man_Cod);
			$obBD_con1->operacionobBD('manifiesto.update', $datos, $obBD_conexion);
		} else { // Nuevo registro: validar vehículo por placa+empresa y chofer por Cho_Cod
			$aux_message = '';
			$veh_pla = $obBD_con1->getRowConsulta('vehiculo.selectWhere', array('where' => array('Veh_Cod' => $Veh_Cod)), $obBD_conexion);
			$Veh_Pla_val = isset($veh_pla['Veh_Pla']) ? $veh_pla['Veh_Pla'] : '';
			if ($Veh_Pla_val !== '') {
				$verifica_veh = $obBD_con1->getArrayConsulta('manifiesto.4', array('Veh_Pla' => $Veh_Pla_val, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion, true);
				if (!empty($verifica_veh)) {
					$aux_message = 'Vehiculo';
				}
			}
			$verifica_chofer = $obBD_con1->getArrayConsulta('manifiesto.3', array('Veh_Cod' => -1, 'Cho_Cod' => $Cho_Cod), $obBD_conexion, true);
			if (!empty($verifica_chofer)) {
				$aux_message .= $aux_message !== '' ? ' y Chofer' : 'Chofer';
			}
			if ($aux_message !== '') {
				$resp['message'] = 'El ' . $aux_message . ' ya tiene un manifiesto en ruta';
				$obBD_con1->echoJson($resp);
				exit;
			}
			$datos = array_merge($datos, array('Usu_Cod' => $Ses_Usu_Cod));			
			$obBD_con1->operacionobBD('manifiesto.insert', $datos, $obBD_conexion, true);
			$resp['Man_Cod_New'] = $obBD_con1->insercionid($obBD_conexion);
		}

		// Calcular saldos actualizados después de guardar
		$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
		$anticipo = !empty($cliente_manifiesto['Pla_Cod']) ? $obBD_con1->getRowConsulta('manifiesto_anticipo.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion) : array('saldo' => 0);

		$saldo_sin_factura = array('saldo' => 0);
		if (!empty($cliente_manifiesto['Cli_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) {
			try {
				$Cli_Cod = intval($cliente_manifiesto['Cli_Cod']);
				$Emp_Cod = intval($Ses_Emp_Cod);
				$Pla_Cod = intval($cliente_manifiesto['Pla_Cod']);
				$sql_saldo_sin_factura = "SELECT COALESCE(SUM(cast(manifiesto.Man_Pes*(manifiesto.Man_Pun/1000) as decimal(10,2))), 0) as saldo
										  FROM manifiesto
										  INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
										  WHERE manifiesto.Cli_Cod = $Cli_Cod
										  AND manifiesto.Man_Est = 'A'
										  AND cliente.Emp_Cod = $Emp_Cod
										  AND manifiesto.Pla_Cod = $Pla_Cod
										  AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)";
				$result = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_saldo_sin_factura, $obBD_conexion->conexion));
				if ($result && isset($result['saldo'])) {
					$saldo_sin_factura['saldo'] = floatval($result['saldo']);
				}
			} catch (Exception $e) {
				$saldo_sin_factura['saldo'] = 0;
			}
		}

		$saldo_anticipo_val = isset($anticipo['saldo']) ? floatval($anticipo['saldo']) : 0;
		$saldo_sin_factura_val = isset($saldo_sin_factura['saldo']) ? floatval($saldo_sin_factura['saldo']) : 0;
		$saldo_total = $saldo_anticipo_val - $saldo_sin_factura_val;

		$resp['saldos'] = array(
			'anticipo' => $saldo_anticipo_val,
			'sin_factura' => $saldo_sin_factura_val,
			'total' => $saldo_total
		);
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexion);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
	}
	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
	if (!$resp['success']) $resp['error'] = $obBD_con1->MsgError;
	$obBD_con1->echoJson($resp);
}

// Endpoint para obtener saldos actualizados
if (isset($getSaldosAjax)) {
	$resp = array('success' => true);
	$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
	$pla_calc = !empty($cliente_manifiesto['Pla_Cod']) ? intval($cliente_manifiesto['Pla_Cod']) : 0;
	$cli_calc = !empty($cliente_manifiesto['Cli_Cod']) ? intval($cliente_manifiesto['Cli_Cod']) : 0;

	if (isset($_POST['getSaldosPla_Cod'])) {
		$pla_req = intval($_POST['getSaldosPla_Cod']);
		if ($pla_req > 0) {
			$pla_calc = $pla_req;
		}
	}
	if (isset($_POST['getSaldosCli_Cod'])) {
		$cli_req = intval($_POST['getSaldosCli_Cod']);
		if ($cli_req > 0) {
			$cli_calc = $cli_req;
		}
	}

	// Validar que la combinación cliente/planta exista y esté activa para evitar cruces incorrectos.
	if ($cli_calc > 0 && $pla_calc > 0) {
		$planta_cli_valida = $obBD_con1->getRowConsulta(
			'manifiesto_plantas.selectWhere',
			array('where' => array('Cli_Cod' => $cli_calc, 'Pla_Cod' => $pla_calc, 'Pla_Est' => 'A')),
			$obBD_conexion
		);
		if (empty($planta_cli_valida['Pla_Cod'])) {
			$pla_calc = 0;
		}
	}

	$anticipo_params = array('Pla_Cod' => $pla_calc);
	if ($cli_calc > 0) {
		$anticipo_params['Cli_Cod'] = $cli_calc;
	}
	$anticipo = ($pla_calc > 0) ? $obBD_con1->getRowConsulta('manifiesto_anticipo.1', $anticipo_params, $obBD_conexion) : array('saldo' => 0);

	$saldo_sin_factura = array('saldo' => 0);
	if ($cli_calc > 0 && $pla_calc > 0) {
		try {
			$Cli_Cod = $cli_calc;
			$Emp_Cod = intval($Ses_Emp_Cod);
			$Pla_Cod = $pla_calc;
			$sql_saldo_sin_factura = "SELECT COALESCE(SUM(cast(manifiesto.Man_Pes*(manifiesto.Man_Pun/1000) as decimal(10,2))), 0) as saldo
									  FROM manifiesto
									  INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
									  WHERE manifiesto.Cli_Cod = $Cli_Cod
									  AND manifiesto.Man_Est = 'A'
									  AND cliente.Emp_Cod = $Emp_Cod
									  AND manifiesto.Pla_Cod = $Pla_Cod
									  AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)";
			$result = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_saldo_sin_factura, $obBD_conexion->conexion));
			if ($result && isset($result['saldo'])) {
				$saldo_sin_factura['saldo'] = floatval($result['saldo']);
			}
		} catch (Exception $e) {
			$saldo_sin_factura['saldo'] = 0;
		}
	}

	$saldo_anticipo_val = isset($anticipo['saldo']) ? floatval($anticipo['saldo']) : 0;
	$saldo_sin_factura_val = isset($saldo_sin_factura['saldo']) ? floatval($saldo_sin_factura['saldo']) : 0;
	$saldo_total = $saldo_anticipo_val - $saldo_sin_factura_val;

	$resp['saldos'] = array(
		'anticipo' => $saldo_anticipo_val,
		'sin_factura' => $saldo_sin_factura_val,
		'total' => $saldo_total
	);

	$obBD_con1->echoJson($resp);
}

// Endpoint para anular manifiesto
if (isset($anularManifiestoAjax)) {
	$Man_Cod = isset($_POST['Man_Cod']) ? intval($_POST['Man_Cod']) : 0;
	$resp = array('success' => false);

	if (empty($Man_Cod)) {
		$resp['message'] = 'Código de manifiesto no válido';
		$obBD_con1->echoJson($resp);
		exit;
	}

	$obBD_con1->inicio_transaccion($obBD_conexionIns);
	try {
		$datos = array('Man_Est' => 'I');
		$datos['where'] = array('Man_Cod' => $Man_Cod);
		$obBD_con1->operacionobBD('manifiesto.update', $datos, $obBD_conexion);

		// Calcular saldos actualizados después de anular
		$cliente_manifiesto = $obBD_con1->getRowConsulta('manifiesto_usuario.selectWhere', array('where' => array('manifiesto_usuario.Usu_Cod' => $Ses_Usu_Cod)), $obBD_conexion);
		$anticipo = !empty($cliente_manifiesto['Pla_Cod']) ? $obBD_con1->getRowConsulta('manifiesto_anticipo.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion) : array('saldo' => 0);		
		$saldo_sin_factura = array('saldo' => 0);
		if (!empty($cliente_manifiesto['Cli_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) {
			try {
				$Cli_Cod = intval($cliente_manifiesto['Cli_Cod']);
				$Emp_Cod = intval($Ses_Emp_Cod);
				$Pla_Cod = intval($cliente_manifiesto['Pla_Cod']);
				$sql_saldo_sin_factura = "SELECT COALESCE(SUM(cast(manifiesto.Man_Pes*(manifiesto.Man_Pun/1000) as decimal(10,2))), 0) as saldo
										  FROM manifiesto
										  INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
										  WHERE manifiesto.Cli_Cod = $Cli_Cod
										  AND manifiesto.Man_Est = 'A'
										  AND cliente.Emp_Cod = $Emp_Cod
										  AND manifiesto.Pla_Cod = $Pla_Cod
										  AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)";
				$result = $obBD_con1->fetch_assoc($obBD_con1->consulta($sql_saldo_sin_factura, $obBD_conexion->conexion));
				if ($result && isset($result['saldo'])) {
					$saldo_sin_factura['saldo'] = floatval($result['saldo']);
				}
			} catch (Exception $e) {
				$saldo_sin_factura['saldo'] = 0;
			}
		}

		$saldo_anticipo_val = isset($anticipo['saldo']) ? floatval($anticipo['saldo']) : 0;
		$saldo_sin_factura_val = isset($saldo_sin_factura['saldo']) ? floatval($saldo_sin_factura['saldo']) : 0;
		$saldo_total = $saldo_anticipo_val - $saldo_sin_factura_val;

		$resp['saldos'] = array(
			'anticipo' => $saldo_anticipo_val,
			'sin_factura' => $saldo_sin_factura_val,
			'total' => $saldo_total
		);
	} catch (Exception $e) {
		$obBD_con1->rollBack_nomsn($obBD_conexionIns);
		$resp['message'] = $e->getMessage();
		$obBD_con1->echoJson($resp);
		exit;
	}

	$resp['success'] = $obBD_con1->fin_transaccion_nomsn($obBD_conexionIns);
	if (!$resp['success']) {
		$resp['error'] = $obBD_con1->MsgError;
	}

	$obBD_con1->echoJson($resp);
}
if (isset($imprimirAjax)) {
	$responce['success'] = true;
	//$row= $obBD_con1->getRowConsulta('manifiesto.selectWhere', array('where' => array('Man_Cod' => $Man_Cod)), $obBD_conexion, true);
	$row = $obBD_con1->getRowConsulta(6,  array('Man_Cod' => $Man_Cod), $obBD_conexion, true);
	// Formatear fecha en letras
	$fecha_letras = '';
	if (!empty($row['Man_Fec'])) {
		$meses = array(
			1 => 'Enero',
			2 => 'Febrero',
			3 => 'Marzo',
			4 => 'Abril',
			5 => 'Mayo',
			6 => 'Junio',
			7 => 'Julio',
			8 => 'Agosto',
			9 => 'Septiembre',
			10 => 'Octubre',
			11 => 'Noviembre',
			12 => 'Diciembre'
		);
		$fecha = date_create($row['Man_Fec']);
		if ($fecha) {
			$dia = date_format($fecha, 'd');
			$mes = (int)date_format($fecha, 'm');
			$ano = date_format($fecha, 'Y');
			$fecha_letras = $dia . ' de ' . $meses[$mes] . ' de ' . $ano;
		}
	}

	$tabla = array(
		'{Man_Reg}' => isset($row['Man_Reg']) ? $row['Man_Reg'] : '',
		'{Man_Lic}' => isset($row['Man_Lic']) ? $row['Man_Lic'] : '',
		'{Pla_Crd}' => isset($row['Pla_Crd']) ? $row['Pla_Crd'] : '',
		'{Man_Rgd}' => isset($row['Man_Rgd']) ? $row['Man_Rgd'] : '',
		'{Man_Lac}' => isset($row['Man_Lac']) ? $row['Man_Lac'] : '',
		'{Man_Fec}' => isset($row['Man_Fec']) ? $row['Man_Fec'] : '',
		'{Man_Hor}' => isset($row['Man_Hor']) ? $row['Man_Hor'] : '',
		'{Man_Num}' => isset($row['Man_Num']) && isset($row['Pla_Cod']) ? 'M' . $row['Pla_Cod'] . '-' . str_pad($row['Man_Num'], 4, '0', STR_PAD_LEFT) : (isset($row['Man_Num']) ? $row['Man_Num'] : ''),

		'{Man_Fea_Dia}' => isset($row['Man_Fea_Dia']) ? $row['Man_Fea_Dia'] : '',
		'{Man_Fea_Mes}' => isset($row['Man_Fea_Mes']) ? $row['Man_Fea_Mes'] : '',
		'{Man_Fea_Ano}' => isset($row['Man_Fea_Ano']) ? $row['Man_Fea_Ano'] : '',
		'{Man_Fea_Hor}' => isset($row['Man_Fea_Hor']) ? $row['Man_Fea_Hor'] : '',
		'{Man_Fes_Dia}' => isset($row['Man_Fes_Dia']) ? $row['Man_Fes_Dia'] : '',
		'{Man_Fes_Mes}' => isset($row['Man_Fes_Mes']) ? $row['Man_Fes_Mes'] : '',
		'{Man_Fes_Ano}' => isset($row['Man_Fes_Ano']) ? $row['Man_Fes_Ano'] : '',
		'{Man_Fes_Hor}' => isset($row['Man_Fes_Hor']) ? $row['Man_Fes_Hor'] : '',

		'{Man_Fec_Let}' => $fecha_letras,
		'{cliente}' => isset($row['cliente']) ? $row['cliente'] : '',
		'{Cli_Prs_Ced}' => isset($row['Cli_Prs_Ced']) ? $row['Cli_Prs_Ced'] : '',
		'{Pla_Nom}' => isset($row['Pla_Nom']) ? $row['Pla_Nom'] : '',
		'{Prs_Ced}' => isset($row['Prs_Ced']) ? $row['Prs_Ced'] : '',
		'{Prs_Dir}' => isset($row['Prs_Dir']) ? $row['Prs_Dir'] : '',
		'{Prs_Tel}' => isset($row['Prs_Tel']) ? $row['Prs_Tel'] : '',
		'{Prs_Cor}' => isset($row['Prs_Cor']) ? $row['Prs_Cor'] : '',
		'{Ciu_Des}' => isset($row['Ciu_Des']) ? $row['Ciu_Des'] : '',
		'{Pro_Nom}' => isset($row['Pro_Nom']) ? $row['Pro_Nom'] : '',
		'{Tde_Cde}' => isset($row['Tde_Cde']) ? $row['Tde_Cde'] : '',
		//Usuario
		'{usuario_nombre}' => isset($row['usuario_nombre']) ? $row['usuario_nombre'] : '',
		'{usuario_cedula}' => isset($row['usuario_cedula']) ? $row['usuario_cedula'] : '',
		'{usuario_correo}' => isset($row['usuario_correo']) ? $row['usuario_correo'] : '',
		'{usuario_telefono}' => isset($row['usuario_telefono']) ? $row['usuario_telefono'] : '',
		'{Tde_Des}' => isset($row['Tde_Des']) ? $row['Tde_Des'] : '',
		'{Tde_Cas}' => isset($row['Tde_Cas']) ? $row['Tde_Cas'] : '',
		'{Veh_Tit}' => isset($row['Veh_Tit']) ? $row['Veh_Tit'] : '',
		'{Veh_Tip}' => isset($row['Veh_Tip']) ? $row['Veh_Tip'] : '',
		'{Veh_Cap}' => isset($row['Veh_Cap']) ? $row['Veh_Cap'] : '',
		'{Veh_Mar}' => isset($row['Veh_Mar']) ? $row['Veh_Mar'] : '',
		'{Veh_Pla}' => isset($row['Veh_Pla']) ? $row['Veh_Pla'] : '',
		'{Veh_Mod}' => isset($row['Veh_Mod']) ? $row['Veh_Mod'] : '',
		'{Man_Pes}' => isset($row['Man_Pes']) ? $row['Man_Pes'] : '',
		// '{Man_Fea}' => isset($row['Man_Fea']) ? $row['Man_Fea'] : '',
		// '{Man_Fea_Hor}' => isset($row['Man_Fea_Hor']) ? $row['Man_Fea_Hor'] : '',
		// '{Man_Fes}' => isset($row['Man_Fes']) ? $row['Man_Fes'] : '',
		// '{Man_Fes_Hor}' => isset($row['Man_Fes_Hor']) ? $row['Man_Fes_Hor'] : '',
		'{Man_Obe}' => isset($row['Man_Obe']) ? $row['Man_Obe'] : '',
		// Transporte
		'{Mat_Des}' => isset($row['Mat_Des']) ? $row['Mat_Des'] : '',
		'{Mat_Dir}' => isset($row['Mat_Dir']) ? $row['Mat_Dir'] : '',
		'{Mat_Tel}' => isset($row['Mat_Tel']) ? $row['Mat_Tel'] : '',
		'{Mat_Mae}' => isset($row['Mat_Mae']) ? $row['Mat_Mae'] : '',
		'{Mat_Pco}' => isset($row['Mat_Pco']) ? $row['Mat_Pco'] : '',
		// Chofer
		'{chofer}' => isset($row['chofer']) ? $row['chofer'] : '',
		'{Cho_Tsa}' => isset($row['Cho_Tsa']) ? $row['Cho_Tsa'] : '',
		'{Cho_Cel}' => isset($row['Cho_Cel']) ? $row['Cho_Cel'] : '',
		'{Cho_Cor}' => isset($row['Cho_Cor']) ? $row['Cho_Cor'] : '',
		'{Cho_Tli}' => isset($row['Cho_Tli']) ? $row['Cho_Tli'] : '',
		// Ruta
		/*'{Ruta_Prov}'=>isset($row['Ruta_Prov']) ? $row['Ruta_Prov'] : '',
		'{Ruta_Cam}'=>isset($row['Ruta_Cam']) ? $row['Ruta_Cam'] : '',*/
		'{cho_ciu_prov}' => isset($row['cho_ciu_prov']) ? $row['cho_ciu_prov'] : '',
		'{Pla_Rut}' => isset($row['Pla_Rut']) ? $row['Pla_Rut'] : '',
		// Báscula
		'{Bas_Nro}' => isset($row['Bas_Nro']) ? $row['Bas_Nro'] : '',
		'{Bas_Ubi}' => isset($row['Bas_Ubi']) ? $row['Bas_Ubi'] : '',
		'{Bas_Cap}' => isset($row['Bas_Cap']) ? $row['Bas_Cap'] : '',
		'{Bas_Tec}' => isset($row['Bas_Tec']) ? $row['Bas_Tec'] : '',
		'{Bas_Car}' => isset($row['Bas_Car']) ? $row['Bas_Car'] : '',
		'{Bas_Tel}' => isset($row['Bas_Tel']) ? $row['Bas_Tel'] : '',
		'{Bas_Cor}' => isset($row['Bas_Cor']) ? $row['Bas_Cor'] : '',
		// Destinatario
		'{Des_Emp}' => isset($row['Des_Emp']) ? $row['Des_Emp'] : '',
		'{Des_Lic}' => isset($row['Des_Lic']) ? $row['Des_Lic'] : '',
		'{Des_Dir}' => isset($row['Des_Dir']) ? $row['Des_Dir'] : '',
		'{Des_Can}' => isset($row['Des_Can']) ? $row['Des_Can'] : '',
		'{Des_Pro}' => isset($row['Des_Pro']) ? $row['Des_Pro'] : '',
		'{Des_Par}' => isset($row['Des_Par']) ? $row['Des_Par'] : '',
		'{Des_Obs}' => isset($row['Des_Obs']) ? $row['Des_Obs'] : '',
		'{Des_Tra}' => isset($row['Des_Tra']) ? $row['Des_Tra'] : '',
		'{Des_Dis}' => isset($row['Des_Dis']) ? $row['Des_Dis'] : '',
		// Técnico
		'{Tec_Nom}' => isset($row['tecnico_nombre']) ? $row['tecnico_nombre'] : '',
		'{Tec_Car}' => isset($row['Tec_Car']) ? $row['Tec_Car'] : '',
		'{Tec_Jor}' => isset($row['Tec_Jor']) ? $row['Tec_Jor'] : '',
		// Depósito
		'{Dep_Nro}' => isset($row['Dep_Nro']) ? $row['Dep_Nro'] : '',
		'{Dep_Nom}' => isset($row['Dep_Nom']) ? $row['Dep_Nom'] : '',
		'{Dep_Cod}' => isset($row['Dep_Cod']) ? $row['Dep_Cod'] : '',
		'{Dep_Des}' => isset($row['Dep_Des']) ? $row['Dep_Des'] : '',
		// Recepción
		'{Rec_Obs}' => isset($row['Rec_Obs']) ? $row['Rec_Obs'] : '',
		'{Rec_Nom}' => isset($row['Rec_Nom']) ? $row['Rec_Nom'] : '',
		'{Rec_Car}' => isset($row['Rec_Car']) ? $row['Rec_Car'] : '',

		'{Rec_Dia}' => isset($row['Rec_Dia']) ? $row['Rec_Dia'] : '',
		'{Rec_Mes}' => isset($row['Rec_Mes']) ? $row['Rec_Mes'] : '',
		'{Rec_Ano}' => isset($row['Rec_Ano']) ? $row['Rec_Ano'] : '',
		// Campos Manifiesto Técnico
		'{Mat_Dna}' => isset($row['Mat_Dna']) ? $row['Mat_Dna'] : '',
		'{Mat_Tra}' => isset($row['Mat_Tra_Des']) ? $row['Mat_Tra_Des'] : (isset($row['Mat_Tra']) ? $row['Mat_Tra'] : ''),
		'{Mat_Ear}' => isset($row['Mat_Ear_Des']) ? $row['Mat_Ear_Des'] : (isset($row['Mat_Ear']) ? $row['Mat_Ear'] : ''),
		'{Mat_Eae}' => isset($row['Mat_Eae_Des']) ? $row['Mat_Eae_Des'] : (isset($row['Mat_Eae']) ? $row['Mat_Eae'] : ''),
		'{Mat_Oce}' => isset($row['Mat_Oce']) ? $row['Mat_Oce'] : '',
		'{Hum_Des}' => isset($row['Hum_Des']) ? $row['Hum_Des'] : '',
		'{Hum_Rie}' => isset($row['Hum_Rie']) ? $row['Hum_Rie'] : '',
		'{Mat_Nce}' => isset($row['Mat_Nce']) ? $row['Mat_Nce'] : '',
		'{Mat_Cce}' => isset($row['Mat_Cce']) ? $row['Mat_Cce'] : '',
		'{Mat_Dce}' => isset($row['Mat_Dce']) ? $row['Mat_Dce'] : ''
	);

	if ($tipo == 'admin') {
		$responce['tabla'] = reporteHtml($tabla, __DIR__ . '/man_pri_manifiesto_adm.html');
	} elseif ($tipo == 'ticket') {
		// Generar código QR para el ticket
		$cliente_nombre = isset($row['cliente']) ? $row['cliente'] : '';
		$man_cod = isset($row['Man_Cod']) ? $row['Man_Cod'] : '';
		$man_num = isset($row['Man_Num']) ? $row['Man_Num'] : '';

		// Crear JSON con la información del QR: nombre del Cliente, Man_Cod, Man_Num
		// Compatible con PHP 5.3.8 (sin JSON_UNESCAPED_UNICODE que fue introducido en PHP 5.4)
		$qr_data = array(
			'cliente' => $cliente_nombre,
			'Man_Cod' => $man_cod,
			'Man_Num' => $man_num
		);
		// PHP 5.3.8 compatible: json_encode sin flags (JSON_UNESCAPED_UNICODE no existe en PHP 5.3.8)
		$qr_string = json_encode($qr_data);

		// Inicializar variable para el QR en base64
		$qr_base64 = '';

		// Verificar que la clase QRcode existe (es una clase, no una función)
		if (class_exists('QRcode')) {
			// Verificar que las constantes estén definidas
			// if (!defined('QR_ECLEVEL_L')) {
			// 	define('QR_ECLEVEL_L', 0);
			// }

			// Intentar generar el QR directamente en memoria (más confiable)
			ob_start();
			// @QRcode::png($qr_string, false, QR_ECLEVEL_L, 6, 2);
			@QRcode::png($qr_string, false, 0, 6, 2);
			$qr_image_data = ob_get_contents();
			ob_end_clean();

			// Verificar que se generó correctamente (debe tener al menos 100 bytes)
			if (!empty($qr_image_data) && strlen($qr_image_data) > 100) {
				$qr_base64 = 'data:image/png;base64,' . base64_encode($qr_image_data);
			} /*else {
				// Si falló la generación en memoria, intentar con archivo temporal
				// Directorio para guardar el QR temporal
				$qr_dir = __DIR__ . '/../TEMP/';
				if (!is_dir($qr_dir)) {
					if (!@mkdir($qr_dir, 0777, true)) {
						$qr_dir = sys_get_temp_dir() . '/';
					}
				}

				// Nombre único para el archivo QR
				$qr_filename = 'qr_' . $man_cod . '_' . time() . '_' . rand(1000, 9999) . '.png';
				$qr_path = $qr_dir . $qr_filename;

				// Intentar generar en archivo
				@QRcode::png($qr_string, $qr_path, QR_ECLEVEL_L, 6, 2);

				if (file_exists($qr_path) && filesize($qr_path) > 100) {
					$qr_image_data = @file_get_contents($qr_path);
					if ($qr_image_data !== false && strlen($qr_image_data) > 100) {
						$qr_base64 = 'data:image/png;base64,' . base64_encode($qr_image_data);
					}
					// Eliminar el archivo temporal
					@unlink($qr_path);
				}
			}*/
		}

		// Si todo falla, usar un placeholder (imagen transparente pequeña)
		if (empty($qr_base64)) {
			// $qr_base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
			// Usamos GoQR.me que es rápido y no requiere registro
			$qr_base64 = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_string);
		}

		// Agregar el código QR y los datos necesarios al array de datos
		// Incluir todos los campos del array $tabla y agregar los específicos del ticket
		$tabla_ticket = $tabla;
		$tabla_ticket['{QR_CODE_IMAGE}'] = $qr_base64;
		$tabla_ticket['{fecha_impresion}'] = date('d/m/Y H:i:s');
		// Asegurar que los campos principales estén presentes
		$tabla_ticket['{cliente}'] = $cliente_nombre;
		$tabla_ticket['{Man_Cod}'] = $man_cod;
		$tabla_ticket['{Man_Num}'] = $man_num;
		$tabla_ticket['{Man_Fec}'] = isset($row['Man_Fec']) ? $row['Man_Fec'] : '';
		$tabla_ticket['{Man_Hor}'] = isset($row['Man_Hor']) ? $row['Man_Hor'] : '';
		$tabla_ticket['{estado}'] = isset($row['estado']) ? $row['estado'] : '';
		$tabla_ticket['{Cli_Ced}'] = isset($row['Cli_Ced']) ? $row['Cli_Ced'] : '';
		$tabla_ticket['{chofer}'] = isset($row['chofer']) ? $row['chofer'] : '';
		$tabla_ticket['{Veh_Pla}'] = isset($row['Veh_Pla']) ? $row['Veh_Pla'] : '';
		$tabla_ticket['{Mat_Des}'] = isset($row['Mat_Des']) ? $row['Mat_Des'] : '';
		$tabla_ticket['{Tde_Des}'] = isset($row['Tde_Des']) ? $row['Tde_Des'] : '';
		$tabla_ticket['{Man_Pes}'] = isset($row['Man_Pes']) ? $row['Man_Pes'] : '';
		$tabla_ticket['{total}'] = isset($row['total']) ? $row['total'] : '';

		$responce['tabla'] = reporteHtml($tabla_ticket, __DIR__ . '/man_pri_ticket_qr.php');
	} else {
		$responce['tabla'] = reporteHtml($tabla, __DIR__ . '/man_pri_manifiesto_clie.html');
	}
	$obBD_con1->echoJson($responce);
}

if (isset($printCertificadoAjax)) {
	$responce['success'] = true;
	$row = $obBD_con1->getRowConsulta(10, array('Man_Cod' => $Man_Cod), $obBD_conexion, true);

	// Procesar imágenes para Base64 (Carga instantánea)
	if (!isset($_SESSION['Cert_Logos'])) {
		$img_dir = __DIR__ . '/../../imagenes/620/';
		$_SESSION['Cert_Logos'] = array(
			'pre' => file_exists($img_dir . 'prefectua.png') ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_dir . 'prefectua.png')) : '',
			'rel' => file_exists($img_dir . 'relavera.png') ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_dir . 'relavera.png')) : '',
			'com' => file_exists($img_dir . 'logo-completo.png') ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_dir . 'logo-completo.png')) : ''
		);
	}
	$logo_pre = $_SESSION['Cert_Logos']['pre'];
	$logo_rel = $_SESSION['Cert_Logos']['rel'];
	$logo_com = $_SESSION['Cert_Logos']['com'];

	$tabla = array(
		'{logo_prefectura}' => $logo_pre,
		'{logo_relavera}' => $logo_rel,
		'{logo_completo}' => $logo_com,
		'{watermark}' => $logo_rel,
		'{Man_Num}' => isset($row['Man_Num_Full']) ? $row['Man_Num_Full'] : '',
		'{Man_Fec_Ano}' => isset($row['Man_Fec_Ano']) ? $row['Man_Fec_Ano'] : '',
		'{Man_Fec}' => isset($row['Man_Fec']) ? $row['Man_Fec'] : '',
		'{Man_Hor}' => isset($row['Man_Hor']) ? $row['Man_Hor'] : '',
		'{Suc_Dir}' => isset($row['Suc_Dir']) ? $row['Suc_Dir'] : '',
		'{Des_Lic}' => isset($row['Des_Lic']) ? $row['Des_Lic'] : '',
		'{cliente}' => isset($row['cliente']) ? $row['cliente'] : '',
		'{Pla_Car}' => isset($row['Pla_Car']) ? $row['Pla_Car'] : '',
		'{usuario}' => isset($row['usuario']) ? $row['usuario'] : '',
		'{Prs_Dir}' => isset($row['Prs_Dir']) ? $row['Prs_Dir'] : '',
		'{Prs_Tel}' => isset($row['Prs_Tel']) ? $row['Prs_Tel'] : '',
		'{Mat_Des}' => isset($row['Mat_Des']) ? $row['Mat_Des'] : '',
		'{Mat_Mae}' => isset($row['Mat_Mae']) ? $row['Mat_Mae'] : '',
		'{chofer}' => isset($row['chofer']) ? $row['chofer'] : '',
		'{Cho_Cor}' => isset($row['Cho_Cor']) ? $row['Cho_Cor'] : '',
		'{Tde_Des}' => isset($row['Tde_Des']) ? $row['Tde_Des'] : '',
		'{Tde_Cde}' => isset($row['Tde_Cde']) ? $row['Tde_Cde'] : '',
		'{Man_Pes}' => isset($row['Man_Pes']) ? $row['Man_Pes'] : '',
		'{Rec_Nom}' => isset($row['Rec_Nom']) ? $row['Rec_Nom'] : '',
		'{Rec_Car}' => isset($row['Rec_Car']) ? $row['Rec_Car'] : '',
		'{Emp_Nom}' => isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : ''
	);

	// --- Lógica de Firma Digital y Marca de Agua ---
    $signature_section = '';
    $draft_watermark = '';
    
    if ($firmar_solo_si) {
        // Buscar llave electrónica
        $sql_llave = "SELECT Lla_Rut, Lla_Cla, Lla_Cad FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = $Ses_Emp_Cod";
        $res_llave = $obBD_con1->consulta($sql_llave, $obBD_conexion->conexion);
        $llave = $obBD_con1->fetch_assoc($res_llave);
        
        $firma_aplicada = false;
        if ($llave && !empty($llave['Lla_Rut'])) {
            $ruta_p12 = $APP_REAL_PATH . "/facturacion/FRONT/$Ses_Emp_Cod/" . $llave['Lla_Rut'];
            $password = $llave['Lla_Cla'];
            
            if (file_exists($ruta_p12)) {
                $p12_data = file_get_contents($ruta_p12);
                $certs = array();
                if (openssl_pkcs12_read($p12_data, $certs, $password)) {
                    $cert_info = openssl_x509_parse($certs['cert']);
                    $nombre_firmante = isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : 'Firmante Autorizado';
                    $entidad_cert = isset($cert_info['issuer']['O']) ? $cert_info['issuer']['O'] : 'Entidad Certificadora';
                    
                    // Generar QR para la firma (Lógica con Fallback)
                    $qr_sig_data = "Firmado electr\u00f3nicamente por: $nombre_firmante\nFecha: " . (isset($row['Man_Fec']) ? $row['Man_Fec'] : date('Y-m-d')) . "\nEntidad: $entidad_cert\nValidar en: www.firmadigital.gob.ec";
                    $qr_src = '';
                    
                    if (class_exists('QRcode')) {
                        ob_start();
                        @QRcode::png($qr_sig_data, false, 0, 6, 2);
                        $qr_img_bin = ob_get_contents();
                        ob_end_clean();
                        if (!empty($qr_img_bin)) {
                            $qr_src = 'data:image/png;base64,' . base64_encode($qr_img_bin);
                        }
                    }
                    
                    // Si no hay librería local, usamos la API externa de GoQR.me
                    if (empty($qr_src)) {
                        $qr_src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_sig_data);
                    }
                    
                    $signature_section = "
                        <div class='signature-box'>
                            <div class='signature-box-content'>
                                <img src='$qr_src' class='signature-qr'>
                                <div class='signature-details'>
                                    <span class='label'>Firmado electr&oacute;nicamente por:</span>
                                    <span class='name'>" . strtoupper($nombre_firmante) . "</span>
                                    <span class='check'>Validar documento con FirmaEC</span>
                                </div>
                            </div>
                            <div class='signature-line-bottom'></div>
                            <div class='signature-company'>" . (isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : '') . "</div>
                        </div>";
                    $firma_aplicada = true;
                }
            }
        }
        
        if (!$firma_aplicada) {
            // Si no hay firma digital pero el perfil es autorizado, mostrar firma manual similar al estilo
            $signature_section = "
                <div class='signature-box'>
                    <div style='height: 80px;'></div>
                    <div class='signature-line-bottom'></div>
                    <div class='signature-company'>" . (isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : '') . "</div>
                </div>";
        }
    } else {
        // Usuario no autorizado para firmar -> Mostrar BORRADOR y estilo base
        $draft_watermark = "<div class='draft-watermark'>BORRADOR</div>";
        $signature_section = "
            <div class='signature-box' style='opacity: 0.4;'>
                <div style='height: 80px; text-align: center; padding-top: 30px; font-weight: bold; color: #999;'>SIN FIRMA AUTORIZADA</div>
                <div class='signature-line-bottom'></div>
                <div class='signature-company'>" . (isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : '') . "</div>
            </div>";
    }

    $tabla['{signature_section}'] = $signature_section;
    $tabla['{draft_watermark}'] = $draft_watermark;

	try {
		$responce['tabla'] = reporteHtml($tabla, __DIR__ . '/man_pri_certficado.html');
	} catch (Exception $e) {
		$responce['success'] = false;
		$responce['message'] = $e->getMessage();
		$responce['path'] = __DIR__ . '/man_pri_certficado.html';
	}
	$obBD_con1->echoJson($responce);
}

/**************** CODIGO JOSE ********************/

/* Tipos de pago */
$tPagos = $obBD_con1->getArrayConsulta('tipos_pago.selectWhere', array('where' => array("Pag_Abr='EFE' OR Pag_Abr='CHE' OR Pag_Abr='TRF' OR Pag_Abr='DEP'")), $obBD_conexion);
// /* Perfiles */
// $perfil = $obBD_con1->getArrayConsulta('perfiles.selectWhere', array('where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Usu_Cod' => $Ses_Usu_Cod), 'setWhere' => array('getPerfil')), $obBD_conexion);
// utf8_encode_deep($perfil);
// /* Periodos */
// $periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
// utf8_encode_deep($periodos);

// // Validar si el usuario tiene permiso para ver el botón de certificado
// $perfiles_permitidos = array('Administrador de Sistemas', 'Gerente', 'Admin_Oper', 'Contador', 'Auditor');
// $mostrarBotonCertificado = false;
// foreach ($perfil as $p) {
// 	if (in_array(trim($p['Per_Des']), $perfiles_permitidos)) {
// 		$mostrarBotonCertificado = true;
// 		break;
// 	}
// }

// // Configuración de firma según perfil
// $firmar_solo_si = false;
// $firmar_solo_no = false;
// foreach ($perfil as $p) {
//     $per_desc = trim($p['Per_Des']);
//     if ($per_desc == 'Gerente' || $per_desc == 'Contador') {
//         $firmar_solo_si = true;
//     }
//     if ($per_desc == 'Admin_Oper') {
//         $firmar_solo_no = true;
//     }
// }

/**
 * Anular Anticipo
 */
if (isset($anularAnticipo)) {
	$resp = array('success' => false);
	$obBD_ins1 = new  Class_Log_Datos_Ant_Prv;
	$obBD_conexionIns = new Class_Log_Conexion_Global($Ses_Dat_Dis);
	//$obBD_ins1->debug(true);
	$obBD_ins1->debugLogs(false);
	$obBD_ins1->inicio_transaccion($obBD_conexionIns);
	//$obBD_ins1->echoLog('PHP ANULAR ANTICIPO');
	try {

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



?>
<!DOCTYPE html>
<HTML>

<HEAD>
	<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?Php echo "Manifiesto [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
	<script src="../../framework/jquery/jquery.mask/jquery.mask.min.js"></script>
	<script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
	<script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
	<script>
		var Cli_Cod_Man = '<?php echo $cliente_manifiesto['Cli_Cod']; ?>';
		var peridodo = <?php echo json_encode($periodos) ?>,
			prf = <?php echo json_encode($perfil) ?>;
		var plantasSaldosModal = <?php echo json_encode($plantas_saldos_modal); ?>;
		var hoy= <?php echo json_encode($hoy); ?>;
		var esPerfilPlanta = <?php echo json_encode($esPerfilPlanta); ?>;
		var infoPlantaCertificado = <?php echo json_encode($infoPlantaCertificado); ?>;
	</script>
	<style>
		.pagination>li>a,
		.pagination>li>span {
			padding: 4px 2px;
		}
		/* Indicador visual para nuevos campos obligatorios */
		.new-field-indicator {
			display: inline-block;
			margin-left: 6px;
			font-size: 1.1em;
			vertical-align: middle;
			color: #f0ad4e;
			animation: newFieldPulse 1.2s ease-in-out infinite;
		}
		.new-field-indicator[title] {
			cursor: help;
		}
		@keyframes newFieldPulse {
			0%, 100% { transform: scale(1); opacity: 0.85; }
			50% { transform: scale(1.2); opacity: 1; }
		}

		.pagination {
			/*display: block;*/
			margin: 0;
			padding: 0;
		}

		.chosen-default span,
		.chosen-single span {
			color: #555;
		}

		.chosen-single span {
			padding-left: 5px;
		}

		/* Barra compacta de control de saldos por planta */
		.saldos-toolbar {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			flex-wrap: wrap;
			padding: 3px 6px;
			border: 1px solid #d9e2ef;
			border-radius: 6px;
			background: #f8fbff;
		}

		.saldos-toolbar .btn {
			height: 24px;
			padding: 2px 7px;
			font-size: 11px;
			line-height: 1.2;
		}

		#lblPlantaSaldosActiva {
			max-width: 280px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			font-size: 10px;
			padding: 4px 6px;
			border-radius: 4px;
			background: #3aa3cf;
		}

		/* Filtro compacto del modal de plantas para saldos */
		.planta-filtro-wrap {
			margin: 6px 0 10px 0;
			padding: 8px 10px;
			border: 1px solid #d5e0ee;
			border-radius: 6px;
			background: #f7faff;
		}

		.planta-filtro-label {
			display: block;
			margin: 0 0 4px 0;
			font-size: 11px;
			font-weight: 600;
			color: #3a5876;
			letter-spacing: 0.2px;
		}

		.planta-filtro-wrap .input-group-addon {
			padding: 4px 8px;
			background: #eef4fb;
			border-color: #c9d7e8;
			color: #4d6b8a;
		}

		#txtBuscarPlantaSaldos {
			height: 28px;
			font-size: 12px;
			border-color: #c9d7e8;
		}

		/* Compactar jqGrid del modal de saldos */
		#gview_tablaPlantasSaldos .ui-jqgrid-htable th,
		#gview_tablaPlantasSaldos .ui-jqgrid-btable tr.jqgrow td {
			font-size: 11px;
			padding: 2px 4px;
			height: 22px;
		}

		#gview_tablaPlantasSaldos .ui-jqgrid-title {
			font-size: 12px;
		}

		#gview_tablaPlantasSaldos .btn.btn-xs {
			padding: 1px 5px;
			font-size: 10px;
			line-height: 1.1;
		}

		#wrapGridPlantasSaldos .ui-jqgrid,
		#wrapGridPlantasSaldos .ui-jqgrid-view {
			box-sizing: border-box;
		}

		/* Estilos para modal de turnos */
		.turno-card {
			border: 2px solid #ddd;
			border-radius: 8px;
			padding: 12px;
			margin-bottom: 10px;
			transition: all 0.3s ease;
			background: linear-gradient(145deg, #ffffff, #f5f5f5);
		}

		.turno-card:hover {
			transform: translateY(-2px);
		}

		/* Turno ACTIVO - el que está disponible para seleccionar */
		.turno-card.disponible.activo {
			background: linear-gradient(145deg, #d4edda, #b8dacd);
			border-color: #28a745;
			cursor: pointer;
			box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
			animation: pulseGreen 2s infinite;
		}

		.turno-card.disponible.activo:hover {
			border-color: #1e7e34;
			box-shadow: 0 4px 20px rgba(40, 167, 69, 0.5);
		}

		@keyframes pulseGreen {
			0% {
				box-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
			}

			50% {
				box-shadow: 0 0 20px rgba(40, 167, 69, 0.6);
			}

			100% {
				box-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
			}
		}

		/* Turno LLENO - ya se completaron los 25 cupos */
		.turno-card.lleno {
			background: linear-gradient(145deg, #e2e3e5, #d6d8db);
			border-color: #6c757d;
			cursor: not-allowed;
			opacity: 0.8;
		}

		.turno-card.lleno:hover {
			transform: none;
			box-shadow: none;
		}

		/* Turno BLOQUEADO - esperando que se llene el anterior */
		.turno-card.bloqueado {
			background: linear-gradient(145deg, #f8f9fa, #e9ecef);
			border-color: #adb5bd;
			cursor: not-allowed;
			opacity: 0.6;
		}

		.turno-card.bloqueado:hover {
			transform: none;
			box-shadow: none;
		}

		/* Turno DESHABILITADO - bloqueado manualmente por configuración */
		.turno-card.deshabilitado {
			background: linear-gradient(145deg, #f8d7da, #f1b0b7);
			border-color: #dc3545;
			cursor: not-allowed;
			opacity: 0.5;
		}

		.turno-card.deshabilitado:hover {
			transform: none;
			box-shadow: none;
		}

		.turno-card.deshabilitado .turno-hora {
			color: #721c24;
			text-decoration: line-through;
		}

		/* Turno CERRADO - la hora ya pasó */
		.turno-card.cerrado {
			background: linear-gradient(145deg, #d1d3e2, #b7b9c9);
			border-color: #858796;
			cursor: not-allowed;
			opacity: 0.6;
		}

		.turno-card.cerrado:hover {
			transform: none;
			box-shadow: none;
		}

		.turno-card.cerrado .turno-hora {
			color: #5a5c69;
			text-decoration: line-through;
		}

		.turno-card.cerrado .turno-progress-bar {
			background: #858796;
		}

		.turno-card.bloqueado .turno-hora,
		.turno-card.bloqueado .turno-cupos {
			color: #adb5bd;
		}

		.turno-hora {
			font-size: 16px;
			font-weight: bold;
			color: #333;
		}

		.turno-estado {
			font-size: 11px;
			padding: 3px 10px;
			border-radius: 12px;
			display: inline-block;
			font-weight: bold;
		}

		.badge-activo {
			background-color: #28a745;
			color: white;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
		}

		.badge-inactivo {
			background-color: rgb(247, 57, 57);
			color: white;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
		}

		.badge-facturado {
			background-color: rgb(171, 111, 228);
			color: white;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
		}

		.badge-garita-in {
			background-color: rgb(255, 193, 7);
			color: #333;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
			font-weight: bold;
		}

		.badge-garita-out {
			background-color: rgb(23, 162, 184);
			color: white;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
			font-weight: bold;
		}

		.turno-estado.libre {
			background-color: #28a745;
			color: white;
		}

		.turno-estado.medio {
			background-color: #28a745;
			color: white;
		}

		.turno-estado.casi-lleno {
			background-color: #ffc107;
			color: #333;
		}

		.turno-estado.ocupado {
			background-color: #6c757d;
			color: white;
		}

		.turno-estado.bloqueado {
			background-color: #adb5bd;
			color: #495057;
		}

		.turno-estado.deshabilitado {
			background-color: #dc3545;
			color: white;
		}

		.turno-estado.cerrado {
			background-color: #858796;
			color: white;
		}

		.turno-info {
			font-size: 11px;
			color: #155724;
			margin-top: 8px;
			font-style: italic;
		}

		.turno-info.turno-activo {
			color: #155724;
			font-weight: bold;
			font-style: normal;
		}

		.turno-info.turno-lleno {
			color: #383d41;
		}

		.turno-info.turno-bloqueado {
			color: #6c757d;
		}

		.turno-info.turno-deshabilitado {
			color: #721c24;
			font-weight: bold;
		}

		.turno-info.turno-cerrado {
			color: #5a5c69;
		}

		.turno-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 8px;
		}

		#turnosContainer .no-turnos {
			text-align: center;
			padding: 30px;
			color: #999;
		}

		/* Barra de progreso de ocupación */
		.turno-progress-container {
			background-color: #e9ecef;
			border-radius: 4px;
			height: 8px;
			overflow: hidden;
			margin: 8px 0;
		}

		.turno-progress-bar {
			background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
			height: 100%;
			border-radius: 4px;
			transition: width 0.3s ease;
		}

		.turno-card.bloqueado .turno-progress-bar {
			background: #adb5bd;
		}

		.turno-card.lleno .turno-progress-bar {
			background: #6c757d;
		}

		/* Información de cupos */
		.turno-cupos {
			display: flex;
			flex-wrap: wrap;
			justify-content: space-between;
			gap: 6px 10px;
			font-size: 12px;
			margin-top: 5px;
		}

		.cupos-disponibles {
			color: #28a745;
			font-weight: bold;
		}

		.cupos-ocupados {
			color: #6c757d;
		}

		.cupos-reservados {
			color: #c0392b;
			font-weight: 600;
		}

		/* Estilo para input de fecha en modal */
		#turno_fecha {
			font-weight: bold;
			text-align: center;
		}

		/* Contenedor principal del modal de turnos */
		.turnos-modal-container {
			max-height: 420px;
			overflow-y: auto;
			padding: 5px;
		}

		/* Encabezado fijo del modal */
		#turnoResumenFijo {
			position: sticky;
			top: -5px;
			z-index: 10;
			margin-bottom: 10px;
		}
	</style>
</HEAD>

<BODY>

	<div class="panel panel-main">
		<div class="panel-heading exa-header">
			<h3 class="panel-title">&raquo;Gestion de Manifiesto</h3>
		</div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div id="documentoSearch">
				<div class="row">
					<form name="searchManifiesto" id="searchManifiesto" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#searchManifiesto','manifiestoAjax');">
						<input type="hidden" id="ordenar_por_hidden" name="ordenar_por" value="" />
						<div class="col-xs-4">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">B&uacute;squeda</legend>
								<div class="form-group">
									<label class="col-xs-3 control-label label-xs">Filtrar Por:</label>
									<div class="col-xs-9 radioset opt_search">
										<input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" />
										<label for="radsc1">Cliente</label>
										<input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
										<label for="radsc2">C&eacute;dula/RUC</label>
										<input id="radsc3" name="op_opciones" type="radio" value="m" onclick="setfocus(this.form.search)" alt="" />
										<label for="radsc3">No. Manifiesto</label>
										<input id="radsc4" name="op_opciones" type="radio" value="pl" onclick="setfocus(this.form.search)" alt="" />
										<label for="radsc4">Placa</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label">B&uacute;squeda:</label>
									<div class="col-xs-8">
										<div class="input-group">
											<input name="search" onkeydown="if (event.keyCode === 13)
                                            this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
											<span class="input-group-btn">
												<button type="button" onclick="buscarYActualizarSaldos()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1">
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
						<div class="col-sm-8">
							<fieldset class="exa-fieldset">
								<legend class="Titulos2">Filtros</legend>
								<div class="form-group" style="display: flex; align-items: center; flex-wrap: nowrap; gap: 10px; margin-bottom: 0;">
									<label class="control-label label-xs" style="margin: 0; flex-shrink: 0;">Periodo:</label>
									<div style="flex-shrink: 0; width: 140px;">
										<select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
											<!--<option data-year='2018' data-inicio='2018-01-01' data-fin='2030-12-31' value="T">Todos</option>-->
											<?php $a = 1;
											foreach ($periodos as $p) { ?>
												<option data-year="<?php echo $p['Year']; ?>" <?php echo ($a == 1 ? 'selected' : ''); ?> data-inicio="<?php echo $p['Pec_Fei']; ?>" data-fin="<?php echo $p['Pec_Fef']; ?>" data-pec-cod="<?php echo $p['Pec_Cod']; ?>" value="<?php echo $p['Pec_Cod']; ?>">Periodo <?php echo $p['Year']; ?></option>
											<?php $a++;
											} ?>
										</select>
									</div>
									<div id="btnFiltroCorte" style="display: none; flex-shrink: 0; margin-left: -5px;">
										<button type="button" class="btn btn-info btn-xs" style="height: 22px; width: 22px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Filtro Fecha Corte Activo: se esta visualizando todos los anticipos de clientes hasta la fecha elegida">
											<i class="glyphicon glyphicon-info-sign" style="font-size: 12px;"></i>
										</button>
									</div>
									<label class="control-label label-xs" style="margin: 0 0 0 5px; flex-shrink: 0;">Estados:</label>
									<div style="flex-shrink: 0; width: 120px;">
									<select id="filtro_factura" name="filtro_factura" class="form-control input-xs">
										<option value="">Todos</option>
										<option value="FACTURADOS">Facturados</option>
										<option value="SIN FACTURAR">Sin Facturar</option>
										<option value="P">Pendiente</option>
										<option value="GE">Garita In</option>
										<option value="A">Aprobado</option>
										<option value="GS">Garita Out</option>
										<option value="F">Facturado</option>
										<option value="R">Rechazado</option>
									</select>
									</div>
									<div class="input-group input-group-xs" style="flex-shrink: 0; width: auto;">
										<span class="input-group-addon bold alert-info">Desde:</span>
										<input onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="6" class="form-control input-xs datepicker databind" style="text-align: center; width: 100px;" />
										<span class="input-group-addon bold alert-info">Hasta:</span>
										<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="6" class="form-control input-xs datepicker databind" style="text-align: center; width: 100px;" />
										<input type="hidden" id="letra" name="letra" value="Activos" />
									</div>
									<nav style="margin: 0; flex-shrink: 0;">
										<?php $valores = array("Activos", "Anulados"); ?>
										<ul class="pagination pagination-centered" style="margin: 0;">
											<?php foreach ($valores as $val) { ?>
												<li <?php if ($val == 'Activos') echo 'class="active"'; ?>>
													<a><?php echo $val ?></a>
												</li>
											<?php } ?>
										</ul>
									</nav>
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
								</div>
							</fieldset>
							<div id="saldo_total_container" class="form-group" style="margin-right: 10px; margin-bottom: 10px;">
								<div class="col-sm-12" style="display: flex; align-items: center; justify-content: flex-end; gap: 15px;">
									<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
										<?php if ($mostrarBotonSelectorPlantaSaldos) { ?>
											<div class="saldos-toolbar">
												<button class="btn btn-xs btn-primary" type="button" onclick="abrirModalSelectorPlantaSaldos();" title="Mostrar listado de plantas">
													<i class="glyphicon glyphicon-list-alt"></i> Plantas
												</button>
												<button class="btn btn-xs btn-default" id="btnCerrarSaldosPlanta" type="button" onclick="cerrarSaldosPlantaSeleccionada();" title="Quitar planta seleccionada para saldos" style="display: none;">
													<i class="glyphicon glyphicon-remove-circle"></i> Cerrar
												</button>
												<span id="lblPlantaSaldosActiva" class="label label-info" style="display:none;"></span>
											</div>
										<?php } ?>
										<div style="display: flex; align-items: right; gap: 6px; padding: 5px 12px; background: linear-gradient(135deg, #e7f3ff 0%, #cfe2ff 100%); border: 2px solid #0d6efd; border-radius: 8px; box-shadow: 0 2px 5px rgba(13, 110, 253, 0.2);">
											<div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
												<label class="control-label" style="margin: 0; font-size: 10px; font-weight: 600; color: #0a58ca; text-transform: uppercase; letter-spacing: 0.3px;">Anticipos (A)</label>
											</div>
											<div style="display: flex; align-items: baseline; gap: 3px;">
												<span style="font-size: 11px; font-weight: 600; color: #0d6efd;">$</span>
												<span id="anticipo_saldo" style="font-size: 16px; font-weight: 700; color: #0d6efd; letter-spacing: -0.3px;">
													<?php echo isset($anticipo['saldo']) ? number_format($anticipo['saldo'], 2, '.', ',') : '0.00'; ?>
												</span>
											</div>
										</div>
										<div style="display: flex; align-items: right; gap: 6px; padding: 5px 12px; background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%); border: 2px solid #ffc107; border-radius: 8px; box-shadow: 0 2px 5px rgba(255, 193, 7, 0.2);">
											<div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
												<label class="control-label" style="margin: 0; font-size: 10px; font-weight: 600; color: #b8860b; text-transform: uppercase; letter-spacing: 0.3px;">Sin Facturar (B)</label>
											</div>
											<div style="display: flex; align-items: baseline; gap: 3px;">
												<span style="font-size: 11px; font-weight: 600; color: #856404;">$</span>
												<span id="saldo_sin_factura" style="font-size: 16px; font-weight: 700; color: #856404; letter-spacing: -0.3px;">
													<?php echo isset($saldo_sin_factura['saldo']) ? number_format($saldo_sin_factura['saldo'], 2, '.', ',') : '0.00'; ?>
												</span>
											</div>
										</div>
										<div style="display: flex; align-items: center; gap: 6px; padding: 5px 12px; background: linear-gradient(135deg, <?php echo $saldo_total < 60 ? '#f8d7da 0%, #f5c6cb 100%' : '#d1e7dd 0%, #badbcc 100%'; ?>); border: 2px solid <?php echo $saldo_total < 60 ? '#dc3545' : '#198754'; ?>; border-radius: 8px; box-shadow: 0 2px 5px rgba(<?php echo $saldo_total < 60 ? '220, 53, 69' : '25, 135, 84'; ?>, 0.25);">
											<div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
												<label class="control-label" style="margin: 0; font-size: 10px; font-weight: 600; color: <?php echo $saldo_total < 60 ? '#721c24' : '#0f5132'; ?>; text-transform: uppercase; letter-spacing: 0.3px;">Saldo Total (A-B)</label>
											</div>
											<div style="display: flex; align-items: baseline; gap: 3px;">
												<span style="font-size: 11px; font-weight: 600; color: <?php echo $saldo_total < 60 ? '#dc3545' : '#198754'; ?>;">$</span>
												<span id="saldo_total" style="font-size: 18px; font-weight: 800; color: <?php echo $saldo_total < 60 ? '#dc3545' : '#198754'; ?>; letter-spacing: -0.3px;">
													<?php echo number_format($saldo_total, 2, '.', ','); ?>
												</span>
											</div>
											<?php if ($saldo_total < 60) { ?>
												<span class="label label-danger" title="Saldo total insuficiente para crear un nuevo manifiesto (Mínimo requerido: $120.00)" style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; padding: 3px 8px; margin-left: 6px; border-radius: 10px; background-color: #dc3545; border: 1px solid #b02a37;">
													<i class="glyphicon glyphicon-exclamation-sign" style="font-size: 11px;"></i> Insuficiente
												</span>
											<?php } else { ?>
												<span style="font-size: 9px; color: <?php echo $saldo_total < 60 ? '#721c24' : '#0f5132'; ?>; font-weight: 600; margin-left: 6px; padding: 2px 6px; background: rgba(255,255,255,0.5); border-radius: 6px;">
													✓ Disponible
												</span>
											<?php } ?>
										</div>
									</div>

								</div>
							</div>
						</div>
					</form>
					<div class="col-xs-12" style="min-height: 360px;">
						<table id="searchGrid" name="searchGrid"></table>
						<div class="Titulos2">
							<span id="plan-footer">
								<strong>Leyenda:</strong>
								<span class="glyphicon glyphicon-stop green"></span> Manifiestos Activos </span>
							<span class="glyphicon glyphicon-stop red"></span> Manifiestos Anulados </span>

						</div>
						<div id="searchGridPager"></div>
					</div>
				</div>
				<br>
				<div>
					<?php if( empty($plaSanciones[0]['Msa_Cod']) ) { ?>
						<button class="btn btn-sm btn-success" id="btnNuevo" type="button" onclick="<?php echo ($saldo_total >= 60) ? 'abrirModalTurno();' : ''; ?>" <?php echo ($saldo_total < 60) ? 'disabled title="Saldo total insuficiente para crear un nuevo manifiesto (Mínimo: 120.00)"' : ''; ?>><i class="glyphicon glyphicon-plus"></i> Nuevo</button>
					<?php } else { ?>
						<button class="btn btn-sm btn-warning" id="btnNuevoSancion" type="button" onclick="$('#sancionPlantaDialog').dialog('open');" title="La planta tiene una sanción activa"><i class="glyphicon glyphicon-alert"></i> Nuevo</button>
					<?php } ?>
					<?php if ($mostrarBotonCertificado) { ?>
						<button class="btn btn-sm btn-info" id="btnGenerarCertificado" type="button" onclick="abrirCertificadoModal();"><i class="glyphicon glyphicon-certificate"></i> Generar Certificado</button>
					<?php } ?>
				</div>
			</div>



			<div id="documentoUpdate" hidden="true">
				<div class="row">
					<div class="col-sm-12">
						<form class="form-horizontal normal" id="manifiestoForm" name="manifiestoForm" action="javascript:preSaveManifiesto()">
							<div class="col-sm-12">
								<div class="row">
									<div class="col-sm-8">
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Manifiesto</legend>
											<div class="form-group">
												<label class="col-sm-2 control-label label-sm required">Empresa Generadora:</label>
												<div class="col-sm-7">
													<input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" value="<?php echo $cliente_manifiesto['Prs_Cod']; ?>" />
													<input name="Man_Cod" id="Man_Cod" type="text" style="display:none;" />
													<input name="Cel_Cod" id="Cel_Cod" type="text" style="display:none;" />
													<input name="Cli_Cod" id="Cli_Cod" type="text" style="display:none;" value="<?php echo $cliente_manifiesto['Cli_Cod']; ?>" />
													<input name="op_opciones" type="text" value="c" style="display: none;" />
													<div class="input-group input-group-xs">
														<span id="Prs_Ced_Inf" name="Prs_Ced_Inf" class="input-group-addon bold alert-info"><?php echo $cliente_manifiesto['Prs_Ced']; ?></span>
														<input name="cliente" id="cliente" type="text" placeholder="Seleccione o cliente..." class="form-control input-xs" tabindex="1" required="" value="<?php echo $cliente_manifiesto['nombre']; ?>" readonly />
														<span id="btn_span" class="input-group-btn">
															<button type="button" onclick="$('#cliDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2">
																<span class="glyphicon glyphicon-search"></span>
															</button>
														</span>
													</div>
												</div>
												<label class="col-sm-1 control-label label-sm required"><b>SALDO:</b></label>
												<div class="col-sm-2">
													<div class="input-group input-group-xs">
														<input name="saldo" id="saldo" type="text" title="<?php echo $saldo_total < 60 ? 'Saldo Insuficiente (Mínimo requerido: $120.00)' : ''; ?>" style="font-size: 18px; font-weight:bold; text-align: right; <?php echo $saldo_total < 60 ? 'color:#dc3545' : ''; ?>" class="form-control input-sm" value="<?php echo number_format($saldo_total, 2, '.', ''); ?>" readonly />
														<span class="input-group-addon validate"><i id="saldo_inf"></i></span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-2 control-label label-sm required">Generadora Desecho:</label>
												<div class="col-sm-5">

													<?php $plantas = $obBD_con1->getRowConsulta('manifiesto_plantas.selectWhere', array('where' => array('Cli_Cod' => $cliente_manifiesto['Cli_Cod'], 'Pla_Cod' => $cliente_manifiesto['Pla_Cod'], 'Pla_Est' => 'A')), $obBD_conexion);
													$obBD_con1->utf8_change_param($plantas);
													?>
													<input type="hidden" id="Pla_Cod" name="Pla_Cod" value="<?php echo $plantas['Pla_Cod']; ?>" />
													<input type="hidden" id="Pla_Dis" name="Pla_Dis" value="<?php echo $plantas['Pla_Dis']; ?>" />
													<span id="Pla_Nom" name="Pla_Nom" class="form-control input-xs databind datatitle" title="<?php echo $plantas['Pla_Nom']; ?>"><?php echo $plantas['Pla_Nom']; ?></span>

												</div>
												<!--<label class="col-xs-2 control-label label-xs required" title="Numero del Manifiesto">N° Manifiesto:</label>
												<div class="col-xs-3">
													<div class="input-group input-group-xs">
														<input name="Man_Nme" id="Man_Nme" type="text" class="form-control input-xs databind datatitle" required onchange="numeroManifiesto(this.value)"/>
														<span class="input-group-addon validate"><i id="Man_Num_Est"></i></span>
													</div>
												</div>-->
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs required" title="Numero de Registro Generador del Desecho">Fecha Manifiesto:</label>
												<div class="col-xs-4">
													<div class="input-group input-group-xs">
														<input type="text" name="Man_Fec" id="Man_Fec" class="form-control /*datepicker*/" value="<?php echo date('Y-m-d'); ?>" size="10" readonly required />
														<span class="input-group-addon bold alert-info">&nbsp;Hora:</span>
														<input type="time" name="Man_Hor" id="Man_Hor" class="form-control" placeholder="HH:MM" value="<?php echo date('H:i'); ?>" readonly required />
													</div>
												</div>
												<!--<label class="col-xs-2 control-label label-xs required" title="Numero de Registro Generador del Desecho">N° Registro G.D:</label>
												<div class="col-xs-3">
													<input name="Man_Rgd" id="Man_Rgd" class="form-control input-xs databind datatitle" required/></div>-->
												<label class="col-xs-3 control-label label-xs required" title="Numero de Licencia Ambiental">N° Licencia AMB.</label>
												<div class="col-xs-3">
													<input name="Man_Lac" id="Man_Lac" type="text" class="form-control input-xs databind datatitle" value="<?php echo $plantas['Pla_Lic']; ?>" readonly required />
												</div>
											</div>

										</fieldset>
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos del Generado Desecho</legend>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs required">Tipo Desecho:</label>
												<div class="col-xs-6">
													<?php $desecho = $obBD_con1->getArrayConsulta('manifiesto_desechos.selectWhere', array('setWhere' => array('setEmpCod', 'isActive')), $obBD_conexion, true);
													$obBD_con1->utf8_change_param($desecho);
													?>
													<select id="Tde_Cod" name="Tde_Cod" class="form-control input-xs readOnly" required="">
														<!--<option value="">Seleccione...</option>-->
														<?php
														if (count($desecho) > 0) {
															foreach ($desecho as $row) {
																echo "<option value='$row[Tde_Cod]'>$row[Tde_Cde] - $row[Tde_Des]</option>";
															}
														}
														?>
													</select>
												</div>
												<label class="col-xs-2 control-label label-xs required" title="Peso del Desecho (Tonelada)">Peso Reportado:</label>
												<div class="col-sm-2">
													<div class="input-group input-group-xs">
														<input type="text" id="Man_Pes" name="Man_Pes" class="form-control input-xs" placeholder="Kilogramos" readonly value="20000" onkeypress="return validar_decimal(event);">
														<span class="input-group-addon validate">Kg</span>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs" title="Direccion origen del Desecho">Origen Desecho:</label>
												<div class="col-xs-5">
													<input type="text" class="form-control input-xs" id="Man_Dsa" readonly value="<?php echo $plantas['Ciu_Des'] . ' / ' . $plantas['Pla_Dir']; ?>" name="Man_Dsa">
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs" title="Direccion destino del Desecho">Dir Destino Desecho:</label>
												<div class="col-xs-6">
													<input type="text" class="form-control input-xs" id="Man_Dde" value="RELAVERA COMUNITARIA EL TABLÓN" name="Man_Dde">
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs" title="Direccion destino del Desecho">Ruta de Llegada:</label>
												<div class="col-xs-6">
													<input type="text" class="form-control input-xs" id="Man_Rut" val="" name="Man_Rut" value="<?php echo $plantas['Pla_Rut']; ?>">
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs required" title="Numero de Registro Generador del Desecho">Fecha Salida:</label>
												<div class="col-xs-5">
													<div class="input-group input-group-xs">
														<input type="text" name="Man_Fes" id="Man_Fes" class="form-control /*datepicker*/" readonly size="10" required />
														<span class="input-group-addon bold alert-info">&nbsp;Hora Salida:</span>
														<input type="time" name="Man_Fes_Hor" id="Man_Fes_Hor" class="form-control" readonly value="" placeholder="HH:MM" required>
														<input type="hidden" name="Tud_Cod" id="Tud_Cod" value="">
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs required" title="Numero de Registro Generador del Desecho">Fecha Llegada:</label>
												<div class="col-xs-5">
													<div class="input-group input-group-xs">
														<input type="text" name="Man_Fea" id="Man_Fea" class="form-control /*datepicker*/" readonly size="10" required />
														<span class="input-group-addon bold alert-info">&nbsp;Hora Llegada:</span>
														<input type="time" name="Man_Fea_Hor" id="Man_Fea_Hor" class="form-control" readonly value="<?php echo $plantas['Pla_Dis']; ?>" placeholder="HH:MM" required>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label class="col-xs-2 control-label label-xs ">Obser. Especiales:</label>
												<div class="col-sm-6">
													<textarea class="form-control" id="Man_Obe" val="" name="Man_Obe" rows="2"></textarea>
												</div>
											</div>
										</fieldset>
										<fieldset class="exa-fieldset">
											<legend class="Titulos2">Datos Chofer y Vehiculo</legend>
											<div class="form-group">
												<label class="col-sm-2 control-label label-sm required">
													Guia Remisión:
													<span class="glyphicon glyphicon-exclamation-sign text-warning new-field-indicator" title="Campo nuevo por completar" aria-hidden="true"></span>
												</label>
												<div class="col-sm-3">
													<input type="text" class="form-control input-xs required" id="Man_Gui" name="Man_Gui" value="" required>
												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-2 control-label label-sm required">Vehiculo:</label>
												<div class="col-sm-8">

													<?php
													/*$man_pendiente = $obBD_con1->getArrayConsulta("manifiesto.2", array('Pla_Cod' => $cliente_manifiesto['Pla_Cod'],'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
													$obBD_con1->utf8_change_param($man_pendiente);
													if (empty($cliente_manifiesto))
														$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('setWhere' => array('getVehiculo', 'setEmpCod'), 'where' => array('Mat_Est' => 'A')), $obBD_conexion);
													else
														$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('setWhere' => array('getVehiculoByPla'), 'where' => array('Mat_Est' => 'A', 'manifiesto_vehiculo.Pla_Cod' => $cliente_manifiesto['Pla_Cod'])), $obBD_conexion);
													$obBD_con1->utf8_change_param($vehiculos);*/
													$vehiculos = $obBD_con1->getArrayConsulta('manifiesto_transporte.1', array('Pla_Cod' => $cliente_manifiesto['Pla_Cod']), $obBD_conexion);
													$obBD_con1->utf8_change_param($vehiculos);

													?>
													<div class="input-group input-group-xs">
														<select id="Veh_Cod" name="Veh_Cod" style="width: 100%;" class="form-control input-xs readOnly" required="" onchange="$('#Man_Des_Inf').html($(this).find(':selected').data('mat_des')); $('#Man_Pes').val($(this).find(':selected').data('veh_cap'));">
															<option value=''>Seleccione...</option>
															<?php
															if (count($vehiculos) > 0) {
																// Crear array de vehículos bloqueados por placa y empresa (Veh_Pla|Emp_Cod)
																/*$vehiculos_bloqueados = array();
																if (!empty($man_pendiente) && is_array($man_pendiente)) {
																	foreach ($man_pendiente as $man) {
																		if (isset($man['Veh_Pla']) && isset($man['Emp_Cod']) && $man['Veh_Pla'] !== '' && $man['Emp_Cod'] !== '') {
																			$vehiculos_bloqueados[] = $man['Veh_Pla'] . '|' . $man['Emp_Cod'];
																		}
																	}
																}*/
																// Vehículos sancionados (vigentes, solo tipo VE): solo los que están en el select Veh_Cod
																$vehiculos_sancionados = array();
																$listado_vehiculos_sancionados_modal = array();
																$veh_pla_del_select = array();
																$vehiculos_por_pla = array();
																foreach ($vehiculos as $v) {
																	$pla = isset($v['Veh_Pla']) ? trim((string)$v['Veh_Pla']) : '';
																	if ($pla !== '') {
																		$veh_pla_del_select[] = $pla;
																		$vehiculos_por_pla[$pla] = $v;
																	}
																}
																foreach ($sanciones as $s) {
																	if (isset($s['Msa_Tip']) && $s['Msa_Tip'] === 'VE' && !empty($s['identi'])) {
																		$identi_trim = trim((string)$s['identi']);
																		$vehiculos_sancionados[] = $identi_trim;
																		if (in_array($identi_trim, $veh_pla_del_select)) {
																			$nombre =  $s['nombre'];
																			$listado_vehiculos_sancionados_modal[] = array(
																				'placa' => $identi_trim,
																				'nombre' => $nombre,
																				'fei' => isset($s['Msa_Fei']) ? $s['Msa_Fei'] : '',
																				'fef' => isset($s['Msa_Fef']) ? $s['Msa_Fef'] : '',
																				'obs' => isset($s['Msa_Obs']) ? $s['Msa_Obs'] : ''
																			);
																		}
																	}
																}
																$emp_cod_ctx = isset($Ses_Emp_Cod) ? $Ses_Emp_Cod : '';																
																foreach ($vehiculos as $row) {
																	$esta_sancionado = in_array($row['Veh_Pla'], $vehiculos_sancionados);
																	//$clave_veh = (isset($row['Veh_Pla']) ? $row['Veh_Pla'] : '') . '|' . $emp_cod_ctx;
																	//$esta_bloqueado = in_array($clave_veh, $vehiculos_bloqueados);
																	$disabled = ($esta_bloqueado || $esta_sancionado) ? 'disabled' : '';
																	$texto_vehiculo = $row['Veh_Pla'] . ' - ' . $row['Veh_Mar'];
																	if ($row['total']*1 > 0) { //if ($esta_bloqueado)
																		$texto_vehiculo .= ' << En Ruta >>';
																		$disabled = 'disabled';
																	}
																	if ($esta_sancionado) {
																		$texto_vehiculo .= ' << Sancionado >>';
																	}
																	echo "<option value='$row[Veh_Cod]' data-veh_cap='$row[Veh_Cap]' data-mat_cod='$row[Mat_Cod]' data-mat_des='$row[Mat_Des]' $disabled>$texto_vehiculo</option>";
																}
															}
															?>
														</select>
														<span class="input-group-addon validate" style="width: 60%;"> <i id="Man_Des_Inf"></i></span>
														<?php if (!empty($listado_vehiculos_sancionados_modal)) { ?>
														<span class="input-group-btn">
															<button type="button" id="btnVerVehiculosSancionados" class="btn btn-warning btn-xs" title="Ver vehículos sancionados del listado" data-toggle="modal" data-target="#modalVehiculosSancionados">
																<span class="glyphicon glyphicon-alert"></span> Sancionados
															</button>
														</span>
														<?php } ?>
													</div>

												</div>
											</div>
											<div class="form-group">
												<label class="col-sm-2 control-label label-sm required">Chofer:</label>
												<div class="col-sm-5">
													<div class="input-group input-group-xs">
														<span id="Prs_Ced_Cho" name="Prs_Ced_Cho" class="input-group-addon bold alert-info"> - </span>
														<?php $datos = $obBD_con1->getArrayConsulta('manifiesto_chofer.selectWhere', array('where' => array('Cho_Est' => 'A', 'Cli_Cod' => $cliente_manifiesto['Cli_Cod'])), $obBD_conexion, true);
														$obBD_con1->utf8_change_param($datos);
														// Crear array de Prs_Ced bloqueados (en ruta)
														$choferes_bloqueados = array();
														if (!empty($man_pendiente) && is_array($man_pendiente)) {
															foreach ($man_pendiente as $man) {
																if (isset($man['Cho_Cod']) && !empty($man['Cho_Cod'])) {
																	$choferes_bloqueados[] = $man['Prs_Ced'];
																}
															}
														}
														// Choferes sancionados (vigentes, solo tipo CH): solo los que están en el select Cho_Cod
														$choferes_sancionados = array();
														$listado_choferes_sancionados_modal = array();
														$cho_cod_del_select = array();
														$datos_por_cho_cod = array();
														$prs_ced_del_select = array();
														foreach ($datos as $d) {
															$cod = isset($d['Cho_Cod']) ? $d['Cho_Cod'] : null;
															if ($cod !== null && $cod !== '') {
																$cho_cod_del_select[] = $cod;
																$datos_por_cho_cod[$cod] = $d;
															}
															if (!empty($d['Prs_Ced'])) {
																$prs_ced_del_select[] = trim((string)$d['Prs_Ced']);
															}
														}
														foreach ($sanciones as $s) {
															if (isset($s['Msa_Tip']) && $s['Msa_Tip'] === 'CH' && !empty($s['identi'])) {
																$choferes_sancionados[] = $s['identi'];
																// Solo los que están en el select Cho_Cod: por Cho_Cod o por cédula (identi)
																$cho_cod_sancion = isset($s['Cho_Cod']) ? $s['Cho_Cod'] : null;
																$identi_trim = trim((string)$s['identi']);
																$esta_en_select = ($cho_cod_sancion !== null && in_array($cho_cod_sancion, $cho_cod_del_select))
																	|| in_array($identi_trim, $prs_ced_del_select);
																if ($esta_en_select) {
																	$nombre = '-';
																	if ($cho_cod_sancion !== null && isset($datos_por_cho_cod[$cho_cod_sancion]['nombre'])) {
																		$nombre = $datos_por_cho_cod[$cho_cod_sancion]['nombre'];
																	} elseif (isset($s['nombre'])) {
																		$nombre = $s['nombre'];
																	}
																	$listado_choferes_sancionados_modal[] = array(
																		'cedula' => $identi_trim,
																		'nombre' => $nombre,
																		'fei' => isset($s['Msa_Fei']) ? $s['Msa_Fei'] : '',
																		'fef' => isset($s['Msa_Fef']) ? $s['Msa_Fef'] : '',
																		'obs' => isset($s['Msa_Obs']) ? $s['Msa_Obs'] : ''
																	);
																}
															}
														}
														?>
														<select id="Cho_Cod" name="Cho_Cod" class="form-control input-xs readOnly" required="" onchange="$('#Prs_Ced_Cho').html($(this).find(':selected').data('ced'));  $('#lic_cho').val('TIPO ' + $(this).find(':selected').data('lic'))">
															<option value=''>Seleccione...</option>
															<?php
															if (count($datos) > 0) {
																foreach ($datos as $row) {
																	$cho_ced = isset($row['Prs_Ced']) ? $row['Prs_Ced'] : 0;
																	$esta_bloqueado = in_array($cho_ced, $choferes_bloqueados);
																	$esta_sancionado = in_array($row['Prs_Ced'], $choferes_sancionados);
																	$disabled = ($esta_bloqueado || $esta_sancionado) ? 'disabled' : '';
																	$texto_chofer = $row['nombre'];
																	if ($esta_bloqueado) {
																		$texto_chofer .= ' << En ruta >>';
																	}
																	if ($esta_sancionado) {
																		$texto_chofer .= ' << Sancionado >>';
																	}
																	echo "<option value='$row[Cho_Cod]' data-lic='$row[Cho_Tli]' data-ced='$row[Prs_Ced]' $disabled>$texto_chofer</option>";
																}
															}
															?>
														</select>
														<?php if (!empty($listado_choferes_sancionados_modal)) { ?>
														<span class="input-group-btn">
															<button type="button" id="btnVerChoferesSancionados" class="btn btn-warning btn-xs" title="Ver choferes sancionados del listado" data-toggle="modal" data-target="#modalChoferesSancionados">
																<span class="glyphicon glyphicon-alert"></span> Sancionados
															</button>
														</span>
														<?php } ?>
													</div>
												</div>
												<label class="col-sm-2 control-label label-sm required">Licencia:</label>
												<div class="col-xs-2">
													<input name="lic_cho" id="lic_cho" type="text" size="3" style="font-size: 12px; font-weight:bold; text-align: center;" class="form-control input-xs" readonly />
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
								<button class="btn btn-sm btn-inverse no" onclick="moveToMain()"><i class="glyphicon glyphicon-arrow-left"></i> Atras</button>
								<?php if ($saldo_total >= 60) { ?>
									<button class="btn btn-sm btn-primary no" onclick="$('#manifiestoForm').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
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

	<div id="transporteDialog" title="Registrar Transporte" style="display: none;">
		<form id="transporteForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required" title="Empresa de Transporte">Empresa:</label>
				<input type="hidden" id="Mat_Cod" name="Mat_Cod">
				<div class="col-xs-8">
					<input type="text" id="Mat_Des" name="Mat_Des" class="form-control input-xs" required="" placeholder="Ingrese el nombre completo">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">C&oacute;digo MAE:</label>
				<div class="col-xs-8">
					<input type="text" id="Mat_Mae" name="Mat_Mae" class="form-control input-xs" required="" placeholder="Ingrese el c&oacute;digo MAE">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tel&eacute;fono:</label>
				<div class="col-xs-8">
					<input type="text" id="Mat_Tel" name="Mat_Tel" class="form-control input-xs" required="" placeholder="Ingrese el tel&eacute;fono" onkeypress="return soloNumeros(event)">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required" title="Numero Plan Contingencia">No Pla. Con.:</label>
				<div class="col-xs-8">
					<input type="text" id="Mat_Pco" name="Mat_Pco" class="form-control input-xs" required="" placeholder="Ingrese numero Plan Contingencia">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Direcci&oacute;n:</label>
				<div class="col-xs-8">
					<textarea id="Mat_Dir" name="Mat_Dir" class="form-control input-xs" rows="2" required="" placeholder="Ingrese la direcci&oacute;n"></textarea>
				</div>
			</div>
		</form>
		<br>
		<div style="text-align: center;">
			<button class="btn btn-sm btn-danger" type="button" onclick="anularTransporte();"><i class="glyphicon glyphicon-trash"></i> Anular Registro</button>
			<button class="btn btn-sm btn-primary" type="button" onclick="preSaveTransporte();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
		</div>
	</div>

	<div id="vehiculoDialog" title="Registrar Vehiculo" style="display: none;">
		<form id="vehiculoForm" class="form-horizontal normal">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required" title="Empresa de Transporte">Empresa:</label>
				<input type="hidden" id="Veh_Cod" name="Veh_Cod">
				<div class="col-xs-8">
					<?php $datos = $obBD_con1->getArrayConsulta('manifiesto_transporte.selectWhere', array('setWhere' => array('setEmpCod')), $obBD_conexion, true);
					$obBD_con1->utf8_change_param($datos);
					?>
					<select id="Mat_Cod_New" name="Mat_Cod_New" class="form-control input-xs readOnly" required="">
						<option value=''>Seleccione...</option>
						<?php
						if (count($datos) > 0) {
							foreach ($datos as $row) {
								echo "<option value='$row[Mat_Cod]' data-mae='$row[Mat_Mae]' data-pco='$row[Mat_Pco]'>$row[Mat_Des]</option>";
							}
						}
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Marca:</label>
				<div class="col-xs-8">
					<input type="text" id="Veh_Mar" name="Veh_Mar" class="form-control input-xs" required="" placeholder="Ingrese marca">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Placa:</label>
				<div class="col-xs-8">
					<input type="text" id="Veh_Pla" name="Veh_Pla" class="form-control input-xs" required="" placeholder="Ingrese numero placa">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Color:</label>
				<div class="col-xs-8">
					<input type="text" id="Veh_Col" name="Veh_Col" class="form-control input-xs" required="" placeholder="Ingrese color">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Capacidad:</label>
				<div class="col-xs-5">
					<div class="input-group input-group-xs">
						<input name="Veh_Cap" id="Veh_Cap" type="text" class="form-control input-xs databind datatitle" onkeypress="return validar_decimal(event);" required />
						<span class="input-group-addon validate"><i>Kg</i></span>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required" title="Empresa de Transporte">Tipo Vehiculo:</label>
				<div class="col-xs-8">
					<select id="Veh_Tit" name="Veh_Tit" class="form-control input-xs readOnly" required="">
						<option value='V'>VOLQUETA</option>
						<option value='D'>TIPO DUMPER</option>
						<option value='C'>CAMION</option>
					</select>
				</div>
			</div>
		</form>
		<br>
		<div style="text-align: center;">
			<button class="btn btn-sm btn-danger" type="button" onclick="anularVehiculo();"><i class="glyphicon glyphicon-trash"></i> Anular Registro</button>
			<button class="btn btn-sm btn-primary" type="button" onclick="preSaveVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
		</div>
	</div>

	<!-- Modal para Registrar Chofer -->
	<div id="choferDialog" title="Registrar Chofer" style="display: none;">
		<form id="choferForm" class="form-horizontal normal">
			<input type="hidden" id="Cho_Cod_Form" name="Cho_Cod">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">C&eacute;dula:</label>
				<div class="col-xs-8">
					<div class="input-group input-group-xs">
						<input type="text" id="Cho_Ced" name="Cho_Ced" class="form-control input-xs" required="" placeholder="N&uacute;mero de c&eacute;dula" maxlength="13" onchange="buscarPersonaPorCedula(this.value)">
						<span class="input-group-addon validate"><i id="Cho_Ced_Est"></i></span>
					</div>
				</div>
			</div>
			<input type="hidden" id="Prs_Cod_Chofer" name="Prs_Cod">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Nombres:</label>
				<div class="col-xs-8">
					<input type="text" id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" required="" placeholder="Nombre del chofer">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Apellidos:</label>
				<div class="col-xs-8">
					<input type="text" id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" required="" placeholder="Apellidos del chofer">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tipo Licencia:</label>
				<div class="col-xs-8">
					<div class="input-group input-group-xs">
						<select id="Cho_Tli" name="Cho_Tli" class="form-control input-xs" required="">
							<option value="">Licencia...</option>
							<option value="A">A</option>
							<option value="A1">A1</option>
							<option value="B">B</option>
							<option value="C">C</option>
							<option value="C1">C1</option>
							<option value="D">D</option>
							<option value="D1">D1</option>
							<option value="E">E</option>
						</select>
						<span class="input-group-addon bold alert-info">Caducidad:</span>
						<input type="text" id="Cho_Cli" name="Cho_Cli" class="form-control input-xs datepicker" required="" placeholder="Fecha caducidad">
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tel&eacute;fono:</label>
				<div class="col-xs-8">
					<input type="text" id="Cho_Tel" name="Cho_Tel" class="form-control input-xs" required="" placeholder="Tel&eacute;fono" maxlength="20" onkeypress="return soloNumeros(event)">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Tipo de Sangre:</label>
				<div class="col-xs-8">
					<select id="Cho_Tsa" name="Cho_Tsa" class="form-control input-xs" required="">
						<option value="">Seleccione...</option>
						<option value="A+">A+</option>
						<option value="A-">A-</option>
						<option value="B+">B+</option>
						<option value="B-">B-</option>
						<option value="AB+">AB+</option>
						<option value="AB-">AB-</option>
						<option value="O+">O+</option>
						<option value="O-">O-</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs">Licencia AMB MAE:</label>
				<div class="col-xs-8">
					<input type="text" id="Cho_Mae" name="Cho_Mae" class="form-control input-xs" placeholder="Licencia ambiental MAE" maxlength="20">
				</div>
			</div>
		</form>
		<br>
		<div style="text-align: center;">
			<button class="btn btn-sm btn-danger" type="button" onclick="anularChofer();"><i class="glyphicon glyphicon-trash"></i> Anular</button>
			<button class="btn btn-sm btn-primary" type="button" onclick="preSaveChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
		</div>
	</div>

	<!-- Modal para Registrar Planta -->
	<div id="plantaDialog" title="Registrar Planta" style="display: none;">
		<form id="plantaForm" class="form-horizontal normal">
			<input type="hidden" id="Pla_Cod_Form" name="Pla_Cod">
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Nombre Planta:</label>
				<div class="col-xs-8">
					<input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control input-xs" required="" placeholder="Nombre de la planta" maxlength="50">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Ciudad:</label>
				<div class="col-xs-8">
					<?php $ciudades = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('setWhere' => array('getProvincia', 'getPais')), $obBD_conexion, true);
					$obBD_con1->utf8_change_param($ciudades);
					?>
					<select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs /*chosen-select*/" data-placeholder="Seleccione ciudad..." required="">
						<option value=""></option>
						<?php
						if (count($ciudades) > 0) {
							foreach ($ciudades as $row) {
								echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]' data-pais='$row[Pas_Nom]'>$row[Ciu_Des]</option>";
							}
						}
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">N&uacute;mero Licencia:</label>
				<div class="col-xs-8">
					<input type="text" id="Pla_Lic" name="Pla_Lic" class="form-control input-xs" required="" placeholder="N&uacute;mero de licencia" maxlength="20">
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Direcci&oacute;n:</label>
				<div class="col-xs-8">
					<input type="text" id="Pla_Dir" name="Pla_Dir" class="form-control input-xs" required="" placeholder="Direcci&oacute;n de la planta" maxlength="30">
				</div>
			</div>
		</form>
		<br>
		<div style="text-align: center;">
			<button class="btn btn-sm btn-primary" type="button" onclick="preSavePlanta();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
		</div>
	</div>

	<!-- Modal Vehículos Sancionados: compacto y profesional -->
	<div class="modal fade modal-sancionados" id="modalVehiculosSancionados" tabindex="-1" role="dialog" aria-labelledby="modalVehiculosSancionadosLabel">
		<div class="modal-dialog" role="document" style="width: 520px;">
			<div class="modal-content">
				<div class="modal-header" style="padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
					<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="margin-top: 0; opacity: 0.6;"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="modalVehiculosSancionadosLabel" style="font-size: 13px; font-weight: 600; margin: 0;">
						<span class="glyphicon glyphicon-alert text-warning"></span> Vehículos sancionados
						<?php if (!empty($listado_vehiculos_sancionados_modal)) { ?><span class="badge" style="background: #856404; font-size: 10px; margin-left: 4px;"><?php echo count($listado_vehiculos_sancionados_modal); ?></span><?php } ?>
					</h4>
				</div>
				<div class="modal-body" style="padding: 10px 12px; max-height: 70vh; overflow-y: auto;">
					<?php if (!empty($listado_vehiculos_sancionados_modal)) { ?>
					<div class="table-responsive" style="margin: 0;">
						<table class="table table-condensed table-bordered" style="font-size: 11px; margin: 0;">
							<thead>
								<tr style="background: #f1f3f5;">
									<th style="padding: 4px 6px;">Placa</th>
									<th style="padding: 4px 6px;">Vehículo</th>
									<th style="padding: 4px 6px;">Desde</th>
									<th style="padding: 4px 6px;">Hasta</th>
									<th style="padding: 4px 6px;">Obs.</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($listado_vehiculos_sancionados_modal as $item) { ?>
								<tr>
									<td style="padding: 3px 6px;"><strong><?php echo htmlspecialchars($item['placa']); ?></strong></td>
									<td style="padding: 3px 6px;"><?php echo htmlspecialchars($item['nombre']); ?></td>
									<td style="padding: 3px 6px; white-space: nowrap;"><?php echo htmlspecialchars($item['fei']); ?></td>
									<td style="padding: 3px 6px; white-space: nowrap;"><?php echo htmlspecialchars($item['fef']); ?></td>
									<td style="padding: 3px 6px; max-width: 120px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($item['obs']); ?>"><?php echo htmlspecialchars($item['obs']); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<?php } else { ?>
					<p class="text-muted" style="font-size: 12px; margin: 8px 0 0 0;"><span class="glyphicon glyphicon-ok-circle text-success"></span> No hay vehículos sancionados en el listado.</p>
					<?php } ?>
				</div>
				<div class="modal-footer" style="padding: 6px 12px; border-top: 1px solid #dee2e6;">
					<button type="button" class="btn btn-default btn-xs" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Choferes Sancionados: compacto y profesional -->
	<div class="modal fade modal-sancionados" id="modalChoferesSancionados" tabindex="-1" role="dialog" aria-labelledby="modalChoferesSancionadosLabel">
		<div class="modal-dialog" role="document" style="width: 520px;">
			<div class="modal-content">
				<div class="modal-header" style="padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
					<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="margin-top: 0; opacity: 0.6;"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="modalChoferesSancionadosLabel" style="font-size: 13px; font-weight: 600; margin: 0;">
						<span class="glyphicon glyphicon-alert text-warning"></span> Choferes sancionados
						<?php if (!empty($listado_choferes_sancionados_modal)) { ?><span class="badge" style="background: #856404; font-size: 10px; margin-left: 4px;"><?php echo count($listado_choferes_sancionados_modal); ?></span><?php } ?>
					</h4>
				</div>
				<div class="modal-body" style="padding: 10px 12px; max-height: 70vh; overflow-y: auto;">
					<?php if (!empty($listado_choferes_sancionados_modal)) { ?>
					<div class="table-responsive" style="margin: 0;">
						<table class="table table-condensed table-bordered" style="font-size: 11px; margin: 0;">
							<thead>
								<tr style="background: #f1f3f5;">
									<th style="padding: 4px 6px;">Cédula</th>
									<th style="padding: 4px 6px;">Nombre</th>
									<!--<th style="padding: 4px 6px;">Desde</th>-->
									<th style="padding: 4px 6px;">Hasta</th>
									<th style="padding: 4px 6px;">Obs.</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($listado_choferes_sancionados_modal as $item) { ?>
								<tr>
									<td style="padding: 3px 6px;"><strong><?php echo htmlspecialchars($item['cedula']); ?></strong></td>
									<td style="padding: 3px 6px;"><?php echo htmlspecialchars($item['nombre']); ?></td>
									<!--<td style="padding: 3px 6px; white-space: nowrap;"><?php echo htmlspecialchars($item['fei']); ?></td>-->
									<td style="padding: 3px 6px; white-space: nowrap;"><?php echo htmlspecialchars($item['fef']); ?></td>
									<td style="padding: 3px 6px; max-width: 120px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($item['obs']); ?>"><?php echo htmlspecialchars($item['obs']); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<?php } else { ?>
					<p class="text-muted" style="font-size: 12px; margin: 8px 0 0 0;"><span class="glyphicon glyphicon-ok-circle text-success"></span> No hay choferes sancionados en el listado.</p>
					<?php } ?>
				</div>
				<div class="modal-footer" style="padding: 6px 12px; border-top: 1px solid #dee2e6;">
					<button type="button" class="btn btn-default btn-xs" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal para Modificar Tiempo Llegada -->
	<div id="modalModificarTiempoLlegada" title="Modificar Hora de Llegada" style="display: none;">
		<form id="modificarTiempoLlegadaForm" class="form-horizontal normal">
			<input type="hidden" id="Man_Cod_Mod" name="Man_Cod" />
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs">No. Manifiesto:</label>
				<div class="col-xs-8">
					<input type="text" id="Man_Num_Modificar" name="Man_Num" class="form-control input-xs" readonly />
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Fecha de Llegada:</label>
				<div class="col-xs-8">
					<input type="date" id="Man_Fea_Modi" name="Man_Fea" readonly class="form-control input-xs" required />
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-4 control-label label-xs required">Hora de Llegada:</label>
				<div class="col-xs-8">
					<input type="time" id="Man_Fea_Hor_Modificar" name="Man_Fea_Hor" lang="es" class="form-control input-xs" required />
				</div>
			</div>
			<div style="text-align: center; margin-top: 15px;">
				<button type="button" class="btn btn-sm btn-primary" onclick="guardarModificacionTiempoLlegada();">
					<i class="glyphicon glyphicon-floppy-disk"></i> Guardar
				</button>
				<button type="button" class="btn btn-sm btn-default" onclick="$('#modalModificarTiempoLlegada').dialog('close');" style="margin-left: 10px;">
					<i class="glyphicon glyphicon-remove"></i> Cancelar
				</button>
			</div>
		</form>
	</div>

	<!-- Modal para Información del Manifiesto (Usuario Creador y Fecha de Creación) -->
	<div id="infoManifiestoDialog" title="Información del Registro" style="display: none;">
		<div style="padding: 12px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 6px;">
			<div style="background: white; border-radius: 4px; padding: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
				<div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 2px solid #e9ecef;">
					<h4 style="margin: 0; color: #495057; font-weight: 600; font-size: 14px;">
						<i class="glyphicon glyphicon-info-sign" style="color: #007bff; margin-right: 6px;"></i>
						Datos de Creación
					</h4>
				</div>
				
				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #007bff; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-user" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Usuario:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoUsuario">-</span>
					</div>
				</div>
				
				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #28a745; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-barcode" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Código:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoUsuCod">-</span>
					</div>
				</div>
				
				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #ffc107; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-calendar" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Fecha:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoFecha">-</span>
					</div>
				</div>								
				
				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #6f42c1; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-wrench" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Técnico:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoTecnico">-</span>
					</div>
				</div>
				
				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #17a2b8; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-user" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Chofer:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoChofer">-</span>
					</div>
				</div>

				<div style="margin-bottom: 6px; padding: 6px; background: #f8f9fa; border-left: 3px solid #6f42c1; border-radius: 3px;">
				<div style="margin-bottom: 8px; padding: 6px; background: #f8f9fa; border-left: 3px solid #fd7e14; border-radius: 3px;">
					<div style="display: flex; align-items: center;">
						<i class="glyphicon glyphicon-road" style="color: #6c757d; margin-right: 6px; font-size: 12px;"></i>
						<strong style="color: #495057; font-size: 11px; min-width: 100px;">Placa vehículo:</strong>
						<span style="color: #212529; font-size: 12px; font-weight: 500; margin-left: 4px;" id="infoManifiestoPlaca">-</span>
					</div>
				</div>
				
				<div style="text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #dee2e6;">
					<button type="button" class="btn btn-primary btn-sm" onclick="$('#infoManifiestoDialog').dialog('close');" style="min-width: 80px; padding: 4px 12px;">
						<i class="glyphicon glyphicon-ok"></i> Cerrar
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal de información de sanción de planta -->
	<div id="sancionPlantaDialog" title="Información de Sanción de Planta" style="display: none;">
		<div style="padding: 12px; background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); border-radius: 6px;">
			<div style="background: white; border-radius: 4px; padding: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); border-left: 4px solid #f0ad4e;">
				<h4 style="margin: 0 0 12px 0; color: #856404; font-weight: 600; font-size: 14px;">
					<i class="glyphicon glyphicon-alert" style="margin-right: 6px;"></i>
					La planta tiene una sanción activa y no puede realizar manifiestos hasta que finalice el período.
				</h4>
				<?php foreach ($plaSanciones as $s): 
					$obs = isset($s['Msa_Obs']) ? trim((string)$s['Msa_Obs']) : '';
					$fei = isset($s['Msa_Fei']) ? date('d/m/Y H:i', strtotime($s['Msa_Fei'])) : '-';
					$fef = isset($s['Msa_Fef']) ? date('d/m/Y H:i', strtotime($s['Msa_Fef'])) : '-';
					$nombre = isset($s['nombre']) ? htmlspecialchars($s['nombre'], ENT_QUOTES, 'UTF-8') : '';
				?>
				<div style="margin-bottom: 12px; padding: 10px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6;">
					<div style="margin-bottom: 6px; padding: 6px; background: #fff; border-left: 3px solid #f0ad4e;">
						<strong style="color: #495057; font-size: 11px;">Observación:</strong>
						<p style="margin: 4px 0 0 0; color: #212529; font-size: 12px;"><?php echo $obs !== '' ? htmlspecialchars($obs, ENT_QUOTES, 'UTF-8') : '(Sin observación)'; ?></p>
					</div>
					<div style="margin-bottom: 4px;">
						<strong style="color: #6c757d; font-size: 11px;">Fecha inicio:</strong>
						<span style="color: #212529; font-size: 12px;"><?php echo $fei; ?></span>
					</div>
					<div style="margin-bottom: 4px;">
						<strong style="color: #6c757d; font-size: 11px;">Fecha fin:</strong>
						<span style="color: #212529; font-size: 12px;"><?php echo $fef; ?></span>
					</div>
					<?php if ($nombre !== ''): ?>
					<div>
						<strong style="color: #6c757d; font-size: 11px;">Cliente/Planta:</strong>
						<span style="color: #212529; font-size: 12px;"><?php echo $nombre; ?></span>
					</div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
				<div style="text-align: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #dee2e6;">
					<button type="button" class="btn btn-warning btn-sm" onclick="$('#sancionPlantaDialog').dialog('close');" style="min-width: 80px; padding: 4px 12px;">
						<i class="glyphicon glyphicon-ok"></i> Cerrar
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal para Selección de Turno -->
	<div id="turnoDialog" title="Seleccionar Turno Disponible" style="display: none;">
		<div class="form-horizontal normal">
			<input type="hidden" id="turno_fecha" name="turno_fecha" value="<?php echo date('Y-m-d'); ?>">
			<div id="turnosContainer" class="turnos-modal-container">
				<div class="text-center" style="padding: 20px;">
					<i class="fa fa-spinner fa-spin fa-2x"></i>
					<p>Cargando turnos disponibles...</p>
				</div>
			</div>
		</div>
		<div style="text-align: center; margin-top: 10px;">
			<button class="btn btn-sm btn-default" type="button" onclick="$('#turnoDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
		</div>
	</div>
	<div id="certificadoDialog" title="Generar Certificado" style="display: none;">
		<div id="ambienteCertificado" style="display: block; margin-top: 30px">
			<form id="certificadoForm" class="form-horizontal normal">
				<div class="form-group">
					<label class="col-xs-2 control-label label-xs required">Cliente:</label>
					<div class="col-xs-9">
						<div class="input-group input-group-xs">
							<span id="Cert_Cli_Ced_Span" class="input-group-addon bold alert-info" style="min-width: 120px;"></span>
							<input type="hidden" id="Cert_Cli_Ced" name="Cert_Cli_Ced" required placeholder="Cedula/RUC">
							<input type="text" id="Cert_Cli_Nom" name="Cert_Cli_Nom" class="form-control input-xs" readonly placeholder="Nombre del Cliente">
							<span class="input-group-btn">
								<button type="button" class="btn btn-success btn-xs" onclick="cambiarAmbienteBusqueda();" title="Buscar Cliente">
									<span class="glyphicon glyphicon-search"></span>
								</button>
							</span>
						</div>
						<input type="hidden" id="Cert_Prs_Cod" name="Cert_Prs_Cod">
						<input type="hidden" id="Cert_Cli_Cod" name="Cert_Cli_Cod">
					</div>
				</div>
				<div class="form-group" style="margin-top: 10px;">
					<label class="col-xs-2 control-label label-xs required">Planta:</label>
					<div class="col-xs-8">
						<select id="Cert_Pla_Cod" name="Cert_Pla_Cod" class="form-control input-xs" required>
							<option value="">Seleccione planta...</option>
						</select>
					</div>
				</div>
				<div class="form-group" style="margin-top: 10px;">
					<label class="col-xs-2 control-label label-xs required">Fechas:</label>
					<div class="col-xs-8">
						<div class="input-group input-group-xs">
							<span class="input-group-addon bold alert-info">Desde</span>
							<input type="date" id="Cert_Fec_Des" name="Cert_Fec_Des" class="form-control input-xs" style="text-align: center;" required>
							<span class="input-group-addon bold alert-info">Hasta</span>
							<input type="date" id="Cert_Fec_Has" name="Cert_Fec_Has" class="form-control input-xs" style="text-align: center;" required>
						</div>
					</div>
				</div>
				<div class="form-group" style="margin-top: 10px;">
					<label class="col-xs-3 control-label label-xs">¿Desea Firmarlo?</label>
					<div class="col-xs-8">
						<div class="btn-group">
							<?php if (!$firmar_solo_no) { ?>
								<button id="btnCertSi" type="button" class="btn btn-xs <?php echo (!$firmar_solo_no ? 'btn-primary active' : 'btn-default'); ?>" style="width: 40px;" onclick="$('#Cert_Firmar').prop('checked', true); $(this).addClass('btn-primary active').removeClass('btn-default'); $('#btnCertNo').addClass('btn-default').removeClass('btn-primary active');">Si</button>
							<?php } ?>
							<?php if (!$firmar_solo_si) { ?>
								<button id="btnCertNo" type="button" class="btn btn-xs <?php echo ($firmar_solo_no ? 'btn-primary active' : 'btn-default'); ?>" style="width: 40px;" onclick="$('#Cert_Firmar').prop('checked', false); $(this).addClass('btn-primary active').removeClass('btn-default'); $('#btnCertSi').addClass('btn-default').removeClass('btn-primary active');">No</button>
							<?php } ?>
						</div>
						<input type="checkbox" id="Cert_Firmar" name="Cert_Firmar" style="display:none;" <?php echo ($firmar_solo_no ? '' : 'checked'); ?> />
					</div>
				</div>
			</form>
			<br>
			<div style="text-align: center;">
				<button class="btn btn-sm btn-primary" type="button" onclick="impCertificadoRango();"><i class="glyphicon glyphicon-print"></i> Generar Informe</button>
			</div>
		</div>

		<div id="ambienteBusqueda" style="display: none; padding: 10px 20px;">
			<form id="Cert_frm_bus" class="form-horizontal normal" onsubmit="return false;">
				<fieldset class="exa-fieldset" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
					<legend class="Titulos2">Busqueda de Clientes</legend>
					<div class="form-group" style="margin-bottom: 5px;">
						<label class="col-sm-2 control-label label-xs">Filtrar por:</label>
						<div class="col-sm-6 radioset">
							<input id="Cert_rad_ba1" name="Cert_op_opciones" type="radio" value="d" checked/><label for="Cert_rad_ba1">&nbsp;Apellido/Nombre&nbsp;</label>
							<input id="Cert_rad_ba2" name="Cert_op_opciones" type="radio" value="c"/><label for="Cert_rad_ba2">&nbsp;C&eacute;dula/RUC&nbsp;</label>
							<input id="Cert_rad_ba3" name="Cert_op_opciones" type="radio" value="p"/><label for="Cert_rad_ba3">&nbsp;Planta&nbsp;</label>
						</div>
						<div class="col-sm-4 text-right">
							<button class="btn btn-sm btn-primary" type="button" onclick="volverAmbienteCertificado()" style="margin-top: -3px; font-size: 11px;">
								<i class="glyphicon glyphicon-arrow-left"></i> Volver
							</button>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
						<div class="col-sm-9">
							<div class="input-group">
								<input type="text" id="Cert_search" name="Cert_search" class="form-control input-xs" placeholder="Ingrese dato...">
								<span class="input-group-btn">
									<button id="Cert_btnSearch" onclick="buscarClientesCertificado()" class="btn btn-success btn-xs" type="button"><span class="glyphicon glyphicon-search"></span> Buscar</button>
								</span>
							</div>
						</div>
					</div>
				</fieldset>
			</form>
			<div style="min-height: 250px;">
				<table id="Cert_tableResult"></table>
				<div id="Cert_tableResultPager"></div>
			</div>
		</div>
	</div>

	<div id="modalSelectorPlantaSaldos" title="Seleccionar Planta para visualizar saldos" style="display:none;">
		<div class="planta-filtro-wrap">
			<label class="planta-filtro-label">Filtro de plantas</label>
			<div class="input-group input-group-sm">
				<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
				<input type="text" id="txtBuscarPlantaSaldos" class="form-control input-sm" placeholder="Buscar por planta, ciudad o dirección...">
			</div>
		</div>
		<div id="wrapGridPlantasSaldos" style="min-height: 320px; width: 100%; overflow-x: hidden;">
			<table id="tablaPlantasSaldos"></table>
			<div id="tablaPlantasSaldosPager"></div>
		</div>
		
	</div>

	<script src="../VALIDACIONES/man_val_manifiesto.js?a=305"></script>
	<script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
	<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=2"></script>
	<script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
	<script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>

</HTML>
<?php
/* Cierra las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>