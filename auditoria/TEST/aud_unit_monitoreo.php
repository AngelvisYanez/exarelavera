<?php
/**
 * Pruebas unitarias del monitor: proceso <-> modulo raiz.
 *
 * Reproduce el fallo de simulacion donde un proceso aparecia
 * bajo un modulo que no le corresponde.
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_interpretar.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_sql_monitoreo.php';

function aud_run_monitoreo_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas unitarias monitor (proceso-modulo) ==\n";

	$cases = array(
		'aud_unit_sim_casos_sin_cruce_tablas',
		'aud_unit_sim_rechaza_modulo_ajeno',
		'aud_unit_sim_acepta_modulo_propio',
		'aud_unit_resolver_modulo_solo_raiz',
		'aud_unit_resolver_no_usa_directorio',
		'aud_unit_nombre_modulo_respeta_proceso',
		'aud_unit_nombre_modulo_no_inventa_por_tabla',
		'aud_unit_sql_lookup_no_like_suelto',
		'aud_unit_sql_lookup_exige_modulo',
		'aud_unit_sql_modulo_solo_raiz',
		'aud_unit_sql_no_fallback_monitor',
		'aud_unit_sql_usuarios_empresa',
		'aud_unit_sql_filtro_empresa_estricto',
		'aud_unit_parse_old_y_proceso',
		'aud_unit_sql_export_y_captura',
		'aud_unit_estado_captura',
		'aud_unit_front_export_banner',
		'aud_unit_db_sim_proceso_pertenece_al_modulo',
		'aud_unit_db_modulo_siempre_es_raiz',
		'aud_unit_sim_casos_relavera',
		'aud_unit_demo_old_y_detalle'
	);
	foreach ($cases as $fn) {
		try {
			$fn();
			$passed++;
		} catch (Exception $e) {
			$failed++;
			echo "  FAIL  " . $fn . ": " . $e->getMessage() . "\n";
		}
	}

	echo "Monitor: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}

function aud_unit_sim_casos_sin_cruce_tablas()
{
	$tabs = array();
	$ids = array();
	foreach (aud_sim_casos() as $c) {
		if (empty($c['id']) || empty($c['pcs_noms']) || empty($c['tabs'])) {
			throw new Exception('Caso de simulacion incompleto');
		}
		if (isset($ids[$c['id']])) {
			throw new Exception('Ids de simulacion duplicado: ' . $c['id']);
		}
		$ids[$c['id']] = true;
		foreach ($c['tabs'] as $t) {
			if (isset($tabs[$t])) {
				throw new Exception('La tabla ' . $t . ' se simula en dos procesos');
			}
			$tabs[$t] = $c['id'];
		}
	}
	aud_assert(isset($tabs['comprobantes']) && isset($tabs['manifiesto']), 'Hay casos de comprobantes y manifiesto');
	aud_assert($tabs['comprobantes'] !== $tabs['manifiesto'], 'Comprobantes y manifiesto no comparten proceso');
	aud_assert(isset($tabs['ventas']) && $tabs['ventas'] === 'ventas', 'Registrar venta tiene caso propio');
	aud_assert($tabs['ventas'] !== $tabs['comprobantes'], 'Ventas no se cruza con comprobantes');
	aud_assert(isset($tabs['caja_aper']) && $tabs['caja_aper'] === 'caja', 'Caja registrar tiene caso propio');
	aud_assert($tabs['caja_aper'] !== $tabs['ventas'], 'Caja no se cruza con ventas');
}

function aud_unit_sim_rechaza_modulo_ajeno()
{
	$man = aud_sim_caso_por_id('manifiesto');
	$comp = aud_sim_caso_por_id('comprobantes');
	$ven = aud_sim_caso_por_id('ventas');
	$caj = aud_sim_caso_por_id('caja');
	aud_assert($man !== null && $comp !== null, 'Existen casos manifiesto y comprobantes');
	aud_assert($ven !== null, 'Existe caso ventas / registrar venta');
	aud_assert($caj !== null, 'Existe caso caja registrar');

	$cruces = array(
		array($man, array('Pcs_Cod' => 10, 'Pcs_Nom' => 'man_alt_manifiesto', 'Mod_Des' => 'Contabilidad'), 'Manifiesto no puede colgar de Contabilidad'),
		array($man, array('Pcs_Cod' => 11, 'Pcs_Nom' => 'man_alt_manifiesto', 'Mod_Des' => 'Auditoria'), 'Manifiesto no puede colgar de Auditoria'),
		array($comp, array('Pcs_Cod' => 12, 'Pcs_Nom' => 'con_alt_compr', 'Mod_Des' => 'Auditoria'), 'Comprobante no puede colgar de Auditoria'),
		array($comp, array('Pcs_Cod' => 13, 'Pcs_Nom' => 'con_pri_compr', 'Mod_Des' => 'Facturacion'), 'Comprobante de Facturacion no es de Contabilidad'),
		array($comp, array('Pcs_Cod' => 14, 'Pcs_Nom' => 'con_alt_compr', 'Mod_Des' => 'Relavera'), 'Comprobante no puede colgar de Relavera'),
		array($man, array('Pcs_Cod' => 15, 'Pcs_Nom' => 'man_alt_manifiesto', 'Mod_Des' => ''), 'Sin modulo resuelto se rechaza'),
		array($ven, array('Pcs_Cod' => 16, 'Pcs_Nom' => 'fac_alt_fac_ven_3.2', 'Mod_Des' => 'Contabilidad'), 'Venta no puede colgar de Contabilidad'),
		array($ven, array('Pcs_Cod' => 17, 'Pcs_Nom' => 'fac_alt_fac_ven_3.2', 'Mod_Des' => 'Auditoria'), 'Venta no puede colgar de Auditoria'),
		array($caj, array('Pcs_Cod' => 18, 'Pcs_Nom' => 'fac_alt_caja_2.0', 'Mod_Des' => 'Contabilidad'), 'Caja no puede colgar de Contabilidad'),
		array($caj, array('Pcs_Cod' => 19, 'Pcs_Nom' => 'fac_alt_caja_2.0', 'Mod_Des' => 'Auditoria'), 'Caja no puede colgar de Auditoria')
	);
	foreach ($cruces as $item) {
		aud_assert(aud_sim_modulo_valido($item[1], $item[0]) === false, $item[2]);
	}
}

function aud_unit_sim_acepta_modulo_propio()
{
	$man = aud_sim_caso_por_id('manifiesto');
	$comp = aud_sim_caso_por_id('comprobantes');
	$ven = aud_sim_caso_por_id('ventas');
	$caj = aud_sim_caso_por_id('caja');
	aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 1, 'Mod_Des' => 'Relavera'), $man) === true, 'Manifiesto bajo Relavera es valido');
	aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 2, 'Mod_Des' => 'Contabilidad'), $comp) === true, 'Comprobante bajo Contabilidad es valido');
	aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 3, 'Mod_Des' => 'Facturacion'), $ven) === true, 'Venta bajo Facturacion es valido');
	aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 4, 'Mod_Des' => 'Facturacion'), $caj) === true, 'Caja bajo Facturacion es valido');
}

function aud_unit_resolver_modulo_solo_raiz()
{
	$dir = array('Org_Cod' => 30, 'Org_Des' => 'Manifiestos', 'Org_Niv' => 20);
	$padre = array('Org_Cod' => 20, 'Org_Des' => 'Operaciones', 'Org_Niv' => 10);
	$abuelo = array('Org_Cod' => 10, 'Org_Des' => 'Relavera', 'Org_Niv' => 0);
	$m = aud_resolver_modulo_arbol($dir, $padre, $abuelo);
	aud_assert($m !== null && $m['Org_Des'] === 'Relavera', 'El modulo es la raiz Org_Niv=0, no el directorio');
	aud_assert((int)$m['Org_Cod'] === 10, 'Org_Cod del modulo es el raiz');
}

function aud_unit_resolver_no_usa_directorio()
{
	$dir = array('Org_Cod' => 30, 'Org_Des' => 'Manifiestos', 'Org_Niv' => 20);
	$padre = array('Org_Cod' => 20, 'Org_Des' => 'Operaciones', 'Org_Niv' => 10);
	$m = aud_resolver_modulo_arbol($dir, $padre, null);
	aud_assert($m === null, 'Un directorio intermedio no se promociona a modulo');

	$raiz = array('Org_Cod' => 1, 'Org_Des' => 'Contabilidad', 'Org_Niv' => 0);
	$m2 = aud_resolver_modulo_arbol($raiz, null, null);
	aud_assert($m2 !== null && $m2['Org_Des'] === 'Contabilidad', 'Proceso colgando directo del modulo raiz');
}

function aud_unit_nombre_modulo_respeta_proceso()
{
	$row = array(
		'Mod_Des' => 'Relavera',
		'Tab_Nom' => 'comprobantes',
		'Pcs_Cod' => 99,
		'Pcs_Lin' => 'Manifiestos'
	);
	aud_assert(aud_nombre_modulo($row) === 'Relavera', 'Mod_Des del proceso gana sobre el mapa de tablas');
}

function aud_unit_nombre_modulo_no_inventa_por_tabla()
{
	$row = array(
		'Tab_Nom' => 'comprobantes',
		'Pcs_Cod' => 50,
		'Pcs_Lin' => 'Comprobantes de pago',
		'Dir_Des' => 'Pagos',
		'Org_Niv' => 12
	);
	$nom = aud_nombre_modulo($row);
	aud_assert(stripos($nom, 'Contabilidad') === false, 'No inventar Contabilidad por Tab_Nom si hay proceso de otro modulo (obtuvo: ' . $nom . ')');
	aud_assert($nom === 'Sin modulo', 'Sin raiz Org_Niv=0 el modulo queda vacio, no se adivina');
}

function aud_unit_sql_lookup_no_like_suelto()
{
	$sql = sentencias(23, array('man_alt_manifiesto', 'elavera'));
	aud_assert(strpos($sql, '`Pcs_Lin` LIKE') === false, 'Lookup no usa Pcs_Lin (etiquetas cruzan modulos)');
	aud_assert(!preg_match("/LIKE '%man_alt_manifiesto%'/", $sql), 'LIKE %nombre% agarra procesos de otro modulo');
	aud_assert(strpos($sql, 'man_alt_manifiesto') !== false, 'Busca el nombre de pagina del proceso');
	aud_assert(strpos($sql, "LIKE '%elavera%'") !== false, 'Filtra por modulo raiz Relavera');
}

function aud_unit_sql_lookup_exige_modulo()
{
	$sqlCfg = sentencias(23, array('man_adm_configuracion', 'elavera'));
	aud_assert(strpos($sqlCfg, "LIKE '%elavera%'") !== false, 'Configuracion de Relavera no busca en Administracion');
	$sqlComp = sentencias(19, '');
	aud_assert(strpos($sqlComp, 'con_alt_compr') !== false, 'Comprobantes busca con_alt_compr');
	aud_assert(strpos($sqlComp, "LIKE '%ontabilid%'") !== false, 'Comprobantes exige modulo Contabilidad');
	aud_assert(strpos($sqlComp, '`Pcs_Lin`') === false || strpos($sqlComp, '`Pcs_Lin` LIKE') === false, 'Comprobantes no busca por etiqueta');
}

function aud_unit_sql_modulo_solo_raiz()
{
	$expr = aud_sql_expr_modulo_des();
	aud_assert(strpos($expr, 'Org_Niv`,0) = 0') !== false, 'El CASE exige Org_Niv=0');
	aud_assert(!preg_match('/WHEN `org_abuelo`.`Org_Cod` IS NOT NULL THEN/', $expr), 'No usar cualquier abuelo como modulo');
	aud_assert(stripos($expr, 'ELSE NULL') !== false, 'Si no hay raiz, modulo es NULL (no el directorio)');
}

function aud_unit_sql_no_fallback_monitor()
{
	$sql19 = sentencias(19, '');
	$sql23 = sentencias(23, array('man_alt_manifiesto', 'elavera'));
	aud_assert(strpos($sql19, 'aud_con_monitoreo') === false, 'Simulacion de comprobantes no cae al proceso de Auditoria');
	aud_assert(strpos($sql23, 'aud_con_monitoreo') === false, 'Simulacion de manifiesto no usa el monitor');
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php');
	aud_assert(strpos($front, 'getRowConsulta(21') === false, 'FRONT no usa el proceso de monitoreo como fallback de simulacion');
	aud_assert(strpos($front, 'aud_sim_modulo_valido') !== false, 'FRONT valida modulo antes de insertar demos');
	aud_assert(preg_match('/if\s*\(\s*\(int\)\$d\[0\]\s*<=\s*0\s*\)/', $front), 'FRONT no inserta demo con Pcs_Cod=0');
	aud_assert(strpos($front, "empty(\$countAll['count'])") === false, 'FRONT no reinserta demos al abrir el monitoreo vacio');
	aud_assert(strpos($front, "\$_POST['simular']") !== false, 'Simular actividad sigue disponible a demanda');
}

function aud_unit_sql_usuarios_empresa()
{
	$sql = sentencias(27, array(7, 0));
	aud_assert(stripos($sql, '`auditoria`.`logs`') === false, 'Combo usuarios no sale de logs');
	aud_assert(stripos($sql, '`exa`.`usuarios`') !== false, 'Lista usuarios de la empresa');
	aud_assert(stripos($sql, '`exa`.`sucursal`') !== false, 'Usuarios ligados por sucursal de la empresa');
	aud_assert(strpos($sql, '`Usu_Cods`') !== false && strpos($sql, '`N_Ctas`') !== false, 'Combo agrupa cuentas de la persona (sin repetidos)');
	aud_assert(strpos($sql, 's.`Emp_Cod`=7') !== false, 'Filtra por empresa de sesion');
	aud_assert(strpos($sql, 'IS NULL') === false, 'No incluye usuarios de otra empresa');

	$sqlSuc = sentencias(27, array(7, 12));
	aud_assert(strpos($sqlSuc, 'u.`Suc_Cod`=12') !== false, 'Con sucursal lista solo usuarios de esa sucursal');

	$sqlVacio = sentencias(27, array(0));
	aud_assert(strpos($sqlVacio, '1=0') !== false, 'Sin empresa de sesion no lista usuarios');

	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php');
	aud_assert(strpos($front, 'listUsuariosAjax') !== false, 'FRONT recarga usuarios por AJAX');
	$js = file_get_contents(dirname(__FILE__) . '/../VALIDACIONES/aud_par_monitoreo.js');
	aud_assert(strpos($js, 'cargarUsuarios') !== false, 'JS recarga el combo al cambiar sucursal');
}

function aud_unit_sql_filtro_empresa_estricto()
{
	$w = aud_logs_filtro(array(7, '', '', 0, 0, 0, 0, 0));
	aud_assert(strpos($w, '`logs`.`Emp_Cod`=7') !== false, 'Listado filtra Emp_Cod de sesion');
	aud_assert(strpos($w, 'IS NULL') === false, 'No mezcla logs sin empresa al cambiar de sesion');

	$sql12 = sentencias(12, array(7, '2026-01-01', '2026-01-31', 0, 0, 0, 0, 0, 25, 0, 0, 0));
	aud_assert(strpos($sql12, '`logs`.`Emp_Cod`=7') !== false, 'Grid filtra empresa activa');
	aud_assert(strpos($sql12, 'Emp_Cod` IS NULL') === false, 'Grid no incluye Emp_Cod nulo');

	$sql25 = sentencias(25, array(7));
	aud_assert(strpos($sql25, 'Emp_Cod`=7') !== false, 'Combo modulo de la empresa');
	aud_assert(strpos($sql25, 'Emp_Cod` IS NULL') === false, 'Combo modulo no mezcla empresas');

	$sql20 = sentencias(20, array(99, 7));
	aud_assert(strpos($sql20, '`logs`.`Emp_Cod`=7') !== false, 'Detalle exige empresa de sesion');

	$menu = file_get_contents(dirname(__FILE__) . '/../../db/auditoria_menu.sql');
	aud_assert(strpos($menu, "'Gerente'") !== false, 'Menu asigna el perfil Gerente');
}

function aud_unit_parse_old_y_proceso()
{
	$row = array(
		'Log_Int' => 'Pcs_Nom=fac_alt_fac_ven_3.2.php || Vet_Cod=99 || OLD:Vet_Est=A,Vet_Obs=x',
		'Pcs_Nom' => ''
	);
	aud_assert(aud_pcs_nom_desde_int($row['Log_Int']) === 'fac_alt_fac_ven_3.2.php', 'Lee Pcs_Nom embebido en Log_Int');
	aud_assert(strpos(aud_nombre_proceso($row), 'no registrado') !== false, 'Proceso sin menu se marca como no registrado');
	$old = aud_valores_anteriores($row);
	aud_assert(isset($old['Vet_Est']) && $old['Vet_Est'] === 'A', 'Parsea valor anterior de UPDATE');
	aud_assert(isset($old['Vet_Obs']) && $old['Vet_Obs'] === 'x', 'Parsea varios campos OLD');
	$ident = aud_identificador($row);
	aud_assert(strpos($ident, '99') !== false, 'Identificador ignora Pcs_Nom y OLD');
}

function aud_unit_sql_export_y_captura()
{
	$sql = sentencias(31, array(7, '2026-01-01', '2026-01-31', 0, 0, 0, 0, 0, 5000, 0, 0, 0));
	aud_assert(strpos($sql, '`logs`.`Emp_Cod`=7') !== false, 'Export filtra empresa activa');
	aud_assert(strpos($sql, 'LIMIT 5000') !== false, 'Export tiene tope de filas');
	aud_assert(stripos($sql, 'ORDER BY') !== false, 'Export ordena como el grid');

	$sql32 = sentencias(32, array(7));
	aud_assert(strpos($sql32, '`auditoria`.`cfg_monitoreo`') !== false, 'Conteo de reglas sale de cfg_monitoreo');
	aud_assert(strpos($sql32, 'Emp_Cod`=7') !== false, 'Conteo de reglas es por empresa');
}

function aud_unit_estado_captura()
{
	aud_test_putenv('AUDIT_ENABLED', 'false');
	AuditQueue::resetForTests();
	$off = aud_estado_captura(1, 4);
	aud_assert(empty($off['ok']), 'AUDIT_ENABLED=false marca captura inactiva');
	$htmlOff = aud_html_banner_captura($off);
	aud_assert(strpos($htmlOff, 'aud-captura-off') !== false, 'Banner rojo si la captura esta apagada');

	aud_test_putenv('AUDIT_ENABLED', 'true');
	AuditQueue::resetForTests();
	$ok = aud_estado_captura(7, 12);
	aud_assert(!empty($ok['ok']) && strpos($ok['message'], '12') !== false, 'Aviso con cantidad de reglas');
	$htmlOk = aud_html_banner_captura($ok);
	aud_assert(strpos($htmlOk, 'aud-captura-ok') !== false, 'Banner verde si la captura esta activa');

	$def = aud_estado_captura(7, 0);
	aud_assert(empty($def['ok']) && strpos($def['message'], 'no se registrara actividad') !== false, 'Sin reglas avisa que no se registrara actividad hasta marcar modulos');
}

function aud_unit_front_export_banner()
{
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php');
	aud_assert(strpos($front, 'exportMonitoreoCsv') !== false, 'FRONT exporta el filtro completo por CSV');
	aud_assert(strpos($front, 'aud_html_banner_captura') !== false, 'FRONT muestra aviso de captura');
	aud_assert(strpos($front, 'confirm(') !== false, 'Simular pide confirmacion');
	aud_assert(strpos($front, "name=\"simular\"") !== false, 'El formulario de simular conserva el input');
	$js = file_get_contents(dirname(__FILE__) . '/../VALIDACIONES/aud_par_monitoreo.js');
	aud_assert(strpos($js, 'exportMonitoreoCsv=1') !== false, 'Excel usa export del servidor');
	aud_assert(strpos($js, 'serialize()') !== false, 'Excel envia los filtros actuales');
}

function aud_exa_disponible($con)
{
	$r = @mysqli_query($con, "SELECT 1 FROM `exa`.`procesos` p INNER JOIN `exa`.`organizado` o ON p.`Org_Cod`=o.`Org_Cod` LIMIT 1");
	if ($r) {
		mysqli_free_result($r);
		return true;
	}
	return false;
}

function aud_unit_db_sim_proceso_pertenece_al_modulo()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  simulacion vs BD (sin conexion a auditoria)\n";
		return;
	}
	if (!aud_exa_disponible($con)) {
		echo "  SKIP  simulacion vs BD (no hay exa.procesos: " . mysqli_error($con) . ")\n";
		@mysqli_close($con);
		return;
	}

	$vistos = 0;
	foreach (aud_sim_casos() as $c) {
		$found = null;
		foreach ($c['pcs_noms'] as $nom) {
			$sql = sentencias(23, array($nom, $c['mod_like']));
			$r = @mysqli_query($con, $sql);
			if (!$r) {
				throw new Exception('SQL lookup fallo para ' . $c['id'] . ': ' . mysqli_error($con));
			}
			$row = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			if ($row && !empty($row['Pcs_Cod'])) {
				$found = $row;
				break;
			}
		}
		if (!$found) {
			echo "       SKIP  " . $c['id'] . " (proceso no registrado en el menu)\n";
			continue;
		}
		$vistos++;
		$mod = isset($found['Mod_Des']) ? $found['Mod_Des'] : '';
		aud_assert(aud_sim_modulo_valido($found, $c) === true,
			'Proceso ' . $found['Pcs_Nom'] . ' (Pcs_Cod=' . $found['Pcs_Cod'] . ') modulo "' . $mod . '" coincide con ' . $c['id']);

		foreach (aud_sim_casos() as $otro) {
			if ($otro['id'] === $c['id']) {
				continue;
			}
			if (preg_match($otro['mod_not_re'], $mod) || !preg_match($otro['mod_re'], $mod)) {
				aud_assert(aud_sim_modulo_valido($found, $otro) === false,
					'Proceso ' . $found['Pcs_Nom'] . ' no se acepta como caso ' . $otro['id']);
			}
		}
	}
	if ($vistos === 0) {
		echo "  SKIP  ningun proceso de simulacion esta en el menu\n";
	} else {
		echo "       procesos de simulacion verificados: " . $vistos . "\n";
	}
	@mysqli_close($con);
}

function aud_unit_db_modulo_siempre_es_raiz()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  arbol organizado (sin conexion)\n";
		return;
	}
	if (!aud_exa_disponible($con)) {
		echo "  SKIP  arbol organizado (no hay exa.procesos)\n";
		@mysqli_close($con);
		return;
	}

	$sql = "SELECT p.`Pcs_Cod`, p.`Pcs_Nom`, p.`Pcs_Lin`,
		`organizado`.`Org_Cod` AS `Dir_Cod`,
		`organizado`.`Org_Des` AS `Dir_Des`,
		`organizado`.`Org_Niv` AS `Dir_Niv`,
		`org_padre`.`Org_Cod` AS `Padre_Cod`,
		`org_padre`.`Org_Des` AS `Padre_Des`,
		`org_padre`.`Org_Niv` AS `Padre_Niv`,
		`org_abuelo`.`Org_Cod` AS `Abuelo_Cod`,
		`org_abuelo`.`Org_Des` AS `Abuelo_Des`,
		`org_abuelo`.`Org_Niv` AS `Abuelo_Niv`,
		`org_bisabuelo`.`Org_Cod` AS `Bis_Cod`,
		`org_bisabuelo`.`Org_Des` AS `Bis_Des`,
		`org_bisabuelo`.`Org_Niv` AS `Bis_Niv`,
		".aud_sql_expr_modulo_cod()." AS `Mod_Cod`,
		".aud_sql_expr_modulo_des()." AS `Mod_Des`
	FROM `exa`.`procesos` p
	".aud_sql_org_tree_joins('p')."
	WHERE IFNULL(p.`Pcs_Est`,'A')='A' AND p.`Org_Cod` IS NOT NULL
	ORDER BY p.`Pcs_Cod` ASC
	LIMIT 250";
	$r = @mysqli_query($con, $sql);
	if (!$r) {
		throw new Exception('No se pudo leer el arbol de procesos: ' . mysqli_error($con));
	}
	$n = 0;
	$sinRaiz = 0;
	$errores = array();
	while ($row = mysqli_fetch_assoc($r)) {
		$n++;
		$esperado = aud_resolver_modulo_arbol(
			array('Org_Cod' => $row['Dir_Cod'], 'Org_Des' => $row['Dir_Des'], 'Org_Niv' => $row['Dir_Niv']),
			array('Org_Cod' => $row['Padre_Cod'], 'Org_Des' => $row['Padre_Des'], 'Org_Niv' => $row['Padre_Niv']),
			array('Org_Cod' => $row['Abuelo_Cod'], 'Org_Des' => $row['Abuelo_Des'], 'Org_Niv' => $row['Abuelo_Niv']),
			array('Org_Cod' => $row['Bis_Cod'], 'Org_Des' => $row['Bis_Des'], 'Org_Niv' => $row['Bis_Niv'])
		);
		$sqlMod = isset($row['Mod_Des']) ? trim((string)$row['Mod_Des']) : '';
		$nom = isset($row['Pcs_Nom']) ? $row['Pcs_Nom'] : ('Pcs '.$row['Pcs_Cod']);
		if ($esperado === null) {
			$sinRaiz++;
			if ($sqlMod !== '') {
				$errores[] = $nom . ' sin raiz pero Mod_Des="' . $sqlMod . '"';
			}
			continue;
		}
		if ($sqlMod !== $esperado['Org_Des']) {
			$errores[] = $nom . ' SQL="' . $sqlMod . '" PHP="' . $esperado['Org_Des'] . '"';
		}
		if ((int)$row['Mod_Cod'] !== (int)$esperado['Org_Cod']) {
			$errores[] = $nom . ' Mod_Cod SQL=' . $row['Mod_Cod'] . ' PHP=' . $esperado['Org_Cod'];
		}
		$nivSql = null;
		foreach (array('Dir', 'Padre', 'Abuelo', 'Bis') as $pref) {
			if ((int)$row[$pref . '_Cod'] === (int)$row['Mod_Cod']) {
				$nivSql = (int)$row[$pref . '_Niv'];
				break;
			}
		}
		if ($nivSql !== 0) {
			$errores[] = $nom . ' modulo no es Org_Niv=0 (niv=' . var_export($nivSql, true) . ')';
		}
	}
	mysqli_free_result($r);
	aud_assert($n > 0, 'Hay procesos para validar el arbol');
	$muestra = implode('; ', array_slice($errores, 0, 5));
	aud_assert(count($errores) === 0, 'Ningun proceso cuelga de un modulo que no es raiz (' . count($errores) . ' fallos). ' . $muestra);
	echo "       procesos revisados=" . $n . ", sin raiz Org_Niv=0=" . $sinRaiz . "\n";
	@mysqli_close($con);
}

function aud_unit_sim_casos_relavera()
{
	$ids = array('anticipos', 'contratos', 'maquinaria', 'tecnicos', 'operario_vehiculos', 'inventario', 'cobranzas');
	foreach ($ids as $id) {
		$c = aud_sim_caso_por_id($id);
		aud_assert($c !== null, 'El caso de simulacion relavera "' . $id . '" existe');
		aud_assert(!empty($c['pcs_noms']) && !empty($c['tabs']), 'El caso "' . $id . '" tiene procesos y tablas');
		aud_assert(preg_match($c['mod_re'], 'Relavera') === 1, 'El caso "' . $id . '" acepta el modulo Relavera');
		aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 1, 'Mod_Des' => 'Relavera'), $c) === true, 'Proceso bajo Relavera es valido para "' . $id . '"');
		aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 2, 'Mod_Des' => 'Contabilidad'), $c) === false, '"' . $id . '" rechaza Contabilidad');
		aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 3, 'Mod_Des' => 'Facturacion'), $c) === false, '"' . $id . '" rechaza Facturacion');
		aud_assert(aud_sim_modulo_valido(array('Pcs_Cod' => 4, 'Mod_Des' => 'Auditoria'), $c) === false, '"' . $id . '" rechaza Auditoria');
	}

	$tabs = array();
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php');
	$logica = file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_log_interpretar.php');
	foreach (aud_sim_casos() as $c) {
		foreach ($c['tabs'] as $t) {
			if (isset($tabs[$t])) {
				throw new Exception('La tabla ' . $t . ' se simula en dos procesos (' . $tabs[$t] . ' y ' . $c['id'] . ')');
			}
			$tabs[$t] = $c['id'];
		}
		aud_assert(strpos($logica, $c['pcs_noms'][0]) !== false, 'La logica del simulador conoce el proceso ' . $c['pcs_noms'][0] . ' del caso ' . $c['id']);
	}
	aud_assert(isset($tabs['anticipos_clientes']) && isset($tabs['manifiesto_contratos']) && isset($tabs['maquinaria_horometro']), 'Las tablas relavera se reparten entre casos');
	aud_assert(strpos($front, 'getRowConsulta(23') !== false && strpos($front, 'aud_sim_modulo_valido') !== false, 'El FRONT resuelve y valida cada caso del simulador');
	aud_assert(strpos($front, 'pcsAnt') !== false && strpos($front, 'pcsCob') !== false, 'El FRONT prepara demos de los nuevos procesos');
	aud_assert(strpos($front, "'manifiesto_contratos'") !== false && strpos($front, "'inventario_dispositivos'") !== false, 'El FRONT siembra las tablas relavera');
}

function aud_unit_demo_old_y_detalle()
{
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_monitoreo_1.0.php');
	aud_assert(strpos($front, 'OLD:Man_Pes=12500') !== false, 'El UPDATE de manifiesto guarda valores anteriores');
	aud_assert(strpos($front, 'OLD:Tud_Cup=15') !== false, 'El UPDATE de turno guarda cupo anterior');
	aud_assert(strpos($front, 'OLD:MHor_Val=1250.00') !== false, 'El UPDATE de horometro guarda valor anterior');
	aud_assert(strpos($front, 'OLD:Inv_Est=A') !== false, 'El UPDATE de inventario guarda estado anterior');
	aud_assert(strpos($front, 'OLD:Pag_Mon=120.00') !== false, 'El UPDATE de pago guarda valor anterior');

	$dac = file_get_contents(dirname(__FILE__) . '/../../DATA/DAC.php');
	aud_assert(strpos($dac, 'AuditQueue::captureBefore') !== false, 'DAC captura el valor anterior antes de actualizar');
	aud_assert(strpos($dac, 'AuditQueue::capture') !== false, 'DAC encola el movimiento despues de ejecutar');
	aud_assert(strpos($dac, 'grabarv_registros') !== false && strpos($dac, 'capAuditoria') !== false, 'La capa de grabacion usa la captura');

	$row = array(
		'Log_Int' => 'MHor_Cod=DEMO-HOR-1 || OLD:MHor_Val=1250.00,MHor_Est=A',
		'Log_Cam' => 'MHor_Val,MHor_Est',
		'Log_Val' => '1310.00,~A~',
		'Log_Fec' => '2026-01-15 10:00:00',
		'Eve_Ini' => 'U',
		'Eve_Des' => 'Actualizacion',
		'Tab_Nom' => 'maquinaria_horometro',
		'Tab_Cod' => 1,
		'Usu_Cod' => 5,
		'Emp_Cod' => 7,
		'Log_Cod' => 99,
		'Pcs_Lin' => 'Horometros de maquinaria'
	);
	$pares = array(
		array('atr' => 'MHor_Val', 'eti' => 'Valor horometro', 'val' => '1310.00'),
		array('atr' => 'MHor_Est', 'eti' => 'Estado', 'val' => 'A')
	);
	$html = aud_html_detalle($row, $pares);
	aud_assert(isset($html) && is_string($html) && $html !== '', 'El detalle del UPDATE se renderiza');
	aud_assert(strpos($html, 'Valor anterior') !== false, 'El detalle muestra la columna de valor anterior');
	aud_assert(strpos($html, '1250.00') !== false && strpos($html, '1310.00') !== false, 'El detalle muestra antes y despues del UPDATE');
	aud_assert(strpos($html, 'aud-det-badge-u') !== false, 'El detalle marca el evento como actualizacion');
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
	$fails = aud_run_monitoreo_tests();
	echo "\n========================================\n";
	echo $fails === 0 ? "MONITOR OK\n" : ("FALLOS: " . $fails . "\n");
	echo "========================================\n";
	exit($fails === 0 ? 0 : 1);
}
