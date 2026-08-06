<?Php
if (!is_object($obBD_con1)) return;
require_once('../../componentes/LOGICA/logica.php');
/* Consulta los periodos actuales en base a la Etapa-Modalidad y fechas de inicio y fin de matricula */
 $rs_costo = $obBD_con1->consulta(sentencias_com(201, $obBD_con1->parametros('')), $obBD_conexion->conexion);
 $row_rs_costo = $obBD_con1->registros();
 $total_rs_costo = $obBD_con1->numregistros();
?>
<table id="Tbl_Costos" width="600" >
<tr align="left">
<td>
<FIELDSET class="Busqueda_ajax">
<LEGEND>
	<label class="Titulos2">Seleccione de Costos y Gastos:</label>
</LEGEND>
<table  border="0" width="500">
  <tr>
    <td width="60" class="Etiqueta1"><span class="Asterisco" >* </span>Tipo:</td>
    <td width="430">	 
		<select name="Tip_Cst" id="Tip_Cst"  onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_cst_cod=1&Tip_Cst=' + this.value,'div_costos')"  >
	 <option></option>
	 <?Php do{ ?>
	  	 <option value="<?Php echo $row_rs_costo['Tdc_Cod']; ?>"><?Php echo $row_rs_costo['Tdc_Des']; ?>	     </option>
	 <?Php }while($row_rs_costo=$obBD_con1->fetch_assoc($rs_costo));?>	 
	 </select>    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Monto:</td>
    <td>
      <input name="txt_monto" type="text"id="txt_monto" size="10" onkeyup="numerico(this)">    </td>
  </tr>
</table>
<div id="div_costos">
</div>
<br/>
<table width="100" border="0">
  <tr>
    <td><input name="btn_distrib" type="button"    class="Boton_Calcular" title="Distribuir" id="btn_distrib" onclick="cargar_filas_distri('c_contenido',<?Php echo $iva_cod; ?>,<?Php echo $iva_por; ?>,<?Php echo $ice_cod; ?>, <?Php echo $ice_por; ?>, <?Php echo $ad_cod; ?>, <?Php echo $ad_por; ?>, '<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>','txt_por','txt_monto')" value="Buscar" />
	</td>
  </tr>
</table>

</FIELDSET>
</td>
</tr>
</table>
<?Php
$obBD_con1->free_result($rs_costo);
?>