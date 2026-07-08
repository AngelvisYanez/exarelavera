<?Php
/*	Ajax que permite cargar:
	Paises	= 	Todas
	Regiones	=	Todas
	Provincias		=	Todas
	Ciudades 		=   Todas
	Parroquias = Todas
*/
?>
<table width="100%" border="0">
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span> Pa&iacute;s de nacimiento:</td>
    <td class="LetraNegra">
	<?Php	
	/* consulto los paises en la base de datos */
	$rs_paises=$obBD_con1->consulta(sentencias_com(106, ''), $obBD_conexion->conexion);
	$row_rs_paises= $obBD_con1->registros();
	$total_row_rs_paises = $obBD_con1->numregistros();
?>
<select name="Pas_Cod" id="Pas_Cod" 
onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pai_cod=1&Pas_Cod=' + this.value,'div_regiones')"  
    style="text-transform:uppercase"   >
    		<option value="">Seleccione...</option>
             <option></option>
        <?Php do{ ?>  
          <option <?PHP if($row_rs_paises['Pas_Cod']== $row_rs_persona['Pas_Cod'] ){ echo "selected";}?> value="<?php  echo $row_rs_paises['Pas_Cod']; ?>"><?php  echo $row_rs_paises['Pas_Nom']; ?></option>
         <?Php }while($row_rs_paises=$obBD_con1->fetch_assoc($rs_paises));  ?> 
    </select></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Region de nacimiento:</td>
    <td width="89%" class="LetraNegra"><div id="div_regiones" >
      <select name="Reg_Cod" id="Reg_Cod">
      <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Provincia de nacimiento:</td>
    <td class="LetraNegra"><div id="div_provincias">
      <select name="Pro_Cod" id="Pro_Cod">
        <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Ciudad de nacimiento:</td>
    <td class="LetraNegra">
	<div id="div_ciudades">
	 <select name="Ciu_Cod" id="Ciu_Cod" >
       
         <option value="">Seleccione...</option>
        
     </select>
	  </div>
    </td>
  </tr>
  <tr>
    <td width="20%" class="Etiqueta1"><span class="Asterisco">*</span>Parroquia de nacimiento:</td>
    <td class="LetraNegra">
	<div id="div_parroquias">
      <select name="Par_Cod" id="Par_Cod">
      <option value="">Seleccione...</option>
      </select>
    </div></td>
  </tr>
</table>
