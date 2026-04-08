<?Php 
/**
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_prod_plan.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Cons extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 */
class Class_Log_Datos_Cons extends MysqlDatos{	
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
            //
		$Par_Sql= $this->parametros($param);
               // var_dump($Par_Sql);
		return $this->consulta(sentencias_cons($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_cons($sen_sql,$Par_Sql), $obBD->conexion);
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

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Cli $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD)
	{		
		$this->inicio_transaccion($obBD->conexion);		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);			
		$this->fin_transaccion($obBD->conexion);		
	}

}
