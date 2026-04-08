<?php
	/**
	* Ajustes de inventario
	*/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{	
		  case 1:
		  /**
		  * Consulta los tipos de ajuste 
		  */
		  $Sql_ta="SELECT Tia_Cod,Tia_Des,Tia_Est, IF (Tia_Tra='I','INGRESO', 'EGRESO') AS Tia_Tra FROM tipo_ajus WHERE Tia_Est='A' AND Emp_Cod =  $Par_Sql[0] ORDER BY Tia_Tra DESC, Tia_Des ASC";
		// echo $Sql_ta;
		  return $Sql_ta;
		  break;
		  
                /** 
		* Consulta los datos del systema
		*/
		case 2:	
		$sql = "SELECT Sys_Cod,Sys_Nom,Sys_Ver,Sys_Des,Sys_Cor,concat('Ofsercont- ',Sys_Nom,' [',Sys_Des,']')as Sys_Tit FROM system";
		//echo $sql;
		return $sql;
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
		
			case 24:
			/**
			* Consulta del vendedor en base al codigo de la persona
			*/
			$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
								vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
			//echo $consultar_vendedor;
			return $consultar_vendedor;
			break;

			case 126: 
			/* Consulta la información la ciudada en base a la sucursal */
			$cargar_ciudad="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
							//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

			/**
			* Búsqueda de un proveedor por apellido 
			*/
			case 487:
			$bus_proa="SELECT Prv_Cod, Prs_Ced, Prs_Ape, Prs_Nom, Prv_Fax, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est FROM proveedore, persona WHERE Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Emp_Cod = $Par_Sql[1] AND proveedore.Prs_Cod=persona.Prs_Cod ORDER BY persona.Prs_Ape ASC";
			//echo $bus_proa;
			return $bus_proa;
			break;

		    /**
			* Búsqueda de un proveedor por Cédula 
			*/
			case 702:
			$bus_proc_702="SELECT proveedore.Prv_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom, proveedore.Prv_Fax,
		            IF (Prv_Est='A','Activo','Inactivo') as Prv_Est
                   FROM proveedore INNER JOIN persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ced= '$Par_Sql[0]' AND proveedore.Emp_Cod = $Par_Sql[1]";
                        //echo bus_proc_702;
			return $bus_proc_702;
			break;

			/**
			* Consulta de los datos del proveedor 
			*/
			case 708:	
			$consultar_proveedore_708 = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
							persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, proveedore.Prv_Cod
							FROM proveedore, persona WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Cod = $Par_Sql[0]";
			return $consultar_proveedore_708;
			break;

			case 1035:
			/**
			* Inserta datos en el kardex
			*/
			$sql = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],0)";
			//echo  $sql;
			return $sql;
			break;

			/**
			*  Verifica que un producto sea de tipo BIEN
			*/
			case 1037:
				$sql = "SELECT adquisicio.Adq_Cod, producto.Iva_Cod FROM producto,adquisicio WHERE producto.Adq_Cod=adquisicio.Adq_Cod AND adquisicio.Adq_Cor='B' AND producto.Pro_Cod=$Par_Sql[0]";
				//echo $sql.'<br>';
			return $sql;		
			break;

			case 1040: 
			  /*Consultar todas las tablas relacionadas con la tablaproducto*/
			  $busca_ite_cat="SELECT 
			  precios.Tpv_Cod,
			  item.Ite_Cod,
			  item.Ite_Est,
			  categorias.Cat_Cod,
			  categorias.Cat_Des,
			  item.Ite_Cor,
			  item.Ite_Lar,
			  marca.Mar_Cod,
			  marca.Mar_Des,
			  adquisicio.Adq_Cod,
			  adquisicio.Adq_Des,
			  adquisicio.Adq_Cor,
			  iva.Iva_Cod,
			  iva.Iva_Por,
			  producto.Pro_Bar,
			  producto.Pro_Obs,
			  producto.Pro_Cod,
			  producto.Pro_Est,
			  producto.Pro_Gen,
			  producto.Pro_Cdc,
			  producto.Pro_Sec,
			  stock.Stk_Can,
			  precios.Pre_Pvp
			FROM
			  categorias
			  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
			  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
			  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
			  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
			  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
			  INNER JOIN stock ON (producto.Pro_Cod = stock.Pro_Cod)
			  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
			WHERE
			  precios.Pre_Est = 'A' AND 
			  producto.Pro_Bar='$Par_Sql[0]' AND precios.Suc_Cod = $Par_Sql[1] AND adquisicio.Adq_Cor = 'B' ORDER BY item.Ite_Lar";
		  return $busca_ite_cat;
		  break;		    	

		  case 1050:
		  /**
		  * Consulta los tipos de ajuste 
		  */
		  $Sql_ta="SELECT Tia_Cod,Tia_Des,Tia_Est,Tia_Tra FROM tipo_ajus WHERE Tia_Est='A' AND Emp_Cod =  $Par_Sql[0] AND Tia_Tra = '$Par_Sql[1]' ORDER BY Tia_Des ASC";
		  //echo $Sql_ta.'<br>';
		  return $Sql_ta;
		  break;
			
		  case 1051:
		  /**
		  * Insercion del ajuste 
		  */
		  $sql="INSERT INTO ajuste_kar(Aju_Fec,Aju_Hor,Aju_Det,Aju_Obs,Aju_Num,Aju_Sec,Tia_Cod,Prv_Cod,Vnd_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]',
						'$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',$Par_Sql[5],$Par_Sql[6],$Par_Sql[7],$Par_Sql[8])";
		  //echo $sql.'<br>';
		  return $sql;
		  break;

		  case 1052:
		  /**
		  * Inserta los datos en la tabla de detalle de ajuste
		  */
		   $Sql_ta="INSERT INTO det_ajustek(Aju_Cod,Pro_Cod,Aju_Can,Aju_Pru,Aju_Imp) VALUES ($Par_Sql[0],$Par_Sql[1],
						$Par_Sql[2],$Par_Sql[3],$Par_Sql[4])";
		  //echo $Sql_ta.'<br>';
		  return $Sql_ta;
		  break;

		  case 1054: 
		  /**
		  * Consultar todos los productos que son bienes 
		  */
		  /*$busca_ite_cat= "SELECT precios.Tpv_Cod,item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des,Adq_Cor, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Stk_Can,Pre_Pvp FROM item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios,tipo_preci,stock WHERE item.Cat_Cod=categorias.Cat_Cod AND producto.Ite_Cod=item.Ite_Cod AND producto.Mar_Cod= marca.Mar_Cod AND  ubicacion.Ubi_Cod= producto.Ubi_Cod AND  unidad.Uni_Cod= producto.Uni_Cod AND  adquisicio.Adq_Cod= producto.Adq_Cod AND producto.Iva_Cod=iva.Iva_Cod   AND  precios.Pro_Cod=producto.Pro_Cod AND precios.Tpv_Cod=tipo_preci.Tpv_Cod AND precios.Pre_Est='A' AND tipo_preci.Tpv_Def='D' AND producto.Pro_Cod=stock.Pro_Cod AND item.Ite_Lar  LIKE '%$Par_Sql[0]%'";*/
                    $search=""; 
                    $array=explode(" ",strtoupper($Par_Sql[0]));
                    foreach($array as $ar){
                        if(!empty($ar) && $ar!='') $search.=(($search!=''?" AND ":"")."CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs)) AS CHAR)LIKE '%$ar%'");                    
                    }
                    if($search=='') $search="1=1";
		  $busca_ite_cat="SELECT 
		  precios.Tpv_Cod,
		  item.Ite_Cod,
		  item.Ite_Est,
		  categorias.Cat_Cod,
		  categorias.Cat_Des,
		  item.Ite_Cor,
		  item.Ite_Lar,
		  marca.Mar_Cod,
		  marca.Mar_Des,
		  adquisicio.Adq_Cod,
		  adquisicio.Adq_Des,
		  adquisicio.Adq_Cor,
		  iva.Iva_Cod,
		  iva.Iva_Por,
		  producto.Pro_Bar,
		  producto.Pro_Obs,
		  producto.Pro_Cod,
		  producto.Pro_Est,
		  producto.Pro_Gen,
		  producto.Pro_Cdc,
		  producto.Pro_Sec,
		  stock.Stk_Can,
		  precios.Pre_Pvp
		FROM
		  categorias
		  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
		  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
		  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
		  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
		  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod)
		  INNER JOIN stock ON (producto.Pro_Cod = stock.Pro_Cod)
		  INNER JOIN precios ON (producto.Pro_Cod = precios.Pro_Cod)
		WHERE
		  precios.Pre_Est = 'A' AND 
		  ( $search /*item.Ite_Lar LIKE '%$Par_Sql[0]%' or producto.Pro_Obs LIKE '%$Par_Sql[0]%'*/) AND precios.Suc_Cod = $Par_Sql[1] AND adquisicio.Adq_Cor = 'B' ORDER BY item.Ite_Lar";
		  //echo $busca_ite_cat;
		  return $busca_ite_cat;
		  break;                

		  case 1055: 
		  /**
		  * Consulta para generar un codigo  
		  */
		  $busca_ite_cat = "SELECT MAX(Aju_Sec) as Aju_Sec FROM ajuste_kar WHERE Tia_Cod=$Par_Sql[0]";
		 // echo $busca_ite_cat;
		  return $busca_ite_cat;
		  break; 

		  case 1056: 
		  /**
		  * Consulta por tipo de comprabente y fecha la transaccion 
		  */
		  $busca_ite_cat= "SELECT Aju_Cod,Aju_Sec,Aju_Est,Tia_Cod,Aju_Fec,Aju_Det,Aju_Obs,Prs_Ape,Prs_Nom,Prs_Ced FROM ajuste_kar,persona,proveedore WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=ajuste_kar.Prv_Cod AND Tia_Cod=$Par_Sql[0] AND Aju_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'";
		  //echo  $busca_ite_cat;
		  return $busca_ite_cat;
		  break;

		  case 1057: 
		  /**
		  * Consulta datos de l detalle del ajuste
		  */
		  $busca_ite_cat= "SELECT Aju_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Aju_Can,Aju_Pru,Aju_Imp FROM det_ajustek,producto,item WHERE det_ajustek.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod AND Aju_Cod='$Par_Sql[0]'";
		  //echo $busca_ite_cat.'<br>';
		  return $busca_ite_cat;
		  break;

		  case 1058: 
		  /**
		  * Anulo los comprobantes de ajustes
		  */
		  $sql= "UPDATE ajuste_kar SET Aju_Est='I' WHERE Aju_Cod='$Par_Sql[0]'";
		  //echo "<br>".$sql;
		  return $sql;
		  break;

		  case 1059: 
		  /**
		  * Anulo las lineas de kardex del ajuste
		  */
		  $sql= "UPDATE kardex_ie SET Kar_Est='I' WHERE Aju_Cod='$Par_Sql[0]'";
		 // echo "<br>".$sql;
		  return $sql;
		  break;

			case 1063: 
			/**
			* Consulta por tipo de comprabente y fecha la transaccion 
			*/
			$busca_ite_cat= "SELECT Aju_Cod,Aju_Sec,Aju_Est,Prs_Dir,Aju_Num,Tia_Tra,tipo_ajus.Tia_Cod,Ciu_Des,Tia_Des,Aju_Fec,Aju_Det,Aju_Obs,Prs_Ape,Prs_Nom,Prs_Ced,Vnd_Cod FROM ajuste_kar,persona,proveedore,tipo_ajus,ciudad WHERE ciudad.Ciu_Cod=persona.Ciu_Cod AND tipo_ajus.Tia_Cod=ajuste_kar.Tia_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=ajuste_kar.Prv_Cod AND ajuste_kar.Aju_Cod=$Par_Sql[0] ";
			
			return $busca_ite_cat;
			break;

    		case 1064: 
  			/**
			* Consulta de ajustes 
			*/
 			 $busca_ite_cat= "SELECT Aju_Cod,producto.Pro_Cod,Ite_Lar,Aju_Can,Aju_Pru,Aju_Imp FROM det_ajustek,item,producto WHERE det_ajustek.Aju_Cod=$Par_Sql[0] AND det_ajustek.Pro_Cod=producto.Pro_Cod AND item.Ite_Cod=producto.Ite_Cod ";
  			return $busca_ite_cat;
  			break;

			case 1066:
			/** 
			* Consulta del vendedor en base al codigo de la persona
			*/
			$consultar_vendedor = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod,Prs_Nom, Prs_Ape FROM vendedor,persona WHERE persona.Prs_Cod=vendedor.Prs_Cod AND vendedor.Vnd_Cod = $Par_Sql[0]";
			return $consultar_vendedor;
			break;
			
			case 1204: 
  			/**
			* Consulta sentencia consulto stock del kardex  
			*/
  			$sql= "UPDATE stock SET Stk_Can='$Par_Sql[0]' WHERE Pro_Cod='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[2]'" ;			
			//echo $sql.'<br>';
	 		return $sql;
  			break;

  			case 12044: 
  			/**
			* Consulta sentencia consulto stock del kardex  
			*/
  			$sql= "UPDATE producto SET Pro_Stk='$Par_Sql[0]' WHERE Pro_Cod='$Par_Sql[1]' " ;			
			//echo $sql.'<br>';
	 		return $sql;
  			break;

			case 1206: 
			/**
			* Consulta sentencia consulto stock del kardex  
			*/
			$sql= "SELECT Pro_Cod,SUM(Kar_Sal)as Kar_Sal,(SUM(Kar_Can)-SUM(Kar_Sal)) AS stock FROM kardex_ie WHERE Pro_Cod='$Par_Sql[0]' AND Kar_Est='A' GROUP BY Pro_Cod";			
			//echo $sql.'<br>';
			return $sql;
			
			case 1207: 
		  /**
		  * Consulta por tipo de comprabente y fecha la transaccion 
		  */
		  $sql= "SELECT proveedore.Prv_Cod,Aju_Cod,Aju_Sec,Aju_Est, Aju_Num, tipo_ajus.Tia_Cod,Aju_Fec,Aju_Det,Aju_Obs,Prs_Ape,Prs_Nom,Prs_Ced, tipo_ajus.Tia_Des FROM ajuste_kar,persona,proveedore, tipo_ajus WHERE ajuste_kar.Tia_Cod = tipo_ajus.Tia_Cod AND  persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod = ajuste_kar.Prv_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Emp_Cod = $Par_Sql[1] $Par_Sql[2] AND YEAR(Aju_Fec) = '$Par_Sql[3]'";
		  //echo  $sql.'<br>';
		  return $sql;
		  break;
		  
		case 1208: 
		/**
		* Consulta los años activos 
		*/
		$sql= "SELECT DISTINCT YEAR(Aju_Fec) AS ann FROM ajuste_kar WHERE Aju_Est='A'";			
		//echo $sql.'<br>';
		return $sql;
		  
			/**
			* 	Consulta la provicia y pais de la ciudad de la sucursal
			*/
			case 934:
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
			* 	Consulta la información la ciudada en base a la sucursal
			*/
			case 935:
				$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
				sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
				return $sql;
			break;
			
			/**
		   *  Consulta los datos del usuario
		   */
		   case 936:
			$sql="SELECT Prs_Ape, Prs_Nom, Prs_Ced FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
		   break;
		}
	}
?>