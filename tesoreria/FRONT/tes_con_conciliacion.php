<?php

/**
 * @abstract Permite realizar el registro de productores de fruta
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2020-03-10
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_conciliacion.php');

/* Creacion del Objeto de conexion */
$obBD_conexion_get = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con_get = new Class_Log_Datos_Conciliacion();
$obBD_con_get->setConnection($obBD_conexion_get);

/**
 * Calcula saldo acumulado Cob_Disp y deja solo columnas usadas por el grid.
 * Reduce JSON y evita recalcular fila a fila con setCell en el cliente.
 */
function tes_con_prepare_asientos_grid($asientos) {
    if (!is_array($asientos)) {
        return array(array(), 0);
    }
    $keep = array(
        'conc_select', 'Com_Cod', 'Asi_Cod', 'pago_tipo', 'Com_Codigo', 'Com_Fec',
        'Doc_Num', 'Num_Doc', 'FormasPago', 'Inv_Nom', 'Asi_Glo', 'Asi_Sald',
        'Cob_Disp', 'Che_Num', 'Vet_Che', 'Com_Con'
    );
    $acum = 0.0;
    $out = array();
    foreach ($asientos as $row) {
        if (!is_array($row)) {
            continue;
        }
        $select = isset($row['conc_select']) ? $row['conc_select'] : 'N';
        $isInit = (isset($row['Asi_Cod']) && (string) $row['Asi_Cod'] === 'no_id');
        $sald = isset($row['Asi_Sald']) ? floatval($row['Asi_Sald']) : 0.0;
        if ($select === 'S' || $isInit) {
            $acum += $sald;
            $row['Cob_Disp'] = round($acum, 2);
        } else {
            $row['Cob_Disp'] = null;
        }
        // Etiqueta visible de tipo (sin prefijo de ordenamiento de grouping)
        if ($isInit) {
            $label = !empty($row['Com_Con']) ? $row['Com_Con'] : 'Saldo Anterior';
            $row['pago_tipo'] = ltrim((string) $label, " \t\n\r\0\x0B!");
        } elseif (!isset($row['pago_tipo']) || $row['pago_tipo'] === null || trim((string) $row['pago_tipo']) === '') {
            $row['pago_tipo'] = 'Sin tipo';
        } else {
            $row['pago_tipo'] = ltrim((string) $row['pago_tipo'], '!');
        }
        $slim = array();
        foreach ($keep as $k) {
            if (array_key_exists($k, $row)) {
                $slim[$k] = $row[$k];
            }
        }
        $out[] = $slim;
    }
    return array($out, round($acum, 2));
}

/* Informacion de Representante y Contador */
$infoFirmas = $obBD_con_get->getRowConsultaSql("SELECT Emp_Ren, Emp_Rre, Emp_Con, Emp_Rco FROM empresas WHERE Emp_Cod='$Ses_Emp_Cod'", $obBD_conexion_get);
/* Informacion del Usuario logeado */
$rowUsr = $obBD_con_get->getRowConsultaSql("SELECT persona.Prs_Cod, Prs_Ced, Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Ses_Usu_Cod", $obBD_conexion_get);

if (isset($conciliaAjax)) {
    $data = array_merge($_GET, array(
        'where' => array("perio_cont.Pec_Cod" => $Pec_Cod, "banco.Ban_Cod" => $Ban_Cod),
        'order' => 'Cob_Fec DESC'
    ));
    $page = $obBD_con_get->getPageGrid('conciliacion_bancaria', $data);
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array_merge($data, array('setWhere' => array('isActive', 'setEmpCod'), 'limits' => 'LIMIT 0,1')));
    foreach ($page['rows'] as &$p) {
        if ($p['Cob_Cod'] == $last['Cob_Cod']) {
            $p['Cob_Last'] = 'S';
            break;
        }
    }
    unset($p);
    $obBD_con_get->echoJson($page);
}

if (isset($buscarDiarioConc)) {
    $diario = isset($Com_Codigo) ? trim((string) $Com_Codigo) : '';
    $diario = strtoupper(preg_replace('/\s+/', '', $diario));
    $comCod = isset($Com_Cod) ? trim((string) $Com_Cod) : '';
    $comCod = preg_replace('/\D+/', '', $comCod); // solo dígitos del Id

    $tieneDiario = ($diario !== '' && preg_match('/^[A-Z0-9\-]{3,40}$/', $diario));
    $tieneComCod = ($comCod !== '' && ctype_digit($comCod));

    if (!$tieneDiario && !$tieneComCod) {
        $obBD_con_get->echoJson(array(
            'success' => false,
            'message' => 'Ingrese Nro. Diario (Ej: RL-06-20) y/o Id Comprobante (Com_Cod).'
        ));
    }
    if (empty($Pec_Cod)) {
        $obBD_con_get->echoJson(array(
            'success' => false,
            'message' => 'Seleccione el <b>Periodo</b> en la b&uacute;squeda principal.'
        ));
    }

    $emp = intval($Ses_Emp_Cod);
    $extra = ' AND cb.Pec_Cod=' . intval($Pec_Cod) . ' AND c.Pec_Cod=' . intval($Pec_Cod);
    if (!empty($Ban_Cod)) {
        $extra .= ' AND cb.Ban_Cod=' . intval($Ban_Cod);
    }

    $exprCodigo = "CONCAT(ta.Tia_Abr, '-', LPAD(MONTH(c.Com_Fec), 2, '0'), '-', c.Com_Num)";
    $whereParts = array();
    $labelBusqueda = array();
    if ($tieneDiario) {
        $diarioEsc = addslashes($diario);
        $whereParts[] = "UPPER(REPLACE({$exprCodigo}, ' ', '')) = '{$diarioEsc}'";
        $labelBusqueda[] = 'diario <b>' . htmlspecialchars($diario) . '</b>';
    }
    if ($tieneComCod) {
        $whereParts[] = 'c.Com_Cod=' . intval($comCod);
        $labelBusqueda[] = 'Com_Cod <b>' . htmlspecialchars($comCod) . '</b>';
    }
    if ($tieneDiario && $tieneComCod) {
        $whereSql = '(' . implode(' AND ', $whereParts) . ')';
    } else {
        $whereSql = '(' . implode(' OR ', $whereParts) . ')';
    }
    $labelTxt = implode(' / ', $labelBusqueda);

    $sql = "
        SELECT
            cb.Cob_Cod, cb.Cob_Fec, cb.Cob_Obs, cb.Cob_Dis, cb.Cob_Est, cb.Ban_Cod, cb.Pec_Cod,
            p.Prs_Nom, det_plan.Pld_Des, banco.Ban_Cue,
            {$exprCodigo} AS Com_Codigo,
            c.Com_Cod, c.Com_Fec AS Com_Fec_Diario, a.Asi_Cod, a.Asi_Glo
        FROM conciliacion_banc_asientos cba
        INNER JOIN conciliacion_bancaria cb ON cb.Cob_Cod = cba.Cob_Cod AND cb.Cob_Est = 'A'
        INNER JOIN asientos a ON a.Asi_Cod = cba.Asi_Cod
        INNER JOIN comprobantes c ON c.Com_Cod = a.Com_Cod AND c.Com_Est = 'A'
        INNER JOIN tipo_asien ta ON ta.Tia_Cod = c.Tia_Cod
        INNER JOIN perio_cont ON perio_cont.Pec_Cod = cb.Pec_Cod
        INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod = perio_cont.Pla_Cod AND plan_cuenta.Emp_Cod = {$emp}
        INNER JOIN banco ON banco.Ban_Cod = cb.Ban_Cod
        INNER JOIN det_plan ON det_plan.Pld_Cod = banco.Pld_Cod
        LEFT JOIN usuarios ON usuarios.Usu_Cod = cb.Usu_Cod
        LEFT JOIN persona p ON p.Prs_Cod = usuarios.Prs_Cod
        WHERE {$whereSql}
        {$extra}
        ORDER BY cb.Cob_Fec DESC
    ";
    $rows = $obBD_con_get->getArrayConsultaSql($sql, $obBD_conexion_get);
    if (!is_array($rows)) {
        $rows = array();
    }
    $byCob = array();
    foreach ($rows as $r) {
        $cod = $r['Cob_Cod'];
        if (!isset($byCob[$cod])) {
            $byCob[$cod] = $r;
            $byCob[$cod]['Cob_Last'] = '';
        }
    }
    $conciliaciones = array_values($byCob);
    $obBD_con_get->echoJson(array(
        'success' => true,
        'Com_Codigo' => $tieneDiario ? $diario : (isset($conciliaciones[0]['Com_Codigo']) ? $conciliaciones[0]['Com_Codigo'] : ''),
        'Com_Cod' => $tieneComCod ? $comCod : (isset($conciliaciones[0]['Com_Cod']) ? $conciliaciones[0]['Com_Cod'] : ''),
        'records' => count($conciliaciones),
        'rows' => $conciliaciones,
        'message' => count($conciliaciones)
            ? ('La b&uacute;squeda por ' . $labelTxt . ' encontr&oacute; ' . count($conciliaciones) . ' conciliaci&oacute;n(es).')
            : ('No se encontr&oacute; ' . $labelTxt . ' en conciliaciones activas.')
    ));
}

