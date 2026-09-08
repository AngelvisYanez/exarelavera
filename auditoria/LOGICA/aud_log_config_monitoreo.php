<?php
/**
 * Logica configuracion de monitoreo.
 * @package auditoria.LOGICA
 */
if (!class_exists('DebugBar')) {
	class DebugBar {
		public static function __callStatic($name, $args) {}
	}
}

require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
require_once(__DIR__ . '/aud_sql_config_monitoreo.php');
require_once(__DIR__ . '/aud_log_interpretar.php');

class Class_Log_Conexion_CfgMon extends MysqlConexion
{
	function __construct($bd = null)
	{
		if (empty($bd)) {
			$bd = !empty($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null;
		}
		if (empty($bd) && !empty($GLOBALS['Ses_Dat_Dis'])) {
			$bd = $GLOBALS['Ses_Dat_Dis'];
		}
		if (empty($bd) && class_exists('Env')) {
			$bd = \Env::get('DB_DATABASE', 'exa');
		}
		if (empty($bd) || $bd === 'exa_master') {
			$bd = 'exa';
		}
		parent::__construct(preg_replace('/[^a-zA-Z0-9_]/', '', $bd));
	}
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
			return array();
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

// Aliases para compatibilidad con código existente
if (!class_exists('Class_Log_Conexion_Cfg_Monitoreo')) {
	class Class_Log_Conexion_Cfg_Monitoreo extends Class_Log_Conexion_CfgMon {}
}
if (!class_exists('Class_Log_Datos_Cfg_Monitoreo')) {
	class Class_Log_Datos_Cfg_Monitoreo extends Class_Log_Datos_CfgMon {}
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

if (!function_exists('aud_cfg_asegurar_tabla')) {
	function aud_cfg_asegurar_tabla($obBD_conexion)
	{
		return aud_cfg_ensure_schema($obBD_conexion);
	}
}

if (!function_exists('aud_cfg_comprobar_captura')) {
	function aud_cfg_comprobar_captura($obBD_con1, $obBD_conexion, $audEmpCod)
	{
		$audEmpCod = (int)$audEmpCod;
		$rowCfgCount = $obBD_con1->getRowConsulta(6, array($audEmpCod), $obBD_conexion);
		$audCfgCount = isset($rowCfgCount['count']) ? (int)$rowCfgCount['count'] : 0;
		return aud_estado_captura($audEmpCod, $audCfgCount);
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
		$modDes = isset($row['Mod_Des']) ? trim($row['Mod_Des']) : '';
		$dirCod = isset($row['Dir_Cod']) ? (int)$row['Dir_Cod'] : 0;
		$dirDes = isset($row['Dir_Des']) ? trim($row['Dir_Des']) : '';
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
			$lin = trim($row['Pcs_Lin']);
		} elseif (!empty($row['Pcs_Nom'])) {
			$lin = trim($row['Pcs_Nom']);
		} else {
			$lin = 'Proceso '.$pcsCod;
		}
		$mods[$modCod]['directorios'][$dirCod]['procesos'][] = array(
			'Pcs_Cod' => $pcsCod,
			'Pcs_Lin' => $lin,
			'Pcs_Nom' => isset($row['Pcs_Nom']) ? $row['Pcs_Nom'] : ''
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
			: 'Configuracion vacia: se registraran los eventos de las tablas por defecto (AUDIT_TABLES).'
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
 * Verifica si el usuario actual tiene permisos de Administrador de Sistemas.
 */
function aud_cfg_es_admin_sistemas($usuCod = 0, $obBD_con = null, $obBD_conexion = null)
{
	// 1. Revisar sesion Ses_Per_Des
	if (isset($_SESSION['Ses_Per_Des'])) {
		$perfilesArray = is_array($_SESSION['Ses_Per_Des']) ? $_SESSION['Ses_Per_Des'] : array($_SESSION['Ses_Per_Des']);
		foreach ($perfilesArray as $perfil) {
			if (stripos($perfil, 'Administrador de sistemas') !== false || strtoupper(trim($perfil)) === 'ADMINISTRADOR' || stripos($perfil, 'Sistemas') !== false) {
				return true;
			}
		}
	}
	// 2. Revisar sesion Ses_Lis_Per (ID 1 es el perfil Administrador de Sistemas en Exa)
	if (isset($_SESSION['Ses_Lis_Per'])) {
		$perfilesCod = is_array($_SESSION['Ses_Lis_Per']) ? $_SESSION['Ses_Lis_Per'] : array($_SESSION['Ses_Lis_Per']);
		if (in_array(1, $perfilesCod)) {
			return true;
		}
	}
	// 3. Usuario root / admin maestro (Usu_Cod = 1)
	if ((int)$usuCod === 1) {
		return true;
	}
	// 4. Verificacion directa en base de datos si se proporcionan objetos de conexion
	if ((int)$usuCod > 0 && is_object($obBD_con) && is_object($obBD_conexion)) {
		$row = $obBD_con->getRowConsulta(13, array((int)$usuCod), $obBD_conexion);
		if (!empty($row['is_admin'])) {
			return true;
		}
	}
	return false;
}
?>
