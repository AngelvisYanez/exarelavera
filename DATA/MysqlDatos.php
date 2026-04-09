<?php

use \Exception;

/**
 *  Clase para conexion con MySql
 *  @author Lewis
 *  Modificado 13/06/2017
 *  @author Erik Niebla
 */
if (isset($APP_REAL_PATH)) {
    if (file_exists($APP_REAL_PATH . "/auditoria/LOGICA/aud_log_auditoria.php")) require_once($APP_REAL_PATH . "/auditoria/LOGICA/aud_log_auditoria.php");
}
class MysqlDatos
{
    /* @var bool */
    var $audit = 0;
    var $debug = 0;
    var $ek = 1, $con = null, $conDB = null, $_sentencias_func = null;
    /* @var object */
    var $rs_cargar = 0; // iden. de consulta
    /* @var number */
    var $ErrorDebugged = true; // Error enviado
    var $Error = 0; // Cod. de error
    var $lastNumSql = 0; // ultima sql
    /* @var string */
    var $beforeLastSqlQuery = '';
    var $lastSqlQuery = '';     // ultima sql
    var $MsgError = ''; // msg del error de MySql
    var $sentencias_sql; // string - funcion q arma las sql
    var $sentencias = ''; //audit //guardara las sql concatenadas con *
    var $codigos = ''; //audit //guarda los cods de insercion
    /* M E T O D O S */
    function MysqlDatos($con = null, $sqls = null)
    {
        $this->setConnection($con);
        $this->setSentencias($sqls);
    }
    //function MysqlDatos($sql_function=null){  if($sql_function!=null)$this->setSentencias($sql_function); }
    function debug($b = true)
    {
        if (!is_bool($b)) $b = 0;
        $this->debug = $b;
        $this->ek = $b;
    }
    function debugLogs($b = true)
    {
        if (!is_bool($b)) $b = 0;
        $this->ek = $b;
    }
    function setAudit($b)
    {
        if (is_bool($b)) $this->audit = $b;
    }
    function setSentencias($sql_func)
    {
        if (is_string($sql_func)) $this->sentencias_sql = $sql_func;
    }
    //function setSentenciasFunc($sql_func){ if(!is_string($sql_func)&& is_callable($sql_func)){ $this->_sentencias_func=$sql_func; $this->sentencias_sql='_sentencias_func'; }  }
    function getSentencias()
    {
        return $this->sentencias_sql;
    }
    function setError($error, $msg = NULL)
    {
        $this->ErrorDebugged = empty($error);
        $this->Error = $error;
        if ($msg != NULL) $this->setMsgError($msg);
    }
    function setErrorEmpty($dbData = array())
    {
        $this->setError(1, 'No ha especificado una consulta SQL.');
        DebugBar::addQuery('', $dbData + $this->getErrorData());
        return 0;
    }
    function getError()
    {
        return $this->Error;
    }
    function setMsgError($e = '')
    {
        $this->MsgError = ($this->Error == 0 || empty($this->lastNumSql) ? "" : "SQL " . (is_int($this->lastNumSql) ? 'No. ' : '') . "{$this->lastNumSql}: ") . $e;
    }
    function getMsgError()
    {
        return $this->MsgError;
    }
    function getDB($c = null)
    {
        if (!class_exists('DebugBar\DebugHelper')) {
            return array();
        }

        $c = is_null($c) || is_bool($c) ? $this->con : $c;

        if (is_subclass_of($c, 'MysqlConexion'))
            return $c->getDB();

        if (get_class($c) == 'mysqli' && !$this->conDB) {
            $resultado = $c->query("SELECT DATABASE()")->fetch_assoc();
            $this->conDB = $resultado['DATABASE()'];
        }

        if ($this->conDB)
            return array('db' => $this->conDB);

        if (!$c && !$this->conDB)
            return array('is_success'=>false, 'error_message'=>'La conexion presenta problemas');

        return array();
    }
    function getErrorData()
    {
        if($this->ErrorDebugged) return array();
        $this->ErrorDebugged = true;
        return array('is_success'=>!$this->Error, 'error_code'=>$this->Error, 'error_message'=>$this->MsgError);
    }
    function getRowCount($result, $sql='', $con = null)
    {
        if(!$result || stripos($sql, 'SELECT COUNT('))
            return array();
        if ($con && preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $sql))
            return array('row_count'=>@mysqli_affected_rows($con)); // Devuelve las filas afectadas
        return array('row_count'=>@mysqli_num_rows($result));
    }
    function startConnection($DatDis = null)
    {
        if (is_null($DatDis) && isset($_SESSION) && isset($_SESSION['Ses_Dat_Dis'])) $DatDis = $_SESSION['Ses_Dat_Dis'];
        if (!is_null($DatDis)) {
            require_once (isset($GLOBALS['APP_REAL_PATH']) ? $GLOBALS['APP_REAL_PATH'] . '/DATA' : '.') . '/MysqlConexion.php';
            $this->setConnection(new MyGlobalConexion($DatDis));
        }
        return $this;
    }
    function startConn($DatDis = null)
    {
        return $this->startConnection($DatDis);
    }
    function setConnection($con)
    {
        if (is_bool($con) && $con) {
            $this->startConnection();
        } else if (!is_null($con)) $this->con = is_subclass_of($con, 'MysqlConexion') ? $con->conexion : get_class($con) == 'mysqli' ? $con : null;
    }
    function setConn($con)
    {
        $this->setConnection($con);
    }
    function closeConnection($c = null)
    {
        $d = is_null($c) ? $this->con : $this->getMyCon($c);
        return !is_null($d) ? @mysqli_close($d) : null;
    }
    function closeConn($c = null)
    {
        return $this->closeConnection();
    }
    function getMyCon($c = null)
    {
        return is_null($c) || is_bool($c) ? (!is_null($this->con) ? $this->getMyCon($this->con) : null) : (is_subclass_of($c, 'MysqlConexion') ? $c->conexion : $c);
    }
    function getMyCharacterSet($c = null)
    {
        $con = $this->getMyCon($c);

        return is_null($con) || $con === false ? null : $con->character_set_name();
    }
    function getConn($c = null)
    {
        return $this->getMyCon(c);
    }
    function mensajes($param)
    {
        $Par_Sql = $param;
        return $Par_Sql;
    }
    function echoLog($l, $type = 'log')
    {
        if ($this->ek && class_exists('DebugBar\DebugHelper')) {
            \DebugBar::$type($l);
            return 1;
        }
        return 0;
    }
    function comment($comment, $conexion = null)
    {
        \DebugBar::addQueryComment($comment, $this->getDB($conexion));
    }
    function echoJson($a, $b = true)
    {
        $this->utf8_change_param($a);
        $c = json_encode($a);
        if ($b == true) {
            echo $c;
            exit();
        }
        return $c;
    }
    function toHtmlJson($a)
    {
        return htmlentities(is_array($a) ? json_encode($a) : $a, ENT_QUOTES);
    }
    function pages($count, $page, $limit)
    {
        if (is_null($count)) $count = 0;/* agregado por erik para crear formato JqGrid */
        if ($count > 0 && $limit > 0) {
            $tot_pags = ceil($count / $limit);
        } else {
            $tot_pags = 0;
        }
        if ($page > $tot_pags) {
            $page = $tot_pags;
        }
        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }
        return array('limits' => " LIMIT {$start}, {$limit}", 'data' => array('rows' => NULL, 'page' => $page, 'total' => $tot_pags, 'records' => $count, 'success' => true));
    }
    function stripTags(&$input)
    {
        if (is_string($input)) {
            $input = strip_tags($input);
        } else if (is_array($input)) {
            foreach ($input as $k => &$value) {
                if ($k != 'where') $this->stripTags($value);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                $this->stripTags($input->$var);
            }
        }
        return $this;
    }
    function cleanSaltosLinea(&$input)
    {
        if (is_string($input)) {
            $input = str_ireplace(array(chr(13), chr(10), "\r\n", "\n", "\r\r", "\r"), array(", ", " ", ", ", ", ", " ", " "), trim($input));
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->cleanSaltosLinea($value);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                $this->cleanSaltosLinea($input->$var);
            }
        }
    }
    function utf8_change_param(&$input, $type = false)
    { /* agregado por erik para limpieza de caracteres especiales */
        if (is_string($input)) {
            $input = trim($input);
            if ((!!mb_detect_encoding($input, 'UTF-8', true)) == $type) $input = call_user_func($type ? 'utf8_decode' : 'utf8_encode', $input);
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->utf8_change_param($value, $type);
            }
            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));
            foreach ($vars as $var) {
                $this->utf8_change_param($input->$var, $type);
            }
        }
    }
    /**
     * retorna array en base a los parametros
     * @param string/array $param
     * @return array
     */
    function parametros($param, $encoding = 'latin1')
    {
        if (!is_object($param)) {
            $Par_Sql = is_array($param) ? $param : explode('*', $param);
            $this->stripTags($Par_Sql)->utf8_change_param($Par_Sql, $encoding != 'utf8');
        } else {
            if (!is_a($param, "Zend_Db_Select")) {
                $this->utf8_change_param($param, $encoding != 'utf8');
            } else {
                $d = $param->getDataSelect();
                $this->stripTags($d)->utf8_change_param($d, $encoding != 'utf8');
                $param->setDataSelect($d);
            }
            $Par_Sql = $param;
        }
        return $Par_Sql;
    }
    /* MEJORAS EN CLASE */
    /******************************************/
    /* Ejecuta un select sql */
    function consulta($sql, $conexion = null)
    {
        if (empty($sql)) return $this->setErrorEmpty($this->getDB($conexion));
        DebugBar::startQueryMeasure();
        $con = $this->getMyCon($conexion);
        $this->rs_cargar = @mysqli_query($con, $sql); /* ejecutamos la consulta */
        if (!$this->rs_cargar) $this->setError(@mysqli_errno($con), @mysqli_error($con));
        DebugBar::addQuery($sql, $this->getDB($conexion) + $this->getErrorData() + $this->getRowCount($this->rs_cargar, $sql, $con));
        return $this->rs_cargar; /* Si hubo �xito devuelve el identificador de la conexi�n, sino devuelve 0  */
    }
    /* Ejecuta Insert/Update/Delete */
    function grabarv_registros($sql, $conexion = null, $num = 0)
    {
        if (!is_string($sql)) $sql = '';
        DebugBar::startQueryMeasure();
        $con = $this->getMyCon($conexion);
        if ($this->Error == 0) {
            $this->beforeLastSqlQuery = $this->insercionid($con) . " -> " . $this->lastSqlQuery;
            $this->lastNumSql = $num;
            $this->lastSqlQuery = $sql;
            $result = @mysqli_query($con, $sql);
            $this->setError(@mysqli_errno($con), @mysqli_error($con));
        } else $result = false;
        DebugBar::addQuery($sql, $this->getDB($conexion) + $this->getErrorData() + $this->getRowCount($this->rs_cargar, $sql, $con));
        return $result;
    }
    /* Load model */
    function loadModel($sen_sql, $Par_Sql)
    {
        if (is_object($Par_Sql)) return $Par_Sql->__toString();
        if ($Par_Sql == null || empty($Par_Sql)) $Par_Sql = array();
        $m = null;
        $model = explode('.', $sen_sql);
        if (!isset($model[1]) || $model[1] == 'sw') $model[1] = 'selectWhere';
        $modelFile = dirname(__file__) . "/../MODELS/" . $model[0] . ".php";
        if (file_exists($modelFile)) {
            require_once $modelFile;
            $m = new $model[0]();
        } else {
            require_once dirname(__file__) . "/libs/AbstractModel.php";
            $m = new AbstractModel($model[0]);
        }
        return $m->getSqlString(is_numeric($model[1]) ? ($m->sqlByNumero($model[1], $Par_Sql)) : (isset($model[2]) ? $m->sqlByNombre($model[2], $Par_Sql) : $m->$model[1]($Par_Sql)));
    }
    /* sentncias internas o externas */
    function executeSentencias($sen_sql, $par)
    {
        $method = $this->getSentencias();
        return method_exists($this, $method) ? $this->$method($sen_sql, $par) : call_user_func($this->getSentencias(), $sen_sql, $par);
    }
    /* select */
    function select()
    {
        require_once dirname(__file__) . "/libs/Select.php";
        return new Zend_Db_Select(null);
    }
    function expr($str)
    {
        require_once dirname(__file__) . "/libs/Expr.php";
        return new Zend_Db_Expr($str);
    }
    /**
     * Realiza consulta en la bd - STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de parametros
     * @param MysqlConexion $obBD para la conexion
     * @return result mysql
     */
    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        if (!is_string($this->getSentencias()) && is_numeric($sen_sql)) return NULL;
        $par = $this->parametros($param, $this->getMyCharacterSet($obBD));
        return $this->consulta(!is_numeric($sen_sql) ? $this->loadModel($sen_sql, $par) : $this->executeSentencias($sen_sql, $par), $obBD);
    }
    /**
     * Realiza insert/update/delete en la bd -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de parametros
     * @param MysqlConexion $obBD para la conexion
     * @return result mysql
     */
    function operacionobBD($sen_sql, $param, $obBD = null)
    {
        if (!is_string($this->getSentencias()) && is_numeric($sen_sql)) return NULL;
        $par = $this->parametros($param, $this->getMyCharacterSet($obBD));
        $Query = !is_numeric($sen_sql) ? $this->loadModel($sen_sql, $par) : $this->executeSentencias($sen_sql, $par);
        $result = $this->grabarv_registros($Query, $obBD, $sen_sql);
        if ($this->audit && $result != false) {
            $this->sentencias .= $Query . '*';
            $this->codigos .= $this->insercionid($obBD) . '*';
        }
        return $result;
    }
    function operation($sen_sql, $param, $echo = 0, $obBD = null)
    {
        $this->operacionobBD($sen_sql, $param, $obBD);
        return $this;
    }
    /**
     * Ejecuta consulta a la bd -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de parametros
     * @param MysqlConexion $obBD para la conexion
     * @return array fila de datos
     */
    function getRowConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $r = $this->fetch_assoc($result);
        $this->free_result($result);
        return $r;
    }
    function getRow($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->getRowConsulta($sen_sql, $param, $obBD);
    }
    function fetchRow($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->getRowConsulta($sen_sql, $param, $obBD);
    }
    function getRowConsultaSql($sql, $obBD = null)
    {
        $result = $this->consulta($sql, $obBD);
        $r = $this->fetch_assoc($result);
        $this->free_result($result);
        return $r;
    }
    /**
     * Ejecuta consulta a la bd -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de valores para el filtrado de la busqueda
     * @param MysqlConexion $obBD para la coneccion
     * @return array $array arreglo de datos asociados
     */
    function getArrayConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $a = array();
        while ($row_rs = $this->fetch_assoc($result)) {
            $a[] = $row_rs;
        }
        $this->free_result($result);
        return $a;
    }
    function getArray($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->getArrayConsulta($sen_sql, $param, $obBD);
    }
    function fetchArray($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->getArrayConsulta($sen_sql, $param, $obBD);
    }
    function getArrayConsultaSql($sql, $obBD = null)
    {
        $result = $this->consulta($sql, $obBD);
        $a = array();
        while ($row_rs = $this->fetch_assoc($result)) {
            $a[] = $row_rs;
        }
        $this->free_result($result);
        return $a;
    }
    /**
     * Inserta/actualiza/elimina datos en una sola transacccion -  STARDARD
     * @param int $sen_sql numero de la sql
     * @param string $param cadena de datos
     * @param MysqlConexion $obBD objeto de conexion
     */
    function insertUpdateDelete($sen_sql, $param, $obBD = null)
    {
        $this->inicio_transaccion($obBD);
        $this->operacionobBD($sen_sql, $param, $obBD);
        $this->fin_transaccion($obBD);
    }
    /**
     * Ejecuta consulta y retorna un arreglo para JqGrid
     * @param int $sql numero de la sql
     * @param array $data arreglo de datos
     * @param MysqlConexion $obBD
     * @return array
     */
    function getPageGrid($sql, $data, $obBD = null, $page = 1, $rows = 50)
    {
        if (is_array($data)) {
            if (!isset($data['rows'])) array_push($data, '');
            else {
                $data["limits"] = NULL;
                $data["isGrid"] = true;
                if (!isset($data['where'])) $data['where'] = '';
                $page = isset($data['page']) ? $data['page'] * 1 : $page;
                $rows = isset($data['rows']) ? $data['rows'] * 1 : $rows;
            }
        } else $data = $data . '*';
        $contar = $this->getRowConsulta($sql, $data, $obBD);
        $pagination = $this->pages($contar['total'], $page, $rows);
        $r = $pagination['data'];
        if (is_array($data)) $data[isset($data['rows']) ? "limits" : count($data) - 1] = $pagination['limits'];
        else $data = $data . $pagination['limits'];
        $r['rows'] = !is_null($contar['total']) && $contar['total'] * 1 > 0 ? $this->getArrayConsulta($sql, $data, $obBD) : array();
        if ($this->Error != 0) {
            $r['error'] = $this->MsgError;
            $r['success'] = false;
        }
        $this->utf8_change_param($r['rows']);
        return $r;
    }
    function getPageGridJson($sql, $data, $obBD = null, $page = 1, $rows = 50)
    {
        $rs = $this->getPageGrid($sql, $data, $obBD, $page, $rows);
        echo json_encode($rs);
        exit();
    }
    function getPageGridFormat($sql, $data, $obBD = null, $log = false)
    {
        $r = $this->getArrayConsulta($sql, $data, $obBD, $log);
        $this->utf8_change_param($r);
        return array('rows' => $r, 'page' => 1, 'total' => 1, 'records' => count($r), 'success' => true);
    }
    /* Mejoras en la transaccion */
    /* Graba un registro utilizando transacciones */
    function grabaru($sql, $conexion = null)
    {
        if (empty($sql)) return $this->setErrorEmpty($this->getDB($conexion));
        DebugBar::addTransactionEvent('Begin Transaction', $this->getDB($conexion));
        $con = $this->getMyCon($conexion);
        mysqli_autocommit($con, FALSE);
        mysqli_query($con, "BEGIN");
        $this->rs_save = @mysqli_query($con, $sql);
        $this->setError(@mysqli_errno($con), @mysqli_error($con));
        DebugBar::addQuery($sql, $this->getDB($conexion));
        if ($this->Error == 0) $this->commit($con);
        else $this->rollBack($con);
        //$this->setErrorEmpty();
    }
    /* Abre la transaccion */
    function inicio_transaccion($conexion = null)
    {
        $this->setError(0, '');
        $con = $this->getMyCon($conexion);
        mysqli_autocommit($con, FALSE);
        mysqli_query($con, "BEGIN");
        DebugBar::addTransactionEvent('Begin Transaction', $this->getDB($conexion));
    }
    function inicioTransaccion($c = null)
    {
        $this->inicio_transaccion($c);
    }
    function beginTrans($c = null)
    {
        $this->inicio_transaccion($c);
    }
    /* Confirma la transaccion */
    function commit($conexion = null, $msge = 1)
    {
        mysqli_commit($this->getMyCon($conexion));
        DebugBar::addTransactionEvent('Commit Transaction', $this->getDB($conexion));
        if (!$msge) return; ?><script type="text/javascript">alert("La transacción se ha realizado con Éxito!");</script><?php
    }
    function commit_nomsn($c = null)
    {
        $this->commit($c, 0);
    }
    function commitNoMsn($c = null)
    {
        $this->commit($c, 0);
    }
    /* Anula la transaccion */
    function rollBack($conexion = null, $msge = 1)
    {
        mysqli_rollback($this->getMyCon($conexion));
        DebugBar::addTransactionEvent('RollBack Transaction', $this->getDB($conexion)+$this->getErrorData());
        if (!$msge) return; ?><script type="text/javascript">alert("< < < !!! A l e r ta !!!: NO se ha podido completar con Éxito la transacción > > >");</script><?php
    }
    function rollBack_nomsn($c = null, $m = null, &$r = array())
    {
        if (is_string($m)) {
            $r['message'] = $m;
            $this->setError(0, $m);
        }
        $r['success'] = false;
        $this->rollBack($c, 0);
    }
    function rollBackNomsn($m = null, &$r = array(), $c = null)
    {
        $this->rollBack_nomsn($c, $m, $r);
    }
    function revertTrans($m = null, &$r = array(), $c = null)
    {
        $this->rollBack_nomsn($c, $m, $r);
    }
    function rollB($m = null, &$r = array(), $c = null)
    {
        $this->rollBack_nomsn($c, $m, $r);
    }
    /* Cierra la transaccion */
    function fin_transaccion($conexion = null, $msge = 1)
    {
        $con = $this->getMyCon($conexion);
        //$this->setError($this->Error,mysqli_error($con));
        if ($this->Error == 0) {
            $this->setMsgError();
            $this->commit($con, $msge);
        } else $this->rollBack($con, $msge);
        return !$this->Error;
    }
    function fin_transaccion_nomsn($c = null, &$r = array())
    {
        $r['success'] = $this->fin_transaccion($c, 0);
        if (!$r['success']) {
            $r['error'] = $this->MsgError;
            $r['lastSql'] = $this->beforeLastSqlQuery;
            $r['errorSql'] = $this->lastSqlQuery;
        }
        return $r['success'];
    }
    function finTransaccionNoMsn(&$r = array(), $c = null)
    {
        return $this->fin_transaccion_nomsn($c, $r);
    }
    function endTrans(&$r = array(), $c = null)
    {
        return $this->fin_transaccion_nomsn($c, $r);
    }
    function truncateTrans($msg = "<u class='blue'>Truncate Transaction:</u> Todo se guardo correctamente, commit truncado por pruebas!")
    {
        if ($this->Error == 0) throw new Exception($msg);
    }
    /* Devuelve el ultimo codigo generado en una conexion */
    function insercionid($c = null)
    {
        return mysqli_insert_id($this->getMyCon($c));
    }
    function lastId($c = null)
    {
        return $this->insercionid($c);
    }
    /* Devuelve el numero de campos de una consulta - @return number|object */
    function numcampos()
    {
        return mysqli_num_fields($this->rs_cargar);
    }
    /* Devuelve el numero de registros de una consulta - @return number|object */
    function numregistros()
    {
        return @mysqli_num_rows($this->rs_cargar);
    }
    /* Devuelve una matriz con los datos consultados - @return number|object */
    function registros()
    {
        return @mysqli_fetch_assoc($this->rs_cargar);
    }
    /* Devuelve el nombre de un campo de una consulta - @param number $numcampo */
    function nombrecampo($numcampo)
    {
        return mysqli_field_name($this->rs_cargar, $numcampo);
    }
    /* Desvuelve una matriz con los datos consultados en base a un rs */
    function fetch_assoc($rs_consulta)
    {
        if (is_bool($rs_consulta)||is_null($rs_consulta))return array();
        return @mysqli_fetch_assoc($rs_consulta);
    }
    /* Desvuelve el total de datos consultados en base a un rs */
    function num_rows($rs_consulta)
    {
        return @mysqli_num_rows($rs_consulta);
    }
    /* libera un rs */
    function free_result(&$rs_consulta)
    {
        $ban = true;
        if ($rs_consulta instanceof mysqli_result) {
            // Si es un recurso v�lido, lo liberamos
            try{
                $ban = @mysqli_free_result($rs_consulta);
            } catch (\Exception $e){}
            $rs_consulta = null; // Evita reutilizaci�n accidental
        }

        return $ban;
    }
    /* Mueve el apuntador de la consulta */
    function data_seek($rs_consulta, $puntero)
    {
        return @mysqli_data_seek($rs_consulta, $puntero);
    }
    /* Devuelve un arreglo de una consulta */
    function fetch_array($rs_consulta)
    {
        return @mysqli_fetch_array($rs_consulta);
    }
    /* Libera la memoria ram de los datos cargados - @return number|object */
    function liberar()
    {
        return $this->free_result($this->rs_cargar);
    }
    /* Muestra los datos de una consulta */
    function verconsulta()
    {
        echo "<table border=1>\n";
        $z = $this->numcampos();
        echo "<tr>\n";
        for ($i = 0; $i < $z; $i++) {
            echo "<td><b>" . $this->nombrecampo($i) . "</b></td>\n";
        }
        echo "</tr>\n"; // mostramos la cabecera
        while ($row = mysqli_fetch_row($this->rs_cargar)) {
            echo "<tr>\n";
            for ($i = 0; $i < $z; $i++) {
                echo "<td>" . $row[$i] . "</td>\n";
            }
            echo "</tr>\n";
        } // mostrarmos los datos
    }
    function htmlAttrData($arr)
    {
        $attr = "";
        foreach ($arr as $k => $d) $attr .= ("data-" . preg_replace_callback('/([A-Z])/', function ($word) {
            return strtolower("-$word[1]");
        }, $k) . "='" . $this->toHtmlJson($d) . "' ");
        return $attr;
    }
    function htmlOptions($arr, $id, $label, $json = false, $selected = null)
    {
        $max = count($arr);
        $html = "";
        $isFunc = is_callable($label);
        $selIsFunc = is_callable($selected);
        foreach ($arr as $v) $html .= "<option value='$v[$id]'" . ($json == true ? $this->htmlAttrData($v)/*" data-row='".$this->toHtmlJson($v)."'"*/ : '') . ($max == 1 || (!is_null($selected) && ((!$selIsFunc && $v[$id] == $selected) || ($selIsFunc && $selected($v)))) ? ' selected="" default=""' : '') . ">" . ($isFunc ? $label($v) : $v[$label]) . "</option>";
        return $html;
    }
    /**
     * graba en la base de datos auditoria
     * @param string $Request_Uri pagina donde se estan modificando valores
     * @param int $Ses_Usu_Cod codigo del usuario
     * @param MysqlConexion $obBD
     * @return int codigo de error mysql [0='No Error']
     */
    function saveAuditoria($Ses_Dat_Dis, $Request_Uri, $Ses_Usu_Cod, $obBD)
    {
        if ($this->Error == 0) {
            $objAud = new Class_Log_Datos_Aud;
            $aux = explode('*', $objAud->grabarAuditoria($Ses_Dat_Dis, $Request_Uri, $Ses_Usu_Cod, $this, $obBD));
            foreach ($aux as $row) {
                $this->grabarv_registros($row, $obBD);
                if ($this->Error > 0) return $this->Error;
            }
            $objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
        } else return $this->Error;
    }
    function reportesExa($pagina, $Emp_Cod, $obBD)
    {
        $pag = explode("/", $pagina);
        $report = array();
        $proceso = $this->getRowConsultaSql("SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom LIKE '" . ($pag[count($pag) - 1]) . "' ORDER BY Pcs_Nom DESC;", $obBD);
        $reportes = $this->getArrayConsultaSql("SELECT reportes.Rep_Cod,procesos.Pcs_Nom,reportes.Rep_Ord,rutas.Rut_Des FROM procesos
INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req) INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod)
WHERE reportes.Pcs_Cod = '$proceso[Pcs_Cod]' AND reportes.Emp_Cod = '$Emp_Cod' ORDER BY reportes.Rep_Ord;", $obBD);
        foreach ($reportes as $r) $report[$r['Rep_Ord']] = $r['Rut_Des'] . $r['Pcs_Nom'];
        return $report;
    }
} //fin de la Clase Class_Datos()
class MysqlDatosContab extends MysqlDatos
{
    /* Bloqueo de periodos */
    function validaCierrePeriodo($table, $fec_field, $id_field, $fec = null, $id = null, $con = null, $trib=null)
    {
        if ((is_null($id) || empty($id)) && (is_null($fec) || empty($fec))) return true;
        $c = $this->getMyCon($con);
        $dato = is_null($id) ? (!is_null($fec) ? array($fec_field => $fec) : array()) : $this->getRowConsulta("$table.selectWhere", array('unsetCols' => true, 'addCols' => array('' => array($id_field, $fec_field)), 'where' => array($id_field => $id), 'setWhere' => array()), $c);
        if (isset($dato[$fec_field]) && !empty($dato[$fec_field])) {

            //No permite modificar los mensuales
            // $cierre = $this->getRowConsulta('perio_cierre.selectWhere', array('unsetCols' => true, 'addCols' => array('' => array('Total' => 'COUNT(*)')), 'where' => array("'$dato[$fec_field]'>=Pci_Ini", "'$dato[$fec_field]'<=Pci_Fin"), 'setWhere' => array('isActive', 'setEmpCod')), $c, $echo);
            $cierre = $this->getRowConsulta('perio_cierre.selectWhere', array('unsetCols' => true, 'addCols' => array('' => array('Total' => 'COUNT(*)')), 'where' => array("'$dato[$fec_field]'>=Pci_Ini", "'$dato[$fec_field]'<=Pci_Fin","if('$trib'<>'',(Pci_Tri is not null or Pci_Tri is null),Pci_Tri is null)"), 'setWhere' => array('isActive', 'setEmpCod')), $c);
            
            //Me permite modificar los mensuales
            //$cierre = $this->getRowConsulta('perio_cierre.selectWhere',array('unsetCols'=> true,'addCols'=>array(''=>array('Total'=>'COUNT(*)')),'where'=>array("'$dato[$fec_field]'>=Pci_Ini", "'$dato[$fec_field]'<=Pci_Fin" ,"Pci_Tip='A'"),'setWhere'=>array('isActive','setEmpCod')), $c, $echo);

            if (($cierre['Total'] * 1) > 0)
                $this->echoJson(array('success' => false, 'message' => 'La fecha <span class="green">' . $dato[$fec_field] . '</span> se encuentra <span class="red">BLOQUEADA</span> comuniquese con su <span class="blue">CONTADOR (A)</span>!'));
        }
        if (!(is_null($id) || empty($id)) && !(is_null($fec) || empty($fec))) return $this->validaCierrePeriodo($table, $fec_field, $id_field, $fec, null, $con, $trib);
        return true;
    }
    /* optiene sucursal/empresa */
    function getUsuario($Usu_Cod, $obBD = null)
    {
        return $this->getRowConsultaSql("SELECT persona.Prs_Cod, Prs_Ced, Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Usu_Cod", $this->getMyCon($obBD));
    }
    /* optiene sucursal/empresa */
    function getSucursal($Suc_Cod, $obBD = null)
    {
        return $this->getRowConsultaSql("SELECT empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Des, empresas.Emp_Log, sucursal.* FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Suc_Cod AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod", $this->getMyCon($obBD));
    }
    /* optiene sucursal/empresa */
    function getCiudad($Ciu_Cod, $obBD = null)
    {
        return $this->getRowConsultaSql("SELECT provincia.Pro_Nom, pais.Pas_Nom FROM provincia INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod) INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod) INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) WHERE ciudad.Ciu_Cod = $Ciu_Cod", $this->getMyCon($obBD));
    }
    /* optiene el periodo contable */
    function getPerioCont($Emp_Cod, $Fecha, $obBD = null)
    {
        return $this->getRowConsultaSql("SELECT perio_cont.*,YEAR(Pec_Fei)AS Periodo FROM perio_cont INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=perio_cont.Pla_Cod WHERE Emp_Cod=$Emp_Cod AND " . (strlen($Fecha) == 4 ? "YEAR(Pec_Fei)='$Fecha'" : "'$Fecha' BETWEEN Pec_Fei AND Pec_Fef"), $this->getMyCon($obBD));
    }
    /* numero de comprobante automatico */
    function getComNumAuto($Emp_Cod, $Tia_Cod, $Fecha, $obBD = null)
    {
        $c = $this->getMyCon($obBD); /* Codificaci�n numerica en base al periodo contable y mensualmente  */
        $row_rs_perio =  $this->getPerioCont($Emp_Cod, $Fecha, $c);
        $result1 = $this->getRowConsultaSql("SELECT COALESCE(MAX(Com_Num),0)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Tia_Cod AND Pec_Cod=$row_rs_perio[Pec_Cod] AND MONTH(Com_Fec)=" . (strlen($Fecha) > 2 ? "MONTH('$Fecha')" : $Fecha), $c);
        return $result1['Com_Num'];
    }
    function getComNumPecAuto($Tia_Cod, $Pec_Cod, $Fecha, $obBD = null)
    { /* Codificaci�n numerica en base al periodo contable y mensualmente  */
        $result1 = $this->getRowConsultaSql("SELECT COALESCE(MAX(Com_Num),0)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod=$Tia_Cod AND Pec_Cod=$Pec_Cod AND MONTH(Com_Fec)=" . (strlen($Fecha) > 2 ? "MONTH('$Fecha')" : $Fecha), $this->getMyCon($obBD));
        return $result1['Com_Num'];
    }
    /* optiene varios ingresos/varios egresos */
    function getProveeClie($Emp_Cod, $Campo, $obBD = null)
    {
        $sql = "";
        if ($Campo == 'Prv_Cod') $sql = "SELECT compra_prov.Prv_Cod FROM compra_prov INNER JOIN proveedore ON proveedore.Prv_Cod= compra_prov.Prv_Cod WHERE Emp_Cod=$Emp_Cod";
        if ($Campo == 'Cli_Cod') $sql = "SELECT caja_clien.Cli_Cod FROM caja_clien INNER JOIN cliente ON caja_clien.Cli_Cod= cliente.Cli_Cod WHERE Emp_Cod=$Emp_Cod";
        $result1 = $this->getRowConsultaSql($sql, $this->getMyCon($obBD));
        //ChromePhp::log("pro_cod...".$Campo);
        //ChromePhp::log($sql);
        return $result1[$Campo];
    }


