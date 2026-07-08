<?php 
/**
* @abstract Reporte de ventas para la impresiï¿½n en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaciï¿½n  2012-05-23
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
	//print_r($row_rs_cliente);
	$total_rs_cliente = $obBD_con1->numregistros();	
	$cliente = $row_rs_cliente['Vet_Cod'];	
	$observacion = $row_rs_cliente['Vet_Obs'];	
	$estudiante = $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];		
	/*
	* Llamado del representate delcliente
	*/
	$rs_representante = $obBD_con1->consulta(sentencias_tes(33, $obBD_con1->parametros($row_rs_cliente['Cli_Cod'])),$obBD_conexion->conexion);
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
.flota{position: absolute;font-size: 8px;font-weight: normal;font: 7pt Verdana, Geneva, sans-serif;}
.titulos{position: absolute;font-size: 8px;font-weight: normal;font: 10pt Verdana, Geneva, sans-serif;}
.detalle{position: absolute;font-size: 8px;font-weight: normal;font: 7pt Verdana, Geneva, sans-serif;}
.rigth{    text-align: right; width: 70px;}
</style>
<link rel="stylesheet" href="print.css" type="text/css" media="print"/>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente['Caj_Fec']);

if($row_rs_cliente['Tic_Sri']=='1'){
	$cabTop=60;
}else{
	$cabTop=90;
}
if($row_rs_cliente['Tic_Sri']=='0'){
?>
<span style="top:<?php echo $cabTop-30;?>px;left:10px;" class="titulos"><strong> <?php echo "RECIBO DE APORTACION &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No. ".$anio."-".str_pad($row_rs_cliente['Vet_Num'], 9, "0", STR_PAD_LEFT)?></strong></span>
<?php }?>
<span style="top:<?php echo $cabTop;?>px;left:10px;" class="flota"><?php if ($row_rs_representante['Cli_Fac'] != ""){echo "CLIENTE: ".$row_rs_representante['Cli_Fac'];}else{ echo "CLIENTE: ".$row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];}?></span>
<span style="top:<?php echo $cabTop+15;?>px;left:10px;" class="flota"><?php if ($row_rs_representante['Cli_Fac'] != ""){echo "RUC.: ".$row_rs_cliente['Prs_Ced'];}else{echo "RUC.: ".$row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:<?php echo $cabTop+30;?>px;left:10px;" class="flota"><?php if ($row_rs_representante['Cli_Dir'] != ""){echo substr("DIRECCION: ".$row_rs_representante['Cli_Dir'],0,31);}else{echo substr("DIRECCION: ".$row_rs_cliente['Prs_Dir'],0,31);}?></span>

<span style="top:<?php echo $cabTop;?>px;left:255px;" class="flota"><?php echo "FECHA EMI.: ".$dia.'/'.$mes.'/'.$anio; ?></span>
<span style="top:<?php echo $cabTop+15;?>px;left:255px;" class="flota"><?php echo "CIUDAD: ".$row_institucion['Ciu_Des'];?></span>
<!--<span style="top:170px;left:300px;" class="flota"><?php //$nom = explode(' ',$Ses_Prs_Ape);$ape = explode(' ',$Ses_Prs_Nom); echo "VENDEDOR: ".$nom[0].' '.$ape[0];  ?></span>-->
                     
<?Php 
 $tarifa_0 = 0;
 $tarifa_12 = 0;

?>
  <!-- Opciones para el retorno 
0 = SUBTOTAL
1 = TARIFA 0
2 = TARIFA 12
3 = IVA
4 = DESCUENTO
5 = TOTAL -->
<?php 

if($row_rs_cliente['Tic_Sri']=='1'){
	$aux=110;
}else{
	$aux=140;
}
?>
<span style="top:<?php echo $aux-10; ?>px;left:10px;" class="flota"><?php echo "-----------------------------------------------------------------------------------------"?></span>
<span style="top:<?php echo $aux; ?>px;left:10px;" class="flota"><?php echo "CANT.";?></span>
<span style="top:<?php echo $aux; ?>px;left:60px;" class="flota"><?php echo "DETALLE";?></span>
<span style="top:<?php echo $aux; ?>px;left:250px;" class="flota"><?php echo "P. UNIT.";?></span>
<span style="top:<?php echo $aux; ?>px;left:340px;" class="flota"><?php echo "IMPORTE";?></span>

<span style="top:<?php echo $aux+8; ?>px;left:10px;" class="flota"><?php echo "-----------------------------------------------------------------------------------------"?></span>
<?php $aux=$aux+20;
do{?>
	<span style=" top:<?php echo $aux; ?>px;left:10px;" class="flota"><?php echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<?php echo $aux; ?>px;left:60px;" class="flota"><?php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'];?></span>
    <span style="top:<?php echo $aux; ?>px;left:210px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Pru'], 2);?></span>
    <span style="top:<?php echo $aux; ?>px;left:310px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

<?php $aux+=25; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	

if($row_rs_cliente['Tic_Sri']=='1'){
	$posTot='180';
}else{
	$posTot='210';
}
?>
<span style="top:820px;left:300px;" class="flota"><?php //echo 'x'; ?></span>
<span style="top:<?php echo $posTot;?>px;left:280px;" class="flota">Subtotal:</span>
<span style="top:<?php echo $posTot;?>px;left:310px;" class="flota rigth"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>

<span style="top:<?php echo $posTot+15;?>px;left:280px;" class="flota">Descuento:</span>
<span style="top:<?php echo $posTot+15;?>px;left:310px;" class="flota rigth"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>

<span style="top:<?php echo $posTot+30;?>px;left:280px;" class="flota">Tarifa 0%:</span>
<span style="top:<?php echo $posTot+30;?>px;left:310px;" class="flota rigth"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>

<span style="top:<?php echo $posTot+45;?>px;left:280px;" class="flota">Iva:</span>
<span style="top:<?php echo $posTot+45;?>px;left:310px;" class="flota rigth"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>

<span style="top:<?php echo $posTot+60;?>px;left:280px;" class="flota">Tarifa <?=$resultados[6]?>%:</span>
<span style="top:<?php echo $posTot+60;?>px;left:310px;" class="flota rigth"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></span>

<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:280px;" class="flota">TOTAL:</strong></span>
<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:310px;" class="flota rigth"><strong><?php echo number_format($resultados[5], 2); ?></strong></span>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>