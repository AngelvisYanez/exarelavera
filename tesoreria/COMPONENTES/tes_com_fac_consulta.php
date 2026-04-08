<?Php
/*Componente que pemite listar las facturas que tiene pendiente un proveedor en especifico-*/

if (isset($codigo))
{		
		/*consulta de las facturas del proveedor*/
		$rs_factura = $obBD_con1->consulta(sentencias_tes(840, $obBD_con1->parametros($codigo.'*'.$txt_fec_fin)), $obBD_conexion->conexion);	//.'*'.$Pec_Cod
		$row_rs_factura = $obBD_con1->registros();
		$total_rs_factura = $obBD_con1->numregistros();			
?>
<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Facturas a cr&eacute;dito </label>
			</LEGEND>
				<table width="100%" border="1" cellpadding="0" cellspacing="0">
				  <tr class="Cabecera1">
					   <td width="4%" align="center">C&oacute;d. Int.</td>
					   <td width="15%" align="center">Proveedor</td>
					   <td width="15%" align="center">No. Factura </td>
					   <td width="15%" align="center">Fecha de emisi&oacute;n</td>
					   <td width="15%" align="center">Fecha de vencimiento </td>
					   <td width="15%" align="center" >Dias de vencimiento </td>
					   <td width="8%" align="center">Valor</td>
					   <td width="8%" align="center">Saldo</td>
				  </tr>
				  <?Php 
				  $i=0;
				if($total_rs_factura != 0)
				{	  
				  $cnt=0;
				  $suma_saldo=0;
				  $suma_final=0;
				  do { 
				 $i++;
				  /*cONSULTA PARA OBTENER LOS DIAS QUE FALTAN PARA VENCER UNA FACTURA **/
					$rs_dias = $obBD_con1->consulta(sentencias_tes(808, $obBD_con1->parametros($row_rs_factura['Cpp_Ven'].'*'.$hoy)), $obBD_conexion->conexion);	
					$row_rs_dias = $obBD_con1->registros();
					$total_rs_dias = $obBD_con1->numregistros();	
				  ?>
				   <?php
					/*consulta de pagos echos a un factura de a credito*/
					$rs_pago = $obBD_con1->consulta(sentencias_tes(843, $obBD_con1->parametros($row_rs_factura['Cop_Cod'].'*'.$txt_fec_fin)), $obBD_conexion->conexion);	
					$row_rs_pago = $obBD_con1->registros();
					$total_rs_pago = $obBD_con1->numregistros();	
					/*Operación para obtner el saldo de la factura a credito*/
					$saldo= $row_rs_factura['Asi_Val'] - $row_rs_pago['total'];	
					$saldo_final=formato_numero($saldo,2,3);
					if(round($saldo,2) >0)
					{
					 $cnt=$cnt+1;
					 $suma_final= $saldo+$suma_final;
					 ?>
				  <tr class="Fondo" >
				 
					<td width="4%" align="center"><?Php echo $row_rs_factura['Cop_Cod'];?></td>
					<td width="15%" align="center"><?Php echo $row_rs_factura['Prs_Ape'].' '.$row_rs_factura['Prs_Nom'];?>&nbsp;</td>
					<td width="15%" align="center"><?Php echo $row_rs_factura['Cop_Num'];?></td>
					<td align="center"><?Php echo $row_rs_factura['Cop_Fec'];?>&nbsp;</td>
					<td align="center"><?Php echo $row_rs_factura['Cpp_Ven'];?>&nbsp;</td>
					<td align="center" <?php if($row_rs_dias['dias']== 0) { echo "bgcolor='#00CCFF'";}  if($row_rs_dias['dias']== 1) { echo "bgcolor='#00FF99'";}  if($row_rs_dias['dias']== -1) { echo "bgcolor='#CCCCCC'";}?>?>
					<?php 
					if(($row_rs_dias['dias'] < -1)){echo "<font color='#FF0000'>".$row_rs_dias['dias']." dias </font>" ;}
					if($row_rs_dias['dias']== 1) { echo "Mañana";} 
					if($row_rs_dias['dias']== 0) { echo "Hoy";}
					if($row_rs_dias['dias']== -1) { echo "Ayer";}
					if(($row_rs_dias['dias'] > 1)){echo $row_rs_dias['dias']." dias ";}
										
					?>					</td>
					<td align="right"><?Php echo formato_numero($row_rs_factura['Asi_Val'], 2, 3); $suma_saldo= $row_rs_factura['Asi_Val']+ $suma_saldo;?></td>
					<td align="right"><?Php  echo $saldo_final;?>
					<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">	
					<input name="op" id="op" type="hidden" value="1">
					<input name="Fec_Fin" id="Fec_Fin" type="hidden" value="<?Php echo $Fec_Fin;?>"> 
					<input name="Fec_Ini" id="Fec_Ini" type="hidden" value="<?Php echo $hoy;?>"> 
					<input name="cadena" type="hidden"  id="cadena"  value="<?php  echo $cadena;?>">	
					<input name="Cop_Cod" type="hidden"  id="Cop_Cod"  value="<?php  echo $row_rs_factura['Cop_Cod'];?>">
					<input name="codigo" type="hidden"  id="codigo"  value="<?php  echo $codigo;?>">	
					<input name="txt_fec_fin" id="txt_fec_fin" type="hidden" value="<?Php echo $txt_fec_fin;?>">
					 </td>
					<?php }//FIn del if($saldo!=0)
				  ?>
				  </tr>
				  <?Php } while ($row_rs_factura = $obBD_con1->fetch_assoc($rs_factura)); 
				}//FIn del if($total_rs_buscar != 0)
				else
				{ ?>
					<tr><td colspan="8">
					<?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($proveedor)." ".$periodo, 2); ?>
							
					</td></tr>	   
				<?Php
				}//Fin del else if($total_rs_buscar != 0) ?>	
				<?php
			  if($cnt >0)
			  {
			  ?>
			  <tr class="Fondo">
			  <td colspan="6" class="LetraNegra" align="right"><STRONG>TOTAL:</STRONG></td>
			  <td align="right" class=""><strong><?Php echo formato_numero($suma_saldo, 2, 3);?></strong></td>
			  <td align="right" class=""><strong><?Php echo formato_numero($suma_final, 2, 3);?></strong></td>
			  </tr>
			  <?php }// fin dle if ($cont>0)	
	  ?>   
			  </table>
			  
</FIELDSET>
<!--fin de la tabla de facturas a credito-->
<?php 
@$obBD_con1->free_result($rs_factura);
 @$obBD_con1->free_result($rs_cuenta_manual);
 @$obBD_con1->free_result($rs_pago);
 @$obBD_con1->free_result($rs_diaS);
?>

<?php 

			
}//fin if (isset($codigo))

else
	{
		echo error_alerta("<< Error de componente: tes_com_fac_consulta.php >>  <br>Descripción: No se ha definido la Propiedad: 
		Pec_Cod<br>	Pec_Cod: Variable que contiene el código del periodo contable", 2); 							
	}
?>