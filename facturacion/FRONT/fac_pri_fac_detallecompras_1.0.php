<?php 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	 
		
$hoy = date("Y-m-d");
$mes = date("m"); 
?>
<HTML>
<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY  >

<p>
  <?Php
/**
* Componente que muestra el detalle de las compras 
*/
if (isset($com_codigo))
{  
	$rs_infoEmpresa = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion); 
	$resultados = explode('*',$obBD_con1->calculosCompraIce($com_codigo, $obBD_conexion));
	$rs_usuVendedor = $obBD_con1->getRowConsulta(1073, $com_codigo, $obBD_conexion); 
	$rs_info = $obBD_con1->getRowConsulta(1071, $com_codigo, $obBD_conexion);
	$rs_tipoCompr = $obBD_con1->getRowConsulta(1076, $rs_info['Tic_Cod'], $obBD_conexion);
	$rs_detalle = $obBD_con1->getArrayConsulta(723, $com_codigo, $obBD_conexion);
	$row = current($rs_detalle);
	$tot_rs_detalle=count($rs_detalle);
	$Obs = $row['Cop_Obs'];
?>
</p>
<table width="95%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="50%" valign="bottom" class="TEXTO_NORMAL_UPPER_18" style="font-size:14px"><div align="left"><strong><?php echo $rs_infoEmpresa['Emp_Nom'];?></strong></div></td>
    <td width="50%">&nbsp;</td>
  </tr>
  <tr>
    <td width="50%" valign="bottom" class="TITULO_REPORTE"><div align="left" > detalle de compras</div></td>
    <td align="right" class="Texto_Listados" style="font-size:11px"><div align="right" ><?php echo $hoy;?></div></td>
  </tr>
  <tr>
    <td height="19" colspan="2"><hr /></td>
  </tr>
</table>

<table width="95%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td class="Texto_Listados" style="font-size:11px"><strong>Proveedor</strong>:</td>
        <td colspan="5" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Prs_Ape'].' '.$rs_info['Prs_Nom'];?></span></td>
  </tr>
      <tr>
       <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><strong>C&eacute;dula/R.U.C.:&nbsp;</strong></span></td>
        <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Prs_Ced'];?>&nbsp;</span></td>
        <td class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Tipo:&nbsp;</strong></div></td>
        <td class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><?php echo $rs_tipoCompr['Tic_Des'];?></span></td>
        <td class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Creado el:&nbsp;</strong></div></td>
        <td class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Cop_Sys']; ?>&nbsp;</td>
      </tr>
      <tr>
        <td width="10%" class="Texto_Listados" style="font-size:11px"><strong>No. Docto:</strong></td>
        <td width="18%" class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Cop_Num'];?>&nbsp;</td>
        <td width="6%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Autorizaci&oacute;n:&nbsp;</strong></div></td>
        <td width="17%" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Cop_Aut'];?></span></td>
        <td width="14%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Emisi&oacute;n:&nbsp;</strong></div></td>
        <td width="35%" class="Texto_Listados" style="font-size:11px"><span class="Texto_Listados" style="font-size:11px"><?php echo $rs_info['Cop_Fec'];?></span>&nbsp;</td>
      </tr>  
      <tr>
        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        <td width="14%" class="Texto_Listados" style="font-size:11px"><div align="right"><strong>Usuario:</strong></div></td>
        <td width="35%" class="Texto_Listados" style="font-size:11px">&nbsp;<?php echo $rs_usuVendedor['Prs_Ape'].' '.$rs_usuVendedor['Prs_Nom']; ?></td>
      </tr>
