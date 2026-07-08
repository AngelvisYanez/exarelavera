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
            .flota{position: absolute;font-size: 10px;font-weight: normal;font: 7pt Arial, Helvetica, sans-serif;}
        </style>
    </head>
    <body>
        <span style="top:8px;left:20px;" class="flota"><?php if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></span>
        <span style="top:7px;left:380px;" class="flota"><b><?php echo number_format($row_pri_cheque['Che_Val'],2); ?></b></span>
        <span style="top:17px;left:20px;" class="flota"><?php $v_absoluto=explode(".",$row_pri_cheque['Che_Val']); echo '<span style="letter-spacing:0.1em">'.num2letras($v_absoluto[0],false,true).'</span>&nbsp; '.$v_absoluto[1].' / 100'; //$row_pri_cheque['Che_Val'];?></span>
        <span style="top:40px;left:0px;" class="flota"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;<?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); echo $ann.'/'.str_pad($mes, 2, '0', STR_PAD_LEFT).'/'.$dia;?></span>   
    </body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>