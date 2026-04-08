<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-06-08
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_tes($id,$Par_Sql)
{
	switch($id)
	{	
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
		 * Consulta la informaci�n la ciudada en base a la sucursal
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
		$carga_marcas= "SELECT Mar_Cod, Mar_Des FROM marca WHERE Emp_Cod = $Par_Sql[0] ORDER BY Mar_Des";
		//echo $carga_marcas;
		return $carga_marcas;
		break;
		/**
		* Carga iva 
		*/
		case 429:
		$carga_iva= "SELECT Iva_Por, Iva_Cod FROM iva WHERE Iva_Est='A'";
		return $carga_iva;
		break;
		/** 
		* Busca item de acuerdo a la categoria 
		*/
		case 432:
		$carg_item= "SELECT Cat_Cod, Cat_Des FROM categorias WHERE Emp_Cod = $Par_Sql[0]";
//		echo $carg_item;
		return $carg_item;
		break;
		/** 
		* Cargar la adquisicon 
		*/
		case 712:
		$adquisicion="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Cor , adquisicio.Adq_Des, adquisicio.Adq_Est FROM adquisicio WHERE adquisicio.Adq_Est='A'"; 
		//echo $adquisicion;
		return $adquisicion;
		break;

		/** 
		* Cargar los items
		*/
		case 713:
		$sql="SELECT item.Ite_Cod, item.Ite_Lar, Cat_Des FROM item, categorias WHERE categorias.Cat_Cod = item.Cat_Cod AND item.Ite_Est='A' AND categorias.Emp_Cod = $Par_Sql[0] ORDER BY Cat_Des, Ite_Lar"; 
		//echo $adquisicion;
		return $sql;
		break;


		case 1002: 
		/**
		* Consulta una forma de pago en base a un parametro
		*/
		//$busca_categoria_nom= "SELECT item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,Pro_Cod,Mar_Des, Pro_Est FROM item,categorias,producto,marca WHERE marca.Mar_Cod=producto.Mar_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=producto.Ite_Cod AND Ite_Lar LIKE '%$Par_Sql[0]%' AND categorias.Emp_Cod = $Par_Sql[1]";
		$busca_categoria_nom= "SELECT 
			    item.Ite_Cod,
				item.Ite_Cor,
				categorias.Cat_Des,
				item.Ite_Lar,
				categorias.Cat_Cod,
				producto.Pro_Cod,  
				producto.Pro_Obs,
				producto.Pro_Est,
				marca.Mar_Des,producto.Pre_Cod
			FROM
			  item
			  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
			  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
			  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
			WHERE
			  (producto.Pro_Obs LIKE '%$Par_Sql[0]%' or item.Ite_Lar LIKE '%$Par_Sql[0]%')  AND categorias.Emp_Cod = $Par_Sql[1]";
		
		//echo $busca_categoria_nom;
		return $busca_categoria_nom;
		break;

	 	case 1003: 
		/**
		* Consulta una forma de pago en base a un parametro
		*/
		$busca_categoria_nom= "SELECT Ubi_Cod,Ubi_Des FROM ubicacion WHERE Ubi_Est='A' AND Emp_Cod = $Par_Sql[0] ORDER BY Ubi_Cod ASC";
		return $busca_categoria_nom;
		break;
		
	    case 1004: 
		/**
		* Consulta una forma de pago en base a un parametro
		*/
		$busca_unidad_nom= "SELECT Uni_Cod,Uni_Des FROM unidad WHERE Uni_Est='A'";
		return $busca_unidad_nom;
		break;


		case 1005:
		/** 
		* Insertar formas de pago 
		*/
		$ins_item = "INSERT INTO item(Cat_Cod, Ite_Cor,Ite_Lar) VALUES ($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]')";
		return $ins_item;

		/**Consulta una forma de pago en base a un parametro*/
	 	case 1007: 
		$ins_producto= "INSERT INTO producto(Adq_Cod,Ite_Cod,Mar_Cod,Iva_Cod,Pro_Obs,Pro_Bar,Ubi_Cod,Uni_Cod,Pro_Sec,Pro_Cdc,Pro_Uni,Pro_Dsc,Pre_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],'$Par_Sql[4]','$Par_Sql[5]',$Par_Sql[6],$Par_Sql[7],$Par_Sql[8],'$Par_Sql[9]',$Par_Sql[10],$Par_Sql[11],$Par_Sql[12])";		
		return $ins_producto;
		break;
		/** 
		* Consultar si existen productos con las mismas marcas
		*/
		case 1008: 
		$busca_pro_mar= "SELECT Pro_Cod FROM producto WHERE Ite_Cod=$Par_Sql[0] AND Mar_Cod=$Par_Sql[1]";
		//echo $busca_pro_mar;
		return $busca_pro_mar;
		break;
		/**
		* Consultar si existen productos con las mismas Categorias
		*/
		case 1009: 
		$busca_ite_cat= "SELECT item.Ite_Cod FROM item WHERE  Ite_Lar='$Par_Sql[0]' AND Cat_Cod=$Par_Sql[1]";
		return $busca_ite_cat;
		break;

		 case 1010: 
  		/**
		* Consultar todas las tablas relacionadas con la tablaproducto
		*/
	    $busca_ite_cat= "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des, adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,precios.Pre_Cod,Pre_Pvp FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod AND precios.Pro_Cod=producto.Pro_Cod AND precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0]";
		//echo $busca_ite_cat;
	    return $busca_ite_cat;
	    break;

		case 1012: 
		$update_item= "UPDATE item SET Cat_Cod=$Par_Sql[1],Ite_Cor='$Par_Sql[2]',Ite_Lar='$Par_Sql[3]' WHERE Ite_Cod=$Par_Sql[0]";
		return $update_item;		
		break;
		
		/**
		* Inserta precios
		*/
		case 1018: 
		$ins_precio= "INSERT INTO precios(Pro_Cod,Pre_Pvp,Pre_Des,Suc_Cod,Tpv_Cod) VALUES ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]',$Par_Sql[3],'$Par_Sql[4]')";			
		return $ins_precio;
		break;
		/**
		* Consulta codigo de  por degfecto 
		*/
		case 1019: 		
		$con_tp_precio=  "SELECT Tpv_Cod,Tpv_Des,Tpv_Est,Tpv_Def FROM tipo_preci WHERE Tpv_Def='$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";					
		return $con_tp_precio;
		break;

		case 1021: 
		/**
		* Actualiza el estado de un producto
		*/
		$con_baj_ite="UPDATE producto SET Pro_Est='$Par_Sql[1]' WHERE Pro_Cod=$Par_Sql[0]";	
		return $con_baj_ite;
		break;
		
		/**
		* Consulta los item registrados por empresa 
		*/
		case 1022: 
		$busca_categoria_nom= "SELECT item.Ite_Cod,Ite_Cor,Cat_Des,Ite_Lar,item.Cat_Cod,Cat_Cdc FROM item,categorias WHERE item.Cat_Cod=categorias.Cat_Cod AND Ite_Lar LIKE '%$Par_Sql[0]%' AND categorias.Emp_Cod = $Par_Sql[1]";
		/*$busca_categoria_nom= "SELECT 
			  item.Ite_Cod,
			  item.Ite_Cor,
			  categorias.Cat_Des,
			  item.Ite_Lar,
			  categorias.Cat_Cod,
			  categorias.Cat_Cdc,
			  producto.Pro_Obs
			FROM
			  item
			  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
			  INNER JOIN categorias ON (item.Cat_Cod = categorias.Cat_Cod)
			WHERE
			  (producto.Pro_Obs LIKE '%$Par_Sql[0]%' or item.Ite_Lar LIKE '%$Par_Sql[0]%')  AND categorias.Emp_Cod = $Par_Sql[1]";*/
//		echo $busca_categoria_nom;
		return $busca_categoria_nom;
		break;
		/**
		* Actulizco solo el codigo de barra 
		*/
		case 1023: 
		$update_probar="UPDATE producto SET Pro_Bar='$Par_Sql[1]',Pro_Gen='$Par_Sql[2]' WHERE Pro_Cod=$Par_Sql[0]";	
		return $update_probar;
		break; 

		/**
		* Consulta la categorias solo el detalle 
		*/
		case 1030:
		$carg_item= "SELECT 
  categorias1.Cat_Des as Grupo, categorias.Cat_Cod,
  categorias.Cat_Des,
  categorias.Cat_Cdc
  
FROM
  categorias 
  LEFT JOIN categorias AS categorias1 ON (categorias.Cat_Rec = categorias1.Cat_Cod)
WHERE
  categorias.Cat_Tip = 'D' AND 
  categorias.Emp_Cod = $Par_Sql[0]
ORDER BY categorias1.Cat_Des";
		//echo $carg_item;
		return $carg_item;
		
		/**
		* Selecciona la secuencia m�xima de una categoria y la incrementa en uno
		*/
		case 1033:
		$sql = "SELECT MAX(Pro_Sec) as Pro_Sec FROM producto,item,categorias WHERE producto.Ite_Cod=item.Ite_Cod AND item.Cat_Cod=categorias.Cat_Cod AND categorias.Cat_Cod=$Par_Sql[0] ";
		//echo $sql;
		return $sql;
		break;

		/**
		* Consulta la secuencia de una categoria 
		*/
		case 1034:
		$sql = "SELECT Cat_Cdc FROM categorias WHERE categorias.Cat_Cod=$Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;

		case 1038: 
		/**
		* Actualiza el producto
		*/
		$update_producto= "UPDATE producto SET Adq_Cod=$Par_Sql[0],Mar_Cod=$Par_Sql[2],Iva_Cod=$Par_Sql[3],Pro_Obs='$Par_Sql[4]',Pro_Bar='$Par_Sql[5]',Ubi_Cod=$Par_Sql[6],Lin_Cod=$Par_Sql[7],Uni_Cod=$Par_Sql[8],Pro_Cdc='$Par_Sql[10]',Pro_Sec='$Par_Sql[11]',Pro_Uni='$Par_Sql[12]',Pro_Dsc='$Par_Sql[13]' WHERE Ite_Cod=$Par_Sql[1] AND Pro_Cod=$Par_Sql[9]";
		//echo $update_producto;
		return $update_producto;
		break;
		
		case 1205: 
  		/** 
		* Actualiza el stock del producto
		*/
  		$busca_ite_cat= "INSERT INTO stock(Stk_Can,Suc_Cod,Pro_Cod)VALUE($Par_Sql[0],$Par_Sql[1],$Par_Sql[2])";		
 		return $busca_ite_cat;
  		break;
		
		case 1206: 
  		/** 
		* consulta la presentacion del producto
		*/
  		$sql= "SELECT Pre_Cod, Pre_Des FROM presentaci WHERE Pre_Cod = '$Par_Sql[0]'";	
 		return $sql;
  		break;
		
		case 1207: 
  		/** 
		* consulta la presentacion del producto
		*/
  		$sql= "SELECT Pre_Cod, Pre_Des FROM presentaci WHERE Pre_Est = 'A'";	
 		return $sql;
  		break;
		
		case 1208: 
		/**
		* Lista las líneas existentes registradas por empresa
		*/
		$busca_lineas= "SELECT Lin_Cod,CONCAT(Lin_Abr,' - ',Lin_Des) AS Lin_Des FROM lineas WHERE Lin_Est='A' AND Emp_Cod = '$Par_Sql[0]' ORDER BY Lin_Cod ASC";
		return $busca_lineas;
		break;
		
		case 1209: 
		/**
		* Lista las líneas existentes registradas por empresa
		*/
		$producto_linea= "SELECT lineas.Lin_Cod,lineas.Lin_Des FROM producto,lineas WHERE producto.Pro_Cod ='$Par_Sql[0]' AND producto.Lin_Cod=lineas.Lin_Cod";
		return $producto_linea;
		break;
		
            case 1210: 
		/**
		* actualiza un precio
		*/
		$producto_linea= "UPDATE precios SET Pre_Pvp='$Par_Sql[3]' WHERE Suc_Cod=$Par_Sql[0] AND Pre_Cod=$Par_Sql[1] AND Pro_Cod=$Par_Sql[2] ";
		return $producto_linea;
		break;
		
	}
}
