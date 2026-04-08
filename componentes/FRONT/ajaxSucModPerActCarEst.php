<?Php require_once('../../componentes/LOGICA/logica.php');
/**
 * Ajax que permite cargar:
 * Surcursales	= 	Todas
 * Modalidad	=	Todas
 * Etapa		=	Todas
 * Perido 		=	Todos
 * Carrera		=   Solo las que se abren en el periodo y el estudiante no haya tomado
 */
 /**
 *Creacion del Objeto de conexion
 */
$obBD_conexion44 = new Class_Log_Conexion_Com;
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con44 =  new Class_Log_Datos_Com;
/**
 * Creación del objeto para evitar el reenvio 
 */
if(isset($ajax_suc_cod))
{
	/**
	 * Consultar las modalidades
	 */
	$rs_modalidad = $obBD_con44->getArrayConsulta(302, $Suc_Cod, $obBD_conexion44);
	?>
        <select name="Mod_Cod" id="Mod_Cod" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_periodo=1&Mod_Cod='+this.value+'&Suc_Cod=<?Php echo $Suc_Cod; ?>&Com_Hoy=<?php echo $Com_Hoy; ?>','div_periodo')">
          <option value="">Seleccione...</option>
          <?Php
          foreach($rs_modalidad as $ro) 
          { 
          ?>
          <option value="<?Php echo $ro['Mod_Cod'];  ?>" ><?Php echo $ro['Mod_Des'];  ?></option>
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
	 $rs_periodos = $obBD_con44->getArrayConsulta(300, $Mod_Cod.'*'.$Com_Hoy.'*'.$Suc_Cod, $obBD_conexion44);
	 ?>
	 <select name="Per_Int" id="Per_Int" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_carrera=1&Suc_Cod=<?Php echo $Suc_Cod; ?>&Mod_Cod=<?Php echo $Mod_Cod; ?>&Per_Int='+this.value+'&Eta_Cod=<?php echo $rs_periodos['Eta_Cod'] ?>', 'div_carrera')">
	 <option value="">Seleccione...</option>
	 <?Php foreach( $rs_periodos as $rt){ ?>
	  	 <option value="<?Php echo $rt['Per_Int']; ?>"><?Php if (count($rs_periodos) > 0) {
		 echo $rt['Mes_Ini']."/".$rt['Ann_Ini']." - ".
			  $rt['Mes_Fin']."/".$rt['Ann_Fin'].' ['.$rt['Eta_Des'].']'; } ?>
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
	$row_rs_etapa= $obBD_con44->getRowConsulta(16, $Per_Int, $obBD_conexion44);
	/** 
	* Consultar las carreras por etapa y periodos 
	*/
	$rs_carreras_etapa=$obBD_con44->getArrayConsulta(102, $row_rs_etapa['Eta_Cod'].'*'.$Per_Int, $obBD_conexion44);
	?>
     <input type="hidden" id="Eta_Cod" name="Eta_Cod" value="<?php echo $row_rs_etapa['Eta_Cod']."*"; ?>" />
	 <select name="Car_Int" id="Car_Int">
       <option value="">Seleccione...</option>
       <?Php foreach($rs_carreras_etapa as $d){ ?>
       <option value="<?Php echo $d['Car_Int'];  ?>"> <?Php echo $d['Car_Nom']; ?> </option>
       <?Php } ?>
     </select>
	 <?Php	
	 exit();
}//Fin del if(isset($ajax_carrera))