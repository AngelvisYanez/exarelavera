<?php
/**
 * Logica del control del acceso del sistema para bases de datos distribuidas
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2013-JUN-20
 *
 * @package administrador.LOGICA
 */ 
require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../DATA/MysqlDatos.php');
if (file_exists(__DIR__ . '/../../auditoria/LOGICA/aud_log_auditoria.php')) {
    require_once(__DIR__ . '/../../auditoria/LOGICA/aud_log_auditoria.php');
}
require_once(__DIR__ . '/adm_sql_control_1.0.php');

/**
 * Clase para conexion a la capa de acceso a datos
 *
 * @author Lewis Chimarro
 *
 * @package administrador.LOGICA
 */
class Class_Log_Conexion_Cnt extends MysqlConexion{

}

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 * @package administrador.LOGICA
 */
class Class_Log_Datos_Cnt extends MysqlDatos{
	
	/**
	 * Guardara las sql concatenadas con *
	 * de Insert, Update, Delete
	 * @var string
	 */
	var $sentencias = '';
	
	/**
	 * guarda los codigos de autoincrementos en los insert
	 * concatenados con *
	 * @var string
	 */
	var $codigos = '';

	/**
	 * Numero de la tabla que se encontro resultados
	 * 1 - 5: estudiante
	 * 2 - 6: cliente
	 * 3 - 7: proveedor
	 * 4 - 8: personal
	 * @var int
	 */
	var $id_tabla;
	
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cnt $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function consultasobBD($sen_sql,$param,$obBD=null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_cnt($sen_sql,$Par_Sql), $obBD ? $obBD->conexion : null);
	}

	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cnt $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function operacionobBD($sen_sql,$param,$obBD=null)
	{
		$Query = sentencias_cnt($sen_sql,$this->parametros($param));
		$this->sentencias .= $Query.'*';
		$result = $this->grabarv_registros($Query, $obBD ? $obBD->conexion : null);
		$this->codigos .= $this->insercionid($obBD ? $obBD->conexion : null).'*';
		return $result;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cnt $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD=null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);

		$row =  $this->fetch_assoc($result);

		return $row;
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cnt $obBD para realizar la coneccion correspondiente
	 * @return array $array arreglo de datos asociados
	 */
	function getArrayConsulta($sen_sql,$param,$obBD=null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$a = array();
		
		while($row_rs = $this->fetch_assoc($result))
		{
			$a[]=$row_rs;
		}

		$this->free_result($result);

		return $a;
	}

    /**
     * Valida el acceso por dispositivo (IP / Token / Cookies / etc.)
     * Por defecto permite acceso si no hay restricciones
     */
    function validarDispositivo($Usu_Cod, $obBD = null)
    {
        return array('success' => true, 'message' => '');
    }
}
