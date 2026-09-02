<?php
/**
 * AJAX: busqueda de rubros presupuestarios.
 * - presupuestoSolicitudesAjax: solicitudes con rubros (por proyecto y/o nro).
 * - presupuestoRubrosAjax: grid de rubros
 *     * origen=proyecto: por proyecto y/o solicitud (adq_solicitudes_rubros / pre_proyecto_detalles)
 *     * origen=general: partidas detalle que NO pertenecen a proyectos
 * Requiere: $obBD_conexion, $obBD_con1, $Ses_Emp_Cod
 */
if (!function_exists('fac_ppa_tabla_existe')) {
    function fac_ppa_tabla_existe($mysqli, $tabla)
    {
        if (!$mysqli || $tabla === '') {
            return false;
        }
        $t = $mysqli->real_escape_string($tabla);
        $res = @$mysqli->query("SHOW TABLES LIKE '$t'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('fac_ppa_int_request')) {
    function fac_ppa_int_request($keys)
    {
        foreach ((array)$keys as $k) {
            if (isset($GLOBALS[$k]) && $GLOBALS[$k] !== '' && $GLOBALS[$k] !== null && !is_array($GLOBALS[$k])) {
                return (int)$GLOBALS[$k];
            }
            if (isset($_REQUEST[$k]) && $_REQUEST[$k] !== '' && !is_array($_REQUEST[$k])) {
                return (int)$_REQUEST[$k];
            }
        }
        return 0;
    }
}

if (!function_exists('fac_ppa_sin_fecha')) {
    function fac_ppa_sin_fecha($txt)
    {
        $txt = trim(str_replace(array("\t", "\r", "\n"), ' ', (string)$txt));
        $txt = preg_replace('/\s+\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?/', '', $txt);
        $txt = preg_replace('/\s+\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2}(?::\d{2})?)?/', '', $txt);
        return trim(preg_replace('/\s+/', ' ', $txt));
    }
}

if (!function_exists('fac_ppa_str_request')) {
    function fac_ppa_str_request($keys)
    {
        foreach ((array)$keys as $k) {
            if (isset($GLOBALS[$k]) && $GLOBALS[$k] !== '' && $GLOBALS[$k] !== null && !is_array($GLOBALS[$k])) {
                return trim((string)$GLOBALS[$k]);
            }
            if (isset($_REQUEST[$k]) && $_REQUEST[$k] !== '' && !is_array($_REQUEST[$k])) {
                return trim((string)$_REQUEST[$k]);
            }
        }
        return '';
    }
}

$esSolicitudesAjax = isset($presupuestoSolicitudesAjax);
$esRubrosAjax = isset($presupuestoRubrosAjax);
if (!$esSolicitudesAjax && !$esRubrosAjax) {
    return;
}

$responce = array(
    'success' => true,
    'response' => array(),
    'records' => 0,
    'total' => 1,
    'page' => 1,
    'message' => ''
);

try {
    if (!isset($obBD_conexion) || empty($obBD_conexion->conexion)) {
        throw new Exception('Sin conexion a base de datos.');
    }
    $mysqli = $obBD_conexion->conexion;
    $Emp_Cod = (int)$Ses_Emp_Cod;
    if ($Emp_Cod <= 0) {
        throw new Exception('Empresa no valida.');
    }

    $tblSol = fac_ppa_tabla_existe($mysqli, 'adq_solicitudes') ? 'adq_solicitudes' : (fac_ppa_tabla_existe($mysqli, 'adq_solicitud') ? 'adq_solicitud' : '');
    $tblDet = fac_ppa_tabla_existe($mysqli, 'pre_proyecto_detalles') ? 'pre_proyecto_detalles' : (fac_ppa_tabla_existe($mysqli, 'pre_proyecto_detalle') ? 'pre_proyecto_detalle' : '');
    $tblRub = fac_ppa_tabla_existe($mysqli, 'adq_solicitudes_rubros') ? 'adq_solicitudes_rubros' : '';
    $Pro_Cod = fac_ppa_int_request(array('ppa_Pro_Cod', 'Pro_Cod'));
    $Sol_Cod = fac_ppa_int_request(array('ppa_Sol_Cod', 'Sol_Cod'));
    $Sol_Num = fac_ppa_str_request(array('ppa_Sol_Num', 'Sol_Num'));
    $origen = strtolower(fac_ppa_str_request(array('ppa_Origen', 'origen')));
    /* proyecto = por proyecto/solicitud; general = partidas fuera de proyectos */
    if ($origen === 'solicitud') {
        $origen = 'proyecto';
    }
    if ($origen !== 'general' && $origen !== 'proyecto') {
        $origen = ($Sol_Cod > 0 || $Pro_Cod > 0) ? 'proyecto' : 'general';
    }

    if ($esSolicitudesAjax) {
        if ($tblSol === '' || $tblDet === '' || $tblRub === '') {
            $responce['message'] = 'No existen las tablas de solicitudes/rubros de presupuesto.';
            $obBD_con1->echoJson($responce);
            return;
        }
        $sqlSol = "SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, MIN(d.Pro_Cod) AS Pro_Cod
            FROM `$tblSol` s
            INNER JOIN `$tblRub` sr ON sr.Sol_Cod = s.Sol_Cod
            INNER JOIN `$tblDet` d ON d.Pdp_Cod = sr.Pdp_Cod
            WHERE s.Emp_Cod = $Emp_Cod";
        if ($Pro_Cod > 0) {
            $sqlSol .= " AND d.Pro_Cod = $Pro_Cod";
        }
        if ($Sol_Num !== '') {
            $escNum = $mysqli->real_escape_string($Sol_Num);
            $sqlSol .= " AND (s.Sol_Num LIKE '%$escNum%' OR s.Sol_Tit LIKE '%$escNum%')";
        }
        $sqlSol .= " GROUP BY s.Sol_Cod, s.Sol_Num, s.Sol_Tit ORDER BY s.Sol_Num";
        $resSol = $mysqli->query($sqlSol);
        if (!$resSol) {
            throw new Exception('No se pudo consultar solicitudes: ' . $mysqli->error);
        }
        $rowsSol = array();
        while ($rowSol = $resSol->fetch_assoc()) {
            $num = fac_ppa_sin_fecha($rowSol['Sol_Num']);
            $tit = fac_ppa_sin_fecha($rowSol['Sol_Tit']);
            $rowsSol[] = array(
                'Sol_Cod' => (int)$rowSol['Sol_Cod'],
                'Sol_Num' => $num,
                'Sol_Tit' => $tit,
                'Pro_Cod' => (int)$rowSol['Pro_Cod'],
                'Sol_Label' => $tit !== '' ? ($num . ' - ' . $tit) : $num
            );
        }
        $responce['response'] = $rowsSol;
        $responce['records'] = count($rowsSol);
        $obBD_con1->echoJson($responce);
        return;
    }

    $search = isset($search) ? trim($search) : '';
    if ($search === '' && isset($_REQUEST['search'])) {
        $search = trim($_REQUEST['search']);
    }

    /* Mapa completo (activos) para armar la ruta de padres. */
    $map = array();
    $resAll = $mysqli->query(
        "SELECT Ppa_Cod, Ppa_Cla, Ppa_Des, Ppa_Pad, Ppa_Niv,
                COALESCE(NULLIF(Ppa_Clase, ''), 'D') AS Ppa_Clase
         FROM pre_partidas
         WHERE Emp_Cod = $Emp_Cod AND Ppa_Est = 'A'
         ORDER BY Ppa_Cla ASC"
    );
    if (!$resAll) {
        throw new Exception('No se pudo consultar pre_partidas: ' . $mysqli->error);
    }
    while ($row = $resAll->fetch_assoc()) {
        $map[(int)$row['Ppa_Cod']] = $row;
    }

    $byCla = array();
    foreach ($map as $cod => $r) {
        $byCla[$r['Ppa_Cla']] = (int)$cod;
    }

    /* Ppa_Pad es la referencia oficial; si viene vacia se deduce del codigo punteado (01.01.01 -> 01.01). */
    $padreDe = function ($r) use (&$byCla) {
        if (!empty($r['Ppa_Pad'])) {
            return (int)$r['Ppa_Pad'];
        }
        $cla = $r['Ppa_Cla'];
        while (($pos = strrpos($cla, '.')) !== false) {
            $cla = substr($cla, 0, $pos);
            if (isset($byCla[$cla])) {
                return $byCla[$cla];
            }
        }
        return 0;
    };

    $buildRuta = function ($ppaCod) use (&$map, $padreDe) {
        $parts = array();
        $seen = array();
        $cur = (int)$ppaCod;
        while ($cur > 0 && isset($map[$cur]) && !isset($seen[$cur])) {
            $seen[$cur] = true;
            $r = $map[$cur];
            array_unshift($parts, $r['Ppa_Cla'] . ' ' . $r['Ppa_Des']);
            $cur = $padreDe($r);
        }
        return implode(' > ', $parts);
    };

    $pasaBusqueda = function ($textos) use ($search) {
        if ($search === '') {
            return true;
        }
        foreach ((array)$textos as $txt) {
            if ($txt !== '' && stripos($txt, $search) !== false) {
                return true;
            }
        }
        return false;
    };

    $rows = array();
    $pushRubro = function ($r, $solCod) use (&$rows, $buildRuta, $padreDe, $pasaBusqueda) {
        $ppaCod = (int)$r['Ppa_Cod'];
        $ruta = $buildRuta($ppaCod);
        $rubro = isset($r['Pdp_Rubro']) ? trim((string)$r['Pdp_Rubro']) : '';
        if ($rubro === '') {
            $rubro = trim((string)$r['Ppa_Des']);
        }
        if (!$pasaBusqueda(array($r['Ppa_Cla'], $r['Ppa_Des'], $rubro, $ruta))) {
            return;
        }
        $padCod = $padreDe($r);
        $desMostrar = $rubro !== '' ? $rubro : $r['Ppa_Des'];
        $rows[] = array(
            'Pdp_Cod' => isset($r['Pdp_Cod']) ? (int)$r['Pdp_Cod'] : 0,
            'Ppa_Cod' => $ppaCod,
            'Ppa_Cla' => $r['Ppa_Cla'],
            'Ppa_Des' => $r['Ppa_Des'],
            'Pdp_Rubro' => $rubro,
            'Ppa_Pad' => $padCod > 0 ? $padCod : null,
            'Ppa_Niv' => (int)$r['Ppa_Niv'],
            'Ppa_Ruta' => $ruta,
            'Ppa_Label' => $r['Ppa_Cla'] . ' - ' . $desMostrar,
            'Pro_Cod' => isset($r['Pro_Cod']) ? (int)$r['Pro_Cod'] : 0,
            'Sol_Cod' => $solCod > 0 ? $solCod : null
        );
    };

    if ($origen === 'proyecto') {
        /* Presupuesto por Proyecto: filtros por proyecto y/o No. Solicitud. */
        if ($Sol_Cod > 0 && $tblSol !== '' && $tblDet !== '' && $tblRub !== '') {
            $sqlRub = "SELECT DISTINCT d.Pdp_Cod, d.Ppa_Cod, d.Pdp_Rubro, d.Pro_Cod,
                    p.Ppa_Cla, p.Ppa_Des, p.Ppa_Pad, p.Ppa_Niv,
                    COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase
                FROM `$tblDet` d
                INNER JOIN `$tblRub` sr ON sr.Pdp_Cod = d.Pdp_Cod
                INNER JOIN `$tblSol` s ON s.Sol_Cod = sr.Sol_Cod
                INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                WHERE d.Emp_Cod = $Emp_Cod
                  AND s.Sol_Cod = $Sol_Cod
                  AND s.Emp_Cod = $Emp_Cod
                  AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'";
            if ($Pro_Cod > 0) {
                $sqlRub .= " AND d.Pro_Cod = $Pro_Cod";
            }
            $sqlRub .= " ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
            $resRub = $mysqli->query($sqlRub);
            if (!$resRub) {
                throw new Exception('No se pudo consultar rubros de la solicitud: ' . $mysqli->error);
            }
            while ($r = $resRub->fetch_assoc()) {
                $pushRubro($r, $Sol_Cod);
            }
        } elseif ($tblDet !== '') {
            /* Sin solicitud: rubros del/los proyecto(s). */
            $sqlRub = "SELECT DISTINCT d.Pdp_Cod, d.Ppa_Cod, d.Pdp_Rubro, d.Pro_Cod,
                    p.Ppa_Cla, p.Ppa_Des, p.Ppa_Pad, p.Ppa_Niv,
                    COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase
                FROM `$tblDet` d
                INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                INNER JOIN pre_proyectos pr ON pr.Pro_Cod = d.Pro_Cod AND pr.Emp_Cod = d.Emp_Cod
                WHERE d.Emp_Cod = $Emp_Cod
                  AND pr.Pro_Est = 'A'
                  AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'";
            if ($Pro_Cod > 0) {
                $sqlRub .= " AND d.Pro_Cod = $Pro_Cod";
            }
            $sqlRub .= " ORDER BY p.Ppa_Cla, d.Pdp_Rubro";
            $resRub = $mysqli->query($sqlRub);
            if (!$resRub) {
                throw new Exception('No se pudo consultar rubros del proyecto: ' . $mysqli->error);
            }
            while ($r = $resRub->fetch_assoc()) {
                $pushRubro($r, 0);
            }
        }
    } else {
        /* Presupuesto General: solo rubros (partidas detalle) que NO pertenecen a proyectos. */
        $excluyeProyecto = '';
        if ($tblDet !== '') {
            $excluyeProyecto = " AND p.Ppa_Cod NOT IN (
                SELECT DISTINCT d.Ppa_Cod FROM `$tblDet` d WHERE d.Emp_Cod = $Emp_Cod
            )";
        }
        $sqlRub = "SELECT p.Ppa_Cod, p.Ppa_Cla, p.Ppa_Des, p.Ppa_Pad, p.Ppa_Niv,
                COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase,
                p.Ppa_Des AS Pdp_Rubro, 0 AS Pdp_Cod, 0 AS Pro_Cod
            FROM pre_partidas p
            WHERE p.Emp_Cod = $Emp_Cod
              AND p.Ppa_Est = 'A'
              AND COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') = 'D'
              $excluyeProyecto
            ORDER BY p.Ppa_Cla";
        $resRub = $mysqli->query($sqlRub);
        if (!$resRub) {
            throw new Exception('No se pudo consultar rubros generales: ' . $mysqli->error);
        }
        while ($r = $resRub->fetch_assoc()) {
            $pushRubro($r, 0);
        }
    }

    $responce['response'] = $rows;
    $responce['records'] = count($rows);
} catch (Exception $e) {
    $responce['success'] = false;
    $responce['message'] = $e->getMessage();
}

$obBD_con1->echoJson($responce);
