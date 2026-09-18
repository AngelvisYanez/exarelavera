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
$hoy = date('Y-m-d');
$anioIni = date('Y-01-01');

function enl_json($data)
{
    if (ob_get_length()) {
        @ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
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
    $Cop_Cod = (int)$Cop_Cod;
    $asiList = array();
    if (!$mysqli || $Cop_Cod <= 0 || !enl_tabla_ok($mysqli, 'pre_proyecto_detalle_asiento')) {
        return $asiList;
    }

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
           AND pda.Asi_Cod IS NULL"
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
        $Fec_Fin = enl_fecha(isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '', $hoy);
        $ruc = isset($_REQUEST['filtro_ruc']) ? trim((string)$_REQUEST['filtro_ruc']) : '';
        $razon = isset($_REQUEST['filtro_razon']) ? trim((string)$_REQUEST['filtro_razon']) : '';
        $search = isset($_REQUEST['search']) ? trim((string)$_REQUEST['search']) : '';

        $filtro = '';
        if ($ruc !== '') {
            $esc = $mysqli->real_escape_string($ruc);
            $filtro .= " AND prs.Prs_Ced LIKE '%$esc%'";
        }
        if ($razon !== '') {
            $esc = $mysqli->real_escape_string($razon);
            $filtro .= " AND CONCAT(IFNULL(prs.Prs_Ape,''),' ',IFNULL(prs.Prs_Nom,'')) LIKE '%$esc%'";
        }
        if ($search !== '') {
            $esc = $mysqli->real_escape_string($search);
            $filtro .= " AND (c.Cop_Num LIKE '%$esc%' OR CAST(c.Cop_Cod AS CHAR) LIKE '%$esc%')";
        }

        $pendExpr = enl_sql_pendientes_expr('c');
        $vincExpr = enl_sql_vinculados_expr('c');

        /* Compras con asiento de producto pendiente; icono si ya hay >=1 vinculado */
        $sql = "SELECT c.Cop_Cod, c.Cop_Num, c.Cop_Fec, c.Cop_Obs,
                       tc.Tic_Des,
                       CONCAT(IFNULL(prs.Prs_Ape,''),' ',IFNULL(prs.Prs_Nom,'')) AS proveedor,
                       prs.Prs_Ced,
                       (SELECT COUNT(*) FROM det_compra dc2 WHERE dc2.Cop_Cod = c.Cop_Cod) AS tot_items,
                       $pendExpr AS pendientes,
                       $vincExpr AS vinculados,
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
                  AND $pendExpr > 0
                ORDER BY c.Cop_Fec DESC, c.Cop_Cod DESC
                LIMIT 500";
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
            $out['message'] = 'No hay compras con asiento pendiente de presupuesto entre ' . $Fec_Ini . ' y ' . $Fec_Fin . '.';
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
            $rubro = trim((string)$r['Pdp_Rubro']);
            if ($rubro === '') {
                $rubro = trim((string)$r['Ppa_Des']);
            }
            if ($search !== '') {
                $hay = stripos($r['Ppa_Cla'], $search) !== false
                    || stripos($r['Ppa_Des'], $search) !== false
                    || stripos($rubro, $search) !== false
                    || stripos($ruta, $search) !== false;
                if (!$hay) {
                    continue;
                }
            }
            $rows[] = array(
                'Pdp_Cod' => (int)$r['Pdp_Cod'],
                'Ppa_Cod' => $ppa,
                'Ppa_Cla' => $r['Ppa_Cla'],
                'Ppa_Des' => $r['Ppa_Des'],
                'Pdp_Rubro' => $rubro,
                'Ppa_Ruta' => $ruta,
                'Ppa_Label' => $r['Ppa_Cla'] . ' - ' . $rubro,
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
            $asis = enl_asientos_pendientes($mysqli, $Cop_Cod);
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
                    $errVinc = $mysqli->error ? $mysqli->error : 'fallo al insertar en pre_proyecto_detalle_asiento';
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
            throw new Exception('Las facturas seleccionadas no tienen asientos de productos pendientes (cuenta Pld_Cod del detalle) por vincular a presupuesto.');
        }
        $mysqli->commit();
        $mysqli->autocommit(true);
        $out['success'] = true;
        $out['vinculados'] = $okAsi;
        $out['facturas'] = $okFac;
        $out['message'] = 'Enlazadas ' . $okFac . ' factura(s): ' . $okAsi . ' asiento(s) al rubro.';
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

/* ---------- Proyectos para el modal ---------- */
$ppa_proyectos = array();
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
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enlace Compra a Rubro [EXA]</title>
    <meta charset="UTF-8">
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <style>
        .enl-filtros .form-control { height: 28px; }
        .enl-filtros .form-group { margin-right: 6px; margin-bottom: 6px; vertical-align: top; }
        .enl-hint { color: #666; font-size: 11px; margin-top: 4px; }
        #lblResultado { font-weight: 600; color: #254463; }
        #ppaDialog .lbl-compra { font-weight: 700; color: #254463; }
        #ppaDialog .ppa-filtros { margin: 0 0 6px; }
        #ppaDialog .ppa-hint { display: block; margin: 4px 0; color: #8b79a8; font-size: 11px; }
        #ppaDialog td.ppa-arbol { white-space: normal !important; padding-top: 3px !important; padding-bottom: 3px !important; vertical-align: top !important; }
        .exa-arbol { display: block; line-height: 1.25; }
        .exa-arbol .n { display: block; color: #94a5b2; font-size: 10px; line-height: 1.4; }
        .exa-arbol .n.pad { color: #3d5a73; font-weight: 600; }
        .exa-arbol .n .cn { color: #c8d2da; margin-right: 1px; }
        .exa-arbol .n.hoja { color: #254463; font-weight: 700; font-size: 11px; margin-top: 1px; }
        .exa-sinpadre { color: #b3bfc9; font-size: 10px; font-style: italic; }
        .enl-acciones { margin: 8px 0; }
        .enl-hint-row { display: table; width: 100%; margin-top: 6px; }
        .enl-hint-row .enl-hint-left { display: table-cell; vertical-align: middle; }
        .enl-hint-row .enl-hint-right { display: table-cell; vertical-align: middle; text-align: right; white-space: nowrap; width: 1%; }
        .enl-ico-clasif { color: #3c763d; font-size: 14px; }
        .enl-ico-vacio { color: #ccc; font-size: 14px; }
    </style>
</head>
<body>
<div class="panel panel-main">
    <div class="panel-heading exa-header">
        <h3 class="panel-title">&raquo; Enlazar facturas de compra a rubro de presupuesto</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <fieldset class="exa-fieldset enl-filtros">
            <legend class="Titulos2">Filtros</legend>
            <form id="frmFiltro" class="form-inline" onsubmit="return false;">
                <div class="form-group">
                    <label>Desde</label>
                    <input type="text" id="Fec_Ini" class="form-control input-sm" value="<?php echo htmlspecialchars($anioIni); ?>" style="width:105px;" />
                </div>
                <div class="form-group">
                    <label>Hasta</label>
                    <input type="text" id="Fec_Fin" class="form-control input-sm" value="<?php echo htmlspecialchars($hoy); ?>" style="width:105px;" />
                </div>
                <div class="form-group">
                    <label>RUC / CI</label>
                    <input type="text" id="filtro_ruc" class="form-control input-sm" placeholder="RUC o cedula..." style="width:140px;"
                           onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();cargarPendientes();}" />
                </div>
                <div class="form-group">
                    <label>Razon social</label>
                    <input type="text" id="filtro_razon" class="form-control input-sm" placeholder="Proveedor..." style="width:180px;"
                           onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();cargarPendientes();}" />
                </div>
                <div class="form-group">
                    <label>N Doc.</label>
                    <input type="text" id="search" class="form-control input-sm" placeholder="Numero factura..." style="width:130px;"
                           onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();cargarPendientes();}" />
                </div>
                <button type="button" class="btn btn-sm btn-success" onclick="cargarPendientes()">
                    <i class="glyphicon glyphicon-search"></i> Buscar
                </button>
            </form>
            <div class="enl-hint-row">
                <div class="enl-hint-left">
                    <p class="enl-hint" style="margin:0;">Solo asientos de cuentas de productos. Icono <i class="glyphicon glyphicon-ok-sign" style="color:#3c763d;"></i> = ya tiene &ge;1 asiento clasificado.</p>
                    <p class="enl-hint" id="lblResultado" style="margin:2px 0 0;"></p>
                </div>
                <div class="enl-hint-right">
                    <button type="button" class="btn btn-sm btn-success" onclick="guardarEnlace()">
                        <i class="glyphicon glyphicon-floppy-disk"></i> Guardar enlace
                    </button>
                </div>
            </div>
        </fieldset>

        <div class="enl-acciones">
            <button type="button" class="btn btn-sm btn-default" onclick="abrirAsignarRubro()">
                <i class="glyphicon glyphicon-search"></i> Elegir rubro
            </button>
            <span class="rubro-sel-box" style="display:inline-block;vertical-align:middle;margin:0 8px;min-width:280px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-addon">Rubro</span>
                    <input type="text" id="sel_Ppa_Label" class="form-control" readonly placeholder="Sin rubro seleccionado..." />
                    <input type="hidden" id="sel_Pdp_Cod" value="" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-danger" onclick="limpiarRubroSel()" title="Quitar rubro">
                            <span class="glyphicon glyphicon-remove"></span>
                        </button>
                    </span>
                </div>
                <small id="sel_Ppa_Ruta_Txt" class="enl-hint" style="display:block;margin-top:2px;"></small>
            </span>
            <span id="lblSeleccion" class="enl-hint" style="margin-left:10px;"></span>
        </div>

        <table id="gridPendientes"></table>
        <div id="pagerPendientes"></div>
    </div>
</div>

<div id="ppaDialog" title="Rubro de presupuesto (proyecto)" style="display:none;">
    <div style="margin-bottom:8px;font-size:12px;color:#555;">
        <span class="lbl-compra" id="det_titulo">ù</span>
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
        <div class="input-group input-group-xs" style="margin-bottom:4px;">
            <input id="ppa_search" type="text" maxlength="80" placeholder="Filtrar rubros..." class="form-control input-xs"
                   onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();buscarPresupuesto();return false;}" />
            <span class="input-group-btn">
                <button type="button" onclick="buscarPresupuesto()" class="btn btn-success btn-xs">
                    <span class="glyphicon glyphicon-filter"></span>
                </button>
            </span>
        </div>
        <small class="ppa-hint" id="ppa_hint">Seleccione proyecto, busque y elija el rubro para las facturas marcadas.</small>
    </div>
    <table id="containerPpa"></table>
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
            return '<span class="exa-sinpadre">ù sin jerarquia ù</span>';
        }
        var niveles = txt.split('>');
        if (!niveles.length) {
            return '<span class="exa-sinpadre">ù sin jerarquia ù</span>';
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

    window.cargarPendientes = function () {
        var fi = toIsoDate($('#Fec_Ini').val());
        var ff = toIsoDate($('#Fec_Fin').val());
        $('#Fec_Ini').val(fi);
        $('#Fec_Fin').val(ff);
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            dataType: 'json',
            data: {
                listPendientesAjax: 1,
                Fec_Ini: fi,
                Fec_Fin: ff,
                filtro_ruc: $.trim($('#filtro_ruc').val() || ''),
                filtro_razon: $.trim($('#filtro_razon').val() || ''),
                search: $.trim($('#search').val() || '')
            },
            success: function (r) {
                if (!r || r.success === false) {
                    $.alert((r && r.message) ? r.message : 'No se pudo cargar el listado.');
                    return;
                }
                gridPend.clearGrid().setRows(r.rows || []);
                actualizarLblSeleccion();
                if (!(r.rows && r.rows.length) && r.message) {
                    $('#lblResultado').text(r.message);
                } else {
                    $('#lblResultado').text((r.records || 0) + ' compra(s) pendiente(s).');
                }
            },
            error: function () {
                $.alert('Error de comunicacion al buscar compras.');
            }
        });
    };

    window.abrirAsignarRubro = function () {
        var n = facturasMarcadas().length;
        $('#det_titulo').text(n ? (n + ' factura(s) seleccionada(s)') : 'Elija un rubro');
        abrirModalRubro();
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
        $('#sel_Ppa_Ruta_Txt').html(rutaArbol(rubroSel.Ppa_Ruta));
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
                var $g = $('#containerPpa');
                if ($g.length && $g[0].grid) {
                    try { $g.jqGrid('clearGridData', true); } catch (e) {}
                }
                $('#ppa_hint').html($(this).val()
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
        $g.Search({
            search: $.trim($('#ppa_search').val() || ''),
            ppa_Pro_Cod: pro
        }, 'presupuestoRubrosAjax');
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

    $(function () {
        if ($.fn.createDatePickers) {
            $('#Fec_Ini').createDatePickers({ clean: true });
            $('#Fec_Fin').createDatePickers({ clean: true });
            $('#Fec_Ini').datepicker('option', 'dateFormat', 'yy-mm-dd').datepicker('setDate', '<?php echo $anioIni; ?>');
            $('#Fec_Fin').datepicker('option', 'dateFormat', 'yy-mm-dd').datepicker('setDate', '<?php echo $hoy; ?>');
        } else if ($.fn.datepicker) {
            $('#Fec_Ini,#Fec_Fin').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
        }

        gridPend = $('#gridPendientes').createGrid({
            height: 360,
            datatype: 'local',
            rowNum: 100,
            pager: '#pagerPendientes',
            viewrecords: true,
            multiselect: true,
            onSelectRow: actualizarLblSeleccion,
            onSelectAll: actualizarLblSeleccion,
            colModel: [
                { name: 'Cop_Cod', label: 'Id', width: 55, align: 'center', key: true },
                {
                    label: ' ',
                    name: 'vinculados',
                    width: 28,
                    align: 'center',
                    sortable: false,
                    title: false,
                    formatter: function (v) {
                        var n = parseInt(v, 10) || 0;
                        if (n > 0) {
                            return '<i class="glyphicon glyphicon-ok-sign enl-ico-clasif" title="Clasificada: ' + n + ' asiento(s) de producto ya en presupuesto"></i>';
                        }
                        return '<i class="glyphicon glyphicon-unchecked enl-ico-vacio" title="Sin asientos de producto clasificados"></i>';
                    }
                },
                { label: 'Fecha', name: 'Cop_Fec', width: 80, align: 'center' },
                { label: 'Tipo', name: 'Tic_Des', width: 85 },
                { label: 'N Doc.', name: 'Cop_Num', width: 105 },
                { label: 'RUC/CI', name: 'Prs_Ced', width: 100 },
                { label: 'Razon social', name: 'proveedor', width: 200 },
                { label: 'Subtotal', name: 'Cop_Imp', width: 75, align: 'right', formatter: 'number', formatoptions: { decimalPlaces: 2 } },
                { label: 'Total + IVA', name: 'Cop_Tot', width: 90, align: 'right', formatter: 'number', formatoptions: { decimalPlaces: 2 } },
                { label: 'Items', name: 'tot_items', width: 45, align: 'center' },
                { label: 'Pend.', name: 'pendientes', width: 45, align: 'center' }
            ]
        }, true, '#pagerPendientes');

        $('#ppaDialog').appendTo('body');
        cargarPendientes();
    });
})();
</script>
</body>
</html>
