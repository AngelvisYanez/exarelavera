<?php
/**
 * CRUD AJAX de proyectos presupuestarios (pre_proyectos + rubros). Lote B.
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
if (function_exists('ppto_db_set_utf8')) {
    ppto_db_set_utf8($mysqli);
}
ppto_schema_ensure($mysqli);
ppto_schema_ensure_partida_porcentaje($mysqli);
ppto_schema_ensure_partida_meses_prorrateo($mysqli);
ppto_schema_ensure_proyecto_version($mysqli);
ppto_schema_ensure_proyecto_publicacion($mysqli);
ppto_schema_ensure_indexes_perf($mysqli);

$Emp_Cod = ppto_resolve_emp_id();
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'list';

function ppto_json($data) {
    if (class_exists('DebugBar', false) && method_exists('DebugBar', 'setDebugBar')) {
        DebugBar::setDebugBar(null);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $json = ppto_json_encode_safe($data);
    // Un solo buffer limpio: el shutdown de DebugBar hace ob_end_flush sin mezclar BOM/avisos previos.
    @ob_start();
    echo $json;
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
 * El presupuesto Base PDF se recalcula como ton_costo x factor; Pdp_PreAnual en BD conserva el Excel (referencia).
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param float $ton_mes
 * @return array
 */
function ppto_proy_recalcular_rubros_preservar_anual($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $ton_mes, $ton_origen = 0) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $ton_mes = ppto_version_ton_costo_sanitize($ton_mes);
    if ($ton_mes <= 0) {
        return array('ok' => false, 'message' => 'Toneladas costo invalidas.', 'actualizados' => 0);
    }

    $actualizados = 0;
    $res = $mysqli->query("SELECT d.Pdp_Cod AS Pdp_Cod, d.Pdp_Cod, d.Pdp_FacAnualTon AS Pdp_FacAnualTon, d.Pdp_FacAnualTon
        FROM pre_proyecto_detalles d
        WHERE d.Pro_Cod='$esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
          AND d.Pdp_FacAnualTon > 0");
    while ($res && ($row = $res->fetch_assoc())) {
        $Pdp_Cod = (int)$row['Pdp_Cod'];
        $factor = (float)$row['Pdp_FacAnualTon'];
        if ($factor <= 0.0001) {
            continue;
        }
        $mysqli->query("UPDATE pre_proyecto_detalles
            SET Pdp_TonBase=$ton_mes
            WHERE Pdp_Cod=$Pdp_Cod");
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
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @param float $ton_mes
 * @param int $Usu_Cod
 * @param bool $aplicar_rubros
 * @return array
 */
function ppto_proy_version_guardar_ton($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $ton_mes, $Usu_Cod, $aplicar_rubros = false, $tarifa_iva = 3.0, $iva_div = 1.15, $ton_costo_mes = 0) {
    $esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
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

    $sql = "INSERT INTO pre_proyecto_version
            (Pro_Cod, Emp_Cod, Ppe_Cod, Ppv_TonBaseMes, Ppv_TonCostoMes, Ppv_TarifaTonIva, Ppv_IvaDivisor, Ppv_FecReg, Usu_Cod)
            VALUES ('$esc', $Emp_Cod, $Ppe_Cod, $ton_mes, $ton_costo_mes, $tarifa_iva, $iva_div, NOW(), $Usu_Cod)
            ON DUPLICATE KEY UPDATE Ppv_TonBaseMes=$ton_mes, Ppv_TonCostoMes=$ton_costo_mes, Ppv_TarifaTonIva=$tarifa_iva,
                Ppv_IvaDivisor=$iva_div, Ppv_FecReg=NOW(), Usu_Cod=$Usu_Cod";
    $ok = $mysqli->query($sql);
    if (!$ok) {
        return array('ok' => false, 'message' => $mysqli->error);
    }

    $actualizados = 0;
    if ($aplicar_rubros && $ton_costo_mes > 0) {
        $rec = ppto_proy_recalcular_rubros_preservar_anual($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $ton_costo_mes);
        $actualizados = !empty($rec['actualizados']) ? (int)$rec['actualizados'] : 0;
    }

    return array('ok' => true, 'message' => 'Toneladas base guardadas.', 'rubros_actualizados' => $actualizados, 'pv_toneladas_costo_mes' => $ton_costo_mes);
}

/**
 * Sincroniza plan de produccion esperada tras guardar ton base PDF.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array|null
 */
function ppto_proy_version_sync_prod_esperada($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $Ppe_Cod = (int)$Ppe_Cod;
    if ($Ppe_Cod <= 0) {
        return null;
    }
    $res = $mysqli->query("SELECT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Ppe_Cod=$Ppe_Cod LIMIT 1");
    if (!$res || !($row = $res->fetch_assoc())) {
        return null;
    }
    require_once __DIR__ . '/ppto_proyecto_version_logica.php';
    return ppto_prod_sync_esperada_desde_ton_base($mysqli, $Pro_Cod, $Emp_Cod, (int)$row['Ppe_Ani'], $Ppe_Cod, array(
        'preservar_cerrados' => true,
    ));
}

/**
 * Clona partidas de plantilla al crear un proyecto.
 */
function ppto_proy_clonar_plantilla($mysqli, $Pro_Cod, $Plt_Cod, $Emp_Cod, $Usu_Cod) {
    $Plt_Cod = (int)$Plt_Cod;
    if ($Plt_Cod <= 0) {
        return;
    }
    $anio = (int)date('Y');
    $res_ppe = $mysqli->query("SELECT Ppe_Cod AS Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio AND Ppe_Est='A' ORDER BY Ppe_Ver DESC LIMIT 1");
    if (!$res_ppe || !($v = $res_ppe->fetch_assoc())) {
        return;
    }
    $Ppe_Cod = (int)$v['Ppe_Cod'];
    $proy_esc = $mysqli->real_escape_string($Pro_Cod);
    $res = $mysqli->query("SELECT pp.Ppa_Cod AS Ppa_Cod, p.Ppa_Des
        FROM pre_plantilla_partidas pp
        INNER JOIN pre_partidas p ON pp.Ppa_Cod = p.Ppa_Cod
        WHERE pp.Plt_Cod = $Plt_Cod");
    if (!$res) {
        return;
    }
    while ($row = $res->fetch_assoc()) {
        $Ppa_Cod = (int)$row['Ppa_Cod'];
        $rubro = $mysqli->real_escape_string($row['Ppa_Des']);
        $mysqli->query("INSERT IGNORE INTO pre_proyecto_detalles
            (Ppe_Cod, Ppa_Cod, Pro_Cod, Emp_Cod, Pdp_Rubro, Pdp_TonBase, Pdp_FacAnualTon, Pdp_PreAnual, Pdp_FecReg, Usu_Cod)
            VALUES ($Ppe_Cod, $Ppa_Cod, '$proy_esc', $Emp_Cod, '$rubro', 0, 0, 0, NOW(), " . (int)$Usu_Cod . ")");
        $Pdp_Cod = (int)$mysqli->insert_id;
        if ($Pdp_Cod <= 0) {
            $r2 = $mysqli->query("SELECT Pdp_Cod AS Pdp_Cod FROM pre_proyecto_detalles WHERE Ppe_Cod=$Ppe_Cod AND Ppa_Cod=$Ppa_Cod AND Pro_Cod='$proy_esc' AND Pdp_Rubro='$rubro' LIMIT 1");
            if ($r2 && ($x = $r2->fetch_assoc())) {
                $Pdp_Cod = (int)$x['Pdp_Cod'];
            }
        }
        if ($Pdp_Cod > 0) {
            ppto_proy_distribuir_meses($mysqli, $Pdp_Cod, 0);
        }
    }
}

/**
 * Busca rubro de proyecto por partida (codigo via Ppa_Cod), no por nombre.
 *
 * @param mysqli $mysqli
 * @param int $Ppe_Cod
 * @param int $Ppa_Cod
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @return int
 */
function ppto_proy_rubro_id_por_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $Emp_Cod) {
    $Ppe_Cod = (int)$Ppe_Cod;
    $Ppa_Cod = (int)$Ppa_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));
    if ($Ppe_Cod <= 0 || $Ppa_Cod <= 0 || $proy_esc === '') {
        return 0;
    }
    $r = $mysqli->query("SELECT Pdp_Cod AS Pdp_Cod, Pdp_Cod FROM pre_proyecto_detalles
        WHERE Ppe_Cod=$Ppe_Cod AND Ppa_Cod=$Ppa_Cod AND Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod
        ORDER BY Pdp_Cod ASC LIMIT 1");
    if ($r && ($x = $r->fetch_assoc())) {
        return (int)$x['Pdp_Cod'];
    }
    return 0;
}

/**
 * Mapa codigo partida => rubro existente en proyecto/version.
 *
 * @param mysqli $mysqli
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_rubros_map_por_codigo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $map = array();
    if ($proy_esc === '' || $Ppe_Cod <= 0) {
        return $map;
    }
    $sql = "SELECT p.Ppa_Cla, d.Pdp_Cod AS Pdp_Cod, d.Pdp_Cod, d.Pdp_Rubro AS Pdp_Rubro, d.Pdp_Rubro
        FROM pre_proyecto_detalles d
        INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod AND p.Emp_Cod = d.Emp_Cod
        WHERE d.Pro_Cod='$proy_esc' AND d.Emp_Cod=$Emp_Cod AND d.Ppe_Cod=$Ppe_Cod
        ORDER BY d.Pdp_Cod ASC";
    $res = $mysqli->query($sql);
    while ($res && ($row = $res->fetch_assoc())) {
        $cod = $row['Ppa_Cla'];
        if (!isset($map[$cod])) {
            $map[$cod] = array(
                'Pdp_Cod' => (int)$row['Pdp_Cod'],
                'pdp_id' => (int)$row['Pdp_Cod'],
                'Pdp_Rubro' => $row['Pdp_Rubro'],
                'pdp_rubro' => $row['Pdp_Rubro'],
            );
        }
    }
    return $map;
}

/**
 * Elimina rubros duplicados de la misma partida dejando solo pdp_id_keep.
 *
 * @param mysqli $mysqli
 * @param int $Ppe_Cod
 * @param int $Ppa_Cod
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $pdp_id_keep
 * @return int
 */
function ppto_proy_rubro_purgar_duplicados_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $Emp_Cod, $pdp_id_keep) {
    $Ppe_Cod = (int)$Ppe_Cod;
    $Ppa_Cod = (int)$Ppa_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    $pdp_id_keep = (int)$pdp_id_keep;
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));
    if ($pdp_id_keep <= 0 || $Ppe_Cod <= 0 || $Ppa_Cod <= 0 || $proy_esc === '') {
        return 0;
    }
    $eliminados = 0;
    $res = $mysqli->query("SELECT Pdp_Cod AS Pdp_Cod, Pdp_Cod FROM pre_proyecto_detalles
        WHERE Ppe_Cod=$Ppe_Cod AND Ppa_Cod=$Ppa_Cod AND Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod AND Pdp_Cod<>$pdp_id_keep");
    if (!$res) {
        return 0;
    }
    while ($row = $res->fetch_assoc()) {
        $del = ppto_proy_rubro_eliminar($mysqli, (int)$row['Pdp_Cod'], $Pro_Cod, $Emp_Cod, $Ppe_Cod);
        if (!empty($del['ok'])) {
            $eliminados++;
        }
    }
    return $eliminados;
}

/**
 * Crea o actualiza 12 meses de distribucion para un rubro (1 query).
 */
function ppto_proy_distribuir_meses($mysqli, $Pdp_Cod, $anual) {
    $map = array((int)$Pdp_Cod => (float)$anual);
    ppto_proy_distribuir_meses_batch($mysqli, $map);
}

/**
 * Distribuye meses para varios rubros en pocas queries (chunks).
 *
 * @param mysqli $mysqli
 * @param array $pdp_anual map Pdp_Cod => presupuesto_anual
 */
function ppto_proy_distribuir_meses_batch($mysqli, $pdp_anual) {
    if (empty($pdp_anual) || !is_array($pdp_anual)) {
        return;
    }
    $valores = array();
    foreach ($pdp_anual as $pdp_id => $anual) {
        $pdp_id = (int)$pdp_id;
        if ($pdp_id <= 0) {
            continue;
        }
        $mensual = round((float)$anual / 12, 2);
        for ($mes = 1; $mes <= 12; $mes++) {
            $valores[] = "($pdp_id,$mes,22,0.0833,$mensual,0,0,$mensual)";
        }
        if (count($valores) >= 600) {
            ppto_proy_distribuir_meses_exec($mysqli, $valores);
            $valores = array();
        }
    }
    if (!empty($valores)) {
        ppto_proy_distribuir_meses_exec($mysqli, $valores);
    }
}

/**
 * @param mysqli $mysqli
 * @param array $valores filas SQL ya formateadas
 */
function ppto_proy_distribuir_meses_exec($mysqli, $valores) {
    if (empty($valores)) {
        return;
    }
    $mysqli->query(
        "INSERT INTO pre_proyecto_detalles_mes
            (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
         VALUES " . implode(',', $valores) . "
         ON DUPLICATE KEY UPDATE
            Pdm_PreMensual=VALUES(Pdm_PreMensual),
            Pdm_Disponible=GREATEST(0, VALUES(Pdm_PreMensual) - Pdm_Ejecutado - Pdm_Comprometido)"
    );
}

/**
 * Mapa Ppa_Cod => Pdp_Cod para un proyecto/version (1 query).
 *
 * @param mysqli $mysqli
 * @param string|int $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_rubros_map_por_ppa($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $map = array();
    $proy_esc = $mysqli->real_escape_string(trim((string)$Pro_Cod));
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    if ($proy_esc === '' || $Ppe_Cod <= 0) {
        return $map;
    }
    $sql = "SELECT Ppa_Cod, MIN(Pdp_Cod) AS Pdp_Cod
        FROM pre_proyecto_detalles
        WHERE Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod
        GROUP BY Ppa_Cod";
    $res = $mysqli->query($sql);
    while ($res && ($row = $res->fetch_assoc())) {
        $map[(int)$row['Ppa_Cod']] = (int)$row['Pdp_Cod'];
    }
    return $map;
}

/**
 * Elimina un rubro de proyecto (detalle y meses asociados).
 *
 * @param mysqli $mysqli
 * @param int $Pdp_Cod
 * @param string $Pro_Cod
 * @param int $Emp_Cod
 * @param int $Ppe_Cod
 * @return array
 */
function ppto_proy_rubro_eliminar($mysqli, $Pdp_Cod, $Pro_Cod, $Emp_Cod, $Ppe_Cod) {
    $Pdp_Cod = (int)$Pdp_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    $Ppe_Cod = (int)$Ppe_Cod;
    $proy_esc = $mysqli->real_escape_string(trim($Pro_Cod));

    if ($Pdp_Cod <= 0 || $proy_esc === '' || $Ppe_Cod <= 0) {
        return array('ok' => false, 'message' => 'Datos incompletos para eliminar el rubro.');
    }

    $chk = $mysqli->query("SELECT Pdp_Cod AS Pdp_Cod, Pdp_Cod, Ppa_Cod AS Ppa_Cod, Ppa_Cod FROM pre_proyecto_detalles
        WHERE Pdp_Cod=$Pdp_Cod AND Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod LIMIT 1");
    if (!$chk || $chk->num_rows <= 0) {
        return array('ok' => false, 'message' => 'Rubro no encontrado en este proyecto y version.');
    }
    $rubro_row = $chk->fetch_assoc();
    $ppa_id_rubro = (int)$rubro_row['Ppa_Cod'];

    $mov = $mysqli->query("SELECT SUM(Pdm_Ejecutado + Pdm_Comprometido) AS mov
        FROM pre_proyecto_detalles_mes WHERE Pdp_Cod=$Pdp_Cod");
    if ($mov && ($m = $mov->fetch_assoc()) && (float)$m['mov'] > 0.01) {
        return array('ok' => false, 'message' => 'No se puede eliminar: el rubro tiene montos ejecutados o comprometidos.');
    }

    $mysqli->query("DELETE FROM pre_proyecto_detalles_mes WHERE Pdp_Cod=$Pdp_Cod");

    if (!$mysqli->query("DELETE FROM pre_proyecto_detalles
        WHERE Pdp_Cod=$Pdp_Cod AND Pro_Cod='$proy_esc' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod LIMIT 1")) {
        return array('ok' => false, 'message' => $mysqli->error);
    }

    if ($ppa_id_rubro > 0) {
        $otros = $mysqli->query("SELECT COUNT(*) AS cnt FROM pre_proyecto_detalles
            WHERE Ppa_Cod=$ppa_id_rubro AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod LIMIT 1");
        $sin_otros = ($otros && ($o = $otros->fetch_assoc()) && (int)$o['cnt'] === 0);
        if ($sin_otros) {
            $mysqli->query("DELETE FROM pre_detalle WHERE Ppa_Cod=$ppa_id_rubro AND Ppe_Cod=$Ppe_Cod");
        }
    }

    return array('ok' => true, 'message' => 'Rubro eliminado correctamente.');
}

if ($action === 'list') {
    $rows = array();
    $res = $mysqli->query("SELECT p.Pro_Cod AS Pro_Cod, p.Pro_Cod AS proy_id,
        p.Pro_Ide AS Pro_Codigo, p.Pro_Ide AS proy_codigo,
        p.Pro_Nom AS Pro_Nom, p.Pro_Nom AS proy_nombre,
        p.Pro_Est AS Pro_Est, p.Pro_Est AS proy_estado,
        p.Pro_FecReg AS proy_fecha_registro, p.Pro_FecReg AS Pro_FecReg,
        p.Plt_Cod AS Plt_Cod, p.Plt_Cod AS plt_id,
        pl.Plt_Nom AS Plt_Nom, pl.Plt_Nom AS plt_nombre
        FROM pre_proyectos p
        LEFT JOIN pre_plantillas pl ON p.Plt_Cod = pl.Plt_Cod
        WHERE p.Emp_Cod = $Emp_Cod ORDER BY p.Pro_Nom");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    ppto_json(array('status' => 'success', 'rows' => $rows));
}

if ($action === 'save') {
    $proy_codigo = trim(isset($_POST['Pro_Cod']) ? $_POST['Pro_Cod'] : '');
    $proy_id = isset($_POST['proy_id']) ? (int)$_POST['proy_id'] : 0;
    $Pro_Nom = $mysqli->real_escape_string(trim($_POST['Pro_Nom']));
    $Pro_Est = $mysqli->real_escape_string(isset($_POST['Pro_Est']) ? $_POST['Pro_Est'] : 'A');
    $Plt_Cod = isset($_POST['Plt_Cod']) && $_POST['Plt_Cod'] !== '' ? (int)$_POST['Plt_Cod'] : 'NULL';
    $is_edit = !empty($_POST['is_edit']);
    $proy_codigo_sql = $mysqli->real_escape_string($proy_codigo);

    if ($proy_codigo === '' || $Pro_Nom === '') {
        ppto_json(array('status' => 'error', 'message' => 'Codigo y nombre son obligatorios.'));
    }

    if ($is_edit) {
        if ($proy_id <= 0) {
            $proy_id = ppto_resolve_proy_id($mysqli, $Emp_Cod, $proy_codigo);
        }
        if ($proy_id <= 0) {
            ppto_json(array('status' => 'error', 'message' => 'Proyecto no encontrado.'));
        }
        $sql = "UPDATE pre_proyectos SET Pro_Ide='$proy_codigo_sql', Pro_Nom='$Pro_Nom', Pro_Est='$Pro_Est', Plt_Cod=$Plt_Cod
                WHERE Pro_Cod=$proy_id AND Emp_Cod=$Emp_Cod";
        $ok = $mysqli->query($sql);
    } else {
        $sql = "INSERT INTO pre_proyectos (Emp_Cod, Pro_Ide, Pro_Nom, Pro_Est, Pro_FecReg, Usu_Cod, Plt_Cod)
                VALUES ($Emp_Cod, '$proy_codigo_sql', '$Pro_Nom', '$Pro_Est', CURDATE(), " . (int)$Ses_Usu_Cod . ", $Plt_Cod)";
        $ok = $mysqli->query($sql);
        if ($ok) {
            $proy_id = (int)$mysqli->insert_id;
        }
    }
    if ($ok && !$is_edit && $Plt_Cod !== 'NULL' && $proy_id > 0) {
        ppto_proy_clonar_plantilla($mysqli, $proy_id, (int)$_POST['Plt_Cod'], $Emp_Cod, (int)$Ses_Usu_Cod);
    }
    ppto_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Proyecto guardado.' : $mysqli->error,
        'proy_id' => $proy_id,
        'Pro_Cod' => $proy_id,
        'Pro_Codigo' => $proy_codigo
    ));
}

if ($action === 'save_rubro') {
    $Pro_Cod = $mysqli->real_escape_string(trim($_POST['Pro_Cod']));
    $Ppe_Cod = (int)$_POST['Ppe_Cod'];
    $Ppa_Cod = (int)$_POST['Ppa_Cod'];
    if (!ppto_partida_es_destino_regla($mysqli, $Ppa_Cod, $Emp_Cod)) {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Detalle activas pueden tener rubros de proyecto.'));
    }
    $rubro_txt = trim(isset($_POST['Pdp_Rubro']) ? $_POST['Pdp_Rubro'] : '');
    if (function_exists('ppto_pdf_a_utf8')) {
        $rubro_txt = ppto_pdf_a_utf8($rubro_txt);
    } elseif (function_exists('ppto_texto_reparar_mojibake')) {
        $rubro_txt = ppto_texto_reparar_mojibake($rubro_txt);
    }
    $rubro = $mysqli->real_escape_string($rubro_txt);
    if ($rubro === '' && $Ppa_Cod > 0) {
        $r_desc = $mysqli->query("SELECT Ppa_Des AS Ppa_Des FROM pre_partidas WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod LIMIT 1");
        if ($r_desc && ($row_desc = $r_desc->fetch_assoc())) {
            $rubro = $mysqli->real_escape_string(trim($row_desc['Ppa_Des']));
        }
    }
    if ($rubro === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione una partida detalle valida.'));
    }
    $pdp_edit = isset($_POST['Pdp_Cod']) ? (int)$_POST['Pdp_Cod'] : 0;
    if ($pdp_edit <= 0) {
        $pdp_edit = ppto_proy_rubro_id_por_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $Emp_Cod);
    }
    $r_cod = $mysqli->query("SELECT Ppa_Cla AS Ppa_Cla FROM pre_partidas WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod LIMIT 1");
    $ppa_codigo = ($r_cod && ($rc = $r_cod->fetch_assoc())) ? $rc['Ppa_Cla'] : '';
    $factor = (float)$_POST['Pdp_FacAnualTon'];
    $ton_override = isset($_POST['Pdp_TonBase']) ? (float)$_POST['Pdp_TonBase'] : 0;
    $tn_dia_post = isset($_POST['pdp_tn_dia']) ? (float)$_POST['pdp_tn_dia'] : 0;
    $presup_fijo = isset($_POST['Pdp_PreAnual']) ? (float)$_POST['Pdp_PreAnual'] : 0;

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
            $r0 = $mysqli->query('SELECT Pdp_PreAnual AS Pdp_PreAnual, Pdp_PreAnual FROM pre_proyecto_detalles WHERE Pdp_Cod=' . $pdp_edit . ' LIMIT 1');
            if ($r0 && ($x0 = $r0->fetch_assoc())) {
                $anual = round((float)$x0['Pdp_PreAnual'], 2);
            }
        }
    }

    if ($anual > 0.0001 && $ton > 0.0001) {
        $factor = round($anual / $ton, 6);
    }

    if ($ppa_codigo !== '') {
        $val_tope = ppto_proy_validar_tope_grupo_rubro($mysqli, trim($_POST['Pro_Cod']), $Emp_Cod, $Ppe_Cod, $ppa_codigo, $anual, $pdp_edit);
        if (!$val_tope['ok']) {
            ppto_json(array('status' => 'error', 'message' => $val_tope['message'], 'tope_grupo' => $val_tope));
        }
    }

    if ($pdp_edit > 0) {
        $sql = "UPDATE pre_proyecto_detalles
                SET Pdp_Rubro='$rubro', Pdp_TonBase=$ton, Pdp_FacAnualTon=$factor, Pdp_PreAnual=$anual
                WHERE Pdp_Cod=$pdp_edit AND Pro_Cod='$Pro_Cod' AND Emp_Cod=$Emp_Cod AND Ppe_Cod=$Ppe_Cod";
    } else {
        $sql = "INSERT INTO pre_proyecto_detalles
                (Ppe_Cod, Ppa_Cod, Pro_Cod, Emp_Cod, Pdp_Rubro, Pdp_TonBase, Pdp_FacAnualTon, Pdp_PreAnual, Pdp_FecReg, Usu_Cod)
                VALUES ($Ppe_Cod, $Ppa_Cod, '$Pro_Cod', $Emp_Cod, '$rubro', $ton, $factor, $anual, NOW(), " . (int)$Ses_Usu_Cod . ")";
    }
    $ok = $mysqli->query($sql);
    $resp = array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Rubro guardado.' : $mysqli->error);
    if ($ok) {
        $Pdp_Cod = $pdp_edit > 0 ? $pdp_edit : (int)$mysqli->insert_id;
        if ($Pdp_Cod <= 0) {
            $Pdp_Cod = ppto_proy_rubro_id_por_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $Emp_Cod);
        }
        if ($Pdp_Cod > 0) {
            $dup = ppto_proy_rubro_purgar_duplicados_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $Emp_Cod, $Pdp_Cod);
            if ($dup > 0) {
                $resp['message'] = 'Rubro guardado. Se eliminaron ' . $dup . ' duplicado(s) del mismo codigo.';
            }
            ppto_proy_distribuir_meses($mysqli, $Pdp_Cod, $anual);
        }
        require_once(__DIR__ . '/ppto_divergencia_logica.php');
        $anio_row = $mysqli->query("SELECT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Ppe_Cod=$Ppe_Cod LIMIT 1");
        $anio = ($anio_row && ($ar = $anio_row->fetch_assoc())) ? (int)$ar['Ppe_Ani'] : (int)date('Y');
        $d2 = ppto_divergencia_comparar_toneladas($mysqli, $Pro_Cod, $Emp_Cod, $anio, $Ppe_Cod);
        $resp['divergencia_d2'] = $d2;
        if (!empty($d2['warning']) && $d2['mensaje'] !== '') {
            $resp['warning'] = $d2['mensaje'];
        }
    }
    ppto_json($resp);
}

