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
		$con_mar=  "SELECT Mar_Cod, Mar_Des FROM marca WHERE Mar_Des = '$Par_Sql[0]' AND Emp_Cod = $Par_Sql[1]";	
		//echo $con_mar;
		return $con_mar;
		break;

		/* 
		* Registra la marca
		*/		
		case 2:
		$registra_tip= "INSERT INTO tipo_asien (Tia_Des, Tia_Ini, Tia_Abr) VALUES (Trim(UPPER('$Par_Sql[0]')),
		Trim(UPPER('$Par_Sql[1]')), Trim(UPPER('$Par_Sql[2]')))";		
		//echo $registra_tip;
		return $registra_tip;
		break;
		/* 
		* Busqueda de los tipos de comprobantes 
		*/
		case 3:
		$busca_tipos= "SELECT tip.Tia_Cod, tip.Tia_Des, tip.Tia_Ini, tip.Tia_Abr FROM tipo_asien as tip WHERE tip.Tia_Des        LIKE '%$Par_Sql[0]%' ORDER BY tip.Tia_Des ASC";	
		//echo $busca_tipos;	
		return $busca_tipos;

		/* 
		* Carga datos de los tipos de comprobantes 
		*/		
		case 4:
		$carga_tipo= "SELECT tip.Tia_Cod, tip.Tia_Des, tip.Tia_Ini, tip.Tia_Abr, tip.Tia_Est FROM tipo_asien as tip WHERE tip.Tia_Cod =        '$Par_Sql[0]'";
		return $carga_tipo;
		break;

		case 5: 
		/* 
		* consulta  si existe otro tipo de asiento
		*/
		$cons_tip=  "SELECT Tia_Cod, Tia_Des FROM tipo_asien WHERE Tia_Des = '$Par_Sql[0]' AND  Tia_Ini = '$Par_Sql[1]'";	
		//echo $cons_tip;
		return $cons_tip;
		break;

		/* 
		* Actualiza los datos del tipo comprobante 
		*/		
		case 6:
		$actualiza_tipo= "UPDATE tipo_asien SET Tia_Des = Trim(UPPER('$Par_Sql[0]')), Tia_Ini= Trim(UPPER('$Par_Sql[1]')), 			        Tia_Abr=Trim(UPPER('$Par_Sql[2]')) WHERE Tia_Cod = $Par_Sql[3]";
	    //echo $actualiza_tipo;
		return $actualiza_tipo;
		break;
		
		case 7:
		$cons_tip= "SELECT Tia_Cod, Tia_Abr, Tia_Des, Tia_Ini, Tia_Est FROM tipo_asien";	
		//echo $cons_tip;
		return $cons_tip;
		break;
		
		case 8:
		$elimina_tip= "UPDATE tipo_asien SET Tia_Est = Trim(UPPER('$Par_Sql[0]')) WHERE Tia_Cod = $Par_Sql[1]";	
		//echo $cons_tip;
		return $elimina_tip;
		break;
	}
}
?>