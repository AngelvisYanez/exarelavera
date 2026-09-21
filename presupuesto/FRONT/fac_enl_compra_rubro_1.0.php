<?php
/**
 * Enlace de facturas de compra a rubro de presupuesto (proyecto).
 * Archivo unico: listado con check, filtro RUC/razon social, un rubro para todas las seleccionadas.
 * Registra Asi_Cod de cada producto en pre_proyecto_detalle_asiento.
 *
 * Dependencia minima del sistema: autenticacion/sesion (seguridad.php).
 *
 * @version 2.0
 */
require_once('../../administrador/LOGICA/seguridad.php');

$obBD = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
$mysqli = isset($obBD->conexion) ? $obBD->conexion : null;
$Emp_Cod = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
$Suc_Cod_Ses = isset($Ses_Suc_Cod) ? (int)$Ses_Suc_Cod : 0;
$hoy = date('Y-m-d');
$anioIni = date('Y-m-01');
$mesFinDef = date('Y-m-t');
$anioActual = (int)date('Y');
$mesActual = (int)date('n');

function enl_to_utf8($val)
{
    if (!is_string($val) || $val === '') {
        return $val;
    }
    /* Si ya es UTF-8 valido y no parece latin1 mal detectado, dejar. */
    if (function_exists('mb_check_encoding') && mb_check_encoding($val, 'UTF-8')) {
        /* Latin1 con tildes a veces pasa el check; si hay bytes tipicos ISO y falla sentido, convertir. */
        return $val;
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
    }
    return utf8_encode($val);
}

/**
 * Normaliza texto para busqueda: UTF-8, minusculas y sin tildes.
 */
function enl_buscar_norm($val)
{
    $val = enl_to_utf8((string)$val);
    if ($val === '') {
        return '';
    }
    if (function_exists('mb_strtolower')) {
        $val = mb_strtolower($val, 'UTF-8');
    } else {
        $val = strtolower($val);
    }
    $from = array(
        '?', '?', '?', '?', '?', '?', '?', '?', '?', '?',
        '?', '?', '?', '?', '?', '?', '?', '?',
        '?', '?', '?', '?', '?', '?', '?', '?',
        '?', '?', '?', '?', '?', '?', '?', '?', '?', '?',
        '?', '?', '?', '?', '?', '?', '?', '?',
        '?', '?', '?', '?'
    );
    $to = array(
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'n', 'n', 'c', 'c'
    );
    return str_replace($from, $to, $val);
}

function enl_contiene($haystack, $needle)
{
    $n = enl_buscar_norm($needle);
    if ($n === '') {
        return true;
    }
    $h = enl_buscar_norm($haystack);
    if (function_exists('mb_strpos')) {
        return mb_strpos($h, $n, 0, 'UTF-8') !== false;
    }
    return strpos($h, $n) !== false;
}

/** Compara codigos de partida (puntos/espacios opcionales). */
function enl_contiene_codigo($codigo, $needle)
{
    $n = enl_buscar_norm($needle);
    if ($n === '') {
        return true;
    }
    $c = enl_buscar_norm($codigo);
    /* Quita espacios; mantiene puntos para jerarquia 02.01.01 */
    $n = preg_replace('/\s+/', '', $n);
    $c = preg_replace('/\s+/', '', $c);
    if ($n === '') {
        return true;
    }
    if (strpos($c, $n) !== false) {
        return true;
    }
    /* Tambien sin puntos: 020101 vs 02.01.01 */
    $n2 = str_replace('.', '', $n);
    $c2 = str_replace('.', '', $c);
    return $n2 !== '' && strpos($c2, $n2) !== false;
}

function enl_utf8_deep($data)
{
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $data[$k] = enl_utf8_deep($v);
        }
        return $data;
    }
    if (is_string($data)) {
        return enl_to_utf8($data);
    }
    return $data;
}

function enl_json($data)
{
    if (ob_get_length()) {
        @ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $data = enl_utf8_deep($data);
    $flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $flags |= JSON_UNESCAPED_UNICODE;
    }
    if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
        $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
    }
    echo $flags ? json_encode($data, $flags) : json_encode($data);
    exit;
}

function enl_fecha($val, $fallback)
{
    $val = trim((string)$val);
    if ($val === '') {
        return $fallback;
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $val, $m)) {
        $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $y = $m[3];
        if (strlen($y) === 2) {
            $y = ((int)$y > 50 ? '19' : '20') . $y;
        }
        if (checkdate((int)$mo, (int)$d, (int)$y)) {
            return $y . '-' . $mo . '-' . $d;
        }
    }
    return $fallback;
}

function enl_tabla_ok($mysqli, $tabla)
{
    if (!$mysqli || $tabla === '') {
        return false;
    }
    $t = $mysqli->real_escape_string($tabla);
    $res = @$mysqli->query("SHOW TABLES LIKE '$t'");
    return $res && $res->num_rows > 0;
}

function enl_tabla_detalle($mysqli)
{
    if (enl_tabla_ok($mysqli, 'pre_proyecto_detalles')) {
        return 'pre_proyecto_detalles';
    }
    if (enl_tabla_ok($mysqli, 'pre_proyecto_detalle')) {
        return 'pre_proyecto_detalle';
    }
    return '';
}

function enl_ruta_partida($mysqli, $ppaCod)
{
    $ppaCod = (int)$ppaCod;
    if (!$mysqli || $ppaCod <= 0) {
        return '';
    }
    $parts = array();
    $seen = array();
    $cur = $ppaCod;
    while ($cur > 0 && !isset($seen[$cur])) {
        $seen[$cur] = true;
        $res = @$mysqli->query(
            "SELECT Ppa_Cod, Ppa_Cla, Ppa_Des, IFNULL(Ppa_Pad, 0) AS Ppa_Pad
             FROM pre_partidas WHERE Ppa_Cod = $cur LIMIT 1"
        );
        if (!$res || !($r = $res->fetch_assoc())) {
            break;
        }
        array_unshift($parts, trim($r['Ppa_Cla'] . ' ' . $r['Ppa_Des']));
        $cur = (int)$r['Ppa_Pad'];
        if ($cur <= 0) {
            $cla = (string)$r['Ppa_Cla'];
            $pos = strrpos($cla, '.');
            if ($pos !== false) {
                $padreCla = $mysqli->real_escape_string(substr($cla, 0, $pos));
                $rp = @$mysqli->query("SELECT Ppa_Cod FROM pre_partidas WHERE Ppa_Cla = '$padreCla' LIMIT 1");
                if ($rp && ($pr = $rp->fetch_assoc())) {
                    $cur = (int)$pr['Ppa_Cod'];
                }
            }
        }
    }
    return implode(' > ', $parts);
}

/**
 * Asi_Cod de la compra via compr_auto -> comprobantes -> asientos
 * solo de cuentas (Pld_Cod) asignadas a productos de det_compra,
 * que aun NO estan en pre_proyecto_detalle_asiento.
 */
function enl_asientos_pendientes($mysqli, $Cop_Cod)
{
    return enl_asientos_producto($mysqli, $Cop_Cod, false);
}

/**
 * Asientos de producto de la compra (pendientes y/o ya vinculados).
 * @param bool $soloPendientes true = solo sin PDA; false = todos los de producto
 */
function enl_asientos_producto($mysqli, $Cop_Cod, $soloPendientes = false)
{
    $Cop_Cod = (int)$Cop_Cod;
    $asiList = array();
    if (!$mysqli || $Cop_Cod <= 0 || !enl_tabla_ok($mysqli, 'pre_proyecto_detalle_asiento')) {
        return $asiList;
    }

    $condPda = $soloPendientes ? 'AND pda.Asi_Cod IS NULL' : '';
    $res = $mysqli->query(
        "SELECT DISTINCT a.Asi_Cod
         FROM compr_auto ca
         INNER JOIN comprobantes co ON co.Com_Cod = ca.Com_Cod AND co.Com_Est = 'A'
         INNER JOIN asientos a ON a.Com_Cod = co.Com_Cod AND a.Asi_Deh = 'D'
         INNER JOIN det_compra dc ON dc.Cop_Cod = ca.Cop_Cod
            AND dc.Pld_Cod = a.Pld_Cod
            AND IFNULL(dc.Pld_Cod, 0) > 0
         LEFT JOIN pre_proyecto_detalle_asiento pda ON pda.Asi_Cod = a.Asi_Cod
         WHERE ca.Cop_Cod = $Cop_Cod
           AND IFNULL(a.Asi_Cod, 0) > 0
           $condPda"
    );
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $asi = (int)$r['Asi_Cod'];
            if ($asi > 0) {
                $asiList[$asi] = $asi;
            }
        }
    }

    return $asiList;
}

/**
 * Cuenta asientos de productos pendientes de vincular.
 */
function enl_sql_pendientes_expr($aliasCompra = 'c')
{
    return "(
        SELECT COUNT(DISTINCT a.Asi_Cod)
        FROM compr_auto ca
        INNER JOIN comprobantes co ON co.Com_Cod = ca.Com_Cod AND co.Com_Est = 'A'
        INNER JOIN asientos a ON a.Com_Cod = co.Com_Cod AND a.Asi_Deh = 'D'
        INNER JOIN det_compra dc ON dc.Cop_Cod = ca.Cop_Cod
           AND dc.Pld_Cod = a.Pld_Cod AND IFNULL(dc.Pld_Cod, 0) > 0
        LEFT JOIN pre_proyecto_detalle_asiento pda ON pda.Asi_Cod = a.Asi_Cod
        WHERE ca.Cop_Cod = $aliasCompra.Cop_Cod
          AND IFNULL(a.Asi_Cod, 0) > 0
          AND pda.Asi_Cod IS NULL
    )";
}

/**
 * Cuenta asientos de productos YA vinculados a presupuesto.
 */
function enl_sql_vinculados_expr($aliasCompra = 'c')
{
    return "(
        SELECT COUNT(DISTINCT a.Asi_Cod)
        FROM compr_auto ca
        INNER JOIN comprobantes co ON co.Com_Cod = ca.Com_Cod AND co.Com_Est = 'A'
        INNER JOIN asientos a ON a.Com_Cod = co.Com_Cod AND a.Asi_Deh = 'D'
        INNER JOIN det_compra dc ON dc.Cop_Cod = ca.Cop_Cod
           AND dc.Pld_Cod = a.Pld_Cod AND IFNULL(dc.Pld_Cod, 0) > 0
        INNER JOIN pre_proyecto_detalle_asiento pda ON pda.Asi_Cod = a.Asi_Cod
        WHERE ca.Cop_Cod = $aliasCompra.Cop_Cod
          AND IFNULL(a.Asi_Cod, 0) > 0
    )";
}

/**
 * Texto del/los rubro(s) ya relacionados a la factura (codigo - nombre).
 */
