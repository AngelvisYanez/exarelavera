<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizacion: 2013-07-05
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
function sentencias_men($id, $Par_Sql)
{
	switch ($id) {
		case 1:
			$where = !empty($Par_Sql[1]) ? "WHERE (" . $Par_Sql[1] . ")" : "";
			$sql = "SELECT DISTINCT organizado.Org_Cod, organizado.Org_Des, organizado.Org_Ico, organizado.Org_Ord, organizado.Org_Niv, organizado.Org_Det
					FROM organizado
					LEFT JOIN procesos ON (organizado.Org_Cod = procesos.Org_Cod)
					LEFT JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
					$where
					ORDER BY organizado.Org_Ord ASC";
			return $sql;
			break;

		case 2:
			$whereNiv = (isset($Par_Sql[0]) && $Par_Sql[0] !== '') ? "organizado.Org_Niv = " . (int)$Par_Sql[0] : "1=1";
			$sql = "SELECT DISTINCT organizado.Org_Cod, organizado.Org_Des, organizado.Org_Ico, organizado.Org_Ord, organizado.Org_Niv, organizado.Org_Det
					FROM organizado
					WHERE $whereNiv
					ORDER BY organizado.Org_Ord ASC";
			return $sql;
			break;

		case 3:
			$whereOrg = (isset($Par_Sql[0]) && $Par_Sql[0] !== '') ? "AND procesos.Org_Cod = " . (int)$Par_Sql[0] : "";
			$wherePerf = !empty($Par_Sql[1]) ? "AND (" . $Par_Sql[1] . ")" : "";
			$sql = "SELECT DISTINCT procesos.Pcs_Cod, procesos.Pcs_Lin, procesos.Pcs_Det, procesos.Pcs_Ico, procesos.Pcs_Ord, rutas.Rut_Des, procesos.Pcs_Nom, procesos.Org_Cod, procesos.Pcs_Tip
					FROM procesos
					INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod)
					LEFT JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)
					WHERE procesos.Pcs_Est = 'A' $whereOrg $wherePerf
					ORDER BY procesos.Pcs_Ord ASC";
			return $sql;
			break;

		case 16:
			/**
			 * Consulta los organizado del nivel 0 
			 */
			$sql =
				"(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
	organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM organizado WHERE organizado.Org_Cod IN 
	(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
	 (procesos.Org_Cod = organizado.Org_Cod) WHERE (" . $Par_Sql[0] . "))) ORDER BY organizado.Org_Ord Asc) 
	 UNION DISTINCT
	 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
	organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
	(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
	 (procesos.Org_Cod = organizado.Org_Cod) WHERE (" . $Par_Sql[0] . ")) AND organizado.Org_Niv = 0 ORDER BY organizado.Org_Ord Asc)";
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
	  (" . $Par_Sql[0] . ")) AND organizado.Org_Niv = $Par_Sql[0] ORDER BY organizado.Org_Ord Asc";
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
	AND procesos.Org_Cod=$Par_Sql[0] AND (" . $Par_Sql[1] . ")
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
	  organizado.Org_Niv = $Par_Sql[0] ORDER BY organizado.Org_Ord Asc";
			return $sql;
			break;
	}
}