if ($action === 'delete_rubro') {
    $Pdp_Cod = isset($_POST['Pdp_Cod']) ? (int)$_POST['Pdp_Cod'] : 0;
    $Pro_Cod = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
    $Ppe_Cod = isset($_POST['Ppe_Cod']) ? (int)$_POST['Ppe_Cod'] : 0;
    if ($Pdp_Cod <= 0 || $Pro_Cod === '' || $Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Rubro, proyecto y version son requeridos.'));
    }
    $res = ppto_proy_rubro_eliminar($mysqli, $Pdp_Cod, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    ppto_json(array('status' => 'success', 'message' => $res['message']));
}

if ($action === 'aplicar_escenario') {
    require_once(__DIR__ . '/ppto_forecast_logica.php');
    $proy_id_raw = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
    $Pro_Cod = $mysqli->real_escape_string($proy_id_raw);
    $Ppe_Cod = isset($_POST['Ppe_Cod']) ? (int)$_POST['Ppe_Cod'] : 0;
    $escenario = isset($_POST['escenario']) ? trim($_POST['escenario']) : '';
    if (!in_array($escenario, array('esperada', 'proyectada', 'real'), true)) {
        ppto_json(array('status' => 'error', 'message' => 'Escenario invalido.'));
    }
    if ($Ppe_Cod <= 0) {
        $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
        $Ppe_Cod = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
    }
    if ($proy_id_raw === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa.'));
    }
    $anio_esc = (int)date('Y');
    $r_anio = $mysqli->query("SELECT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Ppe_Cod=$Ppe_Cod LIMIT 1");
    if ($r_anio && ($ra = $r_anio->fetch_assoc())) {
        $anio_esc = (int)$ra['Ppe_Ani'];
    }
    $meses_prod_esc = ppto_forecast_cargar_produccion_meses($mysqli, $Emp_Cod, $anio_esc, $proy_id_raw);
    $ton_base_pdf = ppto_proy_version_ton_base($mysqli, $proy_id_raw, $Emp_Cod, $Ppe_Cod);
    $ton_esc_gasto_mes = ppto_proy_ton_escenario_gasto_mes($ton_base_pdf);
    $ton_costo_mes = ppto_proy_version_ton_costo($mysqli, $proy_id_raw, $Emp_Cod, $Ppe_Cod);

    $res = $mysqli->query("SELECT Pdp_Cod AS Pdp_Cod, Pdp_Cod, Pdp_FacAnualTon AS Pdp_FacAnualTon, Pdp_FacAnualTon, Pdp_PreAnual AS Pdp_PreAnual, Pdp_PreAnual
        FROM pre_proyecto_detalles
        WHERE Pro_Cod='$Pro_Cod' AND Emp_Cod=$Emp_Cod" . ($Ppe_Cod > 0 ? " AND Ppe_Cod=$Ppe_Cod" : ""));
    $actualizados = 0;
    $total_nuevo = 0.0;
    $pendientes = array();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $factor = (float)$row['Pdp_FacAnualTon'];
            $anual = round((float)$row['Pdp_PreAnual'], 2);
            if ($factor > 0.0001) {
                $esc_esperada_val = round($ton_costo_mes * $factor, 2);
                $factor_esc = ppto_proy_factor_escenario_gasto($esc_esperada_val, $ton_esc_gasto_mes);
                $nuevo = ppto_forecast_pf_rubro_anual_escenario($meses_prod_esc, $factor_esc, $escenario);
            } else {
                $nuevo = $anual;
            }
            $pendientes[] = array('Pdp_Cod' => (int)$row['Pdp_Cod'], 'anual' => $nuevo);
            $total_nuevo += $nuevo;
        }
    }
    foreach ($pendientes as $p) {
        $Pdp_Cod = (int)$p['Pdp_Cod'];
        $anual = round((float)$p['anual'], 2);
        if ($mysqli->query("UPDATE pre_proyecto_detalles SET Pdp_PreAnual=$anual WHERE Pdp_Cod=$Pdp_Cod")) {
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
    $Pro_Cod = isset($_GET['Pro_Cod']) ? trim($_GET['Pro_Cod']) : '';
    $Ppe_Cod = isset($_GET['Ppe_Cod']) ? (int)$_GET['Ppe_Cod'] : 0;
    if ($Ppe_Cod <= 0) {
        $Ppe_Cod = ppto_proy_version_buscar_activa($mysqli, $Emp_Cod, (int)date('Y'));
    }
    $cuadro_vista = isset($_GET['cuadro_vista']) ? $_GET['cuadro_vista'] : 'anual';
    $cuadro_mes = isset($_GET['cuadro_mes']) ? $_GET['cuadro_mes'] : null;
    $anio_precio = isset($_GET['anio_precio']) && $_GET['anio_precio'] !== ''
        ? (int)$_GET['anio_precio']
        : null;
    $modo = isset($_GET['modo']) ? trim($_GET['modo']) : 'completa';
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Proyecto requerido.', 'rows' => array()));
    }
    // modo=simple: listado de rubros (rapido). completa: cuadro + forecast + ajuste.
    if ($modo === 'simple') {
        $data = ppto_proy_rubros_listar_simple($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod);
        ppto_json($data);
    }
    require_once __DIR__ . '/ppto_ajuste_financiero_logica.php';
    $data = ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod, $cuadro_vista, $cuadro_mes, $anio_precio);
    $esc_sim = isset($_GET['escenario']) ? trim($_GET['escenario']) : 'esperada';
    if (!in_array($esc_sim, array('esperada', 'proyectada', 'real'), true)) {
        $esc_sim = 'esperada';
    }
    $anio_sim = isset($data['anio_proyeccion']) ? (int)$data['anio_proyeccion'] : $anio_precio;
    $ajuste_escenarios = array();
    foreach (array('esperada', 'proyectada', 'real') as $esc_loop) {
        $ajuste_escenarios[$esc_loop] = ppto_ajuste_simular($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $data, array(
            'escenario' => $esc_loop,
            'anio' => $anio_sim,
        ));
    }
    $data['ajuste_financiero'] = $ajuste_escenarios[$esc_sim];
    $data['ajuste_financiero_escenarios'] = $ajuste_escenarios;
    $data['ajuste_cfg'] = ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    ppto_json($data);
}

