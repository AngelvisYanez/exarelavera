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
    $resp = array('status' => 'success', 'message' => 'Plan mensual guardado.', 'divergencia_d2' => $d2);
    if (!empty($d2['warning']) && $d2['mensaje'] !== '') {
        $resp['warning'] = $d2['mensaje'];
    }
    ppto_prod_json($resp);
}

if ($action === 'list_periodos') {
    $proy_id = $mysqli->real_escape_string(trim($_GET['proy_id']));
    $anio = (int)$_GET['anio'];
    $rows = array();
    $res = $mysqli->query("SELECT * FROM pre_prod_periodos WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod AND prd_anio=$anio ORDER BY prd_mes");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    $ppe_id = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    $ton_pdf = ($ppe_id > 0) ? ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id) : 0.0;
    $aprobaciones = array();
    if ($ppe_id > 0) {
        require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
        $map = ppto_proy_publicar_aprobaciones_mes($mysqli, trim($_GET['proy_id']), $Emp_Cod, $ppe_id, $anio);
        $usu_ids = array();
        foreach ($map as $row) {
            $uid = (int)$row['Usu_Cod'];
            if ($uid > 0) {
                $usu_ids[$uid] = $uid;
            }
        }
        $nombres = ppto_prod_nombres_usuarios($mysqli, $usu_ids);
        foreach ($map as $m => $row) {
            $uid = (int)$row['Usu_Cod'];
            $total = round((float)$row['pub_total_nuevo'], 2);
            $anterior = round((float)$row['pub_total_anterior'], 2);
            $aprobaciones[$m] = array(
                'fecha' => $row['pub_fecha_registro'],
                'total' => $total,
                'total_anterior' => $anterior,
                'delta' => round($total - $anterior, 2),
                'modo' => isset($row['pub_modo']) ? $row['pub_modo'] : '',
                'notas' => isset($row['pub_notas']) ? $row['pub_notas'] : '',
                'Usu_Cod' => $uid,
                'usuario' => isset($nombres[$uid]) ? $nombres[$uid] : ($uid > 0 ? ('Usuario ' . $uid) : 'Sistema'),
                'veces' => isset($row['veces']) ? (int)$row['veces'] : 1,
                'pub_id' => (int)$row['pub_id'],
            );
        }
    }
    ppto_prod_json(array(
        'status' => 'success',
        'rows' => $rows,
        'ton_base_pdf' => round($ton_pdf, 4),
        'ppe_id' => $ppe_id,
        'ppe_anio' => $anio,
        'aprobaciones' => $aprobaciones,
    ));
}

if ($action === 'hist_aprobaciones_mes') {
    require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
    $proy_id = isset($_GET['proy_id']) ? trim($_GET['proy_id']) : '';
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    $mes = isset($_GET['prd_mes']) ? (int)$_GET['prd_mes'] : 0;
    if ($proy_id === '' || $mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Parametros invalidos.'));
    }
    $ppe_id = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    if ($ppe_id <= 0) {
        ppto_prod_json(array('status' => 'success', 'rows' => array()));
    }
    $hist = ppto_proy_publicar_historial_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes);
    $usu_ids = array();
    foreach ($hist as $h) {
        $u = (int)$h['Usu_Cod'];
        if ($u > 0) { $usu_ids[$u] = $u; }
    }
    $nombres = ppto_prod_nombres_usuarios($mysqli, $usu_ids);
    $out = array();
    $n = count($hist);
    foreach ($hist as $i => $h) {
        $uid = (int)$h['Usu_Cod'];
        $total = round((float)$h['pub_total_nuevo'], 2);
        $anterior = round((float)$h['pub_total_anterior'], 2);
        $out[] = array(
            'pub_id' => (int)$h['pub_id'],
            'orden' => $n - $i,
            'es_actual' => ($i === 0),
            'fecha' => $h['pub_fecha_registro'],
            'total' => $total,
            'total_anterior' => $anterior,
            'delta' => round($total - $anterior, 2),
            'modo' => isset($h['pub_modo']) ? $h['pub_modo'] : '',
            'notas' => isset($h['pub_notas']) ? $h['pub_notas'] : '',
            'Usu_Cod' => $uid,
            'usuario' => isset($nombres[$uid]) ? $nombres[$uid] : ($uid > 0 ? ('Usuario ' . $uid) : 'Sistema'),
        );
    }
    ppto_prod_json(array('status' => 'success', 'mes' => $mes, 'total_registros' => $n, 'rows' => $out));
}

