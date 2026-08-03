<?php
/**
 * CRUD AJAX de proyectos presupuestarios (exa_ppto_proyectos + rubros).
 */
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('ppto_schema_logica.php');
require_once('ppto_partidas_logica.php');
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
        ppto_schema_ensure_partida_porcentaje($mysqli);
        ppto_schema_ensure_partida_meses_prorrateo($mysqli);
        ppto_schema_ensure_proyecto_version($mysqli);
        ppto_schema_ensure_proyecto_publicacion($mysqli);

$Emp_Cod = ppto_resolve_emp_id();
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'list';

function ppto_json($data) {
    echo ppto_json_encode_safe($data);
    exit();
}

/**
 * Valida archivo presupuestario subido (PDF, Excel o CSV).
 *
 * @return array
 */
function ppto_proy_presupuesto_validar_upload() {
    if (!isset($_FILES['pdf_file']) || !is_array($_FILES['pdf_file'])) {
        return array('ok' => false, 'message' => 'Seleccione un archivo PDF, Excel (.xlsx/.xls) o CSV.');
    }
    $f = $_FILES['pdf_file'];
    if (!isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) {
        $cod = isset($f['error']) ? (int)$f['error'] : -1;
        $msg_upload = 'Error al subir el archivo.';
        if ($cod === UPLOAD_ERR_INI_SIZE || $cod === UPLOAD_ERR_FORM_SIZE) {
            $msg_upload = 'El archivo es demasiado grande para el servidor. Reduzca el tamano o aumente upload_max_filesize en PHP.';
        }
        return array('ok' => false, 'message' => $msg_upload);
    }
    if (!isset($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
        return array('ok' => false, 'message' => 'Archivo invalido.');
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $allowed = array('pdf', 'csv', 'xls', 'xlsx', 'xlsm');
    if (!in_array($ext, $allowed, true)) {
        return array('ok' => false, 'message' => 'Formatos permitidos: .pdf, .xlsx, .xls, .csv');
    }
    if ($f['size'] > 15 * 1024 * 1024) {
        return array('ok' => false, 'message' => 'El archivo no puede superar 15 MB.');
    }
    return array(
        'ok' => true,
        'path' => $f['tmp_name'],
        'name' => $f['name'],
        'ext' => $ext,
        'mime' => isset($f['type']) ? (string)$f['type'] : '',
    );
}

/** @deprecated use ppto_proy_presupuesto_validar_upload */
function ppto_proy_pdf_validar_upload() {
    return ppto_proy_presupuesto_validar_upload();
}

/**
 * Aplica ton costo egreso a rubros driver: actualiza ton/mes, mantiene $/Ton (factor Excel).
 * El presupuesto Base PDF se recalcula como ton_costo x factor; pdp_presupuesto_anual en BD conserva el Excel (referencia).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param float $ton_mes
 * @return array
 */
function ppto_proy_recalcular_rubros_preservar_anual($mysqli, $proy_id, $Emp_Cod, $ppe_id, $ton_mes, $ton_origen = 0) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $ton_mes = ppto_version_ton_costo_sanitize($ton_mes);
    if ($ton_mes <= 0) {
        return array('ok' => false, 'message' => 'Toneladas costo invalidas.', 'actualizados' => 0);
    }

    $actualizados = 0;
    $res = $mysqli->query("SELECT d.pdp_id, d.pdp_factor_anual_tonelada
        FROM exa_ppto_proyecto_detalles d
        WHERE d.proy_id='$esc' AND d.Emp_Cod=$Emp_Cod AND d.ppe_id=$ppe_id
          AND d.pdp_factor_anual_tonelada > 0");
    while ($res && ($row = $res->fetch_assoc())) {
        $pdp_id = (int)$row['pdp_id'];
        $factor = (float)$row['pdp_factor_anual_tonelada'];
        if ($factor <= 0.0001) {
            continue;
        }
        $mysqli->query("UPDATE exa_ppto_proyecto_detalles
            SET pdp_toneladas_base=$ton_mes
            WHERE pdp_id=$pdp_id");
        $actualizados++;
    }

    return array(
        'ok' => true,
        'message' => 'Ton costo aplicada. $/Ton intacto; presupuesto Base PDF = ton x $/Ton.',
        'actualizados' => $actualizados,
    );
}

/**
 * Guarda ton base y opcionalmente aplica ton costo a rubros ($/Ton fijo, presup. Base PDF recalculado).
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param float $ton_mes
 * @param int $Usu_Cod
 * @param bool $aplicar_rubros
 * @return array
 */
function ppto_proy_version_guardar_ton($mysqli, $proy_id, $Emp_Cod, $ppe_id, $ton_mes, $Usu_Cod, $aplicar_rubros = false, $tarifa_iva = 3.0, $iva_div = 1.15, $ton_costo_mes = 0) {
    $esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $ton_mes = (float)$ton_mes;
    $ton_costo_mes = ppto_version_ton_costo_sanitize($ton_costo_mes);
    $Usu_Cod = (int)$Usu_Cod;
    $tarifa_iva = (float)$tarifa_iva;
    $iva_div = (float)$iva_div;
    if ($tarifa_iva <= 0) {
        $tarifa_iva = 3.0;
    }
    if ($iva_div <= 0) {
        $iva_div = 1.15;
    }

    $sql = "INSERT INTO exa_ppto_proyecto_version
            (proy_id, Emp_Cod, ppe_id, pv_toneladas_base_mes, pv_toneladas_costo_mes, pv_tarifa_ton_iva, pv_iva_divisor, pv_fecha_registro, Usu_Cod)
            VALUES ('$esc', $Emp_Cod, $ppe_id, $ton_mes, $ton_costo_mes, $tarifa_iva, $iva_div, NOW(), $Usu_Cod)
            ON DUPLICATE KEY UPDATE pv_toneladas_base_mes=$ton_mes, pv_toneladas_costo_mes=$ton_costo_mes, pv_tarifa_ton_iva=$tarifa_iva,
                pv_iva_divisor=$iva_div, pv_fecha_registro=NOW(), Usu_Cod=$Usu_Cod";
    $ok = $mysqli->query($sql);
    if (!$ok) {
        return array('ok' => false, 'message' => $mysqli->error);
    }

    $actualizados = 0;
    if ($aplicar_rubros && $ton_costo_mes > 0) {
        $rec = ppto_proy_recalcular_rubros_preservar_anual($mysqli, $proy_id, $Emp_Cod, $ppe_id, $ton_costo_mes);
        $actualizados = !empty($rec['actualizados']) ? (int)$rec['actualizados'] : 0;
    }

    return array('ok' => true, 'message' => 'Toneladas base guardadas.', 'rubros_actualizados' => $actualizados, 'pv_toneladas_costo_mes' => $ton_costo_mes);
}

/**
 * Sincroniza plan de produccion esperada tras guardar ton base PDF.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array|null
 */
function ppto_proy_version_sync_prod_esperada($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    $ppe_id = (int)$ppe_id;
    if ($ppe_id <= 0) {
        return null;
    }
    $res = $mysqli->query("SELECT ppe_anio FROM exa_ppto_cabeceras WHERE ppe_id=$ppe_id LIMIT 1");
    if (!$res || !($row = $res->fetch_assoc())) {
        return null;
    }
    require_once __DIR__ . '/ppto_proyecto_version_logica.php';
    return ppto_prod_sync_esperada_desde_ton_base($mysqli, $proy_id, $Emp_Cod, (int)$row['ppe_anio'], $ppe_id, array(
        'preservar_cerrados' => true,
    ));
}

/**
 * Clona partidas de plantilla al crear un proyecto.
 */
function ppto_proy_clonar_plantilla($mysqli, $proy_id, $plt_id, $Emp_Cod, $Usu_Cod) {
    $plt_id = (int)$plt_id;
    if ($plt_id <= 0) {
        return;
    }
    $anio = (int)date('Y');
    $res_ppe = $mysqli->query("SELECT ppe_id FROM exa_ppto_cabeceras WHERE Emp_Cod=$Emp_Cod AND ppe_anio=$anio AND ppe_estado='A' ORDER BY ppe_version DESC LIMIT 1");
    if (!$res_ppe || !($v = $res_ppe->fetch_assoc())) {
        return;
    }
    $ppe_id = (int)$v['ppe_id'];
    $proy_esc = $mysqli->real_escape_string($proy_id);
    $res = $mysqli->query("SELECT pp.ppa_id, p.ppa_descripcion
        FROM exa_ppto_plantilla_partidas pp
        INNER JOIN exa_ppto_partidas p ON pp.ppa_id = p.ppa_id
        WHERE pp.plt_id = $plt_id");
    if (!$res) {
        return;
    }
    while ($row = $res->fetch_assoc()) {
        $ppa_id = (int)$row['ppa_id'];
        $rubro = $mysqli->real_escape_string($row['ppa_descripcion']);
        $mysqli->query("INSERT IGNORE INTO exa_ppto_proyecto_detalles
            (ppe_id, ppa_id, proy_id, Emp_Cod, pdp_rubro, pdp_toneladas_base, pdp_factor_anual_tonelada, pdp_presupuesto_anual, pdp_fecha_registro, Usu_Cod)
            VALUES ($ppe_id, $ppa_id, '$proy_esc', $Emp_Cod, '$rubro', 0, 0, 0, NOW(), " . (int)$Usu_Cod . ")");
        $pdp_id = (int)$mysqli->insert_id;
        if ($pdp_id <= 0) {
            $r2 = $mysqli->query("SELECT pdp_id FROM exa_ppto_proyecto_detalles WHERE ppe_id=$ppe_id AND ppa_id=$ppa_id AND proy_id='$proy_esc' AND pdp_rubro='$rubro' LIMIT 1");
            if ($r2 && ($x = $r2->fetch_assoc())) {
                $pdp_id = (int)$x['pdp_id'];
            }
        }
        if ($pdp_id > 0) {
            ppto_proy_distribuir_meses($mysqli, $pdp_id, 0);
        }
    }
}

/**
 * Busca rubro de proyecto por partida (codigo via ppa_id), no por nombre.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param int $ppa_id
 * @param string $proy_id
 * @param int $Emp_Cod
 * @return int
 */
function ppto_proy_rubro_id_por_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $Emp_Cod) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $Emp_Cod = (int)$Emp_Cod;
    $proy_esc = $mysqli->real_escape_string(trim($proy_id));
    if ($ppe_id <= 0 || $ppa_id <= 0 || $proy_esc === '') {
        return 0;
    }
    $r = $mysqli->query("SELECT pdp_id FROM exa_ppto_proyecto_detalles
        WHERE ppe_id=$ppe_id AND ppa_id=$ppa_id AND proy_id='$proy_esc' AND Emp_Cod=$Emp_Cod
        ORDER BY pdp_id ASC LIMIT 1");
    if ($r && ($x = $r->fetch_assoc())) {
        return (int)$x['pdp_id'];
    }
    return 0;
}

/**
 * Mapa codigo partida => rubro existente en proyecto/version.
 *
 * @param mysqli $mysqli
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_proy_rubros_map_por_codigo($mysqli, $proy_id, $Emp_Cod, $ppe_id) {
    $proy_esc = $mysqli->real_escape_string(trim($proy_id));
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $map = array();
    if ($proy_esc === '' || $ppe_id <= 0) {
        return $map;
    }
    $sql = "SELECT p.ppa_codigo_clasificacion, d.pdp_id, d.pdp_rubro
        FROM exa_ppto_proyecto_detalles d
        INNER JOIN exa_ppto_partidas p ON p.ppa_id = d.ppa_id AND p.Emp_Cod = d.Emp_Cod
        WHERE d.proy_id='$proy_esc' AND d.Emp_Cod=$Emp_Cod AND d.ppe_id=$ppe_id
        ORDER BY d.pdp_id ASC";
    $res = $mysqli->query($sql);
    while ($res && ($row = $res->fetch_assoc())) {
        $cod = $row['ppa_codigo_clasificacion'];
        if (!isset($map[$cod])) {
            $map[$cod] = array(
                'pdp_id' => (int)$row['pdp_id'],
                'pdp_rubro' => $row['pdp_rubro'],
            );
        }
    }
    return $map;
}

/**
 * Elimina rubros duplicados de la misma partida dejando solo pdp_id_keep.
 *
 * @param mysqli $mysqli
 * @param int $ppe_id
 * @param int $ppa_id
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $pdp_id_keep
 * @return int
 */
function ppto_proy_rubro_purgar_duplicados_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $Emp_Cod, $pdp_id_keep) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $Emp_Cod = (int)$Emp_Cod;
    $pdp_id_keep = (int)$pdp_id_keep;
    $proy_esc = $mysqli->real_escape_string(trim($proy_id));
    if ($pdp_id_keep <= 0 || $ppe_id <= 0 || $ppa_id <= 0 || $proy_esc === '') {
        return 0;
    }
    $eliminados = 0;
    $res = $mysqli->query("SELECT pdp_id FROM exa_ppto_proyecto_detalles
        WHERE ppe_id=$ppe_id AND ppa_id=$ppa_id AND proy_id='$proy_esc' AND Emp_Cod=$Emp_Cod AND pdp_id<>$pdp_id_keep");
    if (!$res) {
        return 0;
    }
    while ($row = $res->fetch_assoc()) {
        $del = ppto_proy_rubro_eliminar($mysqli, (int)$row['pdp_id'], $proy_id, $Emp_Cod, $ppe_id);
        if (!empty($del['ok'])) {
            $eliminados++;
        }
    }
    return $eliminados;
}

/**
 * Crea o actualiza 12 meses de distribucion para un rubro de proyecto.
 */
function ppto_proy_distribuir_meses($mysqli, $pdp_id, $anual) {
    $pdp_id = (int)$pdp_id;
    $mensual = round((float)$anual / 12, 2);
    for ($mes = 1; $mes <= 12; $mes++) {
        $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes
            (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
            VALUES ($pdp_id, $mes, 22, 0.0833, $mensual, 0, 0, $mensual)
            ON DUPLICATE KEY UPDATE pdm_presupuesto_mensual=$mensual, pdm_disponible=GREATEST(0, $mensual - pdm_ejecutado - pdm_comprometido)");
    }
}

/**
 * Elimina un rubro de proyecto (detalle y meses asociados).
 *
 * @param mysqli $mysqli
 * @param int $pdp_id
 * @param string $proy_id
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array
 */
function ppto_proy_rubro_eliminar($mysqli, $pdp_id, $proy_id, $Emp_Cod, $ppe_id) {
    $pdp_id = (int)$pdp_id;
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    $proy_esc = $mysqli->real_escape_string(trim($proy_id));

    if ($pdp_id <= 0 || $proy_esc === '' || $ppe_id <= 0) {
        return array('ok' => false, 'message' => 'Datos incompletos para eliminar el rubro.');
    }

    $chk = $mysqli->query("SELECT pdp_id, ppa_id FROM exa_ppto_proyecto_detalles
        WHERE pdp_id=$pdp_id AND proy_id='$proy_esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1");
    if (!$chk || $chk->num_rows <= 0) {
        return array('ok' => false, 'message' => 'Rubro no encontrado en este proyecto y version.');
    }
    $rubro_row = $chk->fetch_assoc();
    $ppa_id_rubro = (int)$rubro_row['ppa_id'];

    $mov = $mysqli->query("SELECT SUM(pdm_ejecutado + pdm_comprometido) AS mov
        FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id=$pdp_id");
    if ($mov && ($m = $mov->fetch_assoc()) && (float)$m['mov'] > 0.01) {
        return array('ok' => false, 'message' => 'No se puede eliminar: el rubro tiene montos ejecutados o comprometidos.');
    }

    $mysqli->query("DELETE FROM exa_ppto_proyecto_detalles_mes WHERE pdp_id=$pdp_id");

    if (!$mysqli->query("DELETE FROM exa_ppto_proyecto_detalles
        WHERE pdp_id=$pdp_id AND proy_id='$proy_esc' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1")) {
        return array('ok' => false, 'message' => $mysqli->error);
    }

    if ($ppa_id_rubro > 0) {
        $otros = $mysqli->query("SELECT COUNT(*) AS cnt FROM exa_ppto_proyecto_detalles
            WHERE ppa_id=$ppa_id_rubro AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id LIMIT 1");
        $sin_otros = ($otros && ($o = $otros->fetch_assoc()) && (int)$o['cnt'] === 0);
        if ($sin_otros) {
            $mysqli->query("DELETE FROM exa_ppto_detalles WHERE ppa_id=$ppa_id_rubro AND ppe_id=$ppe_id");
        }
    }

    return array('ok' => true, 'message' => 'Rubro eliminado correctamente.');
}

if ($action === 'list') {
    $rows = array();
    $res = $mysqli->query("SELECT p.*, pl.plt_nombre FROM exa_ppto_proyectos p
        LEFT JOIN exa_ppto_plantillas pl ON p.plt_id = pl.plt_id
        WHERE p.Emp_Cod = $Emp_Cod ORDER BY p.proy_nombre");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'save') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $proy_nombre = $mysqli->real_escape_string(trim($_POST['proy_nombre']));
    $proy_estado = $mysqli->real_escape_string(isset($_POST['proy_estado']) ? $_POST['proy_estado'] : 'A');
    $plt_id = isset($_POST['plt_id']) && $_POST['plt_id'] !== '' ? (int)$_POST['plt_id'] : 'NULL';
    $is_edit = !empty($_POST['is_edit']);

    if ($proy_id === '' || $proy_nombre === '') {
        ppto_json(array('status' => 'error', 'message' => 'Codigo y nombre son obligatorios.'));
    }

    if ($is_edit) {
        $sql = "UPDATE exa_ppto_proyectos SET proy_nombre='$proy_nombre', proy_estado='$proy_estado', plt_id=$plt_id
                WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod";
    } else {
        $sql = "INSERT INTO exa_ppto_proyectos (proy_id, Emp_Cod, proy_nombre, proy_estado, proy_fecha_registro, Usu_Cod, plt_id)
                VALUES ('$proy_id', $Emp_Cod, '$proy_nombre', '$proy_estado', CURDATE(), " . (int)$Ses_Usu_Cod . ", $plt_id)
                ON DUPLICATE KEY UPDATE proy_nombre='$proy_nombre', proy_estado='$proy_estado', plt_id=$plt_id";
    }
    $ok = $mysqli->query($sql);
    if ($ok && !$is_edit && $plt_id !== 'NULL') {
        ppto_proy_clonar_plantilla($mysqli, trim($_POST['proy_id']), (int)$_POST['plt_id'], $Emp_Cod, (int)$Ses_Usu_Cod);
    }
    ppto_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Proyecto guardado.' : $mysqli->error));
}

if ($action === 'save_rubro') {
    $proy_id = $mysqli->real_escape_string(trim($_POST['proy_id']));
    $ppe_id = (int)$_POST['ppe_id'];
    $ppa_id = (int)$_POST['ppa_id'];
    if (!ppto_partida_es_destino_regla($mysqli, $ppa_id, $Emp_Cod)) {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Detalle activas pueden tener rubros de proyecto.'));
    }
    $rubro = $mysqli->real_escape_string(trim(isset($_POST['pdp_rubro']) ? $_POST['pdp_rubro'] : ''));
    if ($rubro === '' && $ppa_id > 0) {
        $r_desc = $mysqli->query("SELECT ppa_descripcion FROM exa_ppto_partidas WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod LIMIT 1");
        if ($r_desc && ($row_desc = $r_desc->fetch_assoc())) {
            $rubro = $mysqli->real_escape_string(trim($row_desc['ppa_descripcion']));
        }
    }
    if ($rubro === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione una partida detalle valida.'));
    }
    $pdp_edit = isset($_POST['pdp_id']) ? (int)$_POST['pdp_id'] : 0;
    if ($pdp_edit <= 0) {
        $pdp_edit = ppto_proy_rubro_id_por_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $Emp_Cod);
    }
    $r_cod = $mysqli->query("SELECT ppa_codigo_clasificacion FROM exa_ppto_partidas WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod LIMIT 1");
    $ppa_codigo = ($r_cod && ($rc = $r_cod->fetch_assoc())) ? $rc['ppa_codigo_clasificacion'] : '';
    $factor = (float)$_POST['pdp_factor_anual_tonelada'];
    $ton_override = isset($_POST['pdp_toneladas_base']) ? (float)$_POST['pdp_toneladas_base'] : 0;
    $tn_dia_post = isset($_POST['pdp_tn_dia']) ? (float)$_POST['pdp_tn_dia'] : 0;
    $presup_fijo = isset($_POST['pdp_presupuesto_anual']) ? (float)$_POST['pdp_presupuesto_anual'] : 0;

    if ($presup_fijo > 0.0001) {
        $ton = ppto_normalizar_ton_mes_rubro(($ton_override > 0) ? $ton_override : 0, $tn_dia_post);
        if ($ton <= 0) {
            ppto_json(array('status' => 'error', 'message' => 'No se pudo determinar toneladas base del rubro (3500 x 22).'));
        }
        $anual = round($presup_fijo, 2);
    } elseif ($factor > 0.0001) {
        $ton = ppto_normalizar_ton_mes_rubro(($ton_override > 0) ? $ton_override : 0, $tn_dia_post);
        if ($ton <= 0) {
            ppto_json(array('status' => 'error', 'message' => 'No se pudo determinar toneladas base del rubro (3500 x 22).'));
        }
        $anual = round($ton * $factor, 2);
    } else {
        $ton = 0;
        $anual = 0;
        if ($pdp_edit > 0) {
            $r0 = $mysqli->query('SELECT pdp_presupuesto_anual FROM exa_ppto_proyecto_detalles WHERE pdp_id=' . $pdp_edit . ' LIMIT 1');
            if ($r0 && ($x0 = $r0->fetch_assoc())) {
                $anual = round((float)$x0['pdp_presupuesto_anual'], 2);
            }
        }
    }

    if ($anual > 0.0001 && $ton > 0.0001) {
        $factor = round($anual / $ton, 6);
    }

    if ($ppa_codigo !== '') {
        $val_tope = ppto_proy_validar_tope_grupo_rubro($mysqli, trim($_POST['proy_id']), $Emp_Cod, $ppe_id, $ppa_codigo, $anual, $pdp_edit);
        if (!$val_tope['ok']) {
            ppto_json(array('status' => 'error', 'message' => $val_tope['message'], 'tope_grupo' => $val_tope));
        }
    }

    if ($pdp_edit > 0) {
        $sql = "UPDATE exa_ppto_proyecto_detalles
                SET pdp_rubro='$rubro', pdp_toneladas_base=$ton, pdp_factor_anual_tonelada=$factor, pdp_presupuesto_anual=$anual
                WHERE pdp_id=$pdp_edit AND proy_id='$proy_id' AND Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id";
    } else {
        $sql = "INSERT INTO exa_ppto_proyecto_detalles
                (ppe_id, ppa_id, proy_id, Emp_Cod, pdp_rubro, pdp_toneladas_base, pdp_factor_anual_tonelada, pdp_presupuesto_anual, pdp_fecha_registro, Usu_Cod)
                VALUES ($ppe_id, $ppa_id, '$proy_id', $Emp_Cod, '$rubro', $ton, $factor, $anual, NOW(), " . (int)$Ses_Usu_Cod . ")";
    }
    $ok = $mysqli->query($sql);
    $resp = array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Rubro guardado.' : $mysqli->error);
    if ($ok) {
        $pdp_id = $pdp_edit > 0 ? $pdp_edit : (int)$mysqli->insert_id;
        if ($pdp_id <= 0) {
            $pdp_id = ppto_proy_rubro_id_por_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $Emp_Cod);
        }
        if ($pdp_id > 0) {
            $dup = ppto_proy_rubro_purgar_duplicados_partida($mysqli, $ppe_id, $ppa_id, $proy_id, $Emp_Cod, $pdp_id);
            if ($dup > 0) {
                $resp['message'] = 'Rubro guardado. Se eliminaron ' . $dup . ' duplicado(s) del mismo codigo.';
            }
            ppto_proy_distribuir_meses($mysqli, $pdp_id, $anual);
        }
        require_once(__DIR__ . '/ppto_divergencia_logica.php');
        $anio_row = $mysqli->query("SELECT ppe_anio FROM exa_ppto_cabeceras WHERE ppe_id=$ppe_id LIMIT 1");
        $anio = ($anio_row && ($ar = $anio_row->fetch_assoc())) ? (int)$ar['ppe_anio'] : (int)date('Y');
        $d2 = ppto_divergencia_comparar_toneladas($mysqli, $proy_id, $Emp_Cod, $anio, $ppe_id);
        $resp['divergencia_d2'] = $d2;
        if (!empty($d2['warning']) && $d2['mensaje'] !== '') {
            $resp['warning'] = $d2['mensaje'];
        }
    }
    ppto_json($resp);
}

if ($action === 'delete_rubro') {
    $pdp_id = isset($_POST['pdp_id']) ? (int)$_POST['pdp_id'] : 0;
    $proy_id = isset($_POST['proy_id']) ? trim($_POST['proy_id']) : '';
    $ppe_id = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : 0;
    if ($pdp_id <= 0 || $proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Rubro, proyecto y version son requeridos.'));
    }
    $res = ppto_proy_rubro_eliminar($mysqli, $pdp_id, $proy_id, $Emp_Cod, $ppe_id);
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    ppto_json(array('status' => 'success', 'message' => $res['message']));
}

if ($action === 'aplicar_escenario') {
    require_once(__DIR__ . '/ppto_forecast_logica.php');
    $proy_id_raw = isset($_POST['proy_id']) ? trim($_POST['proy_id']) : '';
    $proy_id = $mysqli->real_escape_string($proy_id_raw);
    $ppe_id = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : 0;
    $escenario = isset($_POST['escenario']) ? trim($_POST['escenario']) : '';
    if (!in_array($escenario, array('esperada', 'proyectada', 'real'), true)) {
        ppto_json(array('status' => 'error', 'message' => 'Escenario invalido.'));
    }
    if ($proy_id_raw === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione proyecto y version.'));
    }
    $anio_esc = (int)date('Y');
    $r_anio = $mysqli->query("SELECT ppe_anio FROM exa_ppto_cabeceras WHERE ppe_id=$ppe_id LIMIT 1");
    if ($r_anio && ($ra = $r_anio->fetch_assoc())) {
        $anio_esc = (int)$ra['ppe_anio'];
    }
    $meses_prod_esc = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio_esc, $proy_id_raw);
    $ton_base_pdf = ppto_proy_version_ton_base($mysqli, $proy_id_raw, $Emp_Cod, $ppe_id);
    $ton_esc_gasto_mes = ppto_proy_ton_escenario_gasto_mes($ton_base_pdf);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, $proy_id_raw, $Emp_Cod, $ppe_id);

    $res = $mysqli->query("SELECT pdp_id, pdp_factor_anual_tonelada, pdp_presupuesto_anual
        FROM exa_ppto_proyecto_detalles
        WHERE proy_id='$proy_id' AND Emp_Cod=$Emp_Cod" . ($ppe_id > 0 ? " AND ppe_id=$ppe_id" : ""));
    $actualizados = 0;
    $total_nuevo = 0.0;
    $pendientes = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $factor = (float)$row['pdp_factor_anual_tonelada'];
            $anual = round((float)$row['pdp_presupuesto_anual'], 2);
            if ($factor > 0.0001) {
                $esc_esperada_val = round($ton_costo_mes * $factor, 2);
                $factor_esc = ppto_proy_factor_escenario_gasto($esc_esperada_val, $ton_esc_gasto_mes);
                $nuevo = ppto_forecast_pf_rubro_anual_escenario($meses_prod_esc, $factor_esc, $escenario);
            } else {
                $nuevo = $anual;
            }
            $pendientes[] = array('pdp_id' => (int)$row['pdp_id'], 'anual' => $nuevo);
            $total_nuevo += $nuevo;
        }
    }
    foreach ($pendientes as $p) {
        $pdp_id = (int)$p['pdp_id'];
        $anual = round((float)$p['anual'], 2);
        if ($mysqli->query("UPDATE exa_ppto_proyecto_detalles SET pdp_presupuesto_anual=$anual WHERE pdp_id=$pdp_id")) {
            $actualizados++;
        }
    }
    $etq = array('esperada' => 'Base PDF (esperada)', 'proyectada' => 'Proyectada', 'real' => 'Real (+proyectado)');
    ppto_json(array(
        'status' => 'success',
        'message' => 'Presupuesto recalculado a escenario "' . $etq[$escenario] . '": ' . $actualizados . ' rubros, total ' . number_format($total_nuevo, 2, '.', ',') . '.',
        'rubros_actualizados' => $actualizados,
        'total_nuevo' => round($total_nuevo, 2),
        'escenario' => $escenario,
    ));
}

if ($action === 'list_rubros') {
    require_once __DIR__ . '/ppto_proyectos_cuadro_logica.php';
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    $proy_id = isset($_GET['proy_id']) ? trim($_GET['proy_id']) : '';
    $ppe_id = isset($_GET['ppe_id']) ? (int)$_GET['ppe_id'] : 0;
    $cuadro_vista = isset($_GET['cuadro_vista']) ? $_GET['cuadro_vista'] : 'anual';
    $cuadro_mes = isset($_GET['cuadro_mes']) ? $_GET['cuadro_mes'] : null;
    $anio_precio = isset($_GET['anio_precio']) && $_GET['anio_precio'] !== ''
        ? (int)$_GET['anio_precio']
        : null;
    if ($proy_id === '') {
        ppto_json(array('status' => 'error', 'message' => 'Proyecto requerido.', 'rows' => array()));
    }
    $data = ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $proy_id, $ppe_id, $cuadro_vista, $cuadro_mes, $anio_precio);
    $esc_sim = isset($_GET['escenario']) ? trim($_GET['escenario']) : 'esperada';
    $anio_sim = isset($data['anio_proyeccion']) ? (int)$data['anio_proyeccion'] : $anio_precio;
    $ajuste = ppto_ajuste_simular($mysqli, $proy_id, $Emp_Cod, $ppe_id, $data, array(
        'escenario' => $esc_sim,
        'anio' => $anio_sim,
    ));
    $data['ajuste_financiero'] = $ajuste;
    $data['ajuste_cfg'] = ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    ppto_json($data);
}

if ($action === 'save_grupo_meses') {
    ppto_schema_ensure_partida_meses_prorrateo($mysqli);
    $ppa_id = isset($_POST['ppa_id']) ? (int)$_POST['ppa_id'] : 0;
    $raw_meses = isset($_POST['ppa_meses_prorrateo']) ? trim($_POST['ppa_meses_prorrateo']) : '';
    if ($ppa_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Partida grupo requerida.'));
    }
    $chk = $mysqli->query("SELECT ppa_id, ppa_codigo_clasificacion, COALESCE(NULLIF(ppa_clase,''),'D') AS ppa_clase
        FROM exa_ppto_partidas WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod LIMIT 1");
    if (!$chk || !($part = $chk->fetch_assoc()) || $part['ppa_clase'] !== 'G') {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Grupo admiten meses de prorrateo.'));
    }
    if ($raw_meses === '') {
        $ok = $mysqli->query("UPDATE exa_ppto_partidas SET ppa_meses_prorrateo = NULL WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod");
        ppto_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Meses restablecidos a 12 (defecto).' : $mysqli->error));
    }
    $meses = (int)round((float)str_replace(',', '.', $raw_meses));
    if ($meses < 1 || $meses > 999) {
        ppto_json(array('status' => 'error', 'message' => 'Los meses deben estar entre 1 y 999.'));
    }
    $ok = $mysqli->query("UPDATE exa_ppto_partidas SET ppa_meses_prorrateo = $meses WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod");
    ppto_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Meses guardados para ' . $part['ppa_codigo_clasificacion'] . ' (' . $meses . ' meses).' : $mysqli->error,
        'ppa_meses_prorrateo' => $meses,
    ));
}

if ($action === 'save_grupo_pct') {
    ppto_schema_ensure_partida_porcentaje($mysqli);
    $ppa_id = isset($_POST['ppa_id']) ? (int)$_POST['ppa_id'] : 0;
    $raw_pct = isset($_POST['ppa_porcentaje_tope']) ? trim($_POST['ppa_porcentaje_tope']) : '';
    if ($ppa_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Partida grupo requerida.'));
    }
    $chk = $mysqli->query("SELECT ppa_id, ppa_codigo_clasificacion, COALESCE(NULLIF(ppa_clase,''),'D') AS ppa_clase
        FROM exa_ppto_partidas WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod LIMIT 1");
    if (!$chk || !($part = $chk->fetch_assoc()) || $part['ppa_clase'] !== 'G') {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Grupo admiten porcentaje tope.'));
    }
    if ($raw_pct === '') {
        $ok = $mysqli->query("UPDATE exa_ppto_partidas SET ppa_porcentaje_tope = NULL WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod");
        ppto_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Porcentaje eliminado.' : $mysqli->error));
    }
    $pct = round((float)str_replace(',', '.', $raw_pct), 4);
    if ($pct < 0 || $pct > 100) {
        ppto_json(array('status' => 'error', 'message' => 'El porcentaje debe estar entre 0 y 100.'));
    }
    $pct_sql = number_format($pct, 4, '.', '');
    $ok = $mysqli->query("UPDATE exa_ppto_partidas SET ppa_porcentaje_tope = $pct_sql WHERE ppa_id=$ppa_id AND Emp_Cod=$Emp_Cod");
    ppto_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Porcentaje guardado para ' . $part['ppa_codigo_clasificacion'] . '.' : $mysqli->error,
        'ppa_porcentaje_tope' => $pct,
    ));
}

if ($action === 'catalogos') {
    $plantillas = array();
    $partidas = array();
    $versiones = array();
    $r1 = $mysqli->query("SELECT plt_id, plt_nombre FROM exa_ppto_plantillas WHERE Emp_Cod=$Emp_Cod AND plt_estado='A'");
    if ($r1) {
        while ($x = $r1->fetch_assoc()) {
            $plantillas[] = $x;
        }
    }
    $r2_list = ppto_partidas_listar($mysqli, array('Emp_Cod' => $Emp_Cod, 'solo_activas' => true, 'clase' => 'D'));
    foreach ($r2_list as $x) {
        $partidas[] = array(
            'ppa_id' => $x['ppa_id'],
            'ppa_codigo_clasificacion' => $x['ppa_codigo_clasificacion'],
            'ppa_descripcion' => $x['ppa_descripcion'],
            'ppa_nivel' => $x['ppa_nivel'],
            'ppa_clase' => isset($x['ppa_clase']) ? $x['ppa_clase'] : 'D'
        );
    }
    $partidas_grupo = array();
    $r2g = ppto_partidas_listar($mysqli, array('Emp_Cod' => $Emp_Cod, 'solo_activas' => true, 'clase' => 'G'));
    foreach ($r2g as $x) {
        $partidas_grupo[] = array(
            'ppa_id' => $x['ppa_id'],
            'ppa_codigo_clasificacion' => $x['ppa_codigo_clasificacion'],
            'ppa_descripcion' => $x['ppa_descripcion'],
            'ppa_nivel' => $x['ppa_nivel'],
            'ppa_clase' => 'G',
            'ppa_porcentaje_tope' => isset($x['ppa_porcentaje_tope']) ? $x['ppa_porcentaje_tope'] : null,
            'ppa_meses_prorrateo' => isset($x['ppa_meses_prorrateo']) ? $x['ppa_meses_prorrateo'] : null,
        );
    }
    $r3 = $mysqli->query("SELECT ppe_id, ppe_anio, ppe_version, ppe_descripcion FROM exa_ppto_cabeceras WHERE Emp_Cod=$Emp_Cod ORDER BY ppe_anio DESC, ppe_version DESC");
    if ($r3) {
        while ($x = $r3->fetch_assoc()) {
            $versiones[] = $x;
        }
    }
    ppto_json(array('status' => 'success', 'plantillas' => $plantillas, 'partidas' => $partidas, 'partidas_grupo' => $partidas_grupo, 'versiones' => $versiones));
}

if ($action === 'sugerir_codigo_partida') {
    ppto_schema_ensure_partida_clase($mysqli);
    $padre_id = isset($_GET['padre_id']) && $_GET['padre_id'] !== '' ? (int)$_GET['padre_id'] : null;
    if ($padre_id !== null && $padre_id <= 0) {
        $padre_id = null;
    }
    $cod = ppto_partida_sugerir_codigo($mysqli, $Emp_Cod, $padre_id);
    ppto_json(array('status' => 'success', 'codigo' => $cod));
}

if ($action === 'save_partida_catalogo') {
    $padre_id = isset($_POST['ppa_padre_id']) ? (int)$_POST['ppa_padre_id'] : 0;
    $res = ppto_partida_guardar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, array(
        'ppa_codigo_clasificacion' => isset($_POST['ppa_codigo_clasificacion']) ? $_POST['ppa_codigo_clasificacion'] : '',
        'ppa_descripcion' => isset($_POST['ppa_descripcion']) ? $_POST['ppa_descripcion'] : '',
        'ppa_tipo' => isset($_POST['ppa_tipo']) ? $_POST['ppa_tipo'] : 'G',
        'ppa_naturaleza' => isset($_POST['ppa_naturaleza']) ? $_POST['ppa_naturaleza'] : 'OPE',
        'ppa_clase' => isset($_POST['ppa_clase']) ? $_POST['ppa_clase'] : 'D',
        'ppa_padre_id' => $padre_id,
    ));
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    ppto_json(array(
        'status' => 'success',
        'message' => $res['message'],
        'partida' => array(
            'ppa_id' => $res['ppa_id'],
            'ppa_codigo_clasificacion' => $res['ppa_codigo_clasificacion'],
            'ppa_descripcion' => $res['ppa_descripcion'],
            'ppa_nivel' => $res['ppa_nivel'],
            'ppa_clase' => $res['ppa_clase'],
        ),
    ));
}

