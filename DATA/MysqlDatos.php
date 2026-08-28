<?php
if (!class_exists('DebugBar', false)) {
    $debugbarFile = __DIR__ . '/../Librerias/config.php/debugbar.php';
    if (file_exists($debugbarFile)) {
        require_once $debugbarFile;
    }
}
/*****************************************/
/*      Clase para el manejo de Datos    */
/*****************************************/
#[AllowDynamicProperties]
class MysqlDatos
{
    /* P R O P I E D A D E S */
    /*************************/
    public $array = array();
    public $total = 0;
    public $limit = 0;
    public $offset = 0;
    public $data = array();
    public $campos = array();
    public $reg_ini = 0;
    public $reg_fin = 0;
    public $reg_tot = 0;
    public $reg_pag = 0;
    public $pag_tot = 0;
    public $pag_act = 0;
    public $pag_ant = 0;
    public $pag_sig = 0;
    public $totalRegistros = 0;
    public $totalPaginas = 0;
    public $paginaActual = 0;
    public $sql = "";
    public $sql_total = "";
    public $pagina = 1;
    public $tabla = "";
    public $tablas = array();
    public $filtro = "";
    public $orden = "";
    public $tamanoPagina = 10;
    public $resultado = 0;
    public $identificador = "";
    public $campoId = "";
    public $campoOrden = "";
    public $campoFiltro = "";
    public $campoFiltro2 = "";
    public $campoFiltro3 = "";
    public $campoFiltro4 = "";
    public $campoFiltro5 = "";
    public $campoFiltro6 = "";
    public $campoFiltro7 = "";
    public $campoFiltro8 = "";
    public $campoFiltro9 = "";
    public $campoFiltro10 = "";
    public $tipoFiltro = "";
    public $tipoFiltro2 = "";
    public $tipoFiltro3 = "";
    public $tipoFiltro4 = "";
    public $tipoFiltro5 = "";
    public $tipoFiltro6 = "";
    public $tipoFiltro7 = "";
    public $tipoFiltro8 = "";
    public $tipoFiltro9 = "";
    public $tipoFiltro10 = "";
    public $tipoOrden = "";
    public $tipoOrden2 = "";
    public $tipoOrden3 = "";
    public $tipoOrden4 = "";
    public $tipoOrden5 = "";
    public $tipoOrden6 = "";
    public $tipoOrden7 = "";
    public $tipoOrden8 = "";
    public $tipoOrden9 = "";
    public $tipoOrden10 = "";
    public $cadena = "";
    public $cadena2 = "";
    public $cadena3 = "";
    public $cadena4 = "";
    public $cadena5 = "";
    public $cadena6 = "";
    public $cadena7 = "";
    public $cadena8 = "";
    public $cadena9 = "";
    public $cadena10 = "";
    public $cadenaOrden = "";
    public $cadenaOrden2 = "";
    public $cadenaOrden3 = "";
    public $cadenaOrden4 = "";
    public $cadenaOrden5 = "";
    public $cadenaOrden6 = "";
    public $cadenaOrden7 = "";
    public $cadenaOrden8 = "";
    public $cadenaOrden9 = "";
    public $cadenaOrden10 = "";
    public $id = 0;
    public $id2 = 0;
    public $id3 = 0;
    public $id4 = 0;
    public $id5 = 0;
    public $id6 = 0;
    public $id7 = 0;
    public $id8 = 0;
    public $id9 = 0;
    public $id10 = 0;
    public $numeroParametros = 0;
    public $parametros = array();
    public $sqlParametros = array();
    public $tipoParametros = array();
    public $nombreParametros = array();
    public $valorParametros = array();
    public $tipoFiltroParametros = array();
    public $tipoOrdenParametros = array();
    public $cadenaParametros = array();
    public $cadenaOrdenParametros = array();
    public $cadenaFiltroParametros = array();
    public $cadenaFiltroParametros2 = array();
    public $cadenaFiltroParametros3 = array();
    public $cadenaFiltroParametros4 = array();
    public $cadenaFiltroParametros5 = array();
    public $cadenaFiltroParametros6 = array();
    public $cadenaFiltroParametros7 = array();
    public $cadenaFiltroParametros8 = array();
    public $cadenaFiltroParametros9 = array();
    public $cadenaFiltroParametros10 = array();
    public $numeroFiltroParametros = array();
    public $numeroOrdenParametros = array();
    public $totalFilasAfectadas = 0;
    public $ultimoId = 0;
    public $sentencia = "";
    public $sentenciasFuncion = "";

    function __construct() {}

    function setSentencias($fn)
    {
        $this->sentenciasFuncion = $fn;
    }

    function parametros($param)
    {
        if (is_array($param)) {
            return $param;
        }
        return explode('*', (string)$param);
    }

