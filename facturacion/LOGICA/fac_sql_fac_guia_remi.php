<?php
	/**
	* Facturación inventario de las ventas
	*/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{	
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
			$provincia="SELECT 
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
				Asi_Int=$Par_Sql[8], Vet_Rec=$Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni=$Par_Sql[12], Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14]"; //,Vet_Ite='$Par_Sql[15]'
		echo $sql."<br>";
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

			case 20: 
			/* insertar datos de la factura*/
			$inser_factpago_20 = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor,Vet_Xml,Vet_Aut,Ret_Num,Ret_Fec,Ret_Aut) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]', '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]', '$Par_Sql[10]','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]','$Par_Sql[14]')";
			//echo  $inser_factpago_20."<br>";
			return $inser_factpago_20;
			break;

			case 21:
			/* Consulta del cliente si es una persona por apellidos */
			$consultar_buscar_21 = "SELECT cliente.Cli_Cod, persona.Prs_Cod,persona.Prs_Ced, persona.Prs_Ape, 
			persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') 
			as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			//echo $consultar_buscar_21;
			return $consultar_buscar_21;
			break;
	
			case 22:
			/* Consulta del personal por cedula */
			$consultar_cliente1 = "SELECT cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Ape,        persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced = '$Par_Sql[0]'  AND cliente.Emp_Cod = $Par_Sql[1] ORDER BY	persona.Prs_Ape, persona.Prs_Nom ASC";
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
			$num_fac="SELECT MAX(Vet_Num) AS Num FROM ventas, autorizaci  WHERE ventas.Aut_Cod = autorizaci.Aut_Cod 
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
			$up_factpago_34 = "UPDATE ventas SET Vet_Num = '$Par_Sql[0]', Vet_Obs = '$Par_Sql[1]', Vet_Des = '$Par_Sql[2]', Ret_Fec='$Par_Sql[3]',Ret_Num='$Par_Sql[4]',Ret_Aut='$Par_Sql[5]' WHERE Vet_Cod = $Par_Sql[6]"; 
			//echo $up_factpago_34;
			return $up_factpago_34;
			break;

			case 37:
			/* 
			* Consulta de los datos del cliente 
			*/
			$consultar_cli_fac_37 = "SELECT 
			  persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Ape,
			  persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel,
			  persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, ventas.Aut_Cod,
			  ventas.Tic_Cod, caja_aper.Caj_Fec, ciudad.Ciu_Des, ventas.Vet_Des,
			  caja_aper.Pun_Cod, ventas_det.Vet_Can, ventas_det.Ren_Cod, ventas_det.Ren_Iva,
			  ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, item.Ite_Cor,
			  item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, iva.Iva_Cod,
			  producto.Pro_Cod, ventas.Vet_Est, ventas.Vnd_Cod, producto.Pro_Ide,
			  producto.Uni_Cod, producto.Pro_Obs, cliente.Cli_Fac, cliente.Cli_Ruf,
			  cliente.Cli_Dir, ventas_det.Cnt_Cod, ventas_det.Vet_Int, marca.Mar_Des,
			  ventas_det.Vet_Uni
			FROM
			  persona
			  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
			  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
			  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
			  INNER JOIN ciudad ON (ventas.Ciu_Cod = ciudad.Ciu_Cod)
			  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			  INNER JOIN producto ON (producto.Pro_Cod = ventas_det.Pro_Cod)
			  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
			  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
			  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
			  INNER JOIN item ON (item.Ite_Cod = producto.Ite_Cod)
			WHERE  ventas.Vet_Cod = '$Par_Sql[0]' AND Vet_Rec = 0";//         AND iva.Iva_Cod = producto.Iva_Cod
			//echo $consultar_cli_fac_37;
			return $consultar_cli_fac_37;
			break;		

			/**
			* Actualizar datos del detalle de la factura en base a la matricula
			*/
			case 38: 
			$sql = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9]";
			return $sql;
			break;	

			

			case 39:
			/* Consulta de los valores de las facturas para realizar calculos */
			$calcular_fac = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Cod, 
						ventas.Vet_Des FROM ventas, ventas_det, iva WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = 
						iva.Iva_Cod AND ventas.Vet_Cod = '$Par_Sql[0]'";
			//echo "<br>".$calcular_fac;
			return $calcular_fac;
			break;

			/**
			* Actualizar datos del detalle de la factura en base a acta de notas, contratos e indice
			*/
			case 40: 
			$sql = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9], Cnt_Cod=$Par_Sql[10], Vet_Int=$Par_Sql[11], Vet_Uni='$Par_Sql[12]', Ren_Cod=$Par_Sql[13], Ren_Iva=$Par_Sql[14]";
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
			$bor_precio="DELETE FROM ventas_det WHERE Vet_Cod='$Par_Sql[0]'";
			return $bor_precio;
			break;

		   /** Selecionar el numero maximo del codigo del producto**/
			case 46:
			$Pro_conf="SELECT Pro_Ide, Col_Eli, Col_Cad FROM confi_teso WHERE Con_Cod = 1";
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
			$mora_deuda_54= "SELECT datediff(Deu_Fec, now()) as Mora, Deu_Fec FROM deudas WHERE Cli_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1] AND Nge_Cod = $Par_Sql[2] 
							AND Asi_Int = $Par_Sql[3] AND Deu_Rec = 0";		
					//echo $mora_deuda_54;		
			return $mora_deuda_54;
			break;

			/* Consultar los rubros destinados para el interes */
			case 56:
			$consul_interes_56= "SELECT 
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
			$deuda_recur_57= "SELECT Deu_Reg, Deu_Fec, Pro_Cod, datediff(Deu_Reg, now()) as Dias_Mora, Deu_Val, Deu_Obs FROM deudas 
							WHERE Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
							AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3]";
							//echo $deuda_recur_57;
			return $deuda_recur_57;
			break;
			

			/* Consulta de los rubros recursivos, especialmente INTERES */
			case 58:
			$deuda_58= "SELECT deudas.Pro_Cod, Pro_Ide, Deu_Val, Deu_Fec, Ite_Lar, producto.Iva_Cod, Iva_Por, Nge_Cod, Deu_Rec, Asi_Int FROM 
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
			$actualiza_interes_64= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Reg = '$Par_Sql[1]' WHERE Nge_Cod = $Par_Sql[2] AND 
						Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6]";
						//echo $actualiza_interes_64;
			return $actualiza_interes_64;
			break;

			/* Consulta los pagos realizados por el cliente */
			case 68:
			$pagos_68= "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
					ventas_det.Vet_Cod AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
					= $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND ventas.Vet_Est = 'A'";
					//echo $pagos_68;
			return $pagos_68;
			break;

			 /*  Consulta las becas en base al codigo del cliente */ 
			case 73:
			$becas_cli_73= "SELECT becas.Bec_Cod, det_becas.Pro_Cod, Bec_Pot, Bec_Por, Tib_Ini FROM becas, matriculas, estudiante, 
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
			$sql = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec,
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
		   $beca_asignada_76= "SELECT Bec_Pot, Bec_Por, tipo_beca.Tib_Ini, tipo_beca.Tib_Cod, Mat_Int, tipo_beca.Tib_Des FROM becas, det_becas, tipo_beca WHERE becas.Bec_Cod = det_becas.Bec_Cod 
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
	   $Num_factura= "SELECT 
			  autorizaci.Aut_Sri,
			  autorizaci.Pun_Sri,
			  sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin
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
FROM caja_aper, puntos_imp  WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod  AND caja_aper.Pun_Cod = '$Par_Sql[0]'"; //AND caja_aper.Caj_Est ='A'
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
		$selecciona_NumMax_96= "SELECT MAX(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]' AND (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
