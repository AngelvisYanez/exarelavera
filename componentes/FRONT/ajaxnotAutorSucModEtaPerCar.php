<?Php require_once('../../componentes/LOGICA/logica.php');
/* Componente para la busqueda de la Sucursal, Modalidad, Etapa, Periodo, Carrera, para registrar notas

Sucursal= Carga todas las sucursales abiertas por la universidad activas
Modalidad = todas las activas
Etapa = todas las activas
Periodo = todos los periodos activos desde el primero dia de clase al ultimo de clases + dias adicionales
Carreras = todas las abiertas en el periodo*/

if(isset($ajax_suc_cod))
{
	/* Consultar las modalidades */
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
	?>
        <select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&var_mod_cod=' + this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_etapa');">
          <option></option>
          <?Php  do { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=$obBD_con1->fetch_assoc($rs_modalidad));  ?>
        </select>        
<?Php
 	@$obBD_con1->free_result($rs_modalidad);
	exit();
}//Fin del if(isset($ajax_suc_cod))

/* Cargar datos con AJAX  */
if(isset($ajax_mod_cod))
{   
	/* Cargado de etapas */
	/* Actualmente carga las etapas correspondientes a las etapas de nivelacion */
	$rs_etapas = $obBD_con1->consulta(sentencias_com($sql, ''), $obBD_conexion->conexion);
	$row_rs_etapas = $obBD_con1->registros();
	$total_rs_etapas = $obBD_con1->numregistros();
	
	
?>
        <select name="Eta_Cod" id="Eta_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&var_mod_cod=<?Php echo $var_mod_cod; ?>&var_eta_cod='+this.value+'&var_suc_cod=<?Php echo $var_suc_cod; ?>','div_periodo');">
            <option></option>
            <?Php do { ?>
            <option value="<?Php echo $row_rs_etapas['Eta_Cod'].'*'.$row_rs_etapas['Eta_Rec']; ?>"><?Php echo $row_rs_etapas['Eta_Des'];  ?></option>
            <?Php //}
			 }while($row_rs_etapas=$obBD_con1->fetch_assoc($rs_etapas));  ?>
</select>
<?Php
 	@$obBD_con1->free_result($rs_etapas);
	exit();
}//Fin del if(isset($ajax_mod_cod))

/* Cargado de los periodo */
if(isset($ajax_periodo))
{	 
     $hoy = date("Y/m/d");
	
	 $Eta_Arr = explode('*',$var_eta_cod);
	 $rs_periodos = $obBD_con1->consulta(sentencias_com(14, $obBD_con1->parametros(
									$var_suc_cod.'*'.$var_mod_cod.'*'.$Eta_Arr[0].'*'.$hoy)), $obBD_conexion->conexion);
	 $row_rs_periodo = $obBD_con1->registros();
	 $total_rs_periodo = $obBD_con1->numregistros();
	 
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&var_suc_cod=<?Php echo $var_suc_cod; ?>&var_eta_cod=<?Php echo $var_eta_cod; ?>&var_mod_cod=<?Php echo $var_mod_cod; ?>&var_per_int='+this.value, 'div_carrera')">
	 <option></option>
	 <?Php do{ ?>
	  	 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
		 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
			  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin']; } ?>
	     </option>
	 <?Php }while($row_rs_periodo=$obBD_con1->fetch_assoc($rs_periodos)); ?>	 
	 </select>	 
	 <?Php
	 if ($var_eta_cod != "")
	 {
		 if ($total_rs_periodo == 0)
		 { 
			blink("&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de registro de calificaciones ha 
			caducado!", "txt_blink", "#FFFF00", "#FF0000");
		 }//Fin del if ($total_rs_periodo == 0)
	 }//Fin del if ($var_eta_cod != "")
	 @$obBD_con1->free_result($rs_periodo);
	 exit();
}//Fin del if(isset($ajax_periodo))

/* Consulta las carreras en base a la etapa */
if(isset($ajax_carrera))
{	/* Control para saber las carreras de cada etapa */
    $Eta_Arr=explode('*',$var_eta_cod);
	if($Eta_Arr[1]>0)
	{ 
		$Cod_Eta=$Eta_Arr[1];	
	}else{ 
		$Cod_Eta=$Eta_Arr[0]; 
	}

	/* Consultar las carreras por etapa a las cuales no se ha inscrito el estudiante */
	$rs_carreras_etapa=$obBD_con1->consulta(sentencias_com(106, $obBD_con1->parametros($var_per_int.'*'.$Cod_Eta.'*'.$Ses_Prs_Cod)), $obBD_conexion->conexion);
	$row_rs_carrera_etapa = $obBD_con1->registros();
	
	?>
	 <select name="Car_Int" id="Car_Int" >
       <option></option>
       <?Php do { ?>
       <option value="<?Php echo $row_rs_carrera_etapa['Car_Int'];  ?>"> <?Php echo $row_rs_carrera_etapa['Car_Nom']; ?> </option>
       <?Php } while($row_rs_carrera_etapa=$obBD_con1->fetch_assoc($rs_carreras_etapa)); ?>
     </select>
	 <?Php
	@$obBD_con1->free_result($rs_carreras_etapa);
	exit();
}//Fin del if(isset($ajax_carrera))



/* Cargado Ajax de las asignaturas en base al semestre y la variable de sesion de la persona*/
?>