<?php

/**
 * Retorna consulta sql a ejecutarse
 *
 * @author Alejandro Camacho
 * @version 1.0
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * @package tesoreria.LOGICA
 */

function sentencias_cargamasiva($id, $Par_Sql) {
	switch ($id) {
			//sentencia para guardar las facturas del SRI como preguardados 
		case 1:
			/*	$sql="INSERT INTO carga_masiva (Emp_cod, Carm_Ruc, Carm_Emi, Carm_Fec, Carm_Rur, Carm_Cod, Carm_Com, Carm_Num, Carm_Cla, Carm_Aut, Carm_Fea, Carm_Tard, Carm_Tarc, Carm_Iva, Carm_Tot)
				VALUES($_SESSION[Ses_Emp_Cod], '$Par_Sql[Carm_Ruc]', '$Par_Sql[Carm_Emi]', '$Par_Sql[Carm_Fec]', '$Par_Sql[Carm_Rur]', '$Par_Sql[Carm_Cod]',
						'$Par_Sql[Carm_Com]', '$Par_Sql[Carm_Num]', '$Par_Sql[Carm_Cla]', '$Par_Sql[Carm_Aut]', '$Par_Sql[Carm_Fea]', '$Par_Sql[Carm_Tard]', 
						'$Par_Sql[Carm_Tarc]',  '$Par_Sql[Carm_Iva]', '$Par_Sql[Carm_Tot]');"; */

			$sql = "INSERT INTO carga_masiva (Emp_cod, Carm_Ruc, Carm_Emi, Carm_Fec, Carm_Rur, Carm_Cod, Carm_Com, Carm_Num, Carm_Cla, Carm_Aut, Carm_Fea, Carm_NOIVA, Carm_Tard, Carm_Tarcnco, Carm_Taroch, Carm_Tarqnce ,Carm_Tarc, Carm_Iva, Carm_Desc, Carm_Prop, Carm_Tot)
					VALUES($_SESSION[Ses_Emp_Cod], '$Par_Sql[Carm_Ruc]', '$Par_Sql[Carm_Emi]', '$Par_Sql[Carm_Fec]', '$Par_Sql[Carm_Rur]', '$Par_Sql[Carm_Cod]',
							'$Par_Sql[Carm_Com]', '$Par_Sql[Carm_Num]', '$Par_Sql[Carm_Cla]', '$Par_Sql[Carm_Aut]', '$Par_Sql[Carm_Fea]', '$Par_Sql[Carm_NOIVA]', '$Par_Sql[Carm_Tard]', 
							'$Par_Sql[Carm_Tarcnco]','$Par_Sql[Carm_Taroch]','$Par_Sql[Carm_Tarqnce]',  '$Par_Sql[Carm_Tarc]', '$Par_Sql[Carm_Iva]', '$Par_Sql[Carm_Desc]', '$Par_Sql[Carm_Prop]', '$Par_Sql[Carm_Tot]');";
			return $sql;

		case 2:
			$complemento = '';

			if ($Par_Sql[Valor] != '') {
				if ($Par_Sql[Opcion] == 'n') {
					$complemento = " and Carm_Num like '%$Par_Sql[Valor]%'";
				} else {
					$complemento = " and (Carm_Ruc like '%$Par_Sql[Valor]%' or Carm_Emi like '%$Par_Sql[Valor]%')";
				}
			}

			// Convertir fechas con '-' a formato con '/' si es necesario
			$fec_ini = str_replace('-', '/', $Par_Sql['Fec_Ini']);
			$fec_fin = str_replace('-', '/', $Par_Sql['Fec_Fin']);

			$ocultoPre = "";
			if ($Par_Sql['ocultoPre'] == '1') {
				$ocultoPre = "AND STR_TO_DATE(Carm_Fec,'%d/%m/%Y') BETWEEN STR_TO_DATE('$fec_ini','%d/%m/%Y') AND STR_TO_DATE('$fec_fin','%d/%m/%Y')";
			}

			$sql = "SELECT * FROM carga_masiva 
					WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Carm_Est = 'P'
						$ocultoPre $complemento
					ORDER BY Carm_Fec DESC, Carm_Num DESC";
			return $sql;

		case 4:
			$complemento = '';
			if ($Par_Sql[Valor] != '') {
				if ($Par_Sql[Opcion] == 'n') {
					$complemento = " and Carm_Num like '%$Par_Sql[Valor]%'";
				} else {
					$complemento = " and (Carm_Ruc like '%$Par_Sql[Valor]%' or Carm_Emi like '%$Par_Sql[Valor]%')";
				}
			}

			// Convertir fechas con '-' a formato con '/' si es necesario
			$fec_ini_omi = str_replace('-', '/', $Par_Sql['Fec_Ini_Omi']);
			$fec_fin_omi = str_replace('-', '/', $Par_Sql['Fec_Fin_Omi']);

			$ocultoOmi = "";
			if ($Par_Sql['ocultoOmi'] == '1') {
				$ocultoOmi = "AND STR_TO_DATE(Carm_Fec,'%d/%m/%Y') BETWEEN STR_TO_DATE('$fec_ini_omi','%d/%m/%Y') AND STR_TO_DATE('$fec_fin_omi','%d/%m/%Y')";
			}

			$sql = "SELECT * FROM carga_masiva
					WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Carm_Est = 'O'
						$ocultoOmi $complemento
					ORDER BY Carm_Fec DESC, Carm_Num DESC";
			return $sql;

		case 5:
			$complemento = '';
			if ($Par_Sql[Valor] != '') {
				if ($Par_Sql[Opcion] == 'n') {
					$complemento = " and Carm_Num like '%$Par_Sql[Valor]%'";
				} else {
					$complemento = " and (Carm_Ruc like '%$Par_Sql[Valor]%' or Carm_Emi like '%$Par_Sql[Valor]%')";
				}
			}

			// Convertir fechas con '-' a formato con '/' si es necesario
			$fec_ini_exi = str_replace('-', '/', $Par_Sql['Fec_Ini_Exi']);
			$fec_fin_exi = str_replace('-', '/', $Par_Sql['Fec_Fin_Exi']);

			$ocultoExi = "";
			if ($Par_Sql['ocultoExi'] == '1') {
				$ocultoExi = "AND STR_TO_DATE(Carm_Fec,'%d/%m/%Y') BETWEEN STR_TO_DATE('$fec_ini_exi','%d/%m/%Y') AND STR_TO_DATE('$fec_fin_exi','%d/%m/%Y')";
			}

			$sql = "SELECT carga_masiva.*, CONCAT (tipo_asien.Tia_Abr, '-', RIGHT(CONCAT('00',MONTH(comprobantes.Com_Fec)),2), '-', comprobantes.Com_Num) as Com_Num
					FROM compras
						INNER JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
						INNER JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
						INNER JOIN carga_masiva ON (persona.Prs_Ced = carga_masiva.Carm_Ruc and compras.Cop_Num = carga_masiva.Carm_Num and proveedore.Emp_Cod = carga_masiva.Emp_Cod)
						LEFT JOIN compr_auto ON compr_auto.Cop_Cod=compras.Cop_Cod
						LEFT JOIN comprobantes ON compr_auto.Com_Cod=comprobantes.Com_Cod
						LEFT JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod 
					WHERE carga_masiva.Emp_cod = $Par_Sql[Emp_Cod]
						$ocultoExi $complemento
					ORDER BY carga_masiva.Carm_Fec DESC, carga_masiva.Carm_Num DESC";
			return $sql;

		case 6:
			$sql = "SELECT Carm_Cla, Carm_Est FROM carga_masiva WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
			return $sql;

		case 7:
			$sql = "SELECT Carm_Cla, Carm_Est FROM carga_masiva WHERE Emp_Cod = $Par_Sql[Emp_Cod] AND Carm_Cla = '$Par_Sql[Carm_Cla]'";
			return $sql;

		case 8:
			$sql = "SELECT compras.Cop_Cod FROM compras, proveedore, persona
					WHERE compras.Prv_Cod = proveedore.Prv_Cod
					AND proveedore.Prs_Cod = persona.Prs_Cod
					AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod]
					AND compras.Cop_Num = '$Par_Sql[Cop_Num]'
					AND persona.Prs_Ced = '$Par_Sql[Prs_Ced]'";
			return $sql;

		case 9:
			$sql = "SELECT Emp_Ruc FROM empresas WHERE Emp_Cod = $Par_Sql[Emp_Cod]";
			return $sql;

		case 3:
			$sql = "UPDATE carga_masiva SET Carm_Est = '$Par_Sql[Carm_Est]' WHERE Carm_Id = $Par_Sql[Carm_Id] ";
			return $sql;

		case 10:
			$fec_ini = date("Y-m-d", strtotime($Par_Sql['fec_ini']));
			$fec_fin = date("Y-m-d", strtotime($Par_Sql['fec_fin']));
			$sql = "UPDATE carga_masiva SET carga_masiva.Carm_Est = 'C' 
					WHERE 
					carga_masiva.Carm_Est = 'P' AND carga_masiva.Emp_Cod = $Par_Sql[Emp_Cod] AND
					carga_masiva.Carm_Cla IN ( SELECT cop_aut FROM compras
						INNER JOIN  proveedore ON compras.Prv_Cod = proveedore.Prv_Cod WHERE proveedore.Emp_Cod = $Par_Sql[Emp_Cod]  
						AND Cop_Fec Between '$fec_ini' AND '$fec_fin' )";

			/*$sql = "UPDATE carga_masiva, compras, proveedore, persona 
					SET carga_masiva.Carm_Est = 'C' 
					WHERE 
					compras.Prv_Cod = proveedore.Prv_Cod
					AND proveedore.Prs_Cod = persona.Prs_Cod
					AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod]
					AND compras.Cop_Num = carga_masiva.Carm_Num 
					AND compras.Cop_Aut = carga_masiva.Carm_Cla
					AND persona.Prs_Ced = carga_masiva.Carm_Ruc
					AND carga_masiva.Carm_Est = 'P'";*/
			return $sql;
	}

	return $sql;
}
?>