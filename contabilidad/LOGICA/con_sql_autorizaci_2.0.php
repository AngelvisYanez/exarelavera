<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Asael Tello
 * @version 2.0
 * Fecha de actualizaci�n:	2017-23-08
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
		case 101:
			/* 
		* Consultar autorizacion SRI en base a la descripci�n */
			$consultaraut = "SELECT  autorizaci.Aut_Cod, tipo_compr.Tic_Cod, tipo_compr.Tic_Des, Aut_Tem,autorizaci.Aut_Sri, autorizaci.Aut_Cad, autorizaci.Aut_Ini, autorizaci.Aut_Fin FROM autorizaci, tipo_compr, puntos_imp WHERE autorizaci.Tic_Cod = tipo_compr.Tic_Cod AND 	
		autorizaci.Pun_Cod = puntos_imp.Pun_Cod AND autorizaci.Pun_Cod = $Par_Sql[0] AND autorizaci.Aut_Est ='A'";
			//echo $consultaraut;
			return $consultaraut;
			break;

		case 103:
			$Par_Sql["Ext_Cod"] = !empty($Par_Sql["Ext_Cod"]) ? $Par_Sql["Ext_Cod"]  : "NULL";
			$Par_Sql["Aut_Tpt"] = !empty($Par_Sql["Aut_Tpt"]) ? $Par_Sql["Aut_Tpt"]  : "NULL";
			/* 
		* Insertar autorizaci�n 
		*/
			$autoriza = "INSERT INTO autorizaci(Pun_Cod, Tic_Cod, Aut_Sri, Pun_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin, Aut_Adv, Aut_Ads, Aut_Est, Aut_Ima,Aut_Tem, Aut_Tpt,Ext_Cod) VALUES "
				. "('$Par_Sql[Pun_Cod_n]', '$Par_Sql[Tic_Cod_n]', '$Par_Sql[Aut_Sri]', '$Par_Sql[Pun_Sri]', '$Par_Sql[Aut_Fci]', '$Par_Sql[Aut_Cad]', "
				. "'$Par_Sql[Aut_Ini]', '$Par_Sql[Aut_Fin]', " . (empty($Par_Sql['Aut_Adv']) ? 'NULL' : $Par_Sql['Aut_Adv']) . ", " . (empty($Par_Sql['Aut_Ads']) ? 'NULL' : $Par_Sql['Aut_Ads']) . ", 'A', $Par_Sql[Aut_Ima],'$Par_Sql[Aut_Tem]', '$Par_Sql[Aut_Tpt]', $Par_Sql[Ext_Cod])";
			return $autoriza;
			break;


		case 104:
			/*
		* Cierre de la autorizaci�n
		*/
			$mod_cierre = "UPDATE autorizaci SET Aut_Est= 'I' WHERE Aut_Cod = $Par_Sql[0]";
			//echo $mod_cierre;
			return $mod_cierre;
			break;

		case 109:
			//if(!isset($Par_Sql['Aut_Tem'])||empty($Par_Sql['Aut_Tem'])) $Par_Sql['Aut_Tem']='N';
			/* 
		* Modifica o actualiza los cambios realizados en la tabla de autorozaciones 
		*/
			$modificar_aut = "UPDATE autorizaci"
				. " SET Tic_Cod = $Par_Sql[Tic_Cod_n], Aut_Sri ='$Par_Sql[Aut_Sri]', Pun_Sri='$Par_Sql[Pun_Sri]',"
				. " Aut_Fci ='$Par_Sql[Aut_Fci]', Aut_Cad ='$Par_Sql[Aut_Cad]', Aut_Ini = '$Par_Sql[Aut_Ini]', Aut_Fin ='$Par_Sql[Aut_Fin]',"
				. " Aut_Adv='$Par_Sql[Aut_Adv]', Aut_Ads='$Par_Sql[Aut_Adv]', Aut_Tem = '$Par_Sql[Aut_Tem]', Aut_Ima = $Par_Sql[Aut_Ima], Aut_Tpt ='$Par_Sql[Aut_Tpt]', Ext_Cod ='$Par_Sql[Ext_Cod]' "
				. " WHERE Aut_Cod = $Par_Sql[Aut_Cod]";
			//echo $modificar_aut;
			return $modificar_aut;
			break;

		/*
		* Carga las sucursales de la empresa
		*/
		case 507:
			$Sql_507 = "SELECT Suc_Cod, Suc_Sri, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
			//echo $Sql_507;
			return $Sql_507;
			break;

		// Carga las sucursales de la Utsam
		case 508:
			$Sql_508 = "SELECT Pun_Cod, Suc_Cod, Pun_Des FROM puntos_imp WHERE Pun_Est = 'A' AND Suc_Cod= $Par_Sql[Suc_Cod]";
			//echo $Sql_508;
			return $Sql_508;
			break;

		// Carga todos los documentos segun el Punto de impresion
		case 509:
			$Sql_509 = "SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Des FROM tipo_compr WHERE   	
		tipo_compr.Tic_Est='A' AND tipo_compr.Tic_Cod NOT IN (SELECT autorizaci.Tic_Cod FROM autorizaci WHERE autorizaci.Pun_Cod = $Par_Sql[0] AND 	
		autorizaci.Aut_Est = 'A')";
			//echo $Sql_509;
			return $Sql_509;
			break;

		/*
		* Carga todos los documentos segun el Punto de impresion
		*/
		case 510:
			$Sql_510 = "SELECT * FROM autorizaci WHERE autorizaci.Aut_Cod = $Par_Sql[0] AND autorizaci.Aut_Est = 'A'";
			//echo $Sql_510;
			return $Sql_510;
			break;

		/*
         * Get Tipo de Documento by Punto
         */
		case 511:
			$SearchSucCod = "";
			if (isset($Par_Sql['Suc_Cod']) || !empty($Par_Sql['Suc_Cod']))
				$SearchSucCod = "AND puntos_imp.Suc_Cod = $Par_Sql[Suc_Cod]";
			$sql_511 = "SELECT DISTINCT tipo_compr.Tic_Cod, tipo_compr.Tic_Sri, tipo_compr.Tic_Des,Aut_Tem,Aut_Tpt,Ext_Cod FROM puntos_imp "
				. "INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod) "
				. "INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod) "
				. "WHERE autorizaci.Pun_Cod= $Par_Sql[Pun_Cod] $SearchSucCod group by Tic_Sri, tipo_compr.Tic_Des";
			return $sql_511;
			break;

		/*
                 * Get Autorizacion by Punto and Tipo de Documento
                 */
		case 512:
			$SearchTicCod = "";
			if (isset($Par_Sql['Tic_Cod']) || !empty($Par_Sql['Tic_Cod']))
				$SearchTicCod = "AND Tic_Cod = $Par_Sql[Tic_Cod]";
			$sql_512 = "SELECT Aut_Cod, Pun_Cod, Tic_Cod, Aut_Sri, Pun_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin, IF (Aut_Est='A', 'Activo','Inactivo') as Aut_Est, Aut_Ads, Aut_Adv,IF(Aut_Tem IS NULL,'N',Aut_Tem)AS Aut_Tem,IF(Aut_Tem='E','ELECTRONICA','NORMAL') as AutTem, Aut_Ima ,Aut_Tpt ,Ext_Cod "
				. "FROM autorizaci WHERE Pun_Cod = $Par_Sql[Pun_Cod]  $SearchTicCod ORDER BY Aut_Ini DESC";
			return $sql_512;
			break;

		/*
		* Actualiza el estado de Autorizaciones
		*/
		case 513:
			$Sql_513 = "UPDATE autorizaci SET Aut_Est = '$Par_Sql[Aut_Est]' WHERE autorizaci.Aut_Cod = $Par_Sql[Aut_Cod]";
			return $Sql_513;
			break;

		/*
		* Get Autorizaciones by Code
		*/
		case 514:
			$Sql_514 = "SELECT Aut_Cod, Pun_Cod, Tic_Cod, Aut_Sri, Pun_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin, IF (Aut_Est='A', 'Activo','Inactivo') as Aut_Est, Aut_Ads, Aut_Adv, Aut_Tem, Aut_Ima FROM autorizaci WHERE autorizaci.Aut_Cod = $Par_Sql[0]";
			return $Sql_514;
			break;

		/*
		* Get Tipos de Documento
		*/
		case 515:
			$Sql_515 = "SELECT Tic_Cod, Tic_Des, Tic_Sri FROM tipo_compr";
			return $Sql_515;
			break;

		/*
                 * Contar existen tipos de documentos activos by Suc_Cod and Pun_Cod
                 */
		case 516:
			$Sql_516 = "SELECT COUNT(Aut_Cod) as contador FROM autorizaci WHERE Tic_Cod = $Par_Sql[Tic_Cod_n] AND Pun_Cod = $Par_Sql[Pun_Cod_n] AND Aut_Est = 'A' ";
			return $Sql_516;
			break;

		case 517:
			$Par_Sql["Ext_Cod"] = !empty($Par_Sql["Ext_Cod"] ) ? $Par_Sql["Ext_Cod"]  : "NULL";
			$Par_Sql["Aut_Tpt"] = !empty($Par_Sql["Aut_Tpt"]) ? $Par_Sql["Aut_Tpt"]  : "NULL";
			/* 
		* Insertar autorizacion inactiva
		*/
			$autoriza = "INSERT INTO autorizaci(Pun_Cod, Tic_Cod, Aut_Sri, Pun_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin, Aut_Adv, Aut_Ads, Aut_Est, Aut_Ima,Aut_Tem,Aut_Tpt,Ext_Cod) VALUES "
				. "('$Par_Sql[Pun_Cod_n]', '$Par_Sql[Tic_Cod_n]', '$Par_Sql[Aut_Sri]', '$Par_Sql[Pun_Sri]', '$Par_Sql[Aut_Fci]', '$Par_Sql[Aut_Cad]', "
				. "'$Par_Sql[Aut_Ini]', '$Par_Sql[Aut_Fin]', " . (empty($Par_Sql['Aut_Adv']) ? 'NULL' : $Par_Sql['Aut_Adv']) . ", " . (empty($Par_Sql['Aut_Ads']) ? 'NULL' : $Par_Sql['Aut_Ads']) . ", 'I', $Par_Sql[Aut_Ima], '$Par_Sql[Aut_Tem]', '$Par_Sql[Aut_Tpt]', $Par_Sql[Ext_Cod])";
			return $autoriza;
			break;

		/*
                 * Selecciona user by sucursal y persona
                 */
		case 518:
			$sql = "SELECT
                            `sucursal`.`Suc_Cod`,
                            `puntos_imp`.`Pun_Cod`,
                            `sucursal`.`Suc_Des`,
                            `puntos_imp`.`Pun_Des`,
                            `persona`.`Prs_Nom`,
                            `persona`.`Prs_Ape`
                          FROM
                            `persona`
                            INNER JOIN `vendedor` ON `persona`.`Prs_Cod` = `vendedor`.`Prs_Cod`
                            INNER JOIN `puntos_imp` ON `puntos_imp`.`Pun_Cod` = `vendedor`.`Pun_Cod`
                            INNER JOIN `sucursal` ON `sucursal`.`Suc_Cod` = `puntos_imp`.`Suc_Cod` WHERE sucursal.Suc_Cod = $Par_Sql[Suc_Cod] AND persona.Prs_Cod = $Par_Sql[Prs_Cod] ";
			return $sql;
			break;

		/*
		* Actualizaciones diferentes al codigo se desactivan
		*/
		case 519:
			$Sql_519 = "UPDATE autorizaci SET Aut_Est = 'I' WHERE autorizaci.Aut_Cod != $Par_Sql[Aut_Cod] AND autorizaci.Pun_Cod = $Par_Sql[Pun_Cod] AND autorizaci.Tic_Cod = $Par_Sql[Tic_Cod]";
			return $Sql_519;
			break;
		case 520:
			$Sql_520 = "SELECT * FROM confi_fact WHERE Emp_Cod='$Par_Sql[0]';";
			return $Sql_520;
			break;

		//SELECCIONAR SOCIOS CHOFERES
		case 521:
			$Sql_521 = "SELECT * FROM rutas_fact_extra WHERE  Ext_Ruc IS NOT NULL AND Emp_Cod='$Par_Sql[0]';";
			return $Sql_521;
			break;
	}
}
