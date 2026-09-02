<?php
/**
 * Vincula asientos de compra con rubros de presupuesto.
 * - Proyecto: pre_proyecto_detalle_asiento (Pdp_Cod + Asi_Cod)
 * - General:  pre_ejecucion (Ppa_Cod + Asi_Cod + cabecera Ppe_Cod)
 * El rubro de la fila (individual) predomina sobre el general del formulario.
 */

if (!function_exists('fac_ppa_pdp_efectivo')) {
    function fac_ppa_pdp_efectivo($item, $pdpGeneral)
    {
        $codes = fac_ppa_codes_efectivos($item, $pdpGeneral, 0);
        return (int)$codes['Pdp_Cod'];
    }
}

if (!function_exists('fac_ppa_codes_efectivos')) {
    /**
     * Prioridad: Pdp fila > Pdp form > Ppa fila (sin Pdp) > Ppa form.
     * @return array{Pdp_Cod:int,Ppa_Cod:int}
     */
    function fac_ppa_codes_efectivos($item, $pdpGeneral, $ppaGeneral = 0)
    {
        $pdpInd = (is_array($item) && isset($item['Pdp_Cod'])) ? (int)$item['Pdp_Cod'] : 0;
        $ppaInd = (is_array($item) && isset($item['Ppa_Cod'])) ? (int)$item['Ppa_Cod'] : 0;
        $pdpG = (int)$pdpGeneral;
        $ppaG = (int)$ppaGeneral;

        if ($pdpInd > 0) {
            return array('Pdp_Cod' => $pdpInd, 'Ppa_Cod' => 0);
        }
        if ($pdpG > 0) {
            return array('Pdp_Cod' => $pdpG, 'Ppa_Cod' => 0);
        }
        if ($ppaInd > 0) {
            return array('Pdp_Cod' => 0, 'Ppa_Cod' => $ppaInd);
        }
        if ($ppaG > 0) {
            return array('Pdp_Cod' => 0, 'Ppa_Cod' => $ppaG);
        }
        return array('Pdp_Cod' => 0, 'Ppa_Cod' => 0);
    }
}

if (!function_exists('fac_ppa_tabla_nombre_ok')) {
    function fac_ppa_tabla_nombre_ok($mysqli, $tabla)
    {
        if (!$mysqli || $tabla === '') {
            return false;
        }
        if (function_exists('fac_ppa_tabla_existe')) {
            return fac_ppa_tabla_existe($mysqli, $tabla);
        }
        $t = $mysqli->real_escape_string($tabla);
        $res = @$mysqli->query("SHOW TABLES LIKE '$t'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('fac_ppa_asiento_tabla_ok')) {
    function fac_ppa_asiento_tabla_ok($mysqli)
    {
        return fac_ppa_tabla_nombre_ok($mysqli, 'pre_proyecto_detalle_asiento');
    }
}

if (!function_exists('fac_ppa_ejecucion_tabla_ok')) {
    function fac_ppa_ejecucion_tabla_ok($mysqli)
    {
        return fac_ppa_tabla_nombre_ok($mysqli, 'pre_ejecucion');
    }
}

if (!function_exists('fac_ppa_tabla_detalle_nombre')) {
    function fac_ppa_tabla_detalle_nombre($mysqli)
    {
        if (fac_ppa_tabla_nombre_ok($mysqli, 'pre_proyecto_detalles')) {
            return 'pre_proyecto_detalles';
        }
        if (fac_ppa_tabla_nombre_ok($mysqli, 'pre_proyecto_detalle')) {
            return 'pre_proyecto_detalle';
        }
        return '';
    }
}

if (!function_exists('fac_ppa_ruta_partida')) {
    function fac_ppa_ruta_partida($mysqli, $ppaCod)
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
                    $rp = @$mysqli->query(
                        "SELECT Ppa_Cod FROM pre_partidas WHERE Ppa_Cla = '$padreCla' LIMIT 1"
                    );
                    if ($rp && ($pr = $rp->fetch_assoc())) {
                        $cur = (int)$pr['Ppa_Cod'];
                    }
                }
            }
        }
        return implode(' > ', $parts);
    }
}