if ($action === 'save_grupo_meses') {
    ppto_schema_ensure_partida_meses_prorrateo($mysqli);
    $Ppa_Cod = isset($_POST['Ppa_Cod']) ? (int)$_POST['Ppa_Cod'] : 0;
    $raw_meses = isset($_POST['Ppa_Meses']) ? trim($_POST['Ppa_Meses']) : '';
    if ($Ppa_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Partida grupo requerida.'));
    }
    $chk = $mysqli->query("SELECT Ppa_Cod AS Ppa_Cod, Ppa_Cla AS Ppa_Cla, COALESCE(NULLIF(Ppa_Clase,''),'D') AS Ppa_Clase
        FROM pre_partidas WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod LIMIT 1");
    if (!$chk || !($part = $chk->fetch_assoc()) || $part['Ppa_Clase'] !== 'G') {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Grupo admiten meses de prorrateo.'));
    }
    if ($raw_meses === '') {
        $ok = $mysqli->query("UPDATE pre_partidas SET Ppa_Meses = NULL WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod");
        ppto_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Meses restablecidos a 12 (defecto).' : $mysqli->error));
    }
    $meses = (int)round((float)str_replace(',', '.', $raw_meses));
    if ($meses < 1 || $meses > 999) {
        ppto_json(array('status' => 'error', 'message' => 'Los meses deben estar entre 1 y 999.'));
    }
    $ok = $mysqli->query("UPDATE pre_partidas SET Ppa_Meses = $meses WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod");
    ppto_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Meses guardados para ' . $part['Ppa_Cla'] . ' (' . $meses . ' meses).' : $mysqli->error,
        'Ppa_Meses' => $meses,
    ));
}

