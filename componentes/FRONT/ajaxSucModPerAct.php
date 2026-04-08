<?Php

if(isset($ajax_suc_cod))
{
	/**
	 * Consultar las modalidades
	 */
	$rs_modalidad = $obBD_con2->getArrayConsulta(302, $Suc_Cod, $obBD_conexion1);
	?>
        <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&op=<?Php echo $op; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php
        foreach($rs_modalidad as $row_rs_modalidad )  { 
          ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
		  }		  ?>
        </select>
<?Php
	exit();
}
/* 
* Cargado de los periodo 
*/
if(isset($ajax_periodo))
{	 
	 $rs_periodos = $obBD_con2->getArrayConsulta(303, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion1);
	 ?>
	 <select name="Per_Int" id="Per_Int" >
	 <option value="">Seleccione...</option>
	 <?Php foreach($rs_periodos as $row_rs_periodo ){ ?>
	  	 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
		 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
			  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin'].' ['.$row_rs_periodo['Eta_Des'].']'; } ?>
	     </option>
	 <?Php } ?>	 
	 </select>	 
	 <?Php
		 if (count($rs_periodos) == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
			caducado!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	exit();
}//Fin del if(isset($ajax_periodo))
?>