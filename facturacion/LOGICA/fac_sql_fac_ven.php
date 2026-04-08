<?php

/**
 * Facturación inventario de las ventas
 */
function sentencias_tes($id, $Par_Sql)
{
	switch ($id) {
		case 1:
			/* Consulta las facturas de la caja activa para modificarlas */
			$mod_factura_320 = "SELECT 
  cliente.Cli_Cod,
  persona.Prs_Ape,
  persona.Prs_Nom,
  ventas.Vet_Num,
  caja_aper.Caj_Fec,
  ventas.Vet_Cod,
  cliente.Cli_Est,
  ventas.Vet_Est,
  puntos_imp.Pun_Des
FROM
  caja_aper
  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod) WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
							  ventas.Tic_Cod = $Par_Sql[1]  AND cliente.Emp_Cod = $Par_Sql[2] AND 
							  YEAR(caja_aper.Caj_Fec) = '$Par_Sql[3]' $Par_Sql[4]  ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $mod_factura_320;
			return $mod_factura_320;
			break;

		case 2:
			/* Consulta de las facturas por el codigo interno de la caja activa */
			$mod_factura_int_321 = "SELECT 
  cliente.Cli_Cod,
  persona.Prs_Ape,
  persona.Prs_Nom,
  ventas.Vet_Num,
  caja_aper.Caj_Fec,
  ventas.Vet_Cod,
  cliente.Cli_Est,
  ventas.Vet_Est,
  puntos_imp.Pun_Des
FROM
  caja_aper
  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod) WHERE ventas.Vet_Num = '$Par_Sql[0]' AND 
							  ventas.Tic_Cod = $Par_Sql[1]          AND cliente.Emp_Cod = $Par_Sql[2] 
								ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			// echo $mod_factura_int_321;
			return $mod_factura_int_321;
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
			/* 
		* Consulta del usuario
		*/
			$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $consulta_4;
			return $consulta_4;
			break;

		case 5:
			/**
			 * Insertar datos del detalle de la factura para contrato y el indice
			 */
			$sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[0], Pro_Cod=$Par_Sql[1], Vet_Can=$Par_Sql[2], 
				Iva_Cod=$Par_Sql[3], Vet_Pru=$Par_Sql[4], Vet_Imp=$Par_Sql[5], Vet_Dec='$Par_Sql[6]', Nge_Cod = $Par_Sql[7],
				Asi_Int=$Par_Sql[8], Vet_Rec=$Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni=$Par_Sql[12], Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14]";
			//echo $sql."<br>";
			return $sql;
			break;

		case 6:
			/**
			 * Verifica si busca deudas de servicios o matriculas
			 */
			$sql = "SELECT COUNT(cliente.Cli_Cod) AS 'count' FROM persona
      INNER JOIN estudiante ON persona.Prs_Cod=estudiante.Prs_Cod
      INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod
      WHERE
      cliente.Cli_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;

		case 12:
			/* 
		* Consulta el codigo del proceso 
		*/
			$consulta_proceso_12 = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
			//echo $consulta_proceso_12;
			return $consulta_proceso_12;
			break;

		case 13:
			/* 
		* Consulta el reporte recursivo 
		*/
			$consulta_proceso_13 = "SELECT 
			  reportes.Rep_Cod,
			  procesos.Pcs_Nom,
			  reportes.Rep_Ord,
			  rutas.Rut_Des
			FROM
			  procesos
			  INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
			  INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) 
			WHERE 
			  reportes.Pcs_Cod = $Par_Sql[0] AND reportes.Emp_Cod = $Par_Sql[1] ORDER BY reportes.Rep_Ord";
			//echo $consulta_proceso_13;
			return $consulta_proceso_13;
			break;

		case 16:
			/**
			 * Consulta la forma de pago 
			 */
			$sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
			return $sql;
			break;

		case 17:
			/**
			 * Consulta el tipo de pago en base a la forma de pago 
			 */
			$sql = "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE tipos_pago.For_Cod = $Par_Sql[0] AND tipos_pago.Pag_Est = 'A'";
			return $sql;
			break;

		case 18:
			/* Consulta del banco*/
			$consultar_pago = "SELECT Bak_Cod, Bak_Des FROM bancos WHERE Bak_Est = 'A' ORDER BY Bak_Des ASC";
			return $consultar_pago;
			break;

		case 19:
			/* insertar datos de la factura*/
			$sql = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Vet_Ntd,Vet_Fdm,Vet_Nns) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]', '$Par_Sql[6]',$Par_Sql[7],'$Par_Sql[8]','$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]','$Par_Sql[14]')";
			//echo  $sql."<br>";
			return $sql;
			break;

		case 20:
			/* insertar datos de la factura*/
			$inser_factpago_20 = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut,Tpc_Cod,Vet_Ntd,Vet_Fdm,Vet_Nns) 
			VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]', '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]', '$Par_Sql[10]','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]','$Par_Sql[14]','$Par_Sql[15]','$Par_Sql[16]','$Par_Sql[17]','$Par_Sql[18]')";
			//echo  $inser_factpago_20."<br>";
			return $inser_factpago_20;
			break;

		case 21:
			/* Consulta del cliente si es una persona por apellidos */
			$consultar_buscar_21 = "SELECT cliente.Cli_Cod, persona.Prs_Cod,persona.Prs_Ced, persona.Prs_Ape, 
			persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') 
			as Cli_Est,Prs_Dir FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			//echo $consultar_buscar_21;
			return $consultar_buscar_21;
			break;

		case 22:
			/* Consulta del personal por cedula */
			$consultar_cliente1 = "SELECT cliente.Cli_Cod, persona.Prs_Cod,persona.Prs_Ced, persona.Prs_Ape,        persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est,Prs_Dir FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced = '$Par_Sql[0]'  AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY	persona.Prs_Ape, persona.Prs_Nom ASC";
			//echo "<br>".$consultar_cliente1;
			return $consultar_cliente1;
			break;

		case 23:
			/* Consulta de los datos del cliente */
			$consultar_cliente = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
							persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, cliente.Cli_Fac, cliente.Cli_Ruf, cliente.Cli_Dir
							FROM cliente, persona WHERE persona.Prs_Cod = cliente.Prs_Cod AND cliente.Cli_Cod = $Par_Sql[0]";
			//echo $consultar_cliente;
			return $consultar_cliente;
			break;

		case 24:
			/**
			 * Consulta del vendedor en base al codigo de la persona
			 */
			$sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
			//echo $sql;
			return $sql;
			break;

		case 25:
			/* 
			* Consulta de la caja activa en base al vendedor 
			*/
			$consultar_caja_25 = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod, Pun_Des FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND
							caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
			//echo $consultar_caja_25;
			return $consultar_caja_25;
			break;

		case 26:
			/*
			* Consulta la ciudad en base al usuario
			*/
			$consultar_ciudad = "SELECT sucursal.Ciu_Cod, ciudad.Ciu_Des FROM usuarios, sucursal, ciudad 
						WHERE usuarios.Suc_Cod = sucursal.Suc_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND usuarios.Usu_Cod = 
						'$Par_Sql[0]'";
			return $consultar_ciudad;
			break;

			/** Selecionar el numero maximo de la factura**/
		case 27:
			$num_fac = "SELECT MAX(Vet_Num) AS Num FROM ventas, autorizaci  WHERE ventas.Aut_Cod = autorizaci.Aut_Cod 
			AND autorizaci.Aut_Cod = $Par_Sql[0]"; //AND ventas.Tic_Cod = $Par_Sql[0]
			//echo $num_fac;
			return $num_fac;
			break;

		case 29:
			/* insertar datos del detalle de la factura*/
			$inser_detafac_29 = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[0], Pro_Cod=$Par_Sql[1], Vet_Can=$Par_Sql[2], 
					Iva_Cod=$Par_Sql[3], Vet_Pru=$Par_Sql[4], Vet_Imp=$Par_Sql[5], Vet_Dec='$Par_Sql[6]', Nge_Cod = $Par_Sql[7],
					Asi_Int = $Par_Sql[8], Vet_Rec = $Par_Sql[9]";
			//echo $inser_detafac_29;
			return $inser_detafac_29;
			break;

		case 30:
			/*Consulta cargar la autorizacion activa de acuerdo a un tipo de comprobante*/
			$consultar_aut_30 = "SELECT autorizaci.Aut_Cod, autorizaci.Aut_Cad, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Adv, autorizaci.Aut_Ads FROM autorizaci WHERE autorizaci.Tic_Cod = $Par_Sql[0] 	
			AND autorizaci.Pun_Cod = '$Par_Sql[1]'
			AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";
			//echo $consultar_aut_30;
			return $consultar_aut_30;
			break;

		case 33:
			/*
			* Consulta del representate del cliente cuando se trata de un estudiante
			*/
			$consulta_rep = "SELECT cliente.Cli_Fac, cliente.Cli_Ruf, cliente.Cli_Dir FROM cliente WHERE cliente.Cli_Cod = $Par_Sql[0]";
			//echo $consulta_rep;
			return $consulta_rep;
			break;

		case 34:
			/* actualizar datos de la factura*/
			$up_factpago_34 = "UPDATE ventas SET Vet_Num = '$Par_Sql[0]', Vet_Obs = '$Par_Sql[1]', Vet_Des = '$Par_Sql[2]', Ret_Fec='$Par_Sql[3]',Ret_Num='$Par_Sql[4]',Ret_Aut='$Par_Sql[5]', Vnd_Cod='$Par_Sql[6]' WHERE Vet_Cod = $Par_Sql[7]";
			//echo "<br>".$up_factpago_34;
			return $up_factpago_34;

		case 35:
			/* actualizar datos de la factura*/
			$up_factpago_34 = "UPDATE ventas SET Vet_Num = '$Par_Sql[0]', Vet_Obs = '$Par_Sql[1]', Vet_Des = '$Par_Sql[2]', Ret_Fec='$Par_Sql[3]',Ret_Num='$Par_Sql[4]',Ret_Aut='$Par_Sql[5]'/*, Vnd_Cod='$Par_Sql[6]'*/,Tpc_Cod='$Par_Sql[8]' WHERE Vet_Cod = $Par_Sql[7]";
			//echo "<br>".$up_factpago_34;
			return $up_factpago_34;
			break;

		case 37:
			/* 
			* Consulta de los datos del cliente 
			*/
			$consultar_cli_fac_37 = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, persona.Prs_Tel, 	
			persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, ventas.Aut_Cod, ventas.Tic_Cod,Tic_Sri, ventas.Vet_Obs as Vet_Obs, caja_aper.Caj_Fec, ciudad.Ciu_Des, ventas.Vet_Des,ventas.Vet_Xml, ventas.Ret_Num, ventas.Ret_Fec, ventas.Ret_Aut, caja_aper.Pun_Cod, ventas_det.Vet_Can, ventas_det.Ren_Cod, ventas_det.Ren_Iva, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, item.Ite_Cor, item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, ventas_det.Iva_Cod, ventas_det.Pro_Cod, ventas.Vet_Est,ventas.Vnd_Cod,Vet_Xml,Vet_Aut,Vet_Sri, 	
			producto.Pro_Ide, producto.Uni_Cod, producto.Pro_Obs, Nge_Cod, Asi_Int, Vet_Rec, Tic_Des, cliente.Cli_Fac, cliente.Cli_Ruf, cliente.Cli_Dir, ventas_det.Cnt_Cod, ventas_det.Vet_Int,Vet_Sys,ventas_det.Vet_Ite, Mar_Des, Vet_Uni,Uni_Des,Tpc_Cod,Vet_Ntd,Vet_Fdm,Vet_Nns,ventas_det.Des_Adi, CONCAT(LPAD(sucursal.Suc_Sri, 4, '0'), '-', LPAD(autorizaci.Pun_Sri, 4, '0'), '-', LPAD(ventas.Vet_Num, 9, '0')) AS Fac_Num
			FROM cliente, persona, ventas, caja_aper, ciudad, ventas_det, item, iva, producto, tipo_compr, marca ,unidad, puntos_imp, sucursal,autorizaci 
			WHERE cliente.Cli_Cod = ventas.Cli_Cod 
			AND caja_aper.Caj_Cod = ventas.Caj_Cod 
			
			
			AND persona.Ciu_Cod = ciudad.Ciu_Cod
			
			AND ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Mar_Cod = marca.Mar_Cod AND producto.Uni_Cod=unidad.Uni_Cod AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND caja_aper.Pun_Cod=puntos_imp.Pun_Cod AND sucursal.Suc_Cod=puntos_imp.Suc_Cod AND autorizaci.Aut_Cod=ventas.Aut_Cod  AND
			persona.Prs_Cod = cliente.Prs_Cod AND ventas.Tic_Cod = tipo_compr.Tic_Cod AND ventas.Vet_Cod = '$Par_Sql[0]' AND Vet_Rec = 0"; //         AND iva.Iva_Cod = producto.Iva_Cod
			//echo $consultar_cli_fac_37;
			return $consultar_cli_fac_37;
			break;

			/**  
			 * --AND ventas.Ciu_Cod = ciudad.Ciu_Cod 
			 * Actualizar datos del detalle de la factura en base a la matricula
			 */
		case 38:
			$sql = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9]";
			return $sql;
			break;

		case 39:
			/* Consulta de los valores de las facturas para realizar calculos */
			$calcular_fac = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp,
									ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Cod, 
									ventas.Vet_Des, pago_venta.Vet_Mon, pago_venta.Vet_Cam
								FROM ventas, ventas_det, iva,
									( SELECT Vet_Cod,
											SUM(Vet_Mon) AS Vet_Mon,
											MAX(Vet_Cam) AS Vet_Cam
										FROM pago_venta
										GROUP BY Vet_Cod
									) pago_venta
								WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND
								ventas_det.Iva_Cod = iva.Iva_Cod AND
								ventas.Vet_Cod = pago_venta.Vet_Cod AND
								ventas.Vet_Cod = '$Par_Sql[0]'";
			return $calcular_fac;
			break;

			/**
			 * Actualizar datos del detalle de la factura en base a acta de notas, contratos e indice
			 */
		case 40:
			$sql = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni='$Par_Sql[12]', Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14], Vet_Ite='$Par_Sql[15]'";
			//echo "<br>".$sql;
			return $sql;
			break;


		case 43:
			/* Consulta del producto en base al codigo manual del producto */
			$consul_prod =  "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, Pre_Pvp, Iva_Por, iva.Iva_Cod, producto.Pro_Sal FROM producto, item,        
						precios, iva WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = precios.Pro_Cod AND producto.Iva_Cod =        
						iva.Iva_Cod AND producto.Pro_Ide = '$Par_Sql[0]' AND producto.Pro_Est = 'A'";
			//echo $consul_prod;
			return $consul_prod;
			break;

			/* Borrado del codigo del detalle de la Venta */
		case 44:
			$bor_precio = "DELETE FROM ventas_det WHERE Vet_Cod='$Par_Sql[0]'";
			return $bor_precio;
			break;

			/** Selecionar el numero maximo del codigo del producto**/
		case 46:
			$Pro_conf = "SELECT Pro_Ide, Col_Eli, Col_Cad FROM confi_teso WHERE Con_Cod = 1";
			return $Pro_conf;
			break;

		case 51:
			/* Verifica si a un producto se le debe calcular el interes */
			$produc_interes_51 = "SELECT prod_inter.Pro_Cod FROM prod_inter WHERE Pro_Cod = $Par_Sql[0]";
			//echo $produc_interes_51;
			return $produc_interes_51;
			break;

			/* Consulta la cantidad de dias de retrazo de la deuda */
		case 54:
			$mora_deuda_54 = "SELECT datediff(Deu_Fec, now()) as Mora, Deu_Fec FROM deudas WHERE Cli_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1] AND Nge_Cod = $Par_Sql[2] 
							AND Asi_Int = $Par_Sql[3] AND Deu_Rec = 0";
			//echo $mora_deuda_54;		
			return $mora_deuda_54;
			break;

			/* Consultar los rubros destinados para el interes */
		case 56:
			$consul_interes_56 = "SELECT 
  interes.Pro_Cod,
  interes.Int_Por,
  interes.Int_Dia
