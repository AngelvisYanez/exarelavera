<?php

/**
 * Author : Wilson Belduma
 */
function sentencias_camaronera($id, $Par_Sql)
{
    $sql = "";
    switch ($id) {
        case 1: // Lista de productores
            $sql = "SELECT * FROM sector_camaronera  WHERE Prod_Cod=$Par_Sql[0] AND Sec_Est = 'A'";
            return $sql;
            break;
        case 2: //Obtener el numero de negociación
            $sql = "SELECT Num_Neg FROM nego_camaron WHERE Emp_Cod = '$Par_Sql[0]'  ORDER BY Num_Neg DESC LIMIT 1";
            return $sql;
            break;
        case 3: // Guarda comprobantes
            $sql = "INSERT INTO nego_camaron (Tip_Neg, Prod_Cod, Sec_Cod, Num_Neg, Fec_Neg,  Val_garantia, Val_Gar_Neta, Val_Ant, Val_Balanceado, Val_Larva,  Neg_Tot, Tot_Libras, Est_Neg, Link_Contrato, Link_Garantia,  Link_Verf_Garan, Neg_Des, Emp_Cod
            ) VALUES (
                " . (!empty($Par_Sql['Tip_Neg']) ? $Par_Sql['Tip_Neg'] : 'NULL') . ",
                " . (!empty($Par_Sql['Prod_Cod']) ? $Par_Sql['Prod_Cod'] : 'NULL') . ",
                " . (!empty($Par_Sql['Sec_Cod']) ? $Par_Sql['Sec_Cod'] : 'NULL') . ",
                '" . (!empty($Par_Sql['Num_Neg']) ? $Par_Sql['Num_Neg'] : '') . "',
                '" . (!empty($Par_Sql['Fec_Neg']) ? $Par_Sql['Fec_Neg'] : '') . "',
                " . (!empty($Par_Sql['Val_garantia']) ? $Par_Sql['Val_garantia'] : 'NULL') . ",
                " . (!empty($Par_Sql['Val_Gar_Neta']) ? $Par_Sql['Val_Gar_Neta'] : 'NULL') . ",
                " . (!empty($Par_Sql['Val_Ant']) ? $Par_Sql['Val_Ant'] : 'NULL') . ",
                " . (!empty($Par_Sql['Val_Balanceado']) ? $Par_Sql['Val_Balanceado'] : 'NULL') . ",
                " . (!empty($Par_Sql['Val_Larva']) ? $Par_Sql['Val_Larva'] : 'NULL') . ",
                " . (!empty($Par_Sql['Neg_Tot']) ? $Par_Sql['Neg_Tot'] : 'NULL') . ",
                " . (!empty($Par_Sql['Tot_Libras']) ? $Par_Sql['Tot_Libras'] : 'NULL') . ",
                '" . (!empty($Par_Sql['Est_Neg']) ? $Par_Sql['Est_Neg'] : '') . "',
                '" . (!empty($Par_Sql['Link_Contrato']) ? $Par_Sql['Link_Contrato'] : '') . "',
                '" . (!empty($Par_Sql['Link_Garantia']) ? $Par_Sql['Link_Garantia'] : '') . "',
                '" . (!empty($Par_Sql['Link_Verf_Garan']) ? $Par_Sql['Link_Verf_Garan'] : '') . "',
                '" . (!empty($Par_Sql['Neg_Des']) ? $Par_Sql['Neg_Des'] : '') . "',
                " . (!empty($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 'NULL') . "
            )";
            return $sql;
            break;

        case 4:
            $empCod = $Par_Sql[0];
            $filters =  $Par_Sql[3] . ' ' . $Par_Sql[2] . ' ' . $Par_Sql[4] . ' ' . $Par_Sql[5] . ' '  . $Par_Sql[6];
            $ORDER_BY = $Par_Sql[1];
            $groupBy = 'group by ng.Cod_Neg';
            $sql = "SELECT ng.*, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) AS productor, persona.Prs_Ced,
                CONCAT(persona2.Prs_Nom,' ',persona2.Prs_Ape) AS empacadora,
                ROUND(Tot_Libras * Prec_Comis, 2) AS total_comi
                FROM nego_camaron as ng
                INNER JOIN productor_camaron as pcam ON ng.Prod_Cod = pcam.Prod_Cod
                INNER JOIN proveedore ON proveedore.Prv_Cod = pcam.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                --  CARGAR EMPACADORA
                LEFT JOIN productor_camaron AS pcam2 ON ng.Empa_Cod = pcam2.Prod_Cod
                LEFT JOIN proveedore AS proveedore2 ON proveedore2.Prv_Cod = pcam2.Prv_Cod
                LEFT JOIN persona AS persona2 ON persona2.Prs_Cod = proveedore2.Prs_Cod
                WHERE proveedore.Emp_Cod = $empCod AND ng.Est_Neg!='I'   
                $filters 
                $groupBy 
                $ORDER_BY ";
            return $sql;
            break;

        case 5:
            $sql = "SELECT ccpp_pagar.Cpp_Cod,asientos.Asi_Val,compras.Cop_Cod, Cop_Fec,Cop_Num,
                  CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor,
					IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono,
					( asientos.Asi_Val-(IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',
                    ROUND(Pag_Val,2),0)))) ) as saldo
					FROM proveedore    
                    INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
                    INNER JOIN  nego_documentos ON nego_documentos.Cod_Doc = compras.Cop_Cod
					INNER JOIN  nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
                    INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
					INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
					INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
                    INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
					INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod) 
                    INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
					LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod 
                    LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
					WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
                    AND asientos.Com_Cod= comprobantes.Com_Cod
                    AND nego_camaron.Cod_Neg=$Par_Sql[1]  AND nego_documentos.Abr_Doc='CMP'
						AND asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND (comprobantes.Com_Est='A' 
                        OR comprobantes.Com_Est='E' )  AND  proveedore.Emp_Cod IN ($Par_Sql[0]) GROUP BY compras.Cop_Cod
                        ORDER by ccpp_pagar.Cpp_Ven;";
            return $sql;
            break;

        case 6:
            if (!empty($Par_Sql[2]) && $Par_Sql[2] == 'd') {
                $Par_Sql[1] = " AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$Par_Sql[1]%'";
            }
            if (!empty($Par_Sql[2]) && $Par_Sql[2] == 'c') {
                $Par_Sql[1] = " AND (persona.Prs_Ced) LIKE '%$Par_Sql[1]%'";
            }

            $sql = "SELECT persona.*, productor_camaron.Prod_Cod,Prv_Con,Prv_Esp, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as Proveedor, persona.Prs_Ced   
                FROM persona  
                INNER JOIN proveedore ON proveedore.Prs_Cod = persona.Prs_Cod
                INNER JOIN productor_camaron ON productor_camaron.Prv_Cod = proveedore.Prv_Cod
                WHERE proveedore.Emp_Cod = $Par_Sql[0] $Par_Sql[1]";
            return $sql;
            break;

        case 7:
            /*$sql = "SELECT persona.*, productor_camaron.Prod_Cod,Prv_Con,Prv_Esp, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as Empacadora, persona.Prs_Ced   
                    FROM persona  
                    INNER JOIN proveedore ON proveedore.Prs_Cod = persona.Prs_Cod
                    INNER JOIN productor_camaron ON productor_camaron.Prv_Cod = proveedore.Prv_Cod
                    WHERE proveedore.Emp_Cod = $Par_Sql[0] AND Tip_Prod='EMPA'";*/
            $sql = "SELECT persona.*, empac_camaron.Emc_Cod , CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as Empacadora, persona.Prs_Ced   
                    FROM persona  
                    INNER JOIN cliente ON cliente.Prs_Cod = persona.Prs_Cod
                    INNER JOIN empac_camaron ON empac_camaron.Cli_Cod = cliente.Cli_Cod
                    WHERE cliente.Emp_Cod = $Par_Sql[0] AND empac_camaron.Emc_Est='A'";
            return $sql;
            break;

        case 8: //Obtener el numero de aguaje
            $sql = "SELECT Num_Agu FROM aguaje_camaron WHERE Emp_Cod = '$Par_Sql[0]'  ORDER BY Num_Agu DESC LIMIT 1";
            return $sql;
            break;

        case 9: // Guarda comprobantes
            /*if (empty($Par_Sql['Agu_Cod'])) {
                $sql = "INSERT INTO aguaje_camaron(Num_Agu, Nom_Agu, Prod_Cod, Desc_Agu, Est_Agu, Emp_Cod
                ) VALUES (
                    '" . (isset($Par_Sql['Num_Agu']) ? $Par_Sql['Num_Agu'] : 'NULL') . "',
                    '" . (isset($Par_Sql['Nom_Agu']) ? $Par_Sql['Nom_Agu'] : 'NULL') . "',
                    " . (isset($Par_Sql['Prod_Cod']) ? $Par_Sql['Prod_Cod'] : 'NULL') . ",
                    '" . (isset($Par_Sql['Desc_Agu']) ? $Par_Sql['Desc_Agu'] : '') . "',
                    '" . (isset($Par_Sql['Est_Agu']) ? $Par_Sql['Est_Agu'] : '') . "',
                    " . (isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 'NULL') . "
                )";
            } else {
                $sql = "UPDATE aguaje_camaron  SET 
                    Num_Agu = '" . (isset($Par_Sql['Num_Agu']) ? $Par_Sql['Num_Agu'] : 'NULL') . "',
                    Nom_Agu = '" . (isset($Par_Sql['Nom_Agu']) ? $Par_Sql['Nom_Agu'] : 'NULL') . "',
                    Prod_Cod = " . (isset($Par_Sql['Prod_Cod']) ? $Par_Sql['Prod_Cod'] : 'NULL') . ",
                    Desc_Agu = '" . (isset($Par_Sql['Desc_Agu']) ? $Par_Sql['Desc_Agu'] : '') . "',
                    Est_Agu = '" . (isset($Par_Sql['Est_Agu']) ? $Par_Sql['Est_Agu'] : '') . "',
                    Emp_Cod = " . (isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 'NULL') . "
                WHERE Agu_Cod = " . $Par_Sql['Agu_Cod'];
            }*/
            if (empty($Par_Sql['Agu_Cod'])) {
                $sql = "INSERT INTO aguaje_camaron(Num_Agu, Nom_Agu, Emc_Cod, Desc_Agu, Est_Agu, Emp_Cod
                ) VALUES (
                    '" . (isset($Par_Sql['Num_Agu']) ? $Par_Sql['Num_Agu'] : 'NULL') . "',
                    '" . (isset($Par_Sql['Nom_Agu']) ? $Par_Sql['Nom_Agu'] : 'NULL') . "',
                    " . (isset($Par_Sql['Emc_Cod']) ? $Par_Sql['Emc_Cod'] : 'NULL') . ",
                    '" . (isset($Par_Sql['Desc_Agu']) ? $Par_Sql['Desc_Agu'] : '') . "',
                    '" . (isset($Par_Sql['Est_Agu']) ? $Par_Sql['Est_Agu'] : '') . "',
                    " . (isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 'NULL') . "
                )";
            } else {
                $sql = "UPDATE aguaje_camaron  SET 
                    Num_Agu = '" . (isset($Par_Sql['Num_Agu']) ? $Par_Sql['Num_Agu'] : 'NULL') . "',
                    Nom_Agu = '" . (isset($Par_Sql['Nom_Agu']) ? $Par_Sql['Nom_Agu'] : 'NULL') . "',
                    Emc_Cod = " . (isset($Par_Sql['Emc_Cod']) ? $Par_Sql['Emc_Cod'] : 'NULL') . ",
                    Desc_Agu = '" . (isset($Par_Sql['Desc_Agu']) ? $Par_Sql['Desc_Agu'] : '') . "',
                    Est_Agu = '" . (isset($Par_Sql['Est_Agu']) ? $Par_Sql['Est_Agu'] : '') . "',
                    Emp_Cod = " . (isset($Par_Sql['Emp_Cod']) ? $Par_Sql['Emp_Cod'] : 'NULL') . "
                WHERE Agu_Cod = " . $Par_Sql['Agu_Cod'];
            }
            return $sql;
            break;

        case 10:
            /* $sql = "SELECT aguaje_camaron.*,  CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as Empacadora, Prs_Dir, Ciu_Des ,productor_camaron.Prod_Cod FROM aguaje_camaron 
            INNER JOIN productor_camaron ON productor_camaron.Prod_Cod = aguaje_camaron.Prod_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod = productor_camaron.Prv_Cod
            INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
            INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
            WHERE aguaje_camaron.Emp_Cod=$Par_Sql[0] AND aguaje_camaron.Est_Agu = 'A'   $Par_Sql[1]  $Par_Sql[2]  ";*/
            $sql = "SELECT aguaje_camaron.*,  CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as Empacadora, Prs_Dir, Ciu_Des ,
            empac_camaron.Emc_Cod   FROM aguaje_camaron 
            INNER JOIN empac_camaron ON empac_camaron.Emc_Cod = aguaje_camaron.Emc_Cod 
            INNER JOIN cliente ON cliente.Cli_Cod = empac_camaron.Cli_Cod
            INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
            INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
            WHERE aguaje_camaron.Emp_Cod=$Par_Sql[0] AND aguaje_camaron.Est_Agu = 'A'   $Par_Sql[1]  $Par_Sql[2]  ";
            return $sql;
            break;

        case 11:
            $sql = "SELECT * FROM tallas_camaron WHERE Tip='$Par_Sql[0]' AND Tall_Est = 'A'";
            return $sql;
            break;

        case 12: //Obtener el numero de aguaje
            $sql = "INSERT INTO precios_camaron(Cod_Tall, Prec_A, Prec_B, Cod_Agu ) VALUES (
                    '" . (isset($Par_Sql['Cod_Tall']) ? $Par_Sql['Cod_Tall'] : 'NULL') . "',
                    '" . (isset($Par_Sql['Precio_A']) ? $Par_Sql['Precio_A'] : 'NULL') . "',
                    " . (isset($Par_Sql['Precio_B']) ? $Par_Sql['Precio_B'] : 'NULL') . ",
                    '" . (isset($Par_Sql['Cod_Agu']) ? $Par_Sql['Cod_Agu'] : '') . "' )";
            return $sql;
            break;

        case 13: //Obtener el numero de negociación
            $sql = "SELECT Num_Liq FROM liqui_camaron WHERE Emp_Cod = '$Par_Sql[0]'  ORDER BY Num_Liq DESC LIMIT 1";
            return $sql;
            break;

        case 14: // Guarda liquidación
            $sql = "INSERT INTO liqui_camaron ( Cod_Agu, Prod_Cod, Empa_Cod, Liq_Fecha, Peso_Rem,  Peso_Planta, Lib_Falt, Basur, Peso_Net, Lib_Proces, 
                Val_Rendi, Val_Lote, Val_Guia, Val_Gram_Glo, Peso_Prom, Val_Pisc, Val_Comision, Vnd_Cod, Gast_Control, Otr_Gastos,  Num_Liq, Emp_Cod, Cod_Neg
            ) VALUES (
                " .  $Par_Sql['Cod_Agu']  . ",
                " .  $Par_Sql['Prod_Cod']  . ",
                " .  $Par_Sql['Empa_Cod']  . ",
                '" . (!empty($Par_Sql['Liq_Fecha']) ? $Par_Sql['Liq_Fecha'] : '') . "',
                " . (!empty($Par_Sql['Peso_Rem']) ? $Par_Sql['Peso_Rem'] : 0) . ",
                " . (!empty($Par_Sql['Peso_Planta']) ? $Par_Sql['Peso_Planta'] : 0) . ",
                " . (!empty($Par_Sql['Lib_Falt']) ? $Par_Sql['Lib_Falt'] : 0) . ",
                " . (!empty($Par_Sql['Basur']) ? $Par_Sql['Basur'] : 0) . ",
                " . (!empty($Par_Sql['Peso_Net']) ? $Par_Sql['Peso_Net'] : 0) . ",
                " . (!empty($Par_Sql['Lib_Proces']) ? $Par_Sql['Lib_Proces'] : 0) . ",
                " . (!empty($Par_Sql['Val_Rendi']) ? $Par_Sql['Val_Rendi'] : 0) . ",
                '" . (!empty($Par_Sql['Val_Lote']) ? $Par_Sql['Val_Lote'] : 0) . "',
                '" . (!empty($Par_Sql['Val_Guia']) ? $Par_Sql['Val_Guia'] : 0) . "',
                " . (!empty($Par_Sql['Val_Gram_Glo']) ? $Par_Sql['Val_Gram_Glo'] : 0) . ",
                " . (!empty($Par_Sql['Peso_Prom']) ? $Par_Sql['Peso_Prom'] : 0) . ",
                " . (!empty($Par_Sql['Val_Pisc']) ?  "'" . $Par_Sql['Val_Pisc'] . "'" : 0) . ",
                " . (!empty($Par_Sql['Val_Comision']) ? $Par_Sql['Val_Comision'] : 0) . ",
                " . (!empty($Par_Sql['Vnd_Cod']) ? $Par_Sql['Vnd_Cod'] : 'NULL') . ",
                '" . (!empty($Par_Sql['Gast_Control']) ? $Par_Sql['Gast_Control'] : 0) . "',
                '" . (!empty($Par_Sql['Otr_Gastos']) ? $Par_Sql['Otr_Gastos'] : 0) . "',
                '" .  $Par_Sql['Num_Liq'] . "',
                " .  $Par_Sql['Emp_Cod'] . ",
                " . $Par_Sql['Cod_Neg'] . "
            )";
            return $sql;
            break;

        case 15: //Cargar liquidaciones
            $sql = "SELECT Num_Neg,nego_camaron.Est_Neg, Nom_Agu,Num_Agu, liqui_camaron.* ,(Peso_Rem - Peso_Planta) as diferencia, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as productor, persona.Prs_Ced
            FROM liqui_camaron 
            INNER JOIN  aguaje_camaron  ON liqui_camaron.Cod_Agu = aguaje_camaron.Agu_Cod
            INNER JOIN productor_camaron ON productor_camaron.Prod_Cod = liqui_camaron.Prod_Cod
            INNER JOIN proveedore ON proveedore.Prv_Cod = productor_camaron.Prv_Cod 
            INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
            INNER JOIN nego_camaron ON nego_camaron.Cod_Neg = liqui_camaron.Cod_Neg
            WHERE liqui_camaron.Emp_Cod = '$Par_Sql[0]' AND liqui_camaron.Cod_Neg ='$Par_Sql[1]'  AND Est_Liq='A' ";
            return $sql;
            break;

        case 16:
            $sql = "SELECT cliente.Cli_Cod,
							CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente, persona.Prs_Ced,
							persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod,
							ccpp_cobrar.Cpc_Cod,
							caja_aper.Caj_Fec,
							CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
							ccpp_cobrar.Cpc_Ven,
							ccpp_cobrar.Com_Cod,
							asientos.Asi_Cod,
							asientos.Pld_Cod,
							Pld_Cdc,Pld_Des, Num_Neg,
							CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1, CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
							IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
							/* Notas de débito (Tic_Cod=5) deben mostrarse positivas */
							CASE WHEN ventas.Tic_Cod='5' THEN ABS(asientos.Asi_Val) ELSE asientos.Asi_Val END AS Asi_Val,
						  CASE
							WHEN ventas.Tic_Cod='5' THEN
								ABS(IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))))
							ELSE
								IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))
							END AS Abono,
							/* Saldo basado en el mismo signo que Asi_Val/Abono */
							(
								(CASE WHEN ventas.Tic_Cod='5' THEN ABS(asientos.Asi_Val) ELSE asientos.Asi_Val END)
								- (
									CASE
										WHEN ventas.Tic_Cod='5' THEN
											ABS(IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))))
										ELSE
											IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))
									END
								)
							) as saldo
						FROM cliente
						INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
						INNER JOIN  nego_documentos ON nego_documentos.Cod_Doc = ventas.Vet_Cod
						INNER JOIN  nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
						INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
						INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
						INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod)
						INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod)
						INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
						INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
						INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
						INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod)
						INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
						LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod
						LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
						WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND
							asientos.Com_Cod = comprobantes.Com_Cod AND asientos.Asi_Deh= 'D' AND
							(ventas.vet_Est='A' OR ventas.vet_Est='E')  AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
							sucursal.Emp_Cod  IN ($Par_Sql[0])  AND nego_camaron.Cod_Neg=$Par_Sql[1] AND nego_documentos.Abr_Doc='VNT'
						GROUP BY ventas.Vet_Cod ORDER BY Vet_Num;";
			
            return $sql;
            break;

        case 17:
            if (!empty($Par_Sql[2])) {
                $Par_Sql[2] = " AND compras.Cop_Cod = $Par_Sql[2]";
            } else {
               // $Par_Sql[2] = " AND tipos_pago.Pag_Cod != 20 ";
                 $Par_Sql[2] = " AND tipos_pago.Pag_Cod NOT IN (20, 5, 50, 10, 13)";
            }
            $sql = "SELECT det_ccpp_p.*,comprobantes.Com_Cod, det_ccpp_p.Pag_Val ,
                CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as proveedor, persona.Prs_Ced, Cop_Num, Pag_Des,compras.Cop_Cod
                FROM nego_camaron
                INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc 
                INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                INNER JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
                INNER JOIN det_ccpp_p ON det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod
                left JOIN comprobantes ON comprobantes.Com_Cod=det_ccpp_p.Com_Cod
                INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = det_ccpp_p.Pag_Cod
                WHERE proveedore.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1]  
                AND nego_documentos.Abr_Doc='CMP' AND  det_ccpp_p.Pag_Est='A' AND comprobantes.Com_Est='A' $Par_Sql[2] ";
            return $sql;
            break;

        case 18:
            if (!empty($Par_Sql[2])) {
                $Par_Sql[2] = " AND ventas.Vet_Cod = $Par_Sql[2]";
            }
            $sql = "SELECT det_ccpp_c.*, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, persona.Prs_Ced , ventas.Vet_Cod,
                     CONCAT( Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, Pag_Des
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN ventas ON ventas.Vet_Cod = nego_documentos.Cod_Doc 
                    INNER JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                    INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
                    INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_c.Com_Cod
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = det_ccpp_c.Pag_Cod
                    WHERE cliente.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1]  AND nego_documentos.Abr_Doc='VNT' AND Cpc_Est='A' AND Com_Est='A' $Par_Sql[2]";
            return $sql;
            break;

        case 19: // usado
            $sql = "SELECT vendedor.* ,  CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as vendedores FROM vendedor
                    INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN persona ON persona.Prs_Cod =  vendedor.Prs_Cod
                    WHERE Suc_Cod=$Par_Sql[0] AND vendedor.Prs_Cod=$Par_Sql[1]";
            return $sql;
            break;

        case 20: // usado
            $sql = "SELECT precios_camaron.*, Talla FROM precios_camaron
                        INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
                        WHERE tallas_camaron.Tip='$Par_Sql[2]' AND precios_camaron.Cod_Agu=$Par_Sql[1]";
            return $sql;
            break;

        case 21:

            $sql = "INSERT INTO det_liq_camaron (Liq_Cod, Cod_Prec, Cant, Prec, Med, Tip_Cla, Est_Det) 
                    VALUES (
                    '" . $Par_Sql['Liq_Cod'] . "', '" . $Par_Sql['Cod_Prec'] . "',
                    " . $Par_Sql['Cant'] . ", " . $Par_Sql['Prec'] . ",
                    " . (isset($Par_Sql['Med']) ? "'" . $Par_Sql['Med'] . "'" : "NULL") . ",
                    " . (isset($Par_Sql['Tip_Cla']) ? "'" . $Par_Sql['Tip_Cla'] . "'" : "NULL") . ",
                    '" . $Par_Sql['Est_Det'] . "' )";
            return $sql;
            break;
        case 22:
            $sql = "DELETE FROM det_liq_camaron WHERE Liq_Cod = '" . $Par_Sql['Liq_Cod'] . "'";
            return $sql;
            break;
        case 23:
            if (!empty($Par_Sql[1])) {
                $Par_Sql[1] = " AND Tip= '$Par_Sql[1]'";
            }

            $sql = "SELECT det_liq_camaron.*, Talla, Tip ,  ROUND(Cant*Prec, 7) as total FROM det_liq_camaron  
            INNER JOIN precios_camaron ON precios_camaron.Cod_Prec = det_liq_camaron.Cod_Prec
            INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
            WHERE Liq_Cod = " . $Par_Sql[0] .  $Par_Sql[1] . " AND Est_Det = 'A'";
            return $sql;
            break;

        case 24:
            $sql = "SELECT  Sum(ROUND(Cant*Prec, 4)) as total , Sum(Cant) as totalCant
            FROM det_liq_camaron  
                INNER JOIN precios_camaron ON precios_camaron.Cod_Prec = det_liq_camaron.Cod_Prec
                INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
                WHERE Liq_Cod = " . $Par_Sql[0] . " AND Tip= '$Par_Sql[1]'" . " AND Est_Det = 'A'";
            return $sql;
            break;

        case 25:
            $sql = "SELECT Tip FROM tallas_camaron WHERE Tall_Est = 'A' GROUP BY Tip";
            return $sql;
            break;

        case 26:
            $sql = "SELECT det_liq_camaron.*, Talla, Tip ,  Sum( ROUND(Cant*Prec, 4)) as total , 
                SUM(IF(Tip = 'Entero', Cant, 0)) AS totalEntero,
                SUM(IF(Tip = 'COLAA', Cant, 0)) AS totalColaA,
                SUM(IF(Tip = 'COLAB', Cant, 0)) AS totalColaB,
                SUM(IF(Tip = 'NACIONAL', Cant, 0)) AS totalNacional
                FROM det_liq_camaron  
                    INNER JOIN precios_camaron ON precios_camaron.Cod_Prec = det_liq_camaron.Cod_Prec
                    INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
                    WHERE Liq_Cod = '" . $Par_Sql['0'] . "'";
            return $sql;
            break;

        case 27: // usado
            $sql = "SELECT precios_camaron.*, Talla FROM precios_camaron
                    INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
                    WHERE  precios_camaron.Cod_Agu=$Par_Sql[1]";
            return $sql;
            break;

        case 28:
            $sql = "SELECT Cod_Prec FROM precios_camaron WHERE Cod_Tall = $Par_Sql[0] AND Cod_Agu = $Par_Sql[1]";
            return $sql;
            break;
        case 29:
            $sql = "UPDATE precios_camaron SET 
                    Cod_Tall = '" . $Par_Sql['Cod_Tall'] . "',
                    Prec_A   = '" . (isset($Par_Sql['Precio_A']) ? $Par_Sql['Precio_A'] : 'NULL') . "',
                    Prec_B   = "  . (isset($Par_Sql['Precio_B']) ? $Par_Sql['Precio_B'] : 'NULL') . ",
                    Cod_Agu  = '" . $Par_Sql['Cod_Agu'] . "'
                WHERE Cod_Prec = '" . $Par_Sql['Cod_Prec'] . "'";
            return $sql;
            break;

        case 30:
            $sql = "SELECT comprobantes.*,  Com_Fec AS Pag_Fec,  Com_Con AS Pag_Obs, 'Efectivo' AS Pag_Des ,Cop_Num, Com_Val AS Pag_Val,CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS proveedor,persona.Prs_Ced ,compras.Cop_Cod
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc 
                    INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
                    INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod 
                    INNER JOIN compr_auto ON compr_auto.Cop_Cod = compras.Cop_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = compr_auto.Com_Cod
                    LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
                    WHERE proveedore.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg = $Par_Sql[1]  AND nego_documentos.Abr_Doc = 'CMP' AND comprobantes.Com_Est='A'  AND ccpp_pagar.Cop_Cod IS NULL;";
            return $sql;
            break;

        case 31:
            $sql = "UPDATE nego_camaron SET 
            Tip_Neg     = " . (isset($Par_Sql['Tip_Neg'])     ? "'" . $Par_Sql['Tip_Neg'] . "'"     : "NULL") . ",
            Prod_Cod    = " . (isset($Par_Sql['Prod_Cod'])    ? "'" . $Par_Sql['Prod_Cod'] . "'"    : "NULL") . ",
            Sec_Cod     = " . (isset($Par_Sql['Sec_Cod'])     ? "'" . $Par_Sql['Sec_Cod'] . "'"     : "NULL") . ",
            Fec_Neg     = " . (isset($Par_Sql['Fec_Neg'])     ? "'" . $Par_Sql['Fec_Neg'] . "'"     : "NULL") . ",
            Val_Ant     = " . (isset($Par_Sql['Val_Ant'])     ? "'" . $Par_Sql['Val_Ant'] . "'"     : "NULL") . ",
            Tot_Libras  = " . (isset($Par_Sql['Tot_Libras'])  ? "'" . $Par_Sql['Tot_Libras'] . "'"  : "NULL") . ",
            Prec_Comis  = " . (isset($Par_Sql['Prec_Comis'])  ? "'" . $Par_Sql['Prec_Comis'] . "'"  : "NULL") . ",
            Vnd_Cod     = " . (isset($Par_Sql['Vnd_Cod'])     ? "'" . $Par_Sql['Vnd_Cod'] . "'"     : "NULL") . ",
            Clasf       = " . (isset($Par_Sql['Clasf'])       ? "'" . $Par_Sql['Clasf'] . "'"       : "NULL") . ",
            Fec_Pesca   = " . (isset($Par_Sql['Fec_Pesca'])   ? "'" . $Par_Sql['Fec_Pesca'] . "'"   : "NULL") . ",
            Est_Neg     = " . (isset($Par_Sql['Est_Neg'])     ? "'" . $Par_Sql['Est_Neg'] . "'"     : "'A'") . ",
            Empa_Cod     = " . (isset($Par_Sql['Empa_Cod'])   ? "'" . $Par_Sql['Empa_Cod'] . "'"    : "NULL") . ",
            Cod_Agu     = " . (isset($Par_Sql['Cod_Agu'])     ? "'" . $Par_Sql['Cod_Agu'] . "'"     : "NULL") . "
            WHERE Cod_Neg = '" . $Par_Sql['Cod_Neg'] . "'";
            return $sql;
            break;

        case 32:
            $sql = "UPDATE nego_camaron SET Est_Neg='I' WHERE Cod_Neg = $Par_Sql[0]";
            return $sql;
            break;
        case 33:
            $sql = "UPDATE liqui_camaron SET Est_Liq='I' WHERE Liq_Cod = $Par_Sql[0]";
            return $sql;
            break;
        //Obtener totales
        case 34:
            $sql = "SELECT SUM((det_compra.Cop_Imp * (COALESCE(Iva.Iva_Por, 0) / 100))) AS Iva,
            SUM(det_compra.Cop_Can * det_compra.Cop_Imp) AS subtotal ,
            SUM(( det_compra.Cop_Can * det_compra.Cop_Imp)  + ( det_compra.Cop_Can * det_compra.Cop_Imp)  *  (COALESCE(Iva.Iva_Por, 0) / 100)) AS total
            FROM nego_camaron
            INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
            INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc 
            INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
            INNER JOIN det_compra ON det_compra.Cop_Cod = compras.Cop_Cod 
            INNER JOIN iva ON iva.Iva_Cod = det_compra.Iva_Cod
            WHERE nego_camaron.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1]  AND nego_documentos.Abr_Doc='CMP' 
            GROUP BY nego_camaron.Cod_Neg";
            return $sql;
            break;
        case 35:
            $sql = "SELECT SUM(Pag_Val) as tot_pag
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc
                    INNER JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
                    INNER JOIN det_ccpp_p ON det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_p.Com_Cod
                    WHERE nego_camaron.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1] 
                     AND nego_documentos.Abr_Doc='CMP' AND Pag_Est='A' GROUP BY nego_camaron.Cod_Neg";
            return $sql;
            //obtener el total de los contado
        case 36:
            $sql = "SELECT SUM(Com_Val) as tot_pag_c
                        FROM nego_camaron
                        INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                        INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc 
                        INNER JOIN compr_auto ON compr_auto.Cop_Cod = compras.Cop_Cod
                        INNER JOIN comprobantes ON comprobantes.Com_Cod = compr_auto.Com_Cod
                        LEFT JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
                        WHERE nego_camaron.Emp_Cod = $Par_Sql[0] AND nego_camaron.Cod_Neg = $Par_Sql[1]  
                        AND nego_documentos.Abr_Doc = 'CMP'AND ccpp_pagar.Cop_Cod IS NULL  GROUP BY nego_camaron.Cod_Neg;";
            return $sql;
        case 37:
            if (!empty($Par_Sql[2])) {
                $Par_Sql[2] = " AND nego_documentos.Tip_Prod='$Par_Sql[2]'";
            }

            $sql = "SELECT SUM((ventas_det.Vet_Imp * (COALESCE(iva.Iva_Por, 0) / 100))) AS Iva,
                SUM(ventas_det.Vet_Can * ventas_det.Vet_Pru) AS subtotal ,
                SUM(( ventas_det.Vet_Can * ventas_det.Vet_Pru)+( ventas_det.Vet_Can * ventas_det.Vet_Pru)  *  (COALESCE(iva.Iva_Por, 0) / 100)) AS total
                FROM nego_camaron
                INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                INNER JOIN ventas ON ventas.Vet_Cod = nego_documentos.Cod_Doc 
                INNER JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod  
                INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod =  ventas.Vet_Cod            
                INNER JOIN comprobantes ON comprobantes.Com_Cod = ventas_compr.Com_Cod
                INNER JOIN ventas_det ON ventas_det.Vet_Cod = ventas.Vet_Cod 
                INNER JOIN iva ON iva.Iva_Cod = ventas_det.Iva_Cod
                WHERE nego_camaron.Emp_Cod IN ($Par_Sql[0]) AND comprobantes.Com_Est='A' AND nego_camaron.Cod_Neg=$Par_Sql[1] $Par_Sql[2] AND nego_documentos.Abr_Doc='VNT' AND ventas.vet_Est='A' 
                GROUP BY nego_camaron.Cod_Neg";
            return $sql;
            break;

        case 38:
            $sql = "SELECT SUM(comprobantes.Com_Val) as val_cobr 
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN ventas ON ventas.Vet_Cod = nego_documentos.Cod_Doc 
                    INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod 
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_c.Com_Cod
                    WHERE nego_camaron.Emp_Cod = $Par_Sql[0] AND nego_camaron.Cod_Neg=$Par_Sql[1] 
                     AND nego_documentos.Abr_Doc='VNT' AND comprobantes.Com_Est='A' GROUP BY nego_camaron.Cod_Neg";
            return $sql;
            break;

        case 39:
            $sql = "SELECT persona.*, productor_camaron.Prod_Cod,Prv_Con,Prv_Esp,Ciu_Des ,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as nombres, persona.Prs_Ced   
                            FROM persona  
                            INNER JOIN proveedore ON proveedore.Prs_Cod = persona.Prs_Cod
                            INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
                            INNER JOIN productor_camaron ON productor_camaron.Prv_Cod = proveedore.Prv_Cod
                            WHERE proveedore.Emp_Cod = $Par_Sql[0] /*AND Tip_Prod='$Par_Sql[1]'*/ AND  productor_camaron.Prod_Cod =$Par_Sql[2] ";
            return $sql;
            break;

        case 399:
            $sql = "SELECT persona.*, empac_camaron.Cli_Cod,Ciu_Des ,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as nombres, persona.Prs_Ced   
                                FROM persona  
                                INNER JOIN cliente ON cliente.Prs_Cod = persona.Prs_Cod
                                INNER JOIN ciudad ON ciudad.Ciu_Cod = persona.Ciu_Cod
                                INNER JOIN empac_camaron ON empac_camaron.Cli_Cod = cliente.Cli_Cod
                                WHERE cliente.Emp_Cod = $Par_Sql[0] /*AND Tip_Prod='$Par_Sql[1]'*/ AND  empac_camaron.Emc_Cod =$Par_Sql[2] ";
            return $sql;
            break;



        case 40: // Guarda liquidación
            $sql = "UPDATE liqui_camaron SET
                    Cod_Agu = " .  $Par_Sql['Cod_Agu'] . ",
                    Prod_Cod = " . $Par_Sql['Prod_Cod']  . ",
                    Empa_Cod = " .  $Par_Sql['Empa_Cod']  . ",
                    Liq_Fecha = '" . (!empty($Par_Sql['Liq_Fecha']) ? $Par_Sql['Liq_Fecha'] : '') . "',
                    Peso_Rem = " . (!empty($Par_Sql['Peso_Rem']) ? $Par_Sql['Peso_Rem'] : 0) . ",
                    Peso_Planta = " . (!empty($Par_Sql['Peso_Planta']) ? $Par_Sql['Peso_Planta'] : 0) . ",
                    Lib_Falt = " . (!empty($Par_Sql['Lib_Falt']) ? $Par_Sql['Lib_Falt'] : 0) . ",
                    Basur = " . (!empty($Par_Sql['Basur']) ? $Par_Sql['Basur'] : 0) . ",
                    Peso_Net = " . (!empty($Par_Sql['Peso_Net']) ? $Par_Sql['Peso_Net'] : 0) . ",
                    Lib_Proces = " . (!empty($Par_Sql['Lib_Proces']) ? $Par_Sql['Lib_Proces'] : 0) . ",
                    Val_Rendi = " . (!empty($Par_Sql['Val_Rendi']) ? $Par_Sql['Val_Rendi'] : 0) . ",               
                    Val_Lote = " . (!empty($Par_Sql['Val_Lote']) ? "'" . addslashes($Par_Sql['Val_Lote']) . "'" : 0) . ",
                    Val_Guia = " . (!empty($Par_Sql['Val_Guia']) ? "'" . addslashes($Par_Sql['Val_Guia']) . "'" : 0) . ",
                    Val_Gram_Glo = " . (!empty($Par_Sql['Val_Gram_Glo']) ? $Par_Sql['Val_Gram_Glo'] : 0) . ",
                    Peso_Prom = " . (!empty($Par_Sql['Peso_Prom']) ? $Par_Sql['Peso_Prom'] : 0) . ",
                    Val_Pisc = " . (!empty($Par_Sql['Val_Pisc']) ?  "'" . $Par_Sql['Val_Pisc'] . "'"  : 0) . ",
                    Val_Comision = " . (!empty($Par_Sql['Val_Comision']) ? $Par_Sql['Val_Comision'] : 0) . ",
                    Vnd_Cod = " . (!empty($Par_Sql['Vnd_Cod']) ? $Par_Sql['Vnd_Cod'] : 'NULL') . ",
                    Gast_Control = " . (!empty($Par_Sql['Gast_Control']) ? $Par_Sql['Gast_Control'] : 0) . ",
                    Otr_Gastos = " . (!empty($Par_Sql['Otr_Gastos']) ? $Par_Sql['Otr_Gastos'] : 0) . ",
                    Num_Liq = '" . (!empty($Par_Sql['Num_Liq']) ? $Par_Sql['Num_Liq'] : '') . "',
                    Emp_Cod = " .  $Par_Sql['Emp_Cod'] . "
                    WHERE Liq_Cod = " . $Par_Sql['Liq_Cod'];
            return $sql;
            break;

        case 41:
            $sql = "SELECT  Sum(ROUND(Cant*Prec, 4)) as total , Sum(Cant) as totalCant
                    FROM det_liq_camaron  
                        INNER JOIN precios_camaron ON precios_camaron.Cod_Prec = det_liq_camaron.Cod_Prec
                        INNER JOIN tallas_camaron ON tallas_camaron.Cod_Tall = precios_camaron.Cod_Tall
                        WHERE Liq_Cod = " . $Par_Sql[0] . " AND Est_Det = 'A'";
            return $sql;
            break;
        case 42:
            if (!empty($Par_Sql['Cod_Tall'])) {
                $sql = "UPDATE tallas_camaron 
                    SET Talla = '" . $Par_Sql['Talla'] . "',  Tip = '" . $Par_Sql['Tip'] . "', 
                        Tip_Med = '" . $Par_Sql['Tip_Med'] . "',  Tall_Est = '" . $Par_Sql['Tall_Est'] . "' 
                    WHERE Cod_Tall = '" . $Par_Sql['Cod_Tall'] . "'";
            } else {
                $sql = "INSERT INTO tallas_camaron (Talla, Tip, Tip_Med,Tall_Est) 
                    VALUES ('" . $Par_Sql['Talla'] . "', '" . $Par_Sql['Tip'] . "', '" . $Par_Sql['Tip_Med'] . "' , '" . $Par_Sql['Tall_Est'] . "' )";
            }
            return $sql;
            break;

        case 43:
            $sql = "SELECT * FROM tallas_camaron WHERE Tall_Est = 'A' $Par_Sql[1]";
            return $sql;
            break;
        case 44:
            $sql = "UPDATE tallas_camaron  SET Tall_Est = 'I' WHERE Cod_Tall = $Par_Sql[1]";
            return $sql;
            break;
        case 45:
            $sql = "SELECT c.*,   ap.Atp_Cod   AS Cop_Cod, c.Com_Val  AS Pag_Val, c.Com_Fec  AS Pag_Fec, c.Com_Con   AS Pag_Obs,
                    ROUND(COALESCE(pagos.Abono, 0), 2)   AS Abono,
                    ROUND(c.Com_Val - COALESCE(pagos.Abono, 0), 2) AS Saldo,
                    CONCAT(p.Prs_Nom,' ',p.Prs_Ape)   AS proveedor, p.Prs_Ced
                FROM nego_camaron   nc
                JOIN nego_documentos nd ON nd.Cod_Neg = nc.Cod_Neg
                JOIN anticipos_proveedores  ap   ON ap.Atp_Cod = nd.Cod_Doc
                JOIN proveedore pr  ON pr.Prv_Cod = ap.Prv_Cod
                JOIN persona  p  ON p.Prs_Cod  = pr.Prs_Cod
                JOIN comprobantes c  ON c.Com_Cod  = ap.Com_Cod
                LEFT JOIN ( SELECT  d.Atp_Cod, SUM(COALESCE(d.Dac_Val,0)) AS Abono FROM det_ant_ccpp d GROUP BY d.Atp_Cod) pagos ON pagos.Atp_Cod = ap.Atp_Cod
                WHERE pr.Emp_Cod IN ($Par_Sql[0])
                AND nc.Cod_Neg = $Par_Sql[1]   AND nd.Abr_Doc = 'ANTP' AND c.Com_Est  = 'A' ORDER BY ap.Atp_Cod;";
            return $sql;
            break;
        case 46:
            $sql = "SELECT comprobantes.*,comprobantes.Com_Val AS Cpc_Val, Ant_Cod AS Vet_Cod,  Com_Fec AS Cpc_Fec, Com_Con AS Vet_Num, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, persona.Prs_Ced 
                            FROM nego_camaron
                            INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                            INNER JOIN anticipos_clientes ON anticipos_clientes.Ant_Cod = nego_documentos.Cod_Doc 
                            INNER JOIN cliente ON cliente.Cli_Cod = anticipos_clientes.Cli_Cod
                            INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
                            INNER JOIN comprobantes ON comprobantes.Com_Cod = anticipos_clientes.Com_Cod
                            WHERE cliente.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1]  AND nego_documentos.Abr_Doc='ANTC' AND Ant_Est='A'";
            return $sql;
            break;

        case 47:
            $sql = "SELECT Emp_Nom, Emp_Log FROM empresas WHERE Emp_Cod = $Par_Sql[0] AND Emp_Est='A'";
            return $sql;
            break;

        case 48:
            $sql = "SELECT  cliente.Cli_Cod,
							CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente, persona.Prs_Ced,
							persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod,
							ccpp_cobrar.Cpc_Cod,tipo_compr.Tic_Des,
							caja_aper.Caj_Fec, Tip_Prod,
							CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
							ccpp_cobrar.Cpc_Ven,
							ccpp_cobrar.Com_Cod,
							asientos.Asi_Cod,
							asientos.Pld_Cod,
							Pld_Cdc,Pld_Des, Num_Neg,
							CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1, CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
							IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
							asientos.Asi_Val,
						  IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono,
							(asientos.Asi_Val)-(IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))) as saldo
						FROM cliente
						INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
                         INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ventas.Tic_Cod
						INNER JOIN  nego_documentos ON nego_documentos.Cod_Doc = ventas.Vet_Cod
						INNER JOIN  nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
						INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
						INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
						INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod)
						INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod)
						INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
						INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
						INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
						INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod)
						INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
						LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod
						LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
						WHERE cliente.Prs_Cod = persona.Prs_Cod AND
							comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND
							asientos.Com_Cod = comprobantes.Com_Cod AND
							asientos.Asi_Deh= 'D' AND
							(ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
							(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
							sucursal.Emp_Cod  IN ($Par_Sql[0])  AND nego_camaron.Cod_Neg=$Par_Sql[1] 
                            AND nego_documentos.Abr_Doc='VNT'
                            AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '')
						GROUP BY ventas.Vet_Cod
						ORDER BY Vet_Num;";

            return $sql;
            break;

        case 49:
            if (!empty($Par_Sql[4])) {
                $Par_Sql[4] = " AND nego_documentos.Tip_Prod='$Par_Sql[4]'";
            }

            $sql = "SELECT ventas.*, nego_documentos.Tip_Prod, nego_camaron.Num_Neg, comprobantes.Com_Cod, comprobantes.Com_Est, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, persona.Prs_Ced,Com_Fec, 
                        CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
                        SUM((ventas_det.Vet_Imp * (COALESCE(iva.Iva_Por, 0) / 100))) AS Iva,
                        SUM(ventas_det.Vet_Can * ventas_det.Vet_Pru) AS subtotal ,
                        SUM(( ventas_det.Vet_Can * ventas_det.Vet_Pru)+( ventas_det.Vet_Can * ventas_det.Vet_Pru)  *  (COALESCE(iva.Iva_Por, 0) / 100)) AS total
            FROM ventas
                LEFT  JOIN nego_documentos ON nego_documentos.Cod_Doc  = ventas.Vet_Cod 
                LEFT JOIN nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
                INNER JOIN ventas_compr ON ventas_compr.Vet_Cod = ventas.Vet_Cod
                INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                INNER JOIN comprobantes ON comprobantes.Com_Cod = ventas_compr.Com_Cod
                INNER JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
               -- INNER JOIN proveedore ON proveedore.Prs_Cod = persona.Prs_Cod 
               -- INNER JOIN productor_camaron ON productor_camaron.Prv_Cod = proveedore.Prv_Cod  
                INNER JOIN ventas_det ON ventas_det.Vet_Cod = ventas.Vet_Cod 
                INNER JOIN iva ON iva.Iva_Cod = ventas_det.Iva_Cod
            WHERE cliente.Emp_Cod = $Par_Sql[0] $Par_Sql[1] AND
                    comprobantes.Com_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]' $Par_Sql[4] $Par_Sql[5]
            GROUP BY ventas_det.Vet_Cod";
            return $sql;
            break;
        // Nueva - Reporte de compras
        case 50:
            if (!empty($Par_Sql[4])) {
                $Par_Sql[4] = " AND nego_documentos.Tip_Prod='$Par_Sql[4]'";
            }

            $sql = "SELECT compras.*,nego_documentos.Tip_Prod ,nego_camaron.Num_Neg, comprobantes.Com_Cod, comprobantes.Com_Est, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as proveedor, 
                    persona.Prs_Ced,Com_Fec, Cop_Num,
                    SUM((det_compra.Cop_Imp * (COALESCE(iva.Iva_Por, 0) / 100))) AS Iva,
                    SUM(det_compra.Cop_Can * det_compra.Cop_Pru) AS subtotal ,
                    SUM(( det_compra.Cop_Can * det_compra.Cop_Pru)+( det_compra.Cop_Can * det_compra.Cop_Pru)  *  (COALESCE(iva.Iva_Por, 0) / 100)) AS total
            FROM compras
                LEFT  JOIN nego_documentos ON nego_documentos.Cod_Doc  = compras.Cop_Cod 
                LEFT JOIN nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
                INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
                INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                INNER JOIN det_compra ON det_compra.Cop_Cod = compras.Cop_Cod 
                INNER JOIN compr_auto ON compr_auto.Cop_Cod = compras.Cop_Cod
                INNER JOIN comprobantes ON comprobantes.Com_Cod = compr_auto.Com_Cod
                INNER JOIN iva ON iva.Iva_Cod = det_compra.Iva_Cod
            WHERE proveedore.Emp_Cod = $Par_Sql[0] $Par_Sql[1] AND
                    comprobantes.Com_Fec BETWEEN  '$Par_Sql[2]' AND '$Par_Sql[3]' $Par_Sql[4] $Par_Sql[5]
            GROUP BY det_compra.Cop_Cod";
            return $sql;
            break;
        // Nueva - Reporte de Anticipo Proveedores
        case 51:
            $sql = "SELECT anticipos_proveedores.*,
                        CONCAT (tpAst.Tia_Abr,'-', MONTH (cprbnt.Com_Fec),'-',cprbnt.Com_Num) AS codigoCompra,
                        prv.*, prs.*,
                        CONCAT (prs.Prs_Nom, ' ', prs.Prs_Ape) AS nombre,
                        prs.Prs_Ced AS cedProv,
                        cprbnt.*, tpAst.*, nego_documentos.*, negocam.Num_Neg,
                        CAST((Atp_Val) AS DECIMAL(20, 2)) AS sumaAtpVal,
                        CAST(IF (Dac_Val IS NULL, 0, SUM(Dac_Val)) AS DECIMAL(20, 2) ) AS sumaDacVal,
                        CAST((Atp_Val) AS DECIMAL(20, 2)) - IF (Dac_Val IS NULL, 0, SUM(Dac_Val)) AS tot_anti,
                        pagosantprv.Pagos, pagosantprv.Pap_Cod,
                        CONCAT (prsn.Prs_Nom, ' ', prsn.Prs_Ape) AS usuario,
                        usr.Usu_Cod, prsn.Prs_Nom, prsn.Prs_Ape,
                        daCcpp.Dac_Cod, daCcpp.Dac_Val,
                        tpsPg.Pag_Cod, tpsPg.Pag_Abr, tpsPg.Pag_Des
                    FROM anticipos_proveedores
                        INNER JOIN proveedore AS prv ON prv.Prv_Cod = anticipos_proveedores.Prv_Cod
                        LEFT JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
                        INNER JOIN comprobantes AS cprbnt ON cprbnt.Com_Cod = anticipos_proveedores.Com_Cod
                        INNER JOIN tipo_asien AS tpAst ON tpAst.Tia_Cod = cprbnt.Tia_Cod
                        LEFT JOIN nego_documentos ON nego_documentos.Cod_Doc = anticipos_proveedores.Atp_Cod
                        LEFT JOIN nego_camaron AS negocam ON nego_documentos.Cod_Neg = negocam.Cod_Neg
                        LEFT JOIN (
                            SELECT
                            pap.*, tpsPg.Pag_Abr, tpsPg.Pag_Des,
                            CAST(IF (IF (Pap_Val IS NULL, 0, Pap_Val) IS NULL,0,SUM(IF (Pap_Val IS NULL, 0, Pap_Val))) AS DECIMAL(20, 2)) AS Pagos
                            FROM pago_anticipo_proveedores AS pap
                                LEFT JOIN tipos_pago AS tpsPg ON tpsPg.Pag_Cod = pap.Pag_Cod
                            WHERE (pap.Atp_Cod = Atp_Cod)
                            GROUP BY pap.Atp_Cod
                        ) AS pagosantprv ON pagosantprv.Atp_Cod = anticipos_proveedores.Atp_Cod
                        INNER JOIN usuarios AS usr ON usr.Usu_Cod = cprbnt.Usu_Cod
                        INNER JOIN persona AS prsn ON prsn.Prs_Cod = usr.Prs_Cod
                        LEFT JOIN det_ant_ccpp AS daCcpp ON daCcpp.Atp_Cod = anticipos_proveedores.Atp_Cod
                        LEFT JOIN tipos_pago AS tpsPg ON tpsPg.Pag_Cod = pagosantprv.Pag_Cod
                    WHERE
                        prv.Emp_Cod = $Par_Sql[0] $Par_Sql[1] AND
                        anticipos_proveedores.Atp_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]' AND
                        anticipos_proveedores.Atp_Est IN ('A', 'U', 'C') 
                    GROUP BY anticipos_proveedores.Atp_Cod
                    ORDER BY FIELD (Atp_Est, 'A', 'U', 'C'), Atp_Fec ASC";
            return $sql;
            break;
        // Nueva - Detalle del Anticipo Proveedores
        case 52:
            $sql = "SELECT
                        det_ant_ccpp.*,
                        CONCAT (tpAst.Tia_Abr, '-', MONTH (cprbnt.Com_Fec), '-', cprbnt.Com_Num) AS codigoCompra,
                        atp.*, prv.*, prs.*,
                        CONCAT (prs.Prs_Nom, ' ', prs.Prs_Ape) AS nombre,
                        cprbnt.*, tpAst.*
                        FROM det_ant_ccpp
                            INNER JOIN anticipos_proveedores AS atp ON atp.Atp_Cod = det_ant_ccpp.Atp_Cod
                            INNER JOIN proveedore AS prv ON prv.Prv_Cod = atp.Prv_Cod
                            LEFT JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
                            INNER JOIN comprobantes AS cprbnt ON cprbnt.Com_Cod = det_ant_ccpp.Com_Cod
                            INNER JOIN tipo_asien AS tpAst ON tpAst.Tia_Cod = cprbnt.Tia_Cod
                        WHERE det_ant_ccpp.Atp_Cod = '$Par_Sql[Atp_Cod]' AND (cprbnt.Com_Fec >= '$Par_Sql[Fec_IniPr]') AND (cprbnt.Com_Fec <= '$Par_Sql[Fec_FinPr]')";
            return $sql;
            break;
        // Nueva - Reporte de Anticipo Clientes
        case 53:
            $sql = "SELECT
                        anticipos_clientes.*,
                        CONCAT (tpAst.Tia_Abr, '-', MONTH (cprbnt.Com_Fec), '-', cprbnt.Com_Num) AS codigoCompra,
                        cli.*, prs.*,
                        CONCAT (prs.Prs_Nom, ' ', prs.Prs_Ape) AS nombre,
                        prs.Prs_Ced AS cedProv,
                        cprbnt.*, tpAst.*,
                        CAST((Ant_Val) AS DECIMAL(20, 2)) AS sumaAntVal,
                        CAST(IF (Ddc_Val IS NULL, 0, SUM(Ddc_Val)) AS DECIMAL(20, 2)) AS sumaDdcVal,
                        CAST((Ant_Val) AS DECIMAL(20, 2)) - IF (Ddc_Val IS NULL, 0, SUM(Ddc_Val)) AS tot_anti,
                        pagosAntCli.Pagos, pagosAntCli.Pac_Cod,
                        CONCAT (prsn.Prs_Nom, ' ', prsn.Prs_Ape) AS usuario,
                        usr.Usu_Cod,
                        prsn.Prs_Nom, prsn.Prs_Ape,
                        daCCCC.Ddc_Cod, daCCCC.Ddc_Val
                    FROM anticipos_clientes
                        INNER JOIN cliente AS cli ON cli.Cli_Cod = anticipos_clientes.Cli_Cod
                        LEFT JOIN persona AS prs ON prs.Prs_Cod = cli.Prs_Cod
                        INNER JOIN comprobantes AS cprbnt ON cprbnt.Com_Cod = anticipos_clientes.Com_Cod
                        INNER JOIN tipo_asien AS tpAst ON tpAst.Tia_Cod = cprbnt.Tia_Cod
                        LEFT JOIN nego_documentos ON nego_documentos.Cod_Doc = anticipos_clientes.Ant_Cod
                        LEFT JOIN nego_camaron AS negocam ON nego_documentos.Cod_Neg = negocam.Cod_Neg
                        LEFT JOIN (
                            SELECT
                                pac.*, tpsPg.Pag_Abr, tpsPg.Pag_Des,
                                CAST(IF(IF (Pac_Val IS NULL, 0, Pac_Val) IS NULL,0, SUM(IF (Pac_Val IS NULL, 0, Pac_Val))) AS DECIMAL(20, 2)) AS Pagos
                            FROM pag_anticipo_cli AS pac
                                LEFT JOIN tipos_pago AS tpsPg ON tpsPg.Pag_Cod = pac.Pag_Cod
                            WHERE (pac.Ant_Cod = Ant_Cod)
                            GROUP BY pac.Ant_Cod
                            ) AS pagosAntCli ON pagosAntCli.Ant_Cod = anticipos_clientes.Ant_Cod
                        INNER JOIN usuarios AS usr ON usr.Usu_Cod = cprbnt.Usu_Cod
                        INNER JOIN persona AS prsn ON prsn.Prs_Cod = usr.Prs_Cod
                        LEFT JOIN det_ant_cccc AS daCCCC ON daCCCC.Ant_Cod = anticipos_clientes.Ant_Cod
                        WHERE
                            cli.Emp_Cod = $Par_Sql[0] $Par_Sql[1] AND
                            Ant_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]' AND
                            anticipos_clientes.Ant_Est IN ('A', 'U', 'C') 
                        GROUP BY anticipos_clientes.Ant_Cod
                        ORDER BY FIELD (Ant_Est, 'A', 'U', 'C'), Ant_Fec ASC";
            return $sql;
            break;

        case 54:
            $sql = "SELECT  SUM(t.Asi_Val) AS Total_Asiento, SUM(t.Abono) AS Total_Abono, SUM(t.Saldo) AS Total_Saldo
                    FROM (SELECT asientos.Asi_Val,
                    COALESCE(SUM(CASE WHEN comp2.Com_Est='A' THEN ROUND(Cpc_Val,2) ELSE 0 END),0) AS Abono,
                    asientos.Asi_Val - COALESCE(SUM(CASE WHEN comp2.Com_Est='A' THEN ROUND(Cpc_Val,2) ELSE 0 END),0) AS Saldo
                    FROM cliente
                    INNER JOIN ventas ON cliente.Cli_Cod = ventas.Cli_Cod
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Doc = ventas.Vet_Cod
                    INNER JOIN nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg 
                    INNER JOIN caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod = puntos_imp.Pun_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
                    INNER JOIN ccpp_cobrar ON ventas.Vet_Cod = ccpp_cobrar.Vet_Cod
                    INNER JOIN comprobantes ON ccpp_cobrar.Com_Cod = comprobantes.Com_Cod
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod = comprobantes.Tia_Cod
                    INNER JOIN asientos ON comprobantes.Com_Cod = asientos.Com_Cod
                    INNER JOIN perio_cont ON comprobantes.Pec_Cod = perio_cont.Pec_Cod
                    INNER JOIN ccpp_cliente ON asientos.Pld_Cod = ccpp_cliente.Pld_Cod
                    INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
                    LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod = det_ccpp_c.Cpc_Cod
                    LEFT JOIN comprobantes AS comp2 ON comp2.Com_Cod = det_ccpp_c.Com_Cod,
                    persona
                    WHERE cliente.Prs_Cod = persona.Prs_Cod
                    AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod
                    AND asientos.Com_Cod = comprobantes.Com_Cod
                    AND asientos.Asi_Deh= 'D'
                    AND (ventas.vet_Est='A' OR ventas.vet_Est='E')
                    AND (comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')
                    AND sucursal.Emp_Cod IN ($Par_Sql[0])
                    AND nego_camaron.Cod_Neg=$Par_Sql[1]
                    AND nego_documentos.Abr_Doc='VNT'
                    AND Tip_Prod='$Par_Sql[2]'
                    GROUP BY ventas.Vet_Cod) t";
            return $sql;
            break;

        case 488:
            $sql = "SELECT ven.*, nego_documentos.Tip_Prod, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, persona.Prs_Ced, cmp.Com_Fec as Caj_Fec, 
            CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(ven.Vet_Num,9,'0')AS char)) AS Vet_Num, 
            SUM((ventas_det.Vet_Imp * (COALESCE(iva.Iva_Por, 0) / 100))) AS Iva, tipo_compr.Tic_Des,

            SUM(
                CASE 
                    WHEN ven.Tic_Cod = '5' THEN ventas_det.Vet_Can * ventas_det.Vet_Pru
                    ELSE -1 * (ventas_det.Vet_Can * ventas_det.Vet_Pru)
                END
            ) AS Abono,
            
            SUM(
                CASE 
                    WHEN ven.Tic_Cod = '5' THEN 
                        (ventas_det.Vet_Can * ventas_det.Vet_Pru) + (ventas_det.Vet_Can * ventas_det.Vet_Pru) * (COALESCE(iva.Iva_Por, 0) / 100)
                    ELSE 
                        -1 * (ventas_det.Vet_Can * ventas_det.Vet_Pru + (ventas_det.Vet_Can * ventas_det.Vet_Pru) * (COALESCE(iva.Iva_Por, 0) / 100))
                END
            ) AS Asi_Val

            FROM ventas  
            INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
            INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
            INNER JOIN comprobantes as cmp ON cmp.Com_Cod = det_ccpp_c.Com_Cod
            INNER JOIN ventas_compr as vncom  ON vncom.Com_Cod = cmp.Com_Cod
            INNER JOIN ventas as ven ON ven.Vet_Cod = vncom.Vet_Cod
            INNER JOIN caja_aper ON (ven.Caj_Cod = caja_aper.Caj_Cod)
            INNER JOIN puntos_imp ON caja_aper.Pun_Cod = puntos_imp.Pun_Cod
            INNER JOIN autorizaci ON autorizaci.Aut_Cod = ven.Aut_Cod
            INNER JOIN sucursal ON sucursal.Suc_Cod = puntos_imp.Suc_Cod
            INNER JOIN cliente ON cliente.Cli_Cod = ven.Cli_Cod
            INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
            INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = ven.Tic_Cod
            INNER JOIN ventas_det ON ventas_det.Vet_Cod = ven.Vet_Cod 
            INNER JOIN iva ON iva.Iva_Cod = ventas_det.Iva_Cod 
            INNER JOIN nego_documentos ON ventas.Vet_Cod = nego_documentos.Cod_Doc
            INNER JOIN nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg
            WHERE cliente.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg = $Par_Sql[1] AND nego_documentos.Abr_Doc = 'VNT' 
                AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '') AND (ven.Tic_Cod = '4' OR ven.Tic_Cod = '5')
            GROUP BY ventas_det.Vet_Cod";
            return $sql;
            break;

        case 4888: //NOTAS DE CREDITO  debaen ser tanto en larva como balanceado
            if (!empty($Par_Sql[2])) { //L=Larva; B=Balanceado
                $Par_Sql[2] = " AND nego_documentos.Tip_Prod='$Par_Sql[2]'";
            }
            $sql = "SELECT 
                SUM(
                    CASE
                        WHEN ven.Tic_Cod = '5' THEN (ventas_det.Vet_Can * ventas_det.Vet_Pru)
                        ELSE -1 * (ventas_det.Vet_Can * ventas_det.Vet_Pru)
                    END
                ) AS subtotal,
                SUM(
                    CASE 
                        WHEN ven.Tic_Cod = '5' THEN (ventas_det.Vet_Imp * (COALESCE(iva.Iva_Por, 0) / 100))
                        ELSE -1 * (ventas_det.Vet_Imp * (COALESCE(iva.Iva_Por, 0) / 100))
                    END
                ) AS Iva,
                SUM(
                    CASE 
                        WHEN ven.Tic_Cod = '5' THEN ((ventas_det.Vet_Can * ventas_det.Vet_Pru) + (ventas_det.Vet_Can * ventas_det.Vet_Pru) * (COALESCE(iva.Iva_Por, 0) / 100))
                        ELSE -1 * ((ventas_det.Vet_Can * ventas_det.Vet_Pru) + (ventas_det.Vet_Can * ventas_det.Vet_Pru) * (COALESCE(iva.Iva_Por, 0) / 100))
                    END
                ) AS total
                FROM ventas  
                INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                INNER JOIN comprobantes as cmp ON cmp.Com_Cod = det_ccpp_c.Com_Cod
                INNER JOIN ventas_compr as vncom  ON vncom.Com_Cod = cmp.Com_Cod
                INNER JOIN ventas as ven ON ven.Vet_Cod = vncom.Vet_Cod
                INNER JOIN ventas_det ON ventas_det.Vet_Cod = ven.Vet_Cod 
                INNER JOIN iva ON iva.Iva_Cod = ventas_det.Iva_Cod 
                INNER JOIN cliente ON cliente.Cli_Cod = ven.Cli_Cod
                INNER JOIN nego_documentos ON ventas.Vet_Cod = nego_documentos.Cod_Doc
                INNER JOIN nego_camaron ON nego_camaron.Cod_Neg = nego_documentos.Cod_Neg
                WHERE cliente.Emp_Cod IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg = $Par_Sql[1] AND 
                    nego_documentos.Abr_Doc = 'VNT' AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '') AND cmp.Com_Est='A' AND
                    ven.Tic_Cod = '$Par_Sql[2]' ";
                    // Tic_Cod=4 notas de credito y Tic_Cod=5 notas de debito
            return $sql;
            break;

        case 55:
              $sql = " SELECT det_ccpp_c.*, CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, persona.Prs_Ced , ventas.Vet_Cod,
                    CONCAT( Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, Pag_Des ,
                    /* Notas de débito (Tic_Cod=5) deben mostrarse positivas */
                    CASE WHEN ventas.Tic_Cod='5' THEN ABS(det_ccpp_c.Cpc_Val) ELSE (-1 * det_ccpp_c.Cpc_Val) END AS Asi_Val , tipos_pago.Pag_Des AS Tic_Des , Com_Fec AS Caj_Fec, nego_documentos.Tip_Prod
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN ventas ON ventas.Vet_Cod = nego_documentos.Cod_Doc 
                    INNER JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                    INNER JOIN persona ON persona.Prs_Cod = cliente.Prs_Cod
                    INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                    INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                    INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                    INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
                    INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_c.Com_Cod
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = det_ccpp_c.Pag_Cod
                    WHERE cliente.Emp_Cod  IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1] AND nego_documentos.Abr_Doc='VNT' 
                    AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '') AND  ( tipos_pago.Pag_Cod=50 /*or tipos_pago.Pag_Cod=5*/)
                    AND Cpc_Est='A' AND Vet_Est='A' AND Com_Est='A'";
            return $sql;
            break;

        case 56:
            if (!empty($Par_Sql[2])) {
                $Par_Sql[2] = " AND nego_documentos.Tip_Prod='$Par_Sql[2]'";
            }
            $sql = " SELECT det_ccpp_c.*,  SUM(-1 * det_ccpp_c.Cpc_Val ) AS Asi_Val
                    FROM nego_camaron
                    INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
                    INNER JOIN ventas ON ventas.Vet_Cod = nego_documentos.Cod_Doc 
                    INNER JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                    INNER JOIN ccpp_cobrar ON ccpp_cobrar.Vet_Cod = ventas.Vet_Cod
                    INNER JOIN det_ccpp_c ON det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_c.Com_Cod
                    INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = det_ccpp_c.Pag_Cod
                    WHERE cliente.Emp_Cod  IN ($Par_Sql[0]) AND nego_camaron.Cod_Neg=$Par_Sql[1] AND nego_documentos.Abr_Doc='VNT' 
                   -- AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '') AND tipos_pago.Pag_Cod=50
                    AND (nego_documentos.Tip_Prod IS NOT NULL AND nego_documentos.Tip_Prod <> '') AND ( tipos_pago.Pag_Cod=50 /*or tipos_pago.Pag_Cod=5*/)
                    AND Cpc_Est='A' AND Vet_Est='A' AND Com_Est='A' $Par_Sql[2]";
            return $sql;

        case 57:
            $sql = " SELECT grupo_clientes.Emp_Cod ,det_grup_empresas.* FROM grupo_clientes      
            INNER JOIN det_grup_empresas  ON grupo_clientes.Cod_Grup = det_grup_empresas.Cod_Group
            WHERE grupo_clientes.Emp_Cod =  $_SESSION[Ses_Emp_Cod]  AND Grup_Est='A'";
            return $sql;

        case 58:
            $sql = "SELECT persona.*, productor_camaron.Prod_Cod ,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as productor, persona.Prs_Ced   
                            FROM nego_camaron  
                            INNER JOIN productor_camaron ON productor_camaron.Prod_Cod = nego_camaron.Prod_Cod
                            INNER JOIN proveedore ON productor_camaron.Prv_Cod = proveedore.Prv_Cod
                            INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
                            WHERE nego_camaron.Emp_Cod = $Par_Sql[0] AND Cod_Neg='$Par_Sql[1]' ";
            return $sql;


        case 59:
         
            $sql = "SELECT  ROUND(SUM(det_ccpp_p.Pag_Val), 2) AS total_pagado
            FROM nego_camaron
            INNER JOIN nego_documentos ON nego_documentos.Cod_Neg = nego_camaron.Cod_Neg
            INNER JOIN compras ON compras.Cop_Cod = nego_documentos.Cod_Doc 
            INNER JOIN proveedore ON proveedore.Prv_Cod = compras.Prv_Cod
            INNER JOIN persona ON persona.Prs_Cod = proveedore.Prs_Cod
            INNER JOIN ccpp_pagar ON ccpp_pagar.Cop_Cod = compras.Cop_Cod
            INNER JOIN det_ccpp_p ON det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod
            LEFT JOIN comprobantes ON comprobantes.Com_Cod = det_ccpp_p.Com_Cod
            INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = det_ccpp_p.Pag_Cod
            WHERE proveedore.Emp_Cod IN ($Par_Sql[0])
            AND nego_camaron.Cod_Neg = $Par_Sql[1]  
            AND nego_documentos.Abr_Doc = 'CMP'
            AND det_ccpp_p.Pag_Est = 'A'
            AND comprobantes.Com_Est = 'A'
            AND tipos_pago.Pag_Cod NOT IN (5, 50, 10, 13)";//CODIGO DE RETENCION, NOTA CREDITO, DEBITO Y CRUCE DE CUENTAS
            return $sql;
    }
}
