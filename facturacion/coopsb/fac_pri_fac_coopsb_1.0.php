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
    $pagoSri = $row_rs_cliente['Tpc_Cod'];	
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

    /**
	* pagos SRI de la venta
	*/
	$row_pagoSri = $obBD_con1->getRowConsulta(1322, $pagoSri, $obBD_conexion);		
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
 
</head>
<body>
<script type="text/javascript" src="../../facturacion/impreso/JsBarcode.all.js"></script>
<script>
		Number.prototype.zeroPadding = function(){
			var ret = "" + this.valueOf();
			return ret.length == 1 ? "0" + ret : ret;
		};
</script>
<?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);
$top=30;
$claveacceso=$row_rs_cliente['Vet_Xml'];
?>
<div class="ver">
<style type="text/css">
	.mini{position: absolute; font-size: 8px;font-weight: normal; font: 8pt Arial, Geneva, sans-serif;}
	.flota{position: absolute; font-size: 16px;font-weight: normal; font: 16pt Arial, Geneva, sans-serif;}
	.detalle{position: absolute; font-size: 8px;font-weight: normal; font: 8pt Arial, Geneva, sans-serif;}
	.rigth{text-align: left;}
	.ca{word-wrap: break-word; max-width:350px; width:150px;}
	.borde{width:130px; position:absolute;}

</style>

<span style="top:<?php echo $top+40;?>px;left:0px; font: 12pt Verdana;"  class="flota"><strong>CI/RUC:</strong>&nbsp;<?php echo $row_rs_cliente['Prs_Ced'];?></span>
<span style="top:<?php echo $top+65;?>px;left:0px; font: 12pt Verdana;" class="flota"><strong>CLIENTE:</strong>&nbsp;<?php echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];?></span>
<span style="top:<?php echo $top+90;?>px;left:0px; font: 12pt Verdana;" class="flota"><strong>DIR.:</strong>&nbsp;<?echo substr($row_rs_cliente['Prs_Dir'],0,31);?></span>
<span style="top:<?php echo $top+115;?>px;left:0px; font: 12pt Verdana;" class="flota"><strong>FECHA:</strong>&nbsp;<?php echo $dia.'/'.$mes.'/'.$anio.'&nbsp;&nbsp;'.substr($row_rs_cliente['Vet_Sys'],11,18); ?>

 
<span style="top:<?php echo $top+10;?>px;left:0px; font-weight: bold;" class="flota">DETALLE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CANT&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P.U.&nbsp;&nbsp;&nbsp;TOTAL</span>

<?php 
$aux=$top+40;
do{?>	
    <span style="top:<?php echo $aux; ?>px;left:0px;width:530px; font: 16pt Verdana;" class="flota"><?php echo substr ($row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'],0,35);?></span>
    <span style=" top:<?php echo $aux+25; ?>px;left:210px; font: 16pt Verdana;" class="flota"><?php echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<?php echo $aux+25; ?>px;left:310px; font: 16pt Verdana;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Pru'], 4);?></span>
    <span style="top:<?php echo $aux+25; ?>px;left:410px;font: 16pt Verdana;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

<?php $aux+=60; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
?>
<span style="top:683px;left:300px;" class="flota"><?php //echo 'x'; ?></span>
<!--<span style="top:730px;left:55px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',0,36);	?></span>
<span style="top:755px;left:30px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',37,100);	?></span>-->
<?php $posTot=330;?>
<span style="top:<?php echo $posTot+10;?>px;left:275px; font: 16pt Verdana;" class="flota borde">Subtotal:</span>
<span style="top:<?php echo $posTot+10;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>

<span style="top:<?php echo $posTot+35;?>px;left:275px; font: 16pt Verdana;" class="flota borde">Descuento:</span>
<span style="top:<?php echo $posTot+35;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>

<span style="top:<?php echo $posTot+60;?>px;left:275px; font: 16pt Verdana;" class="flota borde">Tarifa 0%:</span>
<span style="top:<?php echo $posTot+60;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>

<span style="top:<?php echo $posTot+85;?>px;left:275px; font: 16pt Verdana;" class="flota borde">Tarifa 12%:</span>
<span style="top:<?php echo $posTot+85;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></span>

<span style="top:<?php echo $posTot+110;?>px;left:275px; font: 16pt Verdana;" class="flota borde">IVA:</span>
<span style="top:<?php echo $posTot+110;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>

<span aling="rigth" style="top:<?php echo $posTot+135;?>px;left:275px; font: 16pt Verdana;" class="flota borde">TOTAL:</strong></span>
<span aling="rigth" style="top:<?php echo $posTot+135;?>px;left:405px; font: 16pt Verdana;" class="flota rigth borde"><strong><?php echo number_format($resultados[5], 2); ?></strong></span>

<span style="top:<?php echo $posTot+272;?>px;left:20px;"  class="flota borde">.</span>

</div>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>