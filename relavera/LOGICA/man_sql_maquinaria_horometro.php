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
            // Obtener catálogo de maquinaria (vehículos) asociados a la planta del usuario
            $sql = "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Mar 
                    FROM manifiesto_vehiculo mv 
                    INNER JOIN vehiculo v ON v.Veh_Cod = mv.Veh_Cod 
                    WHERE v.Veh_Est = 'A' AND mv.Pla_Cod = " . (int)$Par_Sql['Pla_Cod'] . "
                    ORDER BY v.Veh_Pla ASC";
            break;

        case 2:
            // Obtener catálogo de operadores (choferes) asociados a la planta del usuario
            $sql = "SELECT c.Cho_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as nombre, p.Prs_Ced 
                    FROM manifiesto_chofer mc 
                    INNER JOIN chofer c ON c.Cho_Cod = mc.Cho_Cod 
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod 
                    WHERE c.Cho_Est = 'A' AND mc.Pla_Cod = " . (int)$Par_Sql['Pla_Cod'] . "
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

        case 8:
            // CONFIGURACIÓN MANTENIMIENTO: Insertar o actualizar frecuencia
            if ($Par_Sql['op'] == 'insert') {
                $sql = "INSERT INTO maquinaria_config_mantenimiento (Veh_Cod, Cma_Hrs_Fco, Cma_Hrs_Ult, Cma_Est)
                        VALUES (" . (int)$Par_Sql[0] . ", " . (float)$Par_Sql[1] . ", " . (float)$Par_Sql[2] . ", 'A')";
            } else if ($Par_Sql['op'] == 'update') {
                $sql = "UPDATE maquinaria_config_mantenimiento 
                        SET Cma_Hrs_Fco = " . (float)$Par_Sql[1] . ", Cma_Hrs_Ult = " . (float)$Par_Sql[2] . " 
                        WHERE Veh_Cod = " . (int)$Par_Sql[0];
            } else {
                $sql = "SELECT * FROM maquinaria_config_mantenimiento WHERE Veh_Cod = " . (int)$Par_Sql[0] . " LIMIT 1";
            }
            break;

        case 9:
            // REGISTRAR BITÁCORA MANTENIMIENTO REALIZADO
            $sql = "INSERT INTO maquinaria_historial_mantenimiento (Veh_Cod, Usu_Cod, Hma_Fec, Hma_Hor, Hma_Det, Hma_Res)
                    VALUES (" . (int)$Par_Sql[0] . ", " . (int)$Par_Sql[1] . ", '" . $Par_Sql[2] . "', " . (float)$Par_Sql[3] . ", '" . addslashes($Par_Sql[4]) . "', '" . addslashes($Par_Sql[5]) . "')";
            break;

        case 10:
            // ALERTAS MANTENIMIENTO PREVENTIVO (Cálculo horas restantes)
            $sql = "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Mar, cm.Cma_Hrs_Fco, cm.Cma_Hrs_Ult,
                           COALESCE((SELECT MAX(mh.Hor_Fin) FROM maquinaria_horometro mh WHERE mh.Veh_Cod = v.Veh_Cod AND mh.Hor_Est = 'A'), 0) as lectura_actual
                    FROM vehiculo v
                    INNER JOIN maquinaria_config_mantenimiento cm ON cm.Veh_Cod = v.Veh_Cod
                    WHERE cm.Cma_Est = 'A' AND v.Emp_Cod = " . (int)$Par_Sql[0] . "
                    ORDER BY (cm.Cma_Hrs_Ult + cm.Cma_Hrs_Fco) ASC";
            break;

        case 11:
            // Listar Historial de Mantenimientos por Máquina
            $sql = "SELECT hm.*, u.Usu_Nom
                    FROM maquinaria_historial_mantenimiento hm
                    LEFT JOIN usuarios u ON u.Usu_Cod = hm.Usu_Cod
                    WHERE hm.Veh_Cod = " . (int)$Par_Sql[0] . "
                    ORDER BY hm.Hma_Fec DESC, hm.Hma_Cod DESC";
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
    }
    return $sql;
}
