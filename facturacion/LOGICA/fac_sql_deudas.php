<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-08-22
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package academico.LOGICA
 * 
 * id 1: buscar al cliente si esta en tabla estudiante para saber si es cliente de institución o cliente de algun servicio
 * id 2: Consulta los pagos realizados por el estudiante
 * id 3: Consulta la cantidad de dias de retrazo de la deuda
 * id 4: Consulta si ya se encuentra agregado un rubro recursivo (INTERES)
 * id 5: Inserta las deudas de los clientes
 * id 6: cargar asignatura en base al semestre y al codigo del cliente
 * id 7: Consulta las deudas del cleinte en base al cliente
 * id 8: Consulta de los rubros recursivos, especialmente INTERES
 * id 9: Consulta los pagos realizados por el cliente para rubros RECURSIVOS
 * id 10: obtener el ultimo deu_int que tiene el cliente en el Nge_Cod de deudas
 * id 11: busca la cabecera de los rubros
 * id 15: obtener un producto en especial
 * id 21: Consulta del cliente si es una persona por apellidos
 * id 22: Consulta del personal por cedula
 * id 23: Consulta de los datos del cliente
 * id 24: Consulta de los datos del cliente por empresa y codigo de persona
 * id 48: Consulta la información de empresa y sucursal por el codigo de sucursal
 * id 63: Obtener un solo rubro por el codigo del producto
 * id 73: Consulta las becas en base al codigo del cliente
 * id 81: actualizar datos de deudas cuando es estudiante
 * id 170: Consulta las deudas que ya han sido agregadas al cliente
 * id 646: obtener los estudiantes de un semestre
 * id 647: carga deuda dependiendo del cliente, producto y ngcod
 * id 732: obtener datos del semestre
 */
