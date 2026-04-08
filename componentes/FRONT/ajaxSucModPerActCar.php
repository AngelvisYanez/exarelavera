<?Php 
require_once('../../componentes/LOGICA/logica.php');
/**
 * objeto para conexion
 * @var Class_Log_Datos_Com
 */
$obBD_conexion11 = new Class_Log_Conexion_Com;
/**
 * objeto para consultas
 * @var Class_Log_Datos_Com
 */
$obBD_con11 =  new  Class_Log_Datos_Com;

if(isset($ajax_suc_cod))
{
	/*
	 * Consultar las modalidades
	*/
	$rs_modalidad = $obBD_con11->getArrayConsulta(302, $Suc_Cod, $obBD_conexion11);
	?>
        <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php  foreach($rs_modalidad  as $row_rs_modalidad ) { ?>
          <option value="<?Php echo $row_rs_modalidad['Mod_Cod'];  ?>" ><?Php echo $row_rs_modalidad['Mod_Des'];  ?></option>
          <?Php 
			}  ?>
        </select>
<?Php
	exit();
}
/* 
* Cargado de los periodo 
*/
if(isset($ajax_periodo))
{	 
	 $rs_periodos = $obBD_con11->getArrayConsulta(300, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion11);
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value, 'div_carrera')">
	 <option value="">Seleccione...</option>
	 <?Php foreach($rs_periodos as $row_rs_periodo ){ ?>
	  	 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
		 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
			  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin'].' ['.$row_rs_periodo['Eta_Des'].']'; } ?>
	     </option>
	 <?Php }?>	 
	 </select>	 
	 <?Php
		 if ($total_rs_periodo == 0)
		 {
	 		echo "<span class='Texto_Reporte_Rojo'>&iexcl;No existen periodos Activos &oacute; la fecha m&aacute;xima de matr&iacute;cula ha 
			caducado!</span>";
		 }
	exit();
}//Fin del if(isset($ajax_periodo))
/* 
* Consulta las carreras en base a la etapa 
*/
if(isset($ajax_carrera))
{
	/** 
	* Consultar la etapa en base al periodos
	*/
	$row_rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
	/* 
	* Consultar las carreras por etapa y periodos 
	*/
	$rs_carreras_etapa=$obBD_con11->getArrayConsulta(102, $row_rs_etapa['Eta_Cod'].'*'.$Per_Int, $obBD_conexion11);
	?>
	 <select name="Car_Int" id="Car_Int">
       <option value="">Seleccione...</option>
       <?Php foreach($rs_carreras_etapa as $row_rs_carrera_etapa) 
	   { ?>
       <option value="<?Php echo $row_rs_carrera_etapa['Car_Int'];  ?>"> <?Php echo $row_rs_carrera_etapa['Car_Nom']; ?> </option>
       <?Php } ?>
     </select>
	 <?Php
	exit();
}//Fin del if(isset($ajax_carrera))
?>