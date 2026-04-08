<?php 
/** 
* Descripcion: Componente para mostrar el mes y poder elegir un rango de fechas 
*			El año debe ser el selecionado desde el inicio en el periodo
*			Se debe definir un campo oculto hdd_ann con la fecha inicial del periodo
* Fecha de actualizacion:	2009-09-09
* Desarrollador:	Lewis Chimarro
* Fecha de actualizacion:	2012-06-26
* Desarrollador:	Lewis Chimarro
*/

/* Control para determinar si se encuentra seteada la fecha inicial del periodo contable */
if (isset($Pec_Fei)) {
?>
    <!--Librerias para calendario -->       
    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
    <script>
		$(function() { 
			$( "#txt_fec_ini" ).datepicker({
				changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd"});	
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/
			$( "#txt_fec_fin" ).datepicker({
				changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd"});	
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/
		}); 		
    </script>        
	<script language="javascript">
		/* Asigna el dia inicial y final del mes y año seleccionado */ 
		function set_dia_mes() {
			/* Año tomado del objeto que debe estar en el FRONT */
			var dato = document.getElementById('hdd_ann').value;				
			var ann=dato.split("-")
			if (document.getElementById('Chk_Fec').checked) {//En caso de seleccionar la eleccion de la fecha inicial y final 
				/* Toma la fecha del sistema para cargar el mes y dia respectivo */
				var fecha=new Date(); 
				/* Devuelve el mes del sistema */
				var mes=fecha.getMonth()+1;
				/* Devuelve el dia del sistema */
				var dia=fecha.getDate();
				/* Control para agregar un cero al mes */
				if (mes <= 9) {
					mes = "0"+mes;
				}
				/* Control para agregar un cero al dia */
				if (dia <= 9) {
					dia = "0"+dia;
				}
				
				var fecha_str = ann[0]+"-"+mes+"-"+dia;
				document.getElementById('txt_fec_ini').value=fecha_str; 
				document.getElementById('txt_fec_fin').value=fecha_str;	
			} else {//En caso de elegir el mes, se toma el primer dia del mes y el ultimo dia del mes 
				$('#txt_fec_ini').val(ann[0]+"-01-01"); //Antes document.getElementById('cmb_mes').value+"-01"			
				$('#txt_fec_fin').val(ann[0]+"-"+document.getElementById('cmb_mes').value+"-"+cuantosDiasNum(document.getElementById('cmb_mes').value, ann[0]));
			}//Fin del if (document.getElementById('Chk_Fec').checked)
		}//Fin del set_dia_mes()
	</script>
	<?php
	$ann = explode('-', $Pec_Fei);
	$mes = date("m");
	$ann_act = explode('-', (empty($txt_fec_fin)? date("Y-m-d"):$txt_fec_fin));
	?>

	<FIELDSET>  
	<LEGEND><label class="Etiqueta1">Seleccionar Fecha</label></LEGEND>
		<table width="100%" border="0">
			<tr>
				<td width="6%" class="Etiqueta1">
					<div align="left">
						<span id="capa_fecha">Desde:
							<span class="LetraNegra">Enero</span> &nbsp;&nbsp;&nbsp;Hasta: 
								<select name="cmb_mes" id="cmb_mes" style="text-align: center;" onchange="set_dia_mes()">
									<!--<option value=""><< TODOS >></option>-->
									<?php
									for ($i=1;$i<=12;$i++) { 
										/* control para agregar un cero cuando se trata de un valor */
										if ($i <= 9) {
											$i = "0".$i;
										}
										/* Control para mantener seteada la informacion seleccionada */
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
						<span id="capa_rango_fec"> Desde:
							<input name="txt_fec_ini" type="text" id="txt_fec_ini" style="text-align: center;" value="<?php if (isset($Chk_Fec)){ echo $txt_fec_ini;  } else { echo $ann[0]."-01-01"; } //Antes $mes."-01" ?>" size="10"  onkeyup="mascara();" onBlur="validar_fecha2(this)" />
							&nbsp;Hasta:
							<input name="txt_fec_fin" type="text" id="txt_fec_fin" style="text-align: center;" value="<?php if (isset($Chk_Fec)){ echo $txt_fec_fin; } else { echo $ann[0].'-'.$ann_act[1].'-'.ultimoDia($ann_act[1],$ann[0]); } ?>" size="10" onkeyup="mascara();" onBlur="validar_fecha2(this)" />
						</span>     
						<input type="checkbox" name="Chk_Fec" id="Chk_Fec" value="1" <?php if (isset($Chk_Fec)){ echo "checked='checked'"; } ?> onclick="ShowHide('capa_rango_fec'); ShowHide('capa_fecha'); set_dia_mes();" style="cursor:pointer" />
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
	echo error_alerta("<< Error de componente: con_con_anio_mes_fecha.php >> <br>Descripcion: No se ha definido la Propiedad: Pec_Fei<br>
        Pec_Fei: Variable que contiene la fecha inicial del periodo contable", 2);	
}//Fin del else if (isset($Pec_Fei))
?>