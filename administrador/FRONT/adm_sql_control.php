<?php
require_once('../../Librerias/config.php/register_globals.php');
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2013-JUN-20
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
	function sentencias_cnt($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			* Consulta la foto del personal
			*/
			case 1:
			$sql="SELECT Per_Cod, Per_Fot FROM personal WHERE Prs_Cod=$Par_Sql[0] AND Emp_Cod = $Par_Sql[1]";   
			return $sql;
			break;

			/**
			* Consulta la base de datos
			*/
			case 2:
			$sql="SELECT `data`.Dat_Dis, `data`.Dat_Aut, `data`.Dat_Stg FROM
			  access INNER JOIN `data` ON (access.Dat_Cod = `data`.Dat_Cod) WHERE data.`Emp_Cod`=$Par_Sql[0] AND `access`.`Acc_Usr`='$Par_Sql[1]'";  
			return $sql;
			break;


		
			case 14:
			/**
			* Consulta realiza la autenticacion de los usuarios 
			*/
			$sql = "SELECT 
			  usuarios.Usu_Ced,
			  usuarios.Usu_Est,
			  usuarios.Suc_Cod,
			  sucursal.Emp_Cod,
			  usuarios.Prs_Cod,
			  usuarios.Usu_Cod,
			  persona.Prs_Nom,
			  persona.Prs_Ape,
			  persona.Prs_Ced,
			  persona.Prs_Sex,
			  usuarios.Usu_Cad,
			  empresas.Emp_Nom,
			  empresas.Emp_Log,
			  sucursal.Suc_Des,
			  empresas.Emp_Cor,
			  sucursal.Suc_Web,
			  empresas.Emp_Log, 
			  usuarios.Usu_Men
			FROM
			  usuarios
			  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
			  INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
			  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
			WHERE
			  Usu_Ced = '$Par_Sql[0]' AND 
			  Usu_Pal = '".md5($Par_Sql[1])."' AND empresas.Emp_Cod = $Par_Sql[2] AND 
			  usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
			  return $sql;
			  break;

			case 15:
			/**
			* Consulta informacion del sistema 
			*/
			$sql = "SELECT Sys_Nom, Sys_Ver, Sys_Des, Sys_Cor FROM system";
			return $sql;
			break;

			case 16:
			/**
			* Consulta realiza la autenticacion de los usuarios sin encriptar en php
			*/
			$sql = "SELECT 
			  usuarios.Usu_Ced,
			  usuarios.Usu_Est,
			  usuarios.Suc_Cod,
			  sucursal.Emp_Cod,
			  usuarios.Prs_Cod,
			  usuarios.Usu_Cod,
			  persona.Prs_Nom,
			  persona.Prs_Ape,
			  persona.Prs_Ced,
			  persona.Prs_Sex,
			  usuarios.Usu_Cad,
			  empresas.Emp_Nom,
			  empresas.Emp_Log,
			  sucursal.Suc_Des,
			  empresas.Emp_Cor,
			  sucursal.Suc_Web,
			  empresas.Emp_Log, 
			  usuarios.Usu_Men
			FROM
			  usuarios
			  INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
			  INNER JOIN persona ON (usuarios.Prs_Cod = persona.Prs_Cod)
			  INNER JOIN empresas ON (sucursal.Emp_Cod = empresas.Emp_Cod)
			WHERE
			  Usu_Ced = '$Par_Sql[0]' AND 
			  Usu_Pal = '$Par_Sql[1]' AND empresas.Emp_Cod = $Par_Sql[2] AND 
			  usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
			  return $sql;
			  break;
		
			case 21:
			/**
			* Consulta la cantidad de usuarios por empresa que existen 
			*/
			$sql = "SELECT 
	  usuarperfi.Per_Cod, perfiles.Per_Des
	FROM
	  perfiles
	  INNER JOIN usuarperfi ON (perfiles.Per_Cod = usuarperfi.Per_Cod)
	WHERE
	  usuarperfi.Usu_Cod = $Par_Sql[0] AND perfiles.Per_Est = 'A'";
			return $sql;
			break;
		
		}
	}
?>