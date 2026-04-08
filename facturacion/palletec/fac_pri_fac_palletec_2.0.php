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
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 12px}
-->
.flota{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Lucida, Console;}
.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = split('[/.-]', $row_rs_cliente['Caj_Fec']);?>

<span style="top:118px;left:70px;" class="flota"><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Fac'];}else{ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];}?></span>
<span style="top:140px;left:100px;" class="flota"><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:170px;left:70px;" class="flota"><? if ($row_rs_representante['Cli_Dir'] != ""){echo substr($row_rs_representante['Cli_Dir'],0,31);}else{echo substr($row_rs_cliente['Prs_Dir'],0,31);}?></span>
<span style="top:190px;left:70px;" class="flota"><? echo $row_institucion['Ciu_Des'];?></span>
<span style="top:140px;left:350px;" class="flota"><? echo $dia.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$mes.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$anio; ?></span>

<? $aux=250;
do{?>
	<span style="top:<? echo $aux; ?>px;left:30px;" class="flota"><? echo formato_numero($row_rs_cliente['Vet_Can'],2,1);?></span>
    <span style="top:<? echo $aux; ?>px;left:90px;" class="flota"><? echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'];?></span>
    <span style="top:<? echo $aux; ?>px;left:385px;" class="flota"><? echo number_format($row_rs_cliente['Vet_Pru'], 2);?></span>
    <span style="top:<? echo $aux; ?>px;left:440px;" class="flota"><? echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

<? $aux+=25; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
$tops=570;
?>
<span style="top:700px;left:85px;" class="flota"><? echo 'x'; ?></span>
<span style="top:600px;left:35px;" class="flota"><? echo substr(num2letras($resultados[5]),0,40);	?></span>
<span style="top:610px;left:30px;" class="flota"><? echo substr(num2letras($v_absoluto[0]),37,100);	?></span>
<span style="top:<? echo $tops;?>px;left:430px;" class="flota"><!--Tarifa 0%--><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>
<span style="top:<? echo $tops+25;?>px;left:430px;" class="flota"><!--Tarifa 12%&nbsp;--><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></span>
<span style="top:<? echo $tops+55;?>px;left:430px;" class="flota"><!--Subtotal&nbsp;--><?Php echo formato_numero($resultados[0], 2, 1); ?></span>
<span style="top:<? echo $tops+80;?>px;left:430px;" class="flota"><!--Descuento&nbsp;--><?Php echo formato_numero($resultados[4], 2, 1); ?></span>
<span style="top:<? echo $tops+105;?>px;left:430px;" class="flota"><!--IVA&nbsp;--><?Php echo formato_numero($resultados[3], 2, 1); ?></span>
<span style="top:<? echo $tops+135;?>px;left:430px;" class="flota"><!--TOTAL&nbsp;--><strong><?php echo number_format($resultados[5], 2); ?></strong></span>


</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>