if ($action === 'preview_aprobar_mes' || $action === 'aprobar_mes') {
    require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
    $proy_id = isset($_REQUEST['proy_id']) ? trim($_REQUEST['proy_id']) : '';
    $anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : (int)date('Y');
    $mes = isset($_REQUEST['prd_mes']) ? (int)$_REQUEST['prd_mes'] : 0;
    if ($proy_id === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Mes invalido.'));
    }
    $ppe_id = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    if ($ppe_id <= 0) {
        ppto_prod_json(array('status' => 'error', 'message' => 'No hay version de presupuesto para el anio ' . $anio . '. Definala en Proyectos.'));
    }
    $ton_override = null;
    if (isset($_REQUEST['prd_proyectada']) && $_REQUEST['prd_proyectada'] !== '') {
        $ton_override = (float)$_REQUEST['prd_proyectada'];
    }
    if ($action === 'preview_aprobar_mes') {
        $prev = ppto_proy_publicar_preview_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes, $ton_override);
        if (empty($prev['ok'])) {
            ppto_prod_json(array('status' => 'error', 'message' => isset($prev['message']) ? $prev['message'] : 'No se pudo generar vista previa.'));
        }
        ppto_prod_json(array('status' => 'success', 'preview' => $prev));
    }
    $confirmar = !empty($_POST['confirmar_reaprobacion']);
    $res = ppto_proy_publicar_ejecutar_mes($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, $mes, (int)$Ses_Usu_Cod, $ton_override, $confirmar);
    if (!empty($res['needs_confirm'])) {
        ppto_prod_json(array(
            'status' => 'confirm',
            'message' => $res['message'],
            'preview' => $res['preview'],
        ));
    }
    if (empty($res['ok'])) {
        ppto_prod_json(array(
            'status' => 'error',
            'message' => isset($res['message']) ? $res['message'] : 'No se pudo aprobar.',
            'bloqueos' => isset($res['bloqueos']) ? $res['bloqueos'] : array(),
        ));
    }
    ppto_prod_json(array(
        'status' => 'success',
        'message' => $res['message'],
        'result' => $res,
    ));
}

if ($action === 'sync_esperada_pdf') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $solo_vacios = !empty($_POST['solo_vacios']);
    $forzar = !empty($_POST['forzar']);
    if ($proy_id === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    $sync = ppto_prod_sync_esperada_desde_ton_base($mysqli, $proy_id, $Emp_Cod, $anio, 0, array(
        'solo_vacios' => $solo_vacios && !$forzar,
        'preservar_cerrados' => !$forzar,
    ));
    ppto_prod_json(array(
        'status' => !empty($sync['ok']) ? 'success' : 'error',
        'message' => $sync['message'],
        'sync' => $sync,
    ));
}

if ($action === 'sync_relavera') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    ppto_sync_relavera_produccion($mysqli, $proy_id, $Emp_Cod, $anio);
    ppto_prod_json(array('status' => 'success', 'message' => 'Produccion sincronizada desde origen configurado.'));
}

if ($action === 'save_cuadro') {
    require_once('ppto_integracion_motor.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $raw = isset($_POST['filas']) ? $_POST['filas'] : '[]';
    $items = json_decode($raw, true);
    if (!is_array($items)) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Datos del cuadro invalidos.'));
    }
    $guardados = 0;
    foreach ($items as $item) {
        if (!isset($item['mes'])) {
            continue;
        }
        $mes = (int)$item['mes'];
        if ($mes < 1 || $mes > 12) {
            continue;
        }
        $esperada = isset($item['esperada']) ? (float)$item['esperada'] : 0;
        $proyectada = isset($item['proyectada']) ? (float)$item['proyectada'] : 0;
        $ok1 = ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $esperada, 'esperada', $anio, $Emp_Cod);
        $ok2 = ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $proyectada, 'proyectada', $anio, $Emp_Cod);
        if ($ok1 && $ok2) {
            $guardados++;
        }
    }
    ppto_prod_json(array(
        'status' => 'success',
        'message' => 'Cuadro guardado (' . $guardados . ' meses).'
    ));
}

