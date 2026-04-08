<?php 
/**
* @abstract Reporte de cheque para banco de machala
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaci�n  2017-06-01
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
            .flota{position: absolute;font-size: 10px;font-weight: normal;font: 11pt Verdana;}
			.flota_valor{position: absolute;font-size: 10px;font-weight: normal;font: 13pt Arial, Helvetica, sans-serif;}
        </style>
    </head>
    <body>
        <span style="top:60px;left:30px;" class="flota"><? if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></span>
        <span style="top:57px;left:370px;" class="flota_valor"><b><? echo number_format($row_pri_cheque['Che_Val'],2).'xxx'; ?></b></span>
        <span style="top:80px;left:25px;" class="flota"><? echo num2letras($row_pri_cheque['Che_Val']).' xxxxxxxxxxxxxxxxxxx';?></span>
        <span style="top:130px;left:0px;" class="flota"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;<?Php echo str_replace('-','/',$row_pri_cheque['Che_Fec']);?></span>   
    </body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>