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
$top=150;
$topCab=30;
$claveacceso=$row_rs_cliente['Vet_Xml'];
?>
<div class="ver">
<style type="text/css">
	.cabecera{position: absolute; font-size: 10px;font-weight: normal; font: 12pt "Lucida Console", Monaco, monospacee}
	.mini{position: absolute; font-size: 10px;font-weight: normal; font: 11pt Verdana, Geneva, sans-serif;}
	.flota{position: absolute; font-size: 10px;font-weight: normal; font: 11pt Verdana, Geneva, sans-serif;}
	.detalle{position: absolute; font-size: 10px;font-weight: normal; font: 11pt Verdana, Geneva, sans-serif;}
	.rigth{text-align: right; width: 70px;}
	.ca{word-wrap: break-word; max-width:350px; width:150px;}
</style>


<span style="top:<? echo $topCab-20;?>px;left:0px;" class="cabecera"><strong>*JIMENEZ TORRES RAMIRO ALBERTO*</strong></span>

<span style="top:<? echo $topCab+20;?>px;left:0px;" class="mini"><? echo substr($row_institucion['Suc_Dir'],0,40);?></span>
<span style="top:<? echo $topCab+35;?>px;left:0px;" class="mini"><? echo "<strong>CEL:</strong> ".$row_institucion['Suc_Te1']."&nbsp;&nbsp;&nbsp;&nbsp;"."<strong>TELF.:</strong> ".$row_institucion['Suc_Te2']?></span>
<span style="top:<? echo $topCab+50;?>px;left:0px;" class="mini"><? echo "<strong>RUC:</strong> ".$row_institucion['Emp_Ruc']?></span>
<? if($row_institucion['Emp_Cnt']=='S'){?>
<span style="top:<? echo $topCab+65;?>px;left:0px;" class="mini"><? echo "OBLIGADO A LLEVAR CONTABILIDAD"?></span>
<?}?>
<span style="top:<? echo $topCab+75;?>px;left:20px;" class="mini">
	<div>
		<img id="barcode6"/>
		<script>
			//var repeat6 = function(){
			//	JsBarcode("#barcode6", "<? echo $row_institucion['Suc_Sri'].'-'.$auto['Pun_Sri'].'-'.str_pad($row_rs_cliente['Vet_Num'], 9, "0", STR_PAD_LEFT);?>",{width:1 ,height:"27" ,format:"code128",displayValue:false,fontSize:10});
			//};			
			//repeat6();
		</script>
	</div>
</span>
<? if ($row_rs_cliente['Tic_Cod']==1){ ?>
<span style="top:<? echo $topCab+83;?>px;left:0px;" class="flota">CLAVE DE ACCESO  /  AUTORIZACION</span>
<span style="top:<? echo $topCab+100;?>px;left:0px; text-align: left; width: 280px;" class="flota ca"><? echo $row_rs_cliente['Vet_Xml'];?></span>
<? }else{$top=100;}?>

<span style="top:<? echo $top+20;?>px;left:0px;" class="flota"><strong><? if($row_rs_cliente['Tic_Cod']==1){ echo "FACTURA:";}else{echo "RECIBO:";}?></strong>&nbsp;<? echo $row_institucion['Suc_Sri'].'-'.$auto['Pun_Sri'].'-'.str_pad($row_rs_cliente['Vet_Num'], 9, "0", STR_PAD_LEFT);?></span>
<span style="top:<? echo $top+35;?>px;left:0px;" class="flota"><strong>CI/RUC:</strong>&nbsp;<? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:<? echo $top+50;?>px;left:0px;" class="flota"><strong>CLIENTE:</strong>&nbsp;<? echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];?></span>

<span style="top:<? echo $top+65;?>px;left:0px;" class="flota"><strong>FECHA:</strong>&nbsp;<? echo $dia.'/'.$mes.'/'.$anio.'&nbsp;&nbsp;'.substr($row_rs_cliente['Vet_Sys'],11,18); ?></span>
<? if($claveacceso!=''){?>

<? }?>

