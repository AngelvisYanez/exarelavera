<?php
/**
* Descripción: Muestra el detalle de la venta
* Fecha de actualización: 2012-11-29
*/
/**
* Consulta el detalle de las facturas 
*/
$rs_detalle = $obBD_con1->getArrayConsulta(37, $com_codigo, $obBD_conexion);
?>
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
  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="fixedHeader02">
  <thead>
  <tr>
	<th width="17%" align="center">Cant</th>
	<th width="43%" align="center">Descripci&oacute;n</th>
	<th width="19%" align="center">P. Unitario </th>
	<th width="21%" align="center">Importe</th>
  </tr>
  </thead>
  <tbody>
  <?Php  
  foreach($rs_detalle as $row_rs_detalle)
  {
  ?>
  <tr>
	<td align="center"><?Php echo $row_rs_detalle['Vet_Can']; ?></td>
	<td><?Php echo $row_rs_detalle['Ite_Lar']; ?></td>
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