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
            $Emp_Cod = $mysqli->real_escape_string($p['Emp_Cod']);
            $ani = $mysqli->real_escape_string(isset($p['ppe_anio']) ? $p['ppe_anio'] : date('Y'));
            $sql = "SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = '$Emp_Cod' AND Ppe_Ani = '$ani' AND Ppe_Est = 'A' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['ppe_id'];
            }
            return null;

        case 2:
            // Obtener las reglas de asignaci�n autom�ticas activas para un tipo de documento y empresa.
            $Emp_Cod = $mysqli->real_escape_string($p['Emp_Cod']);
            $tip_doc = $mysqli->real_escape_string($p['prg_tipo_documento']);
            $sql = "SELECT * FROM pre_reglas WHERE Emp_Cod = '$Emp_Cod' AND Prg_TipDoc = '$tip_doc' AND Prg_Est = 'A' ORDER BY Prg_Pri ASC";
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
            $sql = "SELECT Pej_Cod AS pej_id FROM pre_ejecucion WHERE Pej_TipDoc = '$tip_doc' AND Pej_DocCod = '$doc_cod' AND Pej_Sig = '$sig' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['pej_id'];
            }
            return null;

        case 4:
            // Insertar una nueva transacci�n en el ledger de ejecuci�n presupuestaria.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $Emp_Cod = (int)$p['Emp_Cod'];
            $Suc_Cod = (isset($p['Suc_Cod']) && $p['Suc_Cod'] !== '' && $p['Suc_Cod'] !== null) ? (int)$p['Suc_Cod'] : "NULL";
            $Dep_Cod = (isset($p['Dep_Cod']) && $p['Dep_Cod'] !== '' && $p['Dep_Cod'] !== null) ? (int)$p['Dep_Cod'] : "NULL";
            $proy_id = (isset($p['proy_id']) && $p['proy_id'] !== '' && $p['proy_id'] !== null) ? "'" . $mysqli->real_escape_string($p['proy_id']) . "'" : "NULL";
            $pej_mes = (int)$p['pej_mes'];
            $pej_anio = (int)$p['pej_anio'];
            $pej_tip_doc = $mysqli->real_escape_string($p['pej_tipo_documento']);
            $pej_doc_cod = $mysqli->real_escape_string($p['pej_documento_codigo']);
            $pej_mon = (float)$p['pej_monto'];
            $pej_sig = $mysqli->real_escape_string($p['pej_signo']);
            $pej_fec = $mysqli->real_escape_string($p['pej_fecha_documento']);
            $Usu_Cod = (int)$p['Usu_Cod'];
            $prg_id = (isset($p['prg_id']) && $p['prg_id'] !== '' && $p['prg_id'] !== null) ? (int)$p['prg_id'] : "NULL";
            $pej_fase = (isset($p['pej_fase']) && $p['pej_fase'] !== '') ? "'" . $mysqli->real_escape_string($p['pej_fase']) . "'" : "'E'";
            $pej_rubro = (isset($p['pej_rubro']) && $p['pej_rubro'] !== '' && $p['pej_rubro'] !== null) ? "'" . $mysqli->real_escape_string($p['pej_rubro']) . "'" : "NULL";

            $sql = "INSERT INTO pre_ejecucion (
                        Ppe_Cod, Ppa_Cod, Emp_Cod, Suc_Cod, Dep_Cod, Pro_Cod, 
                        Pej_Mes, Pej_Ani, Pej_TipDoc, Pej_DocCod, Pej_Mon, Pej_Sig, 
                        Pej_Fec, Pej_FecReg, Usu_Cod, Prg_Cod, Pej_Fase, Pej_Rubro
                    ) VALUES (
                        $ppe_id, $ppa_id, $Emp_Cod, $Suc_Cod, $Dep_Cod, $proy_id,
                        $pej_mes, $pej_anio, '$pej_tip_doc', '$pej_doc_cod', $pej_mon, '$pej_sig',
                        '$pej_fec', NOW(), $Usu_Cod, $prg_id, $pej_fase, $pej_rubro
                    )";
            $res = $mysqli->query($sql);
            return $res ? $mysqli->insert_id : false;

        case 5:
            // Calcular el total presupuestado acumulado mensual de una partida.
            $ppe_id = (int)$p['ppe_id'];
            $ppa_id = (int)$p['ppa_id'];
            $mes = isset($p['mes']) ? (int)$p['mes'] : (isset($p['pde_mes']) ? (int)$p['pde_mes'] : 12);
            $sql = "SELECT SUM(Pde_Mon) AS Total FROM pre_detalle WHERE Ppe_Cod = $ppe_id AND Ppa_Cod = $ppa_id AND Pde_Mes <= $mes";
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
            $sql = "SELECT SUM(CASE WHEN Pej_Sig = '+' THEN Pej_Mon ELSE -Pej_Mon END) AS Total 
                    FROM pre_ejecucion 
                    WHERE Ppe_Cod = $ppe_id 
                      AND Ppa_Cod = $ppa_id 
                      AND Pej_Ani = $ani 
                      AND Pej_Mes <= $mes";
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
            $Usu_Cod = isset($p['Usu_Cod']) ? (int)$p['Usu_Cod'] : "NULL";
            $sql = "INSERT IGNORE INTO pre_alertas (Ppe_Cod, Ppa_Cod, Pal_Umb, Pal_PorAct, Pej_Cod, Pal_Lei, Pal_FecReg, Usu_Cod)
                    VALUES ($ppe_id, $ppa_id, $pal_umb, $pal_pct, $pej_id, 'N', NOW(), $Usu_Cod)";
            $res = $mysqli->query($sql);
            return $res ? true : false;

        case 8:
            // Generar el reporte matricial de balanza presupuestaria (Presupuestado/Proyectado, Ejecutado, Disponible, %).
            // pej_vista: anual|acumulado|mes (defecto acumulado/anual con mes<=N).
            // Presupuestado = plan mensual (exa_ppto_detalles) + proyectado publicado de proyectos (Relaves).
            $Emp_Cod = (int)$p['Emp_Cod'];
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
                $where_pp_proy = "pd.ppe_id = $ppe_id_f AND pd.Emp_Cod = $Emp_Cod";
            } else {
                $where_pp_det = "pp.Emp_Cod = $Emp_Cod AND pp.ppe_anio = $ani AND pp.ppe_estado = 'A'";
                $where_pp_ej = "pp.Emp_Cod = $Emp_Cod AND pp.ppe_anio = $ani AND pp.ppe_estado = 'A'";
                $where_pp_proy = "pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod IN (
                    SELECT Ppe_Cod FROM pre_presupuesto
                    WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $ani AND Ppe_Est = 'A'
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
                    FROM pre_partidas p
                    LEFT JOIN (
                        SELECT pd.Ppa_Cod AS ppa_id, SUM(pd.Pde_Mon) AS Presupuestado
                        FROM pre_detalle pd
                        INNER JOIN pre_presupuesto pp ON pd.Ppe_Cod = pp.Ppe_Cod
                        INNER JOIN pre_partidas px ON px.Ppa_Cod = pd.Ppa_Cod
                            AND px.Emp_Cod = $Emp_Cod AND px.Ppa_Est = 'A'
                        WHERE $where_pp_det
                          AND $where_mes_det
                        GROUP BY pd.Ppa_Cod
                    ) d ON p.Ppa_Cod = d.ppa_id
                    LEFT JOIN (
                        SELECT pd.Ppa_Cod AS ppa_id, SUM(pdm.Pdm_PreMensual) AS Proyectado
                        FROM pre_proyecto_detalles pd
                        INNER JOIN pre_proyecto_detalles_mes pdm ON pd.Pdp_Cod = pdm.Pdp_Cod
                        WHERE $where_pp_proy
                          AND pd.Pro_Cod IS NOT NULL
                          AND $where_mes_proy
                        GROUP BY pd.Ppa_Cod
                    ) pr ON p.Ppa_Cod = pr.ppa_id
                    LEFT JOIN (
                        SELECT pe.Ppa_Cod AS ppa_id, SUM(CASE WHEN pe.Pej_Sig = '+' THEN pe.Pej_Mon ELSE -pe.Pej_Mon END) AS Ejecutado
                        FROM pre_ejecucion pe
                        INNER JOIN pre_presupuesto pp ON pe.Ppe_Cod = pp.Ppe_Cod
                        INNER JOIN pre_partidas px ON px.Ppa_Cod = pe.Ppa_Cod
                            AND px.Emp_Cod = $Emp_Cod AND px.Ppa_Est = 'A'
                        WHERE $where_pp_ej
                          AND $where_mes_ej
                        GROUP BY pe.Ppa_Cod
                    ) e ON p.Ppa_Cod = e.ppa_id
                    WHERE p.Emp_Cod = $Emp_Cod 
                      AND p.Ppa_Est = 'A'
                    ORDER BY p.Ppa_Cla ASC";
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
            $Emp_Cod = (int)$p['Emp_Cod'];
            $ppe_id = (int)$p['ppe_id'];
            $sql = "SELECT a.*, p.Ppa_Cla AS ppa_codigo_clasificacion, p.Ppa_Des AS ppa_descripcion, p.Ppa_Tip AS ppa_tipo, p.Ppa_Nat AS ppa_naturaleza 
                    FROM pre_alertas a
                    INNER JOIN pre_partidas p ON a.Ppa_Cod = p.Ppa_Cod
                    WHERE a.Ppe_Cod = $ppe_id 
                      AND p.Emp_Cod = $Emp_Cod 
                      AND a.Pal_Lei = 'N'
                    ORDER BY a.Pal_FecReg DESC";
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
            $opts = array('Emp_Cod' => (int)$p['Emp_Cod']);
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
                    LEFT JOIN pre_detalle d ON m.Mes = d.Pde_Mes AND d.Ppe_Cod = $ppe_id AND d.Ppa_Cod = $ppa_id
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
            $sql = "SELECT * FROM pre_ejecucion 
                    WHERE Ppe_Cod = $ppe_id 
                      AND Ppa_Cod = $ppa_id 
                      $mes_cond
                    ORDER BY Pej_Fec DESC, Pej_FecReg DESC";
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
 * @param int $Emp_Cod
 * @param int $ppe_id
 * @param int $mes_hasta Mes acumulado (1-12)
 * @return array
 */
function ppto_admin_metricas_consolidado($mysqli, $Emp_Cod, $ppe_id, $mes_hasta, $vista = 'acumulado') {
    $out = array(
        'estandar_vigente' => 0.0,
        'estandar_ejecutado' => 0.0,
        'estandar_disponible' => 0.0,
        'total_vigente' => 0.0,
        'total_ejecutado' => 0.0,
        'total_disponible' => 0.0,
        'proyectos' => array(),
    );

    if (!$mysqli || $Emp_Cod <= 0 || $ppe_id <= 0) {
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
    $join_px = "INNER JOIN pre_partidas px ON (px.Ppa_Cod = r.ppa_id OR px.Ppa_Cod = r.Ppa_Cod)
        AND px.Ppa_Est = 'A'
        AND COALESCE(NULLIF(px.Ppa_Clase, ''), 'D') = 'D'";

    if ($vista === 'mes') {
        $where_mes = "r.mes = $mes_hasta";
    } else {
        $where_mes = "r.mes <= $mes_hasta";
    }
    $base_where = "r.Emp_Cod = $Emp_Cod AND r.ppe_id = $ppe_id AND $where_mes";

    $sql_std = "SELECT
            COALESCE(SUM(r.vigente), 0) AS vigente,
            COALESCE(SUM(r.ejecutado), 0) AS ejecutado,
            COALESCE(SUM(r.disponible), 0) AS disponible
        FROM (" . ppto_sql_resumen_subquery() . ") r
        $join_px
        WHERE $base_where
          AND (r.proy_id IS NULL OR r.proy_id = '')
          AND r.ppa_id NOT IN (
              SELECT DISTINCT pd.Ppa_Cod
              FROM pre_proyecto_detalles pd
              WHERE pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod = $ppe_id
                AND pd.Pro_Cod IS NOT NULL
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
        FROM (" . ppto_sql_resumen_subquery() . ") r
        $join_px
        WHERE $base_where
          AND r.proy_id IS NOT NULL AND r.proy_id != ''
        GROUP BY r.proy_id
        HAVING vigente > 0.009 OR ejecutado > 0.009
        ORDER BY r.proy_id ASC";
    $res_proy = $mysqli->query($sql_proy);
    $nombres_proy = array();
    $res_np = $mysqli->query("SELECT Pro_Cod AS proy_id, Pro_Nom AS proy_nombre FROM pre_proyectos
        WHERE Emp_Cod = $Emp_Cod AND Pro_Est = 'A'");
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
