<?Php 
/**
 * Logica de las paginas que tienen que ver con monitoreo
 *
 * @author car.87cod :)
 * @version 1.0
 *
 * @package administrador.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("aud_sql_monitoreo.php");

/**
 * Clase para conexion a la capa de acceso a datos
 *
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Conexion extends MysqlConexion{

}

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 * @package administrador.LOGICA
 */
class Class_Log_Datos extends MysqlDatos{
	
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function consultasobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias($sen_sql,$Par_Sql), $obBD->conexion);
	}
	

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);

		$row =  $this->fetch_assoc($result);

		$this->free_result($result);

		return $row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */
	function getArrayConsulta($sen_sql,$param,$obBD)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);

		$array = array();

		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}

		$this->free_result($result);

		return $array;
	}

}
?>