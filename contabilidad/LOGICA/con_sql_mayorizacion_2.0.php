<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-06-24
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */


function sentencias_con($id, $Par_Sql)
{
	switch ($id) {
			/* Sacamo  info de la factura de venta */
			/*case 1:
			$sql = "SELECT ventas_compr.Vet_Cod,ventas.Vet_Num
			FROM ventas_compr
			ventas_compr INNER JOIN ventas ON (ventas_compr.Vet_Cod = ventas.Vet_Cod)

			WHERE ventas_compr.Com_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;*/



		case 1:
			$sql = "SELECT ventas_compr.Vet_Cod,ventas.Vet_Num , tipo_compr.Tic_Des
			FROM ventas_compr
			ventas_compr INNER JOIN ventas ON (ventas_compr.Vet_Cod = ventas.Vet_Cod)
			INNER JOIN tipo_compr on tipo_compr.Tic_Cod = ventas.Tic_Cod
			WHERE ventas_compr.Com_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/* Sacamo  info de la factura de compras */
			/*case 2:
			$sql = "SELECT compr_auto.Cop_Cod,compras.Cop_Num
			FROM compr_auto
			INNER JOIN compras ON (compr_auto.Cop_Cod = compras.Cop_Cod)
			WHERE compr_auto.Com_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;*/

		case 2:
			$sql = "SELECT compr_auto.Cop_Cod,compras.Cop_Num, tipo_compr.Tic_Des
				FROM compr_auto
				INNER JOIN compras ON (compr_auto.Cop_Cod = compras.Cop_Cod)
				INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = compras.Tic_Cod
				WHERE compr_auto.Com_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;


		case 3:
			/* Consulta la provicia y pais de la ciudad de la sucursal */
			$provincia = "SELECT 
  provincia.Pro_Nom,
  pais.Pas_Nom
FROM
  provincia
  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
 WHERE 
  ciudad.Ciu_Cod = $Par_Sql[0]";
			//echo $provincia;
			return $provincia;
			break;

		case 4:
			/* 
		* Consulta del usuario
		*/
			$consulta_4 = "SELECT Prs_Ape, Prs_Nom, Prs_Ced FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $consulta_4;
			return $consulta_4;
			break;

		case 126:
			/* Consulta la información la ciudada en base a la sucursal */
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, Emp_Con, Emp_Rco, Emp_Rep, Emp_Rre,
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

		case 201:
			/**
			 * Consulta de la mayorización individual - OPTIMIZADA
			 */
			switch ($Par_Sql[5]) {
				case 't': $Par_Sql[5] = " "; break;
				case 'm': $Par_Sql[5] = " AND comprobantes.Com_Gen='M'"; break;
				case 'a': $Par_Sql[5] = " AND comprobantes.Com_Gen='A'"; break;
			}

			$sql = "SELECT comprobantes.Com_Cod, 
					comprobantes.Com_Num,  tipo_asien.Tia_Ini, 
					tipo_asien.Tia_Abr, 
					comprobantes.Com_Fec, 
					asientos.Asi_Cod, 
					comprobantes.Com_Con, 
					asientos.Asi_Deh,
					asientos.Asi_Val AS Asi_Val, 
					comprobantes.Com_Obs, 
					cheques.Che_Num,
					det_plan.Pld_Des, 
					det_plan.Pld_Cdc, 
					det_plan.Pld_Rec, 
					comprobantes.Com_Val, 
					comprobantes.Cli_Cod, 
					comprobantes.Prv_Cod, 
					comprobantes.Com_Gen,
					-- Documentos de Venta
					v.Vet_Num,
					tv.Tic_Des as Tic_Des_Venta,
					-- Documentos de Compra
					c.Cop_Num,
					tc.Tic_Des as Tic_Des_Compra,
					-- Nombres Cliente o Proveedor
					COALESCE(p_cli.Prs_Ape, p_prv.Prs_Ape) as Prs_Ape,
					COALESCE(p_cli.Prs_Nom, p_prv.Prs_Nom) as Prs_Nom,
					COALESCE(p_cli.Prs_Ced, p_prv.Prs_Ced) as Prs_Ced

					FROM asientos
					INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
					INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod
					INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod
					LEFT JOIN cheques ON cheques.Asi_Cod = asientos.Asi_Cod
					
					-- Joins para optimización de datos de personas y documentos
					LEFT JOIN cliente ON cliente.Cli_Cod = comprobantes.Cli_Cod
					LEFT JOIN persona p_cli ON p_cli.Prs_Cod = cliente.Prs_Cod
					LEFT JOIN proveedore ON proveedore.Prv_Cod = comprobantes.Prv_Cod
					LEFT JOIN persona p_prv ON p_prv.Prs_Cod = proveedore.Prs_Cod
					
					LEFT JOIN ventas_compr vc ON vc.Com_Cod = comprobantes.Com_Cod
					LEFT JOIN ventas v ON v.Vet_Cod = vc.Vet_Cod
					LEFT JOIN tipo_compr tv ON tv.Tic_Cod = v.Tic_Cod
					LEFT JOIN compr_auto ca ON ca.Com_Cod = comprobantes.Com_Cod
					LEFT JOIN compras c ON c.Cop_Cod = ca.Cop_Cod
					LEFT JOIN tipo_compr tc ON tc.Tic_Cod = c.Tic_Cod

					WHERE (comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND 
							det_plan.Pld_Cod = '$Par_Sql[2]' AND 
							comprobantes.Com_Est = 'A' AND 
							comprobantes.Pec_Cod = $Par_Sql[4] $Par_Sql[5] $Par_Sql[3]";
			return $sql;
			break;


		case 202:
			/**
			 * Consulta que realiza el calculo del saldo anterior a una fecha establecida
			 */
			$saldo_202 = "SELECT asientos.Asi_Deh, sum(asientos.Asi_Val) as Asi_Val FROM asientos, comprobantes, det_plan 
				WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND comprobantes.Com_Fec <= '$Par_Sql[0]' 
				 AND comprobantes.Com_Est = 'A' AND det_plan.Pld_Cod = '$Par_Sql[1]' AND comprobantes.Pec_Cod = $Par_Sql[2] 
				GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC"; //Antes AND det_plan.Pld_Est = 'A' //
			//echo $saldo_202;
			return $saldo_202;
			break;

			/**
			 * Cargado de la raíz del Plan de Cuentas de cuantas activas
			 */
		case 203:
			$cargar_nodosrep = "SELECT det_plan.Pld_Cdc, Pld_Rec, Pld_Cod, Pld_Des FROM det_plan WHERE Pld_Rec=$Par_Sql[0]";
			//echo $cargar_nodosrep;
			return $cargar_nodosrep;
			break;

		case 204:
			/**
			 * Consulta la descripcion de la recusividad de una sub-cuenta 
			 */
			$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
			//echo $consul_recur;
			return $consul_recur;
			break;

		case 205:
			/**
			 * Consulta que realiza el calculo del saldo anterior a una fecha establecida, dentro de un mismo periodo
			 */
			$sql = "SELECT asientos.Asi_Deh, sum(asientos.Asi_Val) as Asi_Val FROM asientos, comprobantes, det_plan 
				WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND comprobantes.Com_Fec <= '$Par_Sql[0]' 
				 AND comprobantes.Com_Est = 'A' AND det_plan.Pld_Cod = '$Par_Sql[1]' AND comprobantes.Pec_Cod = $Par_Sql[2]  
				GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC"; //Antes AND det_plan.Pld_Est = 'A' 
			return $sql;
			break;


			/* Consulta el codigo interno de la cuenta  en base al codigo manual */
		case 209:
			$consulta_cuenta = "SELECT Pld_Cod, Pld_Rec, Pld_Des, Pld_Cdc FROM det_plan WHERE Pld_Cdc = '$Par_Sql[0]' AND Pla_Cod = $Par_Sql[1]";
			//echo $consulta_cuenta;
			return $consulta_cuenta;
			break;

			/* Consulta el codigo interno del plan de cuentas en base al codigo del periodo contable */
		case 215:
			$consulta_plan_215 = "SELECT det_plan.Pla_Cod FROM det_plan, plan_cuenta, comprobantes, asientos WHERE plan_cuenta.Pla_Cod = det_plan.Pla_Cod 
					AND asientos.Pld_Cod = det_plan.Pld_Cod AND asientos.Com_Cod = comprobantes.Com_Cod AND comprobantes.Pec_Cod = $Par_Sql[0] GROUP BY det_plan.Pla_Cod ORDER BY det_plan.Pla_Cod DESC";
			//echo $consulta_plan_215;
			return $consulta_plan_215;
			break;

			/**
			 * Consulta el codigo interno del plan de cuentas en base al codigo del plan de cuentas
			 */
		case 216:
			$sql = "SELECT det_plan.Pld_Cod, Pld_Rec FROM det_plan, plan_cuenta WHERE plan_cuenta.Pla_Cod = det_plan.Pla_Cod  AND Pld_Cdc = 
					'$Par_Sql[0]' AND plan_cuenta.Pla_Cod = $Par_Sql[1] GROUP BY det_plan.Pld_Cod, Pld_Rec";
			//echo $sql;
			return $sql;
			break;

		/* Consulta del nombre del cliente de un comprobante POr ahora estan libres */
		case 217:
			$consul_clien_217 = "SELECT Prs_Ape, Prs_Nom,Prs_Ced FROM persona, cliente WHERE persona.Prs_Cod = cliente.Prs_Cod AND cliente.Cli_Cod = $Par_Sql[0]";
			//echo $consul_clien_217;
			return $consul_clien_217;
			break;

		/* Consulta del nombre del proveedor de un comprobante POr ahora estan libres */
		case 218:
			$consul_clien_218 = "SELECT Prs_Ape, Prs_Nom,Prs_Ced FROM persona, proveedore WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Cod = $Par_Sql[0]";
			return $consul_clien_218;
			break;

		case 2199:
			$sql = "SELECT persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced
					FROM 
						compr_arol
						INNER JOIN antici_rol ON compr_arol.Ant_Cod = antici_rol.Ant_Cod
						INNER JOIN contratos_lab ON antici_rol.Con_Cod = contratos_lab.Con_Cod
						INNER JOIN personal ON contratos_lab.Per_Cod = personal.Per_Cod
						INNER JOIN persona ON personal.Prs_Cod = persona.Prs_Cod
					WHERE 
						compr_arol.Com_Cod = $Par_Sql[0]
					LIMIT 1";
			// ChromePhp::log("Consulta20199: ", $sql);
			return $sql;
			break;

		/* Consulta de todos los periodos - UTILIZADO PARA LAS CONSULTAS DE BALANCES */
		case 219:
			$cargar_per_219 = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo, perio_cont.Pla_Cod FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND
						plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY 
						Pec_Fei Desc";
			//echo $cargar_per_219;
			return $cargar_per_219;
			break;

		/* Busqueda de cuentas por descripcion */
		case 312:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2] Order by Pld_Cod";
			return $sql;
			break;

		/* Busqueda de cuentas por codigo */
		case 313:
			$bus_ctac =
				// "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Cdc = TRIM('$Par_Sql[0]') AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2]";
				"SELECT det_plan.Pld_Cod, 
					det_plan.Pld_Cdc, 
					det_plan.Pld_Des, 
					empresas.Emp_Nom, 
					Pla_Obs, 
					IF (Pld_Tip = 'G', 'Grupo', 'Detalle') AS Pld_Tip, 
					IF (Pld_Est = 'A', 'Activa', 'Inactiva') AS Pld_Est, 
					Pla_Obs, 
					Pld_Rec 
				FROM det_plan
				INNER JOIN plan_cuenta ON det_plan.Pla_Cod = plan_cuenta.Pla_Cod
				INNER JOIN empresas ON plan_cuenta.Emp_Cod = empresas.Emp_Cod
				WHERE empresas.Emp_Cod = $Par_Sql[1] 
				AND REPLACE(det_plan.Pld_Cdc, '.', '') = REPLACE(TRIM('$Par_Sql[0]'), '.', '')
				AND plan_cuenta.Pla_Cod = $Par_Sql[3] 
				$Par_Sql[2]";
			//echo $bus_ctac;
			return $bus_ctac;
			break;

			/**
			 * Busqueda de cuentas por codigo 
			 */
		case 314:
			$sql = "SELECT 
						det_plan.Pld_Cod,
						det_plan.Pld_Cdc,
						det_plan.Pld_Des,
						det_plan1.Pld_Des AS Pld_Des_Grupo,
						det_plan1.Pld_Cdc AS Pld_Cdc_Grupo
					FROM det_plan
						INNER JOIN det_plan det_plan1 ON (det_plan1.Pld_Cod = det_plan.Pld_Rec)
						INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod) 
					WHERE det_plan.Pld_Est='A' AND 
							det_plan.Pld_Cdc = '$Par_Sql[0]' AND 
							plan_cuenta.Pla_Cod = $Par_Sql[1]";
			ChromePhp::log("Consulta314: ", $sql);
			return $sql;
			break;

			/* 
	* Cargado del detalle de los comprobantes 
	*/
		case 338:
			$cargar_cuentas_338 = "SELECT comprobantes.Com_Val, Com_Con, Com_Obs, asientos.Asi_Cod, asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val, Asi_Val AS Asi_Val2 FROM asientos, det_plan, comprobantes WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod AND comprobantes.Com_Cod = asientos.Com_Cod 
	"; //AND Asi_Deh = '$Par_Sql[1]' $Par_Sql[2]
			return $cargar_cuentas_338;
			break;

		case 339:
			$sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
              INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
              INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
              INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
              WHERE Ban_Est='A' AND plan_cuenta.Emp_Cod=$Par_Sql[0];";
			return $sql;

		case 399:
			$sql = "SELECT Pec_Cod,Year(Pec_Fei) AS Periodo,Pec_Fei,Pec_Fef,Ban_Cod,banco.Pld_Cod,Pld_Cdc,Pld_Des,Ban_Cue FROM banco
              INNER JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
              INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
              INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
              WHERE Ban_Est='A' AND Ban_Tip='B' AND plan_cuenta.Emp_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1];";
			return $sql;


		case 340:
			$cargar_nodosrep = "SELECT det_plan.Pld_Cdc, Pld_Rec, Pld_Cod, Pld_Des FROM det_plan WHERE det_plan.Pld_Cdc LIKE '$Par_Sql[0]%' AND Pla_Cod='$Par_Sql[1]' AND Pld_Tip='D' ORDER BY det_plan.Pld_Cdc";
			//echo $cargar_nodosrep.'<br/>';
			return $cargar_nodosrep;

		case 341:
			/**
			 * Consulta de la mayorizacion individual 
			 */
			switch ($Par_Sql[5]) {
				case 't':
					$Par_Sql[5] = " ";
					break;
				case 'm':
					$Par_Sql[5] = " AND comprobantes.Com_Gen='M'";
					break;
				case 'a':
					$Par_Sql[5] = " AND comprobantes.Com_Gen='A'";
					break;
			}

			/*	$sql = "SELECT comprobantes.Com_Cod, Com_Num, Tia_Ini, Tia_Abr, Com_Fec, asientos.Asi_Cod, comprobantes.Com_Con, asientos.Asi_Deh, (asientos.Asi_Val) AS Asi_Val, det_plan.Pld_Des, Pld_Cdc, Pld_Rec, comprobantes.Com_Val, Cli_Cod, comprobantes.Prv_Cod, Com_Gen, Che_Num 
                    FROM asientos 
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod 
                    INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod 
                    INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod 
                    LEFT JOIN cheques ON cheques.Asi_Cod=asientos.Asi_Cod
                    WHERE (comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_plan.Pld_Cod = '$Par_Sql[2]' AND comprobantes.Com_Est = 'A'
						AND comprobantes.Pec_Cod = $Par_Sql[4] $Par_Sql[5] $Par_Sql[3]"; //AND det_plan.Pld_Est = 'A' GROUP BY comprobantes.Com_Cod, Com_Num, Tia_Ini, Com_Fec, comprobantes.Com_Con, 						asientos.Asi_Deh, det_plan.Pld_Des, Pld_Cdc, Pld_Rec, comprobantes.Com_Val
			*/

			$sql = "SELECT comprobantes.Com_Cod, Com_Num, Tia_Ini, Tia_Abr, Com_Fec, asientos.Asi_Cod, comprobantes.Com_Con, 
					asientos.Asi_Deh, (asientos.Asi_Val) AS Asi_Val, det_plan.Pld_Des, Pld_Cdc, Pld_Rec, comprobantes.Com_Val, Cli_Cod, comprobantes.Prv_Cod, Com_Gen, pago_venta.Vet_Che ,Che_Num, tipos_pago.Pag_Des 
                    FROM asientos 
                    INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod 
                    INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod 
                    INNER JOIN tipo_asien ON comprobantes.Tia_Cod = tipo_asien.Tia_Cod 
                    LEFT JOIN cheques ON cheques.Asi_Cod = asientos.Asi_Cod
                    LEFT JOIN ventas_compr ON ventas_compr.com_cod = comprobantes.Com_Cod
                    LEFT JOIN pago_venta ON pago_venta.Vet_Cod = ventas_compr.Vet_Cod
                    LEFT JOIN tipos_pago ON tipos_pago.Pag_Cod = pago_venta.Pag_Cod
                    WHERE (comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND det_plan.Pld_Cod = '$Par_Sql[2]' AND comprobantes.Com_Est = 'A'
						AND comprobantes.Pec_Cod = $Par_Sql[4] $Par_Sql[5] $Par_Sql[3]"; //AND det_plan.Pld_Est = 'A' GROUP BY comprobantes.Com_Cod, Com_Num, Tia_Ini, Com_Fec, comprobantes.Com_Con, 						asientos.Asi_Deh, det_plan.Pld_Des, Pld_Cdc, Pld_Rec, comprobantes.Com_Val
			//echo $sql.'<br>';
			return $sql;
			break;
	}
}
