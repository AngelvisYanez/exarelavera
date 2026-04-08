<?Php require_once('../../componentes/LOGICA/logica.php');
/*
Ajax que permite cargar:
Surcursales	= 	Todas
Modalidad	=	Todas
Etapa		=	Todas
Carrera		=   Todas
*/
if(isset($ajax_suc_cod))
{
	/* Consultar las modalidades */
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(1, ''), $obBD_conexion->conexion);
	$row_rs_modalidad = $obBD_con1->registros();
	$total_rs_modalidad = $obBD_con1->numregistros();
	?>
        <select name="Mod_Cod" id="Mod_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>','div_etapa')"  >
          <option></option>
          <?Php  do { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}while($row_rs_modalidad=$obBD_con1->registros());  ?>
        </select>
        <input type="checkbox" name="checkbox" value="checkbox" onClick="if (this.checked){ document.getElementById('hdd_sql').value = 2; }
																else { document.getElementById('hdd_sql').value = 3; }; 
                                                                ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mod_cod=1&sql='+document.getElementById('hdd_sql').value+'&Mod_Cod=' + document.getElementById('Mod_Cod').value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>','div_etapa')">
      Otras Etapas
      <!-- 
2 = Etapas nivelacion
3 = Etapas semestrales -->
      <input name="hdd_sql" type="hidden" id="hdd_sql" value="3">
<?Php
 	@$obBD_con1->liberar();
	exit();
}//Fin del if(isset($ajax_suc_cod))

/* Cargar datos con AJAX  */
if(isset($ajax_mod_cod))
{   /* Cargado de etapas */
	/* Actualmente carga las etapas correspondientes a las etapas de nivelacion */
	$rs_etapas = $obBD_con1->consulta(sentencias_com($sql, ''), $obBD_conexion->conexion);
	$row_rs_etapas = $obBD_con1->registros();
	$total_rs_etapas = $obBD_con1->numregistros();
?>
        <select name="Eta_Cod" id="Eta_Cod" style="text-transform:uppercase"  onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Mod_Cod=<?Php echo $Mod_Cod; ?>&Eta_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>','div_carrera')">
            <option></option>
            <?Php do { ?>
            <option value="<?Php echo $row_rs_etapas['Eta_Cod'].'*'.$row_rs_etapas['Eta_Rec']; ?>"><?Php echo $row_rs_etapas['Eta_Des']; ?></option>
            <?Php 
			 }while($row_rs_etapas=$obBD_con1->registros());  ?>
          </select>
<?Php
 	@$obBD_con1->liberar();
	exit();
}//Fin del if(isset($ajax_mod_cod))

/* Consulta las carreras en base a la etapa */
if(isset($ajax_carrera))
{	/* Control para saber las carreras de cada etapa */
    $Eta_Arr=explode('*',$Eta_Cod);
	if($Eta_Arr[1]>0)
	{ 
		$Cod_Eta=$Eta_Arr[1];	
	}else{ 
		$Cod_Eta=$Eta_Arr[0]; 
	}

	/* Consultar todas las carreras por etapa */
	$rs_carreras_etapa=$obBD_con1->consulta(sentencias_com(104, $obBD_con1->parametros($Cod_Eta)), $obBD_conexion->conexion);
	$row_rs_carrera_etapa = $obBD_con1->registros();
	
	?>
	<select name="Car_Int" id="Car_Int">
           <option value="T"><< TODAS >></option>
		   <?Php do { ?>
		   <option value="<?Php echo $row_rs_carrera_etapa['Car_Int'];  ?>"> <?Php echo $row_rs_carrera_etapa['Car_Nom']; ?></option>
		   <?Php } while($row_rs_carrera_etapa=$obBD_con1->registros()); ?>		   
    </select>
	<?Php
	/* Vuelve al fin del puntero la consulta creditos */
	$row_rs_carrera_etapa = first_last($rs_carreras_etapa, $row_rs_carrera_etapa, 0); ?>

	<?Php
	$i=0;
	do{ 		
	?>						
		<input name="carrera_cod[<?Php echo $i; ?>]" type="hidden" value="<?php echo $row_rs_carrera_etapa['Car_Int']; ?>">
	<?Php
		$i++;
	}while($row_rs_carrera_etapa=$obBD_con1->registros());

	@$obBD_con1->liberar();
	exit();
}//Fin del if(isset($ajax_carrera))
?>