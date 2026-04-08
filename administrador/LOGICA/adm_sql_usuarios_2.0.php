<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-18
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
	function sentencias_admu($id,$Par_Sql)
	{
		switch($id)
		{       
                    
                    case 1:                        
			/**
			* Consulta la base de datos de la empresa
			*/
			$sql = "SELECT `data`.Dat_Cod FROM `data` WHERE data.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
                    case 2:                        
                        /**
                        * Inserta el usuario en la tabla master
                        */
                        $sql = "INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]')";
                        return $sql;
                        break;			

		    case 3:
                        $sql="UPDATE usuarios SET Usu_Pal= md5('$Par_Sql[Usu_Pal]') WHERE Usu_Cod =$Par_Sql[Usu_Cod]";
                        return $sql;
                        break;
                    
                    case 4:
                        $sql= "SELECT COUNT(Usu_Cod) as contador FROM usuarios WHERE Usu_Pal = md5('$Par_Sql[Usu_Pal]') AND Usu_Cod = $Par_Sql[Usu_Cod]";
                        return $sql;
                        break;		
			
		}
	}
?>