if (!function_exists('fac_ppa_presupuesto_activo')) {
    /** Resuelve Ppe_Cod activo para Emp_Cod + anio de la compra. */
    function fac_ppa_presupuesto_activo($mysqli, $Emp_Cod, $anio)
    {
        $Emp_Cod = (int)$Emp_Cod;
        $anio = (int)$anio;
        if (!$mysqli || $Emp_Cod <= 0 || !fac_ppa_tabla_nombre_ok($mysqli, 'pre_presupuesto')) {
            return 0;
        }
        if ($anio > 0) {
            $res = @$mysqli->query(
                "SELECT Ppe_Cod FROM pre_presupuesto
                 WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $anio AND Ppe_Est = 'A'
                 ORDER BY Ppe_Ver DESC LIMIT 1"
            );
            if ($res && ($row = $res->fetch_assoc())) {
                return (int)$row['Ppe_Cod'];
            }
            $res = @$mysqli->query(
                "SELECT Ppe_Cod FROM pre_presupuesto
                 WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $anio
                 ORDER BY Ppe_Ver DESC LIMIT 1"
            );
            if ($res && ($row = $res->fetch_assoc())) {
                return (int)$row['Ppe_Cod'];
            }
        }
        $res = @$mysqli->query(
            "SELECT Ppe_Cod FROM pre_presupuesto
             WHERE Emp_Cod = $Emp_Cod AND Ppe_Est = 'A'
             ORDER BY Ppe_Ani DESC, Ppe_Ver DESC LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            return (int)$row['Ppe_Cod'];
        }
        return 0;
    }
}

if (!function_exists('fac_ppa_vincular_asiento_proyecto')) {
    function fac_ppa_vincular_asiento_proyecto($mysqli, $Pdp_Cod, $Asi_Cod)
    {
        $Pdp_Cod = (int)$Pdp_Cod;
        $Asi_Cod = (int)$Asi_Cod;
        if (!$mysqli || $Pdp_Cod <= 0 || $Asi_Cod <= 0 || !fac_ppa_asiento_tabla_ok($mysqli)) {
            return false;
        }
        return (bool)@$mysqli->query(
            "INSERT INTO pre_proyecto_detalle_asiento (Pdp_Cod, Asi_Cod, Ppa_Cod)
             VALUES ($Pdp_Cod, $Asi_Cod, NULL)
             ON DUPLICATE KEY UPDATE Pdp_Cod = VALUES(Pdp_Cod), Ppa_Cod = NULL"
        );
    }
}

