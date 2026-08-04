<?php
/**
 * Formato num�rico unificado EXA Presupuesto (2 decimales, separador de miles).
 */

function ppto_fmt_num($val, $dec = 2) {
    return number_format((float)$val, (int)$dec, '.', ',');
}

function ppto_fmt_money($val) {
    return '$' . ppto_fmt_num($val, 2);
}

function ppto_fmt_pct($val) {
    return ppto_fmt_num($val, 2) . '%';
}

/**
 * Ton/dia y dias operativos RCET (3500 x 22 = 77000 Ton/mes por rubro).
 *
 * @return float
 */
function ppto_rubro_tn_dia_default() {
    return 3500.0;
}

/**
 * @return int
 */
function ppto_rubro_dias_operativos_default() {
    return 22;
}

/**
 * Toneladas/mes operativas de un rubro driver (tn/dia x 22).
 *
 * @param float $tn_dia
 * @return float
 */
function ppto_rubro_ton_mes_operativa($tn_dia = 0) {
    $tn_dia = (float)$tn_dia;
    if ($tn_dia <= 0) {
        $tn_dia = ppto_rubro_tn_dia_default();
    }
    return round($tn_dia * ppto_rubro_dias_operativos_default(), 4);
}

/**
 * Normaliza ton/mes legacy (105000 = 3500x30) a operativa 77000 (solo rubros driver).
 *
 * @param float $ton
 * @param float $tn_dia
 * @return float
 */
function ppto_normalizar_ton_mes_rubro($ton, $tn_dia = 0) {
    $ton = (float)$ton;
    $oper = ppto_rubro_ton_mes_operativa($tn_dia);
    if ($ton <= 0 || abs($ton - 105000) < 0.01) {
        return $oper;
    }
    return $ton;
}

/**
 * Ton base PDF del proyecto/version (ingresos). Corrige solo confusión con ton costo (~77.000).
 *
 * @param float $ton
 * @return float
 */
function ppto_version_ton_base_sanitize($ton) {
    $ton = (float)$ton;
    if ($ton <= 0) {
        return 0.0;
    }
    $oper = ppto_rubro_ton_mes_operativa();
    // 77.000 es ton/mes costo Excel (3500x22), no ingresos del proyecto (105.000).
    if (abs($ton - $oper) < 500) {
        return 105000.0;
    }
    return round($ton, 4);
}

/**
 * Indica si ton ingresos fue corregido por confundirse con ton costo egreso.
 *
 * @param float $ton_raw
 * @param float $ton_sanitized
 * @return bool
 */
function ppto_version_ton_base_fue_corregida($ton_raw, $ton_sanitized) {
    $raw = (float)$ton_raw;
    $san = (float)$ton_sanitized;
    if ($raw <= 0 || $san <= 0) {
        return false;
    }
    return abs($raw - $san) > 0.01;
}

/**
 * Ton/mes costo egreso (77.000 por defecto). No convierte a ingresos.
 *
 * @param float $ton
 * @return float
 */
function ppto_version_ton_costo_sanitize($ton) {
    $ton = (float)$ton;
    if ($ton <= 0) {
        return ppto_rubro_ton_mes_operativa();
    }
    return round($ton, 4);
}

/**
 * Ton/mes de referencia para escalar gastos en escenarios proyectada/real.
 * Usa ton ingresos del proyecto (105.000), no la base operativa 77.000 del Excel.
 *
 * @param float $ton_ingreso_mes
 * @return float
 */
function ppto_proy_ton_escenario_gasto_mes($ton_ingreso_mes) {
    $ton = (float)$ton_ingreso_mes;
    if ($ton <= 0.0001) {
        $ton = 105000.0;
    }
    return round($ton, 4);
}

/**
 * Factor $/Ton anual coherente con el presupuesto Base PDF actual para escenarios de gasto.
 * Usa ton ingresos del proyecto (105.000), no la base operativa 77k/85k del Excel.
 *
 * @param float $presupuesto_anual
 * @param float $ton_ingreso_mes
 * @return float
 */
