<?php
/**
 * @author Sam :)
 * @version 1.0
 * Fecha de actualizaci�n:	2012-10-15
 */
function sentencias_est($id,$Par_Sql)
{
	switch($id)
	{
		/* Busqueda de cuentas por codigo y plan de cuenta (Verificar que no hay codigo repetido) CONCURRENCIA*/
		case 1:
		$bus_ctac2="SELECT det_plan.Pld_Cdc, det_plan.Pld_Des, Pla_Obs, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND det_plan.Pla_Cod=$Par_Sql[0] AND plan_cuenta.Emp_Cod=$Par_Sql[2] AND det_plan.Pld_Cdc = '$Par_Sql[1]'";
		//echo $bus_ctac2;
		return $bus_ctac2;
		break;

		/* Cargado de la cabecera del reporte de plan de cuentas (Datos de Empresa y del Plan) */
		case 2:
		$cargar_cabplan="SELECT Emp_Nom, Pla_Cod, Pla_Fec, IF (Pla_Est='A', 'Activa', 'Inactiva') as Pla_Est, Pla_Obs FROM empresas,plan_cuenta WHERE empresas.Emp_Cod=plan_cuenta.Emp_Cod AND plan_cuenta.Pla_Cod=$Par_Sql[0]";
		
		return $cargar_cabplan;
		break;

		case 3: 
		/* Consulta la provicia y pais de la ciudad de la sucursal */
 		$provincia="SELECT 
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
		/* Consulta los datos del usuario */
 		$usuario="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
						//echo $usuario;
		return $usuario;
		break;
		
		case 5:
		$sql="INSERT INTO det_estado (Est_Cod,Pld_Cod)VALUE('$Par_Sql[0]','$Par_Sql[1]')";
		//echo "<br>".$sql;
		return $sql;
		break;
		
        case 6:
		$sql = "DELETE det_estado FROM det_estado, det_plan WHERE det_estado.Pld_Cod = det_plan.Pld_Cod AND Est_Cod = '$Par_Sql[0]' AND det_plan.Pla_Cod = $Par_Sql[1]";
		//echo $sql;
		return $sql;
		break;	
	
		case 7:
		$sql = "SELECT DISTINCT  estado_fin.Est_Des FROM estado_fin
  INNER JOIN det_estado ON (estado_fin.Est_Cod = det_estado.Est_Cod) 
  INNER JOIN det_plan ON (det_estado.Pld_Cod = det_plan.Pld_Cod) 
 WHERE det_estado.Est_Cod ='$Par_Sql[0]'";
       //echo $sql;
	   return $sql;
       break;
	
		case 9:
				$sql="SELECT COUNT(Pld_Cod) AS 'count' FROM det_estado 
WHERE Pld_Cod=$Par_Sql[0] AND det_estado.Est_Cod=$Par_Sql[1]";
				return $sql;
			break;
        
		case 11:
		$sql = "SELECT Pld_Cod, Pld_Des FROM det_plan INNER JOIN plan_cuenta ON (plan_cuenta.Pla_Cod = det_plan.Pla_Cod) WHERE Pld_Rec='0' AND plan_cuenta.Emp_Cod = '$Par_Sql[0]' AND Pld_Est='A' AND plan_cuenta.Pla_Cod = '$Par_Sql[1]'";
		//echo $sql;
		return $sql;
		break;
		
		case 126: 
		/* Consulta la informaci�n la ciudada en base a la sucursal */
 		$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
						//echo $cargar_ciudad;
		return $cargar_ciudad;
		break;

		case 145:
		/* Busca si la cuenta tiene moviemto */
		$cargar_cuen="SELECT Pld_Cod FROM asientos WHERE asientos.Pld_Cod=$Par_Sql[0] limit 0,1";		
		return $cargar_cuen;
		break;		
		
		/**
		 * Cargado de los plan de cuentas de una empresa en especifico (Codigo de la empresa)
		*/
		case 302:
		$cargar_planes="SELECT Pla_Cod, Pla_Fec, Pla_Obs, IF (Pla_Est='A','Activo','Inactivo') as Pla_Est FROM plan_cuenta WHERE Emp_Cod=$Par_Sql[0] ;";
		//echo $cargar_planes.'<br/>	';
		return $cargar_planes;
		break;

		/* Cargado de los nodos del plan de cuentasEst_Des, estado_fin.Est_Cod */
		case 303:
	
		$cargar_nodos = " SELECT DISTINCT  estado_fin.Est_Des, estado_fin.Est_Cod FROM estado_fin
  INNER JOIN det_estado ON (estado_fin.Est_Cod = det_estado.Est_Cod) 
  INNER JOIN det_plan ON (det_estado.Pld_Cod = det_plan.Pld_Cod) 
 WHERE det_estado.Est_Cod=estado_fin.Est_Cod AND det_plan.Pla_Cod ='$Par_Sql[0]'";
		//echo $cargar_nodos;
		return $cargar_nodos;
	break;

		/* Insercion de una nueva cuenta en algun nodo del plan de cuentas */
		case 304:
		$ins_cuenta="INSERT INTO det_plan SET Pld_Rec='$Par_Sql[0]', Pla_Cod=$Par_Sql[1], Pld_Cdc='$Par_Sql[2]', Pld_Des='$Par_Sql[3]', Pld_Tip='$Par_Sql[4]'";
		return $ins_cuenta;
		break;

		/* Cargado del nombre de la cuenta para poder mostrar la direcci�n donde esta en ese momento*/
		case 305:
		$cargar_direc="SELECT Pld_Cod,Pld_Cdc,Pld_Des FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
		return $cargar_direc;
		break;

		/* Cargado del recursivo de la cuenta para poder mostrar la direcci�n de volver atr�s*/
		case 306:
		$cargar_direca="SELECT Pld_Rec FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
		return $cargar_direca;
		break;

		/* Cargado de la informaci�n a modificar en una cuenta*/
		case 307:
		$cargar_mcuenta="SELECT Pld_Cdc, Pld_Des, Pld_Tip, Pld_Est FROM det_plan WHERE Pld_Cod=$Par_Sql[0]";
		return $cargar_mcuenta;
		break;

		/* Actualizacion de una cuenta del Plan de Cuentas*/
		case 308:
		$upd_cuenta="UPDATE det_plan SET Pld_Cdc='$Par_Sql[0]', Pld_Des='$Par_Sql[1]', Pld_Tip='$Par_Sql[2]', Pld_Est='$Par_Sql[3]' WHERE Pld_Cod=$Par_Sql[4]";
		return $upd_cuenta;
		break;

		/**
		 * Inserci�n de una nueva cabecera de plan de cuentas
		*/		
		case 309:
		$ins_nplan="INSERT INTO plan_cuenta (Emp_Cod, Pla_Fec, Pla_Obs) VALUES ($Par_Sql[0], '$Par_Sql[1]', '$Par_Sql[2]')";
		return $ins_nplan;
		break;

		/**
		 * Cargado de la informaci�n a modificar en una cabecera de plan de cuenta
		*/				
		case 310:
		$cargar_mplan="SELECT plan_cuenta.Pla_Cod,Pla_Obs, Pla_Est,Pec_Cod FROM plan_cuenta JOIN perio_cont ON perio_cont.Pla_Cod=plan_cuenta.Pla_Cod WHERE plan_cuenta.Pla_Cod=$Par_Sql[0]";
		//echo $cargar_mplan;
		return $cargar_mplan;
		break;

		/* Actualizacion de una cuenta del Plan de Cuentas*/
		case 311:
		$upd_cplan="UPDATE plan_cuenta SET Pla_Obs='$Par_Sql[0]', Pla_Est='$Par_Sql[1]' WHERE Pla_Cod=$Par_Sql[2]";
		//echo $upd_cplan;
		return $upd_cplan;
		break;

		/* Consultas las cuentas del siguiente nivel */
		case 312:
		$cplan="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, IF(Pld_Tip = 'G', 'GRUPO', 'Detalle') AS Pld_Tip,
  IF(Pld_Est = 'A', 'Activo', 'Inactivo') AS Pld_Est FROM det_plan WHERE Pld_Rec = $Par_Sql[0] ORDER BY
  SUBSTRING_INDEX(Pld_Cdc,'.', -1) + 0";
		//echo $cplan;
		return $cplan;
		break;

		/* Busqueda de cuentas por descripcion */
		case 313:
		$bus_ctad="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, Pla_Obs, IF (Pld_Tip='G', 'GRUPO', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' ORDER BY Pld_Cod";
		//echo  $bus_ctad;
		return $bus_ctad;
		break;
	
		/* Busqueda de cuentas por codigo */
		case 314 :
		$bus_ctac="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Cdc = TRIM('$Par_Sql[0]') $Par_Sql[2]"; //AND plan_cuenta.Pla_Cod = $Par_Sql[3]
		//echo $bus_ctac;
		return $bus_ctac;
		break;

		/* Cargado de la ra�z del Plan de Cuentas de cuantas activas*/
		case 315:
		$cargar_nodosrep="SELECT Pld_Cod, Pld_Cdc, Pld_Des, Pld_Est, Pld_Rec, Pld_Tip FROM det_plan WHERE Pla_Cod=$Par_Sql[0] AND Pld_Rec=$Par_Sql[1]"; //AND Pld_Est = 'A' 
		//echo $cargar_nodosrep."<br>";
		return $cargar_nodosrep;
		break;

		/**
		* Consulta los tipos de balance no registrdos
		*/
		case 316:
	
		$cargar_nodos = "SELECT 
  estado_fin.Est_Cod,
  estado_fin.Est_Des
FROM
  estado_fin
WHERE estado_fin.Est_Cod NOT IN (SELECT DISTINCT 
  estado_fin.Est_Cod
FROM
  estado_fin
  INNER JOIN det_estado ON (estado_fin.Est_Cod = det_estado.Est_Cod)
  INNER JOIN det_plan ON (det_estado.Pld_Cod = det_plan.Pld_Cod)
WHERE
  det_plan.Pla_Cod = '$Par_Sql[0]' )";
		//echo $cargar_nodos;
		return $cargar_nodos;
	break;
        case 317:	
		$cargar_nodos = "SELECT utilidades.Pld_Cod,Pec_Cod,Uti_Val,Pla_Cod,Pld_Cdc,Pld_Des,Uti_Tip FROM utilidades "
                . "JOIN det_plan ON utilidades.Pld_Cod=det_plan.Pld_Cod "
                . "WHERE Pla_Cod=$Par_Sql[0] ";
		//echo $cargar_nodos;
		return $cargar_nodos;
        case 318:
		/* 
		* Consulta la descripcion de la recusividad de una sub-cuenta 
		*/
		$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
		//echo $consul_recur;
		return $consul_recur;
        case 319:
            $bus_xmld_331="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
            //echo $bus_xmld_331;
            return $bus_xmld_331;
        case 329:
            $bus_xmld_331="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'G' ORDER BY Pld_Cod";
            //echo $bus_xmld_331;
            return $bus_xmld_331;	
        case 320:
            $bus_xmld_331="DELETE FROM utilidades WHERE Pld_Cod=$Par_Sql[0] AND Pec_Cod=$Par_Sql[1] AND Uti_Tip='$Par_Sql[2]'";
            //echo $bus_xmld_331;
            return $bus_xmld_331;	
         case 321:
            $bus_xmld_331="INSERT INTO utilidades SET Pld_Cod=$Par_Sql[0],Pec_Cod=$Par_Sql[1],Uti_Val=0,Uti_Tip='$Par_Sql[2]';";
           //echo $bus_xmld_331;
            return $bus_xmld_331;

	}
        
}
?>
