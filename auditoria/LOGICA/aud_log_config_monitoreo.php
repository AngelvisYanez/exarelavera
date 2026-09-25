<?php
/**
 * Logica configuracion de monitoreo.
 * @package auditoria.LOGICA
 */
require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
require_once(__DIR__ . '/aud_sql_config_monitoreo.php');

class Class_Log_Conexion_CfgMon extends MysqlConexion
{
}

class Class_Log_Datos_CfgMon extends MysqlDatos
{
	function consultasobBD($sen_sql, $param, $obBD = null)
	{
		$Par_Sql = $this->parametros($param);
		$con = is_object($obBD) && isset($obBD->conexion) ? $obBD->conexion : $obBD;
		return $this->consulta(sentencias_cfg_monitoreo($sen_sql, $Par_Sql), $con);
	}

	function getRowConsulta($sen_sql, $param, $obBD = null)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);
		if (!$result) {
			return array();
		}
		$row = $this->fetch_assoc($result);
		$this->free_result($result);
		return $row ? $row : array();
	}

	function getArrayConsulta($sen_sql, $param, $obBD = null)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);
		$array = array();
		if (!$result) {
			return $array;
		}
		while ($row_rs = $this->fetch_assoc($result)) {
			$array[] = $row_rs;
		}
		$this->free_result($result);
		return $array;
	}

	function operacionobBD($sen_sql, $param, $obBD = null)
	{
		$Par_Sql = $this->parametros($param);
		$sql = sentencias_cfg_monitoreo($sen_sql, $Par_Sql);
		$con = is_object($obBD) && isset($obBD->conexion) ? $obBD->conexion : $obBD;
		return $this->grabarv_registros($sql, $con);
	}
}

