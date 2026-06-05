<?php 
/**
* @abstract Reporte de cheque
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizacion  2017-06-01
* @author Erik Niebla
*/
require_once('../../../../Librerias/config.php/register_globals.php');
include($APP_REAL_PATH.'/tesoreria/FRONT/cheques/cheque_config.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">					
<html>
<head>
    <title><?Php echo $Ses_Sys_Nom; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link href="../../../../mascaras/model1/estilos/print.css" rel="stylesheet" type="text/css">
    <style type="text/css">
	.report {				
		font-family: Verdana;
		font-size: 14px;	
	}	
    </style>
</head>
<body>
<table width="608" border="0" >
      <tr>
        <td height="29" valign="top" class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte" style="font-size:14px;">&nbsp;</td>
      </tr>
      <tr>
		<td width="22" height="32" valign="top" class="report">&nbsp;</td>		
		<td width="371" valign="middle" class="report" style="font-size:13px;"><? if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></td>
        <td width="205" valign="middle"><span class="report" style="font-size:13px;"><? echo number_format($row_pri_cheque['Che_Val'],2); ?></span>xxx</td>
      </tr>
      <tr align="center">
        <td height="50"></td>
        <td height="53" colspan="2" align="left" valign="top"><span class="report" style="font-size:13px;">
          
          <? $v_absoluto=explode(".",$row_pri_cheque['Che_Val']);
			echo strtoupper(num2letras($row_pri_cheque['Che_Val']));
			//$row_pri_cheque['Che_Val'];
			?></span> xxxxxxxxxxxxxxxxxxxxxxxxxxxx</td>
</tr>
<tr>
	    <td height="25" colspan="3" valign="top" class="report" style="font-size:13px;"><?Php echo $row_institucion['Ciu_Des']?>,<strong> &nbsp;</strong>	      <?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); 
		       echo $ann.'/'.strtoupper( mes($mes, 1)).'/'.$dia;
		  ?></td>
</tr>    
    </table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>