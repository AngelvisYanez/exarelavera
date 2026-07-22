<?php

/**
 * Retorna consulta sql para estado de cuenta
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
 */

function sentencias_estado_cuenta($id, $Par_Sql) {
	switch ($id) {
		case 1:
            // Cargar datos del grid principal con filtros
            $wherefiltro = '';
			$wherefecha = '';

            // Filtro por búsqueda
            if (isset($Par_Sql['filtro']) && isset($Par_Sql['search']) && $Par_Sql['search'] !== '') {
                $val = addslashes($Par_Sql['search']);
                switch ($Par_Sql['filtro']) {
                    case 'cl': // Por Cliente
                        $wherefiltro = " AND (persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%')";
                        break;
                    case 'c': // Por Cedula/RUC
                        $wherefiltro = " AND persona_cli.Prs_Ced LIKE '%$val%'";
                        break;
                    case 'r': // Por Responsable
                        $wherefiltro = " AND (persona_usr.Prs_Nom LIKE '%$val%' OR persona_usr.Prs_Ape LIKE '%$val%')";
                        break;
                }
            }
		
            // Filtro por Planta
            if (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '') {
                $val = addslashes($Par_Sql['Pla_Cod']);
                $wherefiltro .= " AND manifiesto_plantas.Pla_Cod = '$val'";
            }

            // Filtro por fecha
            if (isset($Par_Sql['Fec_IniM']) && isset($Par_Sql['Fec_FinM']) && $Par_Sql['Fec_IniM'] !== '' && $Par_Sql['Fec_FinM'] !== '') {
                $wherefecha = " AND manifiesto_anticipo.Ama_Fec BETWEEN '" . addslashes($Par_Sql['Fec_IniM']) . "' AND '" . addslashes($Par_Sql['Fec_FinM']) . "'";
            }

            $sql = "SELECT manifiesto_anticipo.*, 
                        banco.Ban_Cod, banco.Pld_Cod, banco.Ban_Cue, banco.Ban_Obs, 
                        bancos.Bak_Cod, bancos.Bak_Des, 
                        det_plan.Pld_Des,
						tipos_pago.Pag_Cod, tipos_pago.Pag_Des, 
                        usuarios.Usu_Cod, usuarios.Usu_Ced, 
                        persona_usr.Prs_Ced as Usu_Ced,
                        CONCAT ( tipo_asien.Tia_Abr, '-', MONTH (comprobantes.Com_Fec), '-', comprobantes.Com_Num ) AS codigoAnti,
						CONCAT(persona_usr.Prs_Nom, ' ', persona_usr.Prs_Ape) as Responsable,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
                        persona_cli.Prs_Ced as Cli_Ced,
						anticipos_clientes.Com_Cod, anticipos_clientes.Ant_Val, anticipos_clientes.Ant_Doc,
                        0 as Abono,
                        (manifiesto_anticipo.Ama_Val - 0) as Saldo

                    FROM manifiesto_anticipo
                        LEFT JOIN banco ON manifiesto_anticipo.Ban_Cod = banco.Ban_Cod
                        LEFT JOIN bancos ON manifiesto_anticipo.Bak_Cod = bancos.Bak_Cod
                        LEFT JOIN det_plan ON banco.Pld_Cod = det_plan.Pld_Cod
                        LEFT JOIN usuarios ON manifiesto_anticipo.Usu_Cod = usuarios.Usu_Cod
                        LEFT JOIN persona AS persona_usr ON usuarios.Prs_Cod = persona_usr.Prs_Cod
                        LEFT JOIN manifiesto_plantas ON manifiesto_anticipo.Pla_Cod = manifiesto_plantas.Pla_Cod
                        LEFT JOIN cliente ON manifiesto_plantas.Cli_Cod = cliente.Cli_Cod
                        LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                        LEFT JOIN tipos_pago ON manifiesto_anticipo.Ama_Tde = tipos_pago.Pag_Cod
                        LEFT JOIN anticipos_clientes ON anticipos_clientes.Ama_Cod = manifiesto_anticipo.Ama_Cod AND anticipos_clientes.Ant_Est = 'A'
                        INNER JOIN comprobantes ON comprobantes.Com_Cod = anticipos_clientes.Com_Cod
                        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod = comprobantes.Tia_Cod
                    WHERE manifiesto_anticipo.Ama_Est = 'A' AND manifiesto_anticipo.Ama_Tip='A'
                        $wherefecha $wherefiltro
                    ORDER BY manifiesto_anticipo.Ama_Fec DESC, manifiesto_anticipo.Ama_Cod DESC;";
            return $sql;

        case 2:
            // Cargar detalle/balance para un cliente específico (UNION Anticipos + Manifiestos)
            $Cli_Cod = addslashes($Par_Sql['Cli_Cod']);
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) ? addslashes($Par_Sql['Fec_Ini']) : '';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) ? addslashes($Par_Sql['Fec_Fin']) : '';
            $Mes_Cod = isset($Par_Sql['Mes_Cod']) ? addslashes($Par_Sql['Mes_Cod']) : '00';
            
            // Filtros de Fecha
            $wherefecha_ma = '';
            $wherefecha_m = '';
            if ($Fec_Ini !== '' && $Fec_Fin !== '') {
                $wherefecha_ma = " AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
                $wherefecha_m = " AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
            }

            // Filtro por Planta (Pla_Cod)
            $where_pla_ma = '';
            $where_pla_m = '';
            if ($Pla_Cod !== '') {
                $where_pla_ma = " AND ma.Pla_Cod = '$Pla_Cod'";
                $where_pla_m = " AND m.Pla_Cod = '$Pla_Cod'";
            }
            
            // Variables para filtros del saldo inicial (mismos filtros de planta)
            $where_pla_ma_prev = $where_pla_ma;
            $where_pla_m_prev = $where_pla_m;

            $sql = "(SELECT 
                        0 as Ama_Cod,
                        '$Fec_Ini' as Fecha,
                        '' as Documento,
                        'Saldo Inicial' as FormaPago,
                        '' as CuentaBancaria,
                        0 as Valor,
                        0 as Abono,
                        '' as Estado,
                        '' as Responsable,
                        CONCAT('SALDO AL ', DATE_FORMAT(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY), '%d'), ', de ', ELT(MONTH(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY)), 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'), ', ', DATE_FORMAT(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY), '%Y')) as Detalle,
                        0 as CantViajes,
                        DATE_SUB('$Fec_Ini', INTERVAL 1 SECOND) as FechaOrden,
                        '' as codigoAnti,
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val) 
                                        FROM manifiesto_anticipo ma 
                                        WHERE ma.Cli_Cod = '$Cli_Cod' 
                                        AND ma.Ama_Est = 'A' 
                                        AND ma.Ama_Tip = 'A'
                                        AND ma.Ama_Fec < '$Fec_Ini' 
                                        $where_pla_ma_prev), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) 
                                        FROM manifiesto m 
                                            LEFT JOIN ventas v ON m.Vet_Cod = v.Vet_Cod
                                        WHERE m.Cli_Cod = '$Cli_Cod' 
                                        AND m.Man_Est = 'A' 
                                        AND m.Man_Tes NOT IN ('R') 
                                        AND m.Man_Fec < '$Fec_Ini' 
                                        $where_pla_m_prev), 0)
                        ) as Saldo_Inicial_Hidden
                    FROM DUAL)
                    
                    UNION ALL

                    (SELECT 
                        ma.Ama_Cod,
                        ma.Ama_Fec as Fecha,
                        ma.Ama_Doc as Documento,
                        tp.Pag_Des as FormaPago,
                        b.Ban_Cue as CuentaBancaria,
                        ma.Ama_Val as Valor, /*Ingreso*/
                        0 as Abono,          /*Egreso*/
                        ma.Ama_Tip as Estado,
                        CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) as Responsable,
                        CONCAT('Anticipo - ', IFNULL(ma.Ama_Obs, '')) as Detalle,
                        0 as CantViajes,
                        ma.Ama_Fec as FechaOrden,
                        CONCAT(ta.Tia_Abr, '-', MONTH(c.Com_Fec), '-', c.Com_Num) as codigoAnti,
                        0 as Saldo_Inicial_Hidden
                    FROM manifiesto_anticipo ma
                        LEFT JOIN banco b ON ma.Ban_Cod = b.Ban_Cod
                        LEFT JOIN tipos_pago tp ON ma.Ama_Tde = tp.Pag_Cod
                        LEFT JOIN usuarios u ON ma.Usu_Cod = u.Usu_Cod
                        LEFT JOIN persona AS pu ON u.Prs_Cod = pu.Prs_Cod
                        LEFT JOIN anticipos_clientes ac ON ac.Ama_Cod = ma.Ama_Cod AND ac.Ant_Est = 'A'
                        LEFT JOIN comprobantes c ON c.Com_Cod = ac.Com_Cod
                        LEFT JOIN tipo_asien ta ON ta.Tia_Cod = c.Tia_Cod
                    WHERE ma.Cli_Cod = '$Cli_Cod'
                        AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                        $wherefecha_ma
                        $where_pla_ma)

                    UNION ALL

                    (SELECT 
                        m.Man_Cod as Ama_Cod,
                        m.Man_Fec as Fecha,
                        CONCAT('M', m.Pla_Cod, '-', LPAD(m.Man_Num, 4, '0')) as Documento,
                        'Manifiesto' as FormaPago,
                        '' as CuentaBancaria,
                        0 as Valor, /*Ingreso*/
                        ((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) as Abono, /*Egreso (Valor Manifiesto)*/
                        m.Man_Tip as Estado,
                        '' as Responsable,
                        CONCAT('Factura Nº ', IFNULL(v.Vet_Num, ''), ' - Fec: ', IFNULL(ca.Caj_Fec, '')) as Detalle,
                        0 as CantViajes,
                        m.Man_Fec as FechaOrden,
                        '' as codigoAnti,
                        0 as Saldo_Inicial_Hidden
                    FROM manifiesto m
                    LEFT JOIN ventas v ON m.Vet_Cod = v.Vet_Cod
                    LEFT JOIN caja_aper ca ON v.Caj_Cod = ca.Caj_Cod
                    WHERE m.Cli_Cod = '$Cli_Cod'
                        AND m.Man_Est = 'A'
                        AND m.Man_Tes NOT IN ('R')
                        $wherefecha_m
                        $where_pla_m)

                    ORDER BY FechaOrden ASC;";
            return $sql;

        case 18:
            // Detalle optimizado para Consolidado (agrupa consumos por comprobante/factura)
            $Cli_Cod = addslashes($Par_Sql['Cli_Cod']);
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) ? addslashes($Par_Sql['Fec_Ini']) : '';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) ? addslashes($Par_Sql['Fec_Fin']) : '';

            $wherefecha_ma = '';
            $wherefecha_m = '';
            if ($Fec_Ini !== '' && $Fec_Fin !== '') {
                $wherefecha_ma = " AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
                $wherefecha_m = " AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
            }

            $where_pla_ma = '';
            $where_pla_m = '';
            if ($Pla_Cod !== '') {
                $where_pla_ma = " AND ma.Pla_Cod = '$Pla_Cod'";
                $where_pla_m = " AND m.Pla_Cod = '$Pla_Cod'";
            }

            $where_pla_ma_prev = $where_pla_ma;
            $where_pla_m_prev = $where_pla_m;

            $sql = "(SELECT
                        0 as Ama_Cod,
                        '$Fec_Ini' as Fecha,
                        '' as Documento,
                        'Saldo Inicial' as FormaPago,
                        '' as CuentaBancaria,
                        0 as Valor,
                        0 as Abono,
                        '' as Estado,
                        '' as Responsable,
                        CONCAT('SALDO AL ', DATE_FORMAT(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY), '%d'), ', de ', ELT(MONTH(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY)), 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'), ', ', DATE_FORMAT(DATE_SUB('$Fec_Ini', INTERVAL 1 DAY), '%Y')) as Detalle,
                        0 as CantViajes,
                        DATE_SUB('$Fec_Ini', INTERVAL 1 SECOND) as FechaOrden,
                        '' as codigoAnti,
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val)
                                      FROM manifiesto_anticipo ma
                                      WHERE ma.Cli_Cod = '$Cli_Cod'
                                        AND ma.Ama_Est = 'A'
                                        AND ma.Ama_Fec < '$Fec_Ini'
                                        $where_pla_ma_prev), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                      FROM manifiesto m
                                      WHERE m.Cli_Cod = '$Cli_Cod'
                                        AND m.Man_Est = 'A'
                                        AND m.Man_Tes NOT IN ('R')
                                        AND m.Man_Fec < '$Fec_Ini'
                                        $where_pla_m_prev), 0)
                        ) as Saldo_Inicial_Hidden
                    FROM DUAL)
                    UNION ALL
                    (SELECT
                        ma.Ama_Cod,
                        ma.Ama_Fec as Fecha,
                        ma.Ama_Doc as Documento,
                        tp.Pag_Des as FormaPago,
                        b.Ban_Cue as CuentaBancaria,
                        ma.Ama_Val as Valor,
                        0 as Abono,
                        ma.Ama_Tip as Estado,
                        CONCAT(pu.Prs_Nom, ' ', pu.Prs_Ape) as Responsable,
                        CONCAT('Anticipo - ', IFNULL(ma.Ama_Obs, '')) as Detalle,
                        0 as CantViajes,
                        ma.Ama_Fec as FechaOrden,
                        CONCAT(ta.Tia_Abr, '-', MONTH(c.Com_Fec), '-', c.Com_Num) as codigoAnti,
                        0 as Saldo_Inicial_Hidden
                    FROM manifiesto_anticipo ma
                        LEFT JOIN banco b ON ma.Ban_Cod = b.Ban_Cod
                        LEFT JOIN tipos_pago tp ON ma.Ama_Tde = tp.Pag_Cod
                        LEFT JOIN usuarios u ON ma.Usu_Cod = u.Usu_Cod
                        LEFT JOIN persona AS pu ON u.Prs_Cod = pu.Prs_Cod
                        LEFT JOIN anticipos_clientes ac ON ac.Ama_Cod = ma.Ama_Cod AND ac.Ant_Est = 'A'
                        LEFT JOIN comprobantes c ON c.Com_Cod = ac.Com_Cod
                        LEFT JOIN tipo_asien ta ON ta.Tia_Cod = c.Tia_Cod
                    WHERE ma.Cli_Cod = '$Cli_Cod'
                      AND ma.Ama_Est = 'A'
                      $wherefecha_ma
                      $where_pla_ma)
                    UNION ALL
                    (SELECT
                        MIN(m.Man_Cod) as Ama_Cod,
                        MAX(m.Man_Fec) as Fecha,
                        IFNULL(v.Vet_Num, 'S/N') as Documento,
                        'Comprobante' as FormaPago,
                        CONCAT('ABONO FACTS. /', IFNULL(v.Vet_Num, 'S/N')) as CuentaBancaria,
                        0 as Valor,
                        SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) as Abono,
                        'C' as Estado,
                        '' as Responsable,
                        CONCAT('Comprobante: ', IFNULL(v.Vet_Num, 'S/N')) as Detalle,
                        COUNT(*) as CantViajes,
                        MAX(m.Man_Fec) as FechaOrden,
                        '' as codigoAnti,
                        0 as Saldo_Inicial_Hidden
                    FROM manifiesto m
                        LEFT JOIN ventas v ON m.Vet_Cod = v.Vet_Cod
                    WHERE m.Cli_Cod = '$Cli_Cod'
                      AND m.Man_Est = 'A'
                      AND m.Man_Tes NOT IN ('R')
                      AND m.Man_Tip = 'F'
                      $wherefecha_m
                      $where_pla_m
                    GROUP BY IFNULL(v.Vet_Num, 'S/N'))
                    ORDER BY FechaOrden ASC;";
            return $sql;

        case 12:
            // Obtener datos de cabecera del cliente (Nombre, RUC, Cuenta Bancaria Predeterminada)
            $Cli_Cod = addslashes($Par_Sql['Cli_Cod']);
            // Intentar obtener la cuenta bancaria del último anticipo registrado para este cliente
            // Si no tiene anticipos, saldrá vacía la cuenta, pero al menos tendremos los datos del cliente.
            $sql = "SELECT 
                        CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Cliente,
                        p.Prs_Ced,
                        COALESCE((SELECT b.Ban_Cue 
                                    FROM manifiesto_anticipo ma 
                                        INNER JOIN banco b ON ma.Ban_Cod = b.Ban_Cod
                                    WHERE ma.Cli_Cod = c.Cli_Cod AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                                    ORDER BY ma.Ama_Fec DESC LIMIT 1), '') as Ban_Cue
                    FROM cliente c
                    INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                    WHERE c.Cli_Cod = '$Cli_Cod';";
            return $sql;

        case 3:
            // Obtener lista de clientes para dropdown
            $sql = "SELECT DISTINCT 
                        cliente.Cli_Cod, 
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Cliente,
                        persona.Prs_Ced
                    FROM cliente
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN manifiesto_anticipo ON cliente.Cli_Cod = manifiesto_anticipo.Cli_Cod
                    WHERE cliente.Cli_Est = 'A'
                        AND manifiesto_anticipo.Ama_Tip = 'A'
                        AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
                    ORDER BY persona.Prs_Nom, persona.Prs_Ape;";
            return $sql;

        case 4:
            // Obtener lista de responsables (usuarios) para dropdown
            $sql = "SELECT DISTINCT 
                        usuarios.Usu_Cod, 
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) as Responsable,
                        persona.Prs_Ced
                    FROM usuarios
                        INNER JOIN persona ON usuarios.Prs_Cod = persona.Prs_Cod
                        INNER JOIN manifiesto_anticipo ON usuarios.Usu_Cod = manifiesto_anticipo.Usu_Cod
                    WHERE usuarios.Usu_Est = 'A'
                        AND manifiesto_anticipo.Ama_Tip = 'A'
                    ORDER BY persona.Prs_Nom, persona.Prs_Ape;";
            return $sql;

        case 5:
            // Obtener resumen de cuenta por cliente (Desglosado)
            $Cli_Cod = addslashes($Par_Sql['Cli_Cod']);
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) && $Par_Sql['Fec_Ini'] != '' ? addslashes($Par_Sql['Fec_Ini']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) && $Par_Sql['Fec_Fin'] != '' ? addslashes($Par_Sql['Fec_Fin']) : '2099-12-31';
            
            $where_pla_ma = '';
            $where_pla_m = '';
            if ($Pla_Cod !== '') {
                $where_pla_ma = " AND ma.Pla_Cod = '$Pla_Cod'";
                $where_pla_m = " AND m.Pla_Cod = '$Pla_Cod'";
            }

            // Subqueries for Saldo Inicial
            // Ingresos < Fec_Ini
            $sql_ing_ini = "SELECT COALESCE(SUM(ma.Ama_Val), 0) FROM manifiesto_anticipo ma WHERE ma.Cli_Cod = '$Cli_Cod' AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A' AND ma.Ama_Fec < '$Fec_Ini' $where_pla_ma";
            // Egresos < Fec_Ini
            $sql_egr_ini = "SELECT COALESCE(SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)), 0) FROM manifiesto m WHERE m.Cli_Cod = '$Cli_Cod' AND m.Man_Est = 'A' AND m.Man_Tes NOT IN ('R') AND m.Man_Fec < '$Fec_Ini' $where_pla_m";

            // Current Period Ranges
            $where_date_ma = " AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";
            $where_date_m = " AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'";

            $sql = "SELECT 
                        (($sql_ing_ini) - ($sql_egr_ini)) as SaldoInicial,
                        
                        (SELECT COALESCE(SUM(ma.Ama_Val), 0)
                        FROM manifiesto_anticipo ma
                        LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                        WHERE ma.Cli_Cod = '$Cli_Cod' AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                            AND (tp.Pag_Abr IN ('DEP', 'TRF') OR tp.Pag_Abr IS NULL)
                            $where_pla_ma $where_date_ma) as Depositos,
                            
                        (SELECT COALESCE(SUM(ma.Ama_Val), 0)
                        FROM manifiesto_anticipo ma
                        LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                        WHERE ma.Cli_Cod = '$Cli_Cod' AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                            AND tp.Pag_Abr = 'RET'
                            $where_pla_ma $where_date_ma) as Retenciones,
                            
                        (SELECT COALESCE(SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)), 0)
                        FROM manifiesto m
                        WHERE m.Cli_Cod = '$Cli_Cod' AND m.Man_Est = 'A' AND m.Man_Tes NOT IN ('R')
                            AND m.Man_Tip = 'F'
                            $where_pla_m $where_date_m) as ManifiestosFact,
                            
                        (SELECT COALESCE(SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)), 0)
                        FROM manifiesto m
                        WHERE m.Cli_Cod = '$Cli_Cod' AND m.Man_Est = 'A' AND m.Man_Tes NOT IN ('R')
                            AND (m.Man_Tip != 'F' OR m.Man_Tip IS NULL)
                            $where_pla_m $where_date_m) as ManifiestosPend;";
            return $sql;

        case 6:
            // Buscar plantas (manifiesto_plantas)
            $wherefiltro = '';
            $search = isset($Par_Sql['search']) ? trim($Par_Sql['search']) : '';
            
            if ($search !== '') {
                $val = addslashes($search);
                $wherefiltro = " AND (manifiesto_plantas.Pla_Nom LIKE '%$val%' 
                                    OR /*ciudad.Ciu_Des LIKE '%$val%' */ CONCAT(persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%')
                                    OR manifiesto_plantas.Pla_Cod LIKE '%$val%')";
            }
            
            $sql = "SELECT manifiesto_plantas.Pla_Cod, 
                        manifiesto_plantas.Pla_Nom,
                        ciudad.Ciu_Des,
                        CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
                        manifiesto_plantas.Cli_Cod
                    FROM manifiesto_plantas
                        LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
                        LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
                        LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
                    WHERE manifiesto_plantas.Pla_Est = 'A'
                        AND (cliente.Emp_Cod = '{$_SESSION['Ses_Emp_Cod']}' OR manifiesto_plantas.Cli_Cod IS NULL)
                        $wherefiltro
                    ORDER BY manifiesto_plantas.Pla_Nom
                    LIMIT 1000;";
            return $sql;

        case 7:
            // Obtener planta y cliente asignado al usuario
            $Usu_Cod = addslashes($Par_Sql['Usu_Cod']);
            $sql = "SELECT 
                        manifiesto_usuario.* , 
                        cliente.* , 
                        concat (persona.Prs_Nom, ' ', persona.Prs_Ape) AS  nombre, 
                        persona.Prs_Ced, 
                        manifiesto_plantas.Pla_Nom, 
                        manifiesto_plantas.Pla_Lic 
                    FROM 
                        manifiesto_usuario 
                        INNER JOIN cliente ON cliente.Cli_Cod =  manifiesto_usuario.Cli_Cod 
                        INNER JOIN persona ON persona.Prs_Cod =  cliente.Prs_Cod 
                        LEFT JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod =  manifiesto_usuario.Pla_Cod 
                    WHERE 
                        (`manifiesto_usuario`.`Usu_Cod` = '$Usu_Cod' ) 
                    GROUP BY 
                        cliente.Cli_Cod;";
            return $sql;

        case 8:
            // Verificar si el usuario tiene el perfil "Plantas"
            $Usu_Cod = addslashes($Par_Sql['Usu_Cod']);
            $sql = "SELECT COUNT(*) as count
                    FROM usuarperfi up
                        INNER JOIN perfiles p ON up.Per_Cod = p.Per_Cod
                    WHERE up.Usu_Cod = '$Usu_Cod' 
                    AND p.Per_Des = 'Plantas';";
            return $sql;

        case 9:
            // Consulta grupal: Estado de cuenta agrupado por planta
            $Fec_Ini = isset($Par_Sql['Fec_IniM']) && $Par_Sql['Fec_IniM'] != '' ? addslashes($Par_Sql['Fec_IniM']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_FinM']) && $Par_Sql['Fec_FinM'] != '' ? addslashes($Par_Sql['Fec_FinM']) : '2099-12-31';

            $sql = "SELECT 
                        mp.Pla_Cod,
                        mp.Pla_Nom as Planta,
                        
                        -- Saldo Inicial (antes del periodo)
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val) 
                                        FROM manifiesto_anticipo ma 
                                        WHERE ma.Pla_Cod = mp.Pla_Cod 
                                        AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                                        AND ma.Ama_Fec < '$Fec_Ini'), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) 
                                        FROM manifiesto m 
                                        WHERE m.Pla_Cod = mp.Pla_Cod 
                                        AND m.Man_Est = 'A' 
                                        AND m.Man_Tes NOT IN ('R') 
                                        AND m.Man_Fec < '$Fec_Ini'), 0)
                        ) as Saldo_Inicial,
                        
                        -- Depositos (en el periodo)
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                    FROM manifiesto_anticipo ma
                                    LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                    WHERE ma.Pla_Cod = mp.Pla_Cod 
                                    AND ma.Ama_Est = 'A'  AND ma.Ama_Tip = 'A'
                                    AND (tp.Pag_Abr IN ('DEP', 'TRF') OR tp.Pag_Abr IS NULL)
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Depositos,
                        
                        -- Retenciones (en el periodo)
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                    FROM manifiesto_anticipo ma
                                    LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                    WHERE ma.Pla_Cod = mp.Pla_Cod 
                                    AND ma.Ama_Est = 'A'  AND ma.Ama_Tip = 'A'
                                    AND tp.Pag_Abr = 'RET'
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Retenciones,
                        
                        -- Manifiestos Facturados (en el periodo)
                        COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                    FROM manifiesto m
                                    WHERE m.Pla_Cod = mp.Pla_Cod 
                                    AND m.Man_Est = 'A' 
                                    AND m.Man_Tes NOT IN ('R')
                                    AND m.Man_Tip = 'F'
                                    AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Manifiestos_Fact,
                        
                        -- Manifiestos Pendientes (en el periodo)
                        COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                    FROM manifiesto m
                                    WHERE m.Pla_Cod = mp.Pla_Cod 
                                    AND m.Man_Est = 'A' 
                                    AND m.Man_Tes NOT IN ('R')
                                    AND (m.Man_Tip != 'F' OR m.Man_Tip IS NULL)
                                    AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Manifiestos_Pend
                        
                    FROM manifiesto_plantas mp
                    WHERE mp.Pla_Est = 'A'
                    GROUP BY mp.Pla_Cod, mp.Pla_Nom
                    ORDER BY mp.Pla_Nom;";
            return $sql;

        case 10:
            // Misma lógica que el reporte grupal (caso 9), una sola planta
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_IniM']) && $Par_Sql['Fec_IniM'] != '' ? addslashes($Par_Sql['Fec_IniM']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_FinM']) && $Par_Sql['Fec_FinM'] != '' ? addslashes($Par_Sql['Fec_FinM']) : '2099-12-31';

            $sql = "SELECT 
                        mp.Pla_Cod,
                        mp.Pla_Nom as Planta,
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val) 
                                        FROM manifiesto_anticipo ma 
                                        WHERE ma.Pla_Cod = mp.Pla_Cod 
                                        AND ma.Ama_Est = 'A' 
                                        AND ma.Ama_Fec < '$Fec_Ini'), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) 
                                        FROM manifiesto m 
                                        WHERE m.Pla_Cod = mp.Pla_Cod 
                                        AND m.Man_Est = 'A' 
                                        AND m.Man_Tes NOT IN ('R') 
                                        AND m.Man_Fec < '$Fec_Ini'), 0)
                        ) as Saldo_Inicial,
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                    FROM manifiesto_anticipo ma
                                    LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                    WHERE ma.Pla_Cod = mp.Pla_Cod 
                                    AND ma.Ama_Est = 'A'
                                    AND (tp.Pag_Abr IN ('DEP', 'TRF') OR tp.Pag_Abr IS NULL)
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Depositos,
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                    FROM manifiesto_anticipo ma
                                    LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                    WHERE ma.Pla_Cod = mp.Pla_Cod 
                                    AND ma.Ama_Est = 'A'
                                    AND tp.Pag_Abr = 'RET'
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Retenciones,
                        COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                    FROM manifiesto m
                                    WHERE m.Pla_Cod = mp.Pla_Cod 
                                    AND m.Man_Est = 'A' 
                                    AND m.Man_Tes NOT IN ('R')
                                    AND m.Man_Tip = 'F'
                                    AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Manifiestos_Fact,
                        COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                    FROM manifiesto m
                                    WHERE m.Pla_Cod = mp.Pla_Cod 
                                    AND m.Man_Est = 'A' 
                                    AND m.Man_Tes NOT IN ('R')
                                    AND m.Man_Tip != 'F'
                                    AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) as Manifiestos_Pend
                    FROM manifiesto_plantas mp
                    WHERE mp.Pla_Est = 'A'
                        AND mp.Pla_Cod = '$Pla_Cod'
                    LIMIT 1;";
            return $sql;

        case 11:
            // Saldo "Anticipos (A)" como en Gestión de Manifiesto (MODELS/manifiesto_anticipo sqlByNumero 1)
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $sql = "SELECT 
                            CAST(SUM(saldo) AS DECIMAL(10,2)) AS saldo
                        FROM (
                            SELECT
                                CAST(
                                    SUM(Ant_Val) 
                                    - COALESCE((
                                        SELECT SUM(Ddc_Val)
                                        FROM det_ant_cccc 
                                        WHERE det_ant_cccc.Ant_Cod = anticipos_clientes.Ant_Cod
                                    ), 0)
                                AS DECIMAL(10,2)) AS saldo
                            FROM manifiesto_anticipo
                            INNER JOIN anticipos_clientes 
                                ON manifiesto_anticipo.Ama_Cod = anticipos_clientes.Ama_Cod
                            WHERE manifiesto_anticipo.Pla_Cod = '$Pla_Cod'
                            AND anticipos_clientes.Ant_Est IN ('A','U')
                            GROUP BY anticipos_clientes.Ant_Cod
                        ) AS tabla_saldos";
            return $sql;

        case 13:
            // "Sin facturar (B)" como en man_alt_manifiesto (manifiestos activos sin Vet_Cod)
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? addslashes($Par_Sql['Cli_Cod']) : '';
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? addslashes($Par_Sql['Emp_Cod']) : '';
            $sql = "SELECT COALESCE(SUM(CAST(manifiesto.Man_Pes * (manifiesto.Man_Pun / 1000) AS DECIMAL(10,2))), 0) AS saldo
                    FROM manifiesto
                    INNER JOIN cliente ON cliente.Cli_Cod = manifiesto.Cli_Cod
                    WHERE manifiesto.Cli_Cod = '$Cli_Cod'
                    AND manifiesto.Man_Est = 'A'
                    AND cliente.Emp_Cod = '$Emp_Cod'
                    AND manifiesto.Pla_Cod = '$Pla_Cod'
                    AND (manifiesto.Vet_Cod IS NULL OR manifiesto.Vet_Cod = 0)";
            return $sql;

        case 14:
            // Perfiles con derecho a la pestaña Comparación (administradores / roles elevados)
            $Usu_Cod = addslashes($Par_Sql['Usu_Cod']);
            $sql = "SELECT COUNT(*) AS count
                    FROM usuarperfi up
                        INNER JOIN perfiles p ON up.Per_Cod = p.Per_Cod
                    WHERE up.Usu_Cod = '$Usu_Cod'
                    AND p.Per_Des IN ('Administrador de Sistemas', 'Admin_Oper', 'Gerente')";
            return $sql;

        case 15:
            // Validar planta de la empresa (para comparación admin)
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? addslashes($Par_Sql['Emp_Cod']) : '';
            $sql = "SELECT mp.Pla_Cod, mp.Pla_Nom, mp.Cli_Cod
                    FROM manifiesto_plantas mp
                        INNER JOIN cliente c ON c.Cli_Cod = mp.Cli_Cod
                    WHERE mp.Pla_Est = 'A'
                        AND mp.Pla_Cod = '$Pla_Cod'
                        AND c.Emp_Cod = '$Emp_Cod'
                    LIMIT 1;";
            return $sql;

        case 17:
            // Cantidad de manifiestos pendientes de facturar en el período (mismo criterio que ManifiestosPend, caso 5)
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? addslashes($Par_Sql['Cli_Cod']) : '';
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) && $Par_Sql['Fec_Ini'] != '' ? addslashes($Par_Sql['Fec_Ini']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) && $Par_Sql['Fec_Fin'] != '' ? addslashes($Par_Sql['Fec_Fin']) : '2099-12-31';
            $where_pla_m = '';
            if ($Pla_Cod !== '') {
                $where_pla_m = " AND m.Pla_Cod = '$Pla_Cod'";
            }
            $sql = "SELECT 
                        COUNT(CASE WHEN m.Man_Tip != 'F' THEN 1 END) AS ManifiestosPendCnt,
                        MAX(CASE WHEN m.Man_Tip = 'F' THEN DATE(m.Man_Fec) END) AS UltFecFact,
                        MAX(DATE(m.Man_Fec)) AS UltFecManGen
                    FROM manifiesto m
                    WHERE m.Cli_Cod = '$Cli_Cod'
                        AND m.Man_Est = 'A'
                        AND m.Man_Tes NOT IN ('R')
                        AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                        $where_pla_m";
            return $sql;

        case 19:
            // Saldos Virtual - detalle individual (Manifiesto / Transferencia-Deposito / Retenciones)
            $Cli_Cod = isset($Par_Sql['Cli_Cod']) ? addslashes($Par_Sql['Cli_Cod']) : '';
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) && $Par_Sql['Fec_Ini'] != '' ? addslashes($Par_Sql['Fec_Ini']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) && $Par_Sql['Fec_Fin'] != '' ? addslashes($Par_Sql['Fec_Fin']) : '2099-12-31';
            $Tip_Mov = isset($Par_Sql['Tip_Mov']) ? strtoupper(trim($Par_Sql['Tip_Mov'])) : 'MAN,TRF,RET';
            $tips = array_values(array_intersect(array_map('trim', explode(',', $Tip_Mov)), array('MAN', 'TRF', 'RET')));
            if (empty($tips)) {
                $tips = array('MAN', 'TRF', 'RET');
            }
            $includeMan = in_array('MAN', $tips, true);
            $includeTrf = in_array('TRF', $tips, true);
            $includeRet = in_array('RET', $tips, true);
            $agruparManDia = isset($Par_Sql['Agrupar_Man_Dia']) && (string) $Par_Sql['Agrupar_Man_Dia'] === '1';

            $where_pla_ma = '';
            $where_pla_m = '';
            if ($Pla_Cod !== '') {
                $where_pla_ma = " AND ma.Pla_Cod = '$Pla_Cod'";
                $where_pla_m = " AND m.Pla_Cod = '$Pla_Cod'";
            }

            $parts = array();

            // Saldo inicial siempre (base del acumulado)
            $parts[] = "(SELECT
                        0 AS IdMov,
                        '$Fec_Ini' AS Fecha,
                        'Saldo Inicial' AS Concepto,
                        0 AS Ingresos,
                        0 AS Egresos,
                        DATE_SUB('$Fec_Ini', INTERVAL 1 SECOND) AS FechaOrden,
                        0 AS TipoOrden,
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val)
                                      FROM manifiesto_anticipo ma
                                      WHERE ma.Cli_Cod = '$Cli_Cod'
                                        AND ma.Ama_Est = 'A'
                                        AND ma.Ama_Tip = 'A'
                                        AND ma.Ama_Fec < '$Fec_Ini'
                                        $where_pla_ma), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                      FROM manifiesto m
                                      WHERE m.Cli_Cod = '$Cli_Cod'
                                        AND m.Man_Est = 'A'
                                        AND m.Man_Tes NOT IN ('R')
                                        AND m.Man_Fec < '$Fec_Ini'
                                        $where_pla_m), 0)
                        ) AS Saldo_Inicial_Hidden
                    FROM DUAL)";

            if ($includeTrf || $includeRet) {
                $where_tipo_pago = '';
                if ($includeTrf && !$includeRet) {
                    $where_tipo_pago = " AND IFNULL(tp.Pag_Abr, '') <> 'RET'";
                } elseif ($includeRet && !$includeTrf) {
                    $where_tipo_pago = " AND IFNULL(tp.Pag_Abr, '') = 'RET'";
                }
                $parts[] = "(SELECT
                        ma.Ama_Cod AS IdMov,
                        ma.Ama_Fec AS Fecha,
                        CASE
                            WHEN IFNULL(tp.Pag_Abr, '') = 'RET' THEN
                                CONCAT('Retencion', IF(IFNULL(ma.Ama_Doc, '') = '', '', CONCAT(' No ', ma.Ama_Doc)))
                            ELSE
                                CONCAT('Transferencia/Deposito', IF(IFNULL(ma.Ama_Doc, '') = '', '', CONCAT(' No ', ma.Ama_Doc)))
                        END AS Concepto,
                        ma.Ama_Val AS Ingresos,
                        0 AS Egresos,
                        ma.Ama_Fec AS FechaOrden,
                        1 AS TipoOrden,
                        0 AS Saldo_Inicial_Hidden
                    FROM manifiesto_anticipo ma
                        LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                    WHERE ma.Cli_Cod = '$Cli_Cod'
                        AND ma.Ama_Est = 'A'
                        AND ma.Ama_Tip = 'A'
                        AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                        $where_pla_ma
                        $where_tipo_pago)";
            }

            if ($includeMan) {
                if ($agruparManDia) {
                    $parts[] = "(SELECT
                        '' AS IdMov,
                        DATE(m.Man_Fec) AS Fecha,
                        CONCAT('Manifiestos (Total: ', COUNT(*), ' manif.)') AS Concepto,
                        0 AS Ingresos,
                        SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) AS Egresos,
                        DATE(m.Man_Fec) AS FechaOrden,
                        2 AS TipoOrden,
                        0 AS Saldo_Inicial_Hidden
                    FROM manifiesto m
                    WHERE m.Cli_Cod = '$Cli_Cod'
                        AND m.Man_Est = 'A'
                        AND m.Man_Tes NOT IN ('R')
                        AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                        $where_pla_m
                    GROUP BY DATE(m.Man_Fec))";
                } else {
                    $parts[] = "(SELECT
                        m.Man_Cod AS IdMov,
                        m.Man_Fec AS Fecha,
                        CONCAT('Manifiesto No M', m.Pla_Cod, '-', LPAD(m.Man_Num, 4, '0')) AS Concepto,
                        0 AS Ingresos,
                        ((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0)) AS Egresos,
                        m.Man_Fec AS FechaOrden,
                        2 AS TipoOrden,
                        0 AS Saldo_Inicial_Hidden
                    FROM manifiesto m
                    WHERE m.Cli_Cod = '$Cli_Cod'
                        AND m.Man_Est = 'A'
                        AND m.Man_Tes NOT IN ('R')
                        AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'
                        $where_pla_m)";
                }
            }

            $sql = implode("\n\nUNION ALL\n\n", $parts)
                . "\n\nORDER BY DATE(FechaOrden) ASC, TipoOrden ASC, FechaOrden ASC, IdMov ASC";
            return $sql;

        case 20:
            // Saldos Virtual - resumen grupal por planta
            $Fec_Ini = isset($Par_Sql['Fec_Ini']) && $Par_Sql['Fec_Ini'] != '' ? addslashes($Par_Sql['Fec_Ini']) : '2000-01-01';
            $Fec_Fin = isset($Par_Sql['Fec_Fin']) && $Par_Sql['Fec_Fin'] != '' ? addslashes($Par_Sql['Fec_Fin']) : '2099-12-31';
            $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
            $search = isset($Par_Sql['search']) ? addslashes($Par_Sql['search']) : '';

            $where_mp = " WHERE mp.Pla_Est = 'A' ";
            if ($Pla_Cod !== '') {
                $where_mp .= " AND mp.Pla_Cod = '$Pla_Cod' ";
            }
            if ($search !== '') {
                $where_mp .= " AND mp.Pla_Nom LIKE '%$search%' ";
            }

            $sql = "SELECT
                        mp.Pla_Cod,
                        mp.Pla_Nom AS Planta,
                        (
                            COALESCE((SELECT SUM(ma.Ama_Val)
                                      FROM manifiesto_anticipo ma
                                      WHERE ma.Pla_Cod = mp.Pla_Cod
                                        AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                                        AND ma.Ama_Fec < '$Fec_Ini'), 0)
                            -
                            COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                      FROM manifiesto m
                                      WHERE m.Pla_Cod = mp.Pla_Cod
                                        AND m.Man_Est = 'A'
                                        AND m.Man_Tes NOT IN ('R')
                                        AND m.Man_Fec < '$Fec_Ini'), 0)
                        ) AS Saldo_Inicial,
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                  FROM manifiesto_anticipo ma
                                  LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                  WHERE ma.Pla_Cod = mp.Pla_Cod
                                    AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                                    AND IFNULL(tp.Pag_Abr, '') <> 'RET'
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) AS Anticipos,
                        COALESCE((SELECT SUM(ma.Ama_Val)
                                  FROM manifiesto_anticipo ma
                                  LEFT JOIN tipos_pago tp ON ma.Pag_Cod = tp.Pag_Cod
                                  WHERE ma.Pla_Cod = mp.Pla_Cod
                                    AND ma.Ama_Est = 'A' AND ma.Ama_Tip = 'A'
                                    AND tp.Pag_Abr = 'RET'
                                    AND ma.Ama_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) AS Anticipo_Retencion,
                        COALESCE((SELECT SUM((m.Man_Pes / 1000) * IFNULL(m.Man_Pun, 0))
                                  FROM manifiesto m
                                  WHERE m.Pla_Cod = mp.Pla_Cod
                                    AND m.Man_Est = 'A'
                                    AND m.Man_Tes NOT IN ('R')
                                    AND m.Man_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin'), 0) AS Manifiestos
                    FROM manifiesto_plantas mp
                    $where_mp
                    ORDER BY mp.Pla_Nom";
            return $sql;
    }
}
?>
