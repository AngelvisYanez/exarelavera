<?php
	/**
	* Ajustes de inventario
	*/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{	
		    
			case 1:
			/* Consultamos datos de la venta y sus totales*/
			$sql="SELECT 
					  ventas.Vet_Cod,sucursal.Suc_Sri,autorizaci.Pun_Sri,ventas.Vet_Num,ventas.Vet_Sys,
					  (SELECT SUM(ventas_det.Vet_Imp) AS tot FROM ventas_det WHERE ventas_det.Vet_Cod = ventas.Vet_Cod) AS total,
					  (SELECT SUM(ventas_det.Vet_Imp) AS tot FROM ventas_det WHERE ventas_det.Vet_Cod = ventas.Vet_Cod and ventas_det.Iva_Cod='3') AS Imp_Iva,
					  ventas.Vet_Xml,ventas.Vet_Des
					FROM
					  autorizaci
					  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
					  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
					WHERE
					  ventas.Vet_Aut='N' AND ventas.Cli_Cod = '$Par_Sql[0]' AND ventas.Vet_Xml != '' AND ventas.Vet_Est='A'";
			//echo $sql;
			return $sql;
			break;
			
			case 2:
			/* Consultamos datos de la venta y sus totales*/
			$sql="SELECT 
				  ventas.Vet_Cod,ventas.Tic_Cod,sucursal.Suc_Sri,Caj_Fec,autorizaci.Pun_Sri,ventas.Vet_Num,DATE_FORMAT(ventas.Vet_Sys,'%d/%m/%Y')as Vet_Sys,
				  (SELECT SUM(ventas_det.Vet_Imp) AS tot FROM ventas_det WHERE ventas_det.Vet_Cod = ventas.Vet_Cod) AS total,
				  (SELECT SUM(ventas_det.Vet_Imp) AS tot FROM ventas_det WHERE ventas_det.Vet_Cod = ventas.Vet_Cod and ventas_det.Iva_Cod='3') AS Imp_Iva,
				  ventas.Vet_Xml,ventas.Vet_Des,persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape
				FROM
				  autorizaci
				  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				WHERE
				  sucursal.Suc_Cod='$Par_Sql[0]' AND ventas.Vet_Aut='N' AND Aut_Tem='E' AND ventas.Vet_Xml != '' AND ventas.Vet_Est='A' order by ventas.Vet_Num Asc";
			//echo $sql;
			return $sql;
			break;
			
			case 3:
			/* Consultamos TIPO DE COMPROBANTE */
			$sql="SELECT 
					  Tic_Cod,Tic_Sri,Tic_Des
					FROM
					  tipo_compr
					WHERE
					  Tic_Sri='$Par_Sql[0]' AND Tic_Est='A'";
			//echo $sql;
			return $sql;
			break;
			
			case 4:
			/* Consultamos la persona */
			$sql="SELECT 
					  Prs_Cod,Prs_Ced,Prs_Ape,Prs_Nom 
					FROM
					  persona
					WHERE
					  Prs_Ced='$Par_Sql[0]'";
			echo $sql;
			return $sql;
			break;
			
			case 5: 
			/* actualizar datos de la factura*/
			$sql = "UPDATE ventas SET Vet_Aut = 'S', Vet_Sri='$Par_Sql[0]' WHERE Vet_Xml = '$Par_Sql[1]'"; 
			//echo $sql;
			return $sql;
			break;
			
                        case 6: 
			/* consultamos todos los comprobantes electronicos generados sin autorizacion SRI */
			$sql = "(SELECT ventas.Vet_Cod as cod,ventas.Vet_Xml as xml
					FROM
					  puntos_imp
					  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
					WHERE puntos_imp.Suc_Cod='$Par_Sql[0]' AND ventas.Vet_Xml='$Par_Sql[1]') 
					UNION					
					(SELECT retencion.Ret_Cod as cod,retencion.Ret_Xml as xml
					FROM
					  puntos_imp
					  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
					WHERE puntos_imp.Suc_Cod='$Par_Sql[0]' AND retencion.Ret_Xml='$Par_Sql[1]')
					UNION
					(SELECT guias_remi.Gui_Cod as cod,guias_remi.Gui_Xml as xml
					FROM
					  puntos_imp
					  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN guias_remi ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
					WHERE puntos_imp.Suc_Cod='$Par_Sql[0]' AND guias_remi.Gui_Xml='$Par_Sql[1]')
					"; 
			//echo $sql;
			return $sql;
			break;
			
			case 7:
			/* Consultamos TIPO DE COMPROBANTE */
			$sql="SELECT 
					  Tic_Cod,Tic_Sri,Tic_Des
					FROM
					  tipo_compr
					WHERE
					  Tic_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			case 21:
			/* Consulta del cliente si es una persona por apellidos */
			$consultar_buscar_21 = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, 
			persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') 
			as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			//echo $consultar_buscar_21;
			return $consultar_buscar_21;
			break;
	
			case 22:
			/* Consulta del personal por cedula */
			$consultar_cliente1 = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape,persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced = '$Par_Sql[0]'  AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY	persona.Prs_Ape, persona.Prs_Nom ASC";
			//echo "<br>".$consultar_cliente1;
			return $consultar_cliente1;
			break;
			
			/*
			*	Consultamos las guias de remision
			*/
			case 23:
			$sql="SELECT 
				  guias_remi.Gui_Cod, sucursal.Suc_Sri, autorizaci.Pun_Sri,
				  guias_remi.Gui_Num, DATE_FORMAT(guias_remi.Gui_Fec,'%d/%m/%Y')as Gui_Fec, guias_remi.Gui_Xml,
				  persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
				FROM
				  autorizaci
				  INNER JOIN guias_remi ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
				  INNER JOIN transporte ON (guias_remi.Tra_Cod = transporte.Tra_Cod)
				  INNER JOIN guia_destin ON (guias_remi.Des_Cod = guia_destin.Des_Cod)				  
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				  INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)
				WHERE
				  sucursal.Suc_Cod='$Par_Sql[0]' AND
				  guias_remi.Gui_Aut='N' AND 
				  guias_remi.Gui_Xml != '' AND 
				  guias_remi.Gui_Est='A' 
				order by 
				  guias_remi.Gui_Num Asc";
 		
			return $sql;
			break;
		
		    /*
			*	Consultamos las retenciones
			*/
			case 24:
			$sql="SELECT 
					  retencion.Ret_Cod,sucursal.Suc_Sri,autorizaci.Pun_Sri,
					  retencion.Ret_Num,DATE_FORMAT(retencion.Ret_Fec,'%d/%m/%Y')as Ret_Fec,retencion.Ret_Xml,
					  persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Ced
					FROM
					  autorizaci
					  INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
					  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
					  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
					  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
					  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
					WHERE 
					  sucursal.Suc_Cod='$Par_Sql[0]' AND
					  retencion.Ret_Aut='N' AND 
					  retencion.Ret_Xml != '' AND 
					  retencion.Ret_Est='A' 
					order by 
					  retencion.Ret_Num Asc";					   		
			return $sql;
			break;		    
			
			/*
			*	Consultamos los detalles d la retencion
			*/
			case 25:
			$sql="SELECT  
					 Ret_Cod,
					 (Ret_Bas * Ren_Por)/100 as ret
				  FROM 
				     det_retenc, renta_iva 
				  WHERE 
					 det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = '$Par_Sql[0]'";					   		
			return $sql;
			break;	

                        case 26: 
			/* actualizar datos de la Retencion */
			$sql = "UPDATE retencion SET Ret_Aut = 'S', Ret_Sri='$Par_Sql[0]' WHERE Ret_Xml = '$Par_Sql[1]'"; 
			//echo $sql;
			return $sql;
			break;
			
			case 27: 
			/* actualizar datos de la Guias de remision */
			$sql = "UPDATE guias_remi SET Gui_Aut = 'S', Gui_Sri='$Par_Sql[0]' WHERE Gui_Xml = '$Par_Sql[1]'"; 
			//echo $sql;
			return $sql;
			break;	 							
		}
	}
?>