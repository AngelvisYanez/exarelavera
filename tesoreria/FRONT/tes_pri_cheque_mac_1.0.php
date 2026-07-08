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
        <style type="text/css">
            .flota{position: absolute;font-size: 10px;font-weight: normal;font: 10.5pt Arial, Helvetica, sans-serif;}
        </style>
    </head>
    <body>
        <span style="top:39px;left:35px;" class="flota"><?php if($row_pri_cheque['Che_Ben']==''){echo $row_pri_cheque['Prs_Ape'].' '.$row_pri_cheque['Prs_Nom'];}else{echo $row_pri_cheque['Che_Ben'];} ?></span>
        <span style="top:39px;left:415px;" class="flota"><b><?php echo number_format($row_pri_cheque['Che_Val'],2); ?></b></span>
        <span style="top:65px;left:25px;" class="flota"><?php echo '<span style="letter-spacing:0.1em">'.num2letras($row_pri_cheque['Che_Val']).'</span>';?></span>
        <span style="top:112px;left:10px;" class="flota"><?Php echo $row_institucion['Ciu_Des']?>, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $fecha); echo $ann.'/'.$mes.'/'.$dia;?></span>   
    </body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>