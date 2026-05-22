<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2013-JUN-20
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
//echo $sql;
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
                        if(isset($Par_Sql[3])&&!empty($Par_Sql[3])) $Par_Sql[3]=" sucursal.Suc_Cod='$Par_Sql[3]' AND "; else $Par_Sql[3]='';
   $sql = "SELECT 
     usuarios.Usu_Ced,
     usuarios.Usu_Est,
     usuarios.Suc_Cod,
     sucursal.Emp_Cod,
     usuarios.Prs_Cod,
     usuarios.Usu_Cod,
     usuarios.Usu_Tip,
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
     Usu_Ced = '$Par_Sql[0]' AND $Par_Sql[3]
     Usu_Pal = '$Par_Sql[1]' AND empresas.Emp_Cod = $Par_Sql[2] AND 
     usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
                            
                            //echo $sql;
     return $sql;
		
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
		case 22:
			/**
			* Consulta la cantidad de usuarios por empresa que existen 
			*/
			$sql = "SELECT 
     usuarios.Usu_Ced,
     usuarios.Usu_Est,
     usuarios.Suc_Cod,
     sucursal.Emp_Cod,
     usuarios.Prs_Cod,
     usuarios.Usu_Cod,
     usuarios.Usu_Tip,
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
     Usu_Ced = '$Par_Sql[0]' AND sucursal.Suc_Cod=$Par_Sql[2]
     AND empresas.Emp_Cod = $Par_Sql[1] AND 
     usuarios.Usu_Est = 'A' AND sucursal.Suc_Est = 'A'";
	 //echo $sql;
			return $sql;

		case 100: // Contar equipos asignados en el catálogo de acceso
			$sql = "SELECT COUNT(*) as total FROM usuario_inventario WHERE UsInv_Usu = $Par_Sql[0] AND UsInv_Est = 'A'";
			return $sql;

		case 101: // Buscar si el dispositivo actual está registrado y ACTIVO
			$sql = "SELECT * FROM dispositivos_usuario WHERE Dev_Cod = '$Par_Sql[0]' AND Usu_Cod = $Par_Sql[1] AND DisUsr_Est = 'A'";
			return $sql;

		case 102: // Contar asignaciones en usuario_inventario
			$sql = "SELECT COUNT(*) as total FROM usuario_inventario WHERE UsInv_Usu = $Par_Sql[0] AND UsInv_Est = 'A'";
			return $sql;

		case 103: // (No usado en login ahora) Registrar nuevo dispositivo
			$sql = "INSERT INTO dispositivos_usuario (Dev_Cod, Usu_Cod, DisUsr_FecR, DisUsr_FecUA, DisUsr_Est) 
					VALUES ('$Par_Sql[0]', $Par_Sql[1], NOW(), NOW(), 'A')";
			return $sql;

		case 104: // Actualizar fecha de último acceso
			$sql = "UPDATE dispositivos_usuario SET DisUsr_FecUA = NOW() 
					WHERE Dev_Cod = '$Par_Sql[0]' AND Usu_Cod = $Par_Sql[1]";
			return $sql;

		case 105: // Buscar cupo libre que COINCIDA con el tipo de dispositivo (PC o MOVIL)
			$sql = "SELECT ui.InvDis_Cod, inv.InvDis_Nom
					FROM usuario_inventario ui
					INNER JOIN inventario_dispositivos inv ON ui.InvDis_Cod = inv.InvDis_Cod
					LEFT JOIN dispositivos_usuario du ON (ui.InvDis_Cod = du.InvDis_Cod AND du.Usu_Cod = ui.UsInv_Usu AND du.DisUsr_Est = 'A')
					WHERE ui.UsInv_Usu = $Par_Sql[0] 
					  AND ui.UsInv_Est = 'A'
					  AND inv.InvDis_Tipo = '$Par_Sql[1]'
					  AND du.Dev_Cod IS NULL
					LIMIT 1";
			return $sql;

		case 106: // Registrar vinculación completa con IP, User Agent y Nombre
			$sql = "INSERT INTO dispositivos_usuario (Usu_Cod, Dev_Cod, InvDis_Cod, DisUsr_Nom, DisUsr_IP, user_agent, DisUsr_FecR, DisUsr_FecUA, DisUsr_Est)
					VALUES ($Par_Sql[0], '$Par_Sql[1]', $Par_Sql[2], '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', NOW(), NOW(), 'A')";
			return $sql;

		case 107: // Buscar cupo OCUPADO que comparta la misma IP y Mismo Tipo
			$sql = "SELECT du.DisUsr_Cod, du.InvDis_Cod, inv.InvDis_Nom
					FROM dispositivos_usuario du
					INNER JOIN inventario_dispositivos inv ON du.InvDis_Cod = inv.InvDis_Cod
					WHERE du.Usu_Cod = $Par_Sql[0]
					  AND du.DisUsr_IP = '$Par_Sql[1]'
					  AND inv.InvDis_Tipo = '$Par_Sql[2]'
					  AND du.DisUsr_Est = 'A'
					LIMIT 1";
			return $sql;

		case 108: // Reemplazar la huella (Dev_Cod) de un cupo existente (Compartir por IP)
			$sql = "UPDATE dispositivos_usuario 
					SET Dev_Cod = '$Par_Sql[0]',
						user_agent = '$Par_Sql[1]',
						DisUsr_FecUA = NOW()
					WHERE DisUsr_Cod = $Par_Sql[2]";
			return $sql;
		}
	}
?>