<?php
/**
 * Sentencias SQL para el módulo de control de alimentación de personal interno en Relavera
 * Maneja todo el CRUD de consultas, conteo, inserción y modificación.
 * @author Sistema EXA
 * @version 2.0
 */
function sentencias_alimentacion($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1:
            // Listar personal interno
            $sql = "SELECT 
                        pe.Per_Cod, 
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS nombre, 
                        p.Prs_Ced
                    FROM personal pe 
                    INNER JOIN persona p ON pe.Prs_Cod = p.Prs_Cod
                    WHERE pe.Per_Est = 'A'
                    ORDER BY p.Prs_Ape, p.Prs_Nom";
            break;

        case 2:
            // Ya no se requiere listar vehículos
            $sql = "SELECT 1";
            break;

        case 3:
            // Ya no se requiere último vehículo usado
            $sql = "SELECT 1";
            break;

        case 4:
            // Verificar duplicado de alimentación
            $sql = "SELECT Mal_Cod FROM maquinaria_alimentacion 
                    WHERE Per_Cod = '{$Par_Sql['Per_Cod']}' 
                    AND Mal_Fec = '{$Par_Sql['Mal_Fec']}' 
                    AND Mal_Tip = '{$Par_Sql['Mal_Tip']}' 
                    AND Mal_Est = 'A'";
            break;

        case 5:
            // Insertar nuevo registro de alimentación
            $sql = "INSERT INTO maquinaria_alimentacion 
                    (Per_Cod, Usu_Cod, Mal_Tip, Mal_Fec, Mal_Est, Mal_Sys) 
                    VALUES 
                    ('{$Par_Sql['Per_Cod']}', '{$Par_Sql['Usu_Cod']}', '{$Par_Sql['Mal_Tip']}', '{$Par_Sql['Mal_Fec']}', 'A', NOW())";
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
            if (!empty($Par_Sql['Per_Cod'])) {
                $where .= " AND a.Per_Cod = '{$Par_Sql['Per_Cod']}'";
            }
            if (!empty($Par_Sql['Mal_Est'])) {
                $where .= " AND a.Mal_Est = '{$Par_Sql['Mal_Est']}'";
            }
            if (!empty($Par_Sql['f_buscar'])) {
                $where .= " AND (p.Prs_Nom LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ape LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ced LIKE '%{$Par_Sql['f_buscar']}%')";
            }
            $sql = "SELECT COUNT(DISTINCT a.Mal_Fec, a.Per_Cod, a.Usu_Cod, a.Mal_Est) as total 
                    FROM maquinaria_alimentacion a 
                    INNER JOIN personal pe ON pe.Per_Cod = a.Per_Cod
                    INNER JOIN persona p ON p.Prs_Cod = pe.Prs_Cod
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
            if (!empty($Par_Sql['Per_Cod'])) {
                $where .= " AND a.Per_Cod = '{$Par_Sql['Per_Cod']}'";
            }
            if (!empty($Par_Sql['Mal_Est'])) {
                $where .= " AND a.Mal_Est = '{$Par_Sql['Mal_Est']}'";
            }
            if (!empty($Par_Sql['f_buscar'])) {
                $where .= " AND (p.Prs_Nom LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ape LIKE '%{$Par_Sql['f_buscar']}%' OR p.Prs_Ced LIKE '%{$Par_Sql['f_buscar']}%')";
            }
            $sql = "SELECT GROUP_CONCAT(CASE WHEN a.Mal_Est = 'A' THEN a.Mal_Cod END) AS Active_Ids,
                        MIN(a.Mal_Cod) as Mal_Cod, a.Mal_Fec, 
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Per_Nom,
                        p.Prs_Ced AS Per_Ced,
                        MAX(CASE WHEN a.Mal_Tip = 'D' THEN 1 ELSE 0 END) AS Tip_D,
                        MAX(CASE WHEN a.Mal_Tip = 'A' THEN 1 ELSE 0 END) AS Tip_A,
                        MAX(CASE WHEN a.Mal_Tip = 'M' THEN 1 ELSE 0 END) AS Tip_M,
                        MAX(CASE WHEN a.Mal_Tip = 'C' THEN 1 ELSE 0 END) AS Tip_C,
                        CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) AS Usu_Nom,
                        a.Mal_Est
                    FROM maquinaria_alimentacion a
                    INNER JOIN personal pe ON pe.Per_Cod = a.Per_Cod
                    INNER JOIN persona p ON p.Prs_Cod = pe.Prs_Cod
                    INNER JOIN usuarios u ON u.Usu_Cod = a.Usu_Cod
                    INNER JOIN persona pu ON pu.Prs_Cod = u.Prs_Cod
                    $where
                    GROUP BY a.Mal_Fec, a.Per_Cod, a.Usu_Cod, a.Mal_Est, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced, pu.Prs_Nom, pu.Prs_Ape
                    ORDER BY a.Mal_Fec DESC, Mal_Cod DESC " . (isset($Par_Sql['limits']) ? $Par_Sql['limits'] : "");
            break;

        case 8:
            // Anular registro de alimentación (logico) - Soportando múltiples ids separados por coma
            $sql = "UPDATE maquinaria_alimentacion SET Mal_Est = 'I' WHERE Mal_Cod IN ({$Par_Sql['Mal_Cod']})";
            break;

        case 9:
            // Obtener datos para el reporte quincenal/mensual
            $where = " WHERE a.Mal_Est = 'A' AND YEAR(a.Mal_Fec) = '{$Par_Sql['Anio']}' AND MONTH(a.Mal_Fec) = '{$Par_Sql['Mes']}'";
            if (!empty($Par_Sql['Per_Cod'])) {
                $where .= " AND a.Per_Cod = '{$Par_Sql['Per_Cod']}'";
            }
            $sql = "SELECT a.Mal_Fec, a.Mal_Tip, 
                            a.Per_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Per_Nom
                    FROM maquinaria_alimentacion a
                    INNER JOIN personal pe ON pe.Per_Cod = a.Per_Cod
                    INNER JOIN persona p ON p.Prs_Cod = pe.Prs_Cod
                    $where
                    ORDER BY p.Prs_Ape, p.Prs_Nom, a.Mal_Fec";
            break;
    }
    return $sql;
}

