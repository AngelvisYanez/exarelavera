<?php
	/**
	* Anexo transaacional
	*/
	function sentencias_anx($id,$Par_Sql)
	{
		switch($id)
		{	
			case 226:
			/**
			* Consulta la identificación del archivo xml 
			*/
			$sql = "SELECT Emp_Ruc, Emp_Nom, Suc_Dir, Suc_Te1, Suc_Fax, Suc_Cor, Emp_Rce, Emp_Rep, Emp_Rco, Emp_Ren FROM empresas, sucursal
									WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND empresas.Emp_Cod = $Par_Sql[0]";		
			return $sql;
			break;

			case 227:
			/**
			* Consulta del esquema sin recursividad
			*/
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] AND esquema.Esq_Est = 'A'";		
			return $sql;
			break;

			case 229:
			/**
			* Consulta del iva mayor a cero (0) 
			*/
			$sql = "SELECT Iva_Cod, Iva_Sri, Iva_Por FROM iva WHERE iva.Iva_Por > 0 AND iva.Iva_Est = 'A'";
			return $sql;
			break;

			case 230:
			/**
			* Consulta de los datos del detalle 
			*/
			$sql = "SELECT (sum(Cop_Imp) - 
			(sum((Cop_Imp * Cop_Des) /100) + sum((Cop_Imp * Cop_Dec) /100))) as Cop_Imp, Iva_Sri, Iva_Por FROM compras, det_compra, iva
			WHERE compras.Cop_Cod = det_compra.Cop_Cod AND det_compra.Iva_Cod = iva.Iva_Cod AND det_compra.Cop_Cod = $Par_Sql[0] AND det_compra.Adq_Cod !=13 GROUP BY Iva_Sri, Iva_Por";
			return $sql;
			break;

			case 231:
			/**
			* Consulta de los datos del ICE 
			*/
			$sql = "SELECT Sum(Cop_Imp) as Cop_Imp, Ice_Sri, Ice_Por, (Sum(Cop_Imp) * Ice_Por )/100 as Mon_Ice, Ice_Cod 
								FROM det_compra, ice
								WHERE det_compra.Ice_Int = ice.Ice_Int AND det_compra.Cop_Cod = $Par_Sql[0] GROUP BY 
								Ice_Sri, Ice_Por";
			return $sql;
			break;

			case 232:
			/**
			* Consulta de los montos bienes o servicios 
			*/
			$sql = "SELECT 
  sum(det_retenc.Ret_Bas) AS Ret_Bas,
  det_retenc.Adq_Cod,
  renta_iva.Ren_Por
