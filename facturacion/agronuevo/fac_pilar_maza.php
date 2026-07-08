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

	if(empty($Cambio_Moneda) || $Cambio_Moneda <= 0){
		$Cambio_Moneda = 1;
	}

	
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
	$auto = $obBD_con1->getRowConsulta(988, $row_rs_cliente['Aut_Cod'], $obBD_conexion);	
	if($row_rs_cliente['Vet_Xml']!=''){ $titFac="FACTURA&nbsp;&nbsp;DE&nbsp;&nbsp;VENTA";}else{ $titFac="RECIBO&nbsp;&nbsp;DE&nbsp;&nbsp;VENTA";}
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);
$top=30;
$claveacceso=$row_rs_cliente['Vet_Xml'];
?>
<div class="ver">
<style type="text/css">
	.flota{position: absolute; font-size: 10px;font-weight: normal; font: 8pt "Lucida Console", Monaco, monospacee}
	.titulo{position: absolute; font-size: 10px;font-weight: normal; font: 11pt "Lucida Console", Monaco, monospacee}
	.detalle{position: absolute; font-size: 10px;font-weight: normal; font: 10pt Verdana, Geneva, sans-serif;}
	.rigth{text-align: right; width: 70px;}
	.ca{word-wrap: break-word; max-width:270px; width:150px;}
</style>
<span style="top:<?php echo $top-20;?>px;left:0px;" class="titulo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;** COMERCIAL AGROMAS  ** </span>
<span style="top:<?php echo $top+10;?>px;left:90px;" class="flota"><?php echo $titFac;?></span>
<span style="top:<?php echo $top+40;?>px;left:0px;" class="flota">CLIENTE:&nbsp;<?php if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Fac'];}else{ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];}?></span>
<span style="top:<?php echo $top+58;?>px;left:0px;" class="flota">CI:&nbsp;<?php if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:<?php echo $top+58;?>px;left:145px;" class="flota">FECHA:&nbsp;<?php echo $dia.'/'.$mes.'/'.$anio; ?></span>
<?php if($claveacceso!=''){?>
<span style="top:<?php echo $top+75;?>px;left:0px;" class="flota">CLAVE ACCESO/AUTORIZACION:</span>
<span style="top:<?php echo $top+85;?>px;left:0px; text-align: left; width: 270px;" class="flota ca"><?php echo $row_rs_cliente['Vet_Xml'];?></span>
<?php }else{?>

<span style="top:<?php echo $top+55;?>px;left:0px;" class="flota">NUMERO VENTA: <?php echo $row_institucion['Suc_Sri'].'-'.$auto['Pun_Sri'].'-'.str_pad($row_rs_cliente['Vet_Num'], 9, "0", STR_PAD_LEFT);?></span>
<?php }?>

<span style="top:<?php echo $top+130;?>px;left:0px;" class="flota">DETALLE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CANT&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P.U.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</span>
<span style="top:<?php echo $top+144;?>px;left:0px;" class="flota">------------------------------------------</span>
<?php $aux=188;
do{?>	
    <span style="top:<?php echo $aux; ?>px;left:0px;" class="flota"><?php echo $row_rs_cliente['Ite_Cor'].' '.$row_rs_cliente['Pro_Obs'];?></span>
	<span style=" top:<?php echo $aux+17; ?>px;left:90px;" class="flota"><?php echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<?php echo $aux+17; ?>px;left:118px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Pru'] * $Cambio_Moneda, 4);?></span>
    <span style="top:<?php echo $aux+17; ?>px;left:190px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Imp'] * $Cambio_Moneda, 2);?></span>

<?php $aux+=30; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
?>
<span style="top:<?php echo $aux+145;?>px;left:0px;" class="flota">------------------------------------------</span>
<span style="top:683px;left:300px;" class="flota"><?php //echo 'x'; ?></span>
<!--<span style="top:730px;left:55px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',0,36);	?></span>
<span style="top:755px;left:30px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',37,100);	?></span>-->
<?php $posTot=$aux+160;?>
<span style="top:<?php echo $posTot;?>px;left:110px;" class="flota">Subtotal:</span>
<span style="top:<?php echo $posTot;?>px;left:190px;" class="flota rigth"><?Php echo formato_numero($resultados[0] * $Cambio_Moneda, 2, 1); ?></span>

<span style="top:<?php echo $posTot+15;?>px;left:110px;" class="flota">Descuento:</span>
<span style="top:<?php echo $posTot+15;?>px;left:190px;" class="flota rigth"><?Php echo formato_numero($resultados[4] * $Cambio_Moneda, 2, 1); ?></span>

<span style="top:<?php echo $posTot+30;?>px;left:110px;" class="flota">Tarifa 0%:</span>
<span style="top:<?php echo $posTot+30;?>px;left:190px;" class="flota rigth"><?Php echo formato_numero((float)$resultados[1] * $Cambio_Moneda, 2, 1); ?></span>

<span style="top:<?php echo $posTot+45;?>px;left:110px;" class="flota">Tarifa 12%:</span>
<span style="top:<?php echo $posTot+45;?>px;left:190px;" class="flota rigth"><?Php echo formato_numero($resultados[2]+0 * $Cambio_Moneda, 2, 1); ?></span>

<span style="top:<?php echo $posTot+60;?>px;left:110px;" class="flota">IVA:</span>
<span style="top:<?php echo $posTot+60;?>px;left:190px;" class="flota rigth"><?Php echo formato_numero($resultados[3] * $Cambio_Moneda, 2, 1); ?></span>

<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:110px;" class="flota">TOTAL:</strong></span>
<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:190px;" class="flota rigth"><strong><?php echo number_format($resultados[5] * $Cambio_Moneda, 2); ?></strong></span>

</div>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>