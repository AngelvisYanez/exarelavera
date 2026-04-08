<?php 
/**
* @abstract Reporte de ventas para la impresión en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-23
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_guia_remi.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;

if (isset($Gui_Cod))
{
	/* Consulta datos del detallr guia remision */
	$rs_guia_det = $obBD_con1->getArrayConsulta(1273, $Gui_Cod, $obBD_conexion);
	
	/* datos del destinatario*/
	$rs_destino = $obBD_con1->getRowConsulta(1274, $Gui_Cod, $obBD_conexion);
	
	/* datos del transporte*/
	$rs_transporte = $obBD_con1->getRowConsulta(1275, $Gui_Cod, $obBD_conexion);		
	
	/* Consulta datos de la guia remision */
	$rs_guia = $obBD_con1->getArrayConsulta(1276, $Gui_Cod, $obBD_conexion);
			
	/**
	* Consulta de la cabecera del reporte 
	*/
	$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);					
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {				
	font-family: Verdana;
	font-size: 10px;	
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = split('[-]', $row_rs_cliente['Caj_Fec']);?>
<table width="461" height="100%" border="0" align="left">
<tr>
          <td width="455" height="214" colspan="4" align="left" valign="top"><table width="97%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;" class="Letra_punto_venta_2">
            <tr>
              <td height="6" align="right" valign="bottom">&nbsp;</td>
              <td valign="bottom">&nbsp;</td>
              <td align="left" valign="bottom">&nbsp;</td>
            </tr>
            <tr>
              <td height="6" align="right" valign="bottom">&nbsp;</td>
              <td valign="bottom">&nbsp;</td>
              <td align="left" valign="bottom">&nbsp;</td>
            </tr>
            <tr>
              <td height="12" align="right" valign="bottom">&nbsp;</td>
              <td valign="bottom">&nbsp;</td>
              <td align="left" valign="bottom">&nbsp;</td>
            </tr>
            <tr>
              <td height="12" align="right" valign="bottom">&nbsp;</td>
              <td valign="bottom">&nbsp;</td>
              <td align="left" valign="bottom">&nbsp;</td>
            </tr>
            <tr>
              <td width="24%" height="12" align="right" valign="bottom">Destinatario</td>
              <td valign="bottom">
			  <? echo $rs_destino['Prs_Ape'].' '.$rs_destino['Prs_Nom']; ?></td>
              <td width="32%" align="left" valign="bottom"><span style="white-space: nowrap; overflow: hidden;"><? /*pto arribo */echo $rs_guia['Gui_Dar']?></span></td>
            </tr>
            <tr>
              <td height="12">&nbsp;</td>
              <td valign="middle" style="white-space: nowrap; overflow: hidden;"><? echo $rs_destino['Prs_Ced']?></td>
            <td width="32%" align="left" valign="bottom"><span style="white-space: nowrap; overflow: hidden;">
              <? /*pto partida */ echo $rs_guia['Gui_Dsa']?>
            </span></td>
            </tr>
            <tr>
              <td height="12">&nbsp;</td>
              <td width="44%" valign="middle">&nbsp;</td>
              <td width="32%" align="center" valign="middle">&nbsp;</td>
            </tr>
            <tr>
              <td height="3" align="right">Transporte</td>
              <td><? echo $rs_transporte['Prs_Ape'].' '.$rs_transporte['Prs_Nom']; ?></td>
              <td>&nbsp;<? echo $rs_guia['Gui_Pla']?></td>
            </tr>
            <tr>
              <td height="2">&nbsp;</td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_transporte['Prs_Ced']?></span></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="5">&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="4" align="right">Fecha salida</td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_guia['Gui_Fsa']?></span></td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_guia['Gui_Far']?></span></td>
            </tr>
            <tr>
              <td height="5" align="right">Datos Factura</td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_guia['Gui_Fve']?></span></td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_guia['Gui_Ave']?></span></td>
            </tr>
            <tr>
              <td height="9">&nbsp;</td>
              <td><span style="white-space: nowrap; overflow: hidden;"><? echo $rs_guia['Prs_Nve']?></span></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="9">&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="12">&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="26">&nbsp;</td>
              <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="3" align="left" valign="top">
                <table width="97%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
                  <? foreach($rs_guia_det as $dato){?>
                  <tr>
                    <td width="116" align="left"><div align="left"><?Php echo $dato['Gui_Can']?></div></td>
                    <td width="329"><div align="left">&nbsp;<?Php echo $dato['Ite_Lar'].' '.$dato['Pro_Obs']?></div></td>
                  </tr>
                  <?Php 				 											
				   }				
				?>
                </table>
               </td>
            </tr>
          </table>
  <tr valign="top">
    <td valign="top">
  </table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>