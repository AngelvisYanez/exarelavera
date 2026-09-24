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
     * Valida el acceso por dispositivo (OAuth 2.0-lite / MAC).
     *
     * Control de ingreso basado en la MAC del equipo cliente detectada por el
     * servidor (ARP) contra las asignaciones del inventario:
     *  - Sin equipos asignados y MAC detectable: se registra el equipo en el
     *    inventario automaticamente y se asigna al usuario (primer login).
     *  - Con equipos asignados y MAC detectable que NO coincide con la asignada:
     *    se RECHAZA el acceso (error_type 'device').
     *  - Con equipos asignados y MAC detectable que coincide: se vincula ese
     *    equipo y se emiten/validan tokens OAuth.
     *  - MAC no detectable (internet/VPN/localhost): acceso compatible con el
     *    token actual, sin control MAC. En este caso se adjunta ademas la
     *    huella digital del navegador ($fingerprint) como dato de auditoria
     *    (no afecta la decision de acceso ni los cupos del inventario), para
     *    que el monitor de Actividad de Usuarios pueda identificar el equipo
     *    aunque la MAC real no sea detectable fuera de la LAN del servidor.
     *
     * @param int $Usu_Cod
     * @param Class_Log_Conexion_Cnt $obBD
     * @param string $fingerprint Huella digital del navegador (device_fp del login), opcional.
     * @return array {success, message, oauth} oauth: {enforced, dev_cod, inv_dis_cod,
     *               dis_usr_cod, mac, mac_cliente, fingerprint, dis_nom, access_token, token_hash, expires_at}
     */

    function validarDispositivo($Usu_Cod, $obBD = null, $fingerprint = '')
    {
        require_once __DIR__ . '/seguridad_oauth.php';

        $Usu_Cod = (int)$Usu_Cod;
        $con = oauth_conexion_mysqli($obBD);
        if ($Usu_Cod <= 0 || !$con || !class_exists('ExaOAuth')) {
            return array('success' => true, 'message' => '', 'oauth' => array('enforced' => false));
        }

        oauth_asegurar_esquema($con);

        // MAC del equipo con el que se conecta el cliente (deteccion por ARP, lado servidor).
        $macCliente = ExaOAuth::detectarMacCliente();
        // Huella digital del navegador: solo se usa como dato de auditoria de
        // respaldo cuando la MAC no es detectable (acceso remoto/VPN/Internet).
        $fpNorm = ExaOAuth::normalizarFingerprint($fingerprint);
        $oauthBase = array('enforced' => false, 'mac_cliente' => $macCliente, 'fingerprint' => $fpNorm);

        // 1) Usuarios sin dispositivos asignados: accesible. Si la MAC es
        //    detectable se registra el equipo en el inventario y se vincula (Politica 3).
        $asig = $this->getRowConsulta(100, array($Usu_Cod), $obBD);
        $totalAsig = (!empty($asig) && isset($asig['total'])) ? (int)$asig['total'] : 0;
        if ($totalAsig <= 0) {
            if ($macCliente !== '') {
                $auto = ExaOAuth::autoRegistrarDispositivo($con, $Usu_Cod, $macCliente, 'PC');
                if ($auto['success']) {
                    $devCod = oauth_leer_dev_cod();
                    if ($devCod === '') {
                        $devCod = ExaOAuth::generarDevCod();
                    }
                    $accessTok = oauth_leer_access_token();
                    if ($accessTok !== '') {
                        $val = ExaOAuth::validarToken($con, $Usu_Cod, $accessTok, $devCod);
                        if ($val['success']) {
                            return array('success' => true, 'message' => 'Dispositivo autorizado (OAuth).', 'oauth' => array(
                                'enforced' => true,
                                'dev_cod' => $val['dev_cod'],
                                'inv_dis_cod' => $val['inv_dis_cod'],
                                'dis_usr_cod' => $val['dis_usr_cod'],
                                'mac' => $val['mac'],
                                'mac_cliente' => $macCliente,
                                'dis_nom' => $val['dis_nom'],
                                'access_token' => $accessTok,
                                'token_hash' => $val['token_hash'],
                                'expires_at' => $val['expires_at']
                            ));
                        }
                    }
                    $emit = ExaOAuth::emitirTokens($con, $Usu_Cod, $devCod, (int)$auto['inv_dis_cod'], $auto['nom'], $macCliente);
                    if ($emit['success']) {
                        ExaOAuth::setCookies($emit['dev_cod'], $emit['access_token'], $emit['refresh_token'], $emit['expires_at']);
                        return array('success' => true, 'message' => $auto['message'], 'oauth' => array(
                            'enforced' => true,
                            'dev_cod' => $emit['dev_cod'],
                            'inv_dis_cod' => $emit['inv_dis_cod'],
                            'dis_usr_cod' => $emit['dis_usr_cod'],
                            'mac' => $emit['mac'],
                            'mac_cliente' => $macCliente,
                            'dis_nom' => $emit['dis_nom'],
                            'access_token' => $emit['access_token'],
                            'token_hash' => $emit['token_hash'],
                            'expires_at' => $emit['expires_at'],
                            'issued' => true,
                            'auto_registered' => $auto['created']
                        ));
                    }
                }
            }
            return array('success' => true, 'message' => 'Acceso sin control de dispositivo (sin equipos asignados).', 'oauth' => $oauthBase);
        }

        $devCod = oauth_leer_dev_cod();
        $accessTok = oauth_leer_access_token();

        // 2) MAC detectable: acceso estricto por MAC (Politica 1).
        if ($macCliente !== '') {
            $match = ExaOAuth::buscarMacEnAsignaciones($con, $Usu_Cod, $macCliente);
            if (!$match || empty($match['InvDis_Cod'])) {
                return array('success' => false,
                    'message' => 'El acceso desde este dispositivo no está autorizado. Puede que esté intentando entrar desde otro equipo o que su usuario ya esté ingresado desde otro dispositivo. Para más información, contacte a soporte.',
                    'oauth' => array('enforced' => true, 'error' => 'mac_no_autorizada', 'dev_cod' => $devCod, 'mac_cliente' => $macCliente));
            }
            $invDisCod = (int)$match['InvDis_Cod'];
            $invDisNom = isset($match['InvDis_Nom']) ? (string)$match['InvDis_Nom'] : '';
            $invDisTipo = isset($match['InvDis_Tipo']) ? (string)$match['InvDis_Tipo'] : 'PC';
            $macEquipo = isset($match['mac_address']) ? (string)$match['mac_address'] : $macCliente;

            // Con token: se valida normalmente contra el vinculo emitido.
            if ($accessTok !== '') {
                $val = ExaOAuth::validarToken($con, $Usu_Cod, $accessTok, $devCod);
                if ($val['success']) {
                    return array('success' => true, 'message' => 'Dispositivo autorizado (OAuth).', 'oauth' => array(
                        'enforced' => true,
                        'dev_cod' => $val['dev_cod'],
                        'inv_dis_cod' => $val['inv_dis_cod'],
                        'dis_usr_cod' => $val['dis_usr_cod'],
                        'mac' => $val['mac'],
                        'mac_cliente' => $macCliente,
                        'dis_nom' => $val['dis_nom'],
                        'access_token' => $accessTok,
                        'token_hash' => $val['token_hash'],
                        'expires_at' => $val['expires_at']
                    ));
                }
                return array('success' => false, 'message' => $val['message'], 'oauth' => array(
                    'enforced' => true, 'error' => isset($val['error']) ? $val['error'] : 'invalid_token',
                    'dev_cod' => $devCod, 'mac_cliente' => $macCliente
                ));
            }

            // Sin token: vincular este navegador al equipo cuya MAC coincide.
            if ($devCod === '') {
                $devCod = ExaOAuth::generarDevCod();
            }
            $existente = ExaOAuth::buscarDisUsr($con, $Usu_Cod, $devCod);
            $rCnt = @mysqli_query($con, "SELECT COUNT(*) AS c FROM `dispositivos_usuario`
                WHERE `InvDis_Cod` = {$invDisCod} AND `Usu_Cod` = {$Usu_Cod} AND `DisUsr_Est` = 'A'");
            $cupos = ExaOAuth::obtenerCupos($con, $invDisCod);
            $ocupados = ($rCnt) ? (int)mysqli_fetch_assoc($rCnt)['c'] : 1;
            $permite = false;
            if ($existente && !empty($existente['DisUsr_Cod'])) {
                $permite = true;
            } elseif ($cupos !== null && $cupos > $ocupados) {
                $permite = true;
            }
            if (!$permite) {
                return array('success' => false, 'message' => 'No hay cupos libres en los dispositivos asignados. Contacte al administrador de inventario.', 'oauth' => array(
                    'enforced' => true, 'error' => 'cupo_lleno', 'dev_cod' => $devCod, 'mac_cliente' => $macCliente
                ));
            }
            $emit = ExaOAuth::emitirTokens($con, $Usu_Cod, $devCod, $invDisCod, $invDisNom, $macEquipo);
            if (!$emit['success']) {
                return array('success' => false, 'message' => $emit['message'], 'oauth' => array(
                    'enforced' => true, 'error' => isset($emit['error']) ? $emit['error'] : 'issue_failed', 'dev_cod' => $devCod, 'mac_cliente' => $macCliente
                ));
            }
            ExaOAuth::setCookies($emit['dev_cod'], $emit['access_token'], $emit['refresh_token'], $emit['expires_at']);
            return array('success' => true, 'message' => 'Dispositivo registrado y autorizado (OAuth).', 'oauth' => array(
                'enforced' => true,
                'dev_cod' => $emit['dev_cod'],
                'inv_dis_cod' => $emit['inv_dis_cod'],
                'dis_usr_cod' => $emit['dis_usr_cod'],
                'mac' => $emit['mac'],
                'mac_cliente' => $macCliente,
                'dis_nom' => $emit['dis_nom'],
                'access_token' => $emit['access_token'],
                'token_hash' => $emit['token_hash'],
                'expires_at' => $emit['expires_at'],
                'issued' => true
            ));
        }

        // 3) MAC no detectable (internet/VPN/localhost): Politica 2, acceso con
        //    el token actual, sin control MAC.
        if ($accessTok !== '') {
            $val = ExaOAuth::validarToken($con, $Usu_Cod, $accessTok, $devCod);
            if ($val['success']) {
                return array('success' => true, 'message' => 'Dispositivo autorizado (OAuth).', 'oauth' => array(
                    'enforced' => true,
                    'dev_cod' => $val['dev_cod'],
                    'inv_dis_cod' => $val['inv_dis_cod'],
                    'dis_usr_cod' => $val['dis_usr_cod'],
                    'mac' => $val['mac'],
                    'mac_cliente' => '',
                    'fingerprint' => $fpNorm,
                    'dis_nom' => $val['dis_nom'],
                    'access_token' => $accessTok,
                    'token_hash' => $val['token_hash'],
                    'expires_at' => $val['expires_at']
                ));
            }
            return array('success' => false, 'message' => $val['message'], 'oauth' => array(
                'enforced' => true, 'error' => isset($val['error']) ? $val['error'] : 'invalid_token', 'dev_cod' => $devCod, 'fingerprint' => $fpNorm
            ));
        }

        if ($devCod === '') {
            $devCod = ExaOAuth::generarDevCod();
        }

        $existente = ExaOAuth::buscarDisUsr($con, $Usu_Cod, $devCod);
        $invDisCod = 0;
        $invDisNom = '';
        $mac = '';

        if ($existente && !empty($existente['InvDis_Cod'])) {
            $invDisCod = (int)$existente['InvDis_Cod'];
            $dis = ExaOAuth::getDispositivo($con, $invDisCod);
            $invDisNom = $dis ? (isset($dis['InvDis_Nom']) ? $dis['InvDis_Nom'] : '') : (isset($existente['DisUsr_Nom']) ? $existente['DisUsr_Nom'] : '');
            $mac = $dis ? (isset($dis['mac_address']) ? $dis['mac_address'] : '') : (isset($existente['DisUsr_Mac']) ? $existente['DisUsr_Mac'] : '');
        } else {
            $libre = ExaOAuth::findFreeCupo($con, $Usu_Cod, 'PC');
            if (empty($libre)) {
                $libre = ExaOAuth::findFreeCupo($con, $Usu_Cod, 'MOVIL');
            }
            if (empty($libre)) {
                return array('success' => false, 'message' => 'No hay cupos libres en los dispositivos asignados. Contacte al administrador de inventario.', 'oauth' => array(
                    'enforced' => true, 'error' => 'cupo_lleno', 'dev_cod' => $devCod
                ));
            }
            $invDisCod = (int)$libre['InvDis_Cod'];
            $invDisNom = isset($libre['InvDis_Nom']) ? $libre['InvDis_Nom'] : '';
            $mac = isset($libre['mac_address']) ? $libre['mac_address'] : '';
        }

        $emit = ExaOAuth::emitirTokens($con, $Usu_Cod, $devCod, $invDisCod, $invDisNom, $mac);
        if (!$emit['success']) {
            return array('success' => false, 'message' => $emit['message'], 'oauth' => array(
                'enforced' => true, 'error' => isset($emit['error']) ? $emit['error'] : 'issue_failed', 'dev_cod' => $devCod
            ));
        }

        ExaOAuth::setCookies($emit['dev_cod'], $emit['access_token'], $emit['refresh_token'], $emit['expires_at']);

        return array('success' => true, 'message' => 'Dispositivo registrado y autorizado (OAuth).', 'oauth' => array(
            'enforced' => true,
            'dev_cod' => $emit['dev_cod'],
            'inv_dis_cod' => $emit['inv_dis_cod'],
            'dis_usr_cod' => $emit['dis_usr_cod'],
            'mac' => $emit['mac'],
            'mac_cliente' => '',
            'fingerprint' => $fpNorm,
            'dis_nom' => $emit['dis_nom'],
            'access_token' => $emit['access_token'],
            'token_hash' => $emit['token_hash'],
            'expires_at' => $emit['expires_at'],
            'issued' => true
        ));
    }
}