FROM
  producto
  INNER JOIN interes ON (producto.Pro_Cod = interes.Pro_Cod)
  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
WHERE
  categorias.Emp_Cod = $Par_Sql[0]";
			return $consul_interes_56;
			break;

			/* Consulta si ya se encuentra agregado un rubro recursivo (INTERES) */
		case 57:
			$deuda_recur_57 = "SELECT Deu_Reg, Deu_Fec, Pro_Cod, datediff(Deu_Reg, now()) as Dias_Mora, Deu_Val, Deu_Obs FROM deudas 
							WHERE Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
							AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3]";
			//echo $deuda_recur_57;
			return $deuda_recur_57;
			break;


			/* Consulta de los rubros recursivos, especialmente INTERES */
		case 58:
			$deuda_58 = "SELECT deudas.Pro_Cod, Pro_Ide, Deu_Val, Deu_Fec, Ite_Lar, producto.Iva_Cod, Iva_Por, Nge_Cod, Deu_Rec, Asi_Int FROM 
						deudas, producto, item, iva WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Iva_Cod = iva.Iva_Cod AND				
						producto.Ite_Cod = item.Ite_Cod AND Cli_Cod = $Par_Sql[0] AND Nge_Cod = 
						$Par_Sql[1] AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3]";
			//echo $deuda_58;		
			return $deuda_58;
			break;

		case 59:
			/*Consulta de los rubros sin precio en base al ID y la nota general*/
			$productos_59 = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, producto.Iva_Cod, Iva_Por, Pre_Pvp
					FROM producto, item, iva, precios        
					WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Iva_Cod = iva.Iva_Cod AND 
					producto.Pro_Ide = '$Par_Sql[0]' AND producto.Pro_Est = 'A' AND producto.Pro_Cod = precios.Pro_Cod AND
					producto.Pro_Cod NOT IN (SELECT  deudas.Pro_Cod FROM  deudas, notasgener WHERE deudas.Nge_Cod = 
					notasgener.Nge_Cod AND notasgener.Nge_Cod = $Par_Sql[1])";
			//echo $productos_59;
			return $productos_59;
			break;

			/* cargar semestre y la notageneral en base al periodo y al cliente */
			/* Esta funcion toma el primer dia de matriculas ordinarias hasta el fin de clases */
		case 63:
			$nota_63 = "SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2, persona.Prs_Nom, carreras.Car_Nom, modalidad.Mod_Des,
					view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin, view_periodos_suc.Per_Fea,
					view_periodos_suc.Per_Fef, notasgener.Nge_Cod FROM estudiante INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
					INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod) INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int) INNER JOIN view_cursos_mal ON (view_cursos_mal.Sem_Cod = 
					notasgener.Sem_Cod) AND (view_cursos_mal.Sem_Cod = matriculas.Sem_Cod) INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
					INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int) INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = 
					modalidad.Mod_Cod) WHERE matriculas.Mat_Est = 'A' AND ('$Par_Sql[0]' BETWEEN 
					Pem_Ini AND Per_Fec) AND cliente.Cli_Cod = $Par_Sql[1] AND view_cursos_mal.Car_Int = $Par_Sql[2] GROUP BY view_cursos_mal.Sem_Cod ORDER BY 
					view_periodos_suc.Per_Fea DESC";
			return $nota_63;
			break;

			/* cargar asignatura en base al semestre y al codigo del cliente */
		case 64:
			$actualiza_interes_64 = "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Reg = '$Par_Sql[1]' WHERE Nge_Cod = $Par_Sql[2] AND 
						Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6]";
			//echo $actualiza_interes_64;
			return $actualiza_interes_64;
			break;

			/* Consulta los pagos realizados por el cliente */
		case 68:
			$pagos_68 = "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
					ventas_det.Vet_Cod AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
					= $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND ventas.Vet_Est = 'A'";
			//echo $pagos_68;
			return $pagos_68;
			break;

			/*  Consulta las becas en base al codigo del cliente */
		case 73:
			$becas_cli_73 = "SELECT becas.Bec_Cod, det_becas.Pro_Cod, Bec_Pot, Bec_Por, Tib_Ini FROM becas, matriculas, estudiante, 
					persona, cliente, det_becas, tipo_beca WHERE becas.Mat_Int = matriculas.Mat_Int AND matriculas.Est_Int = 
					estudiante.Est_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND 
					becas.Bec_Cod = det_becas.Bec_Cod AND becas.Tib_Cod = tipo_beca.Tib_Cod AND
					cliente.Cli_Cod = $Par_Sql[0] AND det_becas.Pro_Cod = $Par_Sql[1] 
					AND matriculas.Sem_Cod = $Par_Sql[2]  AND becas.Bec_Est = 'A'";
			//echo $becas_cli_73;
			return $becas_cli_73;
			break;

		case 74:
			/**
			 * Consulta de los datos de los rubros recursivos 
			 */
			$sql = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, ventas_det.Vet_Can,
			 item.Ite_Cor, item.Ite_Lar, Ite_Cor, iva.Iva_Por, ventas.Vet_Cod, ventas_det.Iva_Cod, ventas_det.Pro_Cod, producto.Pro_Ide, Nge_Cod, Asi_Int, Vet_Rec
			 FROM  ventas, ventas_det, item, iva, producto WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod
			 AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas.Vet_Cod = '$Par_Sql[0]'
			 AND Nge_Cod = $Par_Sql[1] AND Asi_Int = $Par_Sql[2] AND Vet_Rec = $Par_Sql[3]";
			//echo $sql;
			return $sql;
			break;

		case 75:
			/**
			 * Consulta de los datos de los rubros recursivos en base a actas de notas, contratos e indice
			 */
			$sql = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec,
			 item.Ite_Cor, item.Ite_Lar, Ite_Cor, iva.Iva_Por, ventas.Vet_Cod, ventas_det.Iva_Cod, ventas_det.Pro_Cod, producto.Pro_Ide, Nge_Cod, Asi_Int, Vet_Rec
			 FROM  ventas, ventas_det, item, iva, producto WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod
			 AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas.Vet_Cod = '$Par_Sql[0]'
			 AND Nge_Cod = $Par_Sql[1] AND Asi_Int = $Par_Sql[2] AND Vet_Rec = $Par_Sql[3] AND Cnt_Cod = $Par_Sql[4] AND Vet_Int = $Par_Sql[5]";
			//echo $sql;
			return $sql;
			break;

			/*  Consulta la beca asignada a un rubro */
		case 76:
			$beca_asignada_76 = "SELECT Bec_Pot, Bec_Por, tipo_beca.Tib_Ini, tipo_beca.Tib_Cod, Mat_Int, tipo_beca.Tib_Des FROM becas, det_becas, tipo_beca WHERE becas.Bec_Cod = det_becas.Bec_Cod 
								AND becas.Tib_Cod = tipo_beca.Tib_Cod AND becas.Bec_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1]";
			//echo $beca_asignada_76;
			return $beca_asignada_76;
			break;

		case 78:
			/*Consulta de las carreras las cuales  ha cursado un estudiante*/
			$rs_carreras_si = "SELECT carreras.Car_Int, Car_Nom, semestres.Sem_Cod FROM carreras, estudiante, matriculas, promocione, semestres, persona, cliente WHERE estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod 
	AND semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND persona.Prs_Cod = estudiante.Prs_Cod 
	AND cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = $Par_Sql[0] AND cliente.Cli_Est = 'A' AND cliente.Emp_Cod = $Par_Sql[1] GROUP BY Car_Int
	ORDER BY matriculas.Mat_Fec DESC";
			//echo $rs_carreras_si;
			return $rs_carreras_si;
			break;

			/**
			 *  Concatenar el numero de factura 
			 */
		case 81:
			$Num_factura = "SELECT 
  autorizaci.Aut_Sri,
  autorizaci.Pun_Sri,
  sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin,Aut_Tem
FROM
  puntos_imp
  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
WHERE autorizaci.Aut_Cod = $Par_Sql[0]";
			//echo $Num_factura;
			return $Num_factura;
			break;

		case 90:
			/*
		* Consulta los puntos de impresion 
		*/
			$consultar_punto_impre = "SELECT puntos_imp.Pun_Cod, puntos_imp.Pun_Des FROM puntos_imp WHERE puntos_imp.Suc_Cod =  $Par_Sql[0] AND puntos_imp.Pun_Est = 'A' ";
			return $consultar_punto_impre;
			break;

		case 91:
			/* Consulta del cliente de la factura por apellidos */
			$anular_fac = "SELECT ventas.Cli_Cod, persona.Prs_Ape,persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, 
						IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est 
					   FROM 
					   	  persona, cliente, ventas, caja_aper  
					   WHERE 
					     cliente.Prs_Cod = persona.Prs_Cod AND 
						 caja_aper.Caj_Cod = ventas.Caj_Cod AND  
						 persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
						 caja_aper.Caj_Fec = '$Par_Sql[1]' AND 
						 ventas.Cli_Cod = cliente.Cli_Cod AND 
						 ventas.Tic_Cod = '$Par_Sql[2]' AND 
						 caja_aper.Pun_Cod = '$Par_Sql[3]'
					   ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
			//echo $anular_fac;
			return $anular_fac;
			break;

		case 92:
			/* Consulta de las fechas de acuerdo a la caja activa para eliminar la factura*/
			$elim_facturaCaj = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod, Pun_Des
