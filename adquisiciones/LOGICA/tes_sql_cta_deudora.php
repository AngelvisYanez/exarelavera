<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author juanpuxito
 * @version 1.0
 * Fecha de actualización:	27-05-2014
 *
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package Exa.Facturacion - OFSERCONT
 */
function sentencias_tip($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otro tipo de asiento
		*/
		$con_mar=  "SELECT Pla_Cod,Pla_Obs,Pla_Fec FROM plan_cuenta WHERE Emp_Cod = $Par_Sql[0]";	
		//echo $con_mar;
		return $con_mar;
		break;

		/* 
		* Registra la cuenta en la tabla ccpp_prove 
		*/		
		case 2:
		$sql= "INSERT INTO ccpp_prove(Pld_Cod,Ccp_Def) VALUES (Trim('$Par_Sql[0]'),Trim('$Par_Sql[1]'))";						
		return $sql;
		break;
		/* 
		* Busqueda del plan de cuentas por descripcion
		*/
		case 3:
		$sql= "SELECT Pla_Cod,Pld_Cod,Pld_Des,Pld_Cdc,if(Pld_Tip='D','Detalle','Grupo')as Pld_Tip FROM det_plan WHERE Pld_Tip='D' AND Pla_Cod='$Par_Sql[0]' AND Pld_Des LIKE '%$Par_Sql[1]%' ORDER BY Pld_Des ASC";	
		//echo $sql;
		return $sql;

		/* 
		* Carga datos de los tipos de comprobantes 
		*/		
		case 4:
		$sql= "SELECT Pla_Cod,Pld_Cod,Pld_Des,Pld_Cdc,if(Pld_Tip='D','Detalle','Grupo')as Pld_Tip FROM det_plan WHERE Pld_Tip='D' AND Pla_Cod='$Par_Sql[0]' AND Pld_Cdc=Trim('$Par_Sql[1]') ORDER BY Pld_Des ASC";		
		return $sql;
		break;

		case 5: 
		/* 
		* consulta en la cuenta banco 
		*/
		$sql=  "SELECT Ban_Cod, Pld_Cod FROM banco WHERE Pld_Cod = '$Par_Sql[0]' AND Ban_Est ='A'";	
		//echo $sql;
		return $sql;
		break;

		/* 
		* Actualiza los datos del ccpp_prove 
		*/		
		case 6:
		$actualiza_tipo= "UPDATE ccpp_prove SET Ccp_Def = Trim(UPPER('$Par_Sql[0]')),Pld_Cod = Trim(UPPER('$Par_Sql[1]')) WHERE Pld_Cod = $Par_Sql[1]";
	    //echo $actualiza_tipo;
		return $actualiza_tipo;
		break;
		
		case 7:
		$sql= "SELECT 
				  ccpp_prove.Ccp_Def,
				  ccpp_prove.Ccp_Cxp,
				  plan_cuenta.Pla_Cod,
				  det_plan.Pld_Cod,
				  det_plan.Pld_Des,
				  if(det_plan.Pld_Tip='D','Detalle',' ')as Pld_Tip				 
			   FROM
				  det_plan
				  INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod)
				  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
			   WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] AND det_plan.Pld_Des LIKE '%$Par_Sql[1]%' ORDER BY det_plan.Pld_Des ASC";	
		//echo $sql;
		return $sql;
		break;
		
		case 8:
		$elimina_tip= "UPDATE tipo_asien SET Tia_Est = Trim(UPPER('$Par_Sql[0]')) WHERE Tia_Cod = $Par_Sql[1]";	
		//echo $cons_tip;
		return $elimina_tip;
		break;
		
		case 9:
		$sql= "SELECT 
				  ccpp_prove.Ccp_Def,
				  ccpp_prove.Ccp_Cxp,
				  det_plan.Pld_Cod,
				  det_plan.Pld_Des,
				  if(det_plan.Pld_Tip='D','Detalle',' ')as Pld_Tip,
				  plan_cuenta.Pla_Obs
				FROM
				  det_plan
				  INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod)
				  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
				WHERE det_plan.Pld_Cod= '$Par_Sql[0]'";	
		//echo $sql;
		return $sql;
		break;
		
		case 10:
		$sql= "SELECT 
				  concat(forma_pago.For_Des, '-', tipos_pago.Pag_Des) AS tipo,
				  tipos_pago.Pag_Cod,
				  forma_pago.For_Cod
				FROM
				  forma_pago
				  INNER JOIN tipos_pago ON (forma_pago.For_Cod = tipos_pago.For_Cod)
				WHERE
				  forma_pago.For_Est = 'A' AND 
				  tipos_pago.Pag_Est = 'A'
				ORDER BY Pag_Cod";	
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Registra el tipo de pago
		*/		
		case 11:
		$sql= "INSERT INTO pago_plan(Pag_Cod,Ban_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";						
		//echo $sql."<br>";
		return $sql;
		break;
		
		case 12: 
		/* 
		* consulta pago plan activos
		*/
		$sql=  "SELECT Pag_Cod, Ban_Cod FROM pago_plan WHERE Pag_Cod = '$Par_Sql[0]' AND Ban_Cod = '$Par_Sql[1]' AND Pag_Est='A'";	
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* consulta las cuentas que existen en ccpp_prove
		*/
		case 13: 		
		$sql=  "SELECT 
				  ccpp_prove.Pld_Cod,
				  ccpp_prove.Ccp_Def,
				  plan_cuenta.Pla_Cod
				FROM
				  plan_cuenta
				  INNER JOIN det_plan ON (plan_cuenta.Pla_Cod = det_plan.Pla_Cod)
				  INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod)
				WHERE 
				  plan_cuenta.Pla_Cod = '$Par_Sql[0]'";	
		//echo $sql."<br>";
		return $sql;
		break;
		
		/* 
		* modificamos los datos en ccpp_prove ponemos en Ccp_Def=""
		*/
		case 14:
		$sql= "UPDATE ccpp_prove SET Ccp_Def = '$Par_Sql[0]' WHERE Pld_Cod='$Par_Sql[1]'";	
		//echo $sql."<br>";
		return $sql;
		break;
		
		/* 
		* modificamos los datos en ccpp_prove
		*/
		case 15:
		$sql= "UPDATE ccpp_prove SET Ccp_Def = '$Par_Sql[0]',Pld_Cod='$Par_Sql[1]' WHERE Pld_Cod='$Par_Sql[2]'";	
		//echo $sql."<br>";
		return $sql;
		break;
		
	}
}
?>