function enl_sql_rubros_rel_expr($mysqli, $aliasCompra = 'c')
{
    $tblDet = enl_tabla_detalle($mysqli);
    if ($tblDet === '') {
        return "CAST('' AS CHAR)";
    }
    return "(
        SELECT GROUP_CONCAT(DISTINCT
            CONCAT(
                IFNULL(p.Ppa_Cla, ''),
                IF(IFNULL(p.Ppa_Cla, '') = '', '', ' - '),
                IFNULL(NULLIF(TRIM(d.Pdp_Rubro), ''), IFNULL(p.Ppa_Des, ''))
            )
            ORDER BY p.Ppa_Cla SEPARATOR ' | '
        )
        FROM compr_auto ca
        INNER JOIN comprobantes co ON co.Com_Cod = ca.Com_Cod AND co.Com_Est = 'A'
        INNER JOIN asientos a ON a.Com_Cod = co.Com_Cod AND a.Asi_Deh = 'D'
        INNER JOIN det_compra dc ON dc.Cop_Cod = ca.Cop_Cod
           AND dc.Pld_Cod = a.Pld_Cod AND IFNULL(dc.Pld_Cod, 0) > 0
        INNER JOIN pre_proyecto_detalle_asiento pda ON pda.Asi_Cod = a.Asi_Cod
        INNER JOIN `$tblDet` d ON d.Pdp_Cod = pda.Pdp_Cod
        LEFT JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
        WHERE ca.Cop_Cod = $aliasCompra.Cop_Cod
          AND IFNULL(a.Asi_Cod, 0) > 0
    )";
}

function enl_vincular($mysqli, $Pdp_Cod, $Asi_Cod)
{
    $Pdp_Cod = (int)$Pdp_Cod;
    $Asi_Cod = (int)$Asi_Cod;
    if (!$mysqli || $Pdp_Cod <= 0 || $Asi_Cod <= 0) {
        return false;
    }
    /* Tabla real: solo Pdp_Cod + Asi_Cod (PK Asi_Cod). Sin Ppa_Cod. */
    $ok = @$mysqli->query(
        "INSERT INTO pre_proyecto_detalle_asiento (Pdp_Cod, Asi_Cod)
         VALUES ($Pdp_Cod, $Asi_Cod)
         ON DUPLICATE KEY UPDATE Pdp_Cod = VALUES(Pdp_Cod)"
    );
    return (bool)$ok;
}

/* ---------- AJAX: listado pendientes ---------- */
if (isset($_REQUEST['listPendientesAjax'])) {
    $out = array('success' => true, 'rows' => array(), 'records' => 0, 'message' => '');
    try {
        if (!$mysqli) {
            throw new Exception('Sin conexion a base de datos.');
        }
        if ($Emp_Cod <= 0) {
            throw new Exception('Empresa no valida.');
        }
        if (!enl_tabla_ok($mysqli, 'pre_proyecto_detalle_asiento')) {
            throw new Exception('No existe la tabla pre_proyecto_detalle_asiento.');
        }
        $Fec_Ini = enl_fecha(isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '', $anioIni);
        $Fec_Fin = enl_fecha(isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '', $mesFinDef);
        $filtroTipo = isset($_REQUEST['filtro_tipo']) ? trim((string)$_REQUEST['filtro_tipo']) : '';
        $filtroValor = isset($_REQUEST['filtro_valor']) ? trim((string)$_REQUEST['filtro_valor']) : '';
        $filtroSuc = isset($_REQUEST['filtro_suc']) ? (int)$_REQUEST['filtro_suc'] : 0;
        /* Compat con params antiguos */
        if ($filtroValor === '') {
            if (!empty($_REQUEST['filtro_ruc'])) {
                $filtroTipo = 'ruc';
                $filtroValor = trim((string)$_REQUEST['filtro_ruc']);
            } elseif (!empty($_REQUEST['filtro_razon'])) {
                $filtroTipo = 'razon';
                $filtroValor = trim((string)$_REQUEST['filtro_razon']);
            } elseif (!empty($_REQUEST['search'])) {
                $filtroTipo = 'numero';
                $filtroValor = trim((string)$_REQUEST['search']);
            }
        }

        $filtro = '';
        if ($filtroSuc > 0) {
            $filtro .= " AND EXISTS (
                SELECT 1 FROM vendedor vnd
                INNER JOIN puntos_imp pi ON pi.Pun_Cod = vnd.Pun_Cod
                WHERE vnd.Vnd_Cod = c.Vnd_Cod AND pi.Suc_Cod = $filtroSuc
            )";
        }
        if ($filtroValor !== '') {
            $esc = $mysqli->real_escape_string($filtroValor);
            switch ($filtroTipo) {
                case 'ruc':
                    $filtro .= " AND prs.Prs_Ced LIKE '%$esc%'";
                    break;
                case 'razon':
                    $filtro .= " AND CONCAT(IFNULL(prs.Prs_Ape,''),' ',IFNULL(prs.Prs_Nom,'')) LIKE '%$esc%'";
                    break;
                case 'obs':
                    /* Cop_Obs es BLOB: LIKE directo es binario y no encuentra texto. */
                    $obsParts = preg_split('/\s+/u', $filtroValor, -1, PREG_SPLIT_NO_EMPTY);
                    if (!$obsParts || !is_array($obsParts)) {
                        $obsParts = array($filtroValor);
                    }
                    foreach ($obsParts as $obsTok) {
                        $obsTok = trim((string)$obsTok);
                        if ($obsTok === '') {
                            continue;
                        }
                        $escU = $mysqli->real_escape_string($obsTok);
                        $escL = $obsTok;
                        if (function_exists('mb_convert_encoding')) {
                            $tmp = @mb_convert_encoding($obsTok, 'ISO-8859-1', 'UTF-8');
                            if ($tmp !== false && $tmp !== '') {
                                $escL = $tmp;
                            }
                        } elseif (function_exists('utf8_decode')) {
                            $escL = utf8_decode($obsTok);
                        }
                        $escL = $mysqli->real_escape_string($escL);
                        $filtro .= " AND (
                            LOWER(CONVERT(IFNULL(c.Cop_Obs, '') USING latin1)) LIKE LOWER('%$escL%')
                            OR LOWER(CONVERT(IFNULL(c.Cop_Obs, '') USING utf8)) LIKE LOWER('%$escU%')
                            OR LOWER(CAST(IFNULL(c.Cop_Obs, '') AS CHAR CHARACTER SET latin1)) LIKE LOWER('%$escL%')
                            OR LOWER(CAST(IFNULL(c.Cop_Obs, '') AS CHAR CHARACTER SET utf8)) LIKE LOWER('%$escU%')
                        )";
                    }
                    break;
                case 'numero':
                default:
                    $filtro .= " AND (c.Cop_Num LIKE '%$esc%' OR CAST(c.Cop_Cod AS CHAR) LIKE '%$esc%')";
                    break;
            }
        }

        $pendExpr = enl_sql_pendientes_expr('c');
        $vincExpr = enl_sql_vinculados_expr('c');
        $rubrosRelExpr = enl_sql_rubros_rel_expr($mysqli, 'c');
        $incluirRel = !empty($_REQUEST['incluir_relacionadas'])
            && ($_REQUEST['incluir_relacionadas'] === '1'
                || $_REQUEST['incluir_relacionadas'] === 'true'
                || $_REQUEST['incluir_relacionadas'] === 'on');

        /* Sin check: solo pendientes. Con check: pendientes + ya relacionadas. */
        $condAsiento = $incluirRel
            ? "AND ($pendExpr > 0 OR $vincExpr > 0)"
            : "AND $pendExpr > 0";

        $sql = "SELECT c.Cop_Cod, c.Cop_Num, c.Cop_Fec, c.Cop_Obs,
                       tc.Tic_Des,
                       CONCAT(IFNULL(prs.Prs_Ape,''),' ',IFNULL(prs.Prs_Nom,'')) AS proveedor,
                       prs.Prs_Ced,
                       (SELECT ca.Com_Cod FROM compr_auto ca
                         WHERE ca.Cop_Cod = c.Cop_Cod
                         LIMIT 1) AS Com_Cod,
                       (SELECT COUNT(*) FROM det_compra dc2 WHERE dc2.Cop_Cod = c.Cop_Cod) AS tot_items,
                       $pendExpr AS pendientes,
                       $vincExpr AS vinculados,
                       $rubrosRelExpr AS rubro_rel,
                       ROUND((
                         SELECT SUM(IFNULL(dc3.Cop_Imp,0))
                         FROM det_compra dc3 WHERE dc3.Cop_Cod = c.Cop_Cod
                       ), 2) AS Cop_Imp,
                       ROUND((
                         SELECT SUM(
                           (
                             IFNULL(dc4.Cop_Imp,0)
                             - (IFNULL(dc4.Cop_Imp,0) * IFNULL(c.Cop_Des,0) / 100)
                             - (IFNULL(dc4.Cop_Imp,0) * IFNULL(dc4.Cop_Dec,0) / 100)
                           ) * (1 + IFNULL(iva.Iva_Por,0) / 100)
                         )
                         FROM det_compra dc4
                         LEFT JOIN iva ON iva.Iva_Cod = dc4.Iva_Cod
                         WHERE dc4.Cop_Cod = c.Cop_Cod
                       ), 2) AS Cop_Tot
                FROM compras c
                INNER JOIN proveedore pv ON pv.Prv_Cod = c.Prv_Cod AND pv.Emp_Cod = $Emp_Cod
                INNER JOIN persona prs ON prs.Prs_Cod = pv.Prs_Cod
                INNER JOIN tipo_compr tc ON tc.Tic_Cod = c.Tic_Cod AND tc.Tic_Cod <> 4
                WHERE c.Cop_Est = 'A'
                  AND c.Cop_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                  $filtro
                  $condAsiento
                ORDER BY proveedor ASC, c.Cop_Fec DESC, c.Cop_Cod DESC
                LIMIT 10000";
        $res = $mysqli->query($sql);
        if (!$res) {
            throw new Exception('Error al listar: ' . $mysqli->error);
        }
        $rows = array();
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $out['rows'] = $rows;
        $out['records'] = count($rows);
        $out['Fec_Ini'] = $Fec_Ini;
        $out['Fec_Fin'] = $Fec_Fin;
        if (!$rows) {
            $out['message'] = $incluirRel
                ? 'No hay compras con asientos de producto (pendientes o relacionadas) entre ' . $Fec_Ini . ' y ' . $Fec_Fin . '.'
                : 'No hay compras con asiento pendiente de presupuesto entre ' . $Fec_Ini . ' y ' . $Fec_Fin . '.';
        }
    } catch (Exception $e) {
        $out['success'] = false;
        $out['message'] = $e->getMessage();
    }
    enl_json($out);
}

