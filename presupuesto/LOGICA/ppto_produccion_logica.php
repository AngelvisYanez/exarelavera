<?php
/**
 * Configuracion de produccion, plan mensual y sincronizacion Relavera.
 */
if (!ob_get_level()) {
    @ob_start();
}
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once('../../administrador/LOGICA/seguridad.php');
if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) {
    DebugBar::setDebugBar(null);
}
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
    if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) {
        DebugBar::setDebugBar(null);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $json = json_encode($data);
    // Un solo buffer limpio: el shutdown de DebugBar hace ob_end_flush sin mezclar BOM/avisos previos.
    @ob_start();
    echo $json;
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_GET['Pro_Cod']));
    $config = null;
    $res = $mysqli->query("SELECT Pco_Cod AS pco_id, Pro_Cod AS proy_id, Emp_Cod,
            Pco_Origen AS pco_origen, Pco_Campo AS pco_campo, Pco_Frecuencia AS pco_frecuencia,
            Pco_MetodoFc AS pco_metodo_forecast, Pco_FecIni AS pco_periodo_inicio, Pco_FecFin AS pco_periodo_fin,
            Pco_CfgExtra AS pco_extra_config, Pco_FecReg AS pco_fecha_registro, Usu_Cod
            FROM pre_prod_config WHERE Pro_Cod='$Pro_Cod' AND Emp_Cod=$Emp_Cod LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $config = $res->fetch_assoc();
    }
    ppto_prod_json(array('status' => 'success', 'config' => $config));
}

if ($action === 'list') {
    $rows = array();
    $res = $mysqli->query("SELECT c.Pco_Cod AS pco_id, c.Pro_Cod AS proy_id, c.Emp_Cod,
            c.Pco_Origen AS pco_origen, c.Pco_Campo AS pco_campo, c.Pco_Frecuencia AS pco_frecuencia,
            c.Pco_MetodoFc AS pco_metodo_forecast, c.Pco_FecIni AS pco_periodo_inicio, c.Pco_FecFin AS pco_periodo_fin,
            c.Pco_CfgExtra AS pco_extra_config, c.Pco_FecReg AS pco_fecha_registro, c.Usu_Cod,
            p.Pro_Nom AS Pro_Nom, p.Pro_Nom AS proy_nombre
         FROM pre_prod_config c
        INNER JOIN pre_proyectos p ON c.Pro_Cod = p.Pro_Cod AND c.Emp_Cod = p.Emp_Cod
        WHERE c.Emp_Cod = $Emp_Cod ORDER BY p.Pro_Nom");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_prod_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'save_config') {
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $origen = $mysqli->real_escape_string(trim($_POST['pco_origen']));
    $campo = $mysqli->real_escape_string(trim($_POST['pco_campo']));
    $extra_raw = isset($_POST['pco_extra_config']) ? $_POST['pco_extra_config'] : '{"divisor":1000,"tabla":"manifiesto"}';
    $extra = $mysqli->real_escape_string(trim($extra_raw));

    $sql = "INSERT INTO pre_prod_config
            (Pro_Cod, Emp_Cod, Pco_Origen, Pco_Campo, Pco_Frecuencia, Pco_CfgExtra, Pco_FecReg, Usu_Cod)
            VALUES ('$Pro_Cod', $Emp_Cod, '$origen', '$campo', 'mensual', '$extra', NOW(), " . (int)$Ses_Usu_Cod . ")
            ON DUPLICATE KEY UPDATE Pco_Origen='$origen', Pco_Campo='$campo', Pco_CfgExtra='$extra', Pco_FecReg=NOW()";
    $ok = $mysqli->query($sql);
    ppto_prod_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Configuracion guardada.' : $mysqli->error));
}

if ($action === 'save_plan') {
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = (int)$_POST['prd_anio'];
    $mes = (int)$_POST['prd_mes'];
    $esperada = (float)$_POST['prd_esperada'];
    ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $esperada, 'esperada', $anio, $Emp_Cod);
    require_once(__DIR__ . '/ppto_divergencia_logica.php');
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    $d2 = ppto_divergencia_comparar_toneladas($mysqli, $Pro_Cod, $Emp_Cod, $anio, $Ppe_Cod ? (int)$Ppe_Cod : null);
    $resp = array('status' => 'success', 'message' => 'Plan mensual guardado.', 'divergencia_d2' => $d2);
    if (!empty($d2['warning']) && $d2['mensaje'] !== '') {
        $resp['warning'] = $d2['mensaje'];
    }
    ppto_prod_json($resp);
}

