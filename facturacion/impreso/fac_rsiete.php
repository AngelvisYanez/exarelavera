<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* @abstract Reporte de ventas para la impresiÃ³n en recibo o nota de venta
* @author 
* @version 1.0
* Fecha de actualizaciÃ³n  2012-05-23
* @author 
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
<title><?Php echo '' ?></title>
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
	.empres{position: absolute; font-size: 10px;font-weight: normal; font: 16pt "Arial"}
	.cabecera{position: absolute; font-size: 10px;font-weight: normal; font: 12pt "Arial"}
	.mini{position: absolute; font-size: 10px;font-weight: normal; font: 9pt "Arial"}
	.flota{position: absolute; font-size: 10px;font-weight: normal; font: 9pt "Arial"}
	.detalle{position: absolute; font-size: 10px;font-weight: normal; font: 11pt Verdana, Geneva, sans-serif;}
	.rigth{text-align: right; width: 70px;}
	.center{text-align: center; width: 70px;}
	.ca{word-wrap: break-word; max-width:350px; width:150px;}
</style>

<!--//DATOS EMPRESA-->
<img src="<?php echo $row_institucion['Emp_Log']; ?>" width="60" height="58" style="top: 0px;left:0px;"  >
<span style="top:<?php echo $topCab-20;?>px;left:250px;" class="empres"><strong>  RSIETE CIA. LTDA.</strong></span>
<span style="top:<?php echo $topCab+7;?>px;left:190px;" class="cabecera"><strong>  CANTERA RIO SIETE RSIETE CIA. LTDA.</strong></span>
<span style="top:<?php echo $topCab+28;?>px;left:260px;" class="cabecera"><?php echo "<strong>RUC:</strong> ".$row_institucion['Emp_Ruc']?></span>
<span style="top:<?php echo $topCab+49;?>px;left:0px; padding-top: 5px; padding-bottom: 5px; padding-left: 240px; padding-right: 108px; border: 1px solid; background-color: #0000ff;" class="mini"><strong>VENTA DE MATERIALES PETREOS</strong></span>

<!--DATOS NOTA DE ENTREGA-->
<span style="top:<?php echo $topCab-15;?>px;left:565px;" class="mini"> <strong>NOTA DE ENTREGA</strong></span>
<span style="top:<?php echo $topCab-0;?>px;left:550px; border: 1px solid; padding-left: 15px; padding-right: 15px;padding-top: 10px;padding-bottom: 5px;" class="mini"><?php echo $row_institucion['Suc_Sri'].'-'.$auto['Pun_Sri'].'-'.str_pad($row_rs_cliente['Vet_Num'], 9, "0", STR_PAD_LEFT);?></span>
<span style="top:<?php echo $topCab+30;?>px;left:550px; border: 1px solid; padding-left: 10px; padding-right: 9px;padding-top: 2px;padding-bottom: 2px;" class="mini"><strong>DIA</strong></span>
<span style="top:<?php echo $topCab+30;?>px;left:591px; border: 1px solid; padding-left: 10px; padding-right: 9px;padding-top: 2px;padding-bottom: 2px;" class="mini"><strong>MES</strong></span>
<span style="top:<?php echo $topCab+30;?>px;left:637px; border: 1px solid; padding-left: 12px; padding-right: 12px;padding-top: 2px;padding-bottom: 2px;" class="mini"><strong>AÃ‘O</strong></span>
<span style="top:<?php echo $topCab+49;?>px;left:550px; border: 1px solid; padding-left: 13px; padding-right: 14px;padding-top: 5px;padding-bottom: 5px;" class="mini"><?php echo $dia?></span>
<span style="top:<?php echo $topCab+49;?>px;left:591px; border: 1px solid; padding-left: 16px; padding-right: 16px;padding-top: 5px;padding-bottom: 5px;" class="mini"><?php echo $mes ?></span>
<span style="top:<?php echo $topCab+49;?>px;left:637px; border: 1px solid; padding-left: 12px; padding-right: 12px;padding-top: 5px;padding-bottom: 5px;" class="mini"><?php echo $anio?></span>

<?php if ($row_rs_cliente['Tic_Cod']==1){ ?>
<span style="top:<?php echo $topCab+83;?>px;left:0px;" class="flota">CLAVE DE ACCESO  /  AUTORIZACION</span>
<span style="top:<?php echo $topCab+100;?>px;left:0px; text-align: left; width: 280px;" class="flota ca"><?php echo $row_rs_cliente['Vet_Xml'];?></span>
<?php }else{$top=100;}?>

<!--DATOS DE CLIENTE-->
<span style="top:<?php echo $top+10;?>px;left:5px;" class="flota"><strong>CLIENTE:</strong>&nbsp;<?php echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];?></span>
<span style="top:<?php echo $top+10;?>px;left:385px;" class="flota"><strong>CI/RUC:</strong>&nbsp;<?php if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
<span style="top:<?php echo $top+30;?>px;left:5px;" class="flota"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_rs_cliente['Prs_Dir']?></span>
<span style="top:<?php echo $top+30;?>px;left:385px;" class="flota"><strong>CORREO:</strong>&nbsp;<?php echo $row_rs_cliente['Prs_Cor']?></span>

