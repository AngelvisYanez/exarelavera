<?php
/**
 * ppto_persistencia_logica.php
 * Capa de Persistencia y L�gica de Datos para el M�dulo de Presupuestos (EXA PPTO).
 * centraliza todas las consultas SQL y operaciones CRUD del presupuesto.
 */

/**
 * Motor de persistencia unificado para EXA PPTO.
 *
 * @param mysqli $mysqli Objeto de conexi�n a la base de datos.
 * @param int $caso Identificador del caso o consulta SQL a ejecutar.
 * @param array $p Par�metros requeridos para la consulta estructurados en un arreglo asociativo.
 * @return mixed Retorna ID insertado, valor escalar, array de resultados o null/false seg�n el caso.
 */
function ppto_persistencia_consultar($mysqli, $caso, $p) {
    switch ($caso) {
        case 1:
            // Obtener el ID de la cabecera del presupuesto activo para una empresa y a�o espec�ficos.
            $emp_id = $mysqli->real_escape_string($p['emp_id']);
            $ani = $mysqli->real_escape_string(isset($p['ppe_anio']) ? $p['ppe_anio'] : date('Y'));
            $sql = "SELECT ppe_id FROM exa_ppto_cabeceras WHERE emp_id = '$emp_id' AND ppe_anio = '$ani' AND ppe_estado = 'A' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['ppe_id'];
            }
            return null;

        case 2:
            // Obtener las reglas de asignaci�n autom�ticas activas para un tipo de documento y empresa.
            $emp_id = $mysqli->real_escape_string($p['emp_id']);
            $tip_doc = $mysqli->real_escape_string($p['prg_tipo_documento']);
            $sql = "SELECT * FROM exa_ppto_reglas WHERE emp_id = '$emp_id' AND prg_tipo_documento = '$tip_doc' AND prg_estado = 'A' ORDER BY prg_prioridad ASC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;

        case 3:
            // Verificar si ya existe un registro de ejecuci�n id�ntico (evitar duplicados transaccionales).
            $tip_doc = $mysqli->real_escape_string($p['pej_tipo_documento']);
            $doc_cod = $mysqli->real_escape_string($p['pej_documento_codigo']);
            $sig = $mysqli->real_escape_string($p['pej_signo']);
            $sql = "SELECT pej_id FROM exa_ppto_ejecuciones WHERE pej_tipo_documento = '$tip_doc' AND pej_documento_codigo = '$doc_cod' AND pej_signo = '$sig' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['pej_id'];
            }
            return null;

        case 4:
            // Insertar una nueva transacci�n en el ledger de ejecuci�n presupuestaria.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $emp_id = (int)$p['emp_id'];
            $suc_id = (isset($p['suc_id']) && $p['suc_id'] !== '' && $p['suc_id'] !== null) ? (int)$p['suc_id'] : "NULL";
            $dep_id = (isset($p['dep_id']) && $p['dep_id'] !== '' && $p['dep_id'] !== null) ? (int)$p['dep_id'] : "NULL";
            $proy_id = (isset($p['proy_id']) && $p['proy_id'] !== '' && $p['proy_id'] !== null) ? "'" . $mysqli->real_escape_string($p['proy_id']) . "'" : "NULL";
            $pej_mes = (int)$p['pej_mes'];
            $pej_anio = (int)$p['pej_anio'];
            $pej_tip_doc = $mysqli->real_escape_string($p['pej_tipo_documento']);
            $pej_doc_cod = $mysqli->real_escape_string($p['pej_documento_codigo']);
            $pej_mon = (float)$p['pej_monto'];
            $pej_sig = $mysqli->real_escape_string($p['pej_signo']);
            $pej_fec = $mysqli->real_escape_string($p['pej_fecha_documento']);
            $usu_id = (int)$p['usu_id'];
            $prg_id = (isset($p['prg_id']) && $p['prg_id'] !== '' && $p['prg_id'] !== null) ? (int)$p['prg_id'] : "NULL";
            $pej_fase = (isset($p['pej_fase']) && $p['pej_fase'] !== '') ? "'" . $mysqli->real_escape_string($p['pej_fase']) . "'" : "'E'";
            $pej_rubro = (isset($p['pej_rubro']) && $p['pej_rubro'] !== '' && $p['pej_rubro'] !== null) ? "'" . $mysqli->real_escape_string($p['pej_rubro']) . "'" : "NULL";

            $sql = "INSERT INTO exa_ppto_ejecuciones (
                        ppe_id, ppa_id, emp_id, suc_id, dep_id, proy_id, 
                        pej_mes, pej_anio, pej_tipo_documento, pej_documento_codigo, pej_monto, pej_signo, 
                        pej_fecha_documento, pej_fecha_registro, usu_id, prg_id, pej_fase, pej_rubro
                    ) VALUES (
                        $ppe_id, $ppa_id, $emp_id, $suc_id, $dep_id, $proy_id,
                        $pej_mes, $pej_anio, '$pej_tip_doc', '$pej_doc_cod', $pej_mon, '$pej_sig',
                        '$pej_fec', NOW(), $usu_id, $prg_id, $pej_fase, $pej_rubro
                    )";
            $res = $mysqli->query($sql);
            return $res ? $mysqli->insert_id : false;

        case 5:
            // Calcular el total presupuestado acumulado mensual de una partida.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $mes = isset($p['mes']) ? (int)$p['mes'] : (isset($p['pde_mes']) ? (int)$p['pde_mes'] : 12);
            $sql = "SELECT SUM(pde_monto) AS Total FROM exa_ppto_detalles WHERE ppe_id = $ppe_id AND ppa_id = $ppa_id AND pde_mes <= $mes";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return $row['Total'] !== null ? (float)$row['Total'] : 0.0;
            }
            return 0.0;

        case 6:
            // Calcular la ejecuci�n acumulada de una partida hasta un mes del a�o activo.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $ani = isset($p['pej_anio']) ? (int)$p['pej_anio'] : date('Y');
            $mes = isset($p['pej_mes']) ? (int)$p['pej_mes'] : 12;
            $sql = "SELECT SUM(CASE WHEN pej_signo = '+' THEN pej_monto ELSE -pej_monto END) AS Total 
                    FROM exa_ppto_ejecuciones 
                    WHERE ppe_id = $ppe_id 
                      AND ppa_id = $ppa_id 
                      AND pej_anio = $ani 
                      AND pej_mes <= $mes";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return $row['Total'] !== null ? (float)$row['Total'] : 0.0;
            }
            return 0.0;

        case 7:
            // Registrar una alerta presupuestaria por desv�o/superaci�n de umbrales.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $pal_umb = (int)$p['pal_umbral'];
            $pal_pct = (float)$p['pal_porcentaje_actual'];
            $pej_id = (int)$p['pej_id'];
            $usu_id = isset($p['usu_id']) ? (int)$p['usu_id'] : "NULL";
            $sql = "INSERT IGNORE INTO exa_ppto_alertas (ppe_id, ppa_id, pal_umbral, pal_porcentaje_actual, pej_id, pal_leido, pal_fecha_registro, usu_id)
                    VALUES ($ppe_id, $ppa_id, $pal_umb, $pal_pct, $pej_id, 'N', NOW(), $usu_id)";
            $res = $mysqli->query($sql);
            return $res ? true : false;

        case 8:
            // Generar el reporte matricial de balanza presupuestaria (Presupuestado/Proyectado, Ejecutado, Disponible, %).
            // pej_vista: anual|acumulado|mes (defecto acumulado/anual con mes<=N).
            // Presupuestado = plan mensual (exa_ppto_detalles) + proyectado publicado de proyectos (Relaves).
            $emp_id = (int)$p['emp_id'];
            $ani = isset($p['ppe_anio']) ? (int)$p['ppe_anio'] : (int)date('Y');
            $mes = isset($p['pej_mes']) ? (int)$p['pej_mes'] : 12;
            $mes = max(1, min(12, $mes));
            $vista = isset($p['pej_vista']) ? strtolower(trim((string)$p['pej_vista'])) : 'acumulado';
            if (!in_array($vista, array('anual', 'acumulado', 'mes'), true)) {
                $vista = 'acumulado';
            }
            if ($vista === 'anual') {
                $mes = 12;
            }
            $ppe_id_f = isset($p['ppe_id']) ? (int)$p['ppe_id'] : 0;

            if ($ppe_id_f > 0) {
                $where_pp_det = "pp.ppe_id = $ppe_id_f";
                $where_pp_ej = "pp.ppe_id = $ppe_id_f";
                $where_pp_proy = "pd.ppe_id = $ppe_id_f AND pd.emp_id = $emp_id";
            } else {
                $where_pp_det = "pp.emp_id = $emp_id AND pp.ppe_anio = $ani AND pp.ppe_estado = 'A'";
                $where_pp_ej = "pp.emp_id = $emp_id AND pp.ppe_anio = $ani AND pp.ppe_estado = 'A'";
                $where_pp_proy = "pd.emp_id = $emp_id AND pd.ppe_id IN (
                    SELECT ppe_id FROM exa_ppto_cabeceras
                    WHERE emp_id = $emp_id AND ppe_anio = $ani AND ppe_estado = 'A'
                )";
            }

            if ($vista === 'mes') {
                $where_mes_det = "pd.pde_mes = $mes";
                $where_mes_ej = "pe.pej_mes = $mes";
                $where_mes_proy = "pdm.pdm_mes = $mes";
            } else {
                $where_mes_det = "pd.pde_mes <= $mes";
                $where_mes_ej = "pe.pej_mes <= $mes";
                $where_mes_proy = "pdm.pdm_mes <= $mes";
            }

            $sql = "SELECT 
                        p.ppa_id,
                        p.ppa_codigo_clasificacion, 
                        p.ppa_descripcion, 
                        p.ppa_tipo, 
                        p.ppa_naturaleza,
                        COALESCE(NULLIF(p.ppa_clase, ''), 'D') AS ppa_clase,
                        p.ppa_nivel,
                        (IFNULL(d.Presupuestado, 0.00) + IFNULL(pr.Proyectado, 0.00)) AS Presupuestado,
                        IFNULL(d.Presupuestado, 0.00) AS Presup_Plan,
                        IFNULL(pr.Proyectado, 0.00) AS Proyectado,
                        IFNULL(e.Ejecutado, 0.00) AS Ejecutado,
                        ((IFNULL(d.Presupuestado, 0.00) + IFNULL(pr.Proyectado, 0.00)) - IFNULL(e.Ejecutado, 0.00)) AS Disponible,
                        CASE 
                            WHEN (IFNULL(d.Presupuestado, 0.00) + IFNULL(pr.Proyectado, 0.00)) > 0 
                            THEN ROUND((IFNULL(e.Ejecutado, 0.00) / (IFNULL(d.Presupuestado, 0.00) + IFNULL(pr.Proyectado, 0.00))) * 100, 2) 
                            ELSE 0.00 
                        END AS Pct_Ejecutado
                    FROM exa_ppto_partidas p
                    LEFT JOIN (
                        SELECT pd.ppa_id, SUM(pd.pde_monto) AS Presupuestado
                        FROM exa_ppto_detalles pd
                        INNER JOIN exa_ppto_cabeceras pp ON pd.ppe_id = pp.ppe_id
                        INNER JOIN exa_ppto_partidas px ON px.ppa_id = pd.ppa_id
                            AND px.emp_id = $emp_id AND px.ppa_estado = 'A'
                        WHERE $where_pp_det
                          AND $where_mes_det
                        GROUP BY pd.ppa_id
                    ) d ON p.ppa_id = d.ppa_id
                    LEFT JOIN (
                        SELECT pd.ppa_id, SUM(pdm.pdm_presupuesto_mensual) AS Proyectado
                        FROM exa_ppto_proyecto_detalles pd
                        INNER JOIN exa_ppto_proyecto_detalles_mes pdm ON pd.pdp_id = pdm.pdp_id
                        WHERE $where_pp_proy
                          AND pd.proy_id IS NOT NULL AND pd.proy_id != ''
                          AND $where_mes_proy
                        GROUP BY pd.ppa_id
                    ) pr ON p.ppa_id = pr.ppa_id
                    LEFT JOIN (
                        SELECT pe.ppa_id, SUM(CASE WHEN pe.pej_signo = '+' THEN pe.pej_monto ELSE -pe.pej_monto END) AS Ejecutado
                        FROM exa_ppto_ejecuciones pe
                        INNER JOIN exa_ppto_cabeceras pp ON pe.ppe_id = pp.ppe_id
                        INNER JOIN exa_ppto_partidas px ON px.ppa_id = pe.ppa_id
                            AND px.emp_id = $emp_id AND px.ppa_estado = 'A'
                        WHERE $where_pp_ej
                          AND $where_mes_ej
                        GROUP BY pe.ppa_id
                    ) e ON p.ppa_id = e.ppa_id
                    WHERE p.emp_id = $emp_id 
                      AND p.ppa_estado = 'A'
                    ORDER BY p.ppa_codigo_clasificacion ASC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;

        case 9:
            // Obtener alertas presupuestarias activas (no le�das) de la versi�n activa de presupuesto.
            $emp_id = (int)$p['emp_id'];
            $ppe_id = (int)$p['ppe_id'];
            $sql = "SELECT a.*, p.ppa_codigo_clasificacion, p.ppa_descripcion, p.ppa_tipo, p.ppa_naturaleza 
                    FROM exa_ppto_alertas a
                    INNER JOIN exa_ppto_partidas p ON a.ppa_id = p.ppa_id
                    WHERE a.ppe_id = $ppe_id 
                      AND p.emp_id = $emp_id 
                      AND a.pal_leido = 'N'
                    ORDER BY a.pal_fecha_registro DESC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;

        case 10:
            require_once __DIR__ . '/ppto_partidas_logica.php';
            $opts = array('emp_id' => (int)$p['emp_id']);
            if (!empty($p['incluir_inactivos'])) {
                $opts['incluir_inactivas'] = true;
            }
            if (isset($p['clase']) && ($p['clase'] === 'G' || $p['clase'] === 'D')) {
                $opts['clase'] = $p['clase'];
            }
            return ppto_partidas_listar($mysqli, $opts);

        case 11:
            // Consultar la distribuci�n nominal por cada uno de los 12 meses de una partida espec�fica.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $sql = "SELECT m.Mes AS pde_mes, IFNULL(d.pde_monto, 0.00) AS pde_monto, d.pde_id
                    FROM (
                        SELECT 1 AS Mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 
                        UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
                    ) m
                    LEFT JOIN exa_ppto_detalles d ON m.Mes = d.pde_mes AND d.ppe_id = $ppe_id AND d.ppa_id = $ppa_id
                    ORDER BY m.Mes ASC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;

        case 12:
            // Consultar el desglose de documentos que componen la ejecuci�n real de una partida en particular.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $mes_cond = "";
            if (isset($p['mes']) && $p['mes'] !== '' && $p['mes'] !== null) {
                $mes = (int)$p['mes'];
                $mes_cond = " AND pej_mes = $mes ";
            } elseif (isset($p['pej_mes']) && $p['pej_mes'] !== '' && $p['pej_mes'] !== null) {
                $mes = (int)$p['pej_mes'];
                $mes_cond = " AND pej_mes = $mes ";
            }
            $sql = "SELECT * FROM exa_ppto_ejecuciones 
                    WHERE ppe_id = $ppe_id 
                      AND ppa_id = $ppa_id 
                      $mes_cond
                    ORDER BY pej_fecha_documento DESC, pej_fecha_registro DESC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;
    }
    return null;
}