function ppto_proy_factor_escenario_gasto($presupuesto_anual, $ton_ingreso_mes) {
    $anual = round((float)$presupuesto_anual, 2);
    $ton = ppto_proy_ton_escenario_gasto_mes($ton_ingreso_mes);
    if ($anual <= 0.0001) {
        return 0.0;
    }
    return round($anual / $ton, 6);
}

/**
 * Escapa texto para salida HTML (UTF-8).
 *
 * @param string $text
 * @return string
 */
function ppto_html($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/**
 * json_encode seguro para bloques <script> (compatible PHP 5.3+).
 *
 * @param mixed $data
 * @return string
 */
function ppto_json_encode_safe($data) {
    $flags = 0;
    if (defined('JSON_HEX_TAG')) {
        $flags |= JSON_HEX_TAG;
    }
    if (defined('JSON_HEX_AMP')) {
        $flags |= JSON_HEX_AMP;
    }
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $flags |= JSON_UNESCAPED_UNICODE;
    }

    if ($flags) {
        $json = json_encode($data, $flags);
    } else {
        $json = json_encode($data);
    }

    if ($json === false || $json === '') {
        return 'null';
    }
    return $json;
}

/**
 * Nombre del mes en espanol (1 = Enero).
 *
 * @param int $m
 * @return string
 */
function ppto_nombre_mes($m) {
    $meses = array(
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    );
    $m = (int)$m;
    return isset($meses[$m]) ? $meses[$m] : '';
}

/**
 * Opciones HTML de un select de meses.
 *
 * @param int|null $selected
 * @param bool $incluir_vacio
 * @param string $etiqueta_vacio
 * @return string
 */
function ppto_meses_select_options($selected, $incluir_vacio, $etiqueta_vacio) {
    $html = '';
    if ($incluir_vacio) {
        $html .= '<option value="">' . htmlspecialchars($etiqueta_vacio) . '</option>';
    }
    for ($m = 1; $m <= 12; $m++) {
        $sel = ((int)$selected === $m) ? ' selected="selected"' : '';
        $html .= '<option value="' . $m . '"' . $sel . '>' . htmlspecialchars(ppto_nombre_mes($m)) . '</option>';
    }
    return $html;
}

/**
 * Perfiles con acceso a opciones avanzadas de reglas presupuestarias.
 *
 * @return array
 */
function ppto_usuario_perfiles_nombres() {
    if (isset($GLOBALS['Ses_Per_Des']) && $GLOBALS['Ses_Per_Des'] !== '') {
        $perfiles = $GLOBALS['Ses_Per_Des'];
    } elseif (isset($_SESSION['Ses_Per_Des'])) {
        $perfiles = $_SESSION['Ses_Per_Des'];
    } else {
        return array();
    }
    if (!is_array($perfiles)) {
        return ($perfiles === '' || $perfiles === null) ? array() : array($perfiles);
    }
    return $perfiles;
}

/**
 * @param string $texto
 * @return bool
 */
function ppto_perfil_texto_es_admin($texto) {
    $t = strtolower(trim((string)$texto));
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        if ($conv !== false) {
            $t = strtolower($conv);
        }
    }
    if ($t === 'administrador de sistemas') {
        return true;
    }
    if (strpos($t, 'administrador') !== false) {
        return true;
    }
    return false;
}

/**
 * @return bool
 */
function ppto_usuario_es_admin() {
    foreach (ppto_usuario_perfiles_nombres() as $perfil) {
        if (ppto_perfil_texto_es_admin($perfil)) {
            return true;
        }
    }
    return false;
}

/**
 * Respaldo consultando perfiles del usuario en BD (EXA legacy).
 *
 * @param mysqli $mysqli
 * @param int $Usu_Cod
 * @param int $Emp_Cod
 * @return bool
 */
