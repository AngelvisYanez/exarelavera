<?php
	/* Ajustes de inventario */
	function sentencias_cch($id,$Par_Sql) {
		switch($id){
			  case 1:
			  /* Consulta del monto caja actual */
			  $Sql_ta="SELECT Cch_Cod, Cch_Val FROM caja_chica WHERE Cch_Est='A' AND Emp_Cod =  '$Par_Sql[0]'";
			  //echo $Sql_ta;
			  return $Sql_ta;
			  break;
				
			  /* Consulta los bancos */
			  case 2:
				$sql="SELECT banco.Ban_Cod,det_plan.Pld_Cod,det_plan.Pld_Des
					FROM det_plan
						  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
						  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
					WHERE Ban_Tip = 'B' AND Ban_Est = 'A' AND Pld_Est = 'A' AND Emp_Cod = '$Par_Sql[0]'";
				return $sql;
			  break;
			  /* Consulta de las facturas pendientes a reponer */
		      case 3:
			   $sql="SELECT 
						    compras.Cop_Cod,
							persona.Prs_Ced,
							concat(persona.Prs_Ape, ' ', persona.Prs_Nom)as provee,
							tipo_compr.Tic_Des,
							compras.Cop_Num,
							compras.Cop_Fec,
							IF(Dre_Val is null, 
								(select 
								sum(((det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) + (det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) * (IF(ice.Ice_Por IS NOT NULL, 1 + ice.Ice_Por / 100, 0))) * (1 + iva.Iva_Por / 100))
								from det_compra
									INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
									LEFT JOIN ice ON (ice.Ice_int = det_compra.Ice_Int)
								where det_compra.Cop_Cod = compras.Cop_Cod)
								,sum(Dre_Val))AS total,	
							(
							SELECT SUM((Ret_Bas * Ren_Por) / 100) AS ret
							FROM retencion as r
							INNER JOIN det_retenc ON (r.Ret_Cod = det_retenc.Ret_Cod)
							INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
							WHERE r.Cop_Cod = compras.Cop_Cod AND Ret_Est = 'A'
							group by r.Ret_Cod) AS ret,
							ifnull((SELECT Ret_Asu FROM retencion as p WHERE p.Cop_Cod = compras.Cop_Cod AND p.Ret_Est = 'A'), 'S') as asu
						FROM compras
							INNER JOIN det_reposicion ON (compras.Cop_Cod = det_reposicion.Cop_Cod)
							INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
							INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
							INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)						
						WHERE Dre_Tip = 'P' AND Cop_Est = 'A' AND Emp_Cod = '$Par_Sql[0]'
					  GROUP BY compras.Cop_Cod";

				// $sql = "SELECT  compras.Cop_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS provee, tipo_compr.Tic_Des, compras.Cop_Num, compras.Cop_Fec,
				// 	  (SELECT SUM(dcpp.Pag_Val) FROM ccpp_pagar cpp 
				// 	  INNER JOIN det_ccpp_p dcpp ON dcpp.Cpp_Cod = cpp.Cpp_Cod  
				// 	  INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = dcpp.Pag_Cod 
				// 	  INNER JOIN comprobantes ON comprobantes.Com_Cod = dcpp.Com_Cod
				// 	  WHERE cpp.Cop_Cod = compras.Cop_Cod  AND comprobantes.Com_Est = 'A' AND Pag_Abr = 'RC' ) AS total,
				// 	  (SELECT SUM((Ret_Bas * Ren_Por) / 100)
				// 	  FROM retencion r 
				// 	  INNER JOIN det_retenc ON r.Ret_Cod = det_retenc.Ret_Cod
				// 	  INNER JOIN renta_iva ON det_retenc.Ren_Cod = renta_iva.Ren_Cod 
				// 	  WHERE r.Cop_Cod = compras.Cop_Cod  AND r.Ret_Est = 'A') AS ret,
				// 	  IFNULL((SELECT Ret_Asu  FROM retencion p  WHERE p.Cop_Cod = compras.Cop_Cod  AND p.Ret_Est = 'A'   LIMIT 1 ), 'S') AS asu
				// FROM compras
				// INNER JOIN det_reposicion ON compras.Cop_Cod = det_reposicion.Cop_Cod
				// INNER JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
				// INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
				// INNER JOIN tipo_compr ON compras.Tic_Cod = tipo_compr.Tic_Cod
				// INNER JOIN det_compra ON compras.Cop_Cod = det_compra.Cop_Cod  
				// INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
				// LEFT JOIN ice ON ice.Ice_int = det_compra.Ice_Int
				// WHERE Dre_Tip = 'P'  AND Cop_Est = 'A' AND Emp_Cod = '$Par_Sql[0]'
				// AND EXISTS ( SELECT 1 FROM ccpp_pagar cpp  INNER JOIN det_ccpp_p dcpp ON dcpp.Cpp_Cod = cpp.Cpp_Cod  
				// INNER JOIN comprobantes c ON c.Com_Cod = dcpp.Com_Cod  WHERE cpp.Cop_Cod = compras.Cop_Cod  AND c.Com_Est = 'A' )
				// GROUP BY compras.Cop_Cod;";

			  return $sql;
		      break;
			  
			  case 4://Busqueda de Proveedores
			  if($Par_Sql[2]=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";}
				else {$search="Prs_Ced LIKE '$Par_Sql[0]%'";}
				if($Par_Sql[3]==""){$campos="COUNT(Prv_Cod) as total";}
				else{
					$Par_Sql[3]="ORDER BY Prs_Ape ".$Par_Sql[3];
					$campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
			  }
			  $sql="SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
			  return $sql;
			  break;
			  
			  case 5:
			  /**
			  * Consultamos tipos de Asientos
			  */
			   $sql="SELECT Tia_Cod,Tia_Des,Tia_Ini FROM tipo_asien WHERE Tia_Ini='$Par_Sql[0]' AND Tia_Est='A'";
			  //echo $sql.'<br>';
			  return $sql;
			  break;
			  
			  /*Consultamos el ultimo cheque emitido segun el banco*/
			  case 6:
				$sql="SELECT MAX(Che_Num) as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";				
				//echo $sql;
				return $sql;
			  break;
			  
			  case 7:// consulta el numero d cheques segun Che_Num
                                $sql="SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod = $Par_Sql[Ban_Cod] AND Che_Num = $Par_Sql[numero]";                
                                return $sql; 
                                break;
			  
			  case 8:
			  /**
			  * Inserta la reposicion
			  */
			   $Sql_ta="INSERT INTO cab_reposicio(Cch_Cod,Usu_Cod,Rep_Fec,Rep_Obs,Com_Cod,Rep_Num,Rep_Tip)VALUES($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]',$Par_Sql[4],$Par_Sql[5],'$Par_Sql[6]')";
			  //echo $Sql_ta.'<br>';
			  return $Sql_ta;
			  break;
			  
			  case 9:
			  /**
			  * Inserta detalle reposicion
			  */
			   $Sql_ta="UPDATE det_reposicion SET Rep_Cod='$Par_Sql[0]',Dre_Tip='$Par_Sql[1]' WHERE Cop_Cod='$Par_Sql[2]'";
			  //echo $Sql_ta.'<br>';
			  return $Sql_ta;
			  break;
		 	  
			  case 10:
			  /**
			  * Inserta el comprobante contable
			  */
			   $Sql_ta="INSERT INTO comprobantes(Pec_Cod,Prv_Cod,Usu_Cod,Tia_Cod,Com_Num,Com_Fec,Com_Con,Com_Val,Com_Gen)VALUES
			            ('$Par_Sql[0]',$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]')";
			  //echo $Sql_ta.'<br>';
			  return $Sql_ta;
			  break;
			  
                            case 11:// consulta el periodo contable
                                $sql="SELECT perio_cont.Pec_Cod,plan_cuenta.Pla_Cod
                                        FROM plan_cuenta
                                        INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
                                        WHERE plan_cuenta.Emp_Cod = '$Par_Sql[0]' AND '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef";
                                return $sql; 
                                break;
			  
                            case 12:// consulta la cuenta reposicion de tipo RC
                                $sql="SELECT tipo_param.Tpa_Cod,det_plan.Pld_Cod
                                        FROM tipo_param
                                        INNER JOIN plan_param ON (tipo_param.Tpa_Cod = plan_param.Tpa_Cod)
                                        INNER JOIN det_plan ON (plan_param.Pld_Cod = det_plan.Pld_Cod)
                                        INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
                                        WHERE Tpa_Abr='$Par_Sql[0]' AND Tpa_Est='A' AND Ppc_Est='A' AND Emp_Cod='$Par_Sql[1]'";
                                return $sql; 
                                break;
			  
			  case 13:
			  /**
			  * Inserta el asiento contable
			  */
			  $Sql_ta="INSERT INTO asientos(Com_Cod,Asi_Deh,Asi_Val,Pld_Cod,Asi_Glo)VALUES($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
			  //echo $Sql_ta.'<br>';
			  return $Sql_ta;
			  break;
			  
			  case 14:
			  /**
			  * Inserta el cheque
			  */
			   $Sql_ta="INSERT INTO cheques(Prv_Cod,Ban_Cod,Asi_Cod,Che_Num,Che_Fec,Che_Val,Che_Cod)VALUES($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]',$Par_Sql[6])";
			  //echo $Sql_ta.'<br>';
			  return $Sql_ta;
			  break;
			  
			  /**
	  		 * SENTECIAS UTILILES EN REPORTES PARA CABECERAS
	  		 * Consulta que permite cargar el nombre de la empresa a que pertenece el usuario
	  		 */
			case 15:
			$sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2,
				   sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des,empresas.Emp_Log FROM empresas, sucursal, ciudad 
				   WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return  $sql;
			break;
			
			case 16: 
			/* Consulta la información la ciudada en base a la sucursal */
			$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
							//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;
			
			case 17: 
			/* Consulta la provicia y pais de la ciudad de la sucursal */
			$provincia="SELECT provincia.Pro_Nom,pais.Pas_Nom
			FROM
			  provincia
			  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
			  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
			  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
			 WHERE ciudad.Ciu_Cod = $Par_Sql[0]";
			//echo $provincia;
			return $provincia;
			break;
			
			/** 
			* Consulta los datos del systema
			*/
			case 18:	
			$sql = "SELECT Sys_Cod,Sys_Nom,Sys_Ver,Sys_Des,Sys_Cor,concat('Ofsercont- ',Sys_Nom,' [',Sys_Des,']')as Sys_Tit FROM system";
			//echo $sql;
			return $sql;
			break;
			
			/**
		    *  Consulta los datos del usuario
		    */
		    case 19:
		    $sql="SELECT Prs_Ape, Prs_Nom, Prs_Ced FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
		    return $sql;
		    break;
		    
			/*consultamos compras DEDUCIBLES/NO DEDUCIBLES */
			case 20:
			$sql="SELECT compras.Cop_Cod,persona.Prs_Ced,concat(persona.Prs_Ape,' ',persona.Prs_Nom)as provee,
				  tipo_compr.Tic_Des,compras.Cop_Num,compras.Cop_Fec,
					sum(((det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) + (det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) * (IF(ice.Ice_Por IS NOT NULL, 1 + ice.Ice_Por / 100, 0))) * (1 + iva.Iva_Por / 100)) AS total,
					ifnull((SELECT SUM((Ret_Bas * Ren_Por) / 100) AS ret
					FROM
					  retencion as r
					  INNER JOIN det_retenc ON (r.Ret_Cod = det_retenc.Ret_Cod)
					  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
					WHERE r.Cop_Cod=compras.Cop_Cod  AND Ret_Est='A' group by r.Ret_Cod),0)as ret,
					ifnull((SELECT Ret_Asu FROM retencion as p WHERE p.Cop_Cod=compras.Cop_Cod AND p.Ret_Est='A'),'S') as asu
					FROM compras
					INNER JOIN det_reposicion ON (compras.Cop_Cod = det_reposicion.Cop_Cod)
					INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
					INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
					INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
					INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)  
					INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
					LEFT JOIN ice ON (ice.Ice_int = det_compra.Ice_Int)						
					WHERE  Cop_Est = 'A' AND Emp_Cod = '$Par_Sql[0]' AND Dre_Tip = 'R' AND Rep_Cod='$Par_Sql[1]' 
					GROUP BY compras.Cop_Cod  having asu='$Par_Sql[2]' order by Cop_Fec Asc";
			
			// $sql = "SELECT
			// 		compras.Cop_Cod, persona.Prs_Ced,
			// 		CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS provee,
			// 		tipo_compr.Tic_Des, compras.Cop_Num, compras.Cop_Fec,
			// 		-- Total de pagos hechos con tipo 'RC' (Caja Chica)
			// 		( SELECT SUM(dcpp.Pag_Val)
			// 			FROM ccpp_pagar cpp
			// 			INNER JOIN det_ccpp_p dcpp ON dcpp.Cpp_Cod = cpp.Cpp_Cod
			// 			INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = dcpp.Pag_Cod
			// 			INNER JOIN comprobantes ON comprobantes.Com_Cod = dcpp.Com_Cod
			// 			WHERE cpp.Cop_Cod = compras.Cop_Cod
			// 			AND comprobantes.Com_Est = 'A'
			// 			AND tipos_pago.Pag_Abr = 'RC'
			// 		) AS total,
			// 		-- Retención aplicada
			// 		IFNULL((
			// 			SELECT SUM((Ret_Bas * Ren_Por) / 100)
			// 			FROM retencion r
			// 			INNER JOIN det_retenc ON r.Ret_Cod = det_retenc.Ret_Cod
			// 			INNER JOIN renta_iva ON det_retenc.Ren_Cod = renta_iva.Ren_Cod
			// 			WHERE r.Cop_Cod = compras.Cop_Cod AND r.Ret_Est = 'A'
			// 		), 0) AS ret,
			// 		-- Si la retención fue asumida
			// 		IFNULL((
			// 			SELECT Ret_Asu
			// 			FROM retencion p
			// 			WHERE p.Cop_Cod = compras.Cop_Cod AND p.Ret_Est = 'A'
			// 			LIMIT 1
			// 		), 'S') AS asu
			// 	FROM compras
			// 	INNER JOIN det_reposicion ON compras.Cop_Cod = det_reposicion.Cop_Cod
			// 	INNER JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
			// 	INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
			// 	INNER JOIN tipo_compr ON compras.Tic_Cod = tipo_compr.Tic_Cod
			// 	INNER JOIN det_compra ON compras.Cop_Cod = det_compra.Cop_Cod
			// 	INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
			// 	LEFT JOIN ice ON ice.Ice_int = det_compra.Ice_Int
			// 	WHERE 
			// 		Cop_Est = 'A'
			// 		AND Emp_Cod = '$Par_Sql[0]'
			// 		AND Dre_Tip = 'R'
			// 		AND Rep_Cod = '$Par_Sql[1]'
				
			// 	GROUP BY compras.Cop_Cod
			// 	HAVING asu = '$Par_Sql[2]'
			// 	ORDER BY Cop_Fec ASC";
			//echo $sql;
			return $sql;
		    break;
			
			/*consultamos reposicion por numero de cheque */
			case 21:
			$sql="SELECT cab_reposicio.Rep_Cod,concat(Rep_Num,'-',YEAR(Rep_Fec))as Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Obs,det_plan.Pld_Des,Ban_Cod,asientos.Pld_Cod,Tia_Cod,
				  Rep_Fec,cheques.Che_Num,comprobantes.Com_Val,comprobantes.Com_Cod
				FROM asientos
				  INNER JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
				  INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
				  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE Rep_Num='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Com_Est='A'";
			//echo "21 ".$sql;
			return $sql;
		    break;
			
			/*consultamos reposicion por rango fecha */
			case 22:
			$sql="SELECT 
					cab_reposicio.Rep_Cod,concat(Rep_Num,'-',YEAR(Rep_Fec))as Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Obs, 
					IF (Che_Num != '', CONCAT('Cheque',' - ',det_plan.Pld_Des), CONCAT('Efectivo',' - ',det_plan.Pld_Des)) as Pld_Des,Ban_Cod,asientos.Pld_Cod,Tia_Cod,
					Rep_Fec,IF (cheques.Che_Num != '', cheques.Che_Num,'-------------') AS Che_Num,comprobantes.Com_Val,comprobantes.Com_Cod
				FROM asientos
					LEFT JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
					INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
					INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
					INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
					INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
					INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE 
					(Rep_Fec Between '$Par_Sql[0]' AND '$Par_Sql[1]') AND Emp_Cod='$Par_Sql[2]' AND Com_Est='A' AND Asi_Deh = 'H'";
			//echo "22 ".$sql;
			return $sql;
		    break;
			
			/* Consulta de las facturas pendientes a reponer */
		    case 23:
			    $sql="SELECT 
						  compras.Cop_Cod,persona.Prs_Ced,concat(persona.Prs_Ape,' ',persona.Prs_Nom)as provee,
						  tipo_compr.Tic_Des,compras.Cop_Num,compras.Cop_Fec,
						  sum(((det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) + (det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) * (IF(ice.Ice_Por IS NOT NULL, 1 + ice.Ice_Por / 100, 0))) * (1 + iva.Iva_Por / 100)) AS total,
						  (SELECT SUM((Ret_Bas * Ren_Por) / 100) AS ret
							FROM
							  retencion as r
							  INNER JOIN det_retenc ON (r.Ret_Cod = det_retenc.Ret_Cod)
							  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
							WHERE r.Cop_Cod=compras.Cop_Cod AND Ret_Est='A' group by r.Ret_Cod) AS ret,
						  ifnull((SELECT Ret_Asu FROM retencion as p WHERE p.Cop_Cod=compras.Cop_Cod AND p.Ret_Est='A'),'S') as asu,
						  if(Dre_Tip='P','No','Yes')as act
						FROM
						  compras
						  INNER JOIN det_reposicion ON (compras.Cop_Cod = det_reposicion.Cop_Cod)
						  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
						  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
						  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
						  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)  
						  INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
						  LEFT JOIN ice ON (ice.Ice_int = det_compra.Ice_Int)						
						WHERE (Dre_Tip = 'P' or det_reposicion.Rep_Cod='$Par_Sql[0]') AND Cop_Est = 'A' AND Emp_Cod = '$Par_Sql[1]'
					  GROUP BY compras.Cop_Cod";
				// $sql = "SELECT 
				// 			compras.Cop_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS provee,
				// 			tipo_compr.Tic_Des, compras.Cop_Num, compras.Cop_Fec,
				// 	-- Total pagado por Caja Chica (RC)
				// 	( SELECT SUM(dcpp.Pag_Val)
				// 		FROM ccpp_pagar cpp 
				// 		INNER JOIN det_ccpp_p dcpp ON dcpp.Cpp_Cod = cpp.Cpp_Cod  
				// 		INNER JOIN tipos_pago ON tipos_pago.Pag_Cod = dcpp.Pag_Cod 
				// 		INNER JOIN comprobantes ON comprobantes.Com_Cod = dcpp.Com_Cod
				// 		WHERE cpp.Cop_Cod = compras.Cop_Cod
				// 		AND comprobantes.Com_Est = 'A'
				// 		AND Pag_Abr = 'RC'
				// 	) AS total,
				// 	-- Retención aplicada
				// 	( SELECT SUM((Ret_Bas * Ren_Por) / 100)
				// 		FROM retencion r 
				// 		INNER JOIN det_retenc ON r.Ret_Cod = det_retenc.Ret_Cod  
				// 		INNER JOIN renta_iva ON det_retenc.Ren_Cod = renta_iva.Ren_Cod 
				// 		WHERE r.Cop_Cod = compras.Cop_Cod AND r.Ret_Est = 'A'
				// 	) AS ret,
				// 	-- Si la retención fue asumida
				// 	IFNULL((
				// 		SELECT Ret_Asu
				// 		FROM retencion p
				// 		WHERE p.Cop_Cod = compras.Cop_Cod AND p.Ret_Est = 'A'
				// 		LIMIT 1
				// 	), 'S') AS asu,
				// 	-- Indicador de si está activa o no
				// 	IF(Dre_Tip = 'P', 'No', 'Yes') AS act
				// 	FROM compras
				// 		INNER JOIN det_reposicion ON compras.Cop_Cod = det_reposicion.Cop_Cod
				// 		INNER JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
				// 		INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
				// 		INNER JOIN tipo_compr ON compras.Tic_Cod = tipo_compr.Tic_Cod
				// 		INNER JOIN det_compra ON compras.Cop_Cod = det_compra.Cop_Cod  
				// 		INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
				// 		LEFT JOIN ice ON ice.Ice_int = det_compra.Ice_Int

				// 	WHERE 
				// 		(Dre_Tip = 'P' OR det_reposicion.Rep_Cod = '$Par_Sql[0]')
				// 		AND Cop_Est = 'A'
				// 		AND Emp_Cod = '$Par_Sql[1]'
				// 	GROUP BY compras.Cop_Cod";
			//echo $sql;
			return $sql;
		    break;
			
			case 24:
			  /**
			  * Actualizamos el comprobante contable
			  */
			   $Sql_ta="UPDATE comprobantes SET Pec_Cod='$Par_Sql[0]',Prv_Cod=$Par_Sql[1],Usu_Cod='$Par_Sql[2]',Tia_Cod='$Par_Sql[3]',Com_Fec='$Par_Sql[4]',Com_Con='$Par_Sql[5]',Com_Val='$Par_Sql[6]',Com_Gen='$Par_Sql[7]' WHERE Com_Cod='$Par_Sql[8]'";
			  //echo $Sql_ta.'<br>';
			return $Sql_ta;
			break;
			
			case 25:
			  /**
			  * Eliminamos los datos del Asiento
			  */
			   $Sql_ta="DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
			  //echo $Sql_ta.'<br>';
			return $Sql_ta;
			break;
			
			case 26:
			/**
			* Damos de Baja el Cheque
			*/
			$Sql_ta="UPDATE comprobantes as A INNER JOIN asientos as B ON (A.Com_Cod=B.Com_Cod) INNER JOIN cheques ON (B.Asi_Cod=cheques.Asi_Cod) 
					SET cheques.Che_Est='I'
					WHERE a.Com_Cod='$Par_Sql[0]'";
			//echo $Sql_ta.'<br>';
			return $Sql_ta;
			break;
			
			case 27:
			/**
			* Actualizamos cabecera de reposicion
			*/
			$Sql_ta="UPDATE cab_reposicio SET Cch_Cod=$Par_Sql[0],Usu_Cod=$Par_Sql[1],Rep_Fec='$Par_Sql[2]',Rep_Obs='$Par_Sql[3]',Com_Cod=$Par_Sql[4] WHERE Rep_Cod='$Par_Sql[5]'";
			//echo $Sql_ta.'<br>';
			return $Sql_ta;
			break;
			
			/*consultamos reposicion por numero de cheque */
			case 28:
			$sql="SELECT cab_reposicio.Rep_Cod,Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Obs,det_plan.Pld_Des,Ban_Cod,asientos.Pld_Cod,Tia_Cod,
				  Rep_Fec,cheques.Che_Num,comprobantes.Com_Val,comprobantes.Com_Cod
				FROM asientos
				  INNER JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
				  INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
				  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE cab_reposicio.Rep_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Com_Est='A'";
			//echo $sql;
			return $sql;
		    break;
			
			/*consultamos todas las reposicion */
			case 29:
			$sql="SELECT cab_reposicio.Rep_Cod,concat(Rep_Num,'-',YEAR(Rep_Fec))as Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Tip,Rep_Obs,det_plan.Pld_Des,Ban_Cod,asientos.Pld_Cod,asientos.Asi_Cod,Tia_Cod,
				  Rep_Fec,cheques.Che_Num,format(comprobantes.Com_Val,2)as Com_Val,comprobantes.Com_Cod,comprobantes.Pec_Cod
				FROM asientos
				  LEFT JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
				  INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
				  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE Emp_Cod='$Par_Sql[0]' AND Com_Est='A' group by Rep_Cod";
			//echo $sql;
			return $sql;
		    break;
			
			/*consultamos las reposicion por numero de cheque */
			case 30:
			$sql="SELECT cab_reposicio.Rep_Cod,concat(Rep_Num,'-',YEAR(Rep_Fec))as Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Obs,det_plan.Pld_Des,Ban_Cod,asientos.Pld_Cod,asientos.Asi_Cod,Tia_Cod,
				  Rep_Fec,cheques.Che_Num,format(comprobantes.Com_Val,2)as Com_Val,comprobantes.Com_Cod,comprobantes.Pec_Cod
				FROM asientos
				  INNER JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
				  INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
				  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE Emp_Cod='$Par_Sql[0]' AND Rep_Num='$Par_Sql[1]' AND Com_Est='A'";
			//echo $sql;
			return $sql;
		    break;
			
			/*Consultamos el ultimo cheque emitido segun el banco*/
			case 31:
			$sql="SELECT MAX(Rep_Num) as Rep_Num FROM 
			usuarios 
			INNER JOIN cab_reposicio ON (usuarios.Usu_Cod=cab_reposicio.Usu_Cod)
			WHERE Suc_Cod='$Par_Sql[0]' AND Rep_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]' AND Rep_Est='A'";				
			//echo $sql;
			return $sql;
			break;
			
			/*Consultamos la cuenta Caja-Banco que se a parametrizado*/
			case 32:
			$sql="SELECT banco.Ban_Cod,det_plan.Pld_Cod, det_plan.Pld_Des, banco.Ban_Tip FROM banco, det_plan, plan_cuenta 
			WHERE banco.Pld_Cod=det_plan.Pld_Cod AND plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND banco.Ban_Tip='$Par_Sql[0]' AND banco.Ban_Est='A' AND Emp_Cod='$Par_Sql[1]'";
			//echo "<br>".$sql;
			return $sql;
			break;
			
			case 33:
			/* 
			* Consulta el proveedor reservado para la contabilización
			*/
			$sql = "SELECT compra_prov.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom FROM compra_prov, proveedore, persona WHERE compra_prov.Prv_Cod = proveedore.Prv_Cod AND persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
			/*consultamos reposicion por numero */
			case 34:
			$sql="SELECT cab_reposicio.Rep_Cod,Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,Rep_Obs,det_plan.Pld_Des,asientos.Pld_Cod,Tia_Cod,
				  Rep_Fec,comprobantes.Com_Val,comprobantes.Com_Cod
				FROM asientos				  
				  INNER JOIN comprobantes ON (asientos.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)
				  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE cab_reposicio.Rep_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Com_Est='A'";
			//echo $sql;
			return $sql;
		    break;
			
			/*consultamos todas las reposicion */
			case 35:
			$sql="SELECT  cab_reposicio.Rep_Cod,concat(Rep_Num,'-',YEAR(Rep_Fec))as Rep_Num,Prs_Nom,Prs_Ape,proveedore.Prv_Cod,cab_reposicio.Rep_Fec,
					Rep_Obs,Tia_Cod, Rep_Fec,if(Rep_Tip='C','Cheque','Efectivo')as Rep_Tip,format(comprobantes.Com_Val,2)as Com_Val,
					comprobantes.Com_Cod,comprobantes.Pec_Cod, 
					(SELECT Ban_Cod
					FROM asientos INNER JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
					WHERE Com_Cod = comprobantes.Com_Cod)as Ban_Cod, 
					(SELECT asientos.Asi_Cod
					FROM asientos INNER JOIN cheques ON (asientos.Asi_Cod = cheques.Asi_Cod)
					WHERE Com_Cod = comprobantes.Com_Cod)as Asi_Cod
				FROM comprobantes 
					INNER JOIN cab_reposicio ON (comprobantes.Com_Cod = cab_reposicio.Com_Cod)  
					INNER JOIN proveedore ON (comprobantes.Prv_Cod = proveedore.Prv_Cod) 
					INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)  
				WHERE Emp_Cod='$Par_Sql[0]' AND Com_Est='A' group by Rep_Cod";
			//echo $sql;
			return $sql;
		    break;
			
			case 152:
			/**
			* Selecionar el numero maximo de comprobante mensual según el tipo
			*/
			$sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod = $Par_Sql[0] AND Pec_Cod = $Par_Sql[1] AND 
				MONTH(Com_Fec) = $Par_Sql[2]";
			return $sql;
			break;
		
			case 153: // select the amout of money from facturas compra
				$sql ="SELECT 
						compras.Cop_Cod,persona.Prs_Ced,concat(persona.Prs_Ape,' ',persona.Prs_Nom)as provee,
						tipo_compr.Tic_Des,compras.Cop_Num,compras.Cop_Fec,
						sum(((det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) + (det_compra.Cop_Imp - (((det_compra.Cop_Imp * compras.Cop_Des) / 100) + ((det_compra.Cop_Imp * det_compra.Cop_Dec) / 100))) * (IF(ice.Ice_Por IS NOT NULL, 1 + ice.Ice_Por / 100, 0))) * (1 + iva.Iva_Por / 100)) AS total		  
						FROM
						compras
							INNER JOIN det_reposicion ON (compras.Cop_Cod = det_reposicion.Cop_Cod)
							INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
							INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
							INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
							INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)  
							INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
							LEFT JOIN ice ON (ice.Ice_int = det_compra.Ice_Int)						
						WHERE Dre_Tip = 'P' AND Cop_Est = 'A' AND Emp_Cod = $Par_Sql[0] AND Rep_Cod IN (SELECT Rep_Cod FROM cab_reposicio WHERE Cch_Cod = (SELECT Cch_Cod FROM caja_chica WHERE Cch_Est='A' AND Emp_Cod = $Par_Sql[0]))
						GROUP BY compras.Cop_Cod";
				return $sql;
				break;

			case 154: // actualizacion de estado del detalle de reposicion
				$sql="UPDATE det_reposicion SET Dre_Tip = 'P', Rep_Cod = 0 WHERE Rep_Cod = '$Par_Sql[0]' AND Dre_Tip = 'R';";
				return $sql;
				break;

			case 155: // actualizacion de estado del comprobante de reposicion en base a Rep_Cod
				$sql = "UPDATE comprobantes 
							INNER JOIN cab_reposicio ON comprobantes.Com_Cod = cab_reposicio.Com_Cod
						SET comprobantes.Com_Est = 'I'
						WHERE comprobantes.Com_Cod = '$Par_Sql[0]';";
				return $sql;
				break;

			case 156: // actualizacion de estado del detalle de reposicion
				$sql = "UPDATE cab_reposicio SET Rep_Est = 'I' WHERE Rep_Cod = '$Par_Sql[0]';";
				return $sql;
				break;

			case 157:
				$sql = "UPDATE det_reposicion SET Dre_Tip = 'P', Rep_Cod = 0 WHERE Cop_Cod = '$Par_Sql[0]' AND Dre_Tip = 'R';";
				return $sql;
				break;
		}
	}
?>