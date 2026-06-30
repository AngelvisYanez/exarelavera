<?php
/**
 * Sentencias SQL para el módulo de Dispensadores de Combustible
 */
function sentencias_maquinaria_dispensador($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        // ===============================================
        // FASE 1: DISPENSADORES
        // ===============================================
        case 1:
            // Listar Dispensadores para jqGrid
            $search = "";
            if (!empty($Par_Sql['search'])) {
                $searchTerm = addslashes($Par_Sql['search']);
                $search = " AND (Dis_Nom LIKE '%$searchTerm%')";
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) as total 
                        FROM maquinaria_dispensador 
                        WHERE Emp_Cod = " . (int)$Par_Sql[0] . " $search";
            } else {
                $sql = "SELECT Dis_Cod, Dis_Nom, Dis_Cap, Dis_Tip, Dis_Uni, Dis_Est, Dis_Sys
                        FROM maquinaria_dispensador
                        WHERE Emp_Cod = " . (int)$Par_Sql[0] . " $search
                        ORDER BY Dis_Nom ASC " . $Par_Sql['limits'];
            }
            break;

        case 2:
            // Insertar Dispensador
            $sql = "INSERT INTO maquinaria_dispensador (Emp_Cod, Usu_Cod, Dis_Nom, Dis_Cap, Dis_Tip, Dis_Uni, Dis_Est)
                    VALUES (" . (int)$Par_Sql[0] . ", " . (int)$Par_Sql[1] . ", '" . addslashes($Par_Sql[2]) . "', " . (float)$Par_Sql[3] . ", '" . addslashes($Par_Sql[4]) . "', '" . addslashes($Par_Sql[5]) . "', 'A')";
            break;

        case 3:
            // Actualizar Dispensador
            $sql = "UPDATE maquinaria_dispensador 
                    SET Dis_Nom = '" . addslashes($Par_Sql[1]) . "',
                        Dis_Cap = " . (float)$Par_Sql[2] . ",
                        Dis_Tip = '" . addslashes($Par_Sql[3]) . "',
                        Dis_Uni = '" . addslashes($Par_Sql[4]) . "'
                    WHERE Dis_Cod = " . (int)$Par_Sql[0];
            break;

        case 4:
            // Cambiar Estado Dispensador (Inactivar/Activar)
            $sql = "UPDATE maquinaria_dispensador 
                    SET Dis_Est = '" . $Par_Sql[1] . "' 
                    WHERE Dis_Cod = " . (int)$Par_Sql[0];
            break;
            
        case 5:
            // Obtener datos de un Dispensador por ID
            $sql = "SELECT * FROM maquinaria_dispensador WHERE Dis_Cod = " . (int)$Par_Sql[0] . " LIMIT 1";
            break;

        // ===============================================
        // FASE 2: INGRESOS DE COMBUSTIBLE
        // ===============================================
        case 6:
            // Listar Ingresos para jqGrid
            $where = "md.Did_Tip IN ('IN', 'IC') AND d.Emp_Cod = " . (int)$Par_Sql[0];
            
            if (!empty($Par_Sql['fec_ini'])) {
                $where .= " AND md.Did_Fec >= '" . addslashes($Par_Sql['fec_ini']) . " 00:00:00'";
            }
            if (!empty($Par_Sql['fec_fin'])) {
                $where .= " AND md.Did_Fec <= '" . addslashes($Par_Sql['fec_fin']) . " 23:59:59'";
            }
            if (!empty($Par_Sql['Dis_Cod'])) {
                $where .= " AND md.Dis_Cod = " . (int)$Par_Sql['Dis_Cod'];
            }
            if (!empty($Par_Sql['Prv_Cod'])) {
                $where .= " AND md.Prv_Cod = " . (int)$Par_Sql['Prv_Cod'];
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(1) as total
                        FROM maquinaria_dispensador_det md
                        INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod
                        LEFT JOIN proveedore pv ON md.Prv_Cod = pv.Prv_Cod
                        LEFT JOIN persona p_pv ON pv.Prs_Cod = p_pv.Prs_Cod
                        LEFT JOIN vehiculo v ON md.Veh_Cod = v.Veh_Cod
                        WHERE $where";
            } else {
                $sql = "SELECT 
                            md.Did_Cod,
                            md.Did_Tip,
                            md.Did_Fec,
                            md.Dis_Cod,
                            d.Dis_Nom,
                            md.Prv_Cod,
                            IF(p_pv.Prs_Nom=p_pv.Prs_Ape, p_pv.Prs_Nom, CONCAT(p_pv.Prs_Nom, ' ', p_pv.Prs_Ape)) as proveedor_nombre,
                            md.Veh_Cod,
                            CONCAT(v.Veh_Pla, ' ', IFNULL(v.Veh_Mar, '')) as vehiculo_nombre,
                            md.Did_Can,
                            md.Did_Pun,
                            (md.Did_Can * md.Did_Pun) as total_calculado,
                            md.Usu_Cod,
                            md.Did_Est
                        FROM maquinaria_dispensador_det md
                        INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod
                        LEFT JOIN proveedore pv ON md.Prv_Cod = pv.Prv_Cod
                        LEFT JOIN persona p_pv ON pv.Prs_Cod = p_pv.Prs_Cod
                        LEFT JOIN vehiculo v ON md.Veh_Cod = v.Veh_Cod
                        WHERE $where
                        ORDER BY md.Did_Cod DESC " . $Par_Sql['limits'];
            }
            break;

        case 7:
            // Obtener combo Dispensadores activos
            $sql = "SELECT Dis_Cod, Dis_Nom, Dis_Tip, Dis_Uni, Dis_Cap 
                    FROM maquinaria_dispensador 
                    WHERE Emp_Cod = " . (int)$Par_Sql[0] . " AND Dis_Est = 'A'
                    ORDER BY Dis_Nom ASC";
            break;

        case 8:
            // Obtener combo Proveedores activos
            $sql = "SELECT DISTINCT pv.Prv_Cod, IF(p.Prs_Nom=p.Prs_Ape, p.Prs_Nom, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape)) as proveedor_nombre
                    FROM proveedore pv
                    INNER JOIN persona p ON pv.Prs_Cod = p.Prs_Cod
                    WHERE pv.Prv_Est = 'A' AND p.Emp_Cod = " . (int)$Par_Sql[0] . "
                    ORDER BY proveedor_nombre ASC";
            break;

        case 9:
            // Obtener capacidad y existencia actual
            $sql = "SELECT 
                        d.Dis_Cod, d.Dis_Tip, d.Dis_Uni, d.Dis_Cap,
                        IFNULL((
                            SELECT 
                                IFNULL(SUM(CASE WHEN m.Did_Tip IN ('IN', 'IC') THEN m.Did_Can ELSE 0 END), 0) - 
                                IFNULL(SUM(CASE WHEN m.Did_Tip IN ('SA', 'SC') THEN m.Did_Can ELSE 0 END), 0)
                            FROM maquinaria_dispensador_det m
                            WHERE m.Dis_Cod = d.Dis_Cod AND m.Did_Est = 'A'
                        ), 0) as existencia
                    FROM maquinaria_dispensador d
                    WHERE d.Emp_Cod = " . (int)$Par_Sql[0] . " AND d.Dis_Cod = " . (int)$Par_Sql[1];
            break;

        case 10:
            // Guardar Ingreso
            $prv_val = empty($Par_Sql[1]) ? "NULL" : (int)$Par_Sql[1];
            $veh_val = empty($Par_Sql[7]) ? "NULL" : (int)$Par_Sql[7];

            $sql = "INSERT INTO maquinaria_dispensador_det 
                    (Dis_Cod, Prv_Cod, Veh_Cod, Usu_Cod, Did_Tip, Did_Can, Did_Fec, Did_Pun, Did_Est, Did_Sys, Cho_Cod) 
                    VALUES (" . (int)$Par_Sql[0] . ", " . $prv_val . ", " . $veh_val . ", " . (int)$Par_Sql[2] . ", '" . addslashes($Par_Sql[6]) . "', " . (float)$Par_Sql[3] . ", '" . addslashes($Par_Sql[4]) . "', " . (float)$Par_Sql[5] . ", 'A', NOW(), NULL)";
            break;

        case 11:
            // Anular Ingreso
            $sql = "UPDATE maquinaria_dispensador_det 
                    SET Did_Est = 'I' 
                    WHERE Did_Cod = " . (int)$Par_Sql[0] . " AND Did_Tip IN ('IN', 'IC')";
            break;

        case 12:
            // Obtener combo Vehículos activos (excluyendo VM)
            $sql = "SELECT DISTINCT Veh_Cod, CONCAT(Veh_Pla, ' ', IFNULL(Veh_Mar, '')) as vehiculo_nombre
                    FROM vehiculo
                    WHERE Emp_Cod = " . (int)$Par_Sql[0] . " AND Veh_Est = 'A' AND (Veh_Tip != 'VM' OR Veh_Tip IS NULL)
                    ORDER BY vehiculo_nombre ASC";
            break;
        case 13:
            // Listar Despachos/Salidas para jqGrid
            $where = "md.Did_Tip IN ('SA', 'SC') AND d.Emp_Cod = " . (int)$Par_Sql[0];
            
            if (!empty($Par_Sql['fec_ini'])) {
                $where .= " AND md.Did_Fec >= '" . addslashes($Par_Sql['fec_ini']) . " 00:00:00'";
            }
            if (!empty($Par_Sql['fec_fin'])) {
                $where .= " AND md.Did_Fec <= '" . addslashes($Par_Sql['fec_fin']) . " 23:59:59'";
            }
            if (!empty($Par_Sql['Dis_Cod'])) {
                $where .= " AND md.Dis_Cod = " . (int)$Par_Sql['Dis_Cod'];
            }
            if (!empty($Par_Sql['Veh_Cod'])) {
                $where .= " AND md.Veh_Cod = " . (int)$Par_Sql['Veh_Cod'];
            }
            if (!empty($Par_Sql['Did_Tip'])) {
                $where .= " AND md.Did_Tip = '" . addslashes($Par_Sql['Did_Tip']) . "'";
            }

            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(1) as total
                        FROM maquinaria_dispensador_det md
                        INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod
                        LEFT JOIN vehiculo v ON md.Veh_Cod = v.Veh_Cod
                        WHERE $where";
            } else {
                $sql = "SELECT 
                            md.Did_Cod,
                            md.Did_Tip,
                            md.Did_Fec,
                            md.Dis_Cod,
                            d.Dis_Nom,
                            md.Veh_Cod,
                            CONCAT(v.Veh_Pla, ' ', IFNULL(v.Veh_Mar, '')) as vehiculo_nombre,
                            md.Did_Can,
                            md.Did_Pun,
                            (md.Did_Can * md.Did_Pun) as total_calculado,
                            md.Did_Obs,
                            md.Usu_Cod,
                            md.Did_Est
                        FROM maquinaria_dispensador_det md
                        INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod
                        LEFT JOIN vehiculo v ON md.Veh_Cod = v.Veh_Cod
                        WHERE $where
                        ORDER BY md.Did_Cod DESC " . $Par_Sql['limits'];
            }
            break;

        case 14:
            // Guardar Salida
            $veh_val = empty($Par_Sql[1]) ? "NULL" : (int)$Par_Sql[1];
            $obs_val = empty($Par_Sql[7]) ? "NULL" : "'" . addslashes($Par_Sql[7]) . "'";

            $sql = "INSERT INTO maquinaria_dispensador_det 
                    (Dis_Cod, Veh_Cod, Usu_Cod, Did_Tip, Did_Can, Did_Fec, Did_Pun, Did_Est, Did_Sys, Did_Obs) 
                    VALUES (" . (int)$Par_Sql[0] . ", " . $veh_val . ", " . (int)$Par_Sql[2] . ", '" . addslashes($Par_Sql[6]) . "', " . (float)$Par_Sql[3] . ", '" . addslashes($Par_Sql[4]) . "', " . (float)$Par_Sql[5] . ", 'A', NOW(), " . $obs_val . ")";
            break;

        case 15:
            // Anular Salida
            $sql = "UPDATE maquinaria_dispensador_det 
                    SET Did_Est = 'I' 
                    WHERE Did_Cod = " . (int)$Par_Sql[0] . " AND Did_Tip IN ('SA', 'SC')";
            break;
            
        case 16:
            // Listar Ajustes
            $where = "d.Emp_Cod = " . (int)$Par_Sql[0] . " AND md.Did_Tip IN ('IC', 'SC') ";
            if (!empty($Par_Sql['fec_ini'])) {
                $where .= " AND DATE(md.Did_Fec) >= '" . addslashes($Par_Sql['fec_ini']) . "' ";
            }
            if (!empty($Par_Sql['fec_fin'])) {
                $where .= " AND DATE(md.Did_Fec) <= '" . addslashes($Par_Sql['fec_fin']) . "' ";
            }
            if (!empty($Par_Sql['Dis_Cod'])) {
                $where .= " AND md.Dis_Cod = '" . addslashes($Par_Sql['Dis_Cod']) . "' ";
            }
            if (!empty($Par_Sql['Did_Tip'])) {
                $where .= " AND md.Did_Tip = '" . addslashes($Par_Sql['Did_Tip']) . "' ";
            }
            
            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(DISTINCT md.Did_Cod) AS total FROM maquinaria_dispensador_det md INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod WHERE " . $where;
            } else {
                $sql = "SELECT md.Did_Cod, md.Dis_Cod, d.Dis_Nom, md.Did_Tip, md.Did_Fec, md.Did_Can, md.Did_Obs, md.Usu_Cod, CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) as usuario_nombre, md.Did_Est FROM maquinaria_dispensador_det md INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod LEFT JOIN usuarios u ON md.Usu_Cod = u.Usu_Cod LEFT JOIN persona pu ON u.Prs_Cod = pu.Prs_Cod WHERE " . $where . " ORDER BY md.Did_Cod DESC " . $Par_Sql['limits'];
            }
            break;

        case 17:
            // Listar Kardex
            $where = "d.Emp_Cod = " . (int)$Par_Sql[0] . " AND md.Did_Tip IN ('IN', 'IC', 'SA', 'SC') AND md.Did_Est = 'A' ";
            if (!empty($Par_Sql['fec_ini'])) {
                $where .= " AND DATE(md.Did_Fec) >= '" . addslashes($Par_Sql['fec_ini']) . "' ";
            }
            if (!empty($Par_Sql['fec_fin'])) {
                $where .= " AND DATE(md.Did_Fec) <= '" . addslashes($Par_Sql['fec_fin']) . "' ";
            }
            if (!empty($Par_Sql['Dis_Cod'])) {
                $where .= " AND md.Dis_Cod = '" . addslashes($Par_Sql['Dis_Cod']) . "' ";
            }
            if (!empty($Par_Sql['Did_Tip'])) {
                $where .= " AND md.Did_Tip = '" . addslashes($Par_Sql['Did_Tip']) . "' ";
            }
            
            $sql = "SELECT md.Did_Cod, md.Dis_Cod, d.Dis_Nom, md.Did_Tip, md.Did_Fec, md.Did_Can, md.Did_Pun, md.Did_Obs, md.Veh_Cod, CONCAT(v.Veh_Pla, ' ', IFNULL(v.Veh_Mar, '')) as vehiculo_nombre, md.Prv_Cod, IF(p_pv.Prs_Nom=p_pv.Prs_Ape, p_pv.Prs_Nom, CONCAT(p_pv.Prs_Nom, ' ', p_pv.Prs_Ape)) as proveedor_nombre, CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) as usuario_nombre, md.Did_Est FROM maquinaria_dispensador_det md INNER JOIN maquinaria_dispensador d ON md.Dis_Cod = d.Dis_Cod LEFT JOIN vehiculo v ON md.Veh_Cod = v.Veh_Cod LEFT JOIN proveedore pv ON md.Prv_Cod = pv.Prv_Cod LEFT JOIN persona p_pv ON pv.Prs_Cod = p_pv.Prs_Cod LEFT JOIN usuarios u ON md.Usu_Cod = u.Usu_Cod LEFT JOIN persona pu ON u.Prs_Cod = pu.Prs_Cod WHERE " . $where . " ORDER BY md.Did_Fec ASC, md.Did_Cod ASC";
            break;
        case 18:
            // Listar Cierres
            $where = "c.Emp_Cod = " . (int)$Par_Sql[0];
            if (!empty($Par_Sql['fec_ini'])) {
                $where .= " AND c.Cie_Fec >= '" . addslashes($Par_Sql['fec_ini']) . "' ";
            }
            if (!empty($Par_Sql['fec_fin'])) {
                $where .= " AND c.Cie_Fec <= '" . addslashes($Par_Sql['fec_fin']) . "' ";
            }
            if (!empty($Par_Sql['Dis_Cod'])) {
                $where .= " AND c.Dis_Cod = '" . addslashes($Par_Sql['Dis_Cod']) . "' ";
            }
            if (!empty($Par_Sql['Cie_Estado'])) {
                $where .= " AND c.Cie_Estado = '" . addslashes($Par_Sql['Cie_Estado']) . "' ";
            }
            
            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(1) AS total FROM maquinaria_dispensador_cierre c WHERE " . $where;
            } else {
                $sql = "SELECT c.Cie_Cod, c.Cie_Fec, d.Dis_Nom, c.Cie_Ini, c.Cie_Ing, c.Cie_Sal, c.Cie_Teo, c.Cie_Fis, c.Cie_Dif, c.Cie_Estado, c.Cie_Obs, c.Cie_Est, CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) as usuario_nombre 
                        FROM maquinaria_dispensador_cierre c 
                        INNER JOIN maquinaria_dispensador d ON c.Dis_Cod = d.Dis_Cod 
                        LEFT JOIN usuarios u ON c.Usu_Cod = u.Usu_Cod 
                        LEFT JOIN persona pu ON u.Prs_Cod = pu.Prs_Cod 
                        WHERE " . $where . " ORDER BY c.Cie_Fec DESC, c.Cie_Cod DESC " . $Par_Sql['limits'];
            }
            break;

        case 19:
            // Obtener cálculo previo del Cierre Diario (Cie_Ini, Cie_Ing, Cie_Sal)
            $dis_cod = (int)$Par_Sql[1];
            $fecha = addslashes($Par_Sql[2]);
            
            $sql = "SELECT 
                        (SELECT IFNULL(SUM(CASE WHEN Did_Tip IN ('IN', 'IC') THEN Did_Can ELSE 0 END) - SUM(CASE WHEN Did_Tip IN ('SA', 'SC') THEN Did_Can ELSE 0 END), 0) 
                         FROM maquinaria_dispensador_det 
                         WHERE Dis_Cod = $dis_cod AND Did_Est = 'A' AND DATE(Did_Fec) < '$fecha') as cie_ini,
                        
                        (SELECT IFNULL(SUM(Did_Can), 0) 
                         FROM maquinaria_dispensador_det 
                         WHERE Dis_Cod = $dis_cod AND Did_Est = 'A' AND Did_Tip IN ('IN', 'IC') AND DATE(Did_Fec) = '$fecha') as cie_ing,
                         
                        (SELECT IFNULL(SUM(Did_Can), 0) 
                         FROM maquinaria_dispensador_det 
                         WHERE Dis_Cod = $dis_cod AND Did_Est = 'A' AND Did_Tip IN ('SA', 'SC') AND DATE(Did_Fec) = '$fecha') as cie_sal,
                         
                        (SELECT COUNT(1) 
                         FROM maquinaria_dispensador_cierre 
                         WHERE Dis_Cod = $dis_cod AND Cie_Fec = '$fecha' AND Cie_Est = 'A') as existe_cierre
                    ";
            break;

        case 20:
            // Guardar Cierre Diario
            $sql = "INSERT INTO maquinaria_dispensador_cierre 
                    (Emp_Cod, Dis_Cod, Usu_Cod, Cie_Fec, Cie_Ini, Cie_Ing, Cie_Sal, Cie_Teo, Cie_Fis, Cie_Dif, Cie_Estado, Cie_Obs, Cie_Est, Cie_Sys) 
                    VALUES (
                        " . (int)$Par_Sql[0] . ",
                        " . (int)$Par_Sql[1] . ",
                        " . (int)$Par_Sql[2] . ",
                        '" . addslashes($Par_Sql[3]) . "',
                        " . (float)$Par_Sql[4] . ",
                        " . (float)$Par_Sql[5] . ",
                        " . (float)$Par_Sql[6] . ",
                        " . (float)$Par_Sql[7] . ",
                        " . (float)$Par_Sql[8] . ",
                        " . (float)$Par_Sql[9] . ",
                        '" . addslashes($Par_Sql[10]) . "',
                        '" . addslashes($Par_Sql[11]) . "',
                        'A',
                        NOW()
                    )";
            break;

        case 21:
            // Anular Cierre
            $sql = "UPDATE maquinaria_dispensador_cierre SET Cie_Est = 'I' WHERE Cie_Cod = " . (int)$Par_Sql[0];
            break;

        // ==========================================
        // DASHBOARD EJECUTIVO (FASE 6)
        // ==========================================
        case 22:
            // Resumen General: Dispensadores, Existencia Total
            $sql = "SELECT 
                        COUNT(d.Dis_Cod) as total_dispensadores,
                        SUM(d.existencia) as existencia_total,
                        (SELECT MAX(Cie_Fec) FROM maquinaria_dispensador_cierre WHERE Cie_Est = 'A' AND Emp_Cod = " . (int)$Par_Sql[0] . ") as ultimo_cierre
                    FROM maquinaria_dispensador d
                    WHERE d.Dis_Est = 'A' AND d.Emp_Cod = " . (int)$Par_Sql[0];
            break;

        case 23:
            // Movimientos: Mes y Día
            // Par_Sql: 0=>Emp_Cod, 1=>Fecha_Inicio, 2=>Fecha_Fin, 3=>Dis_Cod(opcional), 4=>Combustible(opcional), 5=>Fecha_Hoy
            $filtro = " AND m.Emp_Cod = " . (int)$Par_Sql[0];
            if (!empty($Par_Sql[3])) $filtro .= " AND m.Dis_Cod = " . (int)$Par_Sql[3];
            if (!empty($Par_Sql[4])) $filtro .= " AND d.Dis_Com = '" . addslashes($Par_Sql[4]) . "'";
            
            $sql = "SELECT 
                        SUM(CASE WHEN m.Did_Tip IN ('IN','IC') AND DATE(m.Did_Fec) BETWEEN '" . addslashes($Par_Sql[1]) . "' AND '" . addslashes($Par_Sql[2]) . "' THEN m.Did_Can ELSE 0 END) as ingresos_mes,
                        SUM(CASE WHEN m.Did_Tip IN ('SA','SC') AND DATE(m.Did_Fec) BETWEEN '" . addslashes($Par_Sql[1]) . "' AND '" . addslashes($Par_Sql[2]) . "' THEN m.Did_Can ELSE 0 END) as despachos_mes,
                        SUM(CASE WHEN m.Did_Tip = 'SA' AND DATE(m.Did_Fec) = '" . addslashes($Par_Sql[5]) . "' THEN m.Did_Can ELSE 0 END) as consumo_dia,
                        SUM(CASE WHEN m.Did_Tip = 'IN' AND DATE(m.Did_Fec) = '" . addslashes($Par_Sql[5]) . "' THEN m.Did_Can ELSE 0 END) as in_dia,
                        SUM(CASE WHEN m.Did_Tip = 'IC' AND DATE(m.Did_Fec) = '" . addslashes($Par_Sql[5]) . "' THEN m.Did_Can ELSE 0 END) as ic_dia,
                        SUM(CASE WHEN m.Did_Tip = 'SC' AND DATE(m.Did_Fec) = '" . addslashes($Par_Sql[5]) . "' THEN m.Did_Can ELSE 0 END) as sc_dia
                    FROM maquinaria_dispensador_movimiento m
                    LEFT JOIN maquinaria_dispensador d ON m.Dis_Cod = d.Dis_Cod
                    WHERE m.Did_Est = 'A' " . $filtro;
            break;

        case 24:
            // Estado de Dispensadores (Tarjetas)
            $filtro = "";
            if (!empty($Par_Sql[1])) $filtro .= " AND d.Dis_Cod = " . (int)$Par_Sql[1];
            if (!empty($Par_Sql[2])) $filtro .= " AND d.Dis_Com = '" . addslashes($Par_Sql[2]) . "'";
            $sql = "SELECT d.Dis_Cod, d.Dis_Nom, d.Dis_Com, d.Dis_Cap, d.existencia, d.Dis_Uni, 
                           ROUND((d.existencia / d.Dis_Cap) * 100, 2) as pct_usado 
                    FROM maquinaria_dispensador d 
                    WHERE d.Dis_Est = 'A' AND d.Emp_Cod = " . (int)$Par_Sql[0] . $filtro;
            break;

        case 25:
            // Cierres
            $filtro = "";
            if (!empty($Par_Sql[1])) $filtro .= " AND Dis_Cod = " . (int)$Par_Sql[1];
            $sql = "SELECT Cie_Fec, Cie_Estado, Cie_Dif 
                    FROM maquinaria_dispensador_cierre 
                    WHERE Cie_Est = 'A' AND Emp_Cod = " . (int)$Par_Sql[0] . $filtro . " 
                    ORDER BY Cie_Fec DESC, Cie_Cod DESC LIMIT 1";
            break;

        case 26:
            // Top Maquinarias
            $filtro = " AND m.Emp_Cod = " . (int)$Par_Sql[0];
            if (!empty($Par_Sql[3])) $filtro .= " AND m.Dis_Cod = " . (int)$Par_Sql[3];
            if (!empty($Par_Sql[4])) $filtro .= " AND d.Dis_Com = '" . addslashes($Par_Sql[4]) . "'";
            $sql = "SELECT v.vehiculo_nombre, SUM(m.Did_Can) as consumo, u.Usu_Ape, u.Usu_Nom
                    FROM maquinaria_dispensador_movimiento m
                    LEFT JOIN vehiculo v ON m.Veh_Cod = v.vehiculo_codigo
                    LEFT JOIN usuarios u ON m.Usu_Cod = u.Usu_Cod
                    LEFT JOIN maquinaria_dispensador d ON m.Dis_Cod = d.Dis_Cod
                    WHERE m.Did_Est = 'A' AND m.Did_Tip = 'SA' 
                      AND DATE(m.Did_Fec) BETWEEN '" . addslashes($Par_Sql[1]) . "' AND '" . addslashes($Par_Sql[2]) . "'
                      " . $filtro . "
                    GROUP BY m.Veh_Cod, v.vehiculo_nombre, u.Usu_Ape, u.Usu_Nom
                    ORDER BY consumo DESC LIMIT 5";
            break;

        case 27:
            // Gráfico Consumo Diario
            $filtro = " AND m.Emp_Cod = " . (int)$Par_Sql[0];
            if (!empty($Par_Sql[3])) $filtro .= " AND m.Dis_Cod = " . (int)$Par_Sql[3];
            if (!empty($Par_Sql[4])) $filtro .= " AND d.Dis_Com = '" . addslashes($Par_Sql[4]) . "'";
            $sql = "SELECT DATE(m.Did_Fec) as fecha, SUM(m.Did_Can) as consumo
                    FROM maquinaria_dispensador_movimiento m
                    LEFT JOIN maquinaria_dispensador d ON m.Dis_Cod = d.Dis_Cod
                    WHERE m.Did_Est = 'A' AND m.Did_Tip = 'SA' 
                      AND DATE(m.Did_Fec) BETWEEN '" . addslashes($Par_Sql[1]) . "' AND '" . addslashes($Par_Sql[2]) . "'
                      " . $filtro . "
                    GROUP BY DATE(m.Did_Fec)
                    ORDER BY fecha ASC";
            break;
    }
    return $sql;
}
?>
