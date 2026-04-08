<?php

/**
 * SQL para Tickets de Cantera
 */
function sentencias_ticket($id, $Par_Sql)
{
    switch ($id) {
        // Select para listar clientes (solo los que tienen vehiculos)
        case 1:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(persona.Prs_Ape LIKE '%$Par_Sql[search]%' OR persona.Prs_Nom LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "persona.Prs_Ced LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY persona.Prs_Ape $Par_Sql[limits]";
                $campos = "DISTINCT cliente.Cli_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente, persona.Prs_Dir, persona.Prs_Cor";
            } else {
                $campos = "COUNT(DISTINCT cliente.Cli_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos FROM cliente
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    INNER JOIN vehiculo ON cliente.Cli_Cod=vehiculo.Cli_Cod
                    WHERE $search AND vehiculo.Veh_Est='A' AND vehiculo.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
            break;

        // Select para listar vehiculos
        case 2:
            $search_vehiculo = "";
            $search_cliente = "";

            // Búsqueda por placa de vehículo
            if (isset($Par_Sql['search']) && !empty($Par_Sql['search'])) {
                if ($Par_Sql['op_opciones'] == "d") {
                    $search_vehiculo = "(vehiculo.Veh_Pla LIKE '%$Par_Sql[search]%')";
                } else {
                    $search_vehiculo = "vehiculo.Veh_Pla LIKE '$Par_Sql[search]%'";
                }
            }

            // Búsqueda por cliente
            if (isset($Par_Sql['search_cliente']) && !empty($Par_Sql['search_cliente'])) {
                $search_cliente = "(CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) LIKE '%$Par_Sql[search_cliente]%' OR persona.Prs_Ced LIKE '%$Par_Sql[search_cliente]%')";
            }

            // Combinar búsquedas
            $where_search = "";
            if ($search_vehiculo && $search_cliente) {
                $where_search = "($search_vehiculo OR $search_cliente)";
            } else if ($search_vehiculo) {
                $where_search = $search_vehiculo;
            } else if ($search_cliente) {
                $where_search = $search_cliente;
            } else {
                $where_search = "1=1";
            }

            // Filtro por cliente seleccionado (si existe)
            $where_cliente = "";
            if (isset($Par_Sql['Cli_Cod']) && !empty($Par_Sql['Cli_Cod'])) {
                $where_cliente = "AND vehiculo.Cli_Cod = '$Par_Sql[Cli_Cod]'";
            }

            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY vehiculo.Veh_Pla $Par_Sql[limits]";
                $campos = "vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Cap, vehiculo.Veh_Tit, vehiculo.Cli_Cod,
                        CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre, persona.Prs_Ced";
            } else {
                $campos = "COUNT(vehiculo.Veh_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos 
                    FROM vehiculo
                    INNER JOIN cliente ON vehiculo.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    WHERE $where_search AND vehiculo.Veh_Est='A' AND vehiculo.Emp_Cod=$Par_Sql[Emp_Cod] 
                    AND vehiculo.Cli_Cod IS NOT NULL $where_cliente $Par_Sql[limits]";
            break;

        // Select para obtener vehiculo por cliente
        case 4:
            $sql = "SELECT vehiculo.Veh_Cod, vehiculo.Veh_Pla, vehiculo.Veh_Cap, vehiculo.Veh_Tit, vehiculo.Cli_Cod
                    FROM vehiculo
                    WHERE vehiculo.Cli_Cod='$Par_Sql[Cli_Cod]' 
                    AND vehiculo.Veh_Est='A' 
                    AND vehiculo.Emp_Cod=$Par_Sql[Emp_Cod]
                    LIMIT 1";
            break;

        // Select para obtener saldo de anticipos del cliente (a favor)
        case 5:
            $sql = "SELECT COALESCE(SUM(saldo), 0) AS saldo_anticipo
                    FROM (
                        SELECT (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo
                        FROM anticipos_clientes ant
                        LEFT JOIN det_ant_cccc dacc ON dacc.Ant_Cod = ant.Ant_Cod
                        WHERE ant.Cli_Cod = " . intval($Par_Sql['Cli_Cod']) . " AND (ant.Ant_Est = 'A' OR ant.Ant_Est = 'U')
                        GROUP BY ant.Ant_Cod
                    ) AS sub";
            break;

        // Select para obtener total de tickets del cliente: solo estado activo (A), excluye facturados
        case 6:
            $sql = "SELECT COALESCE(SUM(ticket_cantera.Tck_Tot), 0) AS total_tickets
                    FROM ticket_cantera
                    INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                    WHERE ticket_cantera.Cli_Cod = " . intval($Par_Sql['Cli_Cod']) . "
                    AND vehiculo.Emp_Cod = " . intval($Par_Sql['Emp_Cod']) . "
                    AND ticket_cantera.Tck_Est = 'A' AND ticket_cantera.Tck_Tip = 'A'";
            break;

        // Select para listar productos
        case 3:
            if ($Par_Sql['op_opciones'] == "d") {
                $search = "(item.Ite_Lar LIKE '%$Par_Sql[search]%' OR producto.Pro_Obs LIKE '%$Par_Sql[search]%')";
            } else {
                $search = "producto.Pro_Cod LIKE '$Par_Sql[search]%'";
            }
            if (isset($Par_Sql["limits"])) {
                $Par_Sql["limits"] = "ORDER BY item.Ite_Lar $Par_Sql[limits]";
                $campos = "producto.Pro_Cod, item.Ite_Lar AS Pro_Des, 
                        COALESCE(precios.Pre_Pvp, 0) AS Pro_Pru";
            } else {
                $campos = "COUNT(DISTINCT producto.Pro_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos 
                    FROM producto
                    INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
                    INNER JOIN categorias ON item.Cat_Cod = categorias.Cat_Cod
                    INNER JOIN precios ON precios.Pro_Cod = producto.Pro_Cod AND precios.Pre_Est='A'
                    INNER JOIN sucursal ON sucursal.Suc_Cod = precios.Suc_Cod
                    WHERE $search 
                    AND producto.Pro_Est='A' 
                    AND categorias.Emp_Cod=$Par_Sql[Emp_Cod]
                    AND sucursal.Suc_Cod=$Par_Sql[Suc_Cod]
                    GROUP BY producto.Pro_Cod $Par_Sql[limits]";
            break;

        // INSERT en la tabla ticket_cantera
        // Nota: Si la tabla tiene Prv_Cod, necesitas agregar Cli_Cod o renombrar Prv_Cod a Cli_Cod
        case 10:
            // Intentar usar Cli_Cod si existe, sino usar NULL (la tabla necesita ser actualizada)
            $cli_cod_value = isset($Par_Sql['Cli_Cod']) && !empty($Par_Sql['Cli_Cod']) ? "'$Par_Sql[Cli_Cod]'" : "NULL";
            $tck_pag = "'E'";
            if (isset($Par_Sql['Tck_Pag'])) {
                $v = strtoupper(trim($Par_Sql['Tck_Pag']));
                if ($v === 'C') $tck_pag = "'C'";
                elseif ($v === 'F') $tck_pag = "'F'";
            }
            $sql = "INSERT INTO ticket_cantera(Cli_Cod, Veh_Cod, Tck_Fec, Tck_Val, Tck_IvA, Tck_Tot, Tck_Est, Tck_Num, Tck_Pag) 
                    VALUES($cli_cod_value,'$Par_Sql[Veh_Cod]','$Par_Sql[Tck_Fec]','$Par_Sql[Tck_Val]','$Par_Sql[Tck_IvA]','$Par_Sql[Tck_Tot]','$Par_Sql[Tck_Est]','$Par_Sql[Tck_Num]',$tck_pag)";
            break;

        // UPDATE en la tabla ticket_cantera
        case 11:
            $cli_cod_value = isset($Par_Sql['Cli_Cod']) && !empty($Par_Sql['Cli_Cod']) ? "'$Par_Sql[Cli_Cod]'" : "NULL";
            $tck_tip_value = isset($Par_Sql['Tck_Tip']) ? "'$Par_Sql[Tck_Tip]'" : "";
            $tck_pag = "'E'";
            if (isset($Par_Sql['Tck_Pag'])) {
                $v = strtoupper(trim($Par_Sql['Tck_Pag']));
                if ($v === 'C') $tck_pag = "'C'";
                elseif ($v === 'F') $tck_pag = "'F'";
            }
            $sql = "UPDATE ticket_cantera SET 
                    Cli_Cod=$cli_cod_value,
                    Veh_Cod='$Par_Sql[Veh_Cod]',
                    Tck_Fec='$Par_Sql[Tck_Fec]',
                    Tck_Val='$Par_Sql[Tck_Val]',
                    Tck_IvA='$Par_Sql[Tck_IvA]',
                    Tck_Tot='$Par_Sql[Tck_Tot]',
                    Tck_Est='$Par_Sql[Tck_Est]',
                    Tck_Pag=$tck_pag";
            if (!empty($tck_tip_value)) {
                $sql .= ", Tck_Tip=$tck_tip_value";
            }
            $sql .= " WHERE Tck_Cod='$Par_Sql[Tck_Cod]'";
            break;

        // UPDATE solo Tck_Tip en la tabla ticket_cantera
        case 12:
            $sql = "UPDATE ticket_cantera SET Tck_Tip='$Par_Sql[Tck_Tip]' WHERE Tck_Cod='$Par_Sql[Tck_Cod]'";
            break;

        // Obtener siguiente número de ticket por empresa
        case 40:
            $sql = "SELECT IFNULL(MAX(ticket_cantera.Tck_Num), 0) + 1 AS siguiente 
                    FROM ticket_cantera
                    INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                    WHERE vehiculo.Emp_Cod = '$Par_Sql[Emp_Cod]'";
            break;

        // INSERT en la tabla ticket_cantera_det
        case 20:
            $sql = "INSERT INTO ticket_cantera_det(Tck_Cod, Pro_Cod, Dtk_Det, Dtk_Can, Dtk_Pru, Dtk_Tot) 
                    VALUES('$Par_Sql[Tck_Cod]','$Par_Sql[Pro_Cod]','$Par_Sql[Dtk_Det]','$Par_Sql[Dtk_Can]','$Par_Sql[Dtk_Pru]','$Par_Sql[Dtk_Tot]')";
            break;

        // UPDATE en la tabla ticket_cantera_det
        case 21:
            $sql = "UPDATE ticket_cantera_det SET 
                    Pro_Cod='$Par_Sql[Pro_Cod]',
                    Dtk_Det='$Par_Sql[Dtk_Det]',
                    Dtk_Can='$Par_Sql[Dtk_Can]',
                    Dtk_Pru='$Par_Sql[Dtk_Pru]',
                    Dtk_Tot='$Par_Sql[Dtk_Tot]'
                    WHERE Dtk_Cod='$Par_Sql[Dtk_Cod]'";
            break;

        // DELETE en la tabla ticket_cantera_det
        case 22:
            $sql = "DELETE FROM ticket_cantera_det WHERE Dtk_Cod='$Par_Sql[Dtk_Cod]'";
            break;

        // Select para obtener detalle de ticket
        case 30:
            $sql = "SELECT Dtk_Cod, Tck_Cod, ticket_cantera_det.Pro_Cod, Dtk_Det, Dtk_Can, Dtk_Pru, Dtk_Tot, item.Ite_Lar AS Pro_Des
                    FROM ticket_cantera_det
                    LEFT JOIN producto ON ticket_cantera_det.Pro_Cod=producto.Pro_Cod
                    LEFT JOIN item ON producto.Ite_Cod=item.Ite_Cod
                    WHERE Tck_Cod='$Par_Sql[Tck_Cod]'";
            break;

        // Select para obtener ticket por codigo
        case 31:
            $sql = "SELECT ticket_cantera.*, 
                    CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre,
                    persona.Prs_Ced, persona.Prs_Dir, persona.Prs_Cor,
                    vehiculo.Veh_Pla, vehiculo.Veh_Cap, vehiculo.Veh_Tit
                    FROM ticket_cantera
                    LEFT JOIN cliente ON ticket_cantera.Cli_Cod=cliente.Cli_Cod
                    LEFT JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    LEFT JOIN vehiculo ON ticket_cantera.Veh_Cod=vehiculo.Veh_Cod
                    WHERE ticket_cantera.Tck_Cod='$Par_Sql[Tck_Cod]'";
            break;
        // Select para listar tickets
        case 50:
            $search = '';
            if (isset($Par_Sql['search']) && !empty($Par_Sql['search'])) {
                $search = "AND (ticket_cantera.Tck_Num LIKE '%$Par_Sql[search]%' 
                          OR CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) LIKE '%$Par_Sql[search]%'
                          OR persona.Prs_Ced LIKE '%$Par_Sql[search]%'
                          OR vehiculo.Veh_Pla LIKE '%$Par_Sql[search]%')";
            }
            // Filtrar por cliente si se proporciona
            if (isset($Par_Sql['Cli_Cod']) && !empty($Par_Sql['Cli_Cod'])) {
                $search .= " AND ticket_cantera.Cli_Cod = '$Par_Sql[Cli_Cod]'";
            }

            if (isset($Par_Sql['Tck_Est']) && $Par_Sql['Tck_Est'] != '') {
                $search .= " AND ticket_cantera.Tck_Est = '$Par_Sql[Tck_Est]'";
            }

            // Filtrar por tipo facturado / no facturado: F = facturado, N = no facturado
            if (isset($Par_Sql['Tck_Tip']) && $Par_Sql['Tck_Tip'] !== '') {
                $tck_tip = $Par_Sql['Tck_Tip'];
                if ($tck_tip === 'F' || $tck_tip === 'f') {
                    $search .= " AND (ticket_cantera.Tck_Tip = 'F' OR ticket_cantera.Tck_Tip = 'f')";
                } elseif ($tck_tip === 'N' || $tck_tip === 'n') {
                    $search .= " AND (ticket_cantera.Tck_Tip IS NULL OR ticket_cantera.Tck_Tip = '' OR ticket_cantera.Tck_Tip NOT IN ('F','f'))";
                }
            }

            if (isset($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Ini'])) {
                $search .= " AND DATE(ticket_cantera.Tck_Fec) >= '$Par_Sql[Fec_Ini]'";
            }

            if (isset($Par_Sql['Fec_Fin']) && !empty($Par_Sql['Fec_Fin'])) {
                $search .= " AND DATE(ticket_cantera.Tck_Fec) <= '$Par_Sql[Fec_Fin]'";
            }
            // Ordenamiento personalizado si se proporciona
            $orderBy = "ORDER BY ticket_cantera.Tck_Cod DESC";
            if (isset($Par_Sql["order"]) && !empty($Par_Sql["order"])) {
                $orderBy = $Par_Sql["order"];
            }

            if (isset($Par_Sql["limits"])) {
                // Si limits ya contiene ORDER BY, no agregarlo de nuevo
                if (stripos($Par_Sql["limits"], "ORDER BY") === false) {
                    $Par_Sql["limits"] = "$orderBy $Par_Sql[limits]";
                } else {
                    // Si ya tiene ORDER BY, reemplazarlo con el ordenamiento personalizado
                    $Par_Sql["limits"] = preg_replace('/ORDER BY.*?(\s|$)/i', "$orderBy ", $Par_Sql["limits"]);
                }
                $campos = "ticket_cantera.*, 
                          CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre,
                          persona.Prs_Ced, vehiculo.Veh_Pla, vehiculo.Veh_Tip";
            } else {
                $campos = "COUNT(ticket_cantera.Tck_Cod) as total";
                $Par_Sql["limits"] = "";
            }
            $sql = "SELECT $campos 
                    FROM ticket_cantera
                    INNER JOIN vehiculo ON ticket_cantera.Veh_Cod=vehiculo.Veh_Cod
                    INNER JOIN cliente ON ticket_cantera.Cli_Cod=cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
                    WHERE cliente.Emp_Cod=$Par_Sql[Emp_Cod]      $search       $Par_Sql[limits]";
            break;

        // Reporte: una fila por chofer (nombre, anticipo, total tickets, saldo); al expandir se ven sus vehículos
        case 51:
            $Emp_Cod = intval($Par_Sql['Emp_Cod']);
            $where_parts = array();
            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '' && (int)$Par_Sql['Cli_Cod'] > 0) {
                $where_parts[] = "c.Cli_Cod = " . intval($Par_Sql['Cli_Cod']);
            }
            if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
                $placa = addslashes(trim($Par_Sql['search']));
                $where_parts[] = "c.Cli_Cod IN (SELECT Cli_Cod FROM vehiculo WHERE Emp_Cod = $Emp_Cod AND Veh_Est = 'A' AND Veh_Pla LIKE '%" . $placa . "%')";
            }
            $where_cli = empty($where_parts) ? "" : " WHERE " . implode(" AND ", $where_parts);
            $sql = "SELECT c.Cli_Cod, c.cliente_nombre, c.Prs_Ced,
                    COALESCE(tt.total_tickets, 0) AS total_tickets,
                    COALESCE(tt.cantidad_tickets, 0) AS cantidad_tickets,
                    COALESCE(ant.saldo_anticipo, 0) AS saldo_anticipo,
                    GREATEST(0, COALESCE(ant.saldo_anticipo, 0) - COALESCE(tt.total_tickets, 0)) AS saldo
                    FROM (
                        SELECT DISTINCT cliente.Cli_Cod,
                        CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre,
                        persona.Prs_Ced
                        FROM cliente
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN vehiculo ON cliente.Cli_Cod = vehiculo.Cli_Cod
                        WHERE vehiculo.Emp_Cod = $Emp_Cod AND vehiculo.Veh_Est = 'A'
                    ) c
                    LEFT JOIN (
                        SELECT ticket_cantera.Cli_Cod,
                        SUM(ticket_cantera.Tck_Tot) AS total_tickets,
                        COUNT(*) AS cantidad_tickets
                        FROM ticket_cantera
                        INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                        WHERE vehiculo.Emp_Cod = $Emp_Cod
                        AND ticket_cantera.Tck_Est = 'A'
                        AND (ticket_cantera.Tck_Tip IS NULL OR ticket_cantera.Tck_Tip = '' OR ticket_cantera.Tck_Tip NOT IN ('F','f'))
                        GROUP BY ticket_cantera.Cli_Cod
                    ) tt ON c.Cli_Cod = tt.Cli_Cod
                    LEFT JOIN (
                        SELECT sub.Cli_Cod, COALESCE(SUM(sub.saldo_ind), 0) AS saldo_anticipo
                        FROM (
                            SELECT ant2.Cli_Cod,
                            (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli pga WHERE pga.Ant_Cod = ant2.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo_ind
                            FROM anticipos_clientes ant2
                            LEFT JOIN det_ant_cccc dacc ON dacc.Ant_Cod = ant2.Ant_Cod
                            WHERE (ant2.Ant_Est = 'A' OR ant2.Ant_Est = 'U')
                            GROUP BY ant2.Ant_Cod, ant2.Cli_Cod
                        ) sub
                        GROUP BY sub.Cli_Cod
                    ) ant ON c.Cli_Cod = ant.Cli_Cod
                    $where_cli ORDER BY c.cliente_nombre";
            break;

        // Reporte SIN tablas de anticipos: una fila por chofer; anticipos=0, saldo=0
        case 52:
            $Emp_Cod = intval($Par_Sql['Emp_Cod']);
            $where_parts = array();
            if (isset($Par_Sql['Cli_Cod']) && $Par_Sql['Cli_Cod'] !== '' && (int)$Par_Sql['Cli_Cod'] > 0) {
                $where_parts[] = "c.Cli_Cod = " . intval($Par_Sql['Cli_Cod']);
            }
            if (isset($Par_Sql['search']) && trim($Par_Sql['search']) !== '') {
                $placa = addslashes(trim($Par_Sql['search']));
                $where_parts[] = "c.Cli_Cod IN (SELECT Cli_Cod FROM vehiculo WHERE Emp_Cod = $Emp_Cod AND Veh_Est = 'A' AND Veh_Pla LIKE '%" . $placa . "%')";
            }
            $where_cli = empty($where_parts) ? "" : " WHERE " . implode(" AND ", $where_parts);
            $sql = "SELECT c.Cli_Cod, c.cliente_nombre, c.Prs_Ced,
                    COALESCE(tt.total_tickets, 0) AS total_tickets,
                    COALESCE(tt.cantidad_tickets, 0) AS cantidad_tickets,
                    0 AS saldo_anticipo,
                    0 AS saldo
                    FROM (
                        SELECT DISTINCT cliente.Cli_Cod,
                        CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre,
                        persona.Prs_Ced
                        FROM cliente
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN vehiculo ON cliente.Cli_Cod = vehiculo.Cli_Cod
                        WHERE vehiculo.Emp_Cod = $Emp_Cod AND vehiculo.Veh_Est = 'A'
                    ) c
                    LEFT JOIN (
                        SELECT ticket_cantera.Cli_Cod,
                        SUM(ticket_cantera.Tck_Tot) AS total_tickets,
                        COUNT(*) AS cantidad_tickets
                        FROM ticket_cantera
                        INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                        WHERE vehiculo.Emp_Cod = $Emp_Cod
                        AND ticket_cantera.Tck_Est = 'A'
                        AND (ticket_cantera.Tck_Tip IS NULL OR ticket_cantera.Tck_Tip = '' OR ticket_cantera.Tck_Tip NOT IN ('F','f'))
                        GROUP BY ticket_cantera.Cli_Cod
                    ) tt ON c.Cli_Cod = tt.Cli_Cod
                    $where_cli ORDER BY c.cliente_nombre";
            break;

        // Detalle por vehículo del cliente (para subGrid): placa, cantidad tickets, consumo
        case 53:
            $Emp_Cod = intval($Par_Sql['Emp_Cod']);
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) && (int)$Par_Sql['Cli_Cod'] > 0 ? intval($Par_Sql['Cli_Cod']) : 0;
            $sql = "SELECT v.Veh_Pla,
                    COALESCE(tt.cantidad_tickets, 0) AS cantidad_tickets,
                    COALESCE(tt.total_tickets, 0) AS total_tickets
                    FROM vehiculo v
                    LEFT JOIN (
                        SELECT ticket_cantera.Veh_Cod,
                        COUNT(*) AS cantidad_tickets,
                        SUM(ticket_cantera.Tck_Tot) AS total_tickets
                        FROM ticket_cantera
                        INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                        WHERE vehiculo.Emp_Cod = $Emp_Cod AND vehiculo.Cli_Cod = $Cli_Cod
                        AND ticket_cantera.Tck_Est = 'A'
                        AND (ticket_cantera.Tck_Tip IS NULL OR ticket_cantera.Tck_Tip = '' OR ticket_cantera.Tck_Tip NOT IN ('F','f'))
                        GROUP BY ticket_cantera.Veh_Cod
                    ) tt ON v.Veh_Cod = tt.Veh_Cod
                    WHERE v.Emp_Cod = $Emp_Cod AND v.Cli_Cod = $Cli_Cod AND v.Veh_Est = 'A'
                    ORDER BY v.Veh_Pla";
            break;

        // Reporte Ventas: tickets por fecha (Fecha, Mes, Documento=Ticket, Cliente, Placa, Descripción volqueta, Forma pago, Valor)
        case 54:
            $Emp_Cod = intval($Par_Sql['Emp_Cod']);
            $where = "vehiculo.Emp_Cod = $Emp_Cod AND ticket_cantera.Tck_Est = 'A'";
            if (isset($Par_Sql['Cli_Cod']) && (int)$Par_Sql['Cli_Cod'] > 0) {
                $Cli_Cod = intval($Par_Sql['Cli_Cod']);
                $where .= " AND ticket_cantera.Cli_Cod = $Cli_Cod";
            }
            if (isset($Par_Sql['Fec_Ini']) && $Par_Sql['Fec_Ini'] !== '') {
                $Fec_Ini = addslashes($Par_Sql['Fec_Ini']);
                $where .= " AND DATE(ticket_cantera.Tck_Fec) >= '$Fec_Ini'";
            }
            if (isset($Par_Sql['Fec_Fin']) && $Par_Sql['Fec_Fin'] !== '') {
                $Fec_Fin = addslashes($Par_Sql['Fec_Fin']);
                $where .= " AND DATE(ticket_cantera.Tck_Fec) <= '$Fec_Fin'";
            }
            $veh_tip_desc = "CASE vehiculo.Veh_Tit
                        WHEN 'V' THEN 'Volqueta Sencilla'
                        WHEN 'VM' THEN 'Volqueta Mula'
                        WHEN 'VB' THEN 'Volqueta Bañera'
                        WHEN 'D' THEN 'TIPO DUMPER'
                        WHEN 'B' THEN 'Bus'
                        WHEN 'C' THEN 'CAMION'
                        WHEN 'T' THEN 'Tractor'
                        WHEN 'M' THEN 'Moto'
                        WHEN 'O' THEN 'Otro'
                        ELSE COALESCE(vehiculo.Veh_Tit, '')
                    END";

            if (isset($Par_Sql['Agrupar']) && (int)$Par_Sql['Agrupar'] === 1) {
                // Agrupado por Día y Placa
                $sql = "SELECT
                        DATE(ticket_cantera.Tck_Fec) AS Tck_Fec,
                        DATE_FORMAT(ticket_cantera.Tck_Fec, '%Y-%m') AS mes,
                        '' AS Tck_Num,
                        GROUP_CONCAT(DISTINCT CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) ORDER BY persona.Prs_Ape, persona.Prs_Nom SEPARATOR ' / ') AS cliente_nombre,
                        vehiculo.Veh_Pla,
                        COUNT(*) AS Cant_Volq,
                        (SUM(ticket_cantera.Tck_Tot) / NULLIF(COUNT(*), 0)) AS Val_Uni,
                        $veh_tip_desc AS Veh_Tip_Desc,
                        COALESCE(ticket_cantera.Tck_Pag, 'E') AS Tck_Pag,
                        SUM(ticket_cantera.Tck_Tot) AS Tck_Tot,
                        SUM(CASE WHEN COALESCE(ticket_cantera.Tck_Pag,'E') = 'C' THEN ticket_cantera.Tck_Tot ELSE 0 END) AS total_credito,
                        SUM(CASE WHEN COALESCE(ticket_cantera.Tck_Pag,'E') = 'C' THEN 0 ELSE ticket_cantera.Tck_Tot END) AS total_efectivo
                        FROM ticket_cantera
                        INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                        INNER JOIN cliente ON ticket_cantera.Cli_Cod = cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        WHERE $where
                        GROUP BY DATE(ticket_cantera.Tck_Fec), vehiculo.Veh_Pla, vehiculo.Veh_Tit, COALESCE(ticket_cantera.Tck_Pag, 'E')
                        ORDER BY DATE(ticket_cantera.Tck_Fec) DESC, vehiculo.Veh_Pla ASC";
            } else {
                // Detallado
                $sql = "SELECT
                        ticket_cantera.Tck_Fec,
                        DATE_FORMAT(ticket_cantera.Tck_Fec, '%Y-%m') AS mes,
                        ticket_cantera.Tck_Num,
                        CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_nombre,
                        vehiculo.Veh_Pla,
                        1 AS Cant_Volq,
                        ticket_cantera.Tck_Tot AS Val_Uni,
                        $veh_tip_desc AS Veh_Tip_Desc,
                        COALESCE(ticket_cantera.Tck_Pag, 'E') AS Tck_Pag,
                        ticket_cantera.Tck_Tot,
                        (CASE WHEN COALESCE(ticket_cantera.Tck_Pag,'E') = 'C' THEN ticket_cantera.Tck_Tot ELSE 0 END) AS total_credito,
                        (CASE WHEN COALESCE(ticket_cantera.Tck_Pag,'E') = 'C' THEN 0 ELSE ticket_cantera.Tck_Tot END) AS total_efectivo
                        FROM ticket_cantera
                        INNER JOIN vehiculo ON ticket_cantera.Veh_Cod = vehiculo.Veh_Cod
                        INNER JOIN cliente ON ticket_cantera.Cli_Cod = cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        WHERE $where
                        ORDER BY ticket_cantera.Tck_Fec DESC, ticket_cantera.Tck_Cod DESC";
            }

                   
            break;

        // Anular ticket (cambiar estado a I)
        case 60:
            $sql = "UPDATE ticket_cantera SET Tck_Est='I' WHERE Tck_Cod='$Par_Sql[Tck_Cod]'";
            break;

        // INSERT vehículo
        case 70:
            $Veh_Mar = isset($Par_Sql['Veh_Mar']) ? addslashes($Par_Sql['Veh_Mar']) : '';
            $Veh_Pla = isset($Par_Sql['Veh_Pla']) ? addslashes($Par_Sql['Veh_Pla']) : '';
            $Veh_Col = isset($Par_Sql['Veh_Col']) ? addslashes($Par_Sql['Veh_Col']) : '';
            $Veh_Cap = isset($Par_Sql['Veh_Cap']) ? floatval($Par_Sql['Veh_Cap']) : 0;
            $Veh_Tip = isset($Par_Sql['Veh_Tip']) ? addslashes($Par_Sql['Veh_Tip']) : '';
            $Veh_Tit = isset($Par_Sql['Veh_Tit']) ? addslashes($Par_Sql['Veh_Tit']) : '';
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? intval($Par_Sql['Cli_Cod']) : 0;
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;

            $Veh_Mar_value = !empty($Veh_Mar) ? "'$Veh_Mar'" : "NULL";
            $Veh_Pla_value = !empty($Veh_Pla) ? "'$Veh_Pla'" : "NULL";
            $Veh_Col_value = !empty($Veh_Col) ? "'$Veh_Col'" : "NULL";
            $Veh_Cap_value = $Veh_Cap > 0 ? $Veh_Cap : "NULL";
            $Veh_Tip_value = !empty($Veh_Tip) ? "'$Veh_Tip'" : "NULL";
            $Veh_Tit_value = !empty($Veh_Tit) ? "'$Veh_Tit'" : "NULL";
            $Cli_Cod_value = $Cli_Cod > 0 ? $Cli_Cod : "NULL";
            $Emp_Cod_value = $Emp_Cod > 0 ? $Emp_Cod : "NULL";

            $sql = "INSERT INTO vehiculo(Emp_Cod, Veh_Mar, Veh_Pla, Veh_Col, Veh_Cap, Veh_Tip, Veh_Tit, Cli_Cod, Veh_Est, Prv_Cod) 
                    VALUES($Emp_Cod_value, $Veh_Mar_value, $Veh_Pla_value, $Veh_Col_value, $Veh_Cap_value, $Veh_Tip_value, $Veh_Tit_value, $Cli_Cod_value, 'A', NULL)";
            break;

        default:
            $sql = "";
            break;
    }
    return $sql;
}
