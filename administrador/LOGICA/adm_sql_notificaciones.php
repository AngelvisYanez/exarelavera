<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Santiago Ruiz
 * @version 1.0
 * Fecha: 21/11/2019
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
//

function sentencias_con($id,$Par_Sql)
{
	switch($id)
	{
		case 101:
		/* 
		* Busqueda all notificaciones  */
		$sql = "select not_cod, not_fei, not_fec, not_enc, not_msj, notificacion.Emp_Cod, if(not_est='A', 'Activo', 'Inactivo') as not_est, exa_master.empresas.emp_nom from notificacion,  exa_master.empresas where notificacion.emp_cod = exa_master.empresas.emp_cod";
		return $sql;
		break;			

		/*
		*Busqueda por filtros
		*/
		case 28:
            if($Par_Sql['op_opciones']=="d") {$search="(exa_master.empresas.emp_nom LIKE '%$Par_Sql[search]%')";}
                        if($Par_Sql['est_opciones']=="a"){$estado="notificacion.not_est = 'A'";}
                        else {$estado="notificacion.not_est = 'I'";}
                        $campos=empty($Par_Sql['limits'])?" COUNT(notificacion.not_cod) AS total":"not_cod, not_fei, not_fec, not_enc, not_msj, notificacion.Emp_Cod, if(not_est='A', 'Activo', 'Inactivo') as not_est, exa_master.empresas.emp_nom";
			$sql = "SELECT $campos
					FROM notificacion,  exa_master.empresas
					WHERE (notificacion.emp_cod = exa_master.empresas.emp_cod) AND $search AND $estado $Par_Sql[limits];";
			return $sql;
		break;



		case 1003:
		/* 
		* Insertar exa notificacion
		*/
 		$sql = "INSERT INTO exa.notificacion(not_fei, not_fec, not_enc, not_msj, not_est, Emp_Cod) VALUES "
                        . "('$Par_Sql[not_fei]', '$Par_Sql[not_fec]', '$Par_Sql[not_enc]', '$Par_Sql[not_msj]', 'A','$Par_Sql[Emp_Cod_n]' )";
		return $sql;
		break;
	
		case 1004:
		/* 
		* Insertar servicios notificacion
		*/
 		$sql = "INSERT INTO servicios.notificacion(not_fei, not_fec, not_enc, not_msj, not_est, Emp_Cod) VALUES "
                        . "('$Par_Sql[not_fei]', '$Par_Sql[not_fec]', '$Par_Sql[not_enc]', '$Par_Sql[not_msj]', 'A','$Par_Sql[Emp_Cod_n]' )";
		return $sql;
		break;
        
        case 1005:
		/* 
		* Insertar agro_nuevo notificacion
		*/
 		$sql = "INSERT INTO agronuevo.notificacion(not_fei, not_fec, not_enc, not_msj, not_est, Emp_Cod) VALUES "
                        . "('$Par_Sql[not_fei]', '$Par_Sql[not_fec]', '$Par_Sql[not_enc]', '$Par_Sql[not_msj]', 'A','$Par_Sql[Emp_Cod_n]' )";
		return $sql;
		break;

		case 1006:
		/* 
		* Insertar orquideas notificacion
		*/
 		$sql = "INSERT INTO orquideas.notificacion(not_fei, not_fec, not_enc, not_msj, not_est, Emp_Cod) VALUES "
                        . "('$Par_Sql[not_fei]', '$Par_Sql[not_fec]', '$Par_Sql[not_enc]', '$Par_Sql[not_msj]', 'A','$Par_Sql[Emp_Cod_n]' )";
		return $sql;
		break;

		case 1007:
		/* 
		* Insertar coopsb notificacion
		*/
 		$sql = "INSERT INTO coopsb.notificacion(not_fei, not_fec, not_enc, not_msj, not_est, Emp_Cod) VALUES "
                        . "('$Par_Sql[not_fei]', '$Par_Sql[not_fec]', '$Par_Sql[not_enc]', '$Par_Sql[not_msj]', 'A','$Par_Sql[Emp_Cod_n]' )";
		return $sql;
		break;

		case 1009: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones exa
		*/
 		$modificar_aut = "UPDATE exa.notificacion"
                        . " SET not_fei = '$Par_Sql[not_fei]', not_fec ='$Par_Sql[not_fec]', not_enc='$Par_Sql[not_enc]', not_msj='$Par_Sql[not_msj]',"
                        . " Emp_Cod ='$Par_Sql[Emp_Cod_n]'"
                        . " WHERE not_cod = $Par_Sql[not_cod]";		
                    //echo $modificar_aut;
		return $modificar_aut;
		break;	

		case 1010: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones servicios
		*/
 		$modificar_aut = "UPDATE servicios.notificacion"
                        . " SET not_fei = '$Par_Sql[not_fei]', not_fec ='$Par_Sql[not_fec]', not_enc='$Par_Sql[not_enc]', not_msj='$Par_Sql[not_msj]',"
                        . " Emp_Cod ='$Par_Sql[Emp_Cod_n]'"
                        . " WHERE not_cod = $Par_Sql[not_cod]";		
                    //echo $modificar_aut;
		return $modificar_aut;
		break;	
		
		case 1011: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones agro_nuevo
		*/
 		$modificar_aut = "UPDATE agronuevo.notificacion"
                        . " SET not_fei = '$Par_Sql[not_fei]', not_fec ='$Par_Sql[not_fec]', not_enc='$Par_Sql[not_enc]', not_msj='$Par_Sql[not_msj]',"
                        . " Emp_Cod ='$Par_Sql[Emp_Cod_n]'"
                        . " WHERE not_cod = $Par_Sql[not_cod]";		
                    //echo $modificar_aut;
		return $modificar_aut;
		break;	

		case 1012: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones orquideas
		*/
 		$modificar_aut = "UPDATE orquideas.notificacion"
                        . " SET not_fei = '$Par_Sql[not_fei]', not_fec ='$Par_Sql[not_fec]', not_enc='$Par_Sql[not_enc]', not_msj='$Par_Sql[not_msj]',"
                        . " Emp_Cod ='$Par_Sql[Emp_Cod_n]'"
                        . " WHERE not_cod = $Par_Sql[not_cod]";		
                    //echo $modificar_aut;
		return $modificar_aut;
		break;	

		case 1013: 
                    
		/* 
		* Modifica o actualiza los cambios realizados en la tabla de notificaciones coopsb
		*/
 		$modificar_aut = "UPDATE coopsb.notificacion"
                        . " SET not_fei = '$Par_Sql[not_fei]', not_fec ='$Par_Sql[not_fec]', not_enc='$Par_Sql[not_enc]', not_msj='$Par_Sql[not_msj]',"
                        . " Emp_Cod ='$Par_Sql[Emp_Cod_n]'"
                        . " WHERE not_cod = $Par_Sql[not_cod]";		
                    //echo $modificar_aut;
		return $modificar_aut;
		break;	
              
        /*
		* Actualiza el estado de Autorizaciones exa
		*/
		case 5013:
		$Sql_5013 = "UPDATE exa.notificacion SET not_est = '$Par_Sql[not_est]' WHERE notificacion.not_cod = $Par_Sql[not_cod]";
		return $Sql_5013;
		break;

		/*
		* Actualiza el estado de Autorizaciones servicios
		*/
		case 5014:
		$Sql_5013 = "UPDATE servicios.notificacion SET not_est = '$Par_Sql[not_est]' WHERE notificacion.not_cod = $Par_Sql[not_cod]";
		return $Sql_5013;
		break;

		/*
		* Actualiza el estado de Autorizaciones agro_nuevo
		*/
		case 5016:
		$Sql_5013 = "UPDATE agronuevo.notificacion SET not_est = '$Par_Sql[not_est]' WHERE notificacion.not_cod = $Par_Sql[not_cod]";
		return $Sql_5013;
		break;

		/*
		* Actualiza el estado de Autorizaciones orquideas
		*/
		case 5017:
		$Sql_5013 = "UPDATE orquideas.notificacion SET not_est = '$Par_Sql[not_est]' WHERE notificacion.not_cod = $Par_Sql[not_cod]";
		return $Sql_5013;
		break;

		/*
		* Actualiza el estado de Autorizaciones coopsb
		*/
		case 5018:
		$Sql_5013 = "UPDATE coopsb.notificacion SET not_est = '$Par_Sql[not_est]' WHERE notificacion.not_cod = $Par_Sql[not_cod]";
		return $Sql_5013;
		break;
            
         /*
		* Get Empresas activas registradas
		*/
		case 515:
		$Sql_515 = "SELECT Emp_Cod, Emp_Nom FROM exa_master.empresas WHERE Emp_Est='A' ORDER BY Emp_Nom";
		return $Sql_515;
		break;
                
            
                
     
		
		
	}
}?>