FROM caja_aper, puntos_imp  WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
			//echo $elim_facturaCaj;
			return $elim_facturaCaj;
			break;

		case 93:
			/* Consulta de la factura por el numero interno y dependiendo del punto de impresion*/
			$consultar_Numfact_punto = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper  WHERE cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND ventas.Tic_Cod = $Par_Sql[1] AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  ventas.Vet_Num = '$Par_Sql[0]' AND caja_aper.Pun_Cod = $Par_Sql[2] AND caja_aper.Caj_Fec = '$Par_Sql[3]' ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
			//echo $consultar_Numfact_punto;
			return $consultar_Numfact_punto;
			break;

		case 94:
			/*Busca las facturas registradas de acuerdo a los intervalos de fecha y por punto de impresión*/
			$cons_fact_punto = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, SUM(ventas_det.Vet_Imp) as Vet_Imp, 
ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, caja_aper.Caj_Est FROM caja_aper, ventas, ventas_det, cliente, iva, persona WHERE ventas.Cli_Cod 
= cliente.Cli_Cod AND ventas.Caj_Cod = caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND ventas_det.Iva_Cod 
= iva.Iva_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND caja_aper.Pun_Cod = $Par_Sql[2] AND ventas.Tic_Cod = $Par_Sql[3] 
GROUP BY ventas.Vet_Cod";
			//echo $cons_fact_punto;
			return $cons_fact_punto;
			break;

			/* 
		* Seleccionar el numero maximo de la factura
		*/
		case 96:
			$selecciona_NumMax_96 = "SELECT MAX(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]'
					AND (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]";
			//echo $selecciona_NumMax_96;
			return $selecciona_NumMax_96;
			break;

			/* 
	   * Seleccionar el numero minimo de la factura
	   */
		case 97:
			$selecciona_NumMin = "SELECT MIN(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]'
					AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]";
			//echo $selecciona_NumMin;
			return $selecciona_NumMin;
			break;

			/**
			 * Consulta el valor de la cuenta por cobrar de una deuda 
			 */
		case 98:
			$sql = "SELECT deudas.Deu_Val FROM  deudas WHERE deudas.Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
						AND Pro_Cod = $Par_Sql[2] AND Asi_Int = $Par_Sql[3]";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta el valor de la cuenta por cobrar de una deuda con acta de notas, contratos e indice
			 */
		case 98:
			$sql = "SELECT deudas.Deu_Val FROM  deudas WHERE deudas.Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
						AND Pro_Cod = $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND Cnt_Cod = $Par_Sql[4] AND Vet_Int = $Par_Sql[5]";
			//echo $sql;
			return $sql;
			break;

		case 106:
			/*
		* Busca las facturas registradas de acuerdo a los intervalos de fecha
		*/
			$cons_fact_106 = "SELECT ventas.Vet_Cod, ventas.Vet_Num,ventas.Ret_Num,ventas.Ret_Fec,ventas.Ret_Aut, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec, ventas.Vet_Est,
					  SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, 					   
					  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,					  
					  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
					  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 					  
					  FROM ventas INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE (Caj_Fec BETWEEN 
					  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY ventas.Vet_Cod, 
					  ventas.Vet_Num, caja_aper.Caj_Fec,
					  persona.Prs_Nom, persona.Prs_Ape, ventas.Vet_Est, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
			//echo $cons_fact_106;		
			return $cons_fact_106;
			break;

		case 107:
			/* Actualizar estado de la facura */
			$actualizar_est_fact = "UPDATE ventas SET Vet_Est = '$Par_Sql[0]' WHERE Vet_Cod = '$Par_Sql[1]'";
			return $actualizar_est_fact;
			break;

		case 110:
			/*
		* Busca el total de facturas de acuerdo a la carrera
		*/
			$my_cons_fact_escuela_110 = "
                SELECT ventas.Vet_Cod, ventas.Vet_Num,ventas.Ret_Num,ventas.Ret_Fec,ventas.Ret_Aut, caja_aper.Caj_Fec,  
					persona.Prs_Nom, persona.Prs_Ape, Car_Nom, sum(ventas_det.Vet_Imp) AS Vet_Tot, 
					ventas_det.Vet_Dec, ventas.Vet_Est, ventas_det.Asi_Int 
					FROM caja_aper, ventas, ventas_det, cliente, iva, persona, carreras, niveles, semestres, promocione, notasgener 
					WHERE ventas.Caj_Cod = caja_aper.Caj_Cod 
					AND ventas_det.Vet_Cod = ventas.Vet_Cod 
					AND ventas.Cli_Cod = cliente.Cli_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod 
			AND cliente.Prs_Cod = persona.Prs_Cod AND notasgener.Nge_Cod = ventas_det.Nge_Cod 
			AND notasgener.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod 
			AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int = carreras.Car_Int 
			AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND promocione.Car_Int = $Par_Sql[2] 
			AND ventas.Vet_Est = '$Par_Sql[3]' AND ventas.Tic_Cod = $Par_Sql[4] $Par_Sql[5] 
			GROUP BY ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec,  persona.Prs_Nom, 
			persona.Prs_Ape, Car_Nom, ventas_det.Vet_Dec, ventas.Vet_Est, 
			ventas_det.Asi_Int
			ORDER BY ventas.Vet_Num, Prs_Ape, Prs_Nom";
			//echo $my_cons_fact_escuela_110;					
			return $my_cons_fact_escuela_110;
			break;

		case 126:
			/* 
		* Consulta la información la ciudada en base a la sucursal 
		*/
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir,Suc_Sri,sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log,Emp_Cnt, confi_fact.* FROM empresas, sucursal, ciudad, confi_fact WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod AND confi_fact.Emp_Cod = empresas.Emp_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

			/*************************************************************************/
			/**************************** SQL Lewis - Deudas *************************/
			/* Consulta el codigo de la matricula obtenida en el semestre actual (Normalmente debe ser un solo registro), solo las matriculas de tipo N=normal */
		case 168:
			$matriculas_168 = "SELECT matriculas.Mat_Int, matriculas.Sem_Cod, Nge_Cod, matriculas.Pem_Cod FROM matriculas, estudiante, persona, 
							cliente, semestres, periodos, notasgener WHERE matriculas.Est_Int = estudiante.Est_Int AND 
							estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND matriculas.Sem_Cod = 
							semestres.Sem_Cod AND semestres.Per_Int = periodos.Per_Int AND matriculas.Mat_Int = notasgener.Mat_Int 
							AND notasgener.Sem_Cod = semestres.Sem_Cod 
							AND cliente.Cli_Cod = $Par_Sql[1] AND matriculas.Mat_Est='$Par_Sql[2]' AND matriculas.Mat_For = 'N'"; //ANtes AND ('$Par_Sql[0]' BETWEEN Per_Fea AND Per_Fec) 
			//echo $matriculas_168;
			return $matriculas_168;
			break;

			/* Carga todos los costos menores o iguales a partir de una fecha para su generación */
		case 169:
			$costos_169 = "SELECT costos.Tio_Cod, Pro_Cod, Cos_Pre, Cos_Gen, Cos_Fec, Asi_Int FROM costos, tipo_costo WHERE costos.Tio_Cod 
							= tipo_costo.Tio_Cod AND Sem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Tio_Car = '$Par_Sql[2]' AND Cos_Est='A'";
			//echo $costos_169;
			return $costos_169;
			break;

			/* Consulta las deudas que ya han sido agregadas al cliente */
		case 170:
			$costos_170 = "SELECT deudas.Pro_Cod FROM deudas, notasgener WHERE deudas.Nge_Cod = notasgener.Nge_Cod 
							AND notasgener.Sem_Cod = $Par_Sql[0] AND deudas.Pro_Cod = $Par_Sql[1] AND deudas.Cli_Cod = $Par_Sql[2]";
			//echo $costos_170;
			return $costos_170;
			break;

			/* Inserta las deudas de los clientes */
		case 171:
			$deudas_171 = "INSERT INTO deudas(Pro_Cod, Nge_Cod, Cli_Cod, Deu_Val, Deu_Reg, Deu_Fec, Bec_Cod, Deu_Rec, Asi_Int) VALUES 		
						($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], $Par_Sql[7], 
						$Par_Sql[8])";
			//echo $deudas_171;
			return $deudas_171;
			break;

			/* 
			* Consulta las modalidades 
			*/
		case 172:
			$consulta_modalidades_172 = "SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Cod = $Par_Sql[0]";
			//echo $consulta_modalidades_172;
			return $consulta_modalidades_172;
			break;

			/*
			* Consulta de las carreras en base al notasgener 
			*/
		case 174:
			$consulta_carreras_174 = "SELECT Car_Nom, Niv_Des, Sem_Par, IF (semestres.Sem_Sec='D', 'Diurna', IF (semestres.Sem_Sec='V', 'Vespertina', IF 
							(semestres.Sem_Sec='N', 'Nocturna', ' '))) as Sem_Sec, modalidad.Mod_Des FROM notasgener, semestres, promocione, carreras, 
							periodos, modalidad, niveles WHERE notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
							promocione.Car_Int = carreras.Car_Int AND niveles.Niv_Cod = semestres.Niv_Cod AND modalidad.Mod_Cod = periodos.Mod_Cod 
							AND periodos.Per_Int = semestres.Per_Int AND Nge_Cod = $Par_Sql[0] GROUP BY Car_Nom, Sem_Par, Sem_Sec, Mod_Des";
			//echo $consulta_carreras_174;
			return $consulta_carreras_174;
			break;

			/*
			* Consulta de la etapa 
			*/
		case 176:
			$consulta_carreras_176 = "SELECT Eta_Des, Eta_Rec FROM etapas WHERE Eta_Cod='$Par_Sql[0]'";
			//echo $consulta_carreras_176;
			return $consulta_carreras_176;
			break;

		case 179:
			/**
			 * Consulta de los bancos del plan de cuentas 
			 */
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan, plan_cuenta
			 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = 
			 $Par_Sql[0] AND plan_cuenta.Emp_Cod = $Par_Sql[1] ORDER BY Pld_Cdc, Pld_Des";
			//echo $sql;
			return $sql;
			break;

		case 185:
			/* Verifica si la asignatura de un cliente especifico, esta registrada en un semestre */
			$verificar_185 = "SELECT notasgedet.Nge_Cod, notasgener.Mat_Int, notasgedet.Nge_Tip, notasgedet.Nge_Est FROM notasgener INNER JOIN notasgedet ON (notasgener.Nge_Cod 
						= notasgedet.Nge_Cod) INNER JOIN matriculas ON (notasgener.Mat_Int = matriculas.Mat_Int) INNER JOIN estudiante ON (matriculas.Est_Int = estudiante.Est_Int)
						INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod) INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE  
						  notasgener.Sem_Cod = $Par_Sql[0] AND cliente.Cli_Cod = $Par_Sql[1] AND notasgedet.Asi_Int = $Par_Sql[2] AND notasgedet.Nge_Tip = '$Par_Sql[3]'";
			//echo $verificar_185;
			return $verificar_185;
			break;

		case 187:
			/* Consulta el bancos del plan de cuentas segun el tipo de pago */
			$bancos_plan_187 = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des FROM banco INNER JOIN det_plan ON (banco.Pld_Cod = det_plan.Pld_Cod) 
								WHERE banco.Ban_Cod = $Par_Sql[0]";
			//echo $bancos_plan_187;
			return $bancos_plan_187;
			break;

		case 188:
			/* Consulta del banco seleccionado */
			$consultar_pago_188 = "SELECT Bak_Cod, Bak_Des FROM bancos WHERE Bak_Cod = $Par_Sql[0]";
			//echo $consultar_pago_188;
			return $consultar_pago_188;
			break;

		case 196:
			$costos_196 = "SELECT Pro_Cod, Cos_Pre, Cos_Gen FROM costo_matr WHERE Pem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Sem_Cod = $Par_Sql[2] AND Cos_Est='A'";
			//echo "<br>".$costos_196;
			return $costos_196;
			break;

		case 207:
			/**
			 * Consulta que permite cargar el nombre de la empresa a que pertenece el usuario 
			 */
			$cabecera_empresa = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, Emp_Log, Emp_Ban, Emp_Ren, sucursal.Ciu_Cod FROM empresas, sucursal, ciudad WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return  $cabecera_empresa;
			break;

		case 210:
			/* 
			* Consulta los totales de las facturas agrupados por rubros 
			*/
			$fac_rubros_210 = "SELECT ventas.Vet_Cod,ventas.Vet_Num,ventas.Ret_Num, caja_aper.Caj_Fec, item.Ite_Lar, round((sum(ventas_det.Vet_Imp) - 
						(sum((Vet_Imp * Vet_Des) /100) + sum((Vet_Imp * Vet_Dec) /100))),2) as Vet_Imp, sum((ventas_det.Vet_Imp 
						- (((Vet_Imp * Vet_Des)/100) + ((Vet_Imp * Vet_Dec)/100))) * Iva_Por)/100 as Iva, sum(ventas_det.Vet_Can)AS Vet_Can 
						FROM caja_aper, ventas, ventas_det, producto, item, iva WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = 
						item.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' 
						AND ventas.Vet_Est = '$Par_Sql[2]' AND Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY caja_aper.Caj_Fec, item.Ite_Lar 
						ORDER BY ventas.Vet_Num ";
			//echo $fac_rubros_210;
			return $fac_rubros_210;
			break;

		case 211:
			/* 
			* Consulta los totales de las facturas agrupados por rubros por CARRERAS
			*/
			$my_fac_rubros_car_211 = "SELECT ventas.Vet_Cod, ventas.Vet_Num,ventas.Ret_Num,ventas.Ret_Fec,ventas.Ret_Aut,caja_aper.Caj_Fec, item.Ite_Lar, sum(ventas_det.Vet_Can)AS Vet_Can, round((sum(ventas_det.Vet_Imp) 
								- (sum((Vet_Imp * Vet_Des) /100) + sum((Vet_Imp * Vet_Dec) /100))),2) as Vet_Imp, 
								sum((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des)/100) + ((Vet_Imp * Vet_Dec)/100))) * Iva_Por)/100 
								as Iva, Car_Nom 
								FROM caja_aper, ventas, ventas_det, producto, item, iva, notasgener, semestres, promocione, carreras 
								WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND 
								ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND ventas_det.Iva_Cod 
								= iva.Iva_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' 
								AND Tic_Cod = $Par_Sql[3] AND ventas_det.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = 
								semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int = $Par_Sql[4] $Par_Sql[5] AND promocione.Car_Int = carreras.Car_Int 
								GROUP BY caja_aper.Caj_Fec, item.Ite_Lar ORDER BY ventas.Vet_Num";
			//echo $my_fac_rubros_car_211;


			ChromePhp::log($my_fac_rubros_car_211);
			return $my_fac_rubros_car_211;
			break;

		case 212:
			/* 
			* Consulta de los totales de las facturas en un rango de fechas detalladamente 
			*/
			$fac_detalle_212 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, ventas.Ret_Num,ventas.Ret_Fec,ventas.Ret_Aut,caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape,
	sum(ventas_det.Vet_Imp) AS Vet_Imp, ventas_det.Vet_Dec, ventas.Vet_Est, ventas.Cli_Cod, ventas_det.Nge_Cod FROM ventas INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE (Caj_Fec BETWEEN 
						  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY 
	ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas.Vet_Est, ventas.Cli_Cod ORDER BY ventas.Vet_Num, persona.Prs_Ape, 
	persona.Prs_Nom";
			//echo $fac_detalle_212;
			ChromePhp::log($fac_detalle_212);
			return $fac_detalle_212;
			break;

		case 223:
			/* Consulta de los datos dela factura*/
			$consultar_cli_fac_223 = "SELECT  ventas.Vet_Obs, ventas.Vet_Des, ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, 
								ventas_det.Vet_Dec, item.Ite_Cor, item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, Nge_Cod  
								FROM  ventas, ventas_det, item, iva, producto WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND 
								ventas_det.Pro_Cod = producto.Pro_Cod AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod
								  AND  ventas.Vet_Cod = $Par_Sql[0]";
			return $consultar_cli_fac_223;
			break;

		case 224:
			/* 
			* Consulta de la descripcion de la carrera que cursa el estudiante 
			*/
			$consultar_carrera_224 = "SELECT 
  carreras.Car_Nom,
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  modalidad.Mod_Des
FROM
  view_cursos_mal
  INNER JOIN notasgener ON (view_cursos_mal.Sem_Cod = notasgener.Sem_Cod)
  INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
  INNER JOIN periodos ON (view_cursos_mal.Per_Int = periodos.Per_Int)
  INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
WHERE
  notasgener.Nge_Cod = $Par_Sql[0]";
			//echo $consultar_carrera_224;
			return $consultar_carrera_224;
			break;

		case 225:
			$inconsistencias_225 = "SELECT ventas.Vet_Cod, Vet_Num, Prs_Ape, Prs_Nom, Caj_Fec, Pun_Des FROM caja_aper, ventas, 
		 				ventas_det, producto, item, cliente, persona, puntos_imp WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod 
						= item.Ite_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND Caj_Fec 
						BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = 
						$Par_Sql[3] AND Nge_Cod = 0 GROUP BY ventas.Vet_Cod, Vet_Num, Prs_Ape, Prs_Nom, Caj_Fec ORDER BY Vet_Num, Prs_Ape, Prs_Nom";
			//echo $inconsistencias_225;
			return $inconsistencias_225;
			break;

		case 235:
			$esquema_235 = "SELECT SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Importe,
  iva.Iva_Cod,
  iva.Iva_Sri,
  iva.Iva_Por,
  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Total,
  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento
						FROM ventas, caja_aper, ventas_det, iva WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
						(caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = '$Par_Sql[2]' AND Tic_Cod = $Par_Sql[3] $Par_Sql[4]
                        GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por
                        ORDER BY Iva_Por DESC";
			//echo $esquema_235;
			return $esquema_235;
			break;

		case 245:
			/**
			 * Consulta los años de las facturas de ventas recibidas 
			 */
			$sql = "SELECT YEAR(caja_aper.Caj_Fec) as Anio FROM ventas, caja_aper WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND caja_aper.Pun_Cod = $Par_Sql[0] GROUP BY YEAR(caja_aper.Caj_Fec) ORDER BY YEAR(caja_aper.Caj_Fec) DESC";
			return $sql;
			break;

			/* 
		* Consulta el detalle academico de la deuda 
		*/
		case 259:
			$detalle_deuda_259 = "SELECT DISTINCT
  view_cursos_mal.Sem_Cod,
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin,
  view_periodos_suc.Per_Int,
  view_periodos_suc.Suc_Des,
  modalidad.Mod_Des,
  etapas.Eta_Des,
  carreras.Car_Nom
FROM
  view_cursos_mal
  INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int)
  INNER JOIN matriculas ON (view_cursos_mal.Sem_Cod = matriculas.Sem_Cod)
  INNER JOIN estudiante ON (matriculas.Est_Int = estudiante.Est_Int)
  INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod)
  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
  INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int)
  INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = modalidad.Mod_Cod)
  INNER JOIN etapas ON (view_periodos_suc.Eta_Cod = etapas.Eta_Cod)
  INNER JOIN mallacurri ON (view_cursos_mal.Mal_Cod = mallacurri.Mal_Cod)
  INNER JOIN carreras ON (mallacurri.Car_Int = carreras.Car_Int)