if ($action === 'list_periodos') {
    $Pro_Cod = $mysqli->real_escape_string(trim($_GET['Pro_Cod']));
    $anio = (int)$_GET['anio'];
    $rows = array();
    $res = $mysqli->query("SELECT Prd_Cod AS prd_id, Pro_Cod AS proy_id, Emp_Cod, Prd_Anio AS prd_anio, Prd_Mes AS prd_mes,
            Prd_Esperada AS prd_esperada, Prd_Real AS prd_real, Prd_Proyectada AS prd_proyectada,
            Prd_Est AS prd_estado, Prd_FecCierre AS prd_fecha_cierre, Prd_Unidad AS prd_unidad,
            Prd_FecReg AS prd_fecha_registro, Usu_Cod
            FROM pre_prod_periodos WHERE Pro_Cod='$Pro_Cod' AND Emp_Cod=$Emp_Cod AND Prd_Anio=$anio ORDER BY Prd_Mes");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    $Ppe_Cod = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    $ton_pdf = ($Ppe_Cod > 0) ? ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) : 0.0;
    $aprobaciones = array();
    if ($Ppe_Cod > 0) {
        require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
        $map = ppto_proy_publicar_aprobaciones_mes($mysqli, trim($_GET['Pro_Cod']), $Emp_Cod, $Ppe_Cod, $anio);
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
        'Ppe_Cod' => $Ppe_Cod,
        'Ppe_Ani' => $anio,
        'aprobaciones' => $aprobaciones,
    ));
}

if ($action === 'hist_aprobaciones_mes') {
    require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
    $Pro_Cod = isset($_GET['Pro_Cod']) ? trim($_GET['Pro_Cod']) : '';
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    $mes = isset($_GET['prd_mes']) ? (int)$_GET['prd_mes'] : 0;
    if ($Pro_Cod === '' || $mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Parametros invalidos.'));
    }
    $Ppe_Cod = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    if ($Ppe_Cod <= 0) {
        ppto_prod_json(array('status' => 'success', 'rows' => array()));
    }
    $hist = ppto_proy_publicar_historial_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes);
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
    $Pro_Cod = isset($_REQUEST['Pro_Cod']) ? trim($_REQUEST['Pro_Cod']) : '';
    $anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : (int)date('Y');
    $mes = isset($_REQUEST['prd_mes']) ? (int)$_REQUEST['prd_mes'] : 0;
    if ($Pro_Cod === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Mes invalido.'));
    }
    $Ppe_Cod = ppto_proy_version_ppe_id_por_anio($mysqli, $Emp_Cod, $anio);
    if ($Ppe_Cod <= 0) {
        ppto_prod_json(array('status' => 'error', 'message' => 'No hay version de presupuesto para el anio ' . $anio . '. Definala en Proyectos.'));
    }
    $ton_override = null;
    if (isset($_REQUEST['prd_proyectada']) && $_REQUEST['prd_proyectada'] !== '') {
        $ton_override = (float)$_REQUEST['prd_proyectada'];
    }
    if ($action === 'preview_aprobar_mes') {
        $prev = ppto_proy_publicar_preview_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes, $ton_override);
        if (empty($prev['ok'])) {
            ppto_prod_json(array('status' => 'error', 'message' => isset($prev['message']) ? $prev['message'] : 'No se pudo generar vista previa.'));
        }
        ppto_prod_json(array('status' => 'success', 'preview' => $prev));
    }
    $confirmar = !empty($_POST['confirmar_reaprobacion']);
    $res = ppto_proy_publicar_ejecutar_mes($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, $mes, (int)$Ses_Usu_Cod, $ton_override, $confirmar);
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $solo_vacios = !empty($_POST['solo_vacios']);
    $forzar = !empty($_POST['forzar']);
    if ($Pro_Cod === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    $sync = ppto_prod_sync_esperada_desde_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $anio, 0, array(
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    ppto_sync_relavera_produccion($mysqli, $Pro_Cod, $Emp_Cod, $anio);
    ppto_prod_json(array('status' => 'success', 'message' => 'Produccion sincronizada desde origen configurado.'));
}

if ($action === 'save_cuadro') {
    require_once('ppto_integracion_motor.php');
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
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
        $ok1 = ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $esperada, 'esperada', $anio, $Emp_Cod);
        $ok2 = ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $proyectada, 'proyectada', $anio, $Emp_Cod);
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
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
        if (ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $valor, 'proyectada', $anio, $Emp_Cod)) {
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    $valor = (float)$_POST['prd_proyectada'];
    if ($mes < 1 || $mes > 12) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Mes invalido.'));
    }
    $ok = ppto_integracion_produccion_registrar($mysqli, $Pro_Cod, $mes, $valor, 'proyectada', $anio, $Emp_Cod);
    ppto_prod_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Proyectada actualizada en mes ' . $mes . '.' : 'No se pudo guardar la proyectada.'
    ));
}

