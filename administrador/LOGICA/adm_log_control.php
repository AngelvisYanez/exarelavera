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
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once('adm_sql_control_1.0.php');

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
		return $this->consulta(sentencias_cnt($sen_sql,$Par_Sql), $obBD->conexion);
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
		$result = $this->grabarv_registros($Query, $obBD->conexion);
		$this->codigos .= $this->insercionid($obBD->conexion).'*';
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

		$this->free_result($result);

		return $row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cnt $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cnt $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
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
	 * graba en la base de datos auditoria
	 * @param string $Request_Uri pagina donde se estan modificando valores
	 * @param number $Ses_Usu_Cod codigo del usuario
	 * @param Class_Log_Conexion_Cnt $obBD_conexion
	 * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
	 */
	function grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $obBD_conexion=null){
		if($this->Error == 0){
			$objAud = new Class_Log_Datos_Aud;
				
			$aux = explode('*', $objAud->grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));
	
			foreach ($aux as $row){
				$this->grabarv_registros($row,$obBD_conexion->conexion);
				if($this->Error > 0){
					return $this->Error;
				}
			}
			$objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
		}else{
			return $this->Error;
		}
	}

	/**
	 * Obtiene o genera un device_id único para el navegador
	 * @return string
	 */
	function getOrCreateDeviceId() {
		if (isset($_COOKIE['device_id']) && !empty($_COOKIE['device_id'])) {
			return $_COOKIE['device_id'];
		}
		
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
		$time = time();
		$rand = mt_rand(1000, 9999);
		$deviceId = md5($userAgent . $time . $rand);
		
		// Cookie por 10 años
		setcookie('device_id', $deviceId, time() + (86400 * 365 * 10), "/");
		$_COOKIE['device_id'] = $deviceId; // Asegurar disponibilidad inmediata
		
		return $deviceId;
	}

	/**
	 * Valida si el dispositivo está autorizado para el usuario
	 * @param int $usuario_id
	 * @param object $obBD_conexion
	 * @return array array('success' => bool, 'message' => string)
	 */
	function validarDispositivo($usuario_id, $obBD_conexion) {
		$deviceId = $this->getOrCreateDeviceId();
		
		// 1. Verificar si el usuario tiene restricción (equipos asignados en usuario_inventario)
		$check_asig = $this->getRowConsulta(100, $usuario_id, $obBD_conexion);
		$total_allowed = $check_asig ? (int)$check_asig['total'] : 0;
		
		if ($total_allowed == 0) {
			return array('success' => true, 'message' => 'Acceso libre (sin equipos asignados)');
		}
		
		// 2. Verificar si el navegador actual ya está vinculado
		$device = $this->getRowConsulta(101, $deviceId . '*' . $usuario_id, $obBD_conexion);
		
		if ($device) {
			// Si ya existe, solo actualizamos fecha de último acceso
			$this->operacionobBD(104, $deviceId . '*' . $usuario_id, $obBD_conexion);
			return array('success' => true, 'message' => 'Dispositivo autorizado');
		}
		
		// 3. Si el navegador es NUEVO, intentar vincularlo a un cupo libre del panel
		// Detectamos el tipo de dispositivo actual
		$ua_user = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
		$is_mobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua_user);
		$current_type = $is_mobile ? 'MOVIL' : 'PC';
		
		// Lógica para obtener IP en formato IPv4
		$ip_user = '0.0.0.0';
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_user = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_user = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip_user = $_SERVER['REMOTE_ADDR'];
		}
		
		if ($ip_user == '::1') $ip_user = '127.0.0.1';

		// Buscamos una MAC asignada que coincida con el tipo de equipo (PC o MOVIL)
		$free_slot = $this->getRowConsulta(105, $usuario_id . '*' . $current_type, $obBD_conexion);
		
		if ($free_slot && isset($free_slot['InvDis_Cod'])) {
			// Capturamos metadatos del sistema
			$inv_cod = $free_slot['InvDis_Cod'];
			$inv_nom = $free_slot['InvDis_Nom'];
			
			// ¡Hay un cupo libre! Vinculamos este navegador a ese equipo físico
			$params = $usuario_id . '*' . $deviceId . '*' . $inv_cod . '*' . $inv_nom . '*' . $ip_user . '*' . $ua_user;
			$this->operacionobBD(106, $params, $obBD_conexion);
			
			return array('success' => true, 'message' => 'Nuevo navegador vinculado exitosamente al equipo (' . $current_type . '): ' . $inv_nom);
		}
		
		// 4. NUEVO HÍBRIDO: Validar si la IP y el TIPO coinciden con un cupo ya asignado (ocupado)
		// Si es así, "hereda" ese cupo (reemplaza el Dev_Cod viejo por el nuevo)
		$shared_slot = $this->getRowConsulta(107, $usuario_id . '*' . $ip_user . '*' . $current_type, $obBD_conexion);
		
		if ($shared_slot && isset($shared_slot['DisUsr_Cod'])) {
			$dis_usr_cod = $shared_slot['DisUsr_Cod'];
			$inv_nom = $shared_slot['InvDis_Nom'];
			
			// Actualizamos el registro existente con la nueva huella (Dev_Cod)
			$this->operacionobBD(108, $deviceId . '*' . $ua_user . '*' . $dis_usr_cod, $obBD_conexion);
			
			return array('success' => true, 'message' => 'Cupo compartido por IP en equipo (' . $current_type . '): ' . $inv_nom);
		}

		// 5. Si no hay cupos libres del TIPO correcto ni IP compartida, BLOQUEAR
		$error_msg = ($current_type == 'MOVIL') 
			? 'Acceso denegado: Este usuario no tiene permitido el acceso desde dispositivos MÓVILES.' 
			: 'Acceso denegado: Este usuario no tiene permitido el acceso desde computadoras (PC).';
			
		return array('success' => false, 'message' => $error_msg . ' Contacte al administrador para asignar el equipo correcto.');
	}
	
}
?>