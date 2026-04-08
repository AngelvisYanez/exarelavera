<?php

/**
 * Retorna consulta sql a ejecutarse
 *
 * @author Edison Moya
 * @version 1.0
 * Fecha de actualización:	2012-11-27
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 *
 * @package tesoreria.LOGICA
 */

function sentencias_cccc($id, $Par_Sql) {
	switch ($id) {
			//sentencia para obtener todos los clientes registrados de la empresa
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
			//sentencia para obtener todos los abonos de proveedores registrados de la empresa
		case 2:
			$hoy = date("Y-m-d");
			//en caso de seleccionar el filtro de vencimiento
			if ($Par_Sql['por_peri'] == "s") {
				$concat = "AND (caja_aper.Caj_Fec BETWEEN '$Par_Sql[txt_fec_ini]' AND '$Par_Sql[txt_fec_fin]')";
			} else {
				if ($Par_Sql['sel_ven'] == 1) {
					$concat = "";
				}
				if ($Par_Sql['sel_ven'] == 2) {
					$Fec_Fin = fechas_futuras($hoy, 30);
					$concat = " AND Cpc_Ven BETWEEN '" . $hoy . "' AND '" . $Fec_Fin . "'";
				}
				if ($Par_Sql['sel_ven'] == 3) {
					$val = 60;
					$Fec_Fin = fechas_futuras($hoy, 60);
					$concat = " AND Cpc_Ven BETWEEN '" . $hoy . "' AND '" . $Fec_Fin . "'";
				}
				if ($Par_Sql['sel_ven'] == 4) {
					$Fec_Fin = fechas_futuras($hoy, 90);
					$concat = " AND Cpc_Ven > '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 5) {
					$Fec_Fin = fechas_futuras($hoy, -30);
					$concat = " AND Cpc_Ven BETWEEN '" . $Fec_Fin . "' AND '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 6) {
					$Fec_Fin = fechas_futuras($hoy, -60);
					$concat = " AND Cpc_Ven BETWEEN '" . $Fec_Fin . "' AND '" . $hoy . "'";
				}
				if ($Par_Sql['sel_ven'] == 7) {
					$Fec_Fin = fechas_futuras($hoy, -90);
					$concat = " AND Cpc_Ven < '" . $hoy . "'";
				}

				//order

				if ($Par_Sql['order'] != '') {
					$order = " ORDER BY " .$Par_Sql['order'];
				}
			}

			$sql = "SELECT det_plan.Pld_Cod,Pld_Cdc,Pld_Des,tipo_compr.Tic_Des,Prs_Nom,Prs_Ape,cliente.Cli_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS cliente_n, 
			persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Cod, ventas.Vet_Obs, ccpp_cobrar.Cpc_Cod, 
			caja_aper.Caj_Fec, CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num, 
			ccpp_cobrar.Cpc_Ven, ccpp_cobrar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
			asientos.Pld_Cod,Pld_Cdc,Pld_Des,
			CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
			CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',
			CAST(comprobantes.Com_Num AS char)) AS Com_Codigo ,IF(SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0))=Asi_Val,'Pagado',
			IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento ,
			IF(SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Cpc_Val,2),0))) AS Abono
			FROM cliente
				INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
				INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
				INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod
				INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
				INNER JOIN ccpp_cobrar ON (ventas.Vet_Cod = ccpp_cobrar.Vet_Cod)
				INNER JOIN comprobantes ON (ccpp_cobrar.Com_Cod = comprobantes.Com_Cod)
				INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
				INNER JOIN tipo_compr ON tipo_compr.Tic_Cod= ventas.Tic_Cod
				INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
				INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
				INNER JOIN ccpp_cliente ON (asientos.Pld_Cod = ccpp_cliente.Pld_Cod)
				INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				LEFT JOIN det_ccpp_c ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod
				LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_c.Com_Cod),persona
			WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod
				AND asientos.Com_Cod= comprobantes.Com_Cod AND
				asientos.Asi_Deh= 'D' AND
				(ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
				(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
				cliente.Cli_Cod = $Par_Sql[Cli_Cod] $concat AND
				sucursal.Emp_Cod=$_SESSION[Ses_Emp_Cod]
				GROUP BY ventas.Vet_Cod  $order ";
			//echo $sql;
			
		//	GROUP BY ventas.Vet_Cod  ORDER BY  ccpp_cobrar.Cpc_Ven ";
			return $sql;

		case 222:
			if ($Par_Sql['por_peri'] == "s") {
				$concat = " AND (perio_cont.Pec_Cod = $Par_Sql[sel_per]) ";
			} else {
				$concat = "";
			}

			$sql = "SELECT cliente.Cli_Cod,
          				asientos.Asi_Val AS Total,
						CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente,   
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
						CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
						,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
						,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
						,IF(SUM(IF(comp2.Com_Est='A' && ROUND(ROUND(Cpc_Val,2),2)!=Asi_Val,ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono,
					
						(asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))) as Saldo

						,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0, (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS PorVencer
						,IF(DATEDIFF(CURDATE(),Cpc_Ven)>=$Par_Sql[rango1Ini] && DATEDIFF(CURDATE(),Cpc_Ven)<=$Par_Sql[rango1Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS Rango1
						,IF(DATEDIFF(CURDATE(),Cpc_Ven)>=$Par_Sql[rango2Ini] && DATEDIFF(CURDATE(),Cpc_Ven)<=$Par_Sql[rango2Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS Rango2
						,IF(DATEDIFF(CURDATE(),Cpc_Ven)>=$Par_Sql[rango3Ini] && DATEDIFF(CURDATE(),Cpc_Ven)<=$Par_Sql[rango3Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS Rango3
						,IF(DATEDIFF(CURDATE(),Cpc_Ven)>$Par_Sql[rango3Fin], (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS Rango4
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
						WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod
							AND asientos.Com_Cod= comprobantes.Com_Cod AND
							asientos.Asi_Deh= 'D'
							$concat AND
							(ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
							(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
							sucursal.Emp_Cod= $_SESSION[Ses_Emp_Cod]
							GROUP BY ventas.Vet_Cod ORDER BY persona.Prs_Ape, Vet_Num ASC";
			return $sql;

		case 558:
			// Sin filtro de periodo - muestra historial acumulado con rangos dinámicos
			// Construir las condiciones de rangos dinámicamente
			$rangosSQL = "";
			$numRangos = isset($Par_Sql['numRangos']) ? intval($Par_Sql['numRangos']) : 0;
			
			// Si no hay rangos definidos, usar valores por defecto
			if ($numRangos <= 0) {
				$numRangos = 4;
				// Valores por defecto
				$Par_Sql['rango1Ini'] = 1;
				$Par_Sql['rango1Fin'] = 30;
				$Par_Sql['rango2Ini'] = 31;
				$Par_Sql['rango2Fin'] = 60;
				$Par_Sql['rango3Ini'] = 61;
				$Par_Sql['rango3Fin'] = 90;
				$Par_Sql['rango4Ini'] = 91;
				$Par_Sql['rango4Fin'] = 120;
			}
			
			for ($i = 1; $i <= $numRangos; $i++) {
				$rangoIni = isset($Par_Sql['rango' . $i . 'Ini']) ? intval($Par_Sql['rango' . $i . 'Ini']) : 0;
				$rangoFin = isset($Par_Sql['rango' . $i . 'Fin']) ? intval($Par_Sql['rango' . $i . 'Fin']) : 0;
				if ($rangoIni > 0 && $rangoFin > 0) {
					$rangosSQL .= ",IF(DATEDIFF(CURDATE(),Cpc_Ven)>=$rangoIni && DATEDIFF(CURDATE(),Cpc_Ven)<=$rangoFin, (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS Rango$i\n\t\t\t\t\t";
				}
			}
			
			// Rango final para valores mayores al último rango
			$ultimoRangoFin = isset($Par_Sql['rango' . $numRangos . 'Fin']) ? intval($Par_Sql['rango' . $numRangos . 'Fin']) : 0;
			if ($ultimoRangoFin > 0) {
				$rangosSQL .= ",IF(DATEDIFF(CURDATE(),Cpc_Ven)>$ultimoRangoFin, (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS RangoUltimo\n\t\t\t\t\t";
			}
			
			$sql = "SELECT cliente.Cli_Cod,
          				asientos.Asi_Val AS Total,
						CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente,   
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
						CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
						,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
						,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT(CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),'Vencido')) AS vencimiento
						,IF(SUM(IF(comp2.Com_Est='A' && ROUND(ROUND(Cpc_Val,2),2)!=Asi_Val,ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono,
						(asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))) as Saldo
						,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0, (asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))), '0.00') AS PorVencer
						$rangosSQL
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
						WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod
							AND asientos.Com_Cod= comprobantes.Com_Cod AND
							asientos.Asi_Deh= 'D'
							AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
							(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
							sucursal.Emp_Cod= $_SESSION[Ses_Emp_Cod]
							GROUP BY ventas.Vet_Cod ORDER BY persona.Prs_Ape, Vet_Num ASC";
			return $sql;

		case 333:
			// Sin filtro de periodo - muestra historial acumulado
			$sql = "SELECT cliente.Cli_Cod,
						@rownum:=@rownum+1 as indx,
						Vet_Num,
						caja_aper.Caj_Fec as Fecha,
						Cpc_Ven as FechaVenc,
          				asientos.Asi_Val AS Total,
						CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Cliente,   
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,
						CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Codigo 
						,IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))=Asi_Val,'Pagado'
						,IF(DATEDIFF(Cpc_Ven,CURDATE())>=0,CONCAT('PV ',CAST(DATEDIFF(Cpc_Ven,CURDATE()) AS char),' d&iacute;as'),CONCAT('Vencido ',CAST(DATEDIFF(CURDATE(),Cpc_Ven) AS char),' d&iacute;as'))) AS vencimiento
						,IF(SUM(IF(comp2.Com_Est='A' && ROUND(ROUND(Cpc_Val,2),2)!=Asi_Val,ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0))) AS Abono,
						(asientos.Asi_Val - IF(SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(ROUND(Cpc_Val,2),2),0)))) as Saldo
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
						WHERE cliente.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_cobrar.Com_Cod
							AND asientos.Com_Cod= comprobantes.Com_Cod AND
							asientos.Asi_Deh= 'D'
							AND (ventas.vet_Est='A' OR ventas.vet_Est='E')  AND
							(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E') AND
							sucursal.Emp_Cod = $_SESSION[Ses_Emp_Cod] AND
              				cliente.Cli_Cod = $Par_Sql[0]
							GROUP BY ventas.Vet_Cod ORDER BY vencimiento DESC, Vet_Num ASC";
			return $sql;

		case 3:
			$sql = "SELECT
						@rownum:=@rownum+1 as indx,
						if(
							comprobantes.Com_Cod = (select dtc.Com_Cod from det_ccpp_c as dtc inner join cheq_det_ccpp as chd on (chd.Dcc_Cod=dtc.Dcc_Cod) inner join cheques_ext as che on (che.Che_Cod=chd.Che_Cod) where che.Che_Est = 'P' and dtc.Cpc_Cod = 31)
							or det_ccpp_c.Cpc_Val <0,
							'n','s'
						) as reg_editable,
						Dcc_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Pag_Cod,det_ccpp_c.Cpc_Cod,det_ccpp_c.Com_Cod,
						Cpc_Fec,Cpc_Val,Cpc_Obs,tipos_pago.For_Cod,Pag_Des,comprobantes.*,tipos_pago.Pag_Abr,
						CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as Com_Codigo
					FROM det_ccpp_c
						INNER JOIN tipos_pago ON tipos_pago.Pag_Cod=det_ccpp_c.Pag_Cod
						INNER JOIN forma_pago ON tipos_pago.For_Cod=forma_pago.For_Cod
						INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
						INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod,(SELECT @rownum:=0) r
					WHERE Cpc_Cod='$Par_Sql[0]' AND comprobantes.Com_Est='A';";
			return $sql;
		case 4:
			$sql = "SELECT Pec_Cod, year(Pec_Fei) as anio, Pec_Fei, Pec_Fef  from perio_cont
					where perio_cont.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
					order by Pec_Fei desc;";
			// echo $sql;
			return $sql;
			//los periodos contables
		case 5:
			$sql = "SELECT plan_cuenta.Pla_Cod, perio_cont.Pec_Cod, perio_cont.Pec_Fei, perio_cont.Pec_Fef, perio_cont.Pec_Est, Year(perio_cont.Pec_Fei) as priodo_m
					FROM plan_cuenta, perio_cont
					WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod='$_SESSION[Ses_Emp_Cod]'
					ORDER BY Year(perio_cont.Pec_Fei) DESC";
			return $sql;
			//retorna tipos de asiento
		case 6:
			if ($Par_Sql[0] == "") $Par_Sql[0] = " WHERE Tia_Tip='B' AND Tia_Est='A' ";
			else $Par_Sql[0] = " WHERE  Tia_Est='A' AND(Tia_Ini='I' OR Tia_Ini='D' )";
			$sql = "SELECT Tia_Cod,Tia_Abr, Tia_Des, Tia_Ini FROM tipo_asien $Par_Sql[0] ";
			return $sql;
		case 7:
			$sql = "SELECT * from tipos_pago WHERE For_Cod='1' and Pag_Abr!='NDC' and Pag_Abr!='RET';";
			return $sql;
		case 8: 

			if($Par_Sql[0]!="")  { $Par_Sql[0]= "det_plan.Pld_Cdc = '$Par_Sql[0]'  AND"; }

			$sql = " SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
					FROM plan_cuenta, det_plan, ccpp_cliente
					WHERE
						det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND
						plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' AND $Par_Sql[0]   /* det_plan.Pld_Cdc = '$Par_Sql[0]'  AND  */
						ccpp_cliente.Pld_Cod = det_plan.Pld_Cod;";
			return $sql;
			//sentencia para obtener los bacnos de cheques externos y la cuenta parametrizada para los mismos
		case 9:
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
		case 10:
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
						from det_plan, banco
						where
							det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')
							and banco.Ban_Tip = '$Par_Sql[Ban_Tip]'
							and banco.Pld_Cod=det_plan.Pld_Cod;";
			//ChromePhp::log($sql);
			return $sql;
			//sentencia que retorna todos los numero de cheques reguistrados de ese bancoy ese cliente
		case 11:
			$sql = "SELECT cheques_ext.Che_Num FROM cheques_ext WHERE cheques_ext.Bak_Cod = '$Par_Sql[Bak_Cod]' AND cheques_ext.Cli_Cod = '$Par_Sql[Cli_Cod]';";
			// echo $sql;
			return $sql;
		case 12:
			$sql = "SELECT asi.*, dpl.* from asientos as asi inner join det_plan as dpl on (dpl.Pld_Cod=asi.Pld_Cod) where asi.Com_Cod = $Par_Sql[Com_Cod] order by asi.Asi_Deh desc;";
			return $sql;
		case 13:
			$sql = "SELECT che.*, asi.* , bnc.Bak_Cod, bnc.Bak_Des from cheques_ext as che
						inner join bancos as bnc on (bnc.Bak_Cod=che.Bak_Cod)
						inner join cheq_det_ccpp as chd on (chd.Che_Cod=che.Che_Cod)
						inner join det_ccpp_c as dcc on (dcc.Dcc_Cod=chd.Dcc_Cod)
						inner join asientos as asi on (asi.Asi_Cod=dcc.Asi_cod)
					where dcc.Com_Cod=$Par_Sql[Com_Cod] group by asi.Asi_Cod;";
			return $sql;
			//sentencia para obtener los planes de cuenta de tipo detalle
		case 14:
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
			//para seleccionar el plande cuentas correspondiente a los pagos de anticipos a clientes
		case 15:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
	          FROM det_plan, tipo_param, plan_param
	          WHERE  det_plan.Pld_Cod=plan_param.Pld_Cod
						AND plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						AND tipo_param.Tpa_Abr='ANC'
						AND det_plan.Pld_Est='A' AND
	          det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'));";
			// echo $sql;
			return $sql;
			//cuenta correspondiente a cruce de cuentas
		case 16:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des
						from plan_cuenta, det_plan, ccpp_prove
						where
							det_plan.Pla_Cod=plan_cuenta.Pla_Cod and
							plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' and
							ccpp_prove.Pld_Cod = det_plan.Pld_Cod;";
			return $sql;
			//seleccionar plan de cuentas y No. de cuenta del banco para anticipos con cheque de la empresa
		case 17:
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, banco.Ban_Cue
					from det_plan, banco
					where
						det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')
						and banco.Ban_Tip = '$Par_Sql[Ban_Tip]'
						and banco.Pld_Cod=det_plan.Pld_Cod;";
			return $sql;
		case 18:
			$sql = "SELECT if(sum(pga.Pac_Val) is null,0,sum(pga.Pac_Val)) as tot_anti from pag_anticipo_cli as pga
					inner join anticipos_clientes as ant2 on (ant2.Ant_Cod=pga.Ant_Cod) inner join comprobantes as com on (com.Com_Cod=ant2.Com_Cod)
					where ant2.Cli_Cod=$Par_Sql[Cli_Cod] and pga.Che_Cod not in(select cheques_ext.Che_Cod from cheques_ext where cheques_ext.Che_Cod=pga.Che_Cod and cheques_ext.Che_Est='P') and
					(ant2.Ant_Est ='U' or ant2.Ant_Est ='A') and
					com.Com_Est='A';";
			//echo $sql;
			return $sql;
			//detalles de anticipos por cliente
		case 1800:
			$sql = "select distinct pag_anticipo_cli.pac_cto as Cta,
			cheques_ext.che_num as Che, if(anticipos_clientes.ant_est='U', pag_anticipo_cli.pac_val-det_ant_cccc.ddc_val,pag_anticipo_cli.pac_val) as Val$,  
			bancos.bak_des as Bco, pag_anticipo_cli.pac_ctd as TraDep,
			case 
			     when pag_anticipo_cli.pag_cod=1 then 'Efectivo'
			     when pag_anticipo_cli.pag_cod=3 then 'Cheque'
			     when pag_anticipo_cli.pag_cod=8 then 'Transferencia'
			     when pag_anticipo_cli.pag_cod=9 then 'Deposito'
			     when pag_anticipo_cli.pag_cod=13 then 'Otros'
			end as Tipo
			from anticipos_clientes, pag_anticipo_cli
			left join cheques_ext on pag_anticipo_cli.che_cod=cheques_ext.che_cod
			left join bancos on cheques_ext.bak_cod=bancos.bak_cod
            		left join det_ant_cccc on pag_anticipo_cli.ant_cod=det_ant_cccc.ant_cod
			where anticipos_clientes.cli_cod=$Par_Sql[Cli_Cod]
			and anticipos_clientes.ant_est!='I'
			and anticipos_clientes.ant_cod=pag_anticipo_cli.ant_cod; ";
			return $sql;
			// total de detallaes de anticipo
		case 19:
			$sql = "SELECT IF(SUM(dca.Ddc_Val) is null,0,SUM(dca.Ddc_Val)) AS tot_dac from det_ant_cccc as dca
			inner join anticipos_clientes as ant3 on(ant3.Ant_Cod=dca.Ant_Cod) inner join comprobantes as com on (com.Com_Cod=ant3.Com_Cod)
			where ant3.Ant_Est ='U' and	ant3.Cli_Cod = $Par_Sql[Cli_Cod] and com.Com_Est='A';";
			//echo $sql;
			return $sql;
		case 20:
			$sql = "SELECT prv.Prv_Cod from cliente as cli inner join proveedore as prv on (prv.Prs_Cod=cli.Prs_Cod) and prv.Emp_Cod=$_SESSION[Ses_Emp_Cod]
					where cli.Cli_Cod=$Par_Sql[Cli_Cod] and cli.Emp_Cod=$_SESSION[Ses_Emp_Cod];";
			// echo $sql;
			return $sql;
		case 21:
			$sql = "SELECT ccpp_pagar.Cpp_Cod,asientos.Asi_Val,Cop_Num, Cop_Fec,CONCAT(Tia_Abr,'-',LPAD(month(comprobantes.Com_Fec),2,'0'),'-',comprobantes.Com_num)as Com_Num,CONCAT(Prs_Ape,' ',Prs_Nom)as nombre,
					IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0))) AS Abono,
					( asientos.Asi_Val-(IF(SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)) IS NULL,0,SUM(IF(comp2.Com_Est='A',ROUND(Pag_Val,2),0)))) ) as saldo
					FROM proveedore 
						INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
						INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
						INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
						INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
						INNER JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
						INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
						INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
						INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod) 
						INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
						LEFT JOIN det_ccpp_p ON ccpp_pagar.Cpp_Cod=det_ccpp_p.Cpp_Cod 
						LEFT JOIN comprobantes as comp2 ON (comp2.Com_Cod=det_ccpp_p.Com_Cod)
					WHERE asientos.Asi_Deh= 'H' AND (compras.Cop_Est='A' OR compras.Cop_Est='E') AND	(comprobantes.Com_Est='A' OR comprobantes.Com_Est='E' )
						AND proveedore.Prv_Cod = '$Par_Sql[Prv_Cod]' AND Emp_Cod=$_SESSION[Ses_Emp_Cod] GROUP BY compras.Cop_Cod HAVING saldo>0 ORDER by ccpp_pagar.Cpp_Ven;";
			return $sql;

			case 22:
				// $sql = "INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Gen,Com_Est,Usu_Cod)
				// 		VALUES($Par_Sql[Pec_Cod], $Par_Sql[Prv_Cod], $Par_Sql[Cli_Cod], '$Par_Sql[Com_Num]', '$Par_Sql[Com_Fec]', '$Par_Sql[Com_Con]', 'I', '$Par_Sql[Com_Val]',
				// 		'$Par_Sql[Com_Obs]', null, $Par_Sql[Tia_Cod], 'A', 'A', '$_SESSION[Ses_Usu_Cod]');";
				// echo $sql;
				
				$sql = "INSERT INTO comprobantes (
					Pec_Cod,Prv_Cod,Cli_Cod,Com_Num,Com_Fec,Com_Con,Com_Tip,
					Com_Val,Com_Obs,Com_Tipo,Tia_Cod,Com_Gen,Com_Est,Usu_Cod, Num_Doc
					) VALUES (
					" . $Par_Sql['Pec_Cod'] . ", " . $Par_Sql['Prv_Cod'] . ", " . $Par_Sql['Cli_Cod'] . ", 
					'" . $Par_Sql['Com_Num'] . "', '" . $Par_Sql['Com_Fec'] . "', '" . $Par_Sql['Com_Con'] . "', 
					'I', '" . $Par_Sql['Com_Val'] . "', '" . $Par_Sql['Com_Obs'] . "', NULL, 
					" . $Par_Sql['Tia_Cod'] . ", 'A', 'A', '" . $_SESSION['Ses_Usu_Cod'] . "', 
					" . (!empty($Par_Sql['Num_Doc']) ? $Par_Sql['Num_Doc'] : "NULL") . ");";
	
				return $sql;

		case 23:
			$sql = "INSERT INTO asientos (Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo)
					VALUES($Par_Sql[Com_Cod], '$Par_Sql[Asi_Deh]', '$Par_Sql[Asi_Val]', '$Par_Sql[Asi_Con]', $Par_Sql[Pld_Cod], '$Par_Sql[Asi_Glo]');";
			// echo $sql;
			return $sql;
		case 24:
			$sql = "INSERT INTO det_ccpp_c (Cpc_Cod, Com_Cod, Pag_Cod, Cpc_Fec, Cpc_Val, Cpc_Obs, Cpc_Est, Asi_cod,Cpc_Cdc)
					VALUES($Par_Sql[Cpc_Cod], $Par_Sql[Com_Cod], $Par_Sql[Pag_Cod], '$Par_Sql[Cpc_Fec]', '$Par_Sql[Cpc_Val]', '$Par_Sql[Cpc_Obs]', 'A', '$Par_Sql[Asi_Cod]', '$Par_Sql[Cpc_Cdc]');";
			return $sql;
		case 25:
			$sql = "SELECT
						(select if(sum(pga.Pac_Val) is null,0,sum(pga.Pac_Val)) from pag_anticipo_cli as pga where pga.Ant_Cod=ant.Ant_Cod) as Ant_Val,
						ant.Ant_Cod
					from anticipos_clientes as ant
						inner join comprobantes as com on (com.Com_Cod=ant.Com_Cod)
					where
						ant.Cli_Cod=$Par_Sql[Cli_Cod] and
						(ant.Ant_Est ='A' OR ant.Ant_Est ='U') and
						com.Com_Est='A' order by ant.Ant_Fec asc, ant.Ant_Est desc;";
			return $sql;
		case 26:
			$sql = "SELECT IF(SUM(dac.Ddc_Val) is null,0,SUM(dac.Ddc_Val)) AS tot_dac
					from det_ant_cccc as dac where dac.Ant_Cod = $Par_Sql[Ant_Cod];";
			return $sql;



		case 55:
			$sql = "SELECT dac.Ant_Cod
					from det_ant_cccc as dac where dac.Dcc_Cod = $Par_Sql[Dcc_Cod];";
			return $sql;

		case 56:
			$sql = "SELECT dac.Ddc_Cod
					from det_ant_cccc as dac 
					where dac.Ant_Cod = $Par_Sql[Ant_Cod]
					and dac.Dcc_Cod <> $Par_Sql[Dcc_Cod];";
			return $sql;

		case 27:
			$sql = "UPDATE anticipos_clientes
						SET Ant_Est='$Par_Sql[Ant_Est]'
						WHERE Ant_Cod='$Par_Sql[Ant_Cod]';";
			return $sql;

		case 57:
			$sql = "UPDATE pag_anticipo_cli
						SET Pac_Est='$Par_Sql[Pac_Est]'
						WHERE Ant_Cod='$Par_Sql[Ant_Cod]';";
			return $sql;

		case 58:
			$sql = "DELETE FROM det_ant_cccc
						WHERE Ant_Cod='$Par_Sql[Ant_Cod]'
						AND   Dcc_Cod='$Par_Sql[Dcc_Cod]';";
			return $sql;

		case 28:
			$sql = "INSERT INTO det_ant_cccc (Ddc_Val, Ddc_Obs, Ant_Cod, Dcc_Cod, Pac_Cod, Com_Cod)
					VALUES('$Par_Sql[Ddc_Val]', '$Par_Sql[Ddc_Obs]', $Par_Sql[Ant_Cod], $Par_Sql[Dcc_Cod], $Par_Sql[Pac_Cod], $Par_Sql[Com_Cod] );";
			// echo $sql;
			return $sql;
		case 29:
			$sql = "INSERT INTO det_ccpp_p (Cpp_Cod, Pag_Cod, Com_Cod, Pag_Fec, Pag_Val, Pag_Est, Pag_Obs, Asi_Cod)
						VALUES($Par_Sql[Cpp_Cod], $Par_Sql[Pag_Cod], $Par_Sql[Com_Cod], '$Par_Sql[Pag_Fec]', '$Par_Sql[Pag_Val]', 'A', '$Par_Sql[Pag_Obs]', '$Par_Sql[Asi_Cod]');";
			// echo $sql;
			return $sql;
		case 30:
			$sql = "INSERT INTO cheq_det_ccpp (Che_Cod, Dcc_Cod) VALUES($Par_Sql[Che_Cod], $Par_Sql[Dcc_Cod]);";
			return $sql;
		case 31:
			$sql = "INSERT INTO cheques_ext (Bak_Cod, Cli_Cod, Che_Cta, Che_Num, Che_Fec, Che_Val, Che_Obs, Che_Cli)
					VALUES($Par_Sql[Bak_Cod], $Par_Sql[Cli_Cod], '$Par_Sql[Che_Cta]', '$Par_Sql[Che_Num]', '$Par_Sql[Che_Fec]', '$Par_Sql[Che_Val]', '$Par_Sql[Che_Obs]', '$Par_Sql[Che_Cli]');";
			return $sql;
		case 32:
			$sql = "INSERT INTO cheq_det_ccpp (Che_Cod, Dcc_Cod) VALUES($Par_Sql[Che_Cod], $Par_Sql[Dcc_Cod]);";
			return $sql;
		case 33:
			$sql = "SELECT det_ccpp_c.Dcc_Cod,ventas.Vet_Num,cheques_ext.Che_Fec,cheques_ext.Che_Num,bancos.Bak_Des,cheques_ext.Che_Cta,
					det_ccpp_c.Cpc_Val,Cpc_Fec,det_ccpp_c.Cpc_Obs,Com_Fec,comprobantes.Com_Con,comprobantes.Num_Doc,tipos_pago.Pag_Des,persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,persona1.Prs_Nom as usuNom, persona1.Prs_Ape as usuApe,
					CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char)) as numCom
					FROM det_ccpp_c
					  LEFT OUTER JOIN cheq_det_ccpp ON (det_ccpp_c.Dcc_Cod = cheq_det_ccpp.Dcc_Cod)
					  LEFT OUTER JOIN cheques_ext ON (cheq_det_ccpp.Che_Cod = cheques_ext.Che_Cod)
					  INNER JOIN ccpp_cobrar ON (det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod)
					  LEFT OUTER JOIN bancos ON (cheques_ext.Bak_Cod = bancos.Bak_Cod)
					  INNER JOIN tipos_pago ON (det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod)
					  INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod)
					  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
					  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					  INNER JOIN comprobantes ON (det_ccpp_c.Com_Cod = comprobantes.Com_Cod)
					  INNER JOIN usuarios ON (comprobantes.Usu_Cod = usuarios.Usu_Cod)
					  INNER JOIN persona persona1 ON (usuarios.Prs_Cod = persona1.Prs_Cod)
					  INNER JOIN tipo_asien ON (comprobantes.Tia_Cod = tipo_asien.Tia_Cod)
					WHERE det_ccpp_c.Com_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		case 34:
			$sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
            sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
		case 35:
			$sql = "SELECT * from comprobantes where Com_Cod=$Par_Sql[0]";
			return $sql;
			//sentencia para obtener los valores de las facturas a editar
		case 36:
			$sql = "SELECT Cpc_Cod, sum(Cpc_Val) as Abono from det_ccpp_c where Com_Cod = $Par_Sql[Com_Cod] group by Cpc_Cod;";
			return $sql;
			//sentencia que debuelve los asientos de un determinado pago por lotes o individual
		case 37:
			$sql = "SELECT
						asi.Asi_Val,asi.Asi_Cod,
						tpg.Pag_Cod,tpg.Pag_Des,tpg.Pag_Abr,
						dtp.Pld_Cod, dtp.Pld_Cdc, dtp.Pld_Des,
						asi.Asi_Deh, asi.Asi_Con,asi.Asi_Glo, che.Che_Cod, che.Che_Fec, che.Che_Num, che.Che_Est, che.Bak_Cod, che.Che_Cta, che.Che_Cli
					from asientos as asi
						inner join det_plan as dtp on (dtp.Pld_Cod=asi.Pld_Cod)
						left join det_ccpp_c as dcc on (dcc.Asi_cod=asi.Asi_Cod)
						left join tipos_pago as tpg on (tpg.Pag_Cod=dcc.Pag_Cod)
						left join cheq_det_ccpp as chd on (chd.Dcc_Cod=dcc.Dcc_Cod)
						left join cheques_ext as che on (che.Che_Cod=chd.Che_Cod)
					where
						asi.Asi_Deh='D' and
						asi.Com_Cod=$Par_Sql[Com_Cod]
					group by asi.Asi_Cod order by asi.Asi_Deh desc;";
			return $sql;
			// sentencia para actualizar comprobante (utilizada en modificacion de pagos por lotes)
		case 38:
			$sql = "UPDATE comprobantes
						SET
							Pec_Cod='$Par_Sql[Pec_Cod]',
							Com_Num='$Par_Sql[Com_Num]',
							Com_Fec='$Par_Sql[Com_Fec]',
							Com_Con='$Par_Sql[Com_Con]',
							Com_Val='$Par_Sql[Com_Val]',
							Com_Obs='$Par_Sql[Com_Obs]',
							Tia_Cod='$Par_Sql[Tia_Cod]',
							Num_Doc='$Par_Sql[Num_Doc]'
						WHERE Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
		case 39:
			$sql = "DELETE from cheques_ext
					where cheques_ext.Che_Est!='P'
						and cheques_ext.Che_Cod in (select chd.Che_Cod from cheq_det_ccpp as chd inner join det_ccpp_c as det on (det.Dcc_Cod=chd.Dcc_Cod) where det.Com_Cod=$Par_Sql[Com_Cod]);";
			return $sql;
		case 40:
			$sql = "DELETE FROM asientos
					WHERE
						asientos.Com_Cod=$Par_Sql[Com_Cod] and
						asientos.Asi_Cod not in (select det.Asi_cod from cheques_ext as che inner join cheq_det_ccpp as chd on (chd.Che_Cod=che.Che_Cod) inner join det_ccpp_c as det on (det.Dcc_Cod=chd.Dcc_Cod) where det.Asi_cod=asientos.Asi_Cod and che.Che_Est='P');";
			return $sql;
		// cambio de estado de Inactivo a Activo (actualizado el 21-02-2026)
		// liberacion de cheques recibidos
		case 41:
			$sql = "UPDATE cheques_ext as che
						inner join cheq_det_ccpp as chd on (chd.Che_Cod=che.Che_Cod)
						inner join det_ccpp_c as det on (det.Dcc_Cod=chd.Dcc_Cod)
					SET che.Che_Est='A'
					where det.Com_Cod=$Par_Sql[Com_Cod]";
			return $sql;
		case 42:
			$sql = "UPDATE comprobantes
					SET Com_Est='I'
					WHERE Com_Cod='$Par_Sql[Com_Cod]';";
			// echo $sql;
			return $sql;
		case 43:
			$sql = "DELETE FROM det_ccpp_c
					WHERE Com_Cod=$Par_Sql[Com_Cod]
					and det_ccpp_c.Dcc_Cod not in (
						select chd.Dcc_Cod from cheq_det_ccpp as chd
						inner join cheques_ext as che on(che.Che_Cod=chd.Che_Cod)
						where che.Che_Est='P' and chd.Dcc_Cod=det_ccpp_c.Dcc_Cod
					);";
			return $sql;
		case 44:
			$sql = "SELECT * from tipos_pago WHERE For_Cod='1' and Pag_Abr!='NDD' and Pag_Abr!='RET';";
			//ChromePhp::log($sql);
			return $sql;
		case 45:
			$sql = "SELECT
					ant.Ant_Val,
					ant.Ant_Cod,
					ant.Ant_Val,
					ant.Ant_Fec,
					CONCAT(pr.Prs_Nom, ' ', pr.Prs_Ape) AS nombre,
					ant.Com_Cod,
					pr.Prs_Ced,
					COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val,
					COALESCE(SUM(dacc.Ddc_Val), 0) AS Dac_Val_Aux,
					(COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo,
					(COALESCE((SELECT SUM(pga.Pac_Val) FROM pag_anticipo_cli AS pga WHERE pga.Ant_Cod = ant.Ant_Cod), 0) - COALESCE(SUM(dacc.Ddc_Val), 0)) AS saldo_aux
				FROM anticipos_clientes AS ant
					INNER JOIN comprobantes AS com ON com.Com_Cod = ant.Com_Cod
					INNER JOIN cliente AS cli ON ant.Cli_Cod = cli.Cli_Cod
					INNER JOIN persona AS pr ON pr.Prs_Cod = cli.Prs_Cod
					LEFT JOIN det_ant_cccc AS dacc ON dacc.Ant_Cod = ant.Ant_Cod
				WHERE
					ant.Cli_Cod = $Par_Sql[Cli_Cod] AND
					(ant.Ant_Est = 'A' OR ant.Ant_Est = 'U') AND
					com.Com_Est = 'A'
				GROUP BY
					ant.Ant_Cod, ant.Ant_Fec, nombre, ant.Com_Cod, pr.Prs_Ced
				ORDER BY
					ant.Ant_Cod, ant.Ant_Fec ASC";

			return $sql;
		/* Cuentas por PAGAR */
		case 46:	
			$sql="select det_plan.Pld_Cod,Pld_Cdc,Pld_Des,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) as proveedor,
				persona.Prs_Ape,persona.Prs_Nom,compras.Cop_Cod,ccpp_pagar.Cpp_Cod,compras.Cop_Fec,compras.Cop_Num,compras.Cop_Obs,ccpp_pagar.Cpp_Ven,ccpp_pagar.Com_Cod,
				asientos.Asi_Cod,asientos.Asi_Val,asientos.Pld_Cod,Pld_Cdc,Pld_Des,Cop_Obs,
				CONCAT(Tia_Abr, '-', if(CHAR_LENGTH(month(comprobantes.Com_Fec))= 1, CONCAT('0', cast(month(comprobantes.Com_Fec) as char)), cast(month(comprobantes.Com_Fec) as char)), '-', cast(comprobantes.Com_Num as char)) as Com_Codigo,
				if(SUM(if(comp2.Com_Est = 'A', ROUND(Pag_Val, 2), 0))= Asi_Val, 'Pagado', if(DATEDIFF(Cpp_Ven, CURDATE())>0, CONCAT(cast(DATEDIFF(Cpp_Ven, CURDATE()) as char), ' d&iacute;as'), 'Vencido')) as vencimiento,
				if(SUM(if(comp2.Com_Est = 'A', ROUND(Pag_Val, 2), 0)) is null, 0, SUM(if(comp2.Com_Est = 'A', ROUND(Pag_Val, 2), 0))) as Abono
			from proveedore
				inner join compras on (proveedore.Prv_Cod = compras.Prv_Cod)
				inner join persona on (proveedore.Prs_Cod = persona.Prs_Cod)
				inner join ccpp_pagar on (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
				inner join comprobantes on (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
				inner join tipo_asien on tipo_asien.Tia_Cod = comprobantes.Tia_Cod
				inner join asientos on (comprobantes.Com_Cod = asientos.Com_Cod)
				inner join perio_cont on (compras.Pec_Cod = perio_cont.Pec_Cod)
				inner join ccpp_prove on (asientos.Pld_Cod = ccpp_prove.Pld_Cod)
				inner join det_plan on (asientos.Pld_Cod = det_plan.Pld_Cod)
				left join det_ccpp_p on ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod
				left join comprobantes as comp2 on (comp2.Com_Cod = det_ccpp_p.Com_Cod)
			where asientos.Asi_Deh = 'H' and (compras.Cop_Est = 'A' or compras.Cop_Est = 'E') and (comprobantes.Com_Est = 'A' or comprobantes.Com_Est = 'E' ) and proveedore.Prv_Cod = $Par_Sql[Prv_Cod] and Emp_Cod = $_SESSION[Ses_Emp_Cod] 
			group by compras.Cop_Cod";
			return $sql;
	}
	return $sql;
}
