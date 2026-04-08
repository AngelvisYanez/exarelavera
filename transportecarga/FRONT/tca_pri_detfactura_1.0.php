<?php
/**
 * @abstract Permite imprimir a través de PDF el detalle de los viajes efectuados
 * @author José Ambuludí
 * @version 1.0
 * Fecha de creación  2017-03-21
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tca_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_viajeFactura($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 = new Class_Log_Datos_viajeFactura;
/*Sección para obtener los datos de la empresa*/
$rs_cabecera = $obBD_con1->getRowConsulta(54,$Ses_Suc_Cod,$obBD_conexion);

$cab_fac=$obBD_con1->getRowConsulta(31,$Ses_Emp_Cod.'*'.$Vet_Cod, $obBD_conexion);
$det_fac=$obBD_con1->getArrayConsulta(32,$Vet_Cod,$obBD_conexion);

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    </HEAD>
    <BODY>
        <table width="100%" style="font-family: sans-serif; font-size: 14px;">
            <tr>
                <td>
                    <table width="100%">
                        <tr><td rowspan="5"><img src="<?php echo $rs_cabecera['Emp_Log']?>" height="80" width="80"></td></tr>
                        <tr style="text-align: center;"><td width="100%"><b><?php echo $rs_cabecera['Emp_Nom']?></b></td></tr>
                        <tr style="text-align: center;"><td><b>R.U.C.: </b><?php echo $rs_cabecera['Emp_Ruc']?></td></tr>
                        <tr style="text-align: center;"><td><b>DIRECCI&Oacute;N: </b><?php echo $rs_cabecera['Suc_Dir']?></td></tr>
                        <tr style="text-align: center;"><td><b><?php echo $rs_cabecera['provincia']?></b></td></tr>
                    </table>
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td>
                    <table width="100%">
                        <tr><td><b>C&eacute;dula/R.U.C.:</b></td><td><?php echo $cab_fac['Prs_Ced']?></td><td><b>Tipo Dcto.:</b></td><td><?php echo $cab_fac['Tic_Des']?></td></tr>
                        <tr><td><b>Cliente:</b></td><td><?php echo $cab_fac['cliente']?></td><td><b>Factura Nro.:</b></td><td><?php echo $cab_fac['Vet_Num']?></td></tr>
                        <tr><td><b>Ciudad:</b></td><td><?php echo $cab_fac['Ciu_De1']?></td><td><b>Fecha:</b></td><td><?php echo $cab_fac['Caj_Fec']?></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td width="100%">
                    <table width="100%" border="1" style="border-collapse: collapse;">
                        <tr style="text-align: center;"><td><b>Item</b></td><td><b>Fecha</b></td><td><b>Descripci&oacute;n</b></td><td><b>Cantidad</b></td><td><b>Precio U.</b></td><td><b>Total</b></td></tr>
                        <?php $cont=1;$tcan=0;$tpu=0;$ttot=0; 
                        foreach ($det_fac as $row){?>
                        <tr style="text-align: center;"><td><?php echo $cont;?></td><td><?php echo $row['Via_Fec'];?></td><td><?php echo $row['Car_Des'];?></td><td align="right"><?php echo $row['Vet_Can'];?></td><td align="right"><?php echo $row['Vet_Pru'];?></td><td align="right"><?php echo $row['Vet_Imp'];?></td></tr>
                        <?php   $cont++;
                                $tcan=$tcan+$row['Vet_Can'];
                                $tpu=$tpu+$row['Vet_Pru'];
                                $ttot=$ttot+$row['Vet_Imp'];
                        }?>
                        <tr align="right"><td colspan="3"><b>TOTAL:</b></td><td><?php echo $tcan;?></td><td><?php echo number_format($tpu, 2, ".","");?></td><td><?php echo number_format($ttot, 2, ".","");?></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </BODY>
</HTML>