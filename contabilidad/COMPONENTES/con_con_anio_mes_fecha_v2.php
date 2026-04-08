<?php 
/** 
* Descripción: Componente para mostrar el mes y poder elegir un rango de fechas 
*			El año debe ser el selecionado desde el inicio en el periodo
*			Se debe definir un campo oculto hdd_ann con la fecha inicial del periodo
* Fecha de actualización:	2009-09-09
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-06-26
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2025-01-XX
* Desarrollador:	Actualización con combo box de mes para elegir fecha
*/

/* Control para determinar si se encuentra seteada la fecha inicial del periodo contable */
if (isset($Pec_Fei)) {
?>
    <!--Librerias para calendario -->       
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
    <script>
		$(function() {
			$( "#txt_fec_ini" ).datepicker({
				changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", onSelect: function(dateText, inst) { deseleccionar_mes(); }
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ 
			});	
			$( "#txt_fec_fin" ).datepicker({
				changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", onSelect: function(dateText, inst) { deseleccionar_mes(); }
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ 
			});	
		}); 		
    </script>        
	<script language="javascript">
	/* Asigna el dia inicial y final del mes y año seleccionado */ 
	function set_dia_mes() {
		/* Año tomado del objeto que debe estar en el FRONT */
		var dato = document.getElementById('hdd_ann').value;				
		var ann=dato.split("-")
		
		if (document.getElementById('Chk_Fec').checked) {//En caso de seleccionar la eleccion de la fecha inicial y final 
			// Si está marcado "Elegir fecha", usar el mes seleccionado en el combo box
			var comboMesElegir = document.getElementById('cmb_mes_elegir');
			if (comboMesElegir && comboMesElegir.value && comboMesElegir.value != '') {
				var mesNum = parseInt(comboMesElegir.value);
				var mesFormato = mesNum <= 9 ? "0" + mesNum : mesNum.toString();
				
				// Establecer desde el día 1 del mes seleccionado hasta el último día del mes
				$('#txt_fec_ini').val(ann[0]+"-"+mesFormato+"-01");
				var ultimoDia = cuantosDiasNum(mesNum, parseInt(ann[0]));
				var ultimoDiaFormato = ultimoDia < 10 ? "0" + ultimoDia : ultimoDia.toString();
				$('#txt_fec_fin').val(ann[0]+"-"+mesFormato+"-"+ultimoDiaFormato);
			} else {
				// Si no hay mes seleccionado, usar la fecha del sistema
				var fecha=new Date(); 
				var mes=fecha.getMonth()+1;
				var dia=fecha.getDate();
				if (mes <= 9) mes = "0"+mes;
				if (dia <= 9) dia = "0"+dia;
				var fecha_str = ann[0]+"-"+mes+"-"+dia;
				document.getElementById('txt_fec_ini').value=fecha_str; 
				document.getElementById('txt_fec_fin').value=fecha_str;
			}
		} else {//En caso de elegir el mes, se toma el primer dia del mes y el ultimo dia del mes 
			$('#txt_fec_ini').val(ann[0]+"-01-01"); //Antes document.getElementById('cmb_mes').value+"-01"			
			$('#txt_fec_fin').val(ann[0]+"-"+document.getElementById('cmb_mes').value+"-"+cuantosDiasNum(document.getElementById('cmb_mes').value, ann[0]));
		}//Fin del if (document.getElementById('Chk_Fec').checked)
	}//Fin del set_dia_mes()
	
	/* Función para deseleccionar y bloquear el mes cuando se cambia manualmente la fecha */
	function deseleccionar_mes() {
		if (document.getElementById('Chk_Fec').checked) {
			// Si está marcado "Elegir fecha", deseleccionar y bloquear el combo box de mes
			var comboMes = document.getElementById('cmb_mes_elegir');
			if (comboMes) {
				comboMes.selectedIndex = 0; // Volver a "-- Seleccione un mes --"
				comboMes.disabled = true;
			}
		}
	}
	
	/* Función para habilitar el combo box cuando se marca "Elegir fecha" */
	function habilitar_combo_mes() {
		var comboMes = document.getElementById('cmb_mes_elegir');
		var capaCombo = document.getElementById('capa_combo_mes');
		if (comboMes && capaCombo) {
			if (document.getElementById('Chk_Fec').checked) {
				comboMes.disabled = false;
				// Mostrar el combo box
				capaCombo.style.display = 'inline';
			} else {
				comboMes.disabled = true;
				// Ocultar el combo box
				capaCombo.style.display = 'none';
			}
		}
	}
	</script>
	<?php
	$ann = explode('-', $Pec_Fei);
	$mes = date("m");
	$ann_act = explode('-', (empty($txt_fec_fin)? date("Y-m-d"):$txt_fec_fin));
	
	/**
	 * Control para mantener seteada la información seleccionada del combo box de mes elegir
	 * Si viene del POST, usar ese valor
	 * Si no, intentar determinar el mes desde las fechas
	 */
	$mes_elegir = '';
	if (isset($cmb_mes_elegir) && $cmb_mes_elegir != '') {
		$mes_elegir = $cmb_mes_elegir;
	} elseif (isset($txt_fec_ini) && $txt_fec_ini != '') {
		// Extraer el mes de la fecha de inicio
		$fec_ini_parts = explode('-', $txt_fec_ini);
		if (count($fec_ini_parts) >= 2) {
			$mes_elegir = $fec_ini_parts[1];
		}
	} elseif (isset($txt_fec_fin) && $txt_fec_fin != '') {
		// Extraer el mes de la fecha de fin
		$fec_fin_parts = explode('-', $txt_fec_fin);
		if (count($fec_fin_parts) >= 2) {
			$mes_elegir = $fec_fin_parts[1];
		}
	}
	?>

	<FIELDSET>  
	<LEGEND> <label class="Etiqueta1">Seleccionar Fecha</label> </LEGEND>
		<table width="100%" border="0">
			<tr>
				<td width="6%" class="Etiqueta1">
					<div align="left">
						<span id="capa_fecha"> Desde: <span class="LetraNegra">Enero</span>
							&nbsp;&nbsp;&nbsp;Hasta: 
							<select name="cmb_mes" id="cmb_mes" style="text-align: center;" onchange="set_dia_mes()">
								<!--<option value=""><< TODOS >></option>-->
								<?php
								for ($i=1;$i<=12;$i++) { 
									/* control para agregar un cero cuando se trata de un valor */
									if ($i <= 9) {
										$i = "0".$i;
									}
									/* Control para mantener seteada la informacin seleccionada */
									if (isset($cmb_mes)) {
										$mes = $cmb_mes;
									}//FIn del if (isset($cmb_mes))
								?>
								<option <?php if ($i == $mes){ echo "selected"; } ?> value="<?php echo $i; ?>"><?php echo mes($i, 1) ?></option>
								<?php
								}//Fin del for ($i=1;$i<=12;$i++)
								?>
							</select>
						</span>
						<span id="capa_combo_mes" style="display:none;"> Mes: 
							<select name="cmb_mes_elegir" id="cmb_mes_elegir" onchange="set_dia_mes()" style="width: 90px; text-align: center;">
								<option value="">-- Seleccione un mes --</option>
								<?php
								// Usar $mes_elegir si está disponible, de lo contrario usar $mes
								$mes_seleccionado = ($mes_elegir != '') ? $mes_elegir : $mes;
								
								for ($i=1;$i<=12;$i++) { 
									if ($i <= 9) {
										$i_formato = "0".$i;
									} else {
										$i_formato = (string)$i;
									}
								?>
								<option <?php if ($i_formato == $mes_seleccionado){ echo "selected"; } ?> value="<?php echo $i_formato; ?>"><?php echo mes($i_formato, 1) ?></option>
								<?php
								}
								?>
							</select>
							&nbsp;&nbsp;&nbsp;
						</span>
						<span id="capa_rango_fec"> Desde:
							<input name="txt_fec_ini" type="text" id="txt_fec_ini" style="text-align: center;" value="<?php if (isset($Chk_Fec)){ echo $txt_fec_ini;  }else{ echo $ann[0]."-01-01"; } //Antes $mes."-01" ?>" size="10"  onkeyup="mascara(); deseleccionar_mes();" onBlur="validar_fecha2(this); deseleccionar_mes();" onchange="deseleccionar_mes();" />
							&nbsp;Hasta:
							<input name="txt_fec_fin" type="text" id="txt_fec_fin" style="text-align: center;" value="<?php if (isset($Chk_Fec)){ echo $txt_fec_fin; } else{ echo $ann[0].'-'.$ann_act[1].'-'.ultimoDia($ann_act[1],$ann[0]); } ?>" size="10" onkeyup="mascara(); deseleccionar_mes();" onBlur="validar_fecha2(this); deseleccionar_mes();" onchange="deseleccionar_mes();" />
						</span>     
						<input type="checkbox" name="Chk_Fec" id="Chk_Fec" value="1" <?php if (isset($Chk_Fec)){ echo "checked='checked'"; } ?> onclick="ShowHide('capa_rango_fec'); ShowHide('capa_fecha'); habilitar_combo_mes(); set_dia_mes();" style="cursor:pointer" />
						Elegir fecha
					</div>
				</td>
			</tr>
		</table>
	</FIELDSET>
	<script language="javascript">
	<?php 
	if (isset($Chk_Fec)) {
	?>
		ShowHide('capa_fecha');
		habilitar_combo_mes();
	<?php
	//Fin del if (isset($Chk_Fec))
	} else {
	?>
		ShowHide('capa_rango_fec');
	<?php
	//Fin del else if (isset($Chk_Fec))
	}
	?>
	</script>
<?php
//Fin del if (isset($Pec_Fei))
} else {
	echo error_alerta("<< Error de componente: con_con_anio_mes_fecha_v2.php >> <br>Descripcion: No se ha definido la Propiedad: Pec_Fei<br>
        Pec_Fei: Variable que contiene la fecha inicial del periodo contable", 2);	
}//Fin del else if (isset($Pec_Fei))
?>