if ($action === 'get_version_config') {
    $proy_id = isset($_GET['proy_id']) ? trim($_GET['proy_id']) : '';
    $ppe_id = isset($_GET['ppe_id']) ? (int)$_GET['ppe_id'] : 0;
    if ($proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Proyecto y version requeridos.'));
    }
    $ton = ppto_version_ton_base_sanitize(ppto_proy_version_ton_base($mysqli, $proy_id, $Emp_Cod, $ppe_id));
    $ton_costo = ppto_proy_version_ton_costo($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $cfg = ppto_proy_version_config($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    ppto_json(array(
        'status' => 'success',
        'pv_toneladas_base_mes' => round($ton, 4),
        'pv_toneladas_costo_mes' => round($ton_costo, 4),
        'pv_tarifa_ton_iva' => round($cfg['tarifa_ton_iva'], 4),
        'pv_iva_divisor' => round($cfg['iva_divisor'], 4),
        'ton_anual' => round($cfg['ton_anual'], 4),
    ));
}

if ($action === 'save_version_ton') {
    $proy_id = isset($_POST['proy_id']) ? trim($_POST['proy_id']) : '';
    $ppe_id = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : 0;
    $ton_raw = isset($_POST['pv_toneladas_base_mes']) ? (float)$_POST['pv_toneladas_base_mes'] : 0;
    $ton = ppto_version_ton_base_sanitize($ton_raw);
    $ton_costo = ppto_version_ton_costo_sanitize(isset($_POST['pv_toneladas_costo_mes']) ? (float)$_POST['pv_toneladas_costo_mes'] : 0);
    $tarifa = isset($_POST['pv_tarifa_ton_iva']) ? (float)$_POST['pv_tarifa_ton_iva'] : 3.0;
    $iva_div = isset($_POST['pv_iva_divisor']) ? (float)$_POST['pv_iva_divisor'] : 1.15;
    $aplicar = !empty($_POST['aplicar_rubros']);
    if ($proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Proyecto y version requeridos.'));
    }
    if ($ton <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Ingrese ton ingresos (mes) mayores a cero.'));
    }
    if ($aplicar && $ton_costo <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Ingrese ton costo egreso (mes) para aplicar a rubros.'));
    }
    $res = ppto_proy_version_guardar_ton($mysqli, $proy_id, $Emp_Cod, $ppe_id, $ton, (int)$Ses_Usu_Cod, $aplicar, $tarifa, $iva_div, $ton_costo);
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    $sync = ppto_proy_version_sync_prod_esperada($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $msg = $res['message'];
    if (ppto_version_ton_base_fue_corregida($ton_raw, $ton)) {
        $msg .= ' Ton ingresos corregida: ' . number_format($ton_raw, 0, '.', ',')
            . ' es ton costo egreso; se guardo ' . number_format($ton, 0, '.', ',') . '.';
    }
    if ($aplicar && (int)$res['rubros_actualizados'] > 0) {
        $msg .= ' Ton costo aplicada en ' . (int)$res['rubros_actualizados'] . ' rubros ($/Ton sin cambio; Base PDF recalculado).';
    }
    if ($sync && !empty($sync['ok'])) {
        $msg .= ' ' . $sync['message'];
    }
    ppto_json(array('status' => 'success', 'message' => $msg, 'pv_toneladas_base_mes' => $ton,
        'pv_toneladas_costo_mes' => $ton_costo,
        'pv_tarifa_ton_iva' => $tarifa, 'pv_iva_divisor' => $iva_div, 'sync_prod' => $sync));
}

if ($action === 'parse_pdf') {
    @set_time_limit(120);
    @ini_set('memory_limit', '256M');
    require_once(__DIR__ . '/ppto_pdf_logica.php');
    $chk = ppto_proy_presupuesto_validar_upload();
    if (!$chk['ok']) {
        ppto_json(array('status' => 'error', 'message' => $chk['message']));
    }
    $ext = isset($chk['ext']) ? $chk['ext'] : 'pdf';
    $origName = isset($chk['name']) ? $chk['name'] : '';
    $mime = isset($chk['mime']) ? $chk['mime'] : '';
    $text = ppto_presupuesto_extraer_texto($chk['path'], $ext, $origName, $mime);
    if (strlen(trim($text)) < 20) {
        $msg = 'No se pudo leer el contenido del archivo.';
        if ($ext === 'pdf' && ppto_pdf_es_solo_grafico($chk['path'])) {
            $msg = 'Este PDF es una tabla dibujada como grafico (sin texto seleccionable). Es el formato tipico al publicar desde Excel. Suba el Excel original (.xlsx o .xls), un CSV exportado desde Excel, o genere un PDF con texto (Archivo > Guardar como PDF en Excel, no imprimir a PDF).';
        } elseif ($ext === 'pdf') {
            $msg = 'No se pudo leer texto del PDF. Use un PDF con texto seleccionable (exportado desde Excel, no escaneado).';
        } else {
            $msg = 'No se detectaron datos en el archivo Excel/CSV. Verifique que tenga el formato presupuestario RCET (grupo, secciones A/B/C y montos anuales).';
            if (function_exists('ppto_spreadsheet_last_error')) {
                $detail = ppto_spreadsheet_last_error();
                if ($detail !== '') {
                    $msg .= ' ' . $detail;
                }
            }
        }
        ppto_json(array('status' => 'error', 'message' => $msg));
    }
    $parsed = ppto_pdf_parsear_presupuesto($text);
    $slim = ppto_pdf_payload_slim($parsed);
    $ton_detectada = isset($parsed['ton_base']) ? (float)$parsed['ton_base'] : 0;
    if ($ton_detectada >= 70000 && $ton_detectada < 95000) {
        $slim['warnings'][] = 'El archivo usa ' . number_format($ton_detectada, 0, '.', ',')
            . ' ton/mes como base de costo (3500 x 22). Ton ingresos del proyecto se mantiene en '
            . number_format($slim['ton_ingreso_mes'], 0, '.', ',') . '.';
    }
    $validacion = ppto_pdf_validar_contra_catalogo($mysqli, $Emp_Cod, $parsed);
    $catalogo = $validacion['catalogo'];
    $proy_parse = isset($_POST['proy_id']) ? trim($_POST['proy_id']) : '';
    $ppe_parse = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : 0;
    $rubros_proyecto = array();
    if ($proy_parse !== '' && $ppe_parse > 0) {
        $rubros_proyecto = ppto_proy_rubros_map_por_codigo($mysqli, $proy_parse, $Emp_Cod, $ppe_parse);
        foreach ($rubros_proyecto as $cod => $info) {
            if (!isset($catalogo[$cod])) {
                $catalogo[$cod] = array(
                    'existe' => true,
                    'estado' => 'existente',
                );
            }
            if (!isset($catalogo[$cod]['estado']) || $catalogo[$cod]['estado'] !== 'conflicto') {
                $catalogo[$cod]['rubro_proyecto'] = true;
                $catalogo[$cod]['rubro_nombre_actual'] = $info['pdp_rubro'];
            }
        }
    }
    $ton_ingreso_resp = (float)$slim['ton_ingreso_mes'];
    if ($proy_parse !== '' && $ppe_parse > 0) {
        $ton_proy_cfg = ppto_proy_version_ton_base($mysqli, $proy_parse, $Emp_Cod, $ppe_parse);
        if ($ton_proy_cfg > 0) {
            $ton_ingreso_resp = ppto_version_ton_base_sanitize($ton_proy_cfg);
        }
    }
    ppto_json(array(
        'status' => 'success',
        'archivo' => $chk['name'],
        'ton_base' => $ton_ingreso_resp,
        'ton_costo_mes' => (float)$slim['ton_costo_mes'],
        'ton_ingreso_mes' => $ton_ingreso_resp,
        'ton_detectada' => (float)$slim['ton_detectada'],
        'meses_prorrateo_global' => isset($slim['meses_prorrateo_global']) ? (int)$slim['meses_prorrateo_global'] : 0,
        'partidas' => $slim['partidas'],
        'rubros' => $slim['rubros'],
        'warnings' => $slim['warnings'],
        'conflictos' => $validacion['conflictos'],
        'catalogo' => $catalogo,
        'rubros_proyecto' => $rubros_proyecto,
        'import_bloqueado' => !empty($validacion['conflictos']),
        'lineas_muestra' => isset($parsed['lineas']) ? array_slice($parsed['lineas'], 0, 12) : array(),
        'payload' => $slim,
    ));
}

if ($action === 'import_pdf') {
    require_once(__DIR__ . '/ppto_pdf_logica.php');
    $proy_id = isset($_POST['proy_id']) ? trim($_POST['proy_id']) : '';
    $ppe_id = isset($_POST['ppe_id']) ? (int)$_POST['ppe_id'] : 0;
    $ton_override = ppto_version_ton_base_sanitize(isset($_POST['pv_toneladas_base_mes']) ? (float)$_POST['pv_toneladas_base_mes'] : 0);

    if ($proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione proyecto y version antes de importar.'));
    }

    $parsed = null;
    if (isset($_POST['payload_json']) && $_POST['payload_json'] !== '') {
        $parsed = json_decode($_POST['payload_json'], true);
    }
    if (!is_array($parsed)) {
        $chk = ppto_proy_presupuesto_validar_upload();
        if (!$chk['ok']) {
            ppto_json(array('status' => 'error', 'message' => $chk['message']));
        }
        $ext = isset($chk['ext']) ? $chk['ext'] : 'pdf';
        $origName = isset($chk['name']) ? $chk['name'] : '';
        $mime = isset($chk['mime']) ? $chk['mime'] : '';
        $text = ppto_presupuesto_extraer_texto($chk['path'], $ext, $origName, $mime);
        $parsed = ppto_pdf_parsear_presupuesto($text);
    }

    $res = ppto_pdf_importar_presupuesto($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, $proy_id, $ppe_id, $parsed, $ton_override);
    if (!$res['ok']) {
        ppto_json(array(
            'status' => 'error',
            'message' => $res['message'],
            'conflictos' => isset($res['conflictos']) ? $res['conflictos'] : array(),
        ));
    }
    $sync = ppto_proy_version_sync_prod_esperada($mysqli, $proy_id, $Emp_Cod, $ppe_id);
    $msg = $res['message'];
    if ($sync && !empty($sync['ok'])) {
        $msg .= ' ' . $sync['message'];
    }
    ppto_json(array(
        'status' => 'success',
        'message' => $msg,
        'stats' => $res['stats'],
        'pv_toneladas_base_mes' => $res['stats']['ton_base'],
        'sync_prod' => $sync,
    ));
}

if ($action === 'preview_publicar' || $action === 'publish_aprobado' || $action === 'ultima_publicacion') {
    require_once(__DIR__ . '/ppto_proy_publicar_logica.php');
    $proy_id = isset($_REQUEST['proy_id']) ? trim($_REQUEST['proy_id']) : '';
    $ppe_id = isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : 0;
    if ($proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione proyecto y version.'));
    }
    $anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : 0;
    if ($anio <= 0) {
        $r_anio = $mysqli->query("SELECT ppe_anio FROM exa_ppto_cabeceras WHERE ppe_id=$ppe_id LIMIT 1");
        $anio = ($r_anio && ($ra = $r_anio->fetch_assoc())) ? (int)$ra['ppe_anio'] : (int)date('Y');
    }
    if ($action === 'ultima_publicacion') {
        $ult = ppto_proy_publicar_ultima($mysqli, $proy_id, $Emp_Cod, $ppe_id);
        ppto_json(array('status' => 'success', 'ultima' => $ult));
    }
    if ($action === 'preview_publicar') {
        $prev = ppto_proy_publicar_preview($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio);
        if (empty($prev['ok'])) {
            ppto_json(array('status' => 'error', 'message' => $prev['message']));
        }
        ppto_json(array('status' => 'success', 'preview' => $prev));
    }
    $forzar = !empty($_POST['confirmar_republicacion']) && $_POST['confirmar_republicacion'] === '1';
    $res = ppto_proy_publicar_ejecutar($mysqli, $proy_id, $Emp_Cod, $ppe_id, $anio, (int)$Ses_Usu_Cod, $forzar, true);
    if (!empty($res['needs_confirm'])) {
        ppto_json(array(
            'status' => 'confirm',
            'message' => $res['message'],
            'preview' => $res['preview'],
        ));
    }
    if (empty($res['ok'])) {
        ppto_json(array(
            'status' => 'error',
            'message' => $res['message'],
            'bloqueos' => isset($res['bloqueos']) ? $res['bloqueos'] : array(),
        ));
    }
    ppto_json(array(
        'status' => 'success',
        'message' => $res['message'],
        'total_anterior' => $res['total_anterior'],
        'total_nuevo' => $res['total_nuevo'],
        'delta' => $res['delta'],
        'rubros_actualizados' => $res['rubros_actualizados'],
        'pub_id' => $res['pub_id'],
    ));
}

if ($action === 'ajuste_cfg_get' || $action === 'ajuste_cfg_save'
    || $action === 'ajuste_precios_list' || $action === 'ajuste_precios_save'
    || $action === 'ajuste_simular' || $action === 'ajuste_aplicar'
    || $action === 'ajuste_historial' || $action === 'ajuste_historial_detalle') {
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    require_once __DIR__ . '/ppto_proyectos_cuadro_logica.php';
    $proy_id = isset($_REQUEST['proy_id']) ? trim($_REQUEST['proy_id']) : '';
    $ppe_id = isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : 0;
    if ($proy_id === '' || $ppe_id <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione proyecto y version.'));
    }

    if ($action === 'ajuste_cfg_get') {
        ppto_json(array(
            'status' => 'success',
            'cfg' => ppto_ajuste_cfg_get($mysqli, $proy_id, $Emp_Cod, $ppe_id),
            'precios' => ppto_ajuste_precios_list($mysqli, $proy_id, $Emp_Cod, $ppe_id),
        ));
    }

    if ($action === 'ajuste_cfg_save') {
        $data = array(
            'costo_capital_pct' => isset($_POST['costo_capital_pct']) ? $_POST['costo_capital_pct'] : 11,
            'gad_monto_objetivo' => isset($_POST['gad_monto_objetivo']) ? $_POST['gad_monto_objetivo'] : 2000000,
            'gad_factor_ton' => isset($_POST['gad_factor_ton']) ? $_POST['gad_factor_ton'] : 0.1984,
            'ajuste_activo' => !empty($_POST['ajuste_activo']) ? 1 : 0,
        );
        if (isset($_POST['gad_recuperado_acum']) && $_POST['gad_recuperado_acum'] !== '') {
            $data['gad_recuperado_acum'] = $_POST['gad_recuperado_acum'];
        }
        $res = ppto_ajuste_cfg_save($mysqli, $proy_id, $Emp_Cod, $ppe_id, $data, (int)$Ses_Usu_Cod);
        ppto_json(array(
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['message'],
            'cfg' => isset($res['cfg']) ? $res['cfg'] : null,
        ));
    }

    if ($action === 'ajuste_precios_list') {
        ppto_json(array(
            'status' => 'success',
            'precios' => ppto_ajuste_precios_list($mysqli, $proy_id, $Emp_Cod, $ppe_id),
        ));
    }

    if ($action === 'ajuste_precios_save') {
        $precios = array();
        if (isset($_POST['precios_json']) && $_POST['precios_json'] !== '') {
            $decoded = json_decode($_POST['precios_json'], true);
            if (is_array($decoded)) {
                $precios = $decoded;
            }
        }
        $res = ppto_ajuste_precios_save($mysqli, $proy_id, $Emp_Cod, $ppe_id, $precios, (int)$Ses_Usu_Cod);
        ppto_json(array(
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['message'],
            'precios' => isset($res['precios']) ? $res['precios'] : array(),
        ));
    }

    if ($action === 'ajuste_simular' || $action === 'ajuste_aplicar') {
        $cuadro_vista = isset($_REQUEST['cuadro_vista']) ? $_REQUEST['cuadro_vista'] : 'anual';
        $cuadro_mes = isset($_REQUEST['cuadro_mes']) ? $_REQUEST['cuadro_mes'] : null;
        $escenario = isset($_REQUEST['escenario']) ? trim($_REQUEST['escenario']) : 'esperada';
        $anio_precio = null;
        if (isset($_REQUEST['anio']) && $_REQUEST['anio'] !== '') {
            $anio_precio = (int)$_REQUEST['anio'];
        } elseif (isset($_REQUEST['anio_precio']) && $_REQUEST['anio_precio'] !== '') {
            $anio_precio = (int)$_REQUEST['anio_precio'];
        }
        $cuadro = ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $proy_id, $ppe_id, $cuadro_vista, $cuadro_mes, $anio_precio);
        $opts = array('escenario' => $escenario);
        if ($anio_precio) {
            $opts['anio'] = $anio_precio;
        } elseif (isset($cuadro['anio_proyeccion'])) {
            $opts['anio'] = (int)$cuadro['anio_proyeccion'];
        }
        if (isset($_REQUEST['costo_capital_pct']) && $_REQUEST['costo_capital_pct'] !== '') {
            $opts['costo_capital_pct'] = (float)$_REQUEST['costo_capital_pct'];
        }
        if (isset($_REQUEST['gad_factor_ton']) && $_REQUEST['gad_factor_ton'] !== '') {
            $opts['gad_factor_ton'] = (float)$_REQUEST['gad_factor_ton'];
        }
        if (isset($_REQUEST['gad_monto_objetivo']) && $_REQUEST['gad_monto_objetivo'] !== '') {
            $opts['gad_monto_objetivo'] = (float)$_REQUEST['gad_monto_objetivo'];
        }
        if (isset($_REQUEST['gad_recuperado_acum']) && $_REQUEST['gad_recuperado_acum'] !== '') {
            $opts['gad_recuperado_acum'] = (float)$_REQUEST['gad_recuperado_acum'];
        }
        $sim = ppto_ajuste_simular($mysqli, $proy_id, $Emp_Cod, $ppe_id, $cuadro, $opts);
        if ($action === 'ajuste_simular') {
            ppto_json(array('status' => 'success', 'sim' => $sim));
        }
        $obs = isset($_POST['observacion']) ? $_POST['observacion'] : '';
        $res = ppto_ajuste_aplicar($mysqli, $proy_id, $Emp_Cod, $ppe_id, $sim, (int)$Ses_Usu_Cod, $obs);
        ppto_json(array(
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['message'],
            'ajc_id' => isset($res['ajc_id']) ? $res['ajc_id'] : null,
            'cfg' => isset($res['cfg']) ? $res['cfg'] : null,
            'sim' => $sim,
        ));
    }

    if ($action === 'ajuste_historial') {
        ppto_json(array(
            'status' => 'success',
            'rows' => ppto_ajuste_historial($mysqli, $proy_id, $Emp_Cod, $ppe_id, 30),
        ));
    }

    if ($action === 'ajuste_historial_detalle') {
        $ajc_id = isset($_REQUEST['ajc_id']) ? (int)$_REQUEST['ajc_id'] : 0;
        $det = ppto_ajuste_historial_detalle($mysqli, $ajc_id, $Emp_Cod);
        if (!$det) {
            ppto_json(array('status' => 'error', 'message' => 'Ajuste no encontrado.'));
        }
        ppto_json(array('status' => 'success', 'cab' => $det['cab'], 'detalle' => $det['detalle']));
    }
}

ppto_json(array('status' => 'error', 'message' => 'Accion no soportada.'));
