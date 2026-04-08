<?php
/* Facturación apertura y cierre de caja */
function sentencias_tes($id, $Par_Sql)
{
	switch ($id) {
		case 1:
			/* 
			* Consulta del usuario
			*/
			$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $consulta_4;
			return $consulta_4;
			break;

		case 2:
			/* 
			* Consulta la información la ciudada en base a la sucursal 
			*/
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

		case 3:
			/* 
			* Consulta la provicia y pais de la ciudad de la sucursal 
			*/
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
			/* Consulta de las cajas selecionada */
			$consulta_caja = "SELECT caja_aper.Caj_Cod, Caj_Exi, caja_aper.Caj_Fec, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Est AS Caj_Est2, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est = 'A', 'C a j a - A b i e r t a', 'Caja Cerrada') as Caj_Est, caja_aper.Caj_Fef FROM caja_aper WHERE caja_aper.Caj_Cod = $Par_Sql[0]";
			//echo $consulta_caja;
			return $consulta_caja; //AND caja_aper.Caj_Est = 'A'
			break;

		case 5:
			/* Consulta de las cajas activas */
			$consulta_caja = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Hoi,Caj_Exi, caja_aper.Caj_Hof, caja_aper.Caj_Est AS Caj_Est2, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est = 'A', 'C a j a - A b i e r t a', 'Caja Cerrada') as Caj_Est, puntos_imp.Pun_Des, caja_aper.Caj_Fef FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod  AND caja_aper.Pun_Cod = '$Par_Sql[0]' ORDER BY  caja_aper.Caj_Est, caja_aper.Caj_Fec DESC LIMIT 0,10  ";
			return $consulta_caja; //AND caja_aper.Caj_Est = 'A'
			break;

		case 6:
			/* Apertura de la caja diaria */
			$abrir_caja = "INSERT INTO caja_aper (Caj_Fec, Caj_Hoi, Pun_Cod, Caj_Exi) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', $Par_Sql[2] , '$Par_Sql[3]')";
			//echo $abrir_caja;
			return $abrir_caja;
			break;

		case 7:
			/* Apertura de la caja diaria */
			$cierre_caja = "UPDATE caja_aper SET Caj_Exi = '$Par_Sql[0]', Caj_Est = '$Par_Sql[1]', Caj_Obs = '$Par_Sql[2]', Caj_Hof ='$Par_Sql[4]', Caj_Fef = '$Par_Sql[5]'  WHERE Caj_Cod = '$Par_Sql[3]'";
			return $cierre_caja;
			break;

		case 15:
			/* Verifica si la fecha que se va a guardar ya existe en la base de datos */
			$consultar_fecha = "SELECT Caj_Fec FROM caja_aper WHERE Caj_Fec = '$Par_Sql[0]' AND Pun_Cod = $Par_Sql[1]";
			//echo $consultar_fecha;
			return $consultar_fecha;
			break;

		case 24:
			/*Consulta del vendedor en base al codigo de la persona*/
			$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
								vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
			//echo $consultar_vendedor;
			return $consultar_vendedor;
			break;

		case 25:
			/*Consulta del vendedor en base al codigo de la persona*/
			$sql = "SELECT caja_aper.Caj_Cod,caja_aper.Caj_Fec,caja_aper.Caj_Hoi
					FROM puntos_imp
					  INNER JOIN caja_aper ON (puntos_imp.Pun_Cod = caja_aper.Pun_Cod)
					  INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
					WHERE Pun_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 26:
			/*Consulta del vendedor en base al codigo de la sucursal*/
			$sql = "SELECT distinct persona.Prs_Ape,persona.Prs_Nom,vendedor.Pun_Cod,vendedor.Prs_Cod
					FROM persona
					  INNER JOIN vendedor ON (persona.Prs_Cod = vendedor.Prs_Cod)
					  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
					WHERE Suc_Cod = '$Par_Sql[0]' AND Vnd_Est='A'";
			//echo $sql;
			return $sql;
			break;

		case 27:
			/* Consulta de las cajas activas */
			$sql = "SELECT caja_aper.Caj_Cod, concat(caja_aper.Caj_Fec,'  ',caja_aper.Caj_Hoi)as Caj_Fec, 
			concat(caja_aper.Caj_Fef,'  ',caja_aper.Caj_Hof) AS Caj_Fef, caja_aper.Caj_Obs, Caj_Exi,caja_aper.Pun_Cod, 
			IF (caja_aper.Caj_Est = 'A', 'Abierta', 'Caja Cerrada') as Caj_Est
			FROM caja_aper, puntos_imp 
			WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod  AND caja_aper.Pun_Cod = '$Par_Sql[0]' 
			ORDER BY  caja_aper.Caj_Est, caja_aper.Caj_Fec DESC ";
			return $sql; //AND caja_aper.Caj_Est = 'A'
			break;

		case 28:
			/* Resumen de las ventas segun tipo Pago: Efectivo, Cheque, Tarjeta */
			$sql = "SELECT tipos_pago.Pag_Des, Caj_Fec,Caj_Hoi,Pun_Des, Caj_Obs,Caj_Fef,Caj_Hof,if(Caj_Est='C','Cerrada','Abierta')as Caj_Est,Caj_Exi,
					(Select count(ventas.Vet_Cod)as x from ventas INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod) 
					
					Where Vet_Est='A' AND Caj_Cod='$Par_Sql[0]' AND pago_venta.Pag_Cod=tipos_pago.Pag_Cod)as conteo,
					SUM(IF(Iva_Por=0,ROUND(ventas_det.Vet_Imp, 2),0)) AS Sub0,
					
					SUM(IF(Iva_Por<>0,ROUND(ventas_det.Vet_Imp, 2),0)) AS SubIva,

					SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,	

					SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)  ) AS Iva, 

					SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento,


 			(Select SUM(pago_venta.Vet_Tot)as x from ventas INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod) 
         	Where Vet_Est='A' AND Caj_Cod='$Par_Sql[0]' AND pago_venta.Pag_Cod=tipos_pago.Pag_Cod) as total


					
					FROM ventas 
						INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
						INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod) 
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
						INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)    
						INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)           
						INNER JOIN tipos_pago ON (pago_venta.Pag_Cod = tipos_pago.Pag_Cod)
					WHERE ventas.Caj_Cod='$Par_Sql[0]' AND ventas.Vet_Est = 'A' GROUP BY tipos_pago.Pag_Cod";
			//echo $sql;
			return $sql; //AND caja_aper.Caj_Est = 'A'
			break;

		/*Consultamos total de ventas*/
		case 29:
			$sql = "SELECT SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Importe,
			  iva.Iva_Cod,
			  Ret_Aut,
			  Ret_Num,
			  iva.Iva_Sri,
			  Pun_Des,
			  iva.Iva_Por,
			  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Total,
			  SUM(ROUND(if(Iva_Por=0,(ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),0),2)) AS sub0, 
			  SUM(ROUND(if(Iva_Por<>0,(ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),0),2)) AS sub12,
			  SUM(ROUND(((ventas_det.Vet_Imp-(((Vet_Imp * Vet_Des)/100)+((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento
			FROM persona
				  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
				  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
			WHERE ventas.Caj_Cod='$Par_Sql[0]' AND ventas.Vet_Est = '$Par_Sql[1]' GROUP BY iva.Iva_Sri ORDER BY Iva_Por DESC";
			//echo $sql;
			return $sql;
			break;

		/*Sacamos las formas de pago en una caja determinada*/
		case 30:
			$sql = "SELECT DISTINCT tipos_pago.Pag_Cod, tipos_pago.Pag_Des,Pun_Des, caja_aper.Caj_Cod, caja_aper.Pun_Cod, caja_aper.Caj_Fec,
  					caja_aper.Caj_Fef, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Obs, caja_aper.Caj_Exi,if(Caj_Est='C','Cerrada','Abierta')as Caj_Est, caja_aper.Caj_Gen
					  FROM caja_aper
					  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
					  INNER JOIN tipos_pago ON (pago_venta.Pag_Cod = tipos_pago.Pag_Cod)
					WHERE ventas.Caj_Cod = '$Par_Sql[0]' AND Vet_Est='A'";
			//echo $sql;
			return $sql;
			break;

		case 31:
			/* Resumen de las ventas segun tipo Pago: Efectivo, Cheque, Tarjeta */
			$sql = "SELECT ventas.Vet_Cod,tipo_compr.Tic_Des,ventas.Vet_Num,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Ced,bancos.Bak_Des,pago_venta.Vet_Cue, pago_venta.Vet_Che,
						  /*SUM(IF(Iva_Por = 0, ROUND(ventas_det.Vet_Imp, 2), 0)) AS Sub0,*/
						  SUM(IF(Iva_Por = 0, ROUND(ventas_det.Vet_Imp, 2), 0)) AS Sub0,
						  /*SUM(IF(Iva_Por <> 0, ROUND(ventas_det.Vet_Imp, 2), 0)) AS SubIva,*/ 
						  SUM(IF(Iva_Por <> 0, ROUND(ventas_det.Vet_Imp, 2), 0)) AS SubIva,
						  /*( ROUND( pago_venta.Vet_Tot, 2)  ) AS SubIva,*/
						  SUM(ROUND((ventas_det.Vet_Imp  /* - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))*/ ), 2)) AS Vet_Pag,
						 /* SUM(ROUND(((pago_venta.Vet_Tot - (((Vet_Tot * Vet_Des) / 100) + ((Vet_Tot * Vet_Dec) / 100))) * Iva_Por) / 100, 2)) AS Iva,*/
						  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100, 2)) AS Iva,
						  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)), 2)) AS Descuento
						  
						FROM ventas
						  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
						  INNER JOIN bancos ON (pago_venta.Bak_cod = bancos.Bak_cod)
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
						WHERE 
						ventas.Caj_Cod='$Par_Sql[0]' AND pago_venta.Pag_Cod='$Par_Sql[1]' AND ventas.Vet_Est = 'A' GROUP BY ventas.Vet_Cod";
			//echo "<br>".$sql;
			return $sql; //AND caja_aper.Caj_Est = 'A'
			break;

		case 32:
			/* Resumen de las retenciones de ventas segun tipo Pago: Efectivo, Cheque, Tarjeta */
			$sql = "SELECT ventas.Ret_Num, ventas.Vet_Cod,tipo_compr.Tic_Des,ventas.Vet_Num,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Ced,
			          SUM(IF(renta_iva.Ren_Ret = 'R',ROUND(((ventas_det.Vet_Imp * renta_iva.Ren_Por)/100),2), 0.00)) as Renta,
			          SUM(IF(renta_iva.Ren_Ret = 'I',ROUND((((ventas_det.Vet_Imp * 0.12) * renta_iva.Ren_Por)/100),2), 0.00)) as Iva, 
			          SUM(IF(renta_iva.Ren_Ret = 'R',ROUND(((ventas_det.Vet_Imp * renta_iva.Ren_Por)/100),2), 0.00)) + SUM(IF(renta_iva.Ren_Ret = 'I',ROUND((((ventas_det.Vet_Imp * 0.12) * renta_iva.Ren_Por)/100),2), 0.00))  as Total
								FROM renta_iva,ventas
								  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
								  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
								  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
								  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
								  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
								  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
								  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
								  WHERE 
			            ventas.Caj_Cod='$Par_Sql[0]' 
			            AND pago_venta.Pag_Cod='$Par_Sql[1]' 
			            AND ventas.Vet_Est = 'A' 
			            AND ventas.Ret_Num <> '' 
			            AND (ventas_det.Ren_Cod = renta_iva.Ren_Cod OR ventas_det.Ren_Iva = renta_iva.Ren_Cod)
			            AND ventas.Ret_Fec = caja_aper.Caj_Fec
			            GROUP BY ventas.Vet_Cod";
			//echo "<br>".$sql;
			return $sql; //AND caja_aper.Caj_Est = 'A'
			break;

		case 33:
			$sql = "SELECT
				          SUM(IF(renta_iva.Ren_Ret = 'R',ROUND(((ventas_det.Vet_Imp * renta_iva.Ren_Por)/100),2), 0.00)) as Renta,
				          SUM(IF(renta_iva.Ren_Ret = 'I',ROUND((((ventas_det.Vet_Imp * 0.12) * renta_iva.Ren_Por)/100),2), 0.00)) as Iva, 
				          SUM(IF(renta_iva.Ren_Ret = 'R',ROUND(((ventas_det.Vet_Imp * renta_iva.Ren_Por)/100),2), 0.00)) + SUM(IF(renta_iva.Ren_Ret = 'I',ROUND((((ventas_det.Vet_Imp * 0.12) * renta_iva.Ren_Por)/100),2), 0.00))  as Total,
				          (SELECT COUNT(ventas.Vet_Cod) FROM ventas WHERE ventas.Caj_Cod='$Par_Sql[0]'  AND ventas.Ret_Num <> ''  AND ventas.Ret_Fec = caja_aper.Caj_Fec) as Cantidad
				          FROM renta_iva,ventas
									  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
									  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
									  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
									  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
									  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
									  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
									  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
									  WHERE 
				            ventas.Caj_Cod='$Par_Sql[0]' 
				            AND ventas.Vet_Est = 'A' 
				            AND ventas.Ret_Num <> '' 
				            AND (ventas_det.Ren_Cod = renta_iva.Ren_Cod OR ventas_det.Ren_Iva = renta_iva.Ren_Cod)
				            AND ventas.Ret_Fec = caja_aper.Caj_Fec";
			return $sql;
			break;


		case 34:
			/* Resumen de las ventas segun tipo Pago: Efectivo, Cheque, Tarjeta */
			$sql = "SELECT ventas.Vet_Cod,tipo_compr.Tic_Des,ventas.Vet_Num,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Ced,bancos.Bak_Des,pago_venta.Vet_Cue, pago_venta.Vet_Che,							
				/*SUM(IF(Iva_Por = 0, ROUND(ventas_det.Vet_Imp, 2), 0)) AS Sub0,*/
				(IF(Iva_Por = 0, ROUND(pago_venta.Vet_Tot, 2), 0)) AS Sub0,
				/*  SUM(IF(Iva_Por <> 0, ROUND((ventas_det.Vet_Imp-Vet_Dec)+((Vet_Imp-Vet_Dec)*(Iva_Por / 100)), 2), 0)) AS SubIva,*/
				/* SUM(  IF(Iva_Por <> 0, ROUND(pago_venta.Vet_Tot, 2), 0)) AS SubIva,*/
				IF(Iva_Por <> 0,  (ROUND( pago_venta.Vet_Tot, 2)  )  , 0)  AS SubIva,				
					SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Vet_Pag,
					/*SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100, 2)) AS Iva,*/
					SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)), 2)) AS Descuento
				FROM ventas
					INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
					INNER JOIN bancos ON (pago_venta.Bak_cod = bancos.Bak_cod)
					INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				WHERE 
				ventas.Caj_Cod='$Par_Sql[0]' AND pago_venta.Pag_Cod='$Par_Sql[1]' AND  ventas.Vet_Est = 'A'
					GROUP BY ventas.Vet_Cod,  pago_venta.Vet_Che ";

			//echo "<br>".$sql;
			return $sql; //AND caja_aper.Caj_Est = 'A'
			break;
		
		/* SQL Jose Cumbicos 2025-09-03 */
		/*Sacamos las formas de pago en una caja determinada*/
		case 35:
			$cxc=$Par_Sql['cxc']==='true'?" AND Pag_Abr='CXC'":" AND Pag_Abr!='CXC'";
			$sql = "SELECT DISTINCT tipos_pago.Pag_Cod, IF(Pag_Abr='CXC','VENTAS A CREDITO',tipos_pago.Pag_Des)as Pag_Des,Pag_Abr,Pun_Des, caja_aper.Caj_Cod, caja_aper.Pun_Cod, caja_aper.Caj_Fec,vendedor.Vnd_Cod,vendedor.Prs_Cod,
  					caja_aper.Caj_Fef, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Obs, caja_aper.Caj_Exi,if(Caj_Est='C','Cerrada','Abierta')as Caj_Est, caja_aper.Caj_Gen
					  FROM caja_aper
					  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
					  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
					  INNER JOIN tipos_pago ON (pago_venta.Pag_Cod = tipos_pago.Pag_Cod)
					WHERE ventas.Caj_Cod = '$Par_Sql[Caj_Cod]' $cxc AND Vet_Est='A'";
			//echo $sql;
			return $sql;			
		
		case 36:			
			$sql= "SELECT ventas.Vet_Num,ventas.Vet_Cod,ccpp_cobrar.Cpc_Cod,det_ccpp_c.Cpc_Val,Com_Con,det_ccpp_c.Pag_Cod,Pag_Des,Pag_Abr,concat(Prs_Ape,' ',Prs_Nom)as cliente,
			Bak_Des,Bak_Abr, cheques_ext.Che_Num
			FROM comprobantes
				INNER JOIN cliente on (comprobantes.Cli_Cod = cliente.Cli_Cod)
				INNER JOIN persona on (cliente.Prs_Cod = persona.Prs_Cod)
				INNER JOIN det_ccpp_c ON (comprobantes.Com_Cod = det_ccpp_c.Com_Cod)				
				INNER JOIN tipos_pago ON (det_ccpp_c.Pag_Cod = tipos_pago.Pag_Cod)				
				INNER JOIN ccpp_cobrar ON (det_ccpp_c.Cpc_Cod = ccpp_cobrar.Cpc_Cod)
				INNER JOIN ventas ON (ccpp_cobrar.Vet_Cod = ventas.Vet_Cod)
				LEFT JOIN cheq_det_ccpp ON (det_ccpp_c.Dcc_Cod = cheq_det_ccpp.Dcc_Cod)
				LEFT JOIN cheques_ext ON (cheq_det_ccpp.Che_Cod = cheques_ext.Che_Cod)
				LEFT JOIN bancos ON (cheques_ext.Bak_Cod = bancos.Bak_Cod)
			WHERE comprobantes.Usu_Cod= $Par_Sql[Usu_Cod] and Com_Est='A' and Cpc_Est='A' and Cpc_Fec ='$Par_Sql[Com_Fec]' and tipos_pago.Pag_Abr in ('EFE','CHE','TRF','DEP')";
		//echo $sql;
		return $sql;

		case 37:
			$sql= "SELECT Cop_Num,compras.Cop_Cod,ccpp_pagar.Cpp_Cod,det_ccpp_p.Pag_Val,det_ccpp_p.Pag_Cod,Pag_Des,Pag_Abr,concat(Prs_Ape,' ',Prs_Nom)as proveedor,
			(SELECT Che_Num FROM cheques Inner Join asientos On cheques.Asi_Cod = asientos.Asi_Cod WHERE asientos.Com_Cod=comprobantes.Com_Cod)
			from comprobantes
				inner join proveedore on (comprobantes.Prv_Cod = proveedore.Prv_Cod)
				inner join persona on (proveedore.Prs_Cod = persona.Prs_Cod)
				inner join det_ccpp_p on (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
				inner join tipos_pago on (det_ccpp_p.Pag_Cod = tipos_pago.Pag_Cod)
				inner join ccpp_pagar on (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)
				inner join compras on (ccpp_pagar.Cop_Cod = compras.Cop_Cod)					
			WHERE comprobantes.Usu_Cod= $Par_Sql[Usu_Cod] and Com_Est='A' and det_ccpp_p.Pag_Est='A' and det_ccpp_p.Pag_Fec ='$Par_Sql[Com_Fec]' and tipos_pago.Pag_Abr in ('EFE','CHE','TRF','DEP')";
		//echo $sql;	
		return $sql;

		case 38:
			$sql="SELECT compras.Cop_Cod,compras.Cop_Fec,compras.Cop_Sec,tipo_compr.Tic_Des,compras.Cop_Num,compras.Cop_Aut,ccpp_pagar.Cpp_Cod,det_reposicion.Cop_Cod,
				persona.Prs_Ced,concat(persona.Prs_Ape,' ',persona.Prs_Nom)as proveedor,proveedore.Prv_Com,
				SUM(IF(Iva_Por = 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0')) AS Sub0,
				SUM(IF(Iva_Por != 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0')) AS Sub12,
				SUM( (Cop_Pru * Cop_Can)*(compras.Cop_Des/100)) AS Descu,
				SUM(IF(Iva_Por != 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0'))*Iva_Por/100 AS IvaTot,
				sum( ( 
				(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
					+(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
					)	*(1+iva.Iva_Por/100)	/* IVA */
				) AS total,
				(select COALESCE(cast(SUM(Ret_Bas*Ren_Por/100) as decimal(10,2)),0)as total
				from retencion 
					inner join det_retenc on retencion.Ret_Cod = det_retenc.Ret_Cod 
					inner join renta_iva on det_retenc.Ren_Cod = renta_iva.Ren_Cod
				where retencion.Cop_Cod=compras.Cop_Cod and Ret_Est='A')as rete
			from compras
				INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
				LEFT JOIN ice ON (ice.Ice_int=det_compra.Ice_Int)
				INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
				INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
				INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
				INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod) 
				LEFT JOIN ccpp_pagar on (compras.Cop_Cod = ccpp_pagar.Cop_Cod) /* Evita compras q esten a credito */
				LEFT JOIN det_reposicion on (compras.Cop_Cod = det_reposicion.Cop_Cod) /* Evita compras pagas con caja chica */
			WHERE
				proveedore.Emp_Cod='$Par_Sql[Emp_Cod]' AND Cop_Est='A' and Cop_Fec = '$Par_Sql[Cop_Fec]' and compras.Vnd_Cod = '$Par_Sql[Vnd_Cod]' AND ccpp_pagar.Cpp_Cod is null
				AND det_reposicion.Cop_Cod is null
			GROUP BY compras.Cop_Cod  order by compras.Cop_Cod Asc";
		//echo $sql;	
		return $sql;
		/* REsumen de Retencones en ventas segun CAJ_COD */
		case 39:
			$sql="SELECT ventas.Vet_Cod,tipo_compr.Tic_Des,ventas.Vet_Num,Ret_Num,concat(Prs_Ape,' ',Prs_Nom)as cliente,persona.Prs_Ced,	
			SUM(ROUND((ventas_det.Vet_Imp-Vet_Dec), 2)) AS SubTotal,
			CAST((SUM(CAST((ventas_det.Vet_Imp-Vet_Dec) as decimal(10,2))) * Ren_Por /100)as decimal(10,2)) as ret
			FROM ventas	
				INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
				INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
				INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				INNER JOIN renta_iva ON (ventas_det.Ren_Cod = renta_iva.Ren_Cod)
			WHERE ventas.Caj_Cod='$Par_Sql[Caj_Cod]' AND ventas.Vet_Est = 'A' GROUP BY ventas.Vet_Cod  ";
		//echo $sql;	
		return $sql;
		
	}
}
