<?php 
/**  Retorna la busqueda para los cheques 
 * @param $row_pri_cheque 
 * @param $fecha 
 * @param $row_institucion 
 */
require_once($APP_REAL_PATH.'/auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH.'/tesoreria/LOGICA/tes_log_cheque_2.0.php');
require_once($APP_REAL_PATH.'/Librerias/procedimientos/almacenados_standar.php'); 

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Che;

/* Consulta el total de las facturas por fecha dada */
$row_pri_cheque = $obBD_con1->getRowConsulta(1, $codigo2.'*'.$asi.'*'.$ban.'*'.$pro, $obBD_conexion);
$fecha = $row_pri_cheque['Che_Fec'];	
if($row_pri_cheque['Che_Ben']==''&&$row_pri_cheque['Prs_Cod']=='2680') $row_pri_cheque['Che_Ben']=' ';
/* Consulta de la cabecera del reporte */
$row_institucion = $obBD_con1->getRowConsulta(2, $Ses_Suc_Cod, $obBD_conexion);
?>