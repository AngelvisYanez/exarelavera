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
    public $Error = 0;
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
    public $val = "";
    public $val2 = "";
    public $val3 = "";
    public $val4 = "";
    public $val5 = "";
    public $val6 = "";
    public $val7 = "";
    public $val8 = "";
    public $val9 = "";
    public $val10 = "";
    public $tipo = "";
    public $tipo2 = "";
    public $tipo3 = "";
    public $tipo4 = "";
    public $tipo5 = "";
    public $tipo6 = "";
    public $tipo7 = "";
    public $tipo8 = "";
    public $tipo9 = "";
    public $tipo10 = "";
    public $nombre = "";
    public $nombre2 = "";
    public $nombre3 = "";
    public $nombre4 = "";
    public $nombre5 = "";
    public $nombre6 = "";
    public $nombre7 = "";
    public $nombre8 = "";
    public $nombre9 = "";
    public $nombre10 = "";
    public $valor = "";
    public $valor2 = "";
    public $valor3 = "";
    public $valor4 = "";
    public $valor5 = "";
    public $valor6 = "";
    public $valor7 = "";
    public $valor8 = "";
    public $valor9 = "";
    public $valor10 = "";
    public $sentencia = "";
    public $ultimoId = 0;
    public $totalFilasAfectadas = 0;
    protected $sentencias_fn = null;

    function __construct() {}

    function setSentencias($fn)
    {
        $this->sentencias_fn = $fn;
    }

    function parametros($param)
    {
        if (is_array($param)) {
            return $param;
        }
        return explode('*', (string)$param);
    }

    function insercionid($obBD = null)
    {
        $link = null;
        if ($obBD === null) {
            $link = (new MysqlConexion)->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        return $link ? mysqli_insert_id($link) : 0;
    }

    function consulta($sql, $obBD = null)
    {
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
        $this->sql = $sql;
        $t_start = microtime(true);
        $result = @mysqli_query($link, $sql);
        $duration = microtime(true) - $t_start;
        if (!$result) {
            $this->Error = $link ? mysqli_errno($link) : -1;
            $err = $link ? mysqli_error($link) : 'No connection link';
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sql, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            return false;
        }
        $this->Error = 0;
        if ($link) {
            $this->ultimoId = mysqli_insert_id($link);
            $this->totalFilasAfectadas = mysqli_affected_rows($link);
        }
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sql, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return $result;
    }

    function fetch_assoc($result)
    {
        if ($result && is_object($result) && $result instanceof mysqli_result) {
            return mysqli_fetch_assoc($result);
        }
        return false;
    }

    function fetch_row($result)
    {
        if ($result && is_object($result) && $result instanceof mysqli_result) {
            return mysqli_fetch_row($result);
        }
        return false;
    }

    function num_rows($result)
    {
        if ($result && is_object($result) && $result instanceof mysqli_result) {
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

    function grabarv_registros($sql, $obBD = null)
    {
        return $this->consulta($sql, $obBD);
    }

    function liberar()
    {
        $this->Error = 0;
        $this->array = array();
        return 0;
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
            $this->Error = $link ? mysqli_errno($link) : -1;
            $err = $link ? mysqli_error($link) : 'No connection link';
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            if ($echo) {
                echo "<p><b>Error:</b> " . htmlspecialchars($err) . "</p>";
                echo "<p><b>SQL:</b> " . htmlspecialchars($this->sentencia) . "</p>";
            }
            return false;
        }
        $this->Error = 0;
        if ($link) {
            $this->ultimoId = mysqli_insert_id($link);
            $this->totalFilasAfectadas = mysqli_affected_rows($link);
        }
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return $result;
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
            $this->Error = $link ? mysqli_errno($link) : -1;
            $err = $link ? mysqli_error($link) : 'No connection link';
            DebugBar::addTransactionEvent('Run Query', array('is_success'=>false, 'error_message'=>$err, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
            return false;
        }
        $this->Error = 0;
        DebugBar::addTransactionEvent('Run Query', array('is_success'=>true, 'sql_text'=>$this->sentencia, 'duration'=>$duration) + ($dbObj ? $dbObj->getDB() : array()));
        return $result;
    }

    function sql($sen_sql, $param)
    {
        $Par_Sql = $this->parametros($param);
        if ($this->sentencias_fn && is_callable($this->sentencias_fn)) {
            $fn = $this->sentencias_fn;
            return $fn($sen_sql, $Par_Sql);
        }
        if (function_exists('sentencias')) {
            return sentencias($sen_sql, $Par_Sql);
        }
        if (function_exists('sentencias_cnt')) {
            return sentencias_cnt($sen_sql, $Par_Sql);
        }
        if (function_exists('sentencias_log')) {
            return sentencias_log($sen_sql, $Par_Sql);
        }
        if (function_exists('sentencias_usr')) {
            return sentencias_usr($sen_sql, $Par_Sql);
        }
        if (function_exists('sentencias_emp')) {
            return sentencias_emp($sen_sql, $Par_Sql);
        }
        return "";
    }

    function operation($sen_sql, $param, $echo = 0, $obBD = null)
    {
        return $this->operacionobBD($sen_sql, $param, $obBD);
    }

    function inicio_transaccion($obBD = null)
    {
        $link = null;
        if ($obBD === null) {
            $link = (new MysqlConexion)->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        return $link ? mysqli_begin_transaction($link) : false;
    }

    function fin_transaccion($obBD = null)
    {
        $link = null;
        if ($obBD === null) {
            $link = (new MysqlConexion)->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        return $link ? mysqli_commit($link) : false;
    }

    function fin_transaccion_nomsn($obBD = null)
    {
        return $this->fin_transaccion($obBD);
    }

    function deshacer_transaccion($obBD = null)
    {
        $link = null;
        if ($obBD === null) {
            $link = (new MysqlConexion)->conexion;
        } elseif (is_object($obBD) && isset($obBD->conexion)) {
            $link = $obBD->conexion;
        } elseif (is_object($obBD) && ($obBD instanceof mysqli)) {
            $link = $obBD;
        }
        return $link ? mysqli_rollback($link) : false;
    }

    function getRowConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $row = $this->fetch_assoc($result);
        $this->free_result($result);
        return is_array($row) ? $row : array();
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
        $row = $this->fetch_assoc($result);
        $this->free_result($result);
        return is_array($row) ? $row : array();
    }

    function getArrayConsulta($sen_sql, $param, $obBD = null)
    {
        $result = $this->consultasobBD($sen_sql, $param, $obBD);
        $arr = array();
        while ($row = $this->fetch_assoc($result)) {
            $arr[] = $row;
        }
        $this->free_result($result);
        return $arr;
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
        $arr = array();
        while ($row = $this->fetch_assoc($result)) {
            $arr[] = $row;
        }
        $this->free_result($result);
        return $arr;
    }

    function getReportHeader($sucursal, $titulo, $subtitulo, $obBD)
    {
        return "";
    }

    function getReportFooter($sucursal, $usuario, $obBD)
    {
        return "";
    }
}

#[AllowDynamicProperties]
class MysqlDatosContab extends MysqlDatos {}
