<?Php
/* 
Alias:	-
Descripción: Actualiza la fecha de conexion del usuario 
Fecha de actualización:	2011-05-23
Desarrollador:	Lewis Chimarro 
*/

require_once('../LOGICA/logica.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

/**Inicio de la transaccion**/
$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	
	$time_online= time(); 
	/* Actualiza la fecha de conexion del usuario */
	$obBD_con1->grabarv_registros(sentencias_adm(38, $obBD_con1->parametros($time_online.'*'.$Ses_Usu_Cod)),$obBD_conexion->conexion);	
	
/*Fin del la transaccion*/
$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

/* cierro las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/* fin cierre las conexiones */
?>