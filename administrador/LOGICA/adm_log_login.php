<?Php 
/**
 * Logica de las paginas que tienen que ver con el distributivo
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-11-25
 *
 * @package administrador.LOGICA
 */

require_once ("DATA/MysqlConexion.php");
require_once ("DATA/MysqlDatos.php");
require_once("adm_sql_login.php");

/**
/* Clase para conexion a la capa de acceso a datos
*/

class Class_Log_Conexion_Log extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
/* Clase para los datos a la capa de acceso a datos
*/

class Class_Log_Datos_Log extends MysqlDatos{
	/**
	* Realiza una consulta en la base de datos.
	*
	* @return result si existen datos de retorno
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	*/
	function consultasobBD($sen_sql,$param,$obBD=null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_log($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @return result si existen datos de retorno
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 */
	function operacionobBD($sen_sql,$param,$obBD=null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_log($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos
	 * @return array $row fila de datos
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 */
	function getRowConsulta($sen_sql,$param,$obBD=null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		
		return $row;//!=null?array_map('htmlentities', $row):$row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos
	 * @return array $array arreglo de datos asociados
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD=null)
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
	 * @param Class_Log_Datos_Con $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param,$obBD=null)
	{
		/**
		 * Inicio de la transaccion
		 */
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * Ejecutar sentencia
		 */
		$this->operacionobBD($sen_sql,$param,$obBD);
	
		/**
		 * Codigo de autoincremento
		 * @var int
		 */
		$cod = $this->insercionid($obBD->conexion);
		/**
		 * Finalizar transaccion
		 */
		$this->fin_transaccion($obBD->conexion);
		
		return $cod;
	}
	
}//Fin de clase Class_Log_Conexion

?>