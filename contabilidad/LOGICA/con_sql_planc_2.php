<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizacion:	2012-04-19
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */
function sentencias_con($id, $Par_Sql) {
	switch ($id) {
			/**
		 * Busqueda de cuentas por codigo y plan de cuenta (Verificar que no hay codigo repetido) CONCURRENCIA
		 */
		case 1:
			$sql = "SELECT det_plan.Pld_Cdc, det_plan.Pld_Des, Pla_Obs, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND det_plan.Pla_Cod=$Par_Sql[0] AND plan_cuenta.Emp_Cod=$Par_Sql[2] AND det_plan.Pld_Cdc = '$Par_Sql[1]'";
			return $sql;
			break;

			/**
			 * Cargado de la cabecera del reporte de plan de cuentas (Datos de Empresa y del Plan) 
			 */
		case 2:
			$sql = "SELECT Emp_Nom, Pla_Cod, Pla_Fec, IF (Pla_Est='A', 'Activa', 'Inactiva') as Pla_Est, Pla_Obs FROM empresas,plan_cuenta WHERE empresas.Emp_Cod=plan_cuenta.Emp_Cod AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Consulta la provicia y pais de la ciudad de la sucursal 
			 */
		case 3:
			$sql = "SELECT 
			  provincia.Pro_Nom,
			  pais.Pas_Nom
			FROM
			  provincia
			  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
			  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
			  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
			 WHERE 
			  ciudad.Ciu_Cod = $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Consulta los datos del usuario 
			 */
		case 4:
			$sql = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Obtener un detalle del plan de cuenta
			 */
		case 5:
			$sql = "SELECT `Pld_Cdc`,`Pld_Des`,`Pld_Tip`,`Pld_Deb`,`Pld_Cre`,`Pla_Cod` FROM `det_plan` WHERE `Pld_Cod`= $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * verificar si tiene subcuentas relacionadas
			 */
		case 6:
			$sql = "SELECT COUNT(`Pld_Cdc`)AS 'count' FROM `det_plan` WHERE `Pld_Rec` = $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * contar si existe otra cuenta con el mismo codigo
			 */
		case 7:
			$sql = "SELECT COUNT(`Pld_Cod`)AS 'count' FROM `det_plan` INNER JOIN `plan_cuenta` ON `plan_cuenta`.`Pla_Cod` = `det_plan`.`Pla_Cod`
			WHERE `plan_cuenta`.`Pla_Cod` = $Par_Sql[0] AND plan_cuenta.Emp_Cod = $Par_Sql[1] AND det_plan.Pld_Cdc = '$Par_Sql[2]' AND `Pld_Cod` != $Par_Sql[3]";
			return $sql;
			break;

			/**
			 * Actualizar detalle de un plan de cuentas
			 */
		case 8:
			$sql = "UPDATE det_plan SET Pld_Cdc = '$Par_Sql[0]', Pld_Des = '$Par_Sql[1]', Pld_Tip = '$Par_Sql[2]', Pld_Deb = '$Par_Sql[3]', Pld_Cre = '$Par_Sql[4]' WHERE Pld_Cod=$Par_Sql[5]";
			return $sql;
			break;

			/**
			 * Consulta la informaci�n la ciudada en base a la sucursal 
			 */
		case 126:
			$sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
			break;

			/**
			 * Busca si la cuenta tiene moviemto 
			 */
		case 145:
			$sql = "SELECT Pld_Cod FROM asientos WHERE asientos.Pld_Cod=$Par_Sql[0] limit 0,1";
			return $sql;
			break;

			/**
			 * Cargado de los plan de cuentas de una empresa en especifico (Codigo de la empresa)
			 */
		case 302:
			$sql = "SELECT Pla_Cod, Pla_Fec, Pla_Obs, IF (Pla_Est='A','Activo','Inactivo') as Pla_Est FROM plan_cuenta WHERE Emp_Cod=$Par_Sql[0] " . (isset($Par_Sql[1]) ? $Par_Sql[1] : '');
			//echo $sql;
			return $sql;
			break;

			/**
			 * Cargado de los nodos del plan de cuentas 
			 */
		case 303:
			$sql = "SELECT Pld_Cod,Pld_Cdc, Pld_Des, IF (Pld_Tip='G','GRUPO','Detalle') as Pld_Tip, IF (Pld_Est='A','Activo','Inactivo') as Pld_Est, Pla_Obs, Pld_Rec, Pld_Deb, Pld_Cre FROM plan_cuenta, det_plan WHERE plan_cuenta.Emp_Cod=$Par_Sql[0] AND plan_cuenta.Pla_Cod=$Par_Sql[1] AND Pld_Rec=$Par_Sql[2] AND plan_cuenta.Pla_Cod=det_plan.Pla_Cod ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0";
			return $sql;
			break;

			/**
			 * Insercion de una nueva cuenta en algun nodo del plan de cuentas 
			 */
		case 304:
			$sql = "INSERT INTO det_plan SET Pld_Rec='$Par_Sql[0]', Pla_Cod=$Par_Sql[1], Pld_Cdc='$Par_Sql[2]', Pld_Des='$Par_Sql[3]', Pld_Tip='$Par_Sql[4]'";
			return $sql;
			break;

			/**
			 * Cargado del nombre de la cuenta para poder mostrar la direcci�n donde esta en ese momento
			 */
		case 305:
			$sql = "SELECT Pld_Cod,Pld_Cdc,Pld_Des, Pld_Rec FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Cargado del recursivo de la cuenta para poder mostrar la direcci�n de volver atr�s
			 */
		case 306:
			$sql = "SELECT Pld_Rec FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Cargado de la informaci�n a modificar en una cuenta
			 */
		case 307:
			$sql = "SELECT Pld_Cdc, Pld_Des, Pld_Tip, Pld_Est, Pld_Deb, Pld_Cre FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Actualizacion de una cuenta del Plan de Cuentas
			 */
		case 308:
			$sql = "UPDATE det_plan SET Pld_Cdc='$Par_Sql[0]', Pld_Des='$Par_Sql[1]', Pld_Tip='$Par_Sql[2]', Pld_Est='$Par_Sql[3]' WHERE Pld_Cod=$Par_Sql[4]";
			return $sql;
			break;

			/**
			 * Inserci�n de una nueva cabecera de plan de cuentas
			 */
		case 309:
			$sql = "INSERT INTO plan_cuenta (Emp_Cod, Pla_Fec, Pla_Obs) VALUES ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]')";
			return $sql;
			break;

			/**
			 * Cargado de la informaci�n a modificar en una cabecera de plan de cuenta
			 */
		case 310:
			$sql = "SELECT Pla_Cod,Pla_Obs, Pla_Est FROM plan_cuenta WHERE Pla_Cod=$Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Actualizacion de una cuenta del Plan de Cuentas
			 */
		case 311:
			$sql = "UPDATE plan_cuenta SET Pla_Obs='$Par_Sql[0]', Pla_Est='$Par_Sql[1]' WHERE Pla_Cod=$Par_Sql[2]";
			return $sql;
			break;

			/**
			 * Consultas las cuentas del siguiente nivel 
			 */
		case 312:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF(Pld_Tip = 'G', 'GRUPO', 'Detalle') AS Pld_Tip,
	  		IF(Pld_Est = 'A', 'Activo', 'Inactivo') AS Pld_Est FROM det_plan WHERE Pld_Rec = $Par_Sql[0] ORDER BY
	  		SUBSTRING_INDEX(Pld_Cdc,'.', -1) + 0";
			return $sql;
			break;

			/**
			 * Busqueda de cuentas por descripcion 
			 */
		case 313:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, Pla_Obs, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec,Pld_Deb,Pld_Cre FROM det_plan, plan_cuenta WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' ORDER BY Pld_Cod";
			return $sql;
			break;

			/**
			 * Busqueda de cuentas por codigo 
			 */
		case 314:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec,Pld_Deb,Pld_Cre FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Cdc = TRIM('$Par_Sql[0]') $Par_Sql[2]";
			return $sql;
			break;

			/**
			 * Cargado de la ra�z del Plan de Cuentas de cuantas activas
			 */
		case 315:
			$sql = "SELECT Pld_Cod, Pld_Cdc, Pld_Des, Pld_Est, Pld_Rec, Pld_Tip, Pld_Deb, Pld_Cre FROM det_plan WHERE Pld_Est='A' AND Pla_Cod=$Par_Sql[0] AND Pld_Rec=$Par_Sql[1] ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0";
			return $sql;
			break;

			/**
			 * Consulta el iva cobrado
			 */
		case 316:
			$sql = "SELECT  
			  iva_cobrad.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc
			FROM
			  det_plan
			  INNER JOIN iva_cobrad ON (det_plan.Pld_Cod = iva_cobrad.Pld_Cod)
			WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Elimina la cuenta de la tabla iva_cobrad
			 */
		case 317:
			$sql = "DELETE FROM iva_cobrad WHERE iva_cobrad.Pld_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 * Insertar en la cuenta de la tabla iva_cobrad
			 */
		case 318:
			$sql = "INSERT INTO iva_cobrad (Pld_Cod) VALUES ($Par_Sql[0])";
			return $sql;
			break;

			/**
			 * Consulta el iva pagado
			 */
		case 319:
			$sql = "SELECT  
			  iva_pagado.Pld_Cod, det_plan.Pld_Des, det_plan.Pld_Cdc
			FROM
			  det_plan
			  INNER JOIN iva_pagado ON (det_plan.Pld_Cod = iva_pagado.Pld_Cod)
			WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			return $sql;
			break;

			/**
			 * Elimina la cuenta de la tabla iva_cobrad
			 */
		case 320:
			$sql = "DELETE FROM iva_pagado WHERE iva_pagado.Pld_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 * Insertar en la cuenta de la tabla iva_cobrad
			 */
		case 321:
			$sql = "INSERT INTO iva_pagado (Pld_Cod) VALUES ($Par_Sql[0])";
			return $sql;
			break;

			/**
			 * Insercion de una nueva cuenta en algun nodo del plan de cuentas para la versi�n de interfaz 2.0
			 */
		case 322:
			$sql = "INSERT INTO det_plan (Pld_Rec,Pla_Cod,Pld_Cdc,Pld_Des,Pld_Tip,Pld_Deb,Pld_Cre)VALUES ('$Par_Sql[0]', $Par_Sql[1],'$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]')";
			return $sql;
			break;

			/**
			 * Actualizacion de una cuenta del Plan de Cuentas para la version 2.0
			 */
		case 323:
			$sql = "UPDATE det_plan SET Pld_Cdc='$Par_Sql[0]', Pld_Des='$Par_Sql[1]', Pld_Tip='$Par_Sql[2]', Pld_Est='$Par_Sql[3]', Pld_Deb='$Par_Sql[5]', Pld_Cre='$Par_Sql[6]' WHERE Pld_Cod=$Par_Sql[4]";
			return $sql;
			break;
		case 324:
			/* Consulta la informaci�n relacionada con las fechas del periodo contable */
			$sql = "SELECT perio_cont.Pec_Cod,perio_cont.Pla_Cod,YEAR(Pec_Fei) as Ann,perio_cont.Pec_Fei, perio_cont.Pec_Fef FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		case 325:
			/* Consulta la informaci�n relacionada con las fechas del periodo contable */
			$sql = "SELECT Pld_Cod,det_plan.Pla_Cod,Pld_Cdc,Pld_Tip,Pld_Des FROM det_plan WHERE Pld_Est='A' AND Pld_Tip='D' AND Pla_Cod=$Par_Sql[1] AND (Pld_Des LIKE '%$Par_Sql[2]%' OR Pld_Cdc LIKE '$Par_Sql[2]%') ORDER BY Pld_Cdc ASC";
			//echo $sql;
			return $sql;
		case 326:
			/* Consulta la informaci�n relacionada con las fechas del periodo contable */
			//$sql = "UPDATE det_plan SET Pld_Est='I' WHERE Pld_Cod='$Par_Sql[0]'";
			$sql = "UPDATE det_plan SET Pld_Est='I' WHERE Pld_Cod='$Par_Sql[0]'  OR Pld_Rec= '$Par_Sql[0]' ";
			//echo $sql;
			return $sql;


		case 327:
			/* Consulta la informaci�n relacionada con las fechas del periodo contable */
			$sql = "DELETE FROM det_plan WHERE Pld_Cod='$Par_Sql[0]'";
			
			//echo $sql;
			return $sql;
		case 328:
			/* Consulta la informaci�n relacionada con las fechas del periodo contable */
			//$sql = "SELECT COUNT(Asi_Cod) AS total FROM asientos WHERE Pld_Cod='$Par_Sql[0]'";

			$sql = "SELECT  CASE  WHEN EXISTS ( SELECT 1  FROM det_plan  
			WHERE Pld_Tip = 'G' AND Pld_Cod = asientos.Pld_Cod ) THEN 1  ELSE COUNT(asientos.Asi_Cod)
			END AS total FROM asientos 
			WHERE asientos.Pld_Cod = '$Par_Sql[0]'";

			//echo $sql;
			return $sql;
		case 329:
			/* Consulta el plan */
			$sql = "SELECT plan_cuenta.Pla_Cod, Year(Pec_Fei)as Pla_Fec,Pec_Cod 
				FROM plan_cuenta,perio_cont WHERE plan_cuenta.Pla_Cod = perio_cont.Pla_Cod AND Pla_Est='A' AND Emp_Cod='$Par_Sql[0]' ORDER BY Pla_Fec DESC";
			//echo $sql;
			return $sql;
		case 330:
			if ($Par_Sql[3] == "d") {
				$search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
			}
			if ($Par_Sql[4] == "") {
				$campos = "COUNT(det_plan.Pld_Cod) as total";
			} else {
				$Par_Sql[4] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[4];
				$campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
						IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
						IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
			}
			$bus_xmld_330 = "SELECT $campos
						FROM det_plan 
						INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
						INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
						INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
						LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
						LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
						WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
						AND $search AND Pec_Cod =$Par_Sql[2] 
						AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
			//echo $bus_xmld_330;
			return $bus_xmld_330;

		case 331:
			/* Consulta el plan */
			$sql = "SELECT det_plan.Pld_Cod,det_plan.Pld_Cdc,det_plan.Pld_Des  
				FROM det_plan,iva_pagado WHERE det_plan.Pld_Cod = iva_pagado.Pld_Cod AND Pla_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;

		case 332:
			/* Consulta ica cobrado */
			$sql = "SELECT det_plan.Pld_Cod,det_plan.Pld_Cdc,det_plan.Pld_Des  
				FROM det_plan,iva_cobrad WHERE det_plan.Pld_Cod = iva_cobrad.Pld_Cod AND Pla_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		case 333:
			/*Insertamos iva cobrado */
			$sql = "INSERT INTO iva_cobrad(Pld_Cod)VALUES('$Par_Sql[0]')";
			//echo $sql;  
			return $sql;
			break;
		case 334:
			/*Insertamos iva pagado */
			$sql = "INSERT INTO iva_pagado(Pld_Cod)VALUES('$Par_Sql[0]')";
			//echo $sql;
			return $sql;
			break;

		case 335:
			/* Consulta el plan */
			$sql = "SELECT Pld_Cod,Pld_Cdc,Pld_Des,IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip,Pld_Cod as id, CAST(IF(Pld_Rec=0,'#',Pld_Rec) AS CHAR) as parent,CONCAT(Pld_Cdc,' - ',Pld_Des) as 'text',IF(Pld_Rec=0,'fa fa-hand-o-right red bold',IF(Pld_Tip='G','glyphicon glyphicon-folder-open blue','fa fa-file-text green')) as icon FROM det_plan WHERE Pla_Cod='$Par_Sql[0]' AND Pld_Est='A' $Par_Sql[1]  ORDER BY CAST( LEFT( Pld_Cdc, LENGTH( Pld_Cdc ) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1) ) ) AS CHAR )  ASC,                               
				CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0)AS DECIMAL)";
			//echo $sql;
			return $sql;

		case 336:
			/* Consulta el plan */
			$sql = "SELECT MAX(CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0)AS DECIMAL)) AS max FROM det_plan WHERE Pld_Rec='$Par_Sql[1]' AND Pla_Cod='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;

		case 337:
			/* BUSQUEDA DE CUENTAS GRUPO */
			if ($Par_Sql[3] == "d") {
				$search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
			}
			if ($Par_Sql[5] == "") {
				$campos = "COUNT(det_plan.Pld_Cod) as total";
			} else {
				$Par_Sql[5] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[5];
				$campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
						IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
						IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
			}
			$sql = "SELECT $campos
						FROM det_plan 
						INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
						/*INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod*/
						INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
						LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
						LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
						WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
						AND $search AND plan_cuenta.Pla_Cod =$Par_Sql[2] 
						AND det_plan.Pld_Tip = 'G' AND det_plan.Pld_Cod!=$Par_Sql[4] $Par_Sql[5]";
			//echo $sql."<br>";
			return $sql;
		case 338:
			/* Actualiza los Pld_Cdc */
			$sql = "UPDATE det_plan SET Pld_Cdc = REPLACE(Pld_Cdc, '$Par_Sql[1]', '$Par_Sql[2]') WHERE Pld_Cdc LIKE '$Par_Sql[1]%' AND Pla_Cod=$Par_Sql[0];";
			//echo $sql;
			return $sql;
		case 339:
			/* Actualiza los Pld_Rec */
			$sql = "UPDATE det_plan SET Pld_Rec = $Par_Sql[1] WHERE Pld_Cod = $Par_Sql[0];";
			//echo $sql;
			return $sql;
		case 340:
			$sql = "SELECT det_plan.Pld_Cdc, det_plan.Pld_Des, Pla_Obs, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND det_plan.Pla_Cod=$Par_Sql[0] AND plan_cuenta.Emp_Cod=$Par_Sql[2] AND det_plan.Pld_Cdc = '$Par_Sql[1]' AND Pld_Est='A' ";
			//echo $sql;
			return $sql;
		case 341:
			$sql = "SELECT * FROM tipo_param WHERE " . (!empty($Par_Sql[1]) ? "Tpa_Est='$Par_Sql[1]'" : "1=1") . " " . ($Par_Sql[0] != '' ? "AND Tpa_Cod=$Par_Sql[0]" : "") . " ORDER BY Tpa_Des DESC";
			//echo $sql;
			return $sql;
			/* tipos de parametros */
		case 342:
			if (isset($Par_Sql[1])) {
				$sqlPec = " AND Pec_Cod='$Par_Sql[1]' ";
			} else {
				$sqlPec = '';
			}
			$sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est,perio_cont.Pla_Cod, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] $sqlPec ORDER BY Pec_Fei Desc";
			//echo $sql;
			return $sql;
		case 343:
			$sql = "SELECT Tpa_Cod, Tpa_Abr FROM tipo_param WHERE Tpa_Abr='$Par_Sql[0]' AND Tpa_Est='A'";
			//echo $sql;
			break;
		case 344:
			$sql = "INSERT INTO tipo_param (Tpa_Grp, Tpa_Abr, Tpa_Des, Tpd_Uni, Tpa_Uni, Tpa_Est) VALUES (UPPER('$Par_Sql[0]'), UPPER('$Par_Sql[1]'), UPPER('$Par_Sql[2]'), '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]')";
			//echo $sql;
			return $sql;
		case 345:
			$sql = "SELECT CONCAT(plan_param.Pld_Cod,'_',plan_param.Tpa_Cod) AS id,plan_param.Pld_Cod,plan_param.Tpa_Cod,Pld_Cdc,Pld_Des,Tpa_Des,Tpa_Abr FROM plan_param
					INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod 
					INNER JOIN tipo_param ON tipo_param.Tpa_Cod=plan_param.Tpa_Cod 
					WHERE Pla_Cod=$Par_Sql[0] AND Tpa_Est='A' " . ($Par_Sql[1] != '' ? " AND tipo_param.Tpa_Cod=$Par_Sql[1] " : "") . " ORDER BY Tpa_Des DESC ";
			//echo $sql;               
			return $sql;
		case 346:
			$sql = "SELECT Pla_Cod FROM perio_cont
					WHERE Pec_Cod=$Par_Sql[0]";
			//echo $sql;               
			return $sql;
		case 347:
			if ($Par_Sql[3] == "d") {
				$search = "det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";
			}
			if ($Par_Sql[4] == "") {
				$campos = "COUNT(det_plan.Pld_Cod) as total";
			} else {
				$Par_Sql[4] = "ORDER BY det_plan.Pld_Cod " . $Par_Sql[4];
				$campos = "det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
						IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
						IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
			}
			$sql = "SELECT $campos
						FROM det_plan 
						INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
						INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
						INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod 
						LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
						LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
						WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
						AND $search AND Pec_Cod =$Par_Sql[2] 
						AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";

			//echo $sql;               
			return $sql;
		case 348:
			$sql = "INSERT INTO plan_param (Tpa_Cod,Pld_Cod) VALUES ($Par_Sql[Tpa_Cod],$Par_Sql[Pld_Cod]);";
			//echo $sql;               
			return $sql;
		case 349:
			$sql = "DELETE FROM plan_param WHERE (Tpa_Cod=$Par_Sql[Tpa_Cod] AND Pld_Cod=$Par_Sql[Pld_Cod]);";
			//echo $sql;               
			return $sql;
		case 350:
			$sql = "SELECT COUNT(Asi_Cod) AS total FROM asientos 
						INNER JOIN ccpp_pagar ON ccpp_pagar.Com_Cod=asientos.Com_Cod
						INNER JOIN plan_param ON plan_param.Pld_Cod=asientos.Pld_Cod
						INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						WHERE (tipo_param.Tpa_Cod=$Par_Sql[Tpa_Cod] AND asientos.Pld_Cod=$Par_Sql[Pld_Cod] AND Asi_Deh='H' ) ";
			//echo $sql;               
			return $sql;
		case 351:
			$sql = "SELECT COUNT(Asi_Cod) AS total FROM asientos 
						INNER JOIN ccpp_cobrar ON ccpp_cobrar.Com_Cod=asientos.Com_Cod
						INNER JOIN plan_param ON plan_param.Pld_Cod=asientos.Pld_Cod
						INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod
						WHERE (tipo_param.Tpa_Cod=$Par_Sql[Tpa_Cod] AND asientos.Pld_Cod=$Par_Sql[Pld_Cod] AND Asi_Deh='D') ";
			//echo $sql;               
			return $sql;
		case 352:
			if ($Par_Sql['pc_opciones'] == "a") {
				$search = "(det_plan.Pld_Des LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(det_plan.Pld_Cod) AS total" : "det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activo', 'Inactivo') as Pld_Est ";
			$sql = "SELECT $campos
					FROM det_plan
					WHERE  $search AND 
                                        det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))
                                        ORDER BY SUBSTRING_INDEX(Pld_Cdc, '.', -20) $Par_Sql[limits];";
			//echo $sql;  
			return $sql;
		case 353:
			$sql = "SELECT plan_cuenta.Pla_Cod, plan_cuenta.Pla_Obs, plan_cuenta.Pla_Fec, IF (Pla_Est='A', 'Activo', 'Inactivo') as Pla_Est FROM plan_cuenta 
                        WHERE plan_cuenta.Pla_Cod=(SELECT MAX(plan_cuenta.Pla_Cod) FROM plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')) ";
			//echo $sql;
			return $sql;

		case 354:
			$campos = empty($Par_Sql['limits']) ? " COUNT(det_plan.Pld_Cod) AS total" : "det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activo', 'Inactivo') as Pld_Est ";
			$sql = "SELECT $campos
					FROM det_plan
					WHERE  det_plan.Pld_Cdc IN (SELECT det_plan.Pld_Cdc FROM det_plan WHERE det_plan.Pla_Cod = $Par_Sql[codigo] GROUP BY Pld_Cdc HAVING COUNT( * ) >1 ORDER BY Pld_Cod ASC) 
                                        AND 
                                        det_plan.Pla_Cod = $Par_Sql[codigo] $Par_Sql[limits];";
			//echo $sql;  
			return $sql;
		case 355:
			$sql = "SELECT COUNT(det_plan.Pld_Cod) AS cuenta 
                            FROM det_plan 
                        WHERE det_plan.Pld_Cdc IN (
                          SELECT det_plan.Pld_Cdc 
                          FROM det_plan
                          WHERE det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]')) 
                          GROUP BY Pld_Cdc
                          HAVING COUNT( * ) >1
                          ORDER BY Pld_Cod ASC
                        ) AND det_plan.Pla_Cod = (select max(plan_cuenta.Pla_Cod) from plan_cuenta WHERE (plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'))";
			//echo $sql;
			return $sql;

			// Niebla
		case 356:
			$sql = "INSERT INTO formulario_det_plan(Pld_Cod,Foc_Cod) VALUES($Par_Sql[Pld_Cod],$Par_Sql[Foc_Cod_New]);";
			//echo $sql;               
			return $sql;
		case 357:
			$sql = "SELECT formulario_det_plan.Pld_Cod FROM formulario_det_plan 
				INNER JOIN det_plan ON det_plan.Pld_Cod=formulario_det_plan.Pld_Cod
				WHERE Foc_Cod='$Par_Sql[1]' AND det_plan.Pla_Cod='$Par_Sql[0]'
				ORDER BY 
					IF(Pld_Tip='G',Pld_Cdc,CAST( LEFT( Pld_Cdc, LENGTH( Pld_Cdc ) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1) )  ) AS CHAR )),
					IF(Pld_Tip='G', 1, CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0)AS DECIMAL));";
			//echo $sql;               
			return $sql;
		case 358:
			$sql = "SELECT  Pla_Cod, det_plan.Pld_Cod, Pld_Tip, Pld_Cdc, Pld_Des, SUM(Debe) AS Debe, SUM(Haber) AS Haber, IF(COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0)>0,COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0),NULL) AS Acreedor, IF(COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0)>0,COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0),NULL) AS Deudor
				FROM det_plan 
				INNER JOIN (SELECT comprobantes.Com_Cod,  Pec_Cod, Com_Fec, CONCAT(Tia_Abr,'-',CAST(LPAD(MONTH(comprobantes.Com_Fec),2,'0')AS CHAR),'-',CAST(comprobantes.Com_Num AS CHAR)) AS Com_Codigo, Com_Con, Com_Obs, Asi_Cod, asientos.Pld_Cod, Asi_Deh, IF(Asi_Deh='D',Asi_Val,NULL) AS Debe, IF(Asi_Deh='H',Asi_Val,NULL) AS Haber, Com_Est
						FROM comprobantes, asientos, tipo_asien
						WHERE tipo_asien.Tia_Cod=comprobantes.Tia_Cod
							AND comprobantes.Com_Cod=asientos.Com_Cod            
							AND Com_Fec BETWEEN '$Par_Sql[Year]-01-01 00:00:00' AND '$Par_Sql[Year]-12-31'
							AND asientos.Pld_Cod=$Par_Sql[Pld_Cod] AND Com_Est='A' AND Pec_Cod=$Par_Sql[Pec_Cod]
						ORDER BY  Com_Fec, Com_Codigo, Asi_Deh)AS tabla ON det_plan.Pld_Cod=tabla.Pld_Cod
				WHERE det_plan.Pld_Cod=$Par_Sql[Pld_Cod]
				GROUP BY Pld_Cod;";
			//echo $sql;               
			return $sql;
		case 362:
			$sql = "SELECT Emp_Cod, det_plan.Pla_Cod, det_plan.Pld_Cod, Pld_Rec, Pld_Cdc, Pld_Des, Pld_Tip, IF(Pld_Tip='D','Detalle','Grupo') AS Tipo, Pld_Est, codi.Foc_Cod AS Foc_Cod_Old, codi.*  FROM det_plan
                                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod 
                                LEFT JOIN formulario_det_plan ON formulario_det_plan.Pld_Cod=det_plan.Pld_Cod
                                LEFT JOIN (SELECT grupo1.Fog_Nom AS Grupo, grupo0.Fog_Nom AS SubGrupo, formulario_codi.* FROM formulario_codi
                                    INNER JOIN formulario_grupo AS grupo0 ON grupo0.Fog_Cod=formulario_codi.Fog_Cod
                                    INNER JOIN formulario_grupo AS grupo1 ON grupo1.Fog_Cod=grupo0.Fog_Rec
                                    INNER JOIN formulario_grupo AS grupo2 ON grupo2.Fog_Cod=grupo1.Fog_Rec
                                    WHERE grupo2.Fog_Cod='$Par_Sql[1]' ) AS codi ON codi.Foc_Cod=formulario_det_plan.Foc_Cod
                                WHERE  plan_cuenta.Pla_Cod='$Par_Sql[0]' AND Pld_Est='A'
                                ORDER BY Emp_Cod,Pla_Cod,
                                IF(Pld_Tip='G',Pld_Cdc,CAST( LEFT( Pld_Cdc, LENGTH( Pld_Cdc ) - LENGTH(SUBSTRING_INDEX(Pld_Cdc, '.', -1) )  ) AS CHAR )),
                                IF(Pld_Tip='G', 1, CAST((SUBSTRING_INDEX(Pld_Cdc, '.', -1) + 0)AS DECIMAL))
                                ;";
			//echo $sql;               
			return $sql;
		case 361:
			$sql = "SELECT * FROM formulario_grupo WHERE Fog_Rec IS NULL AND Fog_Est='A';";
			//echo $sql;               
			return $sql;
		case 360:
			$sql = "SELECT grupo1.Fog_Nom AS Grupo, CONCAT(grupo1.Fog_Ord, '. ',grupo1.Fog_Nom) AS GrupoOrd, grupo0.Fog_Nom AS SubGrupo, formulario_codi.* FROM formulario_codi
                                    INNER JOIN formulario_grupo AS grupo0 ON grupo0.Fog_Cod=formulario_codi.Fog_Cod
                                    INNER JOIN formulario_grupo AS grupo1 ON grupo1.Fog_Cod=grupo0.Fog_Rec
                                    INNER JOIN formulario_grupo AS grupo2 ON grupo2.Fog_Cod=grupo1.Fog_Rec
                                    WHERE grupo2.Fog_Cod='$Par_Sql[0]'
                                    ORDER BY grupo1.Fog_Ord,grupo0.Fog_Ord,Foc_Num";
			//echo $sql;               
			return $sql;
		case 359:
			$sql = "DELETE FROM formulario_det_plan WHERE Pld_Cod='$Par_Sql[Pld_Cod]' AND Foc_Cod='$Par_Sql[Foc_Cod_Old]';";
			//echo $sql;               
			return $sql;
		case 363:
			$campos = empty($Par_Sql['limits']) ? "COUNT(formulario_codi.Foc_Cod) AS total" : "grupo1.Fog_Nom AS Grupo, CONCAT(grupo1.Fog_Ord, '. ',grupo1.Fog_Nom) AS GrupoOrd, grupo0.Fog_Nom AS SubGrupo, formulario_codi.*";
			$search = $Par_Sql['op_opciones'] == 'd' ? "Foc_Nom LIKE '%$Par_Sql[search]%'" : "Foc_Num LIKE '$Par_Sql[search]%'";
			$sql = "SELECT $campos FROM formulario_codi
                                    INNER JOIN formulario_grupo AS grupo0 ON grupo0.Fog_Cod=formulario_codi.Fog_Cod
                                    INNER JOIN formulario_grupo AS grupo1 ON grupo1.Fog_Cod=grupo0.Fog_Rec
                                    INNER JOIN formulario_grupo AS grupo2 ON grupo2.Fog_Cod=grupo1.Fog_Rec
                                    WHERE grupo2.Fog_Cod='$Par_Sql[Fog_Cod]' AND $search
                                    ORDER BY grupo1.Fog_Ord,grupo0.Fog_Ord,Foc_Num $Par_Sql[limits];";
			//echo $sql."<br/>";               
			return $sql;
		case 364:
			$sql = "SELECT Pla_Cod, Pla_Fec, Pla_Obs, IF (Pla_Est='A','Activo','Inactivo') as Pla_Est FROM plan_cuenta WHERE Emp_Cod='$_SESSION[Ses_Emp_Cod]' " . (isset($Par_Sql[1]) ? $Par_Sql[18] : '') . "
			ORDER BY Pla_Cod DESC;";
			//echo $sql;
			return $sql;
		case 365:
			if ($Par_Sql['pc_opciones'] == "a") {
				$search = "(det_plan.Pld_Des LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "det_plan.Pld_Cdc LIKE '$Par_Sql[search]%'";
			}
			$campos = empty($Par_Sql['limits']) ? " COUNT(det_plan.Pld_Cod) AS total" : "det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF (Pld_Tip='G', 'GRUPO', 'DETALLE') as Pld_Tip, IF (Pld_Est='A', 'Activo', 'Inactivo') as Pld_Est ";
			$order = empty($Par_Sql['limits']) ? "" : "ORDER BY 
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=1, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',1),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=2, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',2),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=3, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',3),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=4, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',4),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=5, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',5),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=6, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',6),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=7, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',7),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=8, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',8),'.',-1) AS DECIMAL),0),
                IF(LENGTH(det_plan.Pld_Cdc)-LENGTH( REPLACE ( det_plan.Pld_Cdc, '.', '') )+1>=9, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(det_plan.Pld_Cdc,'.',9),'.',-1) AS DECIMAL),0)
            ";
			$sql = "SELECT $campos
			FROM det_plan
			INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
			WHERE  $search AND
			det_plan.Pla_Cod = $Par_Sql[sel_planes] AND  plan_cuenta.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
				$order $Par_Sql[limits];";
			//echo $sql;
			return $sql;
		case 366:
			$sql = "SELECT Pla_Cod, Pla_Fec, Pla_Obs, IF (Pla_Est='A','Activo','Inactivo') as Pla_Est FROM plan_cuenta WHERE Emp_Cod='$_SESSION[Ses_Emp_Cod]' 
			AND Pla_Cod=$Par_Sql[codigo];";
			//echo $sql;
			return $sql;
		case 367:
			$sql = "SELECT COUNT(tabla.Pld_Cdc)AS cuenta FROM(SELECT det_plan.Pld_Cdc FROM det_plan WHERE det_plan.Pla_Cod = $Par_Sql[codigo] GROUP BY Pld_Cdc HAVING COUNT( * ) >1 ORDER BY Pld_Cod ASC)AS tabla";
			//echo $sql;
			return $sql;
		case 368:
			$sql = "SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF (Pld_Tip='G', 'GRUPO', 'DETALLE') as Pld_Tip, IF (Pld_Est='A', 'Activo', 'Inactivo') as Pld_Est 
			FROM det_plan
			WHERE  det_plan.Pld_Cdc IN (SELECT det_plan.Pld_Cdc FROM det_plan WHERE det_plan.Pla_Cod = $Par_Sql[codigo] GROUP BY Pld_Cdc HAVING COUNT( * ) >1 ORDER BY Pld_Cod ASC)
			AND
			det_plan.Pla_Cod = $Par_Sql[codigo] ORDER BY det_plan.Pld_Cdc";
			//echo $sql;
			return $sql;
		case 371:
			$sql = "SELECT  Pla_Cod, det_plan.Pld_Cod, Pld_Tip, Pld_Cdc, Pld_Des, SUM(Debe) AS Debe, SUM(Haber) AS Haber, IF(COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0)>0,COALESCE(SUM(Debe),0)-COALESCE(SUM(Haber),0),NULL) AS Acreedor, IF(COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0)>0,COALESCE(SUM(Haber),0)-COALESCE(SUM(Debe),0),NULL) AS Deudor
				FROM det_plan 
				INNER JOIN (SELECT comprobantes.Com_Cod,  Pec_Cod, Com_Fec, CONCAT(Tia_Abr,'-',CAST(LPAD(MONTH(comprobantes.Com_Fec),2,'0')AS CHAR),'-',CAST(comprobantes.Com_Num AS CHAR)) AS Com_Codigo, Com_Con, Com_Obs, Asi_Cod, asientos.Pld_Cod, Asi_Deh, IF(Asi_Deh='D',Asi_Val,NULL) AS Debe, IF(Asi_Deh='H',Asi_Val,NULL) AS Haber, Com_Est
						FROM comprobantes, asientos, tipo_asien
						WHERE tipo_asien.Tia_Cod=comprobantes.Tia_Cod
							AND comprobantes.Com_Cod=asientos.Com_Cod " . (COUNT($Par_Sql[Asi_Cods]) ? " AND Asi_Cod NOT IN (" . implode(',', $Par_Sql['Asi_Cods']) . ")" : '') . "           
							AND Com_Fec <= '$Par_Sql[Fin]'
							AND asientos.Pld_Cod=$Par_Sql[Pld_Cod] AND Com_Est='A' AND Pec_Cod=$Par_Sql[Pec_Cod]
						ORDER BY  Com_Fec, Com_Codigo, Asi_Deh)AS tabla ON det_plan.Pld_Cod=tabla.Pld_Cod
				WHERE det_plan.Pld_Cod=$Par_Sql[Pld_Cod]
				GROUP BY Pld_Cod;";
			//echo $sql;               
			return $sql;
	}
}
