<?Php
/**
* Componente que muestra el detalle de las compras 
*/

if (isset($cop_codigo))
{   
	$rs_usuVendedor = $obBD_con1->getRowConsulta(375, $cop_codigo, $obBD_conexion); 
	$rs_info = $obBD_con1->getRowConsulta(376, $cop_codigo, $obBD_conexion);
	$rs_detalle = $obBD_con1->getArrayConsulta(377, $cop_codigo, $obBD_conexion);
	$row = current($rs_detalle);
	$tot_rs_detalle=count($rs_detalle);
	$Obs = $row['Cop_Obs'];
?>
	<FIELDSET>
	<LEGEND>
    <label class="Titulos2">Detalle de la Compra</label>
    </LEGEND>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
        <td colspan="3" class="LetraNegra">&nbsp;<?php echo $rs_info['Prs_Ced'];?></td>
      </tr>
      <tr>
       <td class="Etiqueta1">Proveedor:</td>
        <td class="LetraNegra">&nbsp;<?php echo $rs_info['Prs_Ape'].' '.$rs_info['Prs_Nom'];?></td>
        <td class="Etiqueta1">Creado el:</td>
        <td class="LetraNegra">&nbsp;<?php echo $rs_usuVendedor['fecha']; ?></td>
      </tr>
      <tr>
        <td width="10%" class="Etiqueta1">No. Docto:</td>
        <td width="41%" class="LetraNegra">&nbsp;<?php echo $rs_info['Cop_Num'];?></td>
        <td width="8%" class="Etiqueta1">Usuario:</td>
        <td width="41%" class="LetraNegra">&nbsp;<?php echo $rs_usuVendedor['Prs_Ape'].' '.$rs_usuVendedor['Prs_Nom']; ?></td>
      </tr>        
    </table>

    <hr>   
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <thead>
      <tr height="25" class="Cabecera1">
        <th width="17%" align="center">Cant</th>
        <th width="43%" align="center">Descripci&oacute;n</th>
        <th width="19%" align="center">P. Unitario </th>
        <th width="21%" align="center">Importe</th>
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
      <tr height="20">
        <td align="center"><?Php echo $row_rs_detalle['Cop_Can']; ?></td>
        <td title="<?Php echo $row_rs_detalle['Cop_Pro']; ?>"><?Php echo $CopPro; ?></td>
        <td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Pru'], 2, 4); ?></td>
        <td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Imp'], 2, 4); ?></td>
      </tr>
      <?Php } ?>
      <tr>
		<td colspan="4" align="left">Observaci&oacute;n:<?Php echo "&nbsp;".$Obs; ?></td>
	  </tr>
      </tbody>            
     </table>
     <br>
     <?php
	 
	    $rs_retCod = $obBD_con1->getRowConsulta(378,$cop_codigo,$obBD_conexion);
            $rs_confiEmp = $obBD_con1->getRowConsulta(379,$Ses_Suc_Cod,$obBD_conexion);	  
     	$rs_detalle = $obBD_con1->getArrayConsulta(380,$rs_retCod['Ret_Cod'],$obBD_conexion);	
		$total_rs_detalle =count($rs_detalle);	
		$impTotal=0;
	  if($total_rs_detalle!=0)
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
</FIELDSET>
    <?php echo barra_estado($tot_rs_detalle);?>    
    <?Php
    }//FIn del if (isset($cop_codigo))
    else
    {
        echo error_alerta("<< Error de componente >> <br>Descripci&oacute;n: No se ha definido la Propiedad: cop_codigo<br>
                                        cop_codigo: Variable que contiene el codigo interno de la factura de compra", 2); 							
    
    }//FIn del else if (isset($cop_codigo))
    ?>
