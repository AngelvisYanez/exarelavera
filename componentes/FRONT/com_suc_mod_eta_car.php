<?php
/* Componente para la busqueda de la modalidad-etapa-carrera
Utiliza el componentes Ajax ==> ajax_suc_mod_eta_car.php 
Sucursal= Carga todas las sucursales abiertas por la universidad
Modalidad = todas
Etapa = todas
Carreras = todas
*/

// require_once('../LOGICA/logica.php');
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del acad&eacute;micos:</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
    <td class="Etiqueta1"><span class="Asterisco" >* </span>Sucursal:</td>
    <td class="LetraNegra">
    <?php  
    /* Consultar las sucursales de la universidad */
    $rs_sucursales= $obBD_con1->consulta(sentencias_com(101, ''), $obBD_conexion->conexion);
    $row_rs_sucursales= $obBD_con1->registros();
    $total_row_rs_sucursales = $obBD_con1->numregistros();	
    ?>
    
    <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&Suc_Cod=' + this.value,'div_sucursales')"  
    style="text-transform:uppercase"   >
      <option></option>
      <?php do{ ?>
      <option value="<?php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?php }while($row_rs_sucursales=$obBD_con1->registros()); ?>      
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