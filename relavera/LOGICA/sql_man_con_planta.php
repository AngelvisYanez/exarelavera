<?php

/**
 * Retorna consulta sql a ejecutarse para Consulta de Planta
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
*/

function sentencias_consulta_planta($id, $Par_Sql) {
	switch ($id) {
		case 1:
			// Obtener datos completos de la planta con cliente
			$Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
			$sql = "SELECT manifiesto_plantas.*, 
						ciudad.Ciu_Des,
						cliente.Cli_Cod,
						persona_cli.Prs_Ced as Cli_Ced,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente
					FROM manifiesto_plantas
						LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
						LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
						LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
					WHERE manifiesto_plantas.Pla_Cod = '$Pla_Cod'
						AND manifiesto_plantas.Pla_Est = 'A';";
			return $sql;
			
		case 2:
			// Obtener datos del administrador de la planta (AP)
			$Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
			$sql = "SELECT persona.*, 
						manifiesto_personal_planta.Pep_Tip,
						manifiesto_personal_planta.Pep_Esc,
						manifiesto_personal_planta.Pep_Cor,
						manifiesto_personal_planta.Ciu_Cod_Tra,
						manifiesto_personal_planta.Ciu_Cod_Nac,
						ciudad_nac.Ciu_Des as Ciu_Des_Nac,
						ciudad_tra.Ciu_Des as Ciu_Des_Tra
					FROM manifiesto_personal_planta
						INNER JOIN persona ON persona.Prs_Cod = manifiesto_personal_planta.Prs_Cod
						LEFT JOIN ciudad AS ciudad_nac ON ciudad_nac.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Nac
						LEFT JOIN ciudad AS ciudad_tra ON ciudad_tra.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Tra
					WHERE manifiesto_personal_planta.Pla_Cod = '$Pla_Cod'
						AND manifiesto_personal_planta.Pep_Tip = 'AP'
					LIMIT 1;";
			return $sql;
			
		case 3:
			// Obtener datos del contador/tributario de la planta (AC)
			$Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
			$sql = "SELECT persona.*, 
						manifiesto_personal_planta.Pep_Tip,
						manifiesto_personal_planta.Pep_Esc,
						manifiesto_personal_planta.Pep_Cor,
						manifiesto_personal_planta.Ciu_Cod_Tra,
						manifiesto_personal_planta.Ciu_Cod_Nac,
						ciudad_nac.Ciu_Des as Ciu_Des_Nac,
						ciudad_tra.Ciu_Des as Ciu_Des_Tra
					FROM manifiesto_personal_planta
						INNER JOIN persona ON persona.Prs_Cod = manifiesto_personal_planta.Prs_Cod
						LEFT JOIN ciudad AS ciudad_nac ON ciudad_nac.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Nac
						LEFT JOIN ciudad AS ciudad_tra ON ciudad_tra.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Tra
					WHERE manifiesto_personal_planta.Pla_Cod = '$Pla_Cod'
						AND manifiesto_personal_planta.Pep_Tip = 'AC'
					LIMIT 1;";
			return $sql;
			
		case 4:
			// Obtener datos del ingeniero ambiental de la planta (AM)
			$Pla_Cod = isset($Par_Sql['Pla_Cod']) ? addslashes($Par_Sql['Pla_Cod']) : '';
			$sql = "SELECT persona.*, 
						manifiesto_personal_planta.Pep_Tip,
						manifiesto_personal_planta.Pep_Esc,
						manifiesto_personal_planta.Pep_Cor,
						manifiesto_personal_planta.Ciu_Cod_Tra,
						manifiesto_personal_planta.Ciu_Cod_Nac,
						ciudad_nac.Ciu_Des as Ciu_Des_Nac,
						ciudad_tra.Ciu_Des as Ciu_Des_Tra
					FROM manifiesto_personal_planta
						INNER JOIN persona ON persona.Prs_Cod = manifiesto_personal_planta.Prs_Cod
						LEFT JOIN ciudad AS ciudad_nac ON ciudad_nac.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Nac
						LEFT JOIN ciudad AS ciudad_tra ON ciudad_tra.Ciu_Cod = manifiesto_personal_planta.Ciu_Cod_Tra
					WHERE manifiesto_personal_planta.Pla_Cod = '$Pla_Cod'
						AND manifiesto_personal_planta.Pep_Tip = 'AM'
					LIMIT 1;";
			return $sql;
			
		case 5:
			// Buscar plantas por filtro con todos los datos agrupados
			$wherefiltro = '';
			$search = isset($Par_Sql['search']) ? trim($Par_Sql['search']) : '';
			$filtro = isset($Par_Sql['filtro']) ? $Par_Sql['filtro'] : '';
			
			if ($search !== '') {
				$val = addslashes($search);
				switch ($filtro) {
					case 'c': // Por código
						$wherefiltro = " AND manifiesto_plantas.Pla_Nom LIKE '%$val%'";
						break;
					case 'cl': // Por cliente
						$wherefiltro = " AND (persona_cli.Prs_Nom LIKE '%$val%' OR persona_cli.Prs_Ape LIKE '%$val%' OR persona_cli.Prs_Ced LIKE '%$val%')";
						break;
				}
			}
			
			$sql = "SELECT manifiesto_plantas.Pla_Cod, 
						manifiesto_plantas.Pla_Nom,
						manifiesto_plantas.Pla_Lic,
						manifiesto_plantas.Pla_Car,
						ciudad.Ciu_Des,
						CONCAT(persona_cli.Prs_Nom, ' ', persona_cli.Prs_Ape) as Cliente,
						persona_cli.Prs_Ced as Cli_Ced,
						manifiesto_plantas.Pla_Dir,
						manifiesto_plantas.Pla_Geo,
						DATE_FORMAT(manifiesto_plantas.Pla_Dis, '%H:%i') as Pla_Dis,
						manifiesto_plantas.Pla_Cap,
						manifiesto_plantas.Pla_Crd,
						manifiesto_plantas.Pla_Cau,
						manifiesto_plantas.Pla_Rut,
						DATE_FORMAT(manifiesto_plantas.Pla_Fem, '%d/%m/%Y') as Pla_Fem,
						DATE_FORMAT(manifiesto_plantas.Pla_Fve, '%d/%m/%Y') as Pla_Fve,
						-- Datos Admin Planta
						admin.Prs_Ced as Admin_Ced,
						admin.Prs_Nom as Admin_Nom,
						admin.Prs_Ape as Admin_Ape,
						IF(mpp_admin.Pep_Esc='S','SOLTERO/A',IF(mpp_admin.Pep_Esc='C','CASADO/A',IF(mpp_admin.Pep_Esc='D','DIVORCIADO/A',IF(mpp_admin.Pep_Esc='V','VIUDO/A',IF(mpp_admin.Pep_Esc='U','UNIÓN LIBRE',''))))) as Admin_Esc,
						IF(admin.Prs_Sex='M','MASCULINO',IF(admin.Prs_Sex='F','FEMENINO','')) as Admin_Sex,
						DATE_FORMAT(admin.Prs_Fec, '%Y-%m-%d') as Admin_Fec,
						admin_nac.Ciu_Des as Admin_Ciu_Nac,
						admin.Prs_Tel as Admin_Tel,
						admin.Prs_Te2 as Admin_Tel2,
						admin_tra.Ciu_Des as Admin_Ciu_Tra,
						mpp_admin.Pep_Cor as Admin_Cor,
						-- Datos Contador/Tributario
						contador.Prs_Ced as Cont_Ced,
						contador.Prs_Nom as Cont_Nom,
						contador.Prs_Ape as Cont_Ape,
						IF(mpp_contador.Pep_Esc='S','SOLTERO/A',IF(mpp_contador.Pep_Esc='C','CASADO/A',IF(mpp_contador.Pep_Esc='D','DIVORCIADO/A',IF(mpp_contador.Pep_Esc='V','VIUDO/A',IF(mpp_contador.Pep_Esc='U','UNIÓN LIBRE',''))))) as Cont_Esc,
						IF(contador.Prs_Sex='M','MASCULINO',IF(contador.Prs_Sex='F','FEMENINO','')) as Cont_Sex,
						DATE_FORMAT(contador.Prs_Fec, '%Y-%m-%d') as Cont_Fec,
						contador_nac.Ciu_Des as Cont_Ciu_Nac,
						contador.Prs_Tel as Cont_Tel,
						contador.Prs_Te2 as Cont_Tel2,
						contador_tra.Ciu_Des as Cont_Ciu_Tra,
						mpp_contador.Pep_Cor as Cont_Cor,
						-- Datos Ambiental
						ambiental.Prs_Ced as Amb_Ced,
						ambiental.Prs_Nom as Amb_Nom,
						ambiental.Prs_Ape as Amb_Ape,
						IF(mpp_ambiental.Pep_Esc='S','SOLTERO/A',IF(mpp_ambiental.Pep_Esc='C','CASADO/A',IF(mpp_ambiental.Pep_Esc='D','DIVORCIADO/A',IF(mpp_ambiental.Pep_Esc='V','VIUDO/A',IF(mpp_ambiental.Pep_Esc='U','UNIÓN LIBRE',''))))) as Amb_Esc,
						IF(ambiental.Prs_Sex='M','MASCULINO',IF(ambiental.Prs_Sex='F','FEMENINO','')) as Amb_Sex,
						DATE_FORMAT(ambiental.Prs_Fec, '%Y-%m-%d') as Amb_Fec,
						ambiental_nac.Ciu_Des as Amb_Ciu_Nac,
						ambiental.Prs_Tel as Amb_Tel,
						ambiental.Prs_Te2 as Amb_Tel2,
						ambiental_tra.Ciu_Des as Amb_Ciu_Tra,
						mpp_ambiental.Pep_Cor as Amb_Cor
					FROM manifiesto_plantas
						LEFT JOIN ciudad ON ciudad.Ciu_Cod = manifiesto_plantas.Ciu_Cod
						LEFT JOIN cliente ON cliente.Cli_Cod = manifiesto_plantas.Cli_Cod
						LEFT JOIN persona AS persona_cli ON cliente.Prs_Cod = persona_cli.Prs_Cod
						-- Admin Planta
						LEFT JOIN manifiesto_personal_planta AS mpp_admin ON mpp_admin.Pla_Cod = manifiesto_plantas.Pla_Cod AND mpp_admin.Pep_Tip = 'AP'
						LEFT JOIN persona AS admin ON admin.Prs_Cod = mpp_admin.Prs_Cod
						LEFT JOIN ciudad AS admin_nac ON admin_nac.Ciu_Cod = mpp_admin.Ciu_Cod_Nac
						LEFT JOIN ciudad AS admin_tra ON admin_tra.Ciu_Cod = mpp_admin.Ciu_Cod_Tra
						-- Contador
						LEFT JOIN manifiesto_personal_planta AS mpp_contador ON mpp_contador.Pla_Cod = manifiesto_plantas.Pla_Cod AND mpp_contador.Pep_Tip = 'AC'
						LEFT JOIN persona AS contador ON contador.Prs_Cod = mpp_contador.Prs_Cod
						LEFT JOIN ciudad AS contador_nac ON contador_nac.Ciu_Cod = mpp_contador.Ciu_Cod_Nac
						LEFT JOIN ciudad AS contador_tra ON contador_tra.Ciu_Cod = mpp_contador.Ciu_Cod_Tra
						-- Ambiental
						LEFT JOIN manifiesto_personal_planta AS mpp_ambiental ON mpp_ambiental.Pla_Cod = manifiesto_plantas.Pla_Cod AND mpp_ambiental.Pep_Tip = 'AM'
						LEFT JOIN persona AS ambiental ON ambiental.Prs_Cod = mpp_ambiental.Prs_Cod
						LEFT JOIN ciudad AS ambiental_nac ON ambiental_nac.Ciu_Cod = mpp_ambiental.Ciu_Cod_Nac
						LEFT JOIN ciudad AS ambiental_tra ON ambiental_tra.Ciu_Cod = mpp_ambiental.Ciu_Cod_Tra
					WHERE manifiesto_plantas.Pla_Est = 'A'
						AND (cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]' OR manifiesto_plantas.Cli_Cod IS NULL)
						$wherefiltro
					ORDER BY manifiesto_plantas.Pla_Nom;";
			return $sql;
	}
}
?>