    function getProveeCliente($Emp_Cod, $Campo, $obBD = null)
    {
        $sql = "";
        if ($Campo == 'Prv_Cod') $sql = "SELECT compra_prov.Prv_Cod FROM compra_prov INNER JOIN proveedore ON proveedore.Prv_Cod= compra_prov.Prv_Cod WHERE Emp_Cod=$Emp_Cod";
        if ($Campo == 'Cli_Cod')
            $sql = "SELECT cheques_ext.Cli_Cod FROM cheques_ext
INNER JOIN cliente ON cheques_ext.Cli_Cod= cliente.Cli_Cod
WHERE Emp_Cod=$Emp_Cod AND Che_Cod=$Che_Cod";
        $result1 = $this->getRowConsultaSql($sql, $this->getMyCon($obBD));
        return $result1[$Campo];
    }
    function getReportHeader($Suc_Cod, $titulo = '', $subtitulo = ' ', $obBD = null, $full = true, $colspan = 1, $withLogo = false, $justBody = false, $tamanio = '12px')
    {
        $c = $this->getMyCon($obBD);
        $row_inst = $this->getSucursal($Suc_Cod, $c);
        $appendLogo = ((!$full) && $withLogo);
        $row_ciud = $this->getCiudad($row_inst['Ciu_Cod'], $c);
        //$this->utf8_change_param($row_inst);$this->utf8_change_param($row_ciud);
        $CIUDAD = (empty($row_ciud) || empty($row_ciud['Pro_Nom'])) ? '' : " - " . $row_ciud['Pro_Nom'] . ' - ' . $row_ciud['Pas_Nom'];
        $LOGO = $full || $appendLogo ? "<div align='left'><img style='float:left;position:absolute;" . (empty($row_inst['Suc_Com']) ? "" : "padding-top:7px;") . "' src='" . (empty($row_inst['Suc_Lg2']) ? $row_inst['Emp_Log'] : $row_inst['Suc_Lg2']) . "' width='" . ($appendLogo ? 83 : 103) . "' height='" . ($appendLogo ? 73 : 93) . "' /></div>" : '';
        //$colspan1=$full||$appendLogo?$colspan-1:$colspan;
        $str = "" .
            ($justBody ? '' : "<table width='100%' border='0' cellpadding='0' cellspacing='0' class='reporteClass'>") . "<thead>
<tr><th style='border:0' colspan='$colspan' class='TITULO_REPORTE_2' align='center'>$LOGO<div align='center'><strong>" . (empty($row_inst['Suc_Com']) ? $row_inst['Emp_Nom'] : $row_inst['Suc_Com']) . "</strong></div></th></tr>" .
            (empty($row_inst['Suc_Com']) ? "" : "<tr><th style='border:0' colspan='$colspan' align='center' class='Texto_Reporte'><div align='center'>" . $row_inst['Emp_Nom'] . "</div></th></tr>") . "
<tr><th style='border:0' colspan='$colspan' align='center' class='Texto_Reporte'><div align='center'><strong>R.U.C.:</strong>&nbsp;$row_inst[Emp_Ruc]&nbsp;-&nbsp;<strong>TELEFONO:</strong>&nbsp;$row_inst[Suc_Te1]</div></th></tr>
" . ($full ? "<tr><th style='border:0' colspan='$colspan' align='center' class='Texto_Reporte'><div align='center'><strong>DIRECCION:</strong>&nbsp;$row_inst[Suc_Dir]</div></th></tr>
<tr><th style='border:0' colspan='$colspan' align='center' class='Texto_Reporte'><div align='center'><strong>E-MAIL:</strong> &nbsp;$row_inst[Suc_Cor]</div></th></tr>" : '') . "
<tr><th style='border:0' colspan='$colspan' align='center' class='Texto_Reporte'><div align='center'>$row_inst[Ciu_Des] $CIUDAD</div></th></tr>
<tr><th style='border:0' colspan='$colspan' align='center'><hr /></th></tr>
<tr><th style='border:0' colspan='$colspan' align='center' class='TITULO_REPORTE' style='font-size: $tamanio;'><strong class='titleReporte'>$titulo</strong></th></tr>
" . (empty($subtitulo) || $subtitulo == '' ? '' : "<tr><th style='border:0' colspan='$colspan' align='center' class='TITULO_REPORTE subTitleReporte' style='font-size: $tamanio;'>$subtitulo</th></tr>") . "
</thead>" . ($justBody ? '' : "</table>");
        return $str;
    }
    function getReportFooter($Suc_Cod, $Usu_Cod, $obBD = null, $colspan = 1)
    {
        $c = $this->getMyCon($obBD);
        $row_inst = $this->getSucursal($Suc_Cod, $c);
        $row_usua = $this->getUsuario($Usu_Cod, $c);
        //$this->utf8_change_param($row_inst);$this->utf8_change_param($row_usua);
        $fecha = explode("-", date("Y-m-d"));
        $fechaHoy = $row_inst['Ciu_Des'] . ", " . $fecha[2] . " de " . mes($fecha[1], 1) . " " . $fecha[0];

        if ($Suc_Cod == 334) {
            $str = "
<table width='100%' border='0' cellpadding='0' cellspacing='0' style='font-size:10px !important;' class='reporteClass'>
<tr align='center'><td style='border:0' colspan='$colspan' valign='top'><hr /></td></tr>
<tr align='center'><td style='border:0' colspan='$colspan' valign='top' class='Texto_Reporte'><span style='float:left;'><strong>FECHA IMPRESI&Oacute;N:</strong>&nbsp;$fechaHoy&nbsp;</span><span style='float:right;'><strong>USUARIO:</strong>&nbsp; $row_usua[Prs_Nom]</span></td></tr>
</table>";
        } else {
            $str = "
<table width='100%' border='0' cellpadding='0' cellspacing='0' style='font-size:10px !important;' class='reporteClass'>
<tr align='center'><td style='border:0' colspan='$colspan' valign='top'><hr /></td></tr>
<tr align='center'><td style='border:0' colspan='$colspan' valign='top' class='Texto_Reporte'><span style='float:left;'><strong>FECHA IMPRESI&Oacute;N:</strong>&nbsp;$fechaHoy&nbsp;</span><span style='float:right;'><strong>USUARIO:</strong>&nbsp;$row_usua[Prs_Ape] $row_usua[Prs_Nom]</span></td></tr>
</table>";
        }
        return $str;
    }
    function setReports($title = '', $subtittle = ' ')
    {
        $tbl = '<table class="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout: fixed;"></table>';
        echo '<div id="exportarReporte" style="display:none;">' . $this->getReportHeader($_SESSION['Ses_Suc_Cod'], $title, $subtittle, null, false) . $tbl . '</div>';
        echo '<div id="imprimirReporte" style="width: 700px;display: none;">' . $this->getReportHeader($_SESSION['Ses_Suc_Cod'], $title, $subtittle) . $tbl . $this->getReportFooter($_SESSION['Ses_Suc_Cod'], $_SESSION['Ses_Usu_Cod']) . '</div>';
    }
    function updateStockProd($Suc_Cod, $kardex, $insertKardex, $obBD_conexionCon = null, $obBD_conexionIns = null, $bodCod = null)
    {
        if ($bodCod == null) {
            $bodCod = 'null';
        }
        $c = $this->getMyCon($obBD_conexionCon);
        if (empty($obBD_conexionIns)) $obBD_conexionIns = $c;
        $row_rs_stock = $this->getRowConsultaSql("SELECT * FROM stock WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod", $c);
        $row_rs_prome = $this->getRowConsultaSql("SELECT Pro_Cod,Pro_Stk,Pro_Prp FROM producto WHERE Pro_Cod=$kardex[Pro_Cod] ", $c);

        $i = $kardex['IoE'] == 'I';
        $newStk = array(
            'Can' => ($i ? $kardex['Kar_Can'] : -$kardex['Kar_Sal']),
            'Pru' => ($i ? $kardex['Kar_Prs'] : $kardex['Kar_Pre']),
            'Imp' => ($i ? $kardex['Kar_Ims'] : -$kardex['Kar_Ime'])
        );

        //INGRESO DE KAR_STOCK, KAR_PROMEDIO Y KAR_SALDO
        $saldos = $this->getRowConsultaSql("SELECT Kar_Stock, Kar_Promedio, Kar_Saldo FROM kardex_ie
            WHERE Pro_Cod = $kardex[Pro_Cod] AND Kar_Fec <= '$kardex[Kar_Fec]' AND  Kar_Saldo is not null
            AND  Kar_Est = 'A' AND Kar_Stock is not null ORDER BY Kar_Fec DESC, Kar_Hor DESC
            LIMIT 1", $c);
        if ($i) {
            $saldos['Kar_Stock'] += $kardex['Kar_Can'];
            $saldos['Kar_Saldo'] += $kardex['Kar_Ims'];
            $saldos['Kar_Promedio'] =  $saldos['Kar_Saldo'] / $saldos['Kar_Stock'];
        } else {
            $saldos['Kar_Stock'] -= $kardex['Kar_Sal'];
            $saldos['Kar_Saldo'] = $saldos['Kar_Stock'] * $saldos['Kar_Promedio'];
        }

        if (empty($row_rs_stock)) {
            $this->grabarv_registros("INSERT INTO stock(Pro_Cod,Suc_Cod,Stk_Can) VALUES($kardex[Pro_Cod],$Suc_Cod,$newStk[Can]);", $obBD_conexionIns);
        } else {
            $this->grabarv_registros("UPDATE stock SET Stk_Can=" . ($row_rs_stock['Stk_Can'] + $newStk['Can']) . " WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod;", $obBD_conexionIns);
        }
        //var_dump($newStk);
        if (!empty($row_rs_prome['Pro_Stk']) && !empty($row_rs_prome['Pro_Prp'])) {
            $newStk['Can'] = $row_rs_prome['Pro_Stk'] * 1 + $newStk['Can'] * 1;
            if ($i) {
                $newStk['Imp'] = round(($row_rs_prome['Pro_Stk'] * $row_rs_prome['Pro_Prp']) * 1, 2) + $newStk['Imp'] * 1;          //ULTIMO SALDO + VALOR ACTUAL
                $newStk['Pru'] = ($newStk['Can'] * 1 === 0 ? $row_rs_prome['Pro_Prp'] * 1 : round($newStk['Imp'] / $newStk['Can'], 8)); //PROMEDIO
            } else {
                $newStk['Pru'] = $row_rs_prome['Pro_Prp'];
                $kardex['Kar_Pre'] = $row_rs_prome['Pro_Prp'] * 1;
                $kardex['Kar_Ime'] = round($kardex['Kar_Sal'] * 1 * $kardex['Kar_Pre'], 2);
            }
        }
        //var_dump($newStk);
        $this->grabarv_registros("UPDATE stock SET Stk_Prp = $newStk[Pru] WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod;", $obBD_conexionIns);
        $this->grabarv_registros("UPDATE producto SET Pro_Stk=$newStk[Can],Pro_Prp=$newStk[Pru] WHERE Pro_Cod=$kardex[Pro_Cod] ;", $obBD_conexionIns);
        if ($insertKardex) {
            $sqlKardex = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int,Kar_Stock, Kar_Promedio, Kar_Saldo, Bod_Cod)
            VALUES(" . (empty($kardex['Vet_Cod']) ? 0 : $kardex['Vet_Cod']) . "," . (empty($kardex['Aju_Cod']) ? 0 : $kardex['Aju_Cod']) . ",$kardex[Vnd_Cod]," . (empty($kardex['Cop_Cod']) ? 0 : $kardex['Cop_Cod']) . ",$kardex[Pro_Cod],
            '$kardex[Kar_Fec]','$kardex[Kar_Hor]',
            " . (empty($kardex['Kar_Can']) ? 0 : $kardex['Kar_Can']) . "," . (empty($kardex['Kar_Pre']) ? 0 : $kardex['Kar_Pre']) . "," . (empty($kardex['Kar_Ime']) ? 0 : $kardex['Kar_Ime']) . ",
            " . (empty($kardex['Kar_Sal']) ? 0 : $kardex['Kar_Sal']) . "," . (empty($kardex['Kar_Prs']) ? 0 : $kardex['Kar_Prs']) . "," . (empty($kardex['Kar_Ims']) ? 0 : $kardex['Kar_Ims']) . ",
            " . (empty($kardex['Kar_Des']) ? 0 : $kardex['Kar_Des']) . ",$kardex[Iva_Cod]," . (empty($kardex['Gia_Cod']) ? 0 : $kardex['Gia_Cod']) . "," . (empty($kardex['Kar_Int']) ? 1 : $kardex['Kar_Int']) . "," . round($saldos['Kar_Stock'], 4) . "," . round($saldos['Kar_Promedio'], 4) . "," . round($saldos['Kar_Saldo'], 4) . "," . $bodCod . "); ";
            $this->grabarv_registros($sqlKardex, $obBD_conexionIns);
        }
    }