if ($action === 'save_grupo_pct') {
    ppto_schema_ensure_partida_porcentaje($mysqli);
    $Ppa_Cod = isset($_POST['Ppa_Cod']) ? (int)$_POST['Ppa_Cod'] : 0;
    $raw_pct = isset($_POST['Ppa_Pct']) ? trim($_POST['Ppa_Pct']) : '';
    if ($Ppa_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Partida grupo requerida.'));
    }
    $chk = $mysqli->query("SELECT Ppa_Cod AS Ppa_Cod, Ppa_Cla AS Ppa_Cla, COALESCE(NULLIF(Ppa_Clase,''),'D') AS Ppa_Clase
        FROM pre_partidas WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod LIMIT 1");
    if (!$chk || !($part = $chk->fetch_assoc()) || $part['Ppa_Clase'] !== 'G') {
        ppto_json(array('status' => 'error', 'message' => 'Solo partidas Grupo admiten porcentaje tope.'));
    }
    if ($raw_pct === '') {
        $ok = $mysqli->query("UPDATE pre_partidas SET Ppa_Pct = NULL WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod");
        ppto_json(array('status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Porcentaje eliminado.' : $mysqli->error));
    }
    $pct = round((float)str_replace(',', '.', $raw_pct), 4);
    if ($pct < 0 || $pct > 100) {
        ppto_json(array('status' => 'error', 'message' => 'El porcentaje debe estar entre 0 y 100.'));
    }
    $pct_sql = number_format($pct, 4, '.', '');
    $ok = $mysqli->query("UPDATE pre_partidas SET Ppa_Pct = $pct_sql WHERE Ppa_Cod=$Ppa_Cod AND Emp_Cod=$Emp_Cod");
    ppto_json(array(
        'status' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Porcentaje guardado para ' . $part['Ppa_Cla'] . '.' : $mysqli->error,
        'Ppa_Pct' => $pct,
    ));
}

if ($action === 'crear_version') {
    $anio = isset($_POST['Ppe_Ani']) ? (int)$_POST['Ppe_Ani'] : (int)date('Y');
    $des = isset($_POST['Ppe_Des']) ? trim($_POST['Ppe_Des']) : '';
    $est = isset($_POST['Ppe_Est']) ? trim($_POST['Ppe_Est']) : 'A';
    if (!in_array($est, array('A', 'B', 'R', 'I'), true)) {
        $est = 'A';
    }
    if ($anio < 2000 || $anio > 2100) {
        ppto_json(array('status' => 'error', 'message' => 'Anio invalido.'));
    }
    if ($des === '') {
        $des = 'Version proyectos ' . $anio;
    }
    $des_sql = $mysqli->real_escape_string($des);
    $est_sql = $mysqli->real_escape_string($est);

    $ver = 1;
    $rmax = $mysqli->query("SELECT MAX(Ppe_Ver) AS mx FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio");
    if ($rmax && ($mx = $rmax->fetch_assoc()) && $mx['mx'] !== null) {
        $ver = ((int)$mx['mx']) + 1;
    }

    $ok = $mysqli->query("INSERT INTO pre_presupuesto (Emp_Cod, Ppe_Ani, Ppe_Ver, Ppe_Des, Ppe_Est, Ppe_Fec, Usu_Cod)
        VALUES ($Emp_Cod, $anio, $ver, '$des_sql', '$est_sql', CURDATE(), " . (int)$Ses_Usu_Cod . ")");
    if (!$ok) {
        ppto_json(array('status' => 'error', 'message' => $mysqli->error));
    }
    $ppe_id = (int)$mysqli->insert_id;
    if ($ppe_id <= 0) {
        $r = $mysqli->query("SELECT Ppe_Cod AS Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio AND Ppe_Ver=$ver LIMIT 1");
        if ($r && ($row = $r->fetch_assoc())) {
            $ppe_id = (int)$row['Ppe_Cod'];
        }
    }
    if ($est === 'A' && $ppe_id > 0) {
        $mysqli->query("UPDATE pre_presupuesto SET Ppe_Est='I' WHERE Emp_Cod=$Emp_Cod AND Ppe_Ani=$anio AND Ppe_Cod<>$ppe_id");
    }
    ppto_json(array(
        'status' => 'success',
        'message' => 'Version creada: ' . $anio . ' V' . $ver . ' (sin rubros corporativos). Ya puede cargar rubros del proyecto.',
        'Ppe_Cod' => $ppe_id,
        'Ppe_Ani' => $anio,
        'Ppe_Ver' => $ver,
        'Ppe_Des' => $des,
        'Ppe_Est' => $est
    ));
}

if ($action === 'asegurar_version') {
    $anio = isset($_REQUEST['Ppe_Ani']) ? (int)$_REQUEST['Ppe_Ani'] : (int)date('Y');
    $res = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, $anio);
    if (empty($res['ok'])) {
        ppto_json(array(
            'status' => 'error',
            'message' => !empty($res['message']) ? $res['message'] : 'No se pudo resolver la cabecera presupuestaria.',
        ));
    }
    ppto_json(array(
        'status' => 'success',
        'Ppe_Cod' => (int)$res['Ppe_Cod'],
        'created' => !empty($res['created']),
        'message' => $res['message'],
    ));
}

if ($action === 'catalogos') {
    $plantillas = array();
    $partidas = array();
    $versiones = array();
    $r1 = $mysqli->query("SELECT Plt_Cod AS plt_id, Plt_Nom AS plt_nombre, Plt_Cod AS Plt_Cod, Plt_Nom AS Plt_Nom FROM pre_plantillas WHERE Emp_Cod=$Emp_Cod AND Plt_Est='A'");
    if ($r1) {
        while ($x = $r1->fetch_assoc()) {
            $plantillas[] = $x;
        }
    }
    $r2_list = ppto_partidas_listar($mysqli, array('Emp_Cod' => $Emp_Cod, 'solo_activas' => true, 'clase' => 'D'));
    foreach ($r2_list as $x) {
        $partidas[] = array(
            'Ppa_Cod' => $x['Ppa_Cod'],
            'Ppa_Cla' => $x['Ppa_Cla'],
            'Ppa_Des' => $x['Ppa_Des'],
            'Ppa_Niv' => $x['Ppa_Niv'],
            'Ppa_Clase' => isset($x['Ppa_Clase']) ? $x['Ppa_Clase'] : 'D'
        );
    }
    $partidas_grupo = array();
    $r2g = ppto_partidas_listar($mysqli, array('Emp_Cod' => $Emp_Cod, 'solo_activas' => true, 'clase' => 'G'));
    foreach ($r2g as $x) {
        $partidas_grupo[] = array(
            'Ppa_Cod' => $x['Ppa_Cod'],
            'Ppa_Cla' => $x['Ppa_Cla'],
            'Ppa_Des' => $x['Ppa_Des'],
            'Ppa_Niv' => $x['Ppa_Niv'],
            'Ppa_Clase' => 'G',
            'Ppa_Pct' => isset($x['Ppa_Pct']) ? $x['Ppa_Pct'] : null,
            'Ppa_Meses' => isset($x['Ppa_Meses']) ? $x['Ppa_Meses'] : null,
        );
    }
    $r3 = $mysqli->query("SELECT Ppe_Cod AS Ppe_Cod, Ppe_Ani AS Ppe_Ani, Ppe_Ver AS Ppe_Ver, Ppe_Des AS Ppe_Des, Ppe_Est AS Ppe_Est FROM pre_presupuesto WHERE Emp_Cod=$Emp_Cod ORDER BY Ppe_Ani DESC, Ppe_Ver DESC");
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
    $padre_id = isset($_POST['Ppa_Pad']) ? (int)$_POST['Ppa_Pad'] : 0;
    $res = ppto_partida_guardar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, array(
        'Ppa_Cla' => isset($_POST['Ppa_Cla']) ? $_POST['Ppa_Cla'] : '',
        'Ppa_Des' => isset($_POST['Ppa_Des']) ? $_POST['Ppa_Des'] : '',
        'Ppa_Tip' => isset($_POST['Ppa_Tip']) ? $_POST['Ppa_Tip'] : 'G',
        'Ppa_Nat' => isset($_POST['Ppa_Nat']) ? $_POST['Ppa_Nat'] : 'OPE',
        'Ppa_Clase' => isset($_POST['Ppa_Clase']) ? $_POST['Ppa_Clase'] : 'D',
        'Ppa_Pad' => $padre_id,
    ));
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    ppto_json(array(
        'status' => 'success',
        'message' => $res['message'],
        'partida' => array(
            'Ppa_Cod' => $res['Ppa_Cod'],
            'Ppa_Cla' => $res['Ppa_Cla'],
            'Ppa_Des' => $res['Ppa_Des'],
            'Ppa_Niv' => $res['Ppa_Niv'],
            'Ppa_Clase' => $res['Ppa_Clase'],
        ),
    ));
}

