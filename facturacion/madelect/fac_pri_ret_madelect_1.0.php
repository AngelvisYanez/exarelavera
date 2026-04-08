<?php
/**
* @abstract Reporte de retención para la impresión 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-10-01
* @author Lewis Chimarro
*/
//require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret;
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
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo3 {font-size: 9}
-->
</style>
</head>
<body>
<table width="726"   border="0" align="left">
    <td width="570" height="188" colspan="4" valign="top">
      <table width="821" height="255" border="0" align="left">
      <tr>
        <td colspan="7" ></td>
        <td ></td>
      </tr>
	    <tr>
        <td colspan="7" ></td>
        <td ></td>
      </tr>
      <tr>
        <td height="35" colspan="7" >&nbsp;</td>
        <td ><p>&nbsp;</p>
          <p>&nbsp;</p></td>
      </tr>
      <tr>
        <td colspan="7" >&nbsp;</td>
        <td>
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="80%" align="right" valign="top" class="Texto_Reporte"><?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); ?>
          <span class="Texto_Reporte"><? echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;'.$Fec_Emi[0]; ?></span>&nbsp;</td>
              <td width="20%">&nbsp;</td>
            </tr>
          </table>      	
          </td>
      </tr>
      <tr>
        <td height="23" colspan="7" ><p>&nbsp;</p>
          <p>&nbsp;</p></td>
        <td width="134" >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="8" ></td>
        </tr>
      <tr align="center">
        <td height="2" colspan="8" class=""></td>
        </tr>
	  <tr>
	    <td  colspan="6" class="Texto_Reporte"></td>
	    <td colspan="2" rowspan="4" class="Texto_Reporte"><? echo $row_prin_renta['Prs_Ced']; ?>
	      <table width="97%" height="53" border="0" cellpadding="0" cellspacing="2"> 
          <tr>
            <td height="18" align="right" valign="top" class="Texto_Reporte"><div align="left"></div></td>
          </tr>
          <tr>
            <td align="right" valign="top" ><div align="left" class="Texto_Reporte"><? echo $row_prin_renta['Ciu_Des']; ?></div></td>
          </tr>	  
        </table></td></tr>
	  <tr >
	    <td height="26" colspan="4" valign="middle" class="Texto_Reporte">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></td>	    
	  </tr>
	  <tr >
	    <td height="21" colspan="4" valign="bottom" class="Texto_Reporte">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $row_prin_renta['Prs_Dir']; ?></td>
	    </tr>
	  <tr>
	    <td width="27" height="31" align="rigth" class="Texto_Reporte" >&nbsp;</td>
	    <td width="197" align="rigth" class="Texto_Reporte" >&nbsp;</td>
        <td width="145" align="rigth" class="Texto_Reporte" >&nbsp;</td>
        <td width="253" align="rigth" class="Texto_Reporte" >&nbsp;</td>
      </tr>
	  <tr>
	    <td height="21" colspan="2" align="rigth" valign="bottom" class="Texto_Reporte" ><span class="LetraEval">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $row_prin_renta['Tic_Des']; ?></span></td>
	    <td align="rigth" valign="bottom" class="Texto_Reporte" >
		<? 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo "001-001-000".$row_prin_renta['Cop_Num']; 
		} 
		else 
		{  
			echo $row_prin_renta['Cop_Num'];
		}?></td>
	    <td valign="bottom" class="Texto_Reporte" align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $row_prin_renta['Cop_Fec']; ?></td>	    
	    </tr>
	  <tr >
	    <td class="Texto_Reporte" align="rigth" height="30" >&nbsp;</td>
	    <td align="rigth" valign="bottom" class="Texto_Reporte" >
		<? 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo $row_prin_renta['Aut_Sri']; 
		} 
		else 
		{
			echo $row_prin_renta['Cop_Aut']; 
		}
		?></td>
	    <td valign="bottom" class="Texto_Reporte" align="right">
			<? 
			if ($row_rs_renta['Aut_Cod'] != "") 
			{ 
				echo $row_prin_renta['Aut_Fci']; 
			} 
			else 
			{
				echo $row_prin_renta['Cop_Imf']; 
			}?></td>
	    <td valign="bottom" class="Texto_Reporte" align="right">
			<? 
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
    <td  valign="top"><table width="649" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
	<tr>
	  <td height="104" colspan="5" valign="top" class="Texto_Reporte">
	  <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	 $Cop_Des = $row_prin_renta['Cop_Des'];		
	 $observacion=$row_prin_renta['Cop_Obs'];	
     $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); ?>

    <table width="642" border="0" class="Texto_Reporte" >
	 <tr align="center">
            <td colspan="6"></td>
          <tr align="center">
            <td colspan="6" height="23">&nbsp;</td>
            </tr> 
		  <?Php $Total_Ret=0; 
		  foreach($rs_prin_renta as $row)
		  { ?>
          <tr align="center" >
            <td width="57"  ><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
            <td width="93"   ><?Php $Sri_Cod=$row['Ren_Sri']; echo $obBD_con1->codAir($Sri_Cod); //"&nbsp"  ?></td>
            <td width="195"    ><?Php $Sri_Imp=$row['Ret_Imp']; echo $Sri_Imp;  ?></td>
            <td width="107"  ><?Php $Ren_Bas = number_format($row['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
            <td width="82" align="right">	<?Php $Ren_Por= $row['Ren_Por'].'%'; echo $Ren_Por; ?></td>
            <td width="82" align="center" >
             
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
	  <td height="2" colspan="5" class="Texto_Reporte"></td>
	  </tr>
	<tr>
	  <td width="178" height="23" class="Texto_Reporte">&nbsp;</td>
	  <td width="359" height="23" align="right">&nbsp;</td>
  	  <td width="88" height="23" class="Texto_Reporte" align="center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?Php echo number_format ($Total_Ret, 2,'.',''); ?>  	    </td>	  
	  <td width="1" class="Texto_Reporte" align="right">&nbsp;</td>
	  <td width="3" class="Texto_Reporte" align="right">&nbsp;</td>
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