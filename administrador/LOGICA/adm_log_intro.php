<?php
/**
 * Logica de las paginas que tienen que ver con el distributivo
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-11-25
 *
 * @package administrador.LOGICA
 */

require_once("../../DATA/MysqlConexion.php");
require_once("../../DATA/MysqlDatos.php");
require_once("adm_sql_intro.php");

class Class_Log_Conexion_Log extends MysqlConexion {
	function __construct($bd = "servicios", $host = "localhost", $user = "root", $pass = ""){
		parent::__construct($host, $user, $pass, $bd);
	}
}

class Class_Log_Datos_Log extends MysqlDatos {
	function consultasobBD($sen_sql, $param, $obBD = null)
	{
		$Par_Sql = $this->parametros($param);
		return $this->consulta(sentencias_log($sen_sql, $Par_Sql), $obBD->conexion);
	}

	function getRowConsulta($sen_sql, $param, $obBD = null)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);
		if (!($result instanceof \mysqli_result)) return array();
		$row = $this->fetch_assoc($result);
		return is_array($row) ? $row : array();
	}

	function getArrayConsulta($sen_sql, $param, $obBD = null)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);
		if (!($result instanceof \mysqli_result)) return array();
		$array = array();
		while ($row_rs = $this->fetch_assoc($result)) {
			$array[] = $row_rs;
		}
		return $array;
	}

	function insertUpdateDelete($sen_sql, $param, $obBD = null)
	{
		$this->inicio_transaccion($obBD->conexion);
		$this->operacionobBD($sen_sql, $param, $obBD);
		$cod = $this->insercionid($obBD->conexion);
		$this->fin_transaccion($obBD->conexion);
		return $cod;
	}
}