if ($action === 'insert_proyectada') {
    require_once('ppto_integracion_motor.php');
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes_corte = null;
    if (isset($_POST['mes_corte']) && $_POST['mes_corte'] !== '') {
        $mes_corte = (int)$_POST['mes_corte'];
    }
    $mes_destino = null;
    if (isset($_POST['mes_destino']) && $_POST['mes_destino'] !== '') {
        $mes_destino = (int)$_POST['mes_destino'];
    }
    $result = ppto_integracion_proyectar_promedio_siguiente_mes($mysqli, $Pro_Cod, $anio, $mes_corte, $Emp_Cod, $mes_destino);
    ppto_prod_json(array(
        'status' => $result['ok'] ? 'success' : 'error',
        'message' => $result['message'],
        'detail' => $result
    ));
}

if ($action === 'cerrar_periodo') {
    require_once('../VALIDACIONES/ppto_prod_validaciones.php');
    require_once('ppto_prod_periodo_logica.php');
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    if (!array_key_exists('Prd_Real', $_POST)) {
        ppto_prod_json(array('status' => 'error', 'message' => 'Debe indicar el valor real al cerrar.'));
    }
    $real_val = (float)$_POST['prd_real'];
    $obDatos = new Class_Log_Datos_Con();
    $Ses_Dat_Aut = isset($_SESSION['Ses_Dat_Aut']) ? $_SESSION['Ses_Dat_Aut'] : null;
    $request_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    $result = ppto_prod_periodo_cerrar(
        $mysqli, $obDatos, $Pro_Cod, $Emp_Cod, $anio, $mes,
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
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $anio = isset($_POST['anio']) ? (int)$_POST['anio'] : (int)date('Y');
    $mes = (int)$_POST['prd_mes'];
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    $obDatos = new Class_Log_Datos_Con();
    $Ses_Dat_Aut = isset($_SESSION['Ses_Dat_Aut']) ? $_SESSION['Ses_Dat_Aut'] : null;
    $request_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    $result = ppto_prod_periodo_reabrir(
        $mysqli, $obDatos, $Pro_Cod, $Emp_Cod, $anio, $mes,
        (int)$Ses_Usu_Cod, $motivo, $Ses_Dat_Aut, $request_uri
    );
    ppto_prod_json(array(
        'status' => $result['ok'] ? 'success' : 'error',
        'message' => $result['message']
    ));
}

if ($action === 'proyectos') {
    $rows = array();
    $res = $mysqli->query("SELECT Pro_Cod AS Pro_Cod, Pro_Cod AS proy_id, Pro_Ide AS Pro_Codigo, Pro_Ide AS proy_codigo, Pro_Nom AS Pro_Nom, Pro_Nom AS proy_nombre, CONCAT(Pro_Ide,' - ',Pro_Nom) AS label FROM pre_proyectos WHERE Emp_Cod=$Emp_Cod AND Pro_Est='A' ORDER BY Pro_Nom");
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
    $Pro_Cod = isset($_GET['Pro_Cod']) ? trim($_GET['Pro_Cod']) : '';
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    if ($Pro_Cod === '') {
        ppto_prod_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    $d2 = ppto_divergencia_comparar_toneladas($mysqli, $Pro_Cod, $Emp_Cod, $anio, $Ppe_Cod ? (int)$Ppe_Cod : null);
    $d2['alineado_sn'] = $d2['alineado'] ? 'S' : 'N';
    ppto_prod_json(array('status' => 'success', 'divergencia_d2' => $d2));
}

ppto_prod_json(array('status' => 'error', 'message' => 'Accion no soportada.'));
