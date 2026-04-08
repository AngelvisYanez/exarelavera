<?Php 
/* Componente para mostrar el mes y el año y poder elegir un rango de fechas */
?>
    <!--Librerias para calendario -->       
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
    <script>
    $(function() { 
       /* Campo 1 */					       
		$( "#txt_fec_ini" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});
        /* Campo 2 */			       
		$( "#txt_fec_fin" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});					
    }); 		
    </script>        
<FIELDSET>  
<LEGEND>
<label class="Etiqueta1"> Fecha</label>
</LEGEND>
<table width="100%" border="0">
<tr>
  <td width="6%" class="Etiqueta1"><div align="left">
	    <span id="capa_rango_fec">
		Desde:
		<input name="txt_fec_ini" type="text" id="txt_fec_ini" value="<?php if (isset($txt_fec_ini)){ echo $txt_fec_ini; } else { echo date("Y-m-d"); } ?>" size="10"  onkeyup="mascara(this,'-',patron, true);" onBlur="validar_fecha2(this)" />		
		&nbsp;Hasta:
        <input name="txt_fec_fin" type="text" id="txt_fec_fin" value="<?php  if (isset($txt_fec_fin)){ echo $txt_fec_fin; }else{ echo date("Y-m-d"); } ?>" size="10" onkeyup="mascara(this,'-',patron, true);" onBlur="validar_fecha2(this)" />       	  
        </span>     
</div></td>
  </tr>
	  </table>
</FIELDSET>