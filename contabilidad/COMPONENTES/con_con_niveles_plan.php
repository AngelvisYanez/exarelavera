<?php //$niv = niveles_plan($Pla_Cod, $obBD_con1, $obBD_conexion); 
$niv=array(1,2,3,4,5,6,7);
?>
<FIELDSET>  
<LEGEND> <label class="Etiqueta1">Opciones de presentaci&oacute;n</label> </LEGEND>	
	<?php //print_r($niv) ?>
	<table width="100%" border="0">
		<tr>
			<td width="15%" class="Etiqueta1">Niveles:</td>
			<td width="85%">
				<select name="Max_Niv" id="Max_Niv" style="width: 50px; text-align: center;">
					<?php for ($j=count($niv); $j>=2; $j--) { ?>
						<option <?php if ($j == $Max_Niv){ echo "selected"; } ?> value="<?php echo $j; ?>"><?php echo $j; ?></option>							
					<?php } ?>
				</select>
				<input name="niveles" id="niveles" type="hidden" value="<?php echo $niv; ?>" />
			</td>
		</tr>
	</table>
</FIELDSET>
