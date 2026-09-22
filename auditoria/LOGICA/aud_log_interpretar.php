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
		'Com_Cod' => 'Codigo de comprobante',
		'Com_Num' => 'Numero de comprobante',
		'Com_Fec' => 'Fecha del comprobante',
		'Com_Con' => 'Concepto',
		'Com_Val' => 'Valor',
		'Com_Est' => 'Estado',
		'Tia_Cod' => 'Tipo de asiento',
		'Prv_Cod' => 'Proveedor',
		'Cli_Cod' => 'Cliente',
		'Asi_Cod' => 'Codigo de asiento',
		'Asi_Deh' => 'Debe / Haber',
		'Asi_Val' => 'Valor del asiento',
		'Asi_Con' => 'Concepto del asiento',
		'Asi_Fec' => 'Fecha del asiento',
		'Cta_Cod' => 'Cuenta contable',
		'Emp_Cod' => 'Empresa',
		'Suc_Cod' => 'Sucursal',
		'Usu_Cod' => 'Usuario',
		'Pcs_Cod' => 'Proceso',
		'Log_Int' => 'Identificador',
		'Man_Cod' => 'Codigo de manifiesto',
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
		'Cli_Cod' => 'Cliente',
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
		'MVis_Cod' => 'Codigo de visitante',
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
		'Vet_Cod' => 'Codigo de venta',
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
		'Caj_Exi' => 'Existencia en caja',
		'Caj_Est' => 'Estado de la caja',
		'Caj_Gen' => 'Generacion automatica',
		'Pun_Cod' => 'Punto de emision',
		'Vnd_Cod' => 'Vendedor',
		'Aut_Cod' => 'Punto de emision',
		'Pro_Cod' => 'Producto'
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
			'pcs_noms' => array('inventario_dispositivos', 'man_adm_usuarios', 'man_adm_notificacion'),
			'mod_like' => 'elavera',
			'mod_re' => '/relavera|inventario|dispositivo/i',
			'mod_not_re' => '/auditoria|contabilid|facturaci/i',
			'tabs' => array('inventario_dispositivos', 'usuario_inventario')
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
	return $des !== '' ? $des : 'Actividad';
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
	if (strpos($int, '=') !== false) {
		$partes = explode('=', $int, 2);
		$eti = aud_humanizar_campo(trim($partes[0]));
		$val = isset($partes[1]) ? trim($partes[1]) : '';
		if ($val !== '') {
			return $eti.': '.$val;
		}
		return $eti;
	}
	return $int;
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
	if ($atr === 'Caj_Gen') {
		$u = strtoupper($val);
		if ($u === 'S') {
			return 'Si';
		}
		if ($u === 'N') {
			return 'No';
		}
	}
	$estados = array('Man_Est', 'Tud_Est', 'Tur_Est', 'MVis_Est', 'Man_EEst', 'Ama_Est', 'Com_Est');
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
	}
	if ($atr === 'Man_Vig') {
		$u = strtoupper($val);
		if ($u === 'S') {
			return 'Si';
		}
		if ($u === 'N') {
			return 'No';
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
	$datDis = !empty($row['Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $row['Dat_Dis']) : '';
	if ($datDis === '' && !empty($_SESSION['Ses_Dat_Dis'])) {
		$datDis = preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']);
	}
	if ($datDis === '') {
		$datDis = 'exa';
	}
	$id = (int)$val;
	$sql = '';
	if ($atr === 'Emp_Cod') {
		$sql = "SELECT `Emp_Nom` AS `Nom` FROM `exa`.`empresas` WHERE `Emp_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Suc_Cod') {
		$sql = "SELECT `Suc_Des` AS `Nom` FROM `exa`.`sucursal` WHERE `Suc_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Usu_Cod') {
		$sql = "SELECT TRIM(CONCAT(IFNULL(p.`Prs_Ape`,''),' ',IFNULL(p.`Prs_Nom`,''))) AS `Nom`
			FROM `{$datDis}`.`usuarios` u
			LEFT JOIN `{$datDis}`.`persona` p ON u.`Prs_Cod` = p.`Prs_Cod`
			WHERE u.`Usu_Cod`={$id} LIMIT 1";
	} elseif ($atr === 'Pcs_Cod') {
		$sql = "SELECT IFNULL(NULLIF(TRIM(`Pcs_Lin`),''), IFNULL(NULLIF(TRIM(`Pcs_Det`),''), `Pcs_Nom`)) AS `Nom`
			FROM `exa`.`procesos` WHERE `Pcs_Cod`={$id} LIMIT 1";
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
		if (trim((string)$base) === '' || trim((string)$base) === $nom) {
			return $nom;
		}
		return $nom.' ('.$base.')';
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
			'CAJ' => 'caja'
		);
		$sufNom = array(
			'cod' => 'codigo',
			'num' => 'numero',
			'fec' => 'fecha',
			'con' => 'concepto',
			'val' => 'valor',
			'est' => 'estado',
			'des' => 'descripcion',
			'nom' => 'nombre'
		);
		$izq = isset($prefNom[$pref]) ? $prefNom[$pref] : strtolower($partes[0]);
		$der = isset($sufNom[$suf]) ? $sufNom[$suf] : strtolower($partes[count($partes) - 1]);
		return ucfirst($der).' de '.$izq;
	}
	return $atr;
}

function aud_etiqueta_campo($atr, $rowCampo)
{
	$atr = trim(str_replace('`', '', (string)$atr));
	if (is_array($rowCampo)) {
		if (!empty($rowCampo['Cam_Ali'])) {
			return trim($rowCampo['Cam_Ali']);
		}
		if (!empty($rowCampo['Cam_Des'])) {
			return trim($rowCampo['Cam_Des']);
		}
	}
	return aud_humanizar_campo($atr);
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
		$out[] = array(
			'atr' => $p['atr'],
			'eti' => aud_etiqueta_campo($p['atr'], $rowCampo),
			'val' => aud_valor_natural($p['atr'], $p['val'], $row, $obBD_conexion)
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
	$eveDes = aud_h(isset($row['Eve_Des']) ? $row['Eve_Des'] : aud_verbo_evento($eveIni, ''));
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
	$logCod = isset($row['Log_Cod']) ? (int)$row['Log_Cod'] : 0;

	$html = '<div class="aud-detalle">';

	/* 1) Cabecera: evento + fecha + id */
	$html .= '<div class="aud-det-head">';
	$html .= '<span class="'.$badgeClass.'">'.($eveDes !== '' ? $eveDes : 'Actividad').'</span>';
	$html .= '<div class="aud-det-when">';
	if ($fecha !== '') {
		$html .= '<span class="aud-det-when-main">'.$fecha.($hora !== '' ? ' <span class="aud-det-hora">'.$hora.'</span>' : '').'</span>';
	}
	if ($logCod > 0) {
		$html .= '<span class="aud-det-id">#'.$logCod.'</span>';
	}
	$html .= '</div></div>';

	/* 2) Resumen de la accion */
	$html .= '<div class="aud-det-summary">';
	$html .= '<p class="aud-det-title"><strong>'.$usuario.'</strong> realizo la accion: '.$actividad.'.</p>';
	$html .= '<p class="aud-det-lead">'.aud_h(aud_frase_movimiento($row, $pares)).'</p>';
	$html .= '</div>';

	/* 3) Contexto en rejilla */
	$html .= '<fieldset class="exa-fieldset aud-det-context"><legend class="Titulos2">Contexto</legend>';
	$html .= '<div class="aud-det-meta">';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Empresa</span><span class="aud-det-value">'.$empresa.'</span></div>';
	if ($sucursal !== '') {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Sucursal</span><span class="aud-det-value">'.$sucursal.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Modulo</span><span class="aud-det-value">'.$modulo.'</span></div>';
	if ($directorio !== '' && $directorio !== $modulo) {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Directorio</span><span class="aud-det-value">'.$directorio.'</span></div>';
	} elseif ($directorio !== '') {
		$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Directorio</span><span class="aud-det-value">'.$directorio.'</span></div>';
	}
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Proceso</span><span class="aud-det-value">'.$proceso.'</span></div>';
	$html .= '<div class="aud-det-meta-item"><span class="aud-det-label">Tipo de registro</span><span class="aud-det-value">'.$registro.'</span></div>';
	if ($ident !== '') {
		$html .= '<div class="aud-det-meta-item aud-det-meta-wide"><span class="aud-det-label">Referencia</span><span class="aud-det-value">'.$ident.'</span></div>';
	}
	$html .= '</div></fieldset>';

	/* 4) Datos del movimiento */
	$html .= '<fieldset class="exa-fieldset aud-det-datos"><legend class="Titulos2">Datos del movimiento</legend>';
	if (count($pares) === 0) {
		$html .= '<p class="aud-det-empty">No se registraron campos adicionales en este movimiento.</p>';
	} else {
		$html .= '<div class="table-responsive"><table class="table table-bordered table-condensed table-striped aud-det-table">';
		$html .= '<thead><tr><th class="aud-det-col-dato">Dato</th>';
		$viejos = aud_valores_anteriores($row);
		if (count($viejos) > 0) {
			$html .= '<th>Valor anterior</th>';
		}
		$html .= '<th>Valor registrado</th></tr></thead><tbody>';
		foreach ($pares as $p) {
			$eti = isset($p['eti']) ? $p['eti'] : aud_humanizar_campo($p['atr']);
			if ($eti === $p['atr']) {
				$eti = aud_humanizar_campo($p['atr']);
			}
			$html .= '<tr><td class="aud-det-col-dato">'.aud_h($eti).'</td>';
			if (count($viejos) > 0) {
				$oldVal = '';
				if (isset($p['atr']) && isset($viejos[$p['atr']])) {
					$oldVal = aud_valor_natural($p['atr'], $viejos[$p['atr']], $row, null);
				}
				$html .= '<td>'.aud_h($oldVal).'</td>';
			}
			$html .= '<td>'.aud_h($p['val']).'</td></tr>';
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
		$msg = 'La captura esta desactivada (AUDIT_ENABLED). El monitor no registrara actividad nueva.';
	} elseif ($emp <= 0) {
		$ok = false;
		$msg = 'No hay empresa activa. No se puede asociar la actividad al monitoreo.';
	} elseif ($cfg > 0) {
		$msg = 'Captura activa: se registra la actividad de los '.$cfg.' modulo(s)/directorio(s)/proceso(s) marcados en configuracion.';
	} else {
		$ok = false;
		$msg = 'Captura activa pero sin reglas: no se registrara actividad. Marque modulos en Configuracion de monitoreo para cubrir el sistema.';
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
