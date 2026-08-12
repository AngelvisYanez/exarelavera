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
function sentencias_anticipo_prv($id, $Par_Sql)
{
	switch ($id) {
		//sentencia para obtener todos los proveedores registrados de la empresa
		// OPTIMIZACIÓN: Cambiar JOIN implícito a INNER JOIN explícito
		case 1:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchPrv]%')";
			} else {
				$search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchPrv]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(proveedore.Prv_Cod) AS total" : " proveedore.Prv_Cod, persona.Prs_Cod, persona.Prs_Ced, IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre, persona.Prs_Dir";
			$sql = "SELECT $campos
					FROM proveedore
					INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
					WHERE $search 
						AND proveedore.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
						AND proveedore.Prv_Est = 'A'
					$Par_Sql[limits];";
			return $sql;
			//sentencia para obtener los tipos de pago para anticipos
		case 2: //Se coloco la opcion de OTROS
			$sql = "SELECT Pag_Cod, Pag_Des, Pag_Abr FROM tipos_pago
						WHERE
							Pag_Abr='EFE'
							OR Pag_Abr='CHE'
							OR Pag_Abr='TRF'
							OR Pag_Abr='DEP'
							OR Pag_Abr='OTR'
							
							;";
			return $sql;
			//para seleccionar el plande cuentas correspondiente a los pagos de anticipos a proveedores
		case 3:
			// OPTIMIZACIÓN: Cambiar JOINs implícitos a INNER JOIN explícito
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
					FROM det_plan
					INNER JOIN plan_param ON det_plan.Pld_Cod = plan_param.Pld_Cod
					INNER JOIN tipo_param ON plan_param.Tpa_Cod = tipo_param.Tpa_Cod
					WHERE tipo_param.Tpa_Abr = 'ANP'
						AND det_plan.Pld_Est = 'A'
						AND det_plan.Pla_Cod = (SELECT MAX(plan_cuenta.Pla_Cod) FROM plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]');";
			// echo $sql;
			return $sql;
			//seleccionar plan de cuentas y No. de cuenta del banco para anticipos con cheque de la empresa
		case 4:
			// OPTIMIZACIÓN: Cambiar JOIN implícito a INNER JOIN explícito
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
					FROM det_plan
					INNER JOIN banco ON banco.Pld_Cod = det_plan.Pld_Cod
					WHERE det_plan.Pla_Cod = (SELECT MAX(plan_cuenta.Pla_Cod) FROM plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')
						AND banco.Ban_Tip = '$Par_Sql[Ban_Tip]';";
			return $sql;
			//sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
		case 5:
			$sql = "SELECT Pla_Cod, Pec_Cod, Pec_Fei from perio_cont
						where ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) and
						perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla comprobantes
		case 6:
			$sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Est,Usu_Cod,Com_Gen)
							VALUES($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], null, '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', '$Par_Sql[Com_Con]', 'E', '$Par_Sql[Com_Val]',
						 'SIN OBSERVACIONES', null, $Par_Sql[Tia_Cod], 'A', '$_SESSION[Ses_Usu_Cod]','A');";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla anticipos_proveedores
		case 7:
			$sql = "INSERT INTO anticipos_proveedores (Atp_Fec, Atp_Val, Atp_Est, Atp_Obs, Com_Cod, Prv_Cod, Suc_Cod)
							VALUES('$Par_Sql[Atp_Fec]', $Par_Sql[Atp_Val], 'A', '$Par_Sql[Atp_Obs]', $Par_Sql[Com_Cod], $Par_Sql[Prv_Cod], $_SESSION[Ses_Suc_Cod]);";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla pagos_anticipo_proveedores
		case 8:
			$sql = "INSERT INTO pago_anticipo_proveedores (Pap_Cto, Pap_Ctd, Pap_Val, Atp_Cod, Pag_Cod, Asi_Cod)
							VALUES('$Par_Sql[Pap_Cto]', '$Par_Sql[Pap_Ctd]', '$Par_Sql[Pap_Val]', $Par_Sql[Atp_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Asi_Cod]');";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla asientos
		case 9:
			$sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
							VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', 'ANTICIPO A PROVEEDORES', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";
			// echo $sql;
			return $sql;
			//seleccionar los tipos de asiento para anticipos a proveedores
		case 10:
			$sql = "SELECT * from tipo_asien where Tia_Ini = 'E' order by Tia_Abr;";
			// echo $sql;
			return $sql;
			//seleccionar el rango de fechas  para el periodo contable
		case 11:
			$sql = "SELECT min(Pec_Fei) as minimo, max(Pec_Fef) as maximo from perio_cont
							where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla cheques
		case 12:
			$sql = "INSERT INTO cheques (Che_Cod, Prv_Cod, Ban_Cod, Asi_Cod, Che_Num, Che_Fec, Che_Cob, Che_Val, Che_Obs, Che_Est)
							VALUES($Par_Sql[Che_Cod], $Par_Sql[Prv_Cod], $Par_Sql[Ban_Cod], $Par_Sql[Asi_Cod], $Par_Sql[Che_Num], '$Par_Sql[Che_Fec]', null, '$Par_Sql[Che_Val]', '$Par_Sql[Che_Obs]', 'A');";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla cheques
		case 13:
			$sql = "SELECT Che_Num FROM cheques WHERE Ban_Cod = '$Par_Sql[0]';";
			// echo $sql;
			return $sql;
			//listar los anticipos y proveedores a los que se les ha hecho dichos anticipos
			// OPTIMIZACIÓN: Eliminar subconsulta en SELECT, usar LEFT JOIN con agregación
		case 14:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(prs.Prs_Ced LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "(CONCAT(prs.Prs_Nom, ' ', prs.Prs_Ape)) LIKE '%$Par_Sql[search]%'";
			}

			$contar_regs = "(SELECT 
								prv.Prv_Cod
							FROM proveedore AS prv
							INNER JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
							INNER JOIN anticipos_proveedores AS ant ON ant.Prv_Cod = prv.Prv_Cod
							WHERE $search
								AND (ant.Atp_Est = 'A' OR ant.Atp_Est = 'U')
								AND prv.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
								AND (ant.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
							GROUP BY prv.Prv_Cod)";

			if (empty($Par_Sql['limits'])) {
				$sql = "SELECT COUNT(*) as total FROM $contar_regs AS regscount";
			} else {
				// OPTIMIZACIÓN: Usar LEFT JOIN para det_ant_ccpp en lugar de subconsulta
				$sql = "SELECT 
							proveedore.Prv_Cod,
							anticipos_proveedores.Atp_Cod,
							prs.Prs_Ced,
							CONCAT(prs.Prs_Nom, ' ', prs.Prs_Ape) AS nombre,
							prs.Prs_Dir,
							anticipos_proveedores.Atp_Est,
							anticipos_proveedores.Atp_Fec,
							SUM(anticipos_proveedores.Atp_Val) AS Atp_Val,
							ROUND(SUM(anticipos_proveedores.Atp_Val) - IFNULL(SUM(det_ant_ccpp.Dac_Val), 0), 2) AS tot_anti 
						FROM anticipos_proveedores
						INNER JOIN proveedore ON anticipos_proveedores.Prv_Cod = proveedore.Prv_Cod
						INNER JOIN persona AS prs ON proveedore.Prs_Cod = prs.Prs_Cod
						INNER JOIN comprobantes ON anticipos_proveedores.Com_Cod = comprobantes.Com_Cod
						LEFT JOIN det_ant_ccpp ON det_ant_ccpp.Atp_Cod = anticipos_proveedores.Atp_Cod
						WHERE $search
							AND (anticipos_proveedores.Atp_Est = 'A' OR anticipos_proveedores.Atp_Est = 'U')
							AND proveedore.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
							AND (anticipos_proveedores.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]') 
						GROUP BY anticipos_proveedores.Atp_Cod 
						$Par_Sql[limits];";



				/*"SELECT 
								prv.Prv_Cod,IF(prs.Prs_Nom=prs.Prs_Ape, prs.Prs_Nom,concat(prs.Prs_Nom, ' ', prs.Prs_Ape)) as nombre, prs.Prs_Ced,prs.Prs_Cod,prs.Prs_Dir, 
								ABS((
									select sum(pga.Pap_Val) from pago_anticipo_proveedores as pga inner join anticipos_proveedores as ant2 on (ant2.Atp_Cod=pga.Atp_Cod) inner join asientos as asi on (asi.Asi_Cod=pga.Asi_Cod)
									where ant2.Prv_Cod=prv.Prv_Cod
									and pga.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = asi.Asi_Cod and cheques.Che_Est = 'P')
									)-(
									select IF(SUM(dac.Dac_Val) is null,0,SUM(dac.Dac_Val)) AS tot_dac from det_ant_ccpp as dac
									inner join anticipos_proveedores as ant3 on (ant3.Atp_Cod=dac.Atp_Cod) where ant3.Atp_Est ='U' and ant3.Prv_Cod = prv.Prv_Cod
								)) AS tot_anti
							FROM proveedore as prv
								inner join persona as prs on(prs.Prs_Cod=prv.Prs_Cod)
								inner join anticipos_proveedores as ant on (ant.Prv_Cod=prv.Prv_Cod)
							WHERE  $search
								AND (ant.Atp_Est ='A' OR ant.Atp_Est ='U')
								AND prv.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
								AND (ant.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
							GROUP BY prv.Prv_Cod ORDER BY ant.Atp_Cod $Par_Sql[limits];"	*/
			}
			// echo $sql;
			return $sql;
			//Seleccionar el detalle de los anticipos de los proveedores
			// OPTIMIZACIÓN: Eliminar subconsultas, usar LEFT JOIN con agregaciones
		case 15:
			$sql = "SELECT
						CONCAT(tas.Tia_Abr, '-', MONTH(com.Com_Fec), '-', com.Com_Num) AS codigo_compro,
						tas.Tia_Cod,
						com.Com_Num,
						ant.Com_Cod,
						ant.Atp_Cod,
						ant.Atp_Fec,
						ant.Atp_Est,
						ant.Atp_Obs,
						IFNULL(SUM(CASE WHEN pga.Pap_Est = 'A' AND (che.Che_Est IS NULL OR che.Che_Est != 'P') THEN pga.Pap_Val ELSE 0 END), 0) AS Atp_Val,
						COUNT(DISTINCT CASE WHEN pga2.Pap_Cod IS NOT NULL THEN pga2.Pap_Cod END) AS cnt_pagos,
						ABS(
							IFNULL(SUM(CASE WHEN pga.Pap_Est = 'A' AND (che.Che_Est IS NULL OR che.Che_Est != 'P') THEN pga.Pap_Val ELSE 0 END), 0) - 
							IFNULL(SUM(dac.Dac_Val), 0)
						) AS tot_sald
					FROM anticipos_proveedores AS ant
					INNER JOIN comprobantes AS com ON com.Com_Cod = ant.Com_Cod
					INNER JOIN tipo_asien AS tas ON tas.Tia_Cod = com.Tia_Cod
					LEFT JOIN pago_anticipo_proveedores AS pga ON pga.Atp_Cod = ant.Atp_Cod
					LEFT JOIN cheques AS che ON che.Asi_Cod = pga.Asi_Cod
					LEFT JOIN pago_anticipo_proveedores AS pga2 ON pga2.Atp_Cod = ant.Atp_Cod
					LEFT JOIN det_ant_ccpp AS dac ON dac.Atp_Cod = ant.Atp_Cod
					WHERE (ant.Atp_Est = 'A' OR ant.Atp_Est = 'U')
						AND (ant.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
						AND ant.Prv_Cod = '$Par_Sql[Prv_Cod]'
					GROUP BY ant.Atp_Cod
					ORDER BY ant.Atp_Cod;";
			//echo $sql;
			return $sql;
			//seleccionar todos los asientos y pagos de un anticipo
			// OPTIMIZACIÓN: Mejorar orden de JOINs y formato SQL
		case 16:
			$sql = "SELECT
						che.Che_Est,
						pdt.Pld_Cdc,
						pdt.Pld_Cod,
						pdt.Pld_Des,
						pga.Pap_Cod,
						pga.Pap_Cto,
						pga.Pap_Ctd,
						tpg.Pag_Cod,
						tpg.Pag_Abr,
						tpg.Pag_Des,
						che.Che_Cod,
						che.Che_Fec,
						che.Che_Num,
						che.Ban_Cod,
						asi.Asi_Cod,
						asi.Asi_Deh,
						asi.Asi_Con,
						asi.Pld_Cod,
						asi.Asi_Glo,
						IF(asi.Asi_Deh = 'D', asi.Asi_Val, '') AS Debe,
						IF(asi.Asi_Deh = 'H', asi.Asi_Val, '') AS Haber
					FROM asientos AS asi
					INNER JOIN comprobantes AS com ON com.Com_Cod = asi.Com_Cod
					INNER JOIN det_plan AS pdt ON pdt.Pld_Cod = asi.Pld_Cod
					LEFT JOIN pago_anticipo_proveedores AS pga ON pga.Asi_Cod = asi.Asi_Cod
					LEFT JOIN tipos_pago AS tpg ON tpg.Pag_Cod = pga.Pag_Cod
					LEFT JOIN cheques AS che ON che.Asi_Cod = pga.Asi_Cod
					WHERE com.Com_Cod = $Par_Sql[Com_Cod]
					ORDER BY asi.Asi_Deh;";
			// echo $sql;
			return $sql;
			//seleccionar el asiento inicial por defecto de un anticipo
		case 17:
			$sql = "SELECT
								det_plan.Pld_Cdc,
								det_plan.Pld_Des,
								asientos.Asi_Glo,
								IF(asientos.Asi_Deh='D',asientos.Asi_Val,'') AS Debe,
								IF(asientos.Asi_Deh='H',asientos.Asi_Val,'') AS Haber,
								asientos.Asi_Cod
							FROM anticipos_proveedores,asientos, det_plan
							WHERE
								asientos.Pld_Cod = '$Par_Sql[Pld_Cod]' and
								asientos.Com_Cod = anticipos_proveedores.Com_Cod and
								asientos.Asi_Deh = '$Par_Sql[Asi_Deh]' and
								anticipos_proveedores.Prv_Cod='$Par_Sql[Prv_Cod]' AND
								anticipos_proveedores.Atp_Cod='$Par_Sql[Atp_Cod]' AND
								asientos.Pld_Cod = det_plan.Pld_Cod;";
			// echo $sql;
			return $sql;
			//seleccionar el asiento inicial por defecto de un anticipo
		case 18:
			$sql = "SELECT
							pga.Pap_Cto,pga.Pap_Cod,
							com.Com_Cod,com.Tia_Cod,
							ant.Atp_Fec,ant.Atp_Cod,ant.Prv_Cod,
							asi.Pld_Cod,che.*
						from cheques as che
							inner join asientos as asi on (asi.Asi_Cod=che.Asi_Cod)
							inner join pago_anticipo_proveedores as pga on (pga.Asi_Cod=asi.Asi_Cod)
							inner join anticipos_proveedores as ant on (ant.Atp_Cod=pga.Atp_Cod)
							inner join comprobantes as com on (com.Com_Cod=ant.Com_Cod)
						where
							ant.Atp_Cod = '$Par_Sql[Atp_Cod]' and
							ant.Prv_Cod='$Par_Sql[Prv_Cod]';";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 19:
			$sql = "UPDATE anticipos_proveedores
							SET Atp_Est='I'
							WHERE Atp_Cod='$Par_Sql[0]';";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 20:
			$sql = "SELECT Pec_Cod, year(Pec_Fei) as anio, Pec_Fei, Pec_Fef  from perio_cont
							where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
							order by Pec_Fei desc;";
			// echo $sql;
			return $sql;
			//modificar los valores del anticipo indicado
		case 21:
			$sql = "UPDATE anticipos_proveedores
								SET Atp_Fec='$Par_Sql[Atp_Fec]', Atp_Val='$Par_Sql[Atp_Val]',  Atp_Obs='$Par_Sql[Atp_Obs]'
							WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;
			//Anular un anticipo con sus respectivos comprobante, pagos, asientos, cheques
		case 22:
			$sql = "UPDATE comprobantes
								SET Pec_Cod='$Par_Sql[Pec_Cod]', Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con='$Par_Sql[Com_Con]',  Com_Val='$Par_Sql[Com_Val]', Com_Obs='$Par_Sql[Com_Obs]', Tia_Cod='$Par_Sql[Tia_Cod]'
							WHERE Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
			//eliminar asientos
		case 23:
			$sql = "DELETE FROM asientos
							WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
			//Protestar un cheque del anticipo
		case 24:
			$sql = "UPDATE cheques
								SET cheques.Che_Est='P'
							WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
			//actualizar estado del pago de anticip� a proveedores
		case 25:
			$sql = "UPDATE pago_anticipo_proveedores
								SET pago_anticipo_proveedores.Pap_Est='P', pago_anticipo_proveedores.Pap_Obs='$Par_Sql[Pap_Obs]'
							WHERE Pap_Cod='$Par_Sql[Pap_Cod]';";
			// echo $sql;
			return $sql;
			//decrementar el valor del anticipo***
		case 26:
			$sql = "UPDATE anticipos_proveedores
								SET anticipos_proveedores.Atp_Val = anticipos_proveedores.Atp_Val - $Par_Sql[Atp_Val]
							WHERE Atp_Cod='$Par_Sql[Atp_Cod]';";
			// echo $sql;
			return $sql;
			//modificar asiento de un chueque protestado del haber al debe o viceversa
		case 27:
			$sql = "UPDATE asientos
								SET asientos.Asi_Glo = '$Par_Sql[Asi_Glo]'
							WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
		case 28:
			$sql = "SELECT * from tipo_asien where Tia_Abr ='DG';";
			return $sql;
			//asifnar estado I a un comprobante
		case 29:
			$sql = "UPDATE comprobantes
						INNER JOIN asientos ON (comprobantes.Com_Cod=asientos.Com_Cod)
						LEFT JOIN cheques ON (asientos.Asi_Cod=cheques.Asi_Cod AND Che_Est<>'P')
					  SET 
						Com_Est='I', Che_Est='I'
					  WHERE comprobantes.Com_Cod='$Par_Sql[0]'";
			// echo $sql;
			return $sql;
			//asifnar estado I a un comprobante
		case 30:
			$sql = "UPDATE pago_anticipo_proveedores
								SET Pap_Est='I'
							WHERE Atp_Cod='$Par_Sql[0]';";
			// echo $sql;
			return $sql;
		case 31:
			$sql = "SELECT
								asientos.Asi_Cod,
								asientos.Asi_Deh,
								if(asientos.Asi_Deh='D',asientos.Asi_Val,'') as Debe,
								if(asientos.Asi_Deh='H',asientos.Asi_Val,'') as Haber,
								asientos.Asi_Con,
								asientos.Asi_Glo,
								det_plan.Pld_Cod,
								det_plan.Pld_Cdc,
								det_plan.Pld_Des
							from asientos, det_plan
							where
								det_plan.Pld_Cod = asientos.Pld_Cod and
								asientos.Com_Cod = $Par_Sql[0]
							order by Asi_Deh;";
			// echo $sql;
			return $sql;
		case 32:
			$sql = "DELETE FROM asientos
						where
							asientos.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = asientos.Asi_Cod and cheques.Che_Est = 'P') and
							asientos.Com_Cod=$Par_Sql[Com_Cod];";
			return $sql;

		case 33: //Nueva consulta para cargar el plan de cuentas
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

		case 34:
			$sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
			return $sql;
			//Datos camaronera

		case 35:
			if (!empty($Par_Sql[1])) {
				$Par_Sql[1] = " AND Num_Neg=$Par_Sql[1]";
			}
			$sql = "SELECT Cod_Neg,Num_Neg FROM nego_camaron WHERE Emp_Cod IN ($Par_Sql[0])  $Par_Sql[1]   AND Est_Neg = 'A' OR Est_Neg='P'";
			return $sql;

		case 36:
			//$sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc,Tip_Prod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','' );";
			if (empty($Par_Sql[3])) {
				$sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc,Tip_Prod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','' );";
			} else {
				$sql = "UPDATE nego_documentos SET Cod_Neg=$Par_Sql[0], Cod_Doc=$Par_Sql[1], Abr_Doc='$Par_Sql[2]' WHERE Cod_Nd=$Par_Sql[3];";
			}
			return $sql;

		case 37:
			$sql = "SELECT grupo_clientes.* FROM det_grup_empresas 
            INNER JOIN grupo_clientes ON grupo_clientes.Cod_Grup = det_grup_empresas.Cod_Group
            WHERE det_grup_empresas.Emp_Cod = $_SESSION[Ses_Emp_Cod] ";
			break;

		case  38:
			$sql = "DELETE FROM nego_documentos WHERE  Cod_Nd = $Par_Sql[0] AND  Abr_Doc = '$Par_Sql[1]'";
			break;

		/* Kardex anticipos + consumos (orden por fecha); filas sin saldo (PHP lo acumula) */
		case 201:
			$emp = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
			$fec_ini = isset($Par_Sql['txt_fec_ini']) ? trim($Par_Sql['txt_fec_ini']) : date('Y-01-01');
			$fec_fin = isset($Par_Sql['txt_fec_fin']) ? trim($Par_Sql['txt_fec_fin']) : date('Y-m-d');
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fec_ini)) {
				$fec_ini = date('Y-01-01');
			}
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fec_fin)) {
				$fec_fin = date('Y-m-d');
			}
			$estadoFiltro = isset($Par_Sql['letra']) ? trim($Par_Sql['letra']) : 'AUC';
			$estIn = "('A','U','C')";
			switch ($estadoFiltro) {
				case 'AUC':
					$estIn = "('A','U','C')";
					break;
				case 'AUCI':
					$estIn = "('A','U','C','I')";
					break;
				case 'AU':
					$estIn = "('A','U')";
					break;
				case 'C':
					$estIn = "('C')";
					break;
				case 'I':
					$estIn = "('I')";
					break;
			}
			$op = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : 'p';
			$search = isset($Par_Sql['search']) ? trim($Par_Sql['search']) : '';
			$searchSqlPerson = '';
			$searchSqlNumAnt = '';
			$searchSqlNumCon = '';
			if ($search !== '') {
				$esc = str_replace(array("\\", "'", '"'), array("\\\\", "''", ''), $search);
				if ($op === 'c') {
					$searchSqlPerson = " AND prs.Prs_Ced LIKE '%" . $esc . "%' ";
				} elseif ($op === 'n') {
					$searchSqlNumAnt = " AND (CAST(c.Com_Num AS CHAR) LIKE '%" . $esc . "%' OR CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1,CONCAT('0',CAST(MONTH(c.Com_Fec) AS CHAR)),CAST(MONTH(c.Com_Fec) AS CHAR)),'-',CAST(c.Com_Num AS CHAR)) LIKE '%" . $esc . "%') ";
					$searchSqlNumCon = " AND (CAST(ccon.Com_Num AS CHAR) LIKE '%" . $esc . "%' OR CONCAT(tp2.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(ccon.Com_Fec))=1,CONCAT('0',CAST(MONTH(ccon.Com_Fec) AS CHAR)),CAST(MONTH(ccon.Com_Fec) AS CHAR)),'-',CAST(ccon.Com_Num AS CHAR)) LIKE '%" . $esc . "%') ";
				} else {
					$searchSqlPerson = " AND ((UPPER(prs.Prs_Nom) LIKE UPPER('%" . $esc . "%')) OR (UPPER(prs.Prs_Ape) LIKE UPPER('%" . $esc . "%'))) ";
				}
			}
			$pecSqlAnt = '';
			$pecSqlCon = '';
			$prvSql = '';
			if (isset($Par_Sql['Prv_Cod']) && $Par_Sql['Prv_Cod'] !== '' && ctype_digit((string) $Par_Sql['Prv_Cod']) && intval($Par_Sql['Prv_Cod']) > 0) {
				$prvSql = ' AND ant.Prv_Cod = ' . intval($Par_Sql['Prv_Cod']) . ' ';
			}
			$tipoMov = isset($Par_Sql['tipo_mov']) ? strtoupper(trim($Par_Sql['tipo_mov'])) : 'T';
			$onlyAnticipo = ($tipoMov === 'A');
			$onlyConsumo = ($tipoMov === 'C');
			$ordenKardex = isset($Par_Sql['orden_kardex']) ? strtoupper(trim($Par_Sql['orden_kardex'])) : 'C';
			/* Modo A: detalle anticipo-consumo. Modo C: resumen 1 fila por comprobante (orden fecha). */
			$orderSql = " ORDER BY Fecha_Mov ASC, tipo_ord ASC, row_id ASC";
			if ($ordenKardex === 'A') {
				$orderSql = " ORDER BY Atp_Cod ASC, Fecha_Mov ASC, tipo_ord ASC, row_id ASC";
			}
			$isCorte = isset($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] === 'Corte';
			if ($isCorte) {
				$dateSqlAnt = " AND (
					(ant.Atp_Fec BETWEEN '" . $fec_ini . " 00:00:00' AND '" . $fec_fin . " 23:59:59')
					OR EXISTS (
						SELECT 1 FROM det_ant_ccpp dac_f
						INNER JOIN comprobantes c_f ON c_f.Com_Cod = dac_f.Com_Cod
						WHERE dac_f.Atp_Cod = ant.Atp_Cod
						AND c_f.Com_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "'
					)
				) ";
			} else {
				$dateSqlAnt = " AND ant.Atp_Fec BETWEEN '" . $fec_ini . " 00:00:00' AND '" . $fec_fin . " 23:59:59' ";
			}
			$dateSqlCon = " AND ccon.Com_Fec BETWEEN '" . $fec_ini . "' AND '" . $fec_fin . "' ";
			$sqlBase = "SELECT * FROM (
				SELECT
					CONCAT('A', ant.Atp_Cod) AS row_id,
					'Anticipo' AS Tipo_Linea,
					ant.Atp_Cod AS Atp_Cod,
					ant.Atp_Est AS Atp_Est,
					ant.Prv_Cod AS Prv_Cod,
					DATE(ant.Atp_Fec) AS Fecha_Mov,
					ant.Atp_Fec AS Atp_Fec,
					IF(c.Com_Est='E' OR EXISTS (SELECT 1 FROM pago_anticipo_proveedores _pap INNER JOIN tipos_pago _tp ON _tp.Pag_Cod = _pap.Pag_Cod WHERE _pap.Atp_Cod = ant.Atp_Cod AND _pap.Pap_Est <> 'I' AND (_tp.Pag_Abr='INI' OR UPPER(_tp.Pag_Des) LIKE '%INICIAL%')), 'INICIAL', CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1,CONCAT('0',CAST(MONTH(c.Com_Fec) AS CHAR)),CAST(MONTH(c.Com_Fec) AS CHAR)),'-',CAST(c.Com_Num AS CHAR))) AS codigoCompra,
					TRIM(CONCAT(IFNULL(prs.Prs_Nom,''),' ',IFNULL(prs.Prs_Ape,''))) AS nombre,
					prs.Prs_Ced AS cedProv,
					prs.Prs_Ced AS Prs_Ced,
					prs.Prs_Cod AS Prs_Cod,
					IFNULL(ant.Atp_Obs,'') AS Atp_Obs,
					IFNULL(TRIM(CONCAT(IFNULL(up.Prs_Nom,''),' ',IFNULL(up.Prs_Ape,''))),'') AS usuario,
					IFNULL(c.Com_Sys,'') AS Com_Sys,
					c.Pec_Cod AS Pec_Cod,
					c.Tia_Cod AS Tia_Cod,
					CAST(ant.Atp_Val AS DECIMAL(14,4)) AS TOTAL,
					CAST(0 AS DECIMAL(14,4)) AS CONSUMO,
					1 AS tipo_ord,
					ant.Com_Cod AS Com_Cod,
					ant.Com_Cod AS Com_Cod_eg,
					IFNULL(c.Com_Con,'') AS Glosa,
					'' AS Pag_Des,
					'' AS Pap_Es2,
					prv.Prv_Cod AS prvCod,
					IFNULL(c.Cli_Cod,'') AS Cli_Cod,
					'' AS Asi_Cod,
					'' AS Pag_Cod,
					'' AS Pap_Ctd,
					'' AS Pap_Obs,
					CAST(ant.Atp_Val AS DECIMAL(14,4)) AS Com_Val,
					c.Com_Fec AS Com_Fec
				FROM anticipos_proveedores AS ant
				INNER JOIN proveedore AS prv ON prv.Prv_Cod = ant.Prv_Cod
				INNER JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
				INNER JOIN comprobantes AS c ON c.Com_Cod = ant.Com_Cod
				INNER JOIN tipo_asien AS tp ON tp.Tia_Cod = c.Tia_Cod
				LEFT JOIN usuarios AS u ON u.Usu_Cod = c.Usu_Cod
				LEFT JOIN persona AS up ON up.Prs_Cod = u.Prs_Cod
				WHERE prv.Emp_Cod = " . $emp . "
					AND ant.Atp_Est IN " . $estIn . "
					" . $dateSqlAnt . "
					" . $searchSqlPerson . $searchSqlNumAnt . $pecSqlAnt . $prvSql . "
					" . ($onlyConsumo ? " AND 1=0 " : "") . "
				UNION ALL
				SELECT
					CONCAT('C', dac.Dac_Cod) AS row_id,
					'Consumo' AS Tipo_Linea,
					ant.Atp_Cod AS Atp_Cod,
					ant.Atp_Est AS Atp_Est,
					ant.Prv_Cod AS Prv_Cod,
					DATE(ccon.Com_Fec) AS Fecha_Mov,
					ccon.Com_Fec AS Atp_Fec,
					IF(ccon.Com_Est='E', 'INICIAL', CONCAT(tp2.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(ccon.Com_Fec))=1,CONCAT('0',CAST(MONTH(ccon.Com_Fec) AS CHAR)),CAST(MONTH(ccon.Com_Fec) AS CHAR)),'-',CAST(ccon.Com_Num AS CHAR))) AS codigoCompra,
					TRIM(CONCAT(IFNULL(prs.Prs_Nom,''),' ',IFNULL(prs.Prs_Ape,''))) AS nombre,
					prs.Prs_Ced AS cedProv,
					prs.Prs_Ced AS Prs_Ced,
					prs.Prs_Cod AS Prs_Cod,
					IFNULL(ant.Atp_Obs,'') AS Atp_Obs,
					IFNULL(TRIM(CONCAT(IFNULL(up2.Prs_Nom,''),' ',IFNULL(up2.Prs_Ape,''))),'') AS usuario,
					IFNULL(ccon.Com_Sys,'') AS Com_Sys,
					ccon.Pec_Cod AS Pec_Cod,
					ccon.Tia_Cod AS Tia_Cod,
					CAST(0 AS DECIMAL(14,4)) AS TOTAL,
					CAST(dac.Dac_Val AS DECIMAL(14,4)) AS CONSUMO,
					2 AS tipo_ord,
					ccon.Com_Cod AS Com_Cod,
					ant.Com_Cod AS Com_Cod_eg,
					IFNULL(ccon.Com_Con,'') AS Glosa,
					IFNULL(tpp.Pag_Des,'') AS Pag_Des,
					IFNULL(pap.Pap_Es2,'') AS Pap_Es2,
					prv.Prv_Cod AS prvCod,
					IFNULL(ccon.Cli_Cod,'') AS Cli_Cod,
					IFNULL(pap.Asi_Cod,'') AS Asi_Cod,
					IFNULL(pap.Pag_Cod,'') AS Pag_Cod,
					IFNULL(pap.Pap_Ctd,'') AS Pap_Ctd,
					IFNULL(pap.Pap_Obs,'') AS Pap_Obs,
					IFNULL(ccon.Com_Val,0) AS Com_Val,
					ccon.Com_Fec AS Com_Fec
				FROM det_ant_ccpp AS dac
				INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = dac.Atp_Cod
				INNER JOIN proveedore AS prv ON prv.Prv_Cod = ant.Prv_Cod
				INNER JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
				INNER JOIN comprobantes AS ccon ON ccon.Com_Cod = dac.Com_Cod
				INNER JOIN tipo_asien AS tp2 ON tp2.Tia_Cod = ccon.Tia_Cod
				LEFT JOIN usuarios AS u2 ON u2.Usu_Cod = ccon.Usu_Cod
				LEFT JOIN persona AS up2 ON up2.Prs_Cod = u2.Prs_Cod
				LEFT JOIN pago_anticipo_proveedores AS pap ON pap.Pap_Cod = dac.Pap_Cod
				LEFT JOIN tipos_pago AS tpp ON tpp.Pag_Cod = pap.Pag_Cod
				WHERE prv.Emp_Cod = " . $emp . "
					AND ant.Atp_Est IN " . $estIn . "
					AND IFNULL(ccon.Com_Est,'A') <> 'I'
					" . $dateSqlCon . "
					" . $searchSqlPerson . $searchSqlNumCon . $pecSqlCon . $prvSql . "
					" . ($onlyAnticipo ? " AND 1=0 " : "") . "
			) kardex_base";

			if ($ordenKardex === 'C') {
				$sql = "SELECT
						MIN(row_id) AS row_id,
						Tipo_Linea,
						MIN(Atp_Cod) AS Atp_Cod,
						CASE WHEN SUM(CASE WHEN Atp_Est = 'I' THEN 1 ELSE 0 END) > 0 THEN 'I' ELSE MIN(Atp_Est) END AS Atp_Est,
						MIN(Prv_Cod) AS Prv_Cod,
						MIN(Fecha_Mov) AS Fecha_Mov,
						MIN(Atp_Fec) AS Atp_Fec,
						MIN(codigoCompra) AS codigoCompra,
						MIN(nombre) AS nombre,
						MIN(cedProv) AS cedProv,
						MIN(Prs_Ced) AS Prs_Ced,
						MIN(Prs_Cod) AS Prs_Cod,
						MIN(Atp_Obs) AS Atp_Obs,
						MIN(usuario) AS usuario,
						MIN(Com_Sys) AS Com_Sys,
						MIN(Pec_Cod) AS Pec_Cod,
						MIN(Tia_Cod) AS Tia_Cod,
						CAST(SUM(TOTAL) AS DECIMAL(14,4)) AS TOTAL,
						CAST(SUM(CONSUMO) AS DECIMAL(14,4)) AS CONSUMO,
						MIN(tipo_ord) AS tipo_ord,
						Com_Cod,
						MIN(Com_Cod_eg) AS Com_Cod_eg,
						MIN(Glosa) AS Glosa,
						'' AS Pag_Des,
						'' AS Pap_Es2,
						MIN(prvCod) AS prvCod,
						MIN(Cli_Cod) AS Cli_Cod,
						'' AS Asi_Cod,
						'' AS Pag_Cod,
						'' AS Pap_Ctd,
						'' AS Pap_Obs,
						MAX(Com_Val) AS Com_Val,
						MIN(Com_Fec) AS Com_Fec
					FROM (" . $sqlBase . ") k
					GROUP BY Tipo_Linea, Com_Cod
					ORDER BY Fecha_Mov ASC, tipo_ord ASC, codigoCompra ASC";
			} else {
				$sql = $sqlBase . $orderSql;
			}
			return $sql;

		case 203:
			$atpCod = isset($Par_Sql['Atp_Cod']) ? intval($Par_Sql['Atp_Cod']) : 0;
			if ($atpCod <= 0) {
				return "SELECT '' AS codigo_consumo, '' AS fecha_consumo, '' AS glosa_consumo, 0 AS valor_anticipo, 0 AS valor_consumo, 0 AS saldo_anticipo LIMIT 0";
			}
			$sql = "SELECT
					CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(c.Com_Fec))=1,CONCAT('0',CAST(MONTH(c.Com_Fec) AS CHAR)),CAST(MONTH(c.Com_Fec) AS CHAR)),'-',CAST(c.Com_Num AS CHAR)) AS codigo_consumo,
					DATE(c.Com_Fec) AS fecha_consumo,
					IFNULL(c.Com_Con,'') AS glosa_consumo,
					CAST(ant.Atp_Val AS DECIMAL(14,4)) AS valor_anticipo,
					CAST(dac.Dac_Val AS DECIMAL(14,4)) AS valor_consumo,
					CAST((ant.Atp_Val - IFNULL((SELECT SUM(d2.Dac_Val) FROM det_ant_ccpp d2 WHERE d2.Atp_Cod = ant.Atp_Cod),0)) AS DECIMAL(14,4)) AS saldo_anticipo
				FROM det_ant_ccpp AS dac
				INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = dac.Atp_Cod
				INNER JOIN comprobantes AS c ON c.Com_Cod = dac.Com_Cod
				INNER JOIN tipo_asien AS tp ON tp.Tia_Cod = c.Tia_Cod
				WHERE dac.Atp_Cod = " . $atpCod . "
					AND IFNULL(c.Com_Est,'A') <> 'I'
				ORDER BY c.Com_Fec ASC, c.Com_Cod ASC";
			return $sql;

		case 204:
			$comCod = isset($Par_Sql['Com_Cod']) ? intval($Par_Sql['Com_Cod']) : 0;
			if ($comCod <= 0) {
				return "SELECT 0 AS Dac_Cod, 0 AS Atp_Cod, '' AS asiento_anticipo, 0 AS valor_anticipo, 0 AS valor_consumido, 0 AS saldo_momento, 0 AS saldo_final_hoy LIMIT 0";
			}
			$sql = "SELECT
					dac.Dac_Cod AS Dac_Cod,
					ant.Atp_Cod AS Atp_Cod,
					CONCAT(tp.Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(cant.Com_Fec))=1,CONCAT('0',CAST(MONTH(cant.Com_Fec) AS CHAR)),CAST(MONTH(cant.Com_Fec) AS CHAR)),'-',CAST(cant.Com_Num AS CHAR)) AS asiento_anticipo,
					CAST(ant.Atp_Val AS DECIMAL(14,4)) AS valor_anticipo,
					CAST(dac.Dac_Val AS DECIMAL(14,4)) AS valor_consumido,
					CAST((ant.Atp_Val - IFNULL((
						SELECT SUM(d2.Dac_Val)
						FROM det_ant_ccpp d2
						INNER JOIN comprobantes c2 ON c2.Com_Cod = d2.Com_Cod
						WHERE d2.Atp_Cod = ant.Atp_Cod
							AND IFNULL(c2.Com_Est,'A') <> 'I'
							AND (
								c2.Com_Fec < ccons.Com_Fec
								OR (c2.Com_Fec = ccons.Com_Fec AND d2.Com_Cod <= dac.Com_Cod)
							)
					),0)) AS DECIMAL(14,4)) AS saldo_momento,
					CAST((ant.Atp_Val - IFNULL((
						SELECT SUM(d3.Dac_Val)
						FROM det_ant_ccpp d3
						INNER JOIN comprobantes c3 ON c3.Com_Cod = d3.Com_Cod
						WHERE d3.Atp_Cod = ant.Atp_Cod
							AND IFNULL(c3.Com_Est,'A') <> 'I'
					),0)) AS DECIMAL(14,4)) AS saldo_final_hoy
				FROM det_ant_ccpp AS dac
				INNER JOIN comprobantes AS ccons ON ccons.Com_Cod = dac.Com_Cod
				INNER JOIN anticipos_proveedores AS ant ON ant.Atp_Cod = dac.Atp_Cod
				INNER JOIN comprobantes AS cant ON cant.Com_Cod = ant.Com_Cod
				INNER JOIN tipo_asien AS tp ON tp.Tia_Cod = cant.Tia_Cod
				WHERE dac.Com_Cod = " . $comCod . "
				ORDER BY ant.Atp_Cod ASC";
			return $sql;

		case 202:
			$emp = isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0;
			$fec_ini = isset($Par_Sql['txt_fec_ini']) ? trim($Par_Sql['txt_fec_ini']) : date('Y-01-01');
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fec_ini)) {
				$fec_ini = date('Y-01-01');
			}
			/* Saldo antes del rango: nunca incluye anulados (I); el filtro de pantalla no altera este cálculo */
			$estInSaldoIni = "('A','U','C')";
			$op = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : 'p';
			$search = isset($Par_Sql['search']) ? trim($Par_Sql['search']) : '';
			if ($op === 'n' && $search !== '') {
				return "SELECT 0 AS saldo_ini";
			}
			$searchSqlPerson = '';
			if ($search !== '') {
				$esc = str_replace(array("\\", "'", '"'), array("\\\\", "''", ''), $search);
				if ($op === 'c') {
					$searchSqlPerson = " AND prs.Prs_Ced LIKE '%" . $esc . "%' ";
				} elseif ($op !== 'n') {
					$searchSqlPerson = " AND ((UPPER(prs.Prs_Nom) LIKE UPPER('%" . $esc . "%')) OR (UPPER(prs.Prs_Ape) LIKE UPPER('%" . $esc . "%'))) ";
				}
			}
			$pecSqlAnt = '';
			$pecSqlCon = '';
			if (isset($Par_Sql['Pec_Cod']) && $Par_Sql['Pec_Cod'] !== '' && $Par_Sql['Pec_Cod'] !== 'T' && $Par_Sql['Pec_Cod'] !== 'Corte' && ctype_digit((string) $Par_Sql['Pec_Cod'])) {
				$pec = intval($Par_Sql['Pec_Cod']);
				$pecSqlAnt = ' AND c.Pec_Cod = ' . $pec . ' ';
				$pecSqlCon = ' AND ccon.Pec_Cod = ' . $pec . ' ';
			}
			$prvSql = '';
			if (isset($Par_Sql['Prv_Cod']) && $Par_Sql['Prv_Cod'] !== '' && ctype_digit((string) $Par_Sql['Prv_Cod']) && intval($Par_Sql['Prv_Cod']) > 0) {
				$prvSql = ' AND ant.Prv_Cod = ' . intval($Par_Sql['Prv_Cod']) . ' ';
			}
			$sql = "SELECT (
				COALESCE((SELECT SUM(ant.Atp_Val) FROM anticipos_proveedores AS ant
					INNER JOIN proveedore AS prv ON prv.Prv_Cod = ant.Prv_Cod
					INNER JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
					INNER JOIN comprobantes AS c ON c.Com_Cod = ant.Com_Cod
					WHERE prv.Emp_Cod = " . $emp . " AND ant.Atp_Est IN " . $estInSaldoIni . "
					AND ant.Atp_Fec < '" . $fec_ini . " 00:00:00'
					" . $searchSqlPerson . $prvSql . "
				), 0) - COALESCE((SELECT SUM(dac.Dac_Val) FROM det_ant_ccpp AS dac
					INNER JOIN anticipos_proveedores AS ant ON dac.Atp_Cod = ant.Atp_Cod
					INNER JOIN proveedore AS prv ON prv.Prv_Cod = ant.Prv_Cod
					INNER JOIN persona AS prs ON prs.Prs_Cod = prv.Prs_Cod
					INNER JOIN comprobantes AS ccon ON dac.Com_Cod = ccon.Com_Cod
					WHERE prv.Emp_Cod = " . $emp . " AND ant.Atp_Est IN " . $estInSaldoIni . "
					AND ccon.Com_Fec < '" . $fec_ini . " 00:00:00'
					AND IFNULL(ccon.Com_Est,'A') <> 'I'
					" . $searchSqlPerson . $prvSql . "
				), 0)
			) AS saldo_ini";
			return $sql;

	}
	return $sql;
}
