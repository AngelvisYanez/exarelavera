<?php
/**
 * Sentencias SQL para el módulo de control de alimentación de choferes en Relavera
 * Maneja todo el CRUD de consultas, conteo, inserción y modificación.
 * @author Sistema EXA
 * @version 1.0
 */
function sentencias_alimentacion($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            // Listar choferes asociados a la planta
            $sql = "SELECT 
                        c.Cho_Cod, 
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS nombre, 
                        p.Prs_Ced
                    FROM chofer c 
                    INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                    INNER JOIN manifiesto_chofer mc ON mc.Cho_Cod = c.Cho_Cod
                    WHERE c.Cho_Est = 'A' AND mc.Pla_Cod = '{$Par_Sql['Pla_Cod']}'
                    ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        case 2:
            // Listar vehículos asociados a la planta
            $sql = "SELECT 
                        v.Veh_Cod, 
                        v.Veh_Pla, 
                        v.Veh_Mar
                    FROM vehiculo v 
                    INNER JOIN manifiesto_vehiculo mv ON mv.Veh_Cod = v.Veh_Cod
                    WHERE v.Veh_Est = 'A' AND mv.Pla_Cod = '{$Par_Sql['Pla_Cod']}'
                    ORDER BY v.Veh_Pla";
            break;

        case 3:
            // Obtener último vehículo usado por un chofer uniendo maquinaria_horometro y maquinaria_alimentacion
            $sql = "SELECT combined.Veh_Cod, v.Veh_Pla, v.Veh_Mar 
                    FROM (
                        SELECT h.Veh_Cod, h.Hor_Fec as Fec, h.Hor_Cod as Cod 
                        FROM maquinaria_horometro h 
                        WHERE h.Cho_Cod = '{$Par_Sql['Cho_Cod']}' AND h.Hor_Est = 'A'
                        UNION ALL
                        SELECT a.Veh_Cod, a.Mal_Fec as Fec, a.Mal_Cod as Cod 
                        FROM maquinaria_alimentacion a 
                        WHERE a.Cho_Cod = '{$Par_Sql['Cho_Cod']}' AND a.Mal_Est = 'A'
                    ) as combined
                    INNER JOIN vehiculo v ON v.Veh_Cod = combined.Veh_Cod
                    ORDER BY combined.Fec DESC, combined.Cod DESC 
                    LIMIT 1";
            break;

        case 4:
            // Verificar duplicado de alimentación
            $sql = "SELECT Mal_Cod FROM maquinaria_alimentacion 
                    WHERE Cho_Cod = '{$Par_Sql['Cho_Cod']}' 
                    AND Veh_Cod = '{$Par_Sql['Veh_Cod']}' 
                    AND Mal_Fec = '{$Par_Sql['Mal_Fec']}' 
                    AND Mal_Tip = '{$Par_Sql['Mal_Tip']}' 
                    AND Mal_Est = 'A'";
            break;

        case 5:
            // Insertar nuevo registro de alimentación
            $sql = "INSERT INTO maquinaria_alimentacion 
                    (Cho_Cod, Usu_Cod, Veh_Cod, Mal_Tip, Mal_Fec, Mal_Est, Mal_Sys) 
                    VALUES 
                    ('{$Par_Sql['Cho_Cod']}', '{$Par_Sql['Usu_Cod']}', '{$Par_Sql['Veh_Cod']}', '{$Par_Sql['Mal_Tip']}', '{$Par_Sql['Mal_Fec']}', 'A', NOW())";
            break;

        case 6:
            // Consultar registros de alimentación para el grid (contador)
            $where = " WHERE 1=1";
            if (!empty($Par_Sql['Mal_Fec_Desde'])) {
                $where .= " AND a.Mal_Fec >= '{$Par_Sql['Mal_Fec_Desde']}'";
            }
            if (!empty($Par_Sql['Mal_Fec_Hasta'])) {
                $where .= " AND a.Mal_Fec <= '{$Par_Sql['Mal_Fec_Hasta']}'";
            }
            if (!empty($Par_Sql['Cho_Cod'])) {
                $where .= " AND a.Cho_Cod = '{$Par_Sql['Cho_Cod']}'";
            }
            if (!empty($Par_Sql['Mal_Est'])) {
                $where .= " AND a.Mal_Est = '{$Par_Sql['Mal_Est']}'";
            }
            if (!empty($Par_Sql['f_buscar'])) {
                if ($Par_Sql['f_tipo_busqueda'] === 'chofer') {
                    $where .= " AND (p.Prs_Nom LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ape LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ced LIKE '%{$Par_Sql['f_buscar']}%')";
                } else {
                    $where .= " AND v.Veh_Pla LIKE '%{$Par_Sql['f_buscar']}%'";
                }
            }
            $sql = "SELECT COUNT(DISTINCT a.Mal_Fec, a.Cho_Cod, a.Veh_Cod, a.Usu_Cod, a.Mal_Est) as total 
                    FROM maquinaria_alimentacion a 
                    INNER JOIN chofer c ON c.Cho_Cod = a.Cho_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    INNER JOIN vehiculo v ON v.Veh_Cod = a.Veh_Cod
                    $where";
            break;

        case 7:
            // Consultar registros de alimentación para el grid (datos)
            $where = " WHERE 1=1";
            if (!empty($Par_Sql['Mal_Fec_Desde'])) {
                $where .= " AND a.Mal_Fec >= '{$Par_Sql['Mal_Fec_Desde']}'";
            }
            if (!empty($Par_Sql['Mal_Fec_Hasta'])) {
                $where .= " AND a.Mal_Fec <= '{$Par_Sql['Mal_Fec_Hasta']}'";
            }
            if (!empty($Par_Sql['Cho_Cod'])) {
                $where .= " AND a.Cho_Cod = '{$Par_Sql['Cho_Cod']}'";
            }
            if (!empty($Par_Sql['Mal_Est'])) {
                $where .= " AND a.Mal_Est = '{$Par_Sql['Mal_Est']}'";
            }
            if (!empty($Par_Sql['f_buscar'])) {
                if ($Par_Sql['f_tipo_busqueda'] === 'chofer') {
                    $where .= " AND (p.Prs_Nom LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ape LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ced LIKE '%{$Par_Sql['f_buscar']}%')";
                } else {
                    $where .= " AND v.Veh_Pla LIKE '%{$Par_Sql['f_buscar']}%'";
                }
            }
            $sql = "SELECT GROUP_CONCAT(CASE WHEN a.Mal_Est = 'A' THEN a.Mal_Cod END) AS Active_Ids,
                           MIN(a.Mal_Cod) as Mal_Cod, a.Mal_Fec, 
                           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Cho_Nom,
                           v.Veh_Pla,
                           MAX(CASE WHEN a.Mal_Tip = 'D' THEN 1 ELSE 0 END) AS Tip_D,
                           MAX(CASE WHEN a.Mal_Tip = 'A' THEN 1 ELSE 0 END) AS Tip_A,
                           MAX(CASE WHEN a.Mal_Tip = 'M' THEN 1 ELSE 0 END) AS Tip_M,
                           MAX(CASE WHEN a.Mal_Tip = 'C' THEN 1 ELSE 0 END) AS Tip_C,
                           CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) AS Usu_Nom,
                           a.Mal_Est
                    FROM maquinaria_alimentacion a
                    INNER JOIN chofer c ON c.Cho_Cod = a.Cho_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    INNER JOIN vehiculo v ON v.Veh_Cod = a.Veh_Cod
                    INNER JOIN usuarios u ON u.Usu_Cod = a.Usu_Cod
                    INNER JOIN persona pu ON pu.Prs_Cod = u.Prs_Cod
                    $where
                    GROUP BY a.Mal_Fec, a.Cho_Cod, a.Veh_Cod, a.Usu_Cod, a.Mal_Est, p.Prs_Nom, p.Prs_Ape, v.Veh_Pla, pu.Prs_Nom, pu.Prs_Ape
                    ORDER BY a.Mal_Fec DESC, Mal_Cod DESC " . (isset($Par_Sql['limits']) ? $Par_Sql['limits'] : "");
            break;

        case 8:
            // Anular registro de alimentación (logico) - Soportando múltiples ids separados por coma
            $sql = "UPDATE maquinaria_alimentacion SET Mal_Est = 'I' WHERE Mal_Cod IN ({$Par_Sql['Mal_Cod']})";
            break;

        case 9:
            // Obtener datos para el reporte quincenal/mensual
            $where = " WHERE a.Mal_Est = 'A' AND YEAR(a.Mal_Fec) = '{$Par_Sql['Anio']}' AND MONTH(a.Mal_Fec) = '{$Par_Sql['Mes']}'";
            if (!empty($Par_Sql['Veh_Cod'])) {
                $where .= " AND a.Veh_Cod = '{$Par_Sql['Veh_Cod']}'";
            }
            if (!empty($Par_Sql['Cho_Cod'])) {
                $where .= " AND a.Cho_Cod = '{$Par_Sql['Cho_Cod']}'";
            }
            $sql = "SELECT a.Mal_Fec, 
                           a.Cho_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Cho_Nom,
                           a.Veh_Cod, v.Veh_Pla,
                           a.Mal_Tip
                    FROM maquinaria_alimentacion a
                    INNER JOIN chofer c ON c.Cho_Cod = a.Cho_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    INNER JOIN vehiculo v ON v.Veh_Cod = a.Veh_Cod
                    $where
                    ORDER BY v.Veh_Pla, a.Mal_Fec";
            break;
    }
    return $sql;
}