function ppto_usuario_es_admin_db($mysqli, $Usu_Cod, $Emp_Cod) {
    if (!$mysqli || (int)$Usu_Cod <= 0) {
        return false;
    }
    $Usu_Cod = (int)$Usu_Cod;
    $Emp_Cod = (int)$Emp_Cod;
    $sql = "SELECT p.Per_Des FROM usuarperfi up
            INNER JOIN perfiles p ON p.Per_Cod = up.Per_Cod
            WHERE up.Usu_Cod = $Usu_Cod";
    if ($Emp_Cod > 0) {
        $sql .= " AND p.Emp_Cod = $Emp_Cod";
    }
    $res = $mysqli->query($sql);
    if (!$res) {
        return false;
    }
    while ($row = $res->fetch_assoc()) {
        if (ppto_perfil_texto_es_admin($row['Per_Des'])) {
            return true;
        }
    }
    return false;
}

/**
 * Catalogo UI/validacion de reglas de asignacion por tipo de documento.
 *
 * @return array
 */
function ppto_regla_catalogo_ui() {
    return array(
        'liquidacion_nomina' => array(
            'label' => 'Nomina / Rol de pagos',
            'montos' => array(
                array('v' => 'total_rol', 't' => 'Total del rol de pagos')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los roles de pago (recomendado)')
            )
        ),
        'orden_compra' => array(
            'label' => 'Orden de compra',
            'montos' => array(
                array('v' => 'Ord_Imp', 't' => 'Importe total de la orden')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todas las ordenes de compra (recomendado)')
            )
        ),
        'pago_tesoreria' => array(
            'label' => 'Pago de tesoreria',
            'montos' => array(
                array('v' => 'Pag_Val', 't' => 'Valor del pago')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los pagos (recomendado)')
            )
        ),
        'ventas' => array(
            'label' => 'Ventas / Factura',
            'montos' => array(
                array('v' => 'Vet_Sub', 't' => 'Subtotal de la factura (sin IVA)'),
                array('v' => 'Vet_Tot', 't' => 'Total de la factura (con IVA)')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todas las ventas (recomendado)'),
                array('campo' => 'Vet_Tip', 'valor' => 'S', 't' => 'Solo facturas de servicios'),
                array('campo' => 'Vet_Est', 'valor' => 'I', 't' => 'Notas de credito / anulaciones')
            )
        ),
        'egreso_inventario' => array(
            'label' => 'Egreso de inventario',
            'montos' => array(
                array('v' => 'Mov_Val', 't' => 'Valor del egreso de bodega')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los egresos (recomendado)')
            )
        ),
        'adquisicion_activo' => array(
            'label' => 'Adquisicion activo fijo',
            'montos' => array(
                array('v' => 'Act_Val', 't' => 'Valor de la adquisicion')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todas las adquisiciones (recomendado)')
            )
        ),
        'compras' => array(
            'label' => 'Compras',
            'montos' => array(
                array('v' => 'Cop_Sub', 't' => 'Subtotal del comprobante de compra'),
                array('v' => 'Cop_Tot', 't' => 'Total del comprobante de compra')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todas las compras (recomendado)')
            )
        ),
        'comprobantes' => array(
            'label' => 'Comprobante de egreso',
            'montos' => array(
                array('v' => 'Com_Val', 't' => 'Valor del comprobante')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los comprobantes (recomendado)')
            )
        ),
        'movimiento_cheques' => array(
            'label' => 'Movimiento de cheques',
            'montos' => array(
                array('v' => 'Mov_Val', 't' => 'Valor del cheque')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los cheques (recomendado)')
            )
        ),
        'asientos' => array(
            'label' => 'Asiento contable',
            'montos' => array(
                array('v' => 'Asi_Val', 't' => 'Valor del asiento')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los asientos (recomendado)')
            )
        ),
        'rol_pagos' => array(
            'label' => 'Rol de pagos (legacy)',
            'montos' => array(
                array('v' => 'total_rol', 't' => 'Total del rol de pagos')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Todos los roles (recomendado)')
            )
        ),
        '_default' => array(
            'label' => 'Documento',
            'montos' => array(
                array('v' => '', 't' => 'Definir manualmente (avanzado)')
            ),
            'condiciones' => array(
                array('campo' => '', 'valor' => '', 't' => 'Sin condicion (recomendado)')
            )
        )
    );
}

