<?Php
/**
 * Componente para la busqueda de Sucursal-Modalidad-Periodo Actual-Todas las carreras
 * Se utiliza el componentes Ajax ==> ajaxSucModPerActCarAll.php 
 * Sucursal= Carga todas las sucursales de la empresa
 * Modalidad = todas
 * Periodo = en base a la modalidad
 * Carreras = Filtra carreras que se habrirán en el periodo lectivo por curso
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * @Fecha de actualización:	2014-04-17
 *
 * @package componente.FRONT
 */
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
	/**
	* Consultar las modalidades 
	*/
	$rs_modalidad = $obBD_con11->getArrayConsulta(302, $Suc_Cod, $obBD_conexion11);
	?>
  	<select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&op=<?Php echo $op; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>&Com_Todos=<?Php echo $Com_Todos; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php
          foreach($rs_modalidad as $row)
          { 
          ?>
          <option value="<?Php echo $row['Mod_Cod'];  ?>" ><?Php echo $row['Mod_Des'];  ?></option>
          <?Php 
		  }  
		  ?>
        </select>
<?Php
	exit();
}
/**
* Cargado de los periodo 
*/
if(isset($ajax_periodo))
{	 	
	 $rs_periodos = $obBD_con11->GetArrayConsulta(300, $Mod_Cod.'*'.$hoy.'*'.$Suc_Cod, $obBD_conexion11);
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Com_Todos=<? echo $Com_Todos;?>&Eta_Cod=<?Php echo $rs_periodo['Eta_Cod']; ?>Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value+'&Com_Todos=<?php echo $Com_Todos; ?>', 'div_carrera')">
	 <option value="">Seleccione...</option>
	 <?Php foreach($rs_periodos as $ro)
	      { ?>
	  	 <option value="<?Php echo $ro['Per_Int']; ?>"><?Php if (count($rs_periodos) > 0) {
		 echo $ro['Mes_Ini']."/".$ro['Ann_Ini']." - ".
			  $ro['Mes_Fin']."/".$ro['Ann_Fin']." [".$ro['Eta_Des']."]"; } ?>
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
	$row_rs_etapa= $obBD_con11->getRowConsulta(16, $Per_Int, $obBD_conexion11);
	/* 
	* Consultar las carreras por etapa y periodos 
	*/
	$rs_carreras_etapa=$obBD_con11->getArrayConsulta(112, $Ses_Emp_Cod.'*'.$row_rs_etapa['Eta_Cod'], $obBD_conexion11);
	
	if($Com_Todos=="si")
	{
		foreach($rs_carreras_etapa as $roow) 
		{
			$cadTodo=$cadTodo.'*'.$roow['Car_Int'];
		} 
	}	
	?>
	<select name="Car_Int" id="Car_Int">		   
	   <? if($Com_Todos=="si")
	      {?>
       <option value="<? echo $cadTodo;?>"><? echo"<< TODOS >>";?></option>
	   <? }
		foreach($rs_carreras_etapa as $rows)  { ?>
	   <option value="<?Php echo $rows['Car_Int'];?>"><?Php echo $rows['Car_Nom'];?></option>
	 <?Php } ?>		   
    </select>
	<input type="hidden" id="Com_Todos" name="Com_Todos" value="<? echo $Com_Todos;?>">
	<?Php
	exit();
}//Fin del if(isset($ajax_carrera))
?>