<?php

/**
 * Sentencias SQL para el módulo de Preliquidación de Maquinaria
 * @author Sistema EXA
 * @version 1.0
 */
function sentencias_maquinaria_preliquidacion($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            $sql = "SELECT DISTINCT Veh_Cod, Veh_Pla, Veh_Mar 
                    FROM vehiculo 
                    WHERE Veh_Est = 'A' AND Emp_Cod = " . (int)$Par_Sql['Emp_Cod'] . " AND (Veh_Tip != 'VM' OR Veh_Tip IS NULL)
                    ORDER BY Veh_Pla ASC";
            break;

        case 2:
            // Listar Operadores (Agrupado por persona para no tener repetidos, tomando el MAX Cho_Cod activo)
            $sql = "SELECT MAX(c.Cho_Cod) as Cho_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as nombre
                    FROM chofer c 
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod 
                    WHERE c.Cho_Est = 'A' AND c.Emp_Cod = " . (int)$Par_Sql['Emp_Cod'] . " AND (c.Cho_Tip != 'CM' OR c.Cho_Tip IS NULL)
                    GROUP BY p.Prs_Cod, p.Prs_Nom, p.Prs_Ape
                    ORDER BY p.Prs_Ape ASC, p.Prs_Nom ASC";
            break;

        case 3:
            // Horómetro (Para detalle y cálculo)
            $where = " (mh.Hor_Est IN ('A', 'F') OR mh.Hor_Est IS NULL OR mh.Hor_Est = '') AND (mh.Mal_Cod IS NULL OR mh.Mal_Cod = 0) ";
            if (!empty($Par_Sql['fecha_ini']) && !empty($Par_Sql['fecha_fin'])) {
                $where .= " AND DATE(mh.Hor_Fec) BETWEEN '{$Par_Sql['fecha_ini']}' AND '{$Par_Sql['fecha_fin']}'";
            } elseif (!empty($Par_Sql['fecha_fin'])) {
                $where .= " AND DATE(mh.Hor_Fec) <= '{$Par_Sql['fecha_fin']}'";
            }
            if (!empty($Par_Sql['Veh_Cod'])) {
                $where .= " AND mh.Veh_Cod = '{$Par_Sql['Veh_Cod']}'";
            }
            if (!empty($Par_Sql['Cho_Cod'])) {
                $where .= " AND mh.Cho_Cod = '{$Par_Sql['Cho_Cod']}'";
            }

            $sql = "SELECT mh.Hor_Cod, DATE(mh.Hor_Fec) as fecha, 
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as chofer, 
                           mh.Hor_Hini as hora_ini, 
                           mh.Hor_Hfin as hora_fin, 
                           mh.Hor_Ini as lec_ini,
                           mh.Hor_Fin as lec_fin,
                           IFNULL(mh.Hor_Cal, (mh.Hor_Fin - mh.Hor_Ini)) as horas_trab,
                           IFNULL(v.Veh_Val, 0) as valor_pactado,
                           mh.Hor_Est as estado,
                           CAST(mh.Hor_Obs AS CHAR) as observacion,
                           mh.Hor_Img_Ini as img_ini,
                           mh.Hor_Img_Fin as img_fin
                    FROM maquinaria_horometro mh
                    LEFT JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    LEFT JOIN vehiculo v ON v.Veh_Cod = mh.Veh_Cod
                    WHERE $where
                    ORDER BY mh.Hor_Fec ASC";
            break;

        case 4:
            // Combustible (Para detalle y cálculo)
            $where = " md.Did_Tip = 'SA' AND md.Did_Est = 'A' AND (md.Mal_Cod IS NULL OR md.Mal_Cod = 0) ";
            if (!empty($Par_Sql['fecha_ini']) && !empty($Par_Sql['fecha_fin'])) {
                $where .= " AND DATE(md.Did_Fec) BETWEEN '{$Par_Sql['fecha_ini']}' AND '{$Par_Sql['fecha_fin']}'";
            } elseif (!empty($Par_Sql['fecha_fin'])) {
                $where .= " AND DATE(md.Did_Fec) <= '{$Par_Sql['fecha_fin']}'";
            }
            if (!empty($Par_Sql['Veh_Cod'])) {
                $where .= " AND md.Veh_Cod = '{$Par_Sql['Veh_Cod']}'";
            }
            // Eliminamos el filtro por Cho_Cod aquí porque el despacho de combustible no siempre registra el chofer (suele ser NULL)

            $sql = "SELECT md.Did_Cod, DATE(md.Did_Fec) as fecha, 
                           md.Did_Can as cantidad, 
                           md.Did_Pun as precio_unitario, 
                           (md.Did_Can * md.Did_Pun) as costo,
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as chofer,
                           md.Did_Obs as observacion
                    FROM maquinaria_dispensador_det md
                    LEFT JOIN chofer c ON c.Cho_Cod = md.Cho_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE $where
                    ORDER BY md.Did_Fec ASC";
            break;

        case 5:
            // Compras - A la espera de definir tabla y relación exacta con Veh_Cod
            // Se envía un array vacío desde el PHP directamente.
            $sql = "";
            break;

        case 6:
            // Listado de Preliquidaciones guardadas (Para historial si se requiere)
            $where = " 1 = 1 ";

            if (!empty($Par_Sql['fil_hist_est'])) {
                if ($Par_Sql['fil_hist_est'] == 'P') {
                    $where .= " AND m.Mal_Est = 'A' AND (m.Cop_Cod IS NULL OR m.Cop_Cod = 0)";
                } elseif ($Par_Sql['fil_hist_est'] == 'L') {
                    $where .= " AND m.Mal_Est = 'A' AND m.Cop_Cod > 0";
                } elseif ($Par_Sql['fil_hist_est'] == 'I') {
                    $where .= " AND m.Mal_Est = 'I'";
                }
            }

            if (!empty($Par_Sql['fil_hist_veh'])) {
                $where .= " AND v.Veh_Pla LIKE '%" . addslashes($Par_Sql['fil_hist_veh']) . "%'";
            }

            if (!empty($Par_Sql['fil_hist_doc'])) {
                $where .= " AND m.Mal_Num LIKE '%" . addslashes($Par_Sql['fil_hist_doc']) . "%'";
            }

            $sql = "SELECT m.Mal_Cod, m.Mal_Num, DATE(m.Mal_Fec) as fecha, 
                           CONCAT(DATE(m.Mal_Fec_Ini), ' al ', DATE(m.Mal_Fec_Fin)) as periodo,
                           CONCAT(v.Veh_Pla, ' - ', IFNULL(v.Veh_Mar,'')) as vehiculo,
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as usuario,
                           m.Mal_Est as estado,
                           m.Cop_Cod
                    FROM manifiesto_liquidacion_maq m
                    LEFT JOIN vehiculo v ON v.Veh_Cod = m.Veh_Cod
                    LEFT JOIN usuarios u ON u.Usu_Cod = m.Usu_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                    WHERE $where
                    ORDER BY m.Mal_Cod DESC";
            break;

        case 8:
            // Horometro historico por Mal_Cod
            $sql = "SELECT mh.Hor_Cod, DATE(mh.Hor_Fec) as fecha, 
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as chofer, 
                           CONCAT(v.Veh_Pla, ' ', IFNULL(v.Veh_Mar,'')) as maquinaria,
                           mh.Hor_Ini as hora_ini, 
                           mh.Hor_Fin as hora_fin, 
                           IFNULL(mh.Hor_Cal, (mh.Hor_Fin - mh.Hor_Ini)) as horas_trab,
                           CAST(mh.Hor_Obs AS CHAR) as observacion,
                           mh.Hor_Img_Ini as img_ini,
                           mh.Hor_Img_Fin as img_fin
                    FROM maquinaria_horometro mh
                    LEFT JOIN vehiculo v ON v.Veh_Cod = mh.Veh_Cod
                    LEFT JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE mh.Mal_Cod = '" . (int)$Par_Sql['Mal_Cod'] . "'
                    ORDER BY mh.Hor_Fec ASC";
            break;

        case 9:
            // Combustible historico por Mal_Cod
            $sql = "SELECT md.Did_Cod, DATE(md.Did_Fec) as fecha, 
                           md.Did_Can as cantidad, 
                           md.Did_Pun as precio_unitario, 
                           (md.Did_Can * md.Did_Pun) as costo,
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as chofer,
                           md.Did_Obs as observacion
                    FROM maquinaria_dispensador_det md
                    LEFT JOIN chofer c ON c.Cho_Cod = md.Cho_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE md.Mal_Cod = '" . (int)$Par_Sql['Mal_Cod'] . "' AND md.Did_Tip = 'SA'
                    ORDER BY md.Did_Fec ASC";
            break;

        case 7:
            // Obtener último operador de una maquinaria
            $sql = "SELECT mh.Cho_Cod
                    FROM maquinaria_horometro mh
                    WHERE mh.Veh_Cod = '" . $Par_Sql['Veh_Cod'] . "' 
                      AND (mh.Hor_Est IN ('A', 'F') OR mh.Hor_Est IS NULL OR mh.Hor_Est = '')
                      AND mh.Cho_Cod IS NOT NULL 
                      AND mh.Cho_Cod != '' 
                      AND mh.Cho_Cod != '0'
                    ORDER BY DATE(mh.Hor_Fec) DESC, mh.Hor_Cod DESC 
                    LIMIT 1";
            break;

        case 10:
            // Insertar Cabecera de Preliquidación
            $fecha_ini_val = empty($Par_Sql['Mal_Fec_Ini']) ? 'NULL' : "'" . $Par_Sql['Mal_Fec_Ini'] . "'";

            $mal_tot_hor = isset($Par_Sql['Mal_Tot_Hor']) ? (float)$Par_Sql['Mal_Tot_Hor'] : 0;
            $mal_des_hor = isset($Par_Sql['Mal_Des_Hor']) ? (float)$Par_Sql['Mal_Des_Hor'] : 0;
            $mal_tot_des = isset($Par_Sql['Mal_Tot_Des']) ? (float)$Par_Sql['Mal_Tot_Des'] : 0;
            $mal_tot_cob = isset($Par_Sql['Mal_Tot_Cob']) ? (float)$Par_Sql['Mal_Tot_Cob'] : 0;

            $sql = "INSERT INTO manifiesto_liquidacion_maq 
                    (Veh_Cod, Usu_Cod, Cop_Cod, Mal_Num, Mal_Fec, Mal_Fec_Ini, Mal_Fec_Fin, Mal_Obs, Mal_Tot_Hor, Mal_Des_Hor, Mal_Tot_Des, Mal_Tot_Cob, Mal_Est) 
                    VALUES ('" . $Par_Sql['Veh_Cod'] . "', " . (int)$Par_Sql['Usu_Cod'] . ", NULL, NULL, NOW(), $fecha_ini_val, '" . $Par_Sql['Mal_Fec_Fin'] . "', '" . addslashes($Par_Sql['Mal_Obs']) . "', $mal_tot_hor, $mal_des_hor, $mal_tot_des, $mal_tot_cob, 'A')";
            break;

        case 11:
            // Actualizar Horómetros con Mal_Cod
            $sql = "UPDATE maquinaria_horometro SET Mal_Cod = " . (int)$Par_Sql['Mal_Cod'] . " 
                    WHERE Veh_Cod = '" . $Par_Sql['Veh_Cod'] . "' AND (Hor_Est IN ('A', 'F') OR Hor_Est IS NULL OR Hor_Est = '') AND (Mal_Cod IS NULL OR Mal_Cod = 0)";
            if (!empty($Par_Sql['fecha_ini']) && !empty($Par_Sql['fecha_fin'])) {
                $sql .= " AND DATE(Hor_Fec) BETWEEN '" . $Par_Sql['fecha_ini'] . "' AND '" . $Par_Sql['fecha_fin'] . "'";
            } elseif (!empty($Par_Sql['fecha_fin'])) {
                $sql .= " AND DATE(Hor_Fec) <= '" . $Par_Sql['fecha_fin'] . "'";
            }
            if (!empty($Par_Sql['Cho_Cod'])) {
                $sql .= " AND Cho_Cod = " . (int)$Par_Sql['Cho_Cod'];
            }
            break;

        case 12:
            // Actualizar Combustibles con Mal_Cod
            $sql = "UPDATE maquinaria_dispensador_det SET Mal_Cod = " . (int)$Par_Sql['Mal_Cod'] . " 
                    WHERE Veh_Cod = '" . $Par_Sql['Veh_Cod'] . "' AND Did_Tip = 'SA' AND Did_Est = 'A' AND (Mal_Cod IS NULL OR Mal_Cod = 0)";
            if (!empty($Par_Sql['fecha_ini']) && !empty($Par_Sql['fecha_fin'])) {
                $sql .= " AND DATE(Did_Fec) BETWEEN '" . $Par_Sql['fecha_ini'] . "' AND '" . $Par_Sql['fecha_fin'] . "'";
            } elseif (!empty($Par_Sql['fecha_fin'])) {
                $sql .= " AND DATE(Did_Fec) <= '" . $Par_Sql['fecha_fin'] . "'";
            }
            // NO se filtra por Cho_Cod en combustible porque el surtidor suele no registrar el operador,
            // y se asume todo el consumo del vehículo en ese periodo.
            break;

        case 13:
            // Actualizar Mal_Num automático
            $sql = "UPDATE manifiesto_liquidacion_maq SET Mal_Num = '" . $Par_Sql['Mal_Num'] . "' WHERE Mal_Cod = " . (int)$Par_Sql['Mal_Cod'];
            break;

        case 14:
            // Obtener el siguiente id auto_increment
            $sql = "SELECT AUTO_INCREMENT AS next_id 
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'manifiesto_liquidacion_maq'";
            break;

        case 15:
            // Obtener ultimo operador de una maquina
            $sql = "SELECT h.Cho_Cod 
                    FROM maquinaria_horometro h
                    WHERE h.Veh_Cod = '" . $Par_Sql['Veh_Cod'] . "' 
                      AND h.Cho_Cod IS NOT NULL 
                    ORDER BY h.Hor_Fec DESC, h.Hor_Cod DESC 
                    LIMIT 1";
            break;

        case 16:
            // Consulta masiva horometro
            $sql = "SELECT
                        h.Veh_Cod,
                        h.Cho_Cod,
                        COUNT(h.Hor_Cod) AS total_registros_horometro,
                        SUM(IFNULL(h.Hor_Cal,0)) AS total_horas,
                        IFNULL(v.Veh_Val,0) AS valor_hora,
                        MAX(CONCAT(IFNULL(v.Veh_Pla,''), ' - ', IFNULL(v.Veh_Mar,''))) AS vehiculo_desc,
                        MAX(CONCAT(IFNULL(p.Prs_Nom,''), ' ', IFNULL(p.Prs_Ape,''))) AS chofer_desc
                    FROM maquinaria_horometro h
                    INNER JOIN vehiculo v ON v.Veh_Cod = h.Veh_Cod
                    LEFT JOIN chofer c ON c.Cho_Cod = h.Cho_Cod
                    LEFT JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE h.Mal_Cod IS NULL
                      AND DATE(h.Hor_Fec) BETWEEN '" . $Par_Sql['fecha_ini'] . "' AND '" . $Par_Sql['fecha_fin'] . "'
                      AND IFNULL(h.Hor_Cal,0) > 0
                      AND (h.Hor_Est IN ('F','A') OR h.Hor_Est IS NULL OR h.Hor_Est = '')
                    GROUP BY h.Veh_Cod, h.Cho_Cod, v.Veh_Val";
            break;

        case 17:
            // Consulta masiva combustible
            $sql = "SELECT
                        d.Veh_Cod,
                        d.Cho_Cod,
                        COUNT(d.Did_Cod) AS total_despachos,
                        SUM(IFNULL(d.Did_Can,0)) AS combustible_cargado,
                        SUM(IFNULL(d.Did_Can,0) * IFNULL(d.Did_Pun,0)) AS costo_combustible
                    FROM maquinaria_dispensador_det d
                    WHERE d.Mal_Cod IS NULL
                      AND d.Did_Tip = 'SA'
                      AND d.Did_Est = 'A'
                      AND DATE(d.Did_Fec) BETWEEN '" . $Par_Sql['fecha_ini'] . "' AND '" . $Par_Sql['fecha_fin'] . "'
                    GROUP BY d.Veh_Cod, d.Cho_Cod";
            break;

        case 18:
            // Obtener cabecera de preliquidacion
            $sql = "SELECT m.*, CONCAT(IFNULL(v.Veh_Mar, ''), ' ', IFNULL(v.Veh_Pla, '')) as vehiculo_desc
                    FROM manifiesto_liquidacion_maq m 
                    LEFT JOIN vehiculo v ON m.Veh_Cod = v.Veh_Cod 
                    WHERE m.Mal_Cod = " . (int)$Par_Sql['Mal_Cod'];
            break;
    }
    return $sql;
}
