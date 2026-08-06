<?Php
if (!function_exists('mes')) return; 
/* Componente para mostrar el mes y el a�o y poder elegir un rango de fechas */
?>
<script type="text/javascript">
/* Asigna el dia inicial y final del mes y a�o seleccionado */ 
function set_dia_mes()
{
	if (document.getElementById('Chk_Fec').checked)
	 {
		var fecha=new Date(); 
		var mes=fecha.getMonth()+1;
		var dia=fecha.getDate();
		/* Control para agregar un cero al mes */
		if (mes <= 9)
		{
			mes = "0"+mes;
		}
		/* Control para agregar un cero al dia */
		if (dia <= 9)
		{
			dia = "0"+dia;
		}
		
		var fecha_str = fecha.getFullYear()+"-"+mes+"-"+dia;
		document.getElementById('txt_fec_ini').value=fecha_str; 
		document.getElementById('txt_fec_fin').value=fecha_str;	
	 }
	else
	{
	
		document.getElementById('txt_fec_ini').value=1; 
		document.getElementById('txt_fec_fin').value=cuantosDiasNum(document.getElementById('cmb_mes').value, document.getElementById('ann').value);		
	}
}//Fin del set_dia_mes()
</script>
<FIELDSET>  
<LEGEND>
<label class="Etiqueta1">Seleccionar Fecha</label>
</LEGEND>
<table width="100%" border="0">
<tr>
  <td width="6%" class="Etiqueta1"><div align="left"><span id="capa_fecha">A&ntilde;o:
    <select name="ann" id="ann" onchange="set_dia_mes()">      
      <?Php
		  for ($i=date("Y");$i>=2007;$i--)
		  { 
		  ?>
      <option <?php if ($i == $ann){ echo "selected"; } ?> value="<?Php echo $i; ?>"><?php echo $i; ?></option>
      <?Php
		  } ?>
    </select>  
		 &nbsp;&nbsp;Mes: 
		      <select name="cmb_mes" id="cmb_mes" onchange="set_dia_mes()">
		        <option value=""><< TODOS >></option>
		        <?Php
				  for ($i=1;$i<=12;$i++)
				  { 
				  ?>
		        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1) ?></option>
		        <?Php
				  } ?>
        </select>
		</span>
  <span id="capa_fecha">&nbsp;Mes: 
		      <select name="cmb_mes" id="cmb_mes" onchange="set_dia_mes()">
		        <option value=""><< TODOS >></option>
		        <?Php
				  for ($i=1;$i<=12;$i++)
				  { 
				  ?>
		        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1) ?></option>
		        <?Php
				  } ?>
        </select>
		</span>
	    <span id="capa_rango_fec">
		Desde:
		<input name="txt_fec_ini" type="text" id="txt_fec_ini" value="<?php echo "1"; ?>" size="10"  onkeyup="mascara();" onBlur="validar_fecha2(this)" />
		<img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendario" width= "25" height="17" border="0" align="absmiddle" id="calendario" />
		<script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "txt_fec_ini",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script>
		&nbsp;Hasta:
        <input name="txt_fec_fin" type="text" id="txt_fec_fin" value="<?php echo date("Y-m-d"); ?>" size="10" onkeyup="mascara();" onBlur="validar_fecha2(this)" />
        <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendariof" width= "25" height="17" border="0" align="absmiddle" id="calendariof" />
		<script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "txt_fec_fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendariof",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script>		  
        </span>     
          <input type="checkbox" name="Chk_Fec" id="Chk_Fec" value="1" onclick="ShowHide('capa_rango_fec'); ShowHide('capa_fecha'); set_dia_mes();" 
		  style="cursor:pointer" />
    Elegir fecha </div></td>
  </tr>
	  </table>
</FIELDSET>
<script type="text/javascript">
 ShowHide('capa_rango_fec');
</script>
