<?php 
/**
* @abstract Reporte de cheque para banco de machala
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaciï¿½n  2017-06-01
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
        <style type="text/css">
            .flota{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Arial, Helvetica, sans-serif;}
        </style>
    </head>
    <body>
		<? //$verti=64;
		$verti=40;?>
        <span style="top:<? echo $verti;?>px;left:545px;" class="flota"><b><? echo number_format($row_pri_cheque['Che_Val'],2); ?>xxx</b></span>
		<span style="top:<? echo $verti+5;?>px;left:85px;" class="flota"><? if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></span>        
        <span style="top:<? echo $verti+31;?>px;left:35px;" class="flota"><? echo '<span style="letter-spacing:0.1em">'.strtoupper (num2letras($row_pri_cheque['Che_Val'])).'</span>';?>xxxxxxx</span>
        <span style="top:<? echo $verti+86;?>px;left:10px;" class="flota"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?Php list($ann, $mes, $dia) = split('[/.-]', $fecha); echo $ann.'/'.$mes.'/'.$dia;?></span>   
    </body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>