/* ---------- AJAX: cuentas contables de productos de la compra ---------- */
if (isset($_REQUEST['cuentasCompraAjax'])) {
    $out = array('success' => true, 'rows' => array(), 'message' => '', 'Cop_Num' => '');
    try {
        if (!$mysqli || $Emp_Cod <= 0) {
            throw new Exception('Sin conexion o empresa invalida.');
        }
        $Cop_Cod = isset($_REQUEST['Cop_Cod']) ? (int)$_REQUEST['Cop_Cod'] : 0;
        if ($Cop_Cod <= 0) {
            throw new Exception('Compra no valida.');
        }
        $own = $mysqli->query(
            "SELECT c.Cop_Cod, c.Cop_Num FROM compras c
             INNER JOIN proveedore pv ON pv.Prv_Cod = c.Prv_Cod AND pv.Emp_Cod = $Emp_Cod
             WHERE c.Cop_Cod = $Cop_Cod AND c.Cop_Est = 'A' LIMIT 1"
        );
        if (!$own || !($cab = $own->fetch_assoc())) {
            throw new Exception('Compra no encontrada.');
        }
        $out['Cop_Num'] = $cab['Cop_Num'];
        $res = $mysqli->query(
            "SELECT dc.Cop_Int,
                    IFNULL(it.Ite_Lar, CAST(dc.Pro_Cod AS CHAR)) AS producto,
                    IFNULL(dc.Pld_Cod, 0) AS Pld_Cod,
                    IFNULL(dp.Pld_Cdc, '') AS Pld_Cdc,
                    IFNULL(dp.Pld_Des, '(sin cuenta)') AS Pld_Des,
                    ROUND(IFNULL(dc.Cop_Imp, 0), 2) AS Cop_Imp
             FROM det_compra dc
             LEFT JOIN producto pr ON pr.Pro_Cod = dc.Pro_Cod
             LEFT JOIN item it ON it.Ite_Cod = pr.Ite_Cod
             LEFT JOIN det_plan dp ON dp.Pld_Cod = dc.Pld_Cod
             WHERE dc.Cop_Cod = $Cop_Cod
             ORDER BY dc.Cop_Int"
        );
        if (!$res) {
            throw new Exception('Error al consultar cuentas: ' . $mysqli->error);
        }
        while ($r = $res->fetch_assoc()) {
            $out['rows'][] = $r;
        }
        if (!$out['rows']) {
            $out['message'] = 'La compra no tiene detalle de productos.';
        }
    } catch (Exception $e) {
        $out['success'] = false;
        $out['message'] = $e->getMessage();
    }
    enl_json($out);
}

/* ---------- AJAX: rubros por proyecto ---------- */
if (isset($_REQUEST['presupuestoRubrosAjax'])) {
    $out = array('success' => true, 'response' => array(), 'records' => 0, 'message' => '');
    try {
        if (!$mysqli || $Emp_Cod <= 0) {
            throw new Exception('Sin conexion o empresa invalida.');
        }
        $tblDet = enl_tabla_detalle($mysqli);
        if ($tblDet === '') {
            throw new Exception('No existe tabla de rubros de proyecto.');
        }
        $Pro_Cod = isset($_REQUEST['ppa_Pro_Cod']) ? (int)$_REQUEST['ppa_Pro_Cod'] : (isset($_REQUEST['Pro_Cod']) ? (int)$_REQUEST['Pro_Cod'] : 0);
        $search = isset($_REQUEST['search']) ? trim((string)$_REQUEST['search']) : '';
        $searchCod = '';
        if (isset($_REQUEST['search_cod'])) {
            $searchCod = trim((string)$_REQUEST['search_cod']);
        } elseif (isset($_REQUEST['ppa_search_cod'])) {
            $searchCod = trim((string)$_REQUEST['ppa_search_cod']);
        }
        if ($Pro_Cod <= 0) {
            throw new Exception('Seleccione un proyecto.');
        }

        $sql = "SELECT DISTINCT d.Pdp_Cod, d.Ppa_Cod, d.Pdp_Rubro, d.Pro_Cod,
                       p.Ppa_Cla, p.Ppa_Des, p.Ppa_Pad, p.Ppa_Niv
                FROM `$tblDet` d
                INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                INNER JOIN pre_proyectos pr ON pr.Pro_Cod = d.Pro_Cod AND pr.Emp_Cod = d.Emp_Cod
                WHERE d.Emp_Cod = $Emp_Cod
                  AND d.Pro_Cod = $Pro_Cod
                  AND pr.Pro_Est = 'A'
                  AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'
                ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
        $res = $mysqli->query($sql);
        if (!$res) {
            throw new Exception('Error al consultar rubros: ' . $mysqli->error);
        }
        $rows = array();
        $rutaCache = array();
        while ($r = $res->fetch_assoc()) {
            $ppa = (int)$r['Ppa_Cod'];
            if (!isset($rutaCache[$ppa])) {
                $rutaCache[$ppa] = enl_ruta_partida($mysqli, $ppa);
            }
            $ruta = $rutaCache[$ppa];
            $cla = enl_to_utf8(trim((string)$r['Ppa_Cla']));
            $des = enl_to_utf8(trim((string)$r['Ppa_Des']));
            $ruta = enl_to_utf8($ruta);
            $rubro = enl_to_utf8(trim((string)$r['Pdp_Rubro']));
            if ($rubro === '') {
                $rubro = $des;
            }
            if ($searchCod !== '') {
                if (!enl_contiene_codigo($cla, $searchCod)) {
                    continue;
                }
            }
            if ($search !== '') {
                $hay = enl_contiene($cla, $search)
                    || enl_contiene($des, $search)
                    || enl_contiene($rubro, $search)
                    || enl_contiene($ruta, $search);
                if (!$hay) {
                    continue;
                }
            }
            $rows[] = array(
                'Pdp_Cod' => (int)$r['Pdp_Cod'],
                'Ppa_Cod' => $ppa,
                'Ppa_Cla' => $cla,
                'Ppa_Des' => $des,
                'Pdp_Rubro' => $rubro,
                'Ppa_Ruta' => $ruta,
                'Ppa_Label' => $cla . ' - ' . $rubro,
                'Pro_Cod' => (int)$r['Pro_Cod']
            );
        }
        $out['response'] = $rows;
        $out['records'] = count($rows);
    } catch (Exception $e) {
        $out['success'] = false;
        $out['message'] = $e->getMessage();
    }
    enl_json($out);
}

/* ---------- AJAX: guardar enlace (varias facturas ? un rubro) ---------- */
if (isset($_REQUEST['saveEnlaceAjax'])) {
    $out = array('success' => false, 'message' => '', 'vinculados' => 0, 'facturas' => 0);
    try {
        if (!$mysqli || $Emp_Cod <= 0) {
            throw new Exception('Sin conexion o empresa invalida.');
        }
        if (!enl_tabla_ok($mysqli, 'pre_proyecto_detalle_asiento')) {
            throw new Exception('No existe pre_proyecto_detalle_asiento.');
        }
        $Pdp_Cod = isset($_REQUEST['Pdp_Cod']) ? (int)$_REQUEST['Pdp_Cod'] : 0;
        if ($Pdp_Cod <= 0) {
            throw new Exception('Debe seleccionar un rubro de proyecto.');
        }
        $tblDet = enl_tabla_detalle($mysqli);
        if ($tblDet === '') {
            throw new Exception('No existe tabla de rubros de proyecto.');
        }
        $chk = $mysqli->query(
            "SELECT d.Pdp_Cod FROM `$tblDet` d
             INNER JOIN pre_proyectos pr ON pr.Pro_Cod = d.Pro_Cod AND pr.Emp_Cod = d.Emp_Cod
             WHERE d.Pdp_Cod = $Pdp_Cod AND d.Emp_Cod = $Emp_Cod AND pr.Pro_Est = 'A'
             LIMIT 1"
        );
        if (!$chk || !$chk->fetch_assoc()) {
            throw new Exception('El rubro no pertenece a un proyecto activo de la empresa.');
        }

        $copList = array();
        if (isset($_REQUEST['Cop_Cods']) && is_array($_REQUEST['Cop_Cods'])) {
            foreach ($_REQUEST['Cop_Cods'] as $c) {
                $c = (int)$c;
                if ($c > 0) {
                    $copList[$c] = $c;
                }
            }
        } elseif (isset($_REQUEST['Cop_Cods']) && is_string($_REQUEST['Cop_Cods']) && $_REQUEST['Cop_Cods'] !== '') {
            foreach (explode(',', $_REQUEST['Cop_Cods']) as $c) {
                $c = (int)trim($c);
                if ($c > 0) {
                    $copList[$c] = $c;
                }
            }
        } elseif (isset($_REQUEST['Cop_Cod'])) {
            $c = (int)$_REQUEST['Cop_Cod'];
            if ($c > 0) {
                $copList[$c] = $c;
            }
        }
        if (empty($copList)) {
            throw new Exception('Seleccione al menos una factura.');
        }

        $mysqli->autocommit(false);
        $okAsi = 0;
        $okFac = 0;
        $skipSinAsi = 0;
        $errVinc = '';
        foreach ($copList as $Cop_Cod) {
            $own = $mysqli->query(
                "SELECT c.Cop_Cod FROM compras c
                 INNER JOIN proveedore pv ON pv.Prv_Cod = c.Prv_Cod AND pv.Emp_Cod = $Emp_Cod
                 WHERE c.Cop_Cod = $Cop_Cod AND c.Cop_Est = 'A' LIMIT 1"
            );
            if (!$own || !$own->fetch_assoc()) {
                continue;
            }
            /* Incluye pendientes y ya vinculados: permite reasignar rubro. */
            $asis = enl_asientos_producto($mysqli, $Cop_Cod, false);
            if (empty($asis)) {
                $skipSinAsi++;
                continue;
            }
            $facOk = false;
            foreach ($asis as $Asi_Cod) {
                if (enl_vincular($mysqli, $Pdp_Cod, $Asi_Cod)) {
                    $okAsi++;
                    $facOk = true;
                    @$mysqli->query(
                        "UPDATE det_compra dc
                         INNER JOIN asientos a ON a.Asi_Cod = $Asi_Cod AND a.Pld_Cod = dc.Pld_Cod
                         SET dc.Asi_Cod = $Asi_Cod
                         WHERE dc.Cop_Cod = $Cop_Cod AND IFNULL(dc.Asi_Cod,0) = 0
                         LIMIT 1"
                    );
                } elseif ($errVinc === '') {
                    $errVinc = $mysqli->error ? $mysqli->error : 'fallo al insertar/actualizar en pre_proyecto_detalle_asiento';
                }
            }
            if ($facOk) {
                $okFac++;
            }
        }
        if ($okAsi <= 0) {
            $mysqli->rollback();
            $mysqli->autocommit(true);
            if ($errVinc !== '') {
                throw new Exception('No se pudo vincular el asiento al rubro: ' . $errVinc);
            }
            throw new Exception('Las facturas seleccionadas no tienen asientos de productos (cuenta Pld_Cod del detalle) para asignar o modificar el rubro.');
        }
        $mysqli->commit();
        $mysqli->autocommit(true);
        $out['success'] = true;
        $out['vinculados'] = $okAsi;
        $out['facturas'] = $okFac;
        $out['message'] = 'Guardadas ' . $okFac . ' factura(s): ' . $okAsi . ' asiento(s) asignados/actualizados al rubro.';
    } catch (Exception $e) {
        if ($mysqli) {
            @$mysqli->rollback();
            @$mysqli->autocommit(true);
        }
        $out['success'] = false;
        $out['message'] = $e->getMessage();
    }
    enl_json($out);
}

