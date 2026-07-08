<?php
/**
* Descripción: Muestra el detalle de la venta
* Fecha de actualización: 2012-11-29
*/
/**
* Consulta el detalle de las facturas 
*/
$rs_detalle = $obBD_con1->getArrayConsulta(37, $com_codigo, $obBD_conexion);
$rs_usuVendedor = $obBD_con1->getRowConsulta(1252, $com_codigo, $obBD_conexion);
?>
<FIELDSET>
<legend>
<label class="Titulos2">Detalle de la Venta:</label></legend>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
  <tr>
    <td colspan="4">
      <?Php 	 
	  /**
	  * Consulta las formas de pago de la factura
	  */
	 $rs_pago_fac = $obBD_con1->getArrayConsulta(316, $com_codigo, $obBD_conexion);
	 
	 $Pag_Cod = $rs_pago_fac[0]['Pag_Cod'];
	 $Bak_Cod = $rs_pago_fac[0]['Bak_Cod'];
	 $Ban_Cod = $rs_pago_fac[0]['Ban_Cod'];
	 $Vet_Cue = $rs_pago_fac[0]['Vet_Cue']; 
	 $Vet_Che = $rs_pago_fac[0]['Vet_Che'];
	 $Vet_Tot = $rs_pago_fac[0]['Vet_Tot'];
	 $Pag_Des=  $rs_pago_fac[0]['Pag_Des'];
	 $For_Des=  $rs_pago_fac[0]['For_Des'];
	 	 
	  /**
	  * Contro para saber si hay mas de un tipo de pago 
	  */
	 if (count($rs_pago_fac) > 1)
	  {
		 $Pag_Cod2 = $rs_pago_fac[1]['Pag_Cod'];
		 $Bak_Cod2 = $rs_pago_fac[1]['Bak_Cod'];
		 $Ban_Cod2 = $rs_pago_fac[1]['Ban_Cod'];
		 $Vet_Cue2 = $rs_pago_fac[1]['Vet_Cue']; 
		 $Vet_Che2 = $rs_pago_fac[1]['Vet_Che'];
		 $Vet_Tot2 = $rs_pago_fac[1]['Vet_Tot'];
		 $Pag_Des2=  $rs_pago_fac[1]['Pag_Des'];		
	  }//Fin del if (count($rs_pago_fac) > 1) */	  
	?>
<table width="100%" border="0">
  <tr>
    <td class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
    <td class="LetraNegra"><?Php echo $rs_detalle[0]['Prs_Ced']; ?></td>
    <td class="Etiqueta1">No. Doc:</td>
    <td class="LetraNegra"><?Php echo $rs_detalle[0]['Vet_Num']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Cliente:</td>
    <td class="LetraNegra"><?Php echo $rs_detalle[0]['Prs_Ape'].' '.$rs_detalle[0]['Prs_Nom']; ?></td>
    <td class="Etiqueta1">Fecha:</td>
    <td class="LetraNegra"><?Php echo $rs_detalle[0]['Caj_Fec']; ?></td>
  </tr>
  <tr>
    <td width="8%" class="Etiqueta1">Forma pago:</td>
    <td width="46%" class="LetraNegra"><?php echo $For_Des;?></td>
	<td width="20%" class="Etiqueta1">Usuario:</td>
	<td width="26%" class="LetraNegra">&nbsp;<?php echo $rs_usuVendedor['Prs_Ape'].' '.$rs_usuVendedor['Prs_Nom']; ?></td>
  </tr>