WHERE
  cliente.Cli_Cod = $Par_Sql[0] AND 
  notasgener.Nge_Cod = $Par_Sql[1]
GROUP BY
  view_cursos_mal.Sem_Cod,
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin,
  view_periodos_suc.Per_Int,
  view_periodos_suc.Suc_Des,
  modalidad.Mod_Des,
  etapas.Eta_Des,
  carreras.Car_Nom";
			//echo $detalle_deuda_259;
			return $detalle_deuda_259;
			break;

			/* Consulta las deudas del cleinte en base a la modalidad, etapa, carrera, periodo */
		case 263:
			$modalidades_cliente_263 = "SELECT 
			  deudas.Pro_Cod,
			  producto.Pro_Ide,
			  item.Ite_Lar,
			  deudas.Deu_Val,
			  deudas.Deu_Fec,
			  deudas.Asi_Int,
			  deudas.Nge_Cod,
			  deudas.Deu_Obs,
			  producto.Iva_Cod,
			  iva.Iva_Por,
			  deudas.Bec_Cod,
			  deudas.Deu_Rec
			FROM
			  producto
			  INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod)
			  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
			  INNER JOIN notasgener ON (deudas.Nge_Cod = notasgener.Nge_Cod)
			  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			  INNER JOIN matriculas ON (notasgener.Mat_Int = matriculas.Mat_Int)
			  INNER JOIN semestres ON (semestres.Sem_Cod = matriculas.Sem_Cod)
			  INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
			  INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
			WHERE
			  deudas.Cli_Cod = $Par_Sql[0] AND 
			  Deu_Rec = 0 
			ORDER BY
			  deudas.Deu_Fec"; //AND periodos.Mod_Cod = $Par_Sql[1] AND periodos.Eta_Cod = $Par_Sql[2] AND promocione.Car_Int = $Par_Sql[3] AND periodos.Per_Int = $Par_Sql[4]
			//echo $modalidades_cliente_263;
			return $modalidades_cliente_263;
			break;

		case 315:
			/**
			 * Insertar datos en el tipo de pago 
			 */
			$sql = "INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num) 
			VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], '$Par_Sql[7]')";
			//echo $sql."<br>";
			return $sql;
			break;

		case 316:
			/* Consulta los tipos de pago */
			$inser_pago_316 = "SELECT pago_venta.Bak_Cod, pago_venta.Ban_Cod, pago_venta.Pag_Cod, Vet_Cue, Vet_Che, Vet_Mon, Vet_Cam, Vet_Tot, Vet_Num, Pag_Des, For_Des
						FROM pago_venta, tipos_pago, forma_pago WHERE pago_venta.Pag_Cod = tipos_pago.Pag_Cod AND forma_pago.For_Cod = tipos_pago.For_Cod 
						AND	pago_venta.Vet_Cod = '$Par_Sql[0]' ORDER BY Vet_Num";
			//echo  $inser_pago_316;
			return $inser_pago_316;
			break;

		case 317:
			/* 
			* Consulta del banco del plan de cuenta 
			*/
			$inser_pago_317 = "SELECT det_plan.Pld_Des FROM det_plan, banco, pago_venta WHERE det_plan.Pld_Cod = banco.Pld_Cod AND pago_venta.Ban_Cod = banco.Ban_Cod
					AND pago_venta.Vet_Cod = '$Par_Sql[0]' AND pago_venta.Vet_Num = '$Par_Sql[1]' AND pago_venta.Ban_Cod='$Par_Sql[2]'";
			//echo  $inser_pago_317;
			return $inser_pago_317;
			break;

		case 318:
			/* 
			* Consulta de otros bancos 
			*/
			$inser_pago_318 = "SELECT bancos.Bak_Des FROM bancos, pago_venta WHERE pago_venta.Bak_Cod = bancos.Bak_Cod
					AND pago_venta.Vet_Cod = '$Par_Sql[0]' AND pago_venta.Vet_Num = '$Par_Sql[1]' AND pago_venta.Bak_Cod='$Par_Sql[2]'";
			//echo  $inser_pago_318;
			return $inser_pago_318;
			break;

		case 320:
			/* Consulta las facturas de la caja activa para modificarlas */
			$mod_factura_320 = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, ventas.Vet_Aut, ventas.Vet_Xml, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
							  ventas.Vet_Est FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
							  ventas.Tic_Cod = $Par_Sql[1] AND caja_aper.Pun_Cod = $Par_Sql[2]  AND 
							  YEAR(caja_aper.Caj_Fec) = '$Par_Sql[3]' $Par_Sql[4]  ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			// echo $mod_factura_320;
			return $mod_factura_320;
			break;

		case 321:
			/* Consulta de las facturas por el codigo interno de la caja activa */
			$mod_factura_int_321 = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, ventas.Vet_Aut, ventas.Vet_Xml, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
							  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE ventas.Vet_Num LIKE '$Par_Sql[0]%' AND 
							  ventas.Tic_Cod = $Par_Sql[1] AND caja_aper.Pun_Cod = $Par_Sql[2]           
								ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $mod_factura_int_321;
			return $mod_factura_int_321;
			break;

			/* Borrado del codigo del detalle de la Venta */
		case 322:
			$borrar_pago_322 = "DELETE FROM pago_venta WHERE Vet_Cod='$Par_Sql[0]'";
			//echo $borrar_pago_322;
			return $borrar_pago_322;
			break;

		case 323:
			/**
			 * Consulta de las facturas por el número de la papeleta
			 */
			$sql = "SELECT DISTINCT 
					cliente.Cli_Cod,
					persona.Prs_Ape,
					persona.Prs_Nom,
					ventas.Vet_Num,
					ventas.Vet_Aut,
					ventas.Vet_Xml,
					caja_aper.Caj_Fec,
					ventas.Vet_Cod,
					cliente.Cli_Est,
					ventas.Vet_Est
					FROM
					caja_aper
					INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
					INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
					INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
					WHERE
					pago_venta.Vet_Che LIKE '$Par_Sql[0]%' AND 
					ventas.Tic_Cod = $Par_Sql[1] AND caja_aper.Pun_Cod = $Par_Sql[2] 
					ORDER BY
					caja_aper.Caj_Fec,
					persona.Prs_Ape,
					persona.Prs_Nom,
					ventas.Vet_Num DESC";
			return $sql;
			break;

		case 324:
			/**
			 * Consulta las facturas de la caja activa para modificarlas 
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
							  ventas.Vet_Est FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1]  AND 
							  caja_aper.Caj_Fec = '$Par_Sql[2]'  ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			return $sql;
			break;

		case 325:
			/**
			 * Consulta de las facturas por el codigo interno de la caja activa 
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
							  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE ventas.Vet_Num LIKE '$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1] AND 
							  caja_aper.Caj_Fec = '$Par_Sql[2]'        
								ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			return $sql;
			break;

		case 326:
			/**
			 * Consulta de las facturas por el número de la papeleta
			 */
			$sql = "SELECT DISTINCT 
			  cliente.Cli_Cod,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  ventas.Vet_Num,
			  caja_aper.Caj_Fec,
			  ventas.Vet_Cod,
			  cliente.Cli_Est,
			  ventas.Vet_Est
			FROM
			  caja_aper
			  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
			  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
			WHERE
			  pago_venta.Vet_Che LIKE '$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1] AND 
							  caja_aper.Caj_Fec = '$Par_Sql[2]' 
			ORDER BY
			  caja_aper.Caj_Fec,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  ventas.Vet_Num DESC";
			return $sql;
			break;


			/* Consultar los codigos de renta-iva*/
		case 344:
			$sql = "SELECT 
					  ventas_det.Ren_Cod,
					  ventas_det.Ren_Iva,
					  renta_iva.Ren_Sri,
					  renta_iva.Ren_Con,
					  renta_iva.Ren_Por,  
					  ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2) AS Ret_Bas,	
					  ((ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2))* renta_iva.Ren_Por)/100 as Val_Ret
					FROM
					  renta_iva
					  INNER JOIN ventas_det ON (renta_iva.Ren_Cod = ventas_det.Ren_Cod)
					  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
					WHERE
					ventas_det.Vet_Cod='$Par_Sql[0]' and ventas_det.Pro_Cod='$Par_Sql[1]' AND ventas_det.Ren_Cod='$Par_Sql[2]'";
			//echo $sql;
			return $sql;
			break;

			/* Consultar los codigos de renta-iva*/
		case 345:
			$sql = "SELECT 
					  ventas_det.Ren_Cod,
					  ventas_det.Ren_Iva,
					  renta_iva.Ren_Sri,
					  renta_iva.Ren_Con,
					  renta_iva.Ren_Por,  
					  ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2) AS Ret_Bas,
					  ((ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2))* iva.Iva_Por)/100 as Val_Ret,
					  iva.Iva_Por				
					FROM
					  renta_iva
					  INNER JOIN ventas_det ON (renta_iva.Ren_Cod = ventas_det.Ren_Iva)
					  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)					  
					WHERE
					ventas_det.Vet_Cod='$Par_Sql[0]' and ventas_det.Pro_Cod='$Par_Sql[1]' AND ventas_det.Ren_Iva='$Par_Sql[2]'";
			//echo $sql;
			return $sql;
			break;

			/* Consultar si Bec_Cod esta NULL*/
		case 383:
			$consultar_beca_deuda_383 = "SELECT Pro_Cod, Nge_Cod, Cli_Cod FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Bec_Cod IS NULL";
			return $consultar_beca_deuda_383;
			break;

			/* Consultar si el producto se encuentra asignado en becas */
		case 384:
			$consultar_beca_asignada_384 = "SELECT becas.Bec_Cod, det_becas.Pro_Cod FROM becas, det_becas 
					WHERE becas.Bec_Cod=det_becas.Bec_Cod AND becas.Mat_Int=$Par_Sql[0] AND det_becas.Pro_Cod=$Par_Sql[1]";
			return $consultar_beca_asignada_384;
			break;

			/* Baja de la deuda registrada en la tabla deudas */
		case 385:
			$baja_deuda_sinbeca_385 = "DELETE FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Asi_Int=$Par_Sql[3]";
			//echo $baja_deuda_sinbeca_385;

			return $baja_deuda_sinbeca_385;
			break;

			/* Busca codigo del Item po el codigo del producto JESSICA 16-01-2007*/
		case 462:
			$busca_cod_item_pro = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cod, item.Ite_Cor, item.Ite_Lar, Pro_Obs
	FROM item, producto WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = $Par_Sql[0]";
			return $busca_cod_item_pro;
			break;

		case 631:
			/*Consulta el punto de impresion*/
			$cons_punto = "SELECT puntos_imp.Pun_Cod, puntos_imp.Pun_Des FROM puntos_imp WHERE puntos_imp.Pun_Cod = '$Par_Sql[0]'";
			//echo $cons_punto;
			return $cons_punto;
			break;

			/* Consultar días plazo interés  */
		case 657:
			$mora_interes_dias = "SELECT Int_Dia, Int_Por FROM interes";
			//echo $mora_interes_dias;
			return $mora_interes_dias;
			break;

			/*Consultando datos de la tabla Autorizacion*/
		case 988:
			$Sql_988 = "SELECT * FROM autorizaci WHERE Aut_Cod= $Par_Sql[0]";
			//echo $Sql_988;
			return $Sql_988;
			break;

			/*Consultando datos de la tabla Autorizacion*/
		case 989:
			$Sql_989 = "SELECT * FROM ventas WHERE Aut_Cod = $Par_Sql[0] AND Vet_Num = $Par_Sql[1] AND Tic_Cod = $Par_Sql[2]";
			//echo $Sql_989;
			return $Sql_989;
			break;

		case 990:
			$Sql_990 = "SELECT pago_venta.Vet_Che FROM ventas INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
				  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod) WHERE cliente.Emp_Cod = $Par_Sql[1] AND pago_venta.Vet_Che = trim('$Par_Sql[0]')";
			//echo $Sql_990;
			return $Sql_990;
			break;

		case 1207:
			/*Consulta de los rubros sin precio*/
			$productos_28 = "SELECT precios.Tpv_Cod,item.Ite_Cod,Ite_Est,Ite_Cor, CONCAT(item.Ite_Lar,' ',producto.Pro_Obs) AS Ite_Lar, marca.Mar_Cod,Mar_Des , 
adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar, Pro_Obs,producto.Pro_Cod,Pro_Est,
Pro_Gen,Pro_Cdc,Pro_Sec,Stk_Can,Pre_Pvp,Adq_Cor, Pro_Uni FROM item,producto, marca, adquisicio,iva,precios,tipo_preci,stock, categorias 
WHERE  producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod  
AND adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod AND precios.Pro_Cod=producto.Pro_Cod 
AND precios.Tpv_Cod=tipo_preci.Tpv_Cod AND precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Cod=stock.Pro_Cod AND item.Cat_Cod = categorias.Cat_Cod AND 
 (item.Ite_Lar LIKE '%$Par_Sql[0]%' or producto.Pro_Obs LIKE '%$Par_Sql[0]%') AND categorias.Emp_Cod = $Par_Sql[3] AND producto.Pro_Est = 'A'
AND producto.Pro_Cod NOT IN (SELECT deudas.Pro_Cod FROM deudas, notasgener WHERE deudas.Nge_Cod = notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] 
AND notasgener.Sem_Cod = $Par_Sql[2])";
			//echo $productos_28;
			return $productos_28;
			break;

		case 1054:
			/*Consultar todos los productos que son bienes */
			$busca_ite_cat = "SELECT 
  precios.Tpv_Cod,
  item.Ite_Cod,
  item.Ite_Est,
  item.Ite_Cor,
  CONCAT(item.Ite_Lar,' ',producto.Pro_Obs) AS Ite_Lar,
  marca.Mar_Cod,
  marca.Mar_Des,
  adquisicio.Adq_Cod,
  adquisicio.Adq_Des,
  adquisicio.Adq_Cor,
  iva.Iva_Cod,
  iva.Iva_Por,
  producto.Pro_Bar,
  producto.Pro_Obs,
  producto.Pro_Cod,
  producto.Pro_Est,
  producto.Pro_Gen,
  producto.Pro_Cdc,
  producto.Pro_Sec,
  stock.Stk_Can,
  precios.Pre_Pvp,
  producto.Pro_Uni
FROM
  categorias
  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
  INNER JOIN tipo_preci ON (precios.Tpv_Cod = tipo_preci.Tpv_Cod)
  INNER JOIN stock ON (precios.Pro_Cod = stock.Pro_Cod)
  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
WHERE precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Est = 'A'
 AND (item.Ite_Lar LIKE '%$Par_Sql[0]%' OR producto.Pro_Obs LIKE '%$Par_Sql[0]%') AND categorias.Emp_Cod = $Par_Sql[1]";
			//echo $busca_ite_cat;
			return $busca_ite_cat;
			break;

		case 1035:
			$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Kar_Int)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15])";
			//echo  $sql;
			return $sql;
			break;

			/* Selecciona todos los tipos de comprobantes */
		case 1036:
			$sql = "SELECT Tic_Cod, Tic_Des, Tic_Sri FROM tipo_compr WHERE Tic_Est='A'";
			//echo $sql;
			return $sql;
			break;

			/*  Validar las Adquisiciones */
		case 1037:
			$sql = "SELECT adquisicio.Adq_Cod FROM producto,adquisicio WHERE producto.Adq_Cod=adquisicio.Adq_Cod AND adquisicio.Adq_Cor='B' AND producto.Pro_Cod=$Par_Sql[0]";
			//echo $sql;
			//if ($_SESSION['Ses_Usu_Cod']==1){ echo $sql."<br>";}
			return $sql;
			break;

		case 1204:
			/*
		* Consulta sentencia consulto stock del kardex  
		* Se pone cero en el parametro para cuando no hay stock
		*/
			$sql = "UPDATE stock SET Stk_Can=$Par_Sql[0]+0 WHERE Pro_Cod=$Par_Sql[1] AND Suc_Cod=$Par_Sql[2]";
			//if ($_SESSION['Ses_Usu_Cod']==1){ echo $sql."<br>";}
			return $sql;
			break;

		case 1205:
			/*Consulta sentencia consulto stock del kardex  */
			$busca_ite_cat = "INSERT INTO stock(Stk_Can,Loc_Cod,Pro_Cod)VALUE($Par_Sql[0],$Par_Sql[1],$Par_Sql[2]) ";

			return $busca_ite_cat;
			break;

		case 1206:
			/*
		* Consulta sentencia consulto stock del kardex  
		*/
			$busca_ite_cat = "SELECT (SUM(Kar_Can)-SUM(Kar_Sal)) AS Stock 
		FROM kardex_ie WHERE Pro_Cod=$Par_Sql[0] AND Kar_Est='A'";
			//echo $busca_ite_cat;					
			return $busca_ite_cat;


		case 1208:
			/*Consulta sentencia consulto stock del kardex  */
			$busca_ite_cat = "SELECT Pro_Cod	FROM ventas_det WHERE Vet_Cod=$Par_Sql[0] ";
			//echo $busca_ite_cat;
			return $busca_ite_cat;
			break;

		case 1039:
			$bor_precio = "DELETE FROM kardex_ie WHERE Vet_Cod='$Par_Sql[0]'";
			return $bor_precio;
			break;

		case 1062:
			/*anulo los comprobantes de ajustes*/
			$busca_ite_cat = "UPDATE kardex_ie SET  Kar_Est='I' WHERE Vet_Cod=$Par_Sql[0] ";
			// echo $busca_ite_cat;
			return $busca_ite_cat;
			break;

		case 1072:
			$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15],$Par_Sql[16])";
			//echo $sql;
			//if ($_SESSION['Ses_Usu_Cod']==1){ echo $sql."<br>";}
			return $sql;
			break;

		case 1073:
			/**
			 * Consulta el curso del estudiante
			 */
			$sql = "SELECT 
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  view_cursos_mal.Sem_Cod
FROM
  persona
  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
  INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
  INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
  INNER JOIN view_cursos_mal ON (matriculas.Sem_Cod = view_cursos_mal.Sem_Cod)
  INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int)