/**
 * Valida que condicion y monto pertenezcan al catalogo permitido para usuarios no admin.
 *
 * @param string $tip_doc
 * @param string $campo
 * @param string $valor
 * @param string $campo_monto
 * @return bool
 */
function ppto_regla_catalogo_validar($tip_doc, $campo, $valor, $campo_monto) {
    $catalogo = ppto_regla_catalogo_ui();
    if (!isset($catalogo[$tip_doc])) {
        return false;
    }
    $def = $catalogo[$tip_doc];
    $campo = trim((string)$campo);
    $valor = trim((string)$valor);
    $campo_monto = trim((string)$campo_monto);

    $monto_ok = false;
    foreach ($def['montos'] as $m) {
        if ($m['v'] === $campo_monto) {
            $monto_ok = true;
            break;
        }
    }
    if (!$monto_ok) {
        return false;
    }

    foreach ($def['condiciones'] as $c) {
        if ((string)$c['campo'] === $campo && (string)$c['valor'] === $valor) {
            return true;
        }
    }
    return false;
}

/**
 * Etiqueta legible para ppa_tipo (contable).
 *
 * @param string $tipo
 * @return string
 */
function ppto_tipo_contable_etiqueta($tipo) {
    $map = array('I' => 'Ingreso', 'G' => 'Gasto', 'V' => 'Inversion');
    $t = strtoupper(trim((string)$tipo));
    return isset($map[$t]) ? $map[$t] : $tipo;
}

/**
 * Etiqueta legible para ppa_clase (estructura).
 *
 * @param string $clase
 * @return string
 */
function ppto_clase_estructura_etiqueta($clase) {
    return ($clase === 'G') ? 'Grupo' : 'Detalle';
}

/**
 * Acumula presupuesto/ejecucion de partidas Detalle hacia sus Grupos ancestros.
 *
 * @param array $rows
 * @return array
 */
function ppto_consulta_rollup_partidas($rows) {
    if (empty($rows)) {
        return $rows;
    }

    $by_cod = array();
    foreach ($rows as $i => $r) {
        $rows[$i]['Presupuestado'] = (float)$r['Presupuestado'];
        $rows[$i]['Ejecutado'] = (float)$r['Ejecutado'];
        $clase = isset($r['ppa_clase']) && $r['ppa_clase'] !== '' ? $r['ppa_clase'] : 'D';
        $rows[$i]['ppa_clase'] = $clase;
        $by_cod[$r['ppa_codigo_clasificacion']] = $i;
        if ($clase === 'G') {
            $rows[$i]['Presupuestado'] = 0.00;
            $rows[$i]['Ejecutado'] = 0.00;
        }
    }

    foreach ($rows as $r) {
        if ($r['ppa_clase'] !== 'D') {
            continue;
        }
        $cod = $r['ppa_codigo_clasificacion'];
        $parts = explode('.', $cod);
        $n = count($parts);
        if ($n < 2) {
            continue;
        }
        for ($l = 1; $l < $n; $l++) {
            $anc = implode('.', array_slice($parts, 0, $l));
            if (!isset($by_cod[$anc])) {
                continue;
            }
            $idx = $by_cod[$anc];
            if ($rows[$idx]['ppa_clase'] !== 'G') {
                continue;
            }
            $rows[$idx]['Presupuestado'] += (float)$r['Presupuestado'];
            $rows[$idx]['Ejecutado'] += (float)$r['Ejecutado'];
        }
    }

    foreach ($rows as $i => $r) {
        $pres = (float)$rows[$i]['Presupuestado'];
        $ej = (float)$rows[$i]['Ejecutado'];
        $rows[$i]['Disponible'] = $pres - $ej;
        $rows[$i]['Pct_Ejecutado'] = $pres > 0.0001 ? round(($ej / $pres) * 100, 2) : 0.00;
    }

    return $rows;
}

