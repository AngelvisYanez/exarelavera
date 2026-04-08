<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Sistema
 * @version 1.0
 * Fecha de actualización:	2025-12-31
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */

function sentencias_gestion_periodos($id, $Par_Sql) {
    $sql = "";
    switch($id)
    {
        /* Consulta empresas activas con su base de datos (desde exa_master) */
        case 1:
            $sql = "SELECT e.Emp_Cod, e.Emp_Nom, e.Emp_Ruc, e.Emp_Cor, e.Emp_Est, d.Dat_Dis
                    FROM empresas e
                    LEFT JOIN data d ON e.Emp_Cod = d.Emp_Cod
                    WHERE e.Emp_Est = 'A' 
                    ORDER BY e.Emp_Nom";
            return $sql;
            
        /**
         * Obtener contadores y regímenes de empresas (desde base distribuida)
         * Par_Sql = array de códigos de empresas (viene como array después del explode)
         */
        case 2:
            if (empty($Par_Sql)) {
                return "SELECT 0 AS Emp_Cod, '' AS Emp_Con, '' AS Cof_Rim WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $Par_Sql);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "SELECT e.Emp_Cod, e.Emp_Con, COALESCE(cf.Cof_Rim, '') as Cof_Rim
                    FROM empresas e
                    LEFT JOIN confi_fact cf ON e.Emp_Cod = cf.Emp_Cod
                    WHERE e.Emp_Cod IN ($emp_cods_str)";
            return $sql;
            
        /**
         * Verificar períodos contables actuales (desde base distribuida)
         * Par_Sql = array donde los primeros elementos son códigos de empresas, luego fecha_inicial y fecha_final
         */
        case 3:
            if (count($Par_Sql) < 3) {
                return "SELECT 0 AS Emp_Cod WHERE 1=0";
            }
            // Los últimos dos elementos son las fechas
            $fecha_final = addslashes($Par_Sql[count($Par_Sql) - 1]);
            $fecha_inicial = addslashes($Par_Sql[count($Par_Sql) - 2]);
            // El resto son los códigos de empresas (todos excepto los últimos dos)
            $emp_cods = array_slice($Par_Sql, 0, -2);
            if (empty($emp_cods)) {
                return "SELECT 0 AS Emp_Cod WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $emp_cods);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "SELECT DISTINCT pl.Emp_Cod
                    FROM perio_cont pc
                    INNER JOIN plan_cuenta pl ON pc.Pla_Cod = pl.Pla_Cod
                    WHERE pl.Emp_Cod IN ($emp_cods_str)
                      AND pc.Pec_Fei = '$fecha_inicial'
                      AND pc.Pec_Fef = '$fecha_final'
                      AND pc.Pec_Est = 'A'
                      AND pl.Pla_Est = 'A'";
            return $sql;
            
        /**
         * Verificar períodos contables anteriores (desde base distribuida)
         * Par_Sql = array donde los primeros elementos son códigos de empresas, luego fecha_inicial_anterior y fecha_final_anterior
         */
        case 4:
            if (count($Par_Sql) < 3) {
                return "SELECT 0 AS Emp_Cod WHERE 1=0";
            }
            // Los últimos dos elementos son las fechas
            $fecha_final = addslashes($Par_Sql[count($Par_Sql) - 1]);
            $fecha_inicial = addslashes($Par_Sql[count($Par_Sql) - 2]);
            // El resto son los códigos de empresas (todos excepto los últimos dos)
            $emp_cods = array_slice($Par_Sql, 0, -2);
            if (empty($emp_cods)) {
                return "SELECT 0 AS Emp_Cod WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $emp_cods);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "SELECT DISTINCT pl.Emp_Cod
                    FROM perio_cont pc
                    INNER JOIN plan_cuenta pl ON pc.Pla_Cod = pl.Pla_Cod
                    WHERE pl.Emp_Cod IN ($emp_cods_str)
                      AND pc.Pec_Fei = '$fecha_inicial'
                      AND pc.Pec_Fef = '$fecha_final'
                      AND pc.Pec_Est = 'A'
                      AND pl.Pla_Est = 'A'";
            return $sql;
            
        /**
         * Obtener base distribuida de una empresa (desde exa_master)
         * Par_Sql[0] = Emp_Cod
         */
        case 5:
            $emp_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $sql = "SELECT Dat_Dis FROM data WHERE Emp_Cod = $emp_cod";
            return $sql;
            
        /**
         * Obtener bases distribuidas de múltiples empresas (desde exa_master)
         * Par_Sql = array de códigos de empresas
         */
        case 6:
            if (empty($Par_Sql)) {
                return "SELECT 0 AS Emp_Cod, '' AS Dat_Dis WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $Par_Sql);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "SELECT Emp_Cod, Dat_Dis FROM data WHERE Emp_Cod IN ($emp_cods_str)";
            return $sql;
            
        /* Obtener régimen de una empresa (desde base distribuida) */
        case 7:
            $emp_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $sql = "SELECT Emp_Cod, Cof_Rim AS Tipo_Regimen 
                   FROM confi_fact 
                   WHERE Emp_Cod = $emp_cod";
            return $sql;
            
        /* Verificar que las empresas existan en una base distribuida */
        case 8:
            if (empty($Par_Sql)) {
                return "SELECT 0 AS Emp_Cod WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $Par_Sql);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "SELECT Emp_Cod FROM empresas WHERE Emp_Cod IN ($emp_cods_str)";
            return $sql;
            
        /**
         * Insertar períodos contables (en base distribuida)
         */
        case 9:
            if (count($Par_Sql) < 3) {
                return "SELECT 0 WHERE 1=0";
            }
            // Los primeros dos elementos son las fechas
            $fecha_inicial = addslashes($Par_Sql[0]);
            $fecha_final = addslashes($Par_Sql[1]);
            // El resto son los códigos de empresas
            $emp_cods = array_slice($Par_Sql, 2);
            if (empty($emp_cods)) {
                return "SELECT 0 WHERE 1=0";
            }
            $emp_cods_clean = array_map('intval', $emp_cods);
            $emp_cods_str = implode(',', $emp_cods_clean);
            $sql = "INSERT INTO perio_cont (Pec_Fei, Pec_Fef, Pec_Est, Pla_Cod)
                    SELECT 
                        '$fecha_inicial',
                        '$fecha_final',
                        'A',
                        plan_cuenta.Pla_Cod
                    FROM plan_cuenta
                    WHERE plan_cuenta.Emp_Cod IN ($emp_cods_str)
                      AND plan_cuenta.Pla_Est = 'A'
                      AND NOT EXISTS (
                          SELECT 1
                          FROM perio_cont
                          WHERE Pec_Fei = '$fecha_inicial'
                            AND Pec_Fef = '$fecha_final'
                            AND Pec_Est = 'A'
                            AND perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                      )";
            return $sql;
            
        /**
         * Actualizar régimen de empresa (en base distribuida)
         * Par_Sql[0] = nuevo_regimen
         * Par_Sql[1] = Emp_Cod
         */
        case 10:
            $nuevo_regimen = isset($Par_Sql[0]) ? addslashes($Par_Sql[0]) : '';
            $emp_cod = isset($Par_Sql[1]) ? intval($Par_Sql[1]) : 0;
            $sql = "UPDATE confi_fact SET Cof_Rim = '$nuevo_regimen' WHERE Emp_Cod = $emp_cod";
            return $sql;
            
        case 11: // Insertar listado de apertura
            // Par_Sql[0] = Lis_Id (ID único del listado, timestamp)
            // Par_Sql[1] = Lis_Nom (nombre del listado)
            // Par_Sql[2] = Lis_Per (período/año)
            // Par_Sql[3] = Lis_Fei (fecha inicial)
            // Par_Sql[4] = Lis_Fef (fecha final)
            $lis_id = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $lis_nom = isset($Par_Sql[1]) ? addslashes($Par_Sql[1]) : '';
            $lis_per = isset($Par_Sql[2]) ? intval($Par_Sql[2]) : 0;
            $lis_fei = isset($Par_Sql[3]) ? addslashes($Par_Sql[3]) : '';
            $lis_fef = isset($Par_Sql[4]) ? addslashes($Par_Sql[4]) : '';
            $sql = "INSERT INTO listado_apertura (Lis_Cod, Lis_Nom, Lis_Per, Lis_Fei, Lis_Fef, Lis_Fec, Lis_Est) 
                    VALUES ($lis_id, '$lis_nom', $lis_per, '$lis_fei', '$lis_fef', NOW(), 'A')";
            return $sql;
            
        case 12: // Insertar detalle de empresa en listado
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $emp_cod = isset($Par_Sql[1]) ? intval($Par_Sql[1]) : 0;
            $lis_mar = isset($Par_Sql[2]) ? addslashes($Par_Sql[2]) : 'N';
            $lad_est = isset($Par_Sql[3]) ? addslashes($Par_Sql[3]) : 'L';
            $sql = "INSERT INTO listado_apertura_det (Lis_Cod, Emp_Cod, Lis_Mar, Lad_Est) 
                    VALUES ($lis_cod, $emp_cod, '$lis_mar', '$lad_est')
                    ON DUPLICATE KEY UPDATE Lis_Mar = '$lis_mar', Lad_Est = '$lad_est'";
            return $sql;
            
        case 13: // Obtener listados de apertura (de una base distribuida)
            // Par_Sql[0] = Emp_Cod (opcional, si se proporciona filtra por empresa)
            if (isset($Par_Sql[0]) && !empty($Par_Sql[0])) {
                $emp_cod = intval($Par_Sql[0]);
                $sql = "SELECT DISTINCT l.Lis_Cod, l.Lis_Nom, l.Lis_Per, l.Lis_Fei, l.Lis_Fef, l.Lis_Fec, 
                        COUNT(CASE WHEN ld.Lis_Mar = 'S' THEN 1 END) as cantidad
                        FROM listado_apertura l
                        INNER JOIN listado_apertura_det ld ON l.Lis_Cod = ld.Lis_Cod
                        WHERE l.Lis_Est = 'A' AND ld.Emp_Cod = $emp_cod
                        GROUP BY l.Lis_Cod, l.Lis_Nom, l.Lis_Per, l.Lis_Fei, l.Lis_Fef, l.Lis_Fec
                        ORDER BY l.Lis_Fec DESC";
            } else {
                $sql = "SELECT l.Lis_Cod, l.Lis_Nom, l.Lis_Per, l.Lis_Fei, l.Lis_Fef, l.Lis_Fec, 
                        COUNT(CASE WHEN ld.Lis_Mar = 'S' THEN 1 END) as cantidad
                        FROM listado_apertura l
                        LEFT JOIN listado_apertura_det ld ON l.Lis_Cod = ld.Lis_Cod
                        WHERE l.Lis_Est = 'A'
                        GROUP BY l.Lis_Cod, l.Lis_Nom, l.Lis_Per, l.Lis_Fei, l.Lis_Fef, l.Lis_Fec
                        ORDER BY l.Lis_Fec DESC";
            }
            return $sql;
            
        case 14: // Obtener empresas de un listado (de una base distribuida)
            // Par_Sql[0] = Lis_Cod
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $sql = "SELECT ld.Emp_Cod, ld.Lis_Mar, ld.Lad_Est 
                    FROM listado_apertura_det ld
                    WHERE ld.Lis_Cod = $lis_cod";
            return $sql;
            
        case 15: // Actualizar estado de empresa en listado
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $emp_cod = isset($Par_Sql[1]) ? intval($Par_Sql[1]) : 0;
            $lad_est = isset($Par_Sql[2]) ? addslashes($Par_Sql[2]) : 'L';
            $sql = "UPDATE listado_apertura_det SET Lad_Est = '$lad_est' 
                    WHERE Lis_Cod = $lis_cod AND Emp_Cod = $emp_cod";
            return $sql;
            
        case 16: // Actualizar listado de apertura
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $lis_nom = isset($Par_Sql[1]) ? addslashes($Par_Sql[1]) : '';
            $lis_per = isset($Par_Sql[2]) ? intval($Par_Sql[2]) : 0;
            $lis_fei = isset($Par_Sql[3]) ? addslashes($Par_Sql[3]) : '';
            $lis_fef = isset($Par_Sql[4]) ? addslashes($Par_Sql[4]) : '';
            $sql = "UPDATE listado_apertura 
                    SET Lis_Nom = '$lis_nom', Lis_Per = $lis_per, Lis_Fei = '$lis_fei', Lis_Fef = '$lis_fef', Lis_Fec = NOW()
                    WHERE Lis_Cod = $lis_cod";
            return $sql;
            
        case 17: // Eliminar empresa de listado
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $emp_cod = isset($Par_Sql[1]) ? intval($Par_Sql[1]) : 0;
            $sql = "DELETE FROM listado_apertura_det WHERE Lis_Cod = $lis_cod AND Emp_Cod = $emp_cod";
            return $sql;
            
        case 18: // Eliminar listado completo (marcar como inactivo)
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $sql = "UPDATE listado_apertura SET Lis_Est = 'I' WHERE Lis_Cod = $lis_cod";
            return $sql;
            
        case 22: // Eliminar todos los detalles de un listado
            $lis_cod = isset($Par_Sql[0]) ? intval($Par_Sql[0]) : 0;
            $sql = "DELETE FROM listado_apertura_det WHERE Lis_Cod = $lis_cod";
            return $sql;
            
        case 19: // Verificar si empresa está en algún listado y obtener estado
            if (isset($Par_Sql[0]) && !empty($Par_Sql[0])) {
                $emp_cod = intval($Par_Sql[0]);
                $sql = "SELECT ld.Emp_Cod, ld.Lad_Est, l.Lis_Cod, l.Lis_Nom 
                        FROM listado_apertura_det ld
                        INNER JOIN listado_apertura l ON ld.Lis_Cod = l.Lis_Cod
                        WHERE ld.Emp_Cod = $emp_cod AND l.Lis_Est = 'A'
                        ORDER BY l.Lis_Fec DESC
                        LIMIT 1";
            } else {
                // Retornar todas las empresas con su estado
                $sql = "SELECT ld.Emp_Cod, ld.Lad_Est, l.Lis_Cod, l.Lis_Nom 
                        FROM listado_apertura_det ld
                        INNER JOIN listado_apertura l ON ld.Lis_Cod = l.Lis_Cod
                        WHERE l.Lis_Est = 'A'
                        ORDER BY ld.Emp_Cod, l.Lis_Fec DESC";
            }
            return $sql;
            
        case 20: // Verificar múltiples empresas en listado (optimizado con IN clause)
            // Par_Sql = array de códigos de empresas (viene como array después del explode)
            if (!empty($Par_Sql)) {
                $emp_cods_clean = array_map('intval', $Par_Sql);
                $emp_cods_str = implode(',', $emp_cods_clean);
                $sql = "SELECT ld.Emp_Cod, ld.Lad_Est, l.Lis_Cod, l.Lis_Nom, l.Lis_Fec
                        FROM listado_apertura_det ld
                        INNER JOIN listado_apertura l ON ld.Lis_Cod = l.Lis_Cod
                        WHERE ld.Emp_Cod IN ($emp_cods_str) AND l.Lis_Est = 'A'
                        ORDER BY ld.Emp_Cod, l.Lis_Fec DESC";
            } else {
                // Si no hay empresas, retornar consulta vacía
                $sql = "SELECT ld.Emp_Cod, ld.Lad_Est, l.Lis_Cod, l.Lis_Nom
                        FROM listado_apertura_det ld
                        INNER JOIN listado_apertura l ON ld.Lis_Cod = l.Lis_Cod
                        WHERE 1=0";
            }
            return $sql;
            
        default:
            return "";
    }
}
?>