if ($action === 'get_version_config') {
    $Pro_Cod = isset($_GET['Pro_Cod']) ? trim($_GET['Pro_Cod']) : '';
    $Ppe_Cod = isset($_GET['Ppe_Cod']) ? (int)$_GET['Ppe_Cod'] : 0;
    if ($Ppe_Cod <= 0) {
        $Ppe_Cod = ppto_proy_version_buscar_activa($mysqli, $Emp_Cod, (int)date('Y'));
    }
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa.'));
    }
    $ton = ppto_version_ton_base_sanitize(ppto_proy_version_ton_base($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod));
    $ton_costo = ppto_proy_version_ton_costo($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
    $cfg = ppto_proy_version_config($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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
    $Pro_Cod = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
    $Ppe_Cod = isset($_POST['Ppe_Cod']) ? (int)$_POST['Ppe_Cod'] : 0;
    $ton_raw = isset($_POST['pv_toneladas_base_mes']) ? (float)$_POST['pv_toneladas_base_mes'] : 0;
    $ton = ppto_version_ton_base_sanitize($ton_raw);
    $ton_costo = ppto_version_ton_costo_sanitize(isset($_POST['pv_toneladas_costo_mes']) ? (float)$_POST['pv_toneladas_costo_mes'] : 0);
    $tarifa = isset($_POST['pv_tarifa_ton_iva']) ? (float)$_POST['pv_tarifa_ton_iva'] : 3.0;
    $iva_div = isset($_POST['pv_iva_divisor']) ? (float)$_POST['pv_iva_divisor'] : 1.15;
    $aplicar = !empty($_POST['aplicar_rubros']);
    if ($Ppe_Cod <= 0) {
        $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
        $Ppe_Cod = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
    }
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa.'));
    }
    if ($ton <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Ingrese ton ingresos (mes) mayores a cero.'));
    }
    if ($aplicar && $ton_costo <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'Ingrese ton costo egreso (mes) para aplicar a rubros.'));
    }
    $res = ppto_proy_version_guardar_ton($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $ton, (int)$Ses_Usu_Cod, $aplicar, $tarifa, $iva_div, $ton_costo);
    if (!$res['ok']) {
        ppto_json(array('status' => 'error', 'message' => $res['message']));
    }
    $sync = ppto_proy_version_sync_prod_esperada($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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
    $n_rubros_parse = isset($parsed['rubros']) && is_array($parsed['rubros']) ? count($parsed['rubros']) : 0;
    if ($n_rubros_parse > 2000) {
        ppto_json(array(
            'status' => 'error',
            'message' => 'El archivo tiene ' . $n_rubros_parse . ' rubros (maximo 2000). Divida el Excel e importe por partes.',
        ));
    }
    $slim = ppto_pdf_payload_slim($parsed);
    $ton_detectada = isset($parsed['ton_base']) ? (float)$parsed['ton_base'] : 0;
    if ($ton_detectada >= 70000 && $ton_detectada < 95000) {
        $slim['warnings'][] = 'El archivo usa ' . number_format($ton_detectada, 0, '.', ',')
            . ' ton/mes como base de costo (3500 x 22). Ton ingresos del proyecto se mantiene en '
            . number_format($slim['ton_ingreso_mes'], 0, '.', ',') . '.';
    }
    $validacion = ppto_pdf_validar_contra_catalogo($mysqli, $Emp_Cod, $parsed);
    $catalogo = $validacion['catalogo'];
    $proy_parse = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
    $ppe_parse = isset($_POST['Ppe_Cod']) ? (int)$_POST['Ppe_Cod'] : 0;
    if ($ppe_parse <= 0) {
        $ppe_parse = ppto_proy_version_buscar_activa($mysqli, $Emp_Cod, (int)date('Y'));
        if ($ppe_parse <= 0) {
            $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
            $ppe_parse = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
        }
    }
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
                $catalogo[$cod]['rubro_nombre_actual'] = $info['Pdp_Rubro'];
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
    $cat_existentes = 0;
    $cat_nuevas = 0;
    $cat_nombre_distinto = 0;
    foreach ($catalogo as $cInfo) {
        if (!empty($cInfo['estado']) && $cInfo['estado'] === 'nuevo') {
            $cat_nuevas++;
        } elseif (!empty($cInfo['existe'])) {
            $cat_existentes++;
            if (!empty($cInfo['nombre_distinto'])) {
                $cat_nombre_distinto++;
            }
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
        'catalogo_resumen' => array(
            'existentes' => $cat_existentes,
            'nuevas' => $cat_nuevas,
            'nombre_distinto' => $cat_nombre_distinto,
        ),
        'rubros_proyecto' => $rubros_proyecto,
        'import_bloqueado' => !empty($validacion['conflictos']),
        'lineas_muestra' => isset($parsed['lineas']) ? array_slice($parsed['lineas'], 0, 12) : array(),
        'payload' => $slim,
    ));
}

