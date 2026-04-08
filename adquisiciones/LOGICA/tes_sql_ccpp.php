<?php
	/*TESORERIA*/
	/*************************************FORMA DE PAGO********************************/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{		
		case 1:
		/* Insertar formas de pago */
		$ins_pagos = "INSERT INTO forma_pago (For_Des) VALUES ('$Par_Sql[0]')";
		return $ins_pagos;
		break;
		
		case 2: 
		/*Consulta una forma de pago en base a un parametro*/
		$cons_pagos = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Des like '$Par_Sql[0]%'";
		return $cons_pagos;
		break;
		
		case 3: 
		/*Consulta una forma de pago en base al codigo*/
		$cons_pagos2 = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Cod=$Par_Sql[0]";
		return $cons_pagos2;
		break;

		case 4: 
		/* Modifica o actualiza los cambios realizados en la tabla formas de pago */
 		$modificar_form = "UPDATE forma_pago SET For_Des ='$Par_Sql[0]' WHERE For_Cod =        $Par_Sql[1]";
		return $modificar_form;
		break;
		
/***************************************************************************************************************************************APERTURA DE CAJA*******************************************/

		case 5: 
		/* Consulta de las cajas activas */
 		$consulta_caja = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Est, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est = 'A', 'Abierta', '') as Caj_Est FROM caja_aper WHERE caja_aper.Caj_Est = 'A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
		return $consulta_caja;
		break;

		case 6:
		/* Apertura de la caja diaria */
		$abrir_caja = "INSERT INTO caja_aper (Caj_Fec, Caj_Hoi, Pun_Cod) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]')";
		return $abrir_caja;
		break;
		
		case 7:
		/* Apertura de la caja diaria */
		$cierre_caja = "UPDATE caja_aper SET Caj_Exi = '$Par_Sql[0]', Caj_Est = '$Par_Sql[1]', Caj_Obs = '$Par_Sql[2]', Caj_Hof ='$Par_Sql[4]', Caj_Fef = '$Par_Sql[5]'  WHERE Caj_Cod = '$Par_Sql[3]'";
		return $cierre_caja;
		break;
		
		case 8:
		/* Consulta de la apertura de caja por la fecha*/
		$consultar_nomcompr_8 = "SELECT  Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Fef, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Exi, caja_aper.Caj_Obs, caja_aper.Pun_Cod,  IF (caja_aper.Caj_Est='A','Abierta','Cerrada') as Caj_Est FROM caja_aper WHERE caja_aper.Caj_Fec = '$Par_Sql[0]' AND caja_aper.Pun_Cod = $Par_Sql[1]";
		//echo $consultar_nomcompr_8;
		return $consultar_nomcompr_8;
		break;
				
		case 9: 
		/* Consulta de los datos de la apertura de caja*/
 		$consulta_caja = "SELECT caja_aper.Caj_Fec, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Est, caja_aper.Caja_Exi, caja_aper.Caj_Obs, caja_aper.Pun_Cod FROM caja_aper WHERE caja_aper.Caj_Fec = '$Par_Sql[0]'";;
		return $consulta_caja;
		break;
		
/***************************************************************************************************************************************TIPO DE PAGO*******************************************/

		case 10:
		/* Insertar tipo de pago */
		$ins_tipopagos = "INSERT INTO tipos_pago (For_Cod, Pag_Des) VALUES ($Par_Sql[0], '$Par_Sql[1]')";
		return $ins_tipopagos;
		break;
		
		case 11: /* se utiliza para registrar y para modificar la forma de pago*/
		/*Consulta una tipo de pago en base a un parametro*/
		$cons_tipopagos = "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE Pag_Des like '$Par_Sql[0]%'";
		return $cons_tipopagos;
		break;
		
		case 19: 
		/*Consulta para verificar si esta o no el tipo de pago*/
		$cons_tipopagos = "SELECT Pag_Des FROM tipos_pago WHERE For_Cod = $Par_Sql[0] AND Pag_Des = '$Par_Sql[1]'";
		return $cons_tipopagos;
		break;
		
		case 12: 
		/*Consulta la maxima fecha*/
		$cajafecha = "SELECT adddate(max(Caj_Fec), INTERVAL 1 DAY) as fecha FROM caja_aper WHERE Caj_Est = 'C'";
		return $cajafecha;
		break;
		
		case 13: 
		/*Consulta un tipo de pago en base al codigo*/
		$cons_tipopagos2 = "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE Pag_Cod=$Par_Sql[0]";
		return $cons_tipopagos2;
		break;

		case 14: 
		/* Modifica o actualiza los cambios realizados en la tabla tipo de pago */
 		$modificartipopago_form = "UPDATE tipos_pago SET Pag_Des ='$Par_Sql[0]' WHERE Pag_Cod =        $Par_Sql[1]";
		return $modificartipopago_form;
		break;
		
		/***************************************************************************************************************************************APERTURA DE CAJA*******************************************/

		case 15: 
		/* Verifica si la fecha que se va a guardar ya existe en la base de datos */
 		$consultar_fecha = "SELECT Caj_Fec FROM caja_aper WHERE Caj_Fec = '$Par_Sql[0]' AND Pun_Cod = '$Par_Sql[1]'";
		return $consultar_fecha;
		break;
		
		
		/**********************TESORERIA********************************/
/****************************************************************************************************************************FACTURACION********************************/

		case 16: 
		/* Consulta la forma de pago */
 		$consultar_pago_16 = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
		return $consultar_pago_16;
		break;

        case 17: 
		/* Consulta el tipo de pago en base a la forma de pago */
 		$consultar_factpago = "SELECT Pag_Cod, Pag_Des FROM tipos_pago WHERE tipos_pago.For_Cod = $Par_Sql[0] AND tipos_pago.Pag_Est = 'A'";//ORDER BY Pag_Des
		return $consultar_factpago;
		break;
		
		case 18: 
		/* Consulta del banco*/
 		$consultar_pago = "SELECT Bak_Cod, Bak_Des FROM bancos WHERE Bak_Est = 'A' ORDER BY Bak_Des ASC";
		return $consultar_pago;
		break;
		
		case 20: 
		/* insertar datos de la factura*/
 		$inser_factpago_20 = "INSERT INTO ventas (Tic_Cod, Cli_Cod, Ciu_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Obs, Aut_Cod, Vet_Des, Vet_Hor) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4], '$Par_Sql[5]', '$Par_Sql[6]', $Par_Sql[7], '$Par_Sql[8]', '$Par_Sql[9]')";
		//echo  $inser_factpago_20;
		return $inser_factpago_20;
		break;

       	case 21:
		/* Consulta del cliente si es una persona por apellidos */
		$consultar_buscar_21 = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, 
        persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'  
		ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
		//echo $consultar_buscar_21;
		return $consultar_buscar_21;
		break;

		case 22:
		/* Consulta del personal por cedula */
 		$consultar_cliente1 = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape,        persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced = '$Par_Sql[0]' ORDER BY	persona.Prs_Ape, persona.Prs_Nom ASC";
		return $consultar_cliente1;
		break;
		
		case 23:
		/* Consulta de los datos del cliente */
		$consultar_cliente = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
						persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod
						FROM cliente, persona WHERE persona.Prs_Cod = cliente.Prs_Cod AND cliente.Cli_Cod = '$Par_Sql[0]'";
		return $consultar_cliente;
		break;
		
		case 24:
		/*Consulta del vendedor en base al codigo de la persona*/
		$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
							vendedor.Prs_Cod = $Par_Sql[0]";
		//echo $consultar_vendedor;
		return $consultar_vendedor;
		break;
		
		case 25:
		/* Consulta de la caja activa en base al vendedor */
		$consultar_caja_25 = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod, Pun_Des FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND
						caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
						//echo $consultar_caja_25;
		return $consultar_caja_25;
		break;
		
		case 26:
		/*Consulta del codigo de la persona en base al usuario*/
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
		
		case 28:
		/*Consulta de los rubros sin precio*/ 
  		$productos_28 = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, producto.Iva_Cod, Iva_Por, Pre_Pvp
					FROM producto, item, iva, precios       
					WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Iva_Cod = iva.Iva_Cod AND 
					item.Ite_Lar LIKE '%$Par_Sql[0]%' AND producto.Pro_Est = 'A' AND producto.Pro_Cod = precios.Pro_Cod AND
					producto.Pro_Cod NOT IN (SELECT  deudas.Pro_Cod FROM  deudas, notasgener WHERE deudas.Nge_Cod = 
					notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] AND notasgener.Sem_Cod = $Par_Sql[2])";
					//echo $productos_28;
		return $productos_28;
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
		$consultar_aut_30 = "SELECT autorizaci.Aut_Cod, autorizaci.Aut_Cad, autorizaci.Aut_Sri, autorizaci.Aut_Ini, autorizaci.Aut_Fin FROM autorizaci WHERE autorizaci.Tic_Cod = $Par_Sql[0] 	
			AND autorizaci.Pun_Cod = '$Par_Sql[1]'
			AND autorizaci.Aut_Cad >= '$Par_Sql[2]' AND autorizaci.Aut_Est = 'A'";
		//echo $consultar_aut_30;
		return $consultar_aut_30;
		break;


		case 31:
		/*Consulta el numero de factura en base al codigo interno */
		$consultar_num = "SELECT Vet_Num FROM ventas WHERE Vet_Cod = $Par_Sql[0]";
		return $consultar_num;
		break;
		
		case 32:
		/*Consulta codigo del punto de impresion y de la sucursal, otorgado por SRI*/
		$consultar_pun = "SELECT puntos_imp.Pun_Sri, sucursal.Suc_Sri FROM puntos_imp, sucursal WHERE puntos_imp.Suc_Cod = 
						sucursal.Suc_Cod AND puntos_imp.Pun_Cod = $Par_Sql[0]";
		return $consultar_pun;
		break;
		
		case 33:
		/*Consulta del representate del cliente cuando se trata de un estudiante*/
		$consulta_rep = "SELECT estudiante.Est_Fac, Est_Ruf, Est_Rep, Est_Dir FROM estudiante WHERE estudiante.Prs_Cod = $Par_Sql[0]";
		//echo $consulta_rep;
		return $consulta_rep;
		break;
		
		//case 34: 
		/* actualizar datos de la factura*/
 		//$up_factpago_34 = "UPDATE ventas SET Bak_Cod = $Par_Sql[0], Pag_Cod = $Par_Sql[1], Vet_Num = '$Par_Sql[2]', Vet_Cue = '$Par_Sql[3]', Vet_Che = '$Par_Sql[4]', Vet_Obs = '$Par_Sql[5]', Vet_Des = '$Par_Sql[6]', Ban_Cod = $Par_Sql[8] WHERE Vet_Cod = $Par_Sql[7]"; 
		//echo $up_factpago_34;
		//return $up_factpago_34;
		//break;

		case 34: 
		/* actualizar datos de la factura*/
 		$up_factpago_34 = "UPDATE ventas SET Vet_Num = '$Par_Sql[0]', Vet_Obs = '$Par_Sql[1]', Vet_Des = '$Par_Sql[2]' WHERE Vet_Cod = $Par_Sql[3]"; 
		//echo $up_factpago_34;
		return $up_factpago_34;
		break;

		
		case 35:
		/* Consulta del cliente de la factura por apellidos */
		$consultar_cli_factura_35 = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est, puntos_imp.Pun_Des FROM persona, cliente, ventas, caja_aper, puntos_imp  WHERE cliente.Prs_Cod = persona.Prs_Cod 
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND ventas.Cli_Cod = cliente.Cli_Cod
        AND YEAR(Caj_Fec) = '$Par_Sql[2]' $Par_Sql[3] 
        AND ventas.Tic_Cod = $Par_Sql[1] $Par_Sql[4]
		ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";//AND caja_aper.Pun_Cod = $Par_Sql[4]
		//echo $consultar_cli_factura_35;
		return $consultar_cli_factura_35;
		break;
		
		case 36:
		/* Consulta del personal por cedula */
 		$consultar_Num_fact_cliente_36 = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est, puntos_imp.Pun_Des FROM persona, cliente, ventas, caja_aper, puntos_imp  WHERE cliente.Prs_Cod = persona.Prs_Cod 
 		AND cliente.Cli_Cod = ventas.Cli_Cod AND ventas.Tic_Cod = $Par_Sql[1]
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND  ventas.Vet_Num = '$Par_Sql[0]%' $Par_Sql[2]
		ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";//AND caja_aper.Pun_Cod = $Par_Sql[2]
		//echo $consultar_Num_fact_cliente_36;
		return $consultar_Num_fact_cliente_36;
		break;
		
		case 37:
		/* Consulta de los datos del cliente */
		$consultar_cli_fac_37 = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, persona.Prs_Tel, 	
		persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, ventas.Aut_Cod, ventas.Tic_Cod, ventas.Vet_Obs, caja_aper.Caj_Fec, ciudad.Ciu_Des, ventas.Vet_Des, caja_aper.Pun_Cod, ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, 		item.Ite_Cor, item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, ventas_det.Iva_Cod, ventas_det.Pro_Cod, ventas.Vet_Est, 	
		producto.Pro_Ide, Nge_Cod, Asi_Int, Vet_Rec 
		FROM cliente, persona, ventas, caja_aper, ciudad, ventas_det, item, iva, producto 
		WHERE cliente.Cli_Cod = ventas.Cli_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Ciu_Cod = ciudad.Ciu_Cod AND ventas.Vet_Cod = 	
		ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND 	
		persona.Prs_Cod = cliente.Prs_Cod AND ventas.Vet_Cod = '$Par_Sql[0]' AND Vet_Rec = 0";//         AND iva.Iva_Cod = producto.Iva_Cod
		//echo $consultar_cli_fac_37;
		return $consultar_cli_fac_37;
		break;		
		
		/* actualizar datos del detalle de la factura*/
		case 38: 
 		$ins_rubrosfact_38 = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6], Nge_Cod=$Par_Sql[7], Asi_Int=$Par_Sql[8], Vet_Rec = $Par_Sql[9]";
		return $ins_rubrosfact_38;
		break;	
		
		/* actualizar datos del detalle de la factura*/
		case 38: 
 		$ins_rubrosfact = "INSERT INTO ventas_det SET  Vet_Can=$Par_Sql[0], Iva_Cod=$Par_Sql[1], Vet_Pru=$Par_Sql[2], Vet_Imp=$Par_Sql[3], Vet_Dec='$Par_Sql[4]', Vet_Cod = $Par_Sql[5], Pro_Cod=$Par_Sql[6]";
		return $ins_rubrosfact;
		break;	
		
		case 39:
		/* Consulta de los valores de las facturas para realizar calculos */
		$calcular_fac = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Cod, 
						ventas.Vet_Des FROM ventas, ventas_det, iva WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = 
						iva.Iva_Cod AND ventas.Vet_Cod = '$Par_Sql[0]'";
		return $calcular_fac;
		break;
		
		case 40:
		/* insercion del precio del rubro en la tabla precio REALIZAZA EL 16-01-2007*/
		$precio_ins = "INSERT INTO precios SET Pro_Cod=$Par_Sql[0], Pre_Pvp='$Par_Sql[1]', Pre_Des='$Par_Sql[2]'";
		return $precio_ins;
		break;
		
		case 41: 
		/* actualizar datos del detalle de la factura 17-01-2007*/
 		$ins_precios = "INSERT INTO precios SET  Pro_Cod='$Par_Sql[0]', Suc_Cod='$Par_Sql[1]', Pre_Pvp='$Par_Sql[2]', Pre_Des='$Par_Sql[3]'";
		return $ins_precios;
		break;
		
		case 42: 
		/* consultar los datos del cliente-escuela matriculado en la UTSAM 18-01-2007*/
 		$con_cliente_esc = "SELECT cliente.Cli_Cod, persona.Prs_Ape, carreras.Car_Nom, semestres.Sem_Par,
        persona.Prs_Nom, niveles.Niv_Des,  
        IF (semestres.Sem_Sec='D', 'Diurna', IF (semestres.Sem_Sec='V', 'Vespertina', IF (semestres.Sem_Sec='N', 'Nocturna', ' '))) as Sem_Sec, escuelas.Esc_Nom, modalidad.Mod_Des,
        IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, carreras.Car_Nom FROM persona, cliente, estudiante, niveles, matriculas, 
        semestres, escuelas, promocione, carreras, modalidad, periodos
        WHERE cliente.Prs_Cod = persona.Prs_Cod AND carreras.Esc_Int = escuelas.Esc_Int
        AND promocione.Car_Int = carreras.Car_Int AND semestres.Pro_Cod = promocione.Pro_Cod
 		AND estudiante.Prs_Cod = persona.Prs_Cod AND matriculas.Est_Int = estudiante.Est_Int
 		AND matriculas.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod
 		AND modalidad.Mod_Cod = periodos.Mod_Cod AND periodos.Per_Int = semestres.Per_Int AND persona.Prs_Ced = '$Par_Sql[0]'
		AND semestres.Per_Int = $Par_Sql[1]";
		return $con_cliente_esc;
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
		case 45:
		$num_fac="SELECT MAX(Pro_Ide) AS maximo FROM producto WHERE SUBSTRING(Pro_Ide, 1,1) = '$Par_Sql[0]'";
		return $num_fac;
		break;
		
	   /** Selecionar el numero maximo del codigo del producto**/
		case 46:
		$Pro_conf="SELECT Pro_Ide, Col_Eli, Col_Cad FROM confi_teso WHERE Con_Cod = 1";
		return $Pro_conf;
        break;
		
        /** Selecionar el Pro_ide en base a un parametro**/
		case 47:
		$codigo_ver="SELECT Pro_Ide FROM producto WHERE Pro_Ide ='$Par_Sql[0]' AND Pro_Cod !=$Par_Sql[1]";
        return $codigo_ver;
        break;
		
		case 48:
		/* Consultar  datos de Aper_Caja  mediante la fecha */
		$consultar_caja = "SELECT  caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Fef, caja_aper.Caj_Hoi, 
caja_aper.Caj_Hof, caja_aper.Caj_Exi, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est='A','Abierta','Cerrada') as Caj_Est FROM caja_aper WHERE caja_aper.Caj_Fec = '$Par_Sql[0]'";
		return $consultar_caja;
		break;
		
		case 49:
		/* Consulta datos de Aper Caja*/
		$consultar_cajcod = "SELECT  caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Fef, caja_aper.Caj_Hoi, 
caja_aper.Caj_Hof, caja_aper.Caj_Exi, caja_aper.Caj_Obs, caja_aper.Pun_Cod, Caj_Est
 FROM caja_aper WHERE caja_aper.Caj_Cod = '$Par_Sql[0]'";
		return $consultar_cajcod;
		break;

		case 50:
		/* Apertura de la caja diaria */
		$cierre_caja = "UPDATE caja_aper SET Caj_Exi = '$Par_Sql[0]', Caj_Obs = '$Par_Sql[1]' WHERE Caj_Cod = '$Par_Sql[2]'";
		return $cierre_caja;
		break;

		case 51:
		/* Verifica si a un producto se le debe calcular el interes */
		$produc_interes_51 = "SELECT prod_inter.Pro_Cod FROM prod_inter WHERE Pro_Cod = $Par_Sql[0]";
		//echo $produc_interes_51;
		return $produc_interes_51;
		break;

		case 52:
		/*Consulta de los rubros */ 
  		$productos_52 = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, producto.Iva_Cod, Iva_Por,
					Pre_Pvp
					FROM producto, item, iva , precios       
					WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Iva_Cod = iva.Iva_Cod AND producto.Pro_Cod = 
					precios.Pro_Cod AND 
					item.Ite_Lar LIKE '%$Par_Sql[0]%' AND producto.Pro_Est = 'A'";
					//echo $productos_52;
		return $productos_52;
		break;


		/* Busca el codigo del item para verificar si ya existe */
		case 53:
 		$buscar_deuda= "SELECT  deudas.Pro_Cod FROM  deudas WHERE deudas.Cli_Cod = '$Par_Sql[0]' AND deudas.Pro_Cod =    '$Par_Sql[1]' AND deudas.Nge_Cod = $Par_Sql[2]";
		//echo $buscar_deuda;
		return $buscar_deuda;
		break; 
		

		/* Consulta la cantidad de dias de retrazo de la deuda */
		case 54:
		$mora_deuda_54= "SELECT datediff(Deu_Fec, now()) as Mora, Deu_Fec FROM deudas WHERE Cli_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1] AND Nge_Cod = $Par_Sql[2] 
						AND Asi_Int = $Par_Sql[3] AND Deu_Rec = 0";		
				//echo $mora_deuda_54;		
		return $mora_deuda_54;
		break;


	    /* Consulta de las deudas de los clientes */
		case 55:
		/*$consulta_deuda_55 = "SELECT deudas.Pro_Cod, Pro_Ide, item.Ite_Lar, Deu_Val, Deu_Fec, niveles.Niv_Des, semestres.Sem_Cod, 
					semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as 
					Sem_Sec, modalidad.Mod_Des, Car_Nom, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', IF 
					(MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF 
					(MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',IF (MONTH(Per_Fea)=7,'Julio', IF 
					(MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,'Octubre', 
					IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,IF 
					(MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (MONTH(Per_Fef)=3, 
					'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio', 
					IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', 
					IF (MONTH(Per_Fef)=10, 'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin,
					Asi_Int, deudas.Nge_Cod, Deu_Obs, carreras.Car_Int, Iva_Por, producto.Iva_Cod, Bec_Cod, Deu_Rec  
					FROM deudas, producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras, iva
					WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND deudas.Nge_Cod =
					notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Per_Int = periodos.Per_Int
					AND semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND
					semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND
					producto.Iva_Cod = iva.Iva_Cod AND deudas.Cli_Cod = '$Par_Sql[0]' AND Deu_Rec = 0 ORDER BY deudas.Deu_Fec"; */
		$consulta_deuda_55 = "SELECT deudas.Pro_Cod, producto.Pro_Ide, item.Ite_Lar, deudas.Deu_Val, deudas.Deu_Fec, view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom,
							  view_cursos_mal.Sem_No2, view_cursos_mal.Car_Int, view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin,
							  view_periodos_suc.Mes_Fin, modalidad.Mod_Des, carreras.Car_Nom, deudas.Asi_Int, deudas.Nge_Cod, deudas.Deu_Obs, producto.Iva_Cod,
							  iva.Iva_Por, deudas.Bec_Cod, deudas.Deu_Rec FROM producto INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod) INNER JOIN item 
							  ON (producto.Ite_Cod = item.Ite_Cod) INNER JOIN notasgener ON (deudas.Nge_Cod = notasgener.Nge_Cod) INNER JOIN view_cursos_mal ON 
							  (notasgener.Sem_Cod = view_cursos_mal.Sem_Cod) INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int)
							  INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = modalidad.Mod_Cod) INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
							  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) WHERE deudas.Cli_Cod = '$Par_Sql[0]' AND Deu_Rec = 0 GROUP BY deudas.Pro_Cod, producto.Pro_Ide,
							  item.Ite_Lar, deudas.Deu_Val, deudas.Deu_Fec, view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2, view_cursos_mal.Car_Int,
							  view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin, modalidad.Mod_Des, carreras.Car_Nom,
							  deudas.Asi_Int, deudas.Nge_Cod, deudas.Deu_Obs, producto.Iva_Cod, iva.Iva_Por, deudas.Bec_Cod, deudas.Deu_Rec ORDER BY deudas.Deu_Fec";
					//echo $consulta_deuda_55;
		return $consulta_deuda_55;
		break;
		
		/* Consultar los rubros destinados para el interes */
		case 56:
		$consul_interes_56= "SELECT Pro_Cod, Int_Por, Int_Dia FROM interes";
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
		
		/* cargar semestre en base al periodo y al cliente */
		case 60:
		$carrera_est= "SELECT DISTINCT 
  view_cursos_mal.Per_Int,
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin,
  view_periodos_suc.Per_Fea,
  view_periodos_suc.Per_Fef,
  modalidad.Mod_Des,
  carreras.Car_Nom,
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  view_periodos_suc.Suc_Des,
  etapas.Eta_Des,
  view_cursos_mal.Sem_Cod
FROM
  persona
  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
  INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
  INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
  INNER JOIN view_cursos_mal ON (matriculas.Sem_Cod = view_cursos_mal.Sem_Cod)
  INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
  INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int)
  INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = modalidad.Mod_Cod)
  INNER JOIN etapas ON (carreras.Eta_Cod = etapas.Eta_Cod)
WHERE
  '$Par_Sql[0]' BETWEEN Pem_Ini AND Per_Fec AND 
  cliente.Cli_Cod = $Par_Sql[1] AND 
  view_cursos_mal.Car_Int = $Par_Sql[2] AND 
  matriculas.Mat_Est = 'A' ORDER BY view_periodos_suc.Per_Fea DESC";
        //echo $carrera_est;
		return $carrera_est;
		break;

						
		/* cargar asignatura en base al semestre y al codigo del cliente */
		case 61:
		$carrera_asig= "SELECT notasgedet.Nge_Cod, notasgedet.Asi_Int, asignatura.Asi_Des FROM notasgedet, notasgener, matriculas, estudiante, persona, cliente, asignatura WHERE notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND estudiante.Est_Int = matriculas.Est_Int AND estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND notasgedet.Asi_Int = asignatura.Asi_Int AND cliente.Cli_Cod = '$Par_Sql[0]' AND notasgener.Sem_Cod = '$Par_Sql[1]'";
		return $carrera_asig;
		break;
		
		case 62:
		/*Consulta de los rubros sin precio*/ 
  		$productos_62 = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar FROM producto, item        
					WHERE producto.Ite_Cod = item.Ite_Cod AND item.Ite_Lar LIKE '%$Par_Sql[0]%' AND producto.Pro_Est = 'A' AND 
					producto.Pro_Cod NOT IN (SELECT  deudas.Pro_Cod FROM  deudas, notasgener WHERE deudas.Nge_Cod = 
					notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] AND notasgener.Sem_Cod = $Par_Sql[2])";
		//echo $productos_62;
		return $productos_62;
		break;
		
		/* cargar semestre y la notageneral en base al periodo y al cliente */
		/* Esta funcion toma el primer dia de matriculas ordinarias hasta el fin de clases */
		case 63:
		/*$nota_63 ="SELECT periodos.Per_Int, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', IF (MONTH(Per_Fea)=2, 
'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 'Mayo', 
IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF 
(MONTH(Per_Fea)=9, 'Septiembre', IF(MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 
'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin, IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 
'Febrero', IF ( MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', 
IF(MONTH(Per_Fef)=6, 'Junio', IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF 
(MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10, 'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 
'Diciembre'))))))))))) as Mes_Fin, niveles.Niv_Des, semestres.Sem_Cod, Sem_Par, IF (Sem_Sec = 'D', 
'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des, Car_Nom, Per_Fea,
 Per_Fef, notasgener.Nge_Cod FROM periodos, semestres, matriculas, estudiante, persona, cliente, niveles, modalidad, promocione, 
 notasgener,
 carreras, perio_matr WHERE periodos.Per_Int = perio_matr.Per_Int AND periodos.Per_Int = semestres.Per_Int AND matriculas.Sem_Cod = semestres.Sem_Cod AND 
 matriculas.Est_Int = estudiante.Est_Int AND estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Cod = 
 cliente.Prs_Cod AND semestres.Niv_Cod = niveles.Niv_Cod AND modalidad.Mod_Cod = periodos.Mod_Cod AND 
 semestres.Pro_Cod = promocione.Pro_Cod AND semestres.Sem_Cod = notasgener.Sem_Cod AND matriculas.Mat_Int = 
 notasgener.Mat_Int AND promocione.Car_Int = carreras.Car_Int AND ('$Par_Sql[0]' 
 BETWEEN Pem_Ini AND Per_Fec) AND cliente.Cli_Cod = $Par_Sql[1]  AND promocione.Car_Int = $Par_Sql[2] AND matriculas.Mat_Est = 'A'"; */
		$nota_63 = "SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2, persona.Prs_Nom, carreras.Car_Nom, modalidad.Mod_Des,
					view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin, view_periodos_suc.Per_Fea,
					view_periodos_suc.Per_Fef, notasgener.Nge_Cod FROM estudiante INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
					INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod) INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
					INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int) INNER JOIN view_cursos_mal ON (view_cursos_mal.Sem_Cod = 
					notasgener.Sem_Cod) AND (view_cursos_mal.Sem_Cod = matriculas.Sem_Cod) INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
					INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int) INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = 
					modalidad.Mod_Cod) WHERE matriculas.Mat_Est = 'A' AND ('$Par_Sql[0]' BETWEEN 
					Pem_Ini AND Per_Fec) AND cliente.Cli_Cod = $Par_Sql[1] AND view_cursos_mal.Car_Int = $Par_Sql[2] GROUP BY view_cursos_mal.Sem_Cod ORDER BY view_periodos_suc.Per_Fea DESC";
		return $nota_63;
		break;
		
		/* cargar asignatura en base al semestre y al codigo del cliente */
		case 64:
		$actualiza_interes_64= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Reg = '$Par_Sql[1]' WHERE Nge_Cod = $Par_Sql[2] AND 
					Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6]";
					//echo $actualiza_interes_64;
		return $actualiza_interes_64;
		break;
				
		/* Registra deudas de los clientes */
		case 65:
		$registra_deuda_65= "INSERT INTO deudas (Pro_Cod, Nge_Cod, Cli_Cod, Deu_Val, Deu_Reg, Deu_Fec, Deu_Obs) VALUES 
					($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]')";
					//echo $registra_deuda_65;
		return $registra_deuda_65;
		break;
		
		/* cargar semestre en base a la carrera y al periodo */
		case 66:
		$carrera_sem= "SELECT carreras.Car_Nom, niveles.Niv_Des, semestres.Sem_Cod, niveles.Niv_Des, Sem_Par, 
IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, modalidad.Mod_Des FROM semestres, niveles, modalidad, carreras, periodos, promocione WHERE promocione.Pro_Cod = semestres.Pro_Cod AND niveles.Niv_Cod = semestres.Niv_Cod  AND modalidad.Mod_Cod = periodos.Mod_Cod AND promocione.Car_Int = carreras.Car_Int  AND semestres.Per_Int = periodos.Per_Int 
AND periodos.Per_Int = '$Par_Sql[0]' AND carreras.Car_Int = '$Par_Sql[1]' ORDER BY semestres.Niv_Cod";
		return $carrera_sem;
		break;
		
		/* cargar asignatura en base al semestre y al codigo del cliente */
		case 67:
		$carrera_asig= "SELECT notasgedet.Nge_Cod, notasgedet.Asi_Int, asignatura.Asi_Des FROM notasgedet, notasgener, matriculas, estudiante, persona, cliente, asignatura WHERE notasgener.Nge_Cod = notasgedet.Nge_Cod AND notasgener.Mat_Int = matriculas.Mat_Int AND estudiante.Est_Int = matriculas.Est_Int AND estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND notasgedet.Asi_Int = asignatura.Asi_Int AND notasgener.Sem_Cod = '$Par_Sql[0]'";
		return $carrera_asig;
		break;
		
		
		/* Consulta los pagos realizados por el cliente */
		case 68:
		$pagos_68= "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
				ventas_det.Vet_Cod AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
				= $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND ventas.Vet_Est = 'A'";
				//echo $pagos_68;
		return $pagos_68;
		break;
		
		/* Consulta los pagos realizados por el cliente para rubros RECURSIVOS */
		case 69:
		$pagos_69= "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
				ventas_det.Vet_Cod AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
				= $Par_Sql[2] AND ventas_det.Vet_Rec = '$Par_Sql[3]' AND Asi_Int = '$Par_Sql[4]' AND ventas.Vet_Est = 'A'";
				//echo $pagos_69;
		return $pagos_69;
		break;
		
		///* Carga la deuda de los estudiantes en base al semestre y a la matricula activa */
		case 70:
		$estudiante_sem= "SELECT  carreras.Car_Nom, niveles.Niv_Des, notasgener.Sem_Cod, semestres.Sem_Par,
IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, 
modalidad.Mod_Des, persona.Prs_Ape, 
persona.Prs_Nom, persona.Prs_Ced, deudas.Deu_Cod, deudas.Pro_Cod, deudas.Cli_Cod,  
deudas.Deu_Fec, deudas.Deu_Sal, item.Ite_Lar, 
producto.Pro_Ide, deudas.Nge_Cod, deudas.Asi_Int,
semestres.Sem_Cod, cliente.Cli_Cod,
YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', 
IF (MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',IF (MONTH(Per_Fea)=7,'Julio', 
IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,'Octubre', 
IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,
IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (MONTH(Per_Fef)=3, 'Marzo', 
IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio', 
IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', 
IF (MONTH(Per_Fef)=10, 'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) 
as Mes_Fin FROM semestres, niveles, notasgener, periodos, estudiante, persona, cliente, matriculas, carreras, 
deudas, promocione, item, producto, modalidad
WHERE estudiante.Prs_Cod = persona.Prs_Cod 
AND persona.Prs_Cod = cliente.Prs_Cod 
AND item.Ite_Cod = producto.Ite_Cod 
AND estudiante.Est_Int = matriculas.Est_Int 
AND deudas.Cli_Cod = cliente.Cli_Cod 
AND semestres.Sem_Cod = notasgener.Sem_Cod 
AND periodos.Mod_Cod = modalidad.Mod_Cod 
AND niveles.Niv_Cod = semestres.Niv_Cod 
AND periodos.Per_Int = semestres.Per_Int 
AND notasgener.Mat_Int = matriculas.Mat_Int
AND semestres.Pro_Cod = promocione.Pro_Cod 
AND promocione.Car_Int = carreras.Car_Int
AND deudas.Nge_Cod = notasgener.Nge_Cod 
AND deudas.Pro_Cod = producto.Pro_Cod 
AND matriculas.Mat_Est = 'A'
AND notasgener.Sem_Cod= '$Par_Sql[0]' 
AND deudas.Deu_Sal > 0 
GROUP BY persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced";
        //echo $estudiante_sem;
        return $estudiante_sem;
	    break;
	   
/*/*DEUDAS DEL CLIENTE DEPENDIENDO DEL CLIENTE*/ 
		case 71:
		$deuda_est= "SELECT  deudas.Deu_Sal FROM deudas WHERE deudas.Cli_Cod = '$Par_Sql[0]' AND deudas.Pro_Cod = '$Par_Sql[1]' AND deudas.Deu_Sal > 0";
	   return $deuda_est;
	   break;
	   	   
	   /*/*DEUDAS DEL CLIENTE DEPENDIENDO DEL CLIENTE*/ 
		case 72:
		$rubros_deudas= "SELECT  deudas.Pro_Cod, item.Ite_Cor, deudas.Pro_Cod FROM notasgener, producto, item, deudas WHERE deudas.Pro_Cod = producto.Pro_Cod AND notasgener.Nge_Cod = deudas.Nge_Cod AND item.Ite_Cod = producto.Ite_Cod
AND notasgener.Sem_Cod= '$Par_Sql[0]' GROUP BY producto.Pro_Cod";
	   return $rubros_deudas;
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
		/* Consulta de los datos de los rubros recursivos */
		$consultar_cli_fac_74 = "SELECT ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec,
         item.Ite_Cor, item.Ite_Lar, Ite_Cor, iva.Iva_Por, ventas.Vet_Cod, ventas_det.Iva_Cod, ventas_det.Pro_Cod, producto.Pro_Ide, Nge_Cod, Asi_Int, Vet_Rec
         FROM  ventas, ventas_det, item, iva, producto WHERE ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod
         AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas.Vet_Cod = '$Par_Sql[0]'
		 AND Nge_Cod = $Par_Sql[1] AND Asi_Int = $Par_Sql[2] AND Vet_Rec = $Par_Sql[3]";
		 //echo $consultar_cli_fac_74;
		return $consultar_cli_fac_74;
		break;	   
	   
	    /*  Cargar el Nge_Cod dependiendo del semestre*/ 
		case 75:
		$Nge_Cod_75= "SELECT notasgener.Nge_Cod FROM matriculas, semestres, persona, cliente, estudiante, notasgener WHERE 
				matriculas.Sem_Cod = semestres.Sem_Cod AND matriculas.Est_Int = estudiante.Est_Int AND persona.Prs_Cod = 
				estudiante.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND 
				matriculas.Mat_Int = notasgener.Mat_Int AND cliente.Cli_Cod = $Par_Sql[0]
				AND semestres.Sem_Cod = $Par_Sql[1]";
				//echo $Nge_Cod_75;
	   return $Nge_Cod_75;
	   break;

 		/*  Consulta la beca asignada a un rubro */ 
	   case 76:
	   $beca_asignada_76= "SELECT Bec_Pot, Bec_Por, tipo_beca.Tib_Ini, tipo_beca.Tib_Cod, Mat_Int, tipo_beca.Tib_Des FROM becas, det_becas, tipo_beca WHERE becas.Bec_Cod = det_becas.Bec_Cod 
	   						AND becas.Tib_Cod = tipo_beca.Tib_Cod AND becas.Bec_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1]";
							//echo $beca_asignada_76;
	   return $beca_asignada_76;
	   break;
	   
	      /*  Seleccionar el saldo*/ 
		case 77:
		$selecciona_saldo= "SELECT saldo_favor.Saf_Val, saldo_favor.Saf_Tip, saldo_favor.Pro_Cod, saldo_favor.Vet_Cod 
FROM ventas, saldo_favor
WHERE ventas.Vet_Cod = saldo_favor.Vet_Cod 
AND ventas.Cli_Cod = '$Par_Sql[0]'
AND saldo_favor.Pro_Cod = '$Par_Sql[1]'
AND saldo_favor.Saf_Tip = 'R' AND saldo_favor.Saf_Val > 0"; //ojo  se debe considerar la deuda actual, si hay mas de dos saldos tomara el primero
      // echo $selecciona_saldo;
	   return $selecciona_saldo;
	   break;
	   
	   case 78:
		/*Consulta de las carreras las cuales no ha cursado un estudiante*/
		$rs_carreras_si = "SELECT carreras.Car_Int, Car_Nom FROM carreras, estudiante, matriculas, promocione, semestres, persona, cliente WHERE estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod 
AND semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND persona.Prs_Cod = estudiante.Prs_Cod 
AND cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = '$Par_Sql[0]' AND cliente.Cli_Est = 'A' GROUP BY Car_Int
ORDER BY carreras.Car_Nom";
		return $rs_carreras_si;
		break;
	   
	   	 case 79:
		/*cONSULTAR EL PERIODO E BASE A LA CARRERA*/
		$rs_periodocarrera = "SELECT periodos.Per_Int, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF(MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,	
IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin
FROM cliente, persona, estudiante, matriculas, semestres, periodos, promocione WHERE persona.Prs_Cod = cliente.Prs_Cod
AND estudiante.Prs_Cod = persona.Prs_Cod AND matriculas.Est_Int = estudiante.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod
AND periodos.Per_Int = semestres.Per_Int AND semestres.Pro_Cod = promocione.Pro_Cod 
AND matriculas.Mat_Est = 'A'
AND cliente.Cli_Cod =  '$Par_Sql[0]'
AND promocione.Car_Int =  '$Par_Sql[1]'";
       // echo $rs_periodocarrera;
		return $rs_periodocarrera;
		break;
//		
//	 /*  Actualizar el saldo */ 
//	   case 80:
//	   $actualizar_esta= "UPDATE saldo_favor SET Saf_Est = 'I'  WHERE saldo_favor.Vet_Cod = '$Par_Sql[0]' AND saldo_favor.Pro_Cod = '$Par_Sql[1]'";
//	   return $actualizar_esta;
//	   break;

		/* cargar asignatura en base al semestre y al codigo del cliente */
		case 80:
		$actualiza_deudas_80= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Fec = '$Par_Sql[1]', Deu_Obs = '$Par_Sql[7]' WHERE Nge_Cod = $Par_Sql[2] AND 
					Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6]";
					//echo $actualiza_deudas_80;
		return $actualiza_deudas_80;
		break;

	    /*  Concatenar el numero de factura */ 
	   case 81:
	   $Num_factura= "SELECT Pun_Sri, Suc_Sri FROM autorizaci, sucursal, puntos_imp
WHERE sucursal.Suc_Cod = puntos_imp.Suc_Cod AND autorizaci.Pun_Cod = puntos_imp.Pun_Cod
AND puntos_imp.Pun_Cod = $Par_Sql[0]";
	   return $Num_factura;
	   break;		
	   
	   case 82:
		/* Consulta de los datos de la factura dependiendo del No. de factura y del Pun_Cod */
		$consultar_cli_fac = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
		persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod, ventas.Vet_Obs, caja_aper.Caj_Fec, forma_pago.For_Des, ciudad.Ciu_Des, tipos_pago.Pag_Des, bancos.Bak_Des, ventas.Vet_Cue, ventas.Vet_Des, ventas.Vet_Che, caja_aper.Pun_Cod, ventas_det.Vet_Can, ventas_det.Vet_Pru, ventas_det.Vet_Imp, ventas_det.Vet_Dec,         item.Ite_Cor, item.Ite_Lar, iva.Iva_Por, ventas.Vet_Cod, ventas.Vet_Num, forma_pago.For_Cod, ventas.Pag_Cod, ventas.Bak_Cod, ventas_det.Iva_Cod, ventas_det.Pro_Cod, ventas.Vet_Est, producto.Pro_Ide FROM  cliente, persona, ventas, caja_aper, tipos_pago, bancos, forma_pago, ciudad, ventas_det, item, iva, producto WHERE cliente.Cli_Cod = ventas.Cli_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Pag_Cod = tipos_pago.Pag_Cod AND ventas.Bak_Cod = bancos.Bak_Cod AND forma_pago.For_Cod = tipos_pago.For_Cod AND ventas.Ciu_Cod = ciudad.Ciu_Cod AND ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND item.Ite_Cod = producto.Ite_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND  persona.Prs_Cod = cliente.Prs_Cod AND  ventas.Vet_Cod = '$Par_Sql[0]' AND caja_aper.Pun_Cod = '$Par_Sql[1]'";//         AND iva.Iva_Cod = producto.Iva_Cod
		return $consultar_cli_fac;
		break;
		
		
		case 83:
		/*Consulta de los cliente por punto de impresión y por apellido */
		$consulta_cli_puntonom ="SELECT ventas.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est 
FROM persona, cliente, ventas, caja_aper WHERE cliente.Prs_Cod = persona.Prs_Cod AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' 
AND ventas.Cli_Cod = cliente.Cli_Cod AND ventas.Tic_Cod = '$Par_Sql[1]' AND caja_aper.Pun_Cod = '$Par_Sql[2]' 
ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
		return $consulta_cli_puntonom;
		break;
		
			case 84:
		/* Consulta del personal por No. interno y por punto de impresion */
 		$consulta_cli_puntoced = "SELECT ventas.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper  WHERE cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND ventas.Tic_Cod = '$Par_Sql[0]' AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  ventas.Vet_Cod = '$Par_Sql[1]'
AND  caja_aper.Pun_Cod = '$Par_Sql[2]' ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
		return $consulta_cli_puntoced;
		break;
		
		case 85:
		/*Consulta del vendedor y el punto de impresion de la caja en base al codigo de la persona */
		$consultar_punto_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod
FROM vendedor WHERE vendedor.Vnd_Est = 'A' AND vendedor.Prs_Cod = '$Par_Sql[0]'";
		return $consultar_punto_vendedor;
		break;
		
		case 86:
		/* Consulta los totales de las facturas agrupados por rubros y dependiendo del Pun_Cod */
		$fac_rubros_punto = "SELECT caja_aper.Caj_Fec, item.Ite_Lar, SUM(IF(ventas_det.Vet_Dec > 0, ventas_det.Vet_Imp-(ventas_det.Vet_Imp*ventas_det.Vet_Dec) /100, IF(ventas.Vet_Des > 0, ventas_det.Vet_Imp-(ventas_det.Vet_Imp*ventas.Vet_Des)/100, ventas_det.Vet_Imp))) as Vet_Imp FROM caja_aper, ventas, ventas_det, producto, item WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'  AND ventas.Vet_Est = '$Par_Sql[2]' AND caja_aper.Pun_Cod = '$Par_Sql[3]' AND ventas.Tic_Cod = $Par_Sql[4]
		GROUP BY caja_aper.Caj_Fec, item.Ite_Lar";
		return $fac_rubros_punto;
		break;
		
				case 87: 
		/*Busca las facturas registradas de acuerdo a los intervalos de fecha y dependiendo del Pun_Cod*/
		$my_cons_puntosfact_87 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, tipos_pago.Pag_Des, persona.Prs_Nom, persona.Prs_Ape,  pago_venta.Vet_Tot, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est 
FROM caja_aper, ventas, ventas_det, tipos_pago, cliente, iva, persona, pago_venta WHERE ventas.Cli_Cod = cliente.Cli_Cod 
AND ventas.Caj_Cod = caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND ventas.Vet_Cod = pago_venta.Vet_Cod
AND pago_venta.Pag_Cod = tipos_pago.Pag_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod 
AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' AND caja_aper.Pun_Cod = '$Par_Sql[3]' AND ventas.Tic_Cod = $Par_Sql[4]";
//echo $my_cons_puntosfact_87;
		return $my_cons_puntosfact_87;
		break;
		
		case 88:
		/* Consulta los totales de las facturas agrupados por rubros por CARRERAS y dependiendo además de Pun_Cod*/
		$my_fac_rubros_PunCod_88 = "SELECT caja_aper.Caj_Fec, item.Ite_Lar, SUM(IF(ventas_det.Vet_Dec > 0, ventas_det.Vet_Imp-(ventas_det.Vet_Imp*ventas_det.Vet_Dec) /100, IF(ventas.Vet_Des > 0, ventas_det.Vet_Imp-(ventas_det.Vet_Imp*ventas.Vet_Des)/100, ventas_det.Vet_Imp))) as Vet_Imp, carreras.Car_Nom  FROM caja_aper, ventas, ventas_det, producto, item, cliente, persona, estudiante, matriculas, semestres, promocione, carreras, periodos WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND cliente.Prs_Cod = persona.Prs_Cod 
 AND estudiante.Prs_Cod = persona.Prs_Cod AND estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod  AND promocione.Pro_Cod = semestres.Pro_Cod AND promocione.Car_Int = carreras.Car_Int AND semestres.Per_Int = periodos.Per_Int 
 AND Caj_Fec  BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND promocione.Car_Int = '$Par_Sql[2]' AND ventas.Vet_Est = '$Par_Sql[3]' AND periodos.Pem_Tip = '$Par_Sql[4]' AND matriculas.Mat_Est = 'A' AND semestres.Per_Int = '$Par_Sql[5]' AND caja_aper.Pun_Cod = '$Par_Sql[6]' AND ventas.Tic_Cod = $Par_Sql[7]
 GROUP BY caja_aper.Caj_Fec, item.Ite_Lar"; //
/* En esta SQL se pone "AND matriculas.Mat_Est = 'A'" porque hay en este periodo un estudiante matriculado 2 veces, Cherrez*/
//echo $my_fac_rubros_PunCod_88;
		return $my_fac_rubros_PunCod_88;
		break;
		
		case 89: 
		/*Busca el total de facturas de acuerdo a la carrera y al Pun_Cod*/
		$my_cons_fact_escuela_PunCod_89 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, tipos_pago.Pag_Des, 
persona.Prs_Nom, persona.Prs_Ape, Car_Nom, caja_aper.Pun_Cod, pago_venta.Vet_Tot, 
ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est FROM caja_aper, ventas, ventas_det, tipos_pago,
cliente, iva, persona, carreras, estudiante, niveles, matriculas, semestres, promocione, modalidad, periodos, pago_venta 
WHERE ventas.Cli_Cod = cliente.Cli_Cod AND ventas.Caj_Cod = caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod 
AND pago_venta.Pag_Cod = tipos_pago.Pag_Cod 
AND ventas.Vet_Cod = pago_venta.Vet_Cod
AND ventas_det.Iva_Cod = iva.Iva_Cod AND cliente.Prs_Cod = persona.Prs_Cod 
AND estudiante.Prs_Cod = persona.Prs_Cod AND matriculas.Est_Int = estudiante.Est_Int
AND matriculas.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod 
AND modalidad.Mod_Cod = periodos.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod 
AND promocione.Car_Int = carreras.Car_Int AND semestres.Per_Int = periodos.Per_Int
AND ventas_det.Vet_Cod = ventas.Vet_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND carreras.Car_Int = '$Par_Sql[2]' AND ventas.Vet_Est = '$Par_Sql[3]' AND caja_aper.Pun_Cod = '$Par_Sql[5]' AND ventas.Tic_Cod ='$Par_Sql[4]'GROUP BY ventas.Vet_Cod";
	//	echo $my_cons_fact_escuela_PunCod_89;
		return $my_cons_fact_escuela_PunCod_89;
		break;
		
		
		case 90:
		/*Consulta los puntos de impresion */
		$consultar_punto_impre = "SELECT puntos_imp.Pun_Cod, puntos_imp.Pun_Des FROM puntos_imp WHERE puntos_imp.Pun_Est = 'A' ";
		return $consultar_punto_impre;
		break;
		
		case 91:
		/* Consulta del cliente de la factura por apellidos */
		$anular_fac = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, 
        IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est 
        FROM persona, cliente, ventas, caja_aper  
        WHERE cliente.Prs_Cod = persona.Prs_Cod 
		AND caja_aper.Caj_Cod = ventas.Caj_Cod 
		AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' 
		AND caja_aper.Caj_Fec = '$Par_Sql[1]' 
		AND ventas.Cli_Cod = cliente.Cli_Cod 
        AND ventas.Tic_Cod = '$Par_Sql[2]' 	
        AND caja_aper.Pun_Cod = '$Par_Sql[3]'
        ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
       	//echo $anular_fac;
		return $anular_fac; 
		break;
		
		case 92: 
		/* Consulta de las fechas de acuerdo a la caja activa para eliminar la factura*/
		$elim_facturaCaj = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod 
FROM caja_aper WHERE caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
		return $elim_facturaCaj;
		break;
		
		case 93:
		/* Consulta de la factura por el numero interno y dependiendo del punto de impresion*/
 		$consultar_Numfact_punto = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper  WHERE cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND ventas.Tic_Cod = $Par_Sql[1] AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  ventas.Vet_Cod = '$Par_Sql[0]' AND caja_aper.Pun_Cod = '$Par_Sql[2]' ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
		//echo $consultar_Numfact_punto;
              return $consultar_Numfact_punto;
		break;
		
		case 94: 
		/*Busca las facturas registradas de acuerdo a los intervalos de fecha y por punto de impresión*/
		$cons_fact_punto = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, tipos_pago.Pag_Des, persona.Prs_Nom, 
		persona.Prs_Ape, SUM(ventas_det.Vet_Imp) as Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, 
		caja_aper.Caj_Est FROM caja_aper, ventas, ventas_det, tipos_pago, cliente, iva, persona WHERE ventas.Cli_Cod = 	
		cliente.Cli_Cod AND ventas.Caj_Cod = caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND 
		ventas.Pag_Cod = tipos_pago.Pag_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Vet_Cod = 
		ventas.Vet_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND caja_aper.Pun_Cod =  '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3]
		GROUP BY ventas.Vet_Cod";
		return $cons_fact_punto;		
		break;	

	       /*  Seleccionar el saldo tipo "C"*/ 
		case 95:
		$selecciona_saldoC= "SELECT saldo_favor.Saf_Val, saldo_favor.Saf_Tip, saldo_favor.Pro_Cod, saldo_favor.Vet_Cod 
						FROM ventas, saldo_favor WHERE ventas.Vet_Cod = saldo_favor.Vet_Cod AND ventas.Cli_Cod = 
						'$Par_Sql[0]' AND saldo_favor.Saf_Tip = 'C' AND saldo_favor.Saf_Val > 0"; //ojo  se debe considerar la deuda actual
       //echo $selecciona_saldoC;
	   return $selecciona_saldoC;
	   break;
	   
	      /* Seleccionar el numero maximo de la factura*/ 
		case 96:
		$selecciona_NumMax_96= "SELECT MAX(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]'
					AND (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
//echo $selecciona_NumMax_96;
	   return $selecciona_NumMax_96;
	   break;
	   
	   /* Seleccionar el numero minimo de la factura*/ 
		case 97:
		$selecciona_NumMin= "SELECT MIN(ventas.Vet_Num) AS Num FROM ventas, caja_aper WHERE caja_aper.Caj_Cod = ventas.Caj_Cod AND ventas.Vet_Est = '$Par_Sql[2]'
					AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4]"; 
	   return $selecciona_NumMin;
	   break;
	   
	      /* Consulta el valor de la cuenta por cobrar de una deuda */ 
		case 98:
		$consulta_deuda_98= "SELECT deudas.Deu_Val FROM  deudas WHERE deudas.Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
						AND Pro_Cod = $Par_Sql[2] AND Asi_Int = $Par_Sql[3]"; 
						//echo $consulta_deuda_98;
	   return $consulta_deuda_98;
	   break;
	   
       /* Selecciona el valor de la deuda en base al Deu_Cod */ 
		case 99:
		$consultar_DeuCod= "SELECT deudas.Deu_Sal FROM deudas WHERE deudas.Deu_Cod = '$Par_Sql[0]'"; 
	   return $consultar_DeuCod;
	   break;
	   
      /* Retornar los saldos a favor de las facturas */ 
		case 100:
		$retornar_saldo= "SELECT saldo_favor.Saf_Val, saldo_favor.Saf_Cop, saldo_favor.Pro_Cod FROM saldo_favor WHERE saldo_favor.Vet_Cod = $Par_Sql[0]"; 
	   return $retornar_saldo;
	   break;	   
	   
		/* ***************************************** BANCOS ********************************************** */		
			
		case 101:
		/* Insertar bancos */
		$insbancos = "INSERT INTO bancos (Bak_Des) VALUES (UPPER('$Par_Sql[0]'))";
		return $insbancos;
		break;
		
		case 102:
		/* Consultar bancos en base a la descripción */
		$consultarban = "SELECT Bak_Des FROM bancos WHERE Bak_Des =	'$Par_Sql[0]'";
		return $consultarban;
		break;

		case 103:
		/* Modificar la información de bancos*/		
		$mod_bancos="UPDATE bancos SET Bak_Des= UPPER('$Par_Sql[0]') WHERE Bak_Cod = $Par_Sql[1]";
		return $mod_bancos;
		break;
		
		case 104: 
		/*Consulta la descrición de un banco en base a un parametro*/
		$cons_bancos = "SELECT Bak_Cod, Bak_Des FROM bancos WHERE Bak_Des like '$Par_Sql[0]%'";
		return $cons_bancos;
		break;
		
		case 105:
		/* Consultar bancos en base al código */
		$consultarban = "SELECT Bak_Des FROM bancos WHERE Bak_Cod =	$Par_Sql[0]";
		return $consultarban;
		break;
							
	       case 106: 
		/*Busca las facturas registradas de acuerdo a los intervalos de fecha*/
		$cons_fact_106 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, ventas_det.Vet_Dec, iva.Iva_Por,
					  ventas.Vet_Est, sum(ventas_det.Vet_Imp) AS Vet_Tot FROM ventas INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE (Caj_Fec BETWEEN 
					  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] $Par_Sql[4] GROUP BY ventas.Vet_Cod, 
					  ventas.Vet_Num, caja_aper.Caj_Fec,
					  persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est ORDER BY ventas.Vet_Num, persona.Prs_Ape, persona.Prs_Nom";
			//echo $cons_fact_106;
		return $cons_fact_106;
		break;
		
		
		case 107:
		/* Actualizar estado de la facura */ 
		$actualizar_est_fact = "UPDATE ventas SET Vet_Est = '$Par_Sql[0]' WHERE Vet_Cod = '$Par_Sql[1]'";
		return $actualizar_est_fact;
		break;
		
		case 108:
		/* Consulta la fac por cliente */
		$total_fact = "SELECT ventas.Cli_Cod FROM ventas WHERE  ventas.Vet_Cod = '$Par_Sql[0]'";
		return $total_fact;
		break;

		case 109: 
		/*Busca las facturas registradas de acuerdo a los intervalos de fecha*/
		$cons_fact_109 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, tipos_pago.Pag_Des, persona.Prs_Nom, 
					persona.Prs_Ape, SUM(ventas_det.Vet_Imp) as Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est 
					FROM caja_aper, ventas, ventas_det, tipos_pago, cliente, iva, persona WHERE ventas.Cli_Cod = 	
					cliente.Cli_Cod AND ventas.Caj_Cod = caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND 
					ventas.Pag_Cod = tipos_pago.Pag_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND ventas_det.Vet_Cod = 
					ventas.Vet_Cod AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' GROUP BY ventas.Vet_Cod ";
		return $cons_fact_109;		
		break;	
			
		case 110: 
		/*Busca el total de facturas de acuerdo a la carrera*/
/*		$cons_fact_escuela = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, tipos_pago.Pag_Des, 
					persona.Prs_Nom, persona.Prs_Ape, Car_Nom, SUM(ventas_det.Vet_Imp) as Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, 
					ventas.Vet_Est FROM caja_aper, ventas, ventas_det, tipos_pago, cliente, iva, persona, carreras, estudiante, niveles, 
					matriculas, semestres, promocione, modalidad, periodos WHERE ventas.Cli_Cod = cliente.Cli_Cod  AND ventas.Caj_Cod = 
					caja_aper.Caj_Cod AND cliente.Prs_Cod = persona.Prs_Cod  AND ventas.Pag_Cod = tipos_pago.Pag_Cod AND ventas_det.Iva_Cod = 
					iva.Iva_Cod  AND cliente.Prs_Cod = persona.Prs_Cod AND estudiante.Prs_Cod = persona.Prs_Cod AND matriculas.Est_Int = 
					estudiante.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod AND modalidad.Mod_Cod 
					= periodos.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod  AND promocione.Car_Int = carreras.Car_Int AND semestres.Per_Int = 
					periodos.Per_Int AND ventas_det.Vet_Cod = ventas.Vet_Cod AND semestres.Per_Int = '$Par_Sql[0]' AND Caj_Fec BETWEEN 
					'$Par_Sql[1]' AND '$Par_Sql[2]'  
					AND carreras.Car_Int = '$Par_Sql[3]' AND ventas.Vet_Est = '$Par_Sql[4]' AND ventas.Tic_Cod = $Par_Sql[6] GROUP BY ventas.Vet_Cod ";			
					//echo $cons_fact_escuela; */

		/*Busca el total de facturas de acuerdo a la carrera*/
		$my_cons_fact_escuela_110 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec,  
								  persona.Prs_Nom, persona.Prs_Ape, Car_Nom, sum(ventas_det.Vet_Imp) AS Vet_Tot, 
								  ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, ventas_det.Asi_Int 
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
persona.Prs_Ape, Car_Nom, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, 
ventas_det.Asi_Int
ORDER BY ventas.Vet_Num, Prs_Ape, Prs_Nom";			
	//	echo $my_cons_fact_escuela_110;					
		return $my_cons_fact_escuela_110;
		break;


			
/************************************* ROLES DE PAGO *********************************/		

		case 111:
		/* Consultar tipo de rol de pagos en base al código */
		$consultarol = "SELECT Tir_Des FROM tipos_rol WHERE Tir_Cod =	$Par_Sql[0]";
		return $consultarol;
		break;		
		
		case 112: 
		/* Consulta la información relacionada con el detalle del plan de cuentas */
 		$cargar_plan="SELECT Pld_Cod, Pld_Des FROM det_plan WHERE Pld_Tip = 'D'";
		return $cargar_plan;
		break;
		
		case 113:
		/* Insertar campos del rol de pagos */
 		$inscamposrol = "INSERT INTO campos_rol(Pld_Cod,Cam_Des,Cam_Tip,Cam_Obs) VALUES    	('$Par_Sql[0]',UPPER('$Par_Sql[1]'),'$Par_Sql[2]','$Par_Sql[3]')";
		return $inscamposrol;
		break;
		
		case 114: 
		/* Consulta la información relacionada con el descripcion del campo rol */
 		$cons_camposrol = "SELECT Cam_Des FROM campos_rol WHERE Pld_Cod = '$Par_Sql[0]' OR Cam_Des =
		'$Par_Sql[1]'";
		return $cons_camposrol;
				
		case 115:
		/* Consulta por el nombre del campo */
		$cons_descampo = "SELECT campos_rol.Cam_Cod, campos_rol.Pld_Cod,
		campos_rol.Cam_Des,IF (campos_rol.Cam_Tip='I-T', 'Ingreso Total', IF (campos_rol.Cam_Tip='I-P', 'Ingreso Parcial', IF (campos_rol.Cam_Tip='E', 'Egreso', ' '))) as Cam_Tip FROM campos_rol, det_plan WHERE campos_rol.Cam_Des LIKE '$Par_Sql[0]%' group by Cam_Des";
		return $cons_descampo;
		break;
		
		case 116: 
		/* Modifica o actualiza los cambios realizados en la tabla de autorozaciones */
 		$modificar_crol = "UPDATE campos_rol SET Pld_Cod = '$Par_Sql[0]', Cam_Des ='$Par_Sql[1]', Cam_Tip ='$Par_Sql[2]', Cam_Rec ='$Par_Sql[3]', Cam_Por ='$Par_Sql[4]', Cam_Obs ='$Par_Sql[5]'  WHERE Cam_Cod = $Par_Sql[6]";
		return $modificar_crol;
		break;		
		
		case 117:
		/* Consulta por el código de campo del rol */
		$cons_crol = "SELECT campos_rol.Cam_Cod, campos_rol.Pld_Cod, campos_rol.Cam_Des, campos_rol.Cam_Tip, campos_rol.Cam_Rec, campos_rol.Cam_Por, campos_rol.Cam_Obs FROM campos_rol, det_plan WHERE campos_rol.Cam_Cod = $Par_Sql[0] limit 0,1";
		return $cons_crol;		
		break;
		
		case 118:
		/* Consulta los campos de ingresos del rol de pagos existentes */
		$cons_campo = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Des FROM campos_rol WHERE Cam_Tip like 'I%'";
		return $cons_campo;
		break;
		
		case 119:
		/* Consulta los campos del rol que pertenecen al tipo de rol */
		$cons_camporol = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Des, campos_rol.Cam_Tip FROM campos_rol, tipos_rol, aportacion WHERE campos_rol.Cam_Cod = aportacion.Cam_Cod AND tipos_rol.Tir_Cod = aportacion.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0]";
		return $cons_camporol;
		break;
				
		case 120: 
		/* Carga los tipos de rol */
		$cargar_tir="SELECT tipos_rol.Tir_Cod, tipos_rol.Tir_Des FROM tipos_rol";
		return $cargar_tir;
		break;
		
		case 121: 
		/* Elimina la configuracion establecida para un rol dseleccionado */
		$borrar_confirol= "DELETE FROM aportacion WHERE Tir_Cod = $Par_Sql[0]";
		return $borrar_confirol;
		break;
		
		case 122:
		/* Guarda la configuracion establecida para un rol dseleccionado */
		$save_confirol= "INSERT INTO confi_rol(Tir_Cod,Cam_Cod,Cof_Ord) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]')";			
		//echo $save_confirol;	
		return $save_confirol;
		break;
		
		case 123:
		/* Selecciona la descripcion en base el codigo del tipo de rol */
		$tiporol= "SELECT tipos_rol.Tir_Cod, tipos_rol.Tir_Des FROM tipos_rol WHERE tipos_rol.Tir_Cod = $Par_Sql[0]";
		return $tiporol;
		break;
				
		
		case 124:
		/* COnsulta el personal */
		$cons_perso = "SELECT personal.Per_Cod, personal.Per_Tit, persona.Prs_Nom, persona.Prs_Ape FROM persona, personal WHERE personal.Prs_Cod = persona.Prs_Cod";
		//echo $cons_perso;
		return $cons_perso;
		break;
		
		case 125:
		/* Consulta el personal que pertenecen al tipo de rol */
		$cons_persorol = "SELECT personal.Per_Cod, personal.Per_Tit, persona.Prs_Nom, persona.Prs_Ape, confi_perso.Cof_Suel, confi_perso.Cam_Cod, confi_perso.Cag_Cod FROM personal, persona, tipos_rol, confi_perso WHERE personal.Per_Cod = confi_perso.Per_Cod AND personal.Prs_Cod = persona.Prs_Cod AND tipos_rol.Tir_Cod = confi_perso.Tir_Cod AND tipos_rol.Tir_Cod  = $Par_Sql[0] ORDER BY confi_perso.Cof_Cod ASC";
		//echo $cons_persorol;
		return $cons_persorol;
		break;
				
		case 126: 
		/* Elimina la configuracion establecida del pesonal para un rol dseleccionado */
		$borrar_confipers= "DELETE FROM confi_perso WHERE Tir_Cod = $Par_Sql[0]";
		return $borrar_confipers;
		break;
		
		case 127:
		/* Guarda la configuracion establecida del personal para un rol dseleccionado */
		$save_confirol= "INSERT INTO confi_perso(Cof_Cod,Tir_Cod,Per_Cod,Cof_Suel,Cam_Cod,Cag_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]')";
		//echo $save_confirol;		
		return $save_confirol;
		break;
		
		case 128:
		/* Guarda el registro del rol de pagos */
		$save_iegre= "INSERT INTO ingre_egre(Cam_Cod,Rde_Cod, Ieg_Val) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2])";		
		return $save_iegre;
		break;
				
		case 129:
		/* Insertar tipo de rol de pagos */
		$instirol = "INSERT INTO tipos_rol(Tir_Des) VALUES (UPPER('$Par_Sql[0]'))";
		return $instirol;
		break;
		
		case 130:
		/* Consultar tipos de rol de pagos en base a la descripción */
		$consultartirol = "SELECT Tir_Des FROM tipos_rol WHERE Tir_Des ='$Par_Sql[0]'";
		return $consultartirol;
		break;
		
		case 131:
		/* Modificar la información de tipos de rol de pagos*/		
		$mod_tirol="UPDATE tipos_rol SET Tir_Des= UPPER('$Par_Sql[0]') WHERE Tir_Cod = $Par_Sql[1]";
		return $mod_tirol;
		break;
		
		case 132: 
		/*Consulta la descripción del tipo de rol de pagos en base a un parametro*/
		$cons_tirol = "SELECT Tir_Cod, Tir_Des FROM tipos_rol WHERE Tir_Des like '$Par_Sql[0]%'";
		return $cons_tirol;
		break;
				
		case 133:
		/* Insertar tipo de rol de pagos */
		$insrol = "INSERT INTO rol_pagos(Tir_Cod, Rol_Des, Rol_Fec) VALUES ($Par_Sql[0], UPPER('$Par_Sql[1]'),'$Par_Sql[2]')";
		return $insrol;
		break;
		
		case 134:
		/* Guarda la configuracion establecida del personal para un rol dseleccionado */
		$ins_detrol= "INSERT INTO det_rpagos(Rol_Cod,Per_Cod,Cof_Or,Cag_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]')";		
		return $ins_detrol;
		break;
		
		case 135:
		/* Guarda la configuracion establecida para un rol d PAGOS seleccionado */
		$ins_iegre= "INSERT INTO ingre_egre(Cam_Cod,Rde_Cod,Ieg_Val,Cof_Ord) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2],'$Par_Sql[3]')";		
		return $ins_iegre;
		break;
		

		case 136:
		/* Consulta los campos de ingresos del rol de pagos */
		$cons_campoi = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Des, campos_rol.Cam_Tip, campos_rol.Cam_Rec, campos_rol.Cam_Por, confi_rol.Cof_Ord FROM campos_rol, tipos_rol, confi_rol WHERE campos_rol.Cam_Cod = confi_rol.Cam_Cod AND tipos_rol.Tir_Cod = confi_rol.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] and Cam_Tip LIKE 'I%' order by confi_rol.Cof_Ord";
		return $cons_campoi;
		break;
				
		case 137:
		/* Consulta los campos del rol de egresos de pagos */
		$cons_campoe = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Des, campos_rol.Cam_Tip, campos_rol.Cam_Rec, campos_rol.Cam_Por, confi_rol.Cof_Ord FROM campos_rol, tipos_rol, confi_rol WHERE campos_rol.Cam_Cod = confi_rol.Cam_Cod AND tipos_rol.Tir_Cod = confi_rol.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] and Cam_Tip= 'E' order by confi_rol.Cof_Ord";
		return $cons_campoe;
		break;
				
		case 138:
		/* Consulta los campos del rol de egresos de rol */
		$cons_campos = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Rec FROM campos_rol, tipos_rol, confi_rol WHERE campos_rol.Cam_Cod = confi_rol.Cam_Cod AND tipos_rol.Tir_Cod = confi_rol.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] AND campos_rol.Cam_Rec = $Par_Sql[1]";
		return $cons_campos;
		break;
		
		case 139: 
		/* Consulta la información relacionada con el descripcion del campo rol */
 		$cons_camposrec = "SELECT Cam_Cod, Cam_Des FROM campos_rol";
		return $cons_camposrec;
		break;
		
		case 140: 
		/* Consulta la información relacionada con el descripcion del campo rol */
 		$cons_camposrol = "SELECT Cam_Des FROM campos_rol WHERE Cam_Des = '$Par_Sql[0]'";
		return $cons_camposrol;
		break;
		
		case 141:
		
		$vsueldo = "SELECT confi_perso.Per_Cod, confi_perso.Cof_Suel FROM personal, persona, tipos_rol, confi_perso WHERE personal.Per_Cod = confi_perso.Per_Cod AND personal.Prs_Cod = persona.Prs_Cod AND tipos_rol.Tir_Cod = confi_perso.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] AND personal.Per_Cod = $Par_Sql[1]";
		return $vsueldo;
		break;
		
		case 142:		
		$ordcampo = "SELECT confi_rol.Cam_Cod, confi_rol.Cof_Ord FROM campos_rol, tipos_rol, confi_rol WHERE campos_rol.Cam_Cod = confi_rol.Cam_Cod AND tipos_rol.Tir_Cod = confi_rol.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] AND campos_rol.Cam_Cod = $Par_Sql[1] and Cam_Tip like 'I%'";		
		return $ordcampo;
		break;
		
		
		/***********************  C H E Q U E S *********************************		
		/* Cargado cheques según el número de comprobante de egreso */
		case 143:
		$con_cheques_143="SELECT comprobantes.Com_Cod, comprobantes.Com_Num, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Cob, Che_Obs, Com_Est, Che_Fec, cheques.Ban_Cod, cheques.Prv_Cod FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod
      AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod  
      AND comprobantes.Com_Cod = $Par_Sql[0] ORDER BY Che_Num";
		return $con_cheques_143;
		break;
		
		case 144:
		/* Cargado individual de cheque en el reporte*/
		$pri_cheque_144="SELECT comprobantes.Com_Cod, Pld_Des, Prs_Ape, Prs_Nom, Che_Num, ROUND(Che_Val,2) as Che_Val, Che_Cob, Che_Fec FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND cheques.Che_Cod = $Par_Sql[0] AND cheques.Asi_Cod = $Par_Sql[1] AND cheques.Ban_Cod = $Par_Sql[2] AND cheques.Prv_Cod = $Par_Sql[3]";
		return $pri_cheque_144;
		break;
		
		//***********************  R O L E S    D E    P A G O *********************************		
		case 145:
		/* Consulta por la descripción del Rol de Pagos */
		$carga_rol = "SELECT rol_pagos.Tir_Cod, Tir_Des, Rol_Des, Rol_Est, Rol_Cod FROM rol_pagos, tipos_rol WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND rol_pagos.Rol_Des LIKE '$Par_Sql[0]%'";
		return $carga_rol;
		break;

		case 146:
		/* Carga los nombres del personal que pertenecen al rol */
		$carga_detrol = "SELECT det_rpagos.Per_Cod, Prs_Nom, Prs_Ape, Rde_Cod, rol_pagos.Rol_Cod, confi_perso.Cam_Cod, tiposcargo.Tic_Des, det_rpagos.Cag_Cod, Cof_Or 
FROM rol_pagos, tipos_rol, persona, personal, det_rpagos, confi_perso, cargos, tiposcargo 
WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND persona.Prs_Cod = personal.Prs_Cod 
AND rol_pagos.Rol_Cod = det_rpagos.Rol_Cod AND tipos_rol.Tir_Cod = confi_perso.Tir_Cod AND cargos.Per_Cod = personal.Per_Cod AND tiposcargo.Tic_Cod = cargos.Tic_Cod
AND personal.Per_Cod = det_rpagos.Per_Cod AND rol_pagos.Tir_Cod = '$Par_Sql[0]' 
AND rol_pagos.Rol_Des = '$Par_Sql[1]' group by Prs_Ape order by Cof_Or";
		return $carga_detrol;		
		break;
		
		case 147:
		/* Carga los valores de los campos de ingreso que pertenecen al rol */
		$carga_deting = "SELECT ingre_egre.Cam_Cod, campos_rol.Cam_Des, campos_rol.Cam_Tip, ingre_egre.Ieg_Val, ingre_egre.Rde_Cod, Cof_Ord FROM rol_pagos, tipos_rol, ingre_egre, det_rpagos, campos_rol WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND rol_pagos.Rol_Cod = det_rpagos.Rol_Cod AND det_rpagos.Rde_Cod = ingre_egre.Rde_Cod AND ingre_egre.Cam_Cod = campos_rol.Cam_Cod AND det_rpagos.Rol_Cod = $Par_Sql[0] AND ingre_egre.Rde_Cod = $Par_Sql[1] AND tipos_rol.Tir_Cod=$Par_Sql[2] and Cam_Tip like 'I%' order by Cof_Ord";		
		return $carga_deting;
		break;
		
		case 148:
		/* Consulta los campos de egresos del rol de pagos existentes */
		$cons_campoe = "SELECT campos_rol.Cam_Cod, campos_rol.Cam_Des FROM campos_rol WHERE Cam_Tip='E'";
		return $cons_campoe;
		break;
		
		case 149:	
		$ordcampoe = "SELECT confi_rol.Cam_Cod, confi_rol.Cof_Ord FROM campos_rol, tipos_rol, confi_rol WHERE campos_rol.Cam_Cod = confi_rol.Cam_Cod AND tipos_rol.Tir_Cod = confi_rol.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] AND campos_rol.Cam_Cod = $Par_Sql[1] and Cam_Tip='E'";		
		return $ordcampoe;
		break;
		
		case 150:
		/* Carga los valores de los campos de egreso que pertenecen al rol */
		$carga_detegr = "SELECT distinct ingre_egre.Cam_Cod, campos_rol.Cam_Des, campos_rol.Cam_Por, campos_rol.Cam_Rec, ingre_egre.Ieg_Val, ingre_egre.Rde_Cod, Cof_Ord FROM rol_pagos, tipos_rol, ingre_egre, det_rpagos, campos_rol WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND rol_pagos.Rol_Cod = det_rpagos.Rol_Cod AND det_rpagos.Rde_Cod = ingre_egre.Rde_Cod AND ingre_egre.Cam_Cod = campos_rol.Cam_Cod AND det_rpagos.Rol_Cod = $Par_Sql[0] AND ingre_egre.Rde_Cod = $Par_Sql[1] AND tipos_rol.Tir_Cod= $Par_Sql[2] and Cam_Tip like 'E' order by Cof_Ord";
		return $carga_detegr;
		break;
				
		case 151:
		/* Carga los nombres del personal que no consta en el rol */
		$carga_per = "SELECT personal.Per_Cod, persona.Prs_Nom, persona.Prs_Ape FROM personal, persona WHERE persona.Prs_Cod = personal.Prs_Cod AND Prs_Ape Like '$Par_Sql[0]%' AND personal.Per_Cod NOT IN (select confi_perso.Per_Cod FROM personal, confi_perso WHERE personal.Per_Cod = confi_perso.Per_Cod AND confi_perso.Tir_Cod = $Par_Sql[1])";
		return $carga_per;
		break;
		
		case 152:
		/* Consulta la la descripción del rol de pagos */
		$cons_rolp = "SELECT Tir_Cod, Rol_Des FROM rol_pagos WHERE Rol_Des = '$Par_Sql[0]' AND Tir_Cod=$Par_Sql[1]";
		return $cons_rolp;
		break;
		
		
		/*********************   V A L E     D E     C A J A ***********************************/
				
		case 153:
		/* Carga los vales de caja no registrados en comprobante */
		$carga_vale = "SELECT vale_caja.Val_Num, Per_Cod, Val_Can, Val_Con, Val_Fec, Val_Est FROM vale_caja, vale_compr WHERE Val_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND vale_caja.Val_Num NOT IN (select vale_compr.Val_Num FROM vale_compr, vale_caja WHERE vale_caja.Val_Num = vale_compr.Val_Num) GROUP by vale_caja.Val_Num";
		return $carga_vale;		
		break;
		
		
		case 154:
		/* Consulta por la descripción del Rol de Pagos */
		$carga_codrol = "SELECT rol_pagos.Tir_Cod, Tir_Des, Rol_Des, Rol_Fec, Rol_Est, Rol_Cod FROM rol_pagos, tipos_rol WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND rol_pagos.Rol_Cod =$Par_Sql[0]";
		return $carga_codrol;
		break;
		
		case 155:
		/* Consulta maxino cod de la configuracion de  personal */
		$cons_per = "SELECT MAX(Cof_Cod)AS idc FROM confi_perso WHERE Tir_Cod = $Par_Sql[0]";
		return $cons_per;
		break;
	
		case 156:
		/* Insertar tipo de rol de pagos */
		$updrol = "UPDATE rol_pagos SET Rol_Con= UPPER('$Par_Sql[0]')  WHERE Rol_Cod = $Par_Sql[1]";
		return $updrol;
		break;
		
		
	    	case 157:
		/* Borrado del personal que consta en el rol a modificar */
 		$bor_detrol="DELETE FROM det_rpagos WHERE Rol_Cod=$Par_Sql[0]";
		return $bor_detrol;
		break;
		
		case 158:
		/* Borrado de los valores que constan en el rol */
 		$bor_ierol="DELETE FROM ingre_egre WHERE Rde_Cod=$Par_Sql[0]";
		return $bor_ierol;
		break;
		
		case 159:
		/* Consulta por la descripción del Rol de Pagos */
		$carga_codrol = "SELECT rol_pagos.Tir_Cod, Tir_Des, Rol_Des, Rol_Est, Rol_Fec, Rol_Cod FROM rol_pagos, tipos_rol WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND rol_pagos.Rol_Cod=$Par_Sql[0]";
		//echo $carga_codrol;
		return $carga_codrol;
		break;			
			
//		case 160:
//		/* Cargado cheques según el apellido del proveedor de comprobante de egreso */
//		$con_cheq_ape_160 = "SELECT comprobantes.Com_Cod, comprobantes.Com_Num, Pld_Des, Prs_Ape, Prs_Nom, Che_Cod,  Che_Num, Che_Val, cheques.Che_Fec, Com_Est, cheques.Asi_Cod, cheques.Ban_Cod, cheques.Prv_Cod FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ape like '$Par_Sql[0]%'";
//		return $con_cheq_ape_160;
//		break;		
	
					
		case 161: 
		/* Consulta la descripción del tipo de cargo */
 		$cargar_cargos="SELECT tiposcargo.Tic_Des, cargos.Cag_Cod FROM tiposcargo, cargos WHERE tiposcargo.Tic_Cod = cargos.Tic_Cod AND cargos.Per_Cod = $Par_Sql[0]";
		return $cargar_cargos;
		break;
		
		case 162:	
		/* Consulta el cargo que tiene el personal seleeccionado en la configuración*/	
		$cons_carg = "SELECT confi_perso.Per_Cod, confi_perso.Cag_Cod FROM personal, persona, tipos_rol, confi_perso WHERE personal.Per_Cod = confi_perso.Per_Cod AND personal.Prs_Cod = persona.Prs_Cod AND tipos_rol.Tir_Cod = confi_perso.Tir_Cod AND tipos_rol.Tir_Cod = $Par_Sql[0] AND personal.Per_Cod = $Par_Sql[1]";
		return $cons_carg;
		break;
		
		case 163: 
		/* Selecciona los cheques entre un rango de fechas */
 		$cons_cheq_163 = "SELECT comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Obs, Com_Obs, Com_Con FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod AND (cheques.Che_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND Che_Est = '$Par_Sql[2]'
AND Com_Est = 'A'		  ORDER BY banco.Ban_Cod, Che_Num, Prs_Ape, Prs_Nom";
		//echo $cons_cheq_163;
		return $cons_cheq_163;
		break;
		
		case 164:
		/* Carga los nombres del personal que pertenecen al rol */
		$carga_detrol = "SELECT det_rpagos.Per_Cod, Prs_Nom, Prs_Ape, Per_Tit, Rde_Cod, rol_pagos.Rol_Cod, confi_perso.Cam_Cod, tiposcargo.Tic_Des, det_rpagos.Cag_Cod, Cof_Or 
FROM rol_pagos, tipos_rol, persona, personal, det_rpagos, confi_perso, cargos, tiposcargo 
WHERE tipos_rol.Tir_Cod = rol_pagos.Tir_Cod AND persona.Prs_Cod = personal.Prs_Cod 
AND rol_pagos.Rol_Cod = det_rpagos.Rol_Cod AND tipos_rol.Tir_Cod = confi_perso.Tir_Cod AND cargos.Per_Cod = personal.Per_Cod AND tiposcargo.Tic_Cod = cargos.Tic_Cod
AND personal.Per_Cod = det_rpagos.Per_Cod AND rol_pagos.Tir_Cod = '$Par_Sql[0]' 
AND rol_pagos.Rol_Des = '$Par_Sql[1]' group by Prs_Ape order by Prs_Ape";
		//echo $carga_detrol;
		return $carga_detrol;		
		break;
		
		case 165:
	    /* Carga las facturas de compra en un rango de fechas */
  		$carga_fact_comp = "SELECT compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, compras.Cop_Obs, compras.Cop_Num FROM compras, det_compra WHERE compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Cod AND compras.Cop_Est='A' GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC ";
	    //echo $carga_fact_comp;
  		return $carga_fact_comp;
			
/** Cargar retención de una liquidacion de compras ***********/
		case 166:
		$carg_retenc="SELECT persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, proveedore.Prv_Cod, compras.Aut_Cod, ciudad.Ciu_Des, compras.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, autorizaci.Aut_Sri, autorizaci.Pun_Sri, autorizaci.Aut_Fci, autorizaci.Aut_Cad, retencion.Ret_Est, retencion.Ret_Cod, retencion.Ret_Con, retencion.Ret_Fec, tipo_compr.Tic_Des, renta_iva.Ren_Por, renta_iva.Ren_Sri, det_retenc.Ret_Int, det_retenc.Ret_Bas, IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM tipo_compr,persona, proveedore, compras, retencion, autorizaci,
 det_retenc, renta_iva, ciudad WHERE compras.Cop_Cod=retencion.Cop_cod AND retencion.Ret_Cod=det_retenc.Ret_Cod AND ciudad.Ciu_Cod = persona.Ciu_Cod AND renta_iva.Ren_Cod=det_retenc.Ren_Cod and autorizaci.Aut_Cod = compras.Aut_Cod 
 AND persona.Prs_Cod=proveedore.Prs_Cod  AND proveedore.Prv_Cod=compras.Prv_Cod 
 AND compras.Tic_Cod=tipo_compr.Tic_Cod AND retencion.Ret_Cod=$Par_Sql[0] ORDER BY det_retenc.Ret_Int ASC"; 			
		//echo $carg_retenc;
		return $carg_retenc;
		break;

		/** Revisa si hay Codigo de autorizacion en la compra ***********/
		case 167:
		$carg_retenc="SELECT compras.Aut_Cod FROM compras, retencion, det_retenc, renta_iva, ciudad 
WHERE compras.Cop_Cod=retencion.Cop_cod AND retencion.Ret_Cod=det_retenc.Ret_Cod 
AND renta_iva.Ren_Cod=det_retenc.Ren_Cod AND retencion.Ret_Cod=$Par_Sql[0] LIMIT 0,1"; 			
		return $carg_retenc;
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


		/* SQL's PARA CONTROL AJAX DE MODALIDAD - ETAPA - CARRERA */
		/**********************************************************/
		/* Consulta las modalidades */
		case 172:
			$consulta_modalidades_172 ="SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Cod = $Par_Sql[0]" ;
			//echo $consulta_modalidades_172;
		return $consulta_modalidades_172;
		break;

		/*Consulta de etapas*/
		case 173:
			$consulta_etapas_173="SELECT etapas.Eta_Cod, etapas.Eta_Des, etapas.Eta_Rec FROM etapas WHERE  
									etapas.Eta_Est='A' AND Eta_Rec = 0 ORDER BY etapas.Eta_Des ASC";
		return $consulta_etapas_173;
		break;		
		
		/*Consulta de las carreras en base al notasgener */
		case 174:
			$consulta_carreras_174= "SELECT Car_Nom, Niv_Des, Sem_Par, IF (semestres.Sem_Sec='D', 'Diurna', IF (semestres.Sem_Sec='V', 'Vespertina', IF 
						(semestres.Sem_Sec='N', 'Nocturna', ' '))) as Sem_Sec, modalidad.Mod_Des FROM notasgener, semestres, promocione, carreras, 
						periodos, modalidad, niveles WHERE notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
						promocione.Car_Int = carreras.Car_Int AND niveles.Niv_Cod = semestres.Niv_Cod AND modalidad.Mod_Cod = periodos.Mod_Cod 
						AND periodos.Per_Int = semestres.Per_Int AND Nge_Cod = $Par_Sql[0] GROUP BY Car_Nom, Sem_Par, Sem_Sec, Mod_Des";
						//echo $consulta_carreras_174;
		return $consulta_carreras_174;
		break;		

		/*Consulta de carreras en base a la etapa */
		case 175:
			$consulta_carreras_175="SELECT carreras.Car_Nom, carreras.Car_Int FROM carreras WHERE carreras.Eta_Cod='$Par_Sql[0]' AND carreras.Car_Est='A' ORDER BY Car_Nom";		
			//echo $consulta_carreras_175;
		return $consulta_carreras_175;
		break;

		/*Consulta de la etapa */
		case 176:
			$consulta_carreras_176="SELECT Eta_Des, Eta_Rec FROM etapas WHERE Eta_Cod='$Par_Sql[0]'";
			//echo $consulta_carreras_176;
		return $consulta_carreras_176;
		break;

		case 177:
		/* Consulta del total de facturas por carrera */
		$esquema_177 = "SELECT (sum(ventas_det.Vet_Imp)) as Importe, iva.Iva_Cod, iva.Iva_Sri, Iva_Por, 
					  (sum(ventas_det.Vet_Imp) - (sum((Vet_Imp * Vet_Des) /100) + sum((Vet_Imp * Vet_Dec) /100))) 
					  as Total, (((sum(ventas_det.Vet_Imp) - (sum((Vet_Imp * Vet_Des)/100) + sum((Vet_Imp * Vet_Dec)/100))) 
					  * Iva_Por)/100) as Iva, ((sum((Vet_Imp * Vet_Des)/100) + sum((Vet_Imp * Vet_Dec)/100))
				      ) as Descuento
						FROM ventas, caja_aper, ventas_det, iva, notasgener, semestres, promocione
						 WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
						ventas_det.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod
						= promocione.Pro_Cod AND
						(caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = '$Par_Sql[2]' AND 
						Tic_Cod = $Par_Sql[3] AND Car_Int = $Par_Sql[4]
                        GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por
                        ORDER BY Iva_Por DESC";		
		//echo $esquema_177;
		return $esquema_177;
		break;

		/* Consulta para el Reporte Diario de Caja */
		/*******************************************/
		case 178:
		/* Consulta la cabecera del comprobante de caja */
		$cab_compr_178 = "SELECT comprobantes.Com_Cod, Com_Num, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Ced, 
					Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est, Tia_Des, Tia_Ini FROM comprobantes, cliente, persona, caja_compr, tipo_asien WHERE  
					comprobantes.Cli_Cod=cliente.Cli_Cod AND cliente.Prs_Cod=persona.Prs_Cod AND caja_compr.Com_Cod = comprobantes.Com_Cod AND
					comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND
					caja_compr.Caj_Cod = $Par_Sql[0]";
		return $cab_compr_178;
		break;

		case 179:
		/* Consulta de los bancos del plan de cuentas */
		$bancos_plan_179 = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan 
		 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND pago_plan.Pag_Cod = 
		 $Par_Sql[0] ORDER BY Pld_Cdc, Pld_Des";
		// echo $bancos_plan_179;
		return $bancos_plan_179;
		break;


	//	case 179:
		/* Consulta de los bancos del plan de cuentas */
	//	$bancos_plan_179 = "SELECT Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan 
	//	 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Fac = 'A' ORDER BY Pld_Cdc, Pld_Des";
	// echo $bancos_plan_179;
	//	return $bancos_plan_179;
	//	break;
		
		case 180:
		/* COnsulta el cliente reservado para la caja diaria */
		$caja_clien_180 = "SELECT Cli_Cod FROM caja_clien";
		//echo $caja_clien_180;
		return $caja_clien_180;
		break;
					
case 181:
		/* COnsulta de las cajas que estan listas para generar y que NO han sido generadas */
		$caja_clien_181 = "SELECT Caj_Cod, Caj_Fec FROM caja_aper WHERE Caj_Est = 'C' AND Caj_Gen = 'S' AND Caj_Cod NOT IN (SELECT 
  caja_compr.Caj_Cod
FROM
  caja_compr
  INNER JOIN comprobantes ON (caja_compr.Com_Cod = comprobantes.Com_Cod)
WHERE comprobantes.Com_Est = 'A') AND 
						YEAR(Caj_Fec) = $Par_Sql[0] AND Pun_Cod = $Par_Sql[1]";
		//echo $caja_clien_181;
		return $caja_clien_181;
		break;

		case 182:
		/* COnsulta del detalle de las retenciones */
		/* $det_renta_182 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, det_retenc.Iva_Cod, Ret_Bas, Ren_Por, Iva_Por, 
						(Ret_Bas * Ren_Por)/100 as Val_Ret FROM det_retenc, iva, renta_iva WHERE det_retenc.Iva_Cod 
						= iva.Iva_Cod AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]"; */
		$det_renta_182 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por,  
						(Ret_Bas * Ren_Por)/100 as Val_Ret, det_retenc.Adq_Cod FROM det_retenc, renta_iva WHERE 
						 det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]";
		//echo $det_renta_182;
		return $det_renta_182;
		break;

		case 183:
		/* COnsulta del detalle de las retenciones */
		$caja_clien_183 = "SELECT det_retenc.Ret_Bas AS Total, (det_retenc.Ret_Bas * renta_iva.Ren_Por) / 100 AS Renta 
						FROM renta_iva, det_retenc,retencion, compras WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
						det_retenc.Ret_Cod =retencion.Ret_Cod AND retencion.Tic_Cod=$Par_Sql[0] AND 
						retencion.Ret_Est='$Par_Sql[4]' AND 
						compras.Cop_Cod=retencion.Cop_Cod AND (compras.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND 
						renta_iva.Ren_Cod = $Par_Sql[3]";
		//echo $caja_clien_183;
		return $caja_clien_183;
		break;

		case 184:
		/* COnsulta el numero de comprobantes de rentencion emitidos */
		$renta_184 = "SELECT count(renta_iva.Ren_Cod) as Renta_Iva FROM renta_iva, det_retenc,retencion, compras 
					WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod AND det_retenc.Ret_Cod =retencion.Ret_Cod AND retencion.Tic_Cod
					=$Par_Sql[0] AND retencion.Ret_Est='$Par_Sql[1]' AND compras.Cop_Cod=retencion.Cop_Cod AND 
					(compras.Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]') AND renta_iva.Ren_Ret = '$Par_Sql[4]'";
		//echo $renta_184;
		return $renta_184;
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

		case 186:
		/* Consulta los campos de las asiganturas */
		$asignaturas_186 = "SELECT Asi_Des FROM asignatura WHERE Asi_Int = $Par_Sql[0]";
		//echo $asignaturas_186;
		return $asignaturas_186;
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

		case 189: 
		/* Consulta la información relacionada con el código del periodo contable */
 		$consul_fecha_189 =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, YEAR(Pec_Fei) as Ann, Pla_Cod FROM perio_cont WHERE Pec_Cod =$Par_Sql[0]";
		//echo $consul_fecha_189;
		return $consul_fecha_189;
		break;	

	/* Inserción de los cheques de los comprobantes de egreso */
		case 190:
		$ins_cheques_190="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]', Che_Cob='$Par_Sql[4]', Che_Val=$Par_Sql[5], Che_Obs=UPPER('$Par_Sql[6]'), Che_Fec='$Par_Sql[7]', Che_Cod = $Par_Sql[8]";
		//echo $ins_cheques_190;
		return $ins_cheques_190;
		break;

		/* Actualizacion de la cabecera del comprobante */
		case 191:	
		$act_cabcompr="UPDATE comprobantes SET Com_Con=UPPER('$Par_Sql[0]'), Com_Obs=UPPER('$Par_Sql[1]'), Com_Val=$Par_Sql[3], Com_Num = '$Par_Sql[4]', Com_Fec = '$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[2]";
		return $act_cabcompr;
		break;	

		/* Consulta el total de anticipos por proveedor y periodo contable */
		case 192:	
		$sum_anticipo_192="SELECT (asientos.Asi_Val) AS Asi_Val, det_plan.Pld_Des, det_plan.Pld_Cod, anticipos.Ant_Cod, Ant_Fec, anticipos.Ant_Cod FROM anticipos
				  INNER JOIN compr_anti ON (compr_anti.Ant_Cod = anticipos.Ant_Cod) INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
				  INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
				  INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod) WHERE  anticipos.Prv_Cod = $Par_Sql[0] AND 
				  anticipos.Ant_Est = 'A'"; //GROUP BY det_plan.Pld_Des, det_plan.Pld_Cod sum
						  //AND anticipos.Ant_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
		//echo $sum_anticipo_192;
		return $sum_anticipo_192;
		break;	

		/* Consulta los anticipos individuales del por proveedor y periodo contable */
		case 193:	
		$sum_anticipo_193="SELECT anticipos.Ant_Cod, asientos.Asi_Val, det_plan.Pld_Cod FROM anticipos
					  INNER JOIN compr_anti ON (compr_anti.Ant_Cod = anticipos.Ant_Cod) INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
					  INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
					  INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod) WHERE anticipos.Prv_Cod = $Par_Sql[0] AND 
					  asientos.Pld_Cod = $Par_Sql[1] AND anticipos.Ant_Est = 'A'"; 
						  //AND anticipos.Ant_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
		//  echo $sum_anticipo_193;
		return $sum_anticipo_193;
		break;	

		/* Consulta todos los valores de los anticipos ya cruzados */
		case 194:	
		$sum_anticipo_194="SELECT sum(det_antici.Ant_Val) AS Ant_Val FROM compras INNER JOIN det_antici ON (compras.Cop_Cod = det_antici.Cop_Cod)
						WHERE det_antici.Ant_Cod = $Par_Sql[0] AND compras.Cop_Est = 'A'"; 						  
		 // echo $sum_anticipo_194;
		return $sum_anticipo_194;
		break;	

		/* Inserta el detalle en det_antici */
		case 195:	
		$ins_det_anticipo_195="INSERT INTO det_antici (Ant_Cod, Cop_Cod, Ant_Val) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2])"; 						  
		//  echo $ins_det_anticipo_195;
		return $ins_det_anticipo_195;
		break;	

		case 196: 
		  $costos_196="SELECT Pro_Cod, Cos_Pre, Cos_Gen FROM costo_matr WHERE Pem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Sem_Cod = $Par_Sql[2] AND Cos_Est='A'";         
		//echo "<br>".$costos_196;
	       return $costos_196;
	       break;



			
		/********************************************************************************************************/	
		/********************************************************************************************************/			
		/******************************** FACTURAS DE COMPRA ****************************************************/	
		/********************************************************************************************************/	
		/********************************************************************************************************/			
		case 201:
		/* Consulta de la caja activa en base al vendedor */
		$consultar_caja = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Pun_Cod FROM caja_aper WHERE 
						caja_aper.Caj_Est ='A' AND caja_aper.Pun_Cod = '$Par_Sql[0]'";
		return $consultar_caja;
		break;
			
		case 202:
		/* Consulta de los datos del proveedor */
		$consultar_proveedore = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
						persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, proveedore.Prv_Cod
						FROM proveedore, persona WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Cod = '$Par_Sql[0]'";
		return $consultar_proveedore;
		break;
			
		case 203:
		/* Consulta de los iva activos */
		$consultar_iva = "SELECT iva.Iva_Cod, Iva_Por FROM iva WHERE iva.Iva_Est = 'A'";
		return $consultar_iva;
		break;
			
		case 204:
		/* Consulta de los iva activos */
		$consultar_sustento = "SELECT Tri_Cod, Tri_Sri, Tri_Des FROM sustento WHERE Tri_Est = 'A'";
		return $consultar_sustento;
		break;

			case 205: 
		/* insertar datos de la factura de compra*/
 		$inser_factcom = "INSERT INTO compras (Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Aut, Cop_Fec, Cop_Reg, Cop_Des, Cop_Obs, Cop_Cad, Cop_Imf) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], UPPER('$Par_Sql[3]'), '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', UPPER('$Par_Sql[8]'), '$Par_Sql[9]', '$Par_Sql[10]')";
		return $inser_factcom;
		break;
			
		case 206: 
		$inser_detafaccom = "INSERT INTO det_compra 
		      (Cop_Cod,    Cop_Can,     Iva_Cod,    Cop_Pro,      Cop_Pru,    Cop_Imp,     Cop_Dec)
		VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]' ,'$Par_Sql[5]', '$Par_Sql[6]')";
		return $inser_detafaccom;
		break;

		
		case 207:
		/* SENTECIAS UTILILES EN REPORTES PARA CABECERAS */
		/* Consulta que permite cargar el nombre de la empresa a que pertenece el usuario */
		$cabecera_empresa = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des FROM empresas, sucursal, ciudad WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
		return  $cabecera_empresa;
		break;
				
		/* Consulta de los rubros */
		case 208:
		$deuda_208= "SELECT deudas.Pro_Cod, Pro_Ide, Deu_Val, Deu_Fec, Ite_Lar, producto.Iva_Cod, Iva_Por, Nge_Cod, Deu_Rec, Asi_Int, Deu_Obs FROM 
					deudas, producto, item, iva WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Iva_Cod = iva.Iva_Cod AND				
					producto.Ite_Cod = item.Ite_Cod AND Cli_Cod = $Par_Sql[0] AND Nge_Cod = 
					$Par_Sql[1] AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3] AND deudas.Pro_Cod = $Par_Sql[4]";		
				//echo $deuda_208;		
		return $deuda_208;
		break;

		case 209:
		/* Inserta los saldos a favores del cliente */
		$saldo_deuda = "INSERT INTO saldo_favor (Vet_Cod, Pro_Cod, Saf_Val, Saf_Cop, Saf_Tip) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[2], '$Par_Sql[3]')";
		//echo $saldo_deuda;
		return $saldo_deuda;
		break;

		case 210:
		/* Consulta los totales de las facturas agrupados por rubros */
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
		/* Consulta los totales de las facturas agrupados por rubros por CARRERAS*/
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
		/* Consulta de los totales de las facturas en un rango de fechas detalladamente */
		$fac_detalle_212 = "SELECT ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape,
sum(ventas_det.Vet_Imp) AS Vet_Imp, ventas_det.Vet_Dec, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod, ventas_det.Nge_Cod FROM ventas INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE (Caj_Fec BETWEEN 
					  '$Par_Sql[0]' AND '$Par_Sql[1]')  AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] AND caja_aper.Pun_Cod = $Par_Sql[4] GROUP BY 
ventas.Vet_Cod, ventas.Vet_Num, caja_aper.Caj_Fec, persona.Prs_Nom, persona.Prs_Ape, iva.Iva_Por, ventas.Vet_Est, ventas.Cli_Cod ORDER BY ventas.Vet_Num, persona.Prs_Ape, 
persona.Prs_Nom";
//echo $fac_detalle_212;
		return $fac_detalle_212;
		break;
		
		case 213: 
		/* consultar los datos del cliente-escuela matriculado por codigo del cliente*/
 		$con_client_esc = "SELECT cliente.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, niveles.Niv_Des,  Sem_Par,
        IF (semestres.Sem_Sec='D', 'Diurna', IF (semestres.Sem_Sec='V', 'Vespertina', IF (semestres.Sem_Sec='N', 'Nocturna', ' '))) as Sem_Sec, escuelas.Esc_Nom, modalidad.Mod_Des,
        IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, carreras.Car_Nom, Sem_Des FROM persona, cliente, estudiante, niveles, matriculas, 
        semestres, escuelas, promocione, carreras, modalidad, periodos
        WHERE cliente.Prs_Cod = persona.Prs_Cod AND carreras.Esc_Int = escuelas.Esc_Int
        AND promocione.Car_Int = carreras.Car_Int AND semestres.Pro_Cod = promocione.Pro_Cod
 		AND estudiante.Prs_Cod = persona.Prs_Cod AND matriculas.Est_Int = estudiante.Est_Int
 		AND matriculas.Sem_Cod = semestres.Sem_Cod AND niveles.Niv_Cod = semestres.Niv_Cod
 		AND modalidad.Mod_Cod = periodos.Mod_Cod AND periodos.Per_Int = semestres.Per_Int AND cliente.Cli_Cod = '$Par_Sql[0]'
		AND semestres.Per_Int = $Par_Sql[1]";
		return $con_client_esc;
		break;

	
		case 214:
		/* Consulta del codigo de la deuda en base al producto y el codigo de la factura*/
		$cons_deuda = "SELECT det_deudas.Vet_Cod, det_deudas.Deu_Cod FROM det_deudas, deudas WHERE deudas.Deu_Cod = det_deudas.Deu_Cod AND 
					deudas.Pro_Cod = $Par_Sql[0] AND det_deudas.Vet_Cod = $Par_Sql[1]";
		return $cons_deuda;
		break;
	
		case 215:
		/* Actualiza el pago de las facturas en el detalle de las deudas */
		$act_det_deuda = "UPDATE det_deudas SET Deu_Val = $Par_Sql[1] WHERE  Deu_Cod = $Par_Sql[0]";
		return $act_det_deuda;
		break;
	
		/* Baja de la deuda registrada en la tabla deudas */
		case 216:
		$baja_deuda_216="DELETE FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Asi_Int=$Par_Sql[3] AND Deu_Rec = $Par_Sql[4]";
		//echo $baja_deuda_216;
	    	return $baja_deuda_216;
    		break; 

		case 217:
		/* Consulta el codigo de las deudas del cliente */
		/* En caso de encontrar mas de dos deudas repetidas con diferente periodo, entonces se debe 
		cancelar la primera deuda */
		$cons_deuda = "SELECT deudas.Deu_Cod, deudas.Deu_Sal FROM deudas, notasgener, semestres WHERE deudas.Nge_Cod = notasgener.Nge_Cod AND 
						notasgener.Sem_Cod = semestres.Sem_Cod AND deudas.Pro_Cod = $Par_Sql[0] AND deudas.Cli_Cod = $Par_Sql[1] AND deudas.Deu_Sal 
						> 0 ORDER BY semestres.Per_Int";
		return $cons_deuda; /* Ordena esta consulta por ORDER BY semestres.Per_Int para tratar de elegir la deuda del periodo actual */
		break;
	
		case 218:
		/* Actualiza el saldo a favor */
$act_sal_favor = "UPDATE saldo_favor SET Saf_Val = $Par_Sql[2], Saf_Cop = $Par_Sql[3] WHERE  Vet_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1]";
		//echo $act_sal_favor;
		return $act_sal_favor;
		break;

		case 219:
		/* Consulta el codigo de las deudas del cliente ya cancelada en la tabla det_deudas*/
		$cons_deuda_det = "SELECT det_deudas.Deu_Cod, det_deudas.Deu_Val FROM det_deudas, deudas WHERE deudas.Deu_Cod = det_deudas.Deu_Cod AND 
						det_deudas.Pro_Cod = $Par_Sql[0] AND det_deudas.Vet_Cod = $Par_Sql[1]";
						//echo $cons_deuda_det;
		return $cons_deuda_det; /* Ordena esta consulta por ORDER BY semestres.Per_Int para tratar de elegir la deuda del periodo actual */
		break;
	
		case 220:
  	    /* Consulta los saldo a favor almacenados en base el Codigo de la factura y el Producto*/
		$cons_saldos = "SELECT Saf_Val, Saf_Cop FROM saldo_favor WHERE Vet_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1]";
		return $cons_saldos;
		break;	
		
		
		case 221:
		/* Elimina los saldo a favor en base al Vet_Cod y Pro_Cod */
		$elim_saldos = "DELETE FROM saldo_favor WHERE Vet_Cod=$Par_Sql[0] AND Pro_Cod=$Par_Sql[1]";
		return $elim_saldos;
		break;
		
		/* Borrado de los saldos a favor desde la interfaz de modificar */
		case 222:
		$bor_saldo="DELETE FROM saldo_favor WHERE Vet_Cod='$Par_Sql[0]'";
		return $bor_saldo;
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
		/* Consulta de la descripcion de la carrera que cursa el estudiante */
		$consultar_carrera_224 = "SELECT Car_Nom, niveles.Niv_Des,  Sem_Par, IF (semestres.Sem_Sec='D', 'Diurna', 
					IF (semestres.Sem_Sec='V', 'Vespertina', IF (semestres.Sem_Sec='N', 'Nocturna', ' '))) as Sem_Sec, 
					modalidad.Mod_Des FROM notasgener, semestres, promocione, carreras, niveles, modalidad, periodos 
					WHERE notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
					carreras.Car_Int = promocione.Car_Int AND niveles.Niv_Cod = semestres.Niv_Cod
			 		AND modalidad.Mod_Cod = periodos.Mod_Cod AND periodos.Per_Int = semestres.Per_Int AND
					notasgener.Nge_Cod = $Par_Sql[0]";
					//echo $consultar_carrera_224;
		return $consultar_carrera_224;
		break;

		case 225:
		/* Consulta de la descripcion de la carrera que cursa el estudiante */
		/* $inconsistencias_225 = "SELECT ventas.Vet_Cod, Vet_Num, Prs_Ape, Prs_Nom, Caj_Fec
						 FROM caja_aper, ventas, ventas_det, producto, item, cliente, persona WHERE 
						ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod 
						AND producto.Ite_Cod = item.Ite_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND  
						Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = $Par_Sql[3] 
						AND ventas.Vet_Cod
						NOT IN 
						(SELECT ventas.Vet_Cod 
						FROM caja_aper, ventas, ventas_det, producto, item, cliente, persona, estudiante, matriculas, semestres, promocione, 
						carreras, periodos WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod 
						= producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND cliente.Prs_Cod = 
						persona.Prs_Cod AND estudiante.Prs_Cod = persona.Prs_Cod AND estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod 
						= semestres.Sem_Cod AND promocione.Pro_Cod = semestres.Pro_Cod AND promocione.Car_Int = carreras.Car_Int AND semestres.Per_Int = 	
						periodos.Per_Int AND Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' AND matriculas.Mat_Est = 'A' AND semestres.Per_Int = '$Par_Sql[4]' AND ventas.Tic_Cod = $Par_Sql[3] ) 
						GROUP BY ventas.Vet_Cod";
						//echo $inconsistencias_225; */
		 $inconsistencias_225 = "SELECT ventas.Vet_Cod, Vet_Num, Prs_Ape, Prs_Nom, Caj_Fec, Pun_Des FROM caja_aper, ventas, 
		 				ventas_det, producto, item, cliente, persona, puntos_imp WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas_det.Vet_Cod = ventas.Vet_Cod AND ventas_det.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod 
						= item.Ite_Cod AND cliente.Cli_Cod = ventas.Cli_Cod AND cliente.Prs_Cod = persona.Prs_Cod AND caja_aper.Pun_Cod = puntos_imp.Pun_Cod AND Caj_Fec 
						BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND ventas.Vet_Est = '$Par_Sql[2]' AND ventas.Tic_Cod = 
						$Par_Sql[3] AND Nge_Cod = 0 GROUP BY ventas.Vet_Cod, Vet_Num, Prs_Ape, Prs_Nom, Caj_Fec ORDER BY Vet_Num, Prs_Ape, Prs_Nom";
						//echo $inconsistencias_225;
		return $inconsistencias_225;
		break;


/* CONSULTAS PARA GENERAR LOS ARCHIVOS XML */
		
		case 226:
		/* Consulta la identificación del archivo xml */
		$identificacion_226 = "SELECT Emp_Ruc, Emp_Nom, Suc_Dir, Suc_Te1, Suc_Fax, Suc_Cor, Emp_Rce, Emp_Rep, Emp_Rco FROM empresas, sucursal
								WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND empresas.Emp_Cod = 1";		
								//echo $identificacion_226;
		return $identificacion_226;
		break;
		
		case 227:
		/* Consulta del esquema sin recursividad*/
		$esquema_227 = "SELECT Esq_Cod, Esq_Des, Esq_Xml FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] AND esquema.Esq_Est = 'A'";		
		//echo $esquema_227;
		return $esquema_227;
		break;
	
		case 228:
		/*Consulta de los datos de la cabecera */
		$detalle_xml_228 = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, Cop_Imf, 	
Cop_Aut, Cop_Cad
FROM compras, sustento, proveedore, persona, identifica, tipo_compr
WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND 
proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND 
compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
AND compras.Cop_Est = 'A' AND (compras.Tic_Cod = 1 OR compras.Tic_Cod = 2 OR compras.Tic_Cod = 3 OR compras.Tic_Cod = 10 OR compras.Tic_Cod = 26) AND compras.Tri_Cod != 1";//AND compras.Tic_Cod = $Par_Sql[2] 
//echo $detalle_xml_228;
		return $detalle_xml_228;
		break;
		
		case 229:
		/* Consulta del iva mayor a cero (0) */
		$consul_iva_229 = "SELECT Iva_Cod, Iva_Sri, Iva_Por FROM iva WHERE iva.Iva_Por > 0 AND iva.Iva_Est = 'A'";
		return $consul_iva_229;
		break;
		
case 230:
/*Consulta de los datos del detalle */
$detalle_xml_230 = "SELECT (sum(Cop_Imp) - 
(sum((Cop_Imp * Cop_Des) /100) + sum((Cop_Imp * Cop_Dec) /100))) as Cop_Imp, Iva_Sri, Iva_Por FROM compras, det_compra, iva
WHERE compras.Cop_Cod = det_compra.Cop_Cod AND det_compra.Iva_Cod = iva.Iva_Cod AND det_compra.Cop_Cod = $Par_Sql[0] AND det_compra.Adq_Cod !=13 GROUP BY Iva_Sri, Iva_Por";
return $detalle_xml_230;
break;
		
		case 231:
		/*Consulta de los datos del ICE */
		$detalle_xml_231 = "SELECT Sum(Cop_Imp) as Cop_Imp, Ice_Sri, Ice_Por, (Sum(Cop_Imp) * Ice_Por )/100 as Mon_Ice, Ice_Cod 
							FROM det_compra, ice
							WHERE det_compra.Ice_Int = ice.Ice_Int AND det_compra.Cop_Cod = $Par_Sql[0] GROUP BY 
							Ice_Sri, Ice_Por";
		return $detalle_xml_231;
		break;

	case 232:
    /*Consulta de los montos bienes o servicios */
    $detalle_xml_232 = "SELECT sum(det_retenc.Ret_Bas) AS Ret_Bas, det_retenc.Adq_Cod, renta_iva.Ren_Por FROM   retencion
    INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)   INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
    WHERE  (det_retenc.Adq_Cod = 1 OR det_retenc.Adq_Cod = 2 OR det_retenc.Adq_Cod = 3) AND  renta_iva.Ren_Por != 100 AND  retencion.Cop_Cod =        $Par_Sql[0] AND det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A' GROUP BY  det_retenc.Adq_Cod";
    

    return $detalle_xml_232;
    break;

		case 233:
		/*Consulta de los datos AIR */
		$detalle_xml_233 = "SELECT Ren_Sri, sum(Ret_Bas) AS Ret_Bas, Ren_Por, sum((Ret_Bas * Ren_Por)/100) as Val_Air, Ret_Num, Aut_Sri, Ret_Fec
							FROM retencion, det_retenc, renta_iva, autorizaci WHERE retencion.Ret_Cod = det_retenc.Ret_Cod 
							AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod 
							AND retencion.Cop_Cod = $Par_Sql[0] AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'
							GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec
							";/*En esta SQL se agrego GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec para unificar 
							en caso de que a una retencion se le agregue 2 codigos de los mismos */
//echo $detalle_xml_233;
		return $detalle_xml_233;
		break;
		
		case 234:
		/* Consulta del esquema sin recursividad para los grupos */
		$esquema_234 = "SELECT Esq_Cod, Esq_Des, Esq_Xml FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] AND Esq_Ini = '$Par_Sql[2]'";		
		//echo $esquema_234;
		return $esquema_234;
		break;
	
		case 235:
		/* Consulta total de las ventas */
/*		$esquema_235 = "SELECT round(sum(ventas_det.Vet_Imp),2), iva.Iva_Cod, iva.Iva_Sri, Iva_Por, round(sum(ventas_det.Vet_Imp) - 
(sum((Vet_Imp * Vet_Des) /100) + 
sum((Vet_Imp * Vet_Dec) /100)),2) as Total
						FROM ventas, caja_aper, ventas_det, iva WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
						ventas.Vet_Cod = ventas_det.Vet_Cod AND ventas_det.Iva_Cod = iva.Iva_Cod AND
						(caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = 'A'  
                        GROUP BY iva.Iva_Cod, iva.Iva_Sri, Iva_Por
                        ORDER BY Iva_Por DESC";		
		//echo $esquema_235;
		return $esquema_235;
*/		
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

		case 236:
		/* Consulta total de las ventas Activas o Anuladas*/
		$esquema_236 = "SELECT ventas.Vet_Cod, Tic_Sri, Vet_Num, Aut_Sri, Caj_Fec 
FROM ventas, caja_aper, autorizaci, tipo_compr WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND 
ventas.Aut_Cod = autorizaci.Aut_Cod AND ventas.Tic_Cod = 
tipo_compr.Tic_Cod AND
(caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = '$Par_Sql[2]' ";		
		//echo $esquema_236;
		return $esquema_236;
		break;

		case 237:
		/* Consulta total de las  retenciones o liquidaciones anuladas o activas */
		$esquema_237 = "SELECT retencion.Ret_Cod, Tic_Sri, Ret_Num, Aut_Sri, Ret_Fec FROM retencion, tipo_compr, 
				autorizaci WHERE retencion.Tic_Cod = tipo_compr.Tic_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod AND
				(retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND Ret_Est = '$Par_Sql[2]'";		
		//echo $esquema_237;
		return $esquema_237;
		break;

		case 238:
		/* Consulta las compras o liquidaciones de compra en base al Estado y Tipo de comprobante */
		$esquema_238 = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, 
					Cop_Imf, Cop_Aut, Cop_Cad FROM compras, sustento, proveedore, persona, identifica, tipo_compr
					WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND 
					proveedore.Prs_Cod = persona.Prs_Cod AND proveedore.Ide_Cod = identifica.Ide_Cod AND 
					compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Reg BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
					AND compras.Cop_Est = '$Par_Sql[2]' AND tipo_compr.Tic_Sri = $Par_Sql[3]";		
		//echo $esquema_238;
		return $esquema_238;
		break;
	
		case 239:
		/* Consulta cantidad de comprobantes de venta emitidos */
		$esquema_239 = "SELECT count(ventas.Vet_Cod) as Vet_Cnt FROM ventas, caja_aper WHERE ventas.Caj_Cod = 
					caja_aper.Caj_Cod AND (caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND ventas.Vet_Est = 
					'$Par_Sql[2]'";		
		//echo $esquema_239;
		return $esquema_239;
		break;

		case 240:
		/*Consulta de los datos del detalle */
		$detalle_xml_240 = "SELECT Iva_Sri, Iva_Por, sum(Cop_Imp) AS Cop_Imp FROM compras, det_compra, iva WHERE compras.Cop_Cod = det_compra.Cop_Cod AND det_compra.Iva_Cod = iva.Iva_Cod AND det_compra.Cop_Cod = 
$Par_Sql[0] GROUP BY Iva_Sri, Iva_Por";
//echo $detalle_xml_240;

		return $detalle_xml_240;
		break;

		case 241:
		/*Consulta de los datos AIR agrupados */
		
		$detalle_xml_241 = "SELECT Ren_Sri, sum(Ret_Bas) as Ret_Bas, Ren_Por, (sum(Ret_Bas) * Ren_Por)/100 as Val_Air, Ret_Num, 
					Aut_Sri, Ret_Fec FROM retencion, det_retenc, renta_iva, autorizaci WHERE retencion.Ret_Cod = 
					det_retenc.Ret_Cod AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod 
					AND retencion.Cop_Cod = $Par_Sql[0] AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'
					GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec"; 
		/*$detalle_xml_241 = "SELECT Ren_Sri, (Ret_Bas) as Ret_Bas, Ren_Por, ((Ret_Bas) * Ren_Por)/100 as Val_Air, Ret_Num, 
					Aut_Sri, Ret_Fec FROM retencion, det_retenc, renta_iva, autorizaci WHERE retencion.Ret_Cod = 
					det_retenc.Ret_Cod AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod 
					AND retencion.Cop_Cod = $Par_Sql[0] AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'";*/					
//echo $detalle_xml_241;
		return $detalle_xml_241;
		break;

		case 242:
		/*Consulta de la autorizacion de la liquidacion de compra */
		$detalle_xml_242 = "SELECT Aut_Sri FROM compras, autorizaci WHERE compras.Aut_Cod = autorizaci.Aut_Cod AND
								Cop_Cod = $Par_Sql[0]";
//echo $detalle_xml_242;
		return $detalle_xml_242;
		break;

		case 243:
		/* Consulta los años de las facturas de compras recibidas */
		$anios_243 = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras WHERE  compras.Cop_Est='A' 
					 ORDER BY YEAR(compras.Cop_Fec) DESC";//Antes GROUP BY YEAR(compras.Cop_Fec) compras.Tic_Cod=$Par_Sql[0] AND
					//echo $anios_243;
		return $anios_243;
		break;

		/* Consulta del detalle de la factura de compra */
		case 244: 
		$con_fac_detalle_244="SELECT  compras.Cop_Num, compras.Prv_Cod, compras.Cop_Aut, compras.Ciu_Cod, compras.Cop_Fec, 	
						compras.Cop_Reg, compras.Cop_Cad, compras.Cop_Imf, compras.Cop_Des, det_compra.Cop_Int, 
						det_compra.Cop_Pru, compras.Cop_Obs, compras.Cop_Est, det_compra.Cop_Pro,
						det_compra.Cop_Can, det_compra.Cop_Imp, det_compra.Cop_Dec, det_compra.Iva_Cod, iva.Iva_Por
						FROM compras, det_compra, iva WHERE compras.Cop_Cod=det_compra.Cop_Cod
						AND compras.Cop_Cod=$Par_Sql[0] AND det_compra.Iva_Cod=iva.Iva_Cod";
		return $con_fac_detalle_244; 
		break;
		
		case 245:
		/* Consulta los años de las facturas de ventas recibidas */
		$anios_245 = "SELECT YEAR(caja_aper.Caj_Fec) as Anio FROM ventas, caja_aper WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas.Tic_Cod=$Par_Sql[0]  
					GROUP BY YEAR(caja_aper.Caj_Fec) ORDER BY YEAR(caja_aper.Caj_Fec) DESC";//antes AND caja_aper.Pun_Cod = $Par_Sql[1]
					//echo $anios_245;
		return $anios_245;
		break;

		case 246:
		/* Consulta los años de las retenciones generadas */
		$anios_246 = "SELECT YEAR(retencion.Ret_Fec) as Anio FROM retencion INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod) GROUP BY Anio ORDER BY  Anio DESC";
					//echo $anios_246;
		return $anios_246;
		break;

		case 247:
		/* Consulta los años de las facturas de compras recibidas */
		$anios_247 = "SELECT YEAR(compras.Cop_Fec) as Anio FROM compras WHERE compras.Cop_Est='A' 
					GROUP BY YEAR(compras.Cop_Fec) ORDER BY YEAR(compras.Cop_Fec) DESC";
					//echo $anios_247;
		return $anios_247;
		break;

			/* Consulta el codigo interno del plan de cuentas en base al codigo del periodo contable */
		case 248: 
		$consulta_plan_248 = "SELECT det_plan.Pla_Cod FROM det_plan, plan_cuenta, comprobantes, asientos WHERE plan_cuenta.Pla_Cod = det_plan.Pla_Cod 
					AND asientos.Pld_Cod = det_plan.Pld_Cod AND asientos.Com_Cod = comprobantes.Com_Cod AND comprobantes.Pec_Cod = $Par_Sql[0] GROUP BY det_plan.Pla_Cod ORDER BY det_plan.Pla_Cod DESC"; 
		//echo $consulta_plan_248;
		return $consulta_plan_248;
		break;

		/* Cargado de la cuenta por medio de su codigo de cuenta */
		case 249:
		$cargar_cuenta_249="SELECT Pld_Cod,Pld_Des FROM det_plan, plan_cuenta WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND det_plan.Pld_Cdc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND Pla_Est='A' AND Pld_Est='A' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
		//echo $cargar_cuenta_249;
		return $cargar_cuenta_249;		
		break;	

		/* Insercion de un comprobante de Ingreso/Egreso (Cliente/Proveedor) */
		case 250:
		$ins_compi="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]')";//Antes Com_Tip
		//echo $ins_compi;
		return $ins_compi;
		break;

		/* Consulta el tipo de comprobante contable a emitir en la factura de compra */
		case 251:
		$ins_compi_251="SELECT Tia_Cod FROM form_compr WHERE For_Cod = $Par_Sql[0]";
		//echo $ins_compi_251;
		return $ins_compi_251;
		break;


		/* Consulta para determinar el iva pagado de un plan de cuentas */
		case 252:
		$iva_p_252 = "SELECT iva_pagado.Pld_Cod FROM det_plan INNER JOIN iva_pagado ON (det_plan.Pld_Cod = iva_pagado.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
		return $iva_p_252;
		break;

		/* Determina cuenta unica del proveedor en el plan de cuentas */
		case 253:
		$ccpp_prove_253 = "SELECT ccpp_prove.Pld_Cod, det_plan.Pld_Des, ccpp_prove.Ccp_Def, ccpp_prove.Ccp_Cxp FROM det_plan INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
		//echo $ccpp_prove_253;
		return $ccpp_prove_253;
		break;
		
		/* Inserta el codigo del comprobante y la compra para enlazar */
		case 254:
		$compras_compr_254 = "INSERT INTO compr_auto (Com_Cod, Cop_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
		return $compras_compr_254;
		break;
		
		/* Inserta la cuenta por pagar a proveedores */
		case 255:
		$cc_pp_prove_255 = "INSERT INTO ccpp_pagar (Com_Cod, Cop_Cod, Cpp_Ven, Cpp_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'))";
		//echo $cc_pp_prove_255;
		return $cc_pp_prove_255;
		break;
		
				/* Inserción de cada asiento del comprobante */
		case 256:
		$ins_asie_256="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
		//echo $ins_asie_256.'<br>';
		return $ins_asie_256;
		break;

		/* Consulta los bancos del plan de cuentas actual */
		case 257:
		$bancos_257="SELECT banco.Pld_Cod, det_plan.Pld_Des, banco.Ban_Cue, banco.Ban_Cod, banco.Ban_Tip FROM det_plan INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
			  INNER JOIN compr_plan ON (banco.Ban_Cod = compr_plan.Ban_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0] AND compr_plan.Pag_Cod = $Par_Sql[1]";
					  //echo $bancos_257;
		return $bancos_257;
		break;

		/* Consulta los tipos de pago de las compras */
		case 258:
		$pagos_compras_258="SELECT DISTINCT compr_plan.Pag_Cod, tipos_pago.Pag_Des, banco.Ban_Tip FROM tipos_pago INNER JOIN compr_plan ON (tipos_pago.Pag_Cod = compr_plan.Pag_Cod)
					  INNER JOIN banco ON (compr_plan.Ban_Cod = banco.Ban_Cod) WHERE tipos_pago.For_Cod = $Par_Sql[0]";
		//echo $pagos_compras_258;
		return $pagos_compras_258;	
		break;

		/* Consulta el detalle academico de la deuda */
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

		case 260:
		/*Consulta de los datos de la cabecera */
		$detalle_xml_260 = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, Cop_Imf, 	
Cop_Aut, Cop_Cad, Cop_Reg
FROM compras, sustento, proveedore, persona, identifica, tipo_compr
WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND 
proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND 
compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') 
AND compras.Cop_Est = 'A' AND (compras.Tic_Cod = 1 OR compras.Tic_Cod = 2 OR compras.Tic_Cod = 3 OR compras.Tic_Cod = 10 OR compras.Tic_Cod = 24 OR compras.Tic_Cod = 26) AND compras.Tri_Cod != 1";
//echo $detalle_xml_260;
		//echo $detalle_xml_260;
		return $detalle_xml_260;
		break;

		case 261:
		/* Consulta las modalidades */
		$modalidad_261 = "SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Est='A'";
		return $modalidad_261;
		break;

		/* Consulta en base al periodo, modalidad, etapa - relacionando con las deudas */
		case 262:
		$periodos_deudas_262="SELECT DISTINCT 
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin, view_periodos_suc.Per_Int
FROM
  notasgener
  INNER JOIN deudas ON (notasgener.Nge_Cod = deudas.Nge_Cod)
  INNER JOIN semestres ON (notasgener.Sem_Cod = semestres.Sem_Cod)
  INNER JOIN view_periodos_suc ON (semestres.Per_Int = view_periodos_suc.Per_Int)
WHERE view_periodos_suc.Suc_Cod = $Par_Sql[0] AND view_periodos_suc.Mod_Cod = $Par_Sql[1] AND view_periodos_suc.Eta_Cod = $Par_Sql[2]
ORDER BY view_periodos_suc.Per_Fea DESC";
		//echo $periodos_deudas_262;
		return $periodos_deudas_262;	
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

		/* Consulta el total de deudas del cleinte en base a la modalidad, etapa, carrera, periodo */
		case 264:
		$modalidades_cliente_264="SELECT 
  sum(deudas.Deu_Val) as Deu_Val, deudas.Bec_Cod
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
  Deu_Rec = 0 AND 
  periodos.Mod_Cod = $Par_Sql[1] AND 
  periodos.Eta_Cod = $Par_Sql[2] AND 
  promocione.Car_Int = $Par_Sql[3] AND 
  periodos.Per_Int = $Par_Sql[4]
GROUP BY deudas.Bec_Cod";
		//echo $modalidades_cliente_264;
		return $modalidades_cliente_264;	
		break;




 		/*  Consulta las carreras en base al periodo */ 
	   case 267:
	   $carrera_periodo_267= "SELECT DISTINCT 
  carreras.Car_Nom, carreras.Car_Int
FROM
  notasgener
  INNER JOIN deudas ON (notasgener.Nge_Cod = deudas.Nge_Cod)
  INNER JOIN semestres ON (notasgener.Sem_Cod = semestres.Sem_Cod)
  INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
  INNER JOIN carreras ON (promocione.Car_Int = carreras.Car_Int)
WHERE semestres.Per_Int = $Par_Sql[0]";
			//echo $carrera_periodo_267;
	   return $carrera_periodo_267;
	   break;

	/* Consulta de etapas académicas de tipo NIVELACION */
	case 268:
	$consulta_etapas_268="SELECT etapas.Eta_Cod, etapas.Eta_Rec, etapas.Eta_Des FROM etapas WHERE etapas.Eta_Rec<>0 AND etapas.Eta_Est='A' ORDER BY etapas.Eta_Des";
	return $consulta_etapas_268;
	break;

		case 269: 
		/* inserta centro de consumo */
		$ins_consumo_269 = "INSERT INTO consumo(Emp_Cod, Con_Des) VALUES ($Par_Sql[0],'$Par_Sql[1]')";	
		return $ins_consumo_269;
		break;
		
		case 270: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$ins_consumo_270= "SELECT Con_Cod, Con_Des FROM consumo WHERE Con_Des='$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo $ins_consumo_270;
		return $ins_consumo_270;
		break;

		case 271: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$con_consumo_des= "SELECT Con_Cod, Emp_Cod, Con_Des FROM consumo WHERE Con_Des LIKE '%$Par_Sql[0]%' ";	
//echo $con_consumo_des;

		return $con_consumo_des;
		break;
		
		case 272: 
		/* Busqueda de Codigo de gasto*/
		$con_consumo_cod= "SELECT Con_Cod, Emp_Cod,Con_Des,Con_Est FROM consumo WHERE Con_Cod=$Par_Sql[0]";	
		return $con_consumo_cod;
		break;
		
		case 273: 
		/* Actualizar el gasto*/
		$update_consumo= "UPDATE consumo SET Con_Des='$Par_Sql[1]' WHERE Con_Cod=$Par_Sql[0]";			
		return $update_consumo;
		break;

		case 274: 
		/* Para validar si existe un vehiculo con el mismo nombre*/
		$ins_vehiculo= "SELECT Veh_Cod FROM vehiculo WHERE Veh_Des='$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo  $ins_vehiculo;
		return $ins_vehiculo;
		break;
	
		case 275: 
		/* iserta vehiculo */
		$ins_vehiculo= "INSERT INTO vehiculo (Emp_Cod, Veh_Des, Veh_Mod, Veh_Pla) VALUES ($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";	
		//echo $ins_vehiculo;
		return $ins_vehiculo;
		break;

		case 276: 
		/* Para validar si existe un vehiculo con el mismo nombre*/
		$con_vehiculo= "SELECT Veh_Cod, Veh_Des, Veh_Mod, Veh_Pla FROM vehiculo WHERE Veh_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1]";	
		return $con_vehiculo;
		break;

		case 277: 
		/* Busqueda de vehiculo por codigo */
		$con_vehiculo= "SELECT Veh_Cod, Veh_Des, Veh_Mod, Veh_Pla, Veh_Est FROM vehiculo WHERE Veh_Cod=$Par_Sql[0]";	
		//echo $con_vehiculo;
		return $con_vehiculo;
		break;

		case 278: 
		/* Actualizar el vehiculo*/
		$update_vehiculos= "UPDATE vehiculo SET  Veh_Des='$Par_Sql[1]', Veh_Mod='$Par_Sql[2]', Veh_Pla='$Par_Sql[3]' WHERE Veh_Cod=$Par_Sql[0]";	
		
		return $update_vehiculos;
		break;

		case 279: 
		/* iserta ubicacion */
		$ins_ubicacion= "INSERT INTO ubicacion (Ubi_Des, Ubi_Obs) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";	
		return $ins_ubicacion;
		break;

		case 280: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$ins_gastos= "SELECT Ubi_Cod, Ubi_Des, Ubi_Obs, Ubi_Est FROM ubicacion WHERE Ubi_Des='$Par_Sql[0]'";	
		//echo  $ins_gastos;
		return $ins_gastos;
		break;

		case 281: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$con_ubicacion= "SELECT Ubi_Cod, Ubi_Des, Ubi_Obs, Ubi_Est FROM ubicacion WHERE Ubi_Des LIKE '%$Par_Sql[0]%'";	
		return $con_ubicacion;
		break;

		case 282: 
		/* Busqueda por Codigo de la ubicacion */
		$con_ubi_cod= "SELECT Ubi_Cod, Ubi_Des, Ubi_Obs, Ubi_Est FROM ubicacion WHERE Ubi_Cod=$Par_Sql[0]";	
		return $con_ubi_cod;
		break;

		case 283: 
		/* Actualizar la ubicacion */
		$update_ubi= "UPDATE ubicacion SET Ubi_Des='$Par_Sql[1]', Ubi_Obs='$Par_Sql[2]' WHERE Ubi_Cod=$Par_Sql[0]";			
		return $update_ubi;
		break;



		
/* ******************************************* ALTA DE CHEQUES ******************************************** */

	/* Setear el codigo del Proveedor VARIOS en la 301 - 302
	/* Búsqueda de un proveedor por apellido */
	case 301:
	$bus_proa_301="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Prs_Cod=persona.Prs_Cod";
	//echo $bus_proa_301;
	return $bus_proa_301;
	break;

	/* Búsqueda de un proveedor por Cédula */
	case 302:
	$bus_proc_302="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND proveedore.Prs_Cod=persona.Prs_Cod";
	return $bus_proc_302;
	break;
	
	/* Cargado del Periodo Contable activo */
	case 303:
	$cargar_percon="SELECT Pec_Cod FROM perio_cont WHERE Now() BETWEEN Pec_Fei AND Pec_Fef AND Pec_Est='A'";
	return $cargar_percon;
	break;

	/* Cargado de los bancos que van a ser agregados al combobox */
	case 304:
	$cargar_combo_304="SELECT CONCAT(Ban_Cod,'*',asientos.Asi_Cod) as Banasi, det_plan.Pld_Des, asientos.Asi_Val FROM asientos, banco, det_plan WHERE asientos.Pld_Cod=banco.Pld_Cod AND banco.Pld_Cod=det_plan.Pld_Cod AND asientos.Com_Cod=$Par_Sql[0] AND asientos.Asi_Deh = 'H' 
				AND det_plan.Pld_Cod != 2922 AND banco.Ban_Tip = 'B'";
	//echo $cargar_combo_304;
	return $cargar_combo_304;
	break;
	
	/* Cargado de la cabecera del comprobante, sea este de cualquier tipo */
	case 305:
	$cargar_cabcomp_305="SELECT Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Com_Num='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
AND comprobantes.Com_Cod NOT IN
(SELECT asientos.Com_Cod FROM asientos, cheques WHERE asientos.Asi_Cod=cheques.Asi_Cod)";
	//echo $cargar_cabcomp_305;
	return $cargar_cabcomp_305;
	break;
	
	/* Cargado de las cuentas del comprobante (Resumen)*/
	case 306:
	$cargar_cuentas="SELECT asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod";
	return $cargar_cuentas;
	break;
	
	/* Inserción de los cheques de los comprobantes de egreso */
	case 307:
	$ins_cheques_307="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]', Che_Val=$Par_Sql[4], Che_Obs=UPPER('$Par_Sql[5]'), Che_Fec='$Par_Sql[6]', Che_Cod = $Par_Sql[7]";
	//echo $ins_cheques_307."<br>";
	return $ins_cheques_307;
	break;
		
	/* Consulta para verificar si existen cheques ingresados y redirigirlos a modificacion de cheques */
	case 308:
	$cons_cheques="SELECT cheques.Asi_Cod FROM asientos, cheques WHERE asientos.Asi_Cod=cheques.Asi_Cod AND asientos.Com_Cod=$Par_Sql[0]";
	return $cons_cheques;
	break;
	
	/* Carga de los cheques de un comprobante determinado */
	case 309:
	$car_cheques="SELECT Ban_Cod, cheques.Asi_Cod, persona.Prs_Ape, persona.Prs_Nom, cheques.Prv_Cod, Che_Num, Che_Val, Che_Fec, Che_Cob, Che_Obs, Che_Cod FROM asientos,cheques,proveedore,persona WHERE asientos.Asi_Cod=cheques.Asi_Cod AND asientos.Com_Cod=$Par_Sql[0] AND cheques.Prv_Cod=proveedore.Prv_Cod AND proveedore.Prs_Cod=persona.Prs_Cod";
	return $car_cheques;
	break;
		
	/* Carga de los cheques de un comprobante determinado */
	case 310:
	$del_cheques_310="DELETE FROM cheques WHERE Asi_Cod=$Par_Sql[0]";
//	echo $del_cheques_310;
	return $del_cheques_310;
	break;

	/* Cargado de la cabecera del comprobante, sea este de cualquier tipo */
	case 311:
	$cargar_cheques_311="SELECT Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
AND comprobantes.Com_Cod NOT IN
(SELECT asientos.Com_Cod FROM asientos, cheques WHERE asientos.Asi_Cod=cheques.Asi_Cod AND cheques.Che_Est='A')";
	//echo $cargar_cheques_311;
	return $cargar_cheques_311;
	break;

	/* Consulta de los comprobantes que estan en la tabla cheques */
	case 312:
	$cargar_cheques_312="SELECT comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona, asientos, cheques WHERE Prs_Ape like '%$Par_Sql[1]%' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
AND comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Che_Est='A'
GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est";
	//echo $cargar_cheques_312;
	return $cargar_cheques_312;
	break;

	/* Consulta de los comprobantes que estan en la tabla cheques en base al codigo*/
	case 313:
	$cargar_cabcomp_313="SELECT comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Est FROM comprobantes, $Par_Sql[0], persona, asientos, cheques WHERE Com_Num='$Par_Sql[1]' AND Tia_Cod='$Par_Sql[2]' AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' $Par_Sql[5]
AND comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Che_Est='A'
GROUP BY comprobantes.Com_Cod, Com_Num, proveedore.Prv_Cod, Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
Com_Con, Com_Obs, Com_Fec, Com_Val, Com_Est";
	//echo $cargar_cabcomp_313;
	return $cargar_cabcomp_313;
	break;

	/* Consulta los proveedores que pueden recibir varios cheques */
	case 314:
	$cheques_varios_314="SELECT Prv_Cod FROM varicheque";
//echo $cheques_varios_314;
	return $cheques_varios_314;
	break;
	
	case 315: 
	/* insertar datos en el tipo de pago */
	$inser_pago_315 = "INSERT INTO pago_venta (Vet_Cod, Bak_Cod, Ban_Cod, Pag_Cod, Vet_Cue, Vet_Che, Vet_Tot, Vet_Num) 
	VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], '$Par_Sql[7]')";
	//echo  $inser_pago_315;
	return $inser_pago_315;
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
	/* Consulta del banco del plan de cuenta */
	$inser_pago_317 = "SELECT det_plan.Pld_Des FROM det_plan, banco, pago_venta WHERE det_plan.Pld_Cod = banco.Pld_Cod AND pago_venta.Ban_Cod = banco.Ban_Cod
			AND pago_venta.Vet_Cod = '$Par_Sql[0]' AND pago_venta.Vet_Num = '$Par_Sql[1]' AND pago_venta.Ban_Cod='$Par_Sql[2]'";
	//echo  $inser_pago_317;
	return $inser_pago_317;
	break;

	case 318: 
	/* Consulta de otros bancos */
	$inser_pago_318 = "SELECT bancos.Bak_Des FROM bancos, pago_venta WHERE pago_venta.Bak_Cod = bancos.Bak_Cod
			AND pago_venta.Vet_Cod = '$Par_Sql[0]' AND pago_venta.Vet_Num = '$Par_Sql[1]' AND pago_venta.Bak_Cod='$Par_Sql[2]'";
	//echo  $inser_pago_318;
	return $inser_pago_318;
	break;

	case 319:
	/* Consulta los años de las facturas de ventas recibidas */
	$anios_243 = "SELECT YEAR(caja_aper.Caj_Fec) as Anio FROM ventas, caja_aper WHERE ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas.Tic_Cod=$Par_Sql[0] AND Ventas.Vet_Est='A' 
				GROUP BY YEAR(caja_aper.Caj_Fec) ORDER BY YEAR(caja_aper.Caj_Fec) DESC";
				//echo $anios_243;
	return $anios_243;
	break;

	case 320:
	/* Consulta las facturas de la caja activa para modificarlas */
	$mod_factura_320 = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est,
					  ventas.Vet_Est FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
					  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND 
					  ventas.Tic_Cod = $Par_Sql[1]  AND caja_aper.Pun_Cod = $Par_Sql[2] AND 
					  YEAR(caja_aper.Caj_Fec) = '$Par_Sql[3]' $Par_Sql[4]  ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC"; //Antes AND caja_aper.Caj_Cod NOT IN (SELECT caja_compr.Caj_Cod FROM caja_compr)
					 // echo $mod_factura_320;
	return $mod_factura_320;
	break;

	case 321:
	/* Consulta de las facturas por el codigo interno de la caja activa */
	$mod_factura_int_321 = "SELECT cliente.Cli_Cod, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, cliente.Cli_Est, ventas.Vet_Est
					  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod) INNER JOIN cliente ON (cliente.Cli_Cod = 
					  ventas.Cli_Cod) INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE ventas.Vet_Num = '$Par_Sql[0]' AND 
					  ventas.Tic_Cod = $Par_Sql[1]          AND caja_aper.Pun_Cod = $Par_Sql[2] 
					    ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num DESC";//Antes AND caja_aper.Caj_Cod NOT IN (SELECT caja_compr.Caj_Cod FROM caja_compr)
					  //echo $mod_factura_int_321;
	return $mod_factura_int_321;
	break;


	/* Borrado del codigo del detalle de la Venta */
	case 322:
	$borrar_pago_322="DELETE FROM pago_venta WHERE Vet_Cod='$Par_Sql[0]'";
	return $borrar_pago_322;
	break;

		case 323:
		$impo_sum_fac_comp_323="SELECT SUM(det_compra.Cop_Imp-(det_compra.Cop_Imp*det_compra.Cop_Dec/100)) as Importe, compras.Cop_Des, iva.Iva_Cod, iva.Iva_Por, adquisicio.Adq_Des, adquisicio.Adq_Cod 
FROM det_compra, iva, adquisicio, compras
WHERE det_compra.Cop_Cod='$Par_Sql[0]' AND det_compra.Iva_Cod=iva.Iva_Cod 
AND adquisicio.Adq_Cod=det_compra.Adq_Cod AND adquisicio.Adq_Cod='$Par_Sql[1]' 
AND compras.Cop_Cod=det_compra.Cop_Cod
AND det_compra.Iva_Cod='$Par_Sql[2]' 
GROUP BY adquisicio.Adq_Cod";
		//echo $impo_sum_fac_comp_323;
		return $impo_sum_fac_comp_323;
		break;


		/*** Consulto los tipos de adquisiciones en la base de datos ******/
		case 324:
		$consulta_adquisiciones_324="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Des FROM compras, det_compra, adquisicio WHERE 
$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
AND det_compra.Adq_Cod=adquisicio.Adq_Cod GROUP BY adquisicio.Adq_Cod ORDER BY adquisicio.Adq_Des ASC";
       //echo $consulta_adquisiciones_324;
		return $consulta_adquisiciones_324;
		break;
        
	/*** consultar los tipos de adquisiciones realizadas en una determinada compra  ****/
        case 325:
		$consultar_adquisicion_compra_325="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor FROM det_compra,adquisicio 
		WHERE det_compra.Adq_Cod=adquisicio.Adq_Cod AND det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'   ";
		return $consultar_adquisicion_compra_325;
		break;
  
	/** Consultar facturas sin retención ********/
		case 326:
		$consulta_facturas_sr_326="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
			compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut,
			compras.Tri_Cod, sustento.Tri_Des, 
			SUM(det_compra.Cop_Imp) as Cop_Imp, compras.Cop_Des,
			
			SUM(det_compra.Cop_Imp-(det_compra.Cop_Imp*det_compra.Cop_Dec/100)) as Importe
			
			FROM persona, proveedore,compras, det_compra, sustento, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod
			AND compras.Tri_Cod='$Par_Sql[4]'
			AND sustento.Tri_Cod = compras.Tri_Cod
			AND tipo_compr.Tic_Cod=compras.Tic_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Cop_Fec BETWEEN
			'$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
			AND $Par_Sql[2] 
			AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM compras,retencion  WHERE retencion.Cop_Cod=compras.Cop_Cod)
			GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";        
			//echo $consulta_facturas_sr_326;
		return $consulta_facturas_sr_326;
		break;


		/* sentencias actualizacion de codigos de retención */
      case 327:
		$consulta_retencion_ene_feb_327="SELECT retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, renta_iva.Ren_Cod ,renta_iva.Ren_Sri FROM retencion, det_retenc, renta_iva
WHERE retencion.Ret_Cod=det_retenc.Ret_Cod AND
det_retenc.Ren_Cod=renta_iva.Ren_Cod AND retencion.Ret_Fec >='2009-01-01' AND retencion.Ret_Fec <='2009-03-26' AND renta_iva.Ren_Cod='$Par_Sql[0]'";
		///echo $consulta_retencion_ene_feb_327;
		return $consulta_retencion_ene_feb_327;
		break;
		/* actualizacion de codigos para Enero & Febrero del 2009 */
		case 328:
		$actualiza_retencion_ene_feb_327="UPDATE det_retenc SET Ren_Cod='$Par_Sql[1]' WHERE Ret_Int='$Par_Sql[0]'   ";
		return $actualiza_retencion_ene_feb_327;
		break;

		/* consulto los años para la consulta de las retenciones */
		case 329:
		$consulta_anio_retencion_329="SELECT YEAR(retencion.Ret_Fec) AS Anio, renta_iva.Ren_Cod, renta_iva.Ren_Por  FROM retencion, det_retenc, renta_iva
WHERE retencion.Ret_Cod=det_retenc.Ret_Cod
AND det_retenc.Ren_Cod=renta_iva.Ren_Cod
GROUP BY YEAR(retencion.Ret_Fec) ORDER BY YEAR(retencion.Ret_Fec) DESC";
		return $consulta_anio_retencion_329;
		break;
		/* consulto los años de las retenciones por parámetros */
		case 330:
		$consulto_anio_reten_parametro_330="SELECT YEAR(retencion.Ret_Fec) AS Anio, renta_iva.Ren_Cod, renta_iva.Ren_Por FROM retencion, det_retenc, renta_iva
WHERE retencion.Ret_Cod=det_retenc.Ret_Cod
AND det_retenc.Ren_Cod=renta_iva.Ren_Cod AND YEAR(retencion.Ret_Fec)='$Par_Sql[0]' 
GROUP BY YEAR(retencion.Ret_Fec) ORDER BY YEAR(retencion.Ret_Fec) DESC";
		//echo $consulto_anio_reten_parametro_330;
		return $consulto_anio_reten_parametro_330;
		break;
		/* consulta de codigos de formularios del SRI y los conceptos en la retención en la fuente de impuesto a la renta (AIR) */
		case 331:
		$carg_ret_des_imp_331="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con 
FROM renta_iva, retencion, det_retenc 
WHERE   renta_iva.Ren_Est='A' 
        AND  det_retenc.Ren_Cod=renta_iva.Ren_Cod
        AND retencion.Ret_Cod=det_retenc.Ret_Cod
        AND YEAR(retencion.Ret_Fec)='$Par_Sql[0]'
GROUP BY renta_iva.Ren_Cod 
ORDER BY renta_iva.Ren_Sri";
		//echo $carg_ret_des_imp_331;
		return $carg_ret_des_imp_331;
		break;

		/* consulta de retenciones activas e inactivas buscadas por número de comprobante de retención */
		case 332:
		$carga_retenci_modif_332="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Cod, retencion.Ret_Est, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod, autorizaci.Aut_Sri FROM persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci 
WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND retencion.Ret_Num='$Par_Sql[0]' 
AND retencion.Ret_Fec>='$Par_Sql[2]'
AND retencion.Ret_Fec<='$Par_Sql[3]' AND autorizaci.Aut_Cod= retencion.Aut_Cod AND proveedore.Emp_Cod = 5
GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
      //  echo $carga_retenci_modif_332;
		return $carga_retenci_modif_332;
		break;
		/** Consulta de retenciones activas e inactivas buscadas por apellidos *********************/
		case 333:
		$carga_reten_modif_fac_333="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod, autorizaci.Aut_Sri FROM autorizaci, persona, proveedore, compras, retencion, det_retenc, renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ape LIKE '$Par_Sql[0]%' 
AND retencion.Ret_Fec>='$Par_Sql[2]'
AND retencion.Ret_Fec<='$Par_Sql[3]' AND autorizaci.Aut_Cod= retencion.Aut_Cod AND proveedore.Emp_Cod = 5
GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
      //  echo $carga_reten_modif_fac_333;
		return $carga_reten_modif_fac_333;
		break;

		/* consulta si la factura de compra ya se encuentra registrada */
		case 334:
		$consulta_existe_factura_334="SELECT compras.Cop_Num FROM compras WHERE compras.Cop_Num='$Par_Sql[0]' AND compras.Prv_Cod='$Par_Sql[1]' AND compras.Tic_Cod='$Par_Sql[2]' $Par_Sql[3] AND compras.Cop_Est='A'";
		//echo $consulta_existe_factura_334;
		return $consulta_existe_factura_334;
		break;


	/**
	* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR)  
	*/
 // case 338:
 // $carg_rentaiva_adq_338="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
 // FROM renta_iva, reniva_pla, det_plan WHERE renta_iva.Ren_Ret='$Par_Sql[1]' 
 // AND renta_iva.Ren_Cod=reniva_pla.Ren_Cod AND reniva_pla.Pld_Cod=det_plan.Pld_Cod AND det_plan.Pla_Cod='$Par_Sql[2]'  AND  renta_iva.Ren_Est='A'  ORDER BY renta_iva.Ren_Sri   ";
//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
  //echo $carg_rentaiva_adq_338;
 // return $carg_rentaiva_adq_338;
 // break;
		case 338:
 		$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
		  FROM renta_iva, reniva_pla, det_plan WHERE renta_iva.Ren_Ret='$Par_Sql[1]' 
		  AND renta_iva.Ren_Cod=reniva_pla.Ren_Cod AND reniva_pla.Pld_Cod=det_plan.Pld_Cod AND det_plan.Pla_Cod='$Par_Sql[2]'  AND  					
		  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%'  ORDER BY renta_iva.Ren_Sri";
	  	return $sql;
		break;





	/* Consulto el detalle de la cuenta contable relacionda con el detalle de la factura de compra */
	case 343:
	$consultar_cuenta_detplan_343="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Des FROM det_compra,det_plan 
	WHERE det_compra.Pld_Cod=det_plan.Pld_Cod AND det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'   ";
	//echo $consultar_cuenta_detplan_343;
	return $consultar_cuenta_detplan_343;
	break;


	/* consulta los detalles de las retenciones OJOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOXOXOXOXOXOXOXOXOXOXXXXXXXXX 20/08/2009  */
	case 344:
	$det_compra_renta_344 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por, (Ret_Bas * Ren_Por)/100 as Val_Ret 
FROM det_retenc, renta_iva 
WHERE det_retenc.Ren_Cod = renta_iva.Ren_Cod 
AND det_retenc.Ret_Int='$Par_Sql[0]' AND det_retenc.Ret_Imp='$Par_Sql[1]' AND  det_retenc.Ret_Cod='$Par_Sql[2]'  ";
	//echo $det_compra_renta_344;
	return $det_compra_renta_344;
	break;

	 
  			/* consulta el comprobante de la factura de compra */
	case 345:
	$consulta_comprobante_compra_345="SELECT  comprobantes.Tia_Cod, comprobantes.Com_Con,  comprobantes.Com_Num,comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Val FROM compras, compr_auto,comprobantes  WHERE compr_auto.Cop_Cod=compras.Cop_Cod AND compr_auto.Com_Cod=comprobantes.Com_Cod AND compras.Cop_Cod='$Par_Sql[0]' ";
	//echo $consulta_comprobante_compra_345;
	return $consulta_comprobante_compra_345;
	break;

	/* consulta los cheques generados para la factura de compra */
 case 346:
	 $consultar_asiento_compras_346="SELECT det_plan.Pld_Cod, det_plan.Pld_Des, asientos.Asi_Cod , asientos.Com_Cod, asientos.Asi_Con , asientos.Asi_Val FROM asientos, det_plan, compras, 
comprobantes, compr_auto
WHERE 
asientos.Asi_Deh='H'
AND asientos.Pld_Cod=det_plan.Pld_Cod
AND compras.Cop_Cod=compr_auto.Cop_Cod
AND comprobantes.Com_Cod=compr_auto.Com_Cod
AND comprobantes.Com_Cod=asientos.Com_Cod
AND compras.Cop_Cod='$Par_Sql[0]' 
AND (asientos.Pld_Cod NOT IN (SELECT reniva_pla.Pld_Cod FROM reniva_pla))
AND (asientos.Pld_Cod  NOT IN (SELECT ccpp_prove.Pld_Cod FROM ccpp_prove))";
  // echo $consultar_asiento_compras_346;
	return $consultar_asiento_compras_346;
	break;


/* Consulto los bancos considerando 'O' - 'B' */
case 347:
$consulta_bancos_347="SELECT banco.Ban_Cod,det_plan.Pld_Cod, det_plan.Pld_Des, banco.Ban_Tip FROM banco, det_plan 
WHERE banco.Pld_Cod=det_plan.Pld_Cod AND banco.Ban_Tip='$Par_Sql[0]' AND banco.Ban_Fac='A'";
//echo $consulta_bancos_347;
return $consulta_bancos_347;
break;

	/* elimino de la base de datos el registro de compra a modificar */
	case 348:
	$delete_det_compra_348="DELETE FROM det_compra WHERE Cop_Cod='$Par_Sql[0]' ";
	return $delete_det_compra_348; 
	break;

	/* consulto la forma de pago del comprobante de compra [OJOPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP] */
	case 349:
	$form_pago_compra_349="SELECT form_compr.For_Cod,comprobantes.Com_Cod, forma_pago.For_Des FROM comprobantes, compr_auto,  form_compr, forma_pago
WHERE 
forma_pago.For_Cod=form_compr.For_Cod
AND comprobantes.Com_Cod=compr_auto.Com_Cod 
AND form_compr.Tia_Cod=comprobantes.Tia_Cod
AND form_compr.For_Cod=form_compr.For_Cod
AND compr_auto.Cop_Cod='$Par_Sql[0]'";
//echo $form_pago_compra_349;
	return $form_pago_compra_349;
	break;

	/* consulto si es compra a credito cargo los datos */
	case 350:
	$consulta_compra_credito_350="SELECT Cpp_Cod, Cpp_Ven, Cpp_Obs FROM ccpp_pagar WHERE Com_Cod='$Par_Sql[0]'";
	//echo $consulta_compra_credito_350;
	return $consulta_compra_credito_350;
	break;


/* elimino el asiento del comprobante */
 case 353:
 $delete_asiento_353="DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
//echo $delete_asiento_353;
 return $delete_asiento_353;
 break;
 /* Actualizo los datos de la cabecera de la retención */
 case 354:
$actualizo_cabecera_retencion_354="UPDATE retencion SET Cop_Cod='$Par_Sql[0]', 
Ret_Num='$Par_Sql[1]', Ret_Fec='$Par_Sql[2]',Ret_Con=UPPER('$Par_Sql[3]'), Tic_Cod='$Par_Sql[4]',Vnd_Cod='$Par_Sql[5]',Aut_Cod='$Par_Sql[6]' WHERE retencion.Ret_Cod=$Par_Sql[7]";
return $actualizo_cabecera_retencion_354;
break;
 /* elimino el detalle de la retencion */
case 355:
$delete_detalle_retencion_355="DELETE FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
return $delete_detalle_retencion_355;
break;

	/* actualizo las cuentas por pagar al usuario */
	case 356:
	$inserta_cc_pp_356="INSERT INTO ccpp_pagar (Com_Cod, Cop_Cod, Cpp_Ven, Cpp_Obs, Cpp_Cod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'), '$Par_Sql[4]')";
	//echo $inserta_cc_pp_356;
	return $inserta_cc_pp_356;
	break;

	/* consulto si existen pagos relizados por una compra realizada*/
	case 357:
	$consulta_pagos_compra_357="SELECT ccpp_pagar.Cpp_Cod FROM   ccpp_pagar  INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
	 WHERE det_ccpp_p.Pag_Est='A' AND ccpp_pagar.Com_Cod = $Par_Sql[0]";
	//$consulta_pagos_compra_357="SELECT det_ccpp_p.Pag_Val FROM det_ccpp_p WHERE det_ccpp_p.Com_Cod='$Par_Sql[0]'";
	//echo $consulta_pagos_compra_357;
	return $consulta_pagos_compra_357;
	break;
	



/* Problema desde aqui */




	/* consulto si el codigo del comprobante de compra */
	case 358:
	$consulta_cod_comprobante_358="SELECT comprobantes.Com_Cod FROM comprobantes, compr_auto WHERE comprobantes.Com_Cod=compr_auto.Com_Cod AND compr_auto.Cop_Cod='$Par_Sql[0]'";
	return $consulta_cod_comprobante_358;
	break;


	/* Baja lógica del comprobante en la base de datos*/
	case 359:
	$baja_log_comprobante_359="UPDATE comprobantes SET Com_Est='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]' ";
	return $baja_log_comprobante_359;
	break;

	case 360:
	$consulta_cuenta_relacion_ret_360="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, reniva_pla.Pld_Cod  FROM renta_iva, reniva_pla WHERE 
			 renta_iva.Ren_Cod=$Par_Sql[0] AND reniva_pla.Ren_Cod=renta_iva.Ren_Cod";
	return $consulta_cuenta_relacion_ret_360;
	break;
	
	/* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) Ojoooooooooooooooooooooooooo 
	renta_iva.Adq_Cod='$Par_Sql[0]' AND*/
	case 361:
	$carg_rentaiva_adq_361="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
	FROM renta_iva, reniva_pla, det_plan WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
	AND renta_iva.Ren_Cod=reniva_pla.Ren_Cod AND reniva_pla.Pld_Cod=det_plan.Pld_Cod AND det_plan.Pla_Cod='$Par_Sql[2]'  AND  renta_iva.Ren_Est='A'  
	AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri   ";
//	echo $carg_rentaiva_adq_361;
	return $carg_rentaiva_adq_361;
	break;
		/* Consulto el código del detalle de la cuenta perteneciente al comprobante de compra */
	case 362:
	$consulta_cuenta_comprobante_362="SELECT comprobantes.Com_Cod, det_plan.Pld_Cod, det_plan.Pld_Des, ccpp_prove.Ccp_Cxp FROM ccpp_prove, det_plan, asientos, comprobantes, compr_auto
WHERE ccpp_prove.Pld_Cod=det_plan.Pld_Cod AND asientos.Pld_Cod=det_plan.Pld_Cod
AND comprobantes.Com_Cod=asientos.Com_Cod 
AND comprobantes.Com_Cod=compr_auto.Com_Cod AND compr_auto.Cop_Cod='$Par_Sql[0]'";
	//echo $consulta_cuenta_comprobante_362;
	return $consulta_cuenta_comprobante_362;
	break;

	/* Consultar el detalle del comprobante de compra/asientos del comprobante */
	case 364:
	$consulta_detalle_comprobante_364="SELECT Asi_Cod, Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
	//echo $consulta_detalle_comprobante_364;
	return $consulta_detalle_comprobante_364;
	break;

	
	/* elimino de la base de datos los cheques del asiento contable */
	case 365:
	$delete_cheques_365="DELETE FROM cheques WHERE Asi_Cod='$Par_Sql[0]' ";
	return $delete_cheques_365; 
	break;

	/* consulto el numero del comprobante de compra */
	case 366:
	$consulta_numero_comprobante_366="SELECT comprobantes.Com_Num FROM comprobantes, compr_auto WHERE comprobantes.Com_Cod=compr_auto.Com_Cod AND compr_auto.Cop_Cod='$Par_Sql[0]' ";
	return $consulta_numero_comprobante_366;
	break;
	
	/* consulta los cheques generados para la factura de compra */
	case 367:
	$consulta_cheque_compra_367="SELECT banco.Ban_Cod,banco.Ban_Tip, banco.Pld_Cod, cheques.Che_Cod, cheques.Che_Num,  cheques.Che_Fec, cheques.Che_Obs, 
	cheques.Che_Val FROM cheques,  banco,  asientos 
	WHERE 	cheques.Ban_Cod=banco.Ban_Cod
	 		AND asientos.Asi_Cod=cheques.Asi_Cod
			AND asientos.Asi_Cod='$Par_Sql[0]' ";
    //echo $consulta_cheque_compra_367;
    return $consulta_cheque_compra_367;
	break;
	/* Consultar los bancos */			
	case 368:
	$consultar_bancos_compra_368="SELECT banco.Ban_Cod, banco.Pld_Cod, det_plan.Pld_Des  FROM banco, det_plan WHERE  Ban_Tip!='B' AND det_plan.Pld_Cod=banco.Pld_Cod";
	//echo $consultar_bancos_compra_368;
	return $consultar_bancos_compra_368;
	break;
	/* Consulta que permite saber si la compra es automática o manual */
	
	case 369:
	$consultar_automatica_manual_369="SELECT compr_auto.Com_Cod FROM compr_auto WHERE compr_auto.Com_Cod = $Par_Sql[0]";						
	//echo $consultar_automatica_manual_369;
	return $consultar_automatica_manual_369;
	break;
/* Consulta si la cuenta se encuentra en la tabla Banco */
	case 370:
	$consultar_cuenta_banco_existe_370="SELECT Ban_Cod FROM banco WHERE Pld_Cod='$Par_Sql[0]'";
	return $consultar_cuenta_banco_existe_370;
	break;
	
	/* Consultar la cuenta del plan en base al código*/
	case 371:
	$consultar_cuenta_plan_371="SELECT det_plan.Pld_Cod, det_plan.Pla_Cod, det_plan.Pl ";
	return $consultar_cuenta_plan_371;
	break;
	/* Consultar los datos de banco */
	
	case 372:
	$consultar_datos_banco_372="SELECT banco.Ban_Cod,banco.Ban_Tip, banco.Pld_Cod FROM banco WHERE banco.Pld_Cod='$Par_Sql[0]'";
	return $consultar_datos_banco_372;
	break;
	
	/* Consultar las retencion sin considerar el estado en la BD */
	case 373:
	$consultar_renta_estado_datos_373="SELECT retencion.Ret_Cod, retencion.Ret_Num FROM retencion WHERE  retencion.Cop_Cod='$Par_Sql[0]'";
	//echo $consultar_renta_estado_datos_373;
	return $consultar_renta_estado_datos_373;
	break;

	/*Consultar las cuentas por pagar de un proveedor por factura de compra */
   case 374:
   $consultar_cc_pp_factura_374="SELECT ccpp_pagar.Cpp_Cod, ccpp_pagar.Cop_Cod, ccpp_pagar.Com_Cod, ccpp_pagar.Cpp_Ven, ccpp_pagar.Cpp_Obs  
   FROM ccpp_pagar WHERE ccpp_pagar.Com_Cod='$Par_Sql[0]' ";
   return $consultar_cc_pp_factura_374;
   break;
   /* Consultar los cheques que tiene asignado un asiento contable  */
     case 375:
	 $consulta_asiento_cheque_375="SELECT  cheques.Che_Cod, cheques.Prv_Cod, cheques.Ban_Cod, cheques.Asi_Cod, cheques.Che_Num, cheques.Che_Fec, cheques.Che_Val, cheques.Che_Obs FROM  cheques WHERE Asi_Cod='$Par_Sql[0]'   ";
	 return $consulta_asiento_cheque_375;
	 break;

	 /* Consulta si existe la retencion ya registrada en el mismo punto de venta */
	 /* Quite la restricción a que la retención sea solo duplicada por la persona que registro la compra retencion.Vnd_Cod=$Par_Sql[0] AND */
     	 case 376:
	 $consulta_num_renta_registrada_376="SELECT retencion.Vnd_Cod, retencion.Ret_Cod FROM retencion WHERE  retencion.Ret_Num='$Par_Sql[1]' AND retencion.Aut_Cod=$Par_Sql[2] AND retencion.Ret_Est='A'";
	 //echo $consulta_num_renta_registrada_376; 	
	 return $consulta_num_renta_registrada_376;
	 break; 



	 /* Consulto el punto de venta  */	 
	 case 377:
	 $consultar_pvta_377="SELECT autorizaci.Aut_Cod,autorizaci.Pun_Cod  FROM autorizaci WHERE Pun_Cod='$Par_Sql[0]' AND Tic_Cod=6";
	 return $consultar_pvta_377;
	 break;
	 /* Consulto la autorización para la retención */
	  
	 case 378:
	 $consulta_autorizacion_renta_378="SELECT autorizaci.Aut_Cod,  autorizaci.Pun_Cod, autorizaci.Tic_Cod, autorizaci.Aut_Ini, autorizaci.Aut_Fin FROM autorizaci 
	 WHERE autorizaci.Pun_Cod=$Par_Sql[0] AND autorizaci.Tic_Cod=$Par_Sql[1] AND autorizaci.Aut_Est = 'A'";
	 //echo $consulta_autorizacion_renta_378;
	 return $consulta_autorizacion_renta_378;
	 break;

	 	 /* Consultar si la retención no se repite en la modificación */
	 /* Quite esta clausula para que no registra la modificacion que requiera hacer otro usuario vendedor  retencion.Vnd_Cod=$Par_Sql[0] AND*/
	 case 379:
	 $consultar_retencion_modificar_379="SELECT retencion.Vnd_Cod, retencion.Ret_Cod FROM retencion WHERE  retencion.Ret_Num<>$Par_Sql[1] AND retencion.Aut_Cod=$Par_Sql[2] AND retencion.Ret_Num=$Par_Sql[3]"; //Antes AND retencion.Ret_Est='A'
	 //echo $consultar_retencion_modificar_379;
	 return $consultar_retencion_modificar_379;
	 break;
	 	
 /* Consultar si la retención es automática */
	 case 380:
	 $consultar_automatica_manual_380="SELECT compr_auto.Com_Cod FROM compr_auto WHERE compr_auto.Cop_Cod = $Par_Sql[0]";						
	 //echo $consultar_automatica_manual_380;
	 return $consultar_automatica_manual_380;
	 break;
	 /* Consultar el detalle de la retención */
	 case 381:
	 $consultar_automatica_manual_381="SELECT det_retenc.Ret_Int, det_retenc.Ret_Cod, det_retenc.Ret_Bas, renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por,renta_iva.Ren_Con  
	 FROM det_retenc, renta_iva  WHERE  det_retenc.Ren_Cod=renta_iva.Ren_Cod AND  det_retenc.Ret_Cod = $Par_Sql[0] ";
	// echo $consultar_automatica_manual_381;
	 return $consultar_automatica_manual_381;
	 break;

	/* Consultar si la compra tiene retenciones anuladas */
	 case 382:
	 $consultar_retenc_eliminada_382="SELECT retencion.Ret_Cod FROM retencion WHERE Cop_Cod=$Par_Sql[0] AND retencion.Ret_Est='I'";
	 return $consultar_retenc_eliminada_382;
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
	 
 	/* Consulta del esquema sin recursividad*/
	case 386:
	$esquema_386 = "SELECT Esq_Cod, Esq_Des, Esq_Xml, Esq_Ord FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] ORDER BY Esq_Ord ASC";		
	//echo $esquema_386;
	return $esquema_386;
	break; 	
		
	/* Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional */
	case 387:
	$comprobantes_387="	SELECT DISTINCT 
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
						  persona.Prs_Ced,
						  cliente.Cli_Cod,
						  persona.Prs_Cod,
						  identifica.Ide_Prv
						ORDER BY
						  persona.Prs_Ced";
	//echo $comprobantes_387;
	return $comprobantes_387;
	break;
	
	/* Consultando la cabecera de Facturas*/
	case 388:
	$cabecera_388= "SELECT 
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
	return $cabecera_388;
	break;
	
	/* Consultando los Detalles de Ventas de Factura*/
	case 389:
	$comprobantes_389="	SELECT 
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
         
	//echo $comprobantes_389;
	return $comprobantes_389;
	break;
	
	/* Consultando las facturas Anuladas en un mes y año determinado Anexos Transaccionales 2010*/
	case 390:
	$comprobantes_390="	SELECT 
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
		
	//echo $comprobantes_390;
	return $comprobantes_390;
	break;
	 	
	//Busqueda de gastos
	case 394:
	$Sql_394="SELECT Gas_Cod, Gas_Des, Gas_Cor, Gas_Max, Gas_Est, Gas_Det FROM gastos WHERE Gas_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1] AND Gas_Est='A' ORDER BY Gas_Des ASC";
	//echo $Sql_394;
	return $Sql_394;
	break;


	/* Búsqueda de un personal Receptor caja chica por apellido */
	case 395:
	$Sql_395="SELECT 
				  receptor.Rec_Cod,
				  personal.Per_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  if(receptor.Rec_Est='I','INACTIVO','ACTIVO')AS Estado
			  FROM
				  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
				  INNER JOIN receptor ON (distributi.Dis_Cod = receptor.Dis_Cod)
			  WHERE
				  Prs_Ape LIKE '%$Par_Sql[0]%' AND receptor.Emp_Cod=$Par_Sql[1]";//Fin del AND receptor.Rec_Est = 'A'
	return $Sql_395;
	break;
	
	/* Búsqueda de un personal Receptor caja chica por cedula */
	case 396:
	$Sql_396="SELECT 
				  receptor.Rec_Cod,
				  personal.Per_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  if(receptor.Rec_Est='I','INACTIVO','ACTIVO')AS Estado
			  FROM
				  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
				  INNER JOIN receptor ON (distributi.Dis_Cod = receptor.Dis_Cod)
			  WHERE
				  personal.Prs_Ced=$Par_Sql[0] AND receptor.Emp_Cod=$Par_Sql[1] AND receptor.Rec_Est = 'A'";		
	return $Sql_396;
	break;


	//Busqueda de la personas autorizadas para dar caja chica
	case 397:
	$Sql_397="SELECT 
				  autorizado.Aut_Cod,				  
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  if(autorizado.Aut_Est='I','INACTIVO','ACTIVO')AS Estado
			  FROM
				  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
				  INNER JOIN autorizado ON (distributi.Dis_Cod = autorizado.Dis_Cod)				 
			  WHERE 
			  	  autorizado.Emp_Cod = $Par_Sql[0] AND autorizado.Aut_Est='A'";
	//echo $Sql_397;
	return $Sql_397;
	break;
	
	//Busqueda de la personas que consumen caja chica
	case 398:
	$Sql_398="SELECT 
				  Con_Cod, 
				  Con_Des, 
				  if(Con_Est='I','INACTIVO','ACTIVO')AS Estado
			  FROM
				  consumo				  
			  WHERE 
			  	  Emp_Cod=$Par_Sql[0] AND Con_Est='A'";
	//echo $Sql_398;
	return $Sql_398;
	break;
	
	//Busqueda de la personas que consumen caja chica
	case 399:
	$Sql_399="SELECT 
				  Cja_Cod, Cja_Mon, Cja_Sal, Cja_Tra, Cja_Pun, Cja_Est
			  FROM
				  reposicion				  
			  WHERE 
			  	  Emp_Cod=$Par_Sql[0] AND Cja_Est='A'";
	//echo $Sql_399;
	return $Sql_399;
	break;
	
	//Busqueda del receptor caja chica por apellido
	case 400:
	$Sql_400="SELECT 
				  receptor.Rec_Cod,
				  personal.Per_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  receptor.Rec_Est,
				  recibo.Rcb_Cod,
				  recibo.Aut_Cod,
				  recibo.Con_Cod,
				  recibo.Rcb_Fec,
				  recibo.Rcb_Obs,
				  recibo.Rcb_Con,
				  if(recibo.Rcb_Est='A','ACTIVO','INACTIVO')as Estado,
				  consumo.Con_Cod,
				  consumo.Con_Des
				FROM
				  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
				  INNER JOIN receptor ON (distributi.Dis_Cod = receptor.Dis_Cod)
				  INNER JOIN recibo ON (receptor.Rec_Cod = recibo.Rec_Cod)
				  INNER JOIN consumo ON (recibo.Con_Cod = consumo.Con_Cod)
				WHERE
				  Prs_Ape LIKE '%$Par_Sql[0]%' AND recibo.Rcb_Est = 'A'";
	//echo $Sql_400;
	return $Sql_400;
	break;

/* **************************************** FIN DE ALTA DE CHEQUES **************************************** */
			
		/*****************************************************************************************/
		/*****************************************************************************************/
		/********************************SQL ALMACEN**********************************************/
		/*****************************************************************************************/
		/* Registrar datos de persona */
		case 401:
		$registra_persona= "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Dir, Prs_Tel, Prs_Te2, Prs_Cel, Ciu_Cod, Ide_Cod) VALUES (Trim('$Par_Sql[0]'), Trim(UPPER('$Par_Sql[1]')),Trim(UPPER('$Par_Sql[2]')),UPPER('$Par_Sql[3]'),Trim(UPPER('$Par_Sql[4]')),Trim('$Par_Sql[5]'),Trim('$Par_Sql[6]'), Trim('$Par_Sql[7]'), $Par_Sql[8], $Par_Sql[9])";
		//echo $registra_persona;
		return $registra_persona;
		break;
				
		/* Verifica si eisten datos de una persona*/
		case 402:
		$verifica_persona= "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, IF (persona.Prs_Sex='M','Masculino','Femenino') as sexo , Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel, ciudad.Ciu_Cod, identifica.Ide_Cod, Ide_Des, ciudad.Ciu_Des FROM persona, identifica, ciudad WHERE Prs_Ced='$Par_Sql[0]' AND identifica.Ide_Cod=persona.Ide_Cod AND ciudad.Ciu_Cod=persona.Ciu_Cod ";
		//echo $verifica_persona;
		return $verifica_persona;
		break;		
		
		/* Verifica si el proveedor esta registrado como persona */		
		case 403:
		$verifica_proveedor = "SELECT Prv_Cod FROM proveedore, persona WHERE persona.Prs_Cod = proveedore.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]'";
		return $verifica_proveedor;
		break;
		
			/* Guarda el proveedor */		
		case 404:
		$registrar_proveedor = "INSERT INTO proveedore (Prv_Fax, Prs_Cod) VALUES (Trim('$Par_Sql[0]'),'$Par_Sql[1]')";
		//echo $registrar_proveedor;
		return $registrar_proveedor;
		break;

		
		/* Busqueda de Proveedores */
		case 405:
		$busca_proveedores_nom= "SELECT Prv_Cod, proveedore.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape FROM proveedore,persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND persona.Prs_Cod=proveedore.Prs_Cod ORDER BY Prs_Ape ASC";
	//echo $busca_proveedores_nom;
		return $busca_proveedores_nom;
		break;
		
		case 406:
		$busca_proveedores_cod= "SELECT Prv_Cod,proveedore.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND persona.Prs_Cod=proveedore.Prs_Cod";
		//echo $busca_proveedores_cod;
		return $busca_proveedores_cod;
		break;
		
		/* çarga el proeedor*/
		case 407:
		$carga_proveedor= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Sex, persona.Prs_Dir, 
persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Cel, persona.Prs_Te2, persona.Ide_Cod, identifica.Ide_Des, proveedore.Prv_Est,
ciudad.Ciu_Des
FROM proveedore, persona, identifica, ciudad WHERE persona.Ide_Cod= identifica.Ide_Cod AND persona.Ciu_Cod = ciudad.Ciu_Cod AND proveedore.Prs_Cod = '$Par_Sql[0]' AND proveedore.Prs_Cod= persona.Prs_Cod";
		//echo $carga_proveedor;
		return $carga_proveedor;
		break;
	
		/* Açtualisa probeedor*/ 
		case 408:	
		$actualiza_proveedores="UPDATE proveedore SET  Prv_Fax = '$Par_Sql[0]', Ide_Cod = '$Par_Sql[1]' WHERE Prs_Cod = $Par_Sql[2]";
		//echo $actualiza_proveedores;
		return $actualiza_proveedores;
		break;
		
		/* Açtualisa persona*/
		case 409:
		$actualiza_persona="UPDATE persona SET Prs_Ced = Trim('$Par_Sql[0]'), Ide_Cod =$Par_Sql[1], Prs_Nom = Trim(UPPER('$Par_Sql[2]')), Prs_Ape = Trim(UPPER('$Par_Sql[3]')), Prs_Sex=Trim(UPPER('$Par_Sql[4]')), Prs_Dir = Trim(UPPER('$Par_Sql[5]')), Ciu_Cod = $Par_Sql[6], Prs_Tel ='$Par_Sql[7]', Prs_Cel = '$Par_Sql[8]', Prs_Te2 ='$Par_Sql[9]' WHERE Prs_Cod = $Par_Sql[10]";
		//echo $actualiza_persona;
		return $actualiza_persona;
		break;
		
		/* Registra çategoria JESSICA */
		case 410:
		$registra_categoria= "INSERT INTO categorias (Cat_Des) VALUES (Trim(UPPER('$Par_Sql[0]')))";
		return $registra_categoria;
		break;
		
		/* Busça la çategoria JESSICA */
		case 411:
		$busca_categoria_nom= "SELECT Cat_Cod,Cat_Des FROM categorias WHERE Cat_Des LIKE '%$Par_Sql[0]%' ORDER BY Cat_Des ASC";
		return $busca_categoria_nom;
		break;
		
		case 412:
		$busca_categoria_cod= "SELECT Cat_Cod, Cat_Des FROM categorias WHERE Cat_Cod = '$Par_Sql[0]'";
		return $busca_categoria_cod;
		break;
		
		/* Çarga la Çategoria */
		case 413:
		$carga_categoria= "SELECT categorias.Cat_Cod, categorias.Cat_Des FROM categorias WHERE categorias.Cat_Cod = '$Par_Sql[0]'";
		return $carga_categoria;
		break;
		
		/*Actualiza la categoria JESSICA */
		case 414:
		$actualiza_categoria= "UPDATE categorias SET  Cat_Des = Trim(UPPER('$Par_Sql[0]')) WHERE Cat_Cod = $Par_Sql[1]";
		return $actualiza_categoria;
		break;
		
		/* Registra la marca JESSICA */
		case 415:
		$registra_marca= "INSERT INTO marca (Mar_Des) VALUES (Trim(UPPER('$Par_Sql[0]')))";
		return $registra_marca;
		break;
		
		/* Busqueda de las marcas */
		case 416:
		$busca_marca_nom= "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des LIKE '%$Par_Sql[0]%' ORDER BY Mar_Des ASC";
		return $busca_marca_nom;



		break;
		
		case 417:
		$busca_marca_cod= "SELECT Mar_Cod,Mar_Des FROM marca WHERE Mar_Cod = '$Par_Sql[0]'";
		return $busca_marca_cod;
		break;
		
		/* Carga datos de la marca */		
		case 418:
		$carga_marca= "SELECT marca.Mar_Cod, marca.Mar_Des, marca.Mar_Est FROM marca WHERE marca.Mar_Cod = '$Par_Sql[0]'";
		return $carga_marca;
		break;
		
		/* Actualiza los datos de la marca  jessica */		
		case 419:
		$actualiza_marca= "UPDATE marca SET  Mar_Des = Trim(UPPER('$Par_Sql[0]')), Mar_Est='$Par_Sql[1]' WHERE Mar_Cod = $Par_Sql[2]";
		return $actualiza_marca;
		break;
		
		/* Verifica si vendedor existe */
		case 420:
		$verifica_vendedor= "SELECT Vnd_Cod FROM vendedor,persona WHERE persona.Prs_Cod = vendedor.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]'";
		return $verifica_vendedor;
		break;
		
		/* Registra vendedor */
		case 421:
		$registra_vendedor= "INSERT INTO vendedor (Prs_Cod, Pun_Cod) VALUES ('$Par_Sql[0]', '$Par_Sql[1]')";

		return $registra_vendedor;
		break;
		
		/* Busca vendedores */
		case 422:
		$busca_vendedores_nom= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM vendedor, persona WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND vendedor.Prs_Cod=persona.Prs_Cod ORDER BY Prs_Ape ASC";
		return $busca_vendedores_nom;
		break;
		
		case 423:
		$busca_vendedores_cod= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM vendedor, persona WHERE persona.Prs_Ced = '$Par_Sql[0]' AND vendedor.Prs_Cod=persona.Prs_Cod";
		return $busca_vendedores_cod;
		break;
		
		/* Carga vendedor */
		case 424:
		$carga_vendedores= "SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,persona.Prs_Sex,persona.Prs_Dir,persona.Prs_Tel,
persona.Prs_Cel,persona.Ciu_Cod, vendedor.Vnd_Est, Pun_Cod FROM persona,vendedor
WHERE vendedor.Prs_Cod = $Par_Sql[0]  AND vendedor.Prs_Cod=persona.Prs_Cod";
		return $carga_vendedores;
		break;
		
		/* Actualiza datos de vendedor */
		case 425:
		$actualiza_vendedor= "UPDATE vendedor SET  Pun_Cod = '$Par_Sql[0]' WHERE Prs_Cod = '$Par_Sql[1]'";
		return $actualiza_vendedor;
		break;
		
		/* Registra item */
		case 426:
		$registra_item= "INSERT INTO item (Cat_Cod,Ite_Cor,Ite_Lar) VALUES ($Par_Sql[0], Trim('$Par_Sql[1]'), Trim('$Par_Sql[2]'))";
		return $registra_item;
		break;
		
		
		/* Busca item  */
		case 427:
		$busca_item_nom= "SELECT Ite_Cor, Ite_Lar, Ite_Cod FROM item WHERE Ite_Lar LIKE '%$Par_Sql[0]%' ORDER BY Ite_Lar ASC";
		return $busca_item_nom;
		break;
		
		
		/* Carga todas las marcas */
		case 428:
		$carga_marcas= "SELECT Mar_Cod, Mar_Des FROM marca";
		return $carga_marcas;
		break;
		
		/* Carga iva */
		case 429:
		$carga_iva= "SELECT Iva_Por, Iva_Cod FROM iva WHERE Iva_Est='A'";
		return $carga_iva;
		break;
		
		/* Registra producto*/
		case 430:
		$registra_item= "INSERT INTO producto (Ite_Cod, Mar_Cod, Pro_Ide, Iva_Cod, Pro_Obs, Pro_Tip) VALUES ($Par_Sql[0], $Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]')";
		return $registra_item;
		break;
		
		/* carga item para guardar */
		case 431:
		$carg_item= "SELECT Ite_Cor, Ite_Lar FROM item WHERE Ite_Cod= $Par_Sql[0]";
		return $carg_item;
		break;
		
		/* busca item de acuerdo a la categoria */
		case 432:
		$carg_item= "SELECT Cat_Cod, Cat_Des FROM categorias";
//		echo $carg_item;
		return $carg_item;
		break;
		
		/* busca item de acuerdo a la categoria */
		case 433:
		$carg_item2= "SELECT producto.Pro_Cod, Ite_Cor, Ite_Lar, categorias.Cat_Cod FROM item, categorias, producto WHERE categorias.Cat_Cod=  $Par_Sql[0] AND item.Cat_Cod= categorias.Cat_Cod AND producto.Ite_Cod = item.Ite_Cod";
		return $carg_item2;
		break;
		
		/* carga item para modificaciones */
		case 434:
		$carg_items= "SELECT Cat_Des, item.Cat_Cod, Ite_Cor, Ite_Lar, Ite_Est FROM item, categorias WHERE categorias.Cat_Cod= item.Cat_Cod AND Ite_Cod=$Par_Sql[0]";
		return $carg_items;

		break;
		
		/* Carga todas las categorias */
		case 435:
		$carga_marcas= "SELECT Cat_Cod, Cat_Des FROM categorias ORDER BY Cat_Cod ASC";
		return $carga_marcas;
		break;
		
		/* actualiza item JESSICA */
		case 436:
		$actualizar_item= "UPDATE item SET Cat_Cod='$Par_Sql[0]', Ite_Cor=Trim('$Par_Sql[1]'), Ite_Lar=Trim('$Par_Sql[2]') WHERE Ite_Cod=$Par_Sql[3]";
//		echo $actualizar_item;
		return $actualizar_item;
		break;
		
		/* carga producto para modificacion JESSICA */
		case 437:
  		$carg_prod= "SELECT producto.Pro_Cod, Mar_Des, Cat_Des, Pro_Ide, producto.Mar_Cod, Ite_Cor, Ite_Lar, Pro_Est, 
Pro_Obs, iva.Iva_Por, item.Ite_Cod, categorias.Cat_Cod FROM item, marca,producto, iva, categorias
 WHERE marca.Mar_Cod= producto.Mar_Cod 
 AND producto.Ite_Cod = item.Ite_Cod 
 AND iva.Iva_Cod = producto.Iva_Cod 
 AND item.Ite_Cod=producto.Ite_Cod 
  AND item.Cat_Cod = categorias.Cat_Cod
 AND producto.Pro_Cod= '$Par_Sql[0]'";
		// echo $carg_prod;
		return $carg_prod;
		break;
		
		/* Actualiza cabecera del producto JESSICA 17-01-2007*/
		case 438:
		$actuali_prod= "UPDATE producto SET Mar_Cod=$Par_Sql[0], Pro_Est='$Par_Sql[1]', Pro_Ide='$Par_Sql[2]',Pro_Obs='$Par_Sql[3]', Iva_Cod='$Par_Sql[4]' WHERE Pro_Cod='$Par_Sql[5]'";
		return $actuali_prod;
		break;
		
		/* Busca producto por el codigo del Item JESSICA modificada 16 - 01 -2007*/
		case 439:
		$busca_prod= "SELECT producto.Pro_Cod, producto.Ite_Cod, producto.Pro_Ide, Ite_Cor, Ite_Lar, Pro_Obs FROM item, producto
 WHERE Ite_Lar LIKE '$Par_Sql[0]%' AND producto.Ite_Cod = item.Ite_Cod  ORDER BY Ite_Lar ASC";
		return $busca_prod;
		break;
		
		/* consultas producto */
		case 440:
		$busca_prod= "SELECT Ite_Cor,Pro_Cod, Mar_Des, Iva_Por, Cat_Des, Pro_Ide, IF (producto.Pro_Tip = 'S', 'Servicio', '') as Pro_Tip
        FROM item, producto, marca, categorias, iva WHERE Ite_Cor LIKE '%$Par_Sql[0]%'  AND producto.Ite_Cod = item.Ite_Cod AND        producto.Iva_Cod = iva.Iva_Cod  AND marca.Mar_Cod = producto.Mar_Cod  AND categorias.Cat_Cod= item.Cat_Cod ORDER BY        item.Ite_Cor ASC";
		return $busca_prod;
		break;
		
		/* listado de productos */
		case 441:
		$lista_productos= "SELECT Ite_Cor, Ite_Lar, Pro_Cod, Mar_Des, Cat_Des, Pro_Ide FROM item, producto, marca, categorias 
 WHERE $Par_Sql[0] AND producto.Ite_Cod = item.Ite_Cod AND marca.Mar_Cod = producto.Mar_Cod 
AND categorias.Cat_Cod= item.Cat_Cod ORDER BY item.Ite_Cor ASC";
		return $lista_productos;
		break;
		
		/* Comprueba si cliente existe */
		case 442:
		$verifica_cliente= "SELECT Cli_Cod FROM cliente WHERE Prs_Cod='$Par_Sql[0]'";
		return $verifica_cliente;
		break;
		
		/* registra cliente */
		case 443:
		$registra_cliente= "INSERT INTO cliente (Prs_Cod, Zon_Cod, Cli_Cup) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]')";
		return $registra_cliente;
		break;
		
		/* registra tipo persona */
		case 444:
		$registra_persotipo= "INSERT INTO perso_tipo (Tip_Cod,Prs_Cod) VALUES (3,$Par_Sql[0])";
		return $registra_persotipo;
		break;

		/* carga zonas */
		case 445:
		$zonas= "SELECT Zon_Cod, Zon_Des FROM zonas WHERE Zon_Est='A'";
		return $zonas;
		break;
		
		/* carga identificaciones */
		case 446:
		$identificacion= "SELECT Ide_Cod, Ide_Des FROM identifica WHERE Ide_Est='A'";
		return $identificacion;
		break;
		
		/* Busca cliente por nom 26*/
		case 447:
		$busca_clientes_nom= "SELECT cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM cliente, persona WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND cliente.Prs_Cod=persona.Prs_Cod ORDER BY Prs_Ape ASC";
		return $busca_clientes_nom;
		break;
		
		/* busca cliente por cod 27*/
		case 448:
		$busca_clientes_cod= "SELECT cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM cliente, persona WHERE persona.Prs_Ced = '$Par_Sql[0]' AND cliente.Prs_Cod=persona.Prs_Cod ORDER BY Prs_Ape ASC";
		return $busca_clientes_cod;
		break;
		
		/* carga cliente 28*/
		case 449:
		$carga_cliente= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Sex, persona.Prs_Dir, persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ide_Cod, cliente.Cli_Cup, cliente.Zon_Cod, cliente.Cli_Cod, cliente.Cli_Est FROM persona,cliente WHERE cliente.Prs_Cod = $Par_Sql[0] AND cliente.Prs_Cod=persona.Prs_Cod";
		//echo $carga_cliente;
		return $carga_cliente;
		break;
		
		/* actualiza cliente 30*/
		case 450:
		$actualiza_cliente2= "UPDATE cliente SET  Zon_Cod=$Par_Sql[0], Cli_Cup = '$Par_Sql[1]' WHERE Prs_Cod = $Par_Sql[2]";
		return $actualiza_cliente2;
		break;	
		
		
		case 451:
		$lista_cliente= "SELECT Prs_Ced, Prs_Nom, Prs_Ape, Prs_Dir, Prs_Tel, Cli_Cup, Cli_Est FROM persona, cliente WHERE Prs_Ape LIKE '$Par_Sql[0]%' AND persona.Prs_Cod=cliente.Prs_Cod ORDER BY Prs_Ape ASC";
		return $lista_cliente;
		break;
		
		case 452:
		$listado_cliente= "SELECT Prs_Ced, Prs_Nom, Prs_Ape, Prs_Dir, Prs_Tel, Cli_Cup, Cli_Est FROM persona, cliente WHERE persona.Prs_Cod=cliente.Prs_Cod ORDER BY Prs_Ape ASC";
		return $listado_cliente;
		break;
		
		/* Carga la sucursal */
		case 453:
		$carga_sucursal= "SELECT sucursal.Suc_Cod, sucursal.Suc_Des FROM sucursal WHERE Suc_Est='A'";
		return $carga_sucursal;
		break;
		
		/* registra precios */
		case 454:
		$registra_precio= "INSERT INTO precios (Suc_Cod, Pro_Cod, Pre_Pvp) VALUES ($Par_Sql[0], $Par_Sql[1], Trim($Par_Sql[2]))";
		return $registra_precio;
		break;
		
		/* Carga cliente nota */
		case 455:
		$carga_clte= "SELECT persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, cliente.Cli_Cod FROM persona, cliente WHERE persona.Prs_Cod=cliente.Prs_Cod";
		return $carga_clte;
		break;
		
		/* Carga el punto de impresion */
		case 456:
		$carga_punto= "SELECT Pun_Cod from autorizaci WHERE Tic_Cod=4 and Aut_Est='A'";
		return $carga_punto;
		break;
		
		/* Carga el numero de venta*/
		case 457:
		$carga_numero= "SELECT count(Vet_Num) as total from ventas";
		return $carga_numero;
		break;
		
		/* Carga el fecha de venta*/
		case 458:
		$carga_fecha= "SELECT Caj_Fec from caja_aper where Caj_Est='A'";
		return $carga_fecha;
		break;
		
		case 459:
		$carga_ci= "SELECT Prs_Ced from persona, cliente where cliente.Prs_Cod=persona.Prs_Cod and cliente.Cli_Cod=$Par_Sql[0]";
		return $carga_ci;
		break;
				
		case 460:
		/* Consulta para verificar si el cliente se encuentar registrado */
		$consultar_personal = "SELECT Cli_Cod FROM cliente, persona WHERE persona.Prs_Cod = cliente.Prs_Cod AND persona.Prs_Ced = '$Par_Sql[0]'";
		return $consultar_personal;
		break;

        /* Busca codigo del Item JESSICA  */
		case 461:
		$busca_item_cod= "SELECT Ite_Cod, Ite_Cor, Ite_Lar FROM item WHERE Ite_Cod = $Par_Sql[0]";
		return $busca_item_cod;
		break;
		
		/* Busca codigo del Item po el codigo del producto JESSICA 16-01-2007*/
		case 462:
		$busca_cod_item_pro= "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cod, item.Ite_Cor, item.Ite_Lar, Pro_Obs
FROM item, producto WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = $Par_Sql[0]";
		return $busca_cod_item_pro;
		break;
				
		/* Busca codigo del Item po el codigo del producto JESSICA */
		case 463:
 		$busca_pun_ven= "SELECT  Pun_Cod, Pun_Des FROM puntos_imp WHERE Pun_Est = 'A'";
		return $busca_pun_ven;
		break; 
		
		/* Busca el codigo de la categoria y el Ite_Lar para verificar si ya existe */
		case 464:
 		$busca_ite_cat= "SELECT item.Ite_Cod from categoria, item WHERE categoria.Cat_Cod = item.Cat_Cod AND
item.Ite_Lar = '$Par_Sql[0]' AND item.Cat_Cod = $Par_Sql[1]";
		return $busca_ite_cat;
		break; 

		/* Busca el codigo de la categoria y el Ite_Lar para verificar si ya existe MODIFICACION JESSICA 16 - 01 - 2007*/
		case 465:
 		$busca_pun_ven= "SELECT item.Ite_Cod, producto.Pro_Cod from  item, marca, producto WHERE producto.Mar_Cod = marca.Mar_Cod
 AND item.Ite_Cod = producto.Ite_Cod AND marca.Mar_Cod =$Par_Sql[0] AND item.Ite_Cod = $Par_Sql[1]";
		return $busca_pun_ven;
		break; 
		
		/* Borrado de precios de los productos a modificar 17-01-2007 */
		case 466:
		$bor_precio="DELETE FROM precios WHERE Pro_Cod='$Par_Sql[0]'";
		return $bor_precio;
		break;
		
		case 467:
  		$carg_precio_pro= "SELECT  precios.Pre_Des, precios.Pre_Pvp, precios.Pre_Cod 
FROM item, marca,producto, iva, precios WHERE marca.Mar_Cod= producto.Mar_Cod AND producto.Ite_Cod = item.Ite_Cod 
 AND iva.Iva_Cod = producto.Iva_Cod AND producto.Pro_Cod = precios.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod AND producto.Pro_Cod= '$Par_Sql[0]'";
		return $carg_precio_pro;
		break;
		
		
		
	/* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por Apellido del proveedor */
		
case 468:

$carg_fac_com_anu_468="SELECT persona.Prs_Ape, tipo_compr.Tic_Des, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, 
persona.Prs_Nom, compras.Cop_Num
FROM persona, compras, proveedore, tipo_compr
WHERE
tipo_compr.Tic_Cod=compras.Tic_Cod
AND persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'
AND compras.Pec_Cod = '$Par_Sql[1]' $Par_Sql[2] 
ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom
";return $carg_fac_com_anu_468;
break;
		/* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por número de factura de compra */
				/* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por número de factura de compra */
		case 469:
		$carg_fac_com_anu_469="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona, compras, proveedore
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Cop_Num='$Par_Sql[0]'
ORDER BY compras.Cop_Cod";
//AND compras.Tic_Cod=$Par_Sql[1]
		return $carg_fac_com_anu_469;
		break;
		




		/* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por grupos entre fechas */
		
		case 470:
		$carg_fac_com_anu="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, det_compra.Cop_Imp FROM persona,  proveedore,compras, det_compra WHERE persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod 
GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Cod ASC ";
		return $carg_fac_com_anu;
		break;
		
		/* Dar de baja a las facturas de compra a las cuales no se les haya generado la retención */
		case 471:
		$baj_fac_compra="UPDATE compras SET Cop_Est=UPPER('$Par_Sql[1]') WHERE Cop_Cod=$Par_Sql[0]";
		return $baj_fac_compra;
		break;


			/* Consulta de los datos del proveedor */
	case 472: 
		$con_fac_proveedo="SELECT persona.Prs_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Dir, compras.Cop_Cod,
compras.Cop_Num, compras.Prv_Cod, compras.Cop_Aut, compras.Ciu_Cod, compras.Cop_Fec, compras.Cop_Reg, compras.Cop_Cad, compras.Tic_Cod,
compras.Cop_Imf, compras.Cop_Des, compras.Pec_Cod, det_compra.Cop_Int, det_compra.Cop_Pru, compras.Cop_Obs, compras.Cop_Est, 
det_compra.Cop_Pro, det_compra.Cop_Can, det_compra.Pld_Cod, det_compra.Cop_Imp, det_compra.Cop_Dec, det_compra.Iva_Cod, det_compra.Ice_Int, iva.Iva_Por, 
ciudad.Ciu_Des, compras.Tri_Cod, sustento.Tri_Des,adquisicio.Adq_Cod ,adquisicio.Adq_Des, compras.Tic_Cod, tipo_compr.Tic_Des 

FROM persona, proveedore, compras, det_compra, iva, ciudad, sustento, adquisicio, tipo_compr 
WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod AND adquisicio.Adq_Cod = det_compra.Adq_Cod 
AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Cod=$Par_Sql[0] 
AND compras.Tic_Cod= tipo_compr.Tic_Cod
AND det_compra.Iva_Cod=iva.Iva_Cod AND ciudad.Ciu_Cod=compras.Ciu_Cod AND sustento.Tri_Cod= compras.Tri_Cod ";
		//echo $con_fac_proveedo;
		return $con_fac_proveedo; 
		break;

	/* Consulta de campos para el cálculo de los totales de la factura */
		case 473:
		$con_fac_tot_com="SELECT compras.Cop_Des ,det_compra.Cop_Int,det_compra.Cop_Pro, det_compra.Cop_Can, det_compra.Cop_Pru, 
		det_compra.Cop_Imp, det_compra.Cop_Dec, iva.Iva_Por
		FROM compras, det_compra, iva
		WHERE  compras.Cop_Cod=det_compra.Cop_Cod AND det_compra.Cop_Cod=$Par_Sql[0] 
		AND iva.Iva_Cod=det_compra.Iva_Cod"; //13 adquisicion gastos AND det_compra.Adq_Cod != 13
		return 	$con_fac_tot_com;
		break;


		/* Consultar si la factura ya se encuentra registrada teniendo en cuenta si es de un mismo proveedor */
		case 474:
$con_exi_fac_com="SELECT compras.Cop_Num FROM compras WHERE compras.Cop_Num='$Par_Sql[0]'  AND compras.Prv_Cod=$Par_Sql[1] AND compras.Cop_Est='A'";
		return $con_exi_fac_com;
		break;

		/* Consulta las liquidaciones que se pueden modificar en base al tipo de comprobante, año, mes */
		case 475:
		$carg_fac_com_mofi_475="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, 
		tipo_compr.Tic_Des 
		FROM persona, proveedore, compras, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod
		AND proveedore.Prv_Cod=compras.Prv_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND compras.Tic_Cod = $Par_Sql[1]
		 AND YEAR(Cop_Fec) = '$Par_Sql[2]' $Par_Sql[3] ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom";
       	 // echo $carg_fac_com_mofi_475;
		return $carg_fac_com_mofi_475;
		break;
		
		/* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) */
		case 476:
		$carg_ret_des_imp="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
		FROM renta_iva WHERE renta_iva.Ren_Est='A' ORDER BY renta_iva.Ren_Sri   ";
		return $carg_ret_des_imp;
		break;
		/* Carga los conceptos de la factura de compra */
		
		case 477:
		$carg_ret_des_imp="SELECT  persona.Prs_Ced,  persona.Prs_Dir,
persona.Prs_Nom, persona.Prs_Ape, compras.Cop_Cod,
compras.Aut_Cod,
compras.Cop_Num, compras.Cop_Aut, compras.Cop_Fec,  compras.Cop_Reg, compras.Cop_Cad,
compras.Cop_Imf, compras.Cop_Des, compras.Cop_Obs,
 det_compra.Cop_Int, det_compra.Cop_Pru, compras.Cop_Obs, compras.Cop_Est, det_compra.Cop_Pro,
det_compra.Cop_Can, det_compra.Cop_Imp, det_compra.Cop_Dec, det_compra.Iva_Cod,
ciudad.Ciu_Des, iva.Iva_Por, tipo_compr.Tic_Des
FROM  compras, det_compra, ciudad, persona, proveedore, iva, tipo_compr
WHERE 
    compras.Ciu_Cod=ciudad.Ciu_Cod 
AND compras.Prv_Cod=proveedore.Prv_Cod
AND proveedore.Prs_Cod=persona.Prs_Cod
AND compras.Cop_Cod=det_compra.Cop_Cod
AND iva.Iva_Cod=det_compra.Iva_Cod
AND compras.Cop_Cod=$Par_Sql[0]
AND tipo_compr.Tic_Cod=compras.Tic_Cod
ORDER BY det_compra.Cop_Int
";
		return $carg_ret_des_imp;
		break;
		/** Consultar datos del proveedor y la factura para generar la retencion **/
		case 478:
		$con_dat_ret="SELECT persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, compras.Cop_Num ,compras.Cop_Cod, compras.Cop_Fec
FROM persona, proveedore, compras
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'
AND compras.Cop_Est='A'
AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM retencion WHERE Ret_Est='A' )";
		return $con_dat_ret;
		break;
		/** Consulta las facturas que se pueden modificar con estado Activo por número de factura **********************/
		
		/** Consulta las liquidaciones en base al numero en base al tipo de comprobante */		
		case 479:
		$con_fac_mod_num_479="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, tipo_compr.Tic_Des
			FROM persona, compras, proveedore, tipo_compr
			WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod	
			AND compras.Tic_Cod = tipo_compr.Tic_Cod
			AND compras.Cop_Num='$Par_Sql[0]' AND compras.Tic_Cod = $Par_Sql[1] AND compras.Cop_Cod ORDER BY compras.Cop_Cod ASC ";
		//echo $con_fac_mod_num_479;
		return $con_fac_mod_num_479;
		break;

		/*** Modificación de los datos de la cabecera de la Factura *********************************************/
		case 480:
		$con_mod_fac_compra="UPDATE compras SET Tic_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Cop_Num=UPPER('$Par_Sql[3]'), Cop_Aut=UPPER('$Par_Sql[4]'), Cop_Fec='$Par_Sql[5]', Cop_Reg='$Par_Sql[6]', Cop_Des='$Par_Sql[7]', Cop_Obs=UPPER('$Par_Sql[8]'), Cop_Cad='$Par_Sql[9]', Cop_Imf='$Par_Sql[10]' WHERE compras.Cop_Cod=$Par_Sql[11] ";
		return $con_mod_fac_compra;
		break;

		/* Elimino de la base de datos los items del datalle que ya no vengan en la factura de compras */
		case 481:
		$borr_item_fac_com="DELETE FROM det_compra WHERE det_compra.Cop_Int=$Par_Sql[0] AND det_compra.Cop_Cod=$Par_Sql[1]";
		return $borr_item_fac_com;
		break;
		
		/* Actualizacion del detalle de la factura */
		case 482:
		$actu_item_fac_com="UPDATE det_compra SET Cop_Can=$Par_Sql[1], Cop_Pro=UPPER('$Par_Sql[3]'), Cop_Pru='$Par_Sql[4]', Cop_Imp='$Par_Sql[5]', Cop_Dec='$Par_Sql[6]', 
					Adq_Cod = $Par_Sql[7] WHERE det_compra.Cop_Int=$Par_Sql[0] AND det_compra.Cop_Cod=$Par_Sql[8]";
		//echo $actu_item_fac_com;
		return $actu_item_fac_com;
		break;
		
		
		/** Consulta de facturas de compras por Apellido del proveedor con estado de Factura Activo e Inactivo ************************************************/
		case 483:
		$carg_fac_com_anu="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona, compras, proveedore
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND compras.Tic_Cod=$Par_Sql[1]
AND proveedore.Prv_Cod=compras.Prv_Cod
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND YEAR(Cop_Fec) = '$Par_Sql[2]' $Par_Sql[3]
AND compras.Cop_Cod
ORDER BY  compras.Cop_Cod, compras.Cop_Fec ASC

";
		return $carg_fac_com_anu;
		break;
		
		/** Consulta las facturas de compra por número de la factura de compra con estado activo e inactivo  ********************************************************************************************************************************/
    	case 484:
		$carg_fac_com_anu="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona, compras, proveedore
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND compras.Tic_Cod=$Par_Sql[1]
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Cop_Num='$Par_Sql[0]'
AND compras.Cop_Cod 
ORDER BY  compras.Cop_Cod ASC
";
		return $carg_fac_com_anu;
		break;
		
		/** Consulta grupos de facturas de compra con fechas inicio-fin, con estado Activo e Inactivo de la Factura
		*******************************************************************/
		case 485:
		$carg_fac_com_anu="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona,  proveedore,compras, det_compra
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec ASC
";
		return $carg_fac_com_anu;
		break;
		/** Consulta el total del importe de una factura de compra de acuerdo al código interno
		*******************************************************************************************************************/
		case 486:
		$impo_sum_fac_comp="SELECT SUM(Cop_Imp) as Importe FROM det_compra WHERE Cop_Cod=$Par_Sql[0]";
		return $impo_sum_fac_comp;
		break;
		
		/* Búsqueda de un proveedor por apellido */
		case 487:
		$bus_proa="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Prs_Cod=persona.Prs_Cod ORDER BY persona.Prs_Ape ASC";
		return $bus_proa;
		break;

	/* Búsqueda de un proveedor por Cédula */
		case 488:
  	$bus_proc="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND proveedore.Prs_Cod=persona.Prs_Cod ORDER BY persona.Prs_Ape ASC";
		return $bus_proc;
		break;
		/* Cargado de los porcentajes de retencion Iva Renta  */
		case 489:
		$renta_iva="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por FROM renta_iva WHERE renta_iva.Ren_Cod=$Par_Sql[0]";
		return $renta_iva;
		break;
		/* Cargado de los porcentajes RENTA IVA medidiante tomando el código enviado atraves de AJAX */
		case 490:
		$renta_sri="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por FROM renta_iva WHERE renta_iva.Ren_Sri='$Par_Sql[0]' AND renta_iva.Ren_Est='A'";
		return 	$renta_sri;
		break;
		/* Insercion de el calculo de retenciones por factura en la base de datos */
		case 491:
		$renta_retencion="INSERT INTO retencion (Cop_Cod, Ret_Num, Ret_Fec, Ret_Con, Tic_Cod, Vnd_Cod, Aut_Cod) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'), $Par_Sql[4], $Par_Sql[5], $Par_Sql[6] ) ";
    	return $renta_retencion;
		break;

		/* Insercion del detalle de la retención **/
		case 492:
		$renta_detalle_492="INSERT INTO det_retenc(Ret_Cod,Ret_Bas, Ren_Cod, Ret_Imp, Ret_Int, Adq_Cod)
		VALUES($Par_Sql[0],'$Par_Sql[1]',$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]', $Par_Sql[5])";
		//echo $renta_detalle_492;
		return $renta_detalle_492;
		break;		

		/* Carga las facturas las retenciones que se deben modifcar producto de la actualización de una factura */
		case 493:
		$cons_rete_actuali_mod_fac="SELECT compras.Cop_Cod FROM compras WHERE compras.Cop_Cod=$Par_Sql[0]
AND compras.Cop_Cod IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')";

		return $cons_rete_actuali_mod_fac;
		break; 
	    /* Consulta las facturas con estado activo y sin generación de la retención o con la retencion dada de bajs ******/
		case 494:
		$carg_fac_com_anu="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona, compras, proveedore
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Cop_Est='A'
AND compras.Cop_Num='$Par_Sql[0]'
AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM retencion WHERE Ret_Est='A') ";
		return $carg_fac_com_anu;
		break;
		/** Consultar el codigo de la retencion a modificar en base al codigo de la factura de compra ****************/
		case 495:
		$carga_codigo_modif_rente="SELECT retencion.Ret_Cod FROM retencion WHERE retencion.Ret_Est='A' AND retencion.Cop_Cod=$Par_Sql[0]";
		return $carga_codigo_modif_rente;
		break;
		/** Consulto los datos de la retencion  *****************/
		case 496:
		$carga_datos_retencion="SELECT Ret_Cod, Ret_Num, Ret_Fec, Ret_Con FROM retencion WHERE retencion.Ret_Cod=$Par_Sql[0]";
		return $carga_datos_retencion;
		break;
		/** Eliminar los detalles de la factura a modificar para ingresar valores actualizados *************/
		case 497:
		$carga_datos_retencion="DELETE FROM det_retenc WHERE det_retenc.Ret_Cod=$Par_Sql[0]";
		return $carga_datos_retencion;
		break;
		/** Actualizo la cabera del   ***********/
		case 498:
		$actu_cabe_retencion="UPDATE retencion SET retencion.Ret_Con=UPPER('$Par_Sql[1]') WHERE retencion.Ret_Cod=$Par_Sql[0]";
		return $actu_cabe_retencion; 
		break;
		/** Consultar las retenciones a modificar por número de comprobante de retencion ***/
		case 499:
		$carga_retenci_modif="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Ret_Est='A' AND retencion.Tic_Cod=$Par_Sql[1]
AND retencion.Ret_Num='$Par_Sql[0]' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
		return $carga_retenci_modif;
		break;
		/** Cálculo del importe de la retención ************/
		/** Cálculo del importe de la retención ************/
		case 500:
		/*$importe_retenido="SELECT SUM(det_retenc.Ret_Bas) AS suma_re FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod";ojo borrar*/
		
		$importe_retenido="SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod";

		return $importe_retenido;
		//echo $importe_retenido;
		return $importe_retenido;
		break;
		
/** Cargar retención a actualizar ***********/
			case 501:
		$cargar_reten_actuali="SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape, persona.Prs_Dir, proveedore.Prv_Cod, compras.Aut_Cod, compras.Cop_Aut, compras.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, compras.Cop_Imf, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Est, retencion.Ret_Cod, retencion.Ret_Con, retencion.Ret_Fec, tipo_compr.Tic_Des, renta_iva.Ren_Por, renta_iva.Ren_Sri,
det_retenc.Ret_Int, (det_retenc.Ret_Bas) as Ret_Bas, IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp
, det_retenc.Ret_Cod, det_retenc.Ren_Cod, Ciu_Des FROM tipo_compr,persona, proveedore, compras, retencion, det_retenc, renta_iva, ciudad WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod 
AND compras.Tic_Cod=tipo_compr.Tic_Cod AND retencion.Ret_Cod=$Par_Sql[0] AND persona.Ciu_Cod = ciudad.Ciu_Cod 
ORDER BY det_retenc.Ret_Int ASC";
		//echo $cargar_reten_actuali;
		return $cargar_reten_actuali;
		break;

		
		
		/** Actualiza la cabecera de la retención *****************/
		case 502:
		$actua_cab_retencion="UPDATE retencion SET Ret_Num='$Par_Sql[3]', Ret_Fec='$Par_Sql[1]', Ret_Con=UPPER('$Par_Sql[2]') WHERE retencion.Ret_Cod=$Par_Sql[0]";
		return $actua_cab_retencion;
		break;
		/** Actualiza el detalle de la retencion ***********/
		case 503:
		$actua_det_retencion="UPDATE det_retenc SET Ret_Imp=UPPER('$Par_Sql[1]'), Ret_Bas=$Par_Sql[2], Ren_Cod=$Par_Sql[3] WHERE Ret_Int=$Par_Sql[0]";
		return $actua_det_retencion;
		break;
		/** Consultar las retenciones a modificar por número de comprobante de retencion ***/
			case 504:
		$carga_retenci_modif="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Ret_Est='A' AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ape LIKE '$Par_Sql[0]%' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";

		return $carga_retenci_modif;
		break;



			/** Consulta de retenciones activas e inactivas buscadas por número de comprobante de retención ********************************/
		case 505:
		$carga_retenci_modif="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod, compras.Cop_Est,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Cod, retencion.Ret_Est, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva 
WHERE compras.Cop_Est='A' AND compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND retencion.Ret_Num='$Par_Sql[0]' $Par_Sql[2] GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
		return $carga_retenci_modif;
		break;


		/** Consulta de retenciones activas e inactivas buscadas por número de factura *********************/

		case 506:
		$carga_reten_modif_fac="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod, compras.Cop_Est,  compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva 
WHERE compras.Cop_Est='A' AND compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'  $Par_Sql[2] GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
		return $carga_reten_modif_fac;
		break;



		/** Consulta de retenciones activas e inactivas por fechas de registro de retención *************/
		case 507:
		$carg_reten_fechas="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod,
 compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, 
retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, 
det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, 
renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'
 GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Cod ASC";
	    return $carg_reten_fechas;
		break;
		/** Dar de baja a la retencion de compra ***********/
		case 508:
		$baja_retencion="UPDATE retencion SET Ret_Est=UPPER('$Par_Sql[1]') WHERE Ret_Cod=$Par_Sql[0] ";
		return $baja_retencion;
		break;
		/** Consultar si existe una retencion activa de la factura dada de baja *****************************/
		
		/** Consultar si existe una retencion activa de la factura dada de baja *****************************/
		case 509:
		$consultar_rete_ac="SELECT retencion.Ret_Cod FROM retencion	WHERE retencion.Cop_Cod=$Par_Sql[0] AND retencion.Ret_Est='A'";
		return $consultar_rete_ac;
		break;


		/** Dar de baja a la retencion por codigo de la factura dada de baja ***********/
		case 510:
		$baja_retencion="UPDATE retencion SET Ret_Est=UPPER('$Par_Sql[1]') WHERE Cop_Cod=$Par_Sql[0] ";
		return $baja_retencion;
		break;


		/** Consultar el codigo máximo en la base de datos *********************************************/
		case 511:
		$con_max_cod_ret="SELECT MAX(Ret_Num) AS Ret_Ide FROM retencion  WHERE Aut_Cod=$Par_Sql[0]";
		//echo $con_max_cod_ret;
		return $con_max_cod_ret;
		break;


		/** Selecciona ciudades del ECUADOR *****************************************************/
		case 512:
		$con_ciuda_ecua="SELECT Ciu_Des, Ciu_Cod  FROM ciudad WHERE ciudad.Pas_Cod=1 ORDER BY Ciu_Des ASC ";
		return $con_ciuda_ecua;
		break;
		
		/* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) agrupados */
		case 513:
		$carg_ret_des_imp="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por 
		FROM renta_iva WHERE renta_iva.Ren_Est='A' GROUP BY renta_iva.Ren_Por   ORDER BY renta_iva.Ren_Sri   ";
		return $carg_ret_des_imp;
		break;
		/** Consulta las retenciones de acuerdo al porcentaje de retención *******************************************/
		case 514:
		$carg_reten_fechas_por="SELECT persona.Prs_Nom,renta_iva.Ren_Por,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,
compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, 
retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva
 WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'
AND renta_iva.Ren_Por=$Par_Sql[4]
 GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Cod";
	    return $carg_reten_fechas_por;
		break;
		/** Calculo del valor de la retencion por porcentaje de retención ********************/
		case 515:
		$importe_porce_rete="SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod  AND renta_iva.Ren_Por=$Par_Sql[1]";
		return $importe_porce_rete;
		break;
		/** Consulta de retenciones activas e inactivas por fechas de registro de retención *************/

			case 516:
		$carg_reten_fechas_anu="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Cod ASC";
	    return $carg_reten_fechas_anu;
		break;

/** Consultar el codigo de la retencion desde la autorización grabada en la base de datos *************************************/
 		case 517:
		$ret_num_inic_compro="SELECT vendedor.Vnd_Cod, autorizaci.Aut_Sri, autorizaci.Pun_Sri,  autorizaci.Aut_Cod, autorizaci.Aut_Fci, autorizaci.Aut_Cad, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Est, puntos_imp.Pun_Des, puntos_imp.Pun_Ubi FROM vendedor, puntos_imp, autorizaci, tipo_compr, persona
		 WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod=$Par_Sql[1] AND puntos_imp.Pun_Cod=vendedor.Pun_Cod 
		 AND puntos_imp.Pun_Cod=autorizaci.Pun_Cod AND autorizaci.Aut_Est='A'
		 AND puntos_imp.Pun_Est='A' AND persona.Prs_Cod=vendedor.Prs_Cod AND persona.Prs_Cod=$Par_Sql[0] 
		 AND vendedor.Vnd_Est='A'  ";
		//echo $ret_num_inic_compro;

		return $ret_num_inic_compro;
		break;

		/*** Consultar si ya existe una retencion generada con la autorizacion activa retenciones **********************************/
		case 518:
		$numero_genera_atoriz="SELECT Ret_Cod, Ret_Num FROM retencion WHERE retencion.Aut_Cod=$Par_Sql[0]";
		//echo $numero_genera_atoriz;
		return $numero_genera_atoriz;
		break;
		/* OJO NUEVAS SENTENCIAS MARTES **************************/
		
		/** Consultar si ya existe un código en las liquidaciones de compra con la autorización activa *************************************/
		case 519:
		$con_cod_liqui_compra="SELECT compras.Cop_Cod FROM compras WHERE compras.Aut_Cod=$Par_Sql[0]";
		return $con_cod_liqui_compra;
		break;
		
		/** Consultar el código mayor de una liquidación de compra con una autorización activa  ************************************/
		case 520:
		$con_max_cod_liqui="SELECT MAX(Cop_Num) AS Cop_Num FROM compras WHERE compras.Aut_Cod=$Par_Sql[0]";
		return $con_max_cod_liqui;
		break;
		
		/** Guardar datos de una liquidación de compra ********************/
		case 521: 
 		$inser_fact_liqui_521 = "INSERT INTO compras( Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Fec, Cop_Reg, Cop_Des, Cop_Obs, Vnd_Cod, Aut_Cod) VALUES 
		($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], '$Par_Sql[3]', '$Par_Sql[4]','$Par_Sql[5]', '$Par_Sql[6]' , UPPER('$Par_Sql[7]'), $Par_Sql[8],  $Par_Sql[9] )";
		//echo $inser_fact_liqui_521;
		return $inser_fact_liqui_521;
		break;
		
		/** Modificación de comprobante de liquidación de compra  *******************/
				
		case 522:
		$con_mod_fac_compra="UPDATE compras SET Tic_Cod=$Par_Sql[0], Cop_Num=UPPER('$Par_Sql[2]'), Cop_Fec='$Par_Sql[3]', Cop_Reg='$Par_Sql[4]', Cop_Des='$Par_Sql[5]', Cop_Obs=UPPER('$Par_Sql[6]') WHERE compras.Cop_Cod=$Par_Sql[1] ";
		return $con_mod_fac_compra;
		break;
		/** Consultar información de las liquidaciones de compra por codigo interno de la autorización *************/
		case 523:
		$con_inf_aut_liq="SELECT Aut_Cad FROM autorizaci WHERE Aut_Cod=$Par_Sql[0]";
//echo $con_inf_aut_liq;

		return $con_inf_aut_liq;
		break;
		/** Eliminación de detalles de la retención ******************/
		case 524:
		$elim_det_reten="DELETE FROM det_retenc WHERE Ret_Int=$Par_Sql[0]";
		return $elim_det_reten;
		break;
		
/** Consultas de porcentajes del I.C.E. ***********************/
		case 525:
		$con_ice_porce="SELECT Ice_Int, Ice_Cod, Ice_Por, Ice_Sri FROM ice WHERE ice.Ice_Est='A'";
		return $con_ice_porce;
		break;
		
		/** Graba el detalle de la factura de compra, con I.C.E.  *********************/
		case 526: 
		$inser_detafaccom_526 = "INSERT INTO det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod)
					 VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]' ,'$Par_Sql[5]', '$Par_Sql[6]', $Par_Sql[7])";
 		//echo $inser_detafaccom_526;
		return $inser_detafaccom_526;
		break;

		/** Consulta de los porcentajes del I.C.E ***************/
	
		case 527:
		$con_fac_tot_com="SELECT ice.Ice_Por
		FROM ice, det_compra
		WHERE  ice.Ice_Int=det_compra.Ice_Int AND det_compra.Cop_Int='$Par_Sql[0]'";
		return 	$con_fac_tot_com;
		break;
		
		/** Consulta datos las facturas de compras que se identifiquen como adquisición de bienes o servicios  *************/
		
		case 528:
		$con_fac_tipo_ser_bien="SELECT  compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec,  compras.Cop_Num, persona.Prs_Ape, persona.Prs_Nom

FROM persona, proveedore, compras, det_compra, retencion, det_retenc, renta_iva
WHERE 
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod=retencion.Cop_Cod
AND retencion.Ret_Cod=det_retenc.Ret_Cod
AND renta_iva.Ren_Cod=det_retenc.Ren_Cod
AND renta_iva.Ren_Tip=UPPER('$Par_Sql[3]')
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC ";
		return $con_fac_tipo_ser_bien;
		break;
		/** Consulta del valor por la compra de un bien o servicio en un factura de compra ***********/
		case 529:
		$con_fac_sum_bie_ser="SELECT  SUM(det_retenc.Ret_Bas) as Importe
FROM  retencion, det_retenc, renta_iva
WHERE 
retencion.Cop_Cod=$Par_Sql[0]
AND retencion.Ret_Cod=det_retenc.Ret_Cod
AND renta_iva.Ren_Cod=det_retenc.Ren_Cod
AND renta_iva.Ren_Tip='$Par_Sql[1]'
AND det_retenc.Ret_Imp='R' ";
		return $con_fac_sum_bie_ser;
		break;
		/** Consulta de facturas de compra por pocentajes de I.V.A.   ********************************/
		case 530:
		$impo_sum_fac_comp="SELECT SUM(Cop_Imp) as Importe FROM det_compra
		WHERE det_compra.Cop_Cod=$Par_Sql[0] AND det_compra.Iva_Cod=$Par_Sql[1]";
		return $impo_sum_fac_comp;
		break;
		/** Consulta de las facturas de compras con I.V.A. grabado ********************/
		case 531:
		$con_fac_tipo_ser_bien="SELECT  compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec,  compras.Cop_Num, persona.Prs_Ape, persona.Prs_Nom

FROM persona, proveedore, compras, det_compra, retencion, det_retenc, renta_iva
WHERE 
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod=retencion.Cop_Cod
AND retencion.Ret_Cod=det_retenc.Ret_Cod
AND renta_iva.Ren_Cod=det_retenc.Ren_Cod
AND det_compra.Iva_Cod=$Par_Sql[3]
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC ";
		return $con_fac_tipo_ser_bien;
		break; 
		/** Consulta del porcentaje del I.V.A. *******/
		case 532:
		$con_por_iva="SELECT Iva_Por FROM iva WHERE Iva_cod=$Par_Sql[0]";
		return $con_por_iva;
		break;
		/** Consulta de facturas de compras identificadas como compra de bienes o servicios ***********/

		case 533:
		$con_iva_tipo_comp="SELECT  compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec,  compras.Cop_Num, persona.Prs_Ape, persona.Prs_Nom
FROM persona, proveedore, compras, det_compra, retencion, det_retenc, renta_iva
WHERE 
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod=retencion.Cop_Cod
AND retencion.Ret_Cod=det_retenc.Ret_Cod
AND renta_iva.Ren_Cod=det_retenc.Ren_Cod
AND renta_iva.Ren_Tip=UPPER('$Par_Sql[4]')
AND det_compra.Iva_Cod=$Par_Sql[3]
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
//echo $con_iva_tipo_comp;

		return $con_iva_tipo_comp;
		break;

 /** Consulta de facturas de compra que estan obligadas a generar el comprobante de retención *****************/
		case 534:
		$carg_fac_com_retenidas="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona,  proveedore,compras, det_compra
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod  ASC
";
		return $carg_fac_com_retenidas;
		break;
		/** Consultar las facturas de comprasque no se encuentran sujetas a una retencion en el sistema  *****************************************************************/
		case 535:
		$car_fac_com_sin_ret="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona,  proveedore,compras, det_compra
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
      return $car_fac_com_sin_ret;
	  break;
	  /** Consultar las facturas de comprasque no se encuentran sujetas a una retencion en el sistema por porcentajes de I.V.A. *****************************************************************/
	  	case 536:
		$car_fac_com_sin_ret="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona,  proveedore,compras, det_compra
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND det_compra.Iva_Cod=$Par_Sql[3]
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')
AND compras.Cop_Est='A'
GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
      return $car_fac_com_sin_ret;
	  break;
		 /** Consulta general de facturas ***************************/
	  /** 30-05-2007 **/
	  case 537:
	  $car_con_total_com="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
FROM persona,  proveedore,compras, det_compra, tipo_compr
WHERE
persona.Prs_Cod=proveedore.Prs_Cod
AND proveedore.Prv_Cod=compras.Prv_Cod
AND compras.Tic_Cod=$Par_Sql[2]
AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND compras.Cop_Cod=det_compra.Cop_Cod
AND tipo_compr.Tic_Cod=compras.Tic_Cod
AND compras.Cop_Est='A'

GROUP BY compras.Cop_Cod
ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
     //  echo $car_con_total_com;
	return $car_con_total_com;
	break;
	/** Inserción del detalle de nota de compra ******************/
	case 538:
	$ins_nota_compra="INSERT INTO det_compra (Cop_Cod,    Cop_Can,     Cop_Pro,      Cop_Pru,    Cop_Imp )
 VALUES($Par_Sql[0],$Par_Sql[1],UPPER('$Par_Sql[2]'),'$Par_Sql[3]' ,'$Par_Sql[4]')";
       
		return $ins_nota_compra;
		break;
   /** Consulta de notas de compra para la retención *******/		
   case 539:
   $con_nota_compra="SELECT  proveedore.Prv_Cod,persona.Prs_Ced,  persona.Prs_Dir,
persona.Prs_Nom, persona.Prs_Ape, compras.Cop_Cod,
compras.Aut_Cod,
compras.Cop_Num, compras.Cop_Aut, compras.Cop_Fec,  compras.Cop_Reg, compras.Cop_Cad,
compras.Cop_Imf, compras.Cop_Des, compras.Cop_Obs,
 det_compra.Cop_Int, det_compra.Cop_Pru, compras.Cop_Obs, compras.Cop_Est, det_compra.Cop_Pro,
det_compra.Cop_Can, det_compra.Cop_Imp, det_compra.Cop_Dec, det_compra.Iva_Cod,
ciudad.Ciu_Des, ciudad.Ciu_Cod , tipo_compr.Tic_Des
FROM  compras, det_compra, ciudad, persona, proveedore, tipo_compr
WHERE 
    compras.Ciu_Cod=ciudad.Ciu_Cod 
AND compras.Prv_Cod=proveedore.Prv_Cod
AND proveedore.Prs_Cod=persona.Prs_Cod
AND compras.Cop_Cod=det_compra.Cop_Cod
AND compras.Cop_Cod=$Par_Sql[0]
AND tipo_compr.Tic_Cod=compras.Tic_Cod
ORDER BY det_compra.Cop_Int";
   
   return $con_nota_compra;
   break;
   /** Calculo del total de nota de compras *****************/ 
   case 540:
   $con_nota_sum_tot="SELECT SUM(det_compra.Cop_Imp) AS TOTAL FROM compras,det_compra
   WHERE compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Cod=$Par_Sql[0]";	
   return $con_nota_sum_tot;
   break;
   /** Actualización de los items de las notas de compras  ********************/
   case 541:
   $update_nota_compra="UPDATE det_compra SET det_compra.Cop_Can=$Par_Sql[1], det_compra.Cop_Pro=UPPER('$Par_Sql[2]'), det_compra.Cop_Pru=$Par_Sql[3], det_compra.Cop_Imp=$Par_Sql[4] WHERE det_compra.Cop_Int=$Par_Sql[0]";
   return $update_nota_compra;
   break;
   /** Consultar el tipo de transacción **************************/
   case 542:
   $con_tipo_tran="SELECT transaccio.Tra_Cod, transaccio.Tra_Des  FROM transaccio";
   return $con_tipo_tran;
   break;
	
	/** Consulta las retenciones de acuerdo al codifo del formulario de retención por fecha de compra *******************************************/
		case 543:
		$carg_reten_fechas_for="SELECT persona.Prs_Nom,renta_iva.Ren_Por ,renta_iva.Ren_Sri,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,
compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, 
retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva
 WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'
AND renta_iva.Ren_Cod='$Par_Sql[4]'
 GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Cod";
//echo $carg_reten_fechas_for;

	    return $carg_reten_fechas_for;
		break;
			/** Calculo del valor de la retencion por codifo de formulario de retención ********************/
		case 544:
		$importe_porce_rete="SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod  AND renta_iva.Ren_Cod=$Par_Sql[1] AND det_retenc.Ret_Cod=$Par_Sql[0]";
		return $importe_porce_rete;
		break;
		 /*** 21 DE JUNIO               ****************/
 /*** Consulta de comprobantes de compra por fecha de retención *******************************************************************/
        case 545:
        $carg_reten_fechas_ret="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod,
 compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, 
retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, 
det_retenc.Ret_Cod, det_retenc.Ren_Cod, autorizaci.Aut_Sri FROM persona, proveedore, compras, retencion, det_retenc, 
renta_iva, autorizaci WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'  AND  autorizaci.Aut_Cod= retencion.Aut_Cod 
 GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num ASC";
	 //echo   $carg_reten_fechas_ret;

 return $carg_reten_fechas_ret;
		break;
		
     /** Consulta las retenciones de acuerdo al porcentaje de retención por fechas de retencion *******************************************/
		case 546:
		$carg_reten_fechas_por="SELECT persona.Prs_Nom,renta_iva.Ren_Por,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,
compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, 
retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
det_retenc.Ren_Cod, autorizaci.Aut_Sri FROM persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci
 WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'
AND renta_iva.Ren_Por=$Par_Sql[4] AND autorizaci.Aut_Cod= retencion.Aut_Cod  GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num";
//echo $carg_reten_fechas_por;
	   
 return $carg_reten_fechas_por;
		break;
/** Consulta las retenciones de acuerdo al codifo del formulario de retención por fecha de retención *******************************************/
case 547:
		$carg_reten_fechas_for="SELECT persona.Prs_Nom,renta_iva.Ren_Por ,renta_iva.Ren_Sri,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,
compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, 
retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
det_retenc.Ren_Cod, (det_retenc.Ret_Bas * renta_iva.Ren_Por) / 100 AS Renta,  COUNT(retencion.Cop_Cod) AS Num_Cop, autorizaci.Aut_Sri
FROM persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci
 WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  autorizaci.Aut_Cod= retencion.Aut_Cod AND
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
AND retencion.Ret_Est='$Par_Sql[3]'
AND renta_iva.Ren_Cod='$Par_Sql[4]'
 GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num";
       //echo $carg_reten_fechas_for;
	    return $carg_reten_fechas_for;
		break;
/*** OJO LLEVAR UESMA  **********************************/
/** Consulta de totales por codigo de formulario de retención y fecha de comprobante de compras ***************/
	   case 548:
       $consul_total_form_ret="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Por, renta_iva.Ren_Sri, COUNT(retencion.Cop_Cod) AS Num_Cop,
 SUM(det_retenc.Ret_Bas) AS Total, (SUM(det_retenc.Ret_Bas) * renta_iva.Ren_Por) / 100 AS Renta
FROM renta_iva, det_retenc,retencion, compras WHERE 
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND det_retenc.Ret_Cod=retencion.Ret_Cod
AND retencion.Tic_Cod=$Par_Sql[2]
AND retencion.Ret_Est='$Par_Sql[3]' AND compras.Cop_Cod=retencion.Cop_Cod AND 
compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
GROUP BY renta_iva.Ren_Cod ";
//echo $consul_total_form_ret;

       return $consul_total_form_ret;
       break;
/** Consulta de totales por codigo de formulario de retención y fecha de comprobante de retención ***************/
    case 549:
       $con_tot_form_ret_fec_ret="SELECT renta_iva.Ren_Cod, autorizaci.Aut_Sri, renta_iva.Ren_Por, renta_iva.Ren_Sri, COUNT(retencion.Cop_Cod) AS Num_Cop,
 SUM(det_retenc.Ret_Bas) AS Total
FROM renta_iva, det_retenc,retencion, autorizaci WHERE 
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND det_retenc.Ret_Cod=retencion.Ret_Cod
AND retencion.Tic_Cod=$Par_Sql[2] AND autorizaci.Aut_Cod= retencion.Aut_Cod 
AND retencion.Ret_Est='$Par_Sql[3]' AND 
retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
GROUP BY renta_iva.Ren_Cod";
//echo $con_tot_form_ret_fec_ret;

       return $con_tot_form_ret_fec_ret;
       break;
	   	/** Consulta de retenciones activas e inactivas buscadas por apellidos *********************/
		
		case 550:
		$carga_reten_modif_fac="SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ape LIKE '$Par_Sql[0]%' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
		return $carga_reten_modif_fac;
		break;

		/**** ******************************************************/
		case 551:
		$consultar_reten_dup_551="SELECT persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Dir ,tipo_compr.Tic_Des, compras.Cop_Num,compras.Cop_Cod,compras.Cop_Fec,compras.Cop_Cad,retencion.Ret_Num,  retencion.Ret_Fec, 
retencion.Ret_Con, retencion.Ret_Est, det_retenc.Ret_Bas, det_retenc.Ret_Imp,  renta_iva.Ren_Cod,  renta_iva.Ren_Sri, renta_iva.Ren_Sri, 
renta_iva.Ren_Por FROM persona,proveedore,compras,retencion, det_retenc, renta_iva,tipo_compr WHERE 
retencion.Cop_Cod='$Par_Sql[0]' AND retencion.Ret_Cod=det_retenc.Ret_Cod AND det_retenc.Ren_Cod=renta_iva.Ren_Cod 
AND retencion.Ret_Est='A' AND renta_iva.Ren_Est='A' AND compras.Cop_Cod=retencion.Cop_Cod AND compras.Cop_Est='A'
AND tipo_compr.Tic_Cod=retencion.Tic_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod";
    //    echo $consultar_reten_dup_551;
		return $consultar_reten_dup_551;
		break;
		/***** Consulto si la factura de compra a duplicar ya tiene registrada una retención con estado A=Activa *************************/
        	case 552:
		$consultar_retencion_existe_552="SELECT retencion.Ret_Cod FROM retencion WHERE retencion.Cop_Cod='$Par_Sql[0]'";
		//echo $consultar_retencion_existe_552;
		return $consultar_retencion_existe_552;
		break;
		

/** Consulta el detalle de las retenciones sumadas ***********/
		case 553:
		$cargar_reten_consul_553="SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape, persona.Prs_Dir, proveedore.Prv_Cod, compras.Aut_Cod, compras.Cop_Aut, compras.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, compras.Cop_Imf, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Est, retencion.Ret_Cod, retencion.Ret_Con, retencion.Ret_Fec, tipo_compr.Tic_Des, renta_iva.Ren_Por, renta_iva.Ren_Sri,
det_retenc.Ret_Int, sum(det_retenc.Ret_Bas) as Ret_Bas, IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp
, det_retenc.Ret_Cod, det_retenc.Ren_Cod, Ciu_Des FROM tipo_compr,persona, proveedore, compras, retencion, det_retenc, renta_iva, ciudad WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod 
AND compras.Tic_Cod=tipo_compr.Tic_Cod AND retencion.Ret_Cod=$Par_Sql[0] AND persona.Ciu_Cod = ciudad.Ciu_Cod 
GROUP BY renta_iva.Ren_Sri, renta_iva.Ren_Por ORDER BY det_retenc.Ret_Int ASC";
		//echo $cargar_reten_consul_553;
		return $cargar_reten_consul_553;
		break;

		/* Consulta todas las autorizaciones activas segun el tipo de comprobante de Mayor -> Menor */
		case 554:
		$autorizaciones_554 = "SELECT Aut_Sri, Aut_Cad FROM autorizaci WHERE Tic_Cod = $Par_Sql[0] AND Aut_Est = 'A' ORDER BY Aut_Cad DESC";
		return $autorizaciones_554;
		break;
		

		



		//***************************************************************************************//
		//***************************************************************************************//
		//***************************************************************************************//
		
 		

		/* Actualiza todos los saldos a favor */ 
		case 601:
		$update_saldo= "UPDATE saldo_favor SET saldo_favor.Saf_val = $Par_Sql[0] WHERE saldo_favor.Vet_Cod = $Par_Sql[1]"; 
	    return $update_saldo;
	    break;
		
       /* Retornar los valor del detalle de la deudas de facturas agrupadas */ 
		case 602:
		$agrupa_saldo= "SELECT det_deudas.Deu_Val, deudas.Pro_Cod FROM det_deudas, ventas, deudas WHERE 
					det_deudas.Vet_Cod = ventas.Vet_Cod AND deudas.Deu_Cod = det_deudas.Deu_Cod AND ventas.Vet_Cod 
					= '$Par_Sql[0]' GROUP BY det_deudas.Deu_Val, deudas.Pro_Cod"; 
	    return $agrupa_saldo;
	    break;

 		/* Actualiza todos los saldos a favor en base al Vet_Cod y el Pro_Cod*/ 
		case 603:
		$update_saldo2= "UPDATE saldo_favor SET saldo_favor.Saf_val = $Par_Sql[0] WHERE saldo_favor.Vet_Cod = $Par_Sql[1] 
						AND saldo_favor.Pro_Cod = $Par_Sql[2]"; 
	    return $update_saldo2;
	    break;

		case 604:
		/* Consulta del cliente de la factura por apellidos */
		$consultar_cli_factura = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, 
        IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper
        WHERE cliente.Prs_Cod = persona.Prs_Cod 
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' 
		AND ventas.Cli_Cod = cliente.Cli_Cod 
	    AND ventas.Tic_Cod = $Par_Sql[1]
		AND ventas.Vet_Est ='A'
        AND ventas.Vet_Cod NOT IN (select saldo_favor.Vet_Cod FROM saldo_favor, ventas WHERE 
        ventas.Vet_Cod = saldo_favor.Vet_Cod)
		ORDER BY persona.Prs_Ape, 
        persona.Prs_Nom, caja_aper.Caj_Fec, ventas.Vet_Cod Desc";
		return $consultar_cli_factura;
		break;

		case 605:
		/* Consulta del personal por cedula */
 		$consultar_Num_fact_cliente = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper WHERE cliente.Prs_Cod = persona.Prs_Cod 
 		AND cliente.Cli_Cod = ventas.Cli_Cod 
		AND ventas.Tic_Cod = $Par_Sql[1]
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  ventas.Vet_Cod = '$Par_Sql[0]'  
		AND ventas.Vet_Est ='A' 
		AND ventas.Vet_Cod NOT IN (select saldo_favor.Vet_Cod FROM saldo_favor, ventas WHERE 
        ventas.Vet_Cod = saldo_favor.Vet_Cod)
		ORDER BY persona.Prs_Ape, 
        persona.Prs_Nom, caja_aper.Caj_Fec, ventas.Vet_Cod Desc";
		return $consultar_Num_fact_cliente;
		break;
		
		
	/* Búsqueda de un personal por apellido */
		case 606:
		$bus_proa="SELECT Per_Cod, Prs_Ced, Prs_Ape, Prs_Nom, 
IF (Per_Est='A','Activo','Inactivo') as Per_Est
 FROM personal, persona 
WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND personal.Prs_Cod=persona.Prs_Cod";
		return $bus_proa;
		break;

	/* Búsqueda de un personal por Cédula */
		case 607:
		$bus_proc="SELECT Per_Cod, Prs_Ced, Prs_Ape, Prs_Nom, IF (Per_Est='A','Activo','Inactivo') as Per_Est
 FROM personal, persona WHERE Prs_Ced = '$Par_Sql[0]' AND personal.Prs_Cod=persona.Prs_Cod";
		return $bus_proc;
		break;
		
		case 608:
		/* Consulta de los datos del personal */
		$consultar_personal = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, 
persona.Prs_Ape, persona.Prs_Dir, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, personal.Per_Cod FROM personal, persona WHERE persona.Prs_Cod = personal.Prs_Cod AND personal.Per_Cod = '$Par_Sql[0]'";
		return $consultar_personal;
		break;
		
		case 609:
		/* Insertar datos a la tabla vale de Caja */
		$Insertar_vale = "INSERT INTO recibo( Rec_Cod, Aut_Cod, Con_Cod, Cja_Cod, Rcb_Fec, Rcb_Hor, Rcb_Obs, Rcb_Con) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3], '$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]')";
		//echo $Insertar_vale."<br>";
		return $Insertar_vale;
		break;
		
		case 610:
		/* consultar datos del vale de caja */
		$consultar_vale = "SELECT Val_Can, Val_Con, Val_Fec, Val_Obs, Val_Num, persona.Prs_Ape, persona.Prs_Nom
FROM vale_caja, personal, persona
WHERE personal.Per_Cod = vale_caja.Per_Cod 
AND persona.Prs_Cod = personal.Prs_Cod
AND vale_caja.Val_Num = $Par_Sql[0]";
       // echo  $consultar_vale;
		return $consultar_vale;
		break;
		
		case 611:
		/* consultar los vales de acuerdo al apellido de la persona */
		$consultarvale_apellido = "SELECT vale_caja.Val_Num, persona.Prs_Ape, 
persona.Prs_Nom, IF (personal.Per_Est='A','Activo','Retirado') as Per_Est, vale_caja.Val_Est FROM persona, personal, vale_caja WHERE personal.Prs_Cod = persona.Prs_Cod AND vale_caja.Per_Cod = personal.Per_Cod AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' ORDER BY persona.Prs_Ape, persona.Prs_Nom, vale_caja.Val_Num Desc";
        //echo  $consultar_vale;
		return $consultarvale_apellido;
		break;
		
		case 612:
		/* consultar los vales por el numero interno del vale*/
		$consultarvale_Num = "SELECT vale_caja.Val_Num, persona.Prs_Ape, 
persona.Prs_Nom, IF (personal.Per_Est='A','Activo','Retirado') as Per_Est, vale_caja.Val_Est FROM persona, personal, vale_caja WHERE personal.Prs_Cod = persona.Prs_Cod AND vale_caja.Per_Cod = personal.Per_Cod AND  vale_caja.Val_Num = $Par_Sql[0] ORDER BY persona.Prs_Ape, persona.Prs_Nom, vale_caja.Val_Num Desc";
        //echo  $consultarvale_Num;
		return $consultarvale_Num;
		break;
		
		case 613:
		/* Actualiza los datos del Recibo (caja chica) */
		$act_vale= "UPDATE recibo SET Rcb_Con = '$Par_Sql[0]', Rcb_Obs = '$Par_Sql[1]' WHERE  Rcb_Cod = $Par_Sql[2]";
		//echo $act_vale;
		return $act_vale;
		break;
		
		case 614:
		/* Consulta clientes que esten en deudas - por nombre*/
		$deudas_estudiantes_615 = "SELECT DISTINCT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente, deudas
WHERE cliente.Prs_Cod = persona.Prs_Cod 
AND cliente.Cli_Cod = deudas.Cli_Cod 
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'  
ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
	//echo $deudas_estudiantes_615;
		return $deudas_estudiantes_615;
		break;

	
		case 615:
		/* Consulta clientes que esten en deudas - por codigo */
		$deudas_estudiantesc = "SELECT DISTINCT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, 
IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente, deudas 
WHERE cliente.Prs_Cod = persona.Prs_Cod  
 AND cliente.Cli_Cod = deudas.Cli_Cod 
 AND persona.Prs_Ced = '$Par_Sql[0]' 
 ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
//echo $deudas_estudiantesc;

		return $deudas_estudiantesc;
		break;
		
	 /* Carga los estudiantes de ese semestre */
		case 616:
		$est_sem="SELECT niveles.Niv_Des, semestres.Sem_Cod, semestres.Sem_Par, persona.Prs_Ape, 
persona.Prs_Nom, persona.Prs_Ced, semestres.Sem_Cod, cliente.Cli_Cod
FROM semestres, niveles, periodos, estudiante, 
promocione, persona, cliente, matriculas
WHERE estudiante.Prs_Cod = persona.Prs_Cod 
AND persona.Prs_Cod = cliente.Prs_Cod 
AND semestres.Sem_Cod = matriculas.Sem_Cod
AND promocione.Pro_Cod = semestres.Pro_Cod 
AND estudiante.Est_Int = matriculas.Est_Int 
AND niveles.Niv_Cod = semestres.Niv_Cod 
AND periodos.Per_Int = semestres.Per_Int 
AND matriculas.Mat_Est = 'A'
AND semestres.Sem_Cod= '$Par_Sql[0]' 
GROUP BY persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced";
		//echo $est_sem;
		return $est_sem;
		break;
		
		 /* Carga la deuda de los estudiantes en base al semestre y al cliente*/
		case 617:
		$cargar_estudiante="SELECT (SUM(deudas.Deu_Sal)) as Deu_Sal, deudas.Deu_Cod, deudas.Pro_Cod, 
deudas.Cli_Cod, deudas.Nge_Cod, notasgener.Sem_Cod, persona.Prs_Ape,
persona.Prs_Nom
FROM deudas, item, producto, 
persona, cliente, notasgener
WHERE item.Ite_Cod = producto.Ite_Cod 
AND deudas.Cli_Cod = cliente.Cli_Cod 
AND persona.Prs_Cod = cliente.Prs_Cod 
AND deudas.Nge_Cod = notasgener.Nge_Cod 
AND deudas.Pro_Cod = producto.Pro_Cod 
AND notasgener.Sem_Cod= '$Par_Sql[0]' 
AND cliente.Cli_Cod = $Par_Sql[1]
AND deudas.Deu_Sal > 0 
GROUP BY persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced";
		//echo $cargar_estudiante;
		return $cargar_estudiante;
		break;		

		 /* Cargar de beca del estudiante dependiendo del codigo del cliente*/
		case 618:
		$cargar_beca="SELECT becas.Bec_Cod , matriculas.Mat_Int, persona.Prs_Ape, persona.Prs_Nom, det_becas.Pro_Cod,  becas.Bec_Por
FROM becas, matriculas, persona, cliente, estudiante, det_becas
WHERE estudiante.Prs_Cod = persona.Prs_Cod 
AND persona.Prs_Cod = cliente.Prs_Cod 
AND matriculas.Mat_Int = becas.Mat_Int
AND estudiante.Est_Int = matriculas.Est_Int  
AND matriculas.Mat_Est = 'A'
AND det_becas.Pro_Cod = '$Par_Sql[0]'
AND cliente.Cli_Cod = '$Par_Sql[1]'";
		return $cargar_beca;
		break;
		
		/* Actualizar el valor de la deuda*/
		case 619:
		$act_beca="UPDATE deudas SET Deu_Sal = $Par_Sql[1] WHERE Deu_Cod = '$Par_Sql[0]'";
		//echo $act_beca;
		return $act_beca;
		break;
		
		/* Consultar si existe saldo en caja chica*/
		case 620:
		$cons_cja="SELECT reposicion.Cja_Sal, reposicion.Cja_Cod FROM reposicion WHERE reposicion.Cja_Est='A' AND reposicion.Cja_Sal > 0 ";
		//echo $cons_cja;
		return $cons_cja;
		break;
		
		/* Consultar el fondo de caja chica*/
		case 621:
		$fondomax="SELECT confi_caja.Caj_Fon, confi_caja.Cja_Min 
FROM confi_caja WHERE confi_caja.Con_Cod = '1'";
		//echo $cons_cja;
		return $fondomax;
		break;
		
		/* Actulializar el saldo de caja chica*/
		case 622:
		$actufondo="UPDATE reposicion SET Cja_Sal = $Par_Sql[0] WHERE reposicion.Cja_Cod = $Par_Sql[1]";
		//echo $actufondo."<br>";
		return $actufondo;
		break;
		
		/* Seleccionar datos del vale de caja de acuerdo al apellido de la persona*/
		case 623:
		$selec_vale="SELECT Val_Can, Val_Con, Val_Fec, Val_Obs, Val_Num, persona.Prs_Ape, persona.Prs_Nom,
IF (Val_Est='A','A','I') as Val_Est
FROM vale_caja, personal, persona
WHERE personal.Per_Cod = vale_caja.Per_Cod 
AND persona.Prs_Cod = personal.Prs_Cod
AND Prs_Ape LIKE '%$Par_Sql[0]%'";
		//echo $selec_vale;
		return $selec_vale;
		break;
		
		/* Seleccionar datos del vale de caja de acuerdo al numero del vale*/
		case 624:
		$selec_valenum="SELECT Val_Can, Val_Con, Val_Fec, Val_Obs, Val_Num, persona.Prs_Ape, persona.Prs_Nom,
IF (Val_Est='A','A','I') as Val_Est
FROM vale_caja, personal, persona
WHERE personal.Per_Cod = vale_caja.Per_Cod 
AND persona.Prs_Cod = personal.Prs_Cod
AND vale_caja.Val_Num = '$Par_Sql[0]'";
		//echo $selec_valenum;
		return $selec_valenum;
		break;
		
		case 625:
		/* Actualizar estado del vale*/ 
		$actualizar_vale = "UPDATE vale_caja SET Val_Est = '$Par_Sql[0]' WHERE Val_Num = '$Par_Sql[1]'";
		return $actualizar_vale;
		break;
		
		case 626:
		// consulta para verificar si la factura anulada tiene algun detalle de la deuda//
		$con_valedet = "SELECT det_caja_c.Cja_Cod, vale_caja.Val_Num, caja_chica.Cja_Cod, vale_caja.Val_Can, caja_chica.Cja_Val, det_caja_c.Det_Val 
FROM det_caja_c, vale_caja, caja_chica WHERE det_caja_c.Val_Num = vale_caja.Val_Num
AND caja_chica.Cja_Cod = det_caja_c.Cja_Cod AND vale_caja.Val_Num = $Par_Sql[0]";
        //echo $con_valedet;
		return $con_valedet;
		break;
		
		case 627: 
		/* Actualizar datos en caja chica*/
 		$actualizar_cajachica = "UPDATE caja_chica SET Cja_Val =  Cja_Val $Par_Sql[2] $Par_Sql[1] WHERE Cja_Cod = $Par_Sql[0]";
		//echo $actualizar_cajachica;
		return $actualizar_cajachica;
		break;
		
		case 628:
		/* Inserta el valor del vale en el  detalle del vale */
		$Sql_628 = "INSERT INTO det_recibo(Rcb_Cod,Gas_Cod,Rcb_Val,Rcb_Can,Rcb_Imp) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4])";
		//echo $Sql_628;
		return $Sql_628;
		break;
		
		case 629:
		$Sql_629="SELECT 
					  autorizado.Aut_Cod,
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  autorizado.Aut_Est
				   FROM
					  persona
					  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
					  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
					  INNER JOIN autorizado ON (distributi.Dis_Cod = autorizado.Dis_Cod)
				   WHERE
					  autorizado.Aut_Cod=$Par_Sql[0] AND autorizado.Aut_Est='A'";
		//echo $Sql_629;
		return $Sql_629;
		break;



		
		
		case 631:
		/*Consulta el punto de impresion*/
		$cons_punto = "SELECT puntos_imp.Pun_Cod, puntos_imp.Pun_Des FROM puntos_imp WHERE puntos_imp.Pun_Cod = '$Par_Sql[0]'";
		//echo $cons_punto;
		return $cons_punto;
		break;
		
		case 632:
		/*Consulta los precios de los productos dependiendo del codigo del producto*/
		$cons_precio = "SELECT precios.Pre_Cod, producto.Pro_Cod, producto.Ite_Cod, item.Ite_Cor, precios.Pre_Pvp
FROM precios, producto, item 
WHERE producto.Pro_Cod = precios.Pro_Cod
AND producto.Ite_Cod = item.Ite_Cod
AND producto.Pro_Cod = $Par_Sql[0]";
		//echo $cons_precio;
		return $cons_precio;
		break;
		
		case 633:
		/*Consulta de los rubros*/ 
  		$cons_rubros = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, 
 producto.Pro_Sal FROM producto, item WHERE producto.Ite_Cod = item.Ite_Cod AND item.Ite_Lar LIKE '$Par_Sql[0]%' AND producto.Pro_Tip = 'S' AND producto.Pro_Est = 'A'";
		//echo $cons_rubros;
		return $cons_rubros;
		break;
		
		case 634:
		/*Consulta los precios de los productos dependiendo del Pro_Ide*/
		$cons_precio = "SELECT precios.Pre_Cod, precios.Pre_Pvp, producto.Pro_Ide
FROM precios, producto WHERE producto.Pro_Cod = precios.Pro_Cod AND producto.Pro_Ide = '$Par_Sql[0]'";
		//echo $cons_precio;
		return $cons_precio;
		break;
		
		case 635:
		/* Insertar datos a la tabla detalle del semestre */
		$Insertar_preciosemestre = "INSERT INTO semestre_det( Sem_Cod, Pre_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
    	//echo $Insertar_preciosemestre;
		return $Insertar_preciosemestre;
		break;
		
		case 636:
		/* Insertar datos a la tabla detalle del semestre */
		$consul_itemproducto = "SELECT producto.Pro_Cod, producto.Ite_Cod, item.Ite_Lar
FROM producto, item  WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Ide = '$Par_Sql[0]'";
    	//echo $consul_itemproducto;
		return $consul_itemproducto;
		break;
		
		case 637:
		/* Consultar el precio dependiendo del codigo del semestre y del producto */
		$consul_codsemes = "SELECT precios.Pre_Cod, precios.Pre_Pvp, producto.Pro_Cod, semestre_det.Pre_Cod, semestre_det.Sem_Cod, item.Ite_Lar FROM precios, producto, semestre_det, item
WHERE producto.Pro_Cod = precios.Pro_Cod 
AND item.Ite_Cod = producto.Ite_Cod
AND semestre_det.Pre_Cod = precios.Pre_Cod
AND producto.Pro_Ide = '$Par_Sql[0]'
AND semestre_det.Sem_Cod = $Par_Sql[1]";
    	//echo $consul_codsemes;
		return $consul_codsemes;
		break;
		
		case 638:
		/* Consultar datos del precio dependiendo del codigo del semestre */
		$consul_sem = "SELECT precios.Pre_Cod, precios.Pre_Pvp, producto.Pro_Cod, semestre_det.Pre_Cod, semestre_det.Sem_Cod, item.Ite_Lar, producto.Pro_Ide
FROM precios, producto, semestre_det, item
WHERE producto.Pro_Cod = precios.Pro_Cod 
AND item.Ite_Cod = producto.Ite_Cod
AND semestre_det.Pre_Cod = precios.Pre_Cod
AND semestre_det.Sem_Cod = $Par_Sql[0]";
    	//echo $consul_sem;
		return $consul_sem;
		break;
		
		case 639:
		/*Borrar los campos del detalle del semestre*/
		$Borrar_sem_det = "DELETE FROM semestre_det WHERE Sem_Cod = '$Par_Sql[0]'";
		//echo $Borrar_sem_det;
		return $Borrar_sem_det;
		break;
		
		case 640:
		/*verificarsi existe ya un precio en la tabla semestre_det en base al Sem_cod y al Pre_Cod*/
		$Select_Pre_cod = "SELECT semestre_det.Pre_Cod, semestre_det.Sem_Cod, item.Ite_Lar, producto.Pro_Ide
FROM producto, semestre_det, item, precios
WHERE item.Ite_Cod = producto.Ite_Cod
AND precios.Pro_Cod = producto.Pro_Cod
AND semestre_det.Pre_Cod = precios.Pre_Cod
AND precios.Pro_Cod = '$Par_Sql[0]'
AND semestre_det.Sem_Cod = '$Par_Sql[1]'";
		//echo $Select_Pre_cod;
		return $Select_Pre_cod;
		break;
		
		
		//Actualizar tambien la 69//
		/* Carga la deuda total del semestre y de los estudiantes que tengan matricula inactiva */
		case 642:
		$matri_inact="SELECT SUM(Deu_Sal) as Deu_Sal 
FROM deudas, notasgener, matriculas
WHERE deudas.Nge_Cod = notasgener.Nge_Cod 
AND matriculas.Mat_Int = notasgener.Mat_Int
AND matriculas.Mat_Est = 'I'
AND notasgener.Sem_Cod = '$Par_Sql[0]' AND deudas.Deu_Sal > 0";
        //echo $matri_inact;
		return $matri_inact;
	    break;
		
		//Actuailizar la sentencia 70//
		///* Carga la deuda de los estudiantes en base al semestre y a la matricula inactiva */
		case 643:
		$estudiante_mat_inact= "SELECT  carreras.Car_Nom, niveles.Niv_Des, notasgener.Sem_Cod, semestres.Sem_Par,
IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec, 
modalidad.Mod_Des, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, deudas.Deu_Cod, deudas.Pro_Cod, deudas.Cli_Cod,  
deudas.Deu_Fec, deudas.Deu_Sal, item.Ite_Lar, producto.Pro_Ide, deudas.Nge_Cod, deudas.Asi_Int,
semestres.Sem_Cod, cliente.Cli_Cod, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', 
IF (MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',IF (MONTH(Per_Fea)=7,'Julio', 
IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,'Octubre', 
IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,
IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (MONTH(Per_Fef)=3, 'Marzo', 
IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio', 
IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', 
IF (MONTH(Per_Fef)=10, 'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) 
as Mes_Fin FROM semestres, niveles, notasgener, periodos, estudiante, persona, cliente, matriculas, carreras, 
deudas, promocione, item, producto, modalidad WHERE estudiante.Prs_Cod = persona.Prs_Cod 
AND persona.Prs_Cod = cliente.Prs_Cod AND item.Ite_Cod = producto.Ite_Cod AND estudiante.Est_Int = matriculas.Est_Int 
AND deudas.Cli_Cod = cliente.Cli_Cod AND semestres.Sem_Cod = notasgener.Sem_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND niveles.Niv_Cod = semestres.Niv_Cod AND periodos.Per_Int = semestres.Per_Int AND notasgener.Mat_Int = matriculas.Mat_Int AND semestres.Pro_Cod = promocione.Pro_Cod AND promocione.Car_Int = carreras.Car_Int AND deudas.Nge_Cod = notasgener.Nge_Cod AND deudas.Pro_Cod = producto.Pro_Cod AND matriculas.Mat_Est = 'I' AND notasgener.Sem_Cod= '$Par_Sql[0]' 
AND deudas.Deu_Sal > 0 GROUP BY persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced";
       // echo $estudiante_mat_inact;
        return $estudiante_mat_inact;
	    break;
		
		/* buscar todos los rubros dependiendo del codigo de la categoria */
		  case 644:
		  $buscar_cat="SELECT Ite_Cor, Ite_Lar, item.Ite_Cod, producto.Pro_Cod FROM item, categorias, producto
		WHERE categorias.Cat_Cod = item.Cat_Cod
		AND producto.Ite_Cod = item.Ite_Cod
		AND categorias.Cat_Cod = '$Par_Sql[0]' ORDER BY item.Ite_Lar";
				//echo $buscar_cat;
	    return $buscar_cat;
 	    break;		
		   /** Selecionar el Pro_ide en base a un parametro**/
		case 645:
		$codigo_ProIde="SELECT Pro_Ide FROM producto WHERE Pro_Ide ='$Par_Sql[0]'";
		//echo $codigo_ProIde;
        return $codigo_ProIde;
        break;


		/***SENTENIAS MIRIAM ***/////
		case 646:
		/*$deudas_est_646="SELECT persona.Prs_Ape, persona.Prs_Nom, matriculas.Mat_Int, matriculas.Sem_Cod, cliente.Cli_Cod, persona.Prs_Cod, notasgener.Nge_Cod
FROM matriculas, persona, estudiante, cliente, notasgener
WHERE notasgener.Sem_Cod= '$Par_Sql[0]' AND matriculas.Est_Int=estudiante.Est_Int AND cliente.Prs_Cod = persona.Prs_Cod 
AND notasgener.Mat_Int = matriculas.Mat_Int
AND estudiante.Prs_Cod=persona.Prs_Cod ORDER BY  persona.Prs_Ape, persona.Prs_Nom"; ESTA SQL MUESTRA TODOS LOS ESTUDIANTES HASTA LOS DE ARRASTRE */
		
$deudas_est_646="SELECT persona.Prs_Ape, persona.Prs_Nom, matriculas.Mat_Int, matriculas.Sem_Cod, cliente.Cli_Cod, persona.Prs_Cod, notasgener.Nge_Cod
FROM matriculas, persona, estudiante, cliente, notasgener, semestres
WHERE notasgener.Sem_Cod= '$Par_Sql[0]' AND matriculas.Est_Int=estudiante.Est_Int AND cliente.Prs_Cod = persona.Prs_Cod 
AND notasgener.Mat_Int = matriculas.Mat_Int AND matriculas.Sem_Cod = semestres.Sem_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod 
AND estudiante.Prs_Cod=persona.Prs_Cod AND matriculas.Mat_Est='A' ORDER BY  persona.Prs_Ape, persona.Prs_Nom";

//echo $deudas_est_646;
return $deudas_est_646;
break;

//carga deuda dependiendo del cliente
case 647:
$deu_indi_647="SELECT deudas.Deu_Val, deudas.Pro_Cod, deudas.Asi_Int, deudas.Nge_Cod, Bec_Cod FROM deudas, 
producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras
WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND
deudas.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod 
AND semestres.Per_Int = periodos.Per_Int AND semestres.Niv_Cod = niveles.Niv_Cod 
AND periodos.Mod_Cod = modalidad.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
carreras.Car_Int = promocione.Car_Int AND deudas.Cli_Cod = '$Par_Sql[0]'  AND deudas.Pro_Cod='$Par_Sql[1]' AND deudas.Nge_Cod='$Par_Sql[2]' AND deudas.Asi_Int=$Par_Sql[3]
 AND Deu_Rec = 0";
//echo $deu_indi_647;
return $deu_indi_647;
break;


/***********************************************************/
case 648:
			$consultar_semestres_648="SELECT niveles.Niv_Des, niveles.Niv_Cod, semestres.Sem_Par, modalidad.Mod_Des, semestres.Sem_Cod,
		    IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as Sem_Sec FROM  semestres, niveles, modalidad, periodos
  		    WHERE  niveles.Niv_Cod=semestres.Niv_Cod AND semestres.Per_Int=periodos.Per_Int AND modalidad.Mod_Cod= periodos.Mod_Cod
            AND semestres.Sem_Cod='$Par_Sql[0]' ORDER BY niveles.Niv_Cod ASC";
			//echo $consultar_semestres_6;
		return $consultar_semestres_648;
		break;
case 649:
		$rubros_deudas_649= "SELECT  deudas.Pro_Cod, item.Ite_Cor, deudas.Pro_Cod FROM notasgener, producto, item, deudas WHERE deudas.Pro_Cod = producto.Pro_Cod AND notasgener.Nge_Cod = deudas.Nge_Cod AND item.Ite_Cod = producto.Ite_Cod
AND notasgener.Sem_Cod= '$Par_Sql[0]' GROUP BY producto.Pro_Cod";
	   return $rubros_deudas_649;
	   break;
case 650:
$deud="SELECT  deudas.Deu_Val FROM deudas WHERE deudas.Cli_Cod = '$Par_Sql[0]' AND deudas.Pro_Cod = '$Par_Sql[1]' AND deudas.Nge_Cod= '$Par_Sql[2]' AND deudas.Asi_Int = '$Par_Sql[3]' AND deudas.Deu_Val > 0";
///echo $deud;
return $deud;
break;

	case 651:
	$deud_pagad="SELECT deudas.Deu_Val, deudas.Pro_Cod, deudas.Asi_Int, deudas.Nge_Cod, Bec_Cod FROM deudas, 
	producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras
	WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND
	deudas.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod 
	AND semestres.Per_Int = periodos.Per_Int AND semestres.Niv_Cod = niveles.Niv_Cod 
	AND periodos.Mod_Cod = modalidad.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
	carreras.Car_Int = promocione.Car_Int AND notasgener.Sem_Cod='$Par_Sql[0]'";
	//echo $deud_pagad;
	return $deud_pagad;
	break;
	
	//busca la cabecera de los rubros
	case 652:
	$rubros_deudas= "SELECT DISTINCT 
  item.Ite_Cor,
  deudas.Pro_Cod
FROM
  notasgener
  INNER JOIN deudas ON (notasgener.Nge_Cod = deudas.Nge_Cod)
  INNER JOIN producto ON (deudas.Pro_Cod = producto.Pro_Cod)
  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
WHERE
  Deu_Rec = 0 AND 
  Deu_Val <> 0 AND 
  notasgener.Sem_Cod = $Par_Sql[0]
ORDER BY
  Pro_Cod";
//echo $rubros_deudas;
	   return $rubros_deudas;
	   break;
	   
	 //  
	case 653:
	$rubros_deudas= "SELECT deudas.Pro_Cod, Deu_Rec, deudas.Deu_Val FROM notasgener, producto, deudas 
WHERE deudas.Pro_Cod = producto.Pro_Cod AND notasgener.Nge_Cod = deudas.Nge_Cod 
AND notasgener.Sem_Cod= '$Par_Sql[0]' AND deudas.Pro_Cod='$Par_Sql[1]' AND Deu_Rec=0 GROUP BY producto.Pro_Cod";
//echo $rubros_deudas;
	   return $rubros_deudas;
	   break;
	
		case 654:
		/* Consulta de estudiante por cédula, mostrando las carreras en las que esta matriculado  */								
		$cargar_carreras_654="SELECT DISTINCT 
  persona.Prs_Ced,
  persona.Prs_Nom,
  persona.Prs_Ape,
  carreras.Car_Nom,
  modalidad.Mod_Des,
  cliente.Cli_Cod, modalidad.Mod_Cod
FROM
  persona
  INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
  INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
  INNER JOIN semestres ON (matriculas.Sem_Cod = semestres.Sem_Cod)
  INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
  INNER JOIN carreras ON (promocione.Car_Int = carreras.Car_Int)
  INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
  INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
  INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
WHERE
  persona.Prs_Cod = $Par_Sql[0]";
		//echo $cargar_carreras_654;						
		return $cargar_carreras_654;
		break;
		
		case 655:
		$carga_pro_inter="SELECT prod_inter.Pro_Cod FROM prod_inter";
		return $carga_pro_inter;
		break;
		
		case 656:
		$consulta_deuda_656 = "SELECT deudas.Pro_Cod, Pro_Ide, item.Ite_Lar, Deu_Val, Deu_Fec, niveles.Niv_Des, semestres.Sem_Cod, 
					semestres.Sem_Par, IF (Sem_Sec = 'D', 'Diurna', IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna')) as 
					Sem_Sec, modalidad.Mod_Des, Car_Nom, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', IF 
					(MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF 
					(MONTH(Per_Fea)=5, 'Mayo', IF(MONTH(Per_Fea)=6, 'Junio',IF (MONTH(Per_Fea)=7,'Julio', IF 
					(MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 'Septiembre', IF (MONTH(Per_Fea)=10,'Octubre', 
					IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, YEAR(Per_Fef) as Ann_Fin,IF 
					(MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (MONTH(Per_Fef)=3, 
					'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio', 
					IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', 
					IF (MONTH(Per_Fef)=10, 'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin,
					Asi_Int, deudas.Nge_Cod, Deu_Obs, carreras.Car_Int, Iva_Por, producto.Iva_Cod, Bec_Cod, Deu_Rec  
					FROM deudas, producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras, iva
					WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND deudas.Nge_Cod =
					notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND semestres.Per_Int = periodos.Per_Int
					AND semestres.Niv_Cod = niveles.Niv_Cod AND periodos.Mod_Cod = modalidad.Mod_Cod AND
					semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND
					producto.Iva_Cod = iva.Iva_Cod AND deudas.Cli_Cod = '$Par_Sql[0]' AND deudas.Pro_Cod='$Par_Sql[1]' AND Deu_Rec = 0 ORDER BY deudas.Deu_Fec";
				//	echo $consulta_deuda_656;
		return $consulta_deuda_656;
		break;
		
		
		/*** Consultar días plazo interés  ***************************************************************************/
		case 657:
		$mora_interes_dias="SELECT Int_Dia, Int_Por FROM interes";
		//echo $mora_interes_dias;
		return $mora_interes_dias;
		break;			

		case 658:
		/* Consulta de los bancos del plan de cuentas */
		$bancos_plan_658 = "SELECT Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan 
		 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND banco.Ban_Cod='$Par_Sql[0]' AND Ban_Est = 'A' ORDER BY Pld_Cdc, Pld_Des";
		 //echo $bancos_plan_658;
		return $bancos_plan_658;
		break;		
		/**** Consulto si existe el cliente por código de persona *************/
		case 659:
		/* Consulta para verificar si el cliente se encuentar registrado */
		$consultar_personal = "SELECT Cli_Cod FROM cliente, persona WHERE persona.Prs_Cod = cliente.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]'";
		return $consultar_personal;
		break;

		case 660:
		/* Consulta de los bancos del plan de cuentas */
		$cod_confimatr = "SELECT confimatri.Con_Mac FROM confimatri WHERE Con_Cod =1";
		//echo $cod_confimatr;
		return $cod_confimatr;
		break;
	      
              case 661:
		/* Consulta de las carreras activas */
		$con_carreras_661 = "SELECT Car_Int, Car_Nom FROM carreras WHERE Car_Est = 'A' ORDER BY Car_Nom";
		//echo $cod_confimatr;
		return $cod_confimatr;
		break;

		case 662:
		/* Consulta del cliente de la factura por apellidos */
		$consultar_cli_factura = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper  WHERE cliente.Prs_Cod = persona.Prs_Cod 
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND ventas.Cli_Cod = cliente.Cli_Cod
        AND YEAR(Caj_Fec) = '$Par_Sql[2]' $Par_Sql[3] 
        AND ventas.Tic_Cod = $Par_Sql[1] 
		ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
		//echo $consultar_cli_factura;
		return $consultar_cli_factura;
		break;

		case 663:
		/* Consulta del personal por cedula */
 		$consultar_Num_fact_cliente = "SELECT ventas.Cli_Cod, persona.Prs_Ape, 
        persona.Prs_Nom, ventas.Vet_Num, caja_aper.Caj_Fec, ventas.Vet_Cod, IF (cliente.Cli_Est='A','Activo','Retirado') 
 		as Cli_Est, ventas.Vet_Est FROM persona, cliente, ventas, caja_aper  WHERE cliente.Prs_Cod = persona.Prs_Cod 
 		AND cliente.Cli_Cod = ventas.Cli_Cod AND ventas.Tic_Cod = $Par_Sql[1]
        AND caja_aper.Caj_Cod = ventas.Caj_Cod AND  ventas.Vet_Cod = '$Par_Sql[0]' 
		ORDER BY caja_aper.Caj_Fec, persona.Prs_Ape, persona.Prs_Nom, ventas.Vet_Num Desc";
		//echo $consultar_Num_fact_cliente;
		return $consultar_Num_fact_cliente;
		break;

		/* Consulta el detalle del periodo en base a codigo interno*/
		case 664:
		$consulta_periodos_664="SELECT DISTINCT
  view_periodos_suc.Per_Int,
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin,
  view_periodos_suc.Per_Fea,
  view_periodos_suc.Per_Fef,
  view_periodos_suc.Per_Fec,
  view_periodos_suc.Suc_Des,
  modalidad.Mod_Des
FROM
  modalidad
  INNER JOIN view_periodos_suc ON (modalidad.Mod_Cod = view_periodos_suc.Mod_Cod)
WHERE
  view_periodos_suc.Per_Int = $Par_Sql[0]";		
		//echo $consulta_periodos_664;
		return $consulta_periodos_664;		
		break;
		
		/*Consulta de semestres dependiendo de la carrera*/
		case 665:
		$consulta_periodos_665="SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Per_Int,
 		 view_cursos_mal.Car_Int, carreras.Car_Nom FROM view_cursos_mal INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int) WHERE view_cursos_mal.Car_Int = $Par_Sql[0] AND  view_cursos_mal.Per_Int = $Par_Sql[1] AND  view_cursos_mal.Sem_Est = 'A'
		ORDER BY view_cursos_mal.Niv_Cod";		
		//echo $consulta_periodos_7;
		return $consulta_periodos_665;		
		break;
		
		case 666:
		/* Consulta de la tabla de configuracion de contabilidad */
		$confi_conta_211 = "SELECT Con_Cod, Sri_Num, Col_Eli FROM confi_cont";
		return $confi_conta_211;
		break;
		
		/* Caegado del Número de Comprobate que sigue */
		case 667:
		$cargar_numcom="SELECT Max(Com_Num)+1 as Com_Num FROM comprobantes WHERE Tia_Cod='$Par_Sql[0]' AND Pec_Cod=$Par_Sql[1] AND Com_Num > 0";//Antes Com_Tip
		//echo $cargar_numcom;
		return $cargar_numcom;
		break;

		case 668:
		/** Selecionar el numero maximo de comprobante mensual según el tipo**/
		$num_com_152="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod = $Par_Sql[0] AND Pec_Cod = $Par_Sql[1] AND 
					MONTH(Com_Fec) = $Par_Sql[2]";
					//echo $num_com_152;
		return $num_com_152;
		break;
		
		/***********************  C H E Q U E S *********************************		
		/* Cargado cheques según el número de comprobante de egreso */
		case 669:
		$con_cheques_143="SELECT comprobantes.Com_Cod, comprobantes.Com_Num, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Cob, Che_Obs, Com_Est, Che_Fec, cheques.Ban_Cod, cheques.Prv_Cod FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod
      AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod  
      AND comprobantes.Com_Cod = $Par_Sql[0] ORDER BY Che_Num";
		return $con_cheques_143;
		break;
		
		/*SENTENCIAS COMPRAS */
		case 701:
		/* Búsqueda de un proveedor por apellido */
		$bus_proa_701="SELECT proveedore.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, proveedore.Prv_Fax,
		            IF (Prv_Est='A','Activo','Inactivo') as Prv_Est
                   FROM proveedore INNER JOIN persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ape LIKE '%$Par_Sql[0]%'";
		//echo $bus_proa_701;
		return $bus_proa_701;
		break;

	    /* Búsqueda de un proveedor por Cédula */
		case 702:
		$bus_proc_702="SELECT proveedore.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, proveedore.Prv_Fax,
		            IF (Prv_Est='A','Activo','Inactivo') as Prv_Est
                   FROM proveedore INNER JOIN persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ced= '$Par_Sql[0]'";
		return $bus_proc_702;
		break;
		
		/* Consultar si la factura ya se encuentra registrada teniendo en cuenta si es de un mismo proveedor */
		case 703:
		$con_exi_fac_com_703="SELECT compras.Cop_Num FROM compras WHERE compras.Cop_Num='$Par_Sql[0]'  AND compras.Prv_Cod=$Par_Sql[1] AND compras.Cop_Est='A'";
		//echo $con_exi_fac_com;
		return $con_exi_fac_com_703;
		break;

		case 704: 
		/* insertar datos de la factura de compra*/
		$inser_factcom_704 = "INSERT INTO compras (Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Aut, Cop_Fec, Cop_Reg, Cop_Obs, Cop_Cad, Cop_Imf, Tri_Cod, Cop_Des, Pec_Cod) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], UPPER('$Par_Sql[3]'), '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', UPPER('$Par_Sql[8]'), '$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]', $Par_Sql[12])";
		//echo $inser_factcom_704;		
		return $inser_factcom_704;
		break;
		
		case 705: 
	//	det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod, Ice_Int)
		$inser_detafaccom_705 = "INSERT INTO det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Ice_Int, Adq_Cod)
		VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'), $Par_Sql[4] , $Par_Sql[5], $Par_Sql[6], $Par_Sql[7], $Par_Sql[8])";
		//echo $inser_detafaccom_705;
		return $inser_detafaccom_705;
		break;
		
		/* Consulta de los iva activos */
		case 706:		
		$consultar_iva_706 = "SELECT iva.Iva_Cod, Iva_Por FROM iva WHERE iva.Iva_Est = 'A'";
		return $consultar_iva_706;
		break;
		
		/** Consultas de porcentajes del I.C.E. ****************************/
		case 707:
		$con_ice_porce_707="SELECT Ice_Int, Ice_Cod, Ice_Por, Ice_Sri FROM ice WHERE ice.Ice_Est='A'";
		return $con_ice_porce_707;
		break;
		
		/* Consulta de los datos del proveedor */
		case 708:	
		$consultar_proveedore_708 = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
						persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, proveedore.Prv_Cod
						FROM proveedore, persona WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Cod = '$Par_Sql[0]'";
		return $consultar_proveedore_708;
		break;
		
		/** Selecciona ciudades del ECUADOR *****************************************************/
		case 709:
		$con_ciuda_ecua_709="SELECT Ciu_Des, Ciu_Cod  FROM ciudad WHERE ciudad.Pas_Cod=1 ORDER BY Ciu_Des ASC ";
		return $con_ciuda_ecua_709;
		break;
		
		case 710:
		/*Consulta de los rubros sin precio*/ 
  		$productos_710 = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar, producto.Iva_Cod, Iva_Por, Pre_Pvp
					FROM producto, item, iva, precios       
					WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Iva_Cod = iva.Iva_Cod AND 
					item.Ite_Lar LIKE '%$Par_Sql[0]%' AND producto.Pro_Est = 'A' AND producto.Pro_Cod = precios.Pro_Cod AND
					producto.Pro_Cod NOT IN (SELECT  deudas.Pro_Cod FROM  deudas, notasgener WHERE deudas.Nge_Cod = 
					notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] AND notasgener.Sem_Cod = $Par_Sql[2])";
					//echo $productos_28;
		return $productos_710;
		break;
		
		/*Cargar el sustento */
		case 711:
		$sustento="SELECT sustento.Tri_Sri, sustento.Tri_Cod, sustento.Tri_Des, sustento.Tri_Est FROM sustento WHERE sustento.Tri_Est='A'";
		return $sustento;
		break;
		
		/*Cargar la adquisicon */
		case 712:
		$adquisicion="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Cor , adquisicio.Adq_Des, adquisicio.Adq_Est FROM adquisicio WHERE adquisicio.Adq_Est='A'"; 
		//echo $adquisicion;
		return $adquisicion;
		break;
		
				/* 
		OJO SUBIR SERVIDOR
		Consulta las facturas que se pueden modificar con Estado=Activa */
		case 713:
		$carg_fac_com_mofi_713="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, 
		tipo_compr.Tic_Des 
		FROM persona, proveedore, compras, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod
		AND proveedore.Prv_Cod=compras.Prv_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' 
		 AND Pec_Cod = '$Par_Sql[2]' $Par_Sql[3] ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom"; //AND compras.Cop_Est='A'
       	 // echo $carg_fac_com_mofi_713;
		return $carg_fac_com_mofi_713;
		break;
		/* 
		OJO SUBIR SERVIDOR
		Consulta las facturas que se pueden modificar con estado Activo por número de factura */
		case 714:
		$con_fac_mod_num_714="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, tipo_compr.Tic_Des
			FROM persona, compras, proveedore, tipo_compr
			WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod	
			AND compras.Tic_Cod = tipo_compr.Tic_Cod
			AND compras.Cop_Num='$Par_Sql[0]'  AND Pec_Cod = '$Par_Sql[2]' $Par_Sql[3]  ORDER BY compras.Cop_Cod ASC "; //AND compras.Cop_Est='A'
			//echo $con_fac_mod_num_714;
		return $con_fac_mod_num_714;
		break;
		
		/* Consulta de los datos del proveedor */
		case 715: 
		$con_fac_proveedo_715="SELECT persona.Prs_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Dir, compras.Cop_Cod,
compras.Cop_Num, compras.Prv_Cod, compras.Cop_Aut, compras.Ciu_Cod, compras.Cop_Fec, compras.Cop_Reg, 
compras.Cop_Cad, compras.Cop_Imf, compras.Cop_Des, det_compra.Cop_Int, det_compra.Cop_Pru,
compras.Cop_Obs, compras.Cop_Est, det_compra.Cop_Pro, det_compra.Cop_Can, det_compra.Cop_Imp,
det_compra.Cop_Dec, det_compra.Iva_Cod, det_compra.Ice_Int, iva.Iva_Por, ciudad.Ciu_Des, 
det_compra.Adq_Cod, adquisicio.Adq_Des, compras.Tri_Cod, compras.Tic_Cod, tipo_compr.Tic_Des 
FROM persona, proveedore, compras, det_compra, iva, ciudad, adquisicio, sustento, tipo_compr 
WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Cop_Cod=det_compra.Cop_Cod 
AND compras.Cop_Cod=$Par_Sql[0] AND det_compra.Iva_Cod=iva.Iva_Cod AND ciudad.Ciu_Cod=compras.Ciu_Cod
AND tipo_compr.Tic_Cod = compras.Tic_Cod
AND adquisicio.Adq_Cod = det_compra.Adq_Cod AND sustento.Tri_Cod = compras.Tri_Cod";
		//echo $con_fac_proveedo_715;
		return $con_fac_proveedo_715; 
		break;		
		
		/** Consulta de los porcentajes del I.C.E ***************/	
	case 716:
		$con_fac_tot_com_716="SELECT ice.Ice_Por
		FROM ice, det_compra
		WHERE  ice.Ice_Int=det_compra.Ice_Int AND det_compra.Cop_Int=$Par_Sql[0]";
		//echo $con_fac_tot_com_716;
		return 	$con_fac_tot_com_716;
		break;
		
		/* Carga las facturas las retenciones que se deben modifcar producto de la actualización de una factura */
		case 717:
		$cons_rete_actuali_mod_fac_717="SELECT compras.Cop_Cod FROM compras WHERE compras.Cop_Cod=$Par_Sql[0]
AND compras.Cop_Cod IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')";
		return $cons_rete_actuali_mod_fac_717;
		break; 
		
		/* Consultar el codigo de la retencion a modificar en base al codigo de la factura de compra */
		case 718:
		$carga_codigo_modif_rente_718="SELECT retencion.Ret_Cod, retencion.Cop_Cod, retencion.Vnd_Cod, retencion.Aut_Cod,retencion.Tic_Cod ,retencion.Ret_Num, 
retencion.Ret_Fec, retencion.Ret_Con, det_retenc.Ret_Int, det_retenc.Ren_Cod, det_retenc.Ret_Bas, det_retenc.Ret_Imp FROM retencion, det_retenc 
WHERE retencion.Ret_Est='A' AND retencion.Cop_Cod='$Par_Sql[0]' AND det_retenc.Ret_Cod=retencion.Ret_Cod";
		//echo $carga_codigo_modif_rente_718;		
		return $carga_codigo_modif_rente_718;
		break;

	/*** Modificación de los datos de la cabecera de la Factura *********************************************/
	case 719:
	$con_mod_fac_compra_719="UPDATE compras SET Tic_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Cop_Num=UPPER('$Par_Sql[3]'), Cop_Aut=UPPER('$Par_Sql[4]'), Cop_Fec='$Par_Sql[5]', Cop_Reg='$Par_Sql[6]', Cop_Des='$Par_Sql[7]', Cop_Obs=UPPER('$Par_Sql[8]'), Cop_Cad='$Par_Sql[9]', Cop_Imf='$Par_Sql[10]', Tri_Cod='$Par_Sql[11]' WHERE compras.Cop_Cod=$Par_Sql[12] ";
	//echo $con_mod_fac_compra_719;
	return $con_mod_fac_compra_719;
	break;

	/* Detalle de la factura de compra */ 
		case 720: 
		$inser_detafaccom_720 = "INSERT INTO 
		det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod, Ice_Int, Cop_Int, Pld_Cod) 
		VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'), $Par_Sql[4] , $Par_Sql[5], '$Par_Sql[6]', $Par_Sql[7], $Par_Sql[8], '$Par_Sql[9]', $Par_Sql[10])";		
		//echo $inser_detafaccom_720;
		return $inser_detafaccom_720;
		break;

/** Elimino de la base de datos los items del datalle que ya no vengan en la factura de compras **/
		case 721:
		$borr_item_fac_com_721="DELETE FROM det_compra WHERE det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'  ";	//Antes Cop_Int	
		//echo $borr_item_fac_com_721;
		return $borr_item_fac_com_721;
		break;
		
		/** Actualizacion del detalle de la factura ***********************************************/
		case 722:
		$actu_item_fac_com_722="UPDATE det_compra SET Cop_Can=$Par_Sql[1],Cop_Pro=UPPER('$Par_Sql[3]'), Cop_Pru=$Par_Sql[4], Cop_Imp=$Par_Sql[5], Cop_Dec=$Par_Sql[6], Adq_Cod=$Par_Sql[7] WHERE det_compra.Cop_Int=$Par_Sql[0] AND  det_compra.Cop_Cod=$Par_Sql[9]  ";
		//echo $actu_item_fac_com_722;
		return $actu_item_fac_com_722;
		break;
		
			
		/* Consulta el detalle de las compras */	
		case 723:
		$consultar_det_comprar_723= "SELECT iva.Iva_Cod, det_compra.Cop_Pro, iva.Iva_Por, det_compra.Cop_Int, det_compra.Cop_Can, det_compra.Cop_Imp,
							  det_compra.Cop_Pru, compras.Cop_Obs FROM det_compra INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
							  INNER JOIN compras ON (det_compra.Cop_Cod = compras.Cop_Cod) WHERE det_compra.Cop_Cod = $Par_Sql[0]";
		//echo $consultar_det_comprar_723;
		return $consultar_det_comprar_723;
		break;
		
		/**** Consultar el codigo mayor del detalle de compra ***********************************/
		case 724:
		$consultar_codigomayor_detcompra_724="SELECT MAX(det_compra.Cop_Int) AS Max_Dec FROM det_compra WHERE det_compra.Cop_Cod='$Par_Sql[0]' ";
		return $consultar_codigomayor_detcompra_724;
		break;
		
		/*** Consultar facturas que tengan un sustento tributario ***************/
		case 725:
		$consultar_fac_compra_sustribu_725="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
			compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut,
			compras.Tri_Cod, sustento.Tri_Des, det_compra.Cop_Imp
			FROM persona, proveedore,compras, det_compra, sustento, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod
			AND compras.Tri_Cod='$Par_Sql[4]'
			AND sustento.Tri_Cod = compras.Tri_Cod
			AND tipo_compr.Tic_Cod=compras.Tic_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Cop_Fec BETWEEN
			'$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
			AND $Par_Sql[2] 
			
			GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
        //echo $consultar_fac_compra_sustribu_725.'<br>';
		return $consultar_fac_compra_sustribu_725;
		break;

		//*consultar todas las facturas 
		case 726:
		$consulta_sustento_726 ="SELECT persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
			compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut, compras.Tri_Cod, sustento.Tri_Des
			FROM persona, proveedore,compras, det_compra, sustento WHERE persona.Prs_Cod=proveedore.Prs_Cod
			AND sustento.Tri_Cod = compras.Tri_Cod AND proveedore.Prv_Cod=compras.Prv_Cod 
			AND compras.Tic_Cod=$Par_Sql[2] AND compras.Cop_Fec BETWEEN
			'$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod 
			AND compras.Cop_Est='$Par_Sql[3]' GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC";
		return $consulta_sustento_726;
		break;
		
		case 727:
		$con_iva="SELECT det_compra.Iva_Cod, det_compra.Cop_Imp, iva.Iva_Por FROM det_compra,iva WHERE Cop_Cod='$Par_Sql[0]'
AND iva.Iva_Cod=det_compra.Iva_Cod ORDER BY iva.Iva_Por";
		return $con_iva;
		break;
		
		//Adquisiciones de cada factura		
		case 728:
		$ad_consulta="SELECT det_compra.Cop_Cod, det_compra.Adq_Cod, adquisicio.Adq_Des FROM det_compra, compras, adquisicio
					  WHERE det_compra.Cop_Cod = compras.Cop_Cod AND det_compra.Adq_Cod = adquisicio.Adq_Cod AND det_compra.Cop_Cod = $Par_Sql[0]";
		//echo $ad_consulta;
		return $ad_consulta;
		break;
		
		//tipo de comprobante
		case 729:
		$tipo_compr="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Des, tipo_compr.Tic_Est
					FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
		return $tipo_compr;
		break;
		
		//Consulta las ciudades
		case 730:
		$consulta_ciudad_730="SELECT Ciu_Cod, Ciu_Des FROM ciudad ORDER BY Ciu_Des";
		//echo $consulta_ciudad_730;
		return $consulta_ciudad_730;
		break;
		
               case 731:/*de las sql d epersona*/
		/* Consulta de los datos de la persona */
		$consul_persona_731 = "SELECT ciudad.Ciu_Des, persona.Prs_Cod, persona.Prs_Ced , persona.Prs_Nom,
identifica.Ide_Des, persona.Prs_Ape, IF(persona.Prs_Sex = 'M', 'Masculino', IF(persona.Prs_Sex = 'F', 'Femenino', '')) 
AS Prs_Sex, persona.Prs_San, persona.Prs_Fec, IF(persona.Prs_Esc = 'S', 'Soltero/a', IF(persona.Prs_Esc = 'C', 'Casado/a', 
IF(persona.Prs_Esc = 'V', 'Viudo/a', IF(persona.Prs_Esc = 'D', 'Divorciado/a', IF(persona.Prs_Esc = 'U', 'Unión Libre/a', ''))))) 
AS Prs_Esc, persona.Prs_Dir, persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Prs_Cor, persona.Ide_Cod
FROM identifica, ciudad, persona WHERE ciudad.Ciu_Cod = persona.Ciu_Cod 
AND identifica.Ide_Cod= persona.Ide_Cod 
AND Prs_Cod = $Par_Sql[0]";
		//echo $consul_persona_731;
		return $consul_persona_731;
		break;

		case 732:
		$consul_eta_per_car_cur ="SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2,  view_periodos_suc.Suc_Des, view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin, view_cursos_mal.Car_Int
FROM view_periodos_suc INNER JOIN view_cursos_mal ON (view_periodos_suc.Per_Int = view_cursos_mal.Per_Int)
WHERE view_cursos_mal.Sem_Cod = $Par_Sql[0] GROUP BY  view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2,
  view_periodos_suc.Suc_Des, view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin";
  		//echo $consul_eta_per_car_cur;
		return $consul_eta_per_car_cur;
		break;
		
		/*** Consulta la etapa/Modalidad/carrera  ****************/
		case 733:
		$consultar_eta_mod_car_733="SELECT sucursal.Suc_Des, modalidad.Mod_Des ,etapas.Eta_Cod, etapas.Eta_Des, carreras.Car_Nom, carreras.Car_Int,
		periodos.Per_Int, periodos.Per_Fea, periodos.Per_Fef 
		FROM etapas, carreras, periodos, modalidad, sucursal 
		WHERE etapas.Eta_Cod=periodos.Eta_Cod AND modalidad.Mod_Cod=periodos.Mod_Cod
 		AND etapas.Eta_Cod=periodos.Eta_Cod
		AND periodos.Mod_Cod='$Par_Sql[0]' AND carreras.Car_Int='$Par_Sql[1]' 
		AND  periodos.Per_Int='$Par_Sql[2]' AND sucursal.Suc_Cod=periodos.Suc_Cod AND periodos.Suc_Cod='$Par_Sql[3]'";
		//echo $consultar_eta_mod_car_733;
		return $consultar_eta_mod_car_733;
		break;
	/* Consulta de rubros */
		case 734:
	$consulta_rubros = "SELECT ventas_det.Pro_Cod, item.Ite_Lar, item.Ite_Cor FROM ventas_det INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
	  INNER JOIN producto ON (producto.Pro_Cod = ventas_det.Pro_Cod) INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod) INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod) INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod) INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
	  INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int) INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) WHERE
	  matriculas.Sem_Cod = '$Par_Sql[0]' AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' GROUP BY ventas_det.Pro_Cod ORDER BY item.Ite_Lar";
	return $consulta_rubros;
	break;
	
		/*consulta de rubros pagados */
	case 735:
	$consult_pagos ="SELECT SUM(ventas_det.Vet_Imp) AS Vet_Imp FROM ventas_det INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod)
  	INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod) INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod) INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod) INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
 	INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) WHERE matriculas.Sem_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod =$Par_Sql[1] AND 
  	ventas.Cli_Cod = $Par_Sql[2] AND ventas.Vet_Cod AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[3]' AND '$Par_Sql[4]'
 	AND ventas.Vet_Est = 'A'
	GROUP BY
 	ventas_det.Pro_Cod ";/*"SELECT SUM(ventas_det.Vet_Imp) AS Vet_Imp FROM
       notasgener INNER JOIN ventas_det ON (notasgener.Nge_Cod = ventas_det.Nge_Cod) INNER JOIN ventas ON (ventas_det.Vet_Cod = ventas.Vet_Cod) WHERE                          notasgener.Sem_Cod = $Par_Sql[0] AND  ventas_det.Pro_Cod = $Par_Sql[1] AND  ventas.Cli_Cod = $Par_Sql[2] AND ventas.Vet_Est='A'";*/
    //echo $consult_pagos;
    return $consult_pagos;
	break;
	
	case 736:
	$consulta_pag_deudas = "SELECT item.Ite_Cor, item.Ite_Lar FROM producto INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod) WHERE
  producto.Pro_Cod=$Par_Sql[0]";
  //	echo  $consulta_pag_deudas;
    return $consulta_pag_deudas;
	break;
	
	/*Consulta el rango de fechas del periodo */
	case 737:
	$consul_rang_periodos="SELECT modalidad.Mod_Des, periodos.Per_Int, periodos.Per_Fea, periodos.Per_Fec FROM
	                       modalidad INNER JOIN periodos ON (modalidad.Mod_Cod = periodos.Mod_Cod) 
						   WHERE modalidad.Mod_Cod = periodos.Mod_Cod AND periodos.Per_Int=$Par_Sql[0]";
	return $consul_rang_periodos;
	break;
	
	/*Consulta el rango de fechas del periodo de matricula y el periodo lectivo */
	case 738:
	$consul_fecha_periodo="SELECT perio_matr.Pem_Ini, periodos.Per_Fec FROM periodos, perio_matr
     WHERE periodos.Per_Int= perio_matr.Per_Int AND perio_matr.Tim_Cod= 1 AND periodos.Per_Int= $Par_Sql[0]";
	//echo $consul_fecha_periodo;
	return $consul_fecha_periodo;
	break;	
	
	
	/********** Consultar los periodos ************************************************/
	case 739:
	$consultar_periodo_estudiante_739="SELECT periodos.Per_Int,periodos.Per_Fea, periodos.Per_Fef FROM periodos, perio_matr, tipo_matr, matriculas, estudiante, persona WHERE persona.Prs_Cod=estudiante.Prs_Cod AND estudiante.Est_Int=matriculas.Est_Int
		AND periodos.Per_Int=perio_matr.Per_Int AND tipo_matr.Tim_Cod=perio_matr.Tim_Cod AND perio_matr.Pem_Cod=matriculas.Pem_Cod AND persona.Prs_Cod='$Par_Sql[0]' AND '$Par_Sql[1]'>=perio_matr.Pem_Ini AND '$Par_Sql[1]'<=periodos.Per_Fec";
    //echo $consultar_periodo_estudiante_739;
	return $consultar_periodo_estudiante_739;
	break;
		
		case 740:
		/* Consulta de estudiante por cédula, mostrando las carreras en las que esta matriculado */								
		$cargar_carreras_740="SELECT  promocione.Pro_Cod,niveles.Niv_Des ,carreras.Car_Int, Car_Nom, estudiante.Est_Int, persona.Prs_Ced, persona.Prs_Cod, persona.Prs_Nom, 
						persona.Prs_Ape, periodos.Per_Int, modalidad.Mod_Cod, modalidad.Mod_Des, etapas.Eta_Cod FROM estudiante, matriculas, carreras, promocione, semestres, persona,
			etapas, periodos,  modalidad, niveles
			WHERE    	estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod AND 
						semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND 
						persona.Prs_Ced = '$Par_Sql[0]' AND persona.Prs_Cod = estudiante.Prs_Cod AND estudiante.Est_Est= 'A'
						AND etapas.Eta_Cod=carreras.Eta_Cod AND etapas.Eta_Cod=periodos.Eta_Cod AND modalidad.Mod_Cod=periodos.Mod_Cod
						AND niveles.Niv_Cod=semestres.Niv_Cod AND promocione.Pro_Cod=semestres.Pro_Cod
						AND niveles.Niv_Cod='$Par_Sql[1]' AND periodos.Per_Int=semestres.Per_Int
			GROUP BY Car_Int, Car_Nom, Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape";						
		//echo $cargar_carreras.'<br>';
		return $cargar_carreras_740;
		break;
		
		//***selecciona los niveles que el estudiante ha cursado************************************************************/	
		case 741:
		$selec_carrera_741="SELECT Car_Nom FROM carreras WHERE Car_Int='$Par_Sql[0]'";
		return $selec_carrera_741;
		break;
		
		//***selecciona los niveles que el estudiante ha cursado************************************************************/	
		case 742:
		$selec_carrera_742="SELECT Car_Nom FROM carreras WHERE Car_Int='$Par_Sql[0]'";
		return $selec_carrera_742;
		break;

		//***selecciona los niveles que el estudiante ha cursado************************************************************/	
		case 743:
 		$selec_beca_743="SELECT becas.Bec_Pot, tipo_beca.Tib_Des FROM matriculas, becas, tipo_beca
                              WHERE matriculas.Mat_Int = becas.Mat_Int AND becas.Tib_Cod = tipo_beca.Tib_Cod AND becas.Mat_Int=$Par_Sql[0]";
		//echo $selec_beca_743;
		return $selec_beca_743;
		break;
		
		/*Consulta de la descripcion de la carreras */
		case 745:
			$consulta_carreras_745="SELECT carreras.Car_Nom, carreras.Car_Int FROM carreras WHERE carreras.Car_Int=$Par_Sql[0] ORDER BY Car_Nom";
			//echo $consulta_carreras_745;
		return $consulta_carreras_745;
		break;

		/*Sentencia Miriam caja chica */
		case 746:
		$cons_repo="SELECT reposicion.Cja_Mon, reposicion.Cja_Cod, reposicion.Cja_Fec, reposicion.Cja_Hor, reposicion.Cja_Sal, reposicion.Cja_Tra, reposicion.Cja_Obs,
  reposicion.Cja_Pun, reposicion.Cja_Est FROM reposicion WHERE reposicion.Cja_Est = 'A'";
		return $cons_repo;
		break;
		
		/*Busca el saldo gastado */
		case 747:
		$cons_importe="SELECT SUM(det_recibo.Rcb_Imp) AS Rcb_Imp FROM recibo, liquidacio, det_recibo WHERE recibo.Rcb_Cod = det_recibo.Rcb_Cod
AND recibo.Rcb_Cod = liquidacio.Rcb_Cod AND liquidacio.Cja_Cod='NULL'";
		//echo $cons_importe;
		return $cons_importe;
		break;
		
		/*consulta los recibos creados */
		case 748:
		$cons_recibo="SELECT recibo.Rcb_Cod, det_recibo.Rcb_Can, Rcb_Con, SUM(det_recibo.Rcb_Imp) as Rcb_Imp FROM recibo, liquidacio, det_recibo WHERE recibo.Rcb_Cod = det_recibo.Rcb_Cod
AND recibo.Rcb_Cod = liquidacio.Rcb_Cod AND liquidacio.Cja_Cod='NULL' GROUP BY recibo.Rcb_Cod";
        //echo $cons_recibo;
        return $cons_recibo;
		break;
		
		/* Insertar el valor de reposicion de caja chica */
		case 749:
		$reg_reposici="INSERT INTO reposicion(Emp_Cod, Cja_Fec, Cja_Mon, Cja_Sal, Cja_Tra, Cja_Obs) VALUES('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]')";
		//echo $reg_reposici;
		return $reg_reposici;
		break;
		
		
		
		/* Consulta el codigo de liquidaciones */
		case 750:		
		$codigo_caja="UPDATE liquidacio SET Cja_Cod ='$Par_Sql[0]' WHERE Rcb_Cod = $Par_Sql[1]";
		//echo $codigo_caja;
		return $codigo_caja;
		break;

		/*actualiza el estado de caja */
		case 751:		
		$estad_reposi="UPDATE reposicion SET Cja_Est ='I' WHERE Cja_Cod = $Par_Sql[0]";
		//echo $estad_reposi;
		return $estad_reposi;
		break;
		
		//consulta el codigo de la tabla liquidaciones
		case 752:
		$codigo_liquidacio="select Cja_Cod FROM liquidacio";
		return $codigo_liquidacio;
		break;



	/* CUENTAS POR PAGAR */
		case 801:/*consulta de provedores que tiene pagos pendientes por apellido*/
		$consul_prove_ape= " SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape,  persona.Ide_Cod,
		proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod FROM  persona INNER JOIN proveedore ON (persona.Prs_Cod = 	
		proveedore.Prs_Cod)  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON 	
		(compras.Cop_Cod = ccpp_pagar.Cop_Cod) WHERE  proveedore.Prs_Cod = persona.Prs_Cod AND  Prs_Ape LIKE '%$Par_Sql[0]%'  
		GROUP BY proveedore.Prv_Cod";
		//echo $consul_prove_ape;
		return $consul_prove_ape;
	
		break;
		
		case 802:/*consulta de provedores que tiene pagos pendientes por cédula*/
		$consul_prove_ced= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Ide_Cod, 
		proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod FROM  persona INNER JOIN proveedore ON (persona.Prs_Cod = 
		proveedore.Prs_Cod)	INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod
		= ccpp_pagar.Cop_Cod) WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ced= '$Par_Sql[0]'  GROUP BY proveedore.Prv_Cod";
		//echo $consul_prove_ced;
		return $consul_prove_ced;
		
		break;
		
		case 803:/*consulta de facturas pendientes segun el proveedor*/
		$consul_prove_fac= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
		compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val 
		FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
		AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND 
		comprobantes.Com_Est='A' AND proveedore.Prv_Cod = $Par_Sql[0] ORDER BY 
		ccpp_pagar.Cpp_Ven  $Par_Sql[1]"; //AND perio_cont.Pec_Cod= $Par_Sql[2]
		//echo $consul_prove_fac;
		return $consul_prove_fac;
		break;
		
		case 804:/*consulta de pagos echo a las factura de compras a credito*/
		$consul_pago_fac= "SELECT sum(det_ccpp_p.Pag_Val)  as total FROM comprobantes, asientos, det_ccpp_p, ccpp_pagar, compras 
		WHERE compras.Cop_Cod= ccpp_pagar.Cop_Cod  AND ccpp_pagar.Cpp_Cod= det_ccpp_p.Cpp_Cod AND comprobantes.Com_Cod= 
		asientos.Com_Cod AND comprobantes.Com_Cod= det_ccpp_p.Com_Cod AND compras.Cop_Cod= $Par_Sql[0] AND asientos.Asi_Deh='D' AND det_ccpp_p.Pag_Est = 'A'";
		//echo $consul_pago_fac;
		return $consul_pago_fac;
		break;
		
		/* Insercion de un comprobante de Ingreso/Egreso (Cliente/Proveedor) */
		case 805: 
		$ins_comp_egreso="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Com_Num='$Par_Sql[2]', 
			Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6]";//Antes Com_Tip
		//echo $ins_comp_egreso."<br>";
		return $ins_comp_egreso;
		break;

		/* Inserción de cada asiento del comprobante */
		case 806:
		$ins_asiento="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], 
		Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
		//echo $ins_asiento."<br>";
		return $ins_asiento;
		break;
		
		/* Inserción de detalle del pago de la factura de credito en la tabla det_ccpp_p */
		case 807:
		$ins_det_ccpp="INSERT INTO det_ccpp_p SET Cpp_Cod=$Par_Sql[0], Pag_Cod=$Par_Sql[1], Com_Cod=$Par_Sql[2], 
		Pag_Fec='$Par_Sql[3]', Pag_Val= $Par_Sql[4], Pag_Obs= '$Par_Sql[5]'";
		//echo $ins_det_ccpp;
		return $ins_det_ccpp;
		break;
		
		case 808:/*consulta para saber los dias que faltan para pagaro*/
		$consul_dias_venc= "SELECT DATEDIFF('$Par_Sql[0]', '$Par_Sql[1]' ) AS dias";
		//echo $consul_dias_venc;
		return $consul_dias_venc;
		break;
		
		case 809:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
		$consul_fact_todos= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, 
		compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod,  
		asientos.Asi_Cod, asientos.Asi_Val, comprobantes.Com_Cod FROM  proveedore   INNER JOIN compras 
		ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
	    INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
        INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
        INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
        INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona WHERE 
		proveedore.Prs_Cod= persona.Prs_Cod  and comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND 
		asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND perio_cont.Pec_Cod= 
		$Par_Sql[1] AND comprobantes.Com_Est='A' AND compras.Cop_Cod= $Par_Sql[2] ORDER BY ccpp_pagar.Cpp_Ven  $Par_Sql[0]";
		//echo $consul_fact_todos;
		return $consul_fact_todos;
		break;

		/***************************************************************************************************/
		case 810:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
		$consul_fac_pagos= "SELECT proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_compra.Cop_Int,det_compra.Cop_Imp,
		compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
		comprobantes.Com_Cod, compras.Pec_Cod, det_ccpp_p.Pag_Est FROM proveedore 
		INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN det_compra ON (compras.Cop_Cod = 
		det_compra.Cop_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
		INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
		INNER JOIN det_ccpp_p ON( det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod )
		WHERE asientos.Asi_Deh = 'H' AND compras.Cop_Est = 'A' AND comprobantes.Com_Est = 'A' AND perio_cont.Pec_Cod=$Par_Sql[0] 
		AND  proveedore.Prv_Cod =$Par_Sql[0] AND det_ccpp_p.Pag_Est='A'";
		//echo $consul_fac_pagos;
		return $consul_fac_pagos;
		break;
		/***************************************************************************************************/
		case 811:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
		$consul_fac_pago= "SELECT proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_compra.Cop_Int,det_compra.Cop_Imp,
  		compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod,  asientos.Asi_Cod, asientos.Asi_Val, 
		comprobantes.Com_Cod FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN det_compra ON
		(compras.Cop_Cod = det_compra.Cop_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) INNER JOIN 
		comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) INNER JOIN asientos ON (comprobantes.Com_Cod = 
		asientos.Com_Cod)  INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) WHERE comprobantes.Com_Cod = 
		ccpp_pagar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND 
		comprobantes.Com_Est='A' AND perio_cont.Pec_Cod= $Par_Sql[1] AND proveedore.Prv_Cod = $Par_Sql[0] $Par_Sql[2]";
		//echo $consul_fac_pago;
		return $consul_fac_pago;
		break;	

		/* Consulta los pagos realizados a los proveedores  */
		case 812:
		$consult_pagos_provedor="SELECT compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_ccpp_p.Pag_Val, det_ccpp_p.Pag_Est, 	
		det_ccpp_p.Pag_Fec, det_ccpp_p.Cpp_Cod, det_ccpp_p.Com_Cod, comprobantes.Com_Num FROM  compras
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
		INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
		INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) WHERE  compras.Cop_Cod = $Par_Sql[0]";
		//echo $consult_pagos_provedor;
		return $consult_pagos_provedor;
		break;
		
		/* Consulta el detalle de los pagos de cada proveedor */
		case 813:
		$consult_detalle_pagos="SELECT  proveedore.Prv_Cod, ccpp_pagar.Cpp_Cod,  ccpp_pagar.Cpp_Ven, det_ccpp_p.Pag_Est,
  		det_ccpp_p.Pag_Fec, asientos.Asi_Cod, det_ccpp_p.Com_Cod, comprobantes.Pec_Cod, asientos.Asi_Val
		FROM ccpp_pagar INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) INNER JOIN det_ccpp_p ON 
		(det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod),
        asientos, proveedore WHERE  asientos.Asi_Deh = 'H' AND comprobantes.Com_Est = 'A' AND perio_cont.Pec_Cod = $Par_Sql[0] 
		AND  proveedore.Prv_Cod = $Par_Sql[1]  AND det_ccpp_p.Pag_Est = 'A' AND ccpp_pagar.Cpp_Cod = $Par_Sql[2] 
		AND ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod  AND  det_ccpp_p.Com_Cod = asientos.Com_Cod";
		//echo $consult_detalle_pagos;
		return $consult_detalle_pagos;
		break;

		case 814:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
		$consul_fact_pagos= "SELECT  proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod,
  		compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
  		comprobantes.Com_Cod FROM   proveedore  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
		INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
	    INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
  		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
  		INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
  		INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona
		WHERE proveedore.Prs_Cod = persona.Prs_Cod AND  comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND  asientos.Com_Cod = 
		comprobantes.Com_Cod AND asientos.Asi_Deh = 'H' AND compras.Cop_Est = 'A' AND  
		comprobantes.Com_Est = 'A' ORDER BY ccpp_pagar.Cpp_Ven, persona.Prs_Ape $Par_Sql[0]"; //AND  perio_cont.Pec_Cod = 3
		//echo $consul_fact_pagos;
		return $consul_fact_pagos;
		break;						

		/****Consulta factura pagadas a proveedores segun elapellido*/
		case 815:
		$consult_pago_provee="SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, comprobantes.Com_Cod, 	
		comprobantes.Com_Obs, comprobantes.Com_Num, comprobantes.Com_Fec, comprobantes.Com_Val FROM comprobantes 
        INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
        INNER JOIN persona ON ( persona.Prs_Cod = proveedore.Prs_Cod)
        INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
		INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)
		WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  comprobantes.Tia_Cod = 2 AND comprobantes.Com_Est='A' $Par_Sql[1]  GROUP BY Com_Cod";
		//echo $consult_pago_provee; 
		return $consult_pago_provee;
		break;
		/*******************/
		/*CONSULTA LOS PAGOS REALIZADAOS EN det_cccpp_p	 BASANDOSE ENEL CODIGO DEL COMPROBANTE D EEGREO*/
		case 816:
		$consult_pago_compr="SELECT  det_ccpp_p.Cpp_Cod, det_ccpp_p.Com_Cod as Com_Pag,det_ccpp_p.Pag_Fec,det_ccpp_p.Pag_Val,
  		det_ccpp_p.Pag_Est, ccpp_pagar.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, ccpp_pagar.Com_Cod FROM  ccpp_pagar
	  	INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
		INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) INNER JOIN comprobantes 
		ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) WHERE det_ccpp_p.Com_Cod = $Par_Sql[0]";
		//echo $consult_pago_compr; 
		return $consult_pago_compr;
		break;
		/*CONSULTA EL VALOR DE LA FACtURA EN BASE AL CODIGO DEL COMPROBANTE*/
		case 817:
		$consult_valor_fact="SELECT comprobantes.Com_Val FROM  ccpp_pagar 
		INNER JOIN det_compra ON (ccpp_pagar.Cop_Cod = det_compra.Cop_Cod) 
		INNER JOIN compr_auto ON (det_compra.Cop_Cod = compr_auto.Cop_Cod)
  		INNER JOIN comprobantes ON (compr_auto.Com_Cod = comprobantes.Com_Cod)
		WHERE ccpp_pagar.Cop_Cod = $Par_Sql[0]"; 
		//echo $consult_valor_fact; 
		return $consult_valor_fact;
		break;
		/*ACTUALIZA EL CONCEPTO Y LA OBSERVACION DEL COMPROBANTE*/
		case 818:
		$consult_act_compr="UPDATE comprobantes SET Com_Con ='$Par_Sql[0]', Com_Obs= '$Par_Sql[1]' WHERE Com_Cod = $Par_Sql[2]";
		//echo $consult_act_compr; 
		return $consult_act_compr;
		break;
		/****Consulta factura pagadas a proveedores segun la cedula*/
		case 819:
		$consult_pago_provced="SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, comprobantes.Com_Cod, 	
		comprobantes.Com_Obs, comprobantes.Com_Num, comprobantes.Com_Fec, comprobantes.Com_Val FROM comprobantes 
        INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
        INNER JOIN persona ON ( persona.Prs_Cod = proveedore.Prs_Cod)
        INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
		INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)
		WHERE persona.Prs_Ced= $Par_Sql[0] AND  comprobantes.Tia_Cod = 2 AND comprobantes.Com_Est='A' $Par_Sql[1]  GROUP BY Com_Cod";
		//echo $consult_pago_provced; 
		return $consult_pago_provced;
		break;
		/*ACTUALIZA EL ESTADO DEL COMPROBANTE DE ACTIVO A INACTIVO*/
		case 820:
		$actual_est_compr="UPDATE comprobantes SET Com_Est ='I' WHERE Com_Cod =$Par_Sql[0]";
		//echo $actual_est_compr; 
		return $actual_est_compr;
		break;
		/*ACTUALIZA EL ESTADO DEL PAGO DE ACTIVOA A INACTIVO*/
		case 821:
		$actual_est_pago="UPDATE det_ccpp_p SET Pag_Est ='I' WHERE Com_Cod = $Par_Sql[0]";
		//echo $actual_est_pago; 
		return $actual_est_pago;
		break;
		/****Consulta factura pagadas a proveedores segun elapellido*/
		case 822:
		$consult_pago_anula="SELECT proveedore.Prv_Cod,persona.Prs_Nom,persona.Prs_Ced, persona.Prs_Ape, comprobantes.Com_Val, 
		comprobantes.Com_Cod,det_ccpp_p.Cpp_Cod ,
  		comprobantes.Com_Num, comprobantes.Com_Est, asientos.Asi_Deh, det_ccpp_p.Pag_Cod, det_ccpp_p.Pag_Val, comprobantes.Com_Fec
		FROM comprobantes INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) INNER JOIN det_ccpp_p 
		ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod) INNER JOIN asientos ON (det_ccpp_p.Com_Cod = asientos.Com_Cod)
  		AND (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
		WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  asientos.Asi_Deh = 'H' AND  comprobantes.Tia_Cod =2 
		AND perio_cont.Pec_Cod = $Par_Sql[2]  $Par_Sql[1]  GROUP by comprobantes.Com_Cod";
		//echo $consult_pago_anula; 
		return $consult_pago_anula;
		break;
		/****Consulta factura pagadas a proveedores segun la cedula*/
		case 823:
		$consult_pago_provced="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
		comprobantes.Com_Val,comprobantes.Com_Cod, det_ccpp_p.Cpp_Cod ,
  		comprobantes.Com_Num, comprobantes.Com_Est, asientos.Asi_Deh, det_ccpp_p.Pag_Cod, det_ccpp_p.Pag_Val, comprobantes.Com_Fec
		FROM comprobantes INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) INNER JOIN det_ccpp_p 
		ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod) INNER JOIN asientos ON (det_ccpp_p.Com_Cod = asientos.Com_Cod)
  		AND (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
		WHERE comprobantes.Com_Num= $Par_Sql[0] AND comprobantes.Tia_Cod=2 and asientos.Asi_Deh = 'H' 
		AND perio_cont.Pec_Cod = $Par_Sql[1]  $Par_Sql[2]";
		//echo $consult_pago_provced; 
		return $consult_pago_provced;
		break;
		/****Consulta factura pagadas a proveedores segun elapellido*/
		case 824:
		$consult_pago_anu="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape,  persona.Prs_Ced, comprobantes.Com_Val,
  		comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, comprobantes.Com_Fec FROM comprobantes
  		INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod)
		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
		INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
		 INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
		WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  comprobantes.Tia_Cod =2 AND 
		perio_cont.Pec_Cod = $Par_Sql[2]  $Par_Sql[1] GROUP BY comprobantes.Com_Cod";
		//echo $consult_pago_anu; 
		return $consult_pago_anu;
		break;
		/****Consulta factura pagadas a proveedores segun el nnumero de comprobantes*/
		case 825:
		$consult_pago_provcd="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape,  persona.Prs_Ced, comprobantes.Com_Val,
  		comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, comprobantes.Com_Fec FROM comprobantes
  		INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod)
		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
		INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
		 INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
		WHERE comprobantes.Com_Num= $Par_Sql[0] AND comprobantes.Tia_Cod=2 
		AND perio_cont.Pec_Cod = $Par_Sql[1]  $Par_Sql[2] GROUP BY comprobantes.Com_Cod" ; 
		//echo $consult_pago_provcd; 
		return $consult_pago_provcd;
		break;

		/*ANTICIPOS A EMPLEADOS*/
		/****Consulta de proveedores por apellido para anticipo a proveedores*/
		case 826:
		$consult_ape_prove="SELECT persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Cod, proveedore.Prv_Cod,  persona.Prs_Ape
		FROM persona  INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod) 
		WHERE proveedore.Prs_Cod = persona.Prs_Cod  AND Prs_Ape LIKE '%$Par_Sql[0]%' ";
		//echo $consult_ape_prove; 
		return $consult_ape_prove;
		break;
		/****Consulta de proveedores por cedula para anticipo a proveedores*/
		case 827:
		$consult_ced_prove="SELECT persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Cod, proveedore.Prv_Cod,  persona.Prs_Ape
		FROM persona  INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod) 
		WHERE proveedore.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced =$Par_Sql[0]";
		//echo $consult_ced_prove; 
		return $consult_ced_prove;
		break;
		/****Consulta de proveedores por cedula para anticipo a proveedores*/
		case 828:
		$consult_det_plan="SELECT anti_prove.Pld_Cod, det_plan.Pld_Des FROM det_plan 
		INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod)";
		//echo $consult_det_plan; 
		return $consult_det_plan;
		break;
		 /* Inserción del anticipo en la tabla anticipos */
		case 829:
		$ins_anticipo="INSERT INTO anticipos SET Prv_Cod=$Par_Sql[0], Ant_Fec='$Par_Sql[1]', Ant_Obs='$Par_Sql[2]', 
		Ant_Est='A'";
		//echo $ins_anticipo;
		return $ins_anticipo;
		break;
		 /* Inserción de Com_Cod y Ant_Cod en l atabla compr_anti */
		case 830:
		$ins_compr_anti="INSERT INTO compr_anti SET Com_Cod=$Par_Sql[0], Ant_Cod=$Par_Sql[1]";
		//echo $ins_compr_anti;
		return $ins_compr_anti;
		break;
		/* Consulta de los anticipos en base a mes de inicio y me s de fin */
	   case 831:
	   $cons_compr_anti="SELECT anticipos.Ant_Cod, anticipos.Ant_Fec, anticipos.Ant_Est, comprobantes.Com_Cod, anticipos.Prv_Cod,
	   comprobantes.Com_Num, asientos.Asi_Val,  persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape FROM  anticipos
	   INNER JOIN compr_anti ON (compr_anti.Ant_Cod = anticipos.Ant_Cod)
	   INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
	   INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
	   INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
	   INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod)
	   INNER JOIN proveedore ON (anticipos.Prv_Cod = proveedore.Prv_Cod)
	   INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
	   WHERE asientos.Asi_Deh = 'D' AND anticipos.Ant_Est= 'A' AND anticipos.Ant_Fec <= '$Par_Sql[0]'";
	   //echo $cons_compr_anti;
	   return $cons_compr_anti;
	   break;
	
		/* Consulta de los anticiposa proveedores en base a fecha de inicio y fecha de fin */
	   case 832:
	   $cons_compr_antifec="
	   SELECT anticipos.Ant_Cod, anticipos.Ant_Fec, anticipos.Ant_Est, comprobantes.Com_Cod,  
	   anticipos.Prv_Cod, comprobantes.Com_Num, asientos.Asi_Val FROM anticipos  
	   INNER JOIN compr_anti ON (compr_anti.Ant_Cod = anticipos.Ant_Cod)
	   INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
	   INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
	   INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
	   INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod)
	   WHERE  anticipos.Prv_Cod = $Par_Sql[1] AND  asientos.Asi_Deh = 'D' 
	   AND anticipos.Ant_Est= 'A' AND anticipos.Ant_Fec <= '$Par_Sql[0]' "; //AND  comprobantes.Pec_Cod = $Par_Sql[3]
	   //echo $cons_compr_antifec;
	   return $cons_compr_antifec;
	   break;

//	  AND  MONTH(anticipos.Ant_Fec) >= 1 AND   MONTH(anticipos.Ant_Fec) <= 11

		/****Consulta factura pagadas a proveedores segun elapellido*/
		case 833:
		$consult_anti_anu="SELECT persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, comprobantes.Com_Cod, anticipos.Ant_Cod,
  		asientos.Asi_Val, anticipos.Ant_Fec, comprobantes.Com_Num, anticipos.Ant_Est 
		FROM  anticipos INNER JOIN proveedore ON (anticipos.Prv_Cod = proveedore.Prv_Cod) 
  		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
		INNER JOIN compr_anti ON (anticipos.Ant_Cod = compr_anti.Ant_Cod)
	    INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
		WHERE  comprobantes.Tia_Cod = 2  AND asientos.Asi_Deh = 'H'  AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' 
  		$Par_Sql[1]";
		//echo $consult_anti_anu; 
		return $consult_anti_anu;
		break;
		/****Consulta factura pagadas a proveedores segun el nnumero de comprobantes*/
		case 834:
		$consult_anti_provcd="SELECT persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, comprobantes.Com_Cod, 
		anticipos.Ant_Cod, asientos.Asi_Val, anticipos.Ant_Fec, comprobantes.Com_Num, anticipos.Ant_Est 
		FROM anticipos INNER JOIN proveedore ON (anticipos.Prv_Cod = proveedore.Prv_Cod) 
		INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
		INNER JOIN compr_anti ON (anticipos.Ant_Cod = compr_anti.Ant_Cod) 
		INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod) 
		INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
		WHERE comprobantes.Tia_Cod = 2 AND asientos.Asi_Deh = 'H' AND comprobantes.Com_Num=$Par_Sql[0] " ; 
		//echo $consult_anti_provcd; 
		return $consult_anti_provcd;
		break;
		/****Consulta si el anticipo esta cancelado*/
		case 835:
		$consult_anti_cancel="SELECT Ant_Cod, Ant_Val, Cop_Cod from det_antici where Ant_cod=$Par_Sql[0]" ; 
		//echo $consult_anti_cancel; 
		return $consult_anti_cancel;
		break;
		/****Consulta PARA ANULAR EL ANTICIPO*/
		case 836:
		$elim_antipos="UPDATE anticipos SET Ant_Est ='I' WHERE Ant_Cod = $Par_Sql[0]" ; 
		//echo $elim_antipos; 
		return $elim_antipos;
		break;
		/****CONSULRTA PARA ANULAR ELCOMPROBANTE*/
		case 837:
		$elim_compr="UPDATE comprobantes SET Com_Est ='I' WHERE Com_Cod = $Par_Sql[0]" ; 
		//echo $elim_compr; 
		return $elim_compr;
		break;
		/***CONSULTA DE ANTIICPOS SEGUN PROVEEDOR****/
  case 838:
  $cons_anticipos="SELECT comprobantes.Pec_Cod, comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Con,  
  comprobantes.Com_Num, comprobantes.Prv_Cod, asientos.Asi_Cod, asientos.Asi_Val, asientos.Asi_Deh,  det_plan.Pla_Cod,
    det_plan.Pld_Cdc FROM comprobantes INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
    INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
  WHERE  comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
  comprobantes.Prv_Cod = $Par_Sql[2] AND  asientos.Pld_Cod = $Par_Sql[3] AND comprobantes.Com_Est = 'A' 
  AND comprobantes.Pec_Cod =  $Par_Sql[4]  ORDER BY Com_Fec ASC";
 // echo $cons_anticipos;
  return $cons_anticipos;
  break;
  /****CONSULTA DE CANCELACION DE ANTICIPOS****/
  case 839:
  $cons_saldo="SELECT asientos.Asi_Deh, sum(asientos.Asi_Val) as Asi_Val, det_plan.Pla_Cod, det_plan.Pld_Cdc 
  FROM asientos, comprobantes, det_plan 
  WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod 
  AND comprobantes.Com_Fec <= '$Par_Sql[0]' 
  AND comprobantes.Com_Est = 'A' AND det_plan.Pld_Cod = $Par_Sql[1] AND comprobantes.Pec_Cod = $Par_Sql[2]
  AND comprobantes.Prv_Cod= $Par_Sql[3] 
  GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";
  //echo $cons_saldo;
  return $cons_saldo;
  break;

case 840:/*consulta de facturas pendientes segun el proveedor*/
   $consul_fac_venc= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, 
   compras.Cop_Fec,  compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val 
   FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
   INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
   INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
   INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
   INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
   INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND 
   comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' 
   AND compras.Cop_Est='A' AND comprobantes.Com_Est='A' AND proveedore.Prv_Cod = $Par_Sql[0]  AND comprobantes.Com_Fec <= '$Par_Sql[1]'
   ORDER BY ccpp_pagar.Cpp_Ven "; 
   //echo $consul_fac_venc;
   return $consul_fac_venc;
   break;
 /********/
 case 841:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
 $consul_fact_pago= "SELECT  proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod,
 compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
 comprobantes.Com_Cod FROM   proveedore  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
 INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
 INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
 INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
 INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
 INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona
 WHERE proveedore.Prs_Cod = persona.Prs_Cod AND  comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND  asientos.Com_Cod = 
 comprobantes.Com_Cod AND asientos.Asi_Deh = 'H' AND compras.Cop_Est = 'A' AND comprobantes.Com_Fec <= '$Par_Sql[0]' AND
 comprobantes.Com_Est = 'A' ORDER BY ccpp_pagar.Cpp_Ven, persona.Prs_Ape";
 //echo $consul_fact_pago;
 return $consul_fact_pago;
 break;
/****CONSULTA PARA CONOCER EL NUMERO Y MES DEL COMPROBANTE EN BASE AL CODIGO DEL COMPROBANTE*/
	 case 842:
	 $cons_compr="SELECT  Com_Num, Com_Con, Com_Obs, month(Com_Fec) as mes, Com_Est, comprobantes.Tia_Cod, tipo_asien.Tia_Ini
	 FROM comprobantes, tipo_asien  WHERE Com_Cod=$Par_Sql[0] AND comprobantes.Tia_Cod= tipo_asien.Tia_Cod" ; 
	 //echo $cons_compr; 
	 return $cons_compr;
	 break;
      /*consulta de pagos echo a las factura de compras a credito y segun fecha de corte*/
	  case 843:
	  $consul_fec_pago= "SELECT sum(det_ccpp_p.Pag_Val)  as total FROM comprobantes, asientos, det_ccpp_p, ccpp_pagar, compras 
	  WHERE compras.Cop_Cod= ccpp_pagar.Cop_Cod  AND ccpp_pagar.Cpp_Cod= det_ccpp_p.Cpp_Cod AND comprobantes.Com_Cod= 
	  asientos.Com_Cod AND comprobantes.Com_Cod= det_ccpp_p.Com_Cod AND compras.Cop_Cod= $Par_Sql[0] AND comprobantes.Com_Fec<= 
	  '$Par_Sql[1]' AND asientos.Asi_Deh='D' AND det_ccpp_p.Pag_Est = 'A'";
	 // echo $consul_fec_pago;
	  return $consul_fec_pago;
	  break;	

	    /**Consulta de Roles de pago**/
	 /*consulta de los campos de roles de pago basandose en ingreso o egreso*/
	 case 844:
	 $consul_campos= "SELECT Cam_Cod, Cam_Des, Cam_Obs, Cam_Vis, Cam_Req, Cam_Cal FROM campo_rol WHERE Cam_Est= 'A'
	 AND Cam_Tip='$Par_Sql[0]' AND Cam_Cal='S'";
	 //echo $consul_campos;
	 return $consul_campos;
	 break;
	  /*consulta de campos rol */
	 case 845:
	 $consul_camposrol="SELECT Cam_Cod, Cam_Des, Cam_Obs, Cam_Vis, Cam_Req, Cam_Cal FROM campo_rol WHERE Cam_Est= 'A'
	 AND Cam_Cal='N'";
	 //echo $consul_camposrol;
	 return $consul_camposrol;
	 break;
	 /*consulta de operadores*/
	 case 846:
	 $consul_operador= "SELECT Ope_Cod, Ope_Ope FROM operadores WHERE Ope_Est= 'A'";
	 //echo $consul_operador;
	 return $consul_operador;
	 break;
	/*consulta de grupos*/
	 case 847:
	 $consul_grupo= "SELECT Grp_Cod, Grp_Des FROM grupos";
	 //echo $consul_grupo;
	 return $consul_grupo;
	 break;	
 /*inserción de la formula*/
	 case 848:
	 $insert_formul="INSERT INTO formulas SET Cam_Cod=$Par_Sql[0], Ope_Cod=$Par_Sql[1], Grp_Cod=$Par_Sql[2], Cam_Rec=$Par_Sql[3]";
	// echo $insert_formul;
	 return $insert_formul;
	 break;	
 	
 case 849:
	 $consul_form= "SELECT formulas.Grp_Cod, grupos.Grp_Des, grupos.Ope_Cod, operadores.Ope_Ope, formulas.Cam_Cod FROM  grupos
     INNER JOIN formulas ON (grupos.Grp_Cod = formulas.Grp_Cod) INNER JOIN operadores ON (grupos.Ope_Cod = operadores.Ope_Cod)
	 WHERE  formulas.Cam_Cod = $Par_Sql[0] GROUP BY formulas.Grp_Cod, grupos.Grp_Des, grupos.Ope_Cod, operadores.Ope_Ope";
	//echo $consul_form;
	 return $consul_form;
	 break;
	 
	 /*Consulta de grupos de  formula*/
	 case 850:
	 $consul_formula= "SELECT formulas.Grp_Cod, operadores.Ope_Ope, formulas.Cam_Rec, campo_rol.Cam_Des, formulas.Cam_Cod, campo_rol.Cam_Por,
	 operadores.Ope_Cod FROM operadores INNER JOIN formulas ON (operadores.Ope_Cod = formulas.Ope_Cod) 
	 INNER JOIN campo_rol ON (formulas.Cam_Rec = campo_rol.Cam_Cod)  WHERE formulas.Cam_Cod = $Par_Sql[0] AND formulas.Grp_Cod=$Par_Sql[1] ORDER BY formulas.Ope_Cod";
	//echo $consul_formula;
	 return $consul_formula;
	 break;	
	 
	 /*inserción de la formula*/
	 case 851:
	 $insert_grupo="INSERT INTO grupos SET Ope_Cod=$Par_Sql[0], Grp_Des='$Par_Sql[1]'";
	echo $insert_grupo;
	 return $insert_grupo;
	 break;
	 /******************/
	  /*modifificacion de campos de la formula*/
	 case 852:
	 $actual_campo="UPDATE formulas SET Ope_Cod= $Par_Sql[0] WHERE Cam_Cod= $Par_Sql[1] AND Ope_Cod=$Par_Sql[2] AND Grp_Cod=$Par_Sql[3]";
	 //echo $actual_campo;
	 return $actual_campo;
	 break;
	
  /*modifificacion de campos de la formula*/
  case 853:
  $formula_campo="SELECT Cam_Cod FROM formulas WHERE Cam_Rec=$Par_Sql[0] AND formulas.For_Est = 'A' $Par_Sql[1]";
//echo $formula_campo;
  return $formula_campo;
  break;  


	/*anexo trnsaccional 2010 - retencion del iva 100%*/     
	 case 854:
     $ret_iva_total="SELECT  SUM(det_retenc.Ret_Bas) AS Ret_Bas,  det_retenc.Adq_Cod, renta_iva.Ren_Por FROM  retencion
     INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
     INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
     WHERE  renta_iva.Ren_Por = 100 AND retencion.Cop_Cod = $Par_Sql[0] AND  det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A'
     GROUP BY  det_retenc.Adq_Cod";
     //echo $ret_iva_total;
     return $ret_iva_total;
     break;	
     
     /*consulta para obtener valor de codigo d eretencion 332// facturas que no tienen retencion*/
	  case 855:
  $codigo_retenido="SELECT  SUM(det_compra.Cop_Imp) as Cop_Imp, det_compra.Cop_Cod, det_compra.Cop_Int
     FROM det_compra WHERE  det_compra.Cop_Int NOT IN 
  (SELECT det_retenc.Ret_Int FROM retencion INNER JOIN det_retenc ON
   (retencion.Ret_Cod = det_retenc.Ret_Cod) WHERE retencion.Ret_Est = 'A' AND retencion.Cop_Cod =$Par_Sql[0]) AND 
  det_compra.Cop_Cod = $Par_Sql[0] AND det_compra.Adq_Cod!=13
  GROUP by Cop_Cod";
  //echo $codigo_retenido;
  return $codigo_retenido;
  break;

  /*sentencia 856 esta ocupada en sitio local */	
  /*sentencias roles de pago  */
   /* busqueda del empleado para obtener sueldo*/
	  case 857:
	 $sueldo_empleado="SELECT distributi.Dis_Cod, sueldos.Sue_Val 	FROM distributi, sueldos  
	  WHERE  distributi.Dis_Cod= sueldos.Dis_Cod And distributi.Dis_Cod=$Par_Sql[0]";
	 //echo  $sueldo_empleado;
	 return  $sueldo_empleado;
	 break;	
	 case 858:
	 $mapeo_sueldo="SELECT Map_Cod, Map_Ide, Map_Des FROM map_system WHERE map_system.Map_Est='A'";
	 //echo  $mapeo_sueldo;
	 return  $mapeo_sueldo;
	 /***/
	 case 859:
	 $rol_general="SELECT Rol_Cod, Are_Cod, Pec_Cod, Rol_Fec,Rol_Con, Rol_Mes FROM rol_pagos WHERE Rol_Cod= $Par_Sql[0] AND Are_Cod=$Par_Sql[1] AND Pec_Cod= $Par_Sql[2]";
	//echo  $rol_general;
	 return   $rol_general;
	 break;		
	 case 860:
	 $rol_ingresos="SELECT Cam_Cod, Cam_Des, Cam_Dec, Cam_Por, Cam_Vis, Cam_Cal, Cam_Est, Cam_Req, Cam_Ord, Cam_Tip FROM campo_rol WHERE Cam_Tip='$Par_Sql[0]' AND Cam_Est='A' AND Cam_Vis='S'
	 ORDER BY Cam_Ord";
	//echo  $rol_ingresos;
	 return  $rol_ingresos;
	 break;		
	 case 861:
	 $rol_egresos="SELECT  campo_rol.Cam_Cod, campo_rol.Cam_Des, campo_rol.Cam_Dec, campo_rol.Cam_Por, campo_rol.Cam_Vis, campo_rol.Cam_Cal, campo_rol.Cam_Est, campo_rol.Cam_Req, campo_rol.Cam_Ord,
        campo_rol.Cam_Tip, campo_rol.Cam_Est, campo_rol.Cam_Vis FROM campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE
        Cam_Tip = '$Par_Sql[0]' AND   det_rpagos.Dis_Cod = $Par_Sql[1] AND campo_rol.Cam_Vis='S'  AND  det_rpagos.Rol_Cod = $Par_Sql[2] ORDER BY  Cam_Ord";
	//echo  $rol_egresos;
	 return  $rol_egresos;
	 break;
	 /*detalle de rol*/		
	 case 862:
	 $rol_detalle="SELECT  det_rpagos.Rol_Val,  det_rpagos.Dis_Cod,  det_rpagos.Cam_Cod,  campo_rol.Cam_Tip, campo_rol.Cam_Des, campo_rol.Cam_Vis  
     FROM campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Dis_Cod = $Par_Sql[0] AND det_rpagos.Rol_Cod = $Par_Sql[1]  AND Cam_Tip='$Par_Sql[2]' 
	 AND Cam_Vis= 'S' ORDER BY  Cam_Ord";
	//echo  $rol_detalle;
	 return  $rol_detalle;
	 break;
		
  	/*Insercion de roles con comprobantes*/		
	 case 863:
	 $rol_comprobantes="INSERT INTO compr_rol (Rol_Cod, Com_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
	 //echo  $rol_comprobantes;
	 return  $rol_comprobantes;
	 break;		

	 /*Selcción de cuentas para insertar en el asiento contable*/		
	 case 864:
	 $rol_plan="SElECT Cam_Cod, Pld_Cod FROM rol_plan WHERE Cam_Cod=$Par_Sql[0] AND Are_Cod=$Par_Sql[1]";
	// echo $rol_plan;
	 return $rol_plan;
	 break;				
		
   	/* Inserción de cada asiento del comprobante */
	 case 865:
	 $ins_rol_asiento="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), 
	 Asi_Glo=UPPER('$Par_Sql[4]'),Pld_Cod=$Par_Sql[5]";
	 //echo $ins_rol_asiento;
	 return $ins_rol_asiento;
	 break;

	 /* Inserción de cada asiento del comprobante */
	 case 866:
	 $con_rol_visible="SELECT Cam_Tip, Cam_Cod, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis FROM campo_rol WHERE Cam_Cod=$Par_Sql[0]";
	 //echo $con_rol_visible;
	 return $con_rol_visible;
	 break;
	 /*Seleccion de los campos ingresos para generar columnas*/
	 case 867:
	 $campos_rol_ingresos= "SELECT Cam_Tip, campo_rol.Cam_Cod, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis FROM campo_rol, rol_plan WHERE campo_rol.Cam_Est = 	
	 'A' AND Cam_Tip='I' AND Cam_Vis= 'S' AND rol_plan.Cam_Cod= campo_rol.Cam_Cod AND rol_plan.Are_Cod=$Par_Sql[0] ORDER BY Cam_Tip Desc, Cam_Ord ASC";
	 //echo $campos_rol_ingresos;
	 return $campos_rol_ingresos;
	 break; 
	 /*Seleccion de los campos ingresos para generar columnas*/
	 case 868:
	 $campos_rol_egresos= "SELECT Cam_Tip, campo_rol.Cam_Cod, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis FROM campo_rol, rol_plan WHERE campo_rol.Cam_Est = 	
	 'A' AND Cam_Tip='E' AND Cam_Vis= 'S' AND rol_plan.Cam_Cod= campo_rol.Cam_Cod AND rol_plan.Are_Cod=$Par_Sql[0] ORDER BY Cam_Tip Desc, Cam_Ord ASC";
	 //echo $campos_rol_egresos;
	 return $campos_rol_egresos;
	 break; 
	 /* Determina cuenta unica del proveedor en el plan de cuentas */
	 /*********/
	 case 869:
	 $ccpp_emple = "SELECT ccpp_emple.Pld_Cod, det_plan.Pld_Des, ccpp_emple.Ccp_Def FROM det_plan INNER JOIN ccpp_emple ON (det_plan.Pld_Cod = ccpp_emple.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
	//echo $ccpp_emple;
	 return $ccpp_emple;
	 break;

	 case 870:
	 $campos_reportes = "SELECT  det_rpagos.Rol_Val,  det_rpagos.Dis_Cod,  det_rpagos.Cam_Cod,  campo_rol.Cam_Tip, campo_rol.Cam_Des, campo_rol.Cam_Vis  FROM campo_rol 
	 INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Dis_Cod = $Par_Sql[0] AND det_rpagos.Rol_Cod = $Par_Sql[1] AND campo_rol.Cam_Tip='I' AND 
	 campo_rol.Cam_Vis='S' ORDER BY  campo_rol.Cam_Tip DESC,  campo_rol.Cam_Ord ASC";
	//echo $campos_reportes;
	 return $campos_reportes;
	 break;

	 case 871:
	 $campos_rep = "SELECT  det_rpagos.Rol_Val,  det_rpagos.Dis_Cod,  det_rpagos.Cam_Cod,  campo_rol.Cam_Tip, campo_rol.Cam_Des, campo_rol.Cam_Vis  FROM campo_rol 
	 INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Dis_Cod = $Par_Sql[0] AND det_rpagos.Rol_Cod = $Par_Sql[1] AND campo_rol.Cam_Tip='E' AND 
	 campo_rol.Cam_Vis='S' ORDER BY  campo_rol.Cam_Tip DESC,  campo_rol.Cam_Ord ASC";
	//echo $campos_rep;
	 return $campos_rep;
	 break;

	 case 872:
	 $consult_camp = "SELECT Cam_Des from campo_rol WHERE Cam_Cod=$Par_Sql[0]";
	//echo  $consult_camp;
	 return  $consult_camp;
	 break;

  case 873:
  $actualizar_rol = "UPDATE det_rpagos SET Rol_Val = $Par_Sql[0] WHERE Rol_Cod=$Par_Sql[1] AND Dis_Cod=$Par_Sql[2] AND Cam_Cod= $Par_Sql[3]";
  //echo  $actualizar_rol;
  return  $actualizar_rol;
  break;

 /*Consulta para obtener roles de pago segun el mes de creacion*/
  case 874:
  $consulta_rol = "SELECT rol_pagos.Rol_Cod, areas_rrhh.Are_Des, rol_pagos.Rol_Mes, rol_pagos.Rol_Con, rol_pagos.Rol_Fec, areas_rrhh.Are_Cod FROM areas_rrhh INNER JOIN rol_pagos ON  
  (areas_rrhh.Are_Cod = rol_pagos.Are_Cod) WHERE rol_pagos.Rol_Mes = '$Par_Sql[0]'";
  //echo  $consulta_rol;
  return  $consulta_rol;
  break;

 /*Consulta para obtener eel total d eingresos*/
	 case 875:
	 $consulta_totrol = "SELECT SUM(det_rpagos.Rol_Val) as total_ing FROM det_rpagos, campo_rol WHERE Rol_Cod= $Par_Sql[0] AND Dis_Cod= $Par_Sql[1]
AND det_rpagos.Cam_Cod= campo_rol.Cam_Cod AND campo_rol.Cam_Tip= 'I' AND campo_rol.Cam_Vis= 'S' ";
	 //echo  $consulta_totrol;
	 return  $consulta_totrol;
	 break;
	 /*Consulta para obtener eel total d egresos*/
	 case 876:
	 $consulta_totegr = "SELECT SUM(det_rpagos.Rol_Val) as total_egr  FROM det_rpagos, campo_rol WHERE Rol_Cod= $Par_Sql[0] AND Dis_Cod= $Par_Sql[1]
AND det_rpagos.Cam_Cod= campo_rol.Cam_Cod AND campo_rol.Cam_Tip= 'E' AND campo_rol.Cam_Vis= 'S' ";
	 //echo  $consulta_totegr;
	 return  $consulta_totegr;
	 break;
	  /*inserción de ccpp_rol Almacena los comprobantes que estan como pagos pendientes*/
	 case 877:
	 $insert_ccpprol = "INSERT INTO ccpp_rol SET  Com_Cod=$Par_Sql[0], Rol_Cod=$Par_Sql[1], Cpp_Obs= '$Par_Sql[2]' , Cpp_Sys= '$Par_Sql[3]'";
	 //echo  $insert_ccpprol;
	 return  $insert_ccpprol;
	 break;
	 /****/
	 /*inserción de ccpp_rol Almacena los comprobantes que estan como pagos pendientes*/
	 case 878:
	 $consul_det_pagos = "SELECT  SUM(det_ccpp_r.Pag_Val) AS suma FROM
  	 ccpp_rol  INNER JOIN det_ccpp_r ON (ccpp_rol.Cpp_Cod = det_ccpp_r.Cpp_Cod) INNER JOIN rol_pagos ON (ccpp_rol.Rol_Cod = rol_pagos.Rol_Cod)
	 WHERE det_ccpp_r.Dis_Cod = $Par_Sql[0] AND rol_pagos.Rol_Cod=$Par_Sql[1] ";
	 echo $consul_det_pagos ;
	 return  $consul_det_pagos;
	 break;
	 /****/
	 /*Consulta delplan d ecuentas segun el periodo contable*/
	 case 879:
	 $consul_pla_cod = "SELECT Pla_Cod FROM perio_cont WHERE Pec_Cod=$Par_Sql[0]";
	 //echo $consul_pla_cod ;
	 return $consul_pla_cod;
	 break;
	 /****/
	 /*Consulta del comprobante de diario de Rol de Pagos */
	 case 880:
	 $consul_pla_cod = "SELECT Com_Cod, Cpp_Cod FROM ccpp_rol WHERE ccpp_rol.Rol_Cod= $Par_Sql[0]";
	// echo $consul_pla_cod ;
	 return $consul_pla_cod;
	 break;
	 
	 /****/
	 /*Consulta la cuenta en base al asiento contable */
	 case 881:
	 $consul_pla_cod = "SELECT asientos.Asi_Cod, asientos.Com_Cod, asientos.Pld_Cod FROM ccpp_emple INNER JOIN asientos ON (ccpp_emple.Pld_Cod = asientos.Pld_Cod)
	 WHERE  asientos.Com_Cod=$Par_Sql[0]  AND asientos.Asi_Deh='H'";
	// echo $consul_pla_cod;
	 return $consul_pla_cod;
	 break;
	 /***/
	 /* Inserción de detalle del pago de la factura de credito en la tabla det_ccpp_p */
	case 882:
	$ins_det_ccpprol="INSERT INTO det_ccpp_r SET Cpp_Cod=$Par_Sql[0], Com_Cod=$Par_Sql[1],Pag_Fec='$Par_Sql[2]', Pag_Val= $Par_Sql[3], Pag_Obs= '$Par_Sql[4]', Dis_Cod=$Par_Sql[5]";
	//echo $ins_det_ccpprol;
	return $ins_det_ccpprol;
	break;
	/***/
	 /* Inserción de detalle del pago de la factura de credito en la tabla det_ccpp_p */
	case 883:
	$cons_provee="SELECT proveedore.Prv_Cod FROM persona, distributi, personal, proveedore WHERE persona.Prs_Cod= personal.Prs_Cod AND distributi.Per_Cod= personal.Per_Cod AND 
	proveedore.Prs_Cod= persona.Prs_Cod AND distributi.Dis_Cod=$Par_Sql[0]";
	//echo $cons_provee;
	return $cons_provee;
	break;
	

 /* Inserción de detalle del pago de la factura de credito en la tabla det_ccpp_p ojojojoj*/
 case 884:
 $cons_config="SELECT afiliacion.Afi_Fnd FROM  afiliacion WHERE afiliacion.Afi_Est = 'A' AND afiliacion.Dis_Cod = $Par_Sql[0]";
//echo $cons_config;
 return $cons_config;
 break;

 /* Consulta la funcion asignada para el campo */
 case 885:
 $cons_camcod_885="SELECT  modulo_rol.Mro_Fun, modulo_rol.Mro_Tip FROM campo_rol INNER JOIN modulo_rol ON (campo_rol.Cam_Cod = modulo_rol.Cam_Cod) WHERE
  campo_rol.Cam_Cod = $Par_Sql[0] AND modulo_rol.Mro_Tip = '$Par_Sql[1]'";
//echo $cons_camcod_885;
 return $cons_camcod_885;
 break;
 
 //Consulta el sueldo del empleado
 case 886:
 $cons_tabla_886="SELECT sueldos.Sue_Val FROM sueldos WHERE  sueldos.Sue_Est = 'A' AND sueldos.Dis_Cod = $Par_Sql[0]";
//echo $cons_tabla_886;
 return $cons_tabla_886;
 break;
 
 //Consulta la tabla de configuracion 
 case 887:
 $cons_tabla_887="SELECT Apo_Fnd FROM  aportacion WHERE  aportacion.Cam_Cod = $Par_Sql[0]";
//echo $cons_tabla_887;
 return $cons_tabla_887;
 break;
 
 /* Consulta de los campos de un rol de pagos especifico */
 case 888:
 $campos_rol_888="SELECT DISTINCT
 campo_rol.Cam_Tip, campo_rol.Cam_Cod, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis, campo_rol.Cam_Sum
FROM
  det_rpagos
  INNER JOIN campo_rol ON (det_rpagos.Cam_Cod = campo_rol.Cam_Cod)
WHERE
  det_rpagos.Rol_Cod = $Par_Sql[0] ORDER BY Cam_Tip Desc, Cam_Ord ASC";
	//echo $campos_rol_888;
 return $campos_rol_888;
 break;

 /* Consulta del personal de un rol de pagos especifico*/
 case 889:
$personal_rol_889="SELECT DISTINCT personal.Per_Cod, distributi.Dis_Cod, persona.Prs_Nom, persona.Prs_Ape, tiposcargo.Tic_Des, persona.Prs_Ced, departamen.Dep_Cod,
  departamen.Dep_Des   FROM   rol_pagos INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod) INNER JOIN distributi ON (det_rpagos.Dis_Cod = distributi.Dis_Cod)
  INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod) INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod) INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod)  WHERE  rol_pagos.Rol_Cod = $Par_Sql[0]  ORDER BY   persona.Prs_Ape";
 /*$personal_rol_889="SELECT DISTINCT 
  personal.Per_Cod, distributi.Dis_Cod, persona.Prs_Nom,
  persona.Prs_Ape, tiposcargo.Tic_Des,
  persona.Prs_Ced, departamen.Dep_Cod,
  departamen.Dep_Des
  FROM   rol_pagos
  INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod)
  INNER JOIN distributi ON (det_rpagos.Dis_Cod = distributi.Dis_Cod)
  INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
  INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod)
  WHERE
  rol_pagos.Rol_Cod = $Par_Sql[0]
  ORDER BY
  persona.Prs_Ape";*/

 //echo $personal_rol_889;
 return $personal_rol_889;
 break;

   /* Consulta el valor de un campo del rol de pagos */
 case 890:
 $personal_rol_890="SELECT det_rpagos.Rol_Val FROM det_rpagos WHERE det_rpagos.Rol_Cod = $Par_Sql[0] AND det_rpagos.Dis_Cod = $Par_Sql[1] AND det_rpagos.Cam_Cod = $Par_Sql[2]";
 //echo $personal_rol_890;
 return $personal_rol_890;
 break;

  /*Seleccion de los campos ingresos para generar columnas de un rol de pagos especifico */
  case 891:
  $campos_rol_ingresos_891 = "SELECT DISTINCT 
  campo_rol.Cam_Tip,
  campo_rol.Cam_Cod,
  campo_rol.Cam_Ord,
  campo_rol.Cam_Des,
  campo_rol.Cam_Dec,
  campo_rol.Cam_Por,
  campo_rol.Cam_Cal,
  campo_rol.Cam_Req,
  campo_rol.Cam_Vis
FROM campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Rol_Cod = $Par_Sql[0] AND  Cam_Tip = 'I' AND  Cam_Vis = 'S'
ORDER BY Cam_Tip Desc, Cam_Ord ASC"; 
  //echo $campos_rol_ingresos_891;
  return $campos_rol_ingresos_891;
  break; 
 
   /*Seleccion de los campos ingresos para generar columnas de un rol de pagos especifico */
  case 892:
  $campos_rol_ingresos_892 = "SELECT DISTINCT 
  campo_rol.Cam_Tip,
  campo_rol.Cam_Cod,
  campo_rol.Cam_Ord,
  campo_rol.Cam_Des,
  campo_rol.Cam_Dec,
  campo_rol.Cam_Por,
  campo_rol.Cam_Cal,
  campo_rol.Cam_Req,
  campo_rol.Cam_Vis
  FROM campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Rol_Cod = $Par_Sql[0] AND  Cam_Tip = 'E' AND   Cam_Vis = 'S'  ORDER BY Cam_Tip Desc, Cam_Ord ASC";
  //echo $campos_rol_ingresos_892;
  return $campos_rol_ingresos_892;
  break; 

 /* Consulta el comprobante generado automaticamente */
  case 893:
  $campos_rol_ingresos_893 = "SELECT compr_rol.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Fec FROM comprobantes INNER JOIN compr_rol ON (comprobantes.Com_Cod = compr_rol.Com_Cod)
  WHERE compr_rol.Rol_Cod = $Par_Sql[0]";
  //echo $campos_rol_ingresos_893;
  return $campos_rol_ingresos_893;
  break; 

 	/* Borrado de los asientos del comprobante */
	case 894:
	$bor_ascompr_894 = "DELETE FROM asientos WHERE Com_Cod=$Par_Sql[0]";
	//echo $bor_ascompr_894;
	return $bor_ascompr_894;
	break;	

/* Consulta de la configuración de rol */
 case 895:
 $conf_rol = "SELECT Apo_Fnd, Apo_Con FROM aportacion";
 //echo $conf_rol;
 return $conf_rol;
 break; 
 /* Consulta de empleados que no existe en el rol de pagos actual */
 case 896:
 $agre_emp = "SELECT personal.Per_Cod, distributi.Dis_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as persona, tiposcargo.Tic_Des, persona.Prs_Ced FROM  distributi
   INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
   INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
   INNER JOIN departamen ON (departamen.Dep_Cod = tiposcargo.Dep_Cod)
   INNER JOIN areas_rrhh ON (areas_rrhh.Are_Cod = departamen.Are_Cod)
    WHERE areas_rrhh.Are_Cod=$Par_Sql[0] and distributi.Dis_Cod  NOT IN( SELECT DISTINCT distributi.Dis_Cod FROM  rol_pagos
   INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod)
   INNER JOIN distributi ON (det_rpagos.Dis_Cod = distributi.Dis_Cod)
   INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
   INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
   INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod) WHERE  rol_pagos.Rol_Cod = $Par_Sql[1])";
 //echo $agre_emp;
 return $agre_emp;
 break; 

 /* Consulta de la configuración de rol */
 case 897:
 $cons_depart_897 ="SELECT DISTINCT departamen.Are_Cod, departamen.Dep_Des, rol_pagos.Rol_Cod, departamen.Dep_Cod, rol_pagos.Are_Cod FROM  rol_pagos
   INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod)
   INNER JOIN distributi ON (distributi.Dis_Cod = det_rpagos.Dis_Cod)
   INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
   INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod)
 WHERE rol_pagos.Rol_Cod = $Par_Sql[0] ORDER BY departamen.Dep_Des";
 return $cons_depart_897;
 break;




 /*Consulta el RRHH en un rol d pagos solo el codigo distributivo*/
 // case 983:
 // $sql_983="SELECT DISTINCT CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) as persona, persona.Prs_Ced, det_rpagos.Rol_Cod, 
//  det_rpagos.Dis_Cod
//  FROM distributi INNER JOIN det_rpagos ON (distributi.Dis_Cod = det_rpagos.Dis_Cod) 
//  INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod) INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
//  WHERE det_rpagos.Rol_Cod = $Par_Sql[0]";
  //echo $sql_983;
 // return $sql_983;
 // break;

 	/* Borrado de los asientos del comprobante */
	//case 894:
	//$bor_ascompr_894 = "DELETE FROM asientos WHERE Com_Cod=$Par_Sql[0]";
	//echo $bor_ascompr_894;
	//return $bor_ascompr_894;
	//break;	

/* Consulta de la configuración de rol */
 //case 895:
 //$conf_rol = "SELECT Apo_Fnd, Apo_Con FROM aportacion";
 //echo $conf_rol;
 //return $conf_rol;
// break; 
 /* Consulta de empleados que no existe en el rol de pagos actual */
// case 896:
 //$agre_emp = "SELECT personal.Per_Cod, distributi.Dis_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as persona, tiposcargo.Tic_Des, persona.Prs_Ced FROM  distributi
   //INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
 //  INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
 //  INNER JOIN departamen ON (departamen.Dep_Cod = tiposcargo.Dep_Cod)
 //  INNER JOIN areas_rrhh ON (areas_rrhh.Are_Cod = departamen.Are_Cod)
  //  WHERE areas_rrhh.Are_Cod=$Par_Sql[0] and distributi.Dis_Cod  NOT IN( SELECT DISTINCT distributi.Dis_Cod FROM  rol_pagos
  // INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod)
  // INNER JOIN distributi ON (det_rpagos.Dis_Cod = distributi.Dis_Cod)
  // INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
  // INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
  // INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod) WHERE  rol_pagos.Rol_Cod = $Par_Sql[1])";
 //echo $agre_emp;
// return $agre_emp;
// break; 

	/* Consulta de la configuración de rol */
	//case 897:
	//$cons_depart ="SELECT DISTINCT departamen.Are_Cod, departamen.Dep_Des, rol_pagos.Rol_Cod, departamen.Dep_Cod, rol_pagos.Are_Cod FROM  rol_pagos
  	//INNER JOIN det_rpagos ON (rol_pagos.Rol_Cod = det_rpagos.Rol_Cod)
  	//INNER JOIN distributi ON (distributi.Dis_Cod = det_rpagos.Dis_Cod)
  	//INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod)
  	//INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod)
	//WHERE rol_pagos.Rol_Cod = $Par_Sql[0]"; 
	
	//echo $cons_depart;
	//return $cons_depart;
	//break;	
	/////
	case 898:
	/* Cargando las areas de la universidad	*/								
	$cargar_camfor="SELECT  formulas.Cam_Cod, formulas.Cam_Rec, formulas.For_Est FROM  formulas WHERE  Cam_Rec=$Par_Sql[0]  AND For_Est='A'";
	//echo $cargar_camfor;
	return $cargar_camfor;
	break;

  /* Consulta de los campos de un rol de pagos especifico */
 case 899:
 $campos_rol_888="SELECT DISTINCT
 campo_rol.Cam_Tip, campo_rol.Cam_Cod, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis, campo_rol.Cam_Sum
FROM
  det_rpagos
  INNER JOIN campo_rol ON (det_rpagos.Cam_Cod = campo_rol.Cam_Cod)
WHERE
  det_rpagos.Rol_Cod = $Par_Sql[0] AND campo_rol.Cam_Vis= 'S' ORDER BY Cam_Tip Desc, Cam_Ord ASC";
	//echo $campos_rol_888;
 return $campos_rol_888;
 break;




 

      

	case 909:
	/* Cargando las areas de la universidad	*/								
	$cargar_sue_909="SELECT Are_Cod,Are_Des FROM areas_rrhh WHERE Are_Est='A' ";
	//echo $cargar_sue_909;
	return $cargar_sue_909;
	break;

	case 910:
	 /* Cargando las areas de la universidad	segu el codigo*/								
	 $cargar_sue_910="SELECT Are_Des from areas_rrhh where Are_Cod=$Par_Sql[0] ";
	 //echo $cargar_sue_910;
	 return $cargar_sue_910;
	 break;
	 
	 /*Cargando las personas para el Rol de Pagos*/
	 case 915:
	 $cargar_per_rol_915="SELECT personal.Per_Cod, distributi.Dis_Cod, persona.Prs_Nom, persona.Prs_Ape, tiposcargo.Tic_Des, persona.Prs_Ced FROM
        personal, distributi, persona, tiposcargo, departamen, areas_rrhh WHERE
        distributi.Tic_Cod = tiposcargo.Tic_Cod AND 
        tiposcargo.Dep_Cod = departamen.Dep_Cod AND 
        departamen.Are_Cod = areas_rrhh.Are_Cod AND 
        areas_rrhh.Are_Cod = $Par_Sql[0] AND 
        distributi.Per_Cod = personal.Per_Cod AND 
        personal.Prs_Cod = persona.Prs_Cod AND 
        persona.Prs_Cod = personal.Prs_Cod AND 
        personal.Per_Cod = distributi.Per_Cod
        AND distributi.Dis_Est = 'A'
        ORDER BY Prs_Ape,   Prs_Nom Asc";
	//echo $cargar_per_rol_915;
	 return $cargar_per_rol_915;
	 break;
	 
	 case 918:
	 $cargar_mes_rol_918="SELECT Rol_Val from det_rpagos,ingre_egre,campo_rol where Rol_Cod=$Par_Sql[0] and Dis_Cod=$Par_Sql[1] and 	
	 det_rpagos.Cam_Cod=campo_rol.Cam_Cod and campo_rol.I_e_Cod=ingre_egre.I_e_Cod and det_rpagos.Cam_Cod=$Par_Sql[2]";
	 //echo $cargar_mes_rol_918;
	 return $cargar_mes_rol_918;
	 break;
	 
	 //Busqueda del receptor caja chica por apellido
	case 919:
	$Sql_919="SELECT 
				  receptor.Rec_Cod,
				  personal.Per_Cod,
				  persona.Prs_Ced,
				  persona.Prs_Nom,
				  persona.Prs_Ape,
				  receptor.Rec_Est,
				  recibo.Rcb_Cod,
				  recibo.Aut_Cod,
				  recibo.Con_Cod,
				  recibo.Rcb_Fec,
				  recibo.Rcb_Obs,
				  recibo.Rcb_Con,
				  if(recibo.Rcb_Est='A','ACTIVO','INACTIVO')as Estado,
				  consumo.Con_Cod,
				  consumo.Con_Des
				FROM
				  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN distributi ON (personal.Per_Cod = distributi.Per_Cod)
				  INNER JOIN receptor ON (distributi.Dis_Cod = receptor.Dis_Cod)
				  INNER JOIN recibo ON (receptor.Rec_Cod = recibo.Rec_Cod)
				  INNER JOIN consumo ON (recibo.Con_Cod = consumo.Con_Cod)
				WHERE
				  persona.Prs_Ced=$Par_Sql[0] AND recibo.Rcb_Est = 'A'";
	//echo $Sql_919;
	return $Sql_919;
	break;

	/* Consulta del Detalle del Recibo de caja chica */
	case 920:
	$Sql_920="SELECT 
				  det_recibo.Rcb_Cod,
				  gastos.Gas_Des,
				  det_recibo.Gas_Cod,
				  det_recibo.Rcb_Val,
				  det_recibo.Rcb_Can,
				  det_recibo.Rcb_Imp
			  FROM
				  det_recibo
				  INNER JOIN gastos ON (det_recibo.Gas_Cod = gastos.Gas_Cod)
			  WHERE
				  det_recibo.Rcb_Cod=$Par_Sql[0]";
	//echo $Sql_920;
	return $Sql_920;
	break;	
	
	/* ANULAR UN RECIBO DE CAJA CHICA*/
	case 921:
	$Sql_921="UPDATE recibo SET Rcb_Est ='I' WHERE Rcb_Cod = $Par_Sql[0]" ; 
	//echo $Sql_921; 
	return $Sql_921;
	break;
	
	/* Actualizamos el saldo de caja chica cuando damos de baja un recibo*/
	case 922:
	$Sql_922="UPDATE reposicion SET Cja_Sal =$Par_Sql[0] WHERE Cja_Cod = $Par_Sql[1]"; 
	//echo $Sql_922; 
	return $Sql_922;
	break;
	

	/* Guardando cabecera del rol de pagos */
	 case 924:
	 $insert_cab_rol_924="INSERT INTO rol_pagos (Are_Cod,Pec_Cod,Rol_Fec,Rol_Con,Rol_mes) VALUES ($Par_Sql[0], $Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
	// echo $insert_cab_rol_924;
	 return $insert_cab_rol_924;
	 break; 
	 
	 /* Guardando detalle del rol de pagos */
	 case 925:
	 $insert_det_rpagos_925="INSERT INTO det_rpagos (Rol_Cod,Dis_Cod,Cam_Cod,Rol_Val) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3])";
	 //echo $insert_det_rpagos_925."<br>";
	 return $insert_det_rpagos_925;
	 break; 

	case 980:
	 $campos_rol_ingresos_980= "SELECT Cam_Tip, campo_rol.Cam_Cod, Cam_Sum, Cam_Ord, Cam_Des,Cam_Dec, Cam_Por, Cam_Cal,Cam_Req, Cam_Vis FROM campo_rol, rol_plan 
	WHERE campo_rol.Cam_Est = 'A' AND rol_plan.Cam_Cod= campo_rol.Cam_Cod AND rol_plan.Are_Cod=$Par_Sql[0] ORDER BY Cam_Tip Desc, Cam_Ord ASC ";
	// echo $campos_rol_ingresos_980;
	 return $campos_rol_ingresos_980;
	 break;  
	 
	 /*Consulta la cabecera del reporte general del rol de pagos*/
	 case 981:
	 $Reporte_General="SELECT DISTINCT det_rpagos.Rol_Cod, campo_rol.Cam_Por,  campo_rol.Cam_Cod,campo_rol.Cam_Des, campo_rol.Cam_Tip, campo_rol.Cam_Vis, campo_rol.Cam_Ord FROM
	 campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Rol_Cod = $Par_Sql[0] ORDER BY
     campo_rol.Cam_Tip desc, campo_rol.Cam_Ord ASC";
	//echo $Reporte_General;
	 return $Reporte_General;
	 break;
	 
	 /*Consulta el detalle del reporte general del rol de pagos*/ 
	 case 982:
	 $Reporte_GeneralDetalle_Rolpagos_982="SELECT  det_rpagos.Rol_Val,  det_rpagos.Dis_Cod,  det_rpagos.Cam_Cod,  campo_rol.Cam_Tip, campo_rol.Cam_Des, campo_rol.Cam_Vis  
     FROM campo_rol INNER JOIN det_rpagos ON (campo_rol.Cam_Cod = det_rpagos.Cam_Cod) WHERE det_rpagos.Dis_Cod = $Par_Sql[0] AND det_rpagos.Rol_Cod = $Par_Sql[1]  
	 ORDER BY campo_rol.Cam_Tip desc, campo_rol.Cam_Ord Asc";
	 //echo $Reporte_GeneralDetalle_Rolpagos_982;
	 return $Reporte_GeneralDetalle_Rolpagos_982;
	 break;
	 
	 /*Consulta el RRHH en un rol d pagos solo el codigo distributivo*/
	 case 983:
	 $sql_983="SELECT DISTINCT CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) AS persona, persona.Prs_Ced, det_rpagos.Rol_Cod, det_rpagos.Dis_Cod, sueldos.Sue_Val, tiposcargo.Tic_Des
	 FROM  distributi  INNER JOIN det_rpagos ON (distributi.Dis_Cod = det_rpagos.Dis_Cod) INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)  
        INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)  INNER JOIN sueldos ON (distributi.Dis_Cod = sueldos.Dis_Cod)  INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod) 
	 WHERE det_rpagos.Rol_Cod = $Par_Sql[0]";
	 //echo $sql_983;
	 return $sql_983;
	 break;
	 
	 //Consulta el tipo de Rol y la fecha a la que pertenece
	 case 984:
	 $Sql_984="SELECT areas_rrhh.Are_Cod, areas_rrhh.Are_Des, rol_pagos.Rol_Con, rol_pagos.Rol_Fec 
	 FROM areas_rrhh INNER JOIN rol_pagos ON (areas_rrhh.Are_Cod = rol_pagos.Are_Cod) WHERE rol_pagos.Rol_Cod = $Par_Sql[0]";
	 //echo $Sql_984;
	 return $Sql_984;
	 break;
	 
	 /*Consultando Detalle de Rol de Pagos*/
	 case 985:
	 $Sql_985="SELECT rol_pagos.Rol_Cod, areas_rrhh.Are_Des, rol_pagos.Rol_Mes, rol_pagos.Rol_Con, rol_pagos.Rol_Fec FROM areas_rrhh INNER JOIN rol_pagos ON 	
	 (areas_rrhh.Are_Cod = rol_pagos.Are_Cod) WHERE rol_pagos.Are_Cod = $Par_Sql[0] AND rol_pagos.Rol_Mes = '$Par_Sql[1]'";
	 //echo $Sql_985;
	 return $Sql_985;
	 break;
	 
	 //Busqueda de Personal por "Apellido" para Add al rol de pagos
	 case 986:
	 $Sql_986="SELECT  distributi.Dis_Cod, distributi.Per_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Nombre, tiposcargo.Tic_Des, distributi.Dis_Cod,
	 sueldos.Sue_Val FROM   distributi INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod) INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
	 INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod) INNER JOIN sueldos ON (distributi.Dis_Cod = sueldos.Dis_Cod) WHERE 
     persona.Prs_Ape LIKE '%$Par_Sql[0]%'";
	 //echo $Sql_986;
	 return $Sql_986;
	 break;
	 
	 //Busqueda de Personal por "Cedula" para Add al rol de pagos
	 case 987:
	 $Sql_987="SELECT   distributi.Dis_Cod, personal.Per_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Nombre, tiposcargo.Tic_Des, distributi.Dis_Cod,
	 sueldos.Sue_Val FROM  distributi INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod) INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
	 INNER JOIN tiposcargo ON (distributi.Tic_Cod = tiposcargo.Tic_Cod) INNER JOIN sueldos ON (distributi.Dis_Cod = sueldos.Dis_Cod) WHERE
	 persona.Prs_Ced='$Par_Sql[0]'";
	 //echo $Sql_987;
	 return $Sql_987;
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
	 $Sql_990="SELECT * FROM pago_venta WHERE Vet_Che = trim('$Par_Sql[0]')";
	 //echo $Sql_990;
	 return $Sql_990;
	 break;
	 
	

	/* CONTROL DE PRODUCTOS - Nebil */
case 1002: 
		/*Consulta una forma de pago en base a un parametro*/
		$busca_categoria_nom= "SELECT item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,Pro_Cod,Mar_Des, Pro_Est FROM item,categorias,producto,marca WHERE marca.Mar_Cod=producto.Mar_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=producto.Ite_Cod AND Ite_Lar LIKE '%$Par_Sql[0]%'";
		//echo $busca_categoria_nom;
		return $busca_categoria_nom;
		break;

	 case 1003: 
		/*Consulta una forma de pago en base a un parametro*/
		$busca_categoria_nom= "SELECT Ubi_Cod,Ubi_Des FROM ubicacion WHERE Ubi_Est='A' ORDER BY Ubi_Des ASC";
		return $busca_categoria_nom;
		break;
		
	 case 1004: 
		/*Consulta una forma de pago en base a un parametro*/
		$busca_unidad_nom= "SELECT Uni_Cod,Uni_Des FROM unidad WHERE Uni_Est='A' ORDER BY Uni_Des ASC";
		return $busca_unidad_nom;
		break;
		
		
	case 1005:
		/* Insertar formas de pago */

		$ins_item = "INSERT INTO item(Cat_Cod,Emp_Cod,Ite_Cor,Ite_Lar) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]')";

		return $ins_item;
		
		break;				
	 case 1006: 
		/*Consulta los nombres de los items*/
		$busca_item_nom= "SELECT Ite_Cod,Ite_Cor,Cat_Des,Cat_Cod,Ite_Lar FROM item,categorias WHERE item.Cat_Cod=categorias.Cat_Cod AND Ite_Cor ='$Par_Sql[0]'";		
		return  $busca_item_nom;
		break;

	 case 1007: 
		/*Consulta una forma de pago en base a un parametro*/
		$ins_producto= "INSERT INTO producto(Adq_Cod,Ite_Cod,Mar_Cod,Iva_Cod,Pro_Obs,Pro_Bar,Ubi_Cod,Uni_Cod,Pro_Sec,Pro_Cdc,Pro_Uni,Pro_Dsc) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],'$Par_Sql[4]','$Par_Sql[5]',$Par_Sql[6],$Par_Sql[7],$Par_Sql[8],'$Par_Sql[9]',$Par_Sql[10],$Par_Sql[11])";
 
		
return $ins_producto;
		break;

	case 1008: 
		/*Consultar si existen productos con las mismas marcas*/
		$busca_pro_mar= "SELECT * FROM producto WHERE  Ite_Cod=$Par_Sql[0] AND Mar_Cod=$Par_Sql[1]";
		return $busca_pro_mar;
		break;

		case 1009: 
		/*Consultar si existen productos con las mismas Categorias*/
		$busca_ite_cat= "SELECT * FROM item WHERE  Ite_Lar='$Par_Sql[0]' AND Cat_Cod=$Par_Sql[1]";
		return $busca_ite_cat;
		break;
		
		 case 1010: 
  /*Consultar todas las tablas relacionadas con la tablaproducto*/
  $busca_ite_cat= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod AND precios.Pro_Cod=producto.Pro_Cod AND precios.Tpv_Cod=1 AND precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0]";
  return $busca_ite_cat;
  break;
				
		case 1011: 
		$update_producto= "UPDATE producto SET Adq_Cod=$Par_Sql[0],Mar_Cod=$Par_Sql[2],Iva_Cod=$Par_Sql[3],Pro_Obs='$Par_Sql[4]',Pro_Bar='$Par_Sql[5]',Ubi_Cod=$Par_Sql[6],Uni_Cod=$Par_Sql[7],Pro_Cdc='$Par_Sql[9]' WHERE Ite_Cod=$Par_Sql[1] AND Pro_Cod=$Par_Sql[8]";
		//echo $update_producto;
		return $update_producto;
		
		
		case 1012: 
		$update_item= "UPDATE item SET Cat_Cod=$Par_Sql[1],Ite_Cor='$Par_Sql[2]',Ite_Lar='$Par_Sql[3]' WHERE Ite_Cod=$Par_Sql[0]";
		return $update_item;		
		break;
		
		/* LEWIS CHIMARRO */
		case 1013: 
		/* iserta gastos*/
		$ins_gastos= "INSERT INTO gastos(Emp_Cod,Gas_Des,Gas_Cor,Gas_Max) VALUES ($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]',$Par_Sql[3])";	
		return $ins_gastos;
		break;
		
		case 1014: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$ins_gastos= "SELECT Gas_Cod,Emp_Cod,Gas_Des,Gas_Cor,Gas_Max FROM gastos WHERE Gas_Des='$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo  $ins_gastos;
		return $ins_gastos;
		break;
		
		case 1015: 
		/* Para validar si existe cun gasto con el mismo nombre*/
		$con_gastos_des= "SELECT Gas_Cod,Emp_Cod,Gas_Des,Gas_Cor,Gas_Max FROM gastos WHERE Gas_Des LIKE '%$Par_Sql[0]%' AND Emp_Cod = $Par_Sql[1]";	
		return $con_gastos_des;
		break;

		return $con_gastos_des;
		break;
		
		case 1016: 
		/* Busqueda de Codigo de gasto*/
		$con_gastos_cod= "SELECT Gas_Cod,Emp_Cod,Gas_Des,Gas_Cor,Gas_Max,Gas_Est FROM gastos WHERE Gas_Cod=$Par_Sql[0]";	
//echo $con_gastos_cod;
		return $con_gastos_cod;
		break;

		case 1017: 
		/* Actualizar el gasto*/
		$update_gastos= "UPDATE gastos SET  Gas_Des='$Par_Sql[1]',Gas_Cor='$Par_Sql[2]',Gas_Max=$Par_Sql[3] WHERE Gas_Cod=$Par_Sql[0]";	
		
		return $update_gastos;
		break;

		case 1018: 
		/* Inserta precios*/
		$ins_precio=  "INSERT INTO precios(Pro_Cod,Pre_Pvp,Pre_Des,Suc_Cod,Tpv_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',1,'$Par_Sql[3]')";	
		
		return $ins_precio;
		break;
		
		case 1019: 
		/* consulta codigo de  por degfecto */
		
		$con_tp_precio=  "SELECT Tpv_Cod,Tpv_Des,Tpv_Est,Tpv_Def FROM tipo_preci WHERE Tpv_Def='$Par_Sql[0]'";					
		return $con_tp_precio;
		break;

		case 1020: 
		/* consulta  si existe otra maquina*/
		$con_mar=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' ";	
		//echo $con_mar;
		return $con_mar;
		break;
		
		case 1021: 
		/* consulta codigo de  por degfecto */
		$con_baj_ite="UPDATE producto SET Pro_Est='$Par_Sql[1]' WHERE Pro_Cod=$Par_Sql[0]";	
		return $con_baj_ite;
		break;

	case 1022: 
		/*Consulta una forma de pago en base a un parametro*/
		$busca_categoria_nom= "SELECT item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,Cat_Cdc FROM item,categorias WHERE item.Cat_Cod=categorias.Cat_Cod AND Ite_Lar LIKE '%$Par_Sql[0]%'";
		return $busca_categoria_nom;
		break;

		case 1023: 
		/* Actulizco solo el codigo de barra */
		$update_probar="UPDATE producto SET Pro_Bar='$Par_Sql[1]',Pro_Gen='$Par_Sql[2]' WHERE Pro_Cod=$Par_Sql[0]";	
		return $update_probar;
		break; 
		
		
		// Tomar el codigo Cat_Rec de un codigo determinado. Sirve para regresar de un directorio
		case 1024:
		$sql = "SELECT Cat_Rec, Cat_Des FROM categorias WHERE  Cat_Cod=$Par_Sql[0];";
		return $sql;
		break;
	
	// Tomar la descripcion Cat_Des del nivel superior. Sirve para regresar de un directorio
		case 1025:
		$sql = "SELECT Cat_Des FROM categorias WHERE Cat_Cod=$Par_Sql[0];";
		return $sql;
		break;
	
		
		// Insertar un nuevo tipo de categorias
		case 1026:
		$sql = "INSERT INTO categorias (Cat_Cdc, Cat_Des,Cat_Tip, Cat_Rec, Emp_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]',
				'$Par_Sql[2]',$Par_Sql[3],$Par_Sql[4])";
		
		return $sql;
		break;
		
		// Modificar un tipo de categorias
		case 1027:
		$sql = "UPDATE categorias SET Cat_Des='$Par_Sql[1]', Cat_Est='$Par_Sql[2]' WHERE Cat_Cod=$Par_Sql[0]";
		return $sql;
		break;
	
	// Tomar todos los tipos de categoria de un nivel, cualquiera que este sea 
		case 1028:
		$sql = "SELECT * FROM categorias WHERE Cat_Rec=$Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;
	
		case 1029:
		$sql = "UPDATE categorias SET Cat_Est='$Par_Sql[0]' WHERE Cat_Cod=$Par_Sql[1];";
		return $sql;
		break;
	/* consulta la categorias solo el detalle */
		case 1030:
		$carg_item= "SELECT Cat_Cod, Cat_Des,Cat_Cdc FROM categorias WHERE Cat_Tip='D' ";
		return $carg_item;
		
	/* Actualiza el detalle y grupo  de las catgorias*/	
		case 1031:
		$sql = "UPDATE categorias SET Cat_Des='$Par_Sql[1]', Cat_Tip='$Par_Sql[2]' WHERE Cat_Cod=$Par_Sql[0]";
		return $sql;
		break;
	/* consulta el maximo numero de Pro_Sec */

		case 1032:
		$sql = "SELECT MAX(Pro_Sec) as Pro_Sec FROM producto,item,categorias WHERE producto.Ite_Cod=item.Ite_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=$Par_Sql[0]";
		return $sql;
		break;

		case 1033:
		$sql = "SELECT MAX(Pro_Sec) as Pro_Sec FROM producto,item,categorias WHERE producto.Ite_Cod=item.Ite_Cod AND item.Cat_Cod=categorias.Cat_Cod AND categorias.Cat_Cod=$Par_Sql[0] ";
		echo $sql;

		return $sql;
		break;

		case 1034:
		$sql = "SELECT Cat_Cdc FROM categorias WHERE categorias.Cat_Cod=$Par_Sql[0]";
		echo $sql;
		return $sql;
		break;


	case 1035:
		$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14])";
		echo  $sql;
		return $sql;
	break;
			

	/* Selecciona todos los tipos de comprobantes */
	case 1036:
		$sql = "SELECT Tic_Cod, Tic_Des FROM tipo_compr WHERE Tic_Est='A'";		
		return $sql;
	break;
	
	/*  Validar las Adquisiciones */
	case 1037:
		$sql = "SELECT adquisicio.Adq_Cod FROM producto,adquisicio WHERE producto.Adq_Cod=adquisicio.Adq_Cod AND adquisicio.Adq_Cor='B' AND producto.Pro_Cod=$Par_Sql[0]";
		//echo $sql;


		return $sql;		
	break;
	
		case 1038: 
		$update_producto= "UPDATE producto SET Adq_Cod=$Par_Sql[0],Mar_Cod=$Par_Sql[2],Iva_Cod=$Par_Sql[3],Pro_Obs='$Par_Sql[4]',Pro_Bar='$Par_Sql[5]',Ubi_Cod=$Par_Sql[6],Uni_Cod=$Par_Sql[7],Pro_Cdc='$Par_Sql[9]',Pro_Sec=$Par_Sql[10],Pro_Uni=$Par_Sql[11],Pro_Dsc=$Par_Sql[12] WHERE Ite_Cod=$Par_Sql[1] AND Pro_Cod=$Par_Sql[8]";
echo $update_producto;		
return $update_producto;
		break;
	
	case 1039:
		$bor_precio="DELETE FROM kardex_ie WHERE Vet_Cod='$Par_Sql[0]'";
		return $bor_precio;
		break;
	
case 1040: 
  /*Consultar todas las tablas relacionadas con la tablaproducto*/
  $busca_ite_cat= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod  AND producto.Pro_Bar='$Par_Sql[0]' ";
  return $busca_ite_cat;
  break;		    	
  
  case 1041: 
  /*Consultar todas las tablas relacionadas con la tablaproducto*/
  $busca_ite_cat= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod  AND item.Ite_Lar  LIKE '%$Par_Sql[0]%'";
  return $busca_ite_cat;
  break;
  
 case 1042: 
  /*Cone sta sentencia consulto el movimiento del kardex con fechas */
  $busca_ite_cat= "SELECT Vet_Cod,Aju_Cod,Cop_Cod,Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock
  , (Kar_Ime-Kar_Ims)+(((((Kar_Ime-Kar_Ims)-(((Kar_Ime-Kar_Ims)*Kar_Des)/100)))*Iva_Por)/100) AS Saldo
  , (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_ent
  , (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_sal
   FROM kardex_ie,iva WHERE Kar_Est='A' AND Pro_Cod=$Par_Sql[2]  AND Kar_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'AND iva.Iva_Cod=kardex_ie.Iva_Cod ORDER BY Kar_Fec";
 return $busca_ite_cat;
  break;
    
   case 1043: 
  /*consulta la cantidad del producto por fecha 0000-00-00 */
  $busca_ite_cat= " SELECT SUM(Kar_Can)-SUM(Kar_Sal) AS Stock FROM kardex_ie  WHERE  Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1]";
  return $busca_ite_cat;
  break;

   case 1044: 
  /*consulto las ventas para cargar al kardex  */
  $busca_ite_cat= "SELECT  ventas.Vet_Num,Vet_Des,Tic_Des,Vet_Obs FROM ventas,kardex_ie,tipo_compr WHERE  kardex_ie.Vet_Cod=ventas.Vet_Cod AND tipo_compr.Tic_Cod=ventas.Tic_cod AND ventas.Vet_Cod=$Par_Sql[0]";
  return $busca_ite_cat;
  break;	    	
  
   case 1045: 
  /*consulto las compras para cargar al kardex  */
  $busca_ite_cat= "SELECT  compras.Cop_Cod,compras.Cop_Num,compras.Cop_Obs FROM compras,kardex_ie,tipo_compr WHERE  kardex_ie.Cop_Cod=compras.Cop_Cod AND tipo_compr.Tic_Cod=compras.Tic_cod AND compras.Cop_Cod=$Par_Sql[0]";
  return $busca_ite_cat;
  break;
    		
  case 1046: 
  /*consulto los ajustes para cargar al kardex  */
   $busca_ite_cat= "SELECT  ajuste_kar.Aju_Cod,ajuste_kar.Aju_Obs,Tia_Des,Aju_Sec FROM ajuste_kar,kardex_ie,`tipo_ajus` WHERE tipo_ajus.Tia_Cod=ajuste_kar.`Tia_Cod` AND  kardex_ie.Aju_Cod=ajuste_kar.Aju_Cod AND ajuste_kar.Aju_Cod=$Par_Sql[0]";
  return $busca_ite_cat;  
  break;
    		    
  case 1047: 
  /*consulta la cantidad del producto por fecha 0000-00-00 */
  $busca_ite_cat= " SELECT (Kar_Ime-Kar_Ims)+((  ((Kar_Ime-Kar_Ims)- (((Kar_Ime-Kar_Ims)*Kar_Des)/100)) *Iva_Por)/100) AS Saldo FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod" ;
  return $busca_ite_cat;
  break;  		


  /////////////////////-SQL-/////////////////////////////////////////////////////

case 1050:
  $Sql_ta="SELECT Tia_Cod,Tia_Des,Tia_Est,Tia_Tra FROM tipo_ajus WHERE Tia_Est='A' ORDER BY Tia_Des ASC";
 //echo $Sql_394;
  return $Sql_ta;
  break;
 		
  case 1051:
  $Sql_ta="INSERT INTO ajuste_kar(Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs,Aju_Num,Aju_Sec,Tia_Cod,Prv_Cod,Vnd_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]',
				'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',$Par_Sql[5],$Par_Sql[6],$Par_Sql[7],$Par_Sql[8])";

 
  return $Sql_ta;
  break;
 	    
  
  case 1052:
   $Sql_ta="INSERT INTO det_ajustek(Aju_Cod,Pro_Cod,Aju_Can,Aju_Pru,Aju_Imp) VALUES ($Par_Sql[0],$Par_Sql[1],
				$Par_Sql[2],$Par_Sql[3],$Par_Sql[4])";

	
    
  return $Sql_ta;
  break;

 /* consulta si la transaccion es un ingreso o egreso */
  
  case 1053:
   $Sql_ta="SELECT Tia_Cod,Tia_Des,Tia_Est,Tia_Tra FROM tipo_ajus WHERE Tia_Est='A' AND Tia_Cod=$Par_Sql[0]";
   	
  return $Sql_ta;
  break;
 
  case 1054: 
  /*Consultar todos los productos que son bienes */
  $busca_ite_cat= "SELECT precios.Tpv_Cod,item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des,Adq_Cor, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Stk_Can,Pre_Pvp FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios,tipo_preci,stock WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod   AND  precios.Pro_Cod=producto.Pro_Cod AND precios.Tpv_Cod=tipo_preci.Tpv_Cod AND precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Cod=stock.Pro_Cod AND item.Ite_Lar  LIKE '%$Par_Sql[0]%'";
 
  return $busca_ite_cat;
  break;                

  case 1055: 
  /* esta consulta sirve para generar un codigo  */
  $busca_ite_cat = "SELECT MAX(Aju_Sec) as Aju_Sec FROM ajuste_kar WHERE Tia_Cod=$Par_Sql[0]";
  return $busca_ite_cat;
  break; 
  
  case 1056: 
  /*consulta por tipo de comprabente y fecha la transaccion */
  $busca_ite_cat= "SELECT Aju_Cod,Aju_Sec,Aju_Est,Tia_Cod,Aju_Fec,Aju_Det,Aju_Obs,Prs_Ape,Prs_Nom,Prs_Ced FROM ajuste_kar,persona,proveedore WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=ajuste_kar.Prv_Cod AND Tia_Cod=$Par_Sql[0] AND Aju_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'";
//echo  $busca_ite_cat;
  return $busca_ite_cat;
  break;
  		     
  case 1057: 
  /*consulta datos de l detalle del ajuste*/
  $busca_ite_cat= "SELECT Aju_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Aju_Can,Aju_Pru,Aju_Imp  FROM det_ajustek,producto,item WHERE det_ajustek.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod AND Aju_Cod=$Par_Sql[0]";
 // echo $busca_ite_cat;
  return $busca_ite_cat;
  break;
  			
  case 1058: 
  /*anulo los comprobantes de ajustes*/
  $busca_ite_cat= "UPDATE ajuste_kar SET  Aju_Est='I' WHERE Aju_Cod=$Par_Sql[0] ";
  //echo $busca_ite_cat;
  return $busca_ite_cat;
  break;
  
  case 1059: 
  /*anulo los comprobantes de ajustes*/
  $busca_ite_cat= "UPDATE kardex_ie SET  Kar_Est='I' WHERE Aju_Cod=$Par_Sql[0] ";
 // echo $busca_ite_cat;
  return $busca_ite_cat;
  break;

  case 1060: 
  /*anulo los comprobantes de ajustes*/
  $busca_ite_cat= " SELECT Pro_Gen,Pro_Bar,Pre_Pvp,Ite_Lar,Pre_Est,Ubi_Des,Mar_Des,Pro_Cdc FROM producto,item,precios,ubicacion,marca WHERE producto.Mar_Cod = marca.Mar_Cod  AND ubicacion.Ubi_Cod=producto.Ubi_Cod AND precios.Pro_Cod=producto.Pro_cod AND producto.Ite_Cod=item.Ite_Cod AND Pro_Gen='G' AND Ite_Lar LIKE '%$Par_Sql[0]%'  LIMIT $Par_Sql[1], $Par_Sql[2]";
			
  return $busca_ite_cat;
  break;
  
  case 1061: 
  /*anulo los comprobantes de ajustes*/
  $busca_ite_cat= "SELECT Pro_Gen,Pro_Bar,Pre_Pvp,Ite_Lar,Pre_Est,Ubi_Des,Mar_Des FROM producto,item,precios,ubicacion,marca WHERE producto.Mar_Cod = marca.Mar_Cod  AND ubicacion.Ubi_Cod=producto.Ubi_Cod AND precios.Pro_Cod=producto.Pro_cod AND producto.Ite_Cod=item.Ite_Cod AND Pro_Gen='G' AND Ite_Lar LIKE '%$Par_Sql[0]%'";
 return $busca_ite_cat;
  break;
		     
 
  case 1062: 
  /*anulo los comprobantes de ajustes*/
  $busca_ite_cat= "UPDATE kardex_ie SET  Kar_Est='I' WHERE Vet_Cod=$Par_Sql[0] ";
 // echo $busca_ite_cat;
  return $busca_ite_cat;
  break; 


//SQL LIQUIDACION Y COMBUSTIBLES

//CAJA CHICA//
		case 1101: 
		/* Consultar datos del autorizado*/
		$consult_recibo=" SELECT recibo.Rcb_Obs, recibo.Rcb_Con, recibo.Rcb_Cod, autorizado.Aut_Cod,  consumo.Con_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
  		consumo.Con_Des,recibo.Cja_Cod FROM  autorizado   INNER JOIN recibo ON (autorizado.Aut_Cod = recibo.Aut_Cod)   INNER JOIN consumo ON (recibo.Con_Cod = consumo.Con_Cod)
  		INNER JOIN distributi ON (distributi.Dis_Cod = autorizado.Dis_Cod)  INNER JOIN personal ON (personal.Per_Cod = distributi.Per_Cod)  
		INNER JOIN persona ON (persona.Prs_Cod = personal.Prs_Cod) WHERE  recibo.Rcb_Est = 'A' AND  recibo.Rcb_Cod = $Par_Sql[0]";	
		//echo $consult_recibo;

		return $consult_recibo;
		break; 
		
		case 1102: 
		/* Consultar total del recibo*/
		$consult_total="SELECT  SUM(det_recibo.Rcb_Imp) as total, recibo.Rcb_Obs,  recibo.Rcb_Con,  recibo.Rcb_Cod FROM   recibo
        INNER JOIN det_recibo ON (recibo.Rcb_Cod = det_recibo.Rcb_Cod) WHERE recibo.Rcb_Cod = $Par_Sql[0]  GROUP by Rcb_Cod";	
		return $consult_total;
		break; 
		case 1103: 
		/* Consultar datos del recibo*/
		$consult_trecibo="SELECT det_recibo.Rcb_Imp, det_recibo.Rcb_Val, det_recibo.Gas_Cod, gastos.Gas_Des, gastos.Gas_Cor, recibo.Aut_Cod,
  		recibo.Rcb_Cod,  recibo.Rec_Cod,  det_recibo.Rcb_Can FROM   recibo  INNER JOIN det_recibo ON (recibo.Rcb_Cod = det_recibo.Rcb_Cod)
        INNER JOIN gastos ON (det_recibo.Gas_Cod = gastos.Gas_Cod) WHERE  recibo.Rcb_Cod = $Par_Sql[0] AND  recibo.Rcb_Est = 'A'";	
		//echo $consult_trecibo;
		return $consult_trecibo;
		break; 
		/* Consultar datos del recibo*/
		case 1104: 
		$consult_ced="SELECT  chofer.Dis_Cod, chofer.Chf_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Fec, persona.Prs_Cel
		FROM  chofer  INNER JOIN distributi ON (chofer.Dis_Cod = distributi.Dis_Cod) INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
  		INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod) WHERE  persona.Prs_Ced =$Par_Sql[0]";	
		return $consult_ced;
		break; 
		/* Consultar datos del recibo*/
		case 1105: 
		$consult_chofer="SELECT chofer.Dis_Cod, chofer.Chf_Est, chofer.Chf_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Fec,
  		persona.Prs_Cel FROM  chofer  INNER JOIN distributi ON (chofer.Dis_Cod = distributi.Dis_Cod)   INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
        INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod) WHERE  persona.Prs_Ape LIKE '%$Par_Sql[0]%'";	
		return $consult_chofer;
		break;
		
		//insertar detalle
		case 1106:
		$insert_liquid="INSERT INTO liquidacio (Rcb_Cod, Cop_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])"; 
		//echo $insert_liquid;
		return $insert_liquid;
		break;
		//consulta de vehiculo
		case 1107:
		$consult_vehi="SELECT Veh_Cod, Veh_Des, Vhe_Mod, Vhe_Pla from vehiculo WHERE vehiculo.Vhe_Est='A'"; 
		//echo $consult_vehi;
		return $consult_vehi;
		break;
		//Inserción de vehiculo
		case 1108:
		$insert_vehi="INSERT kilometraj (Veh_Cod, Chf_Cod, Kil_Ksa, Kil_Des, Kil_Mot,Kil_Fsa, Kil_Hsa) VALUES($Par_Sql[0], $Par_Sql[1],$Par_Sql[2], '$Par_Sql[3]', '$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]')"; 
		echo $insert_vehi;
		return $insert_vehi;
		break;
		//Consulta de vehiculo con chofer
		case 1109:
		$con_vehichofer="SELECT  kilometraj.Kil_Cod, chofer.Chf_Cod, kilometraj.Chf_Cod, vehiculo.Veh_Des, kilometraj.Kil_Ksa, kilometraj.Kil_Des, kilometraj.Kil_Mot,
        kilometraj.Kil_Fsa, kilometraj.Kil_Hsa FROM  kilometraj  INNER JOIN chofer ON (kilometraj.Chf_Cod = chofer.Chf_Cod)
        INNER JOIN vehiculo ON (kilometraj.Veh_Cod = vehiculo.Veh_Cod) WHERE  chofer.Chf_Cod=$Par_Sql[0]"; 
		echo $con_vehichofer;
		return $con_vehichofer;
		break;
			//Consulta de vehiculo con chofer
		case 1110:
		$mod_vehichofer="UPDATE kilometraj SET Kil_Kll= $Par_Sql[1], Kil_Fll='$Par_Sql[2]', Kil_Hll= '$Par_Sql[3]' WHERE Kil_Cod = $Par_Sql[0]"; 
		echo $mod_vehichofer;
		return $mod_vehichofer;
		break;
		//Consulta de requerido del campo
		case 1115:
		$con_camreq="SELECT campo_rol.Cam_Cal FROM  campo_rol WHERE campo_rol.Cam_Cod= $Par_Sql[0]"; 
		//echo $con_camreq;
		return $con_camreq;
		break;

		//Nebil
   		case 1063: 
  		/*consulta por tipo de comprabente y fecha la transaccion */
 		 $busca_ite_cat= "SELECT Aju_Cod,Aju_Sec,Aju_Est,Prs_Dir,Aju_Num,Tia_Tra,tipo_ajus.Tia_Cod,Ciu_Des,Tia_Des,Aju_Fec,Aju_Det,Aju_Obs,Prs_Ape,Prs_Nom,Prs_Ced,Vnd_Cod FROM ajuste_kar,persona,proveedore,tipo_ajus,ciudad WHERE ciudad.Ciu_Cod=persona.Ciu_Cod AND tipo_ajus.Tia_Cod=ajuste_kar.Tia_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=ajuste_kar.Prv_Cod AND ajuste_kar.Aju_Cod=$Par_Sql[0] ";
 		//echo  $busca_ite_cat;
 		 return $busca_ite_cat;
		 break;
  					
    		case 1064: 
  		/*COnsulta de ajustes */
 		 $busca_ite_cat= "SELECT Aju_Cod,producto.Pro_Cod,Ite_Lar,Aju_Can,Aju_Pru,Aju_Imp FROM det_ajustek,item,producto WHERE det_ajustek.Aju_Cod=$Par_Sql[0] AND det_ajustek.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod ";
  		return $busca_ite_cat;
  		break;
  				   
  		case 1065:
		$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Vnd_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15])";
		//echo  $sql;
  		return $sql;
  		break;
  			
  		case 1066:
		/*Consulta del vendedor en base al codigo de la persona*/
		$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod,Prs_Nom FROM vendedor,persona WHERE persona.Prs_Cod=vendedor.Prs_Cod AND vendedor.Vnd_Cod = $Par_Sql[0]";
		return $consultar_vendedor;
		break;
					
		 case 1067: 
  		/*Consultar todos los productos que son bienes y por categoria */
  		$busca_ite_cat= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, 	iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod  AND  Adq_Cor='B' AND Pro_Cdc<>'' AND categorias.Cat_Cod=$Par_Sql[0] ORDER BY ubicacion.Ubi_Cod,Pro_Cdc ";
 		return $busca_ite_cat;
  		break;
   				
   		case 1068: 
  		/*consulto los cantidades del producto  */
   		$busca_ite_cat="SELECT SUM(Kar_Can) AS Kar_Can,SUM(Kar_Sal) AS Kar_Sal,SUM(Kar_Can)-SUM(Kar_Sal) AS Stock FROM kardex_ie  WHERE  Kar_Est='A' AND Pro_Cod=$Par_Sql[2] AND Kar_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' ";
    		return $busca_ite_cat;
  		break;
				
		case 1069: 
  		/*esta consulta sirve para generar un codigo  */
  		$busca_ite_cat= "SELECT MAX(Gia_Sec) as Gia_Sec FROM guias WHERE Tia_Cod=$Par_Sql[0] ";
 		return $busca_ite_cat;
  		break;
  				   

  		case 1070:
  		$Sql_ta="INSERT INTO guias(Gia_Fec,Gia_Hor,Gia_Det,Gia_Obs,Gia_Num,Gia_Sec,Tia_Cod,Est_Int,Vnd_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]',
				'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',$Par_Sql[5],$Par_Sql[6],$Par_Sql[7],$Par_Sql[8])";
   		return $Sql_ta;
  		break;
  
  		case 1071:
   		$Sql_ta="INSERT INTO det_guia(Gia_Cod,Pro_Cod,Gia_Can,Gia_Pru,Gia_Imp) VALUES ($Par_Sql[0],$Par_Sql[1],
				$Par_Sql[2],$Par_Sql[3],$Par_Sql[4])";
  		return $Sql_ta;
  		break;
  		case 1072:
		$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15])";
		return $sql;
		break;
			
   		case 1073:
		$sql = "SELECT guias.Gia_Cod FROM guias,`det_guia` WHERE `guias`.`Gia_Cod`=det_guia.Gia_Cod AND `guias`.`Est_Int`=$Par_Sql[0] AND det_guia.`Pro_Cod`=$Par_Sql[1] AND `guias`.`Gia_Est`='A'";
		return $sql;
		break;
			
		case 1074: 
  		//*consulta por tipo de comprabente y fecha la transaccion */
  		$busca_ite_cat= "SELECT Gia_Cod,Gia_Sec,Gia_Est,Tia_Cod,Gia_Fec,Gia_Det,Gia_Obs,Prs_Ape,Prs_Nom,Prs_Ced FROM guias,persona,estudiante WHERE persona.Prs_Cod=estudiante.Prs_Cod AND guias.Est_Int=estudiante.Est_Int AND Tia_Cod=$Par_Sql[0] AND Gia_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'";
  				   		
		return $busca_ite_cat;
  		break;	
  		case 1075: 
  		/*consulta datos de l detalle del ajuste*/
  		$busca_ite_cat= "SELECT Gia_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Gia_Can,Gia_Pru,Gia_Imp  FROM det_guia,producto,item WHERE det_guia.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod AND Gia_Cod=$Par_Sql[0]";
  				   		
  		return $busca_ite_cat;
  		break;
  		
		case 1076: 
  		/*anulo los comprobantes de ajustes*/
  		$busca_ite_cat= "UPDATE guias SET  Gia_Est='I' WHERE Gia_Cod=$Par_Sql[0] ";
  		
  		return $busca_ite_cat;
  		break;
  
  		case 1077: 
  		/*anulo los comprobantes de ajustes*/
  		$busca_ite_cat= "UPDATE kardex_ie SET  Kar_Est='I' WHERE Gia_Cod=$Par_Sql[0] ";
 		 
  		return $busca_ite_cat;
  		break;
  		
		case 1078: 
  		/*consulta por tipo de comprabente y fecha la transaccion */
  		$busca_ite_cat= "SELECT Gia_Cod,Gia_Sec,Gia_Est,guias.Tia_Cod,Tia_Tra,Tia_Des,Gia_Fec,Gia_Det,Gia_Obs,Prs_Ape,Prs_Nom,Prs_Ced,Gia_Num FROM guias,persona,estudiante,tipo_ajus WHERE persona.Prs_Cod=estudiante.Prs_Cod AND guias.Est_Int=estudiante.Est_Int AND guias.Gia_Cod=$Par_Sql[0] AND tipo_ajus.Tia_Cod=guias.Tia_Cod";
  		return $busca_ite_cat;
  		break;	
					
		
		case 1079:
		/* Consulta  datos de estudiante*/
		$datos_est = "SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,estudiante.Est_Int FROM persona, estudiante WHERE estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'  
ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
  		return $datos_est;
  		break;
	                     
		case 1080:
		/* Consulta  datos de estudiante*/
		$datos_est = "SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,estudiante.Est_Int FROM persona, estudiante WHERE estudiante.Prs_Cod = persona.Prs_Cod AND estudiante.Est_Int=$Par_Sql[0]  
ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC"; 
		return $datos_est;
  		break;

		/* consultas para toma de inventario Nebil */
		case 1090:
			  $sql= "SELECT MAX(Tom_Sec) as Tom_Sec FROM toma_inven  ";
  		return $busca_ite_cat;
 		break;
		case 1091:
  			$Sql_ta="INSERT INTO toma_inven(Tom_Fec,Tom_Hor,Tom_Det,Tom_Obs,Tom_Sec)VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]',$Par_Sql[4])";
 		return $Sql_ta;
  		break;
		case 1092:
   			$Sql_ta="INSERT INTO det_tomain(Tom_Cod,Pro_Cod,Tom_Can) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2])";
    	return $Sql_ta;
  		break;
		case 1093: 
  		/*consulta por tipo de comprabente y fecha la transaccion */
 		 	$Sql_ta= "SELECT Tom_Fec,Tom_Hor,Tom_Fec,Tom_Obs,Tom_Det,Tom_Sec FROM `toma_inven` WHERE Tom_Cod=$Par_Sql[0] ";
		 return $Sql_ta;
 		 break;
  		case 1094: 
  	 		$Sql_ta= "SELECT producto.Pro_Cod,Tom_Can,Ite_Lar FROM item,producto,det_tomain WHERE Tom_Cod=$Par_Sql[0]  AND producto.Pro_Cod=det_tomain.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod ";
		 return $Sql_ta;
  		break;
		case 1095:
			$busca_ite_cat= "SELECT Tom_Cod,Tom_Est,Tom_Fec,Tom_Det,Tom_Obs FROM toma_inven WHERE Tom_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'";
		return $busca_ite_cat;
  		break;	
		case 1096: 
  			$busca_ite_cat= "SELECT Tom_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Tom_Can FROM det_tomain,producto,item WHERE det_tomain.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod AND Tom_Cod=$Par_Sql[0]";
  		return $busca_ite_cat;
		case 1097: 
   			$busca_ite_cat= "UPDATE toma_inven SET  Tom_Est='I' WHERE Tom_Cod=$Par_Sql[0] ";
  		return $busca_ite_cat;
  		break;
   		case 1098: 
    		$busca_ite_cat= " SELECT SUM(Tom_Can) AS Tom_Can FROM toma_inven,det_tomain WHERE det_tomain.Tom_Cod=toma_inven.Tom_Cod AND Tom_Est='A' AND Tom_Fec='$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1]";
  		return $busca_ite_cat;
  		break;
		
		//precios
		case 1099: 
    	$busca_ite_cat= " SELECT Tpv_Cod,Tpv_Des FROM tipo_preci";
  		return $busca_ite_cat;
  		break;
	
		case 1100: 
    	$busca_ite_cat= " INSERT  INTO  precios(Pro_Cod,Pre_Pvp,Tpv_Cod,Pre_Fec,Suc_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]',1)";
		echo $busca_ite_cat;
  		return $busca_ite_cat;
  		break;
		
		case 1201: 
    	$busca_ite_cat= " UPDATE precios SET Pre_Est='I' WHERE Pro_Cod=$Par_Sql[0] AND Tpv_Cod=$Par_Sql[1] ";
		echo $busca_ite_cat;
  		return $busca_ite_cat;
  		break;
		
		case 1202: 
    	$busca_ite_cat= " SELECT Tia_Cod,Tia_Des,Tia_Est,Tia_Tra  FROM tipo_ajus WHERE Tia_Ord='$Par_Sql[0]'";
		
  		return $busca_ite_cat;
  		break;
		
		case 1203: 
  			/*Consulta sentencia consulto stock del kardex  */
  			$busca_ite_cat= "SELECT (Kar_Can-Kar_Sal) AS Stock
     		FROM kardex_ie WHERE Pro_Cod=$Par_Sql[0]" ;
 		return $busca_ite_cat;
  		break;
		
		case 1204: 
  			/*Consulta sentencia consulto stock del kardex  */
  			$busca_ite_cat= "UPDATE stock SET Stk_Can=$Par_Sql[0] WHERE Pro_Cod=$Par_Sql[1] AND Suc_Cod=$Par_Sql[2]	" ;
			
 		return $busca_ite_cat;
  		break;
		case 1205: 
  			/*Consulta sentencia consulto stock del kardex  */
  			$busca_ite_cat= "INSERT INTO stock(Stk_Can,Loc_Cod,Pro_Cod)VALUE($Par_Sql[0],$Par_Sql[1],$Par_Sql[2]) " ;
		
 		return $busca_ite_cat;
  		break;


		case 1208: 
  			/*Consulta sentencia consulto stock del kardex  */
		$busca_ite_cat= "SELECT Pro_Cod	FROM ventas_det WHERE Vet_Cod=$Par_Sql[0] ";
			//echo $busca_ite_cat;
 		return $busca_ite_cat;
		break;

		case 1206: 
  			/*Consulta sentencia consulto stock del kardex  */
  			$busca_ite_cat= "SELECT (SUM(Kar_Can)-SUM(Kar_Sal)) AS Stock
     		FROM kardex_ie WHERE Pro_Cod=$Par_Sql[0] AND Kar_Est='A'";
			
 		return $busca_ite_cat;



		case 1207:
		/*Consulta de los rubros sin precio*/ 
  		$productos_28 = "SELECT precios.Tpv_Cod,item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Stk_Can,Pre_Pvp,Adq_Cor FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios,tipo_preci,stock WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod   AND  precios.Pro_Cod=producto.Pro_Cod AND precios.Tpv_Cod=tipo_preci.Tpv_Cod AND precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Cod=stock.Pro_Cod AND item.Ite_Lar  LIKE '%$Par_Sql[0]%' AND 
					producto.Pro_Cod NOT IN (SELECT  deudas.Pro_Cod FROM  deudas, notasgener WHERE deudas.Nge_Cod = 
					notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] AND notasgener.Sem_Cod = $Par_Sql[2])";
					//echo $productos_28;
		return $productos_28;
		break;

		case 2200: 
  			/*Consulta sentencia consulto stock del kardex  */
		$busca_ite_cat= "SELECT Pro_Cod	FROM det_guia WHERE Gia_Cod=$Par_Sql[0] ";
			//echo $busca_ite_cat;
 		return $busca_ite_cat;
		break;
		
		case  2201: 
  			/*Consulta sentencia consulto stock del kardex  */
  			$busca_ite_cat= "UPDATE stock SET Stk_Can=$Par_Sql[0] WHERE Pro_Cod=$Par_Sql[1] AND Suc_Cod=$Par_Sql[2]	" ;
			//echo   $busca_ite_cat;
 		return $busca_ite_cat;
  		break;
	
		/*Consulta de semestres dependiendo del periodo*/
		case 2202:
		$consulta_periodos="SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Per_Int,
 		 view_cursos_mal.Car_Int, carreras.Car_Nom FROM view_cursos_mal INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int) WHERE view_cursos_mal.Per_Int = $Par_Sql[0] AND  view_cursos_mal.Sem_Est = 'A' AND Car_Vis = '$Par_Sql[1]'
		ORDER BY view_cursos_mal.Car_Int, view_cursos_mal.Niv_Cod";		
	//echo $consulta_periodos;
		return $consulta_periodos;
		
		break;

		/**
		* Consulta de semestres dependiendo del periodo
		*/
		case 2202:
		$consulta_periodos="SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Per_Int,
 		 view_cursos_mal.Car_Int, carreras.Car_Nom FROM view_cursos_mal INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int) WHERE view_cursos_mal.Per_Int = $Par_Sql[0] AND  view_cursos_mal.Sem_Est = 'A' AND Car_Vis = '$Par_Sql[1]'
		ORDER BY view_cursos_mal.Car_Int, view_cursos_mal.Niv_Cod";		
	//echo $consulta_periodos;
		return $consulta_periodos;
		break;

//carga deuda dependiendo del cliente
case 2203:
$deu_indi_647="SELECT deudas.Deu_Val, deudas.Pro_Cod, deudas.Asi_Int, deudas.Nge_Cod, Bec_Cod FROM deudas, 
producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras
WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND
deudas.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod 
AND semestres.Per_Int = periodos.Per_Int AND semestres.Niv_Cod = niveles.Niv_Cod 
AND periodos.Mod_Cod = modalidad.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
carreras.Car_Int = promocione.Car_Int AND deudas.Cli_Cod = '$Par_Sql[0]'  AND deudas.Pro_Cod='$Par_Sql[1]' AND deudas.Nge_Cod='$Par_Sql[2]' AND deudas.Asi_Int=$Par_Sql[3] AND 
deudas.Deu_Fec <= '$Par_Sql[4]' AND Deu_Rec = 0";
//echo $deu_indi_647;
return $deu_indi_647;
break;

		/**
		* Consulta de estudiantes matriculados en pregrado
		*/
		case 2204:
		$sql="SELECT DISTINCT 
  persona.Prs_Cod,
  persona.Prs_Ced,
  persona.Prs_Nom,
  persona.Prs_Ape,
  estudiante.Est_Int
FROM
  persona
  INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
  INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
  INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int)
  INNER JOIN deudas ON (notasgener.Nge_Cod = deudas.Nge_Cod),
  periodos
WHERE
  matriculas.Mat_Est = 'A'
ORDER BY
  Prs_Ape, Prs_Nom";		
		return $sql;
		break;

		/**
		* Consulta la carrera y modalidad del estudiante
		*/
		case 2205:
		$sql="SELECT DISTINCT 
  carreras.Car_Nom,
  modalidad.Mod_Des,
  concat(niveles.Niv_Des, '-', semestres.Sem_Par) AS curso, concat(MONTH(periodos.Per_Fea),'/',YEAR(periodos.Per_Fea),' - ', MONTH(periodos.Per_Fef),'/',YEAR(periodos.Per_Fef)) as periodo
FROM
  matriculas
  INNER JOIN semestres ON (matriculas.Sem_Cod = semestres.Sem_Cod)
  INNER JOIN promocione ON (promocione.Pro_Cod = semestres.Pro_Cod)
  INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
  INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
  INNER JOIN carreras ON (promocione.Car_Int = carreras.Car_Int)
  INNER JOIN niveles ON (semestres.Niv_Cod = niveles.Niv_Cod)
WHERE
  matriculas.Est_Int = $Par_Sql[0] AND carreras.Car_Vis = 'N'";		
		return $sql;
		break;

/**
* carga deuda dependiendo del cliente
*/
case 2206:
$sql="SELECT 
  deudas.Deu_Val,
  deudas.Pro_Cod,
  deudas.Nge_Cod,
  deudas.Bec_Cod,
  deudas.Cli_Cod,
  deudas.Asi_Int
FROM
  cliente
  INNER JOIN deudas ON (cliente.Cli_Cod = deudas.Cli_Cod)
  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
  INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
  INNER JOIN notasgener ON (deudas.Nge_Cod = notasgener.Nge_Cod)
  INNER JOIN matriculas ON (notasgener.Mat_Int = matriculas.Mat_Int)
  INNER JOIN semestres ON (matriculas.Sem_Cod = semestres.Sem_Cod)
  INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
  INNER JOIN carreras ON (promocione.Car_Int = carreras.Car_Int)
WHERE estudiante.Est_Int = $Par_Sql[0] AND deudas.Deu_Fec <= '$Par_Sql[1]' AND carreras.Car_Vis='N' AND deudas.Deu_Val > 0 AND Deu_Rec =0 ";
return $sql;
break;		

//carga deuda dependiendo del cliente
case 2207:
$sql="SELECT deudas.Deu_Val, deudas.Pro_Cod, deudas.Asi_Int, deudas.Nge_Cod, Bec_Cod FROM deudas, 
producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras
WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND
deudas.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod 
AND semestres.Per_Int = periodos.Per_Int AND semestres.Niv_Cod = niveles.Niv_Cod 
AND periodos.Mod_Cod = modalidad.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND 
carreras.Car_Int = promocione.Car_Int AND deudas.Cli_Cod = '$Par_Sql[0]'  AND deudas.Pro_Cod='$Par_Sql[1]' AND deudas.Nge_Cod='$Par_Sql[2]' AND deudas.Asi_Int=$Par_Sql[3] AND Deu_Rec = 0";
return $sql;
break;

//carga deuda dependiendo del cliente
case 2208:
$sql="SELECT 
  deudas.Deu_Val,
  deudas.Pro_Cod,
  deudas.Asi_Int,
  deudas.Nge_Cod,
  deudas.Bec_Cod,
  item.Ite_Cor, MONTH(periodos.Per_Fea) AS mes_ini, YEAR(periodos.Per_Fef) AS ann_ini
FROM
  deudas,
  item,
  producto,
  notasgener,
  semestres,
  periodos, promocione, carreras
WHERE
  deudas.Pro_Cod = producto.Pro_Cod AND 
  producto.Ite_Cod = item.Ite_Cod AND 
  deudas.Nge_Cod = notasgener.Nge_Cod AND 
  notasgener.Sem_Cod = semestres.Sem_Cod AND 
  promocione.Pro_Cod = semestres.Pro_Cod AND 
  promocione.Car_Int = carreras.Car_Int AND
  semestres.Per_Int = periodos.Per_Int AND deudas.Cli_Cod = '$Par_Sql[0]'  AND carreras.Car_Vis='N'
 AND Deu_Rec = 0";
//echo $sql;
return $sql;
break;

		/* Consulta los pagos realizados por el cliente */
		case 2209:
		$pagos_68= "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det, caja_aper WHERE ventas.Vet_Cod = 
				ventas_det.Vet_Cod AND ventas.Caj_Cod = caja_aper.Caj_Cod AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
				= $Par_Sql[2] AND Asi_Int = $Par_Sql[3] AND ventas.Vet_Est = 'A' AND caja_aper.Caj_Fec <= '$Par_Sql[4]'";
				//echo $pagos_68;
		return $pagos_68;
		break;

	}
}

?>