<!--ITEMS/PRODUCTOS-->
<span style="top:<?php echo $top+50;?>px;left:0px; font-weight: bold; border: 1px solid; padding-left: 25px; padding-right: 25px; padding-top: 2px; padding-bottom: 2px;" class="flota">Cantidad</span>
<span style="top:<?php echo $top+50;?>px;left:102px; font-weight: bold; border: 1px solid; padding-left: 125px; padding-right: 125px; padding-top: 2px; padding-bottom: 2px;" class="flota">Material</span>
<span style="top:<?php echo $top+50;?>px;left:398px; font-weight: bold; border: 1px solid; padding-left: 48px; padding-right: 48px; padding-top: 2px; padding-bottom: 2px;" class="flota">P.Unitario</span>
<span style="top:<?php echo $top+50;?>px;left:550px; font-weight: bold; border: 1px solid; padding-left: 55px; padding-right: 55px; padding-top: 2px; padding-bottom: 2px;" class="flota">Total</span>

<?php $aux=$top+80;
do{?>	
		<span style=" top:<?php echo $aux; ?>px;left:40px;" class="flota"><?php echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></span>
    <span style="top:<?php echo $aux; ?>px;left:130px;" class="flota"><?php echo substr ($row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs'],0,35);?></span>
		
    <span style="top:<?php echo $aux; ?>px;left:465px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Pru'], 4);?></span>
    <span style="top:<?php echo $aux; ?>px;left:610px;" class="flota rigth"><?php echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

<?php $aux+=20; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
?>
<span style="top:<?php echo $aux+50;?>px;left:0px; "class="mini"><strong>FORMA DE PAGO: </strong><?php echo $row_rs_pagos['For_Des']?></span>
<span style="top:<?php echo $aux+75;?>px;left:0px; padding-right: 300px; background-color: #0000ff;" class="mini"><strong>OBSERVACIONES: </strong>&nbsp;<?php echo $observacion?></span>
<span style="top:<?php echo $aux+145;?>px;left:0px; "class="mini">________________</span>
<span style="top:<?php echo $aux+165;?>px;left:20px; "class="mini center"><strong>ENTREGUÃ‰<br>CONFORME</strong>&nbsp;</span>
<span style="top:<?php echo $aux+145;?>px;left:130px; "class="mini">________________</span>
<span style="top:<?php echo $aux+165;?>px;left:140px; "class="mini center"><strong>DESPACHADO<br>POR</strong>&nbsp;</span>
<span style="top:<?php echo $aux+145;?>px;left:260px; "class="mini">________________</span>
<span style="top:<?php echo $aux+165;?>px;left:280px; "class="mini center"><strong>RECIBI<br>CONFORME</strong>&nbsp;</span>
<span style="top:683px;left:300px;" class="flota"><?php //echo 'x'; ?></span>
<!--<span style="top:730px;left:55px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',0,36);	?></span>
<span style="top:755px;left:30px;" class="flota"><?php //$v_absoluto=explode(".",$resultados[5]);echo substr(num2letras($v_absoluto[0],false,true).' con '.str_pad($v_absoluto[1],  2, "0").'/100',37,100);	?></span>-->
<?php $posTot=$aux+100;?>
<span style="top:<?php echo $posTot;?>px;left:465px;" class="flota rigth">Descuento:</span>
<span style="top:<?php echo $posTot;?>px;left:610px;" class="flota rigth"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>

<span style="top:<?php echo $posTot+15;?>px;left:465px;" class="flota rigth">Subtotal:</span>
<span style="top:<?php echo $posTot+15;?>px;left:610px;" class="flota rigth"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>

<span style="top:<?php echo $posTot+30;?>px;left:465px;" class="flota rigth">Tarifa 0%:</span>
<span style="top:<?php echo $posTot+30;?>px;left:610px;" class="flota rigth"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>

<span style="top:<?php echo $posTot+45;?>px;left:465px;" class="flota rigth">Tarifa <?=$resultados[6]?>%: </span>
<span style="top:<?php echo $posTot+45;?>px;left:610px;" class="flota rigth"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></span>

<span style="top:<?php echo $posTot+60;?>px;left:465px;" class="flota rigth">IVA:</span>
<span style="top:<?php echo $posTot+60;?>px;left:610px;" class="flota rigth"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>

<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:465px;" class="flota rigth"><strong>TOTAL:</strong></span>
<span aling="rigth" style="top:<?php echo $posTot+75;?>px;left:610px;" class="flota rigth"><strong><?php echo number_format($resultados[5], 2); ?></strong></span>

</div>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>