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
}	
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {				
	font-family: Verdana, Geneva, sans-serif;
	font-size: 14px;	
}
.Letra_punto_venta_3 {				
	font-family: Verdana;
	font-size: 11px; 
}
.flota{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Verdana;}
.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 7pt Verdana, Geneva, sans-serif;}
</style></head>
<body>
<?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); 
//$verti=128;
$verti=35;
?>
<span style="top:<?php echo $verti+85;?>px;left:568px;" class="flota"><?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>
<span style="top:<?php echo $verti+45;?>px;left:80px;" class="flota"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
<span style="top:<?php echo $verti+65;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Ced']; ?></span>

<span style="top:<?php echo $verti+85;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Dir']; //90?></span>

<span style="top:<?php echo $verti+105;?>px;left:70px;" class="flota"><?php echo $row_prin_renta['Tic_Des']; ?></span>
<span style="top:<?php echo $verti+105;?>px;left:280px;" class="flota"><?php echo $row_prin_renta['Cop_Num']; ?></span>
<span style="top:<?php echo $verti+105;?>px;left:570px;" class="flota"><?php echo $row_prin_renta['Cop_Fec']; ?></span>





<span style="top:<?php echo $verti+435;?>px;left:568px;" class="flota"><?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>
<span style="top:<?php echo $verti+395;?>px;left:80px;" class="flota"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
<span style="top:<?php echo $verti+415;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Ced']; ?></span>

<span style="top:<?php echo $verti+435;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Dir']; //90?></span>

<span style="top:<?php echo $verti+455;?>px;left:70px;" class="flota"><?php echo $row_prin_renta['Tic_Des']; ?></span>
<span style="top:<?php echo $verti+455;?>px;left:280px;" class="flota"><?php echo $row_prin_renta['Cop_Num']; ?></span>
<span style="top:<?php echo $verti+455;?>px;left:570px;" class="flota"><?php echo $row_prin_renta['Cop_Fec']; ?></span>




<span style="top:<?php echo $verti+780;?>px;left:568px;" class="flota"><?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>
<span style="top:<?php echo $verti+740;?>px;left:80px;" class="flota"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
<span style="top:<?php echo $verti+760;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Ced']; ?></span>

<span style="top:<?php echo $verti+780;?>px;left:50px;" class="flota"><?php echo $row_prin_renta['Prs_Dir']; //90?></span>

<span style="top:<?php echo $verti+800;?>px;left:70px;" class="flota"><?php echo $row_prin_renta['Tic_Des']; ?></span>
<span style="top:<?php echo $verti+800;?>px;left:280px;" class="flota"><?php echo $row_prin_renta['Cop_Num']; ?></span>
<span style="top:<?php echo $verti+800;?>px;left:570px;" class="flota"><?php echo $row_prin_renta['Cop_Fec']; ?></span>


<?Php $Total_Ret=0;
$Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); 
	$fila="200";
	foreach($rs_prin_renta as $row)
	{ ?>
		<span style="top:<?php echo $fila;?>px;left:30px;" class="flota"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci;?></span>
		<span style="top:<?php echo $fila;?>px;left:120px;" class="flota"><?php echo $row['Ren_Sri']?></span>
		<span style="top:<?php echo $fila;?>px;left:260px;" class="flota"><?php echo $row['Ret_Imp']?></span>
		<span style="top:<?php echo $fila;?>px;left:450px;" class="flota"><?php echo number_format($row['Ret_Bas'], 2,'.',',');  ?></span>
		<span style="top:<?php echo $fila;?>px;left:570px;" class="flota"><?php echo $row['Ren_Por'].'%'; ?></span>
		<span style="top:<?php echo $fila;?>px;left:680px;" class="flota"><?php echo $Val_Ret=number_format((number_format($row['Ret_Bas'],2,'.','')*$row['Ren_Por'])/100, 2,'.',','); $Total_Ret+=$Val_Ret; ?></span>
	<?Php unset($Ejerci); $fila+=20;}//Fin del foreach ?>
	<span style="top:285px;left:690px;" class="flota"><?Php echo number_format ($Total_Ret, 2,'.',''); ?></span>



<?Php $Total_Ret=0;
$Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); 
	$fila="560";
	foreach($rs_prin_renta as $row)
	{ ?>
		<span style="top:<?php echo $fila;?>px;left:30px;" class="flota"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci;?></span>
		<span style="top:<?php echo $fila;?>px;left:120px;" class="flota"><?php echo $row['Ren_Sri']?></span>
		<span style="top:<?php echo $fila;?>px;left:260px;" class="flota"><?php echo $row['Ret_Imp']?></span>
		<span style="top:<?php echo $fila;?>px;left:450px;" class="flota"><?php echo number_format($row['Ret_Bas'], 2,'.',',');  ?></span>
		<span style="top:<?php echo $fila;?>px;left:570px;" class="flota"><?php echo $row['Ren_Por'].'%'; ?></span>
		<span style="top:<?php echo $fila;?>px;left:680px;" class="flota"><?php echo $Val_Ret=number_format((number_format($row['Ret_Bas'],2,'.','')*$row['Ren_Por'])/100, 2,'.',','); $Total_Ret+=$Val_Ret; ?></span>
	<?Php unset($Ejerci); $fila+=20;}//Fin del foreach ?>
	<span style="top:675px;left:690px;" class="flota"><?Php echo number_format ($Total_Ret, 2,'.',''); ?></span>



<?Php $Total_Ret=0;
$Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); 
	$fila="900";
	foreach($rs_prin_renta as $row)
	{ ?>
		<span style="top:<?php echo $fila;?>px;left:30px;" class="flota"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci;?></span>
		<span style="top:<?php echo $fila;?>px;left:120px;" class="flota"><?php echo $row['Ren_Sri']?></span>
		<span style="top:<?php echo $fila;?>px;left:260px;" class="flota"><?php echo $row['Ret_Imp']?></span>
		<span style="top:<?php echo $fila;?>px;left:450px;" class="flota"><?php echo number_format($row['Ret_Bas'], 2,'.',',');  ?></span>
		<span style="top:<?php echo $fila;?>px;left:570px;" class="flota"><?php echo $row['Ren_Por'].'%'; ?></span>
		<span style="top:<?php echo $fila;?>px;left:680px;" class="flota"><?php echo $Val_Ret=number_format((number_format($row['Ret_Bas'],2,'.','')*$row['Ren_Por'])/100, 2,'.',','); $Total_Ret+=$Val_Ret; ?></span>
	<?Php unset($Ejerci); $fila+=20;}//Fin del foreach ?>
	<span style="top:1015px;left:690px;" class="flota"><?Php echo number_format ($Total_Ret, 2,'.',''); ?></span>


</body>
</html>
<?Php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?> 