if (!function_exists('fac_ppa_vincular_asiento_general')) {
    /**
     * Presupuesto General → pre_ejecucion (Asi_Cod + Ppa_Cod).
     * @param array $ctx Emp_Cod, Suc_Cod?, Usu_Cod, Cop_Cod, Cop_Fec, Cop_Num?, monto?, Pej_Fase?
     */
    function fac_ppa_vincular_asiento_general($mysqli, $Ppa_Cod, $Asi_Cod, $ctx = array())
    {
        $Ppa_Cod = (int)$Ppa_Cod;
        $Asi_Cod = (int)$Asi_Cod;
        if (!$mysqli || $Ppa_Cod <= 0 || $Asi_Cod <= 0 || !fac_ppa_ejecucion_tabla_ok($mysqli)) {
            return false;
        }
        $Emp_Cod = isset($ctx['Emp_Cod']) ? (int)$ctx['Emp_Cod'] : 0;
        $Usu_Cod = isset($ctx['Usu_Cod']) ? (int)$ctx['Usu_Cod'] : 0;
        if ($Usu_Cod <= 0 && isset($_SESSION['Ses_Usu_Cod'])) {
            $Usu_Cod = (int)$_SESSION['Ses_Usu_Cod'];
        }
        $Cop_Cod = isset($ctx['Cop_Cod']) ? (int)$ctx['Cop_Cod'] : 0;
        $Cop_Fec = isset($ctx['Cop_Fec']) ? trim((string)$ctx['Cop_Fec']) : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $Cop_Fec)) {
            $Cop_Fec = date('Y-m-d');
        }
        $Cop_Fec = substr($Cop_Fec, 0, 10);
        $anio = (int)substr($Cop_Fec, 0, 4);
        $mes = (int)substr($Cop_Fec, 5, 2);
        if ($mes < 1 || $mes > 12) {
            $mes = (int)date('n');
        }
        $Ppe_Cod = fac_ppa_presupuesto_activo($mysqli, $Emp_Cod, $anio);
        if ($Ppe_Cod <= 0 || $Emp_Cod <= 0 || $Usu_Cod <= 0) {
            return false;
        }

        $Suc_Cod = (isset($ctx['Suc_Cod']) && $ctx['Suc_Cod'] !== '' && $ctx['Suc_Cod'] !== null)
            ? (int)$ctx['Suc_Cod'] : 'NULL';
        $Dep_Cod = (isset($ctx['Dep_Cod']) && $ctx['Dep_Cod'] !== '' && $ctx['Dep_Cod'] !== null)
            ? (int)$ctx['Dep_Cod'] : 'NULL';
        $monto = isset($ctx['monto']) ? round((float)$ctx['monto'], 2) : 0;
        if ($monto < 0) {
            $monto = abs($monto);
        }
        $fase = (isset($ctx['Pej_Fase']) && $ctx['Pej_Fase'] !== '')
            ? $mysqli->real_escape_string($ctx['Pej_Fase']) : 'E';
        $tipDoc = 'compra';
        $docCod = $Cop_Cod > 0
            ? (string)$Cop_Cod
            : $mysqli->real_escape_string(isset($ctx['Cop_Num']) ? (string)$ctx['Cop_Num'] : ('ASI-' . $Asi_Cod));
        $docCodEsc = $mysqli->real_escape_string($docCod);
        $fecEsc = $mysqli->real_escape_string($Cop_Fec);

        /* Si ya hay ejecucion para este Asi_Cod, actualizar. */
        $chk = @$mysqli->query("SELECT Pej_Cod FROM pre_ejecucion WHERE Asi_Cod = $Asi_Cod LIMIT 1");
        if ($chk && ($row = $chk->fetch_assoc())) {
            $pej = (int)$row['Pej_Cod'];
            return (bool)@$mysqli->query(
                "UPDATE pre_ejecucion SET
                    Ppe_Cod = $Ppe_Cod,
                    Ppa_Cod = $Ppa_Cod,
                    Emp_Cod = $Emp_Cod,
                    Suc_Cod = $Suc_Cod,
                    Dep_Cod = $Dep_Cod,
                    Pej_Mes = $mes,
                    Pej_Ani = $anio,
                    Pej_TipDoc = '$tipDoc',
                    Pej_DocCod = '$docCodEsc',
                    Pej_Mon = $monto,
                    Pej_Sig = '+',
                    Pej_Fec = '$fecEsc',
                    Usu_Cod = $Usu_Cod,
                    Pej_Fase = '$fase',
                    Pej_Rubro = NULL
                 WHERE Pej_Cod = $pej"
            );
        }

        return (bool)@$mysqli->query(
            "INSERT INTO pre_ejecucion (
                Ppe_Cod, Ppa_Cod, Asi_Cod, Emp_Cod, Suc_Cod, Dep_Cod, Pec_Cod,
                Pej_Mes, Pej_Ani, Pej_TipDoc, Pej_DocCod, Pej_Mon, Pej_Sig,
                Pej_Fec, Usu_Cod, Prg_Cod, Pej_Fase, Pej_Rubro
             ) VALUES (
                $Ppe_Cod, $Ppa_Cod, $Asi_Cod, $Emp_Cod, $Suc_Cod, $Dep_Cod, NULL,
                $mes, $anio, '$tipDoc', '$docCodEsc', $monto, '+',
                '$fecEsc', $Usu_Cod, NULL, '$fase', NULL
             )"
        );
    }
}

if (!function_exists('fac_ppa_vincular_asiento')) {
    /**
     * Compat: proyecto → pre_proyecto_detalle_asiento; general ya no usa esta tabla.
     * Preferir fac_ppa_vincular_asiento_item con $ctx.
     */
    function fac_ppa_vincular_asiento($mysqli, $Pdp_Cod, $Asi_Cod, $Ppa_Cod = 0)
    {
        $Pdp_Cod = (int)$Pdp_Cod;
        $Asi_Cod = (int)$Asi_Cod;
        $Ppa_Cod = (int)$Ppa_Cod;
        if ($Pdp_Cod > 0) {
            return fac_ppa_vincular_asiento_proyecto($mysqli, $Pdp_Cod, $Asi_Cod);
        }
        if ($Ppa_Cod > 0) {
            return fac_ppa_vincular_asiento_general($mysqli, $Ppa_Cod, $Asi_Cod, array());
        }
        return false;
    }
}