</table><hr>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%" align="left" valign="top">
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="10%" class="Etiqueta1">Tipo pago 1:</td>
          <td width="21%" align="left" class="LetraNegra"> 
               &nbsp;<?Php echo $Pag_Des; ?>          
          </td>
          <td width="17%" class="Etiqueta1">Banco:</td>
          <td width="21%" class="LetraNegra">&nbsp;
		  <?Php
  		  /**
		  * Bancos correspondientes al plan de cuentas 
		  */
		  $row_rs_bancos = $obBD_con1->getRowConsulta(187, $Ban_Cod, $obBD_conexion);

		 	if(count($row_rs_bancos) > 0)
			{
				 echo $row_rs_bancos['Pld_Des'];
    	    }//Fin del if($total_rs_bancos > 0)
			else
	        {
				/**
				* Cargar el banco de otros bancos 
				*/ 
				$row_rs_banco = $obBD_con1->getRowConsulta(188, $Bak_Cod, $obBD_conexion);
				echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if($total_rs_bancos > 0)
	      ?>		  
          </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1" align="">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="LetraNegra">&nbsp;&nbsp;<?php echo $Vet_Che; ?></td>
          <td width="7%" class="Etiqueta1">Valor:</td>
          <td width="24%" class="LetraNegra">
           &nbsp;<?php echo formato_numero($Vet_Tot,2,2); ?>          
           </td>
        </tr>
		</table>	  		
        </td>
      </tr>
	  <?Php  
	  if (count($rs_pago_fac) > 1)
	  { ?>
    <tr>
      <td align="left" valign="top" id="cheque"><hr>
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="10%" class="Etiqueta1">Tipo pago 2:</td>
          <td width="21%" class="LetraNegra">&nbsp;<?PHP echo $Pag_Des2; ?></td>
          <td width="17%" class="Etiqueta1">Banco:</td>
          <td width="21%" class="LetraNegra">
		  &nbsp;
		  <?Php
  		  /**
		  * Bancos correspondientes al plan de cuentas 
		  */
		  $row_rs_bancos = $obBD_con1->getRowConsulta(187, $Ban_Cod2, $obBD_conexion);

		 	if(count($row_rs_bancos) > 0)
			{
				 echo $row_rs_bancos['Pld_Des'];
    	    }//Fin del if($total_rs_bancos > 0)
			else
	        {
				/**
				* Cargar el banco de otros bancos 
				*/ 
				$row_rs_banco = $obBD_con1->getRowConsulta(188, $Bak_Cod2, $obBD_conexion);
				echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if(count($row_rs_bancos) > 0)
	      ?>		   </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue2; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="LetraNegra">&nbsp;&nbsp;<?php echo $Vet_Che2; ?></td>
          <td width="7%" class="Etiqueta1">Valor:</td>
          <td width="24%" class="LetraNegra">
            &nbsp;<?php echo formato_numero($Vet_Tot2,2,2); ?></td>
        </tr>
		</table><hr>			
        </td>
    </tr>
	<?Php 
	}//Fin del if ($total_rs_pago_fac > 1)
	?>
  </table>	</td>
  </tr>
  </table>
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
  <thead>
  <tr height="25" class="Cabecera1">
	<th height="25" width="17%" align="center">Cant</th>
	<th width="43%" align="center">Descripci&oacute;n</th>
	<th width="19%" align="center">P. Unitario </th>
	<th width="21%" align="center">Importe</th>
  </tr>
  </thead>
  <tbody class="Fondo">
  <?Php  
  foreach($rs_detalle as $row_rs_detalle)
  {
  ?>
  <tr>
	<td height="18" align="center"><?Php echo $row_rs_detalle['Vet_Can']; ?></td>
	<td><?Php echo $row_rs_detalle['Ite_Lar'].' '.$row_rs_detalle['Pro_Obs']; ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Vet_Pru'],2,2); ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Vet_Imp'],2,2); ?></td>
  </tr>
  <?Php
   $Obs = $row_rs_detalle['Vet_Obs'];
  }//Fin del foreach		  
  ?>
  <tr>
	<td colspan="4" align="left">Observación: <?Php echo $Obs; ?></td>
	</tr>
   </tbody> 		  
</table>
<br>
<?php
	 
