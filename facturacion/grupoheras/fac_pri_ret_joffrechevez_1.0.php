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
.flota{position: absolute;font-size: 10px;font-weight: normal;font: 9pt Verdana;}
.autoriza{position: absolute;font-size: 10px;font-weight: normal;font: 7pt Verdana;}
.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}
</style></head>
<body>
<?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); ?>
<span style="top:120px;left:585px;" class="flota"><? echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>
<span style="top:146px;left:80px;" class="flota"><? echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
<span style="top:150px;left:570px;" class="flota"><? echo $row_prin_renta['Prs_Ced']; ?></span>

<span style="top:180px;left:80px;" class="flota"><? echo substr($row_prin_renta['Prs_Dir'],0,50); ?></span>
<span style="top:180px;left:520px;" class="flota"><? echo $row_prin_renta['Ciu_Des']; ?></span>

<span style="top:235px;left:95px;" class="flota"><? echo $row_prin_renta['Tic_Des']; ?></span>
<span style="top:235px;left:280px;" class="flota"><? echo $row_prin_renta['Cop_Num']; ?></span>
<span style="top:235px;left:500px;" class="flota"><? echo $row_prin_renta['Cop_Fec']; ?></span>

<span style="top:270px;left:40px;" class="autoriza"><? echo $row_prin_renta['Cop_Aut'];  ?></span>
<span style="top:270px;left:410px;" class="flota"><? echo $row_prin_renta['Cop_Imf']; ?></span>
<span style="top:270px;left:580px;" class="flota"><? echo $row_prin_renta['Cop_Cad']; ?></span>

<?Php $Total_Ret=0; 
$Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); 
$fila="330";
foreach($rs_prin_renta as $row)
{ ?>
	<span style="top:<? echo $fila;?>px;left:40px;" class="flota"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci;?></span>
	<span style="top:<? echo $fila;?>px;left:130px;" class="flota"><? echo $row['Ren_Sri']?></span>
	<span style="top:<? echo $fila;?>px;left:280px;" class="flota"><? echo $row['Ret_Imp']?></span>
	<span style="top:<? echo $fila;?>px;left:450px;" class="flota"><? echo number_format($row['Ret_Bas'], 2,'.',',');  ?></span>
	<span style="top:<? echo $fila;?>px;left:560px;" class="flota"><? echo $row['Ren_Por'].'%'; ?></span>
	<span style="top:<? echo $fila;?>px;left:660px;" class="flota"><? echo $Val_Ret=formato_numero((formato_numero($row['Ret_Bas'],2,1)* $row['Ren_Por'])/100,2,1); $Total_Ret+=$Val_Ret; ?></span>
<?Php unset($Ejerci); $fila+=20;}/*Fin del foreach*/?>
<span style="top:395px;left:660px;" class="flota"><strong><?Php echo $Total_Ret; ?></strong></span>
</body>
</html>
<?Php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?> 