if (!function_exists('fac_ppa_vincular_asiento_item')) {
    /**
     * @param array $ctx contexto compra para pre_ejecucion (solo General)
     */
    function fac_ppa_vincular_asiento_item($mysqli, $item, $pdpGeneral, $Asi_Cod, $ppaGeneral = 0, $ctx = array())
    {
        $c = fac_ppa_codes_efectivos($item, $pdpGeneral, $ppaGeneral);
        if ($c['Pdp_Cod'] > 0) {
            return fac_ppa_vincular_asiento_proyecto($mysqli, $c['Pdp_Cod'], $Asi_Cod);
        }
        if ($c['Ppa_Cod'] > 0) {
            if (!is_array($ctx)) {
                $ctx = array();
            }
            if (!isset($ctx['monto']) && is_array($item)) {
                if (isset($item['Cop_Imp'])) {
                    $ctx['monto'] = (float)$item['Cop_Imp'];
                }
            }
            return fac_ppa_vincular_asiento_general($mysqli, $c['Ppa_Cod'], $Asi_Cod, $ctx);
        }
        return false;
    }
}

if (!function_exists('fac_ppa_enriquecer_items_asiento')) {
    /**
     * Completa Pdp_Cod / Ppa_* en cada item de la compra.
     * Proyecto: pre_proyecto_detalle_asiento
     * General:  pre_ejecucion (Asi_Cod)
     */
    function fac_ppa_enriquecer_items_asiento($mysqli, &$items, $Cop_Cod = 0)
    {
        if (!$mysqli || !is_array($items)) {
            return null;
        }
        $tblDet = fac_ppa_tabla_detalle_nombre($mysqli);
        $Cop_Cod = (int)$Cop_Cod;
        $mapInt = array();
        $mapAsi = array();
        $rutaCache = array();

        $aplicarFila = function ($row) use (&$mapInt, &$mapAsi, &$rutaCache, $mysqli) {
            $ppa = (int)$row['Ppa_Cod'];
            if ($ppa > 0 && !isset($rutaCache[$ppa])) {
                $rutaCache[$ppa] = fac_ppa_ruta_partida($mysqli, $ppa);
            }
            $rubro = isset($row['Pdp_Rubro']) ? trim((string)$row['Pdp_Rubro']) : '';
            $row['Ppa_Ruta'] = ($ppa > 0 && isset($rutaCache[$ppa])) ? $rutaCache[$ppa] : '';
            $row['Ppa_Label'] = $row['Ppa_Cla'] . ' - ' . ($rubro !== '' ? $rubro : $row['Ppa_Des']);
            if (!empty($row['Cop_Int'])) {
                $mapInt[(int)$row['Cop_Int']] = $row;
            }
            if (!empty($row['Asi_Cod'])) {
                $mapAsi[(int)$row['Asi_Cod']] = $row;
            }
        };

        /* --- Proyecto (Pdp_Cod) --- */
        if ($tblDet !== '' && fac_ppa_asiento_tabla_ok($mysqli)) {
            if ($Cop_Cod > 0) {
                $sql = "SELECT dc.Cop_Int, dc.Asi_Cod, pda.Pdp_Cod, d.Ppa_Cod, d.Pdp_Rubro,
                               p.Ppa_Cla, p.Ppa_Des
                        FROM pre_proyecto_detalle_asiento pda
                        INNER JOIN `$tblDet` d ON d.Pdp_Cod = pda.Pdp_Cod
                        INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                        INNER JOIN asientos a ON a.Asi_Cod = pda.Asi_Cod
                        INNER JOIN det_compra dc ON dc.Asi_Cod = a.Asi_Cod
                        WHERE dc.Cop_Cod = $Cop_Cod AND pda.Pdp_Cod IS NOT NULL";
            } else {
                $cods = array();
                foreach ($items as $it) {
                    if (!empty($it['Asi_Cod'])) {
                        $cods[] = (int)$it['Asi_Cod'];
                    }
                }
                $cods = array_values(array_unique(array_filter($cods)));
                if ($cods) {
                    $in = implode(',', $cods);
                    $sql = "SELECT dc.Cop_Int, a.Asi_Cod, pda.Pdp_Cod, d.Ppa_Cod, d.Pdp_Rubro,
                                   p.Ppa_Cla, p.Ppa_Des
                            FROM pre_proyecto_detalle_asiento pda
                            INNER JOIN `$tblDet` d ON d.Pdp_Cod = pda.Pdp_Cod
                            INNER JOIN pre_partidas p ON p.Ppa_Cod = d.Ppa_Cod
                            INNER JOIN asientos a ON a.Asi_Cod = pda.Asi_Cod
                            LEFT JOIN det_compra dc ON dc.Asi_Cod = a.Asi_Cod
                            WHERE a.Asi_Cod IN ($in) AND pda.Pdp_Cod IS NOT NULL";
                } else {
                    $sql = '';
                }
            }
            if ($sql !== '') {
                $res = @$mysqli->query($sql);
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $aplicarFila($row);
                    }
                }
            }
        }

        /* --- General: pre_ejecucion --- */
        if (fac_ppa_ejecucion_tabla_ok($mysqli)) {
            if ($Cop_Cod > 0) {
                $sqlG = "SELECT dc.Cop_Int, dc.Asi_Cod, NULL AS Pdp_Cod, pe.Ppa_Cod,
                                p.Ppa_Des AS Pdp_Rubro, p.Ppa_Cla, p.Ppa_Des
                         FROM pre_ejecucion pe
                         INNER JOIN pre_partidas p ON p.Ppa_Cod = pe.Ppa_Cod
                         INNER JOIN asientos a ON a.Asi_Cod = pe.Asi_Cod
                         INNER JOIN det_compra dc ON dc.Asi_Cod = a.Asi_Cod
                         WHERE dc.Cop_Cod = $Cop_Cod AND pe.Asi_Cod IS NOT NULL";
            } else {
                $cods = array();
                foreach ($items as $it) {
                    if (!empty($it['Asi_Cod'])) {
                        $cods[] = (int)$it['Asi_Cod'];
                    }
                }
                $cods = array_values(array_unique(array_filter($cods)));
                if ($cods) {
                    $in = implode(',', $cods);
                    $sqlG = "SELECT dc.Cop_Int, a.Asi_Cod, NULL AS Pdp_Cod, pe.Ppa_Cod,
                                    p.Ppa_Des AS Pdp_Rubro, p.Ppa_Cla, p.Ppa_Des
                             FROM pre_ejecucion pe
                             INNER JOIN pre_partidas p ON p.Ppa_Cod = pe.Ppa_Cod
                             INNER JOIN asientos a ON a.Asi_Cod = pe.Asi_Cod
                             LEFT JOIN det_compra dc ON dc.Asi_Cod = a.Asi_Cod
                             WHERE pe.Asi_Cod IN ($in)";
                } else {
                    $sqlG = '';
                }
            }
            if ($sqlG !== '') {
                $resG = @$mysqli->query($sqlG);
                if ($resG) {
                    while ($row = $resG->fetch_assoc()) {
                        $asi = (int)$row['Asi_Cod'];
                        $idx = !empty($row['Cop_Int']) ? (int)$row['Cop_Int'] : 0;
                        if (($asi > 0 && isset($mapAsi[$asi])) || ($idx > 0 && isset($mapInt[$idx]))) {
                            continue;
                        }
                        $aplicarFila($row);
                    }
                }
            }
        }

        foreach ($items as &$it) {
            $asi = isset($it['Asi_Cod']) ? (int)$it['Asi_Cod'] : 0;
            $idx = isset($it['Cop_Int']) ? (int)$it['Cop_Int'] : (isset($it['index']) ? (int)$it['index'] : 0);
            $m = null;
            if ($asi > 0 && isset($mapAsi[$asi])) {
                $m = $mapAsi[$asi];
            } elseif ($idx > 0 && isset($mapInt[$idx])) {
                $m = $mapInt[$idx];
            }
            if (!$m) {
                continue;
            }
            $it['Pdp_Cod'] = !empty($m['Pdp_Cod']) ? $m['Pdp_Cod'] : '';
            $it['Ppa_Cod'] = $m['Ppa_Cod'];
            $it['Ppa_Cla'] = $m['Ppa_Cla'];
            $it['Ppa_Des'] = $m['Ppa_Des'];
            $it['Ppa_Label'] = $m['Ppa_Label'];
            $it['Ppa_Ruta'] = $m['Ppa_Ruta'];
            if (empty($it['Asi_Cod']) && !empty($m['Asi_Cod'])) {
                $it['Asi_Cod'] = $m['Asi_Cod'];
            }
        }
        unset($it);

        $conRubro = 0;
        $sinRubro = 0;
        $porClave = array();
        foreach ($items as $it) {
            if (!isset($it['Pro_Cod']) || $it['Pro_Cod'] === '' || $it['Pro_Cod'] === null) {
                continue;
            }
            $pdp = isset($it['Pdp_Cod']) ? (int)$it['Pdp_Cod'] : 0;
            $ppa = isset($it['Ppa_Cod']) ? (int)$it['Ppa_Cod'] : 0;
            if ($pdp > 0 || $ppa > 0) {
                $conRubro++;
                $clave = $pdp > 0 ? ('d:' . $pdp) : ('p:' . $ppa);
                $porClave[$clave] = $it;
            } else {
                $sinRubro++;
            }
        }
        if ($sinRubro === 0 && $conRubro > 0 && count($porClave) === 1) {
            $g = reset($porClave);
            return array(
                'Pdp_Cod' => isset($g['Pdp_Cod']) ? $g['Pdp_Cod'] : '',
                'Ppa_Cod' => $g['Ppa_Cod'],
                'Ppa_Cla' => $g['Ppa_Cla'],
                'Ppa_Des' => $g['Ppa_Des'],
                'Ppa_Label' => $g['Ppa_Label'],
                'Ppa_Ruta' => isset($g['Ppa_Ruta']) ? $g['Ppa_Ruta'] : ''
            );
        }
        return null;
    }
}