WHERE
  cliente.Cli_Cod = $Par_Sql[0] AND notasgener.Nge_Cod = $Par_Sql[1]";
			//echo $sql;
			return $sql;
			break;

		case 1209:
			/*Consulta sentencia consulto stock del kardex  */
			$busca_ite_cat = "SELECT 
				  autorizaci.Aut_Cod,
				  autorizaci.Aut_Cad
				FROM
				  puntos_imp
				  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
				WHERE
				  vendedor.Prs_Cod= $Par_Sql[0]";
			//echo $busca_ite_cat;
			return $busca_ite_cat;
			break;

		case 1210:
			/*Consulta del esquema del xml factura electronica */
			$sql = "SELECT Esq_Cod,Esq_Rec,Esq_Des,Esq_Xml,Esq_Ord FROM esquema WHERE esquema.Tan_Cod=$Par_Sql[0] AND esquema.Esq_Rec=$Par_Sql[1] AND esquema.Esq_Est='A' order by Esq_Ord Asc";
			//echo "1210: ".$sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
			break;

		case 1211:
			/*Consulta informacion de la empresa */
			$sql = "SELECT 
					  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
					  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv,confi_fact.Cof_Con 
					FROM
					  empresas
				      INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
					  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
				   WHERE
				      sucursal.Suc_Cod=$Par_Sql[0]";
			//echo "1211: ".$sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
			break;

		case 1212:
			/*Consulta informacion de la empresa */
			$sql = "SELECT 
					  persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom,persona.Prs_Dir,persona.Prs_Tel,persona.Prs_Cor,cliente.Cli_Cod,
					  if(cliente.Cli_Con='NO','NO','SI')as Cli_Con,ventas.Vet_Cod,ventas.Vet_Num,ventas.Vet_Obs,identifica.Ide_Prv,date_format(caja_aper.Caj_Fec, '%d/%m/%Y') AS fecha,ventas.Vet_Des,autorizaci.Pun_Sri,tipo_compr.Tic_Sri,ventas.Vet_Ntd,date_format(ventas.Vet_Fdm, '%d/%m/%Y')as Vet_Fdm,ventas.Vet_Nns
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
                                          INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					WHERE 
					  ventas.Vet_Cod=$Par_Sql[0]";
			//echo "1212: ".$sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
			break;

		case 1213:
			/*Consulta informacion total de venta y total del descuento de la venta */
			$sql = "SELECT 
					  ventas.Vet_Cod,
                                          ventas.Vet_Obs,
					  sum(ventas_det.Vet_Imp)as total,
					  ((sum(ventas_det.Vet_Imp)*ventas.Vet_Des)/100)as Dscto
					FROM
					  ventas
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					WHERE
					  ventas.Vet_Cod='$Par_Sql[0]' GROUP by ventas_det.Vet_Cod";
			//echo "1213: ".$sql."<br>";
			return $sql;
			break;

		case 1214:
			/*Consulta los importes totales de la factura ya sea ,IVA %12, IVA %0, ICE */
			$sql = "SELECT 
					  iva.Iva_Cod,
					  iva.Iva_Sri,
					  iva.Iva_Por,					  
					  sum(ventas_det.Vet_Imp) AS imp
				   FROM
					  iva
					  INNER JOIN ventas_det ON (iva.Iva_Cod = ventas_det.Iva_Cod)
				   WHERE
					  ventas_det.Vet_Cod = '$Par_Sql[0]'
					GROUP BY
					  iva.Iva_Cod";
			//echo "1214: ".$sql."<br>";                         
			return $sql;
			break;

		case 1215:
			/*Consulta los importes totales de la factura ya sea ,IVA %12, IVA %0, ICE */
			$sql = "SELECT 
					  producto.Pro_Cod,concat(item.Ite_Lar,' ',producto.Pro_Obs)as Pro_Obs,
					  ventas_det.Vet_Can,ventas_det.Vet_Pru,ventas.Vet_Des,iva.Iva_Sri,iva.Iva_Por
					FROM
					  iva
					  INNER JOIN ventas_det ON (iva.Iva_Cod = ventas_det.Iva_Cod)
					  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
					  INNER JOIN producto ON (ventas_det.Pro_Cod = producto.Pro_Cod)
					  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
					WHERE
					  ventas_det.Vet_Cod = '$Par_Sql[0]'";
			//echo "1215: ".$sql."<br>";
			return $sql;
			break;

		case 1216:
			/*Consulta los importes totales de la factura ya sea ,IVA %12, IVA %0, ICE */
			$sql = "SELECT 
					  sucursal.Suc_Sri,autorizaci.Pun_Sri
					FROM
					  sucursal
					  INNER JOIN puntos_imp ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
					  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					WHERE
					  puntos_imp.Pun_Cod = '$Par_Sql[0]'";
			//echo "1216: ".$sql."<br>";
			return $sql;
			break;

		case 1217:
			/*Consulta el vendedor  */
			$sql = "SELECT 
					  persona.Prs_Ape,
					  persona.Prs_Nom,
					  vendedor.Vnd_Cod
				   FROM
					  vendedor
					  INNER JOIN persona ON (vendedor.Prs_Cod = persona.Prs_Cod)
				   WHERE
					  vendedor.Vnd_Cod='$Par_Sql[0]'";
			//echo "1217: ".$sql."<br>";
			return $sql;
			break;

		case 338:
			/**
			 * Consulta el codigo de retencion del SRI
			 */
			$sql = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			 FROM renta_iva WHERE renta_iva.Ren_Ret='$Par_Sql[1]' 
			  AND  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%'  ORDER BY renta_iva.Ren_Sri";
			//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
			//echo $sql;
			return $sql;
			break;

		case 339:
			/**
			 * Consulta el codigo de retencion del SRI
			 */
			$sql = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			 FROM renta_iva 
                            INNER JOIN reniva_pla ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
                             INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
			  WHERE reniva_pla.Ren_Tip='V' AND renta_iva.Ren_Ret='$Par_Sql[1]'  AND  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%' AND plan_cuenta.Pla_Cod='$Par_Sql[2]' AND Emp_Cod='$Par_Sql[4]' ORDER BY renta_iva.Ren_Sri";
			//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
			//echo $sql;
			return $sql;

			/**
			 * Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) 
			renta_iva.Adq_Cod='$Par_Sql[0]' AND
			 */
		case 361:
			$sql = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			FROM renta_iva WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
			AND renta_iva.Ren_Est='A'  
			AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri";
			return $sql;
			break;



		case 363: //aqui2
			$sql = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			FROM renta_iva INNER JOIN reniva_pla ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
			AND reniva_pla.Ren_Tip='V'  AND renta_iva.Ren_Est='A' AND plan_cuenta.Pla_Cod='$Par_Sql[2]' AND Emp_Cod='$Par_Sql[4]'
			AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri";
			//echo $sql;
			return $sql;

		case 1218:
			/*Consulta si existe una autorizacion con la fecha ingresada  */
			$sql = "SELECT Aut_Cod,Aut_Ini,Aut_Fin
				FROM					  
                               autorizaci
                               INNER JOIN tipo_compr ON tipo_compr.Tic_Cod = autorizaci.Tic_Cod
			       WHERE
					  '$Par_Sql[0]' between Aut_Fci AND Aut_Cad AND Pun_Cod='$Par_Sql[1]' AND Aut_Est='A'";
			//echo $sql."<br>";
			return $sql;
			break;

		case 1219:
			/*Consulta si existe una autorizacion con la fecha ingresada  */
			$sql = "SELECT Caj_Cod,Caj_Fec
				   FROM
					  caja_aper
				   WHERE
					  Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
			//echo $sql."<br>";
			return $sql;
			break;

		case 1220:
			/* Cambiamos el Estado de todas las cajas segun Pun_Cod*/
			$sql = "Update caja_aper SET Caj_Est='$Par_Sql[0]' WHERE Pun_Cod=$Par_Sql[1]";
			return $sql;
			break;

		case 1221:
			/* Apertura de la caja diaria */
			$abrir_caja = "INSERT INTO caja_aper (Caj_Fec, Caj_Hoi, Pun_Cod, Caj_Est) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', $Par_Sql[2], '$Par_Sql[3]')";
			//echo $abrir_caja;
			return $abrir_caja;
			break;

		case 1222:
			/* Buscamos numero de venta segun el numero de autorizacion */
			$sql = "SELECT 
					  ventas.Vet_Cod,autorizaci.Aut_Cod,ventas.Vet_Num
				  FROM
					  autorizaci
					  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
				  WHERE
					  autorizaci.Aut_Sri='$Par_Sql[0]' AND ventas.Vet_Num='$Par_Sql[1]'";
			//echo $sql;					   
			return $sql;
			break;

		case 1223:
			/* 
			* Consulta de la caja creada en modo manual en base al vendedor 
			*/
			$sql = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod, Pun_Des FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND caja_aper.Caj_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 1224:
			/* Consultamos los codigos plan cta y periodo contable */
			$sql = "SELECT 
					  plan_cuenta.Pla_Cod,perio_cont.Pec_Cod
					FROM
					  empresas
					  INNER JOIN plan_cuenta ON (empresas.Emp_Cod = plan_cuenta.Emp_Cod)
					  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
					WHERE
					  empresas.Emp_Cod='$Par_Sql[0]' AND '$Par_Sql[1]' between Pec_Fei AND Pec_Fef";
			//echo $sql;
			return $sql;
			break;

		case 1225:
			/**
			 * Consulta las facturas de la caja activa para modificarlas 
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
						   ventas.Vet_Est 
				    FROM 
					       caja_aper 
						   INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
						   INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
						   INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
				    WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1]    
					ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $sql;
			return $sql;
			break;

		case 1226:
			/**
			 * Consulta de las facturas por el codigo interno de la caja activa 
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
				  FROM 
                                     caja_aper 
                                     INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
                                     INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
                                     INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
                                  WHERE ventas.Vet_Num = '$Par_Sql[0]' AND caja_aper.Pun_Cod = $Par_Sql[1] 
                                  ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $sql;
			return $sql;
			break;

		case 1227:
			/**
			 * Consulta de las facturas por el número de la papeleta
			 */
			$sql = "SELECT DISTINCT 
			  cliente.Cli_Cod,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  ventas.Vet_Num,
			  caja_aper.Caj_Fec,
			  ventas.Vet_Cod,
			  cliente.Cli_Est,
			  ventas.Vet_Est
			FROM
			  caja_aper
			  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
			  INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
			WHERE
			  pago_venta.Vet_Che LIKE '$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1] 
			ORDER BY
			  caja_aper.Caj_Fec,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  ventas.Vet_Num DESC";
			return $sql;
			break;

		case 1228:
			/**
			 * Insertar datos del detalle de la factura para contrato y el indice
			 */
			$sql = "INSERT INTO ventas_det SET Vet_Cod=$Par_Sql[0], Pro_Cod=$Par_Sql[1], Vet_Can=$Par_Sql[2], 
			Iva_Cod=$Par_Sql[3], Vet_Pru=$Par_Sql[4], Vet_Imp=$Par_Sql[5], Vet_Dec='$Par_Sql[6]', Nge_Cod = $Par_Sql[7],
			Asi_Int=$Par_Sql[8], Vet_Rec=$Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni=$Par_Sql[12], Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14],Vet_Ite='$Par_Sql[15]'";
			//echo $sql."<br>";
			return $sql;
			break;

			/**
			 * Actualizar datos del detalle de la factura en base a acta de notas, contratos e indice
			 */
		case 1229:
			$sql = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni='$Par_Sql[12]', Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14],Vet_Ite='$Par_Sql[15]'";
			//echo "<br>".$sql;
			return $sql;
			break;

		case 1230:
			/**
			 * Consulta las facturas de la caja activa para modificarlas x cedula
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
						   ventas.Vet_Est 
				    FROM 
					       caja_aper 
						   INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
						   INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
						   INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
						   INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod) 
						   INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod) 						   
				    WHERE persona.Prs_Ced='$Par_Sql[0]' AND puntos_imp.Suc_Cod = $Par_Sql[1]    
					ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $sql;
			return $sql;
			break;

		case 1231:
			/**
			 * Consulta las facturas de ventas segun punto de impresion y Nombre d ciente
			 */
			$sql = "SELECT 
					  cliente.Cli_Cod,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,
					  ventas.Vet_Cod,
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml,
                                          ventas.Tic_Cod      
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
					WHERE
					  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
					  ventas.Tic_Cod = '$Par_Sql[1]' AND 
					  puntos_imp.Suc_Cod = '$Par_Sql[2]' AND 
					  YEAR(caja_aper.Caj_Fec) = '$Par_Sql[3]' $Par_Sql[4]          
					ORDER BY ventas.Vet_Cod ASC";
			//echo $sql;
			return $sql;
			break;

		case 1232:
			/**
			 * Consulta las facturas de ventas segun punto de impresion y numero factura
			 */
			$sql = "SELECT 
					  ventas.Vet_Cod,
					  cliente.Cli_Cod,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  Pun_Sri,Suc_Sri,
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,					  
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml,
                      ventas.Tic_Cod
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod=sucursal.Suc_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod) 
					WHERE
					  ventas.Vet_Num='$Par_Sql[0]' AND 
					  ventas.Tic_Cod = '$Par_Sql[1]' AND 
					  puntos_imp.Suc_Cod = '$Par_Sql[2]'  
					ORDER BY ventas.Vet_Cod ASC";
			//echo $sql;
			return $sql;
			break;

		case 1233:
			/**
			 * Consulta las facturas de ventas segun punto de impresion
			 */
			$sql = "SELECT 
			          ventas.Vet_Cod,  
					  cliente.Cli_Cod,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,					  
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml,
                                          ventas.Tic_Cod
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
					WHERE					 
					  ventas.Tic_Cod = '$Par_Sql[1]' AND 
					  puntos_imp.Suc_Cod = '$Par_Sql[2]'  
					ORDER BY ventas.Vet_Cod ASC";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Insertado de usuario
			 */
		case 1234:
			$sql = "INSERT INTO usuarios (Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Cad,Usu_Tip)
					  VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',md5('$Par_Sql[3]'),'$Par_Sql[4]','C');";
			//if ($_SESSION['Ses_Usu_Cod']==1){ echo $sql."<br>";}
			return $sql;
			break;

		case 1235:
			/* Consultamos un usuario segun cedula y sucursal */
			$sql = "SELECT 
					  Usu_Cod,Prs_Cod,Usu_Ced
					FROM
					  usuarios
					WHERE
					  Suc_Cod='$Par_Sql[0]' AND Usu_Ced='$Par_Sql[1]' AND Usu_Est='A'";
			return $sql;
			break;

		case 1236:
			/**
			 * Consulta los a�os de las facturas de ventas recibidas 
			 */
			$sql = "SELECT YEAR(caja_aper.Caj_Fec) as Anio FROM ventas, caja_aper WHERE ventas.Caj_Cod = caja_aper.Caj_Cod GROUP BY YEAR(caja_aper.Caj_Fec) ORDER BY YEAR(caja_aper.Caj_Fec) DESC";
			//echo $sql;
			return $sql;
			break;

		case 1237:
			/*
			* Busca las facturas registradas de acuerdo a los intervalos de fecha
			*/
			$sql = "SELECT ventas.Vet_Cod, item.Ite_Lar, ventas.Vet_Num,Ret_Aut,Ret_Num,caja_aper.Caj_Fec, persona.Prs_Ced,persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est,
			SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Imp, 	SUM(ventas_det.Vet_Can)AS Vet_Can,				   
			
			SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,					  
		
			SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
			SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 					  
		        FROM 
			  ventas 
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
			  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN producto ON (producto.Pro_Cod = ventas_det.Pro_Cod)
			  INNER JOIN item ON (item.Ite_Cod = producto.Ite_Cod)
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
			WHERE 
			  $Par_Sql[0] AND 
			  ventas.Vet_Est = '$Par_Sql[1]' AND 
			  ventas.Tic_Cod = $Par_Sql[2] $Par_Sql[3] 
			  GROUP BY item.Ite_Lar  
			ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
			//echo $sql;		
			return $sql;
			break;

		case 1238:
			/*
			* Busca las facturas registradas de acuerdo a los intervalos de fecha
			*/
			$cons_fact_106 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, ventas.Ret_Num, ventas.Ret_Fec, ventas.Ret_Aut, caja_aper.Caj_Fec, persona.Prs_Ced,persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est,
			CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Vet_Tot, 
			CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 ))    * (1 + Iva_Por / 100)      AS decimal(20,2) ) AS Vet_Pag,	
			CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva, 
			CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * (ventas.Vet_Des/100))  AS decimal(20,2)) AS Descuento,
			Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced, GROUP_CONCAT(DISTINCT renta_iva.Ren_Sri SEPARATOR ', ') AS CodigoSri					  
			FROM 
			ventas 
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			  LEFT JOIN renta_iva ON (ventas_det.Ren_Cod = renta_iva.Ren_Cod)
			  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 					 
			  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
			WHERE $Par_Sql[0] AND ventas.Vet_Est = '$Par_Sql[1]' AND ventas.Tic_Cod = $Par_Sql[2] $Par_Sql[3] GROUP BY ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec,persona.Prs_Nom, persona.Prs_Ape, ventas.Vet_Est, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom";
			//echo $cons_fact_106;		
			return $cons_fact_106;
			break;

		case 1239:
			$sql = "SELECT SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Importe,
			  iva.Iva_Cod,
			  Ret_Aut,
			  Ret_Num,
			  iva.Iva_Sri,
			  iva.Iva_Por,
			  CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Total,
			  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva,			  
			  CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * (ventas.Vet_Des/100))  AS decimal(20,2)) AS Descuento
			FROM persona
				  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
				  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
			WHERE $Par_Sql[0] AND ventas.Vet_Est = '$Par_Sql[1]' AND Tic_Cod = $Par_Sql[2] $Par_Sql[3]	GROUP BY ventas.Vet_Cod, /*iva.Iva_Cod,*/ iva.Iva_Sri/*, Iva_Por*/ /* Comentado para q agrupe diferentes IVAs */
			ORDER BY Iva_Por DESC";
			//echo $sql;
			return $sql;
			break;

		case 1240:
			/* 
			* Consulta de los totales de las facturas en un rango de fechas detalladamente 
			*/
			$sql = "SELECT ventas.Vet_Cod, ventas.Vet_Num, ventas.Ret_Aut, ventas.Ret_Fec, caja_aper.Caj_Fec, persona.Prs_Ced,persona.Prs_Nom, persona.Prs_Ape,
			sum(ventas_det.Vet_Imp) AS Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est,Vet_Obs, ventas.Cli_Cod, ventas_det.Nge_Cod 
			FROM 
			  ventas 
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
			  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod) 
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
			 WHERE $Par_Sql[0] AND ventas.Vet_Est = '$Par_Sql[1]' AND ventas.Tic_Cod = $Par_Sql[2] $Par_Sql[3] 
			  GROUP BY ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas.Vet_Est, ventas.Cli_Cod ORDER BY ventas.Vet_Num, persona.Prs_Ape,persona.Prs_Nom";
			//echo $sql;
			//ChromePhp::log($sql);
			return $sql;
			break;

			/* Selecciona el tipos de comprobante segun codigo */
		case 1241:
			$sql = "SELECT Tic_Cod, Tic_Sri, Tic_Des FROM tipo_compr WHERE Tic_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 1242:
			/*Consulta informacion total de venta y total del descuento de la venta si iva*/
			$sql = "SELECT 
					  ventas.Vet_Cod,
					  sum(ventas_det.Vet_Imp)as total,
					  iva.Iva_Por,
					  ((sum(ventas_det.Vet_Imp)*ventas.Vet_Des)/100)as Dscto
					FROM
					  ventas
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					WHERE
					  ventas.Vet_Cod='$Par_Sql[0]' AND iva.Iva_Por='12' GROUP by ventas_det.Vet_Cod";
			//echo "1242: ".$sql."<br>";
			return $sql;
			break;

			/*Consultando si una venta tiene retencion */
		case 1243:
			$sql = "INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]')";
			//if ($_SESSION['Ses_Usu_Cod']==1){ echo $sql."<br>";}
			return $sql;
			break;

		case 1244:
			/* Consulta la base de datos de la empresa*/
			$sql = "SELECT 
					  data.Dat_Cod
					FROM
					  data
					WHERE data.Emp_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

		case 1245:
			/* Consulta usuario con la cedula */
			$sql = "SELECT 
						Usu_Ced, Suc_Cod
					FROM
					  usuarios
					WHERE Suc_Cod = '$Par_Sql[0]' AND Usu_Ced='$Par_Sql[1]' AND Usu_Est='A'";
			//echo $sql;
			return $sql;
			break;

		case 1246:
			/* Consulta la si existe usuario en la master */
			$sql = "SELECT 
					  Suc_Cod, Acc_Usr
					FROM
					  access
					WHERE Suc_Cod = '$Par_Sql[0]' AND Dat_Cod = '$Par_Sql[1]' AND Acc_Usr = '$Par_Sql[2]'";
			//echo $sql;
			return $sql;
			break;

			/* 
			* Seleccionar el numero maximo de la factura
			*/
		case 1247:
			$sql = "SELECT 
				     MAX(ventas.Vet_Num) AS Num 
			       FROM 
				     persona
					 INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					 INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					 INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					 INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
			       WHERE 			 
				      $Par_Sql[0] AND ventas.Vet_Est = '$Par_Sql[1]' AND 
				      ventas.Tic_Cod = $Par_Sql[2] $Par_Sql[3]";
			//echo $sql;
			return $sql;
			break;

			/* 
		       * Seleccionar el numero minimo de la factura
		       */
		case 1248:
			$sql = "SELECT 
					MIN(ventas.Vet_Num) AS Num 
			       FROM 
				      persona
				      INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
				      INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
				      INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				      INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
			       WHERE 
				       $Par_Sql[0] AND ventas.Vet_Est = '$Par_Sql[1]' AND 
					   ventas.Tic_Cod = $Par_Sql[2] $Par_Sql[3]";
			return $sql;
			break;

			/* 
		      * Seleccionar los detalles de la venta
		      */
		case 1249:
			$sql = "SELECT 
					  ventas.Vet_Cod,
					  ventas.Ret_Fec,
					  ventas.Ret_Num,
					  ventas.Ret_Aut,
					  ventas_det.Vet_Imp,
					  iva.Iva_Por,  
					  ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2) as Vet_Dsc,
					  ventas_det.Ren_Cod,
					  ventas_det.Ren_Iva
					FROM
					  ventas
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)  
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					WHERE
					  ventas.Vet_Cod = '$Par_Sql[0]'";
			//echo $sql;	
			return $sql;
			break;

			/* 
		       * Consulta los porcentajes renta_iva segun codigo interno
		       */
		case 1250:
			$sql = "SELECT 
					 Ren_Cod, Ren_Sri, Ren_Con, Ren_Por, if(Ren_Ret='R','RENTA','IVA')as Impuesto 
					FROM
					  renta_iva 
					WHERE
					  Ren_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/* 
		       * Consulta si la venta tiene retencion
		       */
		case 1251:
			$sql = "SELECT Vet_Cod, Ren_Cod, Ren_Iva FROM ventas_det WHERE Vet_Cod = '$Par_Sql[0]' AND (Ren_Cod is not null or Ren_Iva is not null) limit 1";
			return $sql;
			break;

			/**
			 * Consultar informacion de la compra
			 */
		case 1252:
			$sql = "SELECT 
				  ventas.Vet_Cod,
				  persona.Prs_Ape,
				  persona.Prs_Nom
				FROM
				  persona
				  INNER JOIN vendedor ON (persona.Prs_Cod = vendedor.Prs_Cod)
				  INNER JOIN ventas ON (vendedor.Vnd_Cod = ventas.Vnd_Cod)
				WHERE
				  ventas.Vet_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consultar informacion tipo de comprobante segun codigo interno
			 */
		case 1253:
			$sql = "SELECT Tic_Cod, Tic_Sri, Tic_Des FROM tipo_compr WHERE Tic_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 * Consultar el codigo del perfil Clientes segun la empresa
			 */
		case 1254:
			$sql = "SELECT Per_Cod, Per_Des FROM perfiles WHERE Per_Des = 'Clientes' AND Emp_Cod = '$Par_Sql[0]' AND Per_Est='A'";
			return $sql;
			break;

			/**
			 * Asignamos el perfil al cliente
			 */
		case 1255:
			$sql = "INSERT INTO usuarperfi (Usu_Cod,Per_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";
			return $sql;
			break;

			/**
			 * Consultar el vendedor
			 */
		case 1256:
			$sql = "SELECT Vnd_Cod, Prs_Cod FROM vendedor WHERE Prs_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 *  consultamos las ventas agrupadas por el cliente
			 */
		case 1257:
			$sql = "SELECT 
			  ventas.Cli_Cod,
			  caja_aper.Caj_Fec,
			  round((sum(ventas_det.Vet_Imp) - (sum((Vet_Imp * Vet_Des) / 100) + sum((Vet_Imp * Vet_Dec) / 100))), 2) AS Vet_Imp,
			  sum((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100 AS Iva,
			  persona.Prs_Ape,
			  persona.Prs_Nom,
			  persona.Prs_Ced
			FROM
			  ventas
			  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
			  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			  INNER JOIN producto ON (producto.Pro_Cod = ventas_det.Pro_Cod)
			  INNER JOIN item ON (item.Ite_Cod = producto.Ite_Cod)
			  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
			WHERE
			  $Par_Sql[0] AND 
			  ventas.Vet_Est = '$Par_Sql[1]' AND 
			  ventas.Tic_Cod = '$Par_Sql[2]' $Par_Sql[3]
			GROUP BY			 
			  ventas.Cli_Cod
			ORDER BY
			  persona.Prs_Ape";
			//echo $sql;
			return $sql;
			break;

		case 1258:
			/**
			 * Consulta las facturas de ventas segun Nombre d ciente
			 */
			$sql = "SELECT 
					  cliente.Cli_Cod,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,
					  Pun_Sri,Suc_Sri,
					  ventas.Vet_Cod,
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml,
					  ventas.Tic_Cod
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)					   
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod=sucursal.Suc_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod) 
					WHERE
					  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
					  ventas.Tic_Cod = '$Par_Sql[1]' AND 
					  puntos_imp.Suc_Cod = '$Par_Sql[2]' 
					ORDER BY ventas.Vet_Cod ASC";
			//echo $sql;
			return $sql;
			break;

		case 1259:
			$sql = "SELECT 
  perio_cont.Pec_Cod,
  perio_cont.Pec_Fei,
  perio_cont.Pec_Fef,
  perio_cont.Pec_Est,
  Year(Pec_Fei) AS Periodo,
  perio_cont.Pla_Cod
