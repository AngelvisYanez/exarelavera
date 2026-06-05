<?php 
/**
* @abstract Reporte de ventas para la impresiÃ³n en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaciÃ³n  2012-05-23
* @author Lewis Chimarro
*/
require_once('../../Librerias/config.php/register_globals.php'); 
require_once($APP_REAL_PATH.'/administrador/LOGICA/logica.php');
require_once('../LOGICA/tes_log_cccc.php');  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Cccc;	 	 	 

if (isset($Com_Cod))
{	
	/*
	* Consulta datos de los clientes
	*/
	$row_rs_datos = $obBD_con1->getArrayConsulta(44,$Com_Cod,$obBD_conexion);
	$row_info_Empresa= $obBD_con1->getRowConsulta(45,$Ses_Suc_Cod,$obBD_conexion);
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
body {
	margin-left: 0px;
}
</style>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 8px}
-->
.tituloFact{font: 9pt verdana;}
.titulotabla{font: 9pt verdana;}
.tituloFact2{font: 9pt verdana;}
.etiquetaFact{font: 9pt verdana;}
.subtitulo{font: 9pt verdana;}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente[0]['Caj_Fec']);?>
<table width="100%" border="0" align="left" cellpadding="0" cellspacing="0">
  <tr>
    <td  align="left" valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
        <td colspan="6" align="center" class="LetraPlan">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>            
            <td width="13%" align="left"><img src="<? echo $Ses_Emp_Log;?>" width="90" height="80"></td>            
              <td width="87%"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="center" class="tituloFact"><strong><? echo $Ses_Emp_Nom;?></strong></td>
              </tr>
              <tr>
                <td align="center" class="tituloFact2"><? echo "Tel&eacute;fono 1:".$row_info_Empresa['Suc_Te1']."&nbsp;&nbsp;&nbsp;"."Tel&eacute;fono 2:".$row_info_Empresa['Suc_Te2']?></td>
              </tr>
              <tr>
                <td align="center" class="tituloFact2"><? echo $row_info_Empresa['Suc_Dir'];?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="6" align="left"><hr></td>
      </tr>
      <tr>
        <td height="24" colspan="6" align="center" class="subtitulo"><strong>COMPROBANTE DE CANCELACI&Oacute;N #:&nbsp;&nbsp;<? echo $row_rs_datos[0]['numCom']?></strong></td>
      </tr>
      <tr>
        <td width="10%" align="left" class="etiquetaFact">R.U.C / C.I:</td>
        <td width="25%" class="etiquetaFact">&nbsp;<? echo $row_rs_datos[0]['Prs_Ced']?></td>
        <td width="10%" align="left" class="etiquetaFact">EMISI&Oacute;N:</td>
        <td width="16%" align="left" class="etiquetaFact">&nbsp;<? echo $row_rs_datos[0]['Cpc_Fec'];?></td>
        <td width="14%" align="left" class="etiquetaFact">TIPO DE PAGO:</td>
        <td width="25%" align="left" class="etiquetaFact">&nbsp;<? echo strtoupper($row_rs_datos[0]['Pag_Des']);?></td>
      </tr>
      <tr>
        <td align="left" class="etiquetaFact">CLIENTE:</td>
        <td colspan="5" class="etiquetaFact">&nbsp;<? echo $row_rs_datos[0]['Prs_Ape'].' '.$row_rs_datos[0]['Prs_Nom'];?></td>
        </tr>
      <tr>
        <td align="left" class="etiquetaFact">CONCEPTO:</td>
        <td colspan="5" align="left"><span class="etiquetaFact"><? echo $row_rs_datos[0]['Com_Con'];?></span></td>
      </tr>
      <tr>
        <td align="left" class="etiquetaFact">OBSERVACI&oacute;N:</td>
        <td colspan="5" align="left"><span class="etiquetaFact">&nbsp;<? echo $row_rs_datos[0]['Cpc_Obs'];?></span></td>
        </tr>
      <tr>
        <td height="94" colspan="6" align="left" valign="top"><?Php 
			 $tarifa_0 = 0;
			 $tarifa_12 = 0;	
			?>
          <br>
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse" >
            <tr class="tituloFact2">
              <td width="216" height="19" align="left">COMP. VENTA</td>
              <td width="182" align="center">FECHA CHEQ.</td>
              <td width="197" align="center"># CHEQUE / # TRANSF</td>
              <td width="303" align="center">BANCO CHEQ.</td>
              <td width="158" align="center">CUENTA CHEQ.</td>
              <td width="118" align="right">VALOR&nbsp;
                <!--Total--></td>
            </tr>
            <tr>
              <td colspan="6"><hr style="border: 0; border-top: 1px solid #999; border-bottom: 1px solid #333; height:0;"></td>
            </tr>
            <? $total=0; 
			foreach ($row_rs_datos as $datos){ ?>
            <tr class="tituloFact2">
              <td align="left">&nbsp;<? echo str_pad($datos['Vet_Num'],8,'0',STR_PAD_LEFT);?></td>
              <td align="center"><? if($datos['Che_Fec']!=''){ echo $datos['Che_Fec'];}else{echo '-';}?></td>
              <!-- <td align="center"><? if($datos['Che_Num']!=''){echo $datos['Che_Num'];}else{echo '-';}?></td> -->
              <td align="center">
                <?php 
                  $cheque = $datos['Che_Num'] ? "CH. " . $datos['Che_Num'] : '';
                  $transferencia = $datos['Num_Doc'] ? "Tr. " . $datos['Num_Doc'] : '';
                  echo $cheque || $transferencia ? trim($cheque . ' / ' . $transferencia, ' / ') : '-';
                ?>
              </td>
              <td align="center"><? if($datos['Bak_Des']!=''){echo $datos['Bak_Des'];}else{echo '-';}?></td>
              <td align="center"><? if($datos['Che_Cta']!=''){echo $datos['Che_Cta'];}else{echo '-';}?></td>
              <td align="right"><? echo formato_numero($datos['Cpc_Val'],2,1); $total+=$datos['Cpc_Val'];?></td>
            </tr>
            <? }?>
            <tr>
              <td height="38">&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td align="right" class="tituloFact2">&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td colspan="3" class="tituloFact2"><span class="etiquetaFact">USUARIO:</span><span class="tituloFact">&nbsp;&nbsp; <? echo $row_rs_datos[0]['usuApe']//.' '.$row_rs_datos[0]['usuNom'];;?></span></td>
              <td>&nbsp;</td>
              <td align="right" class="tituloFact2"><strong>TOTAL:</strong></td>
              <td align="right" class="etiquetaFact"><strong><? echo formato_numero($total,2,1);?></strong>&nbsp;</td>
            </tr>
            <tr>
              <td colspan="6"><hr style="border: 0; border-top: 1px solid #999; border-bottom: 1px solid #333; height:0;"></td>
              </tr>
          </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
  	<td height="25"  align="left" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  	  <tr>
  	    <td height="39">&nbsp;</td>
  	    <td align="center" valign="bottom">__________________</td>
  	    <td valign="bottom">&nbsp;</td>
  	    <td align="center" valign="bottom">__________________</td>
  	    <td align="center" valign="bottom">&nbsp;</td>
  	    <td align="center" valign="bottom">__________________</td>
  	    <td>&nbsp;</td>
	    </tr>
  	  <tr class="tituloFact2">
  	    <td>&nbsp;</td>
  	    <td align="center">REALIZADO POR:</td>
  	    <td align="center">&nbsp;</td>
  	    <td align="center">REVISADO POR:</td>
  	    <td align="center">&nbsp;</td>
  	    <td align="center">APROBADO POR:</td>
  	    <td>&nbsp;</td>
	    </tr>
    </table></td>
  </tr>
  </table>
  </tr>
</table>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>