<?Php
if (!is_object($obBD_con1)) return;
/* Componente para la busqueda de la modalidad-etapa-periodo-carrera-curso para inscripciones
Modalidad = todas
Etapa = todas
Periodo = en base a la etapa y modalidad
Carreras = las a las cuales NO se ha inscrito el estudiante
Curso = cursos activos en base a la carrera y el periodo
*/
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del periodo:</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra">
    <?Php  
	/*** Consultar las sucursales de la universidad *************/
		$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, ''), $obBD_conexion->conexion);
		$row_rs_sucursales= $obBD_con1->registros();
		$total_row_rs_sucursales = $obBD_con1->numregistros();
	
	?>
    
    <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&op=<?Php echo $op; ?>&Suc_Cod=' + this.value,'div_sucursales')"  
    style="text-transform:uppercase"   >
      <option></option>
      <?Php do{ ?>
      <option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?Php }while($row_rs_sucursales=mysqli_fetch_assoc($rs_sucursales)); ?>      
    </select></td>
  </tr>
  <tr>
    <td width="17%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="83%" class="LetraNegra">
    <div id="div_sucursales" >
    <select name="Mod_Cod" id="Mod_Cod">
        <option></option>
      </select>
    </div>	</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Etapa:</td>
    <td class="LetraNegra"><div id="div_etapa">
      <select name="Eta_Cod" id="Eta_Cod">
        <option></option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Periodo:</td>
    <td class="LetraNegra"><div id="div_periodo">
      <select name="Per_Int" id="Per_Int">
      </select>
    </div></td>
  </tr>
</table>
</LEGEND>
</FIELDSET>