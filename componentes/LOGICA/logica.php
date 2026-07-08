<?php
	require_once ("../../DATA/DAC.php");
	require_once ("sql.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Com extends Class_Mysql{
//function Class_Log_Conexion_Con($bd = "macros", $host = "localhost", $user = "root", $pass = "root"){
//		$this->BaseDatos = $bd;
//		$this->Servidor = $host;
//		$this->Usuario = $user;
//		$this->Clave = $pass;
//		
//		$this->conectar();
//	}
}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Com extends Class_Datos{
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @return result si existen datos de retorno
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_com($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @return result si existen datos de retorno
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_est($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @return array $row fila de datos
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		$this->liberar();
		
		return $row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @return array $array arreglo de datos asociados
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_pac $obDT para la abtraccion de los datos
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$array = array();
		
		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}
		
		$this->free_result($result);
		$this->liberar();
		
		return $array;
	}

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param string $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{		
		$this->inicio_transaccion($obBD->conexion);
		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);		
	}
			
}//Fin de clase Class_Log_Conexion
?>