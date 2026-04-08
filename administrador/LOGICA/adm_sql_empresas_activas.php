<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Sistema
 * @version 3.0 - Optimizado
 * Fecha de actualización:	2025-01-XX
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */

function sentencias_empresas_activas($id, $Par_Sql)
{
    $sql = "";
    switch($id)
    {
        /**
         * Consulta empresas activas desde exa_master
         */
        case 1:
            if(isset($Par_Sql["limits"]) && $Par_Sql["limits"] == "COUNT") {
                $campos = "COUNT(DISTINCT empresas.Emp_Cod) as total";
                $orderBy = "";
            } else {
                $campos = "empresas.Emp_Cod,
                          empresas.Emp_Nom,
                          empresas.Emp_Ruc,
                          empresas.Emp_Cor,
                          empresas.Emp_Est,
                          data.Dat_Dis";
                $orderBy = "ORDER BY empresas.Emp_Nom ASC";
                if(isset($Par_Sql["limits"]) && $Par_Sql["limits"] != "") {
                    $orderBy .= " " . $Par_Sql["limits"];
                }
            }
            
            // Filtrar solo por las bases de datos permitidas
            $bases_permitidas = array('exa', 'servicios', 'gsl_chavez', 'agronuevo', 'coopsb');
            $bases_str = "'" . implode("','", $bases_permitidas) . "'";
            
            $sql = "SELECT $campos
                    FROM exa_master.empresas
                    INNER JOIN exa_master.data ON data.Emp_Cod = empresas.Emp_Cod
                    WHERE empresas.Emp_Est = 'A'
                      AND data.Dat_Dis IN ($bases_str)";
            
            if(isset($Par_Sql["search"]) && !empty($Par_Sql["search"])) {
                $search = addslashes($Par_Sql["search"]);
                if(isset($Par_Sql["op_opciones"])) {
                    switch($Par_Sql["op_opciones"]) {
                        case 'e':
                            $sql .= " AND empresas.Emp_Nom LIKE '%$search%'";
                            break;
                        case 'c':
                            $sql .= " AND empresas.Emp_Ruc LIKE '%$search%'";
                            break;
                        default:
                            $sql .= " AND (empresas.Emp_Nom LIKE '%$search%' OR empresas.Emp_Ruc LIKE '%$search%')";
                            break;
                    }
                } else {
                    $sql .= " AND (empresas.Emp_Nom LIKE '%$search%' OR empresas.Emp_Ruc LIKE '%$search%')";
                }
            }
            
            $sql .= " $orderBy";
            return $sql;
            
        /**
         * Consulta periodos por año desde 2020 en adelante
         */
        case 2:
            $base_datos = isset($Par_Sql["base_datos"]) ? $Par_Sql["base_datos"] : "exa";
            
            $sql = "SELECT DISTINCT
                        YEAR(perio_cont.Pec_Fei) AS Periodo,
                        YEAR(perio_cont.Pec_Fei) AS Periodo_Des
                    FROM $base_datos.perio_cont
                    INNER JOIN $base_datos.plan_cuenta ON perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                    INNER JOIN exa_master.empresas ON plan_cuenta.Emp_Cod = empresas.Emp_Cod
                    WHERE perio_cont.Pec_Est = 'A'
                      AND empresas.Emp_Est = 'A'
                      AND YEAR(perio_cont.Pec_Fei) >= 2020
                    ORDER BY Periodo DESC";
            return $sql;
            
        /**
         * Consulta optimizada: Obtiene compras y ventas de múltiples empresas en una sola consulta
         * Agrupa por base de datos para reducir consultas
         * OPTIMIZACIÓN: Usa subconsultas más eficientes y evita JOINs innecesarios
         */
        case 3:
            $base_datos = isset($Par_Sql["base_datos"]) ? $Par_Sql["base_datos"] : "exa";
            $periodo = intval($Par_Sql["Periodo"]);
            $emp_cods = isset($Par_Sql["Emp_Cods"]) ? $Par_Sql["Emp_Cods"] : array();
            
            if(empty($emp_cods)) {
                $sql = "SELECT 0 AS Emp_Cod, 0 AS total_compras, 0 AS total_ventas WHERE 1=0";
                return $sql;
            }
            
            $emp_cods_clean = array_map('intval', $emp_cods);
            $emp_cods_str = implode(',', $emp_cods_clean);
            
            // Optimización: Obtener Pec_Cod del periodo primero para evitar cálculos YEAR() repetidos
            // Usar subconsultas más eficientes
            $sql = "SELECT 
                        empresas.Emp_Cod,
                        IFNULL(compras_totales.total_compras, 0) AS total_compras,
                        IFNULL(ventas_totales.total_ventas, 0) AS total_ventas
                    FROM (
                        SELECT DISTINCT Emp_Cod FROM $base_datos.plan_cuenta 
                        WHERE Emp_Cod IN ($emp_cods_str)
                    ) AS empresas
                    LEFT JOIN (
                        SELECT 
                            plan_cuenta.Emp_Cod,
                            COUNT(DISTINCT compras.Cop_Cod) AS total_compras
                        FROM $base_datos.compras
                        INNER JOIN $base_datos.perio_cont ON compras.Pec_Cod = perio_cont.Pec_Cod
                        INNER JOIN $base_datos.plan_cuenta ON perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                        WHERE compras.Cop_Est = 'A'
                          AND plan_cuenta.Emp_Cod IN ($emp_cods_str)
                          AND YEAR(perio_cont.Pec_Fei) = $periodo
                          AND perio_cont.Pec_Est = 'A'
                        GROUP BY plan_cuenta.Emp_Cod
                    ) AS compras_totales ON compras_totales.Emp_Cod = empresas.Emp_Cod
                    LEFT JOIN (
                        SELECT 
                            sucursal.Emp_Cod,
                            COUNT(DISTINCT ventas.Vet_Cod) AS total_ventas
                        FROM $base_datos.ventas
                        INNER JOIN $base_datos.caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod
                        INNER JOIN $base_datos.autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
                        INNER JOIN $base_datos.puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
                        INNER JOIN $base_datos.sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
                        INNER JOIN $base_datos.plan_cuenta ON sucursal.Emp_Cod = plan_cuenta.Emp_Cod
                        INNER JOIN $base_datos.perio_cont ON plan_cuenta.Pla_Cod = perio_cont.Pla_Cod
                        WHERE ventas.Vet_Est = 'A'
                          AND sucursal.Emp_Cod IN ($emp_cods_str)
                          AND YEAR(perio_cont.Pec_Fei) = $periodo
                          AND perio_cont.Pec_Est = 'A'
                          AND caja_aper.Caj_Fec BETWEEN perio_cont.Pec_Fei AND perio_cont.Pec_Fef
                        GROUP BY sucursal.Emp_Cod
                    ) AS ventas_totales ON ventas_totales.Emp_Cod = empresas.Emp_Cod";
            return $sql;
            
        /**
         * Verifica periodos activos para múltiples empresas en una sola consulta
         */
        case 4:
            $base_datos = isset($Par_Sql["base_datos"]) ? $Par_Sql["base_datos"] : "exa";
            $periodo = intval($Par_Sql["Periodo"]);
            $emp_cods = isset($Par_Sql["Emp_Cods"]) ? $Par_Sql["Emp_Cods"] : array();
            
            if(empty($emp_cods)) {
                $sql = "SELECT 0 AS Emp_Cod, 0 AS tiene_periodo WHERE 1=0";
                return $sql;
            }
            
            $emp_cods_clean = array_map('intval', $emp_cods);
            $emp_cods_str = implode(',', $emp_cods_clean);
            
            $sql = "SELECT 
                        plan_cuenta.Emp_Cod,
                        COUNT(*) as tiene_periodo
                    FROM $base_datos.perio_cont
                    INNER JOIN $base_datos.plan_cuenta ON perio_cont.Pla_Cod = plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod IN ($emp_cods_str)
                      AND perio_cont.Pec_Est = 'A'
                      AND YEAR(perio_cont.Pec_Fei) = $periodo
                    GROUP BY plan_cuenta.Emp_Cod";
            return $sql;
    }
    return $sql;
}
?>
