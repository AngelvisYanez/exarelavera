
<?php



	/**
	 * TESORERIA
	 */
	function sentencias_pre($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			 * Consulta para devolver el listado de tipos de precios que existen
			 */
			case 1: 
		    	$sql= "SELECT Tpv_Cod,Tpv_Des FROM tipo_preci where Suc_Cod = '$Par_Sql[0]'";
	  			return $sql;
  			break;
			
			/**
			 * Consulta de todos los productos disponibles
			 */
			case 2: 
				/*$sql= "SELECT stock.Stk_Can,item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,producto.Pro_Cod,Mar_Des, Pro_Est,Pre_Pvp FROM item,categorias,producto,marca,precios,stock 
				WHERE marca.Mar_Cod=producto.Mar_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=producto.Ite_Cod AND precios.Pro_Cod = producto.Pro_Cod AND precios.Pre_Est = 'A' AND precios.Tpv_Cod = $Par_Sql[0] AND producto.Pro_Est = 'A' AND stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod='$Par_Sql[1]' order by Ite_Lar";*/
				$sql="
					SELECT 
  stock.Stk_Can,
  item.Ite_Cod,
  item.Ite_Cor,
  categorias.Cat_Des,
  item.Ite_Lar,
  categorias.Cat_Cod,
  producto.Pro_Cod,
  producto.Pro_Obs,
  marca.Mar_Des,
  producto.Pro_Est,
  precios.Pre_Pvp,
  iva.Iva_Por
FROM
  item
  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
  INNER JOIN stock ON (producto.Pro_Cod = stock.Pro_Cod)
  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
					WHERE
					  precios.Pre_Est = 'A' AND precios.Tpv_Cod = $Par_Sql[0] AND producto.Pro_Est = 'A' AND stock.Suc_Cod='$Par_Sql[1]' order by Ite_Lar";
				
				return $sql;
			break;
			
		    /**
			 * Consulta de todos los productos disponibles
			 */
			case 3: 
				//$sql= "SELECT stock.Stk_Can,item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,producto.Pro_Cod,Mar_Des, Pro_Est,Pre_Pvp FROM item,categorias,producto,marca,precios,stock WHERE marca.Mar_Cod=producto.Mar_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=producto.Ite_Cod AND precios.Pro_Cod = producto.Pro_Cod AND precios.Pre_Est = 'A' AND precios.Tpv_Cod = $Par_Sql[0] AND Ite_Lar like concat('$Par_Sql[1]','%') AND producto.Pro_Est = 'A' AND stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod='$Par_Sql[2]' order by Ite_Lar";
				$sql="
					SELECT 
  stock.Stk_Can,
  item.Ite_Cod,
  item.Ite_Cor,
  categorias.Cat_Des,
  item.Ite_Lar,
  categorias.Cat_Cod,
  producto.Pro_Cod,
  producto.Pro_Obs,
  marca.Mar_Des,
  producto.Pro_Est,
  precios.Pre_Pvp,
  iva.Iva_Por
FROM
  item
  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
  INNER JOIN stock ON (producto.Pro_Cod = stock.Pro_Cod)
  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
					WHERE
					  precios.Pre_Est = 'A' AND precios.Tpv_Cod = $Par_Sql[0] AND (item.Ite_Lar like '$Par_Sql[1]%' OR producto.Pro_Obs like '$Par_Sql[1]%') AND producto.Pro_Est = 'A' AND stock.Suc_Cod='$Par_Sql[2]' order by Ite_Lar";								
				return $sql;
			break;
			
			/**
			 * Consulta para devolver el listado de tipos de precios que existen
			 */
			case 4: 
			    $sql= "SELECT Tpv_Cod,Tpv_Des FROM tipo_preci where Tpv_Cod = '$Par_Sql[0]'";
		  		return $sql;
	  		break;	
		
	  		/**
	  		 * SENTECIAS UTILILES EN REPORTES PARA CABECERAS
	  		 * Consulta que permite cargar el nombre de la empresa a que pertenece el usuario
	  		 */
			case 5:
				$sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2,
				 			   sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des,empresas.Emp_Log FROM empresas, sucursal, ciudad 
				 			   WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0] AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
				return  $sql;
			break;	
			
			/**
		 * Consulta la provicia y pais de la ciudad de la sucursal
		 */
		case 21:
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
		 * Consulta la información la ciudada en base a la sucursal
		 */
		case 22:
			$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
		break;
		
		/**
		 * Consulta los datos del usuario
		 */
		case 23:
			$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
		break;
			
			/**
			 * Carga todas las marcas
			 */
			case 428:
				$sql= "SELECT Mar_Cod, Mar_Des FROM marca";
				return $sql;
			break;

			/**
			 * Carga iva 
			 */
			case 429:
				$sql= "SELECT Iva_Por, Iva_Cod FROM iva WHERE Iva_Est='A'";
				return $sql;
			break;
		
			/**
			 * busca item de acuerdo a la categoria
			 */
			case 432:
				$sql= "SELECT Cat_Cod, Cat_Des FROM categorias";
				return $sql;
			break;
				
			/**
			 * carga producto para modificacion JESSICA
			 */
			case 437:
				$sql= "SELECT producto.Pro_Cod, Mar_Des, Cat_Des, Pro_Ide, producto.Mar_Cod, Ite_Cor, Ite_Lar, Pro_Est,
				Pro_Obs, iva.Iva_Por, item.Ite_Cod, categorias.Cat_Cod,Pro_Dsc FROM item, marca,producto, iva, categorias
				WHERE marca.Mar_Cod= producto.Mar_Cod
				AND producto.Ite_Cod = item.Ite_Cod
				AND iva.Iva_Cod = producto.Iva_Cod
				AND item.Ite_Cod=producto.Ite_Cod
				AND item.Cat_Cod = categorias.Cat_Cod
				AND producto.Pro_Cod= '$Par_Sql[0]'";
				return $sql;
			break;
				
			
			/**
			 * Busca codigo del Item po el codigo del producto JESSICA 16-01-2007
			 */
			case 462:
				/*$sql= "SELECT 
						 item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,Pro_Cod,Mar_Des, Pro_Est 
						 FROM item,categorias,producto,marca 
						 WHERE marca.Mar_Cod=producto.Mar_Cod 
						 AND item.Cat_Cod=categorias.Cat_Cod 
						 AND item.Ite_Cod=producto.Ite_Cod 
						 AND producto.Pro_Cod = '$Par_Sql[0]'
						 AND categorias.Emp_Cod = '$Par_Sql[1]'";*/
				$sql="SELECT 
						  item.Ite_Cod,
						  item.Ite_Cor,
						  categorias.Cat_Des,
						  item.Ite_Lar,
						  categorias.Cat_Cod,
						  producto.Pro_Cod,
						  producto.Pro_Obs,
						  marca.Mar_Des,
						  producto.Pro_Est
						FROM
						  item
						  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
						  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
						  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
						WHERE
						  producto.Pro_Cod = '$Par_Sql[0]'
						  AND categorias.Emp_Cod = '$Par_Sql[1]'
						ORDER BY
						  item.Ite_Lar";		 					
				return $sql;
			break;
		
			/**
			 * historial de precios segun sucursal
			 */
			case 467:
	  		$sql= "SELECT
	  					precios.Pre_Des, precios.Pre_Pvp, precios.Pre_Cod,tipo_preci.Tpv_Des,precios.Pre_Fec,Pre_Est,Pre_Ini,Pre_Fin
				   FROM item, marca,producto, iva,precios,tipo_preci 
				   WHERE precios.Tpv_Cod=tipo_preci.Tpv_Cod 
				   AND marca.Mar_Cod= producto.Mar_Cod 
				   AND producto.Ite_Cod = item.Ite_Cod 
	 			   AND iva.Iva_Cod = producto.Iva_Cod 
	 			   AND producto.Pro_Cod = precios.Pro_Cod 
	 			   AND item.Ite_Cod=producto.Ite_Cod 
	 			   AND producto.Pro_Cod = '$Par_Sql[0]'
	 			   AND precios.Suc_Cod = '$Par_Sql[1]'
	 			   ORDER BY precios.Pre_Cod DESC;";

			return $sql;

			break;
		
			/**
			 * Consulta de un producto por la descripcion segun empresa
			 */
			case 1002:
				/*$sql= "SELECT 
							item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,
							item.Cat_Cod,Pro_Cod,Mar_Des,Pro_Est 
					   FROM item,categorias,producto,marca 
					   WHERE marca.Mar_Cod=producto.Mar_Cod 
					   AND item.Cat_Cod=categorias.Cat_Cod 
					   AND item.Ite_Cod=producto.Ite_Cod 
					   AND (Ite_Lar LIKE '%$Par_Sql[0]%' OR Ite_Cor LIKE '%$Par_Sql[0]%')
					   AND categorias.Emp_Cod = '$Par_Sql[1]'";*/
					   
				$sql="SELECT 
						  item.Ite_Cod,
						  item.Ite_Cor,
						  categorias.Cat_Des,
						  item.Ite_Lar,
						  categorias.Cat_Cod,
						  producto.Pro_Cod,
						  producto.Pro_Obs,
						  marca.Mar_Des,
						  producto.Pro_Est
						FROM
						  item
						  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
						  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
						  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
						WHERE
						  (item.Ite_Lar LIKE '%$Par_Sql[0]%' OR producto.Pro_Obs LIKE '%$Par_Sql[0]%')
					   	  AND categorias.Emp_Cod = '$Par_Sql[1]' AND producto.Pro_Est='A' 
						ORDER BY
						  item.Ite_Lar";				
				return $sql;
			break;
		
			/**
			 * inserta nuevo precio
			 */
			case 1100:
				$sql= " INSERT  INTO  precios(Pro_Cod,Pre_Pvp,Tpv_Cod,Pre_Fec,Pre_Com,Pre_Por,Pro_Uti,Pre_Ini,Pre_Fin,Suc_Cod)VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]')";
				return $sql;
			break;
		
			/**
			 * actualiza precios
			 */
			case 1201:
				$sql= " UPDATE precios SET Pre_Est='I' WHERE Pro_Cod=$Par_Sql[0] AND Tpv_Cod=$Par_Sql[1] ";
				return $sql;
			break;
			
			/**
			 * Obtiene los tipos de precios segun la sucursal seleccionada
			 */
			case 1099: 
	    		$sql= "SELECT Tpv_Cod,Tpv_Des FROM tipo_preci WHERE Suc_Cod = '$Par_Sql[0]';";
	    		return $sql;
	  		break;

		}
	}
?>