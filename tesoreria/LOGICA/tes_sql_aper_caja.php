<?php
	/* Facturación apertura y cierre de caja */
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{	
			case 4: 
			/* Consulta de las cajas selecionada */
			$consulta_caja = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Est AS Caj_Est2, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est = 'A', 'C a j a - A b i e r t a', 'Caja Cerrada') as Caj_Est, caja_aper.Caj_Fef FROM caja_aper WHERE caja_aper.Caj_Cod = $Par_Sql[0]";
			//echo $consulta_caja;
			return $consulta_caja;//AND caja_aper.Caj_Est = 'A'
			break;
		
			case 5: 
			/* Consulta de las cajas activas */
			$consulta_caja = "SELECT caja_aper.Caj_Cod, caja_aper.Caj_Fec, caja_aper.Caj_Hoi, caja_aper.Caj_Hof, caja_aper.Caj_Est AS Caj_Est2, caja_aper.Caj_Obs, caja_aper.Pun_Cod, IF (caja_aper.Caj_Est = 'A', 'C a j a - A b i e r t a', 'Caja Cerrada') as Caj_Est, puntos_imp.Pun_Des, caja_aper.Caj_Fef FROM caja_aper, puntos_imp WHERE caja_aper.Pun_Cod = puntos_imp.Pun_Cod  AND caja_aper.Pun_Cod = '$Par_Sql[0]' ORDER BY  caja_aper.Caj_Est, caja_aper.Caj_Fec DESC LIMIT 0,10  ";
			return $consulta_caja;//AND caja_aper.Caj_Est = 'A'
			break;
			
			case 6:
			/* Apertura de la caja diaria */
			$abrir_caja = "INSERT INTO caja_aper (Caj_Fec, Caj_Hoi, Pun_Cod) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', $Par_Sql[2])";
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


		}
	}
?>