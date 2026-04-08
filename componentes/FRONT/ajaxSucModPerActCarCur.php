<?Php 
require_once('../../componentes/LOGICA/logica.php');
/**
 * Ajax que permite cargar:
 * Sucursal = Todos
 * Modalidad	=	Todas
 * Perido [Etapa] =	Todos
 * Carrera =   Solo las que se abren en el periodo
 * Curso =   Semestres
 */
/**
 *Creacion del Objeto de conexion
 */ 
 $obBD_conexion11 = new Class_Log_Conexion_Com;
/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con11 =  new  Class_Log_Datos_Com;

if(isset($ajax_suc_cod))
{
	/**
	 * Consultar las modalidades
	 */
	$rs_modalidad = $obBD_con11->getArrayConsulta(302, $Suc_Cod, $obBD_conexion11);
	if(count($rs_modalidad)==1)
	{
	 $row = $obBD_con11->getRowConsulta(17, $Suc_Cod, $obBD_conexion11);	
	 echo $rs_modalidad['Mod_Des'];  	
	 }
	else
	{
	?>	 <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Est_Int=<?php echo $Est_Int;?>&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php  foreach($rs_modalidad as $row_rs_modalidad ) { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}  ?>
        </select> <?
	}
	exit();
}

/** 
 * Cargado de los periodo 
 */
if(isset($ajax_periodo))
{	 
	 $rs_periodos = $obBD_con11->getArrayConsulta(300, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion11);
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Est_Int=<?php echo $Est_Int;?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value+'&Eta_Cod=<?php echo $rs_periodos['Eta_Cod'] ?>', 'div_carrera')">
	 <option value="">Seleccione...</option>
	 <?Php foreach($rs_periodos as $rs){ ?>
	  	 <option value="<?Php echo $rs['Per_Int']; ?>"><?Php if (count($rs_periodos) > 0) {
		 echo $rs['Mes_Ini']."/".$rs['Ann_Ini']." - ".
			  $rs['Mes_Fin']."/".$rs['Ann_Fin'].' ['.$rs['Eta_Des'].']'; } ?>
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

/** 
 * Consulta las carreras en base a la etapa 
 */
if(isset($ajax_carrera))
{
	/** 
	* Consultar la etapa en base al periodos
	*/
	$rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
	 /**
	 * Combo donde carga carrera, si hay más de 1 registro
	 * Carga el combo caso contrario solo 
	 */		 
	$rs_carreras_etapa=$obBD_con11->getArrayConsulta(102, $rs_etapa['Eta_Cod'].'*'.$Per_Int, $obBD_conexion11);
	?>
	 <select name="Car_Int" id="Car_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nivel=1&Eta_Cod=<?Php echo $Eta_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Est_Int=<?php echo $Est_Int;?>&Per_Int=<?Php echo $Per_Int; ?>&Car_Int='+this.value, 'div_curso')" >
       <option value="" >Seleccione...</option>
       <?Php foreach($rs_carreras_etapa as $row)
	    { ?>
       <option style="text-transform:uppercase" value="<?Php echo $row['Car_Int'];  ?>"> <?Php echo $row['Car_Nom']; ?> </option>
       <?Php }  ?>
     </select>
	 <?Php
	 exit();
}//Fin del if(isset($ajax_carrera))

/**
 * Consultar los curso activos 
 */
if(isset($ajax_nivel))
{
	/**
	 * Consulta de los semestres 
	 */
	$rs_semestres=$obBD_con11->getArrayConsulta(301, $Car_Int.'*'.$Per_Int.'*'.$Mod_Cod.'*'.$Est_Int,$obBD_conexion11);
	?>
	<select name="Sem_Cod" id="Sem_Cod" >
	<option value="">Seleccione...</option>
	<?Php foreach($rs_semestres as $row_rs_semestres)
	{  ?>
		<option style="text-transform:uppercase" value="<?Php echo $row_rs_semestres['Sem_Cod'];  ?>"><?Php echo strtoupper($row_rs_semestres['Sem_No2']);  ?></option>
	<?Php 
	}
	 ?>
	</select>
	<?Php
	 if ($Car_Int != "")
	 {
		 if (count($rs_semestres) == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>¡No existen Cursos aperturados!</span>";
		 }//Fin del if ($total_rs_periodo == 0)
	 }//Fin del if ($Eta_Cod != "")	
	 exit();
}//Fin del if(isset($ajax_nivel))
?>