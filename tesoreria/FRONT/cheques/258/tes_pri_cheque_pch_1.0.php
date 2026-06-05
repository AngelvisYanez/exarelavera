<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* @abstract Reporte de cheque para banco de machala
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaciï¿½n  2012-07-23
* @author Lewis Chimarro
* Fecha de actualizaciï¿½n  2013-03-11
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;

/**
* Consulta el total de las facturas por fecha dada 
*/
$row_pri_cheque = $obBD_con1->getRowConsulta(144, $codigo2.'*'.$asi.'*'.$ban.'*'.$pro, $obBD_conexion);
$fecha = $row_pri_cheque['Che_Fec'];	
/**
* Consulta de la cabecera del reporte 
*/
$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.style2 {color: #000000}
.Estilo1 {font-size: 18px}
-->
</style>
</head>
<body>
<table width="608" border="0" class="TablaRepCompr_b">
      <tr>
        <td height="25" valign="top" class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte" style="font-size:14px;">&nbsp;</td>
      </tr>
      <tr>
		<td width="25" height="32" valign="top" class="Texto_Reporte">&nbsp;</td>		
		<td width="371" valign="middle" class="Texto_Reporte" style="font-size:14px;"><? if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></td>
        <td width="195" valign="middle" style="color: #000000 !important;"><span class="Texto_Reporte" style="font-size:15px; "><? echo number_format($row_pri_cheque['Che_Val'],2); ?></span>xxx</td>
      </tr>
      <tr align="center">
        <td height="45"></td>
        <td height="45" colspan="2" align="left" valign="top" style="color: #000000 !important;"><span class="Texto_Reporte" style="font-size:14px;">
          
          <? $v_absoluto=explode(".",$row_pri_cheque['Che_Val']);
			echo strtoupper(num2letras($row_pri_cheque['Che_Val']));
			//$row_pri_cheque['Che_Val'];
			?></span> xxxxxxxxxxxxxxxxxxxxxxxxxxxx</td>
</tr>
<tr>
      <td width="25" /td>
	    <td height="25" colspan="3" valign="top" class="Texto_Reporte" style="font-size:14px;"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;<?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); 
		       echo $ann.'/'.strtoupper( mes($mes, 1)).'/'.$dia;
		  ?>
   </td>
</tr>    
    </table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>