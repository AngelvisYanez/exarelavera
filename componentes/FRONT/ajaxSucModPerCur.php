<?php require_once('../../componentes/LOGICA/logica.php');
/**
 * combo de modalidad
 */
if(isset($_GET['ajax_suc_cod']))
{
	/**
	 * Consultar las modalidades 
	 */
	$rs_modalidad = $obBD_con1->consulta(sentencias_com(302, $obBD_con1->parametros($var_suc_cod)), $obBD_conexion->conexion);
	
	$combo = "<select name='Mod_Cod' id='Mod_Cod' onChange=\"ajax_datos('".$_SERVER['PHP_SELF']."?ajax_periodo=1&var_mod_cod=' + this.value + '&var_suc_cod=".$var_suc_cod."','div_periodo');\">";
	$combo .= "<option value=''>Seleccione...</option>";

	while($row_rs_modalidad = $obBD_con1->fetch_assoc($rs_modalidad))
    { 
    	$combo .= "<option style='text-transform:uppercase' value='".$row_rs_modalidad['Mod_Cod']."'>".$row_rs_modalidad['Mod_Des']."</option>";
	}
	echo $combo."</select>";
	unset($rs_modalidad);
 	$obBD_con1->liberar();
 	$obBD_conexion->cerrar();
	exit();
}

/**
 * Cargado de los periodo 
 */
if(isset($_GET['ajax_periodo']))
{
	/**
	 * Consulta los periodos en base a las sucursales, modalidades y etapas
	 */
	$rs_periodo = $obBD_con1->consulta(sentencias_com(305, $obBD_con1->parametros($var_suc_cod.'*'.$var_mod_cod.'*'.$hoy.'*'.$Ses_Prs_Cod)), $obBD_conexion->conexion);
	
	$combo = "<select name='Per_Int' id='Per_Int' onChange=\"ajax_datos('".$_SERVER['PHP_SELF']."?ajax_nivel=1&var_suc_cod=".$var_suc_cod."&var_mod_cod=".$var_mod_cod."&var_per_int='+this.value,'div_cursos');\" >";
	$combo .= "<option value=''>Seleccione...</option>";
	
	while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodo))
	{ 
		$combo .= "<option value='".$row_rs_periodos['Per_Int']."'>";
			 $combo .= $row_rs_periodos['Mes_Ini']."/".$row_rs_periodos['Ann_Ini']." - ".$row_rs_periodos['Mes_Fin']."/".$row_rs_periodos['Ann_Fin']." [".$row_rs_periodos['Eta_Des']."]";
			 $combo .= "</option>";
	}
	
	echo $combo."</select>";
	
	if (count($Arr_periodos) == 0)
	{ 
		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de registro de calificaciones ha 
			caducado!</span>";
	}
	 unset($rs_periodo);	 
	 $obBD_con1->liberar();
	 $obBD_conexion->cerrar();
	 exit();
}

/**
 * Consultar los curso activos
 */
if(isset($_GET['ajax_nivel']))
{
	/**
	 * Consulta de los semestres 
	 */
	$rs_semestre = $obBD_con1->consulta(sentencias_com(306, $obBD_con1->parametros($Ses_Prs_Cod.'*'.$var_per_int)), $obBD_conexion->conexion);
	
	$combo = "<select name='Sem_Cod' id='Sem_Cod' >";
	$combo .= "<option value=''>Seleccione...</option>";
	 
	while($row_rs_semestres = $obBD_con1->fetch_assoc($rs_semestre))
	{ 
		$combo .= "<option value=".$row_rs_semestres['Sem_Cod']." style='text-transform: uppercase;' >[". cortar_cadena_param(' ', $row_rs_semestres['Car_Nom']).'] '.$row_rs_semestres['Sem_Nom']."</option>"; 
	}
	echo $combo."</select>"; 
	
	
	 unset($rs_semestre);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}
?>