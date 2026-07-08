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
    
    </style>
</head>
<body>
	<table width="608" border="0" class="TablaRepCompr_b">
      <tr>
        <td valign="top" class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte">&nbsp;</td>
        <td class="Texto_Reporte" style="font-size:14px;">&nbsp;</td>
      </tr>
      <tr>
		<td width="18" valign="top" class="Texto_Reporte">&nbsp;</td>		
		<td width="369" class="Texto_Reporte"><?php if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></td>
        <td width="207" class="Texto_Reporte" style="font-size:14px;"><b><?php echo number_format($row_pri_cheque['Che_Val'],2); ?></b></td>
      </tr>
      <tr align="center">
        <td height="43"></td>
        <td height="45" colspan="2" align="left" valign="top"><span class="Texto_Reporte">
       <?php $v_absoluto=explode(".",$row_pri_cheque['Che_Val']);
			echo strtoupper(num2letras($row_pri_cheque['Che_Val']));
			//$row_pri_cheque['Che_Val'];
			?></span></td>
        </tr>
	  <tr>
	    <td height="25" colspan="3" valign="top" class="Texto_Reporte"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;<?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); 
		       echo $ann.'/'.str_pad($mes, 2, '0', STR_PAD_LEFT).'/'.$dia;
		  ?></td>
	    </tr>    
    </table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>