//echo $selecciona_NumMax_96;
	   return $selecciona_NumMax_96;
	   break;

      

	   /* 
	   * Seleccionar el numero minimo de la factura
	   */ 
		case 97:
		$selecciona_NumMin= "SELECT MIN(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]'
					AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
	   return $selecciona_NumMin;
	   break;

		/**
		* Consulta el valor de la cuenta por cobrar de una deuda 
		*/ 
		case 98:
		$sql= "SELECT deudas.Deu_Val FROM  deudas WHERE deudas.Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
						AND Pro_Cod = $Par_Sql[2] AND Asi_Int = $Par_Sql[3]"; 
						//echo $sql;
		return $sql;
		break;

		/**
		* Consulta el valor de la cuenta por cobrar de una deuda con acta de notas, contratos e indice
		*/ 
		case 98:
		$sql= "SELECT deudas.Deu_Val FROM  deudas WHERE deudas.Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
						AND Pro_Cod = $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND Cnt_Cod = $Par_Sql[4] AND Vet_Int = $Par_Sql[5]"; 
						//echo $sql;
		return $sql;
		break;

	    case 106: 
		/*
		* Busca las facturas registradas de acuerdo a los intervalos de fecha
		*/
		$cons_fact_106 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est,
					  SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, 					   
					  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,					  
					  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
					  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 					  
					  FROM 
					  ventas 
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 					 
					  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
					  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
					  WHERE (Caj_Fec BETWEEN 
					  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY ventas.Vet_Cod, 					  ventas.Vet_Num, caja_aper.Caj_Fec,persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
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
		SELECT 
			ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec,  
		  	persona.Prs_Nom, persona.Prs_Ape, Car_Nom, sum(ventas_det.Vet_Imp) AS Vet_Tot, 
		  	ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, ventas_det.Asi_Int 
		FROM 
			caja_aper, ventas, ventas_det, cliente, iva, persona, carreras, niveles, semestres, promocione, notasgener 
		WHERE ventas.Caj_Cod = caja_aper.Caj_Cod 
		    AND ventas_det.Vet_Cod = ventas.Vet_Cod 
		    AND ventas.Cli_Cod = cliente.Cli_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod 
			AND cliente.Prs_Cod = persona.Prs_Cod AND notasgener.Nge_Cod = ventas_det.Nge_Cod 
			AND notasgener.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod 
			AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int = carreras.Car_Int 
			AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND promocione.Car_Int = $Par_Sql[2] 
			AND ventas.Vet_Est = '$Par_Sql[3]' AND ventas.Tic_Cod = $Par_Sql[4] $Par_Sql[5] 
			GROUP BY ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec,  persona.Prs_Nom, 
			persona.Prs_Ape, Car_Nom, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, 
			ventas_det.Asi_Int
			ORDER BY ventas.Vet_Num, Prs_Ape, Prs_Nom";			
		//echo $my_cons_fact_escuela_110;					
		return $my_cons_fact_escuela_110;
		break;

		case 126: 
		/* 
		* Consulta la información la ciudada en base a la sucursal 
		*/
 		$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
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
							AND cliente.Cli_Cod = $Par_Sql[1] AND matriculas.Mat_Est='$Par_Sql[2]' AND matriculas.Mat_For = 'N'";//ANtes AND ('$Par_Sql[0]' BETWEEN Per_Fea AND Per_Fec) 
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
				$consulta_modalidades_172 ="SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Cod = $Par_Sql[0]" ;
				//echo $consulta_modalidades_172;
			return $consulta_modalidades_172;
			break;

			/*
			* Consulta de las carreras en base al notasgener 
			*/
			case 174:
				$consulta_carreras_174= "SELECT Car_Nom, Niv_Des, Sem_Par, IF (semestres.Sem_Sec='D', 'Diurna', IF (semestres.Sem_Sec='V', 'Vespertina', IF 
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
				$consulta_carreras_176="SELECT Eta_Des, Eta_Rec FROM etapas WHERE Eta_Cod='$Par_Sql[0]'";
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
			  $costos_196="SELECT Pro_Cod, Cos_Pre, Cos_Gen FROM costo_matr WHERE Pem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Sem_Cod = $Par_Sql[2] AND Cos_Est='A'";         
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
			$fac_rubros_210 = "SELECT ventas.Vet_Cod, caja_aper.Caj_Fec, item.Ite_Lar, round((sum(ventas_det.Vet_Imp) - 
						(sum((Vet_Imp * Vet_Des) /100) + sum((Vet_Imp * Vet_Dec) /100))),2) as Vet_Imp, sum((ventas_det.Vet_Imp 
						- (((Vet_Imp * Vet_Des)/100) + ((Vet_Imp * Vet_Dec)/100))) * Iva_Por)/100 as Iva 
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
			$my_fac_rubros_car_211 = "SELECT ventas.Vet_Cod, caja_aper.Caj_Fec, item.Ite_Lar, round((sum(ventas_det.Vet_Imp) 
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
			
			return $my_fac_rubros_car_211;
			break;

			case 212:
			/* 
			* Consulta de los totales de las facturas en un rango de fechas detalladamente 
			*/
			$fac_detalle_212 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape,
	sum(ventas_det.Vet_Imp) AS Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod, ventas_det.Nge_Cod FROM ventas INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE (Caj_Fec BETWEEN 
						  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY 
	ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod ORDER BY ventas.Vet_Num, persona.Prs_Ape, 
	persona.Prs_Nom";
	//echo $fac_detalle_212;
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
						FROM ventas, caja_aper, ventas_det, iva 
						WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
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
		//echo $sql;
		return $sql;
		break;			

                case 247:
		/**
		* Consulta los años de las facturas de compras recibidas 
		*/
		$sql = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras, perio_cont, plan_cuenta WHERE compras.Pec_Cod = perio_cont.Pec_Cod AND perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] AND compras.Cop_Est='A' 
			 ORDER BY YEAR(compras.Cop_Fec) DESC";
		return $sql;
		break;

		/* 
		* Consulta el detalle academico de la deuda 
		*/
		case 259:
		$detalle_deuda_259="SELECT DISTINCT
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
			$modalidades_cliente_263="SELECT 
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
			  deudas.Deu_Fec";//AND periodos.Mod_Cod = $Par_Sql[1] AND periodos.Eta_Cod = $Par_Sql[2] AND promocione.Car_Int = $Par_Sql[3] AND periodos.Per_Int = $Par_Sql[4]
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
			$inser_pago_316 = "SELECT pago_venta.Bak_Cod, pago_venta.Ban_Cod, pago_venta.Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num, Pag_Des, For_Des
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
			$borrar_pago_322="DELETE FROM pago_venta WHERE Vet_Cod='$Par_Sql[0]'";
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
						   ventas.Vet_Est 
				    FROM 
					       caja_aper 
						   INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
						   INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
						   INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
				    WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1]  AND caja_aper.Caj_Fec = '$Par_Sql[2]'  
					ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC"; 
			//echo $sql;
			return $sql;
			break;
		
			case 325:
			/**
			* Consulta de las facturas por el codigo interno de la caja activa 
			*/
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
							  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE ventas.Vet_Num LIKE '$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1] AND caja_aper.Caj_Fec = '$Par_Sql[2]' ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC"; 
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
			 $consultar_beca_deuda_383="SELECT Pro_Cod, Nge_Cod, Cli_Cod FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Bec_Cod IS NULL";
			 return $consultar_beca_deuda_383;
				 break;
		  
			/* Consultar si el producto se encuentra asignado en becas */
			case 384:
			$consultar_beca_asignada_384="SELECT becas.Bec_Cod, det_becas.Pro_Cod FROM becas, det_becas 
					WHERE becas.Bec_Cod=det_becas.Bec_Cod AND becas.Mat_Int=$Par_Sql[0] AND det_becas.Pro_Cod=$Par_Sql[1]";
			return $consultar_beca_asignada_384;
			break;

			/* Baja de la deuda registrada en la tabla deudas */
			case 385:
			$baja_deuda_sinbeca_385="DELETE FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Asi_Int=$Par_Sql[3]";
		//echo $baja_deuda_sinbeca_385;
		
			return $baja_deuda_sinbeca_385;
			break; 

			/* Busca codigo del Item po el codigo del producto JESSICA 16-01-2007*/
			case 462:
			$busca_cod_item_pro= "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cod, item.Ite_Cor, item.Ite_Lar, Pro_Obs
	FROM item, producto WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = $Par_Sql[0]";
			return $busca_cod_item_pro;
			break;


                        /**
			* Consulta de facturas de compras por Apellido del proveedor con estado de Factura Activo e Inactivo 
			*/
			case 483:
			$sql="SELECT 
			  persona.Prs_Ape, guias_remi.Gui_Est, guias_remi.Gui_Cod, guias_remi.Gui_Fec,guias_remi.Gui_Aut,guias_remi.Gui_Xml, persona.Prs_Nom, guias_remi.Gui_Num
			FROM
			  persona
			  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
			  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
			  INNER JOIN autorizaci ON (guias_remi.Aut_Cod = autorizaci.Aut_Cod)
			  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
			WHERE			  
			  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND YEAR(Gui_Fec) = '$Par_Sql[1]' $Par_Sql[2] AND
			  guia_destin.Emp_Cod = $Par_Sql[3] " . ($Par_Sql[4] != '' ? " AND puntos_imp.Suc_Cod = '$Par_Sql[4]'" : "") . "
			ORDER BY guias_remi.Gui_Cod ASC";// compras.Cop_Fec
                        //echo $sql;
			return $sql;
			break;

			/**
			* Consulta las facturas de compra por número de la factura de compra con estado activo e inactivo  
			*/
			case 484:
			$sql="SELECT 
			  persona.Prs_Ape, guias_remi.Gui_Est, guias_remi.Gui_Cod, guias_remi.Gui_Fec,guias_remi.Gui_Xml, guias_remi.Gui_Aut, persona.Prs_Nom, guias_remi.Gui_Num
			FROM
			  persona
			  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
			  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
			WHERE			  
			  guias_remi.Gui_Num='$Par_Sql[0]' AND 
			  guia_destin.Emp_Cod = $Par_Sql[1] 
			ORDER BY guias_remi.Gui_Cod ASC";
                        //echo $sql;
			return $sql;
			break;

			/**
			* Consulta las facturas de compra por RUC  
			*/
			case 485:
			$sql="SELECT 
			  persona.Prs_Ape, guias_remi.Gui_Est, guias_remi.Gui_Cod, guias_remi.Gui_Fec,guias_remi.Gui_Xml, guias_remi.Gui_Aut, persona.Prs_Nom, guias_remi.Gui_Num
			FROM
			  persona
			  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
			  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
			WHERE			  
			  persona.Prs_Ced='$Par_Sql[0]' AND YEAR(Gui_Fec) = '$Par_Sql[1]' $Par_Sql[2] AND
			  guia_destin.Emp_Cod = $Par_Sql[3] 
			ORDER BY guias_remi.Gui_Cod ASC";
                        //echo $sql;
			return $sql;
			break;




			case 631:
			/*Consulta el punto de impresion*/
			$cons_punto = "SELECT puntos_imp.Pun_Cod, puntos_imp.Pun_Des FROM puntos_imp WHERE puntos_imp.Pun_Cod = '$Par_Sql[0]'";
			//echo $cons_punto;
			return $cons_punto;
			break;

			/* Consultar días plazo interés  */
			case 657:
			$mora_interes_dias="SELECT Int_Dia, Int_Por FROM interes";
			//echo $mora_interes_dias;
			return $mora_interes_dias;
			break;			

			/*Consultando datos de la tabla Autorizacion*/
			case 988:
			$Sql_988="SELECT * FROM autorizaci WHERE Aut_Cod= $Par_Sql[0]";
			//echo $Sql_988;
			return $Sql_988;
			break;
			 
			/*Consultando datos de la tabla Autorizacion*/
			case 989:
			$Sql_989="SELECT * FROM ventas WHERE Aut_Cod = $Par_Sql[0] AND Vet_Num = $Par_Sql[1] AND Tic_Cod = $Par_Sql[2]";
			//echo $Sql_989;
			return $Sql_989;
			break;

			case 990:
			$Sql_990="SELECT pago_venta.Vet_Che FROM ventas INNER JOIN pago_venta ON (ventas.Vet_Cod = pago_venta.Vet_Cod)
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
		  	$busca_ite_cat= "SELECT 
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
			$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14])";
			//echo  $sql;
			return $sql;
		break;

		/* Selecciona todos los tipos de comprobantes */
		case 1036:
			$sql = "SELECT Tic_Cod, Tic_Des FROM tipo_compr WHERE Tic_Est='A'";		