function aud_cfg_ensure_schema($obBD_conexion)
{
	$sql = "CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_monitoreo` (
		`Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
		`Emp_Cod` INT(11) NOT NULL,
		`Org_Cod` INT(11) NOT NULL,
		`Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
		`Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
		`Cfg_Fec` DATETIME DEFAULT NULL,
		`Usu_Cod` INT(11) DEFAULT NULL,
		PRIMARY KEY (`Cfg_Cod`),
		UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
		KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	if (isset($obBD_conexion->conexion) && $obBD_conexion->conexion) {
		@mysqli_query($obBD_conexion->conexion, $sql);
		@mysqli_query($obBD_conexion->conexion, "INSERT INTO `auditoria`.`tablas` (`Tab_Nom`,`Tab_Des`,`Tab_Ali`)
			SELECT 'cfg_monitoreo','Configuracion de monitoreo','Configuracion de monitoreo'
			FROM DUAL WHERE NOT EXISTS (
				SELECT 1 FROM `auditoria`.`tablas` WHERE `Tab_Nom`='cfg_monitoreo'
			)");
	}
}

/**
 * Limpia texto proveniente de BD: encoding, tags HTML, controles y espacios raros.
 */
if (!function_exists('aud_cfg_sanitizar_texto')) {
	function aud_cfg_sanitizar_texto($s)
	{
		if ($s === null) {
			return '';
		}
		if (!is_string($s)) {
			if (is_numeric($s)) {
				return (string)$s;
			}
			return '';
		}
		if ($s === '') {
			return '';
		}
		/* Encoding a UTF-8 si viene en Latin-1 / Windows-1252 */
		if (function_exists('mb_check_encoding') && !@mb_check_encoding($s, 'UTF-8')) {
			if (function_exists('mb_convert_encoding')) {
				$try = @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8');
				if (is_string($try) && $try !== '') {
					$s = $try;
				}
			} elseif (function_exists('utf8_encode')) {
				$s = @utf8_encode($s);
			}
		}
		$s = strip_tags($s);
		if (function_exists('html_entity_decode')) {
			$s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
		}
		/* Quitar controles excepto tab/LF */
		$s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
		if ($s === null) {
			$s = '';
		}
		$s = preg_replace('/\s+/u', ' ', $s);
		if ($s === null) {
			$s = '';
		}
		return trim($s);
	}
}

/**
 * Sanitiza campos de texto de un arreglo asociativo (filas de BD).
 */
if (!function_exists('aud_cfg_sanitizar_fila')) {
	function aud_cfg_sanitizar_fila($row, $campos)
	{
		if (!is_array($row)) {
			return $row;
		}
		foreach ($campos as $c) {
			if (isset($row[$c])) {
				$row[$c] = aud_cfg_sanitizar_texto($row[$c]);
			}
		}
		return $row;
	}
}

/**
 * Arma modulo -> directorio -> procesos a partir de las filas SQL (case 7).
 */
function aud_cfg_armar_arbol($rows)
{
	$mods = array();
	if (!is_array($rows)) {
		return array();
	}
	foreach ($rows as $row) {
		$modCod = isset($row['Mod_Cod']) ? (int)$row['Mod_Cod'] : 0;
		$modDes = isset($row['Mod_Des']) ? aud_cfg_sanitizar_texto($row['Mod_Des']) : '';
		$dirCod = isset($row['Dir_Cod']) ? (int)$row['Dir_Cod'] : 0;
		$dirDes = isset($row['Dir_Des']) ? aud_cfg_sanitizar_texto($row['Dir_Des']) : '';
		$pcsCod = isset($row['Pcs_Cod']) ? (int)$row['Pcs_Cod'] : 0;
		if ($modCod <= 0 || $pcsCod <= 0) {
			continue;
		}
		if ($dirCod <= 0) {
			$dirCod = $modCod;
			$dirDes = $modDes;
		}
		if (!isset($mods[$modCod])) {
			$mods[$modCod] = array(
				'Org_Cod' => $modCod,
				'Org_Des' => $modDes !== '' ? $modDes : ('Modulo '.$modCod),
				'directorios' => array()
			);
		}
		if (!isset($mods[$modCod]['directorios'][$dirCod])) {
			$mods[$modCod]['directorios'][$dirCod] = array(
				'Org_Cod' => $dirCod,
				'Org_Des' => $dirDes !== '' ? $dirDes : ('Directorio '.$dirCod),
				'es_modulo' => ($dirCod === $modCod),
				'procesos' => array()
			);
		}
		$lin = '';
		if (!empty($row['Pcs_Lin'])) {
			$lin = aud_cfg_sanitizar_texto($row['Pcs_Lin']);
		} elseif (!empty($row['Pcs_Nom'])) {
			$lin = aud_cfg_sanitizar_texto($row['Pcs_Nom']);
		} else {
			$lin = 'Proceso '.$pcsCod;
		}
		$mods[$modCod]['directorios'][$dirCod]['procesos'][] = array(
			'Pcs_Cod' => $pcsCod,
			'Pcs_Lin' => $lin,
			'Pcs_Nom' => isset($row['Pcs_Nom']) ? aud_cfg_sanitizar_texto($row['Pcs_Nom']) : ''
		);
	}
	$out = array();
	foreach ($mods as $m) {
		$m['directorios'] = array_values($m['directorios']);
		$out[] = $m;
	}
	return $out;
}

/**
 * Interpreta cfg_monitoreo sobre el arbol: modulo completo, directorio o proceso.
 */
function aud_cfg_marcar_seleccion($arbol, $cfgRows)
{
	$modIds = array();
	$dirIds = array();
	if (is_array($arbol)) {
		foreach ($arbol as $m) {
			$modIds[(int)$m['Org_Cod']] = true;
			if (empty($m['directorios']) || !is_array($m['directorios'])) {
				continue;
			}
			foreach ($m['directorios'] as $d) {
				$dirIds[(int)$d['Org_Cod']] = true;
			}
		}
	}
	$modFull = array();
	$dirFull = array();
	$selected = array();
	if (!is_array($cfgRows)) {
		return array('modFull' => $modFull, 'dirFull' => $dirFull, 'selected' => $selected);
	}
	foreach ($cfgRows as $c) {
		$org = isset($c['Org_Cod']) ? (int)$c['Org_Cod'] : 0;
		$pcs = isset($c['Pcs_Cod']) ? (int)$c['Pcs_Cod'] : 0;
		if ($org <= 0) {
			continue;
		}
		if ($pcs === 0) {
			if (isset($modIds[$org])) {
				$modFull[$org] = true;
			}
			if (isset($dirIds[$org]) && !isset($modIds[$org])) {
				$dirFull[$org] = true;
			}
			continue;
		}
		$selected[$org.'_'.$pcs] = true;
	}
	return array('modFull' => $modFull, 'dirFull' => $dirFull, 'selected' => $selected);
}

/**
 * Interpreta el payload de guardado (JSON o arreglo).
 * Si el JSON es invalido NO se debe borrar la configuracion.
 */
function aud_cfg_parse_items($raw)
{
	if (is_array($raw)) {
		$items = $raw;
	} else {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return array('ok' => true, 'items' => array());
		}
		$items = json_decode($raw, true);
		if (!is_array($items)) {
			$items = json_decode(stripslashes($raw), true);
		}
	}
	if (!is_array($items)) {
		return array('ok' => false, 'items' => array(), 'message' => 'No se pudo leer la seleccion.');
	}
	$out = array();
	foreach ($items as $it) {
		if (!is_array($it)) {
			continue;
		}
		$org = isset($it['org']) ? (int)$it['org'] : 0;
		$pcs = isset($it['pcs']) ? (int)$it['pcs'] : 0;
		if ($org <= 0) {
			continue;
		}
		$out[] = array('org' => $org, 'pcs' => $pcs);
	}
	return array('ok' => true, 'items' => $out);
}

function aud_cfg_guardar($obBD_con1, $obBD_conexion, $emp, $usu, $items)
{
	$emp = (int)$emp;
	$usu = (int)$usu;
	if ($emp <= 0) {
		return array(
			'success' => false,
			'saved' => 0,
			'message' => 'No hay empresa activa para guardar la configuracion.'
		);
	}
	if (!is_array($items)) {
		$items = array();
	}
	aud_cfg_ensure_schema($obBD_conexion);
	$obBD_con1->Error = 0;
	$del = $obBD_con1->operacionobBD(4, array($emp), $obBD_conexion);
	if ($del === false && (int)$obBD_con1->Error !== 0) {
		return array(
			'success' => false,
			'saved' => 0,
			'message' => 'No se pudo actualizar la configuracion.'
		);
	}
	$n = 0;
	foreach ($items as $it) {
		$org = isset($it['org']) ? (int)$it['org'] : 0;
		$pcs = isset($it['pcs']) ? (int)$it['pcs'] : 0;
		if ($org <= 0) {
			continue;
		}
		$obBD_con1->Error = 0;
		$ok = $obBD_con1->operacionobBD(5, array($emp, $org, $pcs, $usu), $obBD_conexion);
		if ($ok) {
			$n++;
		}
	}
	$obBD_con1->Error = 0;
	$row = $obBD_con1->getRowConsulta(6, array($emp), $obBD_conexion);
	$count = isset($row['count']) ? (int)$row['count'] : $n;
	if (count($items) > 0 && $count <= 0) {
		return array(
			'success' => false,
			'saved' => 0,
			'message' => 'La configuracion no se guardo en la base de datos.'
		);
	}
	aud_cfg_trazar_cambio($obBD_con1, $obBD_conexion, $emp, $usu, $count);
	return array(
		'success' => true,
		'saved' => $count,
		'message' => $count > 0
			? ('Configuracion guardada ('.$count.' reglas). El monitor registrara la actividad de estos modulos, directorios y procesos.')
			: 'Configuracion vacia: no se registrara actividad de monitoreo hasta que marque los modulos en el arbol.'
	);
}

function aud_cfg_trazar_cambio($obBD_con1, $obBD_conexion, $emp, $usu, $count)
{
	try {
		$suc = isset($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 0;
		$obBD_con1->Error = 0;
		$obBD_con1->operacionobBD(8, array((int)$emp, (int)$usu, $suc, (int)$count), $obBD_conexion);
		$obBD_con1->Error = 0;
	} catch (Throwable $e) {
		if (isset($obBD_con1->Error)) {
			$obBD_con1->Error = 0;
		}
	}
}

/**
 * Compacta la seleccion a reglas cfg_monitoreo (modulo / directorio / proceso).
 * $modFull y $dirFull son mapas org=>true; $pcsChecked es mapa "dir_pcs"=>true.
 */
function aud_cfg_compactar_reglas($arbol, $modFull, $dirFull, $pcsChecked)
{
	$items = array();
	if (!is_array($arbol)) {
		return $items;
	}
	if (!is_array($modFull)) {
		$modFull = array();
	}
	if (!is_array($dirFull)) {
		$dirFull = array();
	}
	if (!is_array($pcsChecked)) {
		$pcsChecked = array();
	}
	foreach ($arbol as $mod) {
		$modCod = (int)$mod['Org_Cod'];
		if (!empty($modFull[$modCod]) || !empty($modFull[(string)$modCod])) {
			$items[] = array('org' => $modCod, 'pcs' => 0);
			continue;
		}
		if (empty($mod['directorios']) || !is_array($mod['directorios'])) {
			continue;
		}
		foreach ($mod['directorios'] as $dir) {
			$dirCod = (int)$dir['Org_Cod'];
			if (!empty($dirFull[$dirCod]) || !empty($dirFull[(string)$dirCod])) {
				$items[] = array('org' => $dirCod, 'pcs' => 0);
				continue;
			}
			if (empty($dir['procesos']) || !is_array($dir['procesos'])) {
				continue;
			}
			foreach ($dir['procesos'] as $p) {
				$pcs = (int)$p['Pcs_Cod'];
				$key = $dirCod.'_'.$pcs;
				if (!empty($pcsChecked[$key]) || !empty($pcsChecked[(string)$key])) {
					$items[] = array('org' => $dirCod, 'pcs' => $pcs);
				}
			}
		}
	}
	return $items;
}

/**
 * Verifica si el usuario tiene perfil "Administrador de Sistemas" (para editar reglas).
 */
function aud_cfg_es_admin_sistemas($obBD_con1, $obBD_conexion, $usuCod)
{
	$row = $obBD_con1->getRowConsulta(13, array((int)$usuCod), $obBD_conexion);
	return !empty($row['is_admin']);
}
?>
