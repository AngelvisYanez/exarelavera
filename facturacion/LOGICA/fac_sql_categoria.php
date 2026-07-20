<?php
	/* Facturación inventario */
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{			
			/**
			* Devuelve las categorias dentro de otra categoria 
			*/
			case 1:
			$sql = "SELECT Cat_Cod FROM categorias WHERE Cat_Rec=$Par_Sql[0] LIMIT 0,1";
			//echo $sql;
			return $sql;
			break;

                        case 2:	
			/* Consulta los datos del systema */
			$sql = "SELECT Sys_Cod,Sys_Nom,Sys_Ver,Sys_Des,Sys_Cor,concat('Ofsercont- ',Sys_Nom,' [',Sys_Des,']')as Sys_Tit FROM system";
			//echo $sql;
			return $sql;
			break;
			/**
			* Tomar el codigo Cat_Rec de un codigo determinado. Sirve para regresar de un directorio 
			*/
			case 1024:
			$sql = "SELECT Cat_Rec, Cat_Des FROM categorias WHERE  Cat_Cod=$Par_Sql[0];";
			return $sql;
			break;		
			/**
			* Tomar la descripcion Cat_Des del nivel superior. Sirve para regresar de un directorio
			*/
			case 1025:
			$sql = "SELECT Cat_Des FROM categorias WHERE Cat_Cod=$Par_Sql[0];";
			return $sql;
			break;
			/**
			* Insertar un nuevo tipo de categorias
			*/
			case 1026:
			$sql = "INSERT INTO categorias (Cat_Cdc, Cat_Des,Cat_Tip, Cat_Rec, Emp_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]',
					'$Par_Sql[2]',$Par_Sql[3],$Par_Sql[4])";	
					//echo $sql;		
			return $sql;
			break;		
			/**
			* Modificar un tipo de categorias
			*/
			case 1027:
			$sql = "UPDATE categorias SET Cat_Des='$Par_Sql[1]', Cat_Est='$Par_Sql[2]' WHERE Cat_Cod=$Par_Sql[0]";
			//echo $sql;	
			return $sql;
			break;	
			/**
			* Tomar todos los tipos de categoria de un nivel, cualquiera que este sea 
			*/
			case 1028:
			$sql = "SELECT * FROM categorias WHERE Cat_Rec=$Par_Sql[0] AND Emp_Cod = $Par_Sql[1]";
			return $sql;
			break;
		
		
		
		
		
	
		case 1029:
		$sql = "UPDATE categorias SET Cat_Est='$Par_Sql[0]' WHERE Cat_Cod=$Par_Sql[1];";
		return $sql;
		break;
	/* consulta la categorias solo el detalle */
		case 1030:
		$carg_item= "SELECT Cat_Cod, Cat_Des,Cat_Cdc FROM categorias WHERE Cat_Tip='D' ";
		return $carg_item;
		
			/**
			* Actualiza el detalle y grupo  de las catgorias
			*/	
			case 1031:
			$sql = "UPDATE categorias SET Cat_Des='$Par_Sql[1]', Cat_Tip='$Par_Sql[2]', Cat_Cdc='$Par_Sql[3]' WHERE Cat_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
	/* consulta el maximo numero de Pro_Sec */

		case 1032:
		$sql = "SELECT MAX(Pro_Sec) as Pro_Sec FROM producto,item,categorias WHERE producto.Ite_Cod=item.Ite_Cod AND item.Cat_Cod=categorias.Cat_Cod AND item.Ite_Cod=$Par_Sql[0]";
		return $sql;
		break;

		case 1033:
		$sql = "SELECT MAX(Pro_Sec) as Pro_Sec FROM producto,item,categorias WHERE producto.Ite_Cod=item.Ite_Cod AND item.Cat_Cod=categorias.Cat_Cod AND categorias.Cat_Cod=$Par_Sql[0] ";

		return $sql;
		break;

		case 1034:
		$sql = "SELECT Cat_Cdc FROM categorias WHERE categorias.Cat_Cod=$Par_Sql[0]";
		return $sql;
		break;
		  
			//
			// APLICACION MOVIL
			//

			case 1050:
				$sql = "UPDATE categorias SET Cat_Des='$Par_Sql[Cat_Des]', Cat_Est='$Par_Sql[Cat_Est]' WHERE Cat_Cod=$Par_Sql[Cat_Cod] AND Emp_Cod=$Par_Sql[Emp_Cod]";
				//echo $sql;	
				return $sql;
			break;
			case 1051:
				$sql = "SELECT * FROM categorias WHERE Cat_Tip='D' AND Emp_Cod = '$Par_Sql[Emp_Cod]'";
				return $sql;
			break;
			
			
		}
	}
?>