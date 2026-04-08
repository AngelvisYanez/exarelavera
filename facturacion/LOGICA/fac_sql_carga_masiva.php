<?php
/**
* Retenciones
*/
function sentencias_ret($id,$Par_Sql)
{
	switch($id)
	{		
		
		/* 
		* Registra la persona
		*/
		case 1:
		$sql= "INSERT INTO persona (Prs_Ced,Prs_Nom,Prs_Ape,Prs_Sex,Ciu_Cod,Ide_Cod)VALUES (Trim('$Par_Sql[0]'),Trim(UPPER('$Par_Sql[1]')),Trim(UPPER('$Par_Sql[2]')),Trim('$Par_Sql[3]'),Trim('$Par_Sql[4]'),Trim('$Par_Sql[5]'))";
		//echo "<br>".$sql;
		return $sql;
		break;
			
		/**
		*consulta persona 
		*/
		case 2:
		$carg_retenc="SELECT Prs_Cod,Prs_Ced FROM persona WHERE Prs_Ced='$Par_Sql[0]' or Prs_Ced='$Par_Sql[1]'"; 			
		//echo $carg_retenc;
		return $carg_retenc;
		break;				
		
		/* 
		* Registra la proveedores
		*/
		case 3:
		$sql= "INSERT INTO proveedore(Prs_Cod,Emp_Cod,Prv_Tic,Prv_Con,Prv_Esp,Prv_Com)VALUES('$Par_Sql[0]','$Par_Sql[1]',Trim(UPPER('$Par_Sql[2]')),Trim(UPPER('$Par_Sql[3]')),Trim(UPPER('$Par_Sql[4]')),Trim(UPPER('$Par_Sql[5]')))";		
		//echo "<br>".$sql;
		return $sql;
		break;
		
		/* 
		* Registra cliente
		*/
		case 4:
		$sql= "INSERT INTO cliente(Prs_Cod,Emp_Cod,Cli_Tic,Cli_Con,Cli_Tip)VALUES('$Par_Sql[0]','$Par_Sql[1]',Trim(UPPER('$Par_Sql[2]')),Trim(UPPER('$Par_Sql[3]')),Trim(UPPER('$Par_Sql[4]')))";		
		//echo "<br>".$sql;
		return $sql;
		break;
		/* 
		* Anulamos
		*/
		case 5:
		$sql= "SELECT Cli_Cod,Prs_Cod FROM cliente WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";		
		//echo $sql;
		return $sql;
		break;
		
                /* 
		* consulta d proveedor
		*/
		case 6:
		$sql= "SELECT Prv_Cod,Prs_Cod FROM proveedore WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";		
		//echo $sql;
		return $sql;
		break;
	}
}
?>