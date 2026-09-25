<?php
/**
 * Interpreta logs de auditoria a lenguaje de usuario.
 *
 * @package auditoria.LOGICA
 */

if (!function_exists('aud_h')) {
	function aud_h($val) {
		if ($val === null) {
			return '';
		}
		return htmlspecialchars((string)$val, ENT_QUOTES);
	}
}

function aud_etiquetas_campo()
{
	return array(
		'Pec_Cod' => 'Periodo',
		'Com_Cod' => 'Comprobante',
		'Com_Num' => 'Numero de comprobante',
		'Com_Fec' => 'Fecha del comprobante',
		'Com_Con' => 'Concepto',
		'Com_Val' => 'Valor',
		'Com_Est' => 'Estado',
		'Tia_Cod' => 'Tipo de asiento',
		'Prv_Cod' => 'Proveedor',
		'Cli_Cod' => 'Cliente',
		'Asi_Cod' => 'Asiento',
		'Asi_Deh' => 'Debe / Haber',
		'Asi_Val' => 'Valor del asiento',
		'Asi_Con' => 'Concepto del asiento',
		'Asi_Fec' => 'Fecha del asiento',
		'Cta_Cod' => 'Cuenta contable',
		'Emp_Cod' => 'Empresa',
		'Suc_Cod' => 'Sucursal',
		'Usu_Cod' => 'Usuario',
		'Pcs_Cod' => 'Proceso',
		'Log_Int' => 'Referencia',
		'Man_Cod' => 'Manifiesto',
		'Man_Num' => 'Numero de manifiesto',
		'Man_Fec' => 'Fecha del manifiesto',
		'Man_Pes' => 'Peso',
		'Man_Pun' => 'Tarifa',
		'Man_Est' => 'Estado',
		'Man_Tip' => 'Tipo de manifiesto',
		'Man_Con' => 'Concepto del manifiesto',
		'Man_Obs' => 'Observacion',
		'Man_Obe' => 'Observacion',
		'Man_Gui' => 'Guia',
		'Pla_Cod' => 'Planta',
		'Veh_Cod' => 'Vehiculo',
		'Cho_Cod' => 'Chofer',
		'Tud_Cod' => 'Turno',
		'Tur_Cod' => 'Configuracion de turnos',
		'Tud_Fec' => 'Fecha del turno',
		'Tud_Hin' => 'Hora de inicio',
		'Tud_Hfi' => 'Hora de fin',
		'Tud_Cup' => 'Cupos',
		'Tud_Est' => 'Estado del turno',
		'Tur_Fei' => 'Inicio de vigencia',
		'Tur_Fef' => 'Fin de vigencia',
		'Tur_Est' => 'Estado de turnos',
		'MVis_Cod' => 'Visitante',
		'MVis_Est' => 'Estado del visitante',
		'MVis_Nac' => 'Nacionalidad',
		'MVis_Eci' => 'Estado civil',
		'MVis_Obs' => 'Observacion del visitante',
		'Man_Eve' => 'Evento',
		'Man_ENom' => 'Nombre del evento',
		'Man_EFei' => 'Inicio del evento',
		'Man_EFef' => 'Fin del evento',
		'Man_EEst' => 'Estado del evento',
		'Man_Vig' => 'Vigente',
		'Man_Ehor' => 'Horas del evento',
		'Ama_Val' => 'Valor del anticipo',
		'Ama_Est' => 'Estado del anticipo',
		'Vet_Cod' => 'Venta',
		'Vet_Num' => 'Numero de factura',
		'Vet_Obs' => 'Observacion',
		'Vet_Des' => 'Fecha de la venta',
		'Vet_Hor' => 'Hora de la venta',
		'Vet_Xml' => 'Clave de acceso',
		'Vet_Aut' => 'Autorizacion',
		'Vet_Sri' => 'Autorizacion SRI',
		'Vet_Prop' => 'Propina',
		'Vet_Ide' => 'Identificacion',
		'Vet_Can' => 'Cantidad',
		'Vet_Pru' => 'Precio unitario',
		'Vet_Imp' => 'Importe',
		'Vet_Ite' => 'Item',
		'Tic_Cod' => 'Tipo de comprobante',
		'Caj_Cod' => 'Caja',
		'Caj_Fec' => 'Fecha de apertura',
		'Caj_Fef' => 'Fecha de cierre',
		'Caj_Hoi' => 'Hora de inicio',
		'Caj_Hof' => 'Hora de cierre',
		'Caj_Obs' => 'Observacion',
		'Caj_Est' => 'Estado de caja',
		'Caj_Gen' => 'Generada',
		'Caj_Exi' => 'Efectivo',
		'Vnd_Cod' => 'Vendedor',
		'Pro_Cod' => 'Producto',
		'InvDis_Cod' => 'Dispositivo',
		'DisUsr_Cod' => 'Asignacion de dispositivo',
		'Cfg_Cod' => 'Regla de monitoreo',
		'Cfg_Est' => 'Estado de la regla',
		'Org_Cod' => 'Modulo / directorio',
		'Not_Cod' => 'Notificacion',
		'Not_Est' => 'Estado de notificacion',
		'Correo' => 'Correo electronico',
		'Ses_Cod' => 'Sesion',
		'Ses_Est' => 'Estado de sesion',
		'Ses_Ip' => 'Direccion IP',
		'Ses_Min_Uso' => 'Minutos de uso',
		'Pun_Cod' => 'Punto de emision',
		'Aut_Cod' => 'Punto de emision'
	);
}

function aud_modulos_tabla()
{
	return array(
		'comprobantes' => 'Contabilidad',
		'asientos' => 'Contabilidad',
		'usuarios' => 'Administracion',
		'compras' => 'Compras',
		'proveedore' => 'Compras',
		'facturas' => 'Facturacion',
		'ventas' => 'Facturacion',
		'ventas_det' => 'Facturacion',
		'caja_aper' => 'Facturacion',
		'clientes' => 'Facturacion',
		'manifiesto' => 'Relavera / Manifiestos',
		'manifiesto_turnos_cab' => 'Relavera / Turnos',
		'manifiesto_turnos_det' => 'Relavera / Turnos',
		'manifiesto_visitante' => 'Relavera / Visitantes',
		'manifiesto_evento' => 'Relavera / Visitantes',
		'anticipos_clientes' => 'Relavera / Anticipos',
		'det_ant_cccc' => 'Relavera / Anticipos',
		'manifiesto_anticipo' => 'Relavera / Anticipos',
		'pag_anticipo_cli' => 'Relavera / Anticipos',
		'manifiesto_contratos' => 'Relavera / Contratos',
		'manifiesto_contratos_docu' => 'Relavera / Contratos',
		'param_manifiesto' => 'Relavera / Contratos',
		'maquinaria_alimentacion' => 'Relavera / Maquinaria',
		'maquinaria_dispensador' => 'Relavera / Maquinaria',
		'maquinaria_dispensador_det' => 'Relavera / Maquinaria',
		'maquinaria_dispensador_cierre' => 'Relavera / Maquinaria',
		'maquinaria_equipo' => 'Relavera / Maquinaria',
		'maquinaria_horometro' => 'Relavera / Maquinaria',
		'manifiesto_liquidacion_maq' => 'Relavera / Maquinaria',
		'manifiesto_tecnico' => 'Relavera / Tecnicos',
		'manifiesto_mensajes' => 'Relavera / Tecnicos',
		'chofer' => 'Relavera / Operario y Vehiculos',
		'vehiculo' => 'Relavera / Operario y Vehiculos',
		'personal' => 'Relavera / Operario y Vehiculos',
		'inventario_dispositivos' => 'Relavera / Inventario',
		'usuario_inventario' => 'Relavera / Inventario',
		'ccpp_cobrar' => 'Relavera / Cobranzas',
		'det_ccpp_c' => 'Relavera / Cobranzas',
		'pago_venta' => 'Relavera / Cobranzas',
		'ventas_compr' => 'Relavera / Cobranzas'
	);
}

/**
 * Casos de simulacion: cada tabla se ata a un proceso cuyo modulo raiz coincide.
 * mod_like se usa en SQL; mod_re / mod_not_re validan que no haya cruce.
 */
