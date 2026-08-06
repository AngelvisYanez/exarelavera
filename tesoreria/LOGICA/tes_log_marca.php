<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-04-16
 *
 * @package tesoreria.LOGICA
 */

require_once ("../../DATA/DAC.php");
require_once("tes_sql_marca.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Mar extends Class_Mysql{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Mar extends Class_Datos{
	
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_mar($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_mar($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		
		return is_array($row) ? $row : array();
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
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
		
		return $array;
	}

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Cli $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{		
		$this->inicio_transaccion($obBD->conexion);
		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);		
	}
	

}

/*
* Ajax para validar la existencia de una marca
*/
if (isset($ajax_mar))
{
	/**
	 * objeto para la conexion
	 * @var Class_Log_Conexion_Mar
	 */
	$obBD_conexion = new Class_Log_Conexion_Mar;
	/**
	 * objeto para consultas
	 * @var Class_Log_Datos_Mar
	 */
	$obBD_con1 =  new Class_Log_Datos_Mar;
	
	if (!isset($ajax_mod))
	{
		/** 
		* Consultar si existe el nombre de marca  
		*/
		$row_rs_con_mar = $obBD_con1->getArrayConsulta(1, strtoupper($Mar_Des.'*'.$Ses_Emp_Cod), $obBD_conexion);
		
		if (count($row_rs_con_mar) > 0)
		{ ?>
			<input name="Mar_Des" type="text" id="Mar_Des" value="" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Mar_Des=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	¡La marca: <?php echo strtoupper($Mar_Des); ?> ya existe!  
		<?php
		}
		else
		{ ?>
			<input name="Mar_Des" type="text" id="Mar_Des" value="<?Php echo $Mar_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Mar_Des=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
		<?php	
		}
	}
	else
	{
		/** 
		* Consultar si existe el nombre de marca  omitiendo la marca actual
		*/
		$row_rs_con_mar = $obBD_con1->getArrayConsulta(5, strtoupper($Mar_Des.'*'.$Ses_Emp_Cod.'*'.$Mar_Cod), $obBD_conexion);
		
		if (count($row_rs_con_mar) > 0)
		{ ?>
			<input name="Mar_Des" type="text" id="Mar_Des" value="" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&Mar_Des='+this.value+'&Mar_Cod=<?Php echo $Mar_Cod; ?>','div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	¡La marca: <?php echo strtoupper($Mar_Des); ?> ya existe!  
		<?php
		}
		else
		{ ?>
			<input name="Mar_Des" type="text" id="Mar_Des" value="<?Php echo $Mar_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&Mar_Des='+this.value+'&Mar_Cod=<?Php echo $Mar_Cod; ?>','div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
		<?php	
		}		
	}
	
	/*
	* Cierra las conexiones
	*/	
	$obBD_conexion->cerrar();	
	exit();
}
?>