    function consulta($sql, $obBD = null)
    {
        $this->sql = $sql;
        $link = null;
        $dbObj = null;
        if ($obBD === null) {
            $dbObj = new MysqlConexion;
            $link = $dbObj->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $dbObj = $obBD;
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        $t_start = microtime(true);
        $result = @mysqli_query($link, $this->sql);
        $duration = microtime(true) - $t_start;
        if (!$result) {
            $err = $link ? mysqli_error($link) : 'No connection';
            if ($dbObj) $dbObj->Error = $err;
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sql, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            return false;
        }
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sql, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return $result;
    }

    function fetch_assoc($result)
    {
        if ($result) {
            return mysqli_fetch_assoc($result);
        }
        return false;
    }

    function fetch_row($result)
    {
        if ($result) {
            return mysqli_fetch_row($result);
        }
        return false;
    }

    function num_rows($result)
    {
        if ($result) {
            return mysqli_num_rows($result);
        }
        return 0;
    }

    function free_result($result)
    {
        if ($result && is_object($result) && $result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
    }

    function query($sql, $obBD = null)
    {
        return $this->consulta($sql, $obBD);
    }

    function getResult($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->consultasobBD($sen_sql, $param, $obBD);
    }

    function operacionobBD($sen_sql, $param, $obBD = null)
    {
        $this->sentencia = $this->sql($sen_sql, $param);
        $link = null;
        $dbObj = null;
        if ($obBD === null) {
            $dbObj = new MysqlConexion;
            $link = $dbObj->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $dbObj = $obBD;
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        $t_start = microtime(true);
        $result = @mysqli_query($link, $this->sentencia);
        $duration = microtime(true) - $t_start;
        if (!$result) {
            $err = $link ? mysqli_error($link) : 'No connection';
            if ($dbObj) $dbObj->Error = $err;
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            return false;
        }
        $this->totalFilasAfectadas = mysqli_affected_rows($link);
        $this->ultimoId = mysqli_insert_id($link);
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return true;
    }

    function consultasobBD($sen_sql, $param, $obBD = null)
    {
        $this->sentencia = $this->sql($sen_sql, $param);
        $link = null;
        $dbObj = null;
        if ($obBD === null) {
            $dbObj = new MysqlConexion;
            $link = $dbObj->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $dbObj = $obBD;
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        $t_start = microtime(true);
        $result = @mysqli_query($link, $this->sentencia);
        $duration = microtime(true) - $t_start;
        if (!$result) {
            $err = $link ? mysqli_error($link) : 'No connection';
            if ($dbObj) $dbObj->Error = $err;
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            return false;
        }
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return $result;
    }

    function sql($sen_sql, $param)
    {
        $param_arr = is_array($param) ? $param : explode('*', (string)$param);
        if (!empty($this->sentenciasFuncion) && function_exists($this->sentenciasFuncion)) {
            $fn = $this->sentenciasFuncion;
            return $fn($sen_sql, $param_arr);
        }
        if (function_exists('sentencias_cli')) {
            return sentencias_cli($sen_sql, $param_arr);
        }
        if (function_exists('sentencias_log')) {
            return sentencias_log($sen_sql, $param_arr);
        }
        if (function_exists('sentencias_cnt')) {
            return sentencias_cnt($sen_sql, $param_arr);
        }
        return "";
    }

    function operation($sen_sql, $param, $echo = 0, $obBD = null)
    {
        $this->operacionobBD($sen_sql, $param, $obBD);
        return $this;
    }

    function inicio_transaccion($obBD = null)
    {
        $link = is_object($obBD) && isset($obBD->conexion) ? $obBD->conexion : null;
        if ($link) {
            mysqli_begin_transaction($link);
        }
    }

    function fin_transaccion($obBD = null)
    {
        $link = is_object($obBD) && isset($obBD->conexion) ? $obBD->conexion : null;
        if ($link) {
            return mysqli_commit($link);
        }
        return false;
    }

    function fin_transaccion_nomsn($obBD = null)
    {
        return $this->fin_transaccion($obBD);
    }

    function deshacer_transaccion($obBD = null)
    {
        $link = is_object($obBD) && isset($obBD->conexion) ? $obBD->conexion : null;
        if ($link) {
            return mysqli_rollback($link);
        }
        return false;
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
        return $r ? $r : array();
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
        return $r ? $r : array();
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
        if ($result) {
            while ($row = $this->fetch_assoc($result)) {
                $a[] = $row;
            }
            $this->free_result($result);
        }
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
        if ($result) {
            while ($row = $this->fetch_assoc($result)) {
                $a[] = $row;
            }
            $this->free_result($result);
        }
        return $a;
    }
}

#[AllowDynamicProperties]
class MysqlDatosContab extends MysqlDatos
{
    function getReportHeader($sucursal, $titulo, $subtitulo, $obBD)
    {
        return "";
    }

    function getReportFooter($sucursal, $usuario, $obBD)
    {
        return "";
    }
}
?>
