<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-04-19
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package contabilidad.LOGICA
 */
function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		
		/**
		 * Consulta la provicia y pais de la ciudad de la sucursal 
		 */
		case 3: 
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
		 * Consulta los datos del usuario 
		 */
		case 4: 
	 		$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
		break;
		
		case 9:
		/**
		* Consulta de los comprobantes para el libro diario en base a una cuenta 
		*/
		$sql = "SELECT comprobantes.Com_Cod, Com_Con, Pld_Rec, Pld_Cdc, Pld_Des
						FROM comprobantes, asientos, det_plan WHERE 
						comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND (Com_Fec BETWEEN 
						'$Par_Sql[0]' AND '$Par_Sql[1]') AND det_plan.Pld_Cdc = '$Par_Sql[2]' AND comprobantes.Com_Est = 'A' AND comprobantes.Pec_Cod = $Par_Sql[3] GROUP BY comprobantes.Com_Cod 
						ORDER BY Com_Fec ASC";						
		return $sql;
		break;
		
		case 10:
		/* Consulta el detalle de los comprobantes para el libro diario */
		$consultar_asientos_10 = "SELECT comprobantes.Com_Cod, Com_Num, Com_Fec, Tia_Ini, Tia_Abr, Asi_Deh, Pld_Rec, Pld_Cdc, Pld_Des, Asi_Val, Com_Con
						FROM comprobantes, tipo_asien, asientos, det_plan WHERE comprobantes.Tia_Cod = tipo_asien.Tia_Cod AND 
						comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND comprobantes.Com_Est = 'A' AND comprobantes.Com_Cod = $Par_Sql[0]";						
		//echo $consultar_asientos_10;
		return $consultar_asientos_10;
		break;
		
		case 11:
		/**
		* Consulta de los comprobantes para el libro diario TODOS 
		*/
		$sql = "SELECT comprobantes.Com_Cod, Com_Con, Pld_Rec, Pld_Cdc, Pld_Des
						FROM comprobantes, asientos, det_plan WHERE 
						comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND (Com_Fec BETWEEN 
						'$Par_Sql[0]' AND '$Par_Sql[1]') AND comprobantes.Pec_Cod = $Par_Sql[2] GROUP BY comprobantes.Com_Cod 
						ORDER BY Com_Fec ASC";						
		return $sql;
		break;
		
		case 12:
		/* 
		* Consulta el codigo del proceso 
		*/
		$consulta_proceso_12 = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
		//echo $consulta_proceso_12;
		return $consulta_proceso_12;
		break;

		case 13:
		/* 
		* Consulta el reporte recursivo 
		*/
		$consulta_proceso_13 = "SELECT 
						  reportes.Rep_Cod,
						  procesos.Pcs_Nom,
						  reportes.Rep_Ord,
						  rutas.Rut_Des
						FROM
						  procesos
						  INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
						  INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) WHERE reportes.Pcs_Cod = $Par_Sql[0] AND reportes.Emp_Cod = $Par_Sql[1] ORDER BY
							reportes.Rep_Ord ";
		//echo $consulta_proceso_13;
		return $consulta_proceso_13;
		break;
		
                case 14:
		/**
		* Consulta de los comprobantes para el libro diario TODOS 
		*/
		$sql = "SELECT comprobantes.Com_Cod, Com_Con, Pld_Rec, Pld_Cdc, Pld_Des
						FROM comprobantes, asientos, det_plan WHERE 
						comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Pld_Cod = det_plan.Pld_Cod AND (Com_Fec BETWEEN 
						'$Par_Sql[0]' AND '$Par_Sql[1]') AND comprobantes.Pec_Cod = $Par_Sql[2] $Par_Sql[3] AND Com_Est='A'  GROUP BY comprobantes.Com_Cod 
						ORDER BY Com_Fec ASC";						
                //echo $sql;
		return $sql;
		break;

		case 204:
		/**
		* Consulta la descripcion de la recusividad de una sub-cuenta 
		*/
		$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
		//echo $consul_recur;
		return $consul_recur;
		break;
		
		case 207:
		/* SENTECIAS UTILILES EN REPORTES PARA CABECERAS */
		/* Consulta que permite cargar el nombre de la empresa a que pertenece el usuario */
		$cabecera_empresa = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des FROM empresas, sucursal, ciudad WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
		return  $cabecera_empresa;
		break;
		
		/**
		* Consulta d todos los periodos activos 
		*/
		case 214:
		$sql = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo, perio_cont.Pla_Cod FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND
		 plan_cuenta.Emp_Cod = $Par_Sql[0] ORDER BY Pec_Fei Desc";
		return $sql;
		break;
		
		/* Consulta el codigo interno del plan de cuentas en base al codigo del periodo contable */
		case 215: 
		$consulta_plan_215 = "SELECT det_plan.Pla_Cod FROM det_plan, plan_cuenta, comprobantes, asientos WHERE plan_cuenta.Pla_Cod = det_plan.Pla_Cod 
					AND asientos.Pld_Cod = det_plan.Pld_Cod AND asientos.Com_Cod = comprobantes.Com_Cod AND comprobantes.Pec_Cod = $Par_Sql[0] GROUP BY det_plan.Pla_Cod ORDER BY det_plan.Pla_Cod DESC"; 
		//echo $consulta_plan_215;
		return $consulta_plan_215;
		break;
		
		/**
		 * Consulta la informaci�n la ciudada en base a la sucursal 
		 */
		case 126: 
	 		$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
		break;
		
		//* REVISAR VARIABLES DE SESION DE LA EMPRESA EN LA 312 - 313
		/* Busqueda de cuentas por descripcion */
		case 312:
		$bus_ctad="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2] Order by Pld_Cod";
		//echo  $bus_ctad;
		return $bus_ctad;
		break;
	
		/* Busqueda de cuentas por codigo */
		case 313:
		$bus_ctac="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Cdc = TRIM('$Par_Sql[0]') AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2]";
		//echo $bus_ctac;
		return $bus_ctac;
		break;

		/**
		* Busqueda de cuentas por codigo 
		*/
		case 314:
		$sql="SELECT 
  det_plan.Pld_Cod,
  det_plan.Pld_Cdc,
  det_plan.Pld_Des,
  det_plan1.Pld_Des AS Pld_Des_Grupo,
  det_plan1.Pld_Cdc AS Pld_Cdc_Grupo
FROM
  det_plan
  INNER JOIN det_plan det_plan1 ON (det_plan1.Pld_Cod = det_plan.Pld_Rec)
  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod) WHERE det_plan.Pld_Cdc = '$Par_Sql[0]' AND plan_cuenta.Pla_Cod = $Par_Sql[1]";
		return $sql;
		break;

                case 315:
		/* Consulta de los tipos de asientos */
		$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien";
		//echo $sql;
		return $sql;
		break;

                case 316:
		/* Consulta de los tipos de asientos */
		$sql = "SELECT Tia_Cod, Tia_Des, Tia_Ini FROM tipo_asien WHERE Tia_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;
		
	}
}?>