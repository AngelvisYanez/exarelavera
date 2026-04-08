<?Php
/**
 * Ajax que permite cargar:
 * Modalidad	=	Todas
 * Etapa		=	Todas
*/

require_once('../../componentes/LOGICA/logica.php');

/**
 * objeto para conexion
 * @var Class_Log_Datos_Com
 */
$obBD_conexion3 = new Class_Log_Conexion_Com;
/**
 * objeto para consultas
 * @var Class_Log_Datos_Com
 */
$obBD_con3 =  new  Class_Log_Datos_Com;

/**
* Cargar datos con AJAX  
*/
if(isset($ajax_mod_cod))
{
	/**
	* Cargado de etapas 
	*/
	$rs_etapas = $obBD_con3->getArrayConsulta(3, '', $obBD_conexion);
	?>
	<select name="Eta_Cod" id="Eta_Cod">
		<option value="">Seleccione...</option>
		<?Php 
		foreach($rs_etapas as $row_rs_etapas)
		{ ?>
		<option value="<?Php echo $row_rs_etapas['Eta_Cod']; ?>"><?Php echo $row_rs_etapas['Eta_Des'];  ?></option>
		<?Php 
		} ?>
	 </select>
<?Php
	exit();
}//Fin del if(isset($ajax_mod_cod)) ?>