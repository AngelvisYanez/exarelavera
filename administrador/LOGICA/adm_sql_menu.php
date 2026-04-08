<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2013-07-05
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
	function sentencias_men($id,$Par_Sql)
	{
		switch($id)
		{
			case 16:
			/**
			* Consulta los organizado del nivel 0 
			*/
			$sql = 
	"(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
	organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM organizado WHERE organizado.Org_Cod IN 
	(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
	 (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[0]."))) ORDER BY organizado.Org_Ord Asc) 
	 UNION DISTINCT
	 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
	organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
	(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
	 (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[0].")) AND organizado.Org_Niv = 0 ORDER BY organizado.Org_Ord Asc)"; 
	 //echo $sql;
			return $sql;
			break;	

			case 17:
			/**
			* Consulta los organizados del arbol del siguiente nivel 1 
			*/
			$sql =   
			"SELECT DISTINCT organizado.Org_Det,
	  organizado.Org_Ord,
	  organizado.Org_Des,
	  organizado.Org_Niv,
	  organizado.Org_Cod,
	  organizado.Org_Img,
	  organizado.Org_Ime  FROM organizado
	WHERE organizado.Org_Cod IN
	(SELECT 
	  organizado.Org_Niv
	FROM
	  procesos
	  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
	  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
	WHERE
	  (".$Par_Sql[0].")) AND organizado.Org_Niv = $Par_Sql[0] ORDER BY organizado.Org_Ord Asc";
			return $sql;
			break;
	
			case 18:
			/**
			* Consulta los procesos del arbol 
			*/
			$sql = "SELECT DISTINCT procesos.Pcs_Lin, rutas.Rut_Des,
	procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det
	FROM
	  rutas
	  INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod)
	  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
	WHERE
	procesos.Pcs_Est='A' AND procesos.Pcs_Tip = '$Par_Sql[2]'
	AND procesos.Org_Cod=$Par_Sql[0] AND (".$Par_Sql[1].")
	ORDER BY procesos.Pcs_Ord";
			return $sql;
			break;

			case 31:
			/**
			* Consulta los organizados del arbol del siguiente nivel 2 
			*/
			$sql =   
	"SELECT DISTINCT
	organizado.Org_Det,
	  organizado.Org_Ord,
	  organizado.Org_Des,
	  organizado.Org_Niv,
	  organizado.Org_Cod,
	  organizado.Org_Img,
	  organizado.Org_Ime
	FROM
	  procesos
	  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
	  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)
	WHERE
	  organizado.Org_Niv = $Par_Sql[0] ORDER BY organizado.Org_Ord Asc";// (".$Par_Sql[0].") AND
	
			return $sql;
			break;
				
		}
	}
?>