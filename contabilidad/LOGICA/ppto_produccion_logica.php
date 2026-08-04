<?php
/**
 * Configuracion de produccion, plan mensual y sincronizacion Relavera.
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('ppto_schema_logica.php');
require_once('ppto_hooks_loader.php');
require_once('ppto_proyecto_version_logica.php');
require_once('ppto_format_helpers.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

$obBD = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli = $obBD->conexion;
ppto_schema_ensure($mysqli);
ppto_schema_ensure_proyecto_publicacion($mysqli);
ppto_hooks_cargar();

$Emp_Cod = ppto_resolve_emp_id();
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'list';

function ppto_prod_json($data) {
    echo json_encode($data);
    exit();
}

/**
 * Resuelve nombres de usuario (usuarios + persona) por lote.
 *
 * @param mysqli $mysqli
 * @param array $usu_ids lista de Usu_Cod
 * @return array Usu_Cod => nombre
 */
function ppto_prod_nombres_usuarios($mysqli, $usu_ids) {
    $out = array();
    $ids = array();
    foreach ((array)$usu_ids as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    if (empty($ids)) {
        return $out;
    }
    $lista = implode(',', $ids);
    $sql = "SELECT u.Usu_Cod, TRIM(CONCAT(COALESCE(p.Prs_Ape,''), ' ', COALESCE(p.Prs_Nom,''))) AS nombre, u.Usu_Ced
            FROM usuarios u
            LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            WHERE u.Usu_Cod IN ($lista)";
    $res = @$mysqli->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $uid = (int)$row['Usu_Cod'];
            $nombre = trim($row['nombre']);
            if ($nombre === '') {
                $nombre = ($row['Usu_Ced'] !== '' && $row['Usu_Ced'] !== null) ? $row['Usu_Ced'] : ('Usuario ' . $uid);
            }
            $out[$uid] = $nombre;
        }
    }
    return $out;
}

if ($action === 'get_config') {
    $proy_id = $mysqli->real_escape_string(trim($_GET['proy_id']));
    $config = null;
    $res = $mysqli->query("SELECT * FROM pre_prod_config WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $config = $res->fetch_assoc();
    }
    ppto_prod_json(array('status' => 'success', 'config' => $config));
}

if ($action === 'list') {
    $rows = array();
    $res = $mysqli->query("SELECT c.*, p.Pro_Nom AS proy_nombre FROM pre_prod_config c
        INNER JOIN pre_proyectos p ON (c.Pro_Cod = p.Pro_Cod OR c.proy_id = p.Pro_Ide) AND c.Emp_Cod = p.Emp_Cod
        WHERE c.Emp_Cod = $Emp_Cod ORDER BY p.Pro_Nom");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_prod_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'save_config') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $origen = $mysqli->real_escape_string(trim($_POST['pco_origen']));
    $campo = $mysqli->real_escape_string(trim($_POST['pco_campo']));
    $extra_raw = isset($_POST['pco_extra_config']) ? $_POST['pco_extra_config'] : '{"divisor":1000,"tabla":"manifiesto"}';
    $extra = $mysqli->real_escape_string(trim($extra_raw));

    $sql = "INSERT INTO pre_prod_config
            (proy_id, Emp_Cod, pco_origen, pco_campo, pco_frecuencia, pco_extra_config, pco_fecha_registro, Usu_Cod)
            VALUES ('$proy_id', $Emp_Cod, '$origen', '$campo', 'mensual', '$extra', NOW(), " . (int)$Ses_Usu_Cod . ")
            ON DUPLICATE KEY UPDATE pco_origen='$origen', pco_campo='$campo', pco_extra_config='$extra', pco_fecha_registro=NOW()";
    $ok = $mysqli->query($sql);
    ppto_prod_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Configuracion guardada.' : $mysqli->error));
}

if ($action === 'save_plan') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = (int)$_POST['prd_anio'];
    $mes = (int)$_POST['prd_mes'];
    $esperada = (float)$_POST['prd_esperada'];
    ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $esperada, 'esperada', $anio, $Emp_Cod);
    require_once(__DIR__ . '/ppto_divergencia_logica.php');
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    $d2 = ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id ? (int)$ppe_id : null);
    ppto_prod_json(array(
        'status' => 'success',
        'message' => 'Plan mensual actualizado.',
        'divergencia_toneladas' => $d2
    ));
}