/* ---------- Export Excel: rubros con compras relacionadas ---------- */
if (isset($_REQUEST['exportRubrosComprasExcel'])) {
    try {
        if (!$mysqli || $Emp_Cod <= 0) {
            throw new Exception('Sin conexion o empresa invalida.');
        }
        if (!enl_tabla_ok($mysqli, 'pre_proyecto_detalle_asiento')) {
            throw new Exception('No existe pre_proyecto_detalle_asiento.');
        }
        $tblDet = enl_tabla_detalle($mysqli);
        if ($tblDet === '') {
            throw new Exception('No existe tabla de rubros de proyecto.');
        }
        $Fec_Ini = enl_fecha(isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '', $anioIni);
        $Fec_Fin = enl_fecha(isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '', $mesFinDef);
        if ($Fec_Ini > $Fec_Fin) {
            $tmp = $Fec_Ini;
            $Fec_Ini = $Fec_Fin;
            $Fec_Fin = $tmp;
        }
        $Pro_Cod = isset($_REQUEST['Pro_Cod']) ? (int)$_REQUEST['Pro_Cod'] : 0;
        $condPro = $Pro_Cod > 0 ? " AND d.Pro_Cod = $Pro_Cod" : '';
        $proNom = 'Todos los proyectos';
        if ($Pro_Cod > 0) {
            $qp = @$mysqli->query(
                "SELECT CONCAT(IFNULL(Pro_Ide,''),' - ',IFNULL(Pro_Nom,'')) AS lbl
                 FROM pre_proyectos WHERE Pro_Cod = $Pro_Cod AND Emp_Cod = $Emp_Cod LIMIT 1"
            );
            if ($qp && ($pr = $qp->fetch_assoc())) {
                $proNom = enl_to_utf8(trim((string)$pr['lbl']));
            }
        }

        $totExpr = "ROUND((
            SELECT SUM(
              (
                IFNULL(dc4.Cop_Imp,0)
                - (IFNULL(dc4.Cop_Imp,0) * IFNULL(c.Cop_Des,0) / 100)
                - (IFNULL(dc4.Cop_Imp,0) * IFNULL(dc4.Cop_Dec,0) / 100)
              ) * (1 + IFNULL(iva.Iva_Por,0) / 100)
            )
            FROM det_compra dc4
            LEFT JOIN iva ON iva.Iva_Cod = dc4.Iva_Cod
            WHERE dc4.Cop_Cod = c.Cop_Cod
        ), 2)";

        $sql = "SELECT
                    d.Pdp_Cod,
                    MAX(p.Ppa_Cla) AS Ppa_Cla,
                    MAX(IFNULL(NULLIF(TRIM(d.Pdp_Rubro), ''), p.Ppa_Des)) AS Pdp_Rubro,
                    MAX(IFNULL(pr.Pro_Ide, '')) AS Pro_Ide,
                    MAX(IFNULL(pr.Pro_Nom, '')) AS Pro_Nom,
                    c.Cop_Cod,
                    MAX(c.Cop_Num) AS Cop_Num,
                    MAX(c.Cop_Fec) AS Cop_Fec,
                    MAX(CONCAT(IFNULL(prs.Prs_Ape,''),' ',IFNULL(prs.Prs_Nom,''))) AS proveedor,
                    MAX($totExpr) AS Cop_Tot
                FROM pre_proyecto_detalle_asiento pda
                INNER JOIN `$tblDet` d ON d.Pdp_Cod = pda.Pdp_Cod AND d.Emp_Cod = $Emp_Cod
                INNER JOIN pre_proyectos pr ON pr.Pro_Cod = d.Pro_Cod AND pr.Emp_Cod = d.Emp_Cod AND pr.Pro_Est = 'A'
                LEFT JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                INNER JOIN asientos a ON a.Asi_Cod = pda.Asi_Cod AND a.Asi_Deh = 'D'
                INNER JOIN compr_auto ca ON ca.Com_Cod = a.Com_Cod
                INNER JOIN compras c ON c.Cop_Cod = ca.Cop_Cod AND c.Cop_Est = 'A'
                INNER JOIN proveedore pv ON pv.Prv_Cod = c.Prv_Cod AND pv.Emp_Cod = $Emp_Cod
                INNER JOIN persona prs ON prs.Prs_Cod = pv.Prs_Cod
                WHERE c.Cop_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                  $condPro
                GROUP BY d.Pdp_Cod, c.Cop_Cod
                ORDER BY Ppa_Cla, Pdp_Rubro, Cop_Fec, Cop_Num";
        $res = $mysqli->query($sql);
        if (!$res) {
            throw new Exception('Error al consultar: ' . $mysqli->error);
        }

        $grupos = array();
        $totalGeneral = 0.0;
        $nCompras = 0;
        while ($r = $res->fetch_assoc()) {
            $pdp = (int)$r['Pdp_Cod'];
            if (!isset($grupos[$pdp])) {
                $grupos[$pdp] = array(
                    'cla' => enl_to_utf8(trim((string)$r['Ppa_Cla'])),
                    'rubro' => enl_to_utf8(trim((string)$r['Pdp_Rubro'])),
                    'proy' => enl_to_utf8(trim($r['Pro_Ide'] . ' - ' . $r['Pro_Nom'])),
                    'items' => array(),
                    'subtotal' => 0.0
                );
            }
            $val = (float)$r['Cop_Tot'];
            $grupos[$pdp]['items'][] = array(
                'num' => enl_to_utf8(trim((string)$r['Cop_Num'])),
                'fec' => $r['Cop_Fec'],
                'prv' => enl_to_utf8(trim((string)$r['proveedor'])),
                'val' => $val
            );
            $grupos[$pdp]['subtotal'] += $val;
            $totalGeneral += $val;
            $nCompras++;
        }

        $fname = 'rubros_compras_' . $Fec_Ini . '_' . $Fec_Fin . '.xls';
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"/>';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>Rubros-Compras</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>
            body{font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#222;}
            h2{color:#254463;margin:0 0 6px;}
            .meta{color:#555;margin:0 0 14px;font-size:10pt;}
            table{border-collapse:collapse;width:100%;margin:0 0 16px;}
            th{background:#d9e6f2;color:#2f4a63;border:1px solid #b8c9d9;padding:6px 8px;text-align:left;}
            td{border:1px solid #d0dae4;padding:5px 8px;vertical-align:top;}
            .rubro-h{background:#254463;color:#fff;font-weight:700;padding:8px;}
            .num{text-align:right;mso-number-format:"\#\,\#\#0\.00";}
            .sub{background:#eef8f1;font-weight:700;}
            .tot{background:#fff3cd;font-weight:700;}
        </style></head><body>';
        echo '<h2>Listado de rubros con compras relacionadas</h2>';
        echo '<p class="meta">Proyecto: <b>' . htmlspecialchars($proNom, ENT_QUOTES, 'UTF-8') . '</b>'
            . ' &nbsp;|&nbsp; Periodo: <b>' . htmlspecialchars($Fec_Ini, ENT_QUOTES, 'UTF-8') . '</b> a <b>'
            . htmlspecialchars($Fec_Fin, ENT_QUOTES, 'UTF-8') . '</b>'
            . ' &nbsp;|&nbsp; Rubros: ' . count($grupos)
            . ' &nbsp;|&nbsp; Compras: ' . $nCompras
            . ' &nbsp;|&nbsp; Generado: ' . date('Y-m-d H:i') . '</p>';

        if (empty($grupos)) {
            echo '<p>No hay compras relacionadas a rubros en el periodo seleccionado.</p>';
        } else {
            foreach ($grupos as $g) {
                $tit = trim($g['cla'] . ' - ' . $g['rubro']);
                echo '<table>';
                echo '<tr><td class="rubro-h" colspan="4">'
                    . htmlspecialchars($tit, ENT_QUOTES, 'UTF-8')
                    . ' &nbsp;<span style="font-weight:400;opacity:.9;">('
                    . htmlspecialchars($g['proy'], ENT_QUOTES, 'UTF-8') . ')</span></td></tr>';
                echo '<tr><th style="width:18%;">Numero</th><th style="width:12%;">Fecha</th>'
                    . '<th>Proveedor</th><th style="width:14%;">Valor</th></tr>';
                foreach ($g['items'] as $it) {
                    echo '<tr>'
                        . '<td>' . htmlspecialchars($it['num'], ENT_QUOTES, 'UTF-8') . '</td>'
                        . '<td>' . htmlspecialchars($it['fec'], ENT_QUOTES, 'UTF-8') . '</td>'
                        . '<td>' . htmlspecialchars($it['prv'], ENT_QUOTES, 'UTF-8') . '</td>'
                        . '<td class="num">' . number_format($it['val'], 2, '.', ',') . '</td>'
                        . '</tr>';
                }
                echo '<tr class="sub"><td colspan="3" align="right">Subtotal rubro</td>'
                    . '<td class="num">' . number_format($g['subtotal'], 2, '.', ',') . '</td></tr>';
                echo '</table>';
            }
            echo '<table><tr class="tot"><td align="right">TOTAL GENERAL</td>'
                . '<td class="num" style="width:14%;">' . number_format($totalGeneral, 2, '.', ',') . '</td></tr></table>';
        }
        echo '</body></html>';
        exit;
    } catch (Exception $e) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
        echo '<p style="color:#c0392b;font-family:Arial;">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><a href="javascript:window.close()">Cerrar</a></p></body></html>';
        exit;
    }
}

/* ---------- Catalogos UI ---------- */
$ppa_proyectos = array();
$enl_sucursales = array();
if ($mysqli && $Emp_Cod > 0) {
    $q = @$mysqli->query(
        "SELECT Pro_Cod, Pro_Ide, Pro_Nom FROM pre_proyectos
         WHERE Emp_Cod = $Emp_Cod AND Pro_Est = 'A'
         ORDER BY Pro_Ide, Pro_Nom"
    );
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $ppa_proyectos[] = $row;
        }
    }
    $qs = @$mysqli->query(
        "SELECT Suc_Cod, Suc_Des, Suc_Sri FROM sucursal
         WHERE Emp_Cod = $Emp_Cod AND Suc_Est = 'A'
         ORDER BY Suc_Des"
    );
    if ($qs) {
        while ($row = $qs->fetch_assoc()) {
            $enl_sucursales[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enlace Compra a Rubro [EXA]</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <style>
        .enl-wrap { padding: 0 2px 6px; }
        .enl-card {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 4px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .enl-card-h {
            background: linear-gradient(180deg, #f7fafc 0%, #eef3f8 100%);
            border-bottom: 1px solid #d9e2ec;
            color: #254463;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 6px 10px;
            margin: 0;
        }
        .enl-card-b { padding: 8px 10px; }
        .enl-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }
        .enl-bar .form-control {
            height: 28px;
            padding: 3px 8px;
            font-size: 12px;
            border-color: #c5d0db;
            border-radius: 3px;
            box-shadow: none;
        }
        .enl-bar .form-control:focus {
            border-color: #254463;
            box-shadow: 0 0 0 2px rgba(37, 68, 99, 0.12);
        }
        .enl-bar .btn {
            height: 28px;
            padding: 3px 10px;
            font-size: 12px;
            line-height: 1.4;
            border-radius: 3px;
        }
        .enl-sep {
            width: 1px;
            height: 22px;
            background: #d0dae4;
            margin: 0 2px;
        }
        .enl-lbl {
            color: #5a6f82;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .enl-dates { display: inline-flex; align-items: center; gap: 4px; }
        .enl-dates .enl-a { color: #8a9bab; font-size: 11px; }
        .enl-chk {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            font-size: 11px;
            font-weight: 600;
            color: #3d5a73;
            white-space: nowrap;
            cursor: pointer;
        }
        .enl-chk input { margin: 0; vertical-align: middle; }
        .enl-rubro-row {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 8px;
        }
        .enl-rubro-main {
            flex: 1 1 320px;
            min-width: 240px;
        }
        .enl-rubro-main .input-group-addon {
            background: #254463;
            color: #fff;
            border-color: #254463;
            font-size: 11px;
            font-weight: 600;
            min-width: 58px;
        }
        .enl-rubro-main .form-control[readonly] {
            background: #f8fafc;
            cursor: default;
            font-weight: 600;
            color: #254463;
        }
        .enl-rubro-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .enl-ruta {
            display: block;
            margin-top: 6px;
            padding: 6px 10px;
            background: #f4f7fa;
            border-left: 3px solid #254463;
            border-radius: 0 3px 3px 0;
            color: #000;
            font-size: 12px;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .enl-ruta:empty { display: none; }
        .enl-ruta .sep { color: #9aa8b5; margin: 0 8px; font-weight: 400; font-size: 11px; }
        .enl-ruta .nodo { color: #000; }
        .enl-ruta .hoja { color: #c0392b; font-weight: 700; }
        .enl-status {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 0 0 8px;
            padding: 5px 10px;
            background: #f7fafc;
            border: 1px solid #e3ebf2;
            border-radius: 3px;
            font-size: 11px;
            color: #5a6f82;
        }
        .enl-status #lblResultado { font-weight: 700; color: #254463; }
        .enl-status #lblSeleccion { color: #3d5a73; }
        .enl-legend-ico { margin-left: auto; color: #6b7c8c; }
        .enl-ico-clasif { color: #2e7d4f; font-size: 15px; }
        .enl-ico-vacio { color: #c5ced6; font-size: 14px; }
        #gridPendientes tr.enl-row-rel td { background-color: #eef8f1 !important; }
        #gridPendientes tr.enl-row-rel td[aria-describedby$="_rubro_rel"] { text-align: center; }
        #ppaDialog .lbl-compra { font-weight: 700; color: #254463; }
        #ppaDialog .ppa-filtros { margin: 0 0 6px; }
        #ppaDialog .ppa-hint { display: block; margin: 4px 0; color: #8b79a8; font-size: 11px; }
        #ppaDialog td.ppa-arbol { white-space: normal !important; padding-top: 3px !important; padding-bottom: 3px !important; vertical-align: top !important; }
        .exa-arbol { display: block; line-height: 1.25; }
        .exa-arbol .n { display: block; color: #000; font-size: 10px; line-height: 1.4; }
        .exa-arbol .n.pad { color: #000; font-weight: 600; }
        .exa-arbol .n .cn { color: #666; margin-right: 1px; }
        .exa-arbol .n.hoja { color: #c0392b !important; font-weight: 700; font-size: 11px; margin-top: 1px; }
        .exa-sinpadre { color: #b3bfc9; font-size: 10px; font-style: italic; }
        .enl-hint { color: #666; font-size: 10px; margin: 0; }
        .enl-cuentas-tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
        .enl-cuentas-tbl th {
            background: #eef3f8;
            color: #254463;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #d0dae4;
            font-size: 11px;
        }
        .enl-cuentas-tbl td {
            padding: 5px 8px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: top;
        }
        .enl-cuentas-tbl .num { text-align: right; white-space: nowrap; }
        .enl-cuentas-tbl .cdc { font-family: Consolas, monospace; color: #3d5a73; white-space: nowrap; }
        .enl-cuentas-vacio { color: #8a9bab; padding: 16px; text-align: center; }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title">&raquo; Enlazar facturas de compra a rubro de presupuesto</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
    <div class="enl-wrap">
        <div class="enl-card">
            <div class="enl-card-h">1. Buscar facturas</div>
            <div class="enl-card-b">
                <form id="frmFiltro" class="enl-bar" onsubmit="return false;">
                    <span class="enl-lbl">Periodo</span>
                    <select id="filtro_anio" class="form-control input-sm" style="width:74px;" title="Anio" onchange="aplicarMesFiltro(false)"></select>
                    <select id="filtro_mes" class="form-control input-sm" style="width:110px;" title="Mes" onchange="aplicarMesFiltro(false)">
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                    <span class="enl-sep"></span>
                    <span class="enl-dates">
                        <input type="text" id="Fec_Ini" class="form-control input-sm" title="Desde" style="width:98px;" />
                        <span class="enl-a">?</span>
                        <input type="text" id="Fec_Fin" class="form-control input-sm" title="Hasta" style="width:98px;" />
                    </span>
                    <span class="enl-sep"></span>
                    <span class="enl-lbl">Sucursal</span>
                    <select id="filtro_suc" class="form-control input-sm" style="width:160px;" title="Sucursal">
                        <option value="0">Todas</option>
                        <?php
                        foreach ($enl_sucursales as $sucRow) {
                            $sid = (int)$sucRow['Suc_Cod'];
                            $slbl = trim($sucRow['Suc_Sri'] . ' - ' . $sucRow['Suc_Des']);
                            $sel = ($Suc_Cod_Ses > 0 && $sid === $Suc_Cod_Ses) ? ' selected' : '';
                            echo '<option value="' . $sid . '"' . $sel . '>' . htmlspecialchars($slbl, ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                        ?>
                    </select>
                    <span class="enl-sep"></span>
                    <select id="filtro_tipo" class="form-control input-sm" style="width:120px;" title="Tipo filtro" onchange="actualizarPlaceholderFiltro()">
                        <option value="ruc">RUC / CI</option>
                        <option value="razon">Razon social</option>
                        <option value="numero">Nro. factura</option>
                        <option value="obs">Observacion</option>
                    </select>
                    <input type="text" id="filtro_valor" class="form-control input-sm" placeholder="RUC o cedula..." style="width:160px;flex:1;min-width:120px;max-width:220px;"
                           onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();cargarPendientes();}" />
                    <label class="enl-chk" title="Incluir facturas que ya tienen asientos de producto vinculados a presupuesto">
                        <input type="checkbox" id="incluir_relacionadas" value="1" />
                        Ya relacionadas
                    </label>
                    <button type="button" class="btn btn-sm btn-primary" onclick="cargarPendientes()" title="Buscar">
                        <i class="glyphicon glyphicon-search"></i> Buscar
                    </button>
                </form>
            </div>
        </div>

        <div class="enl-card">
            <div class="enl-card-h">2. Rubro de presupuesto</div>
            <div class="enl-card-b">
                <div class="enl-rubro-row">
                    <div class="enl-rubro-main">
                        <div class="input-group input-group-sm" style="width:100%;">
                            <span class="input-group-addon">Rubro</span>
                            <input type="text" id="sel_Ppa_Label" class="form-control" readonly placeholder="Ningun rubro seleccionado..." />
                            <input type="hidden" id="sel_Pdp_Cod" value="" />
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="abrirAsignarRubro()" title="Elegir rubro">
                                    <i class="glyphicon glyphicon-search"></i> Elegir
                                </button>
                                <button type="button" class="btn btn-danger" onclick="limpiarRubroSel()" title="Quitar rubro">
                                    <span class="glyphicon glyphicon-remove"></span>
                                </button>
                            </span>
                        </div>
                        <div id="sel_Ppa_Ruta_Txt" class="enl-ruta"></div>
                    </div>
                    <div class="enl-rubro-actions">
                        <select id="prn_Pro_Cod" class="form-control input-sm" style="width:210px;max-width:100%;" title="Proyecto para imprimir rubros">
                            <option value="">Proyecto (imprimir)...</option>
                            <?php foreach ($ppa_proyectos as $proyPpa) {
                                $lblPpa = trim($proyPpa['Pro_Ide'] . ' - ' . $proyPpa['Pro_Nom']);
                                $lblPpaEsc = htmlspecialchars($lblPpa, ENT_QUOTES, 'UTF-8');
                                echo '<option value="' . (int)$proyPpa['Pro_Cod'] . '" title="' . $lblPpaEsc . '">' . $lblPpaEsc . '</option>';
                            } ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-default" onclick="imprimirRubros()" title="Imprimir todos los rubros del proyecto">
                            <i class="glyphicon glyphicon-print"></i> Imprimir rubros
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="abrirReporteRubrosCompras()" title="Listado rubros con compras a Excel">
                            <i class="glyphicon glyphicon-download-alt"></i> Excel rubros/compras
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="guardarEnlace()" title="Asignar o modificar rubro de las facturas marcadas">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Guardar / modificar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="enl-status">
            <span id="lblResultado"></span>
            <span id="lblSeleccion">Ninguna factura seleccionada</span>
            <span class="enl-legend-ico"><i class="glyphicon glyphicon-ok-sign enl-ico-clasif"></i> Ya relacionada (tooltip = rubro)</span>
        </div>
    </div>
<table id="gridPendientes"></table>
        <div id="pagerPendientes"></div>
    </div>
</div>

<div id="ppaDialog" title="Rubro de presupuesto (proyecto)" style="display:none;">
    <div style="margin-bottom:8px;font-size:12px;color:#555;">
        <span class="lbl-compra" id="det_titulo">?</span>
    </div>
    <div class="ppa-filtros">
        <div class="input-group input-group-xs" style="width:100%;margin-bottom:4px;">
            <span class="input-group-addon">Proyecto</span>
            <select id="ppa_Pro_Cod" class="form-control input-xs">
                <option value="">Seleccione proyecto...</option>
                <?php foreach ($ppa_proyectos as $proyPpa) {
                    $lblPpa = trim($proyPpa['Pro_Ide'] . ' - ' . $proyPpa['Pro_Nom']);
                    $lblPpaEsc = htmlspecialchars($lblPpa, ENT_QUOTES, 'UTF-8');
                    echo '<option value="' . (int)$proyPpa['Pro_Cod'] . '" title="' . $lblPpaEsc . '">' . $lblPpaEsc . '</option>';
                } ?>
            </select>
        </div>
        <div class="ppa-filtros-row" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px;">
            <div class="input-group input-group-xs" style="flex:0 1 160px;min-width:120px;">
                <span class="input-group-addon">Codigo</span>
                <input id="ppa_search_cod" type="text" maxlength="40" placeholder="Ej. 02.01.01" class="form-control input-xs"
                       onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();buscarPresupuesto();return false;}" />
            </div>
            <div class="input-group input-group-xs" style="flex:1 1 200px;min-width:160px;">
                <span class="input-group-addon">Texto</span>
                <input id="ppa_search" type="text" maxlength="80" placeholder="Rubro o ruta..." class="form-control input-xs"
                       onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();buscarPresupuesto();return false;}" />
                <span class="input-group-btn">
                    <button type="button" onclick="buscarPresupuesto()" class="btn btn-success btn-xs" title="Filtrar rubros">
                        <span class="glyphicon glyphicon-filter"></span>
                    </button>
                </span>
            </div>
        </div>
        <small class="ppa-hint" id="ppa_hint">Seleccione proyecto, filtre por codigo y/o texto, y elija el rubro.</small>
    </div>
    <table id="containerPpa"></table>
</div>

<div id="cuentasDialog" title="Cuentas contables de la compra" style="display:none;">
    <div style="margin-bottom:6px;font-size:12px;color:#555;">
        <b id="cuentas_titulo">Detalle</b>
    </div>
    <div id="cuentas_body" style="max-height:360px;overflow:auto;"></div>
</div>

<div id="repRubrosDialog" title="Excel: rubros con compras" style="display:none;">
    <p style="margin:0 0 10px;font-size:12px;color:#555;">
        Indique el rango de fechas. Se listaran los rubros con sus compras relacionadas (Numero, Fecha, Proveedor, Valor).
    </p>
    <div class="input-group input-group-sm" style="width:100%;margin-bottom:8px;">
        <span class="input-group-addon">Proyecto</span>
        <select id="rep_Pro_Cod" class="form-control input-sm">
            <option value="0">Todos los proyectos</option>
            <?php foreach ($ppa_proyectos as $proyPpa) {
                $lblPpa = trim($proyPpa['Pro_Ide'] . ' - ' . $proyPpa['Pro_Nom']);
                $lblPpaEsc = htmlspecialchars($lblPpa, ENT_QUOTES, 'UTF-8');
                echo '<option value="' . (int)$proyPpa['Pro_Cod'] . '">' . $lblPpaEsc . '</option>';
            } ?>
        </select>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <div class="input-group input-group-sm" style="flex:1;min-width:140px;">
            <span class="input-group-addon">Desde</span>
            <input type="text" id="rep_Fec_Ini" class="form-control input-sm" />
        </div>
        <div class="input-group input-group-sm" style="flex:1;min-width:140px;">
            <span class="input-group-addon">Hasta</span>
            <input type="text" id="rep_Fec_Fin" class="form-control input-sm" />
        </div>
    </div>
    <div style="text-align:right;">
        <button type="button" class="btn btn-sm btn-default" onclick="$('#repRubrosDialog').dialog('close')">Cancelar</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="exportarRubrosComprasExcel()">
            <i class="glyphicon glyphicon-download-alt"></i> Exportar Excel
        </button>
    </div>
</div>

<script type="text/javascript">
(function () {
    var gridPend;
    var rubroSel = null;

    function esc(t) {
        return $('<i/>').text(t == null ? '' : t).html();
    }

    function toIsoDate(val) {
        val = $.trim(val || '');
        if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
        var m = val.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
        if (m) {
            var d = ('0' + m[1]).slice(-2), mo = ('0' + m[2]).slice(-2), y = m[3];
            if (y.length === 2) y = (parseInt(y, 10) > 50 ? '19' : '20') + y;
            return y + '-' + mo + '-' + d;
        }
        return val;
    }

    function rutaArbol(ruta) {
        var txt = $.trim(ruta || ''), html = '';
        if (txt === '') {
            return '<span class="exa-sinpadre">? sin jerarquia ?</span>';
        }
        var niveles = txt.split('>');
        if (!niveles.length) {
            return '<span class="exa-sinpadre">? sin jerarquia ?</span>';
        }
        $.each(niveles, function (i, p) {
            var esHoja = (i === niveles.length - 1);
            var esPadreDirecto = (!esHoja && i === niveles.length - 2);
            html += '<span class="n' + (esHoja ? ' hoja' : (esPadreDirecto ? ' pad' : '')) + '" style="padding-left:' + (i * 12) + 'px">'
                + (i > 0 ? '<span class="cn">\u2514 </span>' : '')
                + esc($.trim(p)) + '</span>';
        });
        return '<span class="exa-arbol" title="' + esc(txt) + '">' + html + '</span>';
    }

    /** Ruta en una sola linea para la seccion de rubro seleccionado. */
    function rutaLinea(ruta) {
        var txt = $.trim(ruta || '');
        if (txt === '') {
            return '';
        }
        var niveles = txt.split('>');
        var partes = [];
        $.each(niveles, function (i, p) {
            var t = $.trim(p);
            if (!t) return;
            var esHoja = (i === niveles.length - 1);
            if (partes.length) {
                partes.push('<span class="sep">/</span>');
            }
            partes.push('<span class="' + (esHoja ? 'hoja' : 'nodo') + '">' + esc(t) + '</span>');
        });
        return '<span title="' + esc(txt) + '">' + partes.join('') + '</span>';
    }

    function actualizarLblSeleccion() {
        var n = (gridPend.jqGrid('getGridParam', 'selarrrow') || []).length;
        $('#lblSeleccion').text(n ? (n + ' factura(s) seleccionada(s)') : 'Ninguna factura seleccionada');
    }

    function facturasMarcadas() {
        var sel = gridPend.jqGrid('getGridParam', 'selarrrow') || [];
        var list = [];
        $.each(sel, function (_, id) {
            var d = gridPend.jqGrid('getLocalRow', id) || gridPend.jqGrid('getRowData', id) || {};
            var cop = parseInt(d.Cop_Cod || id, 10) || 0;
            if (cop > 0) list.push(cop);
        });
        return list;
    }

    window.limpiarRubroSel = function () {
        rubroSel = null;
        $('#sel_Pdp_Cod').val('');
        $('#sel_Ppa_Label').val('');
        $('#sel_Ppa_Ruta_Txt').empty();
    };

    window.actualizarPlaceholderFiltro = function () {
        var placeholders = {
            ruc: 'RUC o cedula...',
            razon: 'Proveedor...',
            numero: 'Numero factura...',
            obs: 'Observacion...'
        };
        var t = $('#filtro_tipo').val() || 'ruc';
        $('#filtro_valor').attr('placeholder', placeholders[t] || 'Buscar...');
    };

    function enlLoader(show) {
        var $l = $('#loader');
        if (!$l.length) return;
        if (show) {
            $l.show();
        } else {
            $l.fadeOut('slow');
        }
    }

    function aplicarColumnaRelacionadas(conRel, rows) {
        if (!gridPend || !gridPend[0] || !gridPend[0].grid) {
            return;
        }
        try {
            if (conRel) {
                gridPend.jqGrid('showCol', 'rubro_rel');
            } else {
                gridPend.jqGrid('hideCol', 'rubro_rel');
            }
        } catch (e) {}
        gridPend.find('tr.jqgrow').removeClass('enl-row-rel');
        if (!conRel || !rows || !rows.length) {
            return;
        }
        $.each(rows, function (_, row) {
            var n = parseInt(row.vinculados, 10) || 0;
            var id = String(row.Cop_Cod || '');
            var label = $.trim(row.rubro_rel || '');
            if (!id) {
                return;
            }
            try {
                gridPend.jqGrid('setCell', id, 'rubro_rel', label);
            } catch (eSet) {}
            if (n > 0 || label) {
                try {
                    gridPend.find('#' + $.jgrid.jqID(id)).addClass('enl-row-rel');
                } catch (e2) {
                    gridPend.find('tr#' + id).addClass('enl-row-rel');
                }
            }
        });
    }

    window.cargarPendientes = function () {
        var fi = toIsoDate($('#Fec_Ini').val());
        var ff = toIsoDate($('#Fec_Fin').val());
        $('#Fec_Ini').val(fi);
        $('#Fec_Fin').val(ff);
        enlLoader(true);
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'json',
            data: {
                listPendientesAjax: 1,
                Fec_Ini: fi,
                Fec_Fin: ff,
                filtro_tipo: $('#filtro_tipo').val() || 'ruc',
                filtro_valor: $.trim($('#filtro_valor').val() || ''),
                filtro_suc: $('#filtro_suc').val() || 0,
                incluir_relacionadas: $('#incluir_relacionadas').is(':checked') ? 1 : 0
            },
            success: function (r) {
                if (!r || r.success === false) {
                    $.alert((r && r.message) ? r.message : 'No se pudo cargar el listado.');
                    return;
                }
                try {
                    gridPend.jqGrid('setGridParam', { rowNum: 9999, page: 1 });
                } catch (eParam) {}
                gridPend.clearGrid().setRows(r.rows || []);
                actualizarLblSeleccion();
                var conRel = $('#incluir_relacionadas').is(':checked');
                aplicarColumnaRelacionadas(conRel, r.rows || []);
                if (!(r.rows && r.rows.length) && r.message) {
                    $('#lblResultado').text(r.message);
                } else {
                    $('#lblResultado').text((r.records || 0) + (conRel ? ' compra(s) encontrada(s).' : ' compra(s) pendiente(s).'));
                }
            },
            error: function () {
                $.alert('Error de comunicacion al buscar compras.');
            },
            complete: function () {
                enlLoader(false);
            }
        });
    };

    window.abrirAsignarRubro = function () {
        var n = facturasMarcadas().length;
        $('#det_titulo').text(n ? (n + ' factura(s) seleccionada(s)') : 'Elija un rubro');
        abrirModalRubro();
    };

    window.verCuentasCompra = function (data) {
        data = data || {};
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { data = {}; }
        }
        var cop = parseInt(data.Cop_Cod, 10) || 0;
        var num = data.Cop_Num || '';
        if (cop <= 0 && gridPend) {
            var id = gridPend.jqGrid('getGridParam', 'selrow');
            if (id) {
                var row = gridPend.jqGrid('getLocalRow', id) || gridPend.jqGrid('getRowData', id) || {};
                cop = parseInt(row.Cop_Cod || id, 10) || 0;
                num = row.Cop_Num || num;
            }
        }
        if (cop <= 0) {
            $.alert('No se identifico la compra.');
            return;
        }
        var $dlg = $('#cuentasDialog');
        if (!$dlg.data('ui-dialog')) {
            $dlg.dialog({
                autoOpen: false,
                modal: true,
                width: 720,
                height: 440,
                resizable: true,
                open: function () {
                    $dlg.find('#cuentas_body').css({ 'min-height': '300px', 'overflow': 'auto' });
                }
            });
        }
        $dlg.find('#cuentas_titulo').text('Doc. ' + (num || cop));
        $dlg.find('#cuentas_body').html('<div class="enl-cuentas-vacio">Cargando...</div>');
        $dlg.dialog('open');
        enlLoader(true);
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'json',
            data: { cuentasCompraAjax: 1, Cop_Cod: cop },
            success: function (r) {
                var $body = $dlg.find('#cuentas_body');
                if (!r || r.success === false) {
                    $body.html('<div class="enl-cuentas-vacio">' + esc((r && r.message) || 'No se pudo cargar.') + '</div>');
                    return;
                }
                if (r.Cop_Num) {
                    $dlg.find('#cuentas_titulo').text('Doc. ' + r.Cop_Num + ' (Id ' + cop + ')');
                }
                var rows = r.rows || [];
                if (!rows.length) {
                    $body.html('<div class="enl-cuentas-vacio">' + esc(r.message || 'Sin productos.') + '</div>');
                    return;
                }
                var html = '<table class="enl-cuentas-tbl"><thead><tr>'
                    + '<th style="width:36px;">#</th>'
                    + '<th>Producto</th>'
                    + '<th style="width:110px;">Codigo</th>'
                    + '<th>Cuenta contable</th>'
                    + '<th style="width:90px;" class="num">Importe</th>'
                    + '</tr></thead><tbody>';
                $.each(rows, function (_, row) {
                    var pld = parseInt(row.Pld_Cod, 10) || 0;
                    html += '<tr>'
                        + '<td>' + esc(row.Cop_Int) + '</td>'
                        + '<td>' + esc(row.producto) + '</td>'
                        + '<td class="cdc">' + esc(pld > 0 ? (row.Pld_Cdc || pld) : '-') + '</td>'
                        + '<td>' + esc(row.Pld_Des) + (pld > 0 ? '' : ' <span style="color:#c0392b;">(sin Pld_Cod)</span>') + '</td>'
                        + '<td class="num">' + (parseFloat(row.Cop_Imp || 0).toFixed(2)) + '</td>'
                        + '</tr>';
                });
                html += '</tbody></table>';
                $body.html(html);
            },
            error: function (xhr) {
                var msg = 'Error de comunicacion.';
                if (xhr && xhr.responseText && xhr.responseText.length < 200) {
                    msg += ' ' + xhr.responseText;
                }
                $dlg.find('#cuentas_body').html('<div class="enl-cuentas-vacio">' + esc(msg) + '</div>');
            },
            complete: function () {
                enlLoader(false);
            }
        });
    };

    /** Solo deja el rubro en pantalla principal (sin confirmar ni guardar). */
    window.selectPresupuestoRubro = function (data) {
        data = data || {};
        var pdp = parseInt(data.Pdp_Cod, 10) || 0;
        if (pdp <= 0) {
            $.alert('Seleccione un rubro de proyecto (Pdp_Cod).');
            return;
        }
        var label = data.Ppa_Label || ((data.Ppa_Cla || '') + ' - ' + (data.Pdp_Rubro || data.Ppa_Des || ''));
        rubroSel = {
            Pdp_Cod: pdp,
            Ppa_Cod: data.Ppa_Cod || '',
            Ppa_Label: $.trim(label),
            Ppa_Ruta: data.Ppa_Ruta || ''
        };
        $('#sel_Pdp_Cod').val(pdp);
        $('#sel_Ppa_Label').val(rubroSel.Ppa_Label);
        $('#sel_Ppa_Ruta_Txt').html(rutaLinea(rubroSel.Ppa_Ruta));
        if ($('#ppaDialog').data('ui-dialog')) {
            $('#ppaDialog').dialog('close');
        }
    };

    /** Guardado en pantalla principal. */
    window.guardarEnlace = function () {
        var pdp = parseInt($('#sel_Pdp_Cod').val(), 10) || (rubroSel && rubroSel.Pdp_Cod) || 0;
        if (pdp <= 0) {
            $.alert('Elija un rubro con el boton <b>Elegir rubro</b>.');
            return;
        }
        var copCods = facturasMarcadas();
        if (!copCods.length) {
            $.alert('Marque al menos una factura con el check.');
            return;
        }
        $.saveDataJson('', {
            saveEnlaceAjax: 1,
            Pdp_Cod: pdp,
            Cop_Cods: copCods
        }, function (r) {
            if (!r || !r.success) {
                $.alert((r && r.message) ? r.message : 'No se pudo guardar.');
                return false;
            }
            $.alert(r.message || 'Enlace guardado.');
            cargarPendientes();
            return false;
        });
    };

    window.abrirModalRubro = function () {
        var $dlg = $('#ppaDialog');
        if (!$dlg.data('ui-dialog')) {
            $dlg.dialog({
                autoOpen: false,
                modal: true,
                width: 860,
                height: 520,
                resizable: true,
                open: function () {
                    armarGridPresupuesto();
                    setTimeout(function () { $('#ppa_Pro_Cod').focus(); }, 60);
                }
            });
            $('#ppa_Pro_Cod').on('change', function () {
                var v = $(this).val() || '';
                $('#prn_Pro_Cod').val(v);
                var $g = $('#containerPpa');
                if ($g.length && $g[0].grid) {
                    try { $g.jqGrid('clearGridData', true); } catch (e) {}
                }
                $('#ppa_hint').html(v
                    ? 'Pulse <b>buscar</b> y elija el rubro.'
                    : 'Seleccione un proyecto.');
            });
        }
        $dlg.dialog('open');
    };

    window.buscarPresupuesto = function () {
        var pro = $('#ppa_Pro_Cod').val() || '';
        if (!pro) {
            $.alert('Seleccione un proyecto.');
            return;
        }
        var $g = $('#containerPpa');
        if (!$g.length || !$g[0].grid) {
            armarGridPresupuesto();
        }
        enlLoader(true);
        var txtBusca = $.trim($('#ppa_search').val() || '');
        var codBusca = $.trim($('#ppa_search_cod').val() || '');
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'json',
            data: {
                presupuestoRubrosAjax: 1,
                search: txtBusca,
                search_cod: codBusca,
                ppa_Pro_Cod: pro
            },
            success: function (r) {
                if (!r || r.success === false) {
                    $.alert((r && r.message) ? r.message : 'No se pudo cargar rubros.');
                    return;
                }
                try {
                    $g.jqGrid('clearGridData', true);
                    $g.setRows(r.response || r.rows || []);
                } catch (e) {
                    $g.Search({
                        search: txtBusca,
                        search_cod: codBusca,
                        ppa_Pro_Cod: pro
                    }, 'presupuestoRubrosAjax');
                }
            },
            error: function () {
                $.alert('Error de comunicacion al buscar rubros.');
            },
            complete: function () {
                enlLoader(false);
            }
        });
    };

    function padreDesdeRuta(ruta, cla, des) {
        var parts = String(ruta || '').split(/\s*>\s*/).map(function (p) {
            return $.trim(p);
        }).filter(Boolean);
        if (parts.length > 1) {
            parts.pop();
            return parts.join(' / ');
        }
        var hoja = $.trim((cla || '') + ' ' + (des || ''));
        if (parts.length === 1 && hoja && parts[0] === hoja) {
            return 'Sin cuenta padre';
        }
        return parts.length ? parts[0] : 'Sin cuenta padre';
    }

    function agruparRubrosPorPadre(rows) {
        var grupos = [];
        var idx = {};
        $.each(rows || [], function (_, row) {
            var padre = padreDesdeRuta(row.Ppa_Ruta, row.Ppa_Cla, row.Ppa_Des);
            if (!Object.prototype.hasOwnProperty.call(idx, padre)) {
                idx[padre] = grupos.length;
                grupos.push({ padre: padre, items: [] });
            }
            grupos[idx[padre]].items.push(row);
        });
        return grupos;
    }

    window.imprimirRubros = function () {
        var pro = $('#prn_Pro_Cod').val() || $('#ppa_Pro_Cod').val() || '';
        var proTxt = '';
        if ($('#prn_Pro_Cod').val()) {
            proTxt = $.trim($('#prn_Pro_Cod option:selected').text() || '');
        } else {
            proTxt = $.trim($('#ppa_Pro_Cod option:selected').text() || '');
        }
        if (!pro) {
            $.alert('Seleccione un proyecto para imprimir sus rubros.');
            return;
        }
        if ($('#prn_Pro_Cod').val() !== pro) {
            $('#prn_Pro_Cod').val(pro);
        }
        if ($('#ppa_Pro_Cod').val() !== pro) {
            $('#ppa_Pro_Cod').val(pro);
        }
        enlLoader(true);
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'json',
            data: {
                presupuestoRubrosAjax: 1,
                search: '',
                ppa_Pro_Cod: pro
            },
            success: function (r) {
                if (!r || r.success === false) {
                    $.alert((r && r.message) ? r.message : 'No se pudo cargar rubros para imprimir.');
                    return;
                }
                var rows = r.response || r.rows || [];
                if (!rows.length) {
                    $.alert('El proyecto no tiene rubros para imprimir.');
                    return;
                }
                var hoy = (function () {
                    var d = new Date();
                    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
                })();
                var grupos = agruparRubrosPorPadre(rows);
                var html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
                    + '<title>Rubros - ' + esc(proTxt) + '</title>'
                    + '<style>'
                    + 'body{margin:18px;font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;color:#2c3e50;background:#fff;font-size:13px;}'
                    + 'h1{font-size:18px;margin:0 0 6px;color:#3a5368;}'
                    + '.meta{color:#5f7386;margin:0 0 16px;font-size:13px;line-height:1.45;}'
                    + '.grupo{margin:0 0 16px;border:1px solid #dce4ec;border-radius:5px;overflow:hidden;page-break-inside:avoid;}'
                    + '.padre{margin:0;padding:10px 14px;background:#d9e6f2;color:#2f4a63;font-size:14px;font-weight:700;line-height:1.4;border-bottom:1px solid #c5d5e4;}'
                    + 'table{width:100%;border-collapse:collapse;font-size:13px;}'
                    + 'th{background:#f0f5f9;color:#3a5368;text-align:left;padding:8px 12px;border-bottom:1px solid #dce4ec;font-size:12px;}'
                    + 'td{padding:8px 12px;border-bottom:1px solid #eef2f6;vertical-align:top;line-height:1.4;}'
                    + 'tbody tr:nth-child(even) td{background:#f7fafc;}'
                    + 'td.num{width:36px;color:#7a8b9a;font-weight:600;}'
                    + 'td.cla{white-space:nowrap;font-family:Consolas,"Courier New",monospace;width:110px;color:#4a6278;font-weight:600;}'
                    + 'td.rubro{font-weight:600;color:#8b3a3a;}'
                    + '.barra{margin:0 0 14px;}'
                    + '.barra button{padding:7px 14px;margin-right:6px;cursor:pointer;font-size:13px;border:1px solid #c5d5e4;border-radius:4px;background:#e8eef4;color:#2f4a63;}'
                    + '.barra button.pri{background:#5b8fbf;border-color:#5b8fbf;color:#fff;}'
                    + '@media print{.barra{display:none;} .padre,th,tbody tr:nth-child(even) td{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}'
                    + '</style></head><body>'
                    + '<div class="barra">'
                    + '<button class="pri" onclick="window.print()">Imprimir</button>'
                    + '<button onclick="window.close()">Cerrar</button>'
                    + '</div>'
                    + '<h1>Listado de rubros de presupuesto</h1>'
                    + '<p class="meta">Proyecto: <b>' + esc(proTxt) + '</b> &nbsp;|&nbsp; Fecha: ' + hoy
                    + ' &nbsp;|&nbsp; ' + rows.length + ' rubro(s) en ' + grupos.length + ' cuenta(s) padre</p>';
                $.each(grupos, function (gi, g) {
                    html += '<div class="grupo">'
                        + '<h2 class="padre">' + esc(g.padre) + '</h2>'
                        + '<table><thead><tr>'
                        + '<th style="width:36px;">#</th>'
                        + '<th style="width:110px;">Codigo</th>'
                        + '<th>Rubro</th>'
                        + '</tr></thead><tbody>';
                    $.each(g.items, function (i, row) {
                        html += '<tr>'
                            + '<td class="num">' + (i + 1) + '</td>'
                            + '<td class="cla">' + esc(row.Ppa_Cla || '') + '</td>'
                            + '<td class="rubro">' + esc(row.Pdp_Rubro || row.Ppa_Des || '') + '</td>'
                            + '</tr>';
                    });
                    html += '</tbody></table></div>';
                });
                html += '</body></html>';
                var w = window.open('', '_blank');
                if (!w) {
                    $.alert('Permita ventanas emergentes para imprimir.');
                    return;
                }
                w.document.open();
                w.document.write(html);
                w.document.close();
                try { w.focus(); } catch (e3) {}
            },
            error: function () {
                $.alert('Error de comunicacion al imprimir rubros.');
            },
            complete: function () {
                enlLoader(false);
            }
        });
    };

    window.abrirReporteRubrosCompras = function () {
        var $dlg = $('#repRubrosDialog');
        if (!$dlg.data('ui-dialog')) {
            $dlg.dialog({
                autoOpen: false,
                modal: true,
                width: 420,
                height: 'auto',
                resizable: false
            });
        }
        var pro = $('#prn_Pro_Cod').val() || $('#ppa_Pro_Cod').val() || '0';
        $('#rep_Pro_Cod').val(pro || '0');
        var fi = toIsoDate($('#Fec_Ini').val()) || '';
        var ff = toIsoDate($('#Fec_Fin').val()) || '';
        if (!fi) {
            fi = '<?php echo $anioIni; ?>';
        }
        if (!ff) {
            ff = '<?php echo $mesFinDef; ?>';
        }
        $('#rep_Fec_Ini').val(fi);
        $('#rep_Fec_Fin').val(ff);
        if ($('#rep_Fec_Ini').hasClass('hasDatepicker')) {
            $('#rep_Fec_Ini').datepicker('setDate', fi);
            $('#rep_Fec_Fin').datepicker('setDate', ff);
        }
        $dlg.dialog('open');
    };

    window.exportarRubrosComprasExcel = function () {
        var fi = toIsoDate($('#rep_Fec_Ini').val());
        var ff = toIsoDate($('#rep_Fec_Fin').val());
        if (!fi || !ff) {
            $.alert('Indique el rango de fechas.');
            return;
        }
        if (fi > ff) {
            var tmp = fi;
            fi = ff;
            ff = tmp;
        }
        var pro = $('#rep_Pro_Cod').val() || '0';
        var url = window.location.pathname
            + '?exportRubrosComprasExcel=1'
            + '&Fec_Ini=' + encodeURIComponent(fi)
            + '&Fec_Fin=' + encodeURIComponent(ff)
            + '&Pro_Cod=' + encodeURIComponent(pro);
        var w = window.open(url, '_blank');
        if (!w) {
            $.alert('Permita ventanas emergentes para descargar el Excel.');
            return;
        }
        if ($('#repRubrosDialog').data('ui-dialog')) {
            $('#repRubrosDialog').dialog('close');
        }
    };

    function armarGridPresupuesto() {
        var $g = $('#containerPpa');
        if (!$g.length || $g[0].grid || typeof $g.createGrid !== 'function') return;
        $g.createGrid({
            width: 820,
            height: 300,
            colModel: [
                { name: 'Pdp_Cod', hidden: true },
                { name: 'Ppa_Cod', hidden: true },
                { name: 'Ppa_Des', hidden: true },
                { label: 'Codigo', name: 'Ppa_Cla', width: 90 },
                { label: 'Rubro', name: 'Pdp_Rubro', width: 200 },
                { label: 'Pertenece a', name: 'Ppa_Ruta', width: 420, classes: 'ppa-arbol', title: false, formatter: rutaArbol },
                {
                    label: '&nbsp;', name: 'act1', width: 36, align: 'center', viewable: false,
                    formatter: 'gridButton',
                    formatoptions: { action: 'selectPresupuestoRubro', title: 'Seleccionar rubro' }
                }
            ],
            jsonReader: { root: 'response', repeatitems: false },
            datatype: 'local',
            rowNum: 200,
            viewrecords: true
        }, true);
    }

    window.aplicarMesFiltro = function (buscar) {
        var y = parseInt($('#filtro_anio').val(), 10) || <?php echo (int)$anioActual; ?>;
        var m = parseInt($('#filtro_mes').val(), 10) || <?php echo (int)$mesActual; ?>;
        if (m < 1) m = 1;
        if (m > 12) m = 12;
        var ini = y + '-' + ('0' + m).slice(-2) + '-01';
        var last = new Date(y, m, 0).getDate();
        var fin = y + '-' + ('0' + m).slice(-2) + '-' + ('0' + last).slice(-2);
        $('#Fec_Ini').val(ini);
        $('#Fec_Fin').val(fin);
        if ($('#Fec_Ini').hasClass('hasDatepicker')) {
            $('#Fec_Ini').datepicker('setDate', ini);
        }
        if ($('#Fec_Fin').hasClass('hasDatepicker')) {
            $('#Fec_Fin').datepicker('setDate', fin);
        }
        if (buscar) {
            cargarPendientes();
        }
    };

    function initFiltroAnioMes() {
        var yNow = <?php echo (int)$anioActual; ?>;
        var mNow = <?php echo (int)$mesActual; ?>;
        var $ya = $('#filtro_anio').empty();
        for (var y = yNow; y >= yNow - 10; y--) {
            $ya.append($('<option/>').val(y).text(y));
        }
        $ya.val(String(yNow));
        $('#filtro_mes').val(String(mNow));
        aplicarMesFiltro(false);
    }

    $(function () {
        $('#prn_Pro_Cod').on('change', function () {
            $('#ppa_Pro_Cod').val($(this).val() || '');
        });
        if ($.fn.createDatePickers) {
            $('#Fec_Ini').createDatePickers({ clean: true });
            $('#Fec_Fin').createDatePickers({ clean: true });
            $('#Fec_Ini').datepicker('option', 'dateFormat', 'yy-mm-dd');
            $('#Fec_Fin').datepicker('option', 'dateFormat', 'yy-mm-dd');
            $('#rep_Fec_Ini').createDatePickers({ clean: true });
            $('#rep_Fec_Fin').createDatePickers({ clean: true });
            $('#rep_Fec_Ini').datepicker('option', 'dateFormat', 'yy-mm-dd');
            $('#rep_Fec_Fin').datepicker('option', 'dateFormat', 'yy-mm-dd');
        } else if ($.fn.datepicker) {
            $('#Fec_Ini,#Fec_Fin,#rep_Fec_Ini,#rep_Fec_Fin').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
        }
        initFiltroAnioMes();

        gridPend = $('#gridPendientes').createGrid({
            height: 360,
            datatype: 'local',
            rowNum: 9999,
            rowList: [100, 200, 500, 1000, 9999],
            pager: '#pagerPendientes',
            viewrecords: true,
            multiselect: true,
            multiboxonly: true,
            beforeSelectRow: function (rowid, e) {
                /* Solo marcar/desmarcar con el checkbox; clic en otras columnas no selecciona. */
                var td = $(e.target).closest('td')[0];
                if (!td) {
                    return false;
                }
                var ci = $.jgrid.getCellIndex(td);
                var cm = $(this).jqGrid('getGridParam', 'colModel');
                return !!(cm[ci] && cm[ci].name === 'cb');
            },
            onSelectRow: actualizarLblSeleccion,
            onSelectAll: actualizarLblSeleccion,
            colModel: [
                { name: 'Cop_Cod', label: 'Id', width: 35, align: 'center', key: true, hidden: false },
                { name: 'Com_Cod', label: 'Com_Cod', width: 35, align: 'center', hidden: false },
                { name: 'vinculados', hidden: true },
                {
                    label: 'OK',
                    name: 'rubro_rel',
                    width: 36,
                    align: 'center',
                    sortable: false,
                    title: false,
                    hidden: true,
                    formatter: function (v, opts, row) {
                        var label = $.trim(v || (row && row.rubro_rel) || '');
                        var n = parseInt(row && row.vinculados, 10) || 0;
                        if (label || n > 0) {
                            var tip = label
                                ? label
                                : ('Ya relacionada: ' + n + ' asiento(s)');
                            return '<i class="glyphicon glyphicon-ok-sign enl-ico-clasif" title="' + esc(tip) + '"></i>';
                        }
                        return '<span class="enl-ico-vacio" title="Sin clasificar">-</span>';
                    }
                },
                { label: 'Fecha', name: 'Cop_Fec', width: 60, align: 'center' },
                { label: 'Tipo', name: 'Tic_Des', width: 85 },
                { label: 'N Doc.', name: 'Cop_Num', width: 80 },
                { label: 'RUC/CI', name: 'Prs_Ced', width: 100 },
                { label: 'Razon social', name: 'proveedor', width: 200 },
                { label: 'Observacion', name: 'Cop_Obs', width: 180 },
                { label: 'Subtotal', name: 'Cop_Imp', width: 80, align: 'right', formatter: 'number', formatoptions: { decimalPlaces: 2 } },
                { label: 'Total + IVA', name: 'Cop_Tot', width: 95, align: 'right', formatter: 'number', formatoptions: { decimalPlaces: 2 } },
                { label: 'Items', name: 'tot_items', width: 45, align: 'center', hidden: false },
                { label: 'Pend.', name: 'pendientes', width: 45, align: 'center', hidden: true },
                {
                    label: ' ',
                    name: 'act_cta',
                    width: 36,
                    align: 'center',
                    sortable: false,
                    title: false,
                    formatter: function (cell, opts, row) {
                        var cop = parseInt(row.Cop_Cod, 10) || 0;
                        var num = String(row.Cop_Num || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                        return '<button type="button" class="btn btn-success btn-xs" title="Ver cuentas contables"'
                            + ' onclick="event.stopPropagation();verCuentasCompra({Cop_Cod:' + cop + ',Cop_Num:\'' + num + '\'}); return false;">'
                            + '<i class="glyphicon glyphicon-list-alt"></i></button>';
                    }
                }
            ]
        }, true, '#pagerPendientes');

        $('#ppaDialog').appendTo('body');
        $('#cuentasDialog').appendTo('body');
        $('#repRubrosDialog').appendTo('body');
        /* Cerrar modales al clic en el fondo (overlay) */
        $(document).on('mousedown', '.ui-widget-overlay', function () {
            $('.ui-dialog-content:visible').each(function () {
                var $d = $(this);
                if ($d.data('ui-dialog')) {
                    $d.dialog('close');
                }
            });
        });
        actualizarPlaceholderFiltro();
        $('#lblResultado').text('Pulse Buscar o Enter para listar compras.');
    });
})();
</script>
</body>
</html>
