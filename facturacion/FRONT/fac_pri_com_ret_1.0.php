<?php
/**
* Descripci�n: Permite consultar las retenciones
* Fecha de actualizaci�n:	2013-05-17
* Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_retencion.php');  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		 
/**
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);

/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;


	  if (isset($Ret_Cod) )
	  {		   
		   $row_rs_renta = $obBD_con1->getRowConsulta(167,$Ret_Cod,$obBD_conexion);			  
		   $num_rs_renta=$rs_renta['Aut_Cod'] > 0? 1 : 0;		   		  
		   		  
		   if ($row_rs_renta['Aut_Cod'] != "") {
			   $rs_print_renta=$obBD_con1->getArrayConsulta(166,$Ret_Cod,$obBD_conexion);			   			   			   
		   }
		   else{		   	   
			   $rs_print_renta=$obBD_con1->getArrayConsulta(553,$Ret_Cod,$obBD_conexion);			  
		   } 
		    //$row_prin_renta=mysqli_fetch_assoc($rs_print_renta);
			$num_rows_compra=count($rs_print_renta);		   		   
	  }		
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<link href="../../css_teso.css" rel="stylesheet" type="text/css">
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo3 {font-size: 12}
-->
</style>
</head>

<body class="Cuerpo">

<table width="726"   border="0" align="left" cellpadding="0" cellspacing="0">

        <td width="570" height="188" colspan="4" valign="top"><table width="821" height="228" border="0" align="left" cellpadding="0" cellspacing="0">
        <tr>
          <td width="622" height="62" >&nbsp;</td>
          <td width="134" >&nbsp;</td>
        </tr>
        <tr>
          <td height="72" >&nbsp;</td>
          <td><p>&nbsp;</p>
          <p>&nbsp;</p></td>
        </tr>
        <tr>
          <td height="23" colspan="2" ><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="18%" height="25">&nbsp;</td>
              <td width="24%"><span class="LetraNegra"><?php echo $rs_print_renta[0]['Prs_Ced']; ?></span></td>
              <td width="38%">&nbsp;
                <?Php $Fec_Emi=explode('-',$rs_print_renta[0]['Ret_Fec']); echo $Fec_Emi[2].'-'.mes($Fec_Emi[1],2).'-'.$Fec_Emi[0]; ?></td>
              <td width="19%"><?Php $Eje_Fis=explode('-',$rs_print_renta[0]['Ret_Fec']); $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td colspan="2"><span class="LetraNegra"><?php echo $rs_print_renta[0]['Prs_Ape'].' '.$rs_print_renta[0]['Prs_Nom']; ?></span></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td height="25">&nbsp;</td>
              <td colspan="2"><span class="LetraNegra"><?php 
				  	if (strlen($rs_print_renta[0]['Prs_Dir'])>40)
					{
						echo substr($rs_print_renta[0]['Prs_Dir'],0,40).'...'; 
					}else{
						echo $rs_print_renta[0]['Prs_Dir'];	
					}
				  ?></span></td>
              <td><?php echo $rs_print_renta[0]['Prs_Tel']?></td>
            </tr>
            <tr>
              <td height="22">&nbsp;</td>
              <td colspan="2"><span class="LetraEval"><?php echo $rs_print_renta[0]['Tic_Des']; ?></span></td>
              <td><span class="LetraNegra">
                <?php if ($rs_print_renta[0]['Aut_Cod'] != "") { echo "001-001-000".$rs_print_renta[0]['Cop_Num']; } else {  echo $rs_print_renta[0]['Cop_Num'];}?>
              </span></td>
            </tr>
          </table></td>
        </tr>
        <tr align="center">
          <td height="2" colspan="2" class=""></td>
        </tr>
      </table>
        <tr valign="top">
          <td height="186"  valign="top"><table width="100%" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
            <tr>
              <td height="23" colspan="3" valign="top" class="LetraNegra">&nbsp;</td>
            </tr>
            <tr>
              <td height="135" colspan="3" valign="top" class="LetraNegra"><?Php 
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$Cop_Des = $rs_print_renta[0]['Cop_Des'];
		$observacion=$rs_print_renta[0]['Cop_Obs'];		    
		?>
                <table width="100%" border="0" class="LetraNegra" >
                  <?Php $Total_Ret=0; 
		  ?>
                  <tr align="center" >
                    <td width="309" height="0">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td align="right">&nbsp;</td>
                    <td align="center">&nbsp;</td>
                  </tr>
                  <?php foreach($rs_print_renta as $row_prin_renta){ ?>
                  <tr align="center" >
                    <td height="25" align="left" valign="bottom" class="Estilo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                      <?Php $Sri_Des=$row_prin_renta['Ren_Con']; echo substr($Sri_Des,0,30); //"&nbsp"  ?></td>
                    <td width="79" valign="bottom">&nbsp;&nbsp;<?Php $Sri_Imp=$row_prin_renta['Ret_Imp']; echo $Sri_Imp;  ?></td>
                    <td width="116" align="left" valign="bottom">&nbsp;&nbsp;&nbsp;<?Php $RenSri=$row_prin_renta['Ren_Sri']; echo $RenSri; ?></td>
                    <td width="61" valign="bottom"><?Php $Ren_Bas = number_format($row_prin_renta['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
                    <td width="89" align="center" valign="bottom"><?Php $Ren_Por= $row_prin_renta['Ren_Por'].'%'; echo $Ren_Por; ?></td>
                    <td width="137" align="center" valign="bottom"><?Php $Val_Ret=($row_prin_renta['Ret_Bas']*$row_prin_renta['Ren_Por'])/100; $Val_Ret=number_format($Val_Ret,2,'.',''); echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?></td>
                  </tr>
                  <?Php }?>
              </table></td>
            </tr>
            <tr>
              <td width="15%" height="23" class="LetraNegra">&nbsp;</td>
              <td width="71%" height="23" align="left"><?php $decimal=explode(".",number_format($Total_Ret,2)); echo num2letras($decimal[0],false,true).', con '.$decimal[1].'/100 D&oacute;lares Americanos'; ?></td>
              <td width="14%" height="23" align="center" class="LetraNegra"><?Php echo number_format ($Total_Ret, 2,'.',''); ?>&nbsp;&nbsp;</td>
            </tr>
          </table>                  
        <tr valign="top">
          <td height="190"  valign="top">        
<tr valign="top">
          <td height="186"  valign="top"><table width="821" height="255" border="0" align="left" cellpadding="0" cellspacing="0">
            <tr>
              <td width="622" height="35" >&nbsp;</td>
              <td width="134" >&nbsp;</td>
            </tr>
            <tr>
              <td height="125" >&nbsp;</td>
              <td><p>&nbsp;</p>
              <p>&nbsp;</p></td>
            </tr>
            <tr>
              <td height="23" colspan="2" ><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="18%" height="25">&nbsp;</td>
                  <td width="21%"><span class="LetraNegra"><?php echo $rs_print_renta[0]['Prs_Ced']; ?></span></td>
                  <td width="38%">&nbsp;
                    <?Php $Fec_Emi=explode('-',$rs_print_renta[0]['Ret_Fec']); echo $Fec_Emi[2].'-'.mes($Fec_Emi[1],2).'-'.$Fec_Emi[0]; ?></td>
                  <td width="17%"><?Php $Eje_Fis=explode('-',$rs_print_renta[0]['Ret_Fec']); $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td colspan="2"><span class="LetraNegra"><?php echo $rs_print_renta[0]['Prs_Ape'].' '.$rs_print_renta[0]['Prs_Nom']; ?></span></td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td height="25">&nbsp;</td>
                  <td colspan="2"><span class="LetraNegra">
                    <?php 
				  	if (strlen($rs_print_renta[0]['Prs_Dir'])>40)
					{
						echo substr($rs_print_renta[0]['Prs_Dir'],0,40).'...'; 
					}else{
						echo $rs_print_renta[0]['Prs_Dir'];	
					}
				  ?>
                  </span></td>
                  <td><?php echo $rs_print_renta[0]['Prs_Tel']?></td>
                </tr>
                <tr>
                  <td height="22">&nbsp;</td>
                  <td colspan="2"><span class="LetraEval"><?php echo $rs_print_renta[0]['Tic_Des']; ?></span></td>
                  <td><span class="LetraNegra">
                    <?php if ($rs_print_renta[0]['Aut_Cod'] != "") { echo "001-001-000".$rs_print_renta[0]['Cop_Num']; } else {  echo $rs_print_renta[0]['Cop_Num'];}?>
                  </span></td>
                </tr>
              </table></td>
            </tr>
            <tr align="center">
              <td height="2" colspan="2" class=""></td>
            </tr>
          </table>
  <tr valign="top">
        <td height="186"  valign="top"><table width="100%" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
          <tr>
            <td height="23" colspan="3" valign="top" class="LetraNegra">&nbsp;</td>
          </tr>
          <tr>
            <td height="136" colspan="3" valign="top" class="LetraNegra"><?Php 
		$tarifa_0 = 0;
		$tarifa_12 = 0;
		$Cop_Des = $rs_print_renta[0]['Cop_Des'];
		$observacion=$rs_print_renta[0]['Cop_Obs'];		    
		?>
              <table width="100%" border="0" class="LetraNegra" >
                <?Php $Total_Ret=0; 
		  ?>
                <tr align="center" >
                  <td width="309" height="0">&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td align="right">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                </tr>
                <?php foreach($rs_print_renta as $row_prin_renta){ ?>
                <tr align="center" >
                  <td height="25" align="left" valign="bottom" class="Estilo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?Php $Sri_Des=$row_prin_renta['Ren_Con']; echo substr($Sri_Des,0,30); //"&nbsp"  ?></td>
                  <td width="81" valign="bottom">&nbsp;&nbsp;<?Php $Sri_Imp=$row_prin_renta['Ret_Imp']; echo $Sri_Imp;  ?></td>
                  <td width="116" align="left" valign="bottom">&nbsp;&nbsp;&nbsp;<?Php $RenSri=$row_prin_renta['Ren_Sri']; echo $RenSri; ?></td>
                  <td width="61" valign="bottom"><?Php $Ren_Bas = number_format($row_prin_renta['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
                  <td width="89" align="center" valign="bottom"><?Php $Ren_Por= $row_prin_renta['Ren_Por'].'%'; echo $Ren_Por; ?></td>
                  <td width="137" align="center" valign="bottom"><?Php $Val_Ret=($row_prin_renta['Ret_Bas']*$row_prin_renta['Ren_Por'])/100; $Val_Ret=number_format($Val_Ret,2,'.',''); echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?></td>
                </tr>
                <?Php }?>
            </table></td>
          </tr>
          <tr>
            <td width="17%" class="LetraNegra">&nbsp;</td>
            <td width="69%" align="left"><?php $decimal=explode(".",number_format($Total_Ret,2)); echo num2letras($decimal[0],false,true).', con '.$decimal[1].'/100 D&oacute;lares Americanos'; ?></td>
            <td width="14%" align="center" class="LetraNegra"><?Php echo number_format ($Total_Ret, 2,'.',''); ?>&nbsp;&nbsp;</td>
          </tr>
        </table>
  </table>
</tr>
</table>

</body>
</html>
<?Php	
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>