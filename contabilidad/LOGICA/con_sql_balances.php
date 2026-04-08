<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-10-04
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */

function sentencias_con($id, $Par_Sql)
{
	switch ($id) {

		case 500:
			$codigoProveedor = "SELECT 
				proveedore.Prv_Cod
				FROM
				persona, proveedore WHERE (persona.Prs_Nom like '%Varios%' or persona.Prs_Ape like '%Varios%') 
				AND persona.Prs_Cod = proveedore.Prs_Cod
				AND proveedore.Emp_Cod = $Par_Sql[0]";
			return $codigoProveedor;
			break;

		case 501:
			$sql = "SELECT comprobantes.Com_Cod FROM comprobantes, tipo_asien
					WHERE comprobantes.Tia_Cod = tipo_asien.Tia_cod 
					AND tipo_asien.Tia_Abr = 'DA' 
					AND comprobantes.Com_Est = 'A'
					AND comprobantes.Pec_Cod = $Par_Sql[0]";
			return $sql;
			break;


		case 519:
			$sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo, 
				perio_cont.Pla_Cod as Pla_Cod FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod 
				AND plan_cuenta.Emp_Cod = $Par_Sql[0] AND Year(Pec_Fei) = $Par_Sql[1] AND Year(Pec_Fef) = $Par_Sql[1] ORDER BY Pec_Fei Desc";
			return $sql;
			break;


		case 3:
			/* Consulta la provicia y pais de la ciudad de la sucursal */
			$provincia = "SELECT 
  provincia.Pro_Nom,
  pais.Pas_Nom
FROM
  provincia
  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
 WHERE 
  ciudad.Ciu_Cod = $Par_Sql[0]";
			//echo $provincia;
			return $provincia;
			break;

		case 4:
			/* 
		* Consulta del usuario
		*/
			$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $consulta_4;
			return $consulta_4;
			break;

			/*
		* Informacion del Representante y Contador de la Empresa
		*/
		case 5:
			$sql = "SELECT Emp_Ren,Emp_Rre,Emp_Con,Emp_Rco FROM empresas WHERE Emp_Cod='$Par_Sql[0]'";
			//echo $sql; 		
			return $sql;
			break;

		case 126:
			/**
			 * Consulta la informaci�n la ciudada en base a la sucursal 
			 */
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

		case 212:
			/**
			 * Consulta que realiza el calculo del saldo establecido entre 2 fechas
			 */
			/*$saldo_212 = "SELECT asientos.Asi_Deh, sum(round(asientos.Asi_Val,2)) as Asi_Val, Pld_Cdc FROM asientos, comprobantes, det_plan 
				WHERE comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND comprobantes.Com_Fec BETWEEN '$Par_Sql[0]'
				AND  '$Par_Sql[1]'
				  AND comprobantes.Com_Est = 'A' AND det_plan.Pld_Cod = '$Par_Sql[2]' GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";*/
			$saldo_212 = "SELECT asientos.Asi_Deh, sum(round(asientos.Asi_Val,2)) as Asi_Val FROM asientos, comprobantes 
				WHERE comprobantes.Com_Cod = asientos.Com_Cod AND comprobantes.Com_Fec BETWEEN '$Par_Sql[0]'
				AND  '$Par_Sql[1]'
				  AND comprobantes.Com_Est = 'A' AND asientos.Pld_Cod = '$Par_Sql[2]' GROUP BY asientos.Asi_Deh ORDER BY asientos.Asi_Deh ASC";
			//if($_SESSION['Ses_Prs_Cod']=='1') echo $saldo_212.'<br>';				  
			return $saldo_212;
			break;

		case 213:
			/**
			 * Consulta que realiza el calculo del saldo establecido entre 2 fechas de los bancos tipo banco
			 */
			/*"SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val
FROM
  det_plan
  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
WHERE
  banco.Ban_Tip = 'B' AND 
  asientos.Asi_Deh = '$Par_Sql[3]' AND 
  asientos.Com_Cod IN (SELECT DISTINCT comprobantes.Com_Cod FROM comprobantes INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) WHERE comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND comprobantes.Com_Est = 'A' AND asientos.Pld_Cod = '$Par_Sql[2]')
GROUP BY
  asientos.Asi_Deh
ORDER BY
  asientos.Asi_Deh";*/
			$sql = "SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val
FROM
  asientos
WHERE
  asientos.Asi_Deh = '$Par_Sql[3]' AND 
  (asientos.Pld_Cod = 2909 or asientos.Pld_Cod = 2910 or asientos.Pld_Cod = 2911 or asientos.Pld_Cod = 4239
  or asientos.Pld_Cod = 4804 or asientos.Pld_Cod = 4403 or asientos.Pld_Cod = 5322) AND
  asientos.Com_Cod IN (SELECT DISTINCT comprobantes.Com_Cod FROM comprobantes INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) WHERE comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND comprobantes.Com_Est = 'A' AND asientos.Pld_Cod = '$Par_Sql[2]')
GROUP BY
  asientos.Asi_Deh
ORDER BY
  asientos.Asi_Deh";
			return $sql;
			break;

		case 214:
			/**
			 * Consulta los comprobantes donde estuvo inmerso la cuenta contable
			 */
			$sql = "SELECT  DISTINCT 
  comprobantes.Com_Cod
FROM
  comprobantes
  INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
WHERE comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND  '$Par_Sql[1]'
				  AND comprobantes.Com_Est = 'A' AND asientos.Pld_Cod = '$Par_Sql[2]'";
			return $sql;
			break;

		case 215:
			/**
			 * Consulta los asientos de tipo banco donde estuvo inmersa segun el comprobante contable
			 */
			$sql = "SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val,
  det_plan.Pld_Des
FROM
  det_plan
  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
WHERE
  asientos.Asi_Deh = '$Par_Sql[1]' AND 
  (asientos.Pld_Cod = 2909 or asientos.Pld_Cod = 2910 or asientos.Pld_Cod = 2911 or asientos.Pld_Cod = 4239
  or asientos.Pld_Cod = 4804 or asientos.Pld_Cod = 4403 or asientos.Pld_Cod = 5322) AND
  asientos.Com_Cod = $Par_Sql[0]
GROUP BY
  asientos.Asi_Deh,
  det_plan.Pld_Des
ORDER BY
  asientos.Asi_Deh";
			return $sql;
			break;

		case 216:
			/**
			 * 
			 */
			$sql = "SELECT 
  persona.Prs_Ape,
  persona.Prs_Nom
FROM
  proveedore
  INNER JOIN comprobantes ON (proveedore.Prv_Cod = comprobantes.Prv_Cod)
  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
WHERE
  comprobantes.Com_Cod = $Par_Sql[0]";
			return $sql;
			break;

		case 217:
			/**
			 * Consulta los comprobantes donde estuvo inmerso la cuenta contable, en base al codigo del plan de cuenta CES
			 */
			$sql = "SELECT DISTINCT 
  comprobantes.Com_Cod, comprobantes.Com_Num, Com_Fec, Prv_Cod
FROM
  comprobantes
  INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
  INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
WHERE
  comprobantes.Com_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
  comprobantes.Com_Est = 'A' AND 
  det_plan.Pld_Ces = '$Par_Sql[2]'";
			//      echo $sql."<br>"; 


			return $sql;
			break;

		case 218: //corregir
			/**
			 * Consulta los valores de las cuentas cuando son iess o roles
			 */
			$sql = "SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val,
  det_plan.Pld_Des
FROM
  det_plan
  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
WHERE
  asientos.Asi_Deh = '$Par_Sql[1]' AND 
  asientos.Com_Cod = $Par_Sql[0]
  AND det_plan.Pld_Ces = $Par_Sql[2]
GROUP BY
  asientos.Asi_Deh,
  det_plan.Pld_Des
ORDER BY
  asientos.Asi_Deh";
			// echo $sql."<br>"; 
			return $sql;
			break;



			/**
			 * Consulta de todos los periodos - UTILIZADO PARA LAS CONSULTAS DE BALANCES 
			 */
		case 219:
			$sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo, perio_cont.Pla_Cod FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei Desc";
			return $sql;
			break;

			/**
			 * Consulta la cuenta que sirve para cargar la utilidad 
			 */
		case 220:
			$sql = "SELECT utilidades.Pld_Cod, Pld_Des, Pld_Rec FROM utilidades, det_plan WHERE utilidades.Pld_Cod = 
						det_plan.Pld_Cod AND Pec_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Cargado de la ra�z del Plan de Cuentas de cuantas activas
			 */
		case 315:
			$sql = "SELECT Pld_Cod, Pld_Cdc, Pld_Des, Pld_Est, Pld_Rec, Pld_Tip, CONVERT(substring_index(Pld_Cdc,'.',-1),UNSIGNED INTEGER) AS orden FROM det_plan WHERE Pla_Cod=$Par_Sql[0] AND Pld_Rec=$Par_Sql[1] ORDER BY orden;"; //AND Pld_Est = 'A' 		
			//echo $sql."<br/>";
			return $sql;
			break;

			/**
			 * Cargado de la ra�z del Plan de Cuentas de cuantas activas en base al tipo de balance financiero 
			 */
		case 337:
			$sql = "SELECT det_plan.Pld_Cod, Pld_Cdc, Pld_Des, Pld_Est, Pld_Rec, Pld_Tip FROM det_plan, det_estado WHERE det_plan.Pld_Cod = det_estado.Pld_Cod 
						AND Pla_Cod=$Par_Sql[0] AND Pld_Rec=$Par_Sql[1] AND Est_Cod = $Par_Sql[2] order by Pld_Cdc Asc";
			//echo $sql."<br/>";
			return $sql;
			break;



		case 238: //Borrar
			/**
			 * Consulta los asientos de tipo banco donde estuvo inmersa segun el comprobante contable
			 */
			$sql = "SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val
FROM
  det_plan
  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
WHERE
  asientos.Asi_Deh = '$Par_Sql[1]' AND 
  (asientos.Pld_Cod = 2909 or asientos.Pld_Cod = 2910 or asientos.Pld_Cod = 2911 or asientos.Pld_Cod = 4239
  or asientos.Pld_Cod = 4804 or asientos.Pld_Cod = 4403 or asientos.Pld_Cod = 5322) AND
  asientos.Com_Cod = $Par_Sql[0]
GROUP BY
  asientos.Asi_Deh
ORDER BY
  asientos.Asi_Deh";
			//			echo $sql."<br>";	
			return $sql;
			break;

		case 239: //Borrar
			/**
			 * Consulta los valores de las cuentas cuando son iess o roles
			 */
			$sql = "SELECT 
  asientos.Asi_Deh,
  sum(round(asientos.Asi_Val, 2)) AS Asi_Val
FROM
  det_plan
  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
WHERE
  asientos.Asi_Deh = '$Par_Sql[1]' AND 
  asientos.Com_Cod = $Par_Sql[0]
  AND det_plan.Pld_Ces = $Par_Sql[2]
GROUP BY
  asientos.Asi_Deh
ORDER BY
  asientos.Asi_Deh";

			return $sql;
			break;

		case 240:
			// OPTIMIZACIÓN: Cambiar LEFT JOIN a INNER JOIN y mejorar estructura de la consulta
			$filtroAdicional = (isset($Par_Sql[3]) && $Par_Sql[3] != '') ? " AND " . $Par_Sql[3] : "";
			$sql = "SELECT 
						det_plan.Pla_Cod,
						det_plan.Pld_Cod,
						Pld_Cdc,
						Pld_Des,
						Pld_Est,
						SUM(IF(Asi_Deh='D', ROUND(Asi_Val,2), 0)) as debe,
						SUM(IF(Asi_Deh='H', ROUND(Asi_Val,2), 0)) as haber,
						IF(SUM(IF(Asi_Deh='D', ROUND(Asi_Val,2), 0)) - SUM(IF(Asi_Deh='H', ROUND(Asi_Val,2), 0)) >= 0,
							SUM(IF(Asi_Deh='D', ROUND(Asi_Val,2), 0)) - SUM(IF(Asi_Deh='H', ROUND(Asi_Val,2), 0)),
							0) AS deudor,
						IF(SUM(IF(Asi_Deh='D', ROUND(Asi_Val,2), 0)) - SUM(IF(Asi_Deh='H', ROUND(Asi_Val,2), 0)) < 0,
							ABS(SUM(IF(Asi_Deh='D', ROUND(Asi_Val,2), 0)) - SUM(IF(Asi_Deh='H', ROUND(Asi_Val,2), 0))),
							0) AS acreedor
					FROM asientos  
					INNER JOIN det_plan ON asientos.Pld_Cod = det_plan.Pld_Cod                    
					INNER JOIN comprobantes ON comprobantes.Com_Cod = asientos.Com_Cod
					WHERE  
						comprobantes.Com_Est = 'A'	 		  
						AND comprobantes.Pec_Cod = '$Par_Sql[0]'
						AND comprobantes.Com_Fec BETWEEN '$Par_Sql[1] 00:00:00' AND '$Par_Sql[2] 23:59:59'
						$filtroAdicional
					GROUP BY det_plan.Pld_Cod
					ORDER BY 
						CAST(LEFT(Pld_Cdc, LENGTH(Pld_Cdc) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1))) AS CHAR) ASC,
						CAST(SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0 AS DECIMAL);";
			//echo $sql;
			return $sql;

			/**
			 * Consulta la cuenta que sirve para cargar la utilidad 
			 */
		case 241:
			$sql = "SELECT utilidades.Pld_Cod, Pld_Des, Pld_Rec FROM utilidades, det_plan WHERE utilidades.Pld_Cod = 
									det_plan.Pld_Cod AND Pec_Cod = $Par_Sql[0] AND Uti_Tip = $Par_Sql[1]";
			return $sql;
			break;

		case 242:
			$sql = "SELECT det_plan.* FROM utilidades 
                            INNER JOIN det_plan ON utilidades.Pld_Cod=det_plan.Pld_Cod
                            WHERE Pla_Cod=$Par_Sql[0] AND Uti_Tip='$Par_Sql[1]'";
			//echo $sql;
			return $sql;
		case 243:
			$sql = "SELECT Pld_Cod,Pld_Cdc,Pld_Des,Pld_Tip,Pld_Rec FROM det_plan                       
                        WHERE det_plan.Pla_Cod='$Par_Sql[0]'  AND ($Par_Sql[1]) AND Pld_Est='A' 
                        ORDER BY  
                        CAST( LEFT( Pld_Cdc, LENGTH( Pld_Cdc ) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1) ) ) AS CHAR )  ASC,                               
                        CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0)AS DECIMAL)";
			//echo $sql;
			return $sql;

		case 244:
			$sql = "SELECT Pld_Cdc FROM utilidades "
				. "JOIN det_plan ON utilidades.Pld_Cod=det_plan.Pld_Cod "
				. "WHERE Pla_Cod=$Par_Sql[0] AND Uti_Tip = 'I' AND det_plan.Pld_Est = 'A'";
			return $sql;
	}
}