function sentencias_deu($id,$Par_Sql)
{
	switch($id)
	{
		/**
		 * buscar al cliente si esta en tabla estudiante para saber si es
		 * cliente de institución o cliente de algun servicio
		 */
		case 1:
			$sql="SELECT COUNT(cliente.Cli_Cod) AS 'count' FROM persona
				  INNER JOIN estudiante ON persona.Prs_Cod=estudiante.Prs_Cod
				  INNER JOIN cliente ON persona.Prs_Cod=cliente.Prs_Cod
				  WHERE
				  cliente.Cli_Cod = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Consulta los pagos realizados por el estudiante
		 */
		case 2:
			$sql = "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
					ventas_det.Vet_Cod AND ventas.Cli_Cod = '$Par_Sql[0]' AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Cnt_Cod 
					= '$Par_Sql[2]' AND Asi_Int = '$Par_Sql[3]' AND ventas.Vet_Est = 'A' AND Vet_Int = '$Par_Sql[4]'";
			return $sql;
		break;
		
		/**
		 * Consulta la cantidad de dias de retrazo de la deuda
		 */
		case 3:
			$sql = "SELECT datediff(Deu_Fec, now()) as Mora, Deu_Fec FROM deudas WHERE Cli_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1] AND Cnt_Cod = $Par_Sql[2] 
							AND Asi_Int = $Par_Sql[3] AND Deu_Rec = 0 AND deudas.Deu_Int = $Par_Sql[4]";
			return $sql;
		break;
		
		/**
		 * Consulta si ya se encuentra agregado un rubro recursivo (INTERES)
		 */
		case 4:
			$sql= "SELECT Deu_Reg, Deu_Fec, Pro_Cod, datediff(Deu_Reg, now()) as Dias_Mora, Deu_Val, Deu_Obs FROM deudas 
							WHERE Cli_Cod = $Par_Sql[0] AND Cnt_Cod = $Par_Sql[1] 
							AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3] AND Deu_Int = $Par_Sql[4]";
			return $sql;
		break;
		
		/**
		 * Inserta las deudas de los clientes 
		 */
		case 5: 
			$sql = "INSERT INTO deudas(Pro_Cod, cnt_Cod, Cli_Cod, Deu_Val, Deu_Reg, Deu_Fec, Bec_Cod, Deu_Rec, Asi_Int,Deu_Int) 
					VALUES($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], $Par_Sql[7], $Par_Sql[8],$Par_Sql[9])";
			return $sql;
		break;
		
		/**
		 * cargar asignatura en base al semestre y al codigo del cliente
		 */
		case 6:
			$sql= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Reg = '$Par_Sql[1]' WHERE Cnt_Cod = $Par_Sql[2] AND 
						Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6] AND Deu_Int = $Par_Sql[7]";
			return $sql;
		break;
		
		/**
		 * Consulta las deudas del cleinte en base al cliente
		 */
		case 7:
			$sql="SELECT
				deudas.Pro_Cod,
				producto.Pro_Ide,
				item.Ite_Lar,
				deudas.Deu_Val,
				deudas.Deu_Fec,
				deudas.Nge_Cod,
				deudas.Deu_Obs,
				deudas.Cnt_Cod,
				producto.Iva_Cod,
				iva.Iva_Por,
				deudas.Bec_Cod,
				deudas.Deu_Rec,
				deudas.Asi_Int,
				deudas.Deu_Int
			FROM
				producto
			INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod)
			INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
			INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			WHERE
				deudas.Cli_Cod = $Par_Sql[0] AND Deu_Rec = 0
			ORDER BY
				deudas.Deu_Fec";
			return $sql;
		break;
		
		/**
		 * Consulta de los rubros recursivos, especialmente INTERES
		 */
		case 8:
			$sql = "SELECT deudas.Pro_Cod, Pro_Ide, Deu_Val, Deu_Fec, Ite_Lar, producto.Iva_Cod, Iva_Por, Nge_Cod, Deu_Rec, Asi_Int,Cnt_Cod,Deu_Int FROM 
						deudas, producto, item, iva WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Iva_Cod = iva.Iva_Cod AND				
						producto.Ite_Cod = item.Ite_Cod AND Cli_Cod = '$Par_Sql[0]' AND Cnt_Cod = 
						'$Par_Sql[1]' AND Asi_Int = '$Par_Sql[2]' AND Deu_Rec = '$Par_Sql[3]' AND Deu_Int = '$Par_Sql[4]'";
			return $sql;
		break;
		
		/**
		 * Consulta los pagos realizados por el cliente para rubros RECURSIVOS
		 */
		case 9:
			$sql = "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = ventas_det.Vet_Cod 
					AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Cnt_Cod 
					= $Par_Sql[2] AND ventas_det.Vet_Rec = '$Par_Sql[3]' AND Asi_Int = '$Par_Sql[4]' AND ventas.Vet_Est = 'A' AND Vet_Int='$Par_Sql[5]'";
			return $sql;
		break;
		
		/**
		 * obtener el ultimo deu_int que tiene el cliente en el Nge_Cod de deudas
		 */
		case 10:
			$sql="SELECT Deu_Int FROM deudas WHERE Cli_Cod='$Par_Sql[0]' AND Nge_Cod='$Par_Sql[1]' ORDER BY Deu_Int DESC LIMIT 0,1;";
			return $sql;
		break;
		
		/**
		 * busca la cabecera de los rubros
		 */
		case 11:
			$sql = "SELECT DISTINCT
			item.Ite_Cor,
			deudas.Pro_Cod
			FROM
			notasgener
			INNER JOIN deudas ON (notasgener.Nge_Cod = deudas.Nge_Cod)
			INNER JOIN producto ON (deudas.Pro_Cod = producto.Pro_Cod)
			INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
			WHERE
			Deu_Rec = 0 AND
			Deu_Val <> 0 AND
			notasgener.Sem_Cod = $Par_Sql[0]
			ORDER BY
			Pro_Cod";
			return $sql;
		break;
		
		/**
		 * obtener un producto en especial
		 */
		case 15:
			$sql="SELECT precios.Tpv_Cod, item.Ite_Cod, item.Ite_Est, item.Ite_Cor, item.Ite_Lar, marca.Mar_Cod,
			marca.Mar_Des, adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor, iva.Iva_Cod, iva.Iva_Por,
			producto.Pro_Bar, producto.Pro_Obs, producto.Pro_Cod, producto.Pro_Est, producto.Pro_Gen, producto.Pro_Cdc,
			producto.Pro_Sec, stock.Stk_Can, precios.Pre_Pvp
			FROM
			categorias
			INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
			INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
			INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
			INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
			INNER JOIN tipo_preci ON (precios.Tpv_Cod = tipo_preci.Tpv_Cod)
			INNER JOIN stock ON (precios.Pro_Cod = stock.Pro_Cod)
			INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
			WHERE precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Est = 'A'
			AND adquisicio.Adq_Cor='S' AND producto.Pro_Cod = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Consulta del cliente si es una persona por apellidos
		 */
		case 21:
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape,
			persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado')
			as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'
			AND Emp_Cod = '$Par_Sql[1]'
			ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			return $sql;
		break;
		
		/**
		 * Consulta del personal por cedula
		 */
		case 22:
			$sql = "SELECT cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, IF (cliente.Cli_Est='A','Activo','Retirado') as Cli_Est FROM persona, cliente WHERE cliente.Prs_Cod = persona.Prs_Cod  AND persona.Prs_Ced = '$Par_Sql[0]' AND Emp_Cod = '$Par_Sql[1]' ORDER BY	persona.Prs_Ape, persona.Prs_Nom ASC";
			return $sql;
		break;
		
		/**
		 * Consulta de los datos del cliente
		 */
		case 23:
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir,
			persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod
			FROM cliente, persona WHERE persona.Prs_Cod = cliente.Prs_Cod AND cliente.Cli_Cod = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Consulta de los datos del cliente por empresa y codigo de persona
		 */
		case 24:
			$sql = "SELECT 
					persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir,
					persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, cliente.Cli_Cod
				FROM cliente, persona 
				WHERE 
				persona.Prs_Cod = cliente.Prs_Cod AND
				cliente.Emp_Cod = '$Par_Sql[0]' AND
				cliente.Prs_Cod = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Selecionar el numero maximo del codigo del producto
		 * @fecha: 26-10-2002
		 */
		case 46:
			$sql="SELECT Pro_Ide, Col_Eli, Col_Cad FROM confi_teso WHERE Emp_Cod = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Consulta la provicia y pais de la ciudad de la sucursal
		 */
		case 47:
			$sql="SELECT
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
		 * Consulta la información de empresa y sucursal por el codigo de sucursal
		 */
		case 48:
			$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
		break;
				
		/**
		 * Consulta los datos del usuario
		 */
		case 49:
			$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
		break;
		
		/**
		 * Verifica si a un producto se le debe calcular el interes
		 * @fecha: 26-10-2002 
		 */
		case 51:
			$sql = "SELECT prod_inter.Pro_Cod FROM prod_inter WHERE Pro_Cod = $Par_Sql[0]";
			return $sql;
		break;
		
		/**
		 * Consulta la cantidad de dias de retrazo de la deuda
		 * @fecha: 26-10-2002 
		 */
		case 54:
			$sql = "SELECT datediff(Deu_Fec, now()) as Mora, Deu_Fec FROM deudas WHERE Cli_Cod = $Par_Sql[0] AND Pro_Cod = $Par_Sql[1] AND Nge_Cod = $Par_Sql[2] 
							AND Asi_Int = $Par_Sql[3] AND Deu_Rec = 0 AND deudas.Deu_Int = $Par_Sql[4]";
			return $sql;
		break;
		
		/**
		 * Consultar los rubros destinados para el interes 
		 * @fecha 22-11-2012
		 */
		case 56:
			$sql= "SELECT interes.Pro_Cod, Int_Por, Int_Dia 
					FROM interes 
					INNER JOIN producto ON interes.Pro_Cod = producto.Pro_Cod
					INNER JOIN item ON producto.Ite_Cod = item.Ite_Cod
					INNER JOIN categorias ON item.Cat_Cod = categorias.Cat_Cod
					WHERE
					categorias.Emp_Cod = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Consulta si ya se encuentra agregado un rubro recursivo (INTERES)
		 * @fecha: 26-10-2002 
		 */
		case 57:
			$sql= "SELECT Deu_Reg, Deu_Fec, Pro_Cod, datediff(Deu_Reg, now()) as Dias_Mora, Deu_Val, Deu_Obs FROM deudas 
							WHERE Cli_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] 
							AND Asi_Int = $Par_Sql[2] AND Deu_Rec = $Par_Sql[3] AND Deu_Int = $Par_Sql[4]";
			return $sql;
		break;
		
		/**
		 * Consulta de los rubros recursivos, especialmente INTERES
		 * @fecha: 26-10-2002 
		 */
		case 58:
			$sql = "SELECT deudas.Pro_Cod, Pro_Ide, Deu_Val, Deu_Fec, Ite_Lar, producto.Iva_Cod, Iva_Por, Nge_Cod, Deu_Rec, Asi_Int,Cnt_Cod,Deu_Int FROM 
						deudas, producto, item, iva WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Iva_Cod = iva.Iva_Cod AND				
						producto.Ite_Cod = item.Ite_Cod AND Cli_Cod = '$Par_Sql[0]' AND Nge_Cod = '$Par_Sql[1]' AND Asi_Int = '$Par_Sql[2]' AND Deu_Rec = '$Par_Sql[3]' AND Deu_Int = '$Par_Sql[4]'";
			return $sql;
		break;
		
		/**
		 * cargar semestre en base al periodo y al cliente 
		 */
		case 60:
			$sql= "SELECT DISTINCT
			view_cursos_mal.Per_Int,
			view_periodos_suc.Ann_Ini,
			view_periodos_suc.Mes_Ini,
			view_periodos_suc.Ann_Fin,
			view_periodos_suc.Mes_Fin,
			view_periodos_suc.Per_Fea,
			view_periodos_suc.Per_Fef,
			modalidad.Mod_Des,
			carreras.Car_Nom,
			view_cursos_mal.Sem_Nom,
			view_cursos_mal.Sem_No2,
			view_periodos_suc.Suc_Des,
			etapas.Eta_Des,
			view_cursos_mal.Sem_Cod
			FROM
			persona
			INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
			INNER JOIN estudiante ON (persona.Prs_Cod = estudiante.Prs_Cod)
			INNER JOIN matriculas ON (estudiante.Est_Int = matriculas.Est_Int)
			INNER JOIN view_cursos_mal ON (matriculas.Sem_Cod = view_cursos_mal.Sem_Cod)
			INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
			INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int)
			INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = modalidad.Mod_Cod)
			INNER JOIN etapas ON (carreras.Eta_Cod = etapas.Eta_Cod)
			WHERE
			'$Par_Sql[0]' BETWEEN Per_Fea AND Per_Fec AND
			cliente.Cli_Cod = $Par_Sql[1] AND
			view_cursos_mal.Car_Int = $Par_Sql[2] AND
			matriculas.Mat_Est = 'A'";
			return $sql;
		break;
		
		/**
		 * Consulta de los rubros sin precio
		 */
		case 62:
			$sql = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar,categorias.Emp_Cod
				FROM producto, item, categorias 
				WHERE producto.Ite_Cod = item.Ite_Cod AND item.Ite_Lar LIKE '%$Par_Sql[0]%' AND item.`Cat_Cod` = categorias.`Cat_Cod` AND
				producto.Pro_Est = 'A' AND categorias.Emp_Cod = $Par_Sql[3]
				AND producto.Pro_Cod NOT IN 
				(SELECT deudas.Pro_Cod FROM deudas, notasgener 
				WHERE deudas.Nge_Cod = notasgener.Nge_Cod AND deudas.Cli_Cod = $Par_Sql[1] 
				AND notasgener.Sem_Cod = $Par_Sql[2])";
			return $sql;
		break;
		
		/**
		 * Obtener un solo rubro por el codigo del producto
		 */
		case 63:
			$sql = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cor, item.Ite_Lar FROM producto, item
			WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = '$Par_Sql[0]' AND producto.Pro_Est = 'A'";
			return $sql;
		break;
		
		/**
		 * cargar asignatura en base al semestre y al codigo del cliente 
		 * @fecha: 26-10-2002 
		 */
		case 64:
			$sql= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Reg = '$Par_Sql[1]' WHERE Nge_Cod = $Par_Sql[2] AND 
						Cli_Cod = $Par_Sql[3] AND Asi_Int = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6] AND Deu_Int = $Par_Sql[7]";
			return $sql;
		break;
		
		/**
		 * Registra deudas de los clientes
		 * @fecha: 26-10-2002 
		 */
		case 65:
			$sql = "INSERT INTO deudas (Pro_Cod, Nge_Cod, Cli_Cod, Deu_Val, Deu_Reg, Deu_Fec, Deu_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]')";
			return $sql;
		break;
		
		/**
		 * obtener datos de una deuda en especial
		 */
		case 66:
			$sql = "SELECT `producto`.`Pro_Cod`,`producto`.`Pro_Ide`,`item`.`Ite_Cor`,`item`.`Ite_Lar`,`deudas`.`Deu_Reg`,`deudas`.`Deu_Fec`,`deudas`.`Deu_Obs`
			FROM `producto`
			INNER JOIN `item` ON `producto`.`Ite_Cod` = `item`.`Ite_Cod`
			INNER JOIN `deudas` ON `producto`.`Pro_Cod` = `deudas`.`Pro_Cod` 
			WHERE `producto`.`Pro_Cod` = $Par_Sql[0] 
			AND `producto`.`Pro_Est` = 'A' AND `deudas`.`Nge_Cod` = $Par_Sql[1] AND `deudas`.`Deu_Int` = $Par_Sql[2]";
			return $sql;
		break;
		
		/**
		 * Consulta los pagos realizados por el estudiante 
		 */
		case 68:
			$sql = "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = 
					ventas_det.Vet_Cod AND ventas.Cli_Cod = '$Par_Sql[0]' AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
					= '$Par_Sql[2]' AND Asi_Int = '$Par_Sql[3]' AND ventas.Vet_Est = 'A' AND Vet_Int = '$Par_Sql[4]'";
			return $sql;
		break;
		
		/**
		 * Consulta los pagos realizados por el cliente para rubros RECURSIVOS
		 * @fecha: 26-10-2002 
		 */
		case 69:
			$sql = "SELECT sum(ventas_det.Vet_Imp) as Vet_Imp FROM ventas, ventas_det WHERE ventas.Vet_Cod = ventas_det.Vet_Cod 
					AND ventas.Cli_Cod = $Par_Sql[0] AND ventas_det.Pro_Cod = '$Par_Sql[1]' AND ventas_det.Nge_Cod 
					= $Par_Sql[2] AND ventas_det.Vet_Rec = '$Par_Sql[3]' AND Asi_Int = '$Par_Sql[4]' AND ventas.Vet_Est = 'A' AND Vet_Int='$Par_Sql[5]'";
			return $sql;
		break;
		
		/**
		 * Consulta las becas en base al codigo del cliente 
		 */
		case 73:
			$sql = "SELECT becas.Bec_Cod, det_becas.Pro_Cod, Bec_Pot, Bec_Por, Tib_Ini FROM becas, matriculas, estudiante,
			persona, cliente, det_becas, tipo_beca WHERE becas.Mat_Int = matriculas.Mat_Int AND matriculas.Est_Int =
			estudiante.Est_Int AND persona.Prs_Cod = estudiante.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND
			becas.Bec_Cod = det_becas.Bec_Cod AND becas.Tib_Cod = tipo_beca.Tib_Cod AND
			cliente.Cli_Cod = $Par_Sql[0] AND det_becas.Pro_Cod = $Par_Sql[1]
			AND matriculas.Sem_Cod = $Par_Sql[2]  AND becas.Bec_Est = 'A'";
			return $sql;
		break;
		
		/**
		 * Cargar el Nge_Cod dependiendo del semestre
		 * @fecha: 26-10-2002
		 */
		case 75:
			$sql= "SELECT notasgener.Nge_Cod FROM matriculas, semestres, persona, cliente, estudiante, notasgener WHERE
			matriculas.Sem_Cod = semestres.Sem_Cod AND matriculas.Est_Int = estudiante.Est_Int AND persona.Prs_Cod =
			estudiante.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod AND
			matriculas.Mat_Int = notasgener.Mat_Int AND cliente.Cli_Cod = $Par_Sql[0]
			AND semestres.Sem_Cod = $Par_Sql[1]";
			return $sql;
		break;
		
		/**
		 * Consulta la beca asignada a un rubro 
		 */
		case 76:
	   		$sql = "SELECT Bec_Pot, Bec_Por, 
	   					   tipo_beca.Tib_Ini, 
	   					   tipo_beca.Tib_Cod, 
	   					   Mat_Int, 
	   					   tipo_beca.Tib_Des 
	   				FROM becas, 
	   					det_becas, 
	   					tipo_beca 
	   				WHERE becas.Bec_Cod = det_becas.Bec_Cod 
	   					AND becas.Tib_Cod = tipo_beca.Tib_Cod AND becas.Bec_Cod = '$Par_Sql[0]' AND Pro_Cod = '$Par_Sql[1]'";
	   		return $sql;
	   break;
		
		/**
		 * Consulta de las carreras las cuales ha cursado un estudiante
		 */
		case 78:
			$sql = "SELECT carreras.Car_Int, Car_Nom FROM carreras, estudiante, matriculas, promocione, semestres, persona, cliente WHERE estudiante.Est_Int = matriculas.Est_Int AND matriculas.Sem_Cod = semestres.Sem_Cod
			AND semestres.Pro_Cod = promocione.Pro_Cod AND carreras.Car_Int = promocione.Car_Int AND persona.Prs_Cod = estudiante.Prs_Cod
			AND cliente.Prs_Cod = persona.Prs_Cod AND cliente.Cli_Cod = '$Par_Sql[0]' AND cliente.Cli_Est = 'A' GROUP BY Car_Int
			ORDER BY carreras.Car_Nom";
			return $sql;
		break;
		
		/**
		 * actualizar deudas
		 * @fecha: 07-01-2013
		 */
		case 80:
			$sql= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Fec = '$Par_Sql[1]', Deu_Obs = '$Par_Sql[2]' 
			WHERE Nge_Cod = $Par_Sql[3] AND	Cli_Cod = $Par_Sql[4] AND Asi_Int = $Par_Sql[5] AND Pro_Cod = $Par_Sql[6] AND Deu_Rec = $Par_Sql[7] AND Cnt_Cod= $Par_Sql[8] AND Deu_Int=$Par_Sql[9]";
			return $sql;
		break;
		
		/**
		 * actualizar datos de deudas cuando es estudiante 
		 */
		case 81:
			$sql = "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Fec = '$Par_Sql[1]', Deu_Obs = '$Par_Sql[2]' 
			WHERE Nge_Cod = $Par_Sql[3] AND	Cli_Cod = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Int=$Par_Sql[6]";
			return $sql;
		break;
		
		/**
		 * actualizar deudas
		 * @fecha: 07-01-2013
		 */
		case 82:
			$sql= "UPDATE deudas SET Deu_Val = $Par_Sql[0], Deu_Fec = '$Par_Sql[1]', Deu_Obs = '$Par_Sql[2]'
			WHERE Nge_Cod = $Par_Sql[3] AND	Cli_Cod = $Par_Sql[4] AND Pro_Cod = $Par_Sql[5] AND Deu_Rec = $Par_Sql[6] AND Deu_Int=$Par_Sql[7]";
			return $sql;
		break;
		
		/**
		 * Consulta el codigo de la matricula obtenida en el semestre actual (Normalmente debe ser un solo registro), solo las matriculas de tipo N=normal
		 * @fecha: 26-10-2002
		 */
		case 168:
			$sql = "SELECT matriculas.Mat_Int, matriculas.Sem_Cod, Nge_Cod, matriculas.Pem_Cod FROM matriculas, estudiante, persona,
			cliente, semestres, periodos, notasgener WHERE matriculas.Est_Int = estudiante.Est_Int AND
			estudiante.Prs_Cod = persona.Prs_Cod AND persona.Prs_Cod = cliente.Prs_Cod AND matriculas.Sem_Cod =
			semestres.Sem_Cod AND semestres.Per_Int = periodos.Per_Int AND matriculas.Mat_Int = notasgener.Mat_Int
			AND notasgener.Sem_Cod = semestres.Sem_Cod
			AND cliente.Cli_Cod = $Par_Sql[1] AND matriculas.Mat_Est='$Par_Sql[2]' AND matriculas.Mat_For = 'N'";
			return $sql;
		break;
		
		/**
		 * Carga todos los costos menores o iguales a partir de una fecha para su generación
		 */
		case 169:
			$sql = "SELECT costos.Tio_Cod, Pro_Cod, Cos_Pre, Cos_Gen, Cos_Fec FROM costos, tipo_costo WHERE costos.Tio_Cod
			= tipo_costo.Tio_Cod AND Sem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Tio_Car = '$Par_Sql[2]' AND Cos_Est='A'";
			return $sql;
		break;
		
		/**
		 * Consulta las deudas que ya han sido agregadas al cliente
		 */
		case 170:
			$sql = "SELECT deudas.Pro_Cod FROM deudas, notasgener WHERE deudas.Nge_Cod = notasgener.Nge_Cod
			AND notasgener.Sem_Cod = $Par_Sql[0] AND deudas.Pro_Cod = $Par_Sql[1] AND deudas.Cli_Cod = $Par_Sql[2]";
			return $sql;
		break;
		
		/**
		 * Inserta las deudas de los clientes 
		 * @fecha: 26-10-2002
		 */
		case 171: 
			$sql = "INSERT INTO deudas(Pro_Cod, Nge_Cod, Cli_Cod, Deu_Val, Deu_Reg, Deu_Fec, Bec_Cod, Deu_Rec, Deu_Int) 
					VALUES($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], '$Par_Sql[4]', '$Par_Sql[5]', $Par_Sql[6], $Par_Sql[7], $Par_Sql[8])";
			return $sql;
		break;
		
		/**
		 *
		 */
		case 196:
			$sql="SELECT Pro_Cod, Cos_Pre, Cos_Gen FROM costo_matr WHERE Pem_Cod = $Par_Sql[0] AND Cos_Gen <= '$Par_Sql[1]' AND Sem_Cod = $Par_Sql[2] AND Cos_Est='A'";
			return $sql;
		break;
			
		/**
		 * Verifica si la asignatura de un cliente especifico, esta registrada en un semestre 
		 *
		 */
		case 185:
			$sql = "SELECT notasgedet.Nge_Cod, notasgener.Mat_Int, notasgedet.Nge_Tip, notasgedet.Nge_Est FROM notasgener INNER JOIN notasgedet ON (notasgener.Nge_Cod
			= notasgedet.Nge_Cod) INNER JOIN matriculas ON (notasgener.Mat_Int = matriculas.Mat_Int) INNER JOIN estudiante ON (matriculas.Est_Int = estudiante.Est_Int)
			INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod) INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod) WHERE
			notasgener.Sem_Cod = $Par_Sql[0] AND cliente.Cli_Cod = $Par_Sql[1] AND notasgedet.Nge_Tip = '$Par_Sql[2]'";
			return $sql;
		break;
		
		/**
		 * Consulta el detalle academico de la deuda
		 *  
		 */
		case 259:
			$sql="SELECT DISTINCT
				view_cursos_mal.Sem_Cod,
				view_cursos_mal.Sem_Nom,
				view_cursos_mal.Sem_No2,
				view_periodos_suc.Ann_Ini,
				view_periodos_suc.Mes_Ini,
				view_periodos_suc.Ann_Fin,
				view_periodos_suc.Mes_Fin,
				view_periodos_suc.Per_Int,
				view_periodos_suc.Suc_Des,
				modalidad.Mod_Des,
				etapas.Eta_Des,
				carreras.Car_Nom
				FROM
				view_cursos_mal
				INNER JOIN view_periodos_suc ON (view_cursos_mal.Per_Int = view_periodos_suc.Per_Int)
				INNER JOIN matriculas ON (view_cursos_mal.Sem_Cod = matriculas.Sem_Cod)
				INNER JOIN estudiante ON (matriculas.Est_Int = estudiante.Est_Int)
				INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod)
				INNER JOIN cliente ON (persona.Prs_Cod = cliente.Prs_Cod)
				INNER JOIN notasgener ON (matriculas.Mat_Int = notasgener.Mat_Int)
				INNER JOIN modalidad ON (view_periodos_suc.Mod_Cod = modalidad.Mod_Cod)
				INNER JOIN etapas ON (view_periodos_suc.Eta_Cod = etapas.Eta_Cod)
				INNER JOIN mallacurri ON (view_cursos_mal.Mal_Cod = mallacurri.Mal_Cod)
				INNER JOIN carreras ON (mallacurri.Car_Int = carreras.Car_Int)
				WHERE
				cliente.Cli_Cod = $Par_Sql[0] AND
				notasgener.Nge_Cod = $Par_Sql[1]
				GROUP BY
				view_cursos_mal.Sem_Cod,
				view_cursos_mal.Sem_Nom,
				view_cursos_mal.Sem_No2,
				view_periodos_suc.Ann_Ini,
				view_periodos_suc.Mes_Ini,
				view_periodos_suc.Ann_Fin,
				view_periodos_suc.Mes_Fin,
				view_periodos_suc.Per_Int,
				view_periodos_suc.Suc_Des,
				modalidad.Mod_Des,
				etapas.Eta_Des,
				carreras.Car_Nom";
			return $sql;
		break;
		
		/**
		 * Consulta las deudas del cleinte en base a la modalidad, etapa, carrera, periodo 
		 * 
		 */
		case 263:
			$sql="SELECT
					deudas.Pro_Cod,
					producto.Pro_Ide,
					item.Ite_Lar,
					deudas.Deu_Val,
					deudas.Deu_Fec,
					deudas.Nge_Cod,
					deudas.Deu_Obs,
					deudas.Cnt_Cod,
					producto.Iva_Cod,
					iva.Iva_Por,
					deudas.Bec_Cod,
					deudas.Deu_Rec,
					deudas.Asi_Int,
					deudas.Deu_Int
				FROM
					producto
					INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod)
					INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
					INNER JOIN notasgener ON (deudas.Nge_Cod = notasgener.Nge_Cod)
					INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
				WHERE
					deudas.Cli_Cod = $Par_Sql[0] AND Deu_Rec = 0
				ORDER BY
					deudas.Deu_Fec";
			return $sql;
		break;
		
		/**
		 * Consultar si Bec_Cod esta NULL
		 * 
		 */
		case 383:
			$sql="SELECT Pro_Cod, Nge_Cod, Cli_Cod, Deu_Int FROM deudas WHERE Pro_Cod=$Par_Sql[0] AND Nge_Cod=$Par_Sql[1] AND Cli_Cod=$Par_Sql[2] AND Bec_Cod IS NULL";
			return $sql;
		break;
		
		/**
		 * Consultar si el producto se encuentra asignado en becas
		 */
		case 384:
			$sql="SELECT becas.Bec_Cod, det_becas.Pro_Cod FROM becas, det_becas
			WHERE becas.Bec_Cod=det_becas.Bec_Cod AND becas.Mat_Int=$Par_Sql[0] AND det_becas.Pro_Cod=$Par_Sql[1]";
			return $sql;
		break;
		
		/**
		 * Baja de la deuda registrada en la tabla deudas
		 * 
		 */
		case 385:
			$sql = "DELETE FROM deudas WHERE Pro_Cod = $Par_Sql[0] AND Nge_Cod = $Par_Sql[1] AND Cli_Cod = $Par_Sql[2] AND Deu_Int=  $Par_Sql[3]";
			return $sql;
		break;
		
		/**
		 * Busca codigo del Item po el codigo del producto 
		 */
		case 462:
			$sql = "SELECT producto.Pro_Cod, producto.Pro_Ide, item.Ite_Cod, item.Ite_Cor, item.Ite_Lar, Pro_Obs
			FROM item, producto WHERE producto.Ite_Cod = item.Ite_Cod AND producto.Pro_Cod = $Par_Sql[0]";
			return $sql;
		break;
		
		/**
		 * obtener los estudiantes de un semestre 
		 */
		case 646:
			$sql = "SELECT persona.Prs_Ape, persona.Prs_Nom, matriculas.Sem_Cod, cliente.Cli_Cod, persona.Prs_Cod, notasgener.Nge_Cod
			FROM matriculas, persona, estudiante, cliente, notasgener
			WHERE notasgener.Sem_Cod= '$Par_Sql[0]' AND matriculas.Est_Int=estudiante.Est_Int AND cliente.Prs_Cod = persona.Prs_Cod
			AND notasgener.Mat_Int = matriculas.Mat_Int
			AND estudiante.Prs_Cod=persona.Prs_Cod ORDER BY  persona.Prs_Ape, persona.Prs_Nom";
			return $sql;
		break;
		
		/**
		 * carga deuda dependiendo del cliente
		 */
		case 647:
			$sql = "SELECT deudas.Deu_Val, deudas.Pro_Cod, deudas.Asi_Int, deudas.Nge_Cod, Bec_Cod,deudas.Deu_Int FROM deudas,
			producto, item, notasgener, semestres, periodos, niveles, modalidad, promocione, carreras
			WHERE deudas.Pro_Cod = producto.Pro_Cod AND producto.Ite_Cod = item.Ite_Cod AND
			deudas.Nge_Cod = notasgener.Nge_Cod AND notasgener.Sem_Cod = semestres.Sem_Cod
			AND semestres.Per_Int = periodos.Per_Int AND semestres.Niv_Cod = niveles.Niv_Cod
			AND periodos.Mod_Cod = modalidad.Mod_Cod AND semestres.Pro_Cod = promocione.Pro_Cod AND
			carreras.Car_Int = promocione.Car_Int AND deudas.Cli_Cod = '$Par_Sql[0]'  AND deudas.Pro_Cod='$Par_Sql[1]' AND deudas.Nge_Cod='$Par_Sql[2]' AND deudas.Asi_Int=$Par_Sql[3]
			AND Deu_Rec = 0";
			return $sql;
		break;
		
		/**
		 * insertar nueva deuda al cliente - estudiante
		 * @fecha: 26-06-2013
		 */
		case 650:
			$sql="INSERT INTO deudas(Pro_Cod,Cli_Cod,Deu_Val,Deu_Reg,Deu_Fec,Nge_Cod,Deu_Int)
			VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]')";
			return $sql;
		break;
		
		/**
		 * obtener una deuda en especial
		 */
		case 651:
			$sql="SELECT
				deudas.Pro_Cod,
				producto.Pro_Ide,
				item.Ite_Lar,
				deudas.Deu_Val,
				deudas.Deu_Fec,
				deudas.Nge_Cod,
				deudas.Deu_Obs,
				deudas.Cnt_Cod,
				producto.Iva_Cod,
				iva.Iva_Por,
				deudas.Bec_Cod,
				deudas.Deu_Rec,
				deudas.Asi_Int,
				deudas.Deu_Int
				FROM
				producto
				INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod)
				INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
				INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
				WHERE
				deudas.Cli_Cod = $Par_Sql[0] AND Deu_Rec = 0 AND
				Cnt_Cod = $Par_Sql[1] AND Deu_Int=$Par_Sql[2]
				ORDER BY
				deudas.Deu_Fec";
			return $sql;
		break;
		
		/**
		 * obtener datos de un contrato
		 * @fecha 02-11-2012
		 */
		case 652:
			$sql="SELECT contratos.Cnt_Cod,	contratos.Cli_Cod,	contratos.Tic_Cod,contratos.Usu_Cod,contratos.Cnt_Fec,
			contratos.Cnt_Sys,contratos.Cnt_Rep,contratos.Cnt_Ini,contratos.Cnt_Fin,contratos.Cnt_Est,contratos.Cnt_Apr,
			contratos.Ciu_Cod,contratos.Cnt_Act,tipo_contratos.Tic_Des,persona.Prs_Ape,persona.Prs_Nom,ciudad.Ciu_Des
			FROM contratos
			INNER JOIN tipo_contratos ON contratos.Tic_Cod=tipo_contratos.Tic_Cod
			INNER JOIN cliente ON contratos.Cli_Cod=cliente.Cli_Cod
			INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
			INNER JOIN ciudad ON contratos.Ciu_Cod=ciudad.Ciu_Cod
			WHERE Cnt_Cod='$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * insertar nueva deuda al cliente
		 * @fecha: 18-12-2012
		 */
		case 653:
			$sql="INSERT INTO deudas(Pro_Cod,Cli_Cod,Deu_Val,Deu_Reg,Deu_Fec,Cnt_Cod,Deu_Int)
			VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]')";
			return $sql;
		break;
		
		/**
		 * obtener el ultimo deu_int que tiene el cliente en el contrato
		 * @fecha: 18-12-2012
		 */
		case 654:
			$sql="SELECT Deu_Int FROM deudas WHERE Cli_Cod='$Par_Sql[0]' AND Cnt_Cod='$Par_Sql[1]' ORDER BY Deu_Int DESC LIMIT 0,1;";
			return $sql;
		break;
		
		/**
		 * obtener el ultimo registro
		 * @fecha: 15-11-2012
		 */
		case 655:
			$sql = "SELECT * FROM det_contratos WHERE Cnt_Cod = '$Par_Sql[0]' ORDER BY Cnt_Fec DESC LIMIT 0,1";
			return $sql;
		break;
		
		/**
		 * obtiene los contratos activos del cliente
		 * @fecha: 18-12-2002
		 */
		case 656:
			$sql = "SELECT Cnt_Cod,CONCAT(tipo_contratos.`Tic_Des`,'[',Cnt_Ini,'/',Cnt_Fin,']')Tic_Des
			FROM `contratos` 
			INNER JOIN `tipo_contratos` ON contratos.`Tic_Cod` = tipo_contratos.`Tic_Cod`
			WHERE Cli_Cod = $Par_Sql[0] AND Cnt_Est = 'A' AND tipo_contratos.`Tic_Est` = 'A' AND 
			('$Par_Sql[1]' BETWEEN Cnt_Ini AND Cnt_Fin)";
			return $sql;
		break;
		
		/**
		 * Consultar días plazo interés
		 * 
		 */
		case 657:
			$sql="SELECT Int_Dia, Int_Por FROM interes";
			return $sql;
		break;
		
		/**
		 * busqueda de productos
		 * @fecha 02-11-2012
		 */
		case 658:
			$sql="SELECT precios.Tpv_Cod, item.Ite_Cod, item.Ite_Est, item.Ite_Cor, item.Ite_Lar, marca.Mar_Cod,
			marca.Mar_Des, adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor, iva.Iva_Cod, iva.Iva_Por,
			producto.Pro_Bar, producto.Pro_Obs, producto.Pro_Cod, producto.Pro_Est, producto.Pro_Gen, producto.Pro_Cdc,
			producto.Pro_Sec, stock.Stk_Can, precios.Pre_Pvp
			FROM
			categorias
			INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
			INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
			INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
			INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
			INNER JOIN tipo_preci ON (precios.Tpv_Cod = tipo_preci.Tpv_Cod)
			INNER JOIN stock ON (precios.Pro_Cod = stock.Pro_Cod)
			INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
			WHERE precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Est = 'A'
			AND item.Ite_Lar LIKE '%$Par_Sql[0]%' AND categorias.Emp_Cod = '$Par_Sql[1]' AND adquisicio.Adq_Cor='S'";
			return $sql;
		break;
		/**
		 * obtener una deuda en especial
		 */
		case 659:
			$sql="SELECT
			deudas.Pro_Cod,
			producto.Pro_Ide,
			item.Ite_Lar,
			deudas.Deu_Val,
			deudas.Deu_Fec,
			deudas.Nge_Cod,
			deudas.Deu_Obs,
			deudas.Cnt_Cod,
			producto.Iva_Cod,
			iva.Iva_Por,
			deudas.Bec_Cod,
			deudas.Deu_Rec,
			deudas.Asi_Int,
			deudas.Deu_Int
			FROM
			producto
			INNER JOIN deudas ON (producto.Pro_Cod = deudas.Pro_Cod)
			INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
			INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			WHERE
			deudas.Cli_Cod = $Par_Sql[0] AND Deu_Rec = 0 AND
			Nge_Cod = $Par_Sql[1] AND Deu_Int=$Par_Sql[2]
			ORDER BY
			deudas.Deu_Fec";
			return $sql;
		break;
		
		/**
		 * Consulta el detalle del periodo en base a codigo interno
		 */
		case 660:
			$sql = "SELECT DISTINCT
			view_periodos_suc.Per_Int,
			view_periodos_suc.Ann_Ini,
			view_periodos_suc.Mes_Ini,
			view_periodos_suc.Ann_Fin,
			view_periodos_suc.Mes_Fin,
			view_periodos_suc.Per_Fea,
			view_periodos_suc.Per_Fef,
			view_periodos_suc.Per_Fec,
			view_periodos_suc.Suc_Des,
			modalidad.Mod_Des
			FROM
			modalidad
			INNER JOIN view_periodos_suc ON (modalidad.Mod_Cod = view_periodos_suc.Mod_Cod)
			WHERE
			view_periodos_suc.Per_Int = $Par_Sql[0]";
			return $sql;
		break;

		/**
		 * Consulta de la descripcion de la carreras 
		 */
		case 661:
			$sql = "SELECT carreras.Car_Nom, carreras.Car_Int FROM carreras WHERE carreras.Car_Int=$Par_Sql[0] ORDER BY Car_Nom";
			return $sql;
		break;
		
		/**
		 * Consulta de semestres dependiendo de la carrera
		 */
		case 662:
			$sql = "SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Per_Int,
			view_cursos_mal.Car_Int, carreras.Car_Nom FROM view_cursos_mal INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int) WHERE view_cursos_mal.Car_Int = $Par_Sql[0] AND  view_cursos_mal.Per_Int = $Par_Sql[1] AND  view_cursos_mal.Sem_Est = 'A'
			ORDER BY view_cursos_mal.Niv_Cod";
			return $sql;
		break;
		/**
		 * obtener datos del semestre
		 */
		case 732:
			$sql ="SELECT view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2,  view_periodos_suc.Suc_Des, view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin
			FROM view_periodos_suc INNER JOIN view_cursos_mal ON (view_periodos_suc.Per_Int = view_cursos_mal.Per_Int)
			WHERE view_cursos_mal.Sem_Cod = $Par_Sql[0] GROUP BY  view_cursos_mal.Sem_Cod, view_cursos_mal.Sem_Nom, view_cursos_mal.Sem_No2,
			view_periodos_suc.Suc_Des, view_periodos_suc.Ann_Ini, view_periodos_suc.Mes_Ini, view_periodos_suc.Ann_Fin, view_periodos_suc.Mes_Fin";
			return $sql;
		break;
	}
}
?>