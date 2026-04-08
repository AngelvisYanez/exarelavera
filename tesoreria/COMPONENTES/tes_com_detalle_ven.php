<table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
  <tr>
    <td colspan="4">
      <?Php 	 
	  /*Consulta las formas de pago de la factura*/
	 $rs_pago_fac = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($com_codigo)), $obBD_conexion->conexion);
	 $row_rs_pago_fac = $obBD_con1->registros();
	 $total_rs_pago_fac = $obBD_con1->numregistros(); 
	 
	 $Pag_Cod = $row_rs_pago_fac['Pag_Cod'];
	 $Bak_Cod = $row_rs_pago_fac['Bak_Cod'];
	 $Ban_Cod = $row_rs_pago_fac['Ban_Cod'];
	 $Vet_Cue = $row_rs_pago_fac['Vet_Cue']; 
	 $Vet_Che = $row_rs_pago_fac['Vet_Che'];
	 $Vet_Tot = $row_rs_pago_fac['Vet_Tot'];
	 $Pag_Des=  $row_rs_pago_fac['Pag_Des'];
	 	 
	  /* Contro para saber si hay mas de un tipo de pago */
	 if ($total_rs_pago_fac > 1)
	  {
	   	 $row_rs_pago_fac = first_last($rs_pago_fac, $row_rs_pago_fac, 1);
		 $Pag_Cod2 = $row_rs_pago_fac['Pag_Cod'];
		 $Bak_Cod2 = $row_rs_pago_fac['Bak_Cod'];
		 $Ban_Cod2 = $row_rs_pago_fac['Ban_Cod'];
		 $Vet_Cue2 = $row_rs_pago_fac['Vet_Cue']; 
		 $Vet_Che2 = $row_rs_pago_fac['Vet_Che'];
		 $Vet_Tot2 = $row_rs_pago_fac['Vet_Tot'];
		 $Pag_Des2=  $row_rs_pago_fac['Pag_Des'];		
	  }//Fin del if ($total_rs_pago_fac > 1) */	  
	?>
<table width="100%" border="0" >
  <tr>
    <td width="8%" class="Etiqueta1">Forma pago:</td>
    <td width="46%" class="LetraNegra"><?php echo $row_rs_pago_fac['For_Des'];  ?></td>
	<td width="20%" class="Etiqueta1">&nbsp;</td>
	<td width="26%" class="LetraNegra">&nbsp;</td>
  </tr>
</table><hr>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="50%" align="left" valign="top">
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="8%" class="Etiqueta1">Tipo pago 1:</td>
          <td width="24%" align="left" class="LetraNegra"> 
               &nbsp;<?Php echo $Pag_Des; ?>          </td>
          <td width="14%" class="Etiqueta1">Banco:</td>
          <td width="19%" class="LetraNegra">&nbsp;
		  <?Php
  		  /* Bancos correspondientes al plan de cuentas */
		  $rs_bancos = $obBD_con1->consulta(sentencias_tes(187, $obBD_con1->parametros($Ban_Cod)), $obBD_conexion->conexion);
		  $row_rs_bancos = $obBD_con1->registros(); 
		  $total_rs_bancos= $obBD_con1->numregistros();

		 	if($total_rs_bancos > 0){
				 echo $row_rs_bancos['Pld_Des'];
    	     }//Fin del if($total_rs_bancos > 0)
			 else
	         {
				/*cargar el banco de otros bancos */ 
				$rs_banco = $obBD_con1->consulta(sentencias_tes(188, $obBD_con1->parametros($Bak_Cod)), $obBD_conexion->conexion);
				$row_rs_banco = $obBD_con1->registros(); 
					 echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if($total_rs_bancos > 0)
	      ?>		  </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
        <tr>
          <td class="Etiqueta1" align="">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="LetraNegra">&nbsp;&nbsp;<?php echo $Vet_Che; ?></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="27%" class="LetraNegra">
           &nbsp;<?php echo formato_numero($Vet_Tot,2,2); ?>          </td>
        </tr>
		</table>	  		</td>
      </tr>
	  <?Php  
	  if ($total_rs_pago_fac > 1)
	  { ?>
    <tr>
      <td align="left" valign="top" id="cheque"><hr>
	  <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="8%" class="Etiqueta1">Tipo pago 2:</td>
          <td width="24%" class="LetraNegra">&nbsp;<?PHP echo $Pag_Des2; ?></td>
          <td width="14%" class="Etiqueta1">Banco:</td>

          <td width="19%" class="LetraNegra">
		  &nbsp;
		  <?Php
  		  /* Bancos correspondientes al plan de cuentas */
		  $rs_bancos = $obBD_con1->consulta(sentencias_tes(187, $obBD_con1->parametros($Ban_Cod2)), $obBD_conexion->conexion);
		  $row_rs_bancos = $obBD_con1->registros(); 
		  $total_rs_bancos= $obBD_con1->numregistros();

		 	if($total_rs_bancos > 0){
				 echo $row_rs_bancos['Pld_Des'];
    	     }//Fin del if($total_rs_bancos > 0)
			 else
	         {
				/*cargar el banco de otros bancos */ 
				$rs_banco = $obBD_con1->consulta(sentencias_tes(188, $obBD_con1->parametros($Bak_Cod2)), $obBD_conexion->conexion);
				$row_rs_banco = $obBD_con1->registros(); 
					 echo $row_rs_banco['Bak_Des'];
	         }//Fin del else if($total_rs_bancos > 0)
	      ?>		   </td>
          <td colspan="2" class="LetraNegra">&nbsp;</td>
          </tr>
		  
        <tr>
          <td class="Etiqueta1">Cuenta No:</td>
          <td class="LetraNegra">&nbsp;<?php echo $Vet_Cue2; ?></td>
          <td class="Etiqueta1">Cheque/Papeleta No: </td>
          <td class="LetraNegra">&nbsp;&nbsp;<?php echo $Vet_Che2; ?></td>
          <td width="8%" class="Etiqueta1">Valor:</td>
          <td width="27%" class="LetraNegra">
            &nbsp;<?php echo formato_numero($Vet_Tot2,2,2); ?></td>
        </tr>
		</table><hr>			</td>
    </tr>
	<?Php 
	}//Fin del if ($total_rs_pago_fac > 1)
	?>
  </table>	</td>
  </tr>
  
  <tr>
	<td width="17%" align="center" class="Etiqueta1">Cant</td>
	<td width="43%" align="center" class="Etiqueta1">Descripci&oacute;n</td>
	<td width="19%" align="center" class="Etiqueta1">P. Unitario </td>
	<td width="21%" align="center" class="Etiqueta1">Importe</td>
  </tr>
  <?Php
	/* Consulta el detalle de las facturas */
	$rs_detalle = $obBD_con1->consulta(sentencias_tes(223, $obBD_con1->parametros($com_codigo)), $obBD_conexion->conexion);
	$row_rs_detalle = $obBD_con1->registros();
	$total_rs_detalle = $obBD_con1->numregistros();			
  
  do{
  ?>
  <tr>
	<td align="center"><?Php echo $row_rs_detalle['Vet_Can']; ?></td>
	<td><?Php echo $row_rs_detalle['Ite_Lar']; ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Vet_Pru'],2,2); ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Vet_Imp'],2,2); ?></td>
  </tr>
  <?Php
   $Obs = $row_rs_detalle['Vet_Obs'];
  }while($row_rs_detalle = $obBD_con1->registros());		  
  ?>
  <tr>
	<td colspan="4" align="left" class="Etiqueta1"><div align="left">Observación: <?Php echo $Obs; ?></div></td>
	</tr>		  
</table>
