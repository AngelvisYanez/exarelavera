<?php
/**
 * Sentencias SQL para el módulo de Gestión y Horómetro de Maquinaria
 * @author Sistema EXA
 * @version 1.0
 */
function sentencias_maquinaria_horometro($id, $Par_Sql)
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
            $sql = "SELECT MIN(c.Cho_Cod) as Cho_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as nombre, p.Prs_Ced 
                    FROM chofer c 
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod 
                    WHERE c.Cho_Est = 'A' AND c.Emp_Cod = " . (int)$Par_Sql['Emp_Cod'] . " AND (c.Cho_Tip != 'CM' OR c.Cho_Tip IS NULL)
                    GROUP BY p.Prs_Cod, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced
                    ORDER BY p.Prs_Ape ASC, p.Prs_Nom ASC";
            break;

        case 3:
            // Listar lecturas de Horómetros para el Grid (Agrupado por Día, Vehículo, Operador)
            $search = "";
            if (!empty($Par_Sql['search']) && !empty($Par_Sql['op_opciones'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                if ($Par_Sql['op_opciones'] == 'p') {
                    $search = " AND v.Veh_Pla LIKE '%$searchTerm%'";
                } else if ($Par_Sql['op_opciones'] == 'o') {
                    $search = " AND (p.Prs_Nom LIKE '%$searchTerm%' OR p.Prs_Ape LIKE '%$searchTerm%' OR p.Prs_Ced LIKE '$searchTerm%')";
                }
            }

            // Filtros de fecha interactivos
            if (!empty($Par_Sql['f_tipo']) && $Par_Sql['f_tipo'] != 'T') {
                $f_tipo = $Par_Sql['f_tipo'];
                $f_val = addslashes($Par_Sql['f_val']);
                $f_val2 = addslashes($Par_Sql['f_val2']);

                if ($f_tipo == 'D' && !empty($f_val)) {
                    $search .= " AND mh.Hor_Fec = '$f_val'";
                } else if ($f_tipo == 'S' && !empty($f_val)) {
                    // Formato week HTML5: YYYY-Www (ej. 2026-W23)
                    $parts = explode('-W', $f_val);
                    if (count($parts) == 2) {
                        $anio = $parts[0];
                        $sem = $parts[1];
                        // YEARWEEK de MySQL con el argumento 1 o 3 devuelve anio y semana, por ejemplo 202623.
                        // Convertiremos la semana y año para hacer math o usamos str_to_date.
                        // Lo más seguro:
                        $search .= " AND YEARWEEK(mh.Hor_Fec, 3) = '" . $anio . $sem . "'";
                    }
                } else if ($f_tipo == 'Q' && !empty($f_val)) {
                    // Formato month HTML5: YYYY-MM
                    $search .= " AND DATE_FORMAT(mh.Hor_Fec, '%Y-%m') = '$f_val'";
                    if ($f_val2 == '1') {
                        $search .= " AND DAY(mh.Hor_Fec) BETWEEN 1 AND 15";
                    } else if ($f_val2 == '2') {
                        $search .= " AND DAY(mh.Hor_Fec) >= 16";
                    }
                } else if ($f_tipo == 'M' && !empty($f_val)) {
                    $search .= " AND DATE_FORMAT(mh.Hor_Fec, '%Y-%m') = '$f_val'";
                }
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total FROM (
                            SELECT mh.Hor_Fec
                            FROM maquinaria_horometro mh
                            INNER JOIN vehiculo v ON v.Veh_Cod = mh.Veh_Cod
                            INNER JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                            INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                            WHERE v.Emp_Cod = " . (int)$Par_Sql[0] . " $search
                            GROUP BY mh.Hor_Fec, mh.Veh_Cod, mh.Cho_Cod
                        ) AS sub";
            } else {
                $sql = "SELECT MIN(mh.Hor_Cod) as Hor_Cod, mh.Hor_Fec, 
                               MIN(mh.Hor_Hini) as Hor_Hini, MAX(mh.Hor_Hfin) as Hor_Hfin, 
                               MIN(mh.Hor_Ini) as Hor_Ini, MAX(mh.Hor_Fin) as Hor_Fin, 
                               SUM(IF(mh.Hor_Fin > 0, mh.Hor_Fin - mh.Hor_Ini, 0)) as Hor_Hrs, 
                               MAX(mh.Hor_Set) as Hor_Set, 
                               MAX(mh.Hor_Obs) as Hor_Obs, MAX(mh.Hor_Est) as Hor_Est,
                               v.Veh_Pla, v.Veh_Mar, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as operador,
                               v.Veh_Cod, mh.Cho_Cod
                        FROM maquinaria_horometro mh
                        INNER JOIN vehiculo v ON v.Veh_Cod = mh.Veh_Cod
                        INNER JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                        INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                        WHERE v.Emp_Cod = " . (int)$Par_Sql[0] . " $search
                        GROUP BY mh.Hor_Fec, mh.Veh_Cod, mh.Cho_Cod
                        ORDER BY mh.Hor_Fec DESC " . $Par_Sql['limits'];
            }
            break;

        case 4:
            // INSERT Lectura de Horómetro (Inicial)
            $sql = "INSERT INTO maquinaria_horometro (Usu_Cod, Veh_Cod, Cho_Cod, Hor_Fec, Hor_Ini, Hor_Fin, Hor_Set, Hor_Obs, Hor_Hini, Hor_Img_Ini, Hor_Hfin, Hor_Img_Fin, Hor_Est)
                    VALUES (" . (int)$Par_Sql[0] . ", " . (int)$Par_Sql[1] . ", " . (int)$Par_Sql[2] . ", '" . $Par_Sql[3] . "', " . (float)$Par_Sql[4] . ", " . (float)$Par_Sql[5] . ", '" . addslashes($Par_Sql[6]) . "', '" . addslashes($Par_Sql[7]) . "', '" . $Par_Sql[8] . "', '" . addslashes($Par_Sql[9]) . "', '" . $Par_Sql[10] . "', '" . addslashes($Par_Sql[11]) . "', 'P')";
            break;

        case 5:
            // UPDATE Estado Horómetro
            $sql = "UPDATE maquinaria_horometro 
                    SET Hor_Est = '" . $Par_Sql[1] . "' 
                    WHERE Hor_Cod = " . (int)$Par_Sql[0];
            break;



        case 7:
            // Cargar Métricas Dashboard
            if ($Par_Sql['tipo'] == 'pendientes') {
                $sql = "SELECT COUNT(*) as total FROM maquinaria_horometro WHERE Hor_Est = 'P'";
            } else if ($Par_Sql['tipo'] == 'horas_mes') {
                $sql = "SELECT SUM(Hor_Fin - Hor_Ini) as total FROM maquinaria_horometro WHERE Hor_Est IN ('A', 'F') AND MONTH(Hor_Fec) = MONTH(CURRENT_DATE()) AND YEAR(Hor_Fec) = YEAR(CURRENT_DATE())";
            }
            break;



        case 12:
            // UPDATE Registro de Horómetro (Añadir Hor_Fin, etc)
            $sql = "UPDATE maquinaria_horometro 
                    SET Hor_Ini = " . (float)$Par_Sql[1] . ",
                        Hor_Fin = " . (float)$Par_Sql[2] . ",
                        Hor_Hfin = IF(Hor_Fin > 0 AND (Hor_Hfin IS NULL OR Hor_Hfin = '' OR Hor_Hfin = '00:00:00' OR Hor_Hfin = '0000-00-00 00:00:00'), '" . $Par_Sql[3] . "', Hor_Hfin),
                        Hor_Set = '" . addslashes($Par_Sql[4]) . "',
                        Hor_Obs = '" . addslashes($Par_Sql[5]) . "',
                        Hor_Img_Ini = IF('" . addslashes($Par_Sql[6]) . "' != '', '" . addslashes($Par_Sql[6]) . "', Hor_Img_Ini),
                        Hor_Img_Fin = IF('" . addslashes($Par_Sql[7]) . "' != '', '" . addslashes($Par_Sql[7]) . "', Hor_Img_Fin),
                        Hor_Est = IF(" . (float)$Par_Sql[2] . " > 0, 'F', Hor_Est)
                    WHERE Hor_Cod = " . (int)$Par_Sql[0];
            break;

        case 13:
            // OBTENER Registros SubGrid (Por Máquina, Operador, Fecha)
            $sql = "SELECT Hor_Cod, Veh_Cod, Cho_Cod, Hor_Fec, Hor_Ini, Hor_Fin, (Hor_Fin - Hor_Ini) as Hor_Hrs, Hor_Set, Hor_Obs, Hor_Hini, Hor_Hfin, Hor_Est 
                    FROM maquinaria_horometro 
                    WHERE Veh_Cod = " . (int)$Par_Sql['Veh_Cod'] . "
                      AND Cho_Cod = " . (int)$Par_Sql['Cho_Cod'] . "
                      AND Hor_Fec = '" . $Par_Sql['Hor_Fec'] . "'
                      AND Hor_Est != 'I'
                    ORDER BY Hor_Cod ASC";
            break;

        case 14:
            // Contar registros del día para renombrado de imágenes
            if ($Par_Sql['Hor_Cod'] > 0) {
                $sql = "SELECT COUNT(*) as total FROM maquinaria_horometro WHERE Veh_Cod = " . (int)$Par_Sql['Veh_Cod'] . " AND Cho_Cod = " . (int)$Par_Sql['Cho_Cod'] . " AND Hor_Fec = '" . $Par_Sql['Hor_Fec'] . "' AND Hor_Cod <= " . (int)$Par_Sql['Hor_Cod'];
            } else {
                $sql = "SELECT COUNT(*) as total FROM maquinaria_horometro WHERE Veh_Cod = " . (int)$Par_Sql['Veh_Cod'] . " AND Cho_Cod = " . (int)$Par_Sql['Cho_Cod'] . " AND Hor_Fec = '" . $Par_Sql['Hor_Fec'] . "'";
            }
            break;

        case 20:
            // Ficha de Maquinaria para Reporte Individual
            $veh_cod = isset($Par_Sql['Veh_Cod']) ? (int)$Par_Sql['Veh_Cod'] : (isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0);
            $sql = "SELECT v.Veh_Pla as id, v.Veh_Mar as marca, 'N/A' as modelo, 'N/A' as serie, 'Empresa' as propiedad
                    FROM vehiculo v
                    WHERE v.Veh_Cod = " . $veh_cod . " LIMIT 1";
            break;

        case 21:
            // Detalle Diario para Reporte Individual
            $veh_cod = isset($Par_Sql['Veh_Cod']) ? (int)$Par_Sql['Veh_Cod'] : (isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0);
            $anio_mes = isset($Par_Sql['anio_mes']) ? $Par_Sql['anio_mes'] : '';
            $cho_cod = isset($Par_Sql['Cho_Cod']) ? $Par_Sql['Cho_Cod'] : '';
            
            $sql = "SELECT 
                        DAY(mh.Hor_Fec) as dia,
                        DATE(mh.Hor_Fec) as fecha,
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as operador,
                        mh.Hor_Ini as hor_inicial,
                        mh.Hor_Fin as hor_final,
                        IF(mh.Hor_Fin > 0, mh.Hor_Fin - mh.Hor_Ini, 0) as total_hrs,
                        IFNULL(mh.Hor_Set, 0) as descuento,
                        IF(mh.Hor_Fin > 0, (mh.Hor_Fin - mh.Hor_Ini) - CAST(IFNULL(mh.Hor_Set, 0) AS DECIMAL(10,2)), 0) as prod_hrs,
                        0 as combustible,
                        mh.Hor_Obs as observaciones,
                        mh.Cho_Cod
                    FROM maquinaria_horometro mh
                    INNER JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE mh.Veh_Cod = " . $veh_cod . "
                      AND DATE_FORMAT(mh.Hor_Fec, '%Y-%m') = '" . $anio_mes . "'";
            
            if (!empty($cho_cod) && $cho_cod != 'TODOS') {
                $sql .= " AND mh.Cho_Cod = " . (int)$cho_cod;
            }
            $sql .= " ORDER BY mh.Hor_Fec ASC";
            break;

        case 22:
            // Reporte Consolidado
            $emp_cod = isset($Par_Sql['Emp_Cod']) ? (int)$Par_Sql['Emp_Cod'] : (isset($Par_Sql[0]) ? (int)$Par_Sql[0] : 0);
            $anio_mes = isset($Par_Sql['anio_mes']) ? $Par_Sql['anio_mes'] : '';
            $veh_cod = isset($Par_Sql['Veh_Cod']) ? $Par_Sql['Veh_Cod'] : '';
            $cho_cod = isset($Par_Sql['Cho_Cod']) ? $Par_Sql['Cho_Cod'] : '';
            
            $sql = "SELECT 
                        v.Veh_Cod as veh_cod,
                        v.Veh_Pla as maquina,
                        mh.Cho_Cod as cho_cod,
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as operador,
                        SUM(IF(mh.Hor_Fin > 0, mh.Hor_Fin - mh.Hor_Ini, 0)) as horas_trabajadas,
                        SUM(IFNULL(mh.Hor_Set, 0)) as desfase,
                        SUM(IF(mh.Hor_Fin > 0, (mh.Hor_Fin - mh.Hor_Ini) - CAST(IFNULL(mh.Hor_Set, 0) AS DECIMAL(10,2)), 0)) as horas_productivas,
                        0 as combustible,
                        'Activo' as estado
                    FROM maquinaria_horometro mh
                    INNER JOIN vehiculo v ON v.Veh_Cod = mh.Veh_Cod
                    INNER JOIN chofer c ON c.Cho_Cod = mh.Cho_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    WHERE v.Emp_Cod = " . $emp_cod . "
                      AND DATE_FORMAT(mh.Hor_Fec, '%Y-%m') = '" . $anio_mes . "'";

            if (!empty($veh_cod) && $veh_cod != 'TODAS') {
                $sql .= " AND mh.Veh_Cod = " . (int)$veh_cod;
            }
            if (!empty($cho_cod) && $cho_cod != 'TODOS') {
                $sql .= " AND mh.Cho_Cod = " . (int)$cho_cod;
            }
            $sql .= " GROUP BY v.Veh_Cod, v.Veh_Pla, mh.Cho_Cod, operador ORDER BY v.Veh_Pla ASC";
            break;

        case 23:
            // Combustible para Reporte Individual (agrupado por fecha)
            $emp_cod = isset($Par_Sql['Emp_Cod']) ? (int)$Par_Sql['Emp_Cod'] : 0;
            $fecha_ini = isset($Par_Sql['fecha_ini']) ? $Par_Sql['fecha_ini'] : '';
            $fecha_fin = isset($Par_Sql['fecha_fin']) ? $Par_Sql['fecha_fin'] : '';
            $veh_cod = isset($Par_Sql['Veh_Cod']) ? $Par_Sql['Veh_Cod'] : '';
            
            $sql = "SELECT 
                        det.Veh_Cod,
                        DATE(det.Did_Fec) AS fecha,
                        SUM(IFNULL(det.Did_Can,0)) AS combustible_cargado,
                        SUM(IFNULL(det.Did_Can,0) * IFNULL(det.Did_Pun,0)) AS costo_combustible
                    FROM maquinaria_dispensador_det det
                    INNER JOIN maquinaria_dispensador d ON det.Dis_Cod = d.Dis_Cod
                    WHERE d.Emp_Cod = " . $emp_cod . "
                      AND det.Did_Tip = 'SA'
                      AND det.Did_Est = 'A'
                      AND DATE(det.Did_Fec) BETWEEN '" . $fecha_ini . "' AND '" . $fecha_fin . "'";
            
            if (!empty($veh_cod) && $veh_cod != 'TODAS') {
                $sql .= " AND det.Veh_Cod = " . (int)$veh_cod;
            }
            $sql .= " GROUP BY det.Veh_Cod, DATE(det.Did_Fec)";
            break;

        case 24:
            // Combustible para Reporte Consolidado (agrupado por maquina)
            $emp_cod = isset($Par_Sql['Emp_Cod']) ? (int)$Par_Sql['Emp_Cod'] : 0;
            $fecha_ini = isset($Par_Sql['fecha_ini']) ? $Par_Sql['fecha_ini'] : '';
            $fecha_fin = isset($Par_Sql['fecha_fin']) ? $Par_Sql['fecha_fin'] : '';
            $veh_cod = isset($Par_Sql['Veh_Cod']) ? $Par_Sql['Veh_Cod'] : '';
            
            $sql = "SELECT 
                        det.Veh_Cod,
                        SUM(IFNULL(det.Did_Can,0)) AS combustible_cargado,
                        SUM(IFNULL(det.Did_Can,0) * IFNULL(det.Did_Pun,0)) AS costo_combustible
                    FROM maquinaria_dispensador_det det
                    INNER JOIN maquinaria_dispensador d ON det.Dis_Cod = d.Dis_Cod
                    WHERE d.Emp_Cod = " . $emp_cod . "
                      AND det.Did_Tip = 'SA'
                      AND det.Did_Est = 'A'
                      AND DATE(det.Did_Fec) BETWEEN '" . $fecha_ini . "' AND '" . $fecha_fin . "'";
            
            if (!empty($veh_cod) && $veh_cod != 'TODAS') {
                $sql .= " AND det.Veh_Cod = " . (int)$veh_cod;
            }
            $sql .= " GROUP BY det.Veh_Cod";
            break;
    }
    return $sql;
}
?>