/**
 * Suma solo partidas hoja (clase D) para KPIs/resumenes.
 * Evita doble conteo cuando el rollup repite montos en grupos G.
 *
 * @param array $rows
 * @return array {presupuestado, ejecutado, disponible}
 */
function ppto_consulta_sumar_hojas($rows) {
    $out = array('presupuestado' => 0.0, 'ejecutado' => 0.0, 'disponible' => 0.0);
    if (empty($rows)) {
        return $out;
    }
    foreach ($rows as $r) {
        $clase = isset($r['ppa_clase']) && $r['ppa_clase'] !== '' ? $r['ppa_clase'] : 'D';
        if ($clase !== 'D') {
            continue;
        }
        $out['presupuestado'] += (float)$r['Presupuestado'];
        $out['ejecutado'] += (float)$r['Ejecutado'];
    }
    $out['presupuestado'] = round($out['presupuestado'], 2);
    $out['ejecutado'] = round($out['ejecutado'], 2);
    $out['disponible'] = round($out['presupuestado'] - $out['ejecutado'], 2);
    return $out;
}

/**
 * Partidas ppa_id cubiertas por proyectos presupuestarios (RCET etc.).
 *
 * @param mysqli $mysqli
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @return array int[]
 */
function ppto_consulta_ppa_ids_proyecto($mysqli, $Emp_Cod, $ppe_id) {
    $out = array();
    $Emp_Cod = (int)$Emp_Cod;
    $ppe_id = (int)$ppe_id;
    if (!$mysqli || $Emp_Cod <= 0 || $ppe_id <= 0) {
        return $out;
    }
    $res = $mysqli->query("SELECT DISTINCT ppa_id FROM pre_proyecto_detalles
        WHERE Emp_Cod=$Emp_Cod AND ppe_id=$ppe_id
          AND proy_id IS NOT NULL AND proy_id != ''");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[(int)$r['ppa_id']] = true;
        }
    }
    return $out;
}

/**
 * Deja solo filas del plan estandar (excluye partidas de proyectos Relavera/RCET).
 *
 * @param array $rows
 * @param array $ppa_proyecto_map ppa_id => true
 * @return array
 */
function ppto_consulta_filtrar_plan_estandar($rows, $ppa_proyecto_map) {
    if (empty($rows) || empty($ppa_proyecto_map)) {
        return $rows;
    }
    $out = array();
    foreach ($rows as $r) {
        $ppa = isset($r['ppa_id']) ? (int)$r['ppa_id'] : 0;
        if ($ppa > 0 && isset($ppa_proyecto_map[$ppa])) {
            continue;
        }
        $out[] = $r;
    }
    return $out;
}

/**
 * Consulta SQL unificada (subconsulta derivada) que sustituye el uso de vistas guardadas en la BD.
 * Genera dinámicamente el resumen de presupuesto, ejecuciones, reajustes y disponibles.
 *
 * @return string
 */