FROM
  plan_cuenta
  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
WHERE
  Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
ORDER BY
  Pec_Fei DESC";
			return $sql;
		case 1260:
			/**
			 * Instar un comprobante contable
			 */
			$sql = "INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'), Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]' "; //Antes Com_Tip
			//if ($_SESSION['Ses_Usu_Cod']==1){echo "<br>".$sql."<br>";} 
			return $sql;

			/**
			 * Determina cuenta unica del proveedor en el plan de cuentas 
			 */
		case 1261:
			$sql = "SELECT ccpp_cliente.Pld_Cod, det_plan.Pld_Des, ccpp_cliente.Cpc_Def, ccpp_cliente.Cpc_Cxc FROM det_plan 
INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod)
INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
 WHERE perio_cont.Pec_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;


		case 1262:
			/**
			 * Inserta los datos en cuentas por pagar
			 */
			$sql = "INSERT INTO ccpp_cobrar (Com_Cod, Vet_Cod, Cpc_Ven, Cpc_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'))";
			//echo "<br>".$sql."<br>";
			return $sql;
		case 1263:
			/**
			 * Inserta datos del asiento contable aqui
			 */
			$sql = "INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
			//echo "<br>".$sql."<br>";
			return $sql;
		case 1264:
			$sql = "SELECT Pld_Cod FROM produ_plan WHERE Pro_Cod=$Par_Sql[0] AND (Tip_Pld='V' OR Tip_Pld='I')";
			//echo $sql;
			return $sql;
		case 1265:
			$sql = "SELECT iva_cobrad.Pld_Cod FROM iva_cobrad 
INNER JOIN det_plan ON iva_cobrad.Pld_Cod=det_plan.Pld_Cod
INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
WHERE plan_cuenta.Pla_Cod='$Par_Sql[0]' AND Pla_Est='A' AND Pld_Est='A'";
			//echo $sql;
			return $sql;
		case 1266:
			$sql = "SELECT Pld_Cod FROM banco WHERE Ban_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
		case 1267:
			$sql = "SELECT Pec_Cod,perio_cont.Pla_Cod FROM perio_cont 
        INNER JOIN plan_cuenta ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
        WHERE '$Par_Sql[1]' BETWEEN Pec_Fei AND Pec_Fef AND Emp_Cod=$Par_Sql[0] ";
			//echo $sql;
			return $sql;
		case 1268:
			$sql = "SELECT reniva_pla.Pld_Cod FROM reniva_pla 
INNER JOIN renta_iva ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
INNER JOIN det_plan  ON reniva_pla.Pld_Cod=det_plan.Pld_Cod
INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
WHERE Ren_Est='A' AND reniva_pla.Ren_Tip='V'  AND Ren_Sri='$Par_Sql[1]' AND Emp_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
		case 1269:
			$sql = "SELECT det_ccpp_c.Cpc_Cod,det_ccpp_c.Com_Cod FROM det_ccpp_c 
					INNER JOIN ccpp_cobrar ON ccpp_cobrar.Cpc_Cod=det_ccpp_c.Cpc_Cod 
					INNER JOIN comprobantes ON det_ccpp_c.Com_Cod=comprobantes.Com_Cod
					WHERE Com_Est='A' AND Vet_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
		case 1270:
			$sql = "SELECT * FROM ccpp_cobrar WHERE Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		case 1271:
			/**
			 * Selecionar el numero maximo de comprobante mensual según el tipo
			 */
			//var_dump($Par_Sql);
			$sql = "SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod = $Par_Sql[0] AND Pec_Cod = $Par_Sql[1] AND 
						MONTH(Com_Fec) = '$Par_Sql[2]'";
			//echo $sql;
			return $sql;
		case 1272:
			$sql = "SELECT 					  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv,confi_fact.Cof_Con 
		    FROM
			empresas
			INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
			INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
		    WHERE
		        confi_fact.Emp_Cod=$Par_Sql[0]";
			return $sql;
		case 1273:
			$sql = "INSERT INTO ventas_compr (Vet_Cod, Com_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
			return $sql;
		case 1274:
			//Antes
			//$sql = "SELECT Com_Cod FROM ventas_compr WHERE Vet_Cod='$Par_Sql[0]'";
			//Después
			$sql = "SELECT comprobantes.Com_Cod,Com_Fec,Com_Num FROM ventas_compr,comprobantes WHERE Vet_Cod='$Par_Sql[0]' AND comprobantes.Com_Cod=ventas_compr.Com_Cod";
			//echo $sql;
			return $sql;
		case 1275:
			$sql = "DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;

		case 1276:
			$sql = "UPDATE ventas SET Cli_Cod='$Par_Sql[1]' WHERE Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;

		case 1277:
			/**
			 * Consulta las facturas de ventas segun cedula
			 */
			$sql = "SELECT 
					  cliente.Cli_Cod,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,
					  ventas.Vet_Cod,
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml,
                      ventas.Tic_Cod      
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
					WHERE
					  persona.Prs_Ced ='$Par_Sql[0]' AND 
					  ventas.Tic_Cod = '$Par_Sql[1]' AND 
					  puntos_imp.Suc_Cod = '$Par_Sql[2]' AND 
					  YEAR(caja_aper.Caj_Fec) = '$Par_Sql[3]' $Par_Sql[4]          
					ORDER BY ventas.Vet_Cod ASC";
			//echo $sql;
			return $sql;
			break;

		case 1278:
			/**
			 * Consulta de las facturas por el numero de factura
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
				  FROM 
					 caja_aper 
						   INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
						   INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
						   INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
						   INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod) 
						   INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod) 						   
				    WHERE ventas.Vet_Num='$Par_Sql[0]' AND puntos_imp.Suc_Cod = $Par_Sql[1]    
					ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $sql;
			return $sql;
			break;

		case 1279:
			/**
			 * Consulta las facturas por apellidos modificarlas 
			 */
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
						   ventas.Vet_Est 
				    FROM 
					 caja_aper 
				     INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
				     INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
				     INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
				     INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod) 
				     INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				    WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND puntos_imp.Suc_Cod = $Par_Sql[1]   
					ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";
			//echo $sql;
			return $sql;
			break;

		case 1280:
			$sql = "SELECT reniva_pla.Pld_Cod FROM reniva_pla 
                INNER JOIN renta_iva ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
                INNER JOIN det_plan  ON reniva_pla.Pld_Cod=det_plan.Pld_Cod
                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                INNER JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE reniva_pla.Ren_Tip='V'  AND reniva_pla.Ren_Cod='$Par_Sql[1]' AND Emp_Cod=$Par_Sql[0] AND Pec_Cod='$Par_Sql[2]'";
			//echo $sql.'<br>';
			return $sql;

		case 1281:
			/*Consultar todos los productos que son bienes */
			$busca_ite_cat = "SELECT 
  precios.Tpv_Cod,
  item.Ite_Cod,
  item.Ite_Est,
  item.Ite_Cor,
  CONCAT(item.Ite_Lar,' ',producto.Pro_Obs) AS Ite_Lar,
  marca.Mar_Cod,
  marca.Mar_Des,
  adquisicio.Adq_Cod,
  adquisicio.Adq_Des,
  adquisicio.Adq_Cor,
  iva.Iva_Cod,
  iva.Iva_Por,
  producto.Pro_Bar,
  producto.Pro_Obs,
  producto.Pro_Cod,
  producto.Pro_Est,
  producto.Pro_Gen,
  producto.Pro_Cdc,
  producto.Pro_Sec,
  stock.Stk_Can,
  precios.Pre_Pvp,
  producto.Pro_Uni
FROM
  categorias
  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
  INNER JOIN tipo_preci ON (precios.Tpv_Cod = tipo_preci.Tpv_Cod)
  INNER JOIN stock ON (precios.Pro_Cod = stock.Pro_Cod)
  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
  INNER JOIN produ_plan ON producto.Pro_Cod= produ_plan.Pro_Cod 
    INNER JOIN det_plan ON det_plan.Pld_Cod= produ_plan.Pld_Cod
    INNER JOIN plan_cuenta ON det_plan.Pla_Cod= plan_cuenta.Pla_Cod
    INNER JOIN perio_cont ON perio_cont.Pla_Cod= plan_cuenta.Pla_Cod