/**
 * Totales consolidados de lectura para tab Metricas Admin (vista exa_ppto_resumen).
 * No modifica datos; alinea cifras con el Dashboard de control.
 *
 * @param mysqli $mysqli
 * @param int $emp_id
 * @param int $ppe_id
 * @param int $mes_hasta Mes acumulado (1-12)
 * @return array
 */
function ppto_admin_metricas_consolidado($mysqli, $emp_id, $ppe_id, $mes_hasta, $vista = 'acumulado') {
    $out = array(
        'estandar_vigente' => 0.0,
        'estandar_ejecutado' => 0.0,
        'estandar_disponible' => 0.0,
        'total_vigente' => 0.0,
        'total_ejecutado' => 0.0,
        'total_disponible' => 0.0,
        'proyectos' => array(),
    );

    if (!$mysqli || $emp_id <= 0 || $ppe_id <= 0) {
        return $out;
    }

    $vista = strtolower(trim((string)$vista));
    if (!in_array($vista, array('anual', 'acumulado', 'mes'), true)) {
        $vista = 'acumulado';
    }
    $mes_hasta = max(1, min(12, (int)$mes_hasta));
    if ($vista === 'anual') {
        $mes_hasta = 12;
    }
    $join_px = "INNER JOIN exa_ppto_partidas px ON px.ppa_id = r.ppa_id
        AND px.ppa_estado = 'A'
        AND COALESCE(NULLIF(px.ppa_clase, ''), 'D') = 'D'";

    if ($vista === 'mes') {
        $where_mes = "r.mes = $mes_hasta";
    } else {
        $where_mes = "r.mes <= $mes_hasta";
    }
    $base_where = "r.emp_id = $emp_id AND r.ppe_id = $ppe_id AND $where_mes";

    $sql_std = "SELECT
            COALESCE(SUM(r.vigente), 0) AS vigente,
            COALESCE(SUM(r.ejecutado), 0) AS ejecutado,
            COALESCE(SUM(r.disponible), 0) AS disponible
        FROM exa_ppto_resumen r
        $join_px
        WHERE $base_where
          AND (r.proy_id IS NULL OR r.proy_id = '')
          AND r.ppa_id NOT IN (
              SELECT DISTINCT pd.ppa_id
              FROM exa_ppto_proyecto_detalles pd
              WHERE pd.emp_id = $emp_id AND pd.ppe_id = $ppe_id
                AND pd.proy_id IS NOT NULL AND pd.proy_id != ''
          )";
    $res_std = $mysqli->query($sql_std);
    if ($res_std && ($row = $res_std->fetch_assoc())) {
        $out['estandar_vigente'] = round((float)$row['vigente'], 2);
        $out['estandar_ejecutado'] = round((float)$row['ejecutado'], 2);
        $out['estandar_disponible'] = round((float)$row['disponible'], 2);
    }

    $sql_proy = "SELECT r.proy_id,
            COALESCE(SUM(r.vigente), 0) AS vigente,
            COALESCE(SUM(r.ejecutado), 0) AS ejecutado,
            COALESCE(SUM(r.disponible), 0) AS disponible
        FROM exa_ppto_resumen r
        $join_px
        WHERE $base_where
          AND r.proy_id IS NOT NULL AND r.proy_id != ''
        GROUP BY r.proy_id
        HAVING vigente > 0.009 OR ejecutado > 0.009
        ORDER BY r.proy_id ASC";
    $res_proy = $mysqli->query($sql_proy);
    $nombres_proy = array();
    $res_np = $mysqli->query("SELECT proy_id, proy_nombre FROM exa_ppto_proyectos
        WHERE emp_id = $emp_id AND proy_estado = 'A'");
    if ($res_np) {
        while ($np = $res_np->fetch_assoc()) {
            $nombres_proy[$np['proy_id']] = $np['proy_nombre'];
        }
    }
    if ($res_proy) {
        while ($pr = $res_proy->fetch_assoc()) {
            $pid = $pr['proy_id'];
            $out['proyectos'][] = array(
                'proy_id' => $pid,
                'proy_nombre' => isset($nombres_proy[$pid]) ? $nombres_proy[$pid] : $pid,
                'vigente' => round((float)$pr['vigente'], 2),
                'ejecutado' => round((float)$pr['ejecutado'], 2),
                'disponible' => round((float)$pr['disponible'], 2),
            );
            $out['total_vigente'] += round((float)$pr['vigente'], 2);
            $out['total_ejecutado'] += round((float)$pr['ejecutado'], 2);
            $out['total_disponible'] += round((float)$pr['disponible'], 2);
        }
    }
    $out['total_vigente'] = round($out['estandar_vigente'] + $out['total_vigente'], 2);
    $out['total_ejecutado'] = round($out['estandar_ejecutado'] + $out['total_ejecutado'], 2);
    $out['total_disponible'] = round($out['estandar_disponible'] + $out['total_disponible'], 2);

    return $out;
}