if ($action === 'save_proyectadas') {
    require_once('ppto_integracion_motor.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $raw = isset($_POST['proyectadas']) ? $_POST['proyectadas'] : '[]';
    $items = json_decode($raw, true);
    if (!is_array($items)) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Datos de proyeccion invalidos.'));
    }
    $guardados = 0;
    foreach ($items as $item) {
        if (!isset($item['mes'])) {
            continue;
        }
        $mes = (int)$item['mes'];
        $valor = isset($item['valor']) ? (float)$item['valor'] : 0;
        if ($mes < 1 || $mes > 12) {
            continue;
        }
        if (ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $valor, 'proyectada', $anio, $Emp_Cod)) {
            $guardados++;
        }
    }
    ppto_prod_json(array(
        'status' => 'success',
        'message' => 'Proyecciones guardadas (' . $guardados . ' meses).'
    ));
}

if ($action === 'save_proyectada') {
    require_once('ppto_integracion_motor.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    $valor = (float)$_POST['prd_proyectada'];
    if ($mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Mes invalido.'));
    }
    $ok = ppto_integracion_produccion_registrar($mysqli, $proy_id, $mes, $valor, 'proyectada', $anio, $Emp_Cod);
    ppto_prod_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Proyectada actualizada en mes ' . $mes . '.' : 'No se pudo guardar la proyectada.'
    ));
}

if ($action === 'insert_proyectada') {
    require_once('ppto_integracion_motor.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes_corte = null;
    if (isset($_POST['mes_corte']) && $_POST['mes_corte'] !== '') {
        $mes_corte = (int)$_POST['mes_corte'];
    }
    $mes_destino = null;
    if (isset($_POST['mes_destino']) && $_POST['mes_destino'] !== '') {
        $mes_destino = (int)$_POST['mes_destino'];
    }
    $result = ppto_integracion_proyectar_promedio_siguiente_mes($mysqli, $proy_id, $anio, $mes_corte, $Emp_Cod, $mes_destino);
    ppto_prod_json(array(
        'status' => $result['ok'] ? 'success' : 'error',
        'message' => $result['message'],
        'detail' => $result
    ));
}

if ($action === 'cerrar_periodo') {
    require_once('../VALIDACIONES/ppto_prod_validaciones.php');
    require_once('ppto_prod_periodo_logica.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    if (!array_key_exists('prd_real', $_POST)) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Debe indicar el valor real al cerrar.'));
    }
    $real_val = (float)$_POST['prd_real'];
    $obDatos = new Class_Log_Datos_Con();
    $Ses_Dat_Aut = isset($_SESSION['Ses_Dat_Aut']) ? $_SESSION['Ses_Dat_Aut'] : null;
    $request_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    $result = ppto_prod_periodo_cerrar(
        $mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes,
        (int)$Ses_Usu_Cod, $real_val, $Ses_Dat_Aut, $request_uri
    );
    ppto_prod_json(array(
        'status' => $result['ok'] ? 'success' : 'error',
        'message' => $result['message']
    ));
}

if ($action === 'reabrir_periodo') {
    require_once('../VALIDACIONES/ppto_prod_validaciones.php');
    require_once('ppto_prod_periodo_logica.php');
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    $obDatos = new Class_Log_Datos_Con();
    $Ses_Dat_Aut = isset($_SESSION['Ses_Dat_Aut']) ? $_SESSION['Ses_Dat_Aut'] : null;
    $request_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    $result = ppto_prod_periodo_reabrir(
        $mysqli, $obDatos, $proy_id, $Emp_Cod, $anio, $mes,
        (int)$Ses_Usu_Cod, $motivo, $Ses_Dat_Aut, $request_uri
    );
    ppto_prod_json(array(
        'status' => $result['ok'] ? 'success' : 'error',
        'message' => $result['message']
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
    ppto_prod_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'divergencia_d2') {
    require_once(__DIR__ . '/ppto_divergencia_logica.php');
    require_once(__DIR__ . '/ppto_persistencia_logica.php');
    $proy_id = isset($_GET['proy_id']) ? trim($_GET['proy_id']) : '';
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    if ($proy_id === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'ppe_anio' => $anio));
    $d2 = ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id ? (int)$ppe_id : null);
    $d2['alineado_sn'] = $d2['alineado'] ? 'S' : 'N';
    ppto_prod_json(array('status' => 'success', 'divergencia_d2' => $d2));
}

ppto_prod_json(array('status' => 'error', 'message' => 'Accion no soportada.'));