if (isset($newConciliacion)) {

    $banco = $obBD_con_get->getRow('banco', array('where' =>
    array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));

    $last = $obBD_con_get->getRow(
        'conciliacion_bancaria',
        array(
            'where' => array(
                'conciliacion_bancaria.Ban_Cod' => $conc['Ban_Cod'],
                'conciliacion_bancaria.Pec_Cod' =>
                $conc['Pec_Cod']
            ),
            'setWhere' => array("isActive", "setEmpCod"),
            'order' => 'Cob_Fec DESC'
        )
    );

    if (is_null($last)) {
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    } else {
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    }

    // $asientos = $obBD_con_get->getArray('asientos', array('where' => array("Com_Fec<='{$conc['Cob_Fec']}'", 'Cob_Cod' => null, 'Pec_Cod' => $conc['Pec_Cod'], 'Pld_Cod' => $banco['Pld_Cod']), 'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'), 'order' => 'Com_Fec ASC', 'addCols' => array('' => array($obBD_con_get->expr("IF(Cob_Cod IS NULL,NULL,'S')AS conc_select"), $obBD_con_get->expr("'N' As conc_select")))));
    $asientos = $obBD_con_get->getArray(
        'asientos',
        array(
            'where' => array(
                "Com_Fec<='{$conc['Cob_Fec']}'",
                'Cob_Cod' => null,
                'Pec_Cod' => $conc['Pec_Cod'],
                'asientos.Pld_Cod' => $banco['Pld_Cod']
            ),
            'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'),
            'order' => 'Com_Fec ASC',
            'addCols' => array('' => array(
                $obBD_con_get->expr("IF(Cob_Cod IS NULL,NULL,'S') AS conc_select"),
                $obBD_con_get->expr("'N' As conc_select"),
                //Doc_Num
                // $obBD_con_get->expr(" CONCAT( ' Num.Transf.: ', Num_Doc) AS Num_Doc"), // modo anterior
                /*  $obBD_con_get->expr("
                    IF(Num_Doc IS NOT NULL AND Num_Doc != '', 
                        CONCAT('Num.Transf.: ', Num_Doc), 
                        NULL
                    ) AS Doc_Num
                ")*/
                // === Com_Cod_Rel: si existe relación en ventas_compr, úsala; sino, usa el Com_Cod del comprobante
                $obBD_con_get->expr("
                    IFNULL(
                        (SELECT vc.Com_Cod
                            FROM ventas_compr vc
                        WHERE vc.Com_Cod = comprobantes.Com_Cod LIMIT 1),
                        comprobantes.Com_Cod
                    ) AS Com_Cod_Rel
                "),

                // === Vet_Che SOLO para Pag_Cod = 8 (tomamos el más reciente si existe Pag_Fec; ajusta ORDER BY a tu campo de fecha/id)
                $obBD_con_get->expr("
                    (SELECT pv.Vet_Che
                    FROM ventas_compr vc
                            JOIN pago_venta pv ON pv.Vet_Cod = vc.Vet_Cod
                    WHERE vc.Com_Cod = comprobantes.Com_Cod
                        AND pv.Pag_Cod = 8 LIMIT 1) AS Vet_Che
                "),

                // === FormasPago: todas las formas de pago de la venta en una sola columna
                $obBD_con_get->expr("
                    (SELECT GROUP_CONCAT(
                                CONCAT(
                                    CASE
                                        WHEN pv.Pag_Cod = 8 THEN 'Trf: '
                                        WHEN pv.Pag_Cod = 9 THEN 'Dps: '
                                        ELSE CONCAT('Pago ', pv.Pag_Cod, ': ')
                                    END,
                                    pv.Vet_Che
                                )
                                SEPARATOR ' | '
                            )
                        FROM ventas_compr vc
                            JOIN pago_venta pv ON pv.Vet_Cod = vc.Vet_Cod
                        WHERE vc.Com_Cod = comprobantes.Com_Cod
                    ) AS FormasPago
                ")
            ))
        )
    );

    array_unshift($asientos, $last);
    list($asientos, $cobDispTotal) = tes_con_prepare_asientos_grid($asientos);
    $obBD_con_get->echoJson(array('success' => true, 'Cob_Last' => $last, 'Ban' => $banco, 'asientos' => $asientos, 'Cob_Disp_Total' => $cobDispTotal));
}


if (isset($editConciliacion)) {
    $conc = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array('Cob_Cod' => $Cob_Cod)));
    $banco = $obBD_con_get->getRow('banco', array('where' => array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array("Cob_Cod!={$conc['Cob_Cod']} AND Cob_Fec<'{$conc['Cob_Fec']}' AND conciliacion_bancaria.Ban_Cod={$conc['Ban_Cod']}"), 'setWhere' => array("isActive", "setEmpCod"), 'order' => 'Cob_Fec DESC'));
    if (is_null($last))
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    else
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    $asientos = $obBD_con_get->getArray('asientos', array(
        'where' => array(
            "Com_Fec<='{$conc['Cob_Fec']}'",
            "(Cob_Cod IS NULL OR Cob_Cod={$conc['Cob_Cod']})",
            'Pec_Cod' => $conc['Pec_Cod'],
            'asientos.Pld_Cod' => $banco['Pld_Cod']
        ),
        'setWhere' => array('comprobante', "saldo", "conciliacion", "tipo_pago", 'isActive'),
        'order' => 'pago_tipo,Com_Fec ASC',
        'addCols' => array('' => array($obBD_con_get->expr("IF(Cob_Cod IS NULL,'N','S')AS conc_select")))
    ));
    array_unshift($asientos, $last);
    list($asientos, $cobDispTotal) = tes_con_prepare_asientos_grid($asientos);
    $obBD_con_get->echoJson(array(
        'success' => true,
        'Cob' => $conc,
        'Cob_Last' => $last,
        'Ban' => $banco,
        'asientos' => $asientos,
        'Cob_Disp_Total' => $cobDispTotal
    ));
}
if (isset($viewConciliacion)) {
    require_once('../../contabilidad/LOGICA/con_log_planc_2.php');
    $obBD_con_cont = new Class_Log_Datos_Con();
    $obBD_con_cont->setConnection($obBD_conexion_get);
    $conc = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array('Cob_Cod' => $Cob_Cod), 'setWhere' => array()));
    $banco = $obBD_con_get->getRow('banco', array('where' => array('Ban_Cod' => $conc['Ban_Cod'], 'Pec_Cod' => $conc['Pec_Cod']), 'setWhere' => array("setPeriodo")));
    $last = $obBD_con_get->getRow('conciliacion_bancaria', array('where' => array("Cob_Cod!={$conc['Cob_Cod']} AND Cob_Fec<'{$conc['Cob_Fec']}' AND conciliacion_bancaria.Ban_Cod={$conc['Ban_Cod']}"), 'setWhere' => array("isActive", "setEmpCod"), 'order' => 'Cob_Fec DESC'));
    if (is_null($last))
        $last = array('Asi_Cod' => 'no_id', 'Asi_Sald' => '0.00', 'Com_Con' => 'Sin Conciliacion Anterior', 'Cob_Dis' => '0.00', 'pago_tipo' => ' ');
    else
        $last = array_merge($last, array('Asi_Cod' => 'no_id', 'Asi_Sald' => $last['Cob_Dis'], 'Com_Fec' => $last['Cob_Fec'], 'Com_Con' => 'Ultima Conciliación', 'pago_tipo' => ' '));
    $Asi_Cods = array();
    $asientos = $obBD_con_get->getArray('asientos', array('where' => array("Com_Fec<='{$conc['Cob_Fec']}'", "(conciliacion_bancaria.Cob_Cod IS NULL OR conciliacion_bancaria.Cob_Cod={$conc['Cob_Cod']})", 'comprobantes.Pec_Cod' => $conc['Pec_Cod'], 'Pld_Cod' => $banco['Pld_Cod']), 'Cob_Cod' => $conc['Cob_Cod'], 'Cob_Fec' => $conc['Cob_Fec'], 'setWhere' => array('comprobante', "saldo", "conciliacion_menor", "tipo_pago", 'isActive'), 'order' => 'tipo,pago_tipo,Com_Fec ASC', 'addCols' => array('' => array($obBD_con_get->expr("IF(conciliacion_bancaria.Cob_Cod={$conc['Cob_Cod']},'CONCILIADAS','EN TRANSITO PERIODO EN CURSO')AS tipo")))));
    /*foreach($asientos AS $as){
        if($as['tipo']=='CONCILIADAS')array_push($Asi_Cods,$as['Asi_Cod']);
    }*/
    $mayor = $obBD_con_cont->getRowConsulta(371, array('Pld_Cod' => $banco['Pld_Cod'], 'Pec_Cod' => $conc['Pec_Cod'], 'Year' => $conc['Pec_Year'], 'Fin' => $conc['Cob_Fec'], 'Inicio' => isset($last['Cob_Fec']) ? $last['Cob_Fec'] : $conc['Pec_Fei'], 'Asi_Cods' => $Asi_Cods));
    $mayor['Pld_Sal'] = (is_null($mayor['Debe']) ? 0 : $mayor['Debe']) - (is_null($mayor['Haber']) ? 0 : $mayor['Haber']);
    $obBD_con_get->echoJson(array('success' => true, 'asientos' => $asientos, 'Mayor' => $mayor, 'Cob' => $conc, 'Cob_Last' => $last, 'Ban' => $banco));
}
if (isset($updateConcilia)) {
    $resp = array('success' => false);
    $obBD_con_set = new Class_Log_Datos_Conciliacion(true);
    //$obBD_con_set->debug(true);
    $obBD_con_set->beginTrans();
    try {
        if (empty($form['Cob_Cod'])) {
            $Cob_Cod = $obBD_con_set->operation('conciliacion_bancaria.insert', array_merge($form, array('Usu_Cod' => $Ses_Usu_Cod)))->lastId();
        } else {
            $Cob_Cod = $form['Cob_Cod'];
            $obBD_con_set->operation('conciliacion_bancaria.update', array_merge($form, array('Usu_Cod' => $Ses_Usu_Cod)));
            $obBD_con_set->operation('conciliacion_banc_asientos.deleteWhere', array('where' => array('Cob_Cod' => $Cob_Cod)));
        }
        foreach ($asientos as $asi) {
            $obBD_con_set->operation('conciliacion_banc_asientos.insert', array('Asi_Cod' => $asi, 'Cob_Cod' => $Cob_Cod));
        }
        //throw new Exception("Se guardo correctamente, pero no!");
    } catch (Exception $e) {
        $obBD_con_set->rollB($e->getMessage(), $resp);
        $obBD_con_set->echoJson($resp);
    }
    $obBD_con_set->endTrans($resp); // finalizo la transaccion y compruebo errores
    $obBD_con_set->echoJson($resp);
}
if (isset($deleteConcilia)) {
    $resp = array('success' => false);
    $obBD_con_set = new Class_Log_Datos_Conciliacion(true);
    //$obBD_con_set->debug(true);
    $obBD_con_set->beginTrans();
    try {
        if (empty($Cob_Cod)) throw new Exception("Error No se recibio el codigo!");
        $obBD_con_set->operation('conciliacion_banc_asientos.deleteWhere', array('where' => array('Cob_Cod' => $Cob_Cod)));
        $obBD_con_set->operation('conciliacion_bancaria.setInactive', array('Cob_Cod' => $Cob_Cod));
        //throw new Exception("Se guardo correctamente, pero no!");
    } catch (Exception $e) {
        $obBD_con_set->rollB($e->getMessage(), $resp);
        $obBD_con_set->echoJson($resp);
    }
    $obBD_con_set->endTrans($resp); // finalizo la transaccion y compruebo errores
    $obBD_con_set->echoJson($resp);
}
if ($docDetalle) {
    $com = $obBD_con_get->getRow('comprobantes', array('Com_Cod' => $Com_Cod, 'setWhere' => array("data")));
    $asientos = $obBD_con_get->getArray('asientos', array('Com_Cod' => $Com_Cod, 'setWhere' => array("data")));
    $obBD_con_get->echoJson(array('success' => true, 'comprobante' => $com, 'asientos' => $asientos));
}
$periodos = $obBD_con_get->getArrayConsulta('perio_cont', array('perio_cont.Pec_Est' => 'A', 'setWhere' => 'setEmpCod', 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion_get);
$bancos = $obBD_con_get->getArrayConsulta('banco', array('setWhere' => array('setPeriodo', 'isActive', 'isTipo', "setEmpCod")), $obBD_conexion_get);
?>
<!DOCTYPE html>
<HTML>

<haed>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Conciliacion [EXA]"; ?></TITLE>    
    <meta charset="UTF-8">    
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <style></style>
    </head>
    <style>
        #gview_jqGridRep {
            height: 350px !important;
        }
        #gview_conciliacionForm .ui-jqgrid-htable th[id$="_conc_select"] {
            text-align: center;
            vertical-align: middle;
        }
        #gview_conciliacionForm .ui-jqgrid-htable th[id$="_conc_select"] input[type=checkbox] {
            margin: 0 4px;
            vertical-align: middle;
            -ms-transform: scale(1.6);
            -moz-transform: scale(1.6);
            -webkit-transform: scale(1.6);
            -o-transform: scale(1.6);
            transform: scale(1.6);
        }
        .conc-grid-wrap {
            position: relative;
            min-height: 500px;
        }
        #concGridProgress {
            margin: 0 0 8px 0;
            padding: 8px 10px;
            background: #f5f7fa;
            border: 1px solid #d0d7de;
            border-radius: 3px;
        }
        #concGridProgress .conc-prog-label {
            font-size: 12px;
            color: #333;
            margin-bottom: 6px;
        }
        #concGridProgress .conc-prog-track {
            height: 10px;
            background: #e6ebf0;
            border-radius: 5px;
            overflow: hidden;
        }
        #concGridProgress .conc-prog-bar {
            height: 100%;
            width: 0%;
            background: #0f766e;
            border-radius: 5px;
            transition: width .12s linear;
        }
        #concGridProgress .conc-prog-bar.is-wait {
            width: 35% !important;
            background: #1d4ed8;
            animation: none;
        }
        #concGridProgress .conc-prog-spin {
            margin-right: 4px;
            animation: concProgSpin .8s linear infinite;
        }
        #concGridBusy {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: 42px;
            bottom: 0;
            z-index: 5;
            background: rgba(255,255,255,.35);
            cursor: wait;
        }
        @keyframes concProgSlide {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        @keyframes concProgSpin {
            to { transform: rotate(360deg); }
        }
        .conc-tipo-legend {
            margin: 0 0 6px 0;
            font-size: 12px;
            color: #444;
        }
        .conc-tipo-legend .conc-tipo-item {
            display: inline-block;
            margin-right: 14px;
            white-space: nowrap;
        }
        .conc-tipo-ico {
            font-size: 14px;
            line-height: 1;
        }
        #gview_conciliacionForm .ui-jqgrid-htable th[id$="_pago_tipo"] {
            text-align: center;
        }
        /* Filas título (separadores de tipo) */
        #conciliacionForm tr.conc-group-hdr td {
            background: #e8eaf6 !important;
            border-top: 2px solid #5c6bc0 !important;
            border-bottom: 1px solid #9fa8da !important;
            font-weight: bold;
            color: #283593;
            height: 28px;
            vertical-align: middle;
        }
        #conciliacionForm tr.conc-group-hdr td input,
        #conciliacionForm tr.conc-group-hdr td button,
        #conciliacionForm tr.conc-group-hdr .glyphicon-list-alt {
            display: none !important;
        }
        #conciliacionForm tr.conc-group-hdr td[aria-describedby="conciliacionForm_Com_Codigo"] {
            text-align: left !important;
            font-size: 13px;
            overflow: visible !important;
            position: relative;
            z-index: 2;
        }
        #conciliacionForm tr.conc-group-hdr td[aria-describedby="conciliacionForm_Com_Codigo"] .conc-hdr-wrap {
            display: inline-block;
            white-space: nowrap;
            min-width: 280px;
        }
        #conciliacionForm tr.conc-group-hdr td[aria-describedby="conciliacionForm_Com_Codigo"] .conc-hdr-title {
            margin-left: 2px;
            letter-spacing: .3px;
        }
        #conciliacionForm tr.conc-group-hdr td[aria-describedby="conciliacionForm_Com_Codigo"] .conc-hdr-count {
            margin-left: 8px;
            color: #3949ab;
            font-weight: bold;
            font-size: 12px;
        }
        #conciliacionForm tr.conc-group-hdr td[aria-describedby="conciliacionForm_pago_tipo"] {
            text-align: center;
        }
        .conc-tipo-ico.fa {
            font-size: 15px;
        }

        /* ===== Filtros compactos (Búsqueda + Nueva/Editar) ===== */
        #buscaDiv .filtros-row,
        #editDiv .filtros-row {
            margin-left: -6px;
            margin-right: -6px;
            margin-bottom: 8px;
        }
        #buscaDiv .filtros-row > [class*="col-"],
        #editDiv .filtros-row > [class*="col-"] {
            padding-left: 6px;
            padding-right: 6px;
        }
        #buscaDiv .filtros-section,
        #editDiv .filtros-section {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 6px 10px 6px;
            margin-bottom: 0;
            background: #fff;
            min-height: 72px;
        }
        #buscaDiv .filtros-section > legend.Titulos2,
        #editDiv .filtros-section > legend.Titulos2 {
            width: auto;
            margin-bottom: 4px;
            font-size: 12px;
            border: 0;
            padding: 0 4px;
            line-height: 1.2;
        }
        #buscaDiv .filtros-section .form-group,
        #editDiv .filtros-section .form-group {
            margin-bottom: 0;
        }
        #buscaDiv .filtros-section .form-control.input-xs,
        #editDiv .filtros-section .form-control.input-xs {
            height: 26px;
            padding: 2px 6px;
        }
        #buscaDiv .filtros-section .input-group-addon,
        #editDiv .filtros-section .input-group-addon,
        #buscaDiv .filtros-section .input-group-btn > .btn,
        #editDiv .filtros-section .input-group-btn > .btn {
            padding: 3px 6px;
            font-size: 11px;
            height: 26px;
            line-height: 1.4;
        }
        #buscaDiv .filtros-section .btn-xs,
        #editDiv .filtros-section .btn-xs {
            height: 26px;
            padding: 2px 8px;
            line-height: 1.4;
        }
        #buscaDiv .filtros-inline,
        #editDiv .filtros-inline {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }
        #buscaDiv .filtros-inline + .filtros-inline,
        #editDiv .filtros-inline + .filtros-inline {
            margin-top: 6px;
        }
        #buscaDiv .filtros-inline .fi-label,
        #editDiv .filtros-inline .fi-label {
            flex: 0 0 auto;
            margin: 0;
            font-size: 11px;
            font-weight: 600;
            color: #555;
            white-space: nowrap;
        }
        #buscaDiv .filtros-inline .fi-period,
        #editDiv .filtros-inline .fi-period {
            flex: 0 0 90px;
        }
        #buscaDiv .filtros-inline .fi-bank,
        #editDiv .filtros-inline .fi-bank {
            flex: 1 1 160px;
            min-width: 140px;
        }
        #buscaDiv .filtros-inline .fi-diario {
            flex: 1 1 150px;
            min-width: 140px;
        }
        #buscaDiv .filtros-inline .fi-id {
            flex: 0 0 110px;
        }
        #buscaDiv .filtros-inline .fi-btn,
        #editDiv .filtros-inline .fi-btn {
            flex: 0 0 auto;
        }
        #editDiv .filtros-inline .fi-date {
            flex: 0 0 120px;
        }
        #editDiv .filtros-inline .fi-saldo {
            flex: 0 0 110px;
            max-width: 110px;
        }
        #editDiv .dato-banco-align {
            display: grid;
            grid-template-columns: auto 90px auto minmax(0, 1fr);
            column-gap: 6px;
            row-gap: 6px;
            align-items: center;
        }
        #editDiv .dato-banco-align > .fi-label {
            margin: 0;
        }
        #editDiv .dato-banco-align .fi-period {
            width: 90px;
            max-width: 90px;
        }
        #editDiv .dato-banco-align .fi-bank {
            min-width: 0;
        }
        #editDiv .dato-banco-align .fi-last-spacer {
            grid-column: 1 / 4;
        }
        #editDiv .dato-banco-align .fi-last-row {
            grid-column: 4;
            width: 100%;
            margin-top: 0;
        }
        #editDiv .fi-last-eq {
            flex: 1 1 0;
            min-width: 0;
        }
        #editDiv .fi-last-eq .fi-ro {
            width: 100%;
            box-sizing: border-box;
        }
        #editDiv .filtros-inline .fi-obs {
            flex: 1 1 auto;
            min-width: 0;
        }
        #editDiv .filtros-inline .fi-obs textarea {
            width: 100%;
            height: 26px;
            min-height: 26px;
            resize: vertical;
            padding: 2px 6px;
            font-size: 12px;
            line-height: 1.4;
        }
        #editDiv .filtros-inline .fi-ro {
            display: block;
            height: 26px;
            padding: 3px 6px;
            font-size: 12px;
            line-height: 1.4;
            background: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #buscaDiv #ubicarPeriodoBadge {
            min-width: 44px;
            font-weight: 700;
            background: #d9edf7;
            color: #31708f;
            border-color: #bce8f1;
        }
        #buscaDiv #buscarDiarioMsg {
            display: block;
            margin: 4px 0 0;
            font-size: 11px;
            line-height: 1.25;
            min-height: 14px;
        }
        #buscaDiv .conc-grid-shell {
            margin-top: 4px;
        }
        #buscaDiv .conc-actions,
        #editDiv .conc-actions {
            margin-top: 8px;
        }
        #editDiv .conc-form-actions {
            margin-top: 0px;
            padding-top: 8px;
            clear: both;
        }
        #editDiv .conc-form-actions .btn {
            margin-right: 8px;
        }
        @media (max-width: 767px) {
            #buscaDiv .filtros-section,
            #editDiv .filtros-section {
                margin-bottom: 8px;
            }
        }
    </style>
    <script>var Usu_Adm=<?Php echo $Ses_Prs_Cod;?></script>
    <BODY>
        <div id="buscaDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Buscar Conciliaciones</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="row filtros-row">
                            <!-- Sección 1: Búsqueda de conciliaciones -->
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset filtros-section">
                                    <legend class="Titulos2">B&uacute;squeda</legend>
                                    <form name="searchConcilia" id="searchConcilia" method="get" class="form-horizontal normal" action="javascript:$('#conciliacion').Search('#searchConcilia','conciliaAjax');">
                                        <div class="filtros-inline">
                                            <label class="fi-label required">Periodo:</label>
                                            <div class="fi-period">
                                                <select name="Pec_Cod" class="form-control input-xs" required="" style="text-align:center;" onchange="loadBancos($(this).val()); syncUbicarPeriodoBadge();">
                                                    <option value="">..</option>
                                                    <?php foreach ($periodos as $p) {
                                                        echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' value='$p[Pec_Cod]' " . (count($periodos) == 1 ? 'selected=""' : '') . ">$p[Year]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <label class="fi-label required">Banco:</label>
                                            <div class="fi-bank">
                                                <select name="Ban_Cod" class="form-control input-xs" required="">
                                                    <option value="">Banco..</option>
                                                    <?php foreach ($bancos as $b) {
                                                        echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' style='display:none;' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="fi-btn">
                                                <button type="submit" class="btn btn-success btn-xs" title="Mostrar Conciliaciones">
                                                    <span class="glyphicon glyphicon-search"></span> Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                            <!-- Sección 2: Ubicar comprobante -->
                            <div class="col-sm-6">
                                <fieldset class="exa-fieldset filtros-section">
                                    <legend class="Titulos2">Ubicar Comprobante</legend>
                                    <form name="searchDiarioConc" id="searchDiarioConc" method="get" class="form-horizontal normal" action="javascript:buscarDiarioConciliacion();">
                                        <div class="filtros-inline">
                                            <label class="fi-label">Diario:</label>
                                            <div class="fi-diario">
                                                <div class="input-group input-group-xs" style="width:100%;">
                                                    <span class="input-group-addon alert-info" id="ubicarPeriodoBadge" title="Periodo seleccionado">—</span>
                                                    <input type="text" name="Com_Codigo" id="buscarDiarioInput" class="form-control input-xs" placeholder="RL-06-20" maxlength="40" style="text-align:center;" />
                                                </div>
                                            </div>
                                            <label class="fi-label">Id:</label>
                                            <div class="fi-id">
                                                <input type="text" name="Com_Cod" id="buscarComCodInput" class="form-control input-xs" placeholder="Com_Cod" maxlength="12" />
                                            </div>
                                            <div class="fi-btn">
                                                <button type="submit" class="btn btn-info btn-xs" title="Ubicar en qu&eacute; conciliaci&oacute;n est&aacute;">
                                                    <span class="glyphicon glyphicon-filter"></span> Ubicar
                                                </button>
                                            </div>
                                        </div>
                                        <span id="buscarDiarioMsg"></span>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="conc-grid-shell" style="min-height:300px;">
                            <table id="conciliacion"></table>
                            <div id="conciliacionPager"></div>
                        </div>
                    </div>
                    <div class="col-xs-12 conc-actions">
                        <button class="btn btn-sm btn-primary" onclick="nuevaConciliacion();"><i class="glyphicon glyphicon-plus"></i> Nueva Conciliaci&oacute;n</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="editDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title" id="editDivTitle">&raquo; Editar Conciliaci&oacute;n</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-xs-12">
                        <form name="formConcilia" id="formConcilia" method="get" class="form-horizontal normal" action="javascript:validarForm();">
                            <div class="row filtros-row">
                                <!-- Sección 1: Dato Banco + Última -->
                                <div class="col-sm-6">
                                    <fieldset class="exa-fieldset filtros-section">
                                        <legend class="Titulos2">Dato Banco</legend>
                                        <div class="dato-banco-align">
                                            <label class="fi-label required">Periodo:</label>
                                            <div class="fi-period">
                                                <select name="Pec_Cod" class="form-control input-xs readOnly" required="" onchange="loadBancos2($(this).val()); syncCobFecDateLimits();">
                                                    <option value="">..</option>
                                                    <?php foreach ($periodos as $p) {
                                                        echo "<option data--year='$p[Year]' data--pec_-cod='$p[Pec_Cod]' data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' " . (count($periodos) == 1 ? 'selected="" default=""' : '') . ">$p[Year]</option>";
                                                    } ?>
                                                </select>
                                            </div>
                                            <label class="fi-label required">Banco:</label>
                                            <div class="fi-bank">
                                                <div class="input-group input-group-xs" style="width:100%;">
                                                    <select name="Ban_Cod" class="form-control input-xs readOnly" required="" onchange="">
                                                        <option value="">Banco..</option>
                                                        <?php foreach ($bancos as $b) {
                                                            echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                                        } ?>
                                                    </select>
                                                    <span class="input-group-btn">
                                                        <button id="loadAsientosBtn" type="button" onclick="loadAsientos();" class="btn btn-success btn-xs" title="Cargar Asientos">
                                                            <span class="glyphicon glyphicon-refresh"></span>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fi-last-spacer" aria-hidden="true"></div>
                                            <div class="filtros-inline fi-last-row">
                                                <label class="fi-label">Última:</label>
                                                <div class="fi-last-eq">
                                                    <span data-last="Cob_Fec" class="fi-ro" title="Fecha última conciliación"></span>
                                                </div>
                                                <label class="fi-label">Saldo:</label>
                                                <div class="fi-last-eq">
                                                    <span data-last="Cob_Dis" class="fi-ro" title="Saldo última conciliación"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <!-- Sección 2: Conciliación -->
                                <div class="col-sm-6">
                                    <fieldset class="exa-fieldset filtros-section">
                                        <legend class="Titulos2">Conciliaci&oacute;n</legend>
                                        <input type="hidden" name="Cob_Cod" data-cob="Cob_Cod" />
                                        <div class="filtros-inline">
                                            <label class="fi-label required">Fecha:</label>
                                            <div class="fi-date">
                                                <input id="Cob_Fec" name="Cob_Fec" data-cob="Cob_Fec" type="text" class="form-control input-xs readOnly" required="" placeholder="yyyy-mm-dd" />
                                            </div>
                                            <label class="fi-label required">Saldo Banco:</label>
                                            <div class="fi-saldo">
                                                <input id="Cob_Dis" name="Cob_Dis" data-cob="Cob_Dis" type="number" class="form-control input-xs nospin readOnly" required="" />
                                            </div>
                                        </div>
                                        <div class="filtros-inline">
                                            <label class="fi-label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Obs.:</label>
                                            <div class="fi-obs">
                                                <textarea name="Cob_Obs" data-cob="Cob_Obs" class="form-control input-xs" rows="1"></textarea>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-xs-12 conc-grid-wrap">
                        <div id="concGridProgress" style="display:none;">
                            <div class="conc-prog-label">
                                <span class="glyphicon glyphicon-refresh conc-prog-spin"></span>
                                <span class="conc-prog-text">Cargando asientos...</span>
                            </div>
                            <div class="conc-prog-track">
                                <div class="conc-prog-bar"></div>
                            </div>
                        </div>
                        <div class="conc-tipo-legend">
                            <span class="conc-tipo-item"><i class="fa fa-history conc-tipo-ico" style="color:#1565c0;"></i> Conciliación Anterior</span>
                            <span class="conc-tipo-item"><i class="fa fa-hand-o-up conc-tipo-ico" style="color:#ef6c00;"></i> Manuales</span>
                            <span class="conc-tipo-item"><i class="fa fa-money conc-tipo-ico" style="color:#2e7d32;"></i> Cheques</span>
                        </div>
                        <div id="concGridBusy"></div>
                        <table id="conciliacionForm"></table>
                        <div id="conciliacionFormPager"></div>
                    </div>                 
                    
                    <div class="col-xs-12 conc-form-actions">
                        <button class="btn btn-sm btn-inverse" onclick="$('#editDiv').moveComp('#buscaDiv').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atr&aacute;s</button>
                        <button class="btn btn-sm btn-primary" onclick="$('#formConcilia').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    </div>

                </div>
            </div>
        </div>

        <div id="viewDiv" class="panel panel-main" style="display:none">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Buscar Conciliaciones</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="form-horizontal">
                    <fieldset class="exa-fieldset" id="formConciliacion">
                        <legend class="Titulos2">Información</legend>
                        <div class="form-group">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label for="Cob_Fec" class="control-label label-xs col-form-label col-xs-2" title="Fecha Conciliacion">Fecha</label>
                                    <div class="col-xs-4"><span type="text" name="Cob_Fec" class="form-control input-xs databind"></span></div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-2" title="Cta. Bancaria">Banco</label>
                                    <div class="col-xs-10 formBaem" id="editBaemId">
                                        <select id="conc_baem_id" name="Ban_Cod" class="form-control input-xs bancoImage readOnly" disabled="" style="background-color:rgba(255,255,255,0.25) !important;">
                                            <option value=''>Seleccione Banco...</option>
                                            <?php foreach ($bancos as $b) {
                                                echo "<option pec='$b[Pec_Cod]' data--pld_-cod='$b[Pld_Cod]' data--ban_-cod='$b[Ban_Cod]' value='$b[Ban_Cod]' " . (count($bancos) == 1 ? 'selected=""' : '') . ">$b[Pld_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">SALDO ANTERIOR BANCO:</label>
                                    <div class="col-xs-4"><span type="text" name="Asi_Sald" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-4"></div>
                                    <div class="col-xs-8">
                                        <table id="jqGridRepBanc" class="gridRepResumen" title="CONCILIACION"></table>
                                        <div id="jqGridRepBancPager"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">(=) SALDO CTA. BANCARIA:</label>
                                    <div class="col-xs-4"><span type="text" name="Tot_Cob_Dis" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label for="conc_obse" class="control-label label-xs col-form-label col-xs-2">Observación</label>
                                    <div class="col-xs-10"><span type="text" name="Cob_Obs" class="form-control input-xs databind"></span></div>
                                </div>
                                <div class="form-group formBaem" id="conc_cta">
                                    <label class="control-label label-xs col-form-label col-xs-2" title="Cta. Contable">Cta.</label>
                                    <div class="col-xs-10">
                                        <div class="input-group input-group-xs">
                                            <span name="Pld_Cdc" class="input-group-addon databind bold"> </span><span name="Pld_Des" class="form-control databind datatitle"> </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">SALDO LIBRO MAYOR:</label>
                                    <div class="col-xs-4"><span type="text" name="Pld_Sal" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-4"></div>
                                    <div class="col-xs-8">

                                        <table id="jqGridRepCta" class="gridRepResumen" title="EN TRANSITO"></table>
                                        <div id="jqGridRepCtaPager"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label label-xs col-form-label col-xs-8">(=) SALDO CTA. BANCARIA:</label>
                                    <div class="col-xs-4"><span type="text" name="Tot_Pld_Sal" class="form-control input-xs databind isNumber txt-right bold" decimals='2'></span></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <table id="jqGridRep"></table>
                            <div id="jqGridRepPager"></div>
                        </div>
                    </div>
                    <div style="padding-top:10px;">
                        <button class="btn btn-sm btn-inverse" onclick="$('#viewDiv').moveComp('#buscaDiv').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                        <button class="btn btn-sm btn-primary" type="button" onclick="imprimir();"><i class="glyphicon glyphicon-print"></i> Imprimir </button>
                        <button class="btn btn-sm btn-primary" type="button" onclick="exportar();"><i class="glyphicon glyphicon-download-alt"></i> Exportar </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="docDetaDialog" title="Documento">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Comprobante Contable:</legend>
                <div class="form-horizontal normal" style="padding: 0 4px;">
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Asiento:</label>
                        <div class="col-xs-4"><span name="Tia_Ini_Long" class="form-control input-xs"></span></div>
                        <label class="col-xs-2 control-label label-xs">Tipo:</label>
                        <div class="col-xs-4"><span name="Tia_Des" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">C&eacute;dula/RUC:</label>
                        <div class="col-xs-4"><span name="Inv_Ced" class="form-control input-xs"></span></div>
                        <label class="col-xs-2 control-label label-xs">Codigo:</label>
                        <div class="col-xs-4"><span name="Com_Codigo" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Proveedor:</label>
                        <div class="col-xs-6"><span name="Inv_Nom" class="form-control input-xs"></span></div>
                        <label class="col-xs-1 control-label label-xs">Fecha:</label>
                        <div class="col-xs-3"><span name="Com_Fec" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Concepto:</label>
                        <div class="col-xs-10"><span name="Com_Con" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Observación:</label>
                        <div class="col-xs-10"><span name="Com_Obs" class="form-control input-xs"></span></div>
                    </div>
                    <div class="form-group condensed">
                        <div class="col-xs-12">
                            <div class="pull-right">
                                <table id="detaDocu"></table>
                            </div>
                        </div>
                        <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACI&Oacute;N:</b> <span name="Com_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="Usu_Nom" class="databind"></span></div>
                    </div>
                </div>
            </fieldset>
        </div>
        <div>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" id="tableExtraInfo" style="display:none;">
                <tbody>
                    <tr>
                        <td width="11%"></td>
                        <td width="39%"></td>
                        <td width="11%"></td>
                        <td width="39%"></td>
                    </tr>
                    <tr class="head">
                        <td colspan="2" style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);"></td>
                        <td style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);font-weight:bold;text-align:right;">CORTE:</td>
                        <td style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);font-weight:normal;"><span name="Cob_Fec" class="form-control"></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;border-bottom:0.1pt solid rgb(102, 102, 102); border-top:0.1pt solid rgb(102, 102, 102);">OBSERVACIÓN:</td>
                        <td colspan="3" style="border-bottom: 0.1pt solid rgb(102, 102, 102); border-top: 0.1pt solid rgb(102, 102, 102);white-space:nowrap; overflow:hidden;"><span name="Cob_Obs" class="form-control"></span></td>
                    </tr>
                    <tr>
                        <td colspan="4"><br /><br /></td>
                    </tr>
                    <tr>
                        <td align="center" valign="top" colspan="2">
                            <table width="86%" border="0" cellpadding="0" cellspacing="0" class="mayor"></table>
                        </td>
                        <td align="center" valign="top" colspan="2">
                            <table width="86%" border="0" cellpadding="0" cellspacing="0" class="banco"></table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4"><br /><br /></td>
                    </tr>
                </tbody>
            </table>
            <div id="imprimir" style="display: none;">
                <div style="width: 1030px;">
                    <style>
                        #imprimir .form-control {
                            border: 0 !important;
                        }
                    </style>
                    <div style="margin-left:5%;">
                        <?php echo $obBD_con_get->getReportHeader($Ses_Suc_Cod, '<div style="text-align:center">CONCILIACION BANCARIA</div>', '<span class="subtitle"></span>', $obBD_conexion_get, false) ?>
                    </div>
                    <table id="tablereportExtra" cellspacing="0" cellpadding="0" style="border-collapse:collapse; table-layout:fixed;/*font-size:11px;*/" width="100%" class='tableReporteExtra'></table>
                    <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>
                    <?php echo $obBD_con_get->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion_get); ?>
                    <br><br>
                    <table width="700" border="0" align="center" cellpadding="0" cellspacing="0" style="font-size: 11px; margin-top: 30px;">
                        <tr>
                            <td align="center" width="45%" valign="top">
                                ________________________________<br>
                                <strong>ELABORADO POR</strong><br>
                                <?php echo $rowUsr['Prs_Ape'] . ' ' . $rowUsr['Prs_Nom']; ?>
                            </td>
                            <td width="10%">&nbsp;</td>
                            <td align="center" width="45%" valign="top">
                                ________________________________<br>
                                <strong>CONTADOR</strong><br>
                                <?php echo $infoFirmas['Emp_Con']; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div id="exportar" style="display: none;">
                <?php echo $obBD_con_get->getReportHeader($Ses_Suc_Cod, '<div style="text-align:center">CONCILIACION BANCARIA</div>', '<div style="text-align:center"><span class="subtitle"></span></div>', $obBD_conexion_get, false, 6) ?>
            </div>
        </div>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
        <script type="text/javascript">
            let selBan1, selBan2;
            $(function() {
                $('#detaDocu').createGrid({
                    height: 'auto',
                    width: 550,
                    responsive: false,
                    rownumbers: false,
                    caption: 'Detalle Comprobante',
                    footerrow: true,
                    userDataOnFooter: true,
                    datatype: 'local',
                    totalCols: ['Asi_Debe', 'Asi_Haber'],
                    colModel: [
                        { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15, align: "center", hidden: true },
                        { label: 'Codigo ', name: 'Pld_Cdc', width: 35 },
                        { label: 'Cuenta', name: 'Pld_Des', width: 120 },
                        { label: 'Glosa.', name: 'Asi_Glo', width: 60, align: 'right' },
                        { label: 'Debe', name: 'Asi_Debe', width: 30, align: 'right', formatter: 'currency',
                            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                            summaryType: "sum"
                        },
                        { label: 'Haber', name: 'Asi_Haber', width: 30, align: 'right', formatter: 'currency',
                            formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                            summaryType: "sum"
                        }
                    ]
                }, true);
                $('#docDetaDialog').createDialog({
                    height: 400,
                    width: 600,
                    noTitleStuff: false,
                    noBorder: true
                });

                $('#buscaDiv,#editDiv,#viewDiv').initDivs({
                    editDiv: () => {
                        $("#conciliacionForm").createGrid({
                            pager: '#conciliacionFormPager',
                            local: true,
                            height: 450,
                            postData: $('#formConcilia').getData(),
                            // Paginación local: solo se pintan ~100 filas (carga rápida)
                            rowNum: 100,
                            rowList: [50, 100, 250, 500],
                            viewrecords: true,
                            pgbuttons: true,
                            pginput: true,
                            pgtext: 'Pág. {0} de {1}',
                            footerrow: true,
                            userDataOnFooter: true,
                            datatype: 'local',
                            gridview: true,
                            caption: 'Asientos No Conciliados',
                            stateCol: 'conc_select',
                            stateConfig: {
                                S: 'cellGreen2'
                            },
                            stateCondition: function(v) {
                                if (isConcHeaderRow(v)) return '__hdr__';
                            },
                            totalCols: ['Asi_Sald'],
                            rowattr: function(rd) {
                                if (isConcHeaderRow(rd)) {
                                    return { 'class': 'conc-group-hdr' };
                                }
                            },
                            colModel: [
                                { label: ' ', name: 'conc_select', width: 20, align: 'center', title: false, sortable: false, formatter: 'checkboxExa',
                                    formatoptions: {
                                        defaultChecked: false,
                                        dataEvents: {
                                            change: 'var _id=$(this).data("rowId"); var _v=$(this).is(":checked")?"S":"N"; setConcSelectValue(_id,_v); updateTotal(_id);'
                                        },
                                        conditional: o => !isConcHeaderRow(o) && o.Asi_Cod !== 'no_id'
                                    }
                                },
                                { label: 'Id', name: 'Com_Cod', width: 20, hidden: true },
                                { label: 'Id', name: 'Asi_Cod', key: true, width: 20, hidden: true },
                                { label: '<i class="fa fa-tags" title="Tipo"></i>', name: 'pago_tipo', width: 8, align: 'center', sortable: false, title: false,
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) {
                                            var m = getConcTipoMeta(rowObject.hdr_title || cellValue, rowObject);
                                            return '<i class="fa ' + m.icon + ' conc-tipo-ico" style="color:' + m.color + ';" title="' + m.title + '"></i>';
                                        }
                                        return formatConcTipoIcon(cellValue, rowObject);
                                    }
                                },
                                { label: 'Codigo', name: 'Com_Codigo', width: 55, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) {
                                            var title = rowObject.hdr_title || 'Grupo',
                                                cnt = rowObject.hdr_count || 0;
                                            return '<span class="conc-hdr-wrap"><span class="conc-hdr-title">' + title.toUpperCase() + '</span>' +
                                                '<span class="conc-hdr-count">' + cnt + ' Operacion(es)</span></span>';
                                        }
                                        return cellValue == null ? '' : cellValue;
                                    }
                                },
                                { label: 'Fecha', name: 'Com_Fec', width: 40, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        return isConcHeaderRow(rowObject) ? '' : (cellValue == null ? '' : cellValue);
                                    }
                                },
                                { label: 'Doc.', name: 'Doc_Num', width: 40, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) return '';
                                        return cellValue ? 'Che: ' + cellValue : '';
                                    }
                                },

                                // { label: 'Nro. Documento.', name: 'Num_Doc', width: 40, align: 'center' }, // forma anterior

                                { label: 'Nro. Documento.', name: 'Num_Doc', width: 50, align: 'center',
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) return '';
                                        let doc = cellValue ? cellValue : '';
                                        let vet = rowObject.FormasPago ? rowObject.FormasPago : '';
                                        // Si FormasPago ya incluye el prefijo (Trf: / Dps: / Pago X:), solo mostrar ese campo
                                        if (vet) {
                                            return vet;
                                        } else if (doc) {
                                            return 'Trf: ' + doc;
                                        } else {
                                            return '';
                                        }
                                    }
                                },
                                { label: 'Clie./Provee.', name: 'Inv_Nom', width: 75,
                                    formatter: function(cellValue, options, rowObject) {
                                        return isConcHeaderRow(rowObject) ? '' : (cellValue == null ? '' : cellValue);
                                    }
                                },
                                { label: 'Observación', name: 'Asi_Glo', width: 100,
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) return '';
                                        return cellValue == null ? '' : cellValue;
                                    }
                                },

                                //{ label: 'Debe', name: 'cmpd_debe', width: 45, formatter:'number', formatoptions:{defaultValue:''}, summaryType:'sum' },
                                //{ label: 'Haber', name: 'cmpd_habe', width: 45, formatter:'number', formatoptions:{defaultValue:''}, summaryType:'sum' },

                                { label: 'Sumas', name: 'Asi_Sald', width: 45, align: 'right',
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject) || cellValue == null || cellValue === '') return '';
                                        var n = parseFloat(cellValue);
                                        return isNaN(n) ? '' : n.toFixed(2);
                                    },
                                    summaryType: 'sumNotInit', hidden: false
                                },
                                { label: 'Saldo', name: 'Cob_Disp', width: 45, align: 'right',
                                    formatter: function(cellValue, options, rowObject) {
                                        if (isConcHeaderRow(rowObject)) return '';
                                        if (cellValue == null || cellValue === '') return '--';
                                        var n = parseFloat(cellValue);
                                        return isNaN(n) ? '--' : n.toFixed(2);
                                    },
                                    classes: 'columnHighlight3'
                                },
                                { label: 'Info.', name: 'view', width: 20, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewInfo', data: 'Com_Cod', type: 'primary', title: 'Ver Comprobante', icon: 'list-alt',
                                        conditional: o => !isConcHeaderRow(o) && $.vv(o.Asi_Cod) && o.Asi_Cod !== '' && o.Asi_Cod !== 'no_id'
                                    }
                                }
                            ],
                            notStriped: true,
                            loadonce: true,
                            grouping: false,
                            gridComplete: function() {
                                styleConcHeaderRows();
                                injectConcSelectHeader();
                                syncConcSelectHeader();
                            }
                        }, true, '#conciliacionFormPager');
                        // createGrid(local:true) desactiva el pager; lo reactivamos
                        $("#conciliacionForm").jqGrid('setGridParam', {
                            grouping: false,
                            rowNum: 100,
                            rowList: [50, 100, 250, 500],
                            pgbuttons: true,
                            pginput: true,
                            pgtext: 'Pág. {0} de {1}',
                            viewrecords: true
                        });
                        $('#conciliacionFormPager_center, #conciliacionFormPager td[id$=Pager_center]').show();
                        $("#conciliacionForm").jqGrid('footerData', 'set', {
                            cmpd_glos: '<div class="txtRight">TOTAL:</div>'
                        }, false);
                        $("#conciliacionForm").on('jqGridAfterLoadComplete', function() {
                            styleConcHeaderRows();
                            injectConcSelectHeader();
                            syncConcSelectHeader();
                        });
                    },

                    buscaDiv: () => {
                        $("#conciliacion").createGrid({
                            stateCol: 'Cob_Est',
                            stateConfig: { I: 'cellRed2' },
                            rowNum: 1000000,
                            height: 300,
                            footerrow: true,
                            userDataOnFooter: true,
                            totalCols: ['cmpd_debe', 'cmpd_habe'],
                            totalDefault: {},
                            datatype: 'local',
                            caption: ' ',
                            colModel: [
                                { label: 'Id', name: 'Cob_Cod', key: true, width: 20 },
                                { label: 'Observación', name: 'Cob_Obs', width: 75 },
                                { label: 'Fecha', name: 'Cob_Fec', width: 40, align: 'center' },
                                { label: 'Responsable', name: 'Prs_Nom', width: 40, align: 'center' },
                                
                                //{ name: 'paem_imag_src',label:'<i class="glyphicon glyphicon-picture"></i>', width:15, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'showImage', icon:'picture', title:'Seleccionar Item', type:'info', data:['index','Cob_Res'], conditional:function(o){ return !!o.Cob_Res; } }, resizable: false },
                                
                                { label: 'Saldo Disp.', name: 'Cob_Dis', width: 40, formatter: 'currency', summaryType: 'sumIfNotDeleted' },
                                { name: 'view', label: $.createIcon('eye-open'), width: 15, align: 'center', viewable: false, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewItem', icon: 'eye-open', type: 'info', title: 'Visualizar Conciliacion', data: 'Cob_Cod',
                                        conditional: o => o.Cob_Est === 'A'
                                    },
                                    resizable: false
                                },
                                { label: '<center><i class="ui-icon ui-icon-pencil"></i></center>', name: 'edit', width: 40, align: 'center', viewable: false, resizable: false,
                                    formatter: function (cellvalue, options, o) {                                                                          
                                        return ((o.Cob_Est === 'A' && o.Cob_Last === 'S') || Usu_Adm==1)?$.getGridButton(editItem, o.Cob_Cod, 'Editar Conciliacion', 'pencil', '',  $.isEmpty(o.Cob_Last)?'warning':'success'):'';
                                    }
                                },                               
                                { name: 'delete', label: $.createIcon('remove'), width: 15, viewable: false, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'validaEliminacion', icon: 'remove', title: 'Eliminar Conciliacion', type: 'danger', data: 'Cob_Cod',
                                        attr: { 'tabindex': '-1' },
                                        conditional: o => o.Cob_Est === 'A' && o.Cob_Last === 'S'
                                    },
                                    resizable: false
                                },
                                { name: 'Cob_Est', label: 'isDeleted', width: 20, hidden: true }
                            ]
                        });
                        $("#conciliacion").on('jqGridAfterLoadComplete', function(ev, glc) {
                            $("#conciliacion").setCaption($('#searchConcilia').find('select[name="Ban_Cod"] option:selected').text() + " - Periodo " + $('#searchConcilia').find('select[name="Pec_Cod"] option:selected').text());
                        });
                    },
                    viewDiv: () => {
                        let optsRes = {
                            url: '',
                            pager: false,
                            main: true,
                            small: true,
                            local: true,
                            footerrow: true,
                            userDataOnFooter: true,
                            totalCols: ['val'],
                            height: 'auto',
                            tableType: ' ',
                            rownumbers: false,
                            colModel: [
                                { label: 'Id', name: 'id', key: true, hidden: true, width: 20 },
                                { label: 'Operacion', name: 'pago_desc', width: 50 },
                                { label: 'Valor', name: 'val', width: 30, formatter: 'number', summaryType: 'sum' }
                            ],
                            notStriped: true,
                            loadonce: true
                        }

                        $('#jqGridRepBanc').createGrid($.extend({
                            caption: 'CONCILIADAS'
                        }, optsRes));

                        $('#jqGridRepCta').createGrid($.extend({
                            caption: 'EN TRANSITO'
                        }, optsRes));

                        gridRep = $("#jqGridRep").createGrid({
                            url: '',
                            pager: true,
                            height: 350,
                            main: true,
                            small: true,
                            local: true,
                            datatype: 'local',
                            caption: 'DETALLE DE MOVIMIENTOS', //footerrow:true, userDataOnFooter:true,  totalCols:['Pld_Sal'],
                            colModel: [
                                { label: 'Id', name: 'Com_Cod', hidden: true, width: 20 },
                                { label: 'Id', name: 'Asi_Cod', key: true, hidden: true, width: 20 },
                                { label: 'Tipo', name: 'tipo', width: 40, hidden: true },
                                { label: 'Tipo', name: 'pago_tipo', width: 40, hidden: true },
                                { label: 'Fecha', name: 'Com_Fec', width: 40, align: 'center' },
                                { label: 'Codigo', name: 'Com_Codigo', width: 40, align: 'center' },
                                { label: 'Doc.', name: 'Doc_Num', width: 40, align: 'center' },
                                { label: 'Clie./Provee.', name: 'Inv_Nom', width: 75, cellattr: $.cellAjust },
                                { label: 'Observación', name: 'Asi_Glo', width: 100 },
                                { label: 'Sumas', name: 'Asi_Sald', width: 45, formatter: 'number', summaryType: 'sum' },
                                { label: 'Info.', name: 'view', width: 20, formatter: 'gridButton',
                                    formatoptions: {
                                        action: 'viewComprobante', data: 'cmpr_id', type: 'primary', title: 'Ver Comprobante', icon: 'list-alt',
                                        conditional: o => $.vv(o.cmpr_id) && o.cmpr_id !== ''
                                    }
                                }
                            ],
                            notStriped: true,
                            loadonce: true,
                            grouping: true,
                            groupingView: {
                                groupField: ['tipo', 'pago_tipo'],
                                groupOrder: ["desc", 'asc'],
                                groupColumnShow: [false, false],
                                groupText: ["<b>{0}</b>", "<div class='txtLeft'><b>{0}</b><span class='green pull-right'> {1} Operacion(es)</span></div>"],
                                groupCollapse: false,
                                groupSummary: [true, true],
                                showSummaryOnHide: [true, true]
                            }
                        }, true, '#jqGridRepPager');
                        gridRep.on('jqGridAfterLoadComplete', function() {
                            var i, groups = gridRep.jqGrid("getGridParam", "groupingView").groups,
                                l = groups.length;
                            for (i = 0; i < l; i++) {
                                let idSelectorPrefix = "#" + gridRep[0].id + "ghead_" + groups[i].idx + "_";
                                $(idSelectorPrefix + i + " ~ tr.jqfoot[jqfootlevel=" + groups[i].idx + "]").first().find(">td[aria-describedby=jqGridRep_Asi_Glo]").css({
                                    'text-align': 'right',
                                    'font-weight': 'bold'
                                }).html((groups[i].idx === 1 ? 'Total ' : 'OPERACIONES ') + groups[i].value);
                            }
                            gridRep.find('tr.jqfoot td[aria-describedby="jqGridRep_Pld_Sal"]').css({
                                'border-bottom': '1px solid #666',
                                'font-weight': 'bold'
                            });
                        });
                        $('#conc_baem_id').on('change', function() {
                            console.log('ver');
                            $('#conc_cta').setData($(this).find('option:selected').data('banco_empresa'));
                        });
                    }
                });
                $('#buscaDiv').show();
                selBan1 = $('<select></select>');
                selBan2 = $('<select></select>');
                $('#Cob_Fec').createDatePickers({ clean: true, checkAvailability: true });
                syncCobFecDateLimits();
                /*<?php if (count($periodos) == 1) { ?>*/
                loadBancos($('#searchConcilia').find('select[name="Pec_Cod"]').val());
                syncUbicarPeriodoBadge();
                /*<?php } ?>*/
            });

            /** Limita el calendario de Cob_Fec al rango Pec_Fei–Pec_Fef del periodo. */
            function syncCobFecDateLimits() {
                var $opt = $('#formConcilia').find('select[name="Pec_Cod"] option:selected'),
                    ini = $opt.data('inicio') || $opt.attr('data-inicio') || '',
                    fin = $opt.data('fin') || $opt.attr('data-fin') || '',
                    $fec = $('#Cob_Fec'),
                    val;
                if (!$fec.length || !$fec.data('datepicker')) return;
                if (ini && fin) {
                    $fec.dateLimits(ini, fin);
                    val = $fec.val();
                    if (val && (val < ini || val > fin)) {
                        $fec.val('');
                    }
                } else {
                    $fec.datepicker('option', 'minDate', null);
                    $fec.datepicker('option', 'maxDate', null);
                }
            }

            function sumNotInit(v, n, obj) {
                if (isConcHeaderRow(obj)) return 0;
                return isNaN(v) ? 0 : (obj['cmpd_id'] === 'no_id' || obj['conc_select'] === 'N' ? 0 : v);
            }

            function getConcSelectableRows() {
                // Usar data local completa (no getGridBatch: solo trae la página actual)
                var data = $("#conciliacionForm").jqGrid('getGridParam', 'data') || [];
                return data.filter(function(r) {
                    return !isConcHeaderRow(r) && String(r.Asi_Cod) !== 'no_id';
                });
            }

            function isConcHeaderRow(row) {
                if (!row) return false;
                return row.conc_hdr === 'S' || String(row.Asi_Cod || '').indexOf('hdr_') === 0;
            }
            window.isConcHeaderRow = isConcHeaderRow;

            function styleConcHeaderRows() {
                var $g = $('#conciliacionForm'),
                    data = $g.jqGrid('getGridParam', 'data') || [],
                    i, row;
                for (i = 0; i < data.length; i++) {
                    row = data[i];
                    if (isConcHeaderRow(row)) {
                        $g.find('tr#' + $.jgrid.jqID(row.Asi_Cod)).addClass('conc-group-hdr');
                    }
                }
            }

            function buildConcRowsWithHeaders(rows) {
                var out = [],
                    counts = {},
                    inserted = {},
                    i, row, meta;
                for (i = 0; i < rows.length; i++) {
                    meta = getConcTipoMeta(rows[i].pago_tipo, rows[i]);
                    counts[meta.ord] = (counts[meta.ord] || 0) + 1;
                }
                for (i = 0; i < rows.length; i++) {
                    row = rows[i];
                    meta = getConcTipoMeta(row.pago_tipo, row);
                    if (!inserted[meta.ord]) {
                        inserted[meta.ord] = true;
                        out.push({
                            Asi_Cod: 'hdr_' + meta.ord,
                            conc_hdr: 'S',
                            conc_select: 'N',
                            pago_tipo: meta.title,
                            hdr_title: meta.title,
                            hdr_count: counts[meta.ord] || 0,
                            Com_Cod: '',
                            Com_Codigo: '',
                            Com_Fec: '',
                            Doc_Num: '',
                            Num_Doc: '',
                            FormasPago: '',
                            Inv_Nom: '',
                            Asi_Glo: meta.title,
                            Asi_Sald: null,
                            Cob_Disp: null
                        });
                    }
                    out.push(row);
                }
                return out;
            }

            function setConcSelectValue(rowId, val) {
                let gridConc = $("#conciliacionForm"),
                    checked = val === 'S',
                    idStr = String(rowId),
                    localRow = gridConc.jqGrid('getLocalRow', rowId),
                    data = gridConc.jqGrid('getGridParam', 'data') || [],
                    i, z;
                if (localRow) localRow.conc_select = val;
                for (i = 0, z = data.length; i < z; i++) {
                    if (String(data[i].Asi_Cod) === idStr) {
                        data[i].conc_select = val;
                        break;
                    }
                }
                var $ck = $('#' + $.jgrid.jqID(idStr + '_conc_select'));
                if (!$ck.length) {
                    $ck = gridConc.find('tr#' + $.jgrid.jqID(idStr) + ' input[type=checkbox][data-name="conc_select"]');
                }
                $ck.prop('checked', checked);
                gridConc.find('tr#' + $.jgrid.jqID(idStr) + ' td').toggleClass('cellGreen2', checked);
            }
            window.setConcSelectValue = setConcSelectValue;

            function injectConcSelectHeader() {
                let $th = $('#gview_conciliacionForm .ui-jqgrid-htable th[id$="_conc_select"]');
                if (!$th.length || $('#conc_select_all').length) return;
                $th.empty().append(
                    $('<input type="checkbox" id="conc_select_all" value="S" offval="N" title="Seleccionar / Deseleccionar todos" onclick="toggleConcSelectAll(this)" />')
                );
            }

            /**
             * Marca/desmarca TODOS los registros del grid (todas las páginas),
             * no solo los visibles en la página actual.
             */
            function toggleConcSelectAll(checkbox) {
                let gridConc = $("#conciliacionForm"),
                    data = gridConc.jqGrid('getGridParam', 'data') || [],
                    rows = getConcSelectableRows(),
                    checkedCount = 0,
                    newState, val, i, z, row, idStr, $ck;

                for (i = 0, z = rows.length; i < z; i++) {
                    if (rows[i].conc_select === 'S') checkedCount++;
                }
                newState = checkedCount < rows.length;
                val = newState ? 'S' : 'N';
                if (checkbox) {
                    checkbox.checked = newState;
                    checkbox.indeterminate = false;
                }

                // 1) Actualizar data local completa
                for (i = 0, z = data.length; i < z; i++) {
                    row = data[i];
                    if (isConcHeaderRow(row) || String(row.Asi_Cod) === 'no_id') continue;
                    row.conc_select = val;
                }

                // 2) Actualizar checkboxes visibles en el DOM
                gridConc.find('tbody tr.jqgrow input[type="checkbox"]').each(function() {
                    var $el = $(this),
                        name = $el.data('name') || $el.attr('data-name') || '',
                        rid = $el.data('rowId');
                    if (!rid && this.id && this.id.indexOf('_conc_select') > 0) {
                        rid = this.id.replace(/_conc_select$/, '');
                    }
                    if (name && name !== 'conc_select') return;
                    if (!rid || String(rid).indexOf('hdr_') === 0 || String(rid) === 'no_id') return;
                    idStr = String(rid);
                    $el.prop('checked', newState);
                    gridConc.find('tr#' + $.jgrid.jqID(idStr) + ' td').toggleClass('cellGreen2', newState);
                });

                updateTotal();
                syncConcSelectHeader();
            }
            window.toggleConcSelectAll = toggleConcSelectAll;

            function syncConcSelectHeader() {
                let rows = getConcSelectableRows(),
                    total = rows.length,
                    checked = 0,
                    $all = $('#conc_select_all'),
                    i;
                for (i = 0; i < rows.length; i++) {
                    if (rows[i].conc_select === 'S') checked++;
                }
                if ($all.length) {
                    $all.prop('checked', total > 0 && checked === total);
                    $all.prop('indeterminate', checked > 0 && checked < total);
                }
            }

            function nuevaConciliacion() {
                concFillToken++;
                showConcGridProgress(false);
                setConcGridBusy(false);
                $('#conciliacionForm').clearGrid(true);
                let form = $('#formConcilia');
                form.setData({}, 'last');
                form.setData({}, 'cob');
                form.find('select[name=Pec_Cod]').trigger('change').prop('disabled', false);
                form.find('select[name=Ban_Cod]').add($('#loadAsientosBtn')).add($('#Cob_Fec')).prop('disabled', false);
                syncCobFecDateLimits();
                $('#editDivTitle').html('&raquo; Nueva Conciliaci&oacute;n');
                $('#buscaDiv').moveComp('#editDiv').updateGridsSizes();
            }

            function applyConcTotal(total) {
                let tot = (total != null && total !== '') ? parseFloat(total) : 0;
                if (isNaN(tot)) tot = 0;
                totConc = tot.toFixed(2);
                $("#conciliacionForm").footerData('set', {
                    Cob_Disp: totConc
                });
                $('#Cob_Dis').attr('max', totConc).attr('min', totConc);
            }

            /** Icono por tipo (simula grouping sin cabeceras). */
            function getConcTipoMeta(cellValue, rowObject) {
                var raw = String(cellValue == null ? '' : cellValue).replace(/^!+/, '').replace(/^\-\s*/, '').trim();
                var tipo = raw.toLowerCase();
                var isInit = rowObject && String(rowObject.Asi_Cod) === 'no_id';
                if (isInit || /conciliaci[oó]n|saldo anterior|sin conciliacion/i.test(tipo)) {
                    return { icon: 'fa-history', color: '#1565c0', title: 'Conciliación Anterior', ord: 0 };
                }
                if (/manual/i.test(tipo)) {
                    return { icon: 'fa-hand-o-up', color: '#ef6c00', title: 'Manuales', ord: 1 };
                }
                if (/cheque/i.test(tipo)) {
                    return { icon: 'fa-money', color: '#2e7d32', title: 'Cheques', ord: 2 };
                }
                return { icon: 'fa-tag', color: '#757575', title: raw || 'Otro', ord: 3 };
            }

            function formatConcTipoIcon(cellValue, rowObject) {
                if (isConcHeaderRow(rowObject)) return '';
                var m = getConcTipoMeta(cellValue, rowObject);
                return '<i class="fa ' + m.icon + ' conc-tipo-ico" style="color:' + m.color + ';" title="' + m.title + '"></i>';
            }
            window.formatConcTipoIcon = formatConcTipoIcon;

            var concFillToken = 0;

            function setConcGridBusy(busy) {
                $('#concGridBusy').toggle(!!busy);
                if (busy) {
                    $('#editDiv .btn-primary, #editDiv .btn-inverse, #loadAsientosBtn').prop('disabled', true);
                } else {
                    $('#editDiv .btn-primary, #editDiv .btn-inverse').prop('disabled', false);
                    if (!$('#formConcilia').find('[name=Cob_Cod]').val()) {
                        $('#loadAsientosBtn').prop('disabled', false);
                    }
                }
            }

            function showConcGridProgress(show, loaded, total, phase, waiting) {
                var $box = $('#concGridProgress'),
                    $bar = $box.find('.conc-prog-bar'),
                    pct = 0;
                if (!show) {
                    $box.hide();
                    $bar.removeClass('is-wait').css('width', '0%');
                    return;
                }
                $box.show();
                if (waiting) {
                    $bar.addClass('is-wait');
                    $box.find('.conc-prog-text').text(phase || 'Consultando asientos...');
                    return;
                }
                $bar.removeClass('is-wait');
                pct = total > 0 ? Math.min(100, Math.round((loaded * 100) / total)) : 100;
                $bar.css('width', pct + '%');
                $box.find('.conc-prog-text').text(
                    (phase || 'Cargando') + (total ? (': ' + loaded + ' / ' + total + ' (' + pct + '%)') : '')
                );
            }

            /**
             * Carga rápida sin grouping: todos los datos en memoria, solo se pinta la página actual.
             */
            function fillConciliacionGrid(asientos, options) {
                options = options || {};
                var token = ++concFillToken,
                    $g = $('#conciliacionForm'),
                    rows = $.isArray(asientos) ? asientos.slice() : [],
                    total = rows.length,
                    rowNum = parseInt($g.jqGrid('getGridParam', 'rowNum'), 10) || 100,
                    pages = Math.max(1, Math.ceil(total / rowNum) || 1),
                    i;

                // Orden: Conciliación Anterior → Manuales → Cheques (al final) → otros
                for (i = 0; i < rows.length; i++) {
                    if (rows[i] && String(rows[i].Asi_Cod) === 'no_id') {
                        rows[i].pago_tipo = String(rows[i].pago_tipo || rows[i].Com_Con || 'Ultima Conciliación').replace(/^!+/, '');
                    }
                }
                rows.sort(function(a, b) {
                    var oa = getConcTipoMeta(a.pago_tipo, a).ord,
                        ob = getConcTipoMeta(b.pago_tipo, b).ord;
                    if (oa !== ob) return oa - ob;
                    var fa = String(a.Com_Fec || ''),
                        fb = String(b.Com_Fec || '');
                    if (fa < fb) return -1;
                    if (fa > fb) return 1;
                    return 0;
                });
                // Recalcular saldo acumulado en el nuevo orden visual
                var acumDisp = 0;
                for (i = 0; i < rows.length; i++) {
                    if (rows[i].conc_select === 'S' || String(rows[i].Asi_Cod) === 'no_id') {
                        acumDisp += parseFloat(rows[i].Asi_Sald) || 0;
                        rows[i].Cob_Disp = Math.round(acumDisp * 100) / 100;
                    } else {
                        rows[i].Cob_Disp = null;
                    }
                }
                if (options.total == null) options.total = Math.round(acumDisp * 100) / 100;
                rows = buildConcRowsWithHeaders(rows);
                total = rows.length;
                pages = Math.max(1, Math.ceil(total / rowNum) || 1);

                setConcGridBusy(true);
                showConcGridProgress(true, total, total, 'Preparando ' + total + ' registros', false);

                setTimeout(function() {
                    if (token !== concFillToken) return;
                    $g.jqGrid('clearGridData', true);
                    $g.jqGrid('setGridParam', {
                        datatype: 'local',
                        grouping: false,
                        data: rows,
                        records: total,
                        page: 1,
                        lastpage: pages,
                        total: pages
                    });
                    $g.trigger('reloadGrid', [{ page: 1 }]);
                    applyConcTotal(options.total);
                    syncConcSelectHeader();
                    showConcGridProgress(false);
                    setConcGridBusy(false);
                    if (typeof options.onDone === 'function') options.onDone();
                }, 20);
            }

            function editItem(Cob_Cod) {
                let $grid = $('#conciliacionForm');
                concFillToken++;
                $('#editDivTitle').html('&raquo; Editar Conciliaci&oacute;n');
                $('#buscaDiv').moveComp('#editDiv').updateGridsSizes();
                $grid.clearGrid(true);
                setConcGridBusy(true);
                showConcGridProgress(true, 0, 1, 'Consultando asientos...', true);
                $.getDataJson('', {
                    Cob_Cod: Cob_Cod,
                    editConciliacion: true
                }, function(r) {
                    let form = $('#formConcilia');
                    form.find('select[name=Pec_Cod]').val(r.Cob.Pec_Cod).trigger('change').prop('disabled', true);
                    form.find('select[name=Ban_Cod]').val(r.Cob.Ban_Cod).add($('#loadAsientosBtn')).add($('#Cob_Fec')).prop('disabled', true);
                    form.setData(r.Cob_Last, 'last');
                    form.setData(r.Cob, 'cob');
                    fillConciliacionGrid(r.asientos, {
                        total: r.Cob_Disp_Total
                    });
                });
            }

            function loadAsientos() {
                let conc = $('#formConcilia').getData(),
                    form = $('#formConcilia');
                if (conc.Pec_Cod === '') return $.alert('Seleccione Periodo Contable!');
                if (conc.Ban_Cod === '') return $.alert('Seleccione Cuenta Bancaria!');
                if (conc.Cob_Fec === '') return $.alert('Seleccione Fecha de Conciliaci&oacute;n!');
                concFillToken++;
                $('#conciliacionForm').clearGrid(true);
                setConcGridBusy(true);
                showConcGridProgress(true, 0, 1, 'Consultando asientos...', true);
                $.getDataJson('', {
                    conc: conc,
                    newConciliacion: true
                }, function(r) {
                    if (r['success'] === true) {
                        form.setData(r.Cob_Last, 'last');
                        fillConciliacionGrid(r.asientos, {
                            total: r.Cob_Disp_Total
                        });
                    } else {
                        showConcGridProgress(false);
                        setConcGridBusy(false);
                    }
                });
            }

            /**
             * Recalcula saldo acumulado sin setCell (muy lento con miles de filas).
             * Actualiza data local + texto DOM de Cob_Disp en un solo pase.
             */
            function updateTotal(rowId) {
                let gridConc = $("#conciliacionForm");
                if (!gridConc[0] || !gridConc[0].grid) return;

                let data = gridConc.jqGrid('getGridParam', 'data') || [],
                    acum = 0,
                    valMove = {},
                    colAttr = 'conciliacionForm_Cob_Disp',
                    rowIdStr = (rowId == null || rowId === '') ? null : String(rowId),
                    trMap = {},
                    rowsDom = gridConc[0].rows,
                    i, z, r, rl, c, cl, tr, cells, row, addVal, sald, disp, $ck, localRow, selVal;

                // checkboxExa no escribe conc_select en data local: sincronizar desde el DOM
                if (rowIdStr !== null) {
                    $ck = $('#' + $.jgrid.jqID(rowIdStr + '_conc_select'));
                    if (!$ck.length) {
                        $ck = gridConc.find('tr#' + $.jgrid.jqID(rowIdStr) + ' input[type=checkbox][data-name="conc_select"]');
                    }
                    if ($ck.length) {
                        selVal = $ck.is(':checked') ? 'S' : 'N';
                        localRow = gridConc.jqGrid('getLocalRow', rowIdStr);
                        if (localRow) localRow.conc_select = selVal;
                        // También actualizar en el array data (misma referencia normalmente)
                        for (i = 0, z = data.length; i < z; i++) {
                            if (String(data[i].Asi_Cod) === rowIdStr) {
                                data[i].conc_select = selVal;
                                break;
                            }
                        }
                        gridConc.find('tr#' + $.jgrid.jqID(rowIdStr) + ' td').toggleClass('cellGreen2', selVal === 'S');
                    }
                }

                for (r = 0, rl = rowsDom.length; r < rl; r++) {
                    tr = rowsDom[r];
                    if (tr.className && tr.className.indexOf('jqgrow') >= 0) {
                        trMap[tr.id] = tr;
                    }
                }

                for (i = 0, z = data.length; i < z; i++) {
                    row = data[i];
                    if (isConcHeaderRow(row)) continue;
                    addVal = row.conc_select === 'S' || String(row.Asi_Cod) === 'no_id';
                    sald = parseFloat(row.Asi_Sald) || 0;
                    if (rowIdStr !== null && String(row.Asi_Cod) === rowIdStr) {
                        valMove = {
                            val: sald * (addVal ? 1 : -1),
                            tipo: row.pago_tipo
                        };
                    }
                    if (addVal) {
                        acum += sald;
                        disp = Math.round(acum * 100) / 100;
                    } else {
                        disp = null;
                    }
                    row.Cob_Disp = disp;
                    tr = trMap[String(row.Asi_Cod)] || trMap[row.Asi_Cod];
                    if (tr) {
                        cells = tr.cells;
                        for (c = 0, cl = cells.length; c < cl; c++) {
                            if (cells[c].getAttribute('aria-describedby') === colAttr) {
                                cells[c].textContent = disp == null ? '--' : disp.toFixed(2);
                                break;
                            }
                        }
                    }
                }

                applyConcTotal(acum);
                syncConcSelectHeader();
            }
            window.updateTotal = updateTotal;

            function validarForm(newItem) {
                var data = {
                    form: $('#formConcilia').getData(),
                    updateConcilia: true,
                    asientos: $.map(getConcSelectableRows().filter(function(o) {
                        return o.conc_select === 'S';
                    }), function(o) {
                        return o.Asi_Cod;
                    })
                };
                /* aqui puedo poner validaciones */
                //console.log(data);
                $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, newItem ? createConcilia : updateConcilia);
            }

            function updateConcilia(data) {
                $.saveDataJson("", data, function(responce) {
                    $('#conciliacion').gridUpdate().loadUpdate();
                    $('#editDiv').moveComp('#buscaDiv').updateGridsSizes();
                });
            }
            //eliminar
            function validaEliminacion(Cob_Cod) {
                $.createDialogConfirm('¿Esta seguro de <u class="red"><b>ANULAR</b></u> la <b>Conciliación Bancaria</b>?', {
                    deleteConcilia: true,
                    Cob_Cod: Cob_Cod
                }, eliminaConciliacion);
            }

            function eliminaConciliacion(data) {
                //console.log(data);
                $.saveDataJson("", data, function(responce) {
                    $("#conciliacion").trigger("reloadGrid");
                });
            }

            function syncUbicarPeriodoBadge() {
                var $opt = $('#searchConcilia').find('select[name="Pec_Cod"] option:selected'),
                    year = $opt.attr('data--year') || $opt.data('year') || $opt.text() || '',
                    $badge = $('#ubicarPeriodoBadge');
                if (!$opt.val()) {
                    $badge.text('—').attr('title', 'Seleccione un periodo');
                    return;
                }
                year = String(year).replace(/Periodo\.\./i, '').trim() || $.trim($opt.text());
                $badge.text(year).attr('title', 'Periodo ' + year);
            }
            window.syncUbicarPeriodoBadge = syncUbicarPeriodoBadge;

            function loadBancos(val) {
                let select = $('#searchConcilia').find('select[name="Ban_Cod"]');
                select.find('option[pec]').hide().detach().appendTo(selBan1);
                if (val === '') {
                    syncUbicarPeriodoBadge();
                    return;
                }
                selBan1.find('option[pec="' + val + '"]').show().detach().appendTo(select);
                select.val('');
                syncUbicarPeriodoBadge();
            }

            function buscarDiarioConciliacion() {
                var $main = $('#searchConcilia'),
                    $form = $('#searchDiarioConc'),
                    diario = $.trim($form.find('[name="Com_Codigo"]').val() || ''),
                    comCod = $.trim($form.find('[name="Com_Cod"]').val() || ''),
                    pec = $main.find('[name="Pec_Cod"]').val(),
                    ban = $main.find('[name="Ban_Cod"]').val(),
                    pecTxt = $('#ubicarPeriodoBadge').text(),
                    $msg = $('#buscarDiarioMsg');
                $msg.html('').css('color', '#333');
                if (!pec) {
                    $msg.css('color', '#c62828').html('Seleccione el <b>Periodo</b> en la b&uacute;squeda principal.');
                    $main.find('[name="Pec_Cod"]').focus();
                    return;
                }
                if (diario === '' && comCod === '') {
                    $msg.css('color', '#c62828').html('Ingrese <b>Nro. Diario</b> y/o <b>Id Comprobante (Com_Cod)</b>.');
                    $form.find('[name="Com_Codigo"]').focus();
                    return;
                }
                $msg.html('<i class="fa fa-spinner fa-spin"></i> Buscando en periodo <b>' + pecTxt + '</b>...');
                $.getDataJson('', {
                    buscarDiarioConc: true,
                    Com_Codigo: diario,
                    Com_Cod: comCod,
                    Pec_Cod: pec,
                    Ban_Cod: ban || ''
                }, function(r) {
                    if (!r || r.success !== true) {
                        $msg.css('color', '#c62828').html((r && r.message) ? r.message : 'No se pudo completar la b&uacute;squeda.');
                        return;
                    }
                    if (!r.rows || !r.rows.length) {
                        $('#conciliacion').clearGrid(true);
                        $msg.css('color', '#c62828').html(r.message || 'Sin resultados.');
                        return;
                    }
                    if (r.rows[0].Ban_Cod) {
                        $main.find('[name="Ban_Cod"]').val(r.rows[0].Ban_Cod);
                    }
                    $('#conciliacion').setRows(r.rows);
                    var cap = ['Periodo ' + pecTxt];
                    if (r.Com_Codigo) cap.push('Diario ' + r.Com_Codigo);
                    if (r.Com_Cod) cap.push('Com_Cod ' + r.Com_Cod);
                    $("#conciliacion").setCaption(cap.join(' / ') + ' — ' + r.rows.length + ' conciliaci&oacute;n(es)');
                    $msg.css('color', '#2e7d32').html(r.message);
                    try {
                        $('#conciliacion').jqGrid('setSelection', r.rows[0].Cob_Cod, true);
                    } catch (e) {}
                });
            }
            window.buscarDiarioConciliacion = buscarDiarioConciliacion;

            $(document).on('keydown', '#buscarDiarioInput, #buscarComCodInput', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    buscarDiarioConciliacion();
                }
            });

            function loadBancos2(val) {
                let select = $('#formConcilia').find('select[name="Ban_Cod"]');
                select.find('option[pec]').hide().detach().appendTo(selBan2);
                if (val === '') {
                    syncCobFecDateLimits();
                    return;
                }
                selBan2.find('option[pec="' + val + '"]').show().detach().appendTo(select);
                select.val('');
                syncCobFecDateLimits();
            }

            function viewInfo(doc) {
                $('#docDetaDialog').dialog('open');
                $.getDataJson('', {
                    docDetalle: true,
                    Com_Cod: doc
                }, function(resp) {
                    $('#detaDocu').setRows(resp.asientos);
                    $('#docDetaDialog').setData(resp.comprobante).dialog('open');
                });
            }
            var gridRep, gridRepBanc, gridRepCta;

            function viewItem(Cob_Cod) {
                $.getDataJson('', {
                    viewConciliacion: true,
                    Cob_Cod: Cob_Cod
                }, function(r) {
                    gridRep.setRows(r.asientos);
                    console.log(gridRep.getGridBatch().length);
                    var i, groups = gridRep.jqGrid("getGridParam", "groupingView").groups,
                        l = groups.length,
                        tablas = {
                            consilia: [],
                            mayor: []
                        },
                        init = '';
                    for (i = 0; i < l; i++) {
                        if (groups[i].dataIndex === 'tipo') init = (groups[i].value === 'CONCILIADAS') ? 'consilia' : 'mayor';
                        else {
                            let add = true;
                            for (let j = 0, jl = tablas[init].length; j < jl; j++) {
                                if (tablas[init][j].pago_desc === groups[i].value) {
                                    tablas[init][j].val += groups[i].summary[0].v;
                                    add = false;
                                    break;
                                }
                            }
                            if (add) tablas[init].push({
                                pago_desc: groups[i].value,
                                val: groups[i].summary[0].v
                            });
                        }
                    }
                    $('#jqGridRepBanc').setRows(tablas.consilia);
                    $('#jqGridRepCta').setRows(tablas.mayor);
                    let tot_banc = $.round($('#jqGridRepBanc').getCol('val', false, 'sum')),
                        tot_mayor = $.round($('#jqGridRepCta').getCol('val', false, 'sum'));

                    $('#jqGridRepBanc').footerData('set', {
                        val: tot_banc,
                        pago_desc: '<div class="txt-right bold">(+) SUMA OPERACIONES:</div>'
                    });
                    $('#jqGridRepCta').footerData('set', {
                        val: tot_mayor,
                        pago_desc: '<div class="txt-right bold">(-) SUMA OP. EN TRANSITO:</div>'
                    });
                    //console.log(r);
                    //console.log("Total banco:"+tot_banc);
                    //No sumar el valor anterir del banco ya que esta en un ajuste al inicio del año(periodo) SOLO AL INICIO DEL AÑO

                    if (r.Cob && r.Cob.Cob_Fec) {
                        //console.log("Ingresa aqui");
                        let partesFecha = r.Cob.Cob_Fec.split('-');
                        let fecha = new Date(partesFecha[0], partesFecha[1] - 1, partesFecha[2]);
                        let dia = fecha.getDate(); // Obtener el día
                        let mes = fecha.getMonth() + 1; // Obtener el mes (sumar 1 porque los meses van de 0 a 11)
                        console.log(dia + "&&" + mes);
                        if (dia == 1 && mes == 1) {
                            //console.log("Debe ingresar aqui");
                            tot_banc = 0;
                            //r.Cob_Last.Cob_Dis = 0;

                        }
                    }
                    //console.log("Este dato :"+r.Cob_Last.Cob_Dis);

                    let data_form = $.extend({
                            Tot_Cob_Dis: (r.Cob_Last.Cob_Dis * 1 + tot_banc).toFixed(2),
                            Tot_Pld_Sal: (r.Mayor.Pld_Sal * 1 - tot_mayor).toFixed(2)
                        }, r.Cob_Last,
                        r.Cob,
                        r.Mayor || {}
                    );
                    $('#formConciliacion').setData(data_form);
                    $('#buscaDiv').moveComp('#viewDiv').updateGridsSizes();
                });
            }


            function reportTableExtra() {
                let table = $('#tableExtraInfo'),
                    data = $('#formConciliacion').getData();
                table.setData(data);
                let banco = table.find('table.banco').html($('#jqGridRepBanc').exportGridInnerHTML({
                        generated: false,
                        caption: true,
                        footer: true,
                        print: true,
                        removeHiddens: true
                    })),
                    banchead = banco.find('thead tr:nth-child(2)');
                banco.find('tbody:last-child tr td:last-child').css('border-top', '1px solid #666');
                $('<th style="text-align: center; border-bottom: 0.1pt solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;" colspan="2">BCO: ' + data.Pld_Des + '</th>').insertBefore(banchead);
                $('<tr><td style="padding-left: 5px; padding-right: 5px;border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden;text-align:left;font-weight:bold;"> (&#187;) SALDO ANTERIOR BANCO:</td><td style="border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden; text-align:right;padding-left: 5px; padding-right: 5px;">' + $.toFixed(data.Asi_Sald) + '</td></tr>').insertBefore(banchead);
                banco.append('<tbody><tr><td style="padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>(=) SALDO CTA. BANCARIA:</b></td><td style="text-align:right;border-bottom:1px dotted #666;border-top:1px solid #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>' + $.toFixed(data.Tot_Cob_Dis) + '</b></td></tr></tbody>');
                let mayor = table.find('table.mayor').html($('#jqGridRepCta').exportGridInnerHTML({
                        generated: false,
                        caption: true,
                        footer: true,
                        print: true,
                        removeHiddens: true
                    })),
                    ctahead = mayor.find('thead tr:nth-child(2)');
                mayor.find('tbody:last-child tr td:last-child').css('border-top', '1px solid #666');
                $('<th style="text-align: center; border-bottom: 0.1pt solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;" colspan="2">CTA: ' + data.Pld_Cdc + ' - ' + data.Pld_Des + '</th>').insertBefore(ctahead);
                $('<tr><td style="padding-left: 5px; padding-right: 5px;border-bottom:1px solid rgb(102, 102, 102);white-space: nowrap; overflow: hidden;text-align:left;font-weight:bold;"> (&#187;) SALDO LIBRO MAYOR:</td><td style="border-bottom:1px solid rgb(102, 102, 102); white-space: nowrap; overflow: hidden; text-align:right;padding-left: 5px; padding-right: 5px;">' + $.toFixed(data.Pld_Sal) + '</td></tr>').insertBefore(ctahead);
                mayor.append('<tbody><tr><td style="border-bottom:1px dotted #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>(=) SALDO CTA. BANCARIA:</b></td><td style="text-align:right;border-bottom:1px dotted #666;border-top:1px solid #666;padding-left:5px;padding-right:5px;white-space:nowrap;overflow:hidden;"><b>' + $.toFixed(data.Tot_Pld_Sal) + '</b></td></tr></tbody>');
                this.html(table.html());
            }



            function imprimir() {
                let grid = gridRep,
                    divRep = $('#imprimir'),
                    opts = {
                        generated: false,
                        print: true,
                        caption: true,
                        footer: true,
                        bodyBorder: false,
                        removeHiddens: true,
                        removeCols: [6]
                    },
                    tableExtra = $('#tablereportExtra').html(''),
                    tableRep = $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', opts));
                divRep.find('span.subtitle').html('').parent().parent().hide();
                reportTableExtra.call(tableExtra, divRep);
                divRep.printElement({
                    pageTitle: "EXA Ofsercont",
                    printMode: 'popup',
                    icon: "glyphicon glyphicon-print",
                    printCss: true
                });
            }

            function exportar(pageExcel) {
                let grid = gridRep,
                    opts = {
                        excel: true,
                        generated: false,
                        caption: true,
                        bodyBorder: false,
                        footer: true,
                        sepEnd: true,
                        removeHiddens: true,
                        removeCols: [6]
                    },
                    divRep = $('#exportar'),
                    temp = $('<div></div>'),
                    data = $('#formConciliacion').getData();
                $('#exportar').find('span.subtitle').html(data.Pld_Cdc + ' - ' + data.Pld_Des + ' al ' + data.Cob_Fec);
                temp.html(divRep.html()).append(grid.jqGrid('exportGridHTML', opts));
                let last = temp.find('tbody:last').prev().find('>tr:last'),
                    newTr = last.clone();
                newTr.find('>td:last').html($.numFormat(data.Tot_Cob_Dis)).prev().html("SALDO ACTUAL");
                newTr.insertAfter(last);
                newTr = last.clone();
                newTr.find('>td:last').html($.numFormat(data.Asi_Sald)).prev().html("SALDO ANTERIOR");
                newTr.insertBefore(temp.find('thead:last>tr:nth-child(2)'));
                $.downloadFile($.exportarExcelBlob(temp.html(), pageExcel || 'report'), (pageExcel || 'report') + '_' + $.getDate() + '.xls');
            }
        </script>
    </body>

</html>