if (!function_exists('fac_ppa_desvincular_asientos_comprobante')) {
    /**
     * Quita vínculos presupuesto del comprobante (y de la compra si se pasa Cop_Cod).
     * - Proyecto: pre_proyecto_detalle_asiento
     * - General:  pre_ejecucion (por Asi_Cod del comprobante y/o Pej_DocCod = Cop_Cod)
     * @param int $Com_Cod
     * @param int $Cop_Cod opcional; limpia ejecución de la compra aunque el Asi_Cod ya no exista
     */
    function fac_ppa_desvincular_asientos_comprobante($mysqli, $Com_Cod, $Cop_Cod = 0)
    {
        $Com_Cod = (int)$Com_Cod;
        $Cop_Cod = (int)$Cop_Cod;
        if (!$mysqli || ($Com_Cod <= 0 && $Cop_Cod <= 0)) {
            return false;
        }
        $ok = true;
        if ($Com_Cod > 0 && fac_ppa_asiento_tabla_ok($mysqli)) {
            $ok = (bool)@$mysqli->query(
                "DELETE pda FROM pre_proyecto_detalle_asiento pda
                 INNER JOIN asientos a ON a.Asi_Cod = pda.Asi_Cod
                 WHERE a.Com_Cod = $Com_Cod"
            ) && $ok;
        }
        if (fac_ppa_ejecucion_tabla_ok($mysqli)) {
            if ($Com_Cod > 0) {
                $ok = (bool)@$mysqli->query(
                    "DELETE pe FROM pre_ejecucion pe
                     INNER JOIN asientos a ON a.Asi_Cod = pe.Asi_Cod
                     WHERE a.Com_Cod = $Com_Cod"
                ) && $ok;
            }
            /* Presupuesto General: borrar también por documento de compra (evita huérfanos). */
            if ($Cop_Cod > 0) {
                $doc = $mysqli->real_escape_string((string)$Cop_Cod);
                $ok = (bool)@$mysqli->query(
                    "DELETE FROM pre_ejecucion
                     WHERE Pej_TipDoc = 'compra' AND Pej_DocCod = '$doc'"
                ) && $ok;
            }
        }
        return $ok;
    }
}
