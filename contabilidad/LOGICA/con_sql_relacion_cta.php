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
		case 204:
		/* Consulta la descripcion de la recusividad de una sub-cuenta */
		$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
		//echo $consul_recur;
		return $consul_recur;
		break;
		
		/* 
		* Consulta todos los periodos activos 
		*/
		case 214:
		$cargar_per_214 = "SELECT 
  perio_cont.Pec_Cod,
  perio_cont.Pec_Fei,
  perio_cont.Pec_Fef,
  perio_cont.Pec_Est,
  Year(Pec_Fei) AS Periodo,
  perio_cont.Pla_Cod
FROM
  plan_cuenta
  INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
WHERE
  Pec_Est = 'A' AND plan_cuenta.Emp_Cod = $Par_Sql[0]
ORDER BY
  Pec_Fei DESC";
		//echo $cargar_per_214;
		return $cargar_per_214;
		break;

		/**
		* Busqueda de cuentas por descripcion 
		*/
		case 312:
		$bus_ctad="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2] Order by Pld_Cod";
		//echo  $bus_ctad;
		return $bus_ctad;
		break;	
		/**
		* Busqueda de cuentas por codigo 
		*/
		case 313:
		$bus_ctac="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est, Pla_Obs, Pld_Rec FROM det_plan, plan_cuenta, empresas WHERE det_plan.Pla_Cod=plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND empresas.Emp_Cod=$Par_Sql[1] AND det_plan.Pld_Cdc = TRIM('$Par_Sql[0]') AND plan_cuenta.Pla_Cod = $Par_Sql[3] $Par_Sql[2]";
		//echo $bus_ctac;
		return $bus_ctac;
		break;

		case 511: 
		/**
		* Consultar todas las tablas relacionadas con la tablaproducto
		*/
		$Sql_511= "SELECT item.Ite_Cod,Ite_Est,Ite_Cor,Ite_Lar,categorias.Cat_Cod,Cat_Des,marca.Mar_Cod,Mar_Des, producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec 
			FROM item,producto,categorias,marca
			WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND producto.Pro_Est='A' AND item.Ite_Lar LIKE '%$Par_Sql[0]%' AND categorias.Emp_Cod = $Par_Sql[1]";
		//echo $Sql_511;
		return $Sql_511;
		break;

		case 512: 
		/**
		* Consultar las modalidades activas
		*/
		$Sql_512 = "SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Est='A'";
		//echo $Sql_512;
		return $Sql_512;
		break;
	
		case 513: 
		/** 
		* Consultar las carreras activas
		*/
		$Sql_513 = "SELECT 
  carreras.Car_Int,
  carreras.Car_Nom,
  etapas.Eta_Des
FROM
  etapas
  INNER JOIN carreras ON (etapas.Eta_Cod = carreras.Eta_Cod)
  INNER JOIN escuelas ON (carreras.Esc_Int = escuelas.Esc_Int)
WHERE
  carreras.Car_Est = 'A' AND escuelas.Emp_Cod = $Par_Sql[0]