FROM
  retencion
  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)			
			
			WHERE  (det_retenc.Adq_Cod = 1 OR det_retenc.Adq_Cod = 2 OR det_retenc.Adq_Cod = 3) AND  renta_iva.Ren_Por != 100 AND  retencion.Cop_Cod =        $Par_Sql[0] AND det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A' AND sucursal.Emp_Cod = $Par_Sql[1] GROUP BY  det_retenc.Adq_Cod";		
			return $sql;
			break;

			case 233:
			/**
			* Consulta de los datos AIR 
			*/
			$sql = "SELECT Ren_Sri, sum(Ret_Bas) AS Ret_Bas, Ren_Por, sum((Ret_Bas * Ren_Por)/100) as Val_Air, Ret_Num, Aut_Sri, Ret_Fec
								FROM retencion, det_retenc, renta_iva, autorizaci WHERE retencion.Ret_Cod = det_retenc.Ret_Cod 
								AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod 
								AND retencion.Cop_Cod = $Par_Sql[0] AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'
								GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec
								";/*En esta SQL se agrego GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec para unificar 
								en caso de que a una retencion se le agregue 2 codigos de los mismos */
	//echo $detalle_xml_233;
			return $sql;
			break;

			case 234:
			/**
			* Consulta del esquema sin recursividad para los grupos 
			*/
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] AND Esq_Ini = '$Par_Sql[2]'";		
			return $sql;
			break;

			case 237:
			/**
			* Consulta total de las  retenciones o liquidaciones anuladas o activas 
			*/
			$sql = "SELECT retencion.Ret_Cod, Tic_Sri, Ret_Num, Aut_Sri, Ret_Fec FROM retencion, tipo_compr, 
					autorizaci, proveedore WHERE retencion.Tic_Cod = tipo_compr.Tic_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod AND retencion.Prv_Cod = proveedore.Prv_Cod AND 
					(retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND Ret_Est = '$Par_Sql[2]' AND proveedore.Emp_Cod = $Par_Sql[3]";		
			return $sql;
			break;

			case 238:
			/**
			* Consulta las compras o liquidaciones de compra en base al Estado y Tipo de comprobante 
			*/
			$esquema_238 = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, 
						Cop_Imf, Cop_Aut, Cop_Cad FROM compras, sustento, proveedore, persona, identifica, tipo_compr
						WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND 
						proveedore.Prs_Cod = persona.Prs_Cod AND proveedore.Ide_Cod = identifica.Ide_Cod AND 
						compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Reg BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
						AND compras.Cop_Est = '$Par_Sql[2]' AND tipo_compr.Tic_Sri = $Par_Sql[3] AND proveedore.Emp_Cod = $Par_Sql[4]";		
			//echo $esquema_238;
			return $esquema_238;
			break;
 		  
			case 260:
			/**
			* Consulta de los datos de la cabecera 
			*/
			$sql = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, Cop_Imf, 	
	Cop_Aut, Cop_Cad, Cop_Reg
	FROM compras, sustento, proveedore, persona, identifica, tipo_compr
	WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND 
	proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND 
	compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
	AND compras.Cop_Est = 'A' AND (compras.Tic_Cod = 1 OR compras.Tic_Cod = 2 OR compras.Tic_Cod = 3 OR compras.Tic_Cod = 10 OR compras.Tic_Cod = 24 OR compras.Tic_Cod = 26) AND compras.Tri_Cod != 1 AND proveedore.Emp_Cod = $Par_Sql[2]";
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta del esquema sin recursividad
			*/
			case 386:
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml, Esq_Ord FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] ORDER BY Esq_Ord ASC";		
			return $sql;
			break; 	

			/**
			* Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional 
			*/
			case 387:
			$sql="	SELECT  
								  persona.Prs_Cod,
								  persona.Prs_Ced,
								  cliente.Cli_Cod,
								  identifica.Ide_Prv
								FROM
								  cliente
								  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
								  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
								  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
								  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
								  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
								WHERE
								  ventas.Vet_Est = '$Par_Sql[0]' AND 
								  caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND cliente.Emp_Cod = $Par_Sql[3]
								GROUP BY
								  persona.Prs_Ced
								ORDER BY
								  persona.Prs_Ced";
								  //echo $sql; //, cliente.Cli_Cod, persona.Prs_Cod, identifica.Ide_Prv
			return $sql;
			break;

			/**
			* Consultando la cabecera de Facturas
			*/
			case 388:
			$sql= "SELECT 
								ventas.Vet_Cod, 
								ventas.Vet_Sys,
								tipo_compr.Tic_Sri 
							FROM 
								caja_aper
								INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
								INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
							WHERE 				  				
								ventas.Cli_Cod = '$Par_Sql[0]' AND 
								ventas.Vet_Est = 'A' AND 
								caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'";
			return $sql;
			break;

			/**
			* Consultando los Detalles de Ventas de Factura
			*/
			case 389:
			$sql="	SELECT 
								  ventas.Vet_Est,
								  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,						  						  SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva,
								  iva.Iva_Por
								FROM
								  ventas
								  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
								  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
								  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
								WHERE
								  ventas.Vet_Est = 'A' AND 
								  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
								  ventas.Cli_Cod ='$Par_Sql[2]'
								GROUP BY
								  ventas.Vet_Est,
								  iva.Iva_Por";					 
			return $sql;
			break;

			/**
			* Consultando las facturas Anuladas en un mes y año determinado Anexos Transaccionales 2010
			*/
			case 390:
			$sql="	SELECT 
								  ventas.Vet_Num,	
								  tipo_compr.Tic_Sri,
								  ventas.Vet_Sys,
								  ventas.Cli_Cod,
								  sucursal.Suc_Sri,
								  autorizaci.Aut_Sri,
								  autorizaci.Pun_Sri  
								FROM
								  ventas
								  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
								  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
								  INNER JOIN vendedor ON (ventas.Vnd_Cod = vendedor.Vnd_Cod)
								  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
								  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
								  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
								WHERE
								  ventas.Vet_Est = 'I' AND 
								  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND sucursal.Emp_Cod = $Par_Sql[2]";				
			return $sql;
			break;

			/**
			* Consultando el total de las facturas
			*/
			case 391:
			$sql="SELECT 
  ventas.Vet_Est,
  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
  SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
FROM
  ventas
  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
WHERE
  ventas.Vet_Est = 'A' AND 
  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
  cliente.Emp_Cod = $Par_Sql[2]
GROUP BY
  ventas.Vet_Est";				
			return $sql;
			break;

			/**
			* Consultando el total de las facturas por punto de impresión
			*/
			case 392:
			$sql="SELECT 
  ventas.Vet_Est,
  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
  sucursal.Suc_Sri
FROM
  ventas
  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE
  ventas.Vet_Est = 'A' AND 
  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
  cliente.Emp_Cod = $Par_Sql[2]
GROUP BY
  ventas.Vet_Est,
  sucursal.Suc_Sri";				
			return $sql;
			break;


			/**
			* Anexo trnsaccional 2010 - retencion del iva 100%
			*/ 
			 case 854:
			 $sql="SELECT  SUM(det_retenc.Ret_Bas) AS Ret_Bas,  det_retenc.Adq_Cod, renta_iva.Ren_Por FROM  retencion
			 INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
			 INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
			 WHERE  renta_iva.Ren_Por = 100 AND retencion.Cop_Cod = $Par_Sql[0] AND  det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A'
			 GROUP BY  det_retenc.Adq_Cod";
			 return $sql;
			 break;	

		   /**
		   * Consulta para obtener valor de codigo d eretencion 332// facturas que no tienen retencion
		   */
		  case 855:
		  $sql="SELECT  SUM(det_compra.Cop_Imp) as Cop_Imp, det_compra.Cop_Cod, det_compra.Cop_Int
			 FROM det_compra WHERE  det_compra.Cop_Int NOT IN 
		  (SELECT det_retenc.Ret_Int FROM retencion INNER JOIN det_retenc ON
		   (retencion.Ret_Cod = det_retenc.Ret_Cod) WHERE retencion.Ret_Est = 'A' AND retencion.Cop_Cod =$Par_Sql[0]) AND 
		  det_compra.Cop_Cod = $Par_Sql[0] AND det_compra.Adq_Cod!=13
		  GROUP by Cop_Cod";
		  return $sql;
		  break;

		}
	}
?>