<span style="top:<? echo $top+95;?>px;left:0px;" class="flota">DETALLE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CANT&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P.U.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</span>
<span style="top:<? echo $top+105;?>px;left:0px;" class="flota">-------------------------------------------------------</span>
<? $aux=$top+120;
do{?>	
    <span style="top:<? echo $aux; ?>px;left:0px;" class="flota"><? echo substr ($row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'],0,35);?></span>
	<span style=" top:<? echo $aux+15; ?>px;left:75px;" class="flota"><? echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<? echo $aux+15; ?>px;left:115px;" class="flota rigth"><? echo number_format($row_rs_cliente['Vet_Pru'], 4);?></span>
    <span style="top:<? echo $aux+15; ?>px;left:190px;" class="flota rigth"><? echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

<? $aux+=30; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
?>
<span style="top:<? echo $aux+50;?>px;left:0px;" class="flota">------------------------------------------</span>
<span style="top:683px;left:300px;" class="flota"><? //echo 'x'; ?></span>
<!--<span style="top:730px;left:55px;" class="flota"><? //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',0,36);	?></span>
<span style="top:755px;left:30px;" class="flota"><? //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',37,100);	?></span>-->
<? $posTot=$aux+80;?>
<span style="top:<? echo $posTot;?>px;left:110px;" class="flota">Subtotal:</span>
<span style="top:<? echo $posTot;?>px;left:210px;" class="flota rigth"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>

<span style="top:<? echo $posTot+15;?>px;left:110px;" class="flota">Descuento:</span>
<span style="top:<? echo $posTot+15;?>px;left:210px;" class="flota rigth"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>

<span style="top:<? echo $posTot+30;?>px;left:110px;" class="flota">Tarifa 0%:</span>
<span style="top:<? echo $posTot+30;?>px;left:210px;" class="flota rigth"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>

<span style="top:<? echo $posTot+45;?>px;left:110px;" class="flota">Tarifa <?= $resultados[6] ?>%:</span>
<span style="top:<? echo $posTot+45;?>px;left:210px;" class="flota rigth"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></span>

<span style="top:<? echo $posTot+60;?>px;left:110px;" class="flota">IVA:</span>
<span style="top:<? echo $posTot+60;?>px;left:210px;" class="flota rigth"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>

<span aling="rigth" style="top:<? echo $posTot+75;?>px;left:110px;" class="flota">TOTAL:</strong></span>
<span aling="rigth" style="top:<? echo $posTot+75;?>px;left:210px;" class="flota rigth"><strong><?php echo number_format($resultados[5], 2); ?></strong></span>
<?php if (!empty($resultados[7]) && floatval($resultados[7]) != 0): ?>
	<span aling="rigth" style="top:<? echo $posTot+90;?>px;left:110px;" class="flota">Valor Efectivo:</span>
	<span aling="rigth" style="top:<? echo $posTot+90;?>px;left:210px;" class="flota rigth"><?php echo number_format($resultados[7], 2); ?></span>
	<!-- <span><?php echo number_format($resultados[7], 2); ?></span> -->
<?php endif; ?>
<?php if (!empty($resultados[8]) && floatval($resultados[8]) != 0): ?>
	<span aling="rigth" style="top:<? echo $posTot+105;?>px;left:110px;" class="flota">Cambio:</span>
	<!-- <span style="text-align: right;" colspan="3">Cambio:</span> -->
	<span aling="rigth" style="top:<? echo $posTot+105;?>px;left:210px;" class="flota rigth"><?php echo number_format($resultados[8], 2); ?></span>
	<!-- <span><?php echo number_format($resultados[8], 2); ?></span> -->
<?php endif; ?>

<? if($claveacceso!=''){?>
<span style="top:<? echo $posTot+125;?>px;left:0px;" class="flota">TIPO DE PAGO:</span>
<span style="top:<? echo $posTot+140?>px;left:0px;" class="flota"><?php echo $row_pagoSri['Tpc_Sri'].' - '.substr($row_pagoSri['Tpc_Des'],0,28).'&nbsp;&nbsp;'.number_format($resultados[5], 2); ?></span>

<span style="top:<? echo $posTot+180;?>px;left:0px;" class="flota">DESCARGUE SU COMPROBANTE EN:</span>
<span style="top:<? echo $posTot+195;?>px;left:0px;" class="flota">http://exa.ofsercont.com/pdf.php</span>
<? }?>
</div>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>