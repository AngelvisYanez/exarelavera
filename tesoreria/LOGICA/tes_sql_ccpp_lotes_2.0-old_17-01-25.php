<?php

/**
 * Retorna consulta sql a ejecutarse
 *
 * @author Erick Cordova
 * @version 1.0
 * Fecha de actualización:	2012-11-27
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 *
 * @package tesoreria.LOGICA
 */

function sentencias_ccpp($id, $Par_Sql)
{
	switch ($id) {
			//sentencia para obtener todos los proveedores registrados de la empresa
		case 1:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchPrv]%')";
			} else {
				$search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchPrv]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(proveedore.Prv_Cod) AS total" : " proveedore.Prv_Cod,	persona.Prs_Cod, persona.Prs_Ced, IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre, persona.Prs_Dir";
			$ordenar = empty($Par_Sql['limits']) ? "" : "ORDER by nombre";
			$sql = "SELECT $campos
							FROM persona,proveedore
							WHERE  $search AND proveedore.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
							AND proveedore.Prs_Cod = persona.Prs_Cod
							AND proveedore.Prv_Est = 'A'
							$ordenar
							$Par_Sql[limits];";
			return $sql;
			//sentencia para obtener todos los abonos de proveedores registrados de la empresa
		case 2:
			//fecha actual
			$hoy = date("Y-m-d");
			$campos_sql = "det_plan.Pld_Cod,
									Pld_Cdc,Pld_Des,tipo_compr.Tic_Des,
									Prs_Nom,Prs_Ape,
									proveedore.Prv_Cod,
									CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor,
									persona.Prs_Ape,
									persona.Prs_Nom,
									compras.Cop_Cod,
									ccpp_pagar.Cpp_Cod,
									compras.Cop_Fec,
									compras.Cop_Num,
									compras.Cop_Obs,
									ccpp_pagar.Cpp_Ven,
									ccpp_pagar.Com_Cod,
									asientos.Asi_Cod,
									asientos.Asi_Val,
									asientos.Pld_Cod,
									Pld_Cdc,
									Pld_Des,Cop_Obs,
									CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono";

			if ($Par_Sql['por_peri'] == "s") {
				$concat = "AND (compras.Cop_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')";
			} else {
				if ($Par_Sql['sel_ven'] == 1) {
					$concat = "";
				}
				if ($Par_Sql['sel_ven'] == 2) {
					$Fec_Fin = fechas_futuras($hoy, 30);
					$concat = " AND Cpp_Ven BETWEEN '" . $hoy . "' AND '" . $Fec_Fin . "'";
				}
				if ($Par_Sql['sel_ven'] == 3) {
					$val = 60;
					$Fec_Fin = fechas_futuras($hoy, 60);
					$concat = " AND Cpp_Ven BETWEEN '" . $hoy . "' AND '" . $Fec_Fin . "'";
				}
				if ($Par_Sql['sel_ven'] == 4) {
					$Fec_Fin = fechas_futuras($hoy, 90);
					$concat = " AND Cpp_Ven > '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 5) {
					$Fec_Fin = fechas_futuras($hoy, -30);
					$concat = " AND Cpp_Ven BETWEEN '" . $Fec_Fin . "' AND '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 6) {
					$Fec_Fin = fechas_futuras($hoy, -60);
					$concat = " AND Cpp_Ven BETWEEN '" . $Fec_Fin . "' AND '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 7) {
					$Fec_Fin = fechas_futuras($hoy, -90);
					$concat = " AND Cpp_Ven < '" . $hoy . "'";
				}

				if ($Par_Sql['order'] != '') {
					$order = " ORDER BY " .$Par_Sql['order'];
				}
				

			}

			

			$campos = empty($Par_Sql['limits']) ? " COUNT(compras.Cop_Cod) AS total" : " " . $campos_sql;
			$ordenar = empty($Par_Sql['limits']) ? "" : "GROUP BY compras.Cop_Cod ORDER by ccpp_pagar.Cpp_Ven";
			$contar_regs = "(
				SELECT COUNT(compras.Cop_Cod) AS tot
								FROM proveedore
									INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
									INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
									INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
									INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
									INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
									INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
									INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
									INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
									LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
									LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
								WHERE
									proveedore.Prs_Cod = persona.Prs_Cod
									AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
									AND asientos.Com_Cod= comprobantes.Com_Cod
									AND asientos.Asi_Deh= 'H'
									AND (compras.Cop_Est='A' OR compras.Cop_Est='E')
									AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
									AND proveedore.Prv_Cod = $Par_Sql[Prv_Cod]
									$concat
									AND Emp_Cod=$_SESSION[Ses_Emp_Cod]
								GROUP BY compras.Cop_Cod  ORDER by ccpp_pagar.Cpp_Ven   
				)";
				//ORDER by ccpp_pagar.Cpp_Ven
			// if(empty($Par_Sql['limits'])){
			// 	$sql = "SELECT COUNT(*) as total FROM $contar_regs as regscount";
			// }else{
			// 	//
			// }
			$sql = "SELECT $campos_sql
							FROM proveedore
								INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
								INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
								INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
								INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
								INNER JOIN tipo_compr ON tipo_compr.Tic_Cod= compras.Tic_Cod
								INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
								INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
								INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
								INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
								LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
								LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
							WHERE
								proveedore.Prs_Cod = persona.Prs_Cod
								AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
								AND asientos.Com_Cod= comprobantes.Com_Cod
								AND asientos.Asi_Deh= 'H'
								AND (compras.Cop_Est='A' OR compras.Cop_Est='E')
								AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
								AND proveedore.Prv_Cod = $Par_Sql[Prv_Cod]
								$concat
								AND Emp_Cod=$_SESSION[Ses_Emp_Cod]
							GROUP BY compras.Cop_Cod /*ORDER by ccpp_pagar.Cpp_Ven*/  $order   ;";
			return $sql;
			//todos los periodos contables
		case 3:
			$sql = "SELECT plan_cuenta.Pla_Cod, perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(perio_cont.Pec_Fei) as priodo_m
						FROM plan_cuenta, perio_cont
						WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
						ORDER BY Year(perio_cont.Pec_Fei) DESC";
			return $sql;
			//retorna tipos de asiento
		case 4:
			if ($Par_Sql[0] == "") $Par_Sql[0] = " WHERE Tia_Tip='B' AND Tia_Est='A' ";
			else $Par_Sql[0] = " WHERE  Tia_Est='A' AND(Tia_Ini='E' OR Tia_Ini='D' )";
			$sql = "SELECT Tia_Cod,Tia_Abr, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
			return $sql;
		case 5:
			$sql = "SELECT * from tipos_pago WHERE For_Cod='1' AND Pag_Abr != 'RET';";
			return $sql;
		case 6:
			$sql = "SELECT * from bancos;";
			return $sql;
			//plan de cuentas inicial para pago a proveedores
		case 7:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
						from plan_cuenta, det_plan, ccpp_prove
						where
							det_plan.Pla_Cod=plan_cuenta.Pla_Cod and
							plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and
							ccpp_prove.Pld_Cod = det_plan.Pld_Cod;";
			return $sql;
			//centencia para obtener la cuenta para pagos en efectivo
		case 8:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
			FROM det_plan, banco
			WHERE
				det_plan.Pld_Cod=banco.Pld_Cod AND
				banco.Ban_Tip='C' AND
				det_plan.Pld_Est='A' AND
				det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) 
				from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' AND plan_cuenta.Pla_Est='A'));";
			//echo $sql;
			return $sql;
			//seleccionar plan de cuentas y No. de cuenta del banco para pago a proveedores
		case 9:
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
							from det_plan, banco
							where
								det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' AND plan_cuenta.Pla_Est='A')
								and banco.Ban_Tip = '$Par_Sql[Ban_Tip]'
								and banco.Pld_Cod=det_plan.Pld_Cod;";
			return $sql;
			//insertar un registro en la tabla cheques
		case 10:
			$sql = "SELECT Che_Num FROM cheques WHERE Ban_Cod = '$Par_Sql[0]';";
			// echo $sql;
			return $sql;
			//seleccionar plan de cuentas por defecto para pagos en Efectivo
		case 11:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
      		FROM det_plan, banco
      		WHERE  det_plan.Pld_Cod=banco.Pld_Cod AND banco.Ban_Tip='C' AND det_plan.Pld_Est='A' AND
      		det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			return $sql;
			//sentencia para obtener los planes de cuenta de tipo de talle
		case 12:
			if ($Par_Sql['op_opciones'] == "d") {
				$search = "(det_plan.Pld_Des LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(det_plan.Pld_Cod) AS total" : " * ";
			$sql = "SELECT $campos
			FROM det_plan
			WHERE  $search AND
			det_plan.Pld_Tip = 'D' AND
			det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
			ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -20) $Par_Sql[limits];";
			return $sql;
			//insertar un registro en la tabla comprobantes
		// case 13:
		// 	$sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Gen,Com_Est,Usu_Cod)
		// 					VALUES($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], $Par_Sql[Cli_Cod], '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', '$Par_Sql[Com_Con]', 'E', '$Par_Sql[Com_Val]',
		// 				 '$Par_Sql[Com_Obs]', null, $Par_Sql[Tia_Cod], 'A', 'A', '$_SESSION[Ses_Usu_Cod]');";
		// 	// echo $sql;
		// 	return $sql;
		// 	//insertar un registro en la tabla asientos

		case 13:
			ChromePhp::log("SQL PAGO: " . $Par_Sql["Num_Doc"]);
		/*	$sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,
			Tia_Cod,Com_Gen,Com_Est,Usu_Cod, Num_Doc)
						VALUES($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], 
						$Par_Sql[Cli_Cod], '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', 
						'$Par_Sql[Com_Con]', 'E', '$Par_Sql[Com_Val]',
						'$Par_Sql[Com_Obs]', null, $Par_Sql[Tia_Cod], 'A', 'A',
						'$_SESSION[Ses_Usu_Cod]' , " . !empty($Par_Sql['Num_Doc'])  ?  $Par_Sql['Num_Doc'] : NULL . "    );";*/


			$sql = "INSERT INTO comprobantes (
					Pec_Cod, Prv_Cod, Cli_Cod, Com_Num, Com_Fec, Com_Con, Com_Tip, 
					Com_Val, Com_Obs, Com_Tipo, Tia_Cod, Com_Gen, Com_Est, Usu_Cod, Num_Doc
					) VALUES (
					" . $Par_Sql['Pec_Cod'] . ", " . $Par_Sql['Prv_Cod'] . ", " . $Par_Sql['Cli_Cod'] . ", 
					'" . $Par_Sql['Com_Num'] . "', '" . $Par_Sql['Com_Fec'] . "', '" . $Par_Sql['Com_Con'] . "', 
					'E', '" . $Par_Sql['Com_Val'] . "', '" . $Par_Sql['Com_Obs'] . "', NULL, 
					" . $Par_Sql['Tia_Cod'] . ", 'A', 'A', '" . $_SESSION['Ses_Usu_Cod'] . "', 
					" . (!empty($Par_Sql['Num_Doc']) ? $Par_Sql['Num_Doc'] : "NULL") . ");";


			// echo $sql;
			//	ChromePhp::log("SQL:"+$sql);
			return $sql;
			//insertar un registro en la tabla asientos

			
		case 14:
			$sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
							VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', '$Par_Sql[Asi_Con]', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";
			// echo $sql;
			return $sql;
		case 15:
			$sql = "INSERT INTO det_ccpp_p (Cpp_Cod, Pag_Cod, Com_Cod, Pag_Fec, Pag_Val, Pag_Est, Pag_Obs, Asi_Cod)
						VALUES($Par_Sql[Cpp_Cod], $Par_Sql[Pag_Cod], $Par_Sql[Com_Cod], '$Par_Sql[Pag_Fec]', '$Par_Sql[Pag_Val]', 'A', '$Par_Sql[Pag_Obs]', '$Par_Sql[Asi_Cod]');";
			// echo $sql;
			return $sql;
		case 16:
			$sql = "INSERT INTO pagos_ccpp (Pcp_Val, Asi_Cod, Pag_Cod)
						VALUES('$Par_Sql[Pcp_Val]', '$Par_Sql[Asi_Cod]', '$Par_Sql[Pag_Cod]');";
			// echo $sql;
			return $sql;
		case 17:
			$sql = "INSERT INTO cheques (Che_Cod, Prv_Cod, Ban_Cod, Asi_Cod, Che_Num, Che_Fec, Che_Cob, Che_Val, Che_Obs, Che_Est, Che_Ben)
						VALUES($Par_Sql[Che_Cod], $Par_Sql[Prv_Cod], $Par_Sql[Ban_Cod], $Par_Sql[Asi_Cod], $Par_Sql[Che_Num], '$Par_Sql[Che_Fec]', null, '$Par_Sql[Che_Val]', '$Par_Sql[Che_Obs]', 'A', '$Par_Sql[Che_Ben]');";
			// echo $sql;
			return $sql;
		case 18:
			$sql = "SELECT
					sum(pga.Pap_Val) as tot_anti
					from pago_anticipo_proveedores as pga
					inner join anticipos_proveedores as ant2 on (ant2.Atp_Cod=pga.Atp_Cod)
					inner join asientos as asi on (asi.Asi_Cod=pga.Asi_Cod)
					where ant2.Prv_Cod=$Par_Sql[Prv_Cod]
						and (ant2.Atp_Est ='A' OR ant2.Atp_Est ='U')
						and pga.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = asi.Asi_Cod and cheques.Che_Est = 'P')";
			//echo $sql;
			return $sql;
			// total de detallaes de anticipo
		case 19:
			$sql = "SELECT
							if(SUM(det_ant_ccpp.Dac_Val) is null,0,SUM(det_ant_ccpp.Dac_Val)) as tot_dac
						from det_ant_ccpp, anticipos_proveedores
						where
							(anticipos_proveedores.Atp_Est ='A' or anticipos_proveedores.Atp_Est ='U') and
							det_ant_ccpp.Atp_Cod = anticipos_proveedores.Atp_Cod and
							anticipos_proveedores.Prv_Cod = $Par_Sql[Prv_Cod];";
			//echo $sql;
			return $sql;
			//para seleccionar el plande cuentas correspondiente a los pagos de anticipos a proveedores
		case 20:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
	          FROM det_plan, tipo_param, plan_param
	          WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
						AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						AND tipo_param.Tpa_Abr='ANP'
						AND det_plan.Pld_Est='A' AND
	          det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			return $sql;
		case 21:
			$sql = "INSERT INTO det_ant_ccpp	(Dac_Val, Com_Cod, Atp_Cod,  Pap_Cod )
						VALUES('$Par_Sql[Dac_Val]', $Par_Sql[Com_Cod], $Par_Sql[Atp_Cod], $Par_Sql[Pap_Cod] );";
			// echo $sql;
			return $sql;

		case 22:
			$sql = "SELECT
						if(sum(pga.Pap_Val) is null,0,sum(pga.Pap_Val)) as Atp_Val,ant.Atp_Cod, ant.Atp_Val as ttfinal
					FROM anticipos_proveedores as ant
						inner join comprobantes as com on (com.Com_Cod = ant.Com_Cod)
						inner join tipo_asien as tas on (tas.Tia_Cod = com.Tia_Cod)
						left join pago_anticipo_proveedores as pga on (pga.Atp_Cod = ant.Atp_Cod and pga.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = pga.Asi_Cod and cheques.Che_Est = 'P'))
					WHERE
						pga.Pap_Est != 'C' and
						(ant.Atp_Est ='A' OR ant.Atp_Est ='U') and
						ant.Prv_Cod='$Par_Sql[Prv_Cod]'
					GROUP BY ant.Atp_Cod
					order by ant.Atp_Cod;";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 23:
			$sql = "UPDATE anticipos_proveedores
						SET Atp_Est='$Par_Sql[Atp_Est]'
						WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;
			// total de detallaes de anticipo DE UN DETERMINADO ANTICIPO
		case 24:
			$sql = "SELECT
							IF(SUM(det_ant_ccpp.Dac_Val) is null,0,SUM(det_ant_ccpp.Dac_Val)) AS tot_dac
						from det_ant_ccpp, anticipos_proveedores
						where
							anticipos_proveedores.Atp_Est ='U' and
							det_ant_ccpp.Atp_Cod = anticipos_proveedores.Atp_Cod and
							det_ant_ccpp.Atp_Cod = $Par_Sql[Atp_Cod] and
							anticipos_proveedores.Prv_Cod = $Par_Sql[Prv_Cod];";
			// echo $sql;
			return $sql;
			//obtenemos el codigo de cliente de un proveedor registrado como cliente y como proveedor
		case 25:
			$sql = "SELECT
							proveedore.Prv_Com,
							proveedore.Prv_Cod,
							cliente.Cli_Cod
						from proveedore,cliente
						where
							proveedore.Prv_Cod = $Par_Sql[Prv_Cod] and
							proveedore.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and
							cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and
							cliente.Prs_Cod = proveedore.Prs_Cod;";
			// echo $sql;
			return $sql;
			//obtenemos el total de la deuda de un clientes
		case 26:
			$sql = "SELECT
							cliente.Cli_Cod,
							CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor,
							persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod,
							ccpp_cobrar.Cpc_Cod,
							caja_aper.Caj_Fec,
							CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
							ccpp_cobrar.Cpc_Ven,
							ccpp_cobrar.Com_Cod,
							asientos.Asi_Cod,
							asientos.Pld_Cod,
							Pld_Cdc,Pld_Des,
							CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1, CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
							IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpc_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
							asientos.Asi_Val,
						  IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono,
							(asientos.Asi_Val)-(IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))) as saldo
						FROM cliente
						INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
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
						WHERE
							cliente.Prs_Cod = persona.Prs_Cod AND
							comprobantes.Com_Cod = ccpp_cobrar.Com_Cod AND
							asientos.Com_Cod = comprobantes.Com_Cod AND
							asientos.Asi_Deh= 'D' AND
							(ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
							(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
						    cliente.Cli_Cod='$Par_Sql[Cli_Cod]' AND
							sucursal.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
						GROUP BY ventas.Vet_Cod
						ORDER BY Vet_Num;";
			// echo $sql;
			return $sql;
			//plan de cuentas inicial para pago a proveedores
		case 27:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
						from plan_cuenta, det_plan, ccpp_cliente
						where
							det_plan.Pla_Cod=plan_cuenta.Pla_Cod and
							plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and
							ccpp_cliente.Pld_Cod = det_plan.Pld_Cod;";
			return $sql;
		case 28:
			$sql = "INSERT INTO det_ccpp_c (Cpc_Cod, Com_Cod, Pag_Cod, Cpc_Fec, Cpc_Val, Cpc_Obs)
						VALUES($Par_Sql[Cpc_Cod], $Par_Sql[Com_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Cpc_Fec]', $Par_Sql[Cpc_Val], '$Par_Sql[Cpc_Obs]');";
			// echo $sql;
			return $sql;
		case 29:
			$sql = "SELECT
							@rownum:=@rownum+1 as indx,
							CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS codigo_compro,
							if(
								comprobantes.Com_Cod = (select det_ccpp_p.Com_Cod from det_ccpp_p, asientos, cheques
								where det_ccpp_p.Cpp_Cod=$Par_Sql[0] and	cheques.Che_Est = 'P' and cheques.Asi_Cod = asientos.Asi_Cod and asientos.Asi_Cod = det_ccpp_p.Asi_Cod)
								or det_ccpp_p.Pag_Val <0
								or comprobantes.Com_Cod = (select compr_auto.Com_Cod from compr_auto where compr_auto.Com_Cod=det_ccpp_p.Com_Cod),
								'n','s'
							) as reg_editable,
							if(comprobantes.Com_Cod = (select compr_auto.Com_Cod from compr_auto where compr_auto.Com_Cod=det_ccpp_p.Com_Cod),'n','s') as ndc,
							det_ccpp_p.Cpp_Cod,
							comprobantes.Com_Cod,
							comprobantes.Tia_Cod,
							comprobantes.Com_Fec,
							comprobantes.Com_Num,
							comprobantes.Pec_Cod,
							comprobantes.Com_Val,
							det_ccpp_p.Pag_Val,
							det_ccpp_p.Pag_Obs,
							tipos_pago.Pag_Des,
							tipos_pago.Pag_Abr,
							comprobantes.Com_Con,
							comprobantes.Com_Obs
						from det_ccpp_p,comprobantes, tipo_asien, tipos_pago, (SELECT @rownum:=0) r
						where
							det_ccpp_p.Cpp_Cod=$Par_Sql[0] and
							tipo_asien.Tia_Cod = comprobantes.Tia_Cod and
							tipos_pago.Pag_Cod = det_ccpp_p.Pag_Cod and
							comprobantes.Com_Cod = det_ccpp_p.Com_Cod and
							comprobantes.Com_Est = 'A'
						order by codigo_compro;";
			// echo $sql;
			return $sql;
		case 30:
			$sql = "SELECT
							det_plan.Pld_Cdc,
							det_plan.Pld_Des,
							asientos.Asi_Deh,
							asientos.Asi_Val,
							asientos.Asi_Con,
							asientos.Asi_Glo
						from asientos, det_plan
						where
							asientos.Com_Cod = $Par_Sql[Com_Cod] and
							det_plan.Pld_Cod = asientos.Pld_Cod;";
			// echo $sql;
			return $sql;
		case 31:
			$sql = "SELECT * from asientos, cheques
						where
							asientos.Com_Cod = $Par_Sql[Com_Cod] and
							cheques.Asi_Cod = asientos.Asi_Cod;";
			return $sql;
		case 32:
			$sql = "SELECT
							if(tipos_pago.Pag_Abr ='CHE',(select cheques.Che_Cod from cheques where cheques.Asi_Cod = asientos.Asi_Cod),'') as Che_Cod,
							if(tipos_pago.Pag_Abr ='CHE',(select cheques.Che_Est from cheques where cheques.Asi_Cod = asientos.Asi_Cod),'') as Che_Est,
							asientos.Asi_Cod,
							tipos_pago.Pag_Cod,
							tipos_pago.Pag_Abr,
							tipos_pago.Pag_Des,
							if(tipos_pago.Pag_Abr ='CHE',(select cheques.Ban_Cod from cheques where cheques.Asi_Cod = asientos.Asi_Cod),'') as Ban_Cod,
							if(tipos_pago.Pag_Abr ='CHE',(select cheques.Che_Num from cheques where cheques.Asi_Cod = asientos.Asi_Cod),'') as Che_Num,
							if(tipos_pago.Pag_Abr ='CHE',(select cheques.Che_Fec from cheques where cheques.Asi_Cod = asientos.Asi_Cod),'') as Che_Fec,
							det_plan.Pld_Cod,
							det_plan.Pld_Cdc,
							det_plan.Pld_Des,
							asientos.Asi_Glo,
							asientos.Asi_Con,
							asientos.Asi_Val,
							asientos.Asi_Deh
						from asientos, det_ccpp_p, tipos_pago, det_plan
						where
							det_ccpp_p.Com_Cod = $Par_Sql[Com_Cod] and
							tipos_pago.Pag_Cod = det_ccpp_p.Pag_Cod and
							det_plan.Pld_Cod = asientos.Pld_Cod and
							asientos.Asi_Cod = det_ccpp_p.Asi_Cod
						group by asientos.Asi_Cod;";
			// echo $sql;
			return $sql;
			// sentencia para actualizar comprobante
		case 33:
			$sql = "UPDATE comprobantes
						SET
							Pec_Cod='$Par_Sql[Pec_Cod]',
							Com_Num='$Par_Sql[Com_Num]',
							Com_Fec='$Par_Sql[Com_Fec]',
							Com_Con='$Par_Sql[Com_Con]',
							Com_Val='$Par_Sql[Com_Val]',
							Com_Obs='$Par_Sql[Com_Obs]',
							Tia_Cod='$Par_Sql[Tia_Cod]'
						WHERE Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
			// ***********************************************
		case 34:
			$sql = "UPDATE det_ccpp_p
						SET
							Pag_Fec='$Par_Sql[Pag_Fec]',
							Pag_Obs='$Par_Sql[Pag_Obs]'
						WHERE
							Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
		case 35:
			$sql = "DELETE FROM det_ant_ccpp
						WHERE Com_Cod=$Par_Sql[Com_Cod];";
			// echo $sql;
			return $sql;
		case 36:
			$sql = "DELETE FROM asientos
						WHERE asientos.Com_Cod=$Par_Sql[Com_Cod] and $Par_Sql[Asi_Cod]
						asientos.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = asientos.Asi_Cod and cheques.Che_Est = 'P');";
			// echo $sql;
			return $sql;
		case 37:
			$sql = "SELECT anticipos_proveedores.Atp_Cod
						from det_ant_ccpp, anticipos_proveedores
						where
							anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod and
							det_ant_ccpp.Com_Cod = '$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 38:
			$sql = "UPDATE anticipos_proveedores
						SET Atp_Est='A'
						WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 39:
			$sql = "INSERT INTO pagos_cccc (Pcc_Val, Asi_Cod, Pag_Cod)
						VALUES('$Par_Sql[Pcc_Val]', $Par_Sql[Asi_Cod], $Par_Sql[Pag_Cod]);";
			// echo $sql;
			return $sql;
		case 40:
			$sql = "DELETE FROM det_ccpp_c
						WHERE Com_Cod=$Par_Sql[Com_Cod];";
			// echo $sql;
			return $sql;
		case 41:
			$sql = "UPDATE comprobantes as com
					inner join asientos as asi on (asi.Com_Cod=com.Com_Cod)
					left join cheques as che on (che.Asi_Cod=asi.Asi_Cod)
				 SET com.Com_Est='I', che.Che_Est='I'
				 where
					com.Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
		case 42:
			$sql = "SELECT Cpp_Cod, sum(Pag_Val) as Abono from det_ccpp_p where Com_Cod = $Par_Sql[Com_Cod] group by Cpp_Cod;";
			// echo $sql;
			return $sql;
			//**********************************************
		case 43:
			$sql = "DELETE FROM det_ccpp_p
						WHERE Cpp_Cod=$Par_Sql[Cpp_Cod] AND Com_Cod=$Par_Sql[Com_Cod];";
			return $sql;
		case 44:
			//fecha actual
			$hoy = date("Y-m-d");
			$campos_sql = "det_plan.Pld_Cod,
									Pld_Cdc,Pld_Des,
									Prs_Nom,Prs_Ape,
									Prs_Ced as ruc,
									proveedore.Prv_Cod,
									CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor,
									persona.Prs_Ape,
									persona.Prs_Nom,
									compras.Cop_Cod,
									ccpp_pagar.Cpp_Cod,
									compras.Cop_Fec,
									compras.Cop_Num,
									compras.Cop_Obs,
									ccpp_pagar.Cpp_Ven,
									ccpp_pagar.Com_Cod,
									asientos.Asi_Cod,
									asientos.Asi_Val,
									asientos.Pld_Cod,
									Pld_Cdc,
									Pld_Des,
									CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono";

			$campos = empty($Par_Sql['limits']) ? " COUNT(compras.Cop_Cod) AS total" : " " . $campos_sql;
			$ordenar = empty($Par_Sql['limits']) ? "" : "GROUP BY compras.Cop_Cod ORDER by ccpp_pagar.Cpp_Ven";

			$prv_par = "";
			if ($Par_Sql[Prv_Cod] == "") {
				$prv_par = "";
			} else {
				$prv_par = "AND proveedore.Prv_Cod = $Par_Sql[Prv_Cod]";
			}

			//codigo para filrar por productores
			$filtroProductores = "";
			$filtroWhereProductores = "";
			if ($Par_Sql[sel_tip_prov] == 2) {
				//Para proveedores solamente
				$filtroProductores = " LEFT OUTER JOIN productor_bana ON (proveedore.Prv_Cod = productor_bana.Prv_Cod) ";
				$filtroWhereProductores = " AND productor_bana.Prd_Cod is null ";
			} else if ($Par_Sql[sel_tip_prov] == 3) {
				//Para productores solamente 
				$filtroProductores = " INNER JOIN productor_bana ON (proveedore.Prv_Cod = productor_bana.Prv_Cod) ";
			} else {
				$filtroProductores = "";
				$filtroWhereProductores = "";
			}


			$contar_regs = "(
				SELECT COUNT(compras.Cop_Cod) AS tot
				FROM proveedore
					INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
					$filtroProductores
					INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
					INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
					INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
					INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
					INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
					INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
					INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
					LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
					LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
				WHERE
					proveedore.Prs_Cod = persona.Prs_Cod $prv_par
					$filtroWhereProductores
					AND asientos.Asi_Deh= 'H'
					AND (compras.Cop_Est='A' OR compras.Cop_Est='E')
					AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
					AND (compras.Cop_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
					AND Emp_Cod=$_SESSION[Ses_Emp_Cod]
					GROUP BY compras.Cop_Cod ORDER by ccpp_pagar.Cpp_Ven
				)";

			// if(empty($Par_Sql['limits'])){
			// 	$sql = "SELECT COUNT(*) as total FROM $contar_regs as regscount";
			// }else{
			//
			// }

			$fec_sql = "";
			if ($Par_Sql[sel_ven] != 'ini') {
				$fec_sql = "AND perio_cont.Pec_Cod = $Par_Sql[sel_ven]";
			} else {
				$fec_sql = "AND (compras.Cop_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')";
			}

			$sql = "SELECT
							$campos_sql
							FROM proveedore
								INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
								$filtroProductores
								INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
								INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
								INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
								INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
								INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
								INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
								INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
								LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
								LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
							WHERE
								proveedore.Prs_Cod = persona.Prs_Cod $prv_par
								$filtroWhereProductores
								AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
								AND asientos.Com_Cod= comprobantes.Com_Cod
								AND asientos.Asi_Deh= 'H'
								AND (compras.Cop_Est='A' OR compras.Cop_Est='E')
								AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
								$fec_sql
								AND Emp_Cod=$_SESSION[Ses_Emp_Cod]
								GROUP BY compras.Cop_Cod ORDER by ccpp_pagar.Cpp_Ven $Par_Sql[limits];";
			//ChromePhp::log($sql);
			return $sql;

		case 444:
			if ($Par_Sql['por_peri'] == "s") {
				$concat = " AND (perio_cont.Pec_Cod = $Par_Sql[sel_per]) ";
			} else {
				$concat = "";
			}

			$sql = "SELECT proveedore.Prv_Cod,
          				asientos.Asi_Val AS Total,
						CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Proveedor,   
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
						CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
						,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0))=Asi_Val,'Pagado'
						,IF(DATEDIFF(Cpp_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
						,IF(SUM(IF(comp2.Com_Est='A' && ROUND(ROUND(Pag_Val,2),2)!=Asi_Val,ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0))) AS Abono,
						(asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))) as Saldo
						,IF(DATEDIFF(Cpp_Ven,CURDATE())>=0, (sum(asientos.Asi_Val) - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))), '0.00') AS PorVencer
						,IF(DATEDIFF(CURDATE(),Cpp_Ven)>=$Par_Sql[rango1Ini] && DATEDIFF(CURDATE(),Cpp_Ven)<=$Par_Sql[rango1Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))), '0.00') AS Rango1
						,IF(DATEDIFF(CURDATE(),Cpp_Ven)>=$Par_Sql[rango2Ini] && DATEDIFF(CURDATE(),Cpp_Ven)<=$Par_Sql[rango2Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))), '0.00') AS Rango2
						,IF(DATEDIFF(CURDATE(),Cpp_Ven)>=$Par_Sql[rango3Ini] && DATEDIFF(CURDATE(),Cpp_Ven)<=$Par_Sql[rango3Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))), '0.00') AS Rango3
						,IF(DATEDIFF(CURDATE(),Cpp_Ven)>$Par_Sql[rango3Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))), '0.00') AS Rango4
						FROM proveedore
							INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
							INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
							INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
							INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
							INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
							INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
							INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
							INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
							LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
							LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
						  WHERE 
              proveedore.Prs_Cod = persona.Prs_Cod 
              AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
			  AND asientos.Com_Cod= comprobantes.Com_Cod 
              AND asientos.Asi_Deh= 'H'
              $concat 
              AND (compras.Cop_Est='A' OR compras.Cop_Est='E') 
              AND(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') 
              AND Emp_Cod= $_SESSION[Ses_Emp_Cod]
			  GROUP BY compras.Cop_Cod ORDER BY persona.Prs_Ape, Cop_Num ASC";
			return $sql;

		case 555:
			if ($Par_Sql[1] == "s") {
				$concat = " AND (perio_cont.Pec_Cod = $Par_Sql[2]) ";
			} else {
				$concat = "";
			}

			$sql = "SELECT 
		            	proveedore.Prv_Cod,
		            	@rownum:=@rownum+1 as indx,
						Cop_Num,
					    comprobantes.Com_Fec as Fecha,
						Cpp_Ven as FechaVenc,
					    asientos.Asi_Val AS Total,
						CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Proveedor,   
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
						CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
						,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0))=Asi_Val,'Pagado'
						,IF(DATEDIFF(Cpp_Ven,CURDATE())>=0,CONCAT('PV ',CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),CONCAT('Vencido ',CAST(DATEDIFF(CURDATE(),Cpp_Ven) AS char),' d&iacute;as'))) AS vencimiento
						,IF(SUM(IF(comp2.Com_Est='A' && ROUND(ROUND(Pag_Val,2),2)!=Asi_Val,ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0))) AS Abono,
						(asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Pag_Val,2),2),0)))) as Saldo
						FROM proveedore
							INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
							INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
							INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
							INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
							INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
							INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod) 
							INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
							INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod) 
							LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
							LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
						WHERE 
			            proveedore.Prs_Cod = persona.Prs_Cod
			            AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
						AND asientos.Com_Cod= comprobantes.Com_Cod 
			            AND asientos.Asi_Deh= 'H'
			            $concat
						AND (compras.Cop_Est='A' OR compras.Cop_Est='E') 
			            AND(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E')  
			            AND Emp_Cod = $_SESSION[Ses_Emp_Cod]
			            AND proveedore.Prv_Cod = $Par_Sql[0]
						GROUP BY compras.Cop_Cod ORDER BY vencimiento DESC, Cop_Num ASC";
			// echo $sql;
			return $sql;

			//
		case 45:
			$sql = "SELECT Pec_Cod, year(Pec_Fei) as anio, Pec_Fei, Pec_Fef  from perio_cont
						where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
						order by Pec_Fei desc;";
			// echo $sql;
			return $sql;
		case 46:
			$sql = "SELECT
							CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS codigo_compro,
							if(tipos_pago.Pag_Abr='CHE', (select banco.Ban_Cue from cheques, banco where cheques.Ban_Cod = banco.Ban_Cod and cheques.Asi_Cod = asientos.Asi_Cod) ,'') as Ban_Cue,
							if(tipos_pago.Pag_Abr='CHE', (select cheques.Che_Fec from cheques, banco where cheques.Ban_Cod = banco.Ban_Cod and cheques.Asi_Cod = asientos.Asi_Cod) ,'') as Che_Fec,
							if(tipos_pago.Pag_Abr='CHE', (select cheques.Che_Num from cheques, banco where cheques.Ban_Cod = banco.Ban_Cod and cheques.Asi_Cod = asientos.Asi_Cod) ,'') as Che_Num,
							det_ccpp_p.Cpp_Cod,
							comprobantes.Com_Cod,
							comprobantes.Com_Fec,
							tipos_pago.Pag_Abr,
							comprobantes.Pec_Cod,
							comprobantes.Com_Val,
							det_ccpp_p.Pag_Val,
							comprobantes.Com_Con
						from det_ccpp_p, comprobantes, tipo_asien, tipos_pago, asientos
						where
							asientos.Asi_Cod = det_ccpp_p.Asi_Cod and
							tipos_pago.Pag_Cod = det_ccpp_p.Pag_Cod and
							det_ccpp_p.Cpp_Cod=$Par_Sql[Cpp_Cod] and
							tipo_asien.Tia_Cod = comprobantes.Tia_Cod and
							comprobantes.Com_Cod = det_ccpp_p.Com_Cod and
							comprobantes.Com_Est = 'A';";
			return $sql;
		case 47:
			//fecha actual
			$hoy = date("Y-m-d");
			$campos_sql = "det_plan.Pld_Cod,
									Pld_Cdc,Pld_Des,
									Prs_Nom,Prs_Ape,
									proveedore.Prv_Cod,
									CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS proveedor,
									persona.Prs_Ape,
									persona.Prs_Nom,
									compras.Cop_Cod,
									compras.Cop_Obs,
									ccpp_pagar.Cpp_Cod,
									compras.Cop_Fec,
									compras.Cop_Num,
									ccpp_pagar.Cpp_Ven,
									ccpp_pagar.Com_Cod,
									comprobantes.Com_Val,
									ccpp_pagar.Cop_Cod,
									asientos.Asi_Cod,
									asientos.Asi_Val,
									asientos.Pld_Cod,
									Pld_Cdc,
									Pld_Des,
									CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))=Asi_Val,'Pagado',IF(DATEDIFF(Cpp_Ven,CURDATE())>0,CONCAT(CAST(DATEDIFF(Cpp_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento,
									IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono";

			$fec_sql = "";
			if ($Par_Sql[sel_ven] != 'ini') {
				$fec_sql = "AND perio_cont.Pec_Cod = $Par_Sql[sel_ven]";
			} else {
				$fec_sql = "AND (compras.Cop_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')";
			}

			$sql = "SELECT $campos_sql
							FROM proveedore
								INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
								INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
								INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
								INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
								INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
								INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
								INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
								INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
								LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
								LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod),persona
							WHERE
								proveedore.Prs_Cod = persona.Prs_Cod
								AND proveedore.Prv_Cod = $Par_Sql[Prv_Cod]
								AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod
								AND asientos.Com_Cod= comprobantes.Com_Cod
								AND asientos.Asi_Deh= 'H'
								AND (compras.Cop_Est='A' OR compras.Cop_Est='E')
								AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
								$fec_sql
								AND Emp_Cod=$_SESSION[Ses_Emp_Cod]
							GROUP BY compras.Cop_Cod
							ORDER by ccpp_pagar.Cpp_Ven";
			//echo $sql;
			return $sql;
		case 48:
			$prv_par = "";
			if ($Par_Sql[Prv_Cod] == "") {
				$prv_par = "";
			} else {
				$prv_par = "AND proveedore.Prv_Cod = $Par_Sql[Prv_Cod]";
			}
			$sql = "SELECT
							proveedore.Prv_Cod,
							persona.Prs_Cod,
							persona.Prs_Ced,
							IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre,
							persona.Prs_Dir
						from persona,proveedore
						where
							proveedore.Emp_Cod='$_SESSION[Ses_Emp_Cod]' $prv_par
							AND proveedore.Prs_Cod = persona.Prs_Cod
							AND proveedore.Prv_Est = 'A'
						ORDER by nombre;";
			return $sql;
			//obtenemos el tipo de asiento para comprobantes de cheques protestados
		case 49:
			$sql = "SELECT * from tipo_asien where Tia_Abr ='DG';";
			return $sql;
			//actualizamos el estado del cheque a protestado
		case 50:
			$sql = "UPDATE cheques
							SET cheques.Che_Est='P'
						WHERE Che_Cod='$Par_Sql[Che_Cod]' AND Prv_Cod='$Par_Sql[Prv_Cod]' AND Ban_Cod='$Par_Sql[Ban_Cod]' AND Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
			//sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
		case 51:
			$sql = "SELECT Pla_Cod, Pec_Cod, Pec_Fei from perio_cont
						where ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) and
						perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//modificamos la glosa del asiento del cheque a protestar, indicando que numero de cheque fue protestado
		case 52:
			$sql = "UPDATE asientos
							SET asientos.Asi_Glo = '$Par_Sql[Asi_Glo]'
						WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
			//modificamos la glosa del asiento del cheque a protestar, indicando que numero de cheque fue protestado
		case 53:
			$sql = "SELECT tipos_pago.Pag_Cod from tipos_pago where tipos_pago.Pag_Abr ='CHE';";
			// echo $sql;
			return $sql;
			//modificamos la glosa del asiento del cheque a protestar, indicando que numero de cheque fue protestado
		case 54:
			$sql = "SELECT
							ccpp_pagar.Cpp_Cod,
							det_ccpp_p.Pag_Val
						from ccpp_pagar, det_ccpp_p
						where
							ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod and
							det_ccpp_p.Asi_Cod = $Par_Sql[Asi_Cod];";
			// echo $sql;
			return $sql;
			//devuelve el cheque ligado a un determinado asiento
		case 55:
			$sql = "SELECT * from cheques where cheques.Asi_Cod = $Par_Sql[Asi_Cod];";
			// echo $sql;
			return $sql;
			//devuelve el cheque ligado a un determinado asiento
		case 56:
			$sql = "UPDATE cheques, asientos
							SET cheques.Che_Val='$Par_Sql[Asi_Val]', asientos.Asi_Val='$Par_Sql[Asi_Val]', asientos.Asi_Con='$Par_Sql[Asi_Con]', asientos.Asi_Glo='$Par_Sql[Asi_Glo]'
						WHERE cheques.Asi_Cod=$Par_Sql[Asi_Cod] AND asientos.Asi_Cod=$Par_Sql[Asi_Cod];";
			// echo $sql;
			return $sql;
		case 57:
			$sql = "SELECT
							asientos.Com_Cod,
							det_ccpp_p.Cpp_Cod,
							asientos.Asi_Cod,
							asientos.Asi_Deh,
							asientos.Asi_Val,
							det_ccpp_p.Pag_Val,
							asientos.Pld_Cod,
							det_ccpp_p.Pag_Cod,
							det_ccpp_p.Pag_Fec,
							det_ccpp_p.Pag_Obs
						from asientos, det_ccpp_p
						where
						asientos.Com_Cod = det_ccpp_p.Com_Cod and
						asientos.Asi_Deh = 'H' and
						det_ccpp_p.Asi_Cod =0 and
						asientos.Asi_Cod = $Par_Sql[0]
						order by det_ccpp_p.Com_Cod;";
			return $sql;
		case 58:
			$sql = "DELETE FROM det_ccpp_p WHERE Asi_Cod=0;";
			return $sql;
		case 59:
			$sql = "SELECT
							asientos.Asi_Cod
						from asientos, det_ccpp_p
						where
							asientos.Com_Cod = det_ccpp_p.Com_Cod and
							asientos.Asi_Deh = 'H' and
							det_ccpp_p.Asi_Cod =0 and
							det_ccpp_p.Cpp_Cod = $Par_Sql[0]
						group by asientos.Asi_Cod;";
			return $sql;
			//retorna todas las compras
		case 60:
			$sql = "select
							det_ccpp_p.Pag_Val,
							det_ccpp_p.Pag_Cod,
							det_ccpp_p.Com_Cod,
							det_ccpp_p.Pag_Fec,
							det_ccpp_p.Pag_Obs,
							det_ccpp_p.Cpp_Cod
						from det_ccpp_p;";
			// echo $sql;
			return $sql;
		case 61:
			$sql = "SELECT Cpp_Cod from ccpp_pagar LIMIT $Par_Sql[limini],$Par_Sql[limfin];";
			// echo $sql;
			return $sql;
		case 62:
			$sql = "SELECT det_ccpp_p.Cpp_Cod,comprobantes.Com_Cod
						from det_ccpp_p,comprobantes, proveedore
						where
							det_ccpp_p.Com_Cod = comprobantes.Com_Cod and
							comprobantes.Prv_Cod = $Par_Sql[0]
						group by Cpp_Cod;";
			// echo $sql;
			return $sql;
		case 63:
			$sql = "SELECT Asi_Cod from asientos where Com_Cod=$Par_Sql[0] and Asi_Deh='H';";
			// echo $sql;
			return $sql;
		case 64:
			$sql = "DELETE FROM det_ccpp_p
						WHERE Com_Cod=$Par_Sql[0]";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla anticipos_proveedores
		case 65:
			$sql = "INSERT INTO anticipos_proveedores (Atp_Fec, Atp_Val, Atp_Est, Atp_Obs, Com_Cod, Prv_Cod, Suc_Cod)
							VALUES('$Par_Sql[Atp_Fec]', $Par_Sql[Atp_Val], 'U', '$Par_Sql[Atp_Obs]', $Par_Sql[Com_Cod], $Par_Sql[Prv_Cod], $_SESSION[Ses_Suc_Cod]);";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla pago_anticipo_proveedores
		case 66:
			$sql = "INSERT INTO pago_anticipo_proveedores (Pap_Cto, Pap_Ctd, Pap_Val, Atp_Cod, Pag_Cod, Asi_Cod)
							VALUES('$Par_Sql[Pap_Cto]', '$Par_Sql[Pap_Ctd]', '$Par_Sql[Pap_Val]', $Par_Sql[Atp_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Asi_Cod]');";
			// echo $sql;
			return $sql;
			//Consultamos una cuenta parametrizada para anticipos a proveedores
		case 67:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
							FROM det_plan, tipo_param, plan_param
							WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
								AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
								AND tipo_param.Tpa_Abr='ANP'
								AND det_plan.Pld_Est='A' AND
								det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//obtenemos las retenciones para el reporte de cuentaS POR PAGAR
		case 68:
			$sql = "SELECT Ret_Num,Ret_Fec,SUM(retencion)AS retencion,tipo FROM (SELECT
								Ret_Num,Ret_Fec,
								CAST( SUM((Ret_Bas*(Ren_Por/100))) AS DECIMAL(10,2) ) AS retencion,
								CONCAT('R',Ren_Ret) AS tipo
							FROM det_retenc
								INNER JOIN renta_iva ON det_retenc.Ren_Cod=renta_iva.Ren_Cod
								INNER JOIN retencion ON det_retenc.Ret_Cod=retencion.Ret_Cod
							WHERE
								Cop_Cod='$Par_Sql[Cop_Cod]' and
								Ret_Asu='N' and
								Ret_Est='A'
							GROUP BY det_retenc.Ren_Cod) AS tlb GROUP BY tipo";
			// echo $sql;
			return $sql;
			//seleccionar el rango de fechas  de entre todos los periodos contables
		case 69:
			$sql = "SELECT min(Pec_Fei) as minimo, max(Pec_Fef) as maximo from perio_cont
							where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
		case 70:
			$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "($importe - ( $importe * compras.Cop_Des/100 )) ";
			$ice = "CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
			$iva = "( /*CAST*/( $importe_con_desc + $ice  /*AS decimal(20,2)*/ )*Iva_Por/100 )";

			$sql = "SELECT compras.Cop_Cod,compras.Cop_Num,CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo,Com_Fec,
                CAST( SUM((
                        $importe_con_desc /* IMPORTE */
                        + $ice /* ICE */
                        + $iva /* IVA */
                    )
                )  + IF(Cop_Irb IS NULL,0,Cop_Irb)  AS decimal(20,2)) AS total,
    proveedore.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,Cpp_Cod
  FROM
	compras
		INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
		INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
	    INNER JOIN ccpp_pagar ON compras.Cop_Cod=ccpp_pagar.Cop_Cod
        INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
        INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
        INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
        INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
	  WHERE Cpp_Cod='$Par_Sql[0]'
	  GROUP BY compras.Cop_Cod";
			//echo $sql;
			break;
		case 71:
			/**
			 * Consulta del vendedor en base al codigo de la persona
			 */
			$sql = "SELECT Cpp_Cod,ccpp_pagar.Cop_Cod,Cop_Num,Com_Fec, Com_Val AS total,compras.Prv_Cod,CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor,
                    CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo
                    FROM ccpp_pagar
                    INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod)
                    INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
                    INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                    INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
                    INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                    WHERE ccpp_pagar.Cpp_Cod=$Par_Sql[0]";
			//echo $sql;
			break;
		case 72:
			$sql = "SELECT det_ccpp_p.Cpp_Cod, sum(Pag_Val) AS Abono, ccpp_pagar.Com_Cod AS Com_Cpp, compras.* FROM det_ccpp_p
					INNER JOIN ccpp_pagar ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod
					INNER JOIN compras ON compras.Cop_Cod=ccpp_pagar.Cop_Cod
					WHERE  det_ccpp_p.Com_Cod = $Par_Sql[Com_Cod]
					GROUP BY ccpp_pagar.Cpp_Cod;";
			break;
		case 73:
			$sql = "SELECT * FROM asientos
					INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
					INNER JOIN ccpp_prove ON ccpp_prove.Pld_Cod=asientos.Pld_Cod
					WHERE Asi_Deh='H' AND Com_Cod=$Par_Sql[Com_Cod]";
			break;
		case 74:
			$sql = "UPDATE anticipos_proveedores
						SET Atp_Est='U'
						WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;


			//Para revertir el anticipo segun se modifique el pago 
		case 123:
			$sql = "UPDATE pago_anticipo_proveedores
						SET Pap_Est='$Par_Sql[Pap_Est]'
						WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;

		case 124:
			$sql = "SELECT dac.Dac_Cod
					from det_ant_ccpp as dac 
					where dac.Atp_Cod = $Par_Sql[Atp_Cod]
					and dac.Com_Cod <> $Par_Sql[Com_Cod];";
			return $sql;

			//Para productores
		case 125:
			$sql = "SELECT count(Prd_Cod) as prd_cant
					from 
					proveedore, productor_bana 
					where 
					proveedore.Prv_Cod = productor_bana.Prv_Cod
					and proveedore.Emp_cod = $Par_Sql[0];";
			return $sql;

		case 126:
			$sql = "SELECT  CONCAT(pr.Prs_Nom,' ',Prs_Ape) as nombre,prov.Prv_Com, 
				anp.Atp_Cod, anp.Atp_Val, anp.Com_Cod, anp.Prv_Cod, 
				anp.Atp_Fec, pr.Prs_Ced, 
				COALESCE(SUM(daccp.Dac_Val),0) AS Dac_Val, 
				COALESCE(SUM(daccp.Dac_Val),0) AS Dac_Val_Aux,
				(anp.Atp_Val - COALESCE(Sum(daccp.Dac_Val),0)) as saldo,    
				(anp.Atp_Val - COALESCE(Sum(daccp.Dac_Val),0)) as saldo_aux 
						 FROM anticipos_proveedores  as anp
						INNER JOIN proveedore as prov ON anp.Prv_Cod = prov.Prv_Cod
						INNER JOIN persona as pr ON pr.Prs_Cod = prov.Prs_Cod
						  LEFT JOIN det_ant_ccpp as daccp ON daccp.Atp_Cod = anp.Atp_Cod
						WHERE anp.Prv_Cod = $Par_Sql[0] AND prov.Emp_Cod =$Par_Sql[1]  AND  anp.Atp_Est!='C' AND anp.Atp_Est!='I'  
						/*
						AND MONTH(anp.Atp_Fec) IN (8, 9)
						AND YEAR(anp.Atp_Fec) = 2024*/
						GROUP BY anp.Atp_Cod  order by anp.Atp_Fec Asc;";
			return $sql;
	}
	return $sql;
}
