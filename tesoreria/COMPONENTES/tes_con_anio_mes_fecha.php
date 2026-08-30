<?Php 
/* Componente para mostrar el mes y poder elegir un rango de fechas 
El a�o debe ser el selecionado desde el inicio en el periodo
Se debe definir un campo oculto hdd_ann con la fecha inicial del periodo
*/
switch (com_com_ven)
{
	case 1://Compras
		/* carga los a�os */
		$rs_anios = $obBD_con1->consulta(sentencias_tes(247,$obBD_con1->parametros($Com_Tic_Cod)), $obBD_conexion->conexion); 	
		$row_rs_anios = $obBD_con1->registros();
		$total_rs_anios = $obBD_con1->numregistros();
    break;
	case 2://Ventas 
		/***************Carga los a�os *******************/
		$rs_anios = $obBD_con1->consulta(sentencias_tes(245,$obBD_con1->parametros($Com_Tic_Cod)), $obBD_conexion->conexion);//Antes .'*'.$Pun_Cod	
		$row_rs_anios = $obBD_con1->registros();
		$total_rs_anios = $obBD_con1->numregistros();
	break;
}//FIn del switch ($com_com_ven)
?>
<script type="text/javascript">
/* Asigna el dia inicial y final del mes y a�o seleccionado */ 
function set_dia_mes()
{
	/* A�o tomado del objeto que debe estar en el FRONT */
	var ann = document.getElementById('cmb_anio').value;
	if (document.getElementById('Chk_Fec').checked)//En caso de seleccionar la eleccion de la fecha inicial y final 
	 {
	 	/* Toma la fecha del sistema para cargar el mes y dia respectivo */
		var fecha=new Date(); 
		/* Devuelve el mes del sistema */
		var mes=fecha.getMonth()+1;
		/* Devuelve el dia del sistema */
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
		
		var fecha_str = ann+"-"+mes+"-"+dia;
		document.getElementById('txt_fec_ini').value=fecha_str; 
		document.getElementById('txt_fec_fin').value=fecha_str;	
	 }
	else //En caso de elegir el mes, se toma el primer dia del mes y el ultimo dia del mes 
	{
	
		document.getElementById('txt_fec_ini').value= ann+"-"+document.getElementById('cmb_mes').value+"-01"; //Antes document.getElementById('cmb_mes').value+"-01"
		document.getElementById('txt_fec_fin').value= ann+"-"+document.getElementById('cmb_mes').value+"-"+cuantosDiasNum(document.getElementById('cmb_mes').value, ann);		
	}//Fin del if (document.getElementById('Chk_Fec').checked)
}//Fin del set_dia_mes()
</script>
<?php
$ann = $row_rs_anios['Anio'];
$mes = date("m");
$ann_act = explode('-', date("Y-m-d"));
?>
<FIELDSET>  
<LEGEND>
<label class="Etiqueta1">Seleccionar Fecha</label>
</LEGEND>
<table width="100%" border="0">
<tr>
  <td width="6%" class="Etiqueta1"><div align="left"><span id="capa_fecha">
    
    A&ntilde;o:&nbsp;
    <select name="cmb_anio" id="cmb_anio" onchange="set_dia_mes()">
      <?php 
		do{
		?>
      <option <?php if ($anio == $row_rs_anios['Anio']){ echo "selected"; } ?> value="<?php echo $row_rs_anios['Anio']; ?>"><?php echo $row_rs_anios['Anio']; ?></option>
      <?Php
		}while($row_rs_anios = $obBD_con1->fetch_assoc($rs_anios));
		?>
    </select>
    &nbsp;Mes:  
		      <select name="cmb_mes" id="cmb_mes" onchange="set_dia_mes()">
		        <!--<option value=""><< TODOS >></option>-->
		        <?Php
				  for ($i=1;$i<=12;$i++)
				  { 
				  	/* control para agregar un cero cuando se trata de un valor */
					if ($i <= 9)
					{
						$i = "0".$i;
					}
					/* Control para mantener seteada la informaci�n seleccionada */
					if (isset($cmb_mes))
					{
						$mes = $cmb_mes;
					}//FIn del if (isset($cmb_mes))
				  ?>
		        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1) ?></option>
		        <?Php
				  }//Fin del for ($i=1;$i<=12;$i++)
				  ?>
        </select>
		</span>
	    <span id="capa_rango_fec">
		Desde:
		<input name="txt_fec_ini" type="text" id="txt_fec_ini" value="<?php echo $ann."-".$ann_act[1]."-01"; //Antes $mes."-01" ?>" size="10"  onkeyup="mascara();" onBlur="validar_fecha2(this)" />
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
        <input name="txt_fec_fin" type="text" id="txt_fec_fin" value="<?php echo $ann.'-'.$ann_act[1].'-'.$ann_act[2]; ?>" size="10" onkeyup="mascara();" onBlur="validar_fecha2(this)" />
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
          <input type="checkbox" name="Chk_Fec" id="Chk_Fec" value="1" onclick="ShowHide('capa_rango_fec'); ShowHide('capa_fecha'); set_dia_mes();" style="cursor:pointer" />
    Elegir fecha </div></td>
  </tr>
	  </table>
</FIELDSET>
<script type="text/javascript">
 ShowHide('capa_rango_fec');
</script>
