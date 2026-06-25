<?php

/**
 * Factura de venta - Consultas para manifiestos
 * Solo contiene los casos usados en fac_alt_fac_ven_mas.php y fac_param_manifiesto.php
 * Numeración secuencial desde 1
 */

function sentencias_manifiesto($id, $Par_Sql)
{
    $sql = '';
    switch ($id) {
        case 1: // Antes: 5 - Obtener periodos contables
            $sql = "SELECT Pec_Cod,Pec_Fei,Pec_Fef,CAST(SUBSTRING_INDEX(Pec_Fei,'-',1) AS char) AS Anio,perio_cont.Pla_Cod
                    FROM perio_cont
                    LEFT JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                    WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND Pec_Est='A' ORDER BY Pec_Fei DESC";
            break;

        case 2: // Antes: 7 - Obtener vendedor y punto de impresión
            $sql = "SELECT Vnd_Cod,vendedor.Pun_Cod,puntos_imp.Pun_Des 
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND puntos_imp.Pun_Est='A'";

            break;

        case 3: // Antes: 8 - Obtener autorizaciones
            $where_doc = "Tic_Sri='0' OR Tic_Sri='1' OR Tic_Sri='2' OR Tic_Sri='41' OR Tic_Sri='44' OR Tic_Sri='47' OR Tic_Sri='48' OR Tic_Sri='49' OR Tic_Sri='50' OR Tic_Sri='51' OR Tic_Sri='52'";
            if (isset($Par_Sql[2])) {
                $where_doc = "Tic_Sri='4' OR Tic_Sri='5'";
            }
            if (isset($Par_Sql[1]) && ($Par_Sql[1]) != 0) {
                $where = "autorizaci.Aut_Cod='$Par_Sql[1]'";
            } else {
                $where = "autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A'";
            }
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri
                    FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE ($where_doc) AND $where ";
            break;

        case 4: // Antes: 9 - Obtener datos vendedor y autorización
            $sql = "SELECT vendedor.Vnd_Cod,puntos_imp.Pun_Cod,vendedor.Prs_Cod,autorizaci.Aut_Cod,Aut_Sri,
                    Aut_Fci,Aut_Cad,Aut_Ini,Aut_Fin, Vet_Cod,Pun_Sri,CURDATE() AS Fec_Sys
                    FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN autorizaci ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    LEFT JOIN ventas ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    WHERE vendedor.Prs_Cod='$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' 
                    AND autorizaci.Tic_Cod='$Par_Sql[2]' AND autorizaci.Aut_Cod='$Par_Sql[3]'";

            break;

        case 5: // Antes: 10 - Obtener siguiente número de factura
            $sql = "SELECT
                        CASE
                            WHEN MAX(Vet_Num)IS NOT NULL AND MAX(Vet_Num)>=$Par_Sql[1] THEN (
                                SELECT MIN(t.Vet_Num)+1
                                FROM ventas t
                                INNER JOIN autorizaci AS ta ON t.Aut_Cod=ta.Aut_Cod
                                INNER JOIN puntos_imp AS tp ON tp.Pun_Cod = ta.Pun_Cod
                                WHERE tp.Suc_Cod=$Par_Sql[4] AND ta.Aut_Sri='$Par_Sql[2]' AND ta.Tic_Cod=$Par_Sql[3] AND ta.Pun_Sri='$Par_Sql[5]' AND t.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1] AND
                                NOT EXISTS (
                                    SELECT NULL FROM ventas n
                                        INNER JOIN autorizaci AS na ON n.Aut_Cod=na.Aut_Cod
                                        INNER JOIN puntos_imp AS np ON np.Pun_Cod = na.Pun_Cod
                                        WHERE n.Vet_Num=t.Vet_Num+1 AND np.Suc_Cod=$Par_Sql[4] AND na.Aut_Sri='$Par_Sql[2]' AND na.Pun_Sri='$Par_Sql[5]' AND na.Tic_Cod=$Par_Sql[3] AND n.Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]
                                    )
                               )
                        ELSE IFNULL(MAX(Vet_Num),$Par_Sql[0]-1)+1
                        END AS siguiente,count(Vet_Num) as contador, autorizaci.Aut_Tem 
                    FROM ventas
                    INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE Suc_Cod=$Par_Sql[4] AND autorizaci.Aut_Sri='$Par_Sql[2] ' AND autorizaci.Pun_Sri='$Par_Sql[5]' AND autorizaci.Tic_Cod=$Par_Sql[3] AND Vet_Num BETWEEN $Par_Sql[0] AND $Par_Sql[1]";

            break;

        case 6: // Antes: 11 - Validar existencia de documento
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND autorizaci.Pun_Sri='$Par_Sql[4]' AND Suc_Cod='$Par_Sql[0]' AND Vet_Num='$Par_Sql[2]'" . (!empty($Par_Sql[3]) ? "AND ventas.Vet_Cod<>$Par_Sql[3]" : '') . ';';
            break;

        case 7: // Antes: 12 - Configuración de facturación
            $sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
            break;

        case 8: // Antes: 16 - Obtener IVAs
            $sql = "SELECT * FROM iva WHERE Iva_Por>0 ORDER BY Iva_Ini DESC";
            break;

        case 9: // Antes: 28 - Obtener parámetro del plan
            $sql = "SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod FROM plan_param
                    INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                    INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                    WHERE Tpa_Abr='$Par_Sql[1]' AND Pla_Cod=$Par_Sql[0];";
            break;

        case 10: // Antes: 34 - Búsqueda de manifiestos
            $iva_cod = $Par_Sql['Iva_Cod'];
            $iva_por = $Par_Sql['Iva_Por'];
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(manifiesto.Man_Cod) AS total";
            } else {
                $campos = "manifiesto.*  , 0 AS total , concat(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente,Prs_Dir,persona.Ciu_Cod, ventas.Vet_Num,
                manifiesto.Man_Pes / 1000 AS Man_Pes, 
                manifiesto_plantas.Pla_Nom,
                ventas.Vet_Aut,
                CASE 
                WHEN ventas.Vet_Aut = 'S' THEN 'Autorizada' 
                WHEN ventas.Vet_Aut = 'N' THEN 'Sin_Autorizar'
                ELSE 'No Facturado' END  AS Vet_Aut_Des, 
                CASE 
                        WHEN manifiesto.Man_Tip = 'A' THEN 'Aprobado'
                        WHEN manifiesto.Man_Tip = 'P' THEN 'Pendiente'
                        WHEN manifiesto.Man_Tip = 'F' THEN 'Facturado'
                        WHEN manifiesto.Man_Tip = 'R' THEN 'Rechazado'
                        WHEN manifiesto.Man_Tip = 'GE' THEN 'Garita_Entrada'
                        WHEN manifiesto.Man_Tip = 'GS' THEN 'Garita_Salida'
                        ELSE 'Sin Estado'
                    END AS est_manifiesto, persona.Prs_Ced, 
                    ROUND(Man_Pun / (1 + ($iva_por/100)), 6) AS Man_Pun,
                    ROUND((Man_Pun / (1 + ($iva_por/100))) * (Man_Pes/1000), 2) AS subtotal,
                    ROUND((Man_Pun / (1 + ($iva_por/100))) * (Man_Pes/1000) * ($iva_por/100), 2) AS total_iva, 
                    ROUND(Man_Pun * (Man_Pes/1000), 2) AS total, $iva_cod AS Iva_Cod, $iva_por AS Iva_Por 
            ";
            }
            $search = '';
            if (!empty($Par_Sql['Pec_Cod'])) {
                // Periodo seleccionado: Usar rango del periodo y opcionalmente el mes
                $search .= " AND Man_Fes BETWEEN '{$Par_Sql['fecha_inicio']} 00:00:00' AND '{$Par_Sql['fecha_fin']} 23:59:59'";
                if (!empty($Par_Sql['Cmb_Mes'])) {
                    $search .= " AND MONTH(Man_Fes) = " . intval($Par_Sql['Cmb_Mes']);
                }
            } elseif (!empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                // Sin periodo: Usar rango de fechas manual
                $search .= " AND Man_Fes BETWEEN '{$Par_Sql['Fec_Ini']} 00:00:00' AND '{$Par_Sql['Fec_Fin']} 23:59:59'";
            }

            if (!empty($Par_Sql['Man_Tip'])) {
                $search .= " AND Man_Tip='{$Par_Sql['Man_Tip']}'";
            }
            if (!empty($Par_Sql['Pla_Cod'])) {
                $search .= " AND manifiesto.Pla_Cod='{$Par_Sql['Pla_Cod']}'";
            }
            if ($Par_Sql['op_opciones'] == 'd') {
                $search = " AND manifiesto.Man_Num = '$Par_Sql[search]'";
            } else {
                if ($Par_Sql['op_opciones'] == 'c')
                    $search .= " AND persona.Prs_Ced LIKE '$Par_Sql[search]%'";

                if ($Par_Sql['op_opciones'] == 'p'   && $Par_Sql['search'] != '') {
                    $nombre_busqueda = trim($Par_Sql['search']);
                    $search .= " AND ( CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$nombre_busqueda%'  OR CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) LIKE '%$nombre_busqueda%' )";
                }
            }
            $sql = "SELECT $campos FROM manifiesto
                INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod=manifiesto.Pla_Cod
             INNER JOIN cliente ON cliente.Cli_Cod=manifiesto.Cli_Cod
             INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
             LEFT join ventas on ventas.Vet_Cod=manifiesto.Vet_Cod
             WHERE  Man_Est = 'A' AND cliente.Emp_Cod = $Par_Sql[Emp_Cod]  $search  $Par_Sql[order] $Par_Sql[limits]; ";
            break;

        case 11: // Antes: 49 - Información de empresa y sucursal
            $sql = "SELECT empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
                    sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
            FROM empresas INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod) INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod) WHERE sucursal.Suc_Cod=$Par_Sql[0]";
            break;

        case 12: // Antes: 50 - Validar documento existe
            $sql = "SELECT COUNT(Vet_Cod)AS total FROM ventas
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod = ventas.Aut_Cod INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = autorizaci.Pun_Cod
                    WHERE autorizaci.Aut_Sri='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[0]' AND autorizaci.Pun_Sri=$Par_Sql[4]  AND    Vet_Num='$Par_Sql[2]'" . (!empty($Par_Sql[3]) ? "AND ventas.Vet_Cod<>$Par_Sql[3]" : '') . ';';
            break;

        case 13: // Antes: 55 - Insertar cuenta por cobrar
            $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'));";

            break;

        case 14: // Antes: 57 - Consultar detalle cuentas por cobrar
            $sql = "SELECT " . (!empty($Par_Sql[1]) ? "SUM(det_ccpp_c.Cpc_Val)" : "COUNT(det_ccpp_c.Cpc_Cod)") . "AS total FROM det_ccpp_c INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod WHERE Cpc_Cod='$Par_Sql[0]' " . (!empty($Par_Sql[1]) ? "AND Cpc_Est='$Par_Sql[1]' AND Com_Est='A'" : '') . ";";

            break;

        case 15: // Antes: 577 - Sumar retenciones
            $sql = "SELECT SUM(det_ccpp_c.Cpc_Val)AS total FROM det_ccpp_c
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
                    INNER JOIN tipos_pago ON det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod
                    WHERE Cpc_Cod='$Par_Sql[0]' AND det_ccpp_c.Cpc_Est='$Par_Sql[1]' AND Com_Est='A' and tipos_pago.Pag_Abr = 'RET'";

            break;

        case 16: // Antes: 61 - Obtener autorización
            $sql = "SELECT autorizaci.Aut_Sri, autorizaci.Pun_Sri, sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin FROM puntos_imp INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod) INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod) WHERE autorizaci.Aut_Cod ='$Par_Sql[0]';";
            break;

        case 17: // Antes: 70 - Insertar comprobante
            $sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]',
                 Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";

            break;

        case 18: // Antes: 72 - Insertar pago venta
            $sql = "INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num,Pld_Cod)
            VALUES ($Par_Sql[Vet_Cod]," . (empty($Par_Sql['Bak_Cod']) ? '1' : $Par_Sql['Bak_Cod']) . "," . (empty($Par_Sql['Ban_Cod']) ? 'NULL' : $Par_Sql['Ban_Cod']) . "," . $Par_Sql['Tipo_Cod'] . "," . (empty($Par_Sql['Vet_Cue']) ? 'NULL' : "'$Par_Sql[Vet_Cue]'") . ", " . (empty($Par_Sql['Vet_Che']) ? 'NULL' : "'$Par_Sql[Vet_Che]'") . ", $Par_Sql[Vet_Tot], '$Par_Sql[Vet_Num]'," . (empty($Par_Sql['Pag_Pld']) ? 'NULL' : "'$Par_Sql[Pag_Pld]'") . ")";

            break;

        case 19: // Antes: 76 - Consultar caja apertura
            $sql = "SELECT Caj_Cod,Pun_Cod,Caj_Fec FROM caja_aper WHERE Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
            break;

        case 20: // Antes: 77 - Insertar caja apertura
            $sql = "INSERT INTO caja_aper(Pun_Cod,Caj_Fec,Caj_Hoi,Caj_Est,Caj_Gen) VALUES('$Par_Sql[0]','$Par_Sql[1]',CURTIME(),'C','S')";

            break;

        case 21: // Antes: 80 - Obtener tipo asiento
            $sql = "SELECT * FROM tipo_asien where Tia_Cod=$Par_Sql[0]";
            break;

        case 22: // Antes: 83 - Insertar ventas comprobante
            $sql = "INSERT INTO ventas_compr(Com_Cod, Vet_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
            break;

        case 23: // Antes: 84 - Obtener cuenta producto
            $sql = "SELECT Pro_Cod,produ_plan.Pld_Cod,Tip_Pld,Pld_Cdc,Pld_Des,Pla_Cod FROM produ_plan 
            INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod 
            WHERE Pro_Cod=$Par_Sql[1] AND (Tip_Pld='$Par_Sql[2]' OR Tip_Pld='I') AND Pla_Cod=$Par_Sql[0]";

            break;

        case 24: // Antes: 85 - Obtener vendedor
            $sql = "SELECT * FROM vendedor INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod WHERE Suc_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1]";
            break;

        case 25: // Antes: 87 - Insertar asiento
            $sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5];";

            break;

        case 26: // Antes: 88 - Obtener cuenta IVA cobrado
            $sql = "SELECT iva_cobrad.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_cobrad
                    INNER JOIN det_plan ON det_plan.Pld_Cod=iva_cobrad.Pld_Cod WHERE Pla_Cod='$Par_Sql[0]'";

            break;

        case 27: // Antes: 140 - Insertar venta
            $sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod,
              Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Sri,Vnd_Cod_Aux)
                    VALUES ($Par_Sql[Tic_Cod], $Par_Sql[Cli_Cod], $Par_Sql[Ciu_Cod], $Par_Sql[Caj_Cod], $Par_Sql[Vnd_Cod], '$Par_Sql[Vet_Num]','$Par_Sql[Vet_Obs]', $Par_Sql[Aut_Cod], '$Par_Sql[Vet_Des]', '$Par_Sql[Vet_Hor]',
                        " . (empty($Par_Sql['Vet_Xml']) ? 'NULL' : "'$Par_Sql[Vet_Xml]'") . ",
                        " . (empty($Par_Sql['Vet_Aut']) ? 'NULL' : "'$Par_Sql[Vet_Aut]'") . ",
                        " . (!empty($Par_Sql['Ret_Num']) ? "'$Par_Sql[Ret_Num]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Ret_Fec']) ? "'$Par_Sql[Ret_Fec]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Ret_Aut']) ? "'$Par_Sql[Ret_Aut]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Tpc_Cod']) ? "'$Par_Sql[Tpc_Cod]'" : "NULL") . ",
                        " . (!empty($Par_Sql['Vet_Sri']) ? "$Par_Sql[Vet_Sri]" : "NULL") . ",
                        " . (!empty($Par_Sql['Vnd_Cod_Aux']) ? "$Par_Sql[Vnd_Cod_Aux]" : "NULL") . ")";

            break;

        case 28: // Antes: 161 - Obtener plan de cuentas
            $sql = "SELECT Pla_Cod FROM plan_cuenta WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]'";
            break;

        case 29: // Antes: 170 - Contar pagos de factura
            $sql = "SELECT COUNT(Dcc_Cod) AS tot_pago FROM ccpp_cobrar
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON det_ccpp_c.Com_Cod = comprobantes.Com_Cod
                    WHERE Vet_Cod = $Par_Sql[0] AND comprobantes.Com_Est='A'";
            break;

        case 30: // Antes: 179 - Actualizar manifiesto
            $sql = "UPDATE manifiesto SET Man_Tip = '$Par_Sql[1]', Vet_Cod = '$Par_Sql[2]', Man_Tes = CONCAT(COALESCE(Man_Tes,''), '-$Par_Sql[1]') WHERE Man_Cod = $Par_Sql[0];";

            break;

        case 77: // Actualizar múltiples manifiestos de una vez (UPDATE masivo)
            // Par_Sql debe ser un array con:
            // [0] = array de Man_Cod (IDs de manifiestos)
            // [1] = Man_Tip (nuevo estado, ej: 'F')
            // [2] = Vet_Cod (código de venta)
            $man_cods = isset($Par_Sql[0]) && is_array($Par_Sql[0]) ? $Par_Sql[0] : array();
            $man_tip = isset($Par_Sql[1]) ? addslashes($Par_Sql[1]) : '';
            $vet_cod = isset($Par_Sql[2]) ? intval($Par_Sql[2]) : 0;

            if (empty($man_cods) || empty($man_tip)) {
                return ""; // Retornar SQL vacío si faltan parámetros
            }

            // Sanitizar los IDs de manifiestos
            $man_cods_clean = array_map('intval', $man_cods);
            $man_cods_str = implode(',', $man_cods_clean);

            // UPDATE masivo usando IN para múltiples IDs
            $sql = "UPDATE manifiesto 
                    SET Man_Tip = '$man_tip', 
                        Vet_Cod = $vet_cod, 
                        Man_Tes = CONCAT(COALESCE(Man_Tes,''), '-$man_tip') 
                    WHERE Man_Cod IN ($man_cods_str) 
                    AND Man_Tip != '$man_tip'"; // Solo actualizar si el estado es diferente

            return $sql;
            break;

        case 31: // Antes: 255 - Insertar detalle cuentas por cobrar
            $sql = "INSERT INTO det_ccpp_c (Com_Cod,Pag_Cod,Cpc_Fec,Cpc_Val,Cpc_Obs,Cpc_Cod,Asi_Cod)
                values ($Par_Sql[Com_Cod],$Par_Sql[Pag_Cod],'$Par_Sql[Cpc_Fec]',$Par_Sql[Cpc_Val],'$Par_Sql[Cpc_Obs]',$Par_Sql[Cpc_Cod],$Par_Sql[Asi_Cod])";
            break;

        case 32: // Antes: 455 - Obtener anticipos de cliente
            $sql = "SELECT ant.Ant_Val, ant.Ant_Cod, ant.Ant_Val, ant.Ant_Fec, CONCAT(pr.Prs_Nom, ' ', pr.Prs_Ape) AS nombre,
                        ant.Com_Cod, pr.Prs_Ced, COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val, COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val_Aux,
                        (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo,
                        (COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo_aux
                    FROM anticipos_clientes AS ant
                        INNER JOIN comprobantes AS com ON com.Com_Cod = ant.Com_Cod
                        INNER JOIN cliente AS cli ON ant.Cli_Cod = cli.Cli_Cod
                        INNER JOIN persona AS pr ON pr.Prs_Cod = cli.Prs_Cod                                                                              
                        INNER JOIN manifiesto_anticipo ON manifiesto_anticipo.Ama_Cod = ant.Ama_Cod
                        LEFT JOIN det_ant_cccc AS dacc ON dacc.Ant_Cod = ant.Ant_Cod
                    WHERE ant.Cli_Cod = $Par_Sql[Cli_Cod] AND (ant.Ant_Est = 'A' OR ant.Ant_Est = 'U') AND com.Com_Est = 'A' 
                    AND manifiesto_anticipo.Pla_Cod = $Par_Sql[Pla_Cod] GROUP BY ant.Ant_Cod, ant.Ant_Fec, nombre, ant.Com_Cod, pr.Prs_Ced 
                    ORDER BY ant.Ant_Cod, ant.Ant_Fec ASC";

            break;

        case 33: // Antes: 866 - Insertar detalle venta
            $sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[Vet_Cod], Pro_Cod=$Par_Sql[Pro_Cod], Vet_Can=$Par_Sql[Vet_Can],
            Iva_Cod=$Par_Sql[Iva_Cod], Vet_Pru=$Par_Sql[Vet_Pru], Vet_Imp=$Par_Sql[Vet_Imp], Vet_Dec='" . (empty($Par_Sql['Vet_Dec']) ? 0 : $Par_Sql['Vet_Dec']) . "', Nge_Cod = '" . (empty($Par_Sql['Nge_Cod']) ? 0 : $Par_Sql['Nge_Cod']) . "',
            Asi_Int='" . (empty($Par_Sql['Asi_Int']) ? 0 : $Par_Sql['Asi_Int']) . "', Vet_Rec='" . (empty($Par_Sql['Vet_Rec']) ? 0 : $Par_Sql['Vet_Rec']) . "', Cnt_Cod='" . (empty($Par_Sql['Cnt_Cod']) ? 0 : $Par_Sql['Cnt_Cod']) . "', Vet_Int='" . (empty($Par_Sql['Vet_Int']) ? 0 : $Par_Sql['Vet_Int']) . "', Vet_Uni='" . (empty($Par_Sql['Vet_Uni']) || $Par_Sql['Vet_Uni'] * 1 <= 0 ? 1 : $Par_Sql['Vet_Uni']) . "', Ren_Cod=" . (empty($Par_Sql['Ret_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Ret_Ren_Cod]'") . ", Des_Adi=" . (empty($Par_Sql['Des_Adi']) ? 'NULL' : "'$Par_Sql[Des_Adi]'") . ", Ren_Iva=" . (empty($Par_Sql['Iva_Ren_Cod']) ? 'NULL' : "'$Par_Sql[Iva_Ren_Cod]'") . ",Vet_Ite='$Par_Sql[Vet_Ite]', Vet_Ice='" . (empty($Par_Sql['Ice_Por']) ? 0 : $Par_Sql['Ice_Por']) . "'";

            break;

        case 34: // Antes: 990 - Obtener detalle pago
            $sql = "SELECT Com_Cod, Cpc_Val FROM det_ccpp_c WHERE Cpc_Cod = $Par_Sql[0] ";
            break;

        case 35: // Antes: 991 - Eliminar asientos haber
            $sql = "DELETE FROM asientos WHERE Com_Cod = $Par_Sql[Com_Ret] AND Asi_Deh = 'H'";
            break;

        case 36: // Antes: 9911 - Actualizar asiento debe
            $sql = "UPDATE asientos SET Asi_Val = (Asi_Val - $Par_Sql[Cpc_Val]), Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'D' ";
            break;

        case 37: // Antes: 992 - Actualizar comprobante asiento
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Cod] WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        case 38: // Antes: 993 - Eliminar detalle cuentas por cobrar
            $sql = "DELETE FROM det_ccpp_c WHERE Cpc_Cod = $Par_Sql[Cpc_Cod] ";
            break;

        case 39: // Antes: 994 - Eliminar comprobante
            $sql = "DELETE FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Ret] ";
            break;

        case 40: // Antes: 995 - Obtener cuenta retención
            $sql = "SELECT det_plan.Pld_Cod, ventas.Ret_Fec FROM ventas
                    INNER JOIN ventas_det ON ventas.Vet_Cod = ventas_det.Vet_Cod
                    INNER JOIN reniva_pla ON (reniva_pla.Ren_Cod = ventas_det.Ren_Cod or reniva_pla.Ren_Cod = ventas_det.Ren_Iva)
                    INNER JOIN det_plan ON reniva_pla.Pld_Cod = det_plan.Pld_Cod
                    INNER JOIN plan_cuenta ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
                    WHERE ventas.Vet_Cod = $Par_Sql[Vet_Cod] AND plan_cuenta.Emp_Cod = $Par_Sql[Emp_Cod] and reniva_pla.Ren_Tip = 'V' and det_plan.Pld_Est = 'A'";
            break;

        case 41: // Antes: 996 - Obtener comprobante
            $sql = "SELECT * FROM comprobantes WHERE Com_Cod = $Par_Sql[Com_Cod] ";
            break;

        case 42: // Antes: 997 - Actualizar asiento retención
            $sql = "UPDATE asientos SET Com_Cod = $Par_Sql[Com_Ret] WHERE Com_Cod = $Par_Sql[Com_Cod] and Pld_Cod = $Par_Sql[Pld_Cod] and Asi_Deh = 'D'";
            break;

        case 43: // Antes: 998 - Sumar retenciones asiento
            $sql = "SELECT sum(Asi_Val) as totalRetencion FROM asientos WHERE Com_Cod = $Par_Sql[Com_Cod] and Asi_Deh = 'D' ";
            break;

        case 44: // Antes: 999 - Insertar asiento haber
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo) VALUES($Par_Sql[Com_Cod], 'H', '$Par_Sql[Asi_Val]', '$Par_Sql[Vet_Num]', $Par_Sql[Pld_Cod], '$Par_Sql[Vet_Num]')";
            break;

        case 45: // Antes: 10000 - Actualizar asiento sumar
            $sql = "UPDATE asientos SET Asi_Val = Asi_Val + $Par_Sql[Asi_Val], Pld_Cod = $Par_Sql[Pld_Cod] WHERE Com_Cod = $Par_Sql[Com_Cod] AND Asi_Deh = 'D'";
            break;

        case 46: // Antes: 10011 - Actualizar valor comprobante
            $sql = "UPDATE comprobantes SET Com_Val = $Par_Sql[Com_Val] WHERE Com_Cod = $Par_Sql[Com_Cod]";
            break;

        case 47: // Antes: 10022 - Insertar cuenta por cobrar alternativo
            $sql = "INSERT INTO ccpp_cobrar(Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[Com_Cod], $Par_Sql[Vet_Cod], '$Par_Sql[Cpc_Ven]', UPPER('$Par_Sql[Cpc_Obs]'));";
            break;

        case 48: // Antes: 1005 - Obtener llave electrónica
            $sql = "SElECT * FROM llave_elect WHERE Lla_Est='A' AND Emp_Cod=$Par_Sql[0];";
            break;

        case 49: // Antes: 1011 - Obtener autorización por tipo
            $sql = "SELECT autorizaci.*,tipo_compr.*,Suc_Sri FROM autorizaci
                    INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=autorizaci.Pun_Cod
                    INNER JOIN sucursal ON puntos_imp.Suc_Cod=sucursal.Suc_Cod
                    INNER JOIN tipo_compr ON tipo_compr.Tic_Cod=autorizaci.Tic_Cod
                    WHERE Tic_Sri=$Par_Sql[2] AND autorizaci.Pun_Cod='$Par_Sql[0]' AND Tic_Est='A' and autorizaci.Aut_Est='A' ";
            break;

        case 50: // Antes: 1777 - Obtener producto con IVA
            $sql = "SELECT producto.*,iva.Iva_Por FROM producto INNER JOIN iva ON iva.Iva_Cod = producto.Iva_Cod WHERE producto.Pro_Cod = $Par_Sql[1]";

            break;

        case 51: // Antes: 222 - Insertar comprobante completo
            $sql = "INSERT INTO comprobantes (Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Gen,Com_Est,Usu_Cod, Num_Doc) VALUES (
                " . $Par_Sql['Pec_Cod'] . ", " . $Par_Sql['Prv_Cod'] . ", " . $Par_Sql['Cli_Cod'] . ", 
                '" . $Par_Sql['Com_Num'] . "', '" . $Par_Sql['Com_Fec'] . "', '" . $Par_Sql['Com_Con'] . "', 
                'I', '" . $Par_Sql['Com_Val'] . "', '" . $Par_Sql['Com_Obs'] . "', NULL, 
                " . $Par_Sql['Tia_Cod'] . ", 'A', 'A', '" . $_SESSION['Ses_Usu_Cod'] . "', 
                " . (!empty($Par_Sql['Num_Doc']) ? $Par_Sql['Num_Doc'] : "NULL") . ");";

            return $sql;

        case 52: // Antes: 233 - Insertar asiento completo
            $sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
					VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', '$Par_Sql[Asi_Con]', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";

            return $sql;

        case 53: // Antes: 244 - Insertar detalle cuentas por cobrar completo
            $sql = "INSERT INTO det_ccpp_c (Cpc_Cod, Com_Cod, Pag_Cod, Cpc_Fec, Cpc_Val, Cpc_Obs, Cpc_Est, Asi_cod)
                        VALUES($Par_Sql[Cpc_Cod], $Par_Sql[Com_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Cpc_Fec]', '$Par_Sql[Cpc_Val]', '$Par_Sql[Cpc_Obs]', 'A', '$Par_Sql[Asi_Cod]');";

            return $sql;

        case 54: // Antes: 277 - Actualizar estado anticipo
            $sql = "UPDATE anticipos_clientes SET Ant_Est='$Par_Sql[Ant_Est]' WHERE Ant_Cod='$Par_Sql[Ant_Cod]';";
            return $sql;

        case 55: // Antes: 288 - Insertar detalle anticipo
            $sql = "INSERT INTO det_ant_cccc (Ddc_Val, Ddc_Obs, Ant_Cod, Dcc_Cod, Pac_Cod, Com_Cod)
                            VALUES('$Par_Sql[Ddc_Val]', '$Par_Sql[Ddc_Obs]', $Par_Sql[Ant_Cod], $Par_Sql[Dcc_Cod], $Par_Sql[Pac_Cod], $Par_Sql[Com_Cod] );";

            return $sql;

        case 56: // Antes: 289 - Obtener total consumido anticipo
            $sql = "SELECT COALESCE(SUM(Ddc_Val), 0) as total_consumido FROM det_ant_cccc WHERE Pac_Cod = " . $Par_Sql['Pac_Cod'];
            return $sql;

            // ==================== CRUD PARAM_MANIFIESTO ====================
        case 57: // Antes: 290 - Listar parámetros manifiesto
            $sql = "SELECT param_manifiesto.Prm_Cod, param_manifiesto.Pld_Cod, param_manifiesto.Pro_Cod, param_manifiesto.Tpc_Cod, param_manifiesto.Emp_Cod,
                        det_plan.Pld_Des, item.Ite_Lar, producto.Pro_Obs, tipopagocom.Tpc_Des
                    FROM param_manifiesto
                    INNER JOIN ccpp_cliente ON param_manifiesto.Pld_Cod = ccpp_cliente.Pld_Cod
                    INNER JOIN det_plan ON det_plan.Pld_Cod = ccpp_cliente.Pld_Cod
                    INNER JOIN producto ON param_manifiesto.Pro_Cod = producto.Pro_Cod
                    INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
                    INNER JOIN tipopagocom ON param_manifiesto.Tpc_Cod = tipopagocom.Tpc_Cod
                    WHERE param_manifiesto.Emp_Cod = " . $Par_Sql['Emp_Cod'];
            return $sql;

        case 58: // Antes: 291 - Obtener parámetro específico
            $sql = "SELECT pm.Prm_Cod, pm.Pld_Cod, pm.Pro_Cod, pm.Tpc_Cod, pm.Emp_Cod,
                        cc.Pld_Des as cuenta_pago, p.Pro_Nom as producto, tpc.Tpc_Des as tipo_pago
                    FROM param_manifiesto pm
                    LEFT JOIN ccpp_cliente cc ON pm.Pld_Cod = cc.Pld_Cod
                    LEFT JOIN producto p ON pm.Pro_Cod = p.Pro_Cod
                    LEFT JOIN tipopagocom tpc ON pm.Tpc_Cod = tpc.Tpc_Cod
                    WHERE pm.Prm_Cod = " . $Par_Sql['Prm_Cod'];
            return $sql;

        case 59: // Antes: 292 - Insertar parámetro manifiesto
            $sql = "INSERT INTO param_manifiesto (Pld_Cod, Pro_Cod, Tpc_Cod, Emp_Cod) 
                    VALUES (" . $Par_Sql['Pld_Cod'] . ", " . $Par_Sql['Pro_Cod'] . ", " . $Par_Sql['Tpc_Cod'] . ", " . $Par_Sql['Emp_Cod'] . ")";
            return $sql;

        case 60: // Antes: 293 - Actualizar parámetro manifiesto
            $sql = "UPDATE param_manifiesto SET Pld_Cod = " . $Par_Sql['Pld_Cod'] . ", Pro_Cod = " . $Par_Sql['Pro_Cod'] . ", Tpc_Cod = " . $Par_Sql['Tpc_Cod'] . " WHERE Prm_Cod = " . $Par_Sql['Prm_Cod'];
            return $sql;

        case 61: // Antes: 294 - Eliminar parámetro manifiesto
            $sql = "DELETE FROM param_manifiesto WHERE Prm_Cod = " . $Par_Sql['Prm_Cod'];
            return $sql;

        case 62: // Antes: 295 - Obtener cuentas de pago
            $sql = "SELECT dp.Pld_Cod, dp.Pld_Des FROM ccpp_cliente cc
                    INNER JOIN det_plan dp ON cc.Pld_Cod = dp.Pld_Cod
                    INNER JOIN plan_cuenta pc ON dp.Pla_Cod = pc.Pla_Cod
                    WHERE pc.Emp_Cod = " . $Par_Sql['Emp_Cod'] . " ORDER BY dp.Pld_Des";

            return $sql;

        case 63: // Antes: 296 - Obtener productos
            $sql = "SELECT producto.Pro_Cod, producto.Pro_Bar, Ite_Lar AS Pro_Nom FROM producto
                    INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
                    INNER JOIN categorias ON item.Cat_Cod = categorias.Cat_Cod
                    WHERE producto.Pro_Est = 'A' AND categorias.Emp_Cod = " . $Par_Sql['Emp_Cod'] . "
                    GROUP BY producto.Pro_Cod ORDER BY Ite_Lar";
            return $sql;

        case 64: // Antes: 297 - Obtener tipos de pago
            $sql = "SELECT Tpc_Cod, Tpc_Des FROM tipopagocom WHERE Tpc_Est = 'A' ORDER BY Tpc_Des";
            return $sql;

        case 65: // Antes: 298 - Contar parámetros por empresa
            $sql = "SELECT COUNT(*) as total FROM param_manifiesto WHERE Emp_Cod = " . $Par_Sql['Emp_Cod'];
            return $sql;

        case 66: // Antes: 299 - Obtener primer parámetro de empresa
            $sql = "SELECT pm.Prm_Cod, pm.Pld_Cod, pm.Pro_Cod, pm.Tpc_Cod, pm.Emp_Cod
                    FROM param_manifiesto pm WHERE pm.Emp_Cod = " . $Par_Sql['Emp_Cod'] . " LIMIT 1";
            return $sql;

        case 67: // Antes: 300 - Obtener producto con precio e IVA
            $sql = "SELECT producto.Pro_Cod, producto.Pro_Bar, producto.Iva_Cod, iva.Iva_Por, precios.Pre_Pvp, Ite_Lar, producto.Pro_Obs
                    FROM producto
                    INNER JOIN item ON item.Ite_Cod = producto.Ite_Cod
                    INNER JOIN iva ON iva.Iva_Cod = producto.Iva_Cod
                    INNER JOIN precios ON precios.Pro_Cod = producto.Pro_Cod AND precios.Pre_Est = 'A'
                    INNER JOIN sucursal ON sucursal.Suc_Cod = precios.Suc_Cod
                    WHERE producto.Pro_Cod = " . $Par_Sql['Pro_Cod'] . " AND sucursal.Emp_Cod = " . $Par_Sql['Emp_Cod'] . " LIMIT 1";
            return $sql;

        case 68:
            $sql = "SELECT 'N' AS Doc_Fir, 'N' AS Doc_Env, 'N' AS Doc_Mail, 
                    Vet_Num AS Doc_Num, Vet_Cod AS Doc_Cod, Vet_Aut AS Doc_Aut, Vet_Xml AS Doc_Xml, 
                    Vet_Sri AS Doc_Sri, 'ventas' AS tabla , IF(Cli_Cor IS NULL OR TRIM(Cli_Cor)='' OR TRIM(Cli_Cor)='-',
                    IF(Prs_Cor IS NULL OR TRIM(Prs_Cor)='-','',Prs_Cor),Cli_Cor) AS Email 
                    FROM ventas 
                    INNER JOIN cliente ON cliente.Cli_Cod=ventas.Cli_Cod
                    INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                    WHERE Vet_Cod = $Par_Sql[0] AND Vet_Est='A';";
            return $sql;


            // ==================== CRUD MANIFIESTO_PLANTAS ====================


        case 69:
            // Obtener plantas de uno o múltiples clientes
            if (isset($Par_Sql['Cli_Cod']) && is_array($Par_Sql['Cli_Cod']) && count($Par_Sql['Cli_Cod']) > 0) {
                // Múltiples clientes
                $cli_cods = array_map('intval', $Par_Sql['Cli_Cod']);
                $cli_cods_str = implode(',', $cli_cods);
                $sql = "SELECT DISTINCT mp.Pla_Cod, mp.Pla_Nom, mp.Cli_Cod 
                            FROM manifiesto_plantas mp 
                            WHERE mp.Pla_Est = 'A' AND mp.Cli_Cod IN ($cli_cods_str) 
                            ORDER BY mp.Cli_Cod, mp.Pla_Nom";
            } elseif (isset($Par_Sql['Cli_Cod']) && !empty($Par_Sql['Cli_Cod'])) {
                // Un solo cliente
                $sql = "SELECT Pla_Cod, Pla_Nom, Cli_Cod FROM manifiesto_plantas WHERE Pla_Est = 'A' AND Cli_Cod = " . intval($Par_Sql['Cli_Cod']) . " ORDER BY Pla_Nom";
            } else {
                // Sin cliente, retornar todas las plantas activas
                $sql = "SELECT Pla_Cod, Pla_Nom, Cli_Cod FROM manifiesto_plantas WHERE Pla_Est = 'A' ORDER BY Pla_Nom";
            }
            return $sql;
            break;

        case 70: // Validar clave de acceso para registro de facturas
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $Cla_Cod = isset($Par_Sql['Cla_Cod']) ? addslashes($Par_Sql['Cla_Cod']) : '';
            $Cod_Psc = 2; // Código de proceso para validar registro de facturas
            $sql = "SELECT * FROM claves_accesos 
                        WHERE Emp_Cod = $Emp_Cod 
                        AND Cla_Cod = '$Cla_Cod' 
                        AND Cod_Psc = $Cod_Psc 
                        AND Cla_Est = 'A' 
                        LIMIT 1";
            return $sql;
            break;

        case 71: // Verificar si existe una clave de acceso activa para registro de facturas
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $Cod_Psc = 2; // Código de proceso para validar registro de facturas
            $sql = "SELECT COUNT(*) AS total FROM claves_accesos 
                        WHERE Emp_Cod = $Emp_Cod 
                        AND Cod_Psc = $Cod_Psc 
                        AND Cla_Est = 'A' 
                        LIMIT 1";
            return $sql;
            break;

        case 72: // Contar manifiestos por estado para panel: GS y pendientes (!=F,!=GS)
            $search = '';
            if (!empty($Par_Sql['Pec_Cod'])) {
                // Periodo seleccionado
                $search .= " AND Man_Fes BETWEEN '{$Par_Sql['fecha_inicio']} 00:00:00' AND '{$Par_Sql['fecha_fin']} 23:59:59'";
                if (!empty($Par_Sql['Cmb_Mes'])) {
                    $search .= " AND MONTH(Man_Fes) = " . intval($Par_Sql['Cmb_Mes']);
                }
            } elseif (!empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                // Rango de fechas manual
                $search .= " AND Man_Fes BETWEEN '{$Par_Sql['Fec_Ini']} 00:00:00' AND '{$Par_Sql['Fec_Fin']} 23:59:59'";
            }
            if (!empty($Par_Sql['Pla_Cod'])) {
                $search .= " AND manifiesto.Pla_Cod='{$Par_Sql['Pla_Cod']}'";
            }
            if (isset($Par_Sql['op_opciones'])) {
                if ($Par_Sql['op_opciones'] == 'd') {
                    $search .= " AND manifiesto.Man_Num = '" . (isset($Par_Sql['search']) ? $Par_Sql['search'] : '') . "'";
                } else {
                    if ($Par_Sql['op_opciones'] == 'c' && !empty($Par_Sql['search'])) {
                        $search .= " AND persona.Prs_Ced LIKE '" . $Par_Sql['search'] . "%'";
                    }
                    if ($Par_Sql['op_opciones'] == 'p' && !empty($Par_Sql['search'])) {
                        $nombre_busqueda = trim($Par_Sql['search']);
                        $search .= " AND ( CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$nombre_busqueda%'  OR CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) LIKE '%$nombre_busqueda%' )";
                    }
                }
            }
            $sql = "SELECT 
                    SUM(CASE WHEN manifiesto.Man_Tip = 'GS' THEN 1 ELSE 0 END) AS total_gs,
                    SUM(CASE WHEN manifiesto.Man_Tip = 'F' THEN 1 ELSE 0 END) AS total_fact,
                    SUM(CASE WHEN manifiesto.Man_Tip NOT IN ('F','GS') THEN 1 ELSE 0 END) AS total_pend
                FROM manifiesto
                INNER JOIN manifiesto_plantas ON manifiesto_plantas.Pla_Cod=manifiesto.Pla_Cod
                INNER JOIN cliente ON cliente.Cli_Cod=manifiesto.Cli_Cod
                INNER JOIN persona ON persona.Prs_Cod=cliente.Prs_Cod
                WHERE Man_Est = 'A' AND cliente.Emp_Cod = " . (isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 0) . " $search";
            break;


        case 75: // Obtener Pla_Cod del usuario desde manifiesto_usuario (filtrar por planta asignada)
            $Usu_Cod = isset($Par_Sql['Usu_Cod']) ? intval($Par_Sql['Usu_Cod']) : 0;
            $sql = "SELECT Pla_Cod FROM manifiesto_usuario WHERE Usu_Cod = $Usu_Cod LIMIT 1";
            return $sql;

        case 76: // Listar plantas con datos de cliente para modal de selección (búsqueda por cédula, nombre planta/cliente)
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $search = '';
            // Parámetros del createSearchDialog: search + op_opciones (c=cédula, n/d=nombre planta o cliente)
            if (!empty($Par_Sql['search'])) {
                $busqueda = addslashes(trim($Par_Sql['search']));
                $op = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : 'd';
                if ($op == 'c') {
                    $search .= " AND persona.Prs_Ced LIKE '" . $busqueda . "%'";
                } else {
                    $search .= " AND (mp.Pla_Nom LIKE '%" . $busqueda . "%' OR CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) LIKE '%" . $busqueda . "%' OR CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%" . $busqueda . "%')";
                }
            }
            if (!empty($Par_Sql['search_planta'])) {
                $pla = addslashes(trim($Par_Sql['search_planta']));
                $search .= " AND mp.Pla_Nom LIKE '%" . $pla . "%'";
            }
            if (!empty($Par_Sql['search_cedula'])) {
                $ced = addslashes(trim($Par_Sql['search_cedula']));
                $search .= " AND persona.Prs_Ced LIKE '" . $ced . "%'";
            }
            $from_where = "FROM manifiesto_plantas mp
                INNER JOIN cliente ON cliente.Cli_Cod = mp.Cli_Cod
                INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                WHERE mp.Pla_Est = 'A' AND cliente.Emp_Cod = $Emp_Cod $search";
            if (empty($Par_Sql['limits'])) {
                $sql = "SELECT COUNT(*) AS total " . $from_where;
            } else {
                $sql = "SELECT mp.Pla_Cod, mp.Pla_Nom, mp.Cli_Cod,
                    CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS cliente,
                    persona.Prs_Ced
                " . $from_where . "
                ORDER BY mp.Pla_Nom, persona.Prs_Ape, persona.Prs_Nom " . $Par_Sql['limits'];
            }
            break;

        case 73: // Listar facturas con sus manifiestos y cantidad de manifiestos por factura (para man_fac_man.php)
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $Pla_Cod_Usuario = isset($Par_Sql['Pla_Cod_Usuario']) ? intval($Par_Sql['Pla_Cod_Usuario']) : 0;
            $Pla_Cod_Usuario = ($Pla_Cod_Usuario > 0) ? $Pla_Cod_Usuario : 0;
            /* Filtro por planta: por Cli_Cod+Pla_Cod (manifiesto puede no tener col. Pla_Cod en algunos esquemas) */
            $filter_planta = ($Pla_Cod_Usuario > 0) ? " AND EXISTS (SELECT 1 FROM manifiesto_plantas mpf WHERE mpf.Cli_Cod = manifiesto.Cli_Cod AND mpf.Pla_Cod = " . $Pla_Cod_Usuario . ")" : "";
            $filter_ventas_planta = ($Pla_Cod_Usuario > 0) ? " AND EXISTS (SELECT 1 FROM manifiesto m2 INNER JOIN manifiesto_plantas mpf2 ON mpf2.Cli_Cod = m2.Cli_Cod AND mpf2.Pla_Cod = " . $Pla_Cod_Usuario . " WHERE m2.Vet_Cod = ventas.Vet_Cod AND m2.Man_Est = 'A')" : "";
            $search = '';
            if (!empty($Par_Sql['Num_Factura'])) {
                $num_fac = addslashes(trim($Par_Sql['Num_Factura']));
                $search .= " AND (ventas.Vet_Num = '" . $num_fac . "' OR CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) LIKE '%" . $num_fac . "%')";
            }
            if (!empty($Par_Sql['search'])) {
                $busqueda = addslashes(trim($Par_Sql['search']));
                $op = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : '';
                if ($op == 'n') {
                    $search .= " AND (ventas.Vet_Num = '" . $busqueda . "' OR CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) LIKE '%" . $busqueda . "%')";
                } elseif ($op == 'c') {
                    $search .= " AND persona.Prs_Ced LIKE '" . $busqueda . "%'";
                } else {
                    $search .= " AND (CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) LIKE '%" . $busqueda . "%' OR CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%" . $busqueda . "%')";
                }
            }
            if (!empty($Par_Sql['Fec_Ini']) && !empty($Par_Sql['Fec_Fin'])) {
                $fec_ini = trim($Par_Sql['Fec_Ini']);
                $fec_fin = trim($Par_Sql['Fec_Fin']);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fec_ini) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fec_fin)) {
                    $search .= " AND caja_aper.Caj_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'";
                }
            }
            if (empty($Par_Sql['limits'])) {
                $campos = "COUNT(DISTINCT ventas.Vet_Cod) AS total";
                $group = "";
            } else {
                $campos = "ventas.Vet_Cod, ventas.Vet_Num,
                    CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) AS Vet_Num_Completo,
                    caja_aper.Caj_Fec AS Vet_Fec,
                    ventas.Vet_Aut,
                    CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS cliente, persona.Prs_Ced,
                    sucursal.Suc_Sri, autorizaci.Pun_Sri,
                    COALESCE(m.cant_manifiestos, 0) AS cant_manifiestos,
                    COALESCE(sm.subtotal_factura, 0) AS subtotal_factura,
                    COALESCE(sm.iva_factura, 0) AS iva_factura,
                    COALESCE(MAX(comprobantes.Com_Val), 0) AS total_factura,
                    CASE WHEN ventas.Vet_Aut = 'S' THEN 'Autorizada' WHEN ventas.Vet_Aut = 'N' THEN 'Sin autorizar' ELSE 'No facturado' END AS Vet_Aut_Des,
                    COALESCE(MAX(pl.Pla_Nom), '') AS Pla_Nom";
                $group = "GROUP BY ventas.Vet_Cod";
            }
            $sql = "SELECT $campos
                FROM ventas
                INNER JOIN cliente ON ventas.Cli_Cod = cliente.Cli_Cod
                INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                INNER JOIN autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
                INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
                LEFT JOIN caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                LEFT JOIN puntos_imp ON puntos_imp.Pun_Cod = caja_aper.Pun_Cod
                LEFT JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                LEFT JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod
                LEFT JOIN comprobantes ON comprobantes.Com_Cod = ventas_compr.Com_Cod
                LEFT JOIN (
                    SELECT vd.Vet_Cod, 
                           SUM(ROUND((vd.Vet_Imp - (vd.Vet_Imp * vd.Vet_Dec / 100)) * (1 - COALESCE(v.Vet_Des, 0) / 100), 2)) AS subtotal_factura,
                           SUM(ROUND(((vd.Vet_Imp - (vd.Vet_Imp * vd.Vet_Dec / 100)) * (1 - COALESCE(v.Vet_Des, 0) / 100) + COALESCE(vd.Vet_Ice, 0)) * i.Iva_Por / 100, 2)) AS iva_factura
                    FROM ventas_det vd
                    INNER JOIN iva i ON i.Iva_Cod = vd.Iva_Cod
                    INNER JOIN ventas v ON v.Vet_Cod = vd.Vet_Cod
                    GROUP BY vd.Vet_Cod
                ) sm ON sm.Vet_Cod = ventas.Vet_Cod
                LEFT JOIN (
                    SELECT Vet_Cod, COUNT(Man_Cod) AS cant_manifiestos
                    FROM manifiesto
                    WHERE Man_Est = 'A' AND Vet_Cod IS NOT NULL AND Vet_Cod > 0 $filter_planta
                    GROUP BY Vet_Cod
                ) m ON m.Vet_Cod = ventas.Vet_Cod
                LEFT JOIN (
                    SELECT m2.Vet_Cod, GROUP_CONCAT(DISTINCT mp.Pla_Nom ORDER BY mp.Pla_Nom SEPARATOR ', ') AS Pla_Nom
                    FROM manifiesto m2
                    INNER JOIN manifiesto_plantas mp ON mp.Cli_Cod = m2.Cli_Cod" . ($Pla_Cod_Usuario > 0 ? " AND mp.Pla_Cod = " . $Pla_Cod_Usuario : "") . "
                    WHERE m2.Man_Est = 'A' AND m2.Vet_Cod IS NOT NULL AND m2.Vet_Cod > 0
                    GROUP BY m2.Vet_Cod
                ) pl ON pl.Vet_Cod = ventas.Vet_Cod
                WHERE cliente.Emp_Cod = $Emp_Cod AND ventas.Vet_Est = 'A'
                AND (tipo_compr.Tic_Sri = '1' OR tipo_compr.Tic_Sri = '4')
                $filter_ventas_planta $search $group ";
            $orderRaw = (isset($Par_Sql['order']) && trim($Par_Sql['order']) !== '') ? trim($Par_Sql['order']) : 'ORDER BY caja_aper.Caj_Fec DESC, ventas.Vet_Num DESC';
            $order = (stripos($orderRaw, 'ORDER BY') === 0) ? $orderRaw : 'ORDER BY ' . $orderRaw;
            $sql .= (empty($Par_Sql['limits']) ? '' : (' ' . $order . ' ' . $Par_Sql['limits']));
            break;

        case 74: // Listar manifiestos de una factura (por Vet_Cod), filtro planta igual que case 73 (EXISTS)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $Pla_Cod_Usuario_74 = isset($Par_Sql['Pla_Cod_Usuario']) ? intval($Par_Sql['Pla_Cod_Usuario']) : 0;
            $filter_planta_74 = ($Pla_Cod_Usuario_74 > 0)
                ? " AND EXISTS (SELECT 1 FROM manifiesto_plantas mpf WHERE mpf.Cli_Cod = manifiesto.Cli_Cod AND mpf.Pla_Cod = $Pla_Cod_Usuario_74)"
                : '';
            $sql = "SELECT manifiesto.Man_Cod, manifiesto.Man_Num, manifiesto.Pla_Cod, manifiesto.Man_Fes, manifiesto.Man_Pes,
                    manifiesto.Man_Pun, manifiesto.Man_Tip, manifiesto.Man_Gui,
                    COALESCE(mp_row.Pla_Nom, mp_agg.Pla_Nom, '') AS Pla_Nom,
                    CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS cliente, persona.Prs_Ced,
                    vehiculo.Veh_Pla,
                    CONCAT(persona_chofer.Prs_Nom, ' ', persona_chofer.Prs_Ape) AS chofer,
                    ROUND(manifiesto.Man_Pun * manifiesto.Man_Pes, 2) AS total
                FROM manifiesto
                LEFT JOIN manifiesto_plantas mp_row ON mp_row.Pla_Cod = manifiesto.Pla_Cod AND mp_row.Cli_Cod = manifiesto.Cli_Cod
                LEFT JOIN (SELECT Cli_Cod, GROUP_CONCAT(Pla_Nom ORDER BY Pla_Nom SEPARATOR ', ') AS Pla_Nom FROM manifiesto_plantas WHERE Pla_Est = 'A' GROUP BY Cli_Cod) mp_agg ON mp_agg.Cli_Cod = manifiesto.Cli_Cod
                INNER JOIN cliente ON manifiesto.Cli_Cod = cliente.Cli_Cod
                INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                LEFT JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
                LEFT JOIN chofer ON manifiesto.Cho_Cod = chofer.Cho_Cod
                LEFT JOIN persona AS persona_chofer ON chofer.Prs_Cod = persona_chofer.Prs_Cod
                WHERE manifiesto.Man_Est = 'A' AND manifiesto.Vet_Cod = $Vet_Cod $filter_planta_74
                ORDER BY manifiesto.Man_Fes DESC, manifiesto.Man_Num";
            break;

             case 78:
            // Consulta: Manifiestos de HOY cuyo periodo de facturación Pla_Pfa es 'D' (diario), junto con el nombre de la planta
            // Devuelve cantidad de manifiestos a facturar hoy y nombre de la(s) planta(s) con facturación diaria
            $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
            $sql = "SELECT mp.Pla_Nom, COUNT(m.Man_Cod) AS cant_manifiestos_hoy
                FROM manifiesto m
                INNER JOIN manifiesto_plantas mp ON mp.Pla_Cod = m.Pla_Cod
                INNER JOIN cliente c ON c.Cli_Cod = m.Cli_Cod AND c.Emp_Cod = $Emp_Cod
                WHERE m.Man_Est = 'A' AND DATE(m.Man_Fes) = CURDATE() AND mp.Pla_Pfa = 'D' AND Man_Tip = 'GS'
                GROUP BY mp.Pla_Cod, mp.Pla_Nom";
            break;

            case 79: // Listar clientes asignados al usuario (desde manifiesto_usuario) para modal
                $Usu_Cod = isset($Par_Sql['Usu_Cod']) ? intval($Par_Sql['Usu_Cod']) : 0;
                $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
                $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? intval($Par_Sql['Pla_Cod']) : 0;

                $where_mu = "mu.Usu_Cod = $Usu_Cod";
                if ($Pla_Cod > 0) {
                    $where_mu .= " AND mu.Pla_Cod = " . $Pla_Cod;
                }

                $sql = "SELECT DISTINCT
                            mu.Cli_Cod,
                            persona.Prs_Ced,
                            CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS cliente
                        FROM manifiesto_usuario mu
                        INNER JOIN cliente c ON c.Cli_Cod = mu.Cli_Cod
                        INNER JOIN persona ON persona.Prs_Cod = c.Prs_Cod
                        WHERE $where_mu AND c.Emp_Cod = $Emp_Cod
                        ORDER BY persona.Prs_Ape, persona.Prs_Nom";
                break;

            case 80: // Manifiestos por facturar agrupados por cliente y planta (con filtro de fechas)
                $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
                $Fec_Ini = isset($Par_Sql['Fec_Ini']) ? addslashes(trim($Par_Sql['Fec_Ini'])) : '';
                $Fec_Fin = isset($Par_Sql['Fec_Fin']) ? addslashes(trim($Par_Sql['Fec_Fin'])) : '';
                $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? intval($Par_Sql['Pla_Cod']) : 0;

                $where = "m.Man_Est = 'A' AND m.Man_Tip = 'GS' AND c.Emp_Cod = $Emp_Cod";
                if ($Fec_Ini !== '' && $Fec_Fin !== '') {
                    $where .= " AND m.Man_Fes BETWEEN '{$Fec_Ini} 00:00:00' AND '{$Fec_Fin} 23:59:59'";
                }
                if ($Pla_Cod > 0) {
                    $where .= " AND m.Pla_Cod = " . $Pla_Cod;
                }

                $sql = "SELECT
                            m.Cli_Cod,
                            m.Pla_Cod,
                            CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS cliente,
                            COALESCE(mp.Pla_Nom, CONCAT('Planta ', m.Pla_Cod)) AS bodega,
                            COUNT(m.Man_Cod) AS cant_manifiestos
                        FROM manifiesto m
                        INNER JOIN cliente c ON c.Cli_Cod = m.Cli_Cod
                        INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                        LEFT JOIN manifiesto_plantas mp ON mp.Pla_Cod = m.Pla_Cod AND mp.Cli_Cod = m.Cli_Cod
                        WHERE $where
                        GROUP BY m.Cli_Cod, m.Pla_Cod, cliente, bodega
                        ORDER BY bodega, cliente";
                break;

            case 81: // Grid paginado: manifiestos por facturar agrupado por cliente y planta (para sfDialog)
                $Emp_Cod = isset($Par_Sql['Emp_Cod']) ? intval($Par_Sql['Emp_Cod']) : 0;
                $Fec_Ini = isset($Par_Sql['Fec_Ini']) ? addslashes(trim($Par_Sql['Fec_Ini'])) : '';
                $Fec_Fin = isset($Par_Sql['Fec_Fin']) ? addslashes(trim($Par_Sql['Fec_Fin'])) : '';
                $Pla_Cod = isset($Par_Sql['Pla_Cod']) ? intval($Par_Sql['Pla_Cod']) : 0;

                $where = "m.Man_Est = 'A' AND m.Man_Tip = 'GS' AND c.Emp_Cod = $Emp_Cod";
                if ($Fec_Ini !== '' && $Fec_Fin !== '') {
                    $where .= " AND m.Man_Fes BETWEEN '{$Fec_Ini} 00:00:00' AND '{$Fec_Fin} 23:59:59'";
                }
                if ($Pla_Cod > 0) {
                    $where .= " AND m.Pla_Cod = " . $Pla_Cod;
                }
                if (!empty($Par_Sql['search'])) {
                    $busqueda = addslashes(trim($Par_Sql['search']));
                    $where .= " AND (
                        p.Prs_Ced LIKE '" . $busqueda . "%'
                        OR CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) LIKE '%" . $busqueda . "%'
                        OR CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) LIKE '%" . $busqueda . "%'
                        OR mp.Pla_Nom LIKE '%" . $busqueda . "%'
                    )";
                }

                $from_where = "FROM manifiesto m
                    INNER JOIN cliente c ON c.Cli_Cod = m.Cli_Cod
                    INNER JOIN persona p ON p.Prs_Cod = c.Prs_Cod
                    LEFT JOIN manifiesto_plantas mp ON mp.Pla_Cod = m.Pla_Cod AND mp.Cli_Cod = m.Cli_Cod
                    WHERE $where";

                if (empty($Par_Sql['limits'])) {
                    $sql = "SELECT COUNT(*) AS total FROM (
                            SELECT 1 $from_where
                            GROUP BY m.Cli_Cod, m.Pla_Cod
                        ) t";
                } else {
                    $sql = "SELECT
                                p.Prs_Ced,
                                CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS cliente,
                                COALESCE(mp.Pla_Nom, CONCAT('Planta ', m.Pla_Cod)) AS bodega,
                                COUNT(m.Man_Cod) AS cant_manifiestos
                            $from_where
                            GROUP BY m.Cli_Cod, m.Pla_Cod, p.Prs_Ced, cliente, bodega
                            ORDER BY bodega, cliente " . $Par_Sql['limits'];
                }
                break;

        case 87: // Cabecera reporte / certificado por factura (Vet_Cod)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            // Número completo y fecha de factura (misma lógica del grid: caja_aper.Caj_Fec)
            $sql = "SELECT
                        CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) AS Vet_Num_Completo,
                        caja_aper.Caj_Fec AS Vet_Fec
                    FROM ventas
                    INNER JOIN autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
                    LEFT JOIN caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                    LEFT JOIN puntos_imp ON puntos_imp.Pun_Cod = caja_aper.Pun_Cod
                    LEFT JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    WHERE ventas.Vet_Cod = $Vet_Cod
                    LIMIT 1";
            break;

        case 88: // Listado de manifiestos para certificado por factura (Vet_Cod) - formato B.07.01
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $Pla_Cod_Usuario_88 = isset($Par_Sql['Pla_Cod_Usuario']) ? intval($Par_Sql['Pla_Cod_Usuario']) : 0;
            $filter_planta_88 = ($Pla_Cod_Usuario_88 > 0)
                ? " AND EXISTS (SELECT 1 FROM manifiesto_plantas mpf WHERE mpf.Cli_Cod = m.Cli_Cod AND mpf.Pla_Cod = $Pla_Cod_Usuario_88)"
                : "";
            $sql = "SELECT
                        m.Man_Cod,
                        m.Pla_Cod,
                        DATE(m.Man_Fes) AS Fecha,
                        m.Man_Num,
                        CONCAT('M', m.Pla_Cod, '-', LPAD(m.Man_Num, 4, '0')) AS Man_Num_Full,
                        m.Man_Gui,
                        m.Man_Pes,
                        COALESCE(v.Vet_Num, 'S/F') AS Factura,
                        vehiculo.Veh_Pla,
                        CONCAT(persona_chofer.Prs_Nom, ' ', persona_chofer.Prs_Ape) AS chofer,
                        CAST((m.Man_Pes * (m.Man_Pun / 1000)) AS DECIMAL(10,2)) AS Valor,
                        IF(m.Vet_Cod IS NOT NULL AND m.Vet_Cod > 0, 1, 0) AS Facturado
                    FROM manifiesto m
                    LEFT JOIN vehiculo ON m.Veh_Cod = vehiculo.Veh_Cod
                    LEFT JOIN chofer ON m.Cho_Cod = chofer.Cho_Cod
                    LEFT JOIN persona AS persona_chofer ON chofer.Prs_Cod = persona_chofer.Prs_Cod
                    LEFT JOIN ventas v ON m.Vet_Cod = v.Vet_Cod
                    WHERE m.Vet_Cod = $Vet_Cod AND m.Man_Est = 'A' $filter_planta_88
                    ORDER BY m.Man_Fes ASC";
            break;

        case 89: // Cabecera para certificado por factura (Vet_Cod)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $Pla_Cod_Usuario_89 = isset($Par_Sql['Pla_Cod_Usuario']) ? intval($Par_Sql['Pla_Cod_Usuario']) : 0;
            $filter_planta_89 = ($Pla_Cod_Usuario_89 > 0)
                ? " AND EXISTS (SELECT 1 FROM manifiesto_plantas mpf WHERE mpf.Cli_Cod = m.Cli_Cod AND mpf.Pla_Cod = $Pla_Cod_Usuario_89)"
                : "";
            $sql = "SELECT
                        persona.Prs_Ced,
                        IF(persona.Prs_Nom = persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS Representante,
                        mp.Pla_Nom,
                        mp.Pla_Car
                    FROM manifiesto m
                    INNER JOIN cliente ON m.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    INNER JOIN manifiesto_plantas mp ON mp.Cli_Cod = m.Cli_Cod AND mp.Pla_Cod = m.Pla_Cod
                    WHERE m.Vet_Cod = $Vet_Cod AND m.Man_Est = 'A' $filter_planta_89
                    ORDER BY m.Man_Fes ASC
                    LIMIT 1";
            break;

        case 90: // Consulta pública: resumen factura por Vet_Cod (Cod_Ven)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $sql = "SELECT
                        ventas.Vet_Cod,
                        ventas.Vet_Num,
                        CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) AS Vet_Num_Completo,
                        caja_aper.Caj_Fec AS Vet_Fec,
                        ventas.Vet_Aut,
                        CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS cliente,
                        persona.Prs_Ced,
                        cliente.Cli_Cod,
                        cliente.Emp_Cod,
                        COALESCE(MAX(comprobantes.Com_Val), 0) AS total_factura,
                        COALESCE(sm.subtotal_factura, 0) AS subtotal_factura,
                        COALESCE(sm.iva_factura, 0) AS iva_factura,
                        CASE WHEN ventas.Vet_Aut = 'S' THEN 'Autorizada' WHEN ventas.Vet_Aut = 'N' THEN 'Sin autorizar' ELSE 'No facturado' END AS Vet_Aut_Des,
                        COALESCE(m.cant_manifiestos, 0) AS cant_manifiestos,
                        COALESCE(m.peso_total, 0) AS peso_total,
                        COALESCE(m.valor_manifiestos, 0) AS valor_manifiestos,
                        COALESCE(ant.total_anticipos, 0) AS total_anticipos,
                        COALESCE(gen.Representante, '') AS Representante,
                        COALESCE(gen.Pla_Nom, pl.Pla_Nom, '') AS Pla_Nom,
                        COALESCE(gen.Pla_Car, '') AS Pla_Car,
                        fr.Fec_Des,
                        fr.Fec_Has
                    FROM ventas
                    INNER JOIN cliente ON ventas.Cli_Cod = cliente.Cli_Cod
                    INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    INNER JOIN autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
                    INNER JOIN tipo_compr ON autorizaci.Tic_Cod = tipo_compr.Tic_Cod
                    LEFT JOIN caja_aper ON caja_aper.Caj_Cod = ventas.Caj_Cod
                    LEFT JOIN puntos_imp ON puntos_imp.Pun_Cod = caja_aper.Pun_Cod
                    LEFT JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    LEFT JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN comprobantes ON comprobantes.Com_Cod = ventas_compr.Com_Cod
                    LEFT JOIN (
                        SELECT vd.Vet_Cod,
                               SUM(ROUND((vd.Vet_Imp - (vd.Vet_Imp * vd.Vet_Dec / 100)) * (1 - COALESCE(v.Vet_Des, 0) / 100), 2)) AS subtotal_factura,
                               SUM(ROUND(((vd.Vet_Imp - (vd.Vet_Imp * vd.Vet_Dec / 100)) * (1 - COALESCE(v.Vet_Des, 0) / 100) + COALESCE(vd.Vet_Ice, 0)) * i.Iva_Por / 100, 2)) AS iva_factura
                        FROM ventas_det vd
                        INNER JOIN iva i ON i.Iva_Cod = vd.Iva_Cod
                        INNER JOIN ventas v ON v.Vet_Cod = vd.Vet_Cod
                        GROUP BY vd.Vet_Cod
                    ) sm ON sm.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN (
                        SELECT Vet_Cod,
                               COUNT(Man_Cod) AS cant_manifiestos,
                               SUM(COALESCE(Man_Pes, 0)) AS peso_total,
                               SUM(CAST((COALESCE(Man_Pes, 0) * (COALESCE(Man_Pun, 0) / 1000)) AS DECIMAL(14, 2))) AS valor_manifiestos
                        FROM manifiesto
                        WHERE Man_Est = 'A' AND Vet_Cod IS NOT NULL AND Vet_Cod > 0
                        GROUP BY Vet_Cod
                    ) m ON m.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN (
                        SELECT cpc.Vet_Cod, SUM(COALESCE(dacc.Ddc_Val, 0)) AS total_anticipos
                        FROM ccpp_cobrar cpc
                        INNER JOIN det_ccpp_c dcc ON dcc.Cpc_Cod = cpc.Cpc_Cod
                        INNER JOIN det_ant_cccc dacc ON dacc.Dcc_Cod = dcc.Dcc_Cod
                        INNER JOIN comprobantes com ON com.Com_Cod = dcc.Com_Cod AND com.Com_Est = 'A'
                        GROUP BY cpc.Vet_Cod
                    ) ant ON ant.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN (
                        SELECT m2.Vet_Cod, GROUP_CONCAT(DISTINCT mp.Pla_Nom ORDER BY mp.Pla_Nom SEPARATOR ', ') AS Pla_Nom
                        FROM manifiesto m2
                        INNER JOIN manifiesto_plantas mp ON mp.Cli_Cod = m2.Cli_Cod AND mp.Pla_Cod = m2.Pla_Cod
                        WHERE m2.Man_Est = 'A' AND m2.Vet_Cod IS NOT NULL AND m2.Vet_Cod > 0
                        GROUP BY m2.Vet_Cod
                    ) pl ON pl.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN (
                        SELECT m3.Vet_Cod,
                               IF(persona.Prs_Nom = persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS Representante,
                               mp.Pla_Nom,
                               mp.Pla_Car
                        FROM manifiesto m3
                        INNER JOIN cliente ON m3.Cli_Cod = cliente.Cli_Cod
                        INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                        INNER JOIN manifiesto_plantas mp ON mp.Cli_Cod = m3.Cli_Cod AND mp.Pla_Cod = m3.Pla_Cod
                        WHERE m3.Man_Est = 'A' AND m3.Vet_Cod = $Vet_Cod
                        ORDER BY m3.Man_Fes ASC
                        LIMIT 1
                    ) gen ON gen.Vet_Cod = ventas.Vet_Cod
                    LEFT JOIN (
                        SELECT Vet_Cod,
                               MIN(DATE(Man_Fes)) AS Fec_Des,
                               MAX(DATE(Man_Fes)) AS Fec_Has
                        FROM manifiesto
                        WHERE Man_Est = 'A' AND Vet_Cod IS NOT NULL AND Vet_Cod > 0
                        GROUP BY Vet_Cod
                    ) fr ON fr.Vet_Cod = ventas.Vet_Cod
                    WHERE ventas.Vet_Cod = $Vet_Cod AND ventas.Vet_Est = 'A'
                    AND (tipo_compr.Tic_Sri = '1' OR tipo_compr.Tic_Sri = '4')
                    GROUP BY ventas.Vet_Cod";
            break;

        case 91: // Anticipos aplicados a la factura (Vet_Cod)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $sql = "SELECT
                        dacc.Ddc_Val AS valor_aplicado,
                        dacc.Ddc_Obs AS observacion,
                        ant.Ant_Cod,
                        ant.Ant_Fec,
                        ant.Ant_Doc,
                        ma.Ama_Doc,
                        ma.Ama_Fec,
                        CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS titular_anticipo,
                        com.Com_Num,
                        com.Com_Fec
                    FROM ccpp_cobrar cpc
                    INNER JOIN det_ccpp_c dcc ON dcc.Cpc_Cod = cpc.Cpc_Cod
                    INNER JOIN det_ant_cccc dacc ON dacc.Dcc_Cod = dcc.Dcc_Cod
                    INNER JOIN comprobantes com ON com.Com_Cod = dcc.Com_Cod AND com.Com_Est = 'A'
                    INNER JOIN anticipos_clientes ant ON ant.Ant_Cod = dacc.Ant_Cod
                    LEFT JOIN manifiesto_anticipo ma ON ma.Ama_Cod = ant.Ama_Cod
                    LEFT JOIN cliente ON ant.Cli_Cod = cliente.Cli_Cod
                    LEFT JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
                    WHERE cpc.Vet_Cod = $Vet_Cod
                    ORDER BY com.Com_Fec DESC, dacc.Ddc_Cod DESC";
            break;

        case 92: // Manifiestos de la factura (consulta pública)
            $Vet_Cod = isset($Par_Sql['Vet_Cod']) ? intval($Par_Sql['Vet_Cod']) : 0;
            $sql = "SELECT
                        manifiesto.Man_Cod,
                        manifiesto.Man_Num,
                        manifiesto.Pla_Cod,
                        manifiesto.Man_Fes,
                        manifiesto.Man_Pes,
                        manifiesto.Man_Gui,
                        CONCAT('M', manifiesto.Pla_Cod, '-', LPAD(manifiesto.Man_Num, 4, '0')) AS Man_Num_Full,
                        COALESCE(mp_row.Pla_Nom, '') AS Pla_Nom,
                        vehiculo.Veh_Pla,
                        CAST((manifiesto.Man_Pes * (manifiesto.Man_Pun / 1000)) AS DECIMAL(10,2)) AS Valor
                    FROM manifiesto
                    LEFT JOIN manifiesto_plantas mp_row ON mp_row.Pla_Cod = manifiesto.Pla_Cod AND mp_row.Cli_Cod = manifiesto.Cli_Cod
                    LEFT JOIN vehiculo ON manifiesto.Veh_Cod = vehiculo.Veh_Cod
                    WHERE manifiesto.Man_Est = 'A' AND manifiesto.Vet_Cod = $Vet_Cod
                    ORDER BY manifiesto.Man_Fes ASC, manifiesto.Man_Num ASC";
            break;
    }
    return $sql;
}
