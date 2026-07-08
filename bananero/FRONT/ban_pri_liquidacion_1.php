<?php	
/**
* @abstract Reporte del libro diario 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-01
* Fecha de actualización  2015-05-01
* @author Lewis Chimarro
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ban_log_liquidacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');			
	
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Liquidacion;
/**
* Consulta de la cabecera del reporte 
*/

$hoy = date("Y-m-d");


?>
<HTML>
<HEAD>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?> 
</HEAD>
<BODY>
    
</BODY>
</HTML>
<?Php 
/* Cierra las conexiones */
$obBD_conexion->cerrar();