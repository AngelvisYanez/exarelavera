<?Php
/*
Ajax que permite cargar:
Surcursales	= 	Todas
Modalidad	=	Todas
Etapa		=	Todas
*/


require_once('../../componentes/LOGICA/logica.php');
if(isset($ajax_suc_cod))
{
	/*** Consultar las modalidades ********/
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
?>
	<? if($total_rs_modalidad!=0){?>
	<select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Est_Int=<?Php echo $codigo ?>','div_etapa')">
	  <option>Seleccione...</option>
	  <?Php do { ?>
	  <option style="text-transform:uppercase" value="<?Php echo $row_rs_modalidad['Mod_Cod'];?>"><?Php echo $row_rs_modalidad['Mod_Des'];?></option>
	  <?Php 
		}while($row_rs_modalidad=$obBD_con1->fetch_assoc($rs_modalidad));  ?>
	</select>	
	<input type="checkbox" name="checkbox" value="checkbox" onClick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }else { document.getElementById('hdd_sql').value = 3; }; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + document.getElementById('Mod_Cod').value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Est_Int=<?Php echo $codigo ?>','div_etapa')">
      Otras Etapas    
      <input name="hdd_sql" type="hidden" id="hdd_sql" value="3">
	  <? }else{?>
		<select name="Mod_Cod" id="Mod_Cod">
		  <option>Ninguno...</option>            	  
		</select>
	  <? }?>
<?Php
 	@$obBD_con1->free_result($rs_modalidad);
	exit();
} //Fin del if(isset($ajax_suc_cod))


/* Cargar datos con AJAX  */
if(isset($ajax_mod_cod))
{
	/* Cargado de etapas */
	/* Actualmente carga las etapas correspondientes a las etapas de nivelacion */
	$rs_etapas = $obBD_con1->consulta(sentencias_com($sql, ''), $obBD_conexion->conexion);
	$row_rs_etapas = $obBD_con1->registros();
	$total_rs_etapas = $obBD_con1->numregistros();
?>
    <? if($total_rs_etapas!=0){?>    
	<select name="Eta_Cod" id="Eta_Cod">
		<option>Seleccione...</option>
		<?Php do { ?>
		<option style="text-transform:uppercase" value="<?Php echo $row_rs_etapas['Eta_Cod'].'*'.$row_rs_etapas['Eta_Rec']; ?>"><?Php echo $row_rs_etapas['Eta_Des'];  ?></option>
		<?Php }while($row_rs_etapas=mysqli_fetch_assoc($rs_etapas));  ?>
	 </select>
	 <? }else{?>
	 <select name="Mod_Cod" id="Mod_Cod">
	  <option>Ninguno...</option>            	  
	 </select>
	 <? }?>
<?Php
 	@$obBD_con1->free_result($rs_etapas);
	exit();
}//Fin del if(isset($ajax_mod_cod))

?>