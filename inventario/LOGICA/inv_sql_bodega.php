<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Santiago Ruiz
 * @version 1.0
 * Fecha: 28/12/2019
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */


function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		case 101:
		/* 
		* Busqueda all bodegas */
		$sql = "select bod_cod, bod_dir, bod_nom, if(bod_tip='P', 'Principal', 'Secundaria') as bod_tip, if(bod_cvt='S', 'Con control de ventas', 'Sin control de ventas') as bod_cvt from bodega, sucursal where bodega.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $_SESSION[Ses_Emp_Cod]";
		return $sql;
		break;			


		case 1003:
		/* 
		* Insertar bodega
		*/
 		$sql = "INSERT INTO bodega(suc_cod,bod_dir,bod_nom,bod_tip,bod_est,bod_cvt) VALUES "
                        . "('$Par_Sql[suc_cod]', '$Par_Sql[bod_dir]', '$Par_Sql[bod_nom]', '$Par_Sql[bod_tip]', 'A', '$Par_Sql[bod_cvt]')";
		return $sql;
		break;
	
		case 1004:
		/* 
		* consulta si hay bodegas con un count
		*/
 		$sql = "select count(*) as total from bodega, sucursal where bodega.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $_SESSION[Ses_Emp_Cod] and bod_est='A' and bod_tip='P'";
		return $sql;
		break;
        

		case 2: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones servicios
		*/
 		$modificar = "UPDATE bodega"
                        . " SET  bod_dir='$Par_Sql[bod_dir]', bod_nom='$Par_Sql[bod_nom]', bod_tip='$Par_Sql[bod_tip]', bod_cvt='$Par_Sql[bod_cvt]'"
                        . " WHERE bod_cod = $Par_Sql[bod_cod]";		
                    //echo $modificar_aut;
		return $modificar;
		break;	
                
        case 200:
        $sql = "select usuarios.usu_cod, concat(persona.prs_ape, ' ',persona.prs_nom ) as persona from usuarios, persona, sucursal where usuarios.prs_cod=persona.prs_cod and usuarios.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $Par_Sql[Emp_Cod] and sucursal.Suc_Cod = $Par_Sql[Suc_Cod] and usu_est='A' order by persona";    
        return $sql;        
     	break;

	 	case 31://  Insert detalle usuario perfil
	    $sql = "INSERT INTO  bodega_usuario(usu_cod, bod_cod) "
	            .  "VALUES($Par_Sql[usu_cod], $Par_Sql[bod_cod])";
	
	    return $sql;
	    break;
		
		case 32:
		$sql = "select u.usu_cod, concat(p.prs_ape,' ',prs_nom) as persona from  persona as p, bodega_usuario as bu, usuarios as u
				where p.prs_cod=u.prs_cod and u.usu_cod=bu.usu_cod and bu.bod_cod='$Par_Sql[bod_cod]'";
		//ChromePhp::log($sql);
		return $sql;
	    break;	

	    case 33:
		$sql = "delete from bodega_usuario where bod_cod=$Par_Sql[bod_cod]";
		//ChromePhp::log($sql);
		return $sql;
	    break;		
	}
}?>