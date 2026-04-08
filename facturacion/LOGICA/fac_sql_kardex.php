<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-06-08
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2013-01-08
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_kar($id,$Par_Sql)
{
	switch($id)
	{
		
		case 207:
		/* SENTECIAS UTILILES EN REPORTES PARA CABECERAS */
		/* Consulta que permite cargar el nombre de la empresa a que pertenece el usuario */
		$cabecera_empresa = "SELECT
								 empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des 
							FROM 
								empresas, sucursal, ciudad 
							WHERE 
								empresas.Emp_Cod = sucursal.Emp_Cod AND 
								sucursal.Suc_Cod = $Par_Sql[0] AND 
								sucursal.Ciu_Cod = ciudad.Ciu_Cod";
		return  $cabecera_empresa;
		break;
		
		 case 1010: 
		/**
		* Consultar todas las tablas relacionadas con la tablaproducto
		*/
		$sql= "SELECT 
				item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp 
				FROM 
					item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios 
				WHERE 
					item.Cat_Cod=categorias.Cat_Cod AND 
					producto.Ite_Cod=item.Ite_Cod AND 
					producto.Mar_Cod= marca.Mar_Cod AND  
					ubicacion.Ubi_Cod= producto.Ubi_Cod AND  
					unidad.Uni_Cod= producto.Uni_Cod AND  
					adquisicio.Adq_Cod= producto.Adq_Cod AND 
					producto.Iva_Cod=iva.Iva_Cod AND 
					precios.Pro_Cod=producto.Pro_Cod AND 
					precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0]"; //Antes AND precios.Tpv_Cod=1
		return $sql;
		break;
		
		case 1040: 
	  	/**
		* Consultar todas las tablas relacionadas con la tablaproducto
		*/
		$sql= "SELECT 
				item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec 
			   FROM 
			   	item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva 
			   WHERE 
			   	item.Cat_Cod=categorias.Cat_Cod AND 
				producto.Ite_Cod=item.Ite_Cod AND 
				producto.Mar_Cod= marca.Mar_Cod AND  
				ubicacion.Ubi_Cod= producto.Ubi_Cod AND  
				unidad.Uni_Cod= producto.Uni_Cod AND  
				adquisicio.Adq_Cod= producto.Adq_Cod AND 
				producto.Iva_Cod=iva.Iva_Cod  AND 
				producto.Pro_Bar='$Par_Sql[0]' AND 
				categorias.Emp_Cod = $Par_Sql[1]";
	  	return $sql;
  		break;		    	

		case 1041: 
		/**
		* Consultar todas las tablas relacionadas con la tablaproducto
		*/
		$sql= "SELECT 
					item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec 
				FROM 
					item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva 
				WHERE 
					item.Cat_Cod=categorias.Cat_Cod AND 
					producto.Ite_Cod=item.Ite_Cod AND 
					producto.Mar_Cod= marca.Mar_Cod AND  
					ubicacion.Ubi_Cod= producto.Ubi_Cod AND  
					unidad.Uni_Cod= producto.Uni_Cod AND  
					adquisicio.Adq_Cod= producto.Adq_Cod AND 
					producto.Iva_Cod=iva.Iva_Cod  AND 
					item.Ite_Lar  LIKE '%$Par_Sql[0]%' AND 
					categorias.Emp_Cod = $Par_Sql[1]";
		return $sql;
		break;

		case 1042: 
		/**
		* Con esta sentencia consulto el movimiento del kardex con fechas 
		*/
		$sql= "SELECT Vet_Cod,Aju_Cod,Cop_Cod,Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock
		, (Kar_Ime-Kar_Ims)+(((((Kar_Ime-Kar_Ims)-(((Kar_Ime-Kar_Ims)*Kar_Des)/100)))*Iva_Por)/100) AS Saldo
		, (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_ent
		, (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_sal
			FROM 
				kardex_ie,iva 
			WHERE 
				Kar_Est='A' AND 
				Pro_Cod=$Par_Sql[2]  AND 
				Kar_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'AND 
				iva.Iva_Cod=kardex_ie.Iva_Cod 
			ORDER BY Kar_Fec";
		//echo $sql;
		return $sql;
		break;
		
		case 1043: 
		/**
		* Consulta la cantidad del producto por fecha 0000-00-00 
		*/
		$sql= " SELECT SUM(Kar_Can)-SUM(Kar_Sal) AS Stock FROM kardex_ie  WHERE  Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1]";
		return $sql;
		break;

		case 1044: 
		/**
		* Consulto las ventas para cargar al kardex  
		*/
		$sql= "SELECT  ventas.Vet_Num,Vet_Des,Tic_Des,Vet_Obs FROM ventas,kardex_ie,tipo_compr WHERE  kardex_ie.Vet_Cod=ventas.Vet_Cod AND tipo_compr.Tic_Cod=ventas.Tic_cod AND ventas.Vet_Cod=$Par_Sql[0]";
		return $sql;
		break;	    	

		case 1045: 
		/**
		* Consulto las compras para cargar al kardex  
		*/
		$sql= "SELECT  compras.Cop_Cod,compras.Cop_Num,compras.Cop_Obs FROM compras,kardex_ie,tipo_compr WHERE  kardex_ie.Cop_Cod=compras.Cop_Cod AND tipo_compr.Tic_Cod=compras.Tic_cod AND compras.Cop_Cod=$Par_Sql[0]";
		return $sql;
		break;
				
		case 1046: 
		/**
		* Consulto los ajustes para cargar al kardex  
		*/
		$sql= "SELECT  ajuste_kar.Aju_Cod,ajuste_kar.Aju_Obs,Tia_Des,Aju_Sec FROM ajuste_kar,kardex_ie,`tipo_ajus` WHERE tipo_ajus.Tia_Cod=ajuste_kar.`Tia_Cod` AND  kardex_ie.Aju_Cod=ajuste_kar.Aju_Cod AND ajuste_kar.Aju_Cod=$Par_Sql[0]";
		return $sql;  
		break;

		case 1047: 
		/**
		* Consulta la cantidad del producto por fecha 0000-00-00 
		*/
		$sql= " SELECT (Kar_Ime-Kar_Ims)+((  ((Kar_Ime-Kar_Ims)- (((Kar_Ime-Kar_Ims)*Kar_Des)/100)) *Iva_Por)/100) AS Saldo FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod" ;
		return $sql;
		break;  
		
		
		
		
		
		
		 /**
	   *  Consulta la provicia y pais de la ciudad de la sucursal
	   */
		   case 5000:
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
			//echo $sql;
			return $sql;
		   break;
						
		  /**
		   *  Consulta la información la ciudada en base a la sucursal
		   */
		   case 5001:
			$sql="SELECT 
					empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log 
				FROM 
					empresas, sucursal, ciudad 
				WHERE 
					sucursal.Suc_Cod = $Par_Sql[0] AND 
					empresas.Emp_Cod = sucursal.Emp_Cod AND 
					sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $sql;
			return $sql;
		   break;
					   		   
			 /**
			 *  Consulta los datos del usuario
			 */
			case 5002:
				$sql="SELECT 
						Prs_Ape, Prs_Nom, Prs_Ced 
					 FROM 
						persona, usuarios 
					 WHERE 
						persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				//echo $sql;
				return $sql;
			 break;
					 
			/**
			   * Consulta los datos del usuario
			   */
			  case 5003:
			   $sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			   return $sql;
			  break;
		
		case 5004:
			$sql="SELECT DISTINCT Vnd_Cod,CONCAT(Prs_Ape,' ',Prs_Nom)AS Vendedor FROM vendedor
					INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
					INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
					INNER JOIN persona ON vendedor.Prs_Cod=persona.Prs_Cod
					WHERE sucursal.Suc_Cod=$Par_Sql[0]";		
			return $sql;

		case 5005:
			$alfa=(($Par_Sql[1]=='A'&&$Par_Sql[2]=='Z')||(empty($Par_Sql[1])||empty($Par_Sql[2]))?'':" AND UPPER(Ite_Lar) BETWEEN '$Par_Sql[1]A%' AND '$Par_Sql[2]Z%' ");
			$sql="SELECT Emp_Cod,item.Ite_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,IF(Pro_Stk IS NULL,0,Pro_Stk)AS Pro_Stk,IF(Pro_Prp IS NULL,0,Pro_Prp)AS Pro_Prp,Stk_Can,Stk_Prp,(Stk_Can*Stk_Prp)AS Stk_Sal FROM producto
					INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod 
					INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
										LEFT JOIN stock ON stock.Pro_Cod=producto.Pro_Cod
					WHERE Emp_Cod=$Par_Sql[0] $alfa /*AND producto.Pro_Cod=1602*/ ORDER BY Ite_Lar; ";
			//echo $sql.'df sdfdf<br/>';dsfdfs
			return $sql;
		
		
		
		
		
		
		
		
				
		
	}
}
?>