    function updatenoStockProd($Suc_Cod, $kardex, $insertKardex, $obBD_conexionCon = null, $obBD_conexionIns = null, $bodCod = null)
    {
        if ($bodCod == null) {
            $bodCod = 'null';
        }
        if ($insertKardex) {
            $sqlKardex = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int, Bod_Cod)
            VALUES(" . (empty($kardex['Vet_Cod']) ? 0 : $kardex['Vet_Cod']) . "," . (empty($kardex['Aju_Cod']) ? 0 : $kardex['Aju_Cod']) . ",$kardex[Vnd_Cod]," . (empty($kardex['Cop_Cod']) ? 0 : $kardex['Cop_Cod']) . ",$kardex[Pro_Cod],
            '$kardex[Kar_Fec]','$kardex[Kar_Hor]',
            " . (empty($kardex['Kar_Can']) ? 0 : $kardex['Kar_Can']) . "," . (empty($kardex['Kar_Pre']) ? 0 : $kardex['Kar_Pre']) . "," . (empty($kardex['Kar_Ime']) ? 0 : $kardex['Kar_Ime']) . ",
            " . (empty($kardex['Kar_Sal']) ? 0 : $kardex['Kar_Sal']) . "," . (empty($kardex['Kar_Prs']) ? 0 : $kardex['Kar_Prs']) . "," . (empty($kardex['Kar_Ims']) ? 0 : $kardex['Kar_Ims']) . ",
            " . (empty($kardex['Kar_Des']) ? 0 : $kardex['Kar_Des']) . ",$kardex[Iva_Cod]," . (empty($kardex['Gia_Cod']) ? 0 : $kardex['Gia_Cod']) . "," . (empty($kardex['Kar_Int']) ? 1 : $kardex['Kar_Int']) . ",$kardex[Bod_Cod]); ";
            $this->grabarv_registros($sqlKardex, $obBD_conexionIns);
        }
    }

    function updateBodegaStockProd($Suc_Cod, $kardex, $insertKardex, $obBD_conexionCon = null, $obBD_conexionIns = null, $bodCod = null)
    {
        if ($bodCod == null) {
            $bodCod = 'null';
        }
        $c = $this->getMyCon($obBD_conexionCon);
        if (empty($obBD_conexionIns)) $obBD_conexionIns = $c;
        $row_rs_stock = $this->getRowConsultaSql("SELECT * FROM stock WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod", $c);
        $row_rs_prome = $this->getRowConsultaSql("SELECT Pro_Cod,Pro_Stk,Pro_Prp FROM producto WHERE Pro_Cod=$kardex[Pro_Cod] ", $c);
        $i = $kardex['IoE'] == 'I';
        //ChromePhp::log($i);
        $newStk = array(
            'Can' => ($i ? $kardex['Kar_Can'] : -$kardex['Kar_Sal']),
            'Pru' => ($i ? $kardex['Kar_Prs'] : $kardex['Kar_Pre']),
            'Imp' => ($i ? $kardex['Kar_Ims'] : -$kardex['Kar_Ime'])
        );

        if (empty($row_rs_stock)) {
            $this->grabarv_registros("INSERT INTO stock(Pro_Cod,Suc_Cod,Stk_Can) VALUES($kardex[Pro_Cod],$Suc_Cod,$newStk[Can]);", $obBD_conexionIns);
        } else {
            $this->grabarv_registros("UPDATE stock SET Stk_Can=" . ($row_rs_stock['Stk_Can'] + $newStk['Can']) . " WHERE Pro_Cod=$kardex[Pro_Cod] AND Suc_Cod=$Suc_Cod;", $obBD_conexionIns);
        }
        //var_dump($newStk);
        if (!empty($row_rs_prome['Pro_Stk']) && !empty($row_rs_prome['Pro_Prp'])) {
            $newStk['Can'] = $row_rs_prome['Pro_Stk'] * 1 + $newStk['Can'] * 1;
            if ($i) {
                $newStk['Imp'] = round(($row_rs_prome['Pro_Stk'] * $row_rs_prome['Pro_Prp']) * 1, 2) + $newStk['Imp'] * 1;
                $newStk['Pru'] = ($newStk['Can'] * 1 === 0 ? $row_rs_prome['Pro_Prp'] * 1 : round($newStk['Imp'] / $newStk['Can'], 8));
            } else {
                $newStk['Pru'] = $row_rs_prome['Pro_Prp'];
                $kardex['Kar_Pre'] = $row_rs_prome['Pro_Prp'] * 1;
                $kardex['Kar_Ime'] = round($kardex['Kar_Sal'] * 1 * $kardex['Kar_Pre'], 2);
            }
        }
        //var_dump($newStk);
        $this->grabarv_registros("UPDATE producto SET Pro_Stk=$newStk[Can],Pro_Prp=$newStk[Pru] WHERE Pro_Cod=$kardex[Pro_Cod] ;", $obBD_conexionIns);
        if ($insertKardex) {
            $sqlKardex = "INSERT INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Pre,Kar_Ime,Kar_Sal,Kar_Prs,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int, Bod_Cod)
            VALUES(" . (empty($kardex['Vet_Cod']) ? 0 : $kardex['Vet_Cod']) . "," . (empty($kardex['Aju_Cod']) ? 0 : $kardex['Aju_Cod']) . ",$kardex[Vnd_Cod]," . (empty($kardex['Cop_Cod']) ? 0 : $kardex['Cop_Cod']) . ",$kardex[Pro_Cod],
            '$kardex[Kar_Fec]','$kardex[Kar_Hor]',
            " . (empty($kardex['Kar_Can']) ? 0 : $kardex['Kar_Can']) . "," . (empty($kardex['Kar_Pre']) ? 0 : $kardex['Kar_Pre']) . "," . (empty($kardex['Kar_Ime']) ? 0 : $kardex['Kar_Ime']) . ",
            " . (empty($kardex['Kar_Sal']) ? 0 : $kardex['Kar_Sal']) . "," . (empty($kardex['Kar_Prs']) ? 0 : $kardex['Kar_Prs']) . "," . (empty($kardex['Kar_Ims']) ? 0 : $kardex['Kar_Ims']) . ",
            " . (empty($kardex['Kar_Des']) ? 0 : $kardex['Kar_Des']) . ",$kardex[Iva_Cod]," . (empty($kardex['Gia_Cod']) ? 0 : $kardex['Gia_Cod']) . "," . (empty($kardex['Kar_Int']) ? 1 : $kardex['Kar_Int']) . ",$kardex[Bod_Cod]); ";
            $this->grabarv_registros($sqlKardex, $obBD_conexionIns);
        }
    }
}