//echo $sql;
		return $sql;
		break;

		/*  Validar las Adquisiciones */
		case 1037:
		$sql = "SELECT adquisicio.Adq_Cod FROM producto,adquisicio WHERE producto.Adq_Cod=adquisicio.Adq_Cod AND adquisicio.Adq_Cor='B' AND producto.Pro_Cod=$Par_Sql[0]";
		//echo $sql;
		return $sql;		
		break;

		case 1204: 
		/*
		* Consulta sentencia consulto stock del kardex  
		* Se pone cero en el parametro para cuando no hay stock
		*/	
			$busca_ite_cat= "UPDATE stock SET Stk_Can=$Par_Sql[0]+0 WHERE Pro_Cod=$Par_Sql[1] AND Suc_Cod=$Par_Sql[2]" ;
			//echo $busca_ite_cat."<br>";
		return $busca_ite_cat;
		break;
			
		case 1205: 
			/*Consulta sentencia consulto stock del kardex  */
			$busca_ite_cat= "INSERT INTO stock(Stk_Can,Loc_Cod,Pro_Cod)VALUE($Par_Sql[0],$Par_Sql[1],$Par_Sql[2]) " ;
		
		return $busca_ite_cat;
		break;

		case 1206: 
		/*
		* Consulta sentencia consulto stock del kardex  
		*/
		$busca_ite_cat= "SELECT (SUM(Kar_Can)-SUM(Kar_Sal)) AS Stock 
		FROM kardex_ie WHERE Pro_Cod=$Par_Sql[0] AND Kar_Est='A'";	
		//echo $busca_ite_cat;					
		return $busca_ite_cat;


		case 1208: 
  			/*Consulta sentencia consulto stock del kardex  */
		$busca_ite_cat= "SELECT Pro_Cod	FROM ventas_det WHERE Vet_Cod=$Par_Sql[0] ";
			//echo $busca_ite_cat;
 		return $busca_ite_cat;
		break;

			case 1039:
			$bor_precio="DELETE FROM kardex_ie WHERE Vet_Cod='$Par_Sql[0]'";
			return $bor_precio;
			break;

		  case 1062: 
		  /*anulo los comprobantes de ajustes*/
		  $busca_ite_cat= "UPDATE kardex_ie SET  Kar_Est='I' WHERE Vet_Cod=$Par_Sql[0] ";
		 // echo $busca_ite_cat;
		  return $busca_ite_cat;
		  break; 

	  		case 1072:
			$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15])";
			//echo "<br>".$sql;
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
			$busca_ite_cat= "SELECT 
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
			$sql= "SELECT Esq_Cod,Esq_Rec,Esq_Des,Esq_Xml,Esq_Ord FROM esquema WHERE esquema.Tan_Cod=$Par_Sql[0] AND esquema.Esq_Rec=$Par_Sql[1] AND esquema.Esq_Est='A' order by Esq_Ord Asc";
			//echo "1210: ".$sql."<br>";
			return $sql;
			break;
 		  	
			case 1211: 
  			/*Consulta informacion de la empresa */
			$sql= "SELECT 
					  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,empresas.Emp_Cor,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
					  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv 
					FROM
					  empresas
				      INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
					  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
				   WHERE
				      sucursal.Suc_Cod=$Par_Sql[0]";
			//echo "1211: ".$sql."<br>";
			return $sql;
			break;
			
			case 1212: 
  			/*Consulta informacion de la empresa */
			$sql= "SELECT 
					  persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom,
					  persona.Prs_Dir,persona.Prs_Tel,persona.Prs_Cor,
					  guia_destin.Des_Cod,guias_remi.Gui_Cod,guias_remi.Gui_Num,
					  identifica.Ide_Prv,date_format(guias_remi.Gui_Fec, '%d/%m/%Y') AS fecha,date_format(guias_remi.Gui_Fsa, '%d/%m/%Y') AS fecha2,
					  autorizaci.Pun_Sri,tipo_compr.Tic_Sri
				   FROM
					  persona
					  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
					  INNER JOIN autorizaci ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
					  INNER JOIN tipo_compr ON (tipo_compr.Tic_Cod = autorizaci.Tic_Cod)
				   WHERE
					  guias_remi.Gui_Cod = '$Par_Sql[0]'";
			//echo "1212: ".$sql."<br>";
			return $sql;
			break;
			
			case 1213: 
  			/*Consulta informacion total de venta y total del descuento de la venta */
			$sql= "SELECT 
					  ventas.Vet_Cod,
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
			$sql= "SELECT 
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
			$sql= "SELECT 
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
			$sql= "SELECT 
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
			$sql= "SELECT 
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
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			 FROM renta_iva WHERE renta_iva.Ren_Ret='$Par_Sql[1]' 
			  AND  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%'  ORDER BY renta_iva.Ren_Sri";
			//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
			//echo $sql;
			return $sql;
			break;
			
			
			
			/**
			* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) 
			renta_iva.Adq_Cod='$Par_Sql[0]' AND
			*/
			case 361:
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			FROM renta_iva WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
			AND renta_iva.Ren_Est='A'  
			AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri";
			return $sql;
			break;
			
			case 1218: 
  			/*Consulta si existe una autorizacion con la fecha ingresada  */
			$sql= "SELECT Aut_Cod,Aut_Ini,Aut_Fin
				   FROM
					  autorizaci
				   WHERE
					  '$Par_Sql[0]' between Aut_Fci AND Aut_Cad AND Pun_Cod='$Par_Sql[1]' AND Tic_Cod='1' AND Aut_Est='A'";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 1219: 
  			/*Consulta si existe una autorizacion con la fecha ingresada  */
			$sql= "SELECT Caj_Cod,Caj_Fec
				   FROM
					  caja_aper
				   WHERE
					  Pun_Cod='$Par_Sql[0]' AND Caj_Fec='$Par_Sql[1]'";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 1220:
			/* Cambiamos el Estado de todas las cajas segun Pun_Cod*/			
			$sql="Update caja_aper SET Caj_Est='$Par_Sql[0]' WHERE Pun_Cod=$Par_Sql[1]";
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
			$sql="SELECT 
					  ventas.Vet_Cod,autorizaci.Aut_Cod,ventas.Vet_Num
				  FROM
					  autorizaci
					  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
				  WHERE
					  autorizaci.Aut_Sri='$Par_Sql[0]' AND ventas.Vet_Num='$Par_Sql[1]' AND autorizaci.Aut_Est='A'";						   
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
			$sql="SELECT 
					  plan_cuenta.Pla_Cod,perio_cont.Pec_Cod
					FROM
					  empresas
					  INNER JOIN plan_cuenta ON (empresas.Emp_Cod = plan_cuenta.Emp_Cod)
					  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
					WHERE
					  empresas.Emp_Cod='$Par_Sql[0]'";
			return $sql;
			break;	
			
			case 1225:
			/**
			* Consulta las facturas de la caja activa para modificarlas 
			*/
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
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
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
							  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
							  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE ventas.Vet_Num LIKE '$Par_Sql[0]%' AND caja_aper.Pun_Cod = $Par_Sql[1] ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC"; 
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
			  persona.Prs_Ced,
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
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
						   ventas.Vet_Est 
				    FROM 
					       caja_aper 
						   INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) 
						   INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod) 
						   INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
				    WHERE persona.Prs_Ced='$Par_Sql[0]' AND caja_aper.Pun_Cod = $Par_Sql[1]    
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
					  ventas.Vet_Xml
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
					  ventas.Vet_Num,
					  caja_aper.Caj_Fec,					  
					  cliente.Cli_Est,
					  ventas.Vet_Est,
					  ventas.Vet_Aut,
					  ventas.Vet_Xml
					FROM
					  persona
					  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
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
					  ventas.Vet_Xml
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
				$sql="INSERT INTO usuarios (Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Cad)
					  VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',md5('$Par_Sql[3]'),'$Par_Sql[4]')";
				return $sql;
			break;	
			
			case 1235:
			/* Consultamos un usuario segun cedula y sucursal */			
			$sql="SELECT 
					  Usu_Cod,Prs_Cod,Usu_Ced
					FROM
					  usuarios
					WHERE
					  Suc_Cod='$Par_Sql[0]' AND Usu_Ced='$Par_Sql[1]' AND Usu_Est='A'";
			return $sql;
			break;
			
			
			case 1236:
			/**
			* Consulta los años de las facturas de ventas recibidas 
			*/
			$sql = "SELECT YEAR(caja_aper.Caj_Fec) as Anio FROM ventas, caja_aper WHERE ventas.Caj_Cod = caja_aper.Caj_Cod GROUP BY YEAR(caja_aper.Caj_Fec) ORDER BY YEAR(caja_aper.Caj_Fec) DESC";
			//echo $sql;
			return $sql;
			break;		
			
			case 1237: 
			/*
			* Busca las facturas registradas de acuerdo a los intervalos de fecha
			*/
			$sql = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est,
						  SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, 					   
						  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,					  
						  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
						  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 					  
						  FROM 
							  ventas 
							  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
							  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
							  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
							  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
							  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
							  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
						  WHERE 
						  (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')  AND 
						  ventas.Vet_Est = '$Par_Sql[2]' AND 
						  ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] 
						  GROUP BY ventas.Vet_Cod,ventas.Vet_Num, caja_aper.Caj_Fec,persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 
						  ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
			//echo "1237:<br>".$sql;		
			return $sql;
			break;					
			
			case 1238: 
			/*
			* Busca las facturas registradas de acuerdo a los intervalos de fecha
			*/
			$sql = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec,iva.Iva_Por, iva.Iva_Por, ventas.Vet_Est,
						  SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Vet_Tot, 					   
						  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Vet_Pag,					  
						  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
						  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced 					  
						  FROM 
						  ventas 
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 					 
						  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
						  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
						  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
						  WHERE (Caj_Fec BETWEEN 
						  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY ventas.Vet_Cod, 					  ventas.Vet_Num, caja_aper.Caj_Fec,persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, Cli_Fac, ventas_det.Nge_Cod, ventas.Cli_Cod, persona.Prs_Ced ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
			//echo $sql;		
			return $sql;
			break;	
			
			case 1239:
			$sql = "SELECT SUM(ROUND(ventas_det.Vet_Imp, 2)) AS Importe,
							  iva.Iva_Cod,
							  iva.Iva_Sri,
							  iva.Iva_Por,
							  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))),2)) AS Total,
							  SUM(ROUND(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100,2)) AS Iva,
							  SUM(ROUND((((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100)),2)) AS Descuento
							FROM ventas, caja_aper, ventas_det, iva, puntos_imp 
							WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND puntos_imp.Pun_Cod=caja_aper.Pun_Cod AND
							ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
							(caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = '$Par_Sql[2]' AND Tic_Cod = $Par_Sql[3] $Par_Sql[4]
							GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por
							ORDER BY Iva_Por DESC";		
			//echo $sql;
			return $sql;
			break;
			
			case 1240:
			/* 
			* Consulta de los totales de las facturas en un rango de fechas detalladamente 
			*/
			$fac_detalle_212 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape,
						  sum(ventas_det.Vet_Imp) AS Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod, ventas_det.Nge_Cod 
						  FROM 
						  ventas 
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod) 
						  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) 
						  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) 
						  WHERE 
						  (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] 
						  GROUP BY ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod ORDER BY ventas.Vet_Num, persona.Prs_Ape, 
	persona.Prs_Nom";
	//echo $fac_detalle_212;
			return $fac_detalle_212;
			break;
			
			/*Consultando datos de la tabla detalde ventas*/
			case 1241:
			$sql = "SELECT Tic_Cod, Tic_Sri, Tic_Des FROM tipo_compr WHERE Tic_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			/*Consultando si una venta tiene retencion */
			case 1242:
			$sql="SELECT * FROM ventas_det WHERE Vet_Cod= '$Par_Sql[0]' AND Ren_Cod<>'null' AND Ren_Iva<>'null'";
			//echo $sql;
			return $sql;
			break;
			
			/*Consultando si una venta tiene retencion */
			case 1243:
			$sql="INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]')";
			//echo $sql;
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
			return $sql;
			break;
			
			case 1246:
			/* Consulta la si existe usuario en la master */
			$sql = "SELECT 
					  Suc_Cod, Acc_Usr
					FROM
					  access
					WHERE Suc_Cod = '$Par_Sql[0]' AND Dat_Cod = '$Par_Sql[1]' AND Acc_Usr = '$Par_Sql[2]'";
			return $sql;
			break;
			
			/* 
			* Seleccionar el numero maximo de la factura
			*/ 
			case 1247:
			$sql= "SELECT 
						MAX(ventas.Vet_Num) AS Num 
				   FROM 
						ventas 		 
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
						INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
				   WHERE 			 
				   ventas.Vet_Est = '$Par_Sql[2]' AND 
				   (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND 
				   ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
		   //echo $sql;
		   return $sql;
		   break;
     	   
		   /* 
		   * Seleccionar el numero minimo de la factura
		   */ 
		   case 1248:
			$sql= "SELECT 
						MIN(ventas.Vet_Num) AS Num 
				   FROM 
				        ventas 		 
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
						INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
				   WHERE 
				        ventas.Vet_Est = '$Par_Sql[2]' AND 
						Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
						ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
		   return $sql;
		   break;
		   
		   /* 
		   * Seleccionar los detalles de la venta
		   */ 
		   case 1249:
			$sql= "SELECT 
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
			$sql= "SELECT 
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
			$sql= "SELECT Vet_Cod, Ren_Cod, Ren_Iva FROM ventas_det WHERE Vet_Cod = '$Par_Sql[0]' AND (Ren_Cod is not null or Ren_Iva is not null) limit 1"; 
		   return $sql;
		   break;
		   
		   /**
		   * Consultar informacion de la compra
		   */
		   case 1252:	
		   $sql="SELECT 
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
		   $sql="SELECT Tic_Cod, Tic_Sri, Tic_Des FROM tipo_compr WHERE Tic_Sri= '$Par_Sql[0]'";		   
		   return $sql;
		   break;
		   
		   /**
		   * Consultar el codigo del perfil Clientes segun la empresa
		   */
		   case 1254:	
		   $sql="SELECT Per_Cod,Per_Des FROM perfiles WHERE Per_Des = 'Clientes' AND Emp_Cod = '$Par_Sql[0]' AND Per_Est='A'";		   
		   return $sql;
		   break;
		   
		   /**
		   * Asignamos el perfil al cliente
		   */
		   case 1255:	
		   $sql="INSERT INTO usuarperfi (Usu_Cod,Per_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";		   
		   return $sql;
		   break;
		   
		   /**
		   * consultamos destinatario por apellido
		   */
		   case 1256:	
		   $sql=" SELECT 
				  guia_destin.Des_Cod,
				  guia_destin.Des_Sri,
				  guia_destin.Des_Adu,
				  persona.Prs_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir
				FROM
				  persona
				  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
				WHERE
				  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND guia_destin.Emp_Cod='$Par_Sql[1]' AND
				  guia_destin.Des_Est='A' order by persona.Prs_Ape ASC";		   
		  //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		   * consultamos destinatario por cedula
		   */
		   case 1257:	
		   $sql=" SELECT 
				  guia_destin.Des_Cod,
				  guia_destin.Des_Sri,
				  guia_destin.Des_Adu,
				  persona.Prs_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir
				FROM
				  persona
				  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
				WHERE
				  persona.Prs_Ced ='$Par_Sql[0]' AND guia_destin.Emp_Cod='$Par_Sql[1]' AND
				  guia_destin.Des_Est='A' order by persona.Prs_Ape ASC";		   
		   return $sql;
		   break;
		   
		   /**
		   * consultamos transporte por apellido
		   */
		   case 1258:	
		   $sql="SELECT 
				  persona.Prs_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir,
				  transporte.Tra_Cod
				FROM
				  persona
				  INNER JOIN transporte ON (persona.Prs_Cod = transporte.Prs_Cod)
				WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND transporte.Tra_Est='A' ORDER BY persona.Prs_Ape";		   
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		   * consultamos transporte por cedula
		   */
		   case 1259:	
		   $sql="SELECT 
				  persona.Prs_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir,
				  transporte.Tra_Cod
				FROM
				  persona
				  INNER JOIN transporte ON (persona.Prs_Cod = transporte.Prs_Cod)
				WHERE persona.Prs_Ced='$Par_Sql[0]' AND transporte.Emp_Cod='$Par_Sql[1]' AND transporte.Tra_Est='A' ORDER BY persona.Prs_Ape";		   
		   return $sql;
		   break;
		   
		    /**
		   * consultamos destinatario por codigo interno
		   */
		   case 1260:	
		   $sql=" SELECT 
				  guia_destin.Des_Cod,
				  guia_destin.Des_Sri,
				  guia_destin.Des_Adu,
				  persona.Prs_Cod,
				  persona.Prs_Ced,
                                  persona.Prs_Cor,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir
				FROM
				  persona
				  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
				WHERE
				  (persona.Prs_Ced= '$Par_Sql[0]' OR persona.Prs_Ced= '$Par_Sql[0]001') AND guia_destin.Emp_Cod='$Par_Sql[1]' AND
				  guia_destin.Des_Est='A' order by persona.Prs_Ape ASC"; // guia_destin.Des_Cod= '$Par_Sql[0]'
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		   * consultamos transporte por codigo interno
		   */
		   case 1261:	
		   $sql="SELECT 
				  persona.Prs_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  persona.Prs_Dir,
				  transporte.Tra_Cod
				FROM
				  persona
				  INNER JOIN transporte ON (persona.Prs_Cod = transporte.Prs_Cod)
				WHERE (persona.Prs_Ced= '$Par_Sql[0]' OR persona.Prs_Ced= '$Par_Sql[0]001') AND transporte.Emp_Cod='$Par_Sql[1]' AND transporte.Tra_Est='A' ORDER BY persona.Prs_Ape";		   
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		   * consultamos la autorizacion para la Guia de Remision
		   */
		   case 1262:	
		   $sql="SELECT 
				  autorizaci.Aut_Cod,
				  autorizaci.Aut_Sri,
				  autorizaci.Aut_Ini,
				  autorizaci.Aut_Fin,
				  autorizaci.Aut_Fci,
				  sucursal.Suc_Sri,
				  puntos_imp.Pun_Des,
				  autorizaci.Pun_Sri,
				  tipo_compr.Tic_Cod
				FROM
				  puntos_imp
				  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				WHERE
				  tipo_compr.Tic_Sri = '6' AND
				  autorizaci.Aut_Est='A' AND 
	  			  vendedor.Vnd_Est='A' AND
				  vendedor.Prs_Cod = '$Par_Sql[0]'";	   
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		   * consultamos el numero maxino de la guia de remision
		   */
		   case 1263:	
		   $sql="SELECT  
				  max(guias_remi.Gui_Num) AS Gui_Num
				FROM
				  autorizaci
				  INNER JOIN guias_remi ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  autorizaci.Aut_Sri = '$Par_Sql[0]' AND
				  tipo_compr.Tic_Sri='06'";	   
		   //echo $sql;
		   return $sql;
		   break; 
		   
		   case 1264: 
  			/*Consulta informacion de la empresa */
			$sql= "SELECT 
					  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
					  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv 
					FROM
					  empresas
				      INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
					  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
				   WHERE
				      sucursal.Suc_Cod=$Par_Sql[0]";
			//echo "1211: ".$sql."<br>";
			return $sql;
			break;
			
			/**
			* Insertamos el nuevo regitro de guias de remision
			*/
			case 1265: 
			$sql = "INSERT INTO guias_remi SET Aut_Cod='$Par_Sql[0]',Des_Cod='$Par_Sql[1]',Tra_Cod='$Par_Sql[2]',Usu_Cod='$Par_Sql[3]',Gui_Num='$Par_Sql[4]',Gui_Xml='$Par_Sql[5]',Gui_Fec='$Par_Sql[6]', Gui_Mot=UPPER('$Par_Sql[7]'),Gui_Pla=UPPER('$Par_Sql[8]'),Gui_Fsa='$Par_Sql[9]',Gui_Far='$Par_Sql[10]',Gui_Rut=UPPER('$Par_Sql[11]'),Gui_Dar=UPPER('$Par_Sql[12]'),Gui_Nve='$Par_Sql[13]',Gui_Fve='$Par_Sql[14]',Gui_Ave='$Par_Sql[15]',Gui_Dve='$Par_Sql[16]',Gui_Dsa=UPPER('$Par_Sql[17]')";
			//echo "<br>".$sql;
			return $sql;
			break;
			
			/**
			* Insertamos el detalle de guias de remision
			*/
			case 1266: 
			$sql = "INSERT INTO guias_det SET Gui_Cod='$Par_Sql[0]',Pro_Cod='$Par_Sql[1]',Gui_Can='$Par_Sql[2]'";
			//echo "<br>".$sql;
			return $sql;
			break;
			
			/**
			* buscamos la factura segun 001-001-000000001
			*/
			case 1267: 
			$sql = "SELECT 
					  ventas.Vet_Cod,
					  caja_aper.Caj_Fec,
					  ventas.Vet_Num,
					  ventas.Vet_Sri,
					  autorizaci.Aut_Sri,
					  autorizaci.Pun_Sri,
					  sucursal.Suc_Sri,
					  persona.Prs_Ape,
  					  persona.Prs_Nom
					FROM
					  puntos_imp
					  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN ventas ON (autorizaci.Aut_Cod = ventas.Aut_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
					  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
  					  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					WHERE
					  sucursal.Suc_Sri='$Par_Sql[0]' AND
					  autorizaci.Pun_Sri='$Par_Sql[1]' AND 
					  ventas.Vet_Num='$Par_Sql[2]' AND 
					  sucursal.Suc_Cod='$Par_Sql[3]'";
			//echo "<br>".$sql;
			return $sql;
			break;

                        /**
			* consultamos el transportistas
			*/
			case 1268: 
			$sql = "SELECT 
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  guias_remi.Gui_Pla,
					  guias_remi.Gui_Dsa,
					  date_format(guias_remi.Gui_Fsa,'%d/%m/%Y')as Gui_Fsa,
					  date_format(guias_remi.Gui_Far,'%d/%m/%Y')as Gui_Far,
					  identifica.Ide_Prv
					FROM
					  persona
					  INNER JOIN transporte ON (persona.Prs_Cod = transporte.Prs_Cod)
					  INNER JOIN guias_remi ON (transporte.Tra_Cod = guias_remi.Tra_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					WHERE
					  guias_remi.Gui_Cod='$Par_Sql[0]' AND transporte.Emp_Cod='$Par_Sql[1]'";
			//echo "<br>".$sql;
			return $sql;
			break;

                        /**
			* consultamos el destinatario
			*/
			case 1269: 
			$sql = "SELECT 
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape,					 
					  guias_remi.Gui_Pla,
					  date_format(guias_remi.Gui_Fsa,'%d/%m/%Y') as Gui_Fsa,
					  date_format(guias_remi.Gui_Fec,'%d/%m/%Y') as Gui_Fec,				
					  date_format(guias_remi.Gui_Far,'%d/%m/%Y') as Gui_Far,
					  guia_destin.Des_Sri,
					  guia_destin.Des_Adu,
					  guias_remi.Gui_Dar,
					  guias_remi.Gui_Rut,
					  guias_remi.Gui_Nve,
					  guias_remi.Gui_Ave,
                      guias_remi.Gui_Fve,
					  guias_remi.Gui_Dve,
					  guias_remi.Gui_Dsa,
                      guias_remi.Gui_Mot 
					FROM
					  persona
					  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
					  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
					WHERE
					  guias_remi.Gui_Cod='$Par_Sql[0]' AND guia_destin.Emp_Cod='$Par_Sql[1]'";
			//echo "<br>".$sql;
			return $sql;
			break;
			
                        /**
			* consultamos el detalle de guias de remision segun codigo
			*/
			case 1270: 
			$sql = "SELECT 
				    guias_det.Gui_Cod,
				    guias_det.Pro_Cod,
				    guias_det.Gui_Can,
				    item.Ite_Lar,
				    producto.Pro_Obs
				FROM
				    item
				    INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
				    INNER JOIN guias_det ON (producto.Pro_Cod = guias_det.Pro_Cod)
				WHERE
				    guias_det.Gui_Cod='$Par_Sql[0]'";
			//echo "<br>".$sql;
			return $sql;
			break;

                        /**
			* Consultar el usuario que creo un guia de remision
			*/
			case 1271:	
			$sql="SELECT 
				  guias_remi.Gui_Cod,
				  persona.Prs_Ape,
				  persona.Prs_Nom
				FROM
				  persona
				  INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)				  
				  INNER JOIN guias_remi ON (usuarios.Usu_Cod = guias_remi.Usu_Cod)
				WHERE
				  guias_remi.Gui_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			/**
			* Consultar informacion de la guias de remision
			*/
			case 1272:	
			$sql="SELECT 
				  guias_remi.Gui_Cod,
				  guias_remi.Gui_Num,
				  persona.Prs_Ape,
				  persona.Prs_Nom,
				  persona.Prs_Ced
				FROM
				  persona				  
				  INNER JOIN guia_destin ON (persona.Prs_Cod = guia_destin.Prs_Cod)
				  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)				  
				WHERE
				  guias_remi.Gui_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
 
                        /**
			* Consultar detalle de la guias de remision
			*/
			case 1273:	
			$sql="SELECT 
				  guias_det.Pro_Cod,
				  guias_det.Gui_Cod,
				  guias_det.Gui_Can,
				  item.Ite_Lar,
				  Uni_Des,
				  producto.Pro_Obs
				FROM
				  item
				  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
				  INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
				  INNER JOIN guias_det ON (producto.Pro_Cod = guias_det.Pro_Cod)				  
				WHERE
				  guias_det.Gui_Cod='$Par_Sql[0]' AND guias_det.Gui_Est='A'";
			//echo $sql;
			return $sql;
			break;

                        /*
			*  Detalle del Destinatario
			*/
			case 1274:
			$sql="SELECT 
				  persona.Prs_Ced,
				  persona.Prs_Ape,
				  persona.Prs_Nom,
				  guia_destin.Des_Sri,
				  guia_destin.Des_Adu,
				  guias_remi.Gui_Dar,
				  guias_remi.Gui_Mot
				FROM
				  guia_destin
				  INNER JOIN guias_remi ON (guia_destin.Des_Cod = guias_remi.Des_Cod)
				  INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)
				WHERE
				  guias_remi.Gui_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			/*
			*  Detalle del Transporte
			*/
			case 1275:
			$sql="SELECT 
				  persona.Prs_Ced,
				  persona.Prs_Ape,
				  persona.Prs_Nom,
				  guias_remi.Gui_Pla,
				  guias_remi.Gui_Fsa,
				  guias_remi.Gui_Far,
				  guias_remi.Gui_Dsa,
				  guias_remi.Gui_Rut
				FROM
				  persona
				  INNER JOIN transporte ON (persona.Prs_Cod = transporte.Prs_Cod)
				  INNER JOIN guias_remi ON (transporte.Tra_Cod = guias_remi.Tra_Cod)
				WHERE
				  guias_remi.Gui_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;


		        /*
			*  Buscamos la cabecera guia remision segun Gui_Cod
			*/
			case 1276:
				$sql="SELECT					  guias_remi.Gui_Cod,guias_remi.Gui_Num,autorizaci.Aut_Sri,guias_remi.Gui_Aut,guias_remi.Gui_Fec,guias_remi.Gui_Mot,guias_remi.Gui_Pla,guias_remi.Gui_Fsa,guias_remi.Gui_Far,guias_remi.Gui_Rut,guias_remi.Gui_Dar,guias_remi.Gui_Dsa,guias_remi.Tra_Cod,guias_remi.Des_Cod,guias_remi.Gui_Dve,guias_remi.Gui_Ave,guias_remi.Gui_Fve,guias_remi.Gui_Nve
				FROM
				  guias_remi
				  INNER JOIN autorizaci ON (guias_remi.Aut_Cod = autorizaci.Aut_Cod)
				WHERE
				  guias_remi.Gui_Est = 'A' AND 
				  guias_remi.Gui_Cod = '$Par_Sql[0]'";
		        //echo $sql;
			return $sql;
			break;

		// =====================================================
		// DERECHOS MINEROS - Consultas SQL
		// =====================================================
		
		case 1277:
		/* Consulta lista de derechos mineros activos */
		$sql = "SELECT Der_Min_Id, Der_Min_Codigo, Der_Min_Nombre, Der_Min_Titular_Operador, Der_Min_Tipo, 
				Der_Min_Ubicacion, Der_Min_Observaciones, Der_Min_Estado 
				FROM fac_derechos_mineros 
				WHERE Der_Min_Estado = 'A' 
				ORDER BY Der_Min_Nombre";
		return $sql;
		break;

		case 1278:
		/* Insertar nuevo derecho minero */
		$sql = "INSERT INTO fac_derechos_mineros 
				(Der_Min_Codigo, Der_Min_Nombre, Der_Min_Titular_Operador, Der_Min_Tipo, 
				 Der_Min_Ubicacion, Der_Min_Observaciones, Der_Min_Estado, Der_Min_Fecha_Registro) 
				VALUES 
				('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', 
				 '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]')";
		return $sql;
		break;

		case 1279:
		/* Actualizar derecho minero existente */
		$sql = "UPDATE fac_derechos_mineros SET 
				Der_Min_Codigo = '$Par_Sql[0]', 
				Der_Min_Nombre = '$Par_Sql[1]', 
				Der_Min_Titular_Operador = '$Par_Sql[2]', 
				Der_Min_Tipo = '$Par_Sql[3]', 
				Der_Min_Ubicacion = '$Par_Sql[4]', 
				Der_Min_Observaciones = '$Par_Sql[5]', 
				Der_Min_Fecha_Modificacion = '$Par_Sql[6]' 
				WHERE Der_Min_Id = $Par_Sql[7]";
		return $sql;
		break;

		case 1280:
		/* Consulta lista única de titulares/operadores */
		$sql = "SELECT DISTINCT Der_Min_Titular_Operador 
				FROM fac_derechos_mineros 
				WHERE Der_Min_Titular_Operador IS NOT NULL 
				AND Der_Min_Titular_Operador != '' 
				ORDER BY Der_Min_Titular_Operador";
		return $sql;
		break;

		}
	}
?>