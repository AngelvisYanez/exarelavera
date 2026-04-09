<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_rhu($id,$Par_Sql)
	{
		switch($id)
		{	
			  /* Consulta relacion laboral */
			  case 1:
			  $consulta_rela_609="SELECT Reb_Cod, Reb_Des FROM relaci_lab WHERE Reb_Est = 'A'";		
			  return $consulta_rela_609;
			  break;
			  
			  /* Consulta dedicacion laboral */
			  case 2:
			  $sql="SELECT Ded_Cod, Ded_Des,concat(Ded_Hrs,' Hrs')as Ded_Hrs FROM dedica_lab WHERE Ded_Est='A'";
			  return $sql;
			  break;
			  
			  /*insertamos relaci_lab*/
			  case 3:
			  $sql="INSERT INTO relaci_lab(Reb_Des)VALUES($Par_Sql[0])";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  /*insertamos dedica_lab*/
			  case 4:
			  $grabar_car="INSERT INTO dedica_lab(Ded_Des,Ded_Hrs)VALUES($Par_Sql[0],$Par_Sql[1])";
			  //echo $grabar_car;
			  return $grabar_car;
			  break;
			  
			  /*Update de contratos_lab */
			  case 5:
			  $sql="UPDATE relaci_lab SET  Reb_Des='$Par_Sql[0]' WHERE Reb_Cod = '$Par_Sql[1]'";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  /*Update de dedica_lab */
			  case 6:
			  $sql="UPDATE dedica_lab SET  Ded_Des='$Par_Sql[0]',Ded_Hrs='$Par_Sql[1]' WHERE Ded_Cod = '$Par_Sql[2]'";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  /*damos de baja dedica_lab */
			  case 7:
			  $sql="UPDATE relaci_lab SET  Reb_Est='$Par_Sql[0]' WHERE Reb_Cod = '$Par_Sql[1]'";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  /*damos de baja dedica_lab */
			  case 8:
			  $sql="UPDATE dedica_lab SET Ded_Est='$Par_Sql[0]' WHERE Ded_Cod = '$Par_Sql[1]'";
			  //echo $sql;
			  return $sql;
			  break;
			  
			  
			}
}
?>