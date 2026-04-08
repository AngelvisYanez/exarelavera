<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author juanpuxito
 * @version 1.0
 * Fecha de actualización:	27-05-2014
 *
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package Exa.Facturacion - OFSERCONT
 */
function sentencias_tip($id,$Par_Sql)
{
	switch($id)
	{
		case 1: 
		/* 
		* consulta  si existe otro tipo de asiento
		*/
		$con_mar=  "SELECT Pla_Cod,Pla_Obs,Pla_Fec FROM plan_cuenta WHERE Emp_Cod = $Par_Sql[0]";	
		//echo $con_mar;
		return $con_mar;
		break;

		/* 
		* Registra el banco 
		*/		
		case 2:
		$sql= "INSERT INTO banco(Pld_Cod,Ban_Cue,Ban_Obs,Ban_Tip) VALUES (Trim('$Par_Sql[0]'),Trim('$Par_Sql[1]'),Trim(UPPER('$Par_Sql[2]')),Trim('$Par_Sql[3]'))";						
		return $sql;
		break;
		case 21:
		$sql= "INSERT INTO banco(Pld_Cod,Ban_Cue,Ban_Obs,Ban_Tip) VALUES ('$Par_Sql[Pld_Cod]','$Par_Sql[Ban_Cue]',Trim(UPPER('$Par_Sql[Ban_Obs]')),Trim('$Par_Sql[Ban_Tip]'))";
		//ChromePhp::log($sql);				
		return $sql;
		break;
		/* 
		* Busqueda del plan de cuentas por descripcion
		*/
		case 3:
		$sql= "SELECT Pld_Cod,Pld_Des,Pld_Cdc,if(Pld_Tip='D','Detalle','Grupo')as Pld_Tip FROM det_plan WHERE Pld_Tip='D' AND Pla_Cod='$Par_Sql[0]' AND Pld_Des LIKE '%$Par_Sql[1]%' ORDER BY Pld_Des ASC";	
		//echo $sql;
		return $sql;

		/* 
		* Carga datos de los tipos de comprobantes 
		*/		
		case 4:
		$sql= "SELECT Pld_Cod,Pld_Des,Pld_Cdc,if(Pld_Tip='D','Detalle','Grupo')as Pld_Tip FROM det_plan WHERE Pld_Tip='D' AND Pla_Cod='$Par_Sql[0]' AND Pld_Cdc=Trim('$Par_Sql[1]') ORDER BY Pld_Des ASC";		
		
		
		return $sql;
		break;
		case 41:
		$sql= "SELECT Pla_Cod,Emp_Cod FROM plan_cuenta WHERE Pla_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;
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
            //ChromePhp::log($bus_xmld_331);
		return $bus_xmld_331;
		case 5: 
		/*
		* consulta en la cuenta banco 
		*/
		$sql=  "SELECT Ban_Cod, Pld_Cod FROM banco WHERE Pld_Cod = '$Par_Sql[0]' AND Ban_Est ='A'";	
		//echo $sql;
		return $sql;
		break;

		/* 
		* Actualiza los datos del tipo comprobante 
		*/		
		case 6:
		$actualiza_tipo= "UPDATE banco SET Ban_Obs = Trim(UPPER('$Par_Sql[0]')), Ban_Cue= Trim(UPPER('$Par_Sql[1]')),Pld_Cod='$Par_Sql[2]',Ban_Est='$Par_Sql[3]',Ban_Tip='$Par_Sql[4]' WHERE Ban_Cod = $Par_Sql[5]";
	    //echo $actualiza_tipo;
		return $actualiza_tipo;
		break;
		
		case 7:
		$sql= "SELECT 
				  banco.Ban_Cod,
				  det_plan.Pld_Des,
				  det_plan.Pla_Cod,
				  banco.Ban_Cue,
				  if(banco.Ban_Est='A','Activo','Inactivo') AS Ban_Est,
				  if(det_plan.Pld_Tip='D','Detalle','Grupo')as Pld_Tip,
				  det_plan.Pld_Cdc
				FROM
				  det_plan
				  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
				WHERE det_plan.Pld_Des LIKE '%$Par_Sql[0]%' ORDER BY det_plan.Pld_Des ASC";	
		//echo $sql;
		return $sql;
		break;
		
		case 8:
		$elimina_tip= "UPDATE tipo_asien SET Tia_Est = Trim(UPPER('$Par_Sql[0]')) WHERE Tia_Cod = $Par_Sql[1]";	
		//echo $cons_tip;
		return $elimina_tip;
		break;
		
		case 9:
		$sql= "SELECT 
				  banco.Ban_Cod,
				  banco.Ban_Obs,
				  det_plan.Pld_Des,
				  det_plan.Pld_Cod,
				  banco.Ban_Cue,
				  banco.Ban_Tip,
				  if(banco.Ban_Est='A','Activo','Inactivo') AS Ban_Est,
				  if(det_plan.Pld_Tip='D','Detalle','Grupo')as Pld_Tip,
				  det_plan.Pld_Cdc, 
				  plan_cuenta.Pla_Obs
				FROM
				  det_plan
				  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
				  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
				WHERE  banco.Ban_Cod= '$Par_Sql[0]'";	
		//echo $sql;
		return $sql;
		break;
		
		case 10:
		$sql= "SELECT 
				  concat(forma_pago.For_Des, '-', tipos_pago.Pag_Des) AS tipo,
				  tipos_pago.Pag_Cod,
				  forma_pago.For_Cod
				FROM
				  forma_pago
				  INNER JOIN tipos_pago ON (forma_pago.For_Cod = tipos_pago.For_Cod)
				WHERE
				  forma_pago.For_Est = 'A' AND 
				  tipos_pago.Pag_Est = 'A'
				ORDER BY Pag_Cod";	
		//echo $sql;
		return $sql;
		break;
		
		/* 
		* Registra el tipo de pago
		*/		
		case 11:
		$sql= "INSERT INTO pago_plan(Pag_Cod,Ban_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";						
		//echo $sql."<br>";
		return $sql;
		break;
		
		case 12: 
		/* 
		* consulta pago plan activos
		*/
		$sql=  "SELECT Pag_Cod, Ban_Cod FROM pago_plan WHERE Pag_Cod = '$Par_Sql[0]' AND Ban_Cod = '$Par_Sql[1]' AND Pag_Est='A'";	
		//echo $sql;
		return $sql;
		break;
		
		case 13: 
		/* 
		* consulta pago plan activos/inactivos
		*/
		$sql=  "SELECT Pag_Cod, Ban_Cod,Pag_Est FROM pago_plan WHERE Pag_Cod = '$Par_Sql[0]' AND Ban_Cod = '$Par_Sql[1]'";	
		//echo $sql."<br>";
		return $sql;
		break;
		
		case 14:
		$sql= "UPDATE pago_plan SET Pag_Est = '$Par_Sql[0]' WHERE Pag_Cod = $Par_Sql[1] AND Ban_Cod = $Par_Sql[2]";	
		//echo $sql."<br>";
		return $sql;
		break;

        case 15:
		$sql= "SELECT 
				  banco.Ban_Cod,
				  det_plan.Pld_Des,
				  det_plan.Pla_Cod,
				  banco.Ban_Cue,
				  if(banco.Ban_Est='A','Activo','Inactivo') AS Ban_Est,
				  if(det_plan.Pld_Tip='D','Detalle','Grupo')as Pld_Tip,
				  det_plan.Pld_Cdc
				FROM
				  det_plan
				  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
				  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
				WHERE det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND plan_cuenta.Emp_Cod='$Par_Sql[1]' ORDER BY det_plan.Pld_Des ASC";	
		
		return $sql;
		break;
		case 411:
            $sql = "select banco.Pld_Cod, Pld_Des from banco 
					inner join det_plan ON det_plan.pld_cod = banco.pld_cod
					inner join plan_cuenta ON plan_cuenta.Pla_Cod = det_plan.Pla_cod
					where plan_cuenta.emp_cod = $_SESSION[Ses_Emp_Cod]";
            //ChromePhp::log($sql);
        break;
        
        case 16:
        // Lista todos los bancos activos de una empresa
        $sql= "SELECT 
				  banco.Ban_Cod,
				  banco.Ban_Obs,
				  det_plan.Pld_Des,
				  det_plan.Pld_Cod,
				  banco.Ban_Cue AS Bac_Cue,
				  banco.Ban_Tip,
				  if(banco.Ban_Est='A','Activo','Inactivo') AS Ban_Est,
				  if(det_plan.Pld_Tip='D','Detalle','Grupo')as Pld_Tip,
				  det_plan.Pld_Cdc, 
				  plan_cuenta.Pla_Obs
				FROM
				  det_plan
				  INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
				  INNER JOIN plan_cuenta ON (det_plan.Pla_Cod = plan_cuenta.Pla_Cod)
				WHERE plan_cuenta.Emp_Cod='$Par_Sql[0]' AND banco.Ban_Est='A'
				ORDER BY det_plan.Pld_Des ASC";	
		return $sql;
		break;
	}
}
?>