function aud_sim_casos()
{
	return array(
		array(
			'id' => 'comprobantes',
			'pcs_noms' => array('con_alt_compr', 'con_pri_compr'),
			'mod_like' => 'ontabilid',
			'mod_re' => '/contabilid/i',
			'mod_not_re' => '/auditoria|relavera|facturaci|tesorer/i',
			'tabs' => array('comprobantes', 'asientos')
		),
		array(
			'id' => 'manifiesto',
			'pcs_noms' => array('man_alt_manifiesto'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|manifiesto/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto')
		),
		array(
			'id' => 'turnos',
			'pcs_noms' => array('man_adm_turnos'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|turno/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_turnos_cab', 'manifiesto_turnos_det')
		),
		array(
			'id' => 'visitantes',
			'pcs_noms' => array('man_alt_visitantes', 'man_alt_visitante'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|visitante/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_visitante')
		),
		array(
			'id' => 'eventos',
			'pcs_noms' => array('man_adm_configuracion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|visitante|evento/i',
			'mod_not_re' => '/auditoria|contabilid/i',
			'tabs' => array('manifiesto_evento')
		),
		array(
			'id' => 'ventas',
			'pcs_noms' => array('fac_alt_fac_ven_3.2', 'fac_alt_fac_ven'),
			'mod_like' => 'acturaci',
			'mod_re' => '/facturaci/i',
			'mod_not_re' => '/auditoria|contabilid|relavera/i',
			'tabs' => array('ventas', 'ventas_det')
		),
		array(
			'id' => 'caja',
			'pcs_noms' => array('fac_alt_caja_2.0', 'fac_alt_caja', 'fac_alt_aper_caja'),
			'mod_like' => 'acturaci',
			'mod_re' => '/facturaci/i',
			'mod_not_re' => '/auditoria|contabilid|relavera/i',
			'tabs' => array('caja_aper')
		),
		array(
			'id' => 'anticipos',
			'pcs_noms' => array('man_ant_1.0', 'man_est_cuenta_1.0'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|anticipo/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('anticipos_clientes', 'det_ant_cccc', 'manifiesto_anticipo', 'pag_anticipo_cli')
		),
		array(
			'id' => 'contratos',
			'pcs_noms' => array('man_alt_contratos', 'man_con_planta', 'man_alt_param'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|contrato|planta/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('manifiesto_contratos', 'manifiesto_contratos_docu', 'param_manifiesto')
		),
		array(
			'id' => 'maquinaria',
			'pcs_noms' => array('man_alt_maquinaria_dispensador', 'man_alt_maquinaria_horometro', 'man_alt_maquinaria_preliquidacion', 'man_alt_alimentacion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|maquinaria|dispensador|horometro|alimentacion/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('maquinaria_alimentacion', 'maquinaria_dispensador', 'maquinaria_dispensador_det', 'maquinaria_dispensador_cierre', 'maquinaria_equipo', 'maquinaria_horometro', 'manifiesto_liquidacion_maq')
		),
		array(
			'id' => 'tecnicos',
			'pcs_noms' => array('man_tec_1.0', 'man_tec_camp_1.0'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|tecnico/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('manifiesto_tecnico', 'manifiesto_mensajes')
		),
		array(
			'id' => 'operario_vehiculos',
			'pcs_noms' => array('man_alt_vehiculos_choferes', 'man_alt_datos_choferes_vehiculos'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|vehiculo|chofer|operario/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('chofer', 'vehiculo', 'personal')
		),
		array(
			'id' => 'inventario',
			'pcs_noms' => array('inventario_dispositivos', 'dispositivos_usuario', 'man_adm_usuarios', 'man_adm_notificacion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|inventario|dispositivo/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('inventario_dispositivos', 'usuario_inventario', 'dispositivos_usuario')
		),
		array(
			'id' => 'cobranzas',
			'pcs_noms' => array('man_est_cuenta_1.0', 'man_fac_man', 'man_alt_fac'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|cobranza|cuenta|pago/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('ccpp_cobrar', 'det_ccpp_c', 'pago_venta', 'ventas_compr')
		)
	);
}

function aud_sim_caso_por_id($id)
{
	foreach (aud_sim_casos() as $c) {
		if ($c['id'] === $id) {
			return $c;
		}
	}
	return null;
}

/** El modulo resuelto del proceso debe coincidir con el caso de simulacion. */
function aud_sim_modulo_valido($row, $caso)
{
	if (!is_array($row) || !is_array($caso) || empty($row['Pcs_Cod'])) {
		return false;
	}
	$mod = '';
	if (!empty($row['Mod_Des'])) {
		$mod = trim($row['Mod_Des']);
	} elseif (!empty($row['Org_Des'])) {
		$mod = trim($row['Org_Des']);
	}
	if ($mod === '') {
		return false;
	}
	if (!preg_match($caso['mod_re'], $mod)) {
		return false;
	}
	if (!empty($caso['mod_not_re']) && preg_match($caso['mod_not_re'], $mod)) {
		return false;
	}
	return true;
}

/**
 * Sube directorio -> padre -> abuelo y solo acepta el nodo raiz (Org_Niv=0).
 * Un directorio intermedio nunca es el modulo.
 */
function aud_resolver_modulo_arbol($nodo, $padre = null, $abuelo = null, $bis = null)
{
	$cands = array($nodo, $padre, $abuelo, $bis);
	foreach ($cands as $n) {
		if (!is_array($n) || empty($n['Org_Cod'])) {
			continue;
		}
		$niv = isset($n['Org_Niv']) ? (int)$n['Org_Niv'] : -1;
		if ($niv === 0 && !empty($n['Org_Des'])) {
			return array(
				'Org_Cod' => (int)$n['Org_Cod'],
				'Org_Des' => trim($n['Org_Des'])
			);
		}
	}
	return null;
}

function aud_nombre_registro($tabNom, $tabAli, $tabDes)
{
	$map = array(
		'comprobantes' => 'comprobante contable',
		'asientos' => 'asiento contable',
		'usuarios' => 'usuario',
		'compras' => 'compra',
		'facturas' => 'factura',
		'ventas' => 'factura de venta',
		'ventas_det' => 'detalle de venta',
		'caja_aper' => 'caja',
		'manifiesto' => 'manifiesto',
		'manifiesto_turnos_cab' => 'turno',
		'manifiesto_turnos_det' => 'detalle de turno',
		'manifiesto_visitante' => 'visitante',
		'manifiesto_evento' => 'evento',
		'anticipos_clientes' => 'anticipo de cliente',
		'det_ant_cccc' => 'detalle de anticipo',
		'manifiesto_anticipo' => 'anticipo',
		'pag_anticipo_cli' => 'pago de anticipo',
		'manifiesto_contratos' => 'contrato con planta',
		'manifiesto_contratos_docu' => 'documento de contrato',
		'param_manifiesto' => 'parametro de manifiesto',
		'maquinaria_alimentacion' => 'alimentacion de maquinaria',
		'maquinaria_dispensador' => 'dispensador de gasolina',
		'maquinaria_dispensador_det' => 'detalle de dispensador',
		'maquinaria_dispensador_cierre' => 'cierre de dispensador',
		'maquinaria_equipo' => 'equipo de maquinaria',
		'maquinaria_horometro' => 'horometro de maquinaria',
		'manifiesto_liquidacion_maq' => 'liquidacion de maquinaria',
		'manifiesto_tecnico' => 'asignacion de tecnico',
		'manifiesto_mensajes' => 'mensaje',
		'chofer' => 'chofer',
		'vehiculo' => 'vehiculo',
		'personal' => 'personal',
		'inventario_dispositivos' => 'dispositivo de inventario',
		'usuario_inventario' => 'usuario de inventario',
		'ccpp_cobrar' => 'cuenta por cobrar',
		'det_ccpp_c' => 'detalle de cuenta por cobrar',
		'pago_venta' => 'pago de venta',
		'ventas_compr' => 'comprobante de venta'
	);
	$k = strtolower(trim((string)$tabNom));
	if (isset($map[$k])) {
		return $map[$k];
	}
	if (strlen(trim((string)$tabDes)) > 0) {
		return strtolower(trim($tabDes));
	}
	if (strlen(trim((string)$tabAli)) > 0) {
		return strtolower(trim($tabAli));
	}
	return $k !== '' ? strtolower(str_replace('_', ' ', $k)) : 'registro';
}

function aud_verbo_evento($eveIni, $eveDes)
{
	$ini = strtoupper(trim((string)$eveIni));
	if ($ini === 'I') {
		return 'Registro';
	}
	if ($ini === 'U') {
		return 'Modifico';
	}
	if ($ini === 'D') {
		return 'Elimino';
	}
	if ($ini === 'F') {
		return 'Intento fallido';
	}
	$des = trim((string)$eveDes);
	$mapDes = array(
		'Insertar' => 'Nuevo registro',
		'Actualizar' => 'Modificacion',
		'Eliminar' => 'Eliminacion',
		'Fallido' => 'Intento fallido'
	);
	if ($des !== '' && isset($mapDes[$des])) {
		return $mapDes[$des];
	}
	return $des !== '' ? $des : 'Actividad';
}

function aud_etiqueta_evento($eveIni, $eveDes)
{
	$ini = strtoupper(trim((string)$eveIni));
	if ($ini === 'I') {
		return 'Nuevo registro';
	}
	if ($ini === 'U') {
		return 'Modificacion';
	}
	if ($ini === 'D') {
		return 'Eliminacion';
	}
	if ($ini === 'F') {
		return 'Intento fallido';
	}
	return aud_verbo_evento($eveIni, $eveDes);
}

function aud_nombre_modulo($row)
{
	if (!empty($row['Mod_Des'])) {
		return trim($row['Mod_Des']);
	}
	$arbol = aud_resolver_modulo_arbol(
		array(
			'Org_Cod' => isset($row['Dir_Cod']) ? $row['Dir_Cod'] : (isset($row['Org_Cod']) ? $row['Org_Cod'] : 0),
			'Org_Des' => isset($row['Dir_Des']) ? $row['Dir_Des'] : (isset($row['Org_Des']) ? $row['Org_Des'] : ''),
			'Org_Niv' => isset($row['Org_Niv']) ? $row['Org_Niv'] : (isset($row['Dir_Niv']) ? $row['Dir_Niv'] : -1)
		),
		array(
			'Org_Cod' => isset($row['Padre_Cod']) ? $row['Padre_Cod'] : 0,
			'Org_Des' => isset($row['Padre_Des']) ? $row['Padre_Des'] : '',
			'Org_Niv' => isset($row['Padre_Niv']) ? $row['Padre_Niv'] : -1
		),
		array(
			'Org_Cod' => isset($row['Abuelo_Cod']) ? $row['Abuelo_Cod'] : 0,
			'Org_Des' => isset($row['Abuelo_Des']) ? $row['Abuelo_Des'] : '',
			'Org_Niv' => isset($row['Abuelo_Niv']) ? $row['Abuelo_Niv'] : -1
		)
	);
	if ($arbol !== null) {
		return $arbol['Org_Des'];
	}
	$tieneProceso = !empty($row['Pcs_Cod']) || !empty($row['Pcs_Lin']) || !empty($row['Pcs_Nom']);
	if ($tieneProceso) {
		return 'Sin modulo';
	}
	$tab = isset($row['Tab_Nom']) ? strtolower(trim($row['Tab_Nom'])) : '';
	$map = aud_modulos_tabla();
	if ($tab !== '' && isset($map[$tab])) {
		return $map[$tab];
	}
	if (!empty($row['Tab_Des'])) {
		return trim($row['Tab_Des']);
	}
	if (!empty($row['Tab_Ali'])) {
		return trim($row['Tab_Ali']);
	}
	return $tab !== '' ? $tab : 'Sistema';
}

function aud_nombre_directorio($row)
{
	if (!empty($row['Dir_Des'])) {
		return trim($row['Dir_Des']);
	}
	if (!empty($row['Org_Des'])) {
		return trim($row['Org_Des']);
	}
	return '';
}

function aud_nombre_empresa($row)
{
	if (!empty($row['Emp_Nom'])) {
		$nom = trim($row['Emp_Nom']);
		if ($nom !== '') {
			return $nom;
		}
	}
	if (!empty($row['Emp_Cod'])) {
		return 'Empresa '.$row['Emp_Cod'];
	}
	return 'Sin empresa';
}

function aud_nombre_sucursal($row)
{
	if (!empty($row['Suc_Des'])) {
		$nom = trim($row['Suc_Des']);
		if ($nom !== '') {
			return $nom;
		}
	}
	if (!empty($row['Suc_Cod'])) {
		return 'Sucursal '.$row['Suc_Cod'];
	}
	return '';
}

function aud_nombre_usuario($row)
{
	$nom = isset($row['Usu_Nom']) ? trim($row['Usu_Nom']) : '';
	if ($nom !== '') {
		return $nom;
	}
	if (!empty($row['Usu_Cod'])) {
		return 'Usuario '.$row['Usu_Cod'];
	}
	return 'Usuario no identificado';
}

function aud_nombre_proceso($row)
{
	if (!empty($row['Pcs_Lin'])) {
		return trim($row['Pcs_Lin']);
	}
	if (!empty($row['Pcs_Det'])) {
		return trim($row['Pcs_Det']);
	}
	if (!empty($row['Pcs_Nom'])) {
		return trim($row['Pcs_Nom']);
	}
	$fromInt = aud_pcs_nom_desde_int(isset($row['Log_Int']) ? $row['Log_Int'] : '');
	if ($fromInt !== '') {
		return $fromInt.' (no registrado)';
	}
	return 'Proceso no registrado';
}

function aud_pcs_nom_desde_int($int)
{
	$int = (string)$int;
	if (preg_match('/Pcs_Nom=([^|;]+)/', $int, $m)) {
		return trim($m[1]);
	}
	return '';
}

function aud_valores_anteriores($row)
{
	$int = isset($row['Log_Int']) ? (string)$row['Log_Int'] : '';
	$pos = strpos($int, 'OLD:');
	if ($pos === false) {
		return array();
	}
	$chunk = substr($int, $pos + 4);
	$chunk = preg_replace('/\|\|.*$/', '', $chunk);
	$out = array();
	foreach (explode(',', $chunk) as $part) {
		$eq = explode('=', $part, 2);
		if (count($eq) === 2) {
			$atr = trim($eq[0]);
			if ($atr !== '') {
				$out[$atr] = trim($eq[1]);
			}
		}
	}
	return $out;
}

function aud_identificador($row)
{
	$int = isset($row['Log_Int']) ? trim(str_replace('~', '', (string)$row['Log_Int'])) : '';
	if ($int === '') {
		return '';
	}
	$int = preg_replace('/Pcs_Nom=[^|;]+\s*\|\|\s*/', '', $int);
	$int = preg_replace('/\s*\|\|\s*OLD:.*$/', '', $int);
	$int = trim($int);
	if ($int === '') {
		return '';
	}
	$bits = array();
	$chunks = preg_split('/\s*\|\|\s*|\s*;\s*/', $int);
	foreach ($chunks as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '' || stripos($chunk, 'Pcs_Nom=') === 0) {
			continue;
		}
		if (strpos($chunk, '=') !== false) {
			$partes = explode('=', $chunk, 2);
			$eti = aud_humanizar_campo(trim($partes[0]));
			$val = isset($partes[1]) ? trim($partes[1]) : '';
			if ($val !== '') {
				$bits[] = $eti.': '.$val;
			} else {
				$bits[] = $eti;
			}
		} else {
			$bits[] = $chunk;
		}
		if (count($bits) >= 3) {
			break;
		}
	}
	return implode(' · ', $bits);
}

function aud_etiqueta_campo($atr, $rowCampo)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	if (is_array($rowCampo)) {
		$ali = !empty($rowCampo['Cam_Ali']) ? trim($rowCampo['Cam_Ali']) : '';
		$des = !empty($rowCampo['Cam_Des']) ? trim($rowCampo['Cam_Des']) : '';
		// Si el alias sigue siendo el codigo tecnico (Cam_Nom), humanizar.
		if ($ali !== '' && strcasecmp($ali, $atr) !== 0 && strpos($ali, '_') === false) {
			return $ali;
		}
		if ($des !== '' && strcasecmp($des, $atr) !== 0 && strpos($des, '_') === false) {
			return $des;
		}
		if ($ali !== '' && strcasecmp($ali, $atr) !== 0) {
			return $ali;
		}
	}
	return aud_humanizar_campo($atr);
}

/**
 * Explica que significa el VALOR concreto (no el nombre del campo).
 * Ej.: A => "Activo: el registro esta vigente";
 *      42 => "El valor 42 corresponde al usuario Juan Perez".
 */
function aud_descripcion_meta($atr, $valNatural, $valRaw, $rowCampo = null)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	$eti = aud_etiqueta_campo($atr, $rowCampo);
	$valNatural = trim((string)$valNatural);
	$valRaw = trim((string)$valRaw);
	$rawUp = strtoupper($valRaw);

	if ($valNatural === '' || $valNatural === '(sin valor)') {
		return 'Sin valor: no se indico informacion en este dato.';
	}

	$suf = '';
	$partes = explode('_', $atr);
	if (count($partes) >= 2) {
		$suf = strtolower($partes[count($partes) - 1]);
	}
	$esCodigo = ($suf === 'cod' || preg_match('/_Cod$/', $atr));
	$codigoResuelto = ($esCodigo && $valRaw !== '' && preg_match('/^\d+$/', $valRaw) && $valNatural !== $valRaw);

	// Estados: significado del valor A/I/C/S/F
	$estados = array(
		'Man_Est', 'Tud_Est', 'Tur_Est', 'MVis_Est', 'Man_EEst', 'Ama_Est',
		'Com_Est', 'Cfg_Est', 'Not_Est', 'Caj_Est', 'Ses_Est'
	);
	if (in_array($atr, $estados) || $suf === 'est') {
		$mapEst = array(
			'A' => 'Activo: el registro esta vigente y disponible para usarse.',
			'I' => 'Inactivo: el registro fue anulado o deshabilitado.',
			'C' => 'Cerrado: el registro quedo finalizado y ya no admite cambios normales.',
			'S' => 'Suspendido: el registro esta temporalmente detenido.',
			'F' => 'Forzado: el cierre se hizo de manera administrativa (no por el usuario).'
		);
		if ($atr === 'Caj_Est') {
			$mapEst['A'] = 'Abierta: la caja esta en operacion.';
			$mapEst['C'] = 'Cerrada: la caja ya fue cuadrada o cerrada.';
		}
		if ($atr === 'Ses_Est') {
			$mapEst['A'] = 'Activa: la sesion del usuario sigue abierta.';
			$mapEst['C'] = 'Cerrada: el usuario cerro sesion normalmente.';
			$mapEst['I'] = 'Inactividad: la sesion se cerro automaticamente por falta de uso.';
			$mapEst['F'] = 'Forzada: un administrador cerro la sesion del usuario.';
		}
		if (isset($mapEst[$rawUp])) {
			return $mapEst[$rawUp];
		}
		return 'El valor "'.$valNatural.'" es el estado actual de '.$eti.'.';
	}

	// Si / No
	if (in_array($atr, array('Caj_Gen', 'Man_Vig', 'Cfg_Activo')) || $suf === 'vig' || $suf === 'gen') {
		if ($rawUp === 'S' || $rawUp === 'A' || strcasecmp($valNatural, 'Si') === 0) {
			return 'Si: esta opcion esta marcada / habilitada.';
		}
		if ($rawUp === 'N' || $rawUp === 'I' || strcasecmp($valNatural, 'No') === 0) {
			return 'No: esta opcion no esta marcada / esta deshabilitada.';
		}
	}

	if ($atr === 'Asi_Deh') {
		if ($rawUp === 'D' || stripos($valNatural, 'Debe') !== false) {
			return 'Debe: el monto se cargo al lado Debe del asiento.';
		}
		if ($rawUp === 'H' || stripos($valNatural, 'Haber') !== false) {
			return 'Haber: el monto se cargo al lado Haber del asiento.';
		}
	}

	if ($atr === 'Man_Tip') {
		$mapTip = array(
			'P' => 'Productor: tipo de manifiesto de productor.',
			'C' => 'Comercial: tipo de manifiesto comercial.',
			'T' => 'Transporte: tipo de manifiesto de transporte.',
			'V' => 'Visitante: tipo asociado a visitante.'
		);
		if (isset($mapTip[$rawUp])) {
			return $mapTip[$rawUp];
		}
	}

	// Codigos resueltos a nombre (usuarios anadidos, plantas, clientes, etc.)
	if ($codigoResuelto) {
		$quien = array(
			'Usu_Cod' => 'usuario',
			'Cli_Cod' => 'cliente',
			'Prv_Cod' => 'proveedor',
			'Pla_Cod' => 'planta',
			'Veh_Cod' => 'vehiculo',
			'Cho_Cod' => 'chofer',
			'Pro_Cod' => 'producto',
			'Vnd_Cod' => 'vendedor',
			'Emp_Cod' => 'empresa',
			'Suc_Cod' => 'sucursal',
			'Pcs_Cod' => 'proceso',
			'Org_Cod' => 'modulo o area',
			'Man_Cod' => 'manifiesto',
			'Vet_Cod' => 'venta',
			'Com_Cod' => 'comprobante',
			'Asi_Cod' => 'asiento',
			'Caj_Cod' => 'caja',
			'InvDis_Cod' => 'dispositivo',
			'DisUsr_Cod' => 'asignacion de dispositivo'
		);
		$tipo = isset($quien[$atr]) ? $quien[$atr] : strtolower($eti);
		return 'El valor '.$valRaw.' corresponde al '.$tipo.' "'.$valNatural.'".';
	}

	if ($esCodigo && preg_match('/^\d+$/', $valRaw)) {
		return 'El valor '.$valRaw.' es el codigo interno de "'.$eti.'". No se pudo obtener el nombre asociado.';
	}

	if ($suf === 'num') {
		return 'El valor "'.$valNatural.'" es el numero que identifica este documento.';
	}
	if ($suf === 'fec' || $suf === 'fei' || $suf === 'fef' || ($suf === 'des' && preg_match('/^\d{4}-\d{2}-\d{2}/', $valNatural))) {
		return 'El valor "'.$valNatural.'" es la fecha registrada en este movimiento.';
	}
	if ($suf === 'hor' || $suf === 'hin' || $suf === 'hfi' || $suf === 'hoi' || $suf === 'hof') {
		return 'El valor "'.$valNatural.'" es la hora registrada en este movimiento.';
	}
	if ($suf === 'val' || $suf === 'imp' || $suf === 'pru' || $suf === 'prop' || $suf === 'exi' || $suf === 'pun') {
		return 'El valor "'.$valNatural.'" es el monto monetario registrado.';
	}
	if ($suf === 'pes') {
		return 'El valor "'.$valNatural.'" es el peso registrado.';
	}
	if ($suf === 'can' || $suf === 'cup') {
		return 'El valor "'.$valNatural.'" es la cantidad de unidades registrada.';
	}
	if ($suf === 'obs' || $suf === 'obe' || $suf === 'con') {
		return 'El valor es el texto: "'.$valNatural.'".';
	}
	if ($atr === 'Ses_Ip') {
		return 'El valor "'.$valNatural.'" es la direccion IP de origen de la accion.';
	}

	if (is_array($rowCampo) && !empty($rowCampo['Cam_Des'])) {
		$desCat = trim($rowCampo['Cam_Des']);
		if ($desCat !== '' && strcasecmp($desCat, $atr) !== 0 && strpos($desCat, '_') === false) {
			return 'El valor "'.$valNatural.'" significa: '.$desCat.'.';
		}
	}

	return 'El valor registrado es "'.$valNatural.'".';
}
function aud_parse_cam_val($campos, $valores)
{
	$campos = str_replace('`', '', (string)$campos);
	$campos = str_replace('(', '', $campos);
	$campos = str_replace(')', '', $campos);
	$Arr = explode(',', $campos);
	$valores = (string)$valores;

	// ~...~ delimita textos (puede incluir comas). Si hay ~, parsear quitando
	// delimitadores; las comas internas se protegen con un marcador temporal.
	// Nunca convertir ~ de apertura/cierre en comas (evita ",D," / ",texto,").
	if (strpos($valores, '~') !== false || substr_count($campos, ',') != substr_count($valores, ',')) {
		$count = strlen($valores);
		$str_ = '';
		$contador = 0;
		for ($i = 0; $i < $count; $i++) {
			$char = $valores[$i];
			if ($char === '~') {
				$contador = ($contador == 0) ? 1 : 0;
				continue;
			}
			if ($char === ',' && $contador == 1) {
				$str_ .= "\x01";
			} else {
				$str_ .= $char;
			}
		}
		$Arr_val = explode(',', $str_);
		foreach ($Arr_val as $k => $v) {
			$Arr_val[$k] = str_replace("\x01", ',', $v);
		}
	} else {
		$Arr_val = explode(',', $valores);
	}

	$pares = array();
	$max = count($Arr);
	for ($i = 0; $i < $max; $i++) {
		$atr = trim($Arr[$i]);
		if ($atr === '') {
			continue;
		}
		$val = isset($Arr_val[$i]) ? $Arr_val[$i] : '';
		$val = trim($val, " \t\n\r\0\x0B'\"");
		$val = trim($val, '~');
		$pares[] = array('atr' => $atr, 'val' => $val);
	}
	return $pares;
}

function aud_formato_valor($atr, $val)
{
	$atr = trim($atr);
	$val = trim((string)$val);
	if ($val === '' || strtoupper($val) === 'NULL') {
		return '(sin valor)';
	}
	if ($atr === 'Asi_Deh') {
		$u = strtoupper(trim($val, " \t,"));
		if ($u === 'D') {
			return 'Debe';
		}
		if ($u === 'H') {
			return 'Haber';
		}
	}
	if ($atr === 'Com_Val' || $atr === 'Asi_Val' || $atr === 'Man_Pun' || $atr === 'Ama_Val' || $atr === 'Vet_Pru' || $atr === 'Vet_Imp' || $atr === 'Vet_Prop' || $atr === 'Caj_Exi') {
		if (is_numeric($val)) {
			return number_format((float)$val, 2, '.', ',');
		}
	}
	if ($atr === 'Man_Pes') {
		if (is_numeric($val)) {
			return number_format((float)$val, 2, '.', ',').' kg';
		}
	}
	if ($atr === 'Caj_Est') {
		$u = strtoupper($val);
		if ($u === 'A') {
			return 'Abierta';
		}
		if ($u === 'C') {
			return 'Cerrada';
		}
	}
	if ($atr === 'Ses_Est') {
		$mapSes = array('A' => 'Activa', 'C' => 'Cerrada', 'I' => 'Cerrada por inactividad', 'F' => 'Cierre forzado');
		$u = strtoupper($val);
		if (isset($mapSes[$u])) {
			return $mapSes[$u];
		}
	}
	if ($atr === 'Caj_Gen' || $atr === 'Man_Vig' || $atr === 'Cfg_Activo') {
		$u = strtoupper($val);
		if ($u === 'S' || $u === 'A') {
			return 'Si';
		}
		if ($u === 'N' || $u === 'I') {
			return 'No';
		}
	}
	$estados = array('Man_Est', 'Tud_Est', 'Tur_Est', 'MVis_Est', 'Man_EEst', 'Ama_Est', 'Com_Est', 'Cfg_Est', 'Not_Est');
	if (in_array($atr, $estados)) {
		$u = strtoupper($val);
		if ($u === 'A') {
			return 'Activo';
		}
		if ($u === 'I') {
			return 'Inactivo';
		}
		if ($u === 'S') {
			return 'Suspendido';
		}
		if ($u === 'C') {
			return 'Cerrado';
		}
	}
	if ($atr === 'Man_Tip') {
		$u = strtoupper($val);
		$mapTip = array('P' => 'Productor', 'C' => 'Comercial', 'T' => 'Transporte', 'V' => 'Visitante');
		if (isset($mapTip[$u])) {
			return $mapTip[$u];
		}
	}
	return $val;
}

function aud_det_conexion_mysqli($obBD_conexion)
{
	if (is_object($obBD_conexion) && !empty($obBD_conexion->conexion)) {
		return $obBD_conexion->conexion;
	}
	if (is_object($obBD_conexion)) {
		return $obBD_conexion;
	}
	return null;
}

function aud_valor_codigo_desde_row($atr, $val, $row)
{
	$atr = trim((string)$atr);
	$val = trim((string)$val);
	if ($val === '') {
		return '';
	}
	if ($atr === 'Emp_Cod' && !empty($row['Emp_Nom'])) {
		return trim($row['Emp_Nom']);
	}
	if ($atr === 'Suc_Cod' && !empty($row['Suc_Des'])) {
		return trim($row['Suc_Des']);
	}
	if ($atr === 'Usu_Cod' && !empty($row['Usu_Nom'])) {
		return trim($row['Usu_Nom']);
	}
	if ($atr === 'Pcs_Cod') {
		$pcs = aud_nombre_proceso($row);
		if ($pcs !== '' && $pcs !== 'Proceso no registrado') {
			return $pcs;
		}
	}
	return '';
}

function aud_db_dis_para_lookup($row)
{
	$datDis = '';
	if (!empty($row['Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $row['Dat_Dis']);
	}
	if ($datDis === '' && !empty($_SESSION['Ses_Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
	}
	if ($datDis === '' && !empty($GLOBALS['Ses_Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $GLOBALS['Ses_Dat_Dis']);
	}
	if ($datDis === '' && function_exists('aud_sql_db_dis')) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace('`', '', aud_sql_db_dis()));
	}
	if ($datDis === '' || $datDis === 'servicios' || $datDis === 'exa') {
		$datDis = 'ecoparkmining';
	}
	return $datDis;
}

function aud_db_master_para_lookup()
{
	$master = 'exa_master';
	if (class_exists('Env')) {
		$cand = preg_replace('/[^a-zA-Z0-9_]/', '', (string)\Env::get('DB_DATABASE', 'exa_master'));
		if ($cand !== '') {
			$master = $cand;
		}
	}
	return $master;
}

function aud_valor_codigo_lookup($atr, $val, $row, $obBD_conexion)
{
	$atr = trim((string)$atr);
	$val = trim((string)$val);
	if ($val === '' || !preg_match('/^\d+$/', $val)) {
		return '';
	}
	$con = aud_det_conexion_mysqli($obBD_conexion);
	if (!$con) {
		return '';
	}
	$datDis = aud_db_dis_para_lookup($row);
	$master = aud_db_master_para_lookup();
	$id = (int)$val;
	$sql = '';
	if ($atr === 'Emp_Cod') {
		$sql = "SELECT `Emp_Nom` AS `Nom` FROM `{$master}`.`empresas` WHERE `Emp_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Suc_Cod') {
		$sql = "SELECT `Suc_Des` AS `Nom` FROM `{$master}`.`sucursal` WHERE `Suc_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Usu_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`usuarios` u
			LEFT JOIN `{$datDis}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
			WHERE u.`Usu_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pcs_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(`Pcs_Det`),''), `Pcs_Nom`)) AS `Nom`
			FROM `{$datDis}`.`procesos` WHERE `Pcs_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cli_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(c.`Cli_Nom`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Nom`
			FROM `{$datDis}`.`clientes` c
			LEFT JOIN `{$datDis}`.`persona` p ON c.`Prs_Cod` = p.`Prs_Cod`
			WHERE c.`Cli_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Prv_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(pr.`Prv_Com`),''), TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,'')))) AS `Nom`
			FROM `{$datDis}`.`proveedore` pr
			LEFT JOIN `{$datDis}`.`persona` p ON pr.`Prs_Cod` = p.`Prs_Cod`
			WHERE pr.`Prv_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pla_Cod') {
		$sql = "SELECT `Pla_Nom` AS `Nom` FROM `{$datDis}`.`manifiesto_plantas` WHERE `Pla_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Veh_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Veh_Pla`),''), IFNULL(NULLIF(TRIM(`Veh_Des`),''), CONCAT('Vehiculo ',`Veh_Cod`))) AS `Nom`
			FROM `{$datDis}`.`vehiculo` WHERE `Veh_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Cho_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`chofer` c
			LEFT JOIN `{$datDis}`.`persona` p ON c.`Prs_Cod` = p.`Prs_Cod`
			WHERE c.`Cho_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pro_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pro_Nom`),''), IFNULL(NULLIF(TRIM(`Pro_Des`),''), CONCAT('Producto ',`Pro_Cod`))) AS `Nom`
			FROM `{$datDis}`.`productos` WHERE `Pro_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Vnd_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`vendedores` v
			LEFT JOIN `{$datDis}`.`persona` p ON v.`Prs_Cod` = p.`Prs_Cod`
			WHERE v.`Vnd_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Org_Cod') {
		$sql = "SELECT `Org_Des` AS `Nom` FROM `{$datDis}`.`organizado` WHERE `Org_Cod`={$id} LIMIT 1";
	}
	if ($sql === '') {
		return '';
	}
	$rs = @mysqli_query($con, $sql);
	if (!$rs) {
		return '';
	}
	$reg = mysqli_fetch_assoc($rs);
	mysqli_free_result($rs);
	return !empty($reg['Nom']) ? trim($reg['Nom']) : '';
}

function aud_valor_natural($atr, $val, $row, $obBD_conexion = null)
{
	$base = aud_formato_valor($atr, $val);
	$nom = aud_valor_codigo_desde_row($atr, $val, $row);
	if ($nom === '') {
		$nom = aud_valor_codigo_lookup($atr, $val, $row, $obBD_conexion);
	}
	if ($nom !== '') {
		return $nom;
	}
	return $base;
}

function aud_humanizar_campo($atr)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	$map = aud_etiquetas_campo();
	if (isset($map[$atr])) {
		return $map[$atr];
	}
	$partes = explode('_', $atr);
	if (count($partes) >= 2) {
		$pref = strtoupper($partes[0]);
		$suf = strtolower($partes[count($partes) - 1]);
		$prefNom = array(
			'COM' => 'comprobante',
			'ASI' => 'asiento',
			'CTA' => 'cuenta',
			'PRV' => 'proveedor',
			'CLI' => 'cliente',
			'USU' => 'usuario',
			'EMP' => 'empresa',
			'SUC' => 'sucursal',
			'PEC' => 'periodo',
			'MAN' => 'manifiesto',
			'TUD' => 'turno',
			'TUR' => 'turno',
			'VEH' => 'vehiculo',
			'CHO' => 'chofer',
			'PLA' => 'planta',
			'AMA' => 'anticipo',
			'VET' => 'venta',
			'TIC' => 'comprobante',
			'VND' => 'vendedor',
			'CAJ' => 'caja',
			'PRO' => 'producto',
			'ORG' => 'modulo',
			'CFG' => 'configuracion',
			'NOT' => 'notificacion',
			'SES' => 'sesion',
			'INV' => 'inventario',
			'DIS' => 'dispositivo'
		);
		$sufNom = array(
			'cod' => 'codigo',
			'num' => 'numero',
			'fec' => 'fecha',
			'con' => 'concepto',
			'val' => 'valor',
			'est' => 'estado',
			'des' => 'descripcion',
			'nom' => 'nombre',
			'obs' => 'observacion',
			'hor' => 'hora',
			'ip' => 'direccion IP'
		);
		$izq = isset($prefNom[$pref]) ? $prefNom[$pref] : strtolower($partes[0]);
		$der = isset($sufNom[$suf]) ? $sufNom[$suf] : strtolower($partes[count($partes) - 1]);
		return ucfirst($der).' de '.$izq;
	}
	return $atr;
}

function aud_resumen_actividad($row)
{
	$verbo = aud_verbo_evento(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : '', isset($row['Eve_Des']) ? $row['Eve_Des'] : '');
	$obj = aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	);
	$ident = aud_identificador($row);
	if ($ident !== '') {
		return $verbo.' un '.$obj.' - '.$ident;
	}
	return $verbo.' un '.$obj;
}

function aud_pares_interpretados($row, $obBD_con1, $obBD_conexion)
{
	$pares = aud_parse_cam_val(
		isset($row['Log_Cam']) ? $row['Log_Cam'] : '',
		isset($row['Log_Val']) ? $row['Log_Val'] : ''
	);
	$out = array();
	foreach ($pares as $p) {
		$rowCampo = null;
		if ($obBD_con1 && $obBD_conexion && !empty($row['Tab_Cod']) && $p['atr'] !== '') {
			$rowCampo = $obBD_con1->getRowConsulta(8, $row['Tab_Cod'].'*'.$p['atr'], $obBD_conexion);
		}
		$valNat = aud_valor_natural($p['atr'], $p['val'], $row, $obBD_conexion);
		$out[] = array(
			'atr' => $p['atr'],
			'eti' => aud_etiqueta_campo($p['atr'], $rowCampo),
			'val' => $valNat,
			'val_raw' => $p['val'],
			'des' => aud_descripcion_meta($p['atr'], $valNat, $p['val'], $rowCampo)
		);
	}
	return $out;
}

function aud_resumen_detalle($row, $pares)
{
	$pcs = aud_nombre_proceso($row);
	$bits = array();
	if (is_array($pares)) {
		foreach ($pares as $p) {
			if (!isset($p['val']) || $p['val'] === '') {
				continue;
			}
			$eti = isset($p['eti']) ? $p['eti'] : $p['atr'];
			if ($eti === $p['atr']) {
				$eti = aud_humanizar_campo($p['atr']);
			}
			$bits[] = $eti.': '.$p['val'];
			if (count($bits) >= 4) {
				break;
			}
		}
	}
	if (count($bits) === 0) {
		$ident = aud_identificador($row);
		if ($ident !== '') {
			$bits[] = $ident;
		}
	}
	if (count($bits) === 0) {
		return $pcs;
	}
	return $pcs.' - '.implode('; ', $bits);
}

function aud_frase_movimiento($row, $pares)
{
	$modulo = aud_nombre_modulo($row);
	$proceso = aud_nombre_proceso($row);
	$obj = aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	);
	$ini = strtoupper(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : '');
	if ($ini === 'I') {
		$frase = 'Se registro un nuevo '.$obj.' en el modulo '.$modulo.'.';
	} elseif ($ini === 'U') {
		$frase = 'Se modificaron datos de un '.$obj.' en el modulo '.$modulo.'.';
	} elseif ($ini === 'D') {
		$frase = 'Se elimino o anulo un '.$obj.' en el modulo '.$modulo.'.';
	} else {
		$frase = 'Se registro una actividad sobre un '.$obj.' en el modulo '.$modulo.'.';
	}
	if ($proceso !== '' && $proceso !== 'Proceso no registrado') {
		$frase .= ' El proceso utilizado fue "'.$proceso.'".';
	}
	$usuario = aud_nombre_usuario($row);
	if ($usuario !== '' && $usuario !== 'Usuario no identificado') {
		$frase .= ' El usuario fue "'.$usuario.'".';
	}
	$empresa = aud_nombre_empresa($row);
	if ($empresa !== '' && $empresa !== 'Sin empresa') {
		$frase .= ' Empresa: '.$empresa.'.';
	}
	$ident = aud_identificador($row);
	if ($ident !== '') {
		$frase .= ' Referencia: '.$ident.'.';
	}
	return $frase;
}

function aud_html_detalle($row, $pares)
{
	$usuario = aud_h(aud_nombre_usuario($row));
	$empresa = aud_h(aud_nombre_empresa($row));
	$sucursal = aud_h(aud_nombre_sucursal($row));
	$modulo = aud_h(aud_nombre_modulo($row));
	$directorio = aud_h(aud_nombre_directorio($row));
	$proceso = aud_h(aud_nombre_proceso($row));
	$actividad = aud_h(aud_resumen_actividad($row));
	$registro = aud_h(aud_nombre_registro(
		isset($row['Tab_Nom']) ? $row['Tab_Nom'] : '',
		isset($row['Tab_Ali']) ? $row['Tab_Ali'] : '',
		isset($row['Tab_Des']) ? $row['Tab_Des'] : ''
	));
	$ident = aud_h(aud_identificador($row));
	$eveIni = strtoupper(trim(isset($row['Eve_Ini']) ? $row['Eve_Ini'] : ''));
	$eveDes = aud_h(aud_etiqueta_evento($eveIni, isset($row['Eve_Des']) ? $row['Eve_Des'] : ''));
	$badgeClass = 'aud-det-badge';
	if ($eveIni === 'I') {
		$badgeClass .= ' aud-det-badge-i';
	} elseif ($eveIni === 'U') {
		$badgeClass .= ' aud-det-badge-u';
	} elseif ($eveIni === 'D') {
		$badgeClass .= ' aud-det-badge-d';
	} elseif ($eveIni === 'F') {
		$badgeClass .= ' aud-det-badge-f';
	}

	$fecha = '';
	$hora = '';
	if (!empty($row['Log_Fec'])) {
		$parts = explode(' ', trim($row['Log_Fec']));
		$fecha = aud_h(isset($parts[0]) ? $parts[0] : '');
		$hora = aud_h(isset($parts[1]) ? $parts[1] : '');
	}

	$html = '<div class="aud-detalle m4-detalle">';

	/* 1) Cabecera: evento + fecha */
	$html .= '<div class="aud-det-head">';
	$html .= '<div class="aud-det-head-left">';
	$html .= '<span class="'.$badgeClass.'">'.($eveDes !== '' ? $eveDes : 'Actividad').'</span>';
	if (!empty($row['Log_Cod'])) {
		$html .= '<span class="aud-det-id">#'.(int)$row['Log_Cod'].'</span>';
	}
	$html .= '</div>';
	$html .= '<div class="aud-det-when">';
	if ($fecha !== '') {
		$html .= '<span class="aud-det-when-main"><i class="fa fa-calendar"></i> '.$fecha;
		if ($hora !== '') {
			$html .= ' <span class="aud-det-hora"><i class="fa fa-clock-o"></i> '.$hora.'</span>';
		}
		$html .= '</span>';
	}
	$html .= '</div></div>';

	/* 2) Resumen de la accion */
	$usuPlain = trim(aud_nombre_usuario($row));
	$inicial = $usuPlain !== '' ? strtoupper(substr($usuPlain, 0, 1)) : 'U';
	$html .= '<div class="aud-det-summary">';
	$html .= '<div class="aud-det-avatar">'.aud_h($inicial).'</div>';
	$html .= '<div class="aud-det-summary-body">';
	$html .= '<p class="aud-det-title"><strong>'.$usuario.'</strong> '.$actividad.'.</p>';
	$html .= '<p class="aud-det-lead">'.aud_h(aud_frase_movimiento($row, $pares)).'</p>';
	$html .= '</div></div>';

	/* 3) Contexto en rejilla */
	$html .= '<fieldset class="exa-fieldset aud-det-context"><legend class="Titulos2">Contexto</legend>';
	$html .= '<div class="aud-det-meta">';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Empresa</span><span class="aud-det-value">'.$empresa.'</span></div>';
	if ($sucursal !== '') {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Sucursal</span><span class="aud-det-value">'.$sucursal.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Modulo</span><span class="aud-det-value">'.$modulo.'</span></div>';
	if ($directorio !== '' && $directorio !== $modulo) {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Area</span><span class="aud-det-value">'.$directorio.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Proceso</span><span class="aud-det-value">'.$proceso.'</span></div>';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Tipo de documento</span><span class="aud-det-value">'.ucfirst($registro).'</span></div>';
	if ($ident !== '') {
		$html .= '<div class="aud-det-meta-item aud-det-meta-wide"><span class="aud-det-label">Referencia</span><span class="aud-det-value">'.$ident.'</span></div>';
	}
	$html .= '</div></fieldset>';

	/* 4) Datos del movimiento */
	$html .= '<fieldset class="exa-fieldset aud-det-datos"><legend class="Titulos2">Datos del movimiento</legend>';
	if (count($pares) === 0) {
		$html .= '<p class="aud-det-empty"><i class="fa fa-info-circle"></i> No hay datos adicionales para mostrar en este movimiento.</p>';
	} else {
		$html .= '<div class="table-responsive aud-det-table-wrap"><table class="table table-bordered table-condensed aud-det-table">';
		$html .= '<thead><tr><th class="aud-det-col-dato">Dato</th>';
		$viejos = aud_valores_anteriores($row);
		if (count($viejos) > 0) {
			$html .= '<th class="aud-det-col-antes">Antes</th><th class="aud-det-col-despues">Despues</th>';
		} else {
			$html .= '<th class="aud-det-col-valor">Valor</th>';
		}
		$html .= '<th class="aud-det-col-des">Que significa este valor</th></tr></thead><tbody>';
		foreach ($pares as $p) {
			$eti = isset($p['eti']) ? $p['eti'] : aud_humanizar_campo($p['atr']);
			if ($eti === $p['atr']) {
				$eti = aud_humanizar_campo($p['atr']);
			}
			$des = isset($p['des']) ? $p['des'] : aud_descripcion_meta(
				isset($p['atr']) ? $p['atr'] : '',
				isset($p['val']) ? $p['val'] : '',
				isset($p['val_raw']) ? $p['val_raw'] : (isset($p['val']) ? $p['val'] : ''),
				null
			);
			$html .= '<tr><td class="aud-det-col-dato">'.aud_h($eti).'</td>';
			if (count($viejos) > 0) {
				$oldVal = '';
				$oldRaw = '';
				if (isset($p['atr']) && isset($viejos[$p['atr']])) {
					$oldRaw = $viejos[$p['atr']];
					$oldVal = aud_valor_natural($p['atr'], $oldRaw, $row, null);
				}
				$html .= '<td class="aud-det-col-antes"><span class="aud-det-val-old">'.aud_h($oldVal !== '' ? $oldVal : '—').'</span></td>';
				if ($oldVal !== '' && $oldVal !== $p['val']) {
					$des = 'Antes: "'.$oldVal.'". Ahora: '.$des;
				}
			}
			$html .= '<td class="aud-det-col-despues"><span class="aud-det-val-new">'.aud_h($p['val']).'</span></td>';
			$html .= '<td class="aud-det-col-des">'.aud_h($des).'</td></tr>';
		}
		$html .= '</tbody></table></div>';
	}
	$html .= '</fieldset></div>';
	return $html;
}

/**
 * Estado de captura para avisos en monitor y configuracion.
 * $cfgCount: reglas activas de la empresa (-1 si no se pudo leer).
 */
function aud_estado_captura($empCod, $cfgCount = -1)
{
	if (!class_exists('AuditQueue')) {
		$queueFile = dirname(__FILE__) . '/aud_log_queue.php';
		if (file_exists($queueFile)) {
			require_once($queueFile);
		}
	}
	$enabled = class_exists('AuditQueue') && AuditQueue::enabled();
	$emp = (int)$empCod;
	$cfg = (int)$cfgCount;
	$msg = '';
	$ok = true;
	if (!$enabled) {
		$ok = false;
		$msg = 'El registro de actividades esta desactivado. No se guardaran movimientos nuevos hasta reactivarlo.';
	} elseif ($emp <= 0) {
		$ok = false;
		$msg = 'No hay empresa activa. No se puede asociar la actividad al monitoreo.';
	} elseif ($cfg > 0) {
		$msg = 'Registro activo: se guardan los movimientos de los '.$cfg.' modulo(s)/area(s)/proceso(s) marcados en la configuracion.';
	} else {
		$ok = false;
		$msg = 'El registro esta activo, pero aun no hay reglas. Marque modulos en Configuracion de monitoreo para comenzar a auditar.';
	}
	return array('ok' => $ok, 'enabled' => $enabled, 'cfg' => $cfg, 'message' => $msg);
}

function aud_html_banner_captura($estado)
{
	if (!is_array($estado) || empty($estado['message'])) {
		return '';
	}
	$cls = !empty($estado['ok']) ? 'aud-captura-ok' : 'aud-captura-off';
	return '<p class="aud-captura-banner '.$cls.'">'.aud_h($estado['message']).'</p>';
}

/**
 * Fecha mas antigua con datos registrados en auditoria (aviso "datos desde").
 * Se consulta una sola vez por empresa y se cachea en sesion.
 * $obBD_con1 / $obBD_conexion son opcionales: si no llegan se usa la fecha
 * de inicio del monitoreo (10-sep-2026).
 */
function aud_fecha_registro_inicio($empCod, $obBD_con1 = null, $obBD_conexion = null)
{
	try {
		$sessOk = (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE);
	} catch (\Exception $eSess) {
		$sessOk = false;
	} catch (\Throwable $eSess2) {
		$sessOk = false;
	}
	if (!isset($sessOk)) {
		$sessOk = false;
	}
	$emp = (int)$empCod;
	$key = 'aud_min_fec_'.(int)$emp;
	if ($sessOk && isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
		return (string)$_SESSION[$key];
	}
	$fecha = '2026-09-10';
	if ($obBD_con1 && $obBD_conexion && method_exists($obBD_con1, 'getRowConsulta')) {
		try {
			$row = $obBD_con1->getRowConsulta(37, array($emp), $obBD_conexion);
			if (is_array($row) && !empty($row['min_fec'])) {
				$d = substr(trim((string)$row['min_fec']), 0, 10);
				if ($d !== '' && $d !== '0000-00-00' && strtotime($d)) {
					$fecha = $d;
				}
			}
		} catch (\Exception $eSQL) {
			// conservar la fecha por defecto
		} catch (\Throwable $eSQL2) {
			// conservar la fecha por defecto
		}
	}
	if ($sessOk) {
		$_SESSION[$key] = $fecha;
	}
	return $fecha;
}

/** Banner informativo: desde que fecha hay datos en auditoria. */
function aud_html_banner_desde($fecha)
{
	$fecha = trim((string)$fecha);
	if ($fecha === '' || $fecha === '0000-00-00') {
		return '';
	}
	$ts = strtotime($fecha);
	if (!$ts) {
		return '';
	}
	$meses = array('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');
	$legible = (int)date('j', $ts).' de '.$meses[(int)date('n', $ts) - 1].' de '.date('Y', $ts);
	$dia = date('d/m/Y', $ts);
	return '<p class="aud-aviso-desde"><span class="glyphicon glyphicon-info-sign"></span> La auditoria registra datos desde el <strong>'.$legible.'</strong> ('.$dia.'). No hay movimientos registrados con anterioridad a esa fecha.</p>';
}

/**
 * Historial (timeline) de un registro: todos los movimientos del mismo
 * tipo de registro e identificador, ordenados cronologicamente.
 */
function aud_html_historial($row, $obBD_con1 = null, $obBD_conexion = null)
{
	if (!$obBD_con1 || !$obBD_conexion || !method_exists($obBD_con1, 'getArrayConsulta')) {
		return '<p class="aud-det-empty">Historial no disponible.</p>';
	}
	$tabCod = isset($row['Tab_Cod']) ? (int)$row['Tab_Cod'] : 0;
	$emp = isset($row['Emp_Cod']) ? (int)$row['Emp_Cod'] : 0;
	$actualCod = isset($row['Log_Cod']) ? (int)$row['Log_Cod'] : 0;
	$int = trim((string)(isset($row['Log_Int']) ? $row['Log_Int'] : ''));
	// El identificador base es lo que precede a " || OLD:..."
	$base = trim(preg_replace('/\s*\|\|.*$/s', '', $int));
	if ($base === '') {
		return '<p class="aud-det-empty">Este movimiento no tiene referencia para armar su historial de cambios.</p>';
	}
	$hist = $obBD_con1->getArrayConsulta(36, array($tabCod, $emp, $base, 100), $obBD_conexion);
	if (!is_array($hist) || count($hist) === 0) {
		return '<p class="aud-det-empty">No hay otros cambios registrados sobre este documento.</p>';
	}
	$html = '<ul class="aud-hist-list">';
	foreach ($hist as $r) {
		$eveIni = strtoupper(trim(isset($r['Eve_Ini']) ? $r['Eve_Ini'] : ''));
		$eveDes = aud_h(aud_etiqueta_evento($eveIni, isset($r['Eve_Des']) ? $r['Eve_Des'] : ''));
		$badgeClass = 'aud-det-badge';
		if ($eveIni === 'I') {
			$badgeClass .= ' aud-det-badge-i';
		} elseif ($eveIni === 'U') {
			$badgeClass .= ' aud-det-badge-u';
		} elseif ($eveIni === 'D') {
			$badgeClass .= ' aud-det-badge-d';
		} elseif ($eveIni === 'F') {
			$badgeClass .= ' aud-det-badge-f';
		}
		$fec = trim(isset($r['Log_Fec']) ? $r['Log_Fec'] : '');
		$usuario = aud_nombre_usuario($r);
		$usuario = ($usuario !== '' && $usuario !== 'Usuario no identificado') ? $usuario : '';
		$paresDet = aud_pares_interpretados($r, null, null);
		$resumen = aud_h(aud_resumen_detalle($r, $paresDet));
		$esActual = ((int)$r['Log_Cod'] === $actualCod);
		$html .= '<li class="aud-hist-item'.($esActual ? ' aud-hist-item-actual' : '').'">';
		$html .= '<div class="aud-hist-top">';
		$html .= '<span class="'.$badgeClass.'">'.($eveDes !== '' ? $eveDes : 'Actividad').'</span>';
		$html .= '<span class="aud-hist-when">'.aud_h($fec).'</span>';
		if ($esActual) {
			$html .= '<span class="aud-hist-actual">Este movimiento</span>';
		}
		$html .= '</div>';
		if ($usuario !== '') {
			$html .= '<div class="aud-hist-usuario">Por '.aud_h($usuario).'</div>';
		}
		if ($resumen !== '') {
			$html .= '<div class="aud-hist-resumen">'.$resumen.'</div>';
		}
		$logCodRow = (int)$r['Log_Cod'];
		if ($logCodRow > 0) {
			$html .= '<div class="aud-hist-ver"><a href="javascript:void(0);" data-logcod="'.$logCodRow.'" class="aud-hist-verlink">Ver este movimiento</a></div>';
		}
		$html .= '</li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * Detalle con pestañas: "Movimiento" (contenido clasico + contenido extra)
 * y "Historial de cambios" (linea de tiempo del registro).
 */
function aud_html_detalle_tabs($row, $pares, $obBD_con1 = null, $obBD_conexion = null, $extraMovHtml = '')
{
	$html = '<div class="aud-det-tabs">';
	$html .= '<ul class="aud-det-tabnav">';
	$html .= '<li class="aud-det-tabli active" data-tab="mov"><a href="javascript:void(0);">Movimiento</a></li>';
	$html .= '<li class="aud-det-tabli" data-tab="hist"><a href="javascript:void(0);">Historial de cambios</a></li>';
	$html .= '</ul>';
	$html .= '<div class="aud-det-tabpane active" id="audDetTabMov">';
	$html .= aud_html_detalle($row, $pares);
	$html .= (string)$extraMovHtml;
	$html .= '</div>';
	$html .= '<div class="aud-det-tabpane" id="audDetTabHist" style="display:none;">';
	$html .= aud_html_historial($row, $obBD_con1, $obBD_conexion);
	$html .= '</div></div>';
	$html .= '<script type="text/javascript">(function(){var $w=window.jQuery;if(!$w){return;}$w("#detalleContenido").off("click.audDet").on("click.audDet",".aud-det-tabnav .aud-det-tabli a",function(e){e.preventDefault();var $li=$w(this).closest(".aud-det-tabli");var t=$li.attr("data-tab")||"mov";var $dlg=$w("#detalleContenido");$dlg.find(".aud-det-tabli").removeClass("active");$li.addClass("active");$dlg.find(".aud-det-tabpane").hide();$dlg.find(".aud-det-tabpane").removeClass("active");var $pane=$dlg.find("#audDetTab"+((t==="hist")?"Hist":"Mov"));$pane.show().addClass("active");try{$w("#detalleDialog").dialog("option","position",{my:"center",at:"center",of:window});}catch(e2){}});$w("#detalleContenido").off("click.audHist").on("click.audHist",".aud-hist-verlink",function(e){e.preventDefault();var c=parseInt($w(this).attr("data-logcod"),10);if(c>0&&typeof window.audVerDetalle==="function"){window.audVerDetalle(c);}});})();</script>';
	return $html;
}
