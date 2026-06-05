<?php 
/**
* @abstract Reporte de cheque para banco de machala
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
   
    </style>
</head>
<body>
	<table width="608" border="0" class="TablaRepCompr_b">
      <tr>
        <td width="15" height="37" valign="top" class="Texto_Reporte">&nbsp;</td>
        <td width="375" class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte" style="font-size:14px;">&nbsp;</td>
      </tr>
      <tr>
		<td height="30" colspan="2" valign="middle" class="Texto_Reporte" style="font-size:14px;">&nbsp;&nbsp;&nbsp;<strong><? if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></strong></td>		
		<td width="204" valign="middle" ><span class="Texto_Reporte" style="font-size:16px;"><strong><? echo number_format($row_pri_cheque['Che_Val'],2); ?></strong></span><strong>xxx</strong></td>
      </tr>
      <tr align="center">
        
        <td height="50" colspan="3" align="left" valign="top"><span class="Texto_Reporte" style="font-size:15px;">&nbsp;&nbsp;<strong>
          <? $v_absoluto=explode(".",$row_pri_cheque['Che_Val']);
			echo strtoupper(num2letras($row_pri_cheque['Che_Val']));
			//$row_pri_cheque['Che_Val'];
			?></strong></span><strong> xxxxxxxxxxxx</strong></td>
      </tr>
	  <tr>
	    <td height="25" colspan="3" valign="top" class="Texto_Reporte" style="font-size:15px;"><strong><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;<?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); 
		       echo $ann.'/'.strtoupper( mes($mes, 1)).'/'.$dia;
		  ?>
	    </strong></td>
	    </tr>    
    </table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>