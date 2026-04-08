<?php
	/* Facturación inventario */
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{			
		    /**
			* Consulta los años de las facturas de compras recibidas 
			*/			
			case 1:			
			$sql = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras, perio_cont, plan_cuenta WHERE compras.Pec_Cod = perio_cont.Pec_Cod AND perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] AND compras.Cop_Est='A' 
						 ORDER BY YEAR(compras.Cop_Fec) DESC";
			return $sql;
			break;
			
			/**
			* Consulta base imponible todo el año 
			*/			
			case 2:			
			$sql = "SELECT 
				  persona.Prs_Ced,  
				  persona.Prs_Ape,
				  persona.Prs_Nom,
				  SUM(Cop_Imp - ((Cop_Imp * Cop_Des) / 100)) AS base
				FROM
				  compras
				  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE
				  persona.Prs_Ced = '$Par_Sql[1]' AND 
				  compras.Cop_Est = 'A' AND 
				  proveedore.Emp_Cod='$Par_Sql[0]' AND
				  Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'
				GROUP BY
				  persona.Prs_Ced,
				  persona.Prs_Ape,
				  persona.Prs_Nom";
			//echo $sql;	  
			return $sql;
			break;
			
			/**
			* Consulta renta todo el año 
			*/			
			case 3:			
			$sql = "SELECT 
					  persona.Prs_Ced,
					  persona.Prs_Ape,
					  persona.Prs_Nom,
					  SUM((Ret_Bas*Ren_Por)/100)as renta
					FROM
					  compras
					  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
					  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
					  INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
					  INNER JOIN persona ON (persona.Prs_Cod = proveedore.Prs_Cod)
					WHERE
					  persona.Prs_Ced = '$Par_Sql[1]' AND 
					  compras.Cop_Est = 'A' AND 
					  proveedore.Emp_Cod='$Par_Sql[0]' AND
					  Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'
					GROUP BY
					  persona.Prs_Ced,
					  persona.Prs_Ape,
					  persona.Prs_Nom";
			//echo "<br>".$sql;
			return $sql;
			break;			
			
			/**
			* Consulta la persona
			*/			
			case 4:			
			$sql = "SELECT persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom FROM persona WHERE persona.Prs_Ced = '$Par_Sql[0]'";
			//echo "<br>".$sql;
			return $sql;
			
			/**
			* Consulta general de renta todo el año 
			*/			
			case 5:			
			$sql = "SELECT 
					  persona.Prs_Ced,
					  persona.Prs_Ape,
					  persona.Prs_Nom,
					  SUM(Ret_Bas)as base,
					  SUM((Ret_Bas*Ren_Por)/100)as renta
					FROM
					  compras
					  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
					  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
					  INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
					  INNER JOIN persona ON (persona.Prs_Cod = proveedore.Prs_Cod)
					WHERE					 
					  compras.Cop_Est = 'A' AND 
					  proveedore.Emp_Cod='$Par_Sql[0]' AND
					  Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
					GROUP BY
					  persona.Prs_Ced,
					  persona.Prs_Ape,
					  persona.Prs_Nom";
			//echo "<br>".$sql;
			return $sql;
			break;
			
			/*Consultamos todas las cedulas de la compras en un rango de fecha */
			case 6:
			$sql="SELECT DISTINCT 
				  persona.Prs_Ced
				FROM
				  compras
				  INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  INNER JOIN persona ON (persona.Prs_Cod = proveedore.Prs_Cod)
				  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND
				  compras.Cop_Est = 'A' AND
				  renta_iva.Ren_Ret='R' AND
				  retencion.Ret_Est = 'A' AND 
				  compras.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'";
			//echo "<br>".$sql;
			return $sql;
			break;
		}
	}
?>