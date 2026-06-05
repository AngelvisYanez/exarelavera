<?php 
/**
* @abstract Reporte de liquidacion de compras
* @author Jose Cumbicos
* @version 1.0
* Fecha de actualización  2016-06-06
* @author Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt;	 	 	 

if (isset($Cop_Cod))
{  
	/*
	* Consulta datos de los clientes
	*/
	
	$row_rs_proveedor = $obBD_con1->getArrayConsulta(472,$Cop_Cod,$obBD_conexion);	
	$proveedor = $row_rs_proveedor[0]['Cop_Cod'];	
	$observacion = $row_rs_proveedor[0]['Cop_Obs'];	
			
	/**
	* Consulta de la cabecera del reporte 
	*/
	$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);					
}
?>	
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">			
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {				
	font-family: Verdana;
	font-size: 13px;	
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_proveedor[0]['Cop_Fec']);?>
<table width="672" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
          <td height="287" colspan="4" align="left" valign="top">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;" class="Letra_punto_venta_2">
          <tr>
            <td height="138" colspan="7" align="right" valign="bottom">&nbsp;</td>
            </tr>
          <tr>
          <td width="12%">RUC./C.I:</td>
          <td><? echo $row_rs_proveedor[0]['Prs_Ced']; ?></td>
          <td width="10%" align="right">FECHA:</td>
          <td width="16%">&nbsp;<?  echo date("d/m/Y", strtotime($row_rs_proveedor[0]['Cop_Fec']));?></td>
          <td width="13%" align="right">CIUDAD:</td>
          <td width="22%" colspan="2" align="left">&nbsp;<?Php echo $row_institucion['Ciu_Des']; ?></td>
          </tr>
        <tr>
          <td>CLIENTES:</td>
          <td colspan="6"><? echo $row_rs_proveedor[0]['Prs_Ape'].' '.$row_rs_proveedor[0]['Prs_Nom']; ?></td>
          </tr>
        <tr>
          <td>DIRECCI&Oacute;N:</td>
          <td colspan="6"><? echo $row_rs_proveedor['Prs_Dir'];?></td>
          </tr>
        <tr>
          <td>&nbsp;</td>
          <td width="27%">&nbsp;</td>
          <td colspan="2">&nbsp;</td>
          <td>&nbsp;</td>
          <td colspan="2">&nbsp;</td>
          </tr>
        <tr>
          <td colspan="7" align="left" valign="top">
            
            <?Php 
			 $subTotal = 0;
			 $sub0 = 0;
		  	 $sub12 = 0;
		     $totIva = 0;
		   	 $total = 0;
				
			
			?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">              
              <tr>
                <td align="center">CANT.</td>
                <td>PRODUCTO</td>
                <td align="right">P. UNIT.</td>
                <td align="right" >VALOR</td>
              </tr>
              <tr>
                <td height="2" colspan="4" align="center"><hr size=1></td>
              </tr>
              <? foreach($row_rs_proveedor as $row){ ?>
              <tr>
                <td width="54" height="26" align="center"><?Php echo $row['Cop_Can']?></td>
                <td width="242"><div align="left">&nbsp;<?Php echo $row['Cop_Pro'];?></div></td>
                <td width="84" align="right"><?Php echo number_format($row['Cop_Pru'], 2); ?>&nbsp;&nbsp;</td>
                <td width="83" align="right" ><div align="right"><?Php echo number_format($row['Cop_Imp'], 2); ?></div></td>
              </tr>
              <?Php 
				  /* 
				  * % de Descuento total 
				  */
				  $Cop_Des = $row['Cop_Des'];
				  $subTotal+=$row['Cop_Imp'];
				  if(($row['Cop_Des']+$row['Cop_Dec'])!='0')
				  {
					  $dscto+=(($row['Cop_Imp']*$Cop_Des)/100)+(($row['Cop_Imp']*$Cop_Dec)/100);
					  if($row_rs_proveedor['Iva_Por']==0)
					  {	  
					  	  $sub0+=$row['Cop_Imp'];						  
					  }
					  else{
						  $sub12+=$row['Cop_Imp'];
						  $totIva+=(($row['Cop_Imp']-$dscto)*$Iva_Por)/100;
					  }
				  }else{					  
					  if($row['Iva_Por']==0)
					  { 
					  	  $sub0+=$row['Cop_Imp'];
					  }else{ 
					  	  $sub12+=$row['Cop_Imp'];
						  $totIva+=($row['Cop_Imp']*$row['Iva_Por'])/100;
					  }					  					  
				  }
				  $total+=$sub0+$sub12+$totIva;
				}					
				?>			  
            </table>          
          <!-- Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL -->          </td>
          </tr>
        
      </table>
<tr>
            <td height="38" colspan="5" align="right"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <tr>
                <td width="74%" rowspan="6" align="left" valign="top"><table width="95%" height="21" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td><? echo "<strong>OBSERVACI&Oacute;N:</strong> ".$row_rs_proveedor[0]['Cop_Obs']?></td>
                  </tr>
                </table></td>
                <td height="0" align="right">Sub-Tot Iva:&nbsp;&nbsp;</td>
                <td height="0" align="right"><?Php echo formato_numero($sub12, 2, 1); ?></td>
              </tr>
              <tr>
                <td width="16%" height="0" align="right">Sub-Tot 0%:
                &nbsp;</td>
                <td width="10%" height="0" align="right"><?Php echo formato_numero($sub0, 2, 1); ?></td>
              </tr>
              <tr>
                <td height="0" align="right">Descuento:&nbsp;&nbsp;</td>
                <td height="0" align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?></td>
              </tr>
              <tr>
                <td height="0" align="right">Subtotal:
                &nbsp;</td>
                <td height="0" align="right"><?Php echo formato_numero($subTotal, 2, 1); ?></td>
              </tr>
              <tr>
                <td height="0" align="right">Iva:
                &nbsp;</td>
                <td height="0" align="right"><?Php echo formato_numero($totIva, 2, 1); ?></td>
              </tr>
              <tr>
                <td height="0" align="right">TOTAL:
                &nbsp;</td>
                <td height="0" align="right"><?php echo number_format($total, 2); ?></td>
              </tr>
              <tr>
                <td colspan="3"><!--Usuario:-->
                  <?Php 
						$nom = explode(' ',$Ses_Prs_Ape);
						$ape = explode(' ',$Ses_Prs_Nom);
						//echo $nom[0].' '.$ape[0]; 
				?></td>
              </tr>
            </table>
  <tr valign="top">
    <td width="672" valign="top">
  </table>
  </tr>
</table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>