if ($action === 'import_pdf') {
    require_once(__DIR__ . '/ppto_pdf_logica.php');
    $Pro_Cod = isset($_POST['Pro_Cod']) ? trim($_POST['Pro_Cod']) : '';
    $Ppe_Cod = isset($_POST['Ppe_Cod']) ? (int)$_POST['Ppe_Cod'] : 0;
    $ton_override = ppto_version_ton_base_sanitize(isset($_POST['pv_toneladas_base_mes']) ? (float)$_POST['pv_toneladas_base_mes'] : 0);

    if ($Ppe_Cod <= 0) {
        $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
        $Ppe_Cod = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
    }
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto antes de importar.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa para importar.'));
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

    $import_opts = array(
        'crear_nuevas' => !(isset($_POST['crear_nuevas']) && (string)$_POST['crear_nuevas'] === '0'),
        'actualizar_nombres' => (isset($_POST['actualizar_nombres']) && (string)$_POST['actualizar_nombres'] === '1'),
    );
    $res = ppto_pdf_importar_presupuesto(
        $mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, $Pro_Cod, $Ppe_Cod, $parsed, $ton_override, $import_opts
    );
    if (!$res['ok']) {
        ppto_json(array(
            'status' => 'error',
            'message' => $res['message'],
            'conflictos' => isset($res['conflictos']) ? $res['conflictos'] : array(),
        ));
    }
    $sync = ppto_proy_version_sync_prod_esperada($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
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
    $Pro_Cod = isset($_REQUEST['Pro_Cod']) ? trim($_REQUEST['Pro_Cod']) : '';
    $Ppe_Cod = isset($_REQUEST['Ppe_Cod']) ? (int)$_REQUEST['Ppe_Cod'] : 0;
    if ($Ppe_Cod <= 0) {
        $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
        $Ppe_Cod = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
    }
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa.'));
    }
    $anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : 0;
    if ($anio <= 0) {
        $r_anio = $mysqli->query("SELECT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Ppe_Cod=$Ppe_Cod LIMIT 1");
        $anio = ($r_anio && ($ra = $r_anio->fetch_assoc())) ? (int)$ra['Ppe_Ani'] : (int)date('Y');
    }
    if ($action === 'ultima_publicacion') {
        $ult = ppto_proy_publicar_ultima($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod);
        ppto_json(array('status' => 'success', 'ultima' => $ult));
    }
    if ($action === 'preview_publicar') {
        $prev = ppto_proy_publicar_preview($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio);
        if (empty($prev['ok'])) {
            ppto_json(array('status' => 'error', 'message' => $prev['message']));
        }
        ppto_json(array('status' => 'success', 'preview' => $prev));
    }
    $forzar = !empty($_POST['confirmar_republicacion']) && $_POST['confirmar_republicacion'] === '1';
    $res = ppto_proy_publicar_ejecutar($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $anio, (int)$Ses_Usu_Cod, $forzar, true);
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
    $Pro_Cod = isset($_REQUEST['Pro_Cod']) ? trim($_REQUEST['Pro_Cod']) : '';
    $Ppe_Cod = isset($_REQUEST['Ppe_Cod']) ? (int)$_REQUEST['Ppe_Cod'] : 0;
    if ($Ppe_Cod <= 0) {
        $ens = ppto_proy_version_asegurar($mysqli, $Emp_Cod, (int)$Ses_Usu_Cod, (int)date('Y'));
        $Ppe_Cod = !empty($ens['ok']) ? (int)$ens['Ppe_Cod'] : 0;
    }
    if ($Pro_Cod === '') {
        ppto_json(array('status' => 'error', 'message' => 'Seleccione un proyecto.'));
    }
    if ($Ppe_Cod <= 0) {
        ppto_json(array('status' => 'error', 'message' => 'No hay cabecera presupuestaria activa.'));
    }

    if ($action === 'ajuste_cfg_get') {
        ppto_json(array(
            'status' => 'success',
            'cfg' => ppto_ajuste_cfg_get($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod),
            'precios' => ppto_ajuste_precios_list($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod),
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
        $res = ppto_ajuste_cfg_save($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $data, (int)$Ses_Usu_Cod);
        ppto_json(array(
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['message'],
            'cfg' => isset($res['cfg']) ? $res['cfg'] : null,
        ));
    }

    if ($action === 'ajuste_precios_list') {
        ppto_json(array(
            'status' => 'success',
            'precios' => ppto_ajuste_precios_list($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod),
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
        $res = ppto_ajuste_precios_save($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $precios, (int)$Ses_Usu_Cod);
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
        $cuadro = ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $Pro_Cod, $Ppe_Cod, $cuadro_vista, $cuadro_mes, $anio_precio);
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
        $todos = !empty($_REQUEST['todos_escenarios']);
        $sims = array();
        if ($todos) {
            foreach (array('esperada', 'proyectada', 'real') as $esc_loop) {
                $opts_esc = $opts;
                $opts_esc['escenario'] = $esc_loop;
                $sims[$esc_loop] = ppto_ajuste_simular($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $cuadro, $opts_esc);
            }
            $sim = isset($sims[$escenario]) ? $sims[$escenario] : $sims['esperada'];
        } else {
            $sim = ppto_ajuste_simular($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $cuadro, $opts);
            $sims[$escenario] = $sim;
        }
        if ($action === 'ajuste_simular') {
            ppto_json(array('status' => 'success', 'sim' => $sim, 'sims' => $sims));
        }
        $obs = isset($_POST['observacion']) ? $_POST['observacion'] : '';
        $res = ppto_ajuste_aplicar($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, $sim, (int)$Ses_Usu_Cod, $obs);
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
            'rows' => ppto_ajuste_historial($mysqli, $Pro_Cod, $Emp_Cod, $Ppe_Cod, 30),
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
