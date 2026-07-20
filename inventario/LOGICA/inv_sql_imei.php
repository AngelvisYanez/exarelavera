<?php
/**
 * Retorna consulta sql para el control de IMEI de teléfonos
 *
 * @author Exa Contable
 * @version 1.0
 * Fecha de creación: 2026-01-19
 */
function sentencias_imei($id, $Par_Sql){
    $sql = "";
    switch ($id) {
        case 1:
            // Listar productos con stock disponible (teléfonos) - para grid
            $sql = "SELECT p.Pro_Cod, i.Ite_Lar AS Pro_Nom, 
                    IFNULL(m.Mar_Des, 'NINGUNA') AS Mar_Des, 
                    IFNULL(s.Stk_Can, 0) AS Stk_Can,
                    p.Pro_Est
                    FROM producto p
                    INNER JOIN item i ON i.Ite_Cod = p.Ite_Cod
                    INNER JOIN categorias c ON c.Cat_Cod = i.Cat_Cod
                    LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                    LEFT JOIN stock s ON s.Pro_Cod = p.Pro_Cod AND s.Suc_Cod = $Par_Sql[0]
                    WHERE p.Pro_Est = 'A' AND c.Emp_Cod = $Par_Sql[1]
                    ORDER BY i.Ite_Lar";
            break;
        case 2:
            // Insertar IMEI
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) && !empty($Par_Sql['Vet_Cod']) ? $Par_Sql['Vet_Cod'] : 'NULL';
            $Ime_Tip = isset($Par_Sql['Ime_Tip']) ? "'$Par_Sql[Ime_Tip]'" : "'P'";
            $sql = "INSERT INTO imei (Pro_Cod, Ime_Num, Vet_Cod, Ime_Tip, Usu_Cod, Suc_Cod, Ime_Est) 
                    VALUES ($Par_Sql[Pro_Cod], '$Par_Sql[Ime_Num]', $Vet_Cod, $Ime_Tip, $Par_Sql[Usu_Cod], $Par_Sql[Suc_Cod], 'A')";
            break;
        case 3:
            // Actualizar IMEI
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) && !empty($Par_Sql['Vet_Cod']) ? $Par_Sql['Vet_Cod'] : 'NULL';
            $Ime_Tip = isset($Par_Sql['Ime_Tip']) ? "'$Par_Sql[Ime_Tip]'" : "'P'";
            $sql = "UPDATE imei SET 
                    Pro_Cod = $Par_Sql[Pro_Cod],
                    Ime_Num = '$Par_Sql[Ime_Num]',
                    Vet_Cod = $Vet_Cod,
                    Ime_Tip = $Ime_Tip
                    WHERE Ime_Cod = $Par_Sql[Ime_Cod]";
            break;
        case 4:
            // Listar IMEI con información del producto
            $sql = "SELECT i.Ime_Cod, i.Pro_Cod, i.Ime_Num, i.Vet_Cod, i.Ime_Tip, i.Usu_Cod, i.Suc_Cod, i.Ime_Est, i.Ime_Sys,
                    it.Ite_Lar AS Pro_Nom,
                    IFNULL(m.Mar_Des, 'NINGUNA') AS Mar_Des,
                    IFNULL(s.Stk_Can, 0) AS Stk_Can,
                    CASE i.Ime_Tip 
                        WHEN 'P' THEN 'Pendiente'
                        WHEN 'V' THEN 'Vendido'
                        WHEN 'CN' THEN 'Con Novedad'
                        WHEN 'R' THEN 'Rechazado'
                        ELSE i.Ime_Tip
                    END AS Ime_Tip_Des
                    FROM imei i
                    INNER JOIN producto p ON p.Pro_Cod = i.Pro_Cod
                    INNER JOIN item it ON it.Ite_Cod = p.Ite_Cod
                    LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                    LEFT JOIN stock s ON s.Pro_Cod = p.Pro_Cod AND s.Suc_Cod = $Par_Sql[0]
                    WHERE i.Suc_Cod = $Par_Sql[0]";
            if (!empty($Par_Sql[2])) {
                $sql .= " AND i.Pro_Cod = $Par_Sql[2]";
            }
            if (!empty($Par_Sql[3])) {
                $sql .= " AND i.Ime_Num LIKE '%$Par_Sql[3]%'";
            }
            $sql .= " ORDER BY i.Ime_Sys DESC";
            break;
        case 5:
            // Obtener un IMEI por código
            $sql = "SELECT i.*, it.Ite_Lar AS Pro_Nom,
                    IFNULL(m.Mar_Des, 'NINGUNA') AS Mar_Des
                    FROM imei i
                    INNER JOIN producto p ON p.Pro_Cod = i.Pro_Cod
                    INNER JOIN item it ON it.Ite_Cod = p.Ite_Cod
                    LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                    WHERE i.Ime_Cod = $Par_Sql[0]";
            break;
        case 6:
            // Verificar si un IMEI ya existe
            $sql = "SELECT COUNT(*) AS total FROM imei 
                    WHERE Ime_Num = '$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";
            break;
        case 7:
            // Eliminar IMEI (cambiar estado)
            $sql = "UPDATE imei SET Ime_Est = 'I' WHERE Ime_Cod = $Par_Sql[0]";
            break;
        case 8:
            // Contar IMEI por producto
            $sql = "SELECT COUNT(*) AS total FROM imei 
                    WHERE Pro_Cod = $Par_Sql[0] AND Ime_Est = 'A'";
            break;
        case 9:
            // Obtener información completa de un producto para el modal
            $sql = "SELECT p.Pro_Cod, i.Ite_Lar AS Pro_Nom, i.Ite_Cor,
                        IFNULL(m.Mar_Des, 'NINGUNA') AS Mar_Des,
                        IFNULL(s.Stk_Can, 0) AS Stk_Can,
                        c.Cat_Des, p.Pro_Obs
                    FROM producto p
                        INNER JOIN item i ON i.Ite_Cod = p.Ite_Cod
                        INNER JOIN categorias c ON c.Cat_Cod = i.Cat_Cod
                        LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                        LEFT JOIN stock s ON s.Pro_Cod = p.Pro_Cod AND s.Suc_Cod = $Par_Sql[0]
                    WHERE p.Pro_Cod = $Par_Sql[1] AND p.Pro_Est = 'A'";
            break;
        case 10:
            // Listar productos con información completa para el grid principal
            // Parámetros: Suc_Cod[0] * Emp_Cod[1] * search[2] * limits[3]
            $sql = "SELECT p.Pro_Cod, i.Ite_Lar AS Pro_Nom, 
                        IFNULL(m.Mar_Des, 'NINGUNA') AS Mar_Des, 
                        IFNULL(p.Pro_Cdc, '') AS Pro_Cdc,
                        IFNULL(s.Stk_Can, 0) AS Stk_Can,
                        (SELECT COUNT(*) FROM imei WHERE imei.Pro_Cod = p.Pro_Cod AND imei.Ime_Est = 'A') AS Total_Imei
                    FROM producto p
                        INNER JOIN item i ON i.Ite_Cod = p.Ite_Cod
                        INNER JOIN categorias c ON c.Cat_Cod = i.Cat_Cod
                        LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                        LEFT JOIN stock s ON s.Pro_Cod = p.Pro_Cod AND s.Suc_Cod = $Par_Sql[0]
                    WHERE p.Pro_Est = 'A' AND c.Emp_Cod = $Par_Sql[1]";
            if (!empty($Par_Sql[2])) {
                $search = addslashes($Par_Sql[2]);
                $sql .= " AND (i.Ite_Lar LIKE '%$search%' OR p.Pro_Cdc LIKE '%$search%' OR IFNULL(m.Mar_Des, '') LIKE '%$search%')";
            }
            $sql .= " ORDER BY i.Ite_Lar";
            
            // Agregar límites si están presentes en los parámetros (índice 3 o superior)
            if (is_array($Par_Sql) && count($Par_Sql) > 3) {
                $limitParam = trim($Par_Sql[3]);
                if (stripos($limitParam, 'LIMIT') !== false) {
                    $sql .= ' ' . $limitParam;
                }
            }
            break;
        case 11:
            // Listar IMEI de un producto específico para vista previa
            // También incluye Vet_Cod para poder filtrar los disponibles
            $sql = "SELECT i.Ime_Cod, i.Ime_Num, i.Ime_Tip, i.Ime_Est, i.Ime_Sys, i.Vet_Cod,
                    CASE i.Ime_Tip 
                        WHEN 'P' THEN 'Pendiente'
                        WHEN 'V' THEN 'Vendido'
                        WHEN 'CN' THEN 'Con Novedad'
                        WHEN 'R' THEN 'Rechazado'
                        ELSE i.Ime_Tip
                    END AS Ime_Tip_Des
                    FROM imei i
                    WHERE i.Pro_Cod = $Par_Sql[0] AND
                            i.Suc_Cod = $Par_Sql[1] AND
                            i.Ime_Est = 'A'
                    ORDER BY i.Ime_Cod DESC";
            break;
        case 12:
            // Búsqueda de productos para el modal
            // Parámetros: Suc_Cod[0] * Emp_Cod[1] * search[2] * op_opciones[3] * limits[4]
            $wherefiltro = '';
            // Agregar condiciones de búsqueda si existe
            $search = isset($Par_Sql[2]) ? trim($Par_Sql[2]) : '';
            $op_opciones = isset($Par_Sql[3]) ? trim($Par_Sql[3]) : 'd';
            
            if (!empty($search)) {
                $val = addslashes($search);
                switch ($op_opciones) {
                    case 'd': // Por descripción
                        $wherefiltro = " AND item.Ite_Lar LIKE '%$val%'";
                        break;
                    case 'm': // Por marca
                        $wherefiltro = " AND IFNULL(marca.Mar_Des, '') LIKE '%$val%'";
                        break;
                    // default: // Por defecto descripción
                    //     $wherefiltro = " AND item.Ite_Lar LIKE '%$val%'";
                    //     break;
                }
            }
                        
            $sql = "SELECT producto.Pro_Cod, 
                        item.Ite_Lar, 
                        item.Ite_Lar AS Pro_Nom,
                        IFNULL(producto.Pro_Obs, '') AS Pro_Obs,
                        IFNULL(marca.Mar_Des, 'NINGUNA') AS Mar_Des,
                        IFNULL(producto.Pro_Cdc, IFNULL(producto.Pro_Bar, '')) AS Pro_Cdc,
                        categorias.Cat_Cod,
                        IFNULL(categorias.Cat_Des, '') AS Cat_Des,
                        IFNULL(stock.Stk_Can, 0) AS Stk_Can
                    FROM categorias
                        INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                        INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                        LEFT JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                        LEFT JOIN stock ON stock.Pro_Cod = producto.Pro_Cod AND stock.Suc_Cod = $Par_Sql[0]
                    WHERE producto.Pro_Est = 'A' AND
                        categorias.Emp_Cod = $Par_Sql[1] AND
                        categorias.Cat_Des = 'CELULARES' AND
                        (categorias.Cat_Tip = 'G' OR categorias.Cat_Tip = 'D')
                        $wherefiltro
                    ORDER BY item.Ite_Lar";
            
            // Agregar límites si están presentes en los parámetros (índice 4 o superior)
            if (is_array($Par_Sql) && count($Par_Sql) > 4) {
                // El límite debería estar en el índice 4 o superior
                $limitParam = trim($Par_Sql[4]);
                if (stripos($limitParam, 'LIMIT') !== false) {
                    $sql .= ' ' . $limitParam;
                }
            }
            break;
        case 13:
            // Contar productos para paginación (mismo filtro que caso 12)
            // Parámetros: Suc_Cod[0] * Emp_Cod[1] * search[2] * op_opciones[3]
            $wherefiltro = '';
            // Agregar condiciones de búsqueda si existe
            $search = isset($Par_Sql[2]) ? trim($Par_Sql[2]) : '';
            $op_opciones = isset($Par_Sql[3]) ? trim($Par_Sql[3]) : 'd';

            if (!empty($search)) {
                $val = addslashes($search);
                switch ($op_opciones) {
                    case 'd': // Por descripción
                        $wherefiltro = " AND item.Ite_Lar LIKE '%$val%'";
                        break;
                    case 'm': // Por marca
                        $wherefiltro = " AND IFNULL(marca.Mar_Des, '') LIKE '%$val%'";
                        break;
                }
            }
            
            $sql = "SELECT COUNT(*) AS total
                    FROM categorias
                        INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                        INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                        LEFT JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                    WHERE producto.Pro_Est = 'A' AND
                        categorias.Emp_Cod = $Par_Sql[1]
                        $wherefiltro";
            break;
        case 14:
            // Contar productos para paginación del grid principal (mismo filtro que caso 10)
            // Parámetros: Suc_Cod[0] * Emp_Cod[1] * search[2]
            $sql = "SELECT COUNT(*) AS total
                    FROM producto p
                    INNER JOIN item i ON i.Ite_Cod = p.Ite_Cod
                    INNER JOIN categorias c ON c.Cat_Cod = i.Cat_Cod
                    LEFT JOIN marca m ON m.Mar_Cod = p.Mar_Cod
                    WHERE p.Pro_Est = 'A' AND c.Emp_Cod = $Par_Sql[1]";
            if (!empty($Par_Sql[2])) {
                $search = addslashes($Par_Sql[2]);
                $sql .= " AND (i.Ite_Lar LIKE '%$search%' OR p.Pro_Cdc LIKE '%$search%' OR IFNULL(m.Mar_Des, '') LIKE '%$search%')";
            }
            break;
    }
    return $sql;
}
?>