function ppto_sql_resumen_subquery() {
    return "SELECT
        d.Emp_Cod AS Emp_Cod,
        d.Ppe_Cod AS ppe_id, d.Ppe_Cod AS Ppe_Cod,
        d.Ppa_Cod AS ppa_id, d.Ppa_Cod AS Ppa_Cod,
        CAST(NULL AS CHAR(50)) AS proy_id, CAST(NULL AS CHAR(50)) AS Pro_Cod,
        CAST(NULL AS CHAR(100)) AS pdp_rubro, CAST(NULL AS CHAR(100)) AS Pdp_Rubro,
        d.Pde_Mes AS mes, d.Pde_Mes AS Mes,
        d.Pde_Mon AS inicial, d.Pde_Mon AS Inicial,
        CAST(0.00 AS DECIMAL(14,2)) AS reajustes, CAST(0.00 AS DECIMAL(14,2)) AS Reajustes,
        d.Pde_Mon AS vigente, d.Pde_Mon AS Vigente,
        CAST(0.00 AS DECIMAL(14,2)) AS comprometido, CAST(0.00 AS DECIMAL(14,2)) AS Comprometido,
        CAST(0.00 AS DECIMAL(14,2)) AS ejecutado, CAST(0.00 AS DECIMAL(14,2)) AS Ejecutado,
        d.Pde_Mon AS disponible, d.Pde_Mon AS Disponible
      FROM pre_detalle d
      INNER JOIN pre_presupuesto c ON d.Ppe_Cod = c.Ppe_Cod
      UNION ALL
      SELECT
        pd.Emp_Cod AS Emp_Cod,
        pd.Ppe_Cod AS ppe_id, pd.Ppe_Cod AS Ppe_Cod,
        pd.Ppa_Cod AS ppa_id, pd.Ppa_Cod AS Ppa_Cod,
        CAST(pd.Pro_Cod AS CHAR(50)) AS proy_id, CAST(pd.Pro_Cod AS CHAR(50)) AS Pro_Cod,
        pd.Pdp_Rubro AS pdp_rubro, pd.Pdp_Rubro AS Pdp_Rubro,
        pdm.Pdm_Mes AS mes, pdm.Pdm_Mes AS Mes,
        pdm.Pdm_PreMensual AS inicial, pdm.Pdm_PreMensual AS Inicial,
        CAST(0.00 AS DECIMAL(14,2)) AS reajustes, CAST(0.00 AS DECIMAL(14,2)) AS Reajustes,
        pdm.Pdm_PreMensual AS vigente, pdm.Pdm_PreMensual AS Vigente,
        IFNULL(pdm.Pdm_Comprometido, 0.00) AS comprometido, IFNULL(pdm.Pdm_Comprometido, 0.00) AS Comprometido,
        IFNULL(pdm.Pdm_Ejecutado, 0.00) AS ejecutado, IFNULL(pdm.Pdm_Ejecutado, 0.00) AS Ejecutado,
        IFNULL(pdm.Pdm_Disponible, pdm.Pdm_PreMensual) AS disponible, IFNULL(pdm.Pdm_Disponible, pdm.Pdm_PreMensual) AS Disponible
      FROM pre_proyecto_detalles pd
      INNER JOIN pre_proyecto_detalles_mes pdm ON pd.Pdp_Cod = pdm.Pdp_Cod
      UNION ALL
      SELECT
        r.Emp_Cod AS Emp_Cod,
        r.Ppe_Cod AS ppe_id, r.Ppe_Cod AS Ppe_Cod,
        r.Ppa_Cod_Destino AS ppa_id, r.Ppa_Cod_Destino AS Ppa_Cod,
        CAST(r.Pro_Cod_Destino AS CHAR(50)) AS proy_id, CAST(r.Pro_Cod_Destino AS CHAR(50)) AS Pro_Cod,
        r.Rea_RubroDestino AS pdp_rubro, r.Rea_RubroDestino AS Pdp_Rubro,
        r.Rea_Mes AS mes, r.Rea_Mes AS Mes,
        0.00 AS inicial, 0.00 AS Inicial,
        r.Rea_Mon AS reajustes, r.Rea_Mon AS Reajustes,
        r.Rea_Mon AS vigente, r.Rea_Mon AS Vigente,
        0.00 AS comprometido, 0.00 AS Comprometido,
        0.00 AS ejecutado, 0.00 AS Ejecutado,
        r.Rea_Mon AS disponible, r.Rea_Mon AS Disponible
      FROM pre_reajustes r WHERE r.Rea_Tipo IN ('incremento','transferencia')
      UNION ALL
      SELECT
        r.Emp_Cod AS Emp_Cod,
        r.Ppe_Cod AS ppe_id, r.Ppe_Cod AS Ppe_Cod,
        r.Ppa_Cod_Origen AS ppa_id, r.Ppa_Cod_Origen AS Ppa_Cod,
        CAST(r.Pro_Cod_Origen AS CHAR(50)) AS proy_id, CAST(r.Pro_Cod_Origen AS CHAR(50)) AS Pro_Cod,
        r.Rea_RubroOrigen AS pdp_rubro, r.Rea_RubroOrigen AS Pdp_Rubro,
        r.Rea_Mes AS mes, r.Rea_Mes AS Mes,
        0.00 AS inicial, 0.00 AS Inicial,
        -(r.Rea_Mon) AS reajustes, -(r.Rea_Mon) AS Reajustes,
        -(r.Rea_Mon) AS vigente, -(r.Rea_Mon) AS Vigente,
        0.00 AS comprometido, 0.00 AS Comprometido,
        0.00 AS ejecutado, 0.00 AS Ejecutado,
        -(r.Rea_Mon) AS disponible, -(r.Rea_Mon) AS Disponible
      FROM pre_reajustes r WHERE r.Rea_Tipo = 'transferencia'
      UNION ALL
      SELECT
        r.Emp_Cod AS Emp_Cod,
        r.Ppe_Cod AS ppe_id, r.Ppe_Cod AS Ppe_Cod,
        r.Ppa_Cod_Destino AS ppa_id, r.Ppa_Cod_Destino AS Ppa_Cod,
        CAST(r.Pro_Cod_Destino AS CHAR(50)) AS proy_id, CAST(r.Pro_Cod_Destino AS CHAR(50)) AS Pro_Cod,
        r.Rea_RubroDestino AS pdp_rubro, r.Rea_RubroDestino AS Pdp_Rubro,
        r.Rea_Mes AS mes, r.Rea_Mes AS Mes,
        0.00 AS inicial, 0.00 AS Inicial,
        -(r.Rea_Mon) AS reajustes, -(r.Rea_Mon) AS Reajustes,
        -(r.Rea_Mon) AS vigente, -(r.Rea_Mon) AS Vigente,
        0.00 AS comprometido, 0.00 AS Comprometido,
        0.00 AS ejecutado, 0.00 AS Ejecutado,
        -(r.Rea_Mon) AS disponible, -(r.Rea_Mon) AS Disponible
      FROM pre_reajustes r WHERE r.Rea_Tipo = 'reduccion'
      UNION ALL
      SELECT
        pe.Emp_Cod AS Emp_Cod,
        pe.Ppe_Cod AS ppe_id, pe.Ppe_Cod AS Ppe_Cod,
        pe.Ppa_Cod AS ppa_id, pe.Ppa_Cod AS Ppa_Cod,
        CAST(pe.Pro_Cod AS CHAR(50)) AS proy_id, CAST(pe.Pro_Cod AS CHAR(50)) AS Pro_Cod,
        pe.Pej_Rubro AS pdp_rubro, pe.Pej_Rubro AS Pdp_Rubro,
        pe.Pej_Mes AS mes, pe.Pej_Mes AS Mes,
        0.00 AS inicial, 0.00 AS Inicial,
        0.00 AS reajustes, 0.00 AS Reajustes,
        0.00 AS vigente, 0.00 AS Vigente,
        (CASE WHEN pe.Pej_Fase='C' THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END) AS comprometido,
        (CASE WHEN pe.Pej_Fase='C' THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END) AS Comprometido,
        (CASE WHEN pe.Pej_Fase='E' THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END) AS ejecutado,
        (CASE WHEN pe.Pej_Fase='E' THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END) AS Ejecutado,
        (-1*(CASE WHEN pe.Pej_Fase IN ('C','E') THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END)) AS disponible,
        (-1*(CASE WHEN pe.Pej_Fase IN ('C','E') THEN (CASE WHEN pe.Pej_Sig='+' THEN pe.Pej_Mon ELSE -(pe.Pej_Mon) END) ELSE 0.00 END)) AS Disponible
      FROM pre_ejecucion pe";
}
