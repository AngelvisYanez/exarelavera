<?Php
require_once('../../componentes/LOGICA/logica.php');
/* Cargar datos con AJAX  */
if(isset($ajax_suc_cod))
{		
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
?>
        <select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Suc_Cod=<?php echo $Suc_Cod;?>&Mod_Cod=' + this.value,'div_etapa')"  >
          <option></option>
          <?Php  do { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=mysqli_fetch_assoc($rs_modalidad));  ?>
        </select>
        <input type="checkbox" name="checkbox" value="checkbox" onClick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }
																else { document.getElementById('hdd_sql').value = 3; }; ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Suc_Cod=<?php echo $Suc_Cod;?>&Mod_Cod=' + document.getElementById('Mod_Cod').value,'div_etapa')">
      Otras Etapas
      <!-- 
2 = Etapas nivelacion
3 = Etapas semestrales -->
      <input name="hdd_sql" type="hidden" id="hdd_sql" value="3">
<?php
exit();
}
if(isset($ajax_mod_cod))
{	
	/* Cargado de etapas */
	/* Actualmente carga las etapas correspondientes a las etapas de nivelacion */
	$rs_etapas = $obBD_con1->consulta(sentencias_com($sql, ''), $obBD_conexion->conexion);
	$row_rs_etapas = $obBD_con1->registros();
	$total_rs_etapas = $obBD_con1->numregistros();
?>
        <select name="Eta_Cod" id="Eta_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod=<?Php echo $Mod_Cod; ?>&Suc_Cod=<?php echo $Suc_Cod;?>&Eta_Cod='+this.value,'div_periodo')">
            <option></option>
            <?Php do { ?>
            <option value="<?Php echo $row_rs_etapas['Eta_Cod'].'*'.$row_rs_etapas['Eta_Rec']; ?>"><?Php echo $row_rs_etapas['Eta_Des'];  ?></option>
            <?Php //}
			 }while($row_rs_etapas=mysqli_fetch_assoc($rs_etapas));  ?>
          </select>
<?Php
	exit();
}//Fin del if(isset($ajax_mod_cod))
/* Cargado de los periodo */
if(isset($ajax_periodo))
{
	 $hoy = date("Y-m-d");
     $Eta_Arr = explode('*',$Eta_Cod);
	 $rs_periodos = $obBD_con1->consulta(sentencias_com(105, $obBD_con1->parametros($Eta_Arr[0].'*'.$Mod_Cod.'*'.$hoy.'*'.$Suc_Cod)), $obBD_conexion->conexion);
	 $row_rs_periodo = $obBD_con1->registros();
	 $total_rs_periodo = $obBD_con1->numregistros();
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Eta_Cod=<?Php echo $Eta_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value, 'div_carrera')">
	 <option></option>
	 <?Php do{ ?>
	  	 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
		 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
			  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin']; if ($row_rs_periodo['Suc_Des'] != "") { echo ' ['.$row_rs_periodo['Suc_Des'].']'; }} ?>
	     </option>
	 <?Php }while($row_rs_periodo=mysqli_fetch_assoc($rs_periodos)); ?>	 
	 </select>	 
	 <?Php
	 if ($Eta_Cod != "")
	 {
		 if ($total_rs_periodo == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
			caducado!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	 }//Fin del if ($Eta_Cod != "")
	 exit();
}//Fin del if(isset($ajax_periodo))
/* Consulta las carreras en base a la etapa */
if(isset($ajax_carrera))
{	
	/* Control para saber las carreras de cada etapa */
    $Eta_Arr=explode('*',$Eta_Cod);
	if($Eta_Arr[1]>0)
	{ 
		$Cod_Eta=$Eta_Arr[1];	
	}else{ 
		$Cod_Eta=$Eta_Arr[0]; 
	}
	/* Consultar las carreras por etapa para los directores */
	$rs_carreras_etapa=$obBD_con1->consulta(sentencias_com(6, $obBD_con1->parametros($Cod_Eta.'*'.$Ses_Emp_Cod)), $obBD_conexion->conexion);
	$row_rs_carrera_etapa = $obBD_con1->registros();
	?>
	<select name="Car_Int" id="Car_Int">
           <option></option>
		   <?Php do { ?>
		   <option value="<?Php echo $row_rs_carrera_etapa['Car_Int'];  ?>"> <?Php echo $row_rs_carrera_etapa['Car_Nom']; ?>
		   </option>
		   <?Php } while($row_rs_carrera_etapa=mysqli_fetch_assoc($rs_carreras_etapa)); ?>
    </select>
	<?Php
	exit();
}//Fin del if(isset($ajax_carrera))
?>