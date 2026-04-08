<FIELDSET>
<LEGEND>
<label class="Titulos2">Vencimiento</label>
</LEGEND>

<table width="100%" border="0">
<tr>
<td width="15%" class="Etiqueta1"><span class="Asterisco">* </span>Tipo: </td>
<td width="85%" colspan="3"><select name="Cmb_Ven" id="Cmb_Ven" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?codigo=<?Php echo $codigo;?>&op=<?php echo $op;?>&Pec_Cod=<?php echo $Pec_Cod;?>&proveedor=<?php echo $proveedor;?>&periodo=<?php echo $periodo;?>&Fec_Fin=<?php echo $Fec_Fin;?>&ajax_credito=1&Cmb_Ven='+  this.value, 'credito');">
    <option  selected="selected" value="1">Todos</option>
    <option value="2">Por vencer a 30 d&iacute;as &gt;&gt;</option>
    <option value="3">Por vencer a 60 d&iacute;as &gt;&gt;</option>
    <option value="4">Por vencer a 90 d&iacute;as &gt;&gt;</option>
    <option value="5">&lt;&lt; Vencidas a 30 dias</option>
    <option value="6">&lt;&lt; Vencidas a 60 dias</option>
    <option value="7">&lt;&lt; Vencidas a 90 dias</option>
  </select>
  	</td>
</tr>
</table>
</FIELDSET>