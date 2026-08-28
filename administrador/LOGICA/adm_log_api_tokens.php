<?php
/**
 * Capa Lógica y de Datos para el Módulo de Gestión de API Tokens
 *
 * @package administrador.LOGICA
 * @author EXA Contable
 * @version 2.0
 */

require_once __DIR__ . '/../../DATA/MysqlConexion.php';
require_once __DIR__ . '/../../DATA/MysqlDatos.php';
require_once __DIR__ . '/adm_sql_api_tokens.php';
require_once __DIR__ . '/../../classes/APITokenManager.php';

/**
 * Conexión a la base de datos para el módulo de tokens (conecta a exa_master)
 */
class Class_Log_Conexion_Tok extends MysqlConexion
{
    public function __construct($bdd = 'exa_master')
    {
        parent::__construct($bdd);
    }
}

/**
 * Capa de datos y operaciones para Tokens de API
 */
class Class_Log_Datos_Tok extends MysqlDatos
{
    public $sentencias = '';
    public $codigos = '';

    /**
     * Realiza una consulta SQL mediante el repositorio de sentencias
     */
    public function consultasobBD($sen_sql, $param = array(), $obBD = null)
    {
        if ($obBD === null) {
            $obBD = new Class_Log_Conexion_Tok();
        }
        $Par_Sql = $this->parametros($param);
        $sql = sentencias_tok($sen_sql, $Par_Sql);
        return $this->consulta($sql, $obBD->conexion);
    }

    /**
     * Realiza una operación de escritura (Insert, Update, Delete)
     */
    public function operacionobBD($sen_sql, $param = array(), $obBD = null)
    {
        if ($obBD === null) {
            $obBD = new Class_Log_Conexion_Tok();
        }
        $Par_Sql = $this->parametros($param);
        $Query = sentencias_tok($sen_sql, $Par_Sql);
        $this->sentencias .= $Query . '*';
        $result = $this->grabarv_registros($Query, $obBD->conexion);
        $this->codigos .= $this->insercionid($obBD->conexion) . '*';
        return $result;
    }

    /**
     * Devuelve una fila asociativa
     */
    public function getRowConsulta($sen_sql, $param = array(), $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        if ($result) {
            return $this->fetch_assoc($result);
        }
        return false;
    }

    /**
     * Devuelve un array de filas
     */
    public function getArrayConsulta($sen_sql, $param = array(), $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $rows = array();
        if ($result) {
            while ($row = $this->fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Listar todos los tokens registrados
     */
    public function listarTokens($empCod = null, $estado = null)
    {
        $param = array($empCod ? (string)$empCod : '', $estado ? (string)$estado : '');
        return $this->getArrayConsulta(1, $param);
    }

    /**
     * Obtener un token por su ID
     */
    public function obtenerToken($tokId)
    {
        return $this->getRowConsulta(2, array((string)$tokId));
    }

    /**
     * Obtener permisos asignados a un token
     */
    public function obtenerPermisos($tokId)
    {
        return $this->getArrayConsulta(4, array((string)$tokId));
    }

    /**
     * Listar empresas disponibles
     */
    public function listarEmpresas()
    {
        return $this->getArrayConsulta(5, array());
    }

    /**
     * Revocar un token por su ID
     */
    public function revocarToken($tokId)
    {
        $mgr = new APITokenManager();
        return $mgr->revoke((int)$tokId);
    }

    /**
     * Eliminar token permanentemente
     */
    public function eliminarToken($tokId)
    {
        $obBD = new Class_Log_Conexion_Tok();
        $this->operacionobBD(6, array((string)$tokId), $obBD);
        return $this->operacionobBD(8, array((string)$tokId), $obBD);
    }

    /**
     * Guardar/actualizar permisos de un token
     */
    public function guardarPermisos($tokId, $permisos = array())
    {
        $mgr = new APITokenManager();
        return $mgr->setPermisos((int)$tokId, $permisos);
    }
}
