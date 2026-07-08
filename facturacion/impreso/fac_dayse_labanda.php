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
.flota{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Arial, Helvetica, sans-serif;}
.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Verdana, Geneva, sans-serif;}
.rigth{text-align: right; width: 70px;}
.observ{text-align: left; width: 400px;}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);?>

<span style="top:175px;left:60px;" class="flota"><?php if ($row_rs_representante['Cli_Fac'] != ""){echo substr($row_rs_representante['Cli_Fac'],0,40);}else{ echo substr($row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'],0,40);}?></span>
<span style="top:205px;left:60px;" class="flota"><?php if ($row_rs_representante['Cli_Dir'] != ""){echo substr($row_rs_representante['Cli_Dir'],0,31);}else{echo substr($row_rs_cliente['Prs_Dir'],0,31);}?></span>
<span style="top:235px;left:120px;" class="flota"><?php if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:265px;left:60px;" class="flota"><?php echo $row_institucion['Ciu_Des'];?></span>
<span style="top:215px;left:475px;" class="flota"><?php echo $dia.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$mes.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$anio; ?></span>

<?php $aux=320;
do{ if($row_rs_cliente['Vet_Obs']!=""){$obs='<strong>OBSERV: </strong>'.$row_rs_cliente['Vet_Obs'];}?>
	<span style="top:<?php echo $aux; ?>px;left:5px;" class="flota"><?php echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<?php echo $aux; ?>px;left:55px;" class="flota"><?php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'];?></span>
    <span style="top:<?php echo $aux; ?>px;left:455px; text-align: right;width: 50px;" class="flota"><?php echo number_format($row_rs_cliente['Vet_Pru'], 2);?></span>
    <span style="top:<?php echo $aux; ?>px;left:560px; text-align: right;width: 50px;" class="flota"><?php echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>	
<?php $aux+=25; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
  
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));				
?>
<span style="top:<?php echo $aux+30; ?>px;left:55px;" class="flota observ"><?php echo $obs;?></span>
<span style="top:798px;left:50px; text-align: left; width: 350px;line-height: 2.0;" class="flota"><?php echo strtoupper(num2letras(formato_numero($resultados[5], 2,1)));?></span>
<span style="top:770px;left:300px;" class="flota"><?php //echo 'x'; ?></span>
<?php $posTot='775';?>
<span style="top:<?php echo $posTot;?>px;left:540px;" class="flota rigth"><!--Subtotal&nbsp;--><?Php echo formato_numero($resultados[0], 2, 1); ?></span>
<span style="top:<?php echo $posTot+33;?>px;left:540px;" class="flota rigth"><!--Descuento&nbsp;--><?Php echo formato_numero($resultados[4], 2, 1); ?></span>
<span style="top:<?php echo $posTot+64;?>px;left:540px;" class="flota rigth"><!--Tarifa 0%--><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>
<span style="top:<?php echo $posTot+92;?>px;left:540px;" class="flota rigth"><!--Tarifa 12%&nbsp;--><?Php //echo formato_numero($resultados[2]+0, 2, 1); ?></span>
<span style="top:<?php echo $posTot+92;?>px;left:540px;" class="flota rigth"><!--IVA&nbsp;--><?Php echo formato_numero($resultados[3], 2, 1); ?></span>
<span style="top:<?php echo $posTot+118;?>px;left:540px;" class="flota rigth"><!--TOTAL&nbsp;--><strong><?php echo number_format($resultados[5], 2); ?></strong></span>
 

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>