<?php
/**
 * Dashboard Operativo RELAVERA - Consultas SQL
 * Flujo: Anticipo → Turno → Manifiesto → Facturación Mensual
 * @author Sistema EXA
 * @version 1.0
 * Compatible PHP 5.3.8
 */

function sentencias_dashboard_relavera($id, $Par_Sql) {
    $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
    $Fec_Ini = isset($Par_Sql['Fec_Ini']) ? addslashes($Par_Sql['Fec_Ini']) : date('Y-m-d');
    $Fec_Fin = isset($Par_Sql['Fec_Fin']) ? addslashes($Par_Sql['Fec_Fin']) : date('Y-m-d');
    $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? intval($Par_Sql['Pla_Cod']) : 0;
    $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? intval($Par_Sql['Cli_Cod']) : 0;
    $Man_Tip = isset($Par_Sql['Man_Tip']) ? addslashes($Par_Sql['Man_Tip']) : '';

    $where_emp = " AND cliente.Emp_Cod = $Emp_Cod";
    $where_pla = ($Pla_Cod > 0) ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "";
    $where_cli = ($Cli_Cod > 0) ? " AND manifiesto.Cli_Cod = $Cli_Cod" : "";
    $where_man_tip = ($Man_Tip !== '') ? " AND manifiesto.Man_Tip = '$Man_Tip'" : "";
    $where_fec = " AND manifiesto.Man_Fec BETWEEN '$Fec_Ini 00:00:00' AND '$Fec_Fin 23:59:59'";
    $where_pla_ma = ($Pla_Cod > 0) ? " AND ma.Pla_Cod = $Pla_Cod" : "";
    $where_cli_ma = ($Cli_Cod > 0) ? " AND ma.Cli_Cod = $Cli_Cod" : "";
    $where_fec_ma = " AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";

    switch ($id) {
        /* 1. Anticipo Total Activo - SUM de anticipos vigentes (manifiesto_anticipo Ama_Val) */
        case 1:
            $sql = "SELECT COALESCE(SUM(ma.Ama_Val), 0) as anticipo_total
                    FROM manifiesto_anticipo ma
                    INNER JOIN cliente ON ma.Cli_Cod = cliente.Cli_Cod
                    WHERE ma.Ama_Est = 'A' $where_emp $where_pla_ma $where_cli_ma";
            return $sql;

        /* 2. Saldo Disponible Total - SUM(anticipo - consumido) por planta/cliente */
        case 2:
            $sql = "SELECT COALESCE(SUM(GREATEST(saldo_cli.saldo, 0)), 0) as saldo_disponible
                    FROM (
                        SELECT mp.Pla_Cod, mp.Cli_Cod,
                            (COALESCE((SELECT SUM(ma.Ama_Val) FROM manifiesto_anticipo ma 
                                WHERE ma.Pla_Cod = mp.Pla_Cod AND ma.Ama_Est = 'A'), 0)
                            - COALESCE((SELECT SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) FROM manifiesto m 
                                WHERE m.Pla_Cod = mp.Pla_Cod AND m.Man_Est = 'A' 
                                AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)), 0)) as saldo
                        FROM manifiesto_plantas mp
                        INNER JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
                        WHERE cliente.Emp_Cod = $Emp_Cod AND mp.Pla_Est = 'A'
                        " . ($Pla_Cod > 0 ? " AND mp.Pla_Cod = $Pla_Cod" : "") . "
                        " . ($Cli_Cod > 0 ? " AND mp.Cli_Cod = $Cli_Cod" : "") . "
                    ) saldo_cli";
            return $sql;

        /* 3. Clientes en Riesgo - saldo < 20% del anticipo total */
        case 3:
            $sql = "SELECT COUNT(*) as cantidad
                    FROM (
                        SELECT mp.Cli_Cod, mp.Pla_Cod,
                            COALESCE((SELECT SUM(ma.Ama_Val) FROM manifiesto_anticipo ma 
                                WHERE ma.Pla_Cod = mp.Pla_Cod AND ma.Ama_Est = 'A'), 0) as anticipo,
                            COALESCE((SELECT SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) FROM manifiesto m 
                                WHERE m.Pla_Cod = mp.Pla_Cod AND m.Man_Est = 'A' 
                                AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)), 0) as consumido
                        FROM manifiesto_plantas mp
                        INNER JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
                        WHERE cliente.Emp_Cod = $Emp_Cod AND mp.Pla_Est = 'A'
                        " . ($Pla_Cod > 0 ? " AND mp.Pla_Cod = $Pla_Cod" : "") . "
                    ) t
                    WHERE t.anticipo > 0 AND ((t.anticipo - t.consumido) / t.anticipo) < 0.20";
            return $sql;

        /* 4. Turnos Generados - COUNT turnos en rango de fechas (Tud_Fec) */
        case 4:
            $sql = "SELECT COUNT(DISTINCT manifiesto.Man_Cod) as turnos_hoy
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    AND manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    $where_pla $where_cli";
            return $sql;

        /* 5. Manifiestos en Proceso - GE, A, GS (Garita IN, En Planta, Aprobado, Garita OUT) */
        case 5:
            $sql = "SELECT COUNT(*) as en_proceso
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip IN ('GE', 'A', 'GS')
                    $where_emp $where_pla $where_cli $where_fec";
            return $sql;

        /* 6. Manifiestos Pendientes de Facturación - GS (Aprobado pero no facturado) */
        case 6:
            $sql = "SELECT COUNT(*) as pendientes_fact
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip = 'GS'
                    AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)
                    $where_emp $where_pla $where_cli $where_fec";
            return $sql;

        /* 6b. Turnos Anulados Hoy - manifiestos con Man_Est = 'I' en el período */
        case 13:
            $sql = "SELECT COUNT(*) as turnos_anulados
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'I'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    $where_emp $where_pla $where_cli $where_fec";
            return $sql;

        /* 6c. Turnos Pendientes - manifiestos sin Garita IN (Man_Tip = 'P'), rango de fechas */
        case 14:
            $sql = "SELECT COUNT(*) as turnos_pendientes
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Tip = 'P'
                    AND manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    $where_pla $where_cli";
            return $sql;


        /* 6e. Total Turnos Hoy - activos + inactivos (Man_Est A o I), solo día actual */
        case 17:
            $hoy = date('Y-m-d');
            $sql = "SELECT COUNT(DISTINCT manifiesto.Man_Cod) as total_turnos
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto_turnos_det.Tud_Fec = '$hoy'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    AND manifiesto.Man_Est IN ('A', 'I')
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    $where_pla $where_cli";
            return $sql;

        /* 6d. Aprobados (KPI) - manifiestos con Garita IN (Man_Tip GE,A,GS,F) para que Pendientes+Aprobados=Total
         * Usa Tud_Fec como Cases 4 y 14 para consistencia del rango de fechas */
        case 16:
            $sql = "SELECT COUNT(DISTINCT manifiesto.Man_Cod) as aprobados
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Tip IN ('GE', 'A', 'GS', 'F')
                    AND manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    $where_pla $where_cli";
            return $sql;

        /* 20. Aprobados por técnico (Man_Tes) - solo para Monitor Operativo día actual */
        case 20:
            $sql = "SELECT COUNT(*) as aprobados_tecnico
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND LOCATE('A', manifiesto.Man_Tes) > 0
                    AND manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    $where_pla $where_cli";
            return $sql;

        /* 21. Garita IN (Man_Tip GE) - para KPI Turnos Pendientes = Pendientes + Garita IN */
        case 21:
            $sql = "SELECT COUNT(DISTINCT manifiesto.Man_Cod) as garita_in
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Tip = 'GE'
                    AND manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    $where_pla $where_cli";
            return $sql;

        /* 22. Garita OUT (Man_Tip GS,F) - para KPI Aprobados = Garita OUT */
        case 22:
            $sql = "SELECT COUNT(DISTINCT manifiesto.Man_Cod) as garita_out
                    FROM manifiesto
                    INNER JOIN manifiesto_turnos_det ON manifiesto.Tud_Cod = manifiesto_turnos_det.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab ON manifiesto_turnos_det.Tur_Cod = manifiesto_turnos_cab.Tur_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Tip IN ('GS', 'F')
                    AND manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto_turnos_det.Tud_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                    AND manifiesto_turnos_cab.Emp_Cod = $Emp_Cod
                    AND manifiesto_turnos_cab.Tur_Est != 'I'
                    $where_pla $where_cli";
            return $sql;

        /* 18. Garita OUT en rango - manifiestos que salieron (Man_Fes) en el período */
        case 18:
            $sql = "SELECT COUNT(*) as garita_out
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip IN ('GS', 'F')
                    AND manifiesto.Man_Fes BETWEEN '$Fec_Ini 00:00:00' AND '$Fec_Fin 23:59:59'
                    $where_emp $where_pla $where_cli";
            return $sql;

        /* 19. Garita IN en rango - manifiestos que ingresaron (Man_Fea) en el período */
        case 19:
            $sql = "SELECT COUNT(*) as garita_in
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip IN ('GE', 'A', 'GS', 'F')
                    AND manifiesto.Man_Fea BETWEEN '$Fec_Ini 00:00:00' AND '$Fec_Fin 23:59:59'
                    $where_emp $where_pla $where_cli";
            return $sql;

        /* 7. Monitor Operativo - Cantidad por estado (GE, A, GS) con tiempo promedio
         * Corregido: tiempo siempre positivo. Si Man_Fes < Man_Fea o NULL, usar 0.
         * tiempo_promedio = fecha_fin - fecha_inicio, con ABS y validación NULL */
        case 7:
            $sql = "SELECT 
                        manifiesto.Man_Tip as estado,
                        COUNT(*) as cantidad,
                        COALESCE(AVG(
                            CASE 
                                WHEN manifiesto.Man_Fea IS NULL OR manifiesto.Man_Fes IS NULL THEN 0
                                WHEN manifiesto.Man_Fes < manifiesto.Man_Fea THEN 0
                                ELSE GREATEST(0, TIMESTAMPDIFF(MINUTE, manifiesto.Man_Fea, manifiesto.Man_Fes))
                            END
                        ), 0) as tiempo_prom_min
                    FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A'
                    AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip IN ('GE', 'A', 'GS')
                    $where_emp $where_pla $where_cli $where_fec
                    GROUP BY manifiesto.Man_Tip
                    ORDER BY FIELD(manifiesto.Man_Tip, 'GE', 'A', 'GS')";
            return $sql;

        /* 8. Proyección consumo anticipo - por cliente/planta: saldo, consumo diario, días estimados
         * Prom. Diario = AVG(egreso_dia) solo en días con egreso > 0 (agrupado por fecha)
         * Días estimados = saldo_actual / prom_diario (9999 si prom_diario=0)
         * Solo egresos (Man_Tip='F' consumo/factura), NO ingresos */
        case 8:
            $where_prom_pla = ($Pla_Cod > 0) ? " AND m.Pla_Cod = $Pla_Cod" : "";
            $where_prom_cli = ($Cli_Cod > 0) ? " AND m.Cli_Cod = $Cli_Cod" : "";
            $sql = "SELECT 
                        mp.Pla_Cod, mp.Cli_Cod,
                        CONCAT(COALESCE(p.Prs_Nom,''), ' ', COALESCE(p.Prs_Ape,'')) as cliente,
                        mp.Pla_Nom as planta,
                        COALESCE(ant.anticipo, 0) as anticipo,
                        COALESCE(cons.consumido, 0) as consumido,
                        (COALESCE(ant.anticipo, 0) - COALESCE(cons.consumido, 0)) as saldo_actual,
                        COALESCE(prom.promedio_diario, 0) as promedio_diario,
                        CASE WHEN COALESCE(prom.promedio_diario, 0) > 0 
                            THEN (COALESCE(ant.anticipo, 0) - COALESCE(cons.consumido, 0)) / prom.promedio_diario 
                            ELSE 9999 END as dias_estimados
                    FROM manifiesto_plantas mp
                    INNER JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona p ON cliente.Prs_Cod = p.Prs_Cod
                    LEFT JOIN (SELECT Pla_Cod, SUM(Ama_Val) as anticipo FROM manifiesto_anticipo 
                        WHERE Ama_Est = 'A' GROUP BY Pla_Cod) ant ON ant.Pla_Cod = mp.Pla_Cod
                    LEFT JOIN (SELECT Pla_Cod, SUM((Man_Pes/1000)*IFNULL(Man_Pun,0)) as consumido FROM manifiesto 
                        WHERE Man_Est = 'A' AND (Man_Tes IS NULL OR LOCATE('R', Man_Tes)=0) GROUP BY Pla_Cod) cons ON cons.Pla_Cod = mp.Pla_Cod
                    /* Prom. Diario = AVG(egreso por día) en últimos 90 días. Ej: $900 en 2 días = $450/día.
                     * Incluye TODO consumo (mismo criterio que cons), no solo facturado */
                    LEFT JOIN (
                        SELECT Pla_Cod, AVG(egreso_dia) as promedio_diario
                        FROM (
                            SELECT m.Pla_Cod,
                                DATE(COALESCE(m.Man_Fes, m.Man_Fea, m.Man_Fec)) as fecha_dia,
                                SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) as egreso_dia
                            FROM manifiesto m
                            INNER JOIN cliente c ON m.Cli_Cod = c.Cli_Cod
                            WHERE m.Man_Est = 'A'
                            AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)
                            AND COALESCE(m.Man_Fes, m.Man_Fea, m.Man_Fec) IS NOT NULL
                            AND COALESCE(m.Man_Fes, m.Man_Fea, m.Man_Fec) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                            AND COALESCE(m.Man_Fes, m.Man_Fea, m.Man_Fec) <= CURDATE()
                            AND c.Emp_Cod = $Emp_Cod
                            $where_prom_pla
                            $where_prom_cli
                            GROUP BY m.Pla_Cod, DATE(COALESCE(m.Man_Fes, m.Man_Fea, m.Man_Fec))
                            HAVING SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) > 0
                        ) dias_con_egreso
                        GROUP BY Pla_Cod
                    ) prom ON prom.Pla_Cod = mp.Pla_Cod
                    WHERE cliente.Emp_Cod = $Emp_Cod AND mp.Pla_Est = 'A'
                    AND (COALESCE(ant.anticipo, 0) - COALESCE(cons.consumido, 0)) > 0
                    " . ($Pla_Cod > 0 ? " AND mp.Pla_Cod = $Pla_Cod" : "") . "
                    " . ($Cli_Cod > 0 ? " AND mp.Cli_Cod = $Cli_Cod" : "") . "
                    ORDER BY dias_estimados ASC";
            return $sql;

        /* 9. Plantas para filtro */
        case 9:
            $sql = "SELECT mp.Pla_Cod, CONCAT(mp.Pla_Cod, ' - ', mp.Pla_Nom) as Pla_Nom, mp.Pla_Pfa
                    FROM manifiesto_plantas mp
                    LEFT JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
                    WHERE mp.Pla_Est = 'A'
                    AND (cliente.Emp_Cod = $Emp_Cod OR mp.Cli_Cod IS NULL)
                    ORDER BY mp.Pla_Nom";
            return $sql;

        /* 10. Clientes para filtro */
        case 10:
            $sql = "SELECT c.Cli_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Cliente
                    FROM cliente c
                    INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                    WHERE c.Emp_Cod = $Emp_Cod AND c.Cli_Est = 'A'
                    ORDER BY p.Prs_Nom, p.Prs_Ape";
            return $sql;

        /* 11. Alertas - Clientes sin saldo suficiente para turno */
        case 11:
            $sql = "SELECT mp.Cli_Cod, mp.Pla_Cod, mp.Pla_Nom,
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as cliente,
                        (COALESCE(ant.anticipo, 0) - COALESCE(cons.consumido, 0)) as saldo
                    FROM manifiesto_plantas mp
                    INNER JOIN cliente ON mp.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona p ON cliente.Prs_Cod = p.Prs_Cod
                    LEFT JOIN (SELECT Pla_Cod, SUM(Ama_Val) as anticipo FROM manifiesto_anticipo 
                        WHERE Ama_Est = 'A' GROUP BY Pla_Cod) ant ON ant.Pla_Cod = mp.Pla_Cod
                    LEFT JOIN (SELECT Pla_Cod, SUM((Man_Pes/1000)*IFNULL(Man_Pun,0)) as consumido FROM manifiesto 
                        WHERE Man_Est = 'A' AND (Man_Tes IS NULL OR LOCATE('R', Man_Tes)=0) GROUP BY Pla_Cod) cons ON cons.Pla_Cod = mp.Pla_Cod
                    WHERE cliente.Emp_Cod = $Emp_Cod AND mp.Pla_Est = 'A'
                    AND (COALESCE(ant.anticipo, 0) - COALESCE(cons.consumido, 0)) < 120";
            return $sql;

        /* 12. RESUMEN DEL PERÍODO - Anticipos por tipo, consumo, saldo final
         * Optimizado: una sola consulta con SUM + CASE WHEN
         * Anticipo Financiero: DEP, TRF, EFE (tipos_pago.Pag_Abr)
         * Anticipo Retenciones: RET
         * Consumo: manifiestos (Man_Pes/1000)*Man_Pun - siempre valor positivo
         * Saldo Final = saldo_inicial + anticipo_financiero + anticipo_retencion - consumo_facturado - consumo_pendiente */
        case 12:
            $where_pla_m = ($Pla_Cod > 0) ? " AND m.Pla_Cod = $Pla_Cod" : "";
            $where_cli_m = ($Cli_Cod > 0) ? " AND m.Cli_Cod = $Cli_Cod" : "";
            $where_fec_m = " AND m.Man_Fec BETWEEN '$Fec_Ini 00:00:00' AND '$Fec_Fin 23:59:59'";
            $where_fec_ma_12 = " AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
            $sql = "SELECT
                        /* Saldo inicial: anticipos antes del período - consumo antes del período (filtrado por planta/cliente) */
                        (COALESCE((SELECT SUM(ma.Ama_Val) FROM manifiesto_anticipo ma
                            INNER JOIN cliente c ON ma.Cli_Cod = c.Cli_Cod
                            WHERE ma.Ama_Est = 'A' AND ma.Ama_Fec < '$Fec_Ini'
                            AND c.Emp_Cod = $Emp_Cod $where_pla_ma $where_cli_ma), 0)
                        - COALESCE((SELECT SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) FROM manifiesto m
                            INNER JOIN cliente c ON m.Cli_Cod = c.Cli_Cod
                            WHERE m.Man_Est = 'A' AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)
                            AND m.Man_Fec < '$Fec_Ini 00:00:00' AND c.Emp_Cod = $Emp_Cod $where_pla_m $where_cli_m), 0)) as saldo_inicial,
                        /* Anticipo Financiero: DEP, TRF, EFE */
                        COALESCE((SELECT SUM(ma.Ama_Val) FROM manifiesto_anticipo ma
                            INNER JOIN cliente c ON ma.Cli_Cod = c.Cli_Cod
                            LEFT JOIN tipos_pago tp ON COALESCE(ma.Pag_Cod, ma.Ama_Tde) = tp.Pag_Cod
                            WHERE ma.Ama_Est = 'A' $where_fec_ma_12
                            AND c.Emp_Cod = $Emp_Cod $where_pla_ma $where_cli_ma
                            AND tp.Pag_Abr IN ('DEP','TRF','EFE')), 0) as anticipo_financiero,
                        /* Anticipo Retenciones: RET */
                        COALESCE((SELECT SUM(ma.Ama_Val) FROM manifiesto_anticipo ma
                            INNER JOIN cliente c ON ma.Cli_Cod = c.Cli_Cod
                            LEFT JOIN tipos_pago tp ON COALESCE(ma.Pag_Cod, ma.Ama_Tde) = tp.Pag_Cod
                            WHERE ma.Ama_Est = 'A' $where_fec_ma_12
                            AND c.Emp_Cod = $Emp_Cod $where_pla_ma $where_cli_ma
                            AND tp.Pag_Abr = 'RET'), 0) as anticipo_retencion,
                        /* Consumo Facturado: Man_Tip = F (siempre positivo) */
                        COALESCE((SELECT SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) FROM manifiesto m
                            INNER JOIN cliente c ON m.Cli_Cod = c.Cli_Cod
                            WHERE m.Man_Est = 'A' AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)
                            AND m.Man_Tip = 'F' AND c.Emp_Cod = $Emp_Cod $where_pla_m $where_cli_m $where_fec_m), 0) as consumo_facturado,
                        /* Consumo Pendiente: no facturados (Man_Tip != F) - siempre positivo */
                        COALESCE((SELECT SUM((m.Man_Pes/1000)*IFNULL(m.Man_Pun,0)) FROM manifiesto m
                            INNER JOIN cliente c ON m.Cli_Cod = c.Cli_Cod
                            WHERE m.Man_Est = 'A' AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)
                            AND (m.Man_Tip != 'F' OR m.Man_Tip IS NULL) AND (m.Vet_Cod IS NULL OR m.Vet_Cod = 0)
                            AND c.Emp_Cod = $Emp_Cod $where_pla_m $where_cli_m $where_fec_m), 0) as consumo_pendiente";
            return $sql;

        /* 24. Anticipos aprobados - SUM depósito, transferencia, efectivo (Ama_Tip='A') en el período */
        case 24:
            $sql = "SELECT COALESCE(SUM(ma.Ama_Val), 0) as anticipo_aprobado
                    FROM manifiesto_anticipo ma
                    INNER JOIN cliente ON ma.Cli_Cod = cliente.Cli_Cod
                    LEFT JOIN tipos_pago tp ON COALESCE(ma.Pag_Cod, ma.Ama_Tde) = tp.Pag_Cod
                    WHERE ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A' $where_fec_ma
                    AND cliente.Emp_Cod = $Emp_Cod $where_pla_ma $where_cli_ma
                    AND tp.Pag_Abr IN ('DEP','TRF','EFE')";
            return $sql;

        /* 25. Anticipos por aprobar - SUM depósito, transferencia, efectivo (Ama_Tip='P') en el período */
        case 25:
            $sql = "SELECT COALESCE(SUM(ma.Ama_Val), 0) as anticipo_por_aprobar
                    FROM manifiesto_anticipo ma
                    INNER JOIN cliente ON ma.Cli_Cod = cliente.Cli_Cod
                    LEFT JOIN tipos_pago tp ON COALESCE(ma.Pag_Cod, ma.Ama_Tde) = tp.Pag_Cod
                    WHERE ma.Ama_Est = 'A' AND ma.Ama_Tip = 'P' $where_fec_ma
                    AND cliente.Emp_Cod = $Emp_Cod $where_pla_ma $where_cli_ma
                    AND tp.Pag_Abr IN ('DEP','TRF','EFE')";
            return $sql;

        /* 23. Lista manifiestos del día por tipo (para modal Monitor Operativo) */
        case 23:
            $hoy = date('Y-m-d');
            $Tipo = isset($Par_Sql['Tipo']) ? addslashes($Par_Sql['Tipo']) : '';
            $where_pla_m = ($Pla_Cod > 0) ? " AND m.Pla_Cod = $Pla_Cod" : "";
            $where_cli_m = ($Cli_Cod > 0) ? " AND m.Cli_Cod = $Cli_Cod" : "";
            $base = "SELECT m.Man_Cod, m.Man_Num, m.Man_Fec, m.Man_Tes,
                    CONCAT('M', mp.Pla_Cod, '-', LPAD(m.Man_Num, 4, 0)) as ManNum,
                    CONCAT(COALESCE(p.Prs_Nom,''), ' ', COALESCE(p.Prs_Ape,'')) as Cliente,
                    mp.Pla_Nom, mtud.Tud_Hin, mtud.Tud_Hfi,
                    IF(LOCATE('GE', m.Man_Tes) > 0,'GE','') as Man_Tip_1,
                    IF(LOCATE('A', m.Man_Tes) > 0,'A','') as Man_Tip_2,
                    IF(LOCATE('GS', m.Man_Tes) > 0,'GS','') as Man_Tip_3,
                    IF(LOCATE('F', m.Man_Tes) > 0,'F','') as Man_Tip_4,
                    IF(LOCATE('R', m.Man_Tes) > 0,'R','') as Man_Tip_5
                    FROM manifiesto m
                    INNER JOIN manifiesto_turnos_det mtud ON m.Tud_Cod = mtud.Tud_Cod
                    INNER JOIN manifiesto_turnos_cab mtuc ON mtud.Tur_Cod = mtuc.Tur_Cod
                    INNER JOIN cliente c ON m.Cli_Cod = c.Cli_Cod
                    INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                    INNER JOIN manifiesto_plantas mp ON m.Pla_Cod = mp.Pla_Cod
                    WHERE mtud.Tud_Fec = '$hoy' AND mtuc.Emp_Cod = $Emp_Cod AND mtuc.Tur_Est != 'I'
                    $where_pla_m $where_cli_m";
            $filtro_tes = " AND (m.Man_Tes IS NULL OR LOCATE('R', m.Man_Tes)=0)";
            switch ($Tipo) {
                case 'total_turnos':
                    return $base . " AND m.Man_Est IN ('A','I') ORDER BY m.Man_Num";
                case 'turnos_pendientes':
                    return $base . $filtro_tes . " AND m.Man_Tip = 'P' AND m.Man_Est = 'A' ORDER BY m.Man_Num";
                case 'garita_in':
                    return $base . $filtro_tes . " AND m.Man_Tip = 'GE' AND m.Man_Est = 'A' ORDER BY m.Man_Num";
                case 'aprobados':
                    return $base . $filtro_tes . " AND m.Man_Est = 'A' AND LOCATE('A', m.Man_Tes) > 0 ORDER BY m.Man_Num";
                case 'garita_out':
                    return $base . $filtro_tes . " AND m.Man_Tip = 'GS' AND m.Man_Est = 'A' ORDER BY m.Man_Num";
                case 'turnos_anulados':
                    return $base . " AND m.Man_Est = 'I' ORDER BY m.Man_Num";
                default:
                    return $base . " AND m.Man_Est IN ('A','I') ORDER BY m.Man_Num LIMIT 0";
            }

        /* 26. Facturación por Planta - Mensual vs Diario (Pendientes de facturar) */
        case 26:
            $sql = "SELECT 
                        manifiesto_plantas.Pla_Pfa as modo,
                        manifiesto_plantas.Pla_Nom as planta,
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as cliente,
                        COUNT(manifiesto.Man_Cod) as cantidad
                    FROM manifiesto
                    INNER JOIN manifiesto_plantas ON manifiesto.Pla_Cod = manifiesto_plantas.Pla_Cod
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    WHERE manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND manifiesto.Man_Tip = 'GS' AND (manifiesto.Vet_Cod IS NULL OR (manifiesto.Vet_Cod = 0))
                    $where_emp
                    " . ($Pla_Cod > 0 ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "") . "
                    $where_fec
                    GROUP BY manifiesto_plantas.Pla_Pfa, manifiesto_plantas.Pla_Nom, persona.Prs_Nom, persona.Prs_Ape";
            return $sql;

        /* 27. Tiempos en Relavera (Entrada vs Salida general de Man_Usu JSON) */
        case 27:
            $sql = "SELECT Man_Usu FROM manifiesto
                    INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                    WHERE manifiesto.Man_Est = 'A' AND (manifiesto.Man_Tes IS NULL OR LOCATE('R', manifiesto.Man_Tes)=0)
                    AND Man_Usu IS NOT NULL AND Man_Usu != ''
                    $where_emp
                    $where_fec
                    " . ($Pla_Cod > 0 ? " AND manifiesto.Pla_Cod = $Pla_Cod" : "") . "
                    " . ($Cli_Cod > 0 ? " AND manifiesto.Cli_Cod = $Cli_Cod" : "");
            return $sql;

        default:
            return '';
    }
}
