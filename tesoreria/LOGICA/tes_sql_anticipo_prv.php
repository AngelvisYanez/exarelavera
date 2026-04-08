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
		case 1:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchPrv]%')";
			} else {
				$search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchPrv]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(proveedore.Prv_Cod) AS total" : " proveedore.Prv_Cod,	persona.Prs_Cod, persona.Prs_Ced, IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre, persona.Prs_Dir";
			$sql = "SELECT $campos
							FROM persona,proveedore
							WHERE  $search AND proveedore.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
							AND proveedore.Prs_Cod = persona.Prs_Cod
							AND proveedore.Prv_Est = 'A'
							$Par_Sql[limits];";;
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
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
	          FROM det_plan, tipo_param, plan_param
	          WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
						AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						AND tipo_param.Tpa_Abr='ANP'
						AND det_plan.Pld_Est='A' AND
	          det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//seleccionar plan de cuentas y No. de cuenta del banco para anticipos con cheque de la empresa
		case 4:
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
							from det_plan, banco
							where
								det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')
								and banco.Ban_Tip = '$Par_Sql[Ban_Tip]'
								and banco.Pld_Cod=det_plan.Pld_Cod;";
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
		case 14:
			$tot_anti = "ABS((SUM(anticipos_proveedores.Atp_Val))-(select
										IF(SUM(det_ant_ccpp.Dac_Val) is null,0,SUM(det_ant_ccpp.Dac_Val)) AS tot_dac
									from det_ant_ccpp, anticipos_proveedores
									where
										anticipos_proveedores.Atp_Est ='U' and
										det_ant_ccpp.Atp_Cod = anticipos_proveedores.Atp_Cod and
										anticipos_proveedores.Prv_Cod = proveedore.Prv_Cod)
									) AS tot_anti";

			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(prs.Prs_Ced LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "(CONCAT(prs.Prs_Nom, ' ',prs.Prs_Ape)) LIKE '%$Par_Sql[search]%'";
			}

			$contar_regs = "(SELECT 
									prv.Prv_Cod
								FROM proveedore as prv
									inner join persona as prs on(prs.Prs_Cod=prv.Prs_Cod)
									inner join anticipos_proveedores as ant on (ant.Prv_Cod=prv.Prv_Cod)
								WHERE  $search
									AND (ant.Atp_Est ='A' OR ant.Atp_Est ='U')
									AND prv.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
									AND (ant.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
								GROUP BY prv.Prv_Cod)";

			if (empty($Par_Sql['limits'])) {
				$sql = "SELECT COUNT(*) as total FROM $contar_regs as regscount";
			} else {
				$sql = "SELECT proveedore.Prv_Cod,anticipos_proveedores.Atp_Cod,Prs_Ced,
						  concat(Prs_Nom,' ',Prs_Ape)as nombre,Prs_Dir,Atp_Est,anticipos_proveedores.Atp_Fec,
						  sum(anticipos_proveedores.Atp_Val) AS Atp_Val,
						  round((sum(anticipos_proveedores.Atp_Val)-sum(det_ant_ccpp.Dac_Val)),2) AS tot_anti 
						FROM
						  anticipos_proveedores
						  LEFT JOIN det_ant_ccpp ON (anticipos_proveedores.Atp_Cod = det_ant_ccpp.Atp_Cod)
						  INNER JOIN proveedore ON (anticipos_proveedores.Prv_Cod = proveedore.Prv_Cod)
						  INNER JOIN persona AS prs ON (proveedore.Prs_Cod = prs.Prs_Cod)
						  INNER JOIN comprobantes ON (anticipos_proveedores.Com_Cod = comprobantes.Com_Cod )		

						 	
						WHERE  $search
								AND (Atp_Est ='A' OR Atp_Est ='U')
								AND Emp_Cod='$_SESSION[Ses_Emp_Cod]'
								AND (Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]') 
						GROUP BY  anticipos_proveedores.Atp_Cod $Par_Sql[limits];";



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
		case 15:
			$sql = "SELECT
								CONCAT(tas.Tia_Abr, '-', MONTH(com.Com_Fec), '-', com.Com_Num) AS codigo_compro,
								tas.Tia_Cod,com.Com_Num,
								ant.Com_Cod,ant.Atp_Cod,ant.Atp_Fec,ant.Atp_Est,ant.Atp_Obs,
								if(sum(pga.Pap_Val) is null,0,sum(pga.Pap_Val)) as Atp_Val,
								(select count(pga2.Pap_Cod) from pago_anticipo_proveedores as pga2 where pga2.Atp_Cod=ant.Atp_Cod) as cnt_pagos,
								ABS(
									if(sum(pga.Pap_Val) is null,0,sum(pga.Pap_Val))-(select IF(SUM(det_ant_ccpp.Dac_Val) is null,0,SUM(det_ant_ccpp.Dac_Val)) AS tot_dac
									from det_ant_ccpp where det_ant_ccpp.Atp_Cod = ant.Atp_Cod)
								) AS tot_sald
							FROM anticipos_proveedores as ant
								inner join comprobantes as com on (com.Com_Cod = ant.Com_Cod)
								inner join tipo_asien as tas on (tas.Tia_Cod = com.Tia_Cod)
								left join pago_anticipo_proveedores as pga on (pga.Atp_Cod = ant.Atp_Cod and pga.Asi_Cod not in (select cheques.Asi_Cod from cheques where cheques.Asi_Cod = pga.Asi_Cod and cheques.Che_Est = 'P'))
							WHERE
								pga.Pap_Est = 'A' and
								(ant.Atp_Est ='A' OR ant.Atp_Est ='U') and
								(ant.Atp_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]') AND
								ant.Prv_Cod='$Par_Sql[Prv_Cod]'
							GROUP BY ant.Atp_Cod
							order by ant.Atp_Cod;";
			//echo $sql;
			return $sql;
			//seleccionar todos los asientos y pagos de un anticipo
		case 16:
			$sql = "SELECT
							che.Che_Est,
							pdt.Pld_Cdc,pdt.Pld_Cod,pdt.Pld_Des,
							pga.Pap_Cod,pga.Pap_Cto,pga.Pap_Ctd,
							tpg.Pag_Cod,tpg.Pag_Abr,tpg.Pag_Des,
							che.Che_Cod,che.Che_Fec,che.Che_Num,che.Ban_Cod,
							asi.Asi_Cod,asi.Asi_Deh,Asi_Con,asi.Pld_Cod,asi.Asi_Glo,
							if(asi.Asi_Deh='D',asi.Asi_Val,'') as Debe,if(asi.Asi_Deh='H',asi.Asi_Val,'') as Haber
						from asientos as asi
							inner join comprobantes as com on (com.Com_Cod=asi.Com_Cod)
							inner join det_plan as pdt on (pdt.Pld_Cod=asi.Pld_Cod)
							left join pago_anticipo_proveedores as pga on (pga.Asi_Cod=asi.Asi_Cod)
							left join tipos_pago as tpg on (tpg.Pag_Cod=pga.Pag_Cod)
							left join cheques as che on (che.Asi_Cod=pga.Asi_Cod)
						where com.Com_Cod=$Par_Sql[Com_Cod] order by Asi_Deh;";
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

	}
	return $sql;
}