$rs_Info = $obBD_con1->getRowConsulta(1251,$com_codigo,$obBD_conexion);
$total_Info=$rs_Info['Vet_Cod'] > 0? 1 : 0;
if($total_Info!=0)	  
{	
	$rs_detalleVet = $obBD_con1->getArrayConsulta(1249,$com_codigo,$obBD_conexion);	
	$total_rs_detalleVet =count($rs_detalleVet);	
	$impTotal=0;
?>     
     <strong>Retenci&oacute;n</strong>
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>        
        <td width="8%" class="Etiqueta1">No. Doc:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo $rs_detalleVet[0]['Ret_Num']; ?></td>
        <td width="8%" class="Etiqueta1">Emitido:</td>
        <td width="16%" class="LetraNegra">&nbsp;<?Php echo $rs_detalleVet[0]['Ret_Fec']; ?></td>
        <td width="9%" class="Etiqueta1">Autorizaci&oacute;n:</td>
        <td width="67%" class="LetraNegra">&nbsp;<?Php echo $rs_detalleVet[0]['Ret_Aut']; ?></td>
      </tr>
      </table>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
       <thead> 
          <tr height="25" class="Cabecera1">	
            <th width="9%" align="center">C&oacute;d. Imp. </th>
            <th width="41%" align="center">Descripci&oacute;n</th>
            <th width="11%" align="center">Impuesto</th>
            <th width="13%" align="center">Base</th>
            <th width="10%" align="center">%  </th>
            <th width="16%" align="center">Valor Retenido </th>
            </tr>
        </thead>
      	  <tbody class="Fondo">
         <?php foreach($rs_detalle as $row_rs_detalle){
			 if($row_rs_detalle['Ren_Cod']!='')
			 {
				 $rs_impuesto = $obBD_con1->getRowConsulta(1250, $row_rs_detalle['Ren_Cod'],$obBD_conexion);	
				 if(strlen($rs_impuesto['Ren_Con'])>44)
				 {
					$det=$rs_impuesto['Ren_Con']; 
					$detalle=substr($rs_impuesto['Ren_Con'],0,44)."..."; 
				 }else{
					$det=$rs_impuesto['Ren_Con'];   
					$detalle=$rs_impuesto['Ren_Con']; 
				 }
		 ?>
              <tr height="20">
                <td align="center"><?Php echo $rs_impuesto['Ren_Sri']; ?></td>
                <td title="<?php echo $det;?>"><?Php echo $detalle; ?></td>
                <td align="center"><?php echo $rs_impuesto['Impuesto'];?></td>
                <td align="center"><?Php echo formato_numero($row_rs_detalle['Vet_Imp'],2,4); ?></td>
                <td align="center"><?Php echo $rs_impuesto['Ren_Por']; ?></td>
                <td align="right">
				<?Php 
					$importe=$row_rs_detalle['Vet_Imp']-$row_rs_detalle['Vet_Dsc'];
					$importe=($importe*$rs_impuesto['Ren_Por'])/100;
					$impTotal=$impTotal+$importe;
					echo formato_numero($importe,2,4); 
				?>
                </td>
              </tr>
          <?php } 
		  	 if($row_rs_detalle['Ren_Iva']!='')
			 {
				 $rs_impuesto = $obBD_con1->getRowConsulta(1250, $row_rs_detalle['Ren_Iva'],$obBD_conexion);	
		  ?>
             <tr height="20">
                <td align="center"><?Php echo $rs_impuesto['Ren_Sri']; ?></td>
                <td><?Php echo $rs_impuesto['Ren_Con']; ?></td>
                <td align="center"><?php echo $rs_impuesto['Impuesto'];?></td>
                <td align="center">
				<?Php 
				    $imp=$row_rs_detalle['Vet_Imp']-$row_rs_detalle['Vet_Dsc'];
					$imp=($imp*$row_rs_detalle['Iva_Por'])/100;
					echo formato_numero($imp,2,4); 
				?>
                </td>
                <td align="center"><?Php echo $rs_impuesto['Ren_Por']; ?></td>
                <td align="right">
				<?Php 					
					$importe=($imp*$rs_impuesto['Ren_Por'])/100;
					$impTotal=$impTotal+$importe; 
					echo formato_numero($importe,2,4); 
				?>
                </td>
             </tr>
          
		  <?php } 
		 }
		  ?>
          </tbody>
  </table>  
  
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="25%">&nbsp;</td>
    <td width="25%">&nbsp;</td>
    <td width="34%">&nbsp;</td>
    <td width="16%" align="right"><strong>Total:&nbsp;&nbsp;<?php echo formato_numero($impTotal,2,4);;?></strong></td>
  </tr>
</table>

<?php }?> 
</FIELDSET>