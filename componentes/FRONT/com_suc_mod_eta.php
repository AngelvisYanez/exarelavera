<?Php
/*
Ajax que permite cargar:
Surcursales	= 	Todas
Modalidad	=	Todas
Etapa		=	Todas
Anio 		=   Actual para el registro de periodos
*/

?>

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
    
	<? if($total_row_rs_sucursales!=0){?>
    <select name="Suc_Cod" id="Suc_Cod" onchange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_suc_cod=1&op=<?Php echo $op; ?>&Suc_Cod=' + this.value,'div_sucursales')" >
      <option>Seleccione...</option>
      <?Php do{ ?>
      <option style="text-transform:uppercase" value="<?Php echo $row_rs_sucursales['Suc_Cod']; ?>" ><?Php echo $row_rs_sucursales['Suc_Des']; ?></option>
	  <?Php }while($row_rs_sucursales=$obBD_con1->fetch_assoc($rs_sucursales)); ?>      
    </select>
	<? }else{?>
	<select name="Suc_Cod" id="Suc_Cod">
      <option>Ninguno...</option>            	  
    </select>
	<? }?>
	</td>
  </tr>
  <tr>
    <td width="10%" class="Etiqueta1"><span class="Asterisco" >* </span>Modalidad:</td>
    <td width="90%" class="LetraNegra">
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
</table>
