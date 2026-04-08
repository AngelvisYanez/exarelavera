<?php 
/**
* @abstract Reporte de ventas para la impresión en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-23
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

if (isset($Vet_Cod))
{
    /*
    * Consulta datos de los clientes
    */
    $rs_cliente = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
    $row_rs_cliente = $obBD_con1->registros();
    $total_rs_cliente = $obBD_con1->numregistros();	
    $cliente = $row_rs_cliente['Vet_Cod'];	
    $observacion = $row_rs_cliente['Vet_Obs'];	
    $estudiante = $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];		
    /*
    * Llamado del representate delcliente
    */
    $rs_representante = $obBD_con1->consulta(sentencias_tes(33, $obBD_con1->parametros($row_rs_cliente['Cli_Cod'])),
                                                                    $obBD_conexion->conexion);
    $row_rs_representante = $obBD_con1->registros();
    /* 
    * Consulta la carrera del cliente 
    */
    /*$rs_carrera = $obBD_con1->consulta(sentencias_tes(224, $obBD_con1->parametros($row_rs_cliente['Nge_Cod'])),
                                                                    $obBD_conexion->conexion);
    $row_rs_carrera = $obBD_con1->registros();
    $total_rs_carrera = $obBD_con1->numregistros();	*/		
    /*
    * Consulta de los tipos de pago 
    */
    $rs_pagos = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
    $row_rs_pagos = $obBD_con1->registros();
    $total_rs_pagos = $obBD_con1->numregistros();	
    /**
    * Consulta de la cabecera del reporte 
    */
    $row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);	
    
    $resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
    
    if(is_readable("./plantilla_fact_{$Ses_Emp_Cod}.json")) $docp= json_decode(file_get_contents("./plantilla_fact_{$Ses_Emp_Cod}.json"), true);
    if(!isset($docp)) include('../IMPRIMIR/plantilla_fact.php');
    
}else die();
function getCss($template_fact,$string,$css=null){     
    if(!isset($template_fact[$string])) return null;
    $item=$template_fact[$string];
    if(empty($css))
        return "top:$item[y]px;left:$item[x]px;width:$item[width]px;";
    else
        return $item[$css];
}