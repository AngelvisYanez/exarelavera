<?php
/**
 * ppto_persistencia_logica.php
 * Capa de Persistencia y Lï¿½gica de Datos para el Mï¿½dulo de Presupuestos (EXA PPTO).
 * centraliza todas las consultas SQL y operaciones CRUD del presupuesto.
 */

/**
 * Motor de persistencia unificado para EXA PPTO.
 *
 * @param mysqli $mysqli Objeto de conexiï¿½n a la base de datos.
 * @param int $caso Identificador del caso o consulta SQL a ejecutar.
 * @param array $p Parï¿½metros requeridos para la consulta estructurados en un arreglo asociativo.
 * @return mixed Retorna ID insertado, valor escalar, array de resultados o null/false segï¿½n el caso.
 */
function ppto_persistencia_consultar($mysqli, $caso, $p) {
    switch ($caso) {
        case 1:
            // Obtener el ID de la cabecera del presupuesto activo para una empresa y aï¿½o especï¿½ficos.
            $Emp_Cod = $mysqli->real_escape_string($p['Emp_Cod']);
            $ani = $mysqli->real_escape_string(isset($p['Ppe_Ani']) ? $p['Ppe_Ani'] : date('Y'));
            $sql = "SELECT Ppe_Cod AS Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod = '$Emp_Cod' AND Ppe_Ani = '$ani' AND Ppe_Est = 'A' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['Ppe_Cod'];
            }
            return null;

        case 2:
            // Obtener las reglas de asignaciï¿½n automï¿½ticas activas para un tipo de documento y empresa.
            $Emp_Cod = $mysqli->real_escape_string($p['Emp_Cod']);
            $tip_doc = $mysqli->real_escape_string($p['Prg_TipDoc']);
            $sql = "SELECT Prg_Cod, Prg_Cod AS prg_id, Emp_Cod, Ppa_Cod, Ppa_Cod AS ppa_id,
                        Prg_TipDoc, Prg_TipDoc AS prg_tipo_documento,
                        Prg_Campo, Prg_Campo AS prg_campo_evaluacion,
                        Prg_Valor, Prg_Valor AS prg_valor_esperado,
                        Prg_Signo, Prg_Signo AS prg_signo,
                        Prg_CamMon, Prg_CamMon AS prg_campo_monto,
                        Prg_Pri, Prg_Pri AS prg_prioridad,
                        Prg_Est, Prg_Est AS prg_estado,
                        Prg_Des, Prg_Des AS prg_descripcion,
                        Usu_Cod, Prg_Fec
                    FROM pre_reglas
                    WHERE Emp_Cod = '$Emp_Cod' AND Prg_TipDoc = '$tip_doc' AND Prg_Est = 'A'
                    ORDER BY Prg_Pri ASC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            return $data;

        case 3:
            // Verificar si ya existe un registro de ejecuciï¿½n idï¿½ntico (evitar duplicados transaccionales).
            $tip_doc = $mysqli->real_escape_string($p['Pej_TipDoc']);
            $doc_cod = $mysqli->real_escape_string($p['Pej_DocCod']);
            $sig = $mysqli->real_escape_string($p['Pej_Sig']);
            $sql = "SELECT Pej_Cod AS Pej_Cod FROM pre_ejecucion WHERE Pej_TipDoc = '$tip_doc' AND Pej_DocCod = '$doc_cod' AND Pej_Sig = '$sig' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return (int)$row['Pej_Cod'];
            }
            return null;

        case 4:
            // Insertar una nueva transacciï¿½n en el ledger de ejecuciï¿½n presupuestaria.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $Emp_Cod = (int)$p['Emp_Cod'];
            $Suc_Cod = (isset($p['Suc_Cod']) && $p['Suc_Cod'] !== '' && $p['Suc_Cod'] !== null) ? (int)$p['Suc_Cod'] : "NULL";
            $Dep_Cod = (isset($p['Dep_Cod']) && $p['Dep_Cod'] !== '' && $p['Dep_Cod'] !== null) ? (int)$p['Dep_Cod'] : "NULL";
            $Pro_Cod = (isset($p['Pro_Cod']) && $p['Pro_Cod'] !== '' && $p['Pro_Cod'] !== null) ? "'" . $mysqli->real_escape_string($p['Pro_Cod']) . "'" : "NULL";
            $Pej_Mes = (int)$p['Pej_Mes'];
            $Pej_Ani = (int)$p['Pej_Ani'];
            $pej_tip_doc = $mysqli->real_escape_string($p['Pej_TipDoc']);
            $pej_doc_cod = $mysqli->real_escape_string($p['Pej_DocCod']);
            $Pej_Mon = (float)$p['Pej_Mon'];
            $Pej_Sig = $mysqli->real_escape_string($p['Pej_Sig']);
            $Pej_Fec = $mysqli->real_escape_string($p['Pej_Fec']);
            $Usu_Cod = (int)$p['Usu_Cod'];
            $Prg_Cod = (isset($p['Prg_Cod']) && $p['Prg_Cod'] !== '' && $p['Prg_Cod'] !== null) ? (int)$p['Prg_Cod'] : "NULL";
            $Pej_Fase = (isset($p['Pej_Fase']) && $p['Pej_Fase'] !== '') ? "'" . $mysqli->real_escape_string($p['Pej_Fase']) . "'" : "'E'";
            $Pej_Rubro = (isset($p['Pej_Rubro']) && $p['Pej_Rubro'] !== '' && $p['Pej_Rubro'] !== null) ? "'" . $mysqli->real_escape_string($p['Pej_Rubro']) . "'" : "NULL";

            $sql = "INSERT INTO pre_ejecucion (
                        Ppe_Cod, Ppa_Cod, Emp_Cod, Suc_Cod, Dep_Cod, Pro_Cod, 
                        Pej_Mes, Pej_Ani, Pej_TipDoc, Pej_DocCod, Pej_Mon, Pej_Sig, 
                        Pej_Fec, Pej_FecReg, Usu_Cod, Prg_Cod, Pej_Fase, Pej_Rubro
                    ) VALUES (
                        $Ppe_Cod, $Ppa_Cod, $Emp_Cod, $Suc_Cod, $Dep_Cod, $Pro_Cod,
                        $Pej_Mes, $Pej_Ani, '$pej_tip_doc', '$pej_doc_cod', $Pej_Mon, '$Pej_Sig',
                        '$Pej_Fec', NOW(), $Usu_Cod, $Prg_Cod, $Pej_Fase, $Pej_Rubro
                    )";
            $res = $mysqli->query($sql);
            return $res ? $mysqli->insert_id : false;

        case 5:
            // Calcular el total presupuestado acumulado mensual de una partida.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $mes = isset($p['mes']) ? (int)$p['mes'] : (isset($p['Pde_Mes']) ? (int)$p['Pde_Mes'] : 12);
            $sql = "SELECT SUM(Pde_Mon) AS Total FROM pre_detalle WHERE Ppe_Cod = $Ppe_Cod AND Ppa_Cod = $Ppa_Cod AND Pde_Mes <= $mes";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return $row['Total'] !== null ? (float)$row['Total'] : 0.0;
            }
            return 0.0;

        case 6:
            // Calcular la ejecuciï¿½n acumulada de una partida hasta un mes del aï¿½o activo.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $ani = isset($p['Pej_Ani']) ? (int)$p['Pej_Ani'] : date('Y');
            $mes = isset($p['Pej_Mes']) ? (int)$p['Pej_Mes'] : 12;
            $sql = "SELECT SUM(CASE WHEN Pej_Sig = '+' THEN Pej_Mon ELSE -Pej_Mon END) AS Total 
                    FROM pre_ejecucion 
                    WHERE Ppe_Cod = $Ppe_Cod 
                      AND Ppa_Cod = $Ppa_Cod 
                      AND Pej_Ani = $ani 
                      AND Pej_Mes <= $mes";
            $res = $mysqli->query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                return $row['Total'] !== null ? (float)$row['Total'] : 0.0;
            }
            return 0.0;

        case 7:
            // Registrar una alerta presupuestaria por desvï¿½o/superaciï¿½n de umbrales.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $pal_umb = (int)$p['pal_umbral'];
            $pal_pct = (float)$p['pal_porcentaje_actual'];
            $Pej_Cod = (int)$p['Pej_Cod'];
            $Usu_Cod = isset($p['Usu_Cod']) ? (int)$p['Usu_Cod'] : "NULL";
            $sql = "INSERT IGNORE INTO pre_alertas (Ppe_Cod, Ppa_Cod, Pal_Umb, Pal_PorAct, Pej_Cod, Pal_Lei, Pal_FecReg, Usu_Cod)
                    VALUES ($Ppe_Cod, $Ppa_Cod, $pal_umb, $pal_pct, $Pej_Cod, 'N', NOW(), $Usu_Cod)";
            $res = $mysqli->query($sql);
            return $res ? true : false;

        case 8:
            // Generar el reporte matricial de balanza presupuestaria (Presupuestado/Proyectado, Ejecutado, Disponible, %).
            // pej_vista: anual|acumulado|mes (defecto acumulado/anual con mes<=N).
            // Presupuestado = plan mensual (pre_detalle) + proyectado publicado de proyectos (Relaves).
            $Emp_Cod = (int)$p['Emp_Cod'];
            $ani = isset($p['Ppe_Ani']) ? (int)$p['Ppe_Ani'] : (int)date('Y');
            $mes = isset($p['Pej_Mes']) ? (int)$p['Pej_Mes'] : 12;
            $mes = max(1, min(12, $mes));
            $vista = isset($p['pej_vista']) ? strtolower(trim((string)$p['pej_vista'])) : 'acumulado';
            if (!in_array($vista, array('anual', 'acumulado', 'mes'), true)) {
                $vista = 'acumulado';
            }
            if ($vista === 'anual') {
                $mes = 12;
            }
            $ppe_id_f = isset($p['Ppe_Cod']) ? (int)$p['Ppe_Cod'] : 0;

            if ($ppe_id_f > 0) {
                $where_pp_det = "pp.Ppe_Cod = $ppe_id_f";
                $where_pp_ej = "pp.Ppe_Cod = $ppe_id_f";
                $where_pp_proy = "pd.Ppe_Cod = $ppe_id_f AND pd.Emp_Cod = $Emp_Cod";
            } else {
                $where_pp_det = "pp.Emp_Cod = $Emp_Cod AND pp.Ppe_Ani = $ani AND pp.Ppe_Est = 'A'";
                $where_pp_ej = "pp.Emp_Cod = $Emp_Cod AND pp.Ppe_Ani = $ani AND pp.Ppe_Est = 'A'";
                $where_pp_proy = "pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod IN (
                    SELECT Ppe_Cod FROM pre_presupuesto
                    WHERE Emp_Cod = $Emp_Cod AND Ppe_Ani = $ani AND Ppe_Est = 'A'
                )";
            }

            if ($vista === 'mes') {
                $where_mes_det = "pd.Pde_Mes = $mes";
                $where_mes_ej = "pe.Pej_Mes = $mes";
                $where_mes_proy = "pdm.Pdm_Mes = $mes";
            } else {
                $where_mes_det = "pd.Pde_Mes <= $mes";
                $where_mes_ej = "pe.Pej_Mes <= $mes";
                $where_mes_proy = "pdm.Pdm_Mes <= $mes";
            }

            $sql = "SELECT 
                        p.Ppa_Cod AS Ppa_Cod, p.Ppa_Cod AS Ppa_Cod,
                        p.Ppa_Cla AS Ppa_Cla, p.Ppa_Cla AS ppa_codigo_clasificacion,
                        p.Ppa_Des AS Ppa_Des, p.Ppa_Des AS ppa_descripcion,
                        p.Ppa_Tip AS Ppa_Tip, p.Ppa_Tip AS ppa_tipo,
                        p.Ppa_Nat AS Ppa_Nat, p.Ppa_Nat AS ppa_naturaleza,
                        COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS Ppa_Clase, COALESCE(NULLIF(p.Ppa_Clase, ''), 'D') AS ppa_clase,
                        p.Ppa_Niv AS Ppa_Niv, p.Ppa_Niv AS ppa_nivel,
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
                        SELECT pd.Ppa_Cod AS Ppa_Cod, SUM(pd.Pde_Mon) AS Presupuestado
                        FROM pre_detalle pd
                        INNER JOIN pre_presupuesto pp ON pd.Ppe_Cod = pp.Ppe_Cod
                        INNER JOIN pre_partidas px ON px.Ppa_Cod = pd.Ppa_Cod
                            AND px.Emp_Cod = $Emp_Cod AND px.Ppa_Est = 'A'
                        WHERE $where_pp_det
                          AND $where_mes_det
                        GROUP BY pd.Ppa_Cod
                    ) d ON p.Ppa_Cod = d.Ppa_Cod
                    LEFT JOIN (
                        SELECT pd.Ppa_Cod AS Ppa_Cod, SUM(pdm.Pdm_PreMensual) AS Proyectado
                        FROM pre_proyecto_detalles pd
                        INNER JOIN pre_proyecto_detalles_mes pdm ON pd.Pdp_Cod = pdm.Pdp_Cod
                        WHERE $where_pp_proy
                          AND pd.Pro_Cod IS NOT NULL
                          AND $where_mes_proy
                        GROUP BY pd.Ppa_Cod
                    ) pr ON p.Ppa_Cod = pr.Ppa_Cod
                    LEFT JOIN (
                        SELECT pe.Ppa_Cod AS Ppa_Cod, SUM(CASE WHEN pe.Pej_Sig = '+' THEN pe.Pej_Mon ELSE -pe.Pej_Mon END) AS Ejecutado
                        FROM pre_ejecucion pe
                        INNER JOIN pre_presupuesto pp ON pe.Ppe_Cod = pp.Ppe_Cod
                        INNER JOIN pre_partidas px ON px.Ppa_Cod = pe.Ppa_Cod
                            AND px.Emp_Cod = $Emp_Cod AND px.Ppa_Est = 'A'
                        WHERE $where_pp_ej
                          AND $where_mes_ej
                        GROUP BY pe.Ppa_Cod
                    ) e ON p.Ppa_Cod = e.Ppa_Cod
                    WHERE p.Emp_Cod = $Emp_Cod 
                      AND p.Ppa_Est = 'A'
                    ORDER BY p.Ppa_Cla ASC";
            $res = $mysqli->query($sql);
            $data = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $row['Ppa_Cod'] = $row['Ppa_Cod'];
                    $row['ppa_codigo_clasificacion'] = $row['Ppa_Cla'];
                    $row['ppa_descripcion'] = $row['Ppa_Des'];
                    $row['ppa_tipo'] = $row['Ppa_Tip'];
                    $row['ppa_naturaleza'] = $row['Ppa_Nat'];
                    $row['ppa_clase'] = $row['Ppa_Clase'];
                    $row['ppa_nivel'] = $row['Ppa_Niv'];
                    $data[] = $row;
                }
            }
            return $data;

        case 9:
            // Obtener alertas presupuestarias activas (no leï¿½das) de la versiï¿½n activa de presupuesto.
            $Emp_Cod = (int)$p['Emp_Cod'];
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $sql = "SELECT a.*, p.Ppa_Cla AS Ppa_Cla, p.Ppa_Des AS Ppa_Des, p.Ppa_Tip AS Ppa_Tip, p.Ppa_Nat AS Ppa_Nat 
                    FROM pre_alertas a
                    INNER JOIN pre_partidas p ON a.Ppa_Cod = p.Ppa_Cod
                    WHERE a.Ppe_Cod = $Ppe_Cod 
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
            // Consultar la distribuciï¿½n nominal por cada uno de los 12 meses de una partida especï¿½fica.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $sql = "SELECT m.Mes AS Pde_Mes, IFNULL(d.Pde_Mon, 0.00) AS Pde_Mon, d.Pde_Cod
                    FROM (
                        SELECT 1 AS Mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 
                        UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
                    ) m
                    LEFT JOIN pre_detalle d ON m.Mes = d.Pde_Mes AND d.Ppe_Cod = $Ppe_Cod AND d.Ppa_Cod = $Ppa_Cod
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
            // Consultar el desglose de documentos que componen la ejecuciï¿½n real de una partida en particular.
            $Ppe_Cod = (int)$p['Ppe_Cod'];
            $Ppa_Cod = (int)$p['Ppa_Cod'];
            $mes_cond = "";
            if (isset($p['mes']) && $p['mes'] !== '' && $p['mes'] !== null) {
                $mes = (int)$p['mes'];
                $mes_cond = " AND Pej_Mes = $mes ";
            } elseif (isset($p['Pej_Mes']) && $p['Pej_Mes'] !== '' && $p['Pej_Mes'] !== null) {
                $mes = (int)$p['Pej_Mes'];
                $mes_cond = " AND Pej_Mes = $mes ";
            }
            $sql = "SELECT * FROM pre_ejecucion 
                    WHERE Ppe_Cod = $Ppe_Cod 
                      AND Ppa_Cod = $Ppa_Cod 
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
 * @param int $Ppe_Cod
 * @param int $mes_hasta Mes acumulado (1-12)
 * @return array
 */
