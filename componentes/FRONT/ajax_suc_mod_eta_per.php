<?Php require_once('../../componentes/LOGICA/logica.php');
if(isset($ajax_suc_cod))
{
	/*** Consultar las modalidades ********/
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
	?>
        <select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&op=<?Php echo $op; ?>&Est_Int=<?Php echo $codigo ?>','div_etapa')"  >
          <option></option>
          <?Php  do { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=mysqli_fetch_assoc($rs_modalidad));  ?>
        </select>
        <input type="checkbox" name="checkbox" value="checkbox" onClick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }
																else { document.getElementById('hdd_sql').value = 3; }; 
                                                                ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + document.getElementById('Mod_Cod').value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Est_Int=<?Php echo $codigo ?>','div_etapa')">
      Otras Etapas
      <!-- 
2 = Etapas nivelacion
3 = Etapas semestrales -->
      <input name="hdd_sql" type="hidden" id="hdd_sql" value="3">
<?Php
	exit();
}
?>
<?Php
/* Cargar datos con AJAX  */
if(isset($ajax_mod_cod))
{   /* Cargado de etapas */
	/* Actualmente carga las etapas correspondientes a las etapas de nivelacion */
	$rs_etapas = $obBD_con1->consulta(sentencias_com($sql, ''), $obBD_conexion->conexion);
	$row_rs_etapas = $obBD_con1->registros();
	$total_rs_etapas = $obBD_con1->numregistros();
?>
        <select name="Eta_Cod" id="Eta_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod=<?Php echo $Mod_Cod; ?>&Eta_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&op=<?Php echo $op; ?>&Est_Int=<?Php echo $Est_Int ?>','div_periodo')">
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
{	 //echo $Suc_Cod;
	 $hoy = date("Y-m-d");
     $Eta_Arr = explode('*',$Eta_Cod);
	 $rs_periodos = $obBD_con1->consulta(sentencias_com(103, $obBD_con1->parametros($Eta_Arr[0].'*'.$Mod_Cod.'*'.$hoy.'*'.$Suc_Cod)), $obBD_conexion->conexion);
	 $row_rs_periodo = $obBD_con1->registros();
	 $total_rs_periodo = $obBD_con1->numregistros();
	 ?>
	 <select name="Per_Int" id="Per_Int"  >
	 <option></option>
	 <?Php do{ ?>
	  	 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
		 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
			  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin']; } ?>
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
?>