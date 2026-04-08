<?php
	/**
	* Ajustes de inventario
	*/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{	
		  case 1: 
		  /*Consulta (Ventas/Notas Credito) segun Tic_Cod  */
		  $sql= "SELECT 
				  ventas.Vet_Cod,
				  ventas.Vet_Xml,
				  ventas.Vet_Sri,
				  ventas.Vet_Aut,
				  ventas.Vet_Num,					 				  
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  ventas					 					  
				  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
				  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  ventas.Vet_Num = '$Par_Sql[0]' AND 
				  sucursal.Suc_Cod = '$Par_Sql[1]' AND
				  tipo_compr.Tic_Sri='$Par_Sql[2]' AND 
				  persona.Prs_Cod='$Par_Sql[3]' AND
				  ventas.Vet_Est = 'A'";
		  //echo "1: ".$sql."<br>";
		  return $sql;
		  break;
		  
		  case 2:
		  /** 
		  * Consulta del usuario
		  */
		  $sql = "SELECT Prs_Ape, Prs_Ced, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
		  return $sql;
		  break;
		  
		  case 3: 
		  /*Consulta informacion total de venta por fechas */
		  $sql= "SELECT 
				  ventas.Vet_Cod,
				  ventas.Vet_Xml,
				  ventas.Vet_Sri,
				  ventas.Vet_Aut,
				  ventas.Vet_Num,					 				  
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  ventas					 					  
				  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
				  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  (caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND				  
				  sucursal.Suc_Cod = '$Par_Sql[2]' AND
				  tipo_compr.Tic_Sri='$Par_Sql[3]' AND 
				  persona.Prs_Cod='$Par_Sql[4]' AND
				  ventas.Vet_Est = 'A'"; 
		  //echo $sql."<br>";
		  return $sql;
		  break;
		  
		  case 4: 
		  /*Consulta Retenciones numero documento*/
		  $sql= "SELECT 
				  retencion.Ret_Cod,
				  retencion.Ret_Xml,
				  retencion.Ret_Sri,
				  retencion.Ret_Aut,
				  retencion.Ret_Num,					 				  
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  retencion					 					  
				  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  retencion.Ret_Num = '$Par_Sql[0]' AND 				  
				  tipo_compr.Tic_Sri='$Par_Sql[1]' AND 
				  sucursal.Suc_Cod = '$Par_Sql[2]' AND
				  proveedore.Prs_Cod='$Par_Sql[3]' AND 
				  retencion.Ret_Est = 'A'";
		  //echo "4: ".$sql."<br>";
		  return $sql;
		  break;
		  
		  case 5: 
		  /*Consulta Retenciones rango fechas */
		  $sql= "SELECT 
				  retencion.Ret_Cod,
				  retencion.Ret_Xml,
				  retencion.Ret_Sri,
				  retencion.Ret_Aut,
				  retencion.Ret_Num,					 				  
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  retencion					 					  
				  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  (retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND 				  
				  tipo_compr.Tic_Sri='$Par_Sql[2]' AND 
				  sucursal.Suc_Cod = '$Par_Sql[3]' AND
				  proveedore.Prs_Cod='$Par_Sql[4]' AND 
				  retencion.Ret_Est = 'A'";
		  //echo "5: ".$sql."<br>";
		  return $sql;
		  break;
		  
		  case 6: 
		  /*Consulta (Ventas/Notas Credito) segun Tic_Cod  */
		  $sql= "SELECT 
				  ventas.Vet_Cod,
				  ventas.Vet_Xml,
				  ventas.Vet_Sri,
				  ventas.Vet_Aut,
				  ventas.Vet_Num,					 				  
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  ventas					 					  
				  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
				  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
				  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  ventas.Vet_Num = '$Par_Sql[0]' AND 
				  sucursal.Suc_Cod = '$Par_Sql[1]' AND
				  tipo_compr.Tic_Sri='$Par_Sql[2]' AND 
				  persona.Ide_Cod='$Par_Sql[3]' AND
				  ventas.Vet_Est = 'A'";
		  //echo "1: ".$sql."<br>";
		  return $sql;
		  break;
		  
		  case 7: 
		  /*Consulta (Guias de Remision) */
		  $sql= "SELECT 
				  guias_remi.Gui_Cod,
				  guias_remi.Gui_Xml,
				  guias_remi.Gui_Sri,
				  guias_remi.Gui_Aut,
				  guias_remi.Gui_Num,
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  autorizaci
				  INNER JOIN guias_remi ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				  INNER JOIN guia_destin ON (guias_remi.Des_Cod = guia_destin.Des_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  guias_remi.Gui_Num='$Par_Sql[0]' AND 
				  tipo_compr.Tic_Sri='$Par_Sql[1]' AND 				  				  
				  sucursal.Suc_Cod = '$Par_Sql[2]' AND
				  guia_destin.Prs_Cod='$Par_Sql[3]' AND
				  guias_remi.Gui_Est = 'A'";
		  //echo "1: ".$sql."<br>";
		  return $sql;
		  break;
		  
		  case 8: 
		  /*Consulta Guias de Remision  */
		  $sql= "SELECT 
				  guias_remi.Gui_Cod,
				  guias_remi.Gui_Xml,
				  guias_remi.Gui_Sri,
				  guias_remi.Gui_Aut,
				  guias_remi.Gui_Num,
				  sucursal.Suc_Sri,
				  tipo_compr.Tic_Des,
				  tipo_compr.Tic_Cod,
				  tipo_compr.Tic_Sri,
				  autorizaci.Pun_Sri
				FROM
				  autorizaci
				  INNER JOIN guias_remi ON (autorizaci.Aut_Cod = guias_remi.Aut_Cod)
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				  INNER JOIN guia_destin ON (guias_remi.Des_Cod = guia_destin.Des_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  (guias_remi.Gui_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND 
				  tipo_compr.Tic_Sri='$Par_Sql[2]' AND 				  				  
				  sucursal.Suc_Cod = '$Par_Sql[3]' AND
				  guia_destin.Prs_Cod='$Par_Sql[4]' AND
				  guias_remi.Gui_Est = 'A'";
		  //echo "2: ".$sql."<br>";
		  return $sql;
		  break;
		
		}
	}
?>