function ppto_admin_metricas_consolidado($mysqli, $Emp_Cod, $Ppe_Cod, $mes_hasta, $vista = 'acumulado') {
    $out = array(
        'estandar_vigente' => 0.0,
        'estandar_ejecutado' => 0.0,
        'estandar_disponible' => 0.0,
        'total_vigente' => 0.0,
        'total_ejecutado' => 0.0,
        'total_disponible' => 0.0,
        'proyectos' => array(),
    );

    if (!$mysqli || $Emp_Cod <= 0 || $Ppe_Cod <= 0) {
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
    $join_px = "INNER JOIN pre_partidas px ON (px.Ppa_Cod = r.Ppa_Cod OR px.Ppa_Cod = r.Ppa_Cod)
        AND px.Ppa_Est = 'A'
        AND COALESCE(NULLIF(px.Ppa_Clase, ''), 'D') = 'D'";

    if ($vista === 'mes') {
        $where_mes = "r.mes = $mes_hasta";
    } else {
        $where_mes = "r.mes <= $mes_hasta";
    }
    $base_where = "r.Emp_Cod = $Emp_Cod AND r.Ppe_Cod = $Ppe_Cod AND $where_mes";

    $sql_std = "SELECT
            COALESCE(SUM(r.vigente), 0) AS vigente,
            COALESCE(SUM(r.ejecutado), 0) AS ejecutado,
            COALESCE(SUM(r.disponible), 0) AS disponible
        FROM (" . ppto_sql_resumen_subquery() . ") r
        $join_px
        WHERE $base_where
          AND (r.Pro_Cod IS NULL OR r.Pro_Cod = '')
          AND r.Ppa_Cod NOT IN (
              SELECT DISTINCT pd.Ppa_Cod
              FROM pre_proyecto_detalles pd
              WHERE pd.Emp_Cod = $Emp_Cod AND pd.Ppe_Cod = $Ppe_Cod
                AND pd.Pro_Cod IS NOT NULL
          )";
    $res_std = $mysqli->query($sql_std);
    if ($res_std && ($row = $res_std->fetch_assoc())) {
        $out['estandar_vigente'] = round((float)$row['vigente'], 2);
        $out['estandar_ejecutado'] = round((float)$row['ejecutado'], 2);
        $out['estandar_disponible'] = round((float)$row['disponible'], 2);
    }

    $sql_proy = "SELECT r.Pro_Cod,
            COALESCE(SUM(r.vigente), 0) AS vigente,
            COALESCE(SUM(r.ejecutado), 0) AS ejecutado,
            COALESCE(SUM(r.disponible), 0) AS disponible
        FROM (" . ppto_sql_resumen_subquery() . ") r
        $join_px
        WHERE $base_where
          AND r.Pro_Cod IS NOT NULL AND r.Pro_Cod != ''
        GROUP BY r.Pro_Cod
        HAVING vigente > 0.009 OR ejecutado > 0.009
        ORDER BY r.Pro_Cod ASC";
    $res_proy = $mysqli->query($sql_proy);
    $nombres_proy = array();
    $res_np = $mysqli->query("SELECT Pro_Cod AS Pro_Cod, Pro_Nom AS Pro_Nom FROM pre_proyectos
        WHERE Emp_Cod = $Emp_Cod AND Pro_Est = 'A'");
    if ($res_np) {
        while ($np = $res_np->fetch_assoc()) {
            $nombres_proy[$np['Pro_Cod']] = $np['Pro_Nom'];
        }
    }
    if ($res_proy) {
        while ($pr = $res_proy->fetch_assoc()) {
            $pid = $pr['Pro_Cod'];
            $out['proyectos'][] = array(
                'Pro_Cod' => $pid,
                'Pro_Nom' => isset($nombres_proy[$pid]) ? $nombres_proy[$pid] : $pid,
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
