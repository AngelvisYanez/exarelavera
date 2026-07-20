<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-06-01
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_cod($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otra maquina
		*/
		$sql=  "SELECT Ren_Cod, Ren_Sri,Ren_Por FROM renta_iva WHERE Ren_Sri = '$Par_Sql[0]' AND Ren_Est = 'A'";	
		//echo $sql;
		return $sql;
		break;

		/* 
		* Registra la marca
		*/
		case 2:
		$sql= "INSERT INTO renta_iva (Ren_Sri,Ren_Con,Ren_Por,Ren_Ing,Ren_Tip,Ren_Ret,Adq_Cod) VALUES (Trim(UPPER('$Par_Sql[0]')),Trim(UPPER('$Par_Sql[1]')),'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]')";		
		//echo $sql;
		return $sql;
		break;
		case 220:
		if ($Par_Sql[Adq_Cod]==1) {
			$variable = 'B';
		}else{
			$variable = 'S';
		}
		$sql= "INSERT INTO renta_iva (Ren_Sri,Ren_Con,Ren_Por,Ren_Ing,Ren_Tip,Ren_Ret,Adq_Cod) VALUES ('$Par_Sql[Ren_Sri]',Trim(UPPER('$Par_Sql[Ren_Con]')),'$Par_Sql[Ren_Por]','N','$variable','$Par_Sql[Ren_Ret]','$Par_Sql[Adq_Cod]')";
			//ChromePhp::log($sql);
		return $sql;
		break;
		case 3: 
		/* 
		* consulta  si existe otra concepto igual
		*/
		$sql=  "SELECT Ren_Cod, Ren_Con FROM renta_iva WHERE Ren_Con = '$Par_Sql[0]' AND Ren_Por ='$Par_Sql[1]' AND Ren_Est = 'A' AND Ren_Cod<>'$Par_Sql[2]'";	
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Consultamos plan de cuenta ectivo
		*/		
		case 4:
		$sql= "SELECT Pla_Cod,Emp_Cod FROM plan_cuenta WHERE Pla_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;
		
		/* Cargado de la b�squeda de cuentas en la p�gina de registro de comprobantes (Revisar la variable de sesi�n Emp_Cod */
		/* Busqueda de cuentas por descripcion */
		case 5:
		$bus_xmld_11="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
//echo $bus_xmld_11;
		//echo $bus_xmld_11;	
		return $bus_xmld_11;	
		break;
			
		/* Busqueda de cuentas por codigo */
		case 6:
		$bus_xmlc_12="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Cdc = '$Par_Sql[0]' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
		//echo $bus_xmlc_12;
		return $bus_xmlc_12;	
		break;
		
		//sentencias miriam consulta periodos de inscripcion
		case 7:
		$consultar_per_inscr="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) AS Ann_Ini, IF(MONTH(Per_Fea) = 1, 'Enero', IF(MONTH(Per_Fea) = 2, 'Febrero', IF(MONTH(Per_Fea) = 3, 'Marzo', IF(MONTH(Per_Fea) = 4, 'Abril', IF(MONTH(Per_Fea) = 5, 'Mayo', IF(MONTH(Per_Fea) = 6, 'Junio', IF(MONTH(Per_Fea) = 7, 'Julio', IF(MONTH(Per_Fea) = 8, 'Agosto', IF(MONTH(Per_Fea) = 9, 'Septiembre', IF(MONTH(Per_Fea) = 10, 'Octubre', IF(MONTH(Per_Fea) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Ini,
  		YEAR(Per_Fef) AS Ann_Fin, IF(MONTH(Per_Fef) = 1, 'Enero', IF(MONTH(Per_Fef) = 2, 'Febrero', IF(MONTH(Per_Fef) = 3, 'Marzo', IF(MONTH(Per_Fef) = 4, 'Abril', IF(MONTH(Per_Fef) = 5, 'Mayo', IF(MONTH(Per_Fef) = 6, 'Junio', IF(MONTH(Per_Fef) = 7, 'Julio', IF(MONTH(Per_Fef) = 8, 'Agosto', IF(MONTH(Per_Fef) = 9, 'Septiembre', IF(MONTH(Per_Fef) = 10, 'Octubre', IF(MONTH(Per_Fef) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Fin FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int)
 		 INNER JOIN sucursal ON (periodos.Suc_Cod = sucursal.Suc_Cod) INNER JOIN incritodet ON (periodos.Per_Int = incritodet.Per_Int)
		WHERE periodos.Eta_Cod = $Par_Sql[0] AND  periodos.Mod_Cod = $Par_Sql[1] AND '$Par_Sql[2]' >= perio_matr.Pem_Ini AND 
  		periodos.Suc_Cod = $Par_Sql[3] GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea DESC";
  		//echo $consultar_per_inscr;
		return $consultar_per_inscr;
		break;
		
		/* 
		* Registra la marca
		*/
		case 8:
		$sql= "INSERT INTO reniva_pla (Ren_Cod,Pld_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";		
		return $sql;
		break;
		
		/* 
		* Consultamos todos los renta_iva activos
		*/	
                case 9:
                   if($Par_Sql[0]!='') $where ="AND Ren_Sri LIKE '$Par_Sql[0]%'"; else $where=' '; 
                   $sql= "SELECT Adq_Cod,Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,if(Ren_Tip='B','BIENES','SERVICIO')as Ren_Tip,if(Ren_Ret='R','RENTA','IVA')as Ren_Ret,if(Ren_Est='A','Activo','Anulado')as Ren_Est FROM renta_iva "
                           . "Where Ren_Est='A' $where ORDER BY Ren_Sri ASC"; 
                   //echo $sql;
                   return $sql;
		
//		case 9:
//		$sql= "SELECT Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,if(Ren_Tip='B','BIENES','SERVICIO')as Ren_Tip,if(Ren_Ret='R','RENTA','IVA')as Ren_Ret,if(Ren_Est='A','Activo','Anulado')as Ren_Est FROM renta_iva Where Ren_Est='A' ORDER BY Ren_Sri ASC"; 
//		//echo $sql;
//		return $sql;
//		break;
		
		/* 
		* Consultamos renta_iva activos por codigo
		*/		
		case 10:
		$sql= "SELECT Ren_Cod,Ren_Sri,Ren_Con,Ren_Por,if(Ren_Tip='B','BIENES','SERVICIO')as Ren_Tip,if(Ren_Ret='R','RENTA','IVA')as Ren_Ret,if(Ren_Est='A','Activo','Anulado')as Ren_Est FROM renta_iva Where Ren_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Anulamos
		*/
		case 11:
		$sql= "UPDATE renta_iva SET Ren_Est='$Par_Sql[0]' WHERE Ren_Cod='$Par_Sql[1]'";		
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* consultamos por codigo reniva_plan
		*/
		case 12:
		$sql= "SELECT 
				  det_plan.Pld_Des,
                                  det_plan.Pld_Cdc,
				  reniva_pla.Pld_Cod,
				  reniva_pla.Ren_Cod
				FROM
				  det_plan
				  INNER JOIN reniva_pla ON (det_plan.Pld_Cod = reniva_pla.Pld_Cod)
                                  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
				WHERE reniva_pla.Ren_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND reniva_pla.Ren_Tip='$Par_Sql[2]' AND plan_cuenta.Pla_Cod=$Par_Sql[3]";		
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Actualizamos
		*/
		case 13:
		$sql= "UPDATE renta_iva SET Ren_Sri='$Par_Sql[0]',Ren_Con=Trim(UPPER('$Par_Sql[1]')),Ren_Por='$Par_Sql[2]',Ren_Tip='$Par_Sql[3]',Ren_Ret='$Par_Sql[4]',Adq_Cod='$Par_Sql[6]' WHERE Ren_Cod='$Par_Sql[5]'";		
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Actualizamos  renta_iva
		*/
		case 14:
		$sql= "UPDATE reniva_pla SET Pld_Cod='$Par_Sql[0]' WHERE Ren_Cod='$Par_Sql[1]' AND Pld_Cod='$Par_Sql[2]'";		
		//echo $sql;
		return $sql;
		break;

                /* 
		*  Damos de baja al codigo que se esta usando actualmente
		*/
		case 15:
		$sql= "Update renta_iva Set renta_iva. Ren_Est='I' Where Ren_Sri='$Par_Sql[0]'";		
		//echo $sql;
		return $sql;
		break;

                /* 
		*  Damos de baja al codigo que se esta usando actualmente
		*/
		case 16:
		$sql= "SELECT Adq_Cod,Adq_Des,if(Adq_Cor='B','BIENES','SERVICIO')as AdqCor,Adq_Cor FROM adquisicio WHERE Adq_Est = 'A' AND Adq_Cor = 'B' OR Adq_Cor = 'S'";		
		//echo $sql;
		return $sql;
		break;
            
              case 17:
		$sql= "DELETE FROM reniva_pla WHERE Ren_Cod='$Par_Sql[0]' AND Pld_Cod='$Par_Sql[1]' AND Ren_Tip='$Par_Sql[2]'";		
		//echo $sql;
		return $sql;
            
                case 18:
                    if($Par_Sql[3]=="d") {$search="det_plan.Pld_Des LIKE '%$Par_Sql[0]%'";}
                    else {$search="det_plan.Pld_Cdc LIKE '$Par_Sql[0]%'";}
                    if($Par_Sql[4]==""){$campos="COUNT(det_plan.Pld_Cod) as total";}
                    else{
                        $Par_Sql[4]="ORDER BY det_plan.Pld_Cod ".$Par_Sql[4];
                        $campos="det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Rec, det_plan.Pld_Des, /*empresas.Emp_Nom,*/ Pla_Obs,
                                IF (parent2.Pld_cod IS NOT NULL, CONCAT(parent.Pld_Des,' <b>(',parent2.Pld_Des,')</b>'), parent.Pld_Des) as Pld_Grupo,
                                IF (det_plan.Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (det_plan.Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est ";
                    }
                    $bus_xmld_331="SELECT $campos
                                FROM det_plan 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod
                                /*INNER JOIN perio_cont ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod
                                INNER JOIN empresas ON plan_cuenta.Emp_Cod=empresas.Emp_Cod */
                                LEFT JOIN det_plan as parent ON det_plan.Pld_Rec=parent.Pld_Cod
                                LEFT JOIN det_plan as parent2 ON parent.Pld_Rec=parent2.Pld_Cod
                                WHERE plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' 
                                AND $search AND plan_cuenta.Pla_Cod =$Par_Sql[2] 
                                AND det_plan.Pld_Tip = 'D' $Par_Sql[4]";
                //echo $bus_xmld_331;
                return $bus_xmld_331;
                
                case 19:
		$sql= "INSERT INTO reniva_pla SET Ren_Cod='$Par_Sql[0]',Pld_Cod='$Par_Sql[1]', Ren_Tip='$Par_Sql[2]'";		
		//echo $sql;
		return $sql;
                
                case 20:
		$sql= "UPDATE renta_iva SET Ren_Con=Trim(UPPER('$Par_Sql[1]')),Ren_Ret='$Par_Sql[2]',Adq_Cod='$Par_Sql[3]' WHERE Ren_Cod='$Par_Sql[0]'";		
		//echo $sql;
		return $sql;
		
		case 21: 
		/* Consulta el plan */
 		$sql = "SELECT plan_cuenta.Pla_Cod, Year(Pec_Fei)as Pla_Fec,Pec_Cod FROM plan_cuenta,perio_cont WHERE plan_cuenta.Pla_Cod = perio_cont.Pla_Cod AND Pla_Est='A' AND Emp_Cod='$Par_Sql[0]' ORDER BY Pla_Fec DESC";
		//echo $sql;
                return $sql;
	}
}
?>