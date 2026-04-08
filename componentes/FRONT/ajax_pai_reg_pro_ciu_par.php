<?Php 
require_once('../../componentes/LOGICA/logica.php');
/**
 * Obtener el combo de datos de regiones segun el pais seleccionado
 * @var $Pas_Cod Codigo principal del pais
 */
if(isset($ajax_pai_cod))
{
	/**
	 * objeto para la consultas
	 * @var Class_Log_Datos_Com
	 */
	$obBD_con1 = new Class_Log_Datos_Com;
	/**
	 * objeto para la conexion
	 * @var Class_Log_Conexion_Com
	 */
	$obBD_conexion = new Class_Log_Conexion_Com;
	/**
	 * Cargado de regiones 
	 */
	$arrRegiones = $obBD_con1->getArrayConsulta(108, $Pas_Cod, $obBD_conexion);
	?>
    <select name="Reg_Cod" id="Reg_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_pro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=' + this.value,'div_provincias')"   >
       <option value="">Seleccione...</option>
       <?Php 
       foreach($arrRegiones as $row_rs_regiones){ ?>
       <option value="<?Php echo $row_rs_regiones['Reg_Cod']; ?>"><?Php echo $row_rs_regiones['Reg_Nom'];?></option>
       <?Php
		}
	   ?>
    </select>
	<?Php
 	$obBD_con1->liberar();
 	$obBD_conexion->cerrar();
	exit();
}

/**
 * Obtener el combo de datos de provincias segun la region seleccionada
 * @var $Reg_Cod Codigo principal de la region
 */
if(isset($ajax_pro_cod)) 
{
	/**
	 * objeto para la consultas
	 * @var Class_Log_Datos_Com
	 */
	$obBD_con1 = new Class_Log_Datos_Com;
	/**
	 * objeto para la conexion
	 * @var Class_Log_Conexion_Com
	 */
	$obBD_conexion = new Class_Log_Conexion_Com;
	
   /**
    * Cargado de provincias 
    */
	$arrProvincias = $obBD_con1->getArrayConsulta(107, $Reg_Cod, $obBD_conexion);
?>
	
	 <select name="Pro_Cod" id="Pro_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_reg_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Pro_Cod=' + this.value,'div_ciudades')"    >
     	<option value="">Seleccione...</option>
        <?Php foreach($arrProvincias as $row_rs_provincias) { ?>
        <option value="<?Php echo $row_rs_provincias['Pro_Cod']; ?>"><?Php echo $row_rs_provincias['Pro_Nom'];  ?></option>
        <?Php
		}?>
     </select>
	<?Php
	$obBD_con1->liberar();
 	$obBD_conexion->cerrar();
	exit();
}

/**
 * Obtener el combo de datos de ciudades segun la provincia seleccionada
 * @var $Pro_Cod Codigo principal de la provincia
 */
if(isset($ajax_reg_cod)) 
{
	/**
	 * objeto para la consultas
	 * @var Class_Log_Datos_Com
	 */
	$obBD_con1 = new Class_Log_Datos_Com;
	/**
	 * objeto para la conexion
	 * @var Class_Log_Conexion_Com
	 */
	$obBD_conexion = new Class_Log_Conexion_Com;
	
   /**
    * Cargado de ciudades 
    */
	$arrCiudades = $obBD_con1->getArrayConsulta(109, $Pro_Cod, $obBD_conexion);?>

	 <select name="Ciu_Cod" id="Ciu_Cod" style="text-transform:uppercase" onChange="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_parro_cod=1&Pas_Cod=<?Php echo $Pas_Cod; ?>&Pro_Cod=<?Php echo $Pro_Cod; ?>&Reg_Cod=<?Php echo $Reg_Cod; ?>&Ciu_Cod=' + this.value,'div_parroquias')"    >
        <option value="">Seleccione...</option>
        <?Php foreach($arrCiudades as $row_rs_ciudades){ ?>
        <option value="<?Php echo $row_rs_ciudades['Ciu_Cod']; ?>"><?Php echo $row_rs_ciudades['Ciu_Des'];  ?></option>
        <?Php 
		}
	?>
     </select>
	<?Php
	$obBD_con1->liberar();
 	$obBD_conexion->cerrar();
	exit();
}

/**
 * Obtener el combo de datos de parroquias segun la ciudad seleccionada
 * @var $Ciu_Cod Codigo principal de la ciudad
 */
if(isset($ajax_parro_cod)) 
{   
	/**
	 * objeto para la consultas
	 * @var Class_Log_Datos_Com
	 */
	$obBD_con1 = new Class_Log_Datos_Com;
	/**
	 * objeto para la conexion
	 * @var Class_Log_Conexion_Com
	 */
	$obBD_conexion = new Class_Log_Conexion_Com;
	
	$arrParroquia = $obBD_con1->getArrayConsulta(110, $Ciu_Cod, $obBD_conexion);
	?>
	
	 <select name="Par_Cod" id="Par_Cod" style="text-transform:uppercase"    >
        <option value="">Seleccione...</option>
        <?Php foreach($arrParroquia as $row_rs_parroquias) { ?>
        <option value="<?Php echo $row_rs_parroquias['Par_Cod']; ?>"><?Php echo $row_rs_parroquias['Par_Nom'];?></option>
        <?Php 
		}
		?>
      </select>
	<?Php
	$obBD_con1->liberar();
 	$obBD_conexion->cerrar();
	exit();
}

?>


