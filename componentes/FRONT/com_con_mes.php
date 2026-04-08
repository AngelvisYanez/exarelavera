<?php 
/* 	Componente que muestra los meses en un combo Excepto el "<<TODOS>>" 
	Desarrollador: 	Jose Cumbicos Ortiz
	Fecha:			02/06/2010
*/
if (isset($Com_Fecha))
{
if (isset($mes)){/* mes seleccionado */$mes_sel=$cmb_mes; ?><select name="cmb_mes" id="cmb_mes">			
			<?Php  for ($i=1;$i<=12;$i++){ ?>
			<option <?php if ($i == $mes && !isset($cmb_mes)){ echo "selected"; }else{ if($mes_sel==$i){ ?> selected="selected" <?Php } } ?> value="<?Php echo $Com_Fecha."$i"; ?>"><?php echo mes($i, 1); ?></option>
			<?Php } ?>
		</select>
		<?Php 
	}//Fin del if (isset($mes))
	else
	{
		echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: mes<br>
						mes: Variable que contiene el mes actual.", 2); 	
	}//Fin del if (isset($mes))
}//Fin del if (isset($Com_Fecha))
else
{
	echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: Com_Fecha<br>
						Com_Fecha: Variable que contiene el campo de la base de datos para la busqueda. Ejemplo: AND MONTH(Cop_Fec)", 2); 
						//AND MONTH(Cop_Fec)							
}//Fin del else if (isset($Com_Fecha))
?>
