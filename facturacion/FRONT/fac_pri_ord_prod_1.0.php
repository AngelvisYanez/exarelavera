<?php 
/**
* @abstract Reporte de ajuste de productos
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización: 2012-07-08
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_produ.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Produ($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Produ;
	 	 
if (isset($Ord_Cod))
{	
    /** Consulta de los materiales */
    $row_rs_cabecera = $obBD_con1->getRowConsulta(11, $Ord_Cod, $obBD_conexion);
    /** Consulta de los materiales */
    $row_rs_detalle = $obBD_con1->getArrayConsulta(12, $Ord_Cod, $obBD_conexion);
    //var_dump($row_rs_cabecera);
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
</head>
<body>
<table width="100%" style="table-layout:fixed;border-collapse: collapse;font-size:9px;width:700px;" cellspacing="0" cellpadding="0">
    <tr>
        <td width="15%"></td>
        <td width="20%"></td>
        <td width="15%"></td>
        <td width="15%"></td>
        <td width="20%"></td>
        <td width="15%"></td>
    </tr>
    <tr>
        <td colspan="6" align="center" style="font-size:14px;border: solid thin;"><strong><?php echo $Ses_Emp_Nom; ?><br><font style="font-size: 12px">ORDEN DE TRABAJO</font></strong></td>  
    </tr>
    <tr><td colspan="6" style="height:10px;"></td></tr>
    <tr>
        <td align="left"><strong>Fecha:</strong></td>
        <td colspan="2"><?php echo $row_rs_cabecera['Ord_Fec']; ?></td>
        <td align="left"><strong>Numero:</strong></td>
        <td colspan="2"><?php echo str_pad($row_rs_cabecera['Ord_Cod'], 7, "0", STR_PAD_LEFT); ?></td>
    </tr>
    <tr>
        <td align="left"><strong>Cliente:</strong></td>
        <td colspan="5"><?php echo $row_rs_cabecera['cliente']; ?></td>
    </tr>
    <tr>
        <td align="left"><strong>Mescladores:</strong></td>
        <td colspan="5"><?php echo $row_rs_cabecera['Ord_Mes']; ?></td>
    </tr>
    <tr><td colspan="6" style="height:5px;"></td></tr>
    
    <tr>
        <td align="left"><strong>Formula:</strong></td>
        <td colspan="2"><?php echo $row_rs_cabecera['Mes_Nom']; ?></td>
        <td align="left"><strong>Cantidad:</strong></td>
        <td colspan="2"><?php echo $row_rs_cabecera['Ord_Res'].'  '.$row_rs_cabecera['Uni_Des']; ?></td>
    </tr>
    <tr>
        <td><strong>Producto Final:</strong></td>
        <td colspan="5"><?php echo $row_rs_cabecera['Ite_Lar']; ?></td>    
    </tr>
    <tr>
        <td><strong>Descripción:</strong></td>
        <td colspan="5"><?php echo $row_rs_cabecera['Ord_Obs']; ?></td>    
    </tr>
    <tr><td colspan="6" style="height:10px;"></td></tr>
    <tr>
        <td colspan="4" align="center" style="border: solid thin;"><strong>Materia Prima</strong></td>
        <td align="center" style="border: solid thin;"><strong>Cantidad</strong></td>
        <td align="center" style="border: solid thin;"><strong>Unidad</strong></td>
    </tr>
    <?php foreach ($row_rs_detalle AS $row){ ?>
    <tr style="border: solid thin;">
        <td colspan="4" style="white-space: nowrap; overflow: hidden;"><?php echo $row['Ite_Lar']; ?></td>
        <td align="right"><?php echo $row['Ord_Can']; ?></td>
        <td>&nbsp;&nbsp;&nbsp;<?php echo $row['Uni_Des']; ?></td>
    </tr>
    <?php } ?>
    <tr><td colspan="6" style="height:10px;"></td></tr>
    <tr>
        <td align="right"><strong>Aut. Por:</strong></td>
        <td colspan="2">___________________</td>
        <td align="right"><strong>Responsable:</strong></td>
        <td colspan="2">___________________</td>
    </tr>
</table>
<?php if($row_rs_cabecera['Ord_Res']>$row_rs_cabecera['Mes_Max']){ 
        $iguales=floor($row_rs_cabecera['Ord_Res']/$row_rs_cabecera['Mes_Max'])*$row_rs_cabecera['Mes_Max']; 
        $row_rs_formula = $obBD_con1->getArrayConsulta(13, $row_rs_cabecera['Mes_Cod'], $obBD_conexion);
        //var_dump($row_rs_formula);
        ?>
    <table width="100%" style="table-layout:fixed;border-collapse: collapse;font-size:9px;width:700px;" cellspacing="0" cellpadding="0">
        <tr>
            <td width="15%"></td>
            <td width="20%"></td>
            <td width="15%"></td>
            <td width="15%"></td>
            <td width="20%"></td>
            <td width="15%"></td>
        </tr>
        <tr><td colspan="6" style="height:10px;"></td></tr>
        <tr>
            <td colspan="6" align="center" style="font-size:10px;"><strong>PROCESO DE PREPARACIÓN</strong></td>  
        </tr>
    <?php if($iguales<=$row_rs_cabecera['Ord_Res']){ ?>
        <tr style="border-bottom: solid thin;">
            <td align="left"><strong>Paradas:</strong></td>
            <td colspan="5"><?php echo floor($row_rs_cabecera['Ord_Res']/$row_rs_cabecera['Mes_Max']); ?></td>            
        </tr> 
        <?php foreach ($row_rs_detalle AS $row){ ?>            
                <?php foreach ($row_rs_formula AS $row2){ 
                    if($row['Pro_Cod']==$row2['Pro_Cod']){ ?>
                    <tr>
                        <td colspan="4" style="white-space: nowrap; overflow: hidden;"><?php echo $row['Ite_Lar']; ?></td>
                        <td align="right"><?php echo $row2['Mes_Can']*$row_rs_cabecera['Mes_Max']; ?></td>   
                        <td>&nbsp;&nbsp;&nbsp;<?php echo $row['Uni_Des']; ?></td>    
                    </tr>
                <?php break; } 
                }?>                  
        <?php } ?>    
    <?php } ?>
        <tr><td colspan="6" style="height:10px;"></td></tr>
     <?php if($iguales<$row_rs_cabecera['Ord_Res']){  $residuo=$row_rs_cabecera['Ord_Res']-$iguales;?>
        <tr style="border-bottom: solid thin;">
            <td align="left"><strong>Paradas:</strong></td>
            <td colspan="5">1</td>            
        </tr>
        <?php foreach ($row_rs_detalle AS $row){ ?>            
                <?php foreach ($row_rs_formula AS $row2){ 
                    if($row['Pro_Cod']==$row2['Pro_Cod']){ ?>
                    <tr>
                        <td colspan="4" style="white-space: nowrap; overflow: hidden;"><?php echo $row['Ite_Lar']; ?></td>
                        <td align="right"><?php echo $row2['Mes_Can']*$residuo; ?></td>   
                        <td>&nbsp;&nbsp;&nbsp;<?php echo $row['Uni_Des']; ?></td>    
                    </tr>
                <?php break; } 
                }?>                  
        <?php } ?> 
    <?php } ?>    
    </table>    
<?php } ?>    
</body>
</html>
<?Php
