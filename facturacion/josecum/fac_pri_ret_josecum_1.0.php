<?php
/**
* @abstract Reporte de retención para la impresión 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-10-01
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 
	 
if (isset($Ret_Cod))
{   
   $row_rs_renta=$obBD_con1->getRowConsulta(167,$Ret_Cod, $obBD_conexion);
   	  
   if ($row_rs_renta['Aut_Cod'] != "") 
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(166,$Ret_Cod, $obBD_conexion);	   
   }
   else
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(553,$Ret_Cod, $obBD_conexion);  
   } 
   $row_prin_renta = current($rs_prin_renta);
}	
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {				
	font-family: Verdana;
	font-size: 11px;	
}
.Letra_punto_venta_3 {				
	font-family: Verdana;
	font-size: 9px;	
}
</style></head>
<body>
<table width="746"   border="0" align="left">
        <td width="740" height="328" colspan="4" valign="top"><table width="743" height="295" border="0" align="left" cellpadding="0" cellspacing="0">
        <tr>
          <td height="110" >&nbsp;</td>
          <td width="146" height="110" >&nbsp;</td>
          <td height="110" valign="bottom" >&nbsp;</td>
        </tr>
        <tr>
          <td width="298" height="26" >&nbsp;</td>
          <td height="26" >&nbsp;</td>
          <td height="26" valign="top" ><table width="100%" height="26" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="28%">&nbsp;</td>
              <td width="72%" align="right" valign="middle"><?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); ?>
                <span><?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            </tr>
          </table></td>
        </tr>
        <tr >
          <td height="19" colspan="2" align="left" valign="middle" class="Letra_punto_venta_2">&nbsp;</td>
          <td align="right" valign="bottom" class="Letra_punto_venta_2">&nbsp;</td>
        </tr>
        <tr >
          <td height="23" colspan="2" align="left" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="32%">&nbsp;</td>
              <td width="68%"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></td>
            </tr>
          </table></td>
          <td align="right" valign="bottom" class="Letra_punto_venta_2">&nbsp;</td>
        </tr>
        <tr >
          <td height="23" colspan="2" align="left" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="15%" align="left">&nbsp;</td>
              <td width="85%" height="13" align="left"><?php echo $row_prin_renta['Prs_Ced']; ?></td>
            </tr>
          </table></td>
          <td align="right" valign="middle" class="Letra_punto_venta_2"><?php 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo "001-001-000".$row_prin_renta['Cop_Num']; 
		} 
		else 
		{  
			echo $row_prin_renta['Cop_Num'];
		}?>&nbsp;&nbsp;</td>
        </tr>
        <tr >
          <td height="25" colspan="2" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="15%">&nbsp;</td>
              <td width="85%"><?php echo $row_prin_renta['Prs_Dir']; ?></td>
            </tr>
          </table></td>
          <td align="right" valign="middle" class="Letra_punto_venta_2"><?php echo $row_prin_renta['Cop_Fec']; ?>&nbsp;&nbsp;</td>
        </tr>
        <tr >
          <td height="32" valign="bottom" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <tr>
                <td width="54%" height="13">&nbsp;</td>
                <td width="46%"><?php echo $row_prin_renta['Tic_Des']; ?></td>
              </tr>
          </table></td>
          <td valign="top" class="Letra_punto_venta_2">&nbsp;</td>
          <td valign="top" class="Letra_punto_venta_2">&nbsp;</td>
        </tr>
        <tr>
          <td height="37" align="left" valign="bottom" class="Letra_punto_venta_2" >&nbsp;&nbsp;&nbsp;<?php 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo $row_prin_renta['Aut_Sri']; 
		} 
		else 
		{
			echo $row_prin_renta['Cop_Aut']; 
		}
		?></td>
          <td align="left" valign="bottom" class="Letra_punto_venta_2" ><?php 
			if ($row_rs_renta['Aut_Cod'] != "") 
			{ 
				echo $row_prin_renta['Aut_Fci']; 
			} 
			else 
			{
				echo $row_prin_renta['Cop_Imf']; 
			}?></td>
          <td width="299" colspan="-2" align="center" valign="bottom" class="Letra_punto_venta_2">
          <?php 
			if ($row_rs_renta['Aut_Cod'] != "")  
			{  
				echo $row_prin_renta['Aut_Cad']; 
			} 
			else 
			{ 
				echo $row_prin_renta['Cop_Cad']; 
			}?></td>
        </tr>
      </table>
  <tr valign="top">
    <td  valign="top"><table width="724" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
	<tr>
	  <td height="60" colspan="2" align="left" valign="top" class="Letra_punto_venta_2">
	  <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	 $Cop_Des = $row_prin_renta['Cop_Des'];		
	 $observacion=$row_prin_renta['Cop_Obs'];	
     $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); ?>

    <table width="721" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2" >
	 <tr align="center">
            <td colspan="6"></td>
          <tr align="center">
            <td height="15" colspan="6">&nbsp;</td>
            </tr> 
		  <?Php $Total_Ret=0; 
		  foreach($rs_prin_renta as $row)
		  { ?>
          <tr align="center" >
            <td width="81" align="center"  ><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
            <td width="112"   ><?Php $Sri_Cod=$row['Ren_Sri']; echo $obBD_con1->codAir($Sri_Cod); //"&nbsp"  ?></td>
            <td width="164"    ><?Php $Sri_Imp=$row['Ret_Imp']; echo $Sri_Imp;  ?></td>
            <td width="151"  ><?Php $Ren_Bas = number_format($row['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
            <td width="106" align="center">	<?Php $Ren_Por= $row['Ren_Por'].'%'; echo $Ren_Por; ?></td>
            <td width="107" align="right" >
             
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?Php $Val_Ret=($row['Ret_Bas']*$row['Ren_Por'])/100; 
			       $Val_Ret=number_format($Val_Ret,2,'.',''); 
				   echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?>
             </td></tr>
<?Php }//Fin del foreach ?>
		</table>		
        </td>
	  </tr>
	
	<tr>
	  <td height="2" colspan="2" class="Letra_punto_venta_2"></td>
	  </tr>
	<tr>
	  <td width="300" height="110" class="Letra_punto_venta_2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td>
	  <td width="416" align="right" valign="bottom" class="Letra_punto_venta_2"><strong><?Php echo number_format ($Total_Ret, 2,'.',''); ?></strong></td>
	</tr>
	</table>	  
</table>
  </tr>
</table>
</body>
</html>
<?Php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>