ORDER BY
  carreras.Car_Nom";
		//echo $Sql_513;
		return $Sql_513;
		break;

	/*Insertando una nueva relacion de un producto con plan d cta.*/
	case 514:
	$Sql_514="INSERT INTO produ_plan(Pro_Cod,Pld_Cod,Car_Int,Mod_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]','$Par_Sql[3]')";
	//echo $Sql_514;
	return $Sql_514;
	break;	

		/**
		* Consulta la relacion de un producto con plan d cta.
		*/
		case 515:
		$Sql_515="SELECT 
				produ_plan.Pro_Cod,
				det_plan.Pld_Cod,
				det_plan.Pld_Des,
				produ_plan.Car_Int,
				produ_plan.Mod_Cod
			   FROM
				produ_plan
				INNER JOIN det_plan ON (produ_plan.Pld_Cod = det_plan.Pld_Cod)
			   WHERE
				produ_plan.Pro_Cod = $Par_Sql[0]";
		//echo $Sql_515;
		return $Sql_515;
		break;
		/**
		* Busqueda de modalidad 
		*/		
		case 516:
		$consul_mod_516 = "SELECT Mod_Cod,Mod_Des FROM modalidad WHERE Mod_Cod = $Par_Sql[0]";	
		return $consul_mod_516;
		break;
	
		/**
		* Consultas de carreras 
		*/
		case 517:
		$Sql_517="SELECT Car_Nom, Car_Int FROM carreras WHERE Car_Int = $Par_Sql[0]";
		return $Sql_517;
		break;	

		/** 
		* Consulta para eliminar registros en la tabla dist-costo
		*/
		case 518:
		$Sql_518="DELETE FROM produ_plan WHERE Pro_Cod = $Par_Sql[0] AND Pld_Cod = $Par_Sql[1] AND Car_Int = $Par_Sql[2] AND Mod_Cod = $Par_Sql[3]";
		//echo $Sql_518;
		return $Sql_518;
		break;	

                /** 
		* Consulta todos los productos relacinados
		*/
		case 519:
		$sql="SELECT 
			producto.Pro_Cod,producto.Pro_Obs,categorias.Cat_Des,adquisicio.Adq_Des,
			item.Ite_Lar,item.Ite_Cor,det_plan.Pld_Cdc,det_plan.Pld_Des
		      FROM
			det_plan
			INNER JOIN produ_plan ON (det_plan.Pld_Cod = produ_plan.Pld_Cod)
			INNER JOIN producto ON (producto.Pro_Cod = produ_plan.Pro_Cod)
			INNER JOIN item ON (item.Ite_Cod = producto.Ite_Cod)
			INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
			INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
		      WHERE
			categorias.Emp_Cod='$Par_Sql[0]' AND producto.Pro_Est='A'";
		//echo $sql;
		return $sql;
		break;	

                /** 
		* Consulta todos los productos NO relacinados
		*/
		case 520:
		$sql="SELECT 
				  producto.Pro_Cod,
				  producto.Pro_Obs,
				  categorias.Cat_Des,
				  adquisicio.Adq_Des,
				  item.Ite_Lar,
				  item.Ite_Cor
				FROM
				  adquisicio
				  INNER JOIN producto ON (adquisicio.Adq_Cod = producto.Adq_Cod)
				  INNER JOIN item ON (producto.Ite_Cod = item.Ite_Cod)
				  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
				WHERE
				  categorias.Emp_Cod='$Par_Sql[0]' AND producto.Pro_Est='A' AND producto.Pro_Cod NOT IN(Select produ_plan.Pro_Cod From produ_plan)";
		//echo $sql;
		return $sql;
		break;

                case 521:
		$sql="SELECT * FROM produ_plan 
                    INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod
                    WHERE Pro_Cod='$Par_Sql[0]' AND Pla_Cod='$Par_Sql[1]' AND Tip_Pld='$Par_Sql[2]'";
		//echo $sql;
		return $sql;
            case 522:
                    if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                    else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                    else{
                        $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                        $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                    }
                    $sql="SELECT $campos
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
              case 523:
		$sql="DELETE FROM produ_plan WHERE Pro_Cod='$Par_Sql[0]' AND Pld_Cod='$Par_Sql[1]' AND Tip_Pld='$Par_Sql[2]'";
		//echo $sql;
		return $sql;  
              case 524:
		$sql="INSERT INTO produ_plan(Pro_Cod,Pld_Cod,Car_Int,Mod_Cod,Tip_Pld) VALUES ('$Par_Sql[0]','$Par_Sql[1]',0,0,'$Par_Sql[2]')";
		//echo $sql;
		return $sql;   
              case 525:  
                if($Par_Sql[4]==""){$campos="COUNT(producto.Pro_Cod) as total";}
                else{$campos="item.Ite_Cod,Ite_Est,Ite_Cor,CONCAT(Ite_Lar,' ',Pro_Obs)AS Ite_Lar,categorias.Cat_Cod,Cat_Des,marca.Mar_Cod,Mar_Des, producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec ";}  
                if($Par_Sql[2]=="t") $Par_Sql[2]='';
                if($Par_Sql[2]=="s") $Par_Sql[2]='AND det_plan.Pla_Cod='.$Par_Sql[3];
                if($Par_Sql[2]=="n") $Par_Sql[2]="AND producto.Pro_Cod NOT IN(SELECT produ_plan.Pro_Cod From produ_plan INNER JOIN det_plan ON det_plan.Pld_Cod=produ_plan.Pld_Cod WHERE Pla_Cod=$Par_Sql[3])";
                $search=""; 
                $array=explode(" ",strtoupper($Par_Sql[0]));
                foreach($array as $ar){
                    if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");                    
                }
                if($search=='') $search="1=1";   
                $sql= "SELECT $campos
			FROM producto
                            INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
							INNER JOIN precios AS prec ON prec.Suc_Cod=$_SESSION[Ses_Suc_Cod] AND prec.Pro_Cod=producto.Pro_Cod AND prec.Pre_Est='A'
                            INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
                            INNER JOIN marca ON producto.Mar_Cod= marca.Mar_Cod ".
                            ($Par_Sql[2]!=''?"LEFT JOIN det_plan ON det_plan.Pld_Cod =( SELECT produ_plan.Pld_Cod FROM produ_plan WHERE produ_plan.Pro_Cod=producto.Pro_Cod LIMIT 1) ":'').
			" WHERE item.Ite_Est='A' AND Pro_Est='A' AND ( $search ) AND categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[2] ".($Par_Sql[2]!=''?($Par_Sql[4]==""?'':"GROUP BY Pro_Cod"):'ORDER BY producto.Pro_Cod')." $Par_Sql[4] ";
		//echo $sql.'<br>';
		return $sql;
		
	}
}
?>