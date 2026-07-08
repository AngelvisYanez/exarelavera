<?Php
/* Componente para la busqueda de la modalidad-etapa-periodo-carrera 
Modalidad = todas
Etapa = todas
Periodo = en base a la etapa y modalidad
Carreras = las que el director de escuela esta autorizado
*/
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del periodo:</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span> Sucursal </td>
    <td class="LetraNegra">
	<?Php  
	/*** Consultar las sucursales de la universidad *************/
		$rs_sucursales= $obBD_con1->consulta(sentencias_com(101, $Ses_Emp_Cod), $obBD_conexion->conexion);
		$row_rs_sucursales= $obBD_con1->registros();
		$total_row_rs_sucursales = $obBD_con1->numregistros();
	?>
    	
    <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Suc_Cod=' + this.value +'&Com_Todos=<?php echo $Com_Todos;?>','div_modalidad')" style="text-transform:uppercase">
      <option></option>
      <?Php do{ ?>
      <option value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?Php }while($row_rs_sucursales=mysqli_fetch_assoc($rs_sucursales)); ?>
    </select>
	</td>
  </tr>
  <tr>
    <td width="11%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="89%" class="LetraNegra"><div id="div_modalidad">
	<?Php
		
	?>
        <select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Suc_Cod=<?php echo $Suc_Cod;?>&Mod_Cod=' + this.value,'div_etapa')"  >
          <option></option>
          <?Php  do { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=mysqli_fetch_assoc($rs_modalidad));  ?>
        </select>
        <input type="checkbox" name="checkbox" value="checkbox" onClick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }
																else { document.getElementById('hdd_sql').value = 3; }; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + document.getElementById('Mod_Cod').value,'div_etapa')">
      Otras Etapas
      <!-- 
2 = Etapas nivelacion
3 = Etapas semestrales -->
      <input name="hdd_sql" type="hidden" id="hdd_sql" value="3">
	  </div>
	  </td>
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
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Carrera:</td>
    <td ><DIV id="div_carrera">
      <select name="Car_Int" id="Car_Int">
        <option></option>
      </select>
    </DIV></td>
  </tr>
</table>
</LEGEND>
</FIELDSET>