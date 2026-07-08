<?php 
/**
* @abstract Reporte de ventas para la impresión en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-23
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_guia_remi.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

if (isset($Gui_Cod))
{
	/*
	* Consulta datos del destinatario
	*/
	$rs_destina = $obBD_con1->getRowConsulta(1269,$Gui_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
	$rs_detalle = $obBD_con1->getArrayConsulta(1273,$Gui_Cod,$obBD_conexion);
	$rs_trans = $obBD_con1->getRowConsulta(1275,$Gui_Cod,$obBD_conexion);
	
	//$total_desti=count($rs_destina);
	//$total_desti=$row_rs_usuario['Suc_Cod'] > 0? 1 : 0;
	
	
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 12px}
-->
.flota{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Arial, Helvetica, sans-serif;}
.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}
</style>
<link rel="stylesheet" href="print.css" type="text/css" media="print" />
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $rs_destina['Gui_Fec']);?>

<span style="top:100px;left:200px;" class="flota"><?php echo $rs_destina['Gui_Fsa']; ?></span>
<span style="top:125px;left:200px;" class="flota"><?php echo $rs_destina['Gui_Far']; ?></span>
<span style="top:125px;left:335px;" class="flota"><?php echo $anio.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$mes.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$dia; ?></span>

<span style="top:150px;left:170px;" class="flota"><?php echo $rs_destina['Gui_Nve']; ?></span>
<span style="top:200px;left:140px;" class="flota"><?php echo $rs_destina['Gui_Mot']; ?></span>
<span style="top:214px;left:10px;" class="flota"><?php echo 'x'; ?></span>
<!--Datos del destinatario-->
<span style="top:295px;left:140px;" class="flota"><?php echo $rs_destina['Gui_Fve']; ?></span>
<span style="top:315px;left:120px;" class="flota"><?php echo $rs_destina['Gui_Dsa']; ?></span>
<span style="top:315px;left:350px;" class="flota"><?php echo substr($rs_destina['Gui_Dar'],0,18); ?></span>
<span style="top:340px;left:245px;" class="flota"><?php echo substr($rs_destina['Prs_Ape'].' '.$rs_destina['Prs_Nom'],0,29); ?></span>
<span style="top:362px;left:10px;" class="flota"><?php echo substr($rs_destina['Prs_Ape'].' '.$rs_destina['Prs_Nom'],29,60); ?></span>
<span style="top:362px;left:350px;" class="flota"><?php echo $rs_destina['Prs_Ced']; ?></span>
<!--Datos del transporte-->
<span style="top:405px;left:145px;" class="flota"><?php echo substr($rs_trans['Prs_Ape'].' '.$rs_trans['Prs_Nom'],0,40); ?></span>
<span style="top:430px;left:60px;" class="flota"><?php echo $rs_trans['Prs_Ced']; ?></span>
<span style="top:430px;left:360px;" class="flota"><?php echo $rs_trans['Gui_Pla']; ?></span>
<!--Detalle de la guia de remision-->
<?php 
$aux='510';
foreach($rs_detalle as $datos){?>
	<span style="top:<?php echo $aux;?>px;left:10px;" class="detalle"><?php echo $datos['Gui_Can']; ?></span>
	<span style="top:<?php echo $aux;?>px;left:80px;" class="detalle"><?php echo $datos['Uni_Des']; ?></span>
	<span style="top:<?php echo $aux;?>px;left:160px;" class="detalle"><?php echo $datos['Ite_Lar']; ?></span>
<?php $aux+=15;}?>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>