if ($action === 'sync_relavera') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = (int)$_POST['prd_anio'];
    $mes = (int)$_POST['prd_mes'];

    $cfg_res = $mysqli->query("SELECT * FROM pre_prod_config WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod LIMIT 1");
    if (!$cfg_res || $cfg_res->num_rows === 0) {
        ppto_prod_json(array('status' => 'error', 'message' => 'El proyecto no tiene configuracion de origen de produccion.'));
    }
    $config = $cfg_res->fetch_assoc();
    $extra = !empty($config['pco_extra_config']) ? json_decode($config['pco_extra_config'], true) : array();
    $divisor = (isset($extra['divisor']) && (float)$extra['divisor'] > 0) ? (float)$extra['divisor'] : 1000.0;
    $campo = !empty($config['pco_campo']) ? $mysqli->real_escape_string($config['pco_campo']) : 'peso_neto';

    $table_check = $mysqli->query("SHOW TABLES LIKE 'manifiesto'");
    if (!$table_check || $table_check->num_rows === 0) {
        ppto_prod_json(array('status' => 'error', 'message' => 'La tabla relavera (manifiesto) no existe en esta base de datos.'));
    }

    $col_check = $mysqli->query("SHOW COLUMNS FROM `manifiesto` LIKE '$campo'");
    if (!$col_check || $col_check->num_rows === 0) {
        ppto_prod_json(array('status' => 'error', 'message' => "La columna '$campo' no existe en la tabla manifiesto."));
    }

    $col_proy = $mysqli->query("SHOW COLUMNS FROM `manifiesto` LIKE 'Proy_Cod'");
    $cond_proy = ($col_proy && $col_proy->num_rows > 0) ? " AND Proy_Cod='$proy_id'" : "";

    $sql_m = "SELECT SUM(`$campo`) AS total FROM `manifiesto` WHERE MONTH(fecha)=$mes AND YEAR(fecha)=$anio $cond_proy";
    $res_m = $mysqli->query($sql_m);
    $toneladas_reales = 0.0;
    if ($res_m && ($row_m = $res_m->fetch_assoc())) {
        $neto = (float)$row_m['total'];
        $toneladas_reales = round($neto / $divisor, 2);
    }

    ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $toneladas_reales, 'real', $anio, $Emp_Cod);
    ppto_integracion_variacion_calcular($mysqli, $proy_id, $mes, $anio);

    ppto_prod_json(array(
        'status' => 'success',
        'toneladas' => $toneladas_reales,
        'message' => "Sincronizacion exitosa desde Relavera ($toneladas_reales ton importadas para el mes $mes/$anio)."
    ));
}

if ($action === 'proyectos') {
    $rows = array();
    $res = $mysqli->query("SELECT Pro_Cod AS proy_id, Pro_Nom AS proy_nombre FROM pre_proyectos WHERE Emp_Cod=$Emp_Cod AND Pro_Est='A' ORDER BY Pro_Nom");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_prod_json(array('status' => 'success', 'proyectos' => $rows));
}

if ($action === 'periodos_tabla') {
    $proy_id = $mysqli->real_escape_string(trim($_GET['proy_id']));
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

    $res = $mysqli->query("SELECT * FROM pre_prod_periodos WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio ORDER BY prd_mes");
    $periodos = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $periodos[(int)$r['prd_mes']] = $r;
        }
    }

    $usu_set = array();
    foreach ($periodos as $pr) {
        if (!empty($pr['Usu_Cod'])) {
            $usu_set[(int)$pr['Usu_Cod']] = true;
        }
        if (!empty($pr['prd_cerrado_usu_id'])) {
            $usu_set[(int)$pr['prd_cerrado_usu_id']] = true;
        }
        if (!empty($pr['prd_reabierto_usu_id'])) {
            $usu_set[(int)$pr['prd_reabierto_usu_id']] = true;
        }
    }

    $nombres_usu = ppto_prod_nombres_usuarios($mysqli, array_keys($usu_set));

    $divergencia_d2 = null;
    $ppe_id_div = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    if (file_exists(__DIR__ . '/ppto_divergencia_logica.php')) {
        require_once(__DIR__ . '/ppto_divergencia_logica.php');
        $divergencia_d2 = ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id_div ? (int)$ppe_id_div : null);
    }

    $out = array();
    for ($m = 1; $m <= 12; $m++) {
        $pr = isset($periodos[$m]) ? $periodos[$m] : null;
        $esp = $pr ? (float)$pr['prd_esperada'] : 0.0;
        $real = $pr ? (float)$pr['prd_real'] : 0.0;
        $proy = $pr ? (float)$pr['prd_proyectada'] : 0.0;
        $var = round($real - $esp, 2);
        $pct = ($esp > 0.0001) ? round(($var / $esp) * 100, 2) : 0.0;
        $estado = $pr ? (isset($pr['prd_estado']) ? $pr['prd_estado'] : 'abierto') : 'abierto';

        $u_reg = ($pr && !empty($pr['Usu_Cod']) && isset($nombres_usu[(int)$pr['Usu_Cod']])) ? $nombres_usu[(int)$pr['Usu_Cod']] : '';
        $u_cerr = ($pr && !empty($pr['prd_cerrado_usu_id']) && isset($nombres_usu[(int)$pr['prd_cerrado_usu_id']])) ? $nombres_usu[(int)$pr['prd_cerrado_usu_id']] : '';

        $out[] = array(
            'mes' => $m,
            'esperada' => $esp,
            'real' => $real,
            'proyectada' => $proy,
            'var_absoluta' => $var,
            'var_porcentual' => $pct,
            'estado' => $estado,
            'fecha_cierre' => ($pr && !empty($pr['prd_fecha_cierre'])) ? $pr['prd_fecha_cierre'] : null,
            'usuario_cierre' => $u_cerr,
            'usuario_registro' => $u_reg
        );
    }

    ppto_prod_json(array(
        'status' => 'success',
        'rows' => $out,
        'divergencia_d2' => $divergencia_d2
    ));
}

ppto_prod_json(array('status' => 'error', 'message' => 'Accion invalida.'));
