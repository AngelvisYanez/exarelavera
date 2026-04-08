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

function sentencias_anticipo_cli($id, $Par_Sql)
{
	switch ($id) {
		//sentencia para obtener todos los proveedores registrados de la empresa
		case 1:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(persona.Prs_Ced LIKE '%$Par_Sql[searchCli]%')";
			} else {
				$search = "(CONCAT(persona.Prs_Nom, ' ',persona.Prs_Ape)) LIKE '%$Par_Sql[searchCli]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(cliente.Cli_Cod) AS total" : " cliente.Cli_Cod,persona.Prs_Cod,persona.Prs_Ced,IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom,concat(persona.Prs_Nom, ' ', persona.Prs_Ape)) as nombre,persona.Prs_Dir";
			$sql = "SELECT $campos
							FROM persona,cliente
							WHERE  $search AND cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]' AND
							cliente.Prs_Cod = persona.Prs_Cod AND
							cliente.Cli_Est = 'A'
							$Par_Sql[limits];";
			return $sql;
			//sentencia para obtener los tipos de pago para anticipos
		case 2:
			$sql = "SELECT Pag_Cod, Pag_Des, Pag_Abr FROM tipos_pago
						WHERE
							Pag_Abr='EFE'
							OR Pag_Abr='CHE'
							OR Pag_Abr='TRF'
							OR Pag_Abr='DEP'
							OR Pag_Abr='NDC'
							OR Pag_Abr='OTR';";
			return $sql;
			//para seleccionar el plande cuentas correspondiente a los pagos de anticipos a clientes
		case 3:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
	          FROM det_plan, tipo_param, plan_param
	          WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
						AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						AND tipo_param.Tpa_Abr='ANC'
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
			ChromePhp::log("Caso 4" . $sql);

			return $sql;
			//sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
		case 5:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des,bancos.Bak_Cod, bancos.Bak_Des
						FROM det_plan, tipo_param, plan_param, bancos
						WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
							and bancos.Bak_Est = 'A'
							AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
							AND tipo_param.Tpa_Abr='CCH'
							AND det_plan.Pld_Est='A' AND
							det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]');";
			// echo $sql;
			return $sql;
			//seleccionamos los cheques de ese banco
		case 6:
			$sql = "SELECT cheques_ext.Che_Num FROM cheques_ext WHERE cheques_ext.Bak_Cod = '$Par_Sql[Bak_Cod]' AND cheques_ext.Cli_Cod = '$Par_Sql[Cli_Cod]';";
			// echo $sql;
			return $sql;
			//seleccionar los tipos de asiento para anticipos de clientes
		case 7:
			$sql = "SELECT * from tipo_asien where Tia_Ini = 'I' order by Tia_Abr;";
			// echo $sql;
			return $sql;
			//seleccionar el rango de fechas  para el periodo contable
		case 8:
			$sql = "SELECT min(Pec_Fei) as minimo, max(Pec_Fef) as maximo from perio_cont
							where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//sentencia para obtener los planes de cuenta de tipo de talle
		case 9:
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
			//sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
		case 10:
			$sql = "SELECT Pla_Cod, Pec_Cod, Pec_Fei from perio_cont
						where ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) and
						perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//insertar un registro en la tabla comprobantes
		case 11:
			$sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Est,Usu_Cod,Com_Gen)
							VALUES($Par_Sql[Pec_Cod], null, $Par_Sql[Cli_Cod], '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', '$Par_Sql[Com_Con]', 'I', '$Par_Sql[Com_Val]','$Par_Sql[Com_Con]', null, $Par_Sql[Tia_Cod], 'A', '$_SESSION[Ses_Usu_Cod]','A');";
			//echo $sql;
			return $sql;
			//insertar un registro en la tabla anticipos_clientes
		case 12:
			$sql = "INSERT INTO anticipos_clientes (Ant_Fec, Ant_Val, Ant_Est, Ant_Doc, Ant_Obs, Com_Cod, Cli_Cod, Ant_Tip)
							VALUES('$Par_Sql[Ant_Fec]', $Par_Sql[Ant_Val], 'A', '$Par_Sql[Ant_Doc]', '$Par_Sql[Ant_Obs]', $Par_Sql[Com_Cod], $Par_Sql[Cli_Cod], 'M');";
			// echo $sql;
			return $sql;
		case 13:
			$sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
							VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', 'ANTICIPO A CLIENTES', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";
			//echo $sql;
			return $sql;
			//insertar un registro en la tabla pagos_anticipo_proveedores
		case 14:
			$sql = "INSERT INTO pag_anticipo_cli (Pac_Cto, Pac_Ctd, Pac_Val, Ant_Cod, Che_Cod, Pac_Obs, Pac_Num, Pag_Cod, Asi_Cod)
							VALUES('$Par_Sql[Pac_Cto]', '$Par_Sql[Pac_Ctd]', '$Par_Sql[Pac_Val]', $Par_Sql[Ant_Cod], $Par_Sql[Che_Cod], '$Par_Sql[Pac_Obs]', '$Par_Sql[Pac_Num]', $Par_Sql[Pag_Cod], $Par_Sql[Asi_Cod] );";
			//echo $sql;
			return $sql;
			//insertar un registro en la tabla cheques_ext
		case 15:
			$sql = "INSERT INTO cheques_ext (Bak_Cod, Cli_Cod, Che_Cta, Che_Num, Che_Fec, Che_Val, Che_Obs, Che_Cli)
						VALUES('$Par_Sql[Bak_Cod]', '$Par_Sql[Cli_Cod]', '$Par_Sql[Che_Cta]', '$Par_Sql[Che_Num]', '$Par_Sql[Che_Fec]', '$Par_Sql[Che_Val]', '$Par_Sql[Che_Obs]', '$Par_Sql[Che_Cli]');";
			//echo $sql;
			return $sql;
			//obtener todos los periodos contables de la empresa
		case 16:
			$sql = "SELECT Pec_Cod, year(Pec_Fei) as anio, Pec_Fei, Pec_Fef  from perio_cont
					where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
					order by Pec_Fei desc;";
			// echo $sql;
			return $sql;
		case 17:
			if ($Par_Sql['op_opciones'] == "c") {
				$search = "(prs.Prs_Ced LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "(CONCAT(prs.Prs_Nom, ' ',prs.Prs_Ape)) LIKE '%$Par_Sql[search]%'";
			}

			$contar_regs = "(SELECT cli.Cli_Cod from cliente as cli inner join anticipos_clientes as ant on (cli.Cli_Cod=ant.Cli_Cod) inner join  persona as prs on (prs.Prs_Cod=cli.Prs_Cod)
								where $search and cli.Emp_Cod='$_SESSION[Ses_Emp_Cod]' and (ant.Ant_Est ='A' OR ant.Ant_Est ='U') and
								(ant.Ant_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]') group by cli.Cli_Cod)";

			if (empty($Par_Sql['limits'])) {
				$sql = "SELECT COUNT(*) as total FROM $contar_regs as regscount";
			} else {
				$sql = "SELECT
								cli.Cli_Cod, prs.Prs_Ced,prs.Prs_Dir, concat(prs.Prs_Nom, ' ', prs.Prs_Ape) as nombre,
								ABS((
										select sum(pga.Pac_Val)	from pag_anticipo_cli as pga inner join anticipos_clientes as ant2 on (ant2.Ant_Cod=pga.Ant_Cod)
										where (ant2.Ant_Est ='U' or ant2.Ant_Est ='A') and ant2.Cli_Cod=cli.Cli_Cod and pga.Che_Cod not in(select cheques_ext.Che_Cod from cheques_ext where cheques_ext.Che_Cod=pga.Che_Cod and cheques_ext.Che_Est='P')
									)-(
										select IF(SUM(dca.Ddc_Val) is null,0,SUM(dca.Ddc_Val)) AS tot_dac from det_ant_cccc as dca
										inner join anticipos_clientes as ant3 on(ant3.Ant_Cod=dca.Ant_Cod) where (ant3.Ant_Est ='U' or ant3.Ant_Est ='A') and ant3.Cli_Cod = cli.Cli_Cod
									)) AS tot_anti
							from cliente as cli inner join anticipos_clientes as ant on (cli.Cli_Cod=ant.Cli_Cod) inner join  persona as prs on (prs.Prs_Cod=cli.Prs_Cod)
							where $search and
								cli.Emp_Cod='$_SESSION[Ses_Emp_Cod]' and
								(ant.Ant_Est ='A' OR ant.Ant_Est ='U') and
								(ant.Ant_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')
							group by cli.Cli_Cod $Par_Sql[limits];";
			}
			//echo $sql;
			return $sql;
		case 18:
			$sql = "SELECT
							CONCAT(tas.Tia_Abr, '-', MONTH(com.Com_Fec), '-', com.Com_Num) AS codigo_compro,com.Com_Num,
							tas.Tia_Cod, ant.Com_Cod, ant.Ant_Cod,
							if(sum(pga.Pac_Val) is null,0,sum(pga.Pac_Val)) as Ant_Val,
							ant.Ant_Est, concat('ANT - ',ant.Ant_Doc)as Ant_num,Ant_Fec, ant.Ant_Obs,
							(select count(pga2.Pac_Cod) from pag_anticipo_cli as pga2 where pga2.Ant_Cod=ant.Ant_Cod) as cnt_pagos,
							ABS(
								if(sum(pga.Pac_Val) is null,0,sum(pga.Pac_Val))-(select IF(SUM(det_ant_cccc.Ddc_Val) is null,0,SUM(det_ant_cccc.Ddc_Val)) AS tot_dac
								from det_ant_cccc where det_ant_cccc.Ant_Cod = ant.Ant_Cod)
							) AS tot_sald
						FROM anticipos_clientes as ant
							inner join comprobantes as com on (com.Com_Cod = ant.Com_Cod)
							inner join tipo_asien as tas on (tas.Tia_Cod = com.Tia_Cod)
							left join pag_anticipo_cli as pga on (pga.Ant_Cod = ant.Ant_Cod and pga.Che_Cod not in(select cheques_ext.Che_Cod from cheques_ext where cheques_ext.Che_Cod=pga.Che_Cod and cheques_ext.Che_Est='P'))
						WHERE
							(ant.Ant_Est ='A' OR ant.Ant_Est ='U' OR ant.Ant_Est ='C') and
							(ant.Ant_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]') and
							ant.Cli_Cod='$Par_Sql[Cli_Cod]'
						GROUP BY ant.Ant_Cod
						order by ant.Ant_Cod";
			//echo $sql;
			return $sql;
			//obtener el ultimo anticipo de clientes
		case 19:
			$sql = "SELECT if(max(Ant_Cod) is null,0,max(Ant_Cod)) as sig from anticipos_clientes,cliente
					where anticipos_clientes.Cli_Cod = cliente.Cli_Cod and cliente.Emp_Cod='$_SESSION[Ses_Emp_Cod]';";
			return $sql;
			//obtener el ultimo valor de ant_doc
		case 20:
			$sql = "SELECT Ant_Doc from anticipos_clientes where Ant_Cod=$Par_Sql[0];";
			return $sql;
			//obtener los asientos relacionados al comprobante de un anticipo
		case 21:
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
							order by Asi_Deh desc;";
			// echo $sql;
			return $sql;
			//obtener los cheques de un anticipo
		case 22:
			$sql = "SELECT
						asi.Asi_Cod,
						pga.Pac_Cod,asi.Pld_Cod,che.Che_Cod,che.Bak_Cod,che.Cli_Cod,che.Che_Num,
						che.Che_Cta,che.Che_Fec,che.Che_Val,che.Che_Obs,che.Che_Est
					from cheques_ext as che
						inner join pag_anticipo_cli as pga on (pga.Che_Cod=che.Che_Cod)
						inner join asientos as asi on (asi.Asi_Cod=pga.Asi_Cod)
					where pga.Ant_Cod=$Par_Sql[Ant_Cod];";
			return $sql;
		case 23:
			$sql = "UPDATE anticipos_clientes, comprobantes
					SET anticipos_clientes.Ant_Est='I', comprobantes.Com_Est='I'
					WHERE anticipos_clientes.Com_Cod=$Par_Sql[0] and comprobantes.Com_Cod=$Par_Sql[0];";
			return $sql;
			// sentencia para obtenmer los asientos de un determinado anticipo
		case 24:
			$sql = "SELECT
						pdt.Pld_Cdc,pdt.Pld_Cod,pdt.Pld_Des,
						pga.Pac_Cod,pga.Pac_Cto,pga.Pac_Ctd,pga.Pac_Num,
						tpg.Pag_Abr,tpg.Pag_Des,tpg.Pag_Cod,
						che.Che_Cod,che.Che_Fec,che.Che_Num,che.Bak_Cod,che.Che_Est,
						asi.Asi_Cod,asi.Asi_Deh,Asi_Con,asi.Pld_Cod,asi.Asi_Glo,
						if(asi.Asi_Deh='D',asi.Asi_Val,'') as Debe,if(asi.Asi_Deh='H',asi.Asi_Val,'') as Haber
					from asientos as asi
						inner join comprobantes as com on (com.Com_Cod=asi.Com_Cod)
						inner join det_plan as pdt on (pdt.Pld_Cod=asi.Pld_Cod)
						left join pag_anticipo_cli as pga on (pga.Asi_Cod=asi.Asi_Cod)
						left join tipos_pago as tpg on (tpg.Pag_Cod=pga.Pag_Cod)
						left join cheques_ext as che on (che.Che_Cod=pga.Che_Cod)
					where com.Com_Cod=$Par_Sql[Com_Cod] order by Asi_Deh desc;";
			return $sql;
		case 25:
			$sql = "SELECT * from tipo_asien where Tia_Abr ='DG';";
			return $sql;
			//sentencia para obtener el periodo contable de la fecha en la que se realiza la consulta
		case 26:
			$sql = "SELECT Pla_Cod, Pec_Cod, Pec_Fei from perio_cont
					where ('$Par_Sql[0]' BETWEEN Pec_Fei AND Pec_Fef) and
					perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//Protestar un cheque del anticipo
		case 27:
			$sql = "UPDATE cheques_ext
					SET cheques_ext.Che_Est='P'
				WHERE cheques_ext.Che_Cod='$Par_Sql[Che_Cod]';";
			//echo $sql;
			return $sql;
		case 28:
			$sql = "UPDATE pag_anticipo_cli
							SET pag_anticipo_cli.Pac_Obs='$Par_Sql[Pac_Obs]'
						WHERE Pac_Cod='$Par_Sql[Pac_Cod]';";
			//echo $sql;
			return $sql;
		case 29:
			$sql = "UPDATE asientos
							SET asientos.Asi_Glo = '$Par_Sql[Asi_Glo]'
						WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			//echo $sql;
			return $sql;
			//actualizar el comprobante del anticipo
		case 30:
			$sql = "UPDATE comprobantes
							SET Pec_Cod='$Par_Sql[Pec_Cod]', Com_Num='$Par_Sql[Com_Num]', Com_Fec='$Par_Sql[Com_Fec]', Com_Con='$Par_Sql[Com_Con]',  Com_Val='$Par_Sql[Com_Val]', Com_Obs='$Par_Sql[Com_Obs]', Tia_Cod='$Par_Sql[Tia_Cod]'
						WHERE Com_Cod='$Par_Sql[Com_Cod]';";
			//echo $sql;
			return $sql;
			//modificar los valores del anticipo indicado
		case 31:
			$sql = "UPDATE anticipos_clientes
							SET Ant_Fec='$Par_Sql[Ant_Fec]', Ant_Val='$Par_Sql[Ant_Val]',  Ant_Obs='$Par_Sql[Ant_Obs]'
						WHERE Ant_Cod='$Par_Sql[Ant_Cod]';";
			//echo $sql;
			return $sql;
		case 32:
			$sql = "SELECT asi.Asi_Cod from asientos as asi
					where asi.Pld_Cod=$Par_Sql[Pld_Cod] and asi.Com_Cod=$Par_Sql[Com_Cod];";
			return $sql;
		case 33:
			$sql = "DELETE FROM asientos
						WHERE Asi_Cod='$Par_Sql[Asi_Cod]';";
			// echo $sql;
			return $sql;
		case 34:
			$sql = "DELETE FROM asientos
					where
						asientos.Asi_Cod not in (select pga.Asi_Cod from pag_anticipo_cli as pga inner join cheques_ext as che on(che.Che_Cod=pga.Che_Cod and che.Che_Est='P') where pga.Asi_Cod=asientos.Asi_Cod) and
						asientos.Com_Cod=$Par_Sql[Com_Cod];";
			//echo $sql;
			return $sql;
		case 35:
			$sql = "SELECT concat(prs.Prs_Nom,' ',prs.Prs_Ape) as nombre, prs.Prs_Ced, prs.Prs_Dir, prs.Prs_Tel from persona as prs inner join cliente as cli on (cli.Prs_Cod=prs.Prs_Cod) and cli.Cli_Cod=$Par_Sql[0];";
			return $sql;
		case 36:
			$sql = "SELECT ant.Ant_Val, ant.Ant_Doc,Ant_Fec from anticipos_clientes as ant where ant.Ant_Cod=$Par_Sql[0];";
			return $sql;
		case 37:
			$sql = "SELECT tpg.Pag_Abr, tpg.Pag_Cod, tpg.Pag_Des from pag_anticipo_cli as pga
						inner join tipos_pago as tpg on(tpg.Pag_Cod=pga.Pag_Cod)
					where pga.Ant_Cod = $Par_Sql[Ant_Cod]
					group by tpg.Pag_Cod order by tpg.Pag_Abr;";
			return $sql;
		case 38:
			$sql = "SELECT
						pga.Pac_Ctd,pga.Pac_Cto,pga.Pac_Val,pga.Pac_Num,
						che.Che_Num,pld.Pld_Des, pld.Pld_Cdc
					from pag_anticipo_cli as pga
						inner join asientos as asi on (asi.Asi_Cod=pga.Asi_Cod)
						inner join det_plan as pld on (pld.Pld_Cod=asi.Pld_Cod)
						left join cheques_ext as che on(che.Che_Cod=pga.Che_Cod)
					where pga.Ant_Cod = $Par_Sql[Ant_Cod] and pga.Pag_Cod=$Par_Sql[Pag_Cod];";
			return $sql;
		case 39:
			$sql = "SELECT suc.Suc_Dir,suc.Suc_Te1,ciu.Ciu_Des from sucursal as suc inner join ciudad as ciu on(ciu.Ciu_Cod=suc.Ciu_Cod) where suc.Suc_Cod=$Par_Sql[0];";
			return $sql;
			//Datos camaronera
		case 40:
			$sql = "SELECT * FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
			return $sql;

		case 41:
			if (!empty($Par_Sql[1])) {
				$Par_Sql[1] = " AND Num_Neg=$Par_Sql[1]";
			}
			$sql = "SELECT Cod_Neg,Num_Neg FROM nego_camaron WHERE Emp_Cod IN ($Par_Sql[0])  $Par_Sql[1]   AND Est_Neg = 'A' OR Est_Neg='P'";
			return $sql;
		case 42:
			$sql = "INSERT INTO nego_documentos(Cod_Neg, Cod_Doc, Abr_Doc,Tip_Prod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]','' );";
			return $sql;

		case 43: //Obtener los Emp_Cod de las empresas agrupadas
			$sql = "SELECT grupo_clientes.* FROM det_grup_empresas 
            INNER JOIN grupo_clientes ON grupo_clientes.Cod_Grup = det_grup_empresas.Cod_Group
            WHERE det_grup_empresas.Emp_Cod = $_SESSION[Ses_Emp_Cod] ";
			return $sql;
	}
	return $sql;
}