</table>

       
<table width="95%" border="0" cellpadding="0" cellspacing="0">
      <thead>      
      <tr height="25" class="TablaRepCompr">
        <th colspan="4" align="left" class="TablaRepComprBottom" >&nbsp;</th>
        </tr>
      <tr height="25" class="TablaRepCompr">
        <th width="11%" align="left" valign="middle" class="TablaRepComprBottom">Cantidad</th>
        <th width="49%" align="left" valign="middle" class="TablaRepComprBottom">Descripci&oacute;n</th>
        <th width="19%" align="right" valign="middle" class="TablaRepComprBottom">P. Unitario </th>
        <th width="21%" align="right" valign="middle" class="TablaRepComprBottom">Importe</th>
      </tr>      
  </thead>
      <tbody class="Fondo">
      <?Php
      foreach ($rs_detalle as $row_rs_detalle)
      {
		  if(strlen($row_rs_detalle['Cop_Pro'])>30)
		  {
				$CopPro=substr($row_rs_detalle['Cop_Pro'],0,30);
				$CopPro=$CopPro.'...';   				
		  }else{
				$CopPro=$row_rs_detalle['Cop_Pro'];				
		  }
      ?>
      
      <tr class="Texto_Listados" height="20" style="font-size:12px">
        <td height="20" align="left" ><?Php echo $row_rs_detalle['Cop_Can']; ?></td>
        <td title="<?Php echo $row_rs_detalle['Cop_Pro']; ?>"><?Php echo $CopPro; ?></td>
        <td height="20" align="right" ><?Php echo formato_numero($row_rs_detalle['Cop_Pru'], 2, 4); ?></td>
        <td height="20" align="right" ><?Php echo formato_numero($row_rs_detalle['Cop_Imp'], 2, 4); ?></td>
      </tr>
      <?Php } ?>
      <tr>
        <td height="64" align="left" >&nbsp;</td>
        <td height="64" align="left">&nbsp;</td>
        <td height="64" align="left" >&nbsp;</td>
        <td height="64" align="left" >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4" align="left" class="TablaRepComprBottom">&nbsp;</td>
      </tr>
      
      <tr>
		<td height="60" colspan="2" align="left" valign="top" class="Texto_Listados" style="font-size:12px"><strong>Observaci&oacute;n</strong>:<?Php echo "&nbsp;".$Obs; ?></td>
		<td colspan="2" align="left" class="Texto_Listados">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
		   <tr>
            <td width="64%" align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Subtotal:</div></td>
            <td width="36%" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?Php echo formato_numero($resultados[0],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Tarifa 0%:</div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?Php echo formato_numero($resultados[1],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Tarifa 12%:</div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?Php echo formato_numero($resultados[2],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Iva:</div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?Php echo formato_numero($resultados[3],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Ice:</div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?php echo formato_numero($resultados[6],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right">Descuento:<strong> <?Php echo isset($Des_Gen)?$Des_Gen:0; ?></strong></div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?Php echo formato_numero($resultados[4],2,1); ?></strong></div></td>
          </tr>
          <tr>
            <td align="left" class="Texto_Listados" style="font-size:12px"><div align="right"><strong>Total</strong>:</div></td>
            <td align="right" class="Texto_Listados" style="font-size:12px"><div align="right"><strong><?php echo formato_numero($resultados[5],2,1); ?></strong></div></td>
          </tr>
	    </table></td>
		</tr>
      </tbody>            
</table>
     <br>
     <?php
	 
	    $rs_retCod = $obBD_con1->getRowConsulta(718,$com_codigo,$obBD_conexion);
            $rs_confiEmp = $obBD_con1->getRowConsulta(1049,$Ses_Suc_Cod,$obBD_conexion);	  
     	$rs_detalle = $obBD_con1->getArrayConsulta(381,$rs_retCod['Ret_Cod'],$obBD_conexion);	
		$total_rs_detalle =count($rs_detalle);	
		$impTotal=0;
	 if($total_rs_detalle!=0 && 1==2)
	 {	
	 ?>     
     <strong>Retenci&oacute;n</strong>
     <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>        
        <td width="8%" class="Etiqueta1">No. Doc:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo str_pad($rs_retCod['Ret_Num'], 9, "0", STR_PAD_LEFT); ?></td>
        <td width="8%" class="Etiqueta1">Emitido:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo $rs_retCod['Ret_Fec']; ?></td>
        <td width="9%" class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td width="67%" class="LetraNegra">&nbsp;
		<?Php 
			if($rs_confiEmp['Cof_Gce']=='N')
			{
				echo $rs_retCod['Aut_Sri']; 
			}else{
				echo $rs_retCod['Ret_Sri'];
			}
		?>
        </td>
      </tr>
</table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <thead> 
          <tr height="25" class="Cabecera1">
            <th width="11%" height="20" align="center">C&oacute;d. Imp. </th>
            <th width="41%" height="20" align="center">Descripci&oacute;n</th>
            <th width="10%" height="20" align="center">Impuesto</th>
            <th width="12%" height="20" align="center">Base</th>
            <th width="13%" align="center">% Retenci&oacute;n </th>
            <th width="13%" height="20" align="center">Valor Retenido </th>
            </tr>
          </thead>
      	  <tbody class="Fondo">
          <?php foreach($rs_detalle as $row_rs_detalle){?>
          <tr height="20">
            <td align="center"><?Php echo $row_rs_detalle['Ren_Sri']; ?></td>
            <td><?Php echo $row_rs_detalle['Ren_Con']; ?></td>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Ret']; ?></td>
            <td align="center"><?Php echo formato_numero($row_rs_detalle['Ret_Bas'],2,4); ?></td>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Por']; ?></td>
            <td align="right"><?Php $impTotal=$impTotal+$row_rs_detalle['Val_Ret']; echo formato_numero($row_rs_detalle['Val_Ret'],2,4); ?></td>
            </tr>
          <?php }?>
          </tbody>
</table>  
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="25%">&nbsp;</td>
            <td width="25%">&nbsp;</td>
            <td width="37%">&nbsp;</td>
            <td width="13%" align="right"><strong>Total:&nbsp;&nbsp;<?php echo formato_numero($impTotal,2,4);?></strong></td>
          </tr>
        </table>                                  
        <br />
     <?php }?>          

    <?php //echo barra_estado($tot_rs_detalle);?>    
    <?Php
    }//FIn del if (isset($com_codigo))
    else
    {
        echo error_alerta("<< Error de componente >> <br>Descripci&oacute;n: No se ha definido la Propiedad: com_codigo<br>
                                        com_codigo: Variable que contiene el codigo interno de la factura de compra", 2); 							
    
    }//FIn del else if (isset($com_codigo))
    ?>  
</BODY></HTML>
<?Php
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

