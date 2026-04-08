<?php
/**
* @abstract Reporte de retención para la impresión 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-10-01
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 
	 
if (isset($Ret_Cod))
{   
   $row_rs_renta=$obBD_con1->getRowConsulta(167,$Ret_Cod, $obBD_conexion);
   	  
   if ($row_rs_renta['Aut_Cod'] != "") 
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(166,$Ret_Cod, $obBD_conexion);	   
   }
   else
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(553,$Ret_Cod, $obBD_conexion);  
   } 
   $row_prin_renta = current($rs_prin_renta);
   
   if(is_readable("./plantilla_fact_{$Ses_Emp_Cod}.json")) $docp= json_decode(file_get_contents("./plantilla_retenc_{$Ses_Emp_Cod}.json"), true);
   if(!isset($docp)) include('../IMPRIMIR/plantilla_retenc.php');
   
}else die();	

function getCss($template_fact,$string,$css=null){     
    if(!isset($template_fact[$string])) return null;
    $item=$template_fact[$string];
    if(empty($css))
        return "top:$item[y]px;left:$item[x]px;width:$item[width]px;";
    else
        return $item[$css];
}