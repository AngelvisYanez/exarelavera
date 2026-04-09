<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_rhu($id,$Par_Sql)
	{
		switch($id)
		{	
			  /* Consulta de ciudades para la inscripción */
			  case 609:
			  $consulta_rela_609="SELECT Reb_Cod, Reb_Des FROM relaci_lab WHERE Reb_Est = 'A'";		
			  return $consulta_rela_609;
			  break;
			  
			  /* Consulta de ciudades para la inscripción */
			  case 610:
			  $consulta_dedi_610="SELECT Ded_Cod, Ded_Des,Ded_Hrs FROM dedica_lab WHERE Ded_Est='A'";
			  return $consulta_dedi_610;
			  break;
			  
			  case 644:
			  /* Busqueda del cargo que desempeña un personal de la UTSAM
			  Ejemplo: 
			  CASO I:								CASO II:
				Ing. Lucas Garces					   Ing. Lewis Chimarro
				  Director Escuela Informatica		      Jefe de Sistemas
				  Docente Informatica
			  */								
			  $cargar_car_personal="select tiposcargo.Tic_Cod,tiposcargo.Tic_Des, Dis_Cod from tiposcargo, distributi, personal where tiposcargo.Tic_Cod=distributi.Tic_Cod and distributi.Per_Cod=personal.Per_Cod and personal.Per_Cod='$Par_Sql[0]'";
			  //echo $cargar_car_personal;
			  return $cargar_car_personal;
			  break; 
		
			  case 645:
			  /* Busqueda de usuarios*/								
			  $cargar_usuarios="SELECT personal.Prs_Cod,personal.Per_Cod, persona.Prs_Ced, personal.Per_Tit, persona.Prs_Ape, persona.Prs_Nom FROM personal, persona WHERE personal.Prs_Cod=persona.Prs_Cod and personal.Per_Cod='$Par_Sql[0]' AND personal.Emp_Cod='$Par_Sql[1]'";
			  //echo $cargar_usuarios;
			  return $cargar_usuarios;
			  break;
			  
			  // busqueda del personal por el apellido
			  case 646:
			  /* Busqueda de usuarios*/		
		      $consultar_docentes="SELECT personal.Per_Cod, persona.Prs_Ced, personal.Per_Tit, persona.Prs_Ape, persona.Prs_Nom FROM personal, persona WHERE 
				personal.Prs_Cod = persona.Prs_Cod AND personal.Per_Est = 'A' AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND personal.Emp_Cod='$Par_Sql[1]' ORDER BY personal.Per_Tit, persona.Prs_Ape, persona.Prs_Nom";
			  //echo $consultar_docentes;
			  return $consultar_docentes;
			  break; 
			  
			  // busqueda del personal por la Cedula de Identidad
			  case 647:
			  /* Busqueda de usuarios*/								
			  $cargar_usuarios_by_ced="SELECT personal.Per_Cod, persona.Prs_Ced, personal.Per_Tit, persona.Prs_Ape, persona.Prs_Nom FROM personal, persona WHERE personal.Prs_Cod = persona.Prs_Cod AND personal.Per_Est = 'A' AND Prs_Ced = '$Par_Sql[0]' AND personal.Emp_Cod='$Par_Sql[1]' ORDER BY personal.Per_Tit, persona.Prs_Ape, persona.Prs_Nom";
			  //echo $cargar_usuarios_by_ced;
			  return $cargar_usuarios_by_ced;
			  break; 
	
			  /*busqueda de area departamentales*/
			  case 648:								
			  $cargar_areas="SELECT Are_Cod, Are_Des FROM areas_rrhh WHERE areas_rrhh.Are_Est = 'A'";
			  //echo $cargar_areas;
			  return $cargar_areas;
			  break; 
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 649:
			  $cargar_dep="SELECT Dep_Cod,Dep_Des FROM departamen WHERE Are_Cod=$Par_Sql[0] AND departamen.Dep_Est='A'";
			  //echo $cargar_dep;
			  return $cargar_dep;
			  break; 
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 650:
			  $cargar_car="SELECT Tic_Cod,Tic_Des FROM tiposcargo WHERE Dep_Cod=$Par_Sql[0]";
			  //echo $cargar_car;
			  return $cargar_car;
			  break; 
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 652:
			  $grabar_car="INSERT INTO contratos_lab(Tic_Cod,Ded_Cod,Reb_Cod,Con_Ini,Con_Fin,Per_Cod)  VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]',$Par_Sql[5])";
			  //echo $grabar_car;
			  return $grabar_car;
			  break;
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 653:
			  $grabar_sueld="INSERT INTO sueldos(Sue_Va1,Sue_Est,Sue_Fec,Con_Cod)VALUES($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]') ";
			  //echo "<br>".$grabar_sueld;
			  return $grabar_sueld;
			  break;
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 654:
			  $sql="SELECT contratos_lab.Con_Cod, contratos_lab.Per_Cod, contratos_lab.Con_Ini, contratos_lab.Con_Fin,contratos_lab.Tic_Cod,sueldos.Sue_Va1, tiposcargo.Tic_Des, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
			  FROM  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN contratos_lab ON (personal.Per_Cod = contratos_lab.Per_Cod)
				  INNER JOIN tiposcargo ON (contratos_lab.Tic_Cod = tiposcargo.Tic_Cod)
				  INNER JOIN sueldos ON (contratos_lab.Con_Cod = sueldos.Con_Cod)
			  WHERE persona.Prs_Ape like '%$Par_Sql[0]%' ";
			  //echo $sql."<br>";			
			  return $sql;
			  break; 
			  
			  /*busqueda de departamentos segun el area escogida*/
			  case 655:
			  $sql="SELECT contratos_lab.Con_Cod, contratos_lab.Per_Cod, contratos_lab.Con_Ini, contratos_lab.Con_Fin,contratos_lab.Tic_Cod,sueldos.Sue_Va1, tiposcargo.Tic_Des, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
			  FROM  persona
				  INNER JOIN personal ON (persona.Prs_Cod = personal.Prs_Cod)
				  INNER JOIN contratos_lab ON (personal.Per_Cod = contratos_lab.Per_Cod)
				  INNER JOIN tiposcargo ON (contratos_lab.Tic_Cod = tiposcargo.Tic_Cod)
				  INNER JOIN sueldos ON (contratos_lab.Con_Cod = sueldos.Con_Cod)
			  WHERE persona.Prs_Ced=$Par_Sql[0]";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  /*busqueda de datos dl contrato*/
			  case 656:
			  $sql="SELECT 
					  contratos_lab.Con_Cod,contratos_lab.Per_Cod,contratos_lab.Con_Ini,
					  contratos_lab.Con_Fin,contratos_lab.Tic_Cod,sueldos.Sue_Va1,
					  tiposcargo.Tic_Des,departamen.Dep_Cod,departamen.Are_Cod,
					  contratos_lab.Reb_Cod,contratos_lab.Ded_Cod
					FROM
					  contratos_lab
					  INNER JOIN tiposcargo ON (contratos_lab.Tic_Cod = tiposcargo.Tic_Cod)
					  INNER JOIN sueldos ON (contratos_lab.Con_Cod = sueldos.Con_Cod)
					  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod) 
					WHERE contratos_lab.Per_Cod= $Par_Sql[0]";			  			  
			  //echo $sql;
			  return $sql;	
			  break;
			  		  
			  /*busqueda de cargos por empleado*/
			  case 657:
			  $cargar_empleadocargo="SELECT contratos_lab.Con_Cod, tiposcargo.Dep_Cod from contratos_lab, tiposcargo WHERE contratos_lab.Per_Cod= $Par_Sql[0] 
			  AND contratos_lab.Tic_Cod= tiposcargo.Tic_Cod AND tiposcargo.Dep_Cod= $Par_Sql[1] AND contratos_lab.Con_Est=A";
			  //echo $cargar_empleadocargo;
			  return $cargar_empleadocargo;
			  break; 
			  
			  /*busqueda del tipo de cargo segun el distributivo*/
			  case 658:
			  $cargar_mod_distri="SELECT tiposcargo.Tic_Cod, tiposcargo.Tic_Des, tiposcargo.Dep_Cod, departamen.Are_Cod FROM tiposcargo, departamen WHERE tiposcargo.Tic_Cod= $Par_Sql[0] AND tiposcargo.Dep_Cod= departamen.Dep_Cod";
			  //echo $cargar_mod_distri;
			  return $cargar_mod_distri;
			  break; 
			  
			  /*busqueda de departamentos */
			  case 659:
			  $cargar_departamentos="SELECT Dep_Cod, Dep_Des FROM departamen";
			  //echo $cargar_departamentos;
			  return $cargar_departamentos;
			  break; 
			
			  /*busqueda de tipos de cargos */
			  case 660:
			  $cargar_tipcargo="SELECT Tic_Cod, Tic_Des FROM tiposcargo";
			  //echo $cargar_tipcargo;
			  return $cargar_tipcargo;
			  break; 
			 		
			  /*Update de distributivo */
			  case 661:
			  $update_distri="UPDATE contratos_lab SET  Tic_Cod=$Par_Sql[0] , Ded_Cod=$Par_Sql[1] , Reb_Cod=$Par_Sql[2], Con_Ini='$Par_Sql[3]', Con_Fin='$Par_Sql[4]' WHERE Con_Cod = $Par_Sql[5]";
			  echo $update_distri;
			  return $update_distri;
			  break;
			  
			  /*Update de sueldo*/
			  case 662:
			  $update_sueldo="UPDATE sueldos SET Sue_Va1=$Par_Sql[0] WHERE Con_Cod= $Par_Sql[1]";
			  echo $update_sueldo;
			  return $update_sueldo;
			  break; 
			}
}
?>