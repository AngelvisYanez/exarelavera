<?php
require_once($APP_REAL_PATH.'/auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH."/administrador/LOGICA/sql.php");
// require_once('../../auditoria/LOGICA/aud_log_auditoria.php'); 
// require_once("sql.php");

/*********************MYSQL****************************/
/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Adm extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_Adm extends MysqlDatos{
    public $codigos='';
    public $sentencias='';
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function consultasobBD($sen_sql,$param,$obBD=null,$echo=0)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_adm($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function operacionobBD($sen_sql,$param,$obBD=null,$echo=0)
	{
		$Query = sentencias_adm($sen_sql,$this->parametros($param));
		$this->sentencias .= $Query.'*';
		$result = $this->grabarv_registros($Query, $obBD->conexion);
		$this->codigos .= $this->insercionid($obBD->conexion).'*';
		return $result;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD=null,$echo=0)
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
	function getArrayConsulta($sen_sql,$param,$obBD=null,$echo=0)
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

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/








/* Funcion que devuelve un arreglo de los reportes del proceso */
function reportes($pagina, $obBD_con1, $obBD_conexion)
{
	$pag = explode("/", $pagina);
	$rs_proceso = $obBD_con1->consulta(sentencias_adm(12, $obBD_con1->parametros($pag[count($pag)-1])), 
							$obBD_conexion->conexion);
	$row_rs_proceso = $obBD_con1->registros();
	$total_rs_proceso = $obBD_con1->numregistros();
		
	$rs_reporte = $obBD_con1->consulta(sentencias_adm(13, $obBD_con1->parametros($row_rs_proceso['Pcs_Cod'])), 
							$obBD_conexion->conexion);
	$row_rs_reporte = $obBD_con1->registros();
	$total_rs_reporte = $obBD_con1->numregistros();
	
	$i=0;
	do{
		$i++;
		$reporte[$i] = $row_rs_reporte['Pcs_Nom'];		
	}while($row_rs_reporte = $obBD_con1->registros());
	
	return $reporte;
}//Fin del function reportes($pagina, $obBD_con1, $obBD_conexion)

/* Funcion para el cargado del menu */
function menu($obBD_con1, $obBD_conexion)
{
	
}//Fin del function menu()	
?>