WHERE precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Est = 'A' AND Pec_Cod=$Par_Sql[2]
 AND (item.Ite_Lar LIKE '%$Par_Sql[0]%' OR producto.Pro_Obs LIKE '%$Par_Sql[0]%') AND categorias.Emp_Cod = $Par_Sql[1] AND (Tip_Pld='V' OR Tip_Pld='I')";
			//echo $busca_ite_cat;
			return $busca_ite_cat;

		case 1282:
			$sql = "SELECT * FROM renta_iva                
                WHERE Ren_Sri='$Par_Sql[1]' $Par_Sql[0] ";
			//echo $sql.'<br>';
			return $sql;
		case 1283:
			$sql = "INSERT INTO renta_iva(Ren_Sri,Ren_Con,Ren_Por,Ren_Ini,Ren_Fin,Ren_Ing,Ren_Tip,Ren_Ret,Ren_Est,Adq_Cod)
            VALUES('$Par_Sql[Ren_Sri]','$Par_Sql[Ren_Con]','$Par_Sql[Ren_Por]','$Par_Sql[Ren_Ini]','$Par_Sql[Ren_Fin]','$Par_Sql[Ren_Ing]','$Par_Sql[Ren_Tip]','$Par_Sql[Ren_Ret]','I','$Par_Sql[Adq_Cod]')";
			//echo $sql.'<br>';
			return $sql;
		case 1284:
			$sql = "INSERT INTO reniva_pla(Ren_Cod,Pld_Cod,Ren_Tip)
                    VALUES($Par_Sql[0],$Par_Sql[1],'V');";
			//echo $sql.'<br>';
			return $sql;

		case 1285:
			$sql = "SELECT ventas.Vet_Cod,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)) AS Fac_Num, ventas.Vet_Num, ventas.Ret_Num, caja_aper.Caj_Fec,CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as cliente, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est, SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, 
			SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,
				SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva, SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 
                            FROM ventas 
                            INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod) 
                            INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
                        INNER JOIN sucursal ON (sucursal.Suc_Cod=puntos_imp.Suc_Cod)
                            INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
                            INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                            INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                    WHERE (Caj_Fec BETWEEN '$Par_Sql[Fec_Ini] 00:00:00' AND '$Par_Sql[Fec_Fin] 23:59:59') 
                            AND ventas.Vet_Est = 'A' AND Vet_Es2 IS NULL
                        AND ventas.Tic_Cod = $Par_Sql[Tic_Cod] 
                        AND puntos_imp.Suc_Cod =  $Par_Sql[Suc_Cod] " .
				($Par_Sql['Cli_Cod'] != '' ? ' AND cliente.Cli_Cod=' . $Par_Sql['Cli_Cod'] : '')
				. " GROUP BY ventas.Vet_Cod";
			//echo $sql.'<br>';
			return $sql;
		case 1286:
			$sql = "SELECT ventas_det.Pro_Cod,Pro_Obs,SUM(Vet_Can) AS Vet_Can,SUM(Vet_Pru)/COUNT(Vet_Num) AS Vet_Pru,SUM(Vet_Can)*SUM(Vet_Pru)/COUNT(Vet_Num) AS Importe,Iva_Por,Ite_Lar,iva.Iva_Cod
                        FROM ventas_det
                        INNER JOIN ventas ON ventas.Vet_Cod=ventas_det.Vet_Cod
                        INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
                        INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                        INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                        WHERE $Par_Sql[0] 
                        GROUP BY ventas_det.Pro_Cod";
			//echo $sql.'<br>';
			return $sql;

		case 1287: //Busqueda de clientes
			if ($Par_Sql[2] == "d") {
				$search = "(Prs_Ape LIKE '%$Par_Sql[0]%' OR Prs_Nom LIKE '%$Par_Sql[0]%')";
			} else {
				$search = "Prs_Ced LIKE '$Par_Sql[0]%'";
			}
			if ($Par_Sql[3] == "") {
				$campos = "COUNT(Cli_Cod) as total";
			} else {
				$Par_Sql[3] = "ORDER BY Prs_Ape " . $Par_Sql[3];
				$campos = " Cli_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as cliente,Prs_Dir, IF (Cli_Est='A','Activo','Inactivo') as Cli_Est,Prs_Dir";
			}
			$sql = " SELECT $campos FROM cliente, persona WHERE $search AND cliente.Prs_Cod=persona.Prs_Cod AND cliente.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
			//echo $sql.'<br>';
			return $sql;
		case 1288:
			$sql = "SELECT det_plan.Pla_Cod,banco.Ban_Cod,banco.Pld_Cod,CONCAT(Pld_Des,' (',Ban_Cue,')') AS Ban_Des FROM banco
                            INNER JOIN pago_plan ON pago_plan.Ban_Cod=banco.Ban_Cod
                            LEFT JOIN det_plan ON banco.Pld_Cod=det_plan.Pld_Cod
                            LEFT JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                            LEFT JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                            WHERE Pag_Cod=$Par_Sql[0] AND plan_cuenta.Pla_Cod=$Par_Sql[1] AND Emp_Cod='$Par_Sql[2]' AND Ban_Est='A'";
			//echo $sql.'<br>';
			return $sql;

		case 1289:
			$sql = "UPDATE ventas SET Vet_Es2='C' WHERE $Par_Sql[0]";
			//echo $sql.'<br>';
			return $sql;

		case 1290:
			/**
			 * Consulta tipo coprobante segun codigo Venta 
			 */
			$sql = "SELECT tipo_compr.Tic_Cod,tipo_compr.Tic_Sri,tipo_compr.Tic_Des
			FROM
			tipo_compr
			INNER JOIN ventas ON (tipo_compr.Tic_Cod = ventas.Tic_Cod)
			WHERE
			ventas.Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

		case 1291:
			$sql = "SELECT * FROM asientos
					INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
                    INNER JOIN ccpp_cliente ON (det_plan.Pld_Cod = ccpp_cliente.Pld_Cod)
					WHERE Com_Cod=$Par_Sql[0] AND Asi_Deh='D' ";
			// $sql = "SELECT * FROM asientos
			// INNER JOIN det_plan ON det_plan.Pld_Cod=asientos.Pld_Cod
			// WHERE Com_Cod=$Par_Sql[0]";
			//echo $sql.'<br>';
			return $sql;

			/* Activar/Anular Comprobantes contables de VENTAS por Vet_Cod*/
		case 1292:
			$sql = "UPDATE 
				comprobantes
				INNER JOIN ventas_compr ON (comprobantes.Com_Cod = ventas_compr.Com_Cod)
				INNER JOIN ventas ON (ventas_compr.Vet_Cod = ventas.Vet_Cod)
				SET comprobantes.Com_Est= '$Par_Sql[0]' WHERE ventas.Vet_Cod = '$Par_Sql[1]'";
			//echo $sql.'<br>';
			return $sql;

		case 1293:
			$sql = "SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
			//echo $sql.'<br>';
			return $sql;

			//Consulta para obtener la información del cliente declarado como consumidor final
		case 1301:
			$sql = "SELECT Cli_Cod 
                        FROM cliente,persona
                        WHERE persona.Prs_Ced='9999999999999' AND persona.Prs_Cod=cliente.Prs_Cod AND cliente.Emp_Cod='$Par_Sql[0]'";
			return $sql;

			//Consulta para listar las ciudades
		case 1302:
			$sql = "SELECT Ciu_Cod,Ciu_Des FROM ciudad WHERE Ciu_Est='A'";
			return $sql;

			//Verifica si la persona existe
		case 1303:
			$sql = "SELECT Prs_Cod FROM persona WHERE Prs_Ced='$Par_Sql[0]' AND Prs_Est='A'";
			//echo $sql;
			return $sql;

			//Inserta una persona en la tabla del mismo nombre
		case 1304:
			$sql = "INSERT INTO persona(Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Prs_Dir,Ciu_Cod,Prs_Cor) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]')";
			//echo $sql;
			return $sql;

			//Inserta un nuevo cliente en la tabla del mismo nombre
		case 1305:
			$sql = "INSERT INTO cliente(Prs_Cod,Emp_Cod,Cli_Tic) VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
			//echo $sql;
			return $sql;

			//Obtenemos el código de la ciudad catalogada como (Ninguna)
		case 1306:
			$sql = "SELECT Ciu_Cod FROM ciudad WHERE Ciu_Des='(Ninguna)'";
			//echo $sql;
			return $sql;
			/**
			 *  Consulta el tipo de pago SRI
			 */
		case 1307:
			$sql = "SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Est='A'";
			return $sql;
			break;

			/* Consultar los codigos de renta-iva*/
		case 1308:
			$sql = "SELECT 
					  ventas_det.Ren_Cod,
					  ventas_det.Ren_Iva,
					  renta_iva.Ren_Sri,
					  renta_iva.Ren_Con,
					  renta_iva.Ren_Por,  
					  ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2) AS Ret_Bas,	
					  ((ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2))* renta_iva.Ren_Por)/100 as Val_Ret
					FROM
					  renta_iva
					  INNER JOIN ventas_det ON (renta_iva.Ren_Cod = ventas_det.Ren_Cod)
					  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
					WHERE
					ventas_det.Vet_Cod='$Par_Sql[0]' and ventas_det.Pro_Cod='$Par_Sql[1]' AND ventas_det.Ren_Cod='$Par_Sql[2]' AND ventas_det.Vet_Ite='$Par_Sql[3]'";
			//echo $sql;
			return $sql;
			break;

			/*consultamos los datos de la Venta de Vet_Cod*/
		case 1309:
			$sql = "SELECT Caj_Cod,Vet_Cod,Vet_Aut,Aut_Cod FROM ventas WHERE Vet_Est='A' AND  Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			/*Consultas realizadas por José Ambuludí*/
			/*Consulta para obtener el Pun_Cod y Caj_Fec de la tabla caja_aper según el Caj_Cod*/
		case 1310:
			$sql = "SELECT Pun_Cod,Caj_Fec,Caj_Est FROM caja_aper WHERE Caj_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/*Consulta para obtener el Caj_Cod de la tabla caja_aper según los parámetros de búsqueda Pun_Cod y Caj_Fec*/
		case 1311:
			$sql = "SELECT Caj_Cod,Pun_Cod,Caj_Fec FROM caja_aper WHERE Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
			//echo $sql;
			return $sql;
			break;

			/*Update de la tabla ventas con el nuevo Caj_Cod*/
		case 1312:
			$sql = "UPDATE ventas SET Caj_Cod='$Par_Sql[1]' WHERE Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/*Update de la tabla comprobantes en el campo Com_Fec*/
		case 1313:
			$sql = "UPDATE comprobantes SET Com_Fec='$Par_Sql[1]',Com_Num='$Par_Sql[2]' WHERE Com_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/*consulta si un numero de ventas no se repite*/
		case 1314:
			$sql = "SELECT COUNT(Vet_Num) AS Vet_Num 
					FROM ventas 
					INNER JOIN cliente ON ventas.Cli_Cod=cliente.Cli_Cod
					INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
					INNER JOIN tipo_compr ON ventas.Tic_Cod=tipo_compr.Tic_Cod
					WHERE Emp_Cod='$Par_Sql[0]' AND Vet_Num='$Par_Sql[1]' AND autorizaci.Aut_Sri='$Par_Sql[2]' AND tipo_compr.Tic_Sri='$Par_Sql[3]'";
			//echo $sql;
			return $sql;
			break;
			/*SElect para obtener el campo Com_Cod de la tabla ventas_compr*/
		case 1315:
			$sql = "SELECT Com_Cod FROM ventas_compr WHERE Vet_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/*Update de la tabla comprobantes en el campo Cli_Cod*/
		case 1316:
			$sql = "UPDATE comprobantes SET Cli_Cod='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;

			/*Consultamos la cuenta parametrizado en la tabla plan_param*/
		case 1317:
			$sql = "SELECT plan_param.Pld_Cod,plan_param.Tpa_Cod 
				  FROM tipo_param
				  INNER JOIN plan_param ON (tipo_param.Tpa_Cod = plan_param.Tpa_Cod)
				  INNER JOIN det_plan ON (plan_param.Pld_Cod = det_plan.Pld_Cod)
				  WHERE Tpa_Abr='$Par_Sql[0]' AND Tpa_Est='A' AND Ppc_Est='A' AND Pla_Cod='$Par_Sql[1]'";
			//echo $sql;
			return $sql;
			break;
		case 1318:
			$sql = "SELECT 
			  SUM(IF(imp_ren.Ren_Ret='R',ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2),0)* imp_ren.Ren_Por/100) AS r_renta,
			  SUM(IF(ret_iva.Ren_Ret='I',ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)))+
			  ((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)))*Vet_Ice/100))*Iva_Por/100, 2)* ret_iva.Ren_Por/100,0)) AS r_iva
			FROM
			  ventas_det 
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
			  LEFT JOIN renta_iva AS imp_ren ON (imp_ren.Ren_Cod = ventas_det.Ren_Cod) 
			  LEFT JOIN renta_iva AS ret_iva ON (ret_iva.Ren_Cod = ventas_det.Ren_Iva)
			  INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
			WHERE
			  ventas.Vet_Cod = '$Par_Sql[0]'   Group by ventas.Vet_Cod";
			//echo $sql;
			return $sql;
			break;

			/**
			 *  Consulta el tipo de pago SRI de la venta
			 */
		case 1319:
			$sql = "SELECT Vet_Cod, ventas.Tpc_Cod,Tpc_Sri,Tpc_Des FROM ventas,tipopagocom WHERE ventas.Tpc_Cod=tipopagocom.Tpc_Cod AND Vet_Cod='$Par_Sql[0]'";
			return $sql;
			break;

		case 1320:
			/**
			 * Consulta tipo coprobante segun codigo Venta 
			 */
			$sql = "SELECT DISTINCT tipo_compr.Tic_Cod,tipo_compr.Tic_Sri,tipo_compr.Tic_Des
				FROM
				tipo_compr
				INNER JOIN ventas ON (tipo_compr.Tic_Cod = ventas.Tic_Cod)
				INNER JOIN cliente ON ventas.Cli_Cod=cliente.Cli_Cod
				WHERE
				cliente.Emp_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
		case 1321:
			$sql = "SELECT plan_param.Pld_Cod,Pld_Cdc,Pld_Des,Pla_Cod FROM plan_param
                            INNER JOIN det_plan ON det_plan.Pld_Cod=plan_param.Pld_Cod
                            INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod
                            WHERE Tpa_Abr='$Par_Sql[1]' AND Pla_Cod=$Par_Sql[0];";
			//echo $sql;
			return $sql;
			break;
		case 1322:
			$sql = "SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;


		//Obtener logo de la sucursal
		case 1323:
			$sql = "SELECT Suc_Log as logo_sucursal FROM sucursal where Suc_Cod= $Par_Sql[0]";